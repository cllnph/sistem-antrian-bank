[CmdletBinding()]
param([string]$DataDirectory = '', [switch]$Once)
$ErrorActionPreference = 'Stop'
if (-not $DataDirectory) { $DataDirectory = $PSScriptRoot }
. (Join-Path $PSScriptRoot 'Agent.Common.ps1')
$config = Get-Content -LiteralPath (Join-Path $DataDirectory 'config.json') -Raw -Encoding UTF8 | ConvertFrom-Json
$agentGuid = [Guid]::Empty
if (-not [Guid]::TryParse($config.agent_id, [ref]$agentGuid) -or $config.printer_name -match '[\x00-\x1f"]|PDF|XPS|OneNote|Fax') { throw 'Konfigurasi penghubung tidak valid.' }
$mutex = [Threading.Mutex]::new($false, 'Local\BankDPTaspenPrintAgent.' + $config.agent_id)
$ownsMutex = $false
$client = $null
$journalDirectory = Join-Path $DataDirectory 'journal'
New-Item -ItemType Directory -Path $journalDirectory -Force | Out-Null
$script:installedPrinters = @()
$script:lastPrinterInventory = [DateTime]::MinValue
$script:inventoryRead=$null
$script:inventoryStatus='unavailable'
$script:agentExit=0
if (-not ($config.PSObject.Properties.Name -contains 'paper_profile')) { $config | Add-Member NoteProperty paper_profile 'thermal80' }
$agentVersion=if (Test-Path -LiteralPath (Join-Path $DataDirectory 'VERSION.txt')) { (Get-Content -LiteralPath (Join-Path $DataDirectory 'VERSION.txt') -Raw).Trim() } else { '1.3.0' }

function Send-Heartbeat {
    if (-not $script:inventoryRead -and ([DateTime]::UtcNow - $script:lastPrinterInventory).TotalSeconds -ge 60) {
        $script:lastPrinterInventory = [DateTime]::UtcNow
        try { $script:inventoryRead=Start-AgentInventoryRead }
        catch {
            $script:inventoryStatus=if ($script:installedPrinters.Count) { 'stale' } else { 'unavailable' }
            Write-AgentLog $DataDirectory 'Pembaca inventaris belum dapat dimulai; heartbeat tetap berjalan.'
        }
    }
    if ($script:inventoryRead) {
        try {
            $inventory=Receive-AgentInventoryRead $script:inventoryRead
            if ($null -ne $inventory) {
                $script:installedPrinters=@($inventory.printers | Select-Object -First 100 -ExpandProperty Name)
                $script:inventoryStatus='ok'; $script:inventoryRead=$null
            }
        } catch {
            $script:inventoryRead=$null
            $script:inventoryStatus=if ($script:installedPrinters.Count) { 'stale' } else { 'unavailable' }
            Write-AgentLog $DataDirectory 'Inventaris printer belum terbaca; heartbeat tetap berjalan dan daftar terakhir dipertahankan.'
        }
    }
    $status = Get-AgentPrinterStatus $config $DataDirectory
    $reply = Invoke-AgentRequest $client $config '/api/print-agent/heartbeat' @{
        printer_ready=[bool]$status.ready; printer_name=$config.printer_name; paper_profile=$config.paper_profile; machine_name=$env:COMPUTERNAME; version=$agentVersion; error=$status.error; installed_printers=@($script:installedPrinters); inventory_status=$script:inventoryStatus
    }
    $script:lastHeartbeat = [DateTime]::UtcNow
    $script:canClaim = $status.ready -and $reply.enabled
    if (($reply.printer_name -and $reply.printer_name -ne $config.printer_name) -or ($reply.paper_profile -and $reply.paper_profile -ne $config.paper_profile)) {
        $script:canClaim = $false
        $target=Get-AgentPrinterStatus ([pscustomobject]@{printer_name=[string]$reply.printer_name}) $DataDirectory
        if (-not $target.ready -or $reply.printer_name -match '[\x00-\x1f"]|PDF|XPS|OneNote|Fax') {
            Write-AgentLog $DataDirectory 'Printer yang dipilih admin belum tersedia pada PC ini.'
            return
        }
        $config.printer_name = [string]$reply.printer_name
        if ($reply.paper_profile) { $config.paper_profile=[string]$reply.paper_profile }
        Write-AgentJson (Join-Path $DataDirectory 'config.json') $config
        $script:lastHeartbeat = [DateTime]::MinValue
    }
}

function Complete-Journal([string]$Path, $State) {
    $reply = Invoke-AgentRequest $client $config ('/api/print-agent/jobs/' + $State.id + '/complete') @{ status=$State.result; failure_code=$State.failure_code } $State.claim_token
    if ($reply.ok) {
        $pdf = Join-Path $journalDirectory ($State.id + '.pdf')
        if (Test-Path -LiteralPath $pdf) { Remove-Item -LiteralPath $pdf }
        Remove-Item -LiteralPath $Path
    }
}

function Recover-Journals {
    foreach ($file in @(Get-ChildItem -LiteralPath $journalDirectory -Filter '*.json')) {
        $state = Get-Content -LiteralPath $file.FullName -Raw -Encoding UTF8 | ConvertFrom-Json
        # Persisted jobs are only acknowledged; they are NEVER sent to the printer again.
        if ($state.phase -ne 'finished') {
            $state.result = if ($state.phase -eq 'started') { 'unknown' } else { 'failed' }
            $state.failure_code = 'agent_restarted'; $state.phase = 'finished'
            Write-AgentJson $file.FullName $state
        }
        try { Complete-Journal $file.FullName $state } catch {
            if ($_.Exception.Data['HttpStatus'] -eq 409) {
                # Server already conservatively expired/closed this attempt. Keep audit, not a retry.
                Move-Item -LiteralPath $file.FullName -Destination ($file.FullName + '.closed') -Force
                $pdf = Join-Path $journalDirectory ($state.id + '.pdf')
                if (Test-Path -LiteralPath $pdf) { Remove-Item -LiteralPath $pdf }
            } else { throw }
        }
    }
}

function Process-Job($Job) {
    $guid = [Guid]::Empty
    if (-not [Guid]::TryParse($Job.id, [ref]$guid) -or $Job.claim_token -notmatch '^[a-f0-9]{64}$') { throw 'Identitas job tidak valid.' }
    $path = Join-Path $journalDirectory ($Job.id + '.json')
    $pdfPath = Join-Path $journalDirectory ($Job.id + '.pdf')
    $state = [pscustomobject]@{ id=$Job.id; claim_token=$Job.claim_token; phase='claimed'; result='failed'; failure_code=$null }
    if (Test-Path -LiteralPath $path) { throw 'Job pernah diterima; pengiriman ulang diblokir.' }
    Write-AgentJson $path $state
    try {
        if ($Job.printer_name -ne $config.printer_name) { throw 'Printer job berbeda dari printer penghubung.' }
        $pdf = Invoke-AgentRequest $client $config ('/api/print-agent/jobs/' + $Job.id + '/pdf') $null $Job.claim_token -Pdf
        [IO.File]::WriteAllBytes($pdfPath, $pdf.Bytes)
        # Journal is durable BEFORE requesting the one-time permission to send.
        $state.phase = 'started'; $state.result = 'unknown'; $state.failure_code = 'start_response_uncertain'
        Write-AgentJson $path $state
        $permission = Invoke-AgentRequest $client $config ('/api/print-agent/jobs/' + $Job.id + '/start') @{ pdf_sha256=$pdf.Hash } $Job.claim_token
        if (-not $permission.ok) { throw 'Izin pengiriman cetak tidak diterima.' }
        $helper = Join-Path $DataDirectory 'Print-TicketPdf.ps1'
        $executable = Join-Path $DataDirectory 'SumatraPDF.exe'
        $arguments = @('-NoProfile','-NonInteractive','-ExecutionPolicy','Bypass','-File',('"'+$helper+'"'),'-Executable',('"'+$executable+'"'),'-PdfPath',('"'+$pdfPath+'"'),'-PrinterName',('"'+$config.printer_name+'"'))
        $process = Start-Process powershell.exe -ArgumentList $arguments -WindowStyle Hidden -PassThru
        $null = $process.Handle
        $deadline = [DateTime]::UtcNow.AddSeconds(40)
        try {
            while (-not $process.HasExited -and [DateTime]::UtcNow -lt $deadline) {
                if (([DateTime]::UtcNow - $script:lastHeartbeat).TotalSeconds -ge 5) {
                    try { Send-Heartbeat } catch { Write-AgentLog $DataDirectory 'Heartbeat terputus saat cetak; job tidak diulang.' }
                }
                Start-Sleep -Milliseconds 250; $process.Refresh()
            }
            if (-not $process.HasExited) { $process.Kill(); $state.result='unknown'; $state.failure_code='native_timeout' }
            else {
                $process.WaitForExit()
                if ($process.ExitCode -eq 0) { $state.result='submitted'; $state.failure_code=$null }
                else { $state.result='unknown'; $state.failure_code='native_exit_' + ([string]$process.ExitCode).Replace('-', 'n') }
            }
        } finally { $process.Dispose() }
    } catch {
        $state.result = if ($state.phase -eq 'started') { 'unknown' } else { 'failed' }
        if (-not $state.failure_code) { $state.failure_code = 'agent_processing_failed' }
        Write-AgentLog $DataDirectory ('Job ' + $Job.id + ': ' + $state.result)
    } finally {
        $state.phase = 'finished'
        Write-AgentJson $path $state
        if (Test-Path -LiteralPath $pdfPath) { Remove-Item -LiteralPath $pdfPath }
    }
    Complete-Journal $path $state
}

try {
    try { $ownsMutex=$mutex.WaitOne(0) } catch [Threading.AbandonedMutexException] { $ownsMutex=$true }
    if (-not $ownsMutex) { exit 0 }
    $secure = (Get-Content -LiteralPath (Join-Path $DataDirectory 'credential.dpapi') -Raw).Trim() | ConvertTo-SecureString
    $pointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure)
    try {
        $token=[Runtime.InteropServices.Marshal]::PtrToStringBSTR($pointer)
        if (($config.PSObject.Properties.Name -contains 'activation_pending') -and $config.activation_pending) {
            $activationClient = New-AgentHttpClient $config
            try {
                $confirmed = $false
                do {
                    try {
                        Invoke-AgentRequest $activationClient $config '/api/print-agent/activate-enrollment' @{agent_id=$config.agent_id;device_id=$config.device_id;token=$token} | Out-Null
                        $confirmed = $true
                    } catch {
                        Write-AgentLog $DataDirectory ('Aktivasi tertunda: ' + $_.Exception.Message)
                        if ($Once -or $_.Exception.Data['HttpStatus'] -eq 401 -or $_.Exception.Data['HttpStatus'] -eq 403) { throw }
                        Start-Sleep -Seconds 5
                    }
                    if (Test-Path -LiteralPath (Join-Path $DataDirectory 'stop.request')) { return }
                } while (-not $confirmed)
            }
            finally { $activationClient.Dispose() }
            $config.activation_pending = $false
            Write-AgentJson (Join-Path $DataDirectory 'config.json') $config
        }
        $client=New-AgentHttpClient $config $token
        $token=$null
    }
    finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($pointer) }
    $script:lastHeartbeat=[DateTime]::MinValue; $script:canClaim=$false
    if (-not $Once) {
        Add-Type -TypeDefinition @'
using System; using System.Text; using System.Runtime.InteropServices;
public static class AgentDefaultPrinter {
 [DllImport("winspool.drv", CharSet=CharSet.Unicode)] static extern bool GetDefaultPrinter(StringBuilder buffer, ref int length);
 public static string Read() { int n=0; GetDefaultPrinter(null,ref n); if(n==0)return null; var b=new StringBuilder(n); return GetDefaultPrinter(b,ref n)?b.ToString():null; }
}
'@
        $guard=@{original_printer=[AgentDefaultPrinter]::Read(); kiosk_printer=$config.printer_name; owner_pid=$PID; config_path=(Join-Path $DataDirectory 'config.json')}
        $encoded=[Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes(($guard|ConvertTo-Json -Compress)))
        Start-Process powershell.exe -WindowStyle Hidden -ArgumentList @('-NoProfile','-NonInteractive','-ExecutionPolicy','Bypass','-File',('"'+(Join-Path $DataDirectory 'Keep-PrinterDefault.ps1')+'"'),'-SettingsBase64',$encoded) | Out-Null
    }
    do {
        try {
            if (([DateTime]::UtcNow - $script:lastHeartbeat).TotalSeconds -ge 5) { Send-Heartbeat }
            Recover-Journals
            if ($script:canClaim) {
                $response=Invoke-AgentRequest $client $config '/api/print-agent/claim' @{}
                if ($response.job) { Process-Job $response.job }
            }
        } catch {
            Write-AgentLog $DataDirectory ('Koneksi/job tertunda: ' + $_.Exception.Message)
            if ($_.Exception.Data['HttpStatus'] -eq 401 -or $_.Exception.Data['HttpStatus'] -eq 403) { $script:agentExit=20; break }
            if (-not $Once) { Start-Sleep -Seconds 3 }
        }
        if (-not $Once) { Start-Sleep -Seconds 1 }
    } while (-not $Once -and -not (Test-Path -LiteralPath (Join-Path $DataDirectory 'stop.request')))
} catch {
    Write-AgentLog $DataDirectory ('Penghubung terhenti: '+$_.Exception.Message)
    $script:agentExit=if ($_.Exception.Data['HttpStatus'] -in @(401,403)) { 20 } else { 1 }
} finally {
    if ($script:inventoryRead) {
        if (-not $script:inventoryRead.Process.HasExited) { $script:inventoryRead.Process.Kill() }
        $script:inventoryRead.Process.Dispose()
    }
    if ($client) { $client.Dispose() }
    if ($ownsMutex) { $mutex.ReleaseMutex() }
    $mutex.Dispose()
}
exit $script:agentExit
