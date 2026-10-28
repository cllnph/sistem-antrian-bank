[CmdletBinding()]
param([string]$PrinterName = '', [switch]$NoStart, [switch]$NoStartup)
$ErrorActionPreference = 'Stop'
$stage = 'Memeriksa paket'
$logPath = Join-Path ([IO.Path]::GetTempPath()) ('BankDPTaspen-Install-' + (Get-Date -Format 'yyyyMMdd-HHmmss') + '.log')
$agentRoot = [IO.Path]::GetFullPath((Join-Path $env:LOCALAPPDATA 'BankDPTaspenPrintAgent'))
$stagingDirectory = $null; $backupDirectory = $null; $directory = $null; $stop = $null
$mutex = $null; $owns = $false; $client = $null; $switched = $false; $activated = $false; $activationUncertain = $false
$hadInstallation = $false; $stoppedExisting = $false; $startupPath = $null; $previousStartup = $null
$installMutex=$null; $ownsInstall=$false; $watchMutex=$null; $ownsWatch=$false; $watchStop=$null

function Assert-ManagedDirectory([string]$Path) {
    $resolved = [IO.Path]::GetFullPath($Path)
    if (-not $resolved.StartsWith($agentRoot + [IO.Path]::DirectorySeparatorChar, [StringComparison]::OrdinalIgnoreCase)) { throw 'Folder pemasangan di luar direktori penghubung.' }
    return $resolved
}
function Remove-ManagedDirectory([string]$Path) {
    $checked = Assert-ManagedDirectory $Path
    if (Test-Path -LiteralPath $checked) { Remove-Item -LiteralPath $checked -Recurse -Force }
}
function Show-InstallStage([string]$Text) {
    $script:stage = $Text
    Write-Host $Text
    Add-Content -LiteralPath $logPath -Value ((Get-Date -Format o) + ' ' + $Text) -Encoding UTF8
}

try {
    Show-InstallStage 'Memeriksa paket'
    if (-not [Environment]::Is64BitOperatingSystem -or -not [Environment]::Is64BitProcess -or [Environment]::OSVersion.Version.Major -lt 10) { throw 'Gunakan Windows 10/11 64 bit dan Windows PowerShell 64 bit.' }
    foreach ($file in @('VERSION.txt','setup.json','Agent.Common.ps1','Printer.Setup.ps1','Printer.Inventory.ps1','Start-Agent.ps1','Watch-Agent.ps1','Open-Kiosk.ps1','Print-TicketPdf.ps1','Keep-PrinterDefault.ps1','CheckPrinter.cs','SumatraPDF.exe')) {
        if (-not (Test-Path -LiteralPath (Join-Path $PSScriptRoot $file))) { throw ('Paket tidak lengkap: ' + $file + '. Ekstrak seluruh ZIP terbaru ke folder baru.') }
    }
    $installerVersion = (Get-Content -LiteralPath (Join-Path $PSScriptRoot 'VERSION.txt') -Raw -Encoding UTF8).Trim()
    Write-Host ('Penghubung Cetak Bank DP Taspen - installer v' + $installerVersion)
    Write-Host ('Folder paket: ' + $PSScriptRoot)
    . (Join-Path $PSScriptRoot 'Agent.Common.ps1')
    . (Join-Path $PSScriptRoot 'Printer.Setup.ps1')
    $setup = Get-Content -LiteralPath (Join-Path $PSScriptRoot 'setup.json') -Raw -Encoding UTF8 | ConvertFrom-Json
    if (-not ($setup.PSObject.Properties.Name -contains 'installer_version') -or $setup.installer_version -ne $installerVersion) { throw 'Versi berkas installer tidak cocok. Ekstrak ZIP terbaru ke folder baru; jangan gabungkan dengan paket lama.' }
    Assert-AgentServerUrl $setup.server_url ([bool]$setup.allow_http)
    $guid = [Guid]::Empty
    if (-not [Guid]::TryParse($setup.agent_id, [ref]$guid)) { throw 'ID penghubung tidak valid.' }
    $installMutex=[Threading.Mutex]::new($false,'Local\BankDPTaspenAgentInstall.'+$setup.agent_id)
    try { $ownsInstall=$installMutex.WaitOne(0) } catch [Threading.AbandonedMutexException] { $ownsInstall=$true }
    if (-not $ownsInstall) { throw 'Pemasangan penghubung ini sedang berjalan. Tunggu jendela pemasangan sebelumnya selesai.' }
    if (-not ($setup.PSObject.Properties.Name -contains 'paper_profile')) { $setup | Add-Member NoteProperty paper_profile 'a4' }
    if (-not $setup.enrollment_key -or $setup.enrollment_key -notmatch '^[a-f0-9]{64}$') { throw 'Kunci pemasangan sudah dipakai atau tidak valid. Unduh installer baru.' }
    # Server preflight enforces expiry; a misconfigured PC clock must not reject a valid package.

    Show-InstallStage ('Memeriksa koneksi ke ' + $setup.server_url)
    $client = New-AgentHttpClient $setup
    $preflight = Invoke-AgentRequest $client $setup '/api/print-agent/preflight' @{agent_id=$setup.agent_id;enrollment_key=$setup.enrollment_key}
    if (-not $preflight.ok -or $preflight.application -ne 'bank-dp-taspen-print-agent') { throw 'Alamat ini bukan layanan penghubung aplikasi antrean. Periksa alamat lengkap termasuk subfolder.' }

    Show-InstallStage 'Memilih printer pada PC ini'
    if ($PrinterName) {
        $PrinterName = Resolve-AgentPrinterSelection $PrinterName @(Get-AgentSelectablePrinters)
    } else {
        $choice = Show-AgentPrinterSettings ([string]$setup.printer_name) ([string]$setup.branch_code) ([string]$setup.paper_profile)
        $PrinterName = $choice.PrinterName
        $setup.paper_profile = $choice.PaperProfile
    }

    Show-InstallStage 'Menyiapkan komponen tanpa mengubah instalasi lama'
    New-Item -ItemType Directory -Path $agentRoot -Force | Out-Null
    $directory = Assert-ManagedDirectory (Join-Path $agentRoot $setup.agent_id)
    $stagingDirectory = Assert-ManagedDirectory (Join-Path $agentRoot ('.staging-' + $setup.agent_id + '-' + [Guid]::NewGuid().ToString('N')))
    $backupDirectory = Assert-ManagedDirectory (Join-Path $agentRoot ('.backup-' + $setup.agent_id + '-' + [Guid]::NewGuid().ToString('N')))
    New-Item -ItemType Directory -Path $stagingDirectory | Out-Null
    Protect-AgentDirectory $stagingDirectory
    foreach ($file in @('VERSION.txt','Start-Agent.ps1','Watch-Agent.ps1','Open-Kiosk.ps1','Printer.Setup.ps1','Printer.Inventory.ps1','Agent.Common.ps1','Print-TicketPdf.ps1','Keep-PrinterDefault.ps1','SumatraPDF.exe')) {
        Copy-Item -LiteralPath (Join-Path $PSScriptRoot $file) -Destination (Join-Path $stagingDirectory $file)
    }
    $sumatra = Join-Path $stagingDirectory 'SumatraPDF.exe'
    if ((Get-FileHash -LiteralPath $sumatra -Algorithm SHA256).Hash -ne '719F689B34F47BE8CA105CE8484948474DAFDE0E106BAB599E4A89326070C3D0' -or (Get-AuthenticodeSignature -LiteralPath $sumatra).Status -ne 'Valid') { throw 'SumatraPDF gagal diverifikasi. Unduh ulang paket resmi.' }
    Add-Type -TypeDefinition (Get-Content -LiteralPath (Join-Path $PSScriptRoot 'CheckPrinter.cs') -Raw) -OutputAssembly (Join-Path $stagingDirectory 'CheckPrinter.exe') -OutputType ConsoleApplication
    $config = [pscustomobject]@{server_url=$setup.server_url;agent_id=$setup.agent_id;branch_code=$setup.branch_code;device_id=[Guid]::NewGuid().ToString();printer_name=$PrinterName;paper_profile=$setup.paper_profile;installer_version=$installerVersion;allow_http=[bool]$setup.allow_http;activation_pending=$true}
    $status = Get-AgentPrinterStatus $config $stagingDirectory
    if (-not $status.ready) { throw $status.error }
    $candidate = Invoke-AgentRequest $client $config '/api/print-agent/prepare' @{agent_id=$setup.agent_id;enrollment_key=$setup.enrollment_key;device_id=$config.device_id;printer_name=$PrinterName;paper_profile=$config.paper_profile;machine_name=$env:COMPUTERNAME}
    $candidate.token | ConvertTo-SecureString -AsPlainText -Force | ConvertFrom-SecureString | Set-Content -LiteralPath (Join-Path $stagingDirectory 'credential.dpapi') -Encoding ASCII
    Write-AgentJson (Join-Path $stagingDirectory 'config.json') $config

    Show-InstallStage 'Mengganti instalasi setelah seluruh persiapan berhasil'
    $hadInstallation = Test-Path -LiteralPath $directory
    $stop = Join-Path $directory 'stop.request'
    $watchStop=Join-Path $agentRoot ('.stop-'+$setup.agent_id)
    if ($hadInstallation) { Set-Content -LiteralPath $watchStop -Value 'Reinstall'; Set-Content -LiteralPath $stop -Value 'Reinstall'; $stoppedExisting=$true }
    $watchMutex=[Threading.Mutex]::new($false,'Local\BankDPTaspenAgentWatch.'+$setup.agent_id)
    try { $ownsWatch=$watchMutex.WaitOne(45000) } catch [Threading.AbandonedMutexException] { $ownsWatch=$true }
    if (-not $ownsWatch) { throw 'Pengawas penghubung masih menunggu cetakan selesai. Coba lagi setelah proses cetak selesai.' }
    $mutex = [Threading.Mutex]::new($false, 'Local\BankDPTaspenPrintAgent.' + $setup.agent_id)
    try { $owns=$mutex.WaitOne(45000) } catch [Threading.AbandonedMutexException] { $owns=$true }
    if (-not $owns) { throw 'Penghubung masih mencetak. Tunggu hasilnya sebelum memasang ulang.' }
    if ($hadInstallation) {
        $journal = Join-Path $directory 'journal'
        if (Test-Path -LiteralPath $journal) { Copy-Item -LiteralPath $journal -Destination (Join-Path $stagingDirectory 'journal') -Recurse }
        Move-Item -LiteralPath (Assert-ManagedDirectory $directory) -Destination (Assert-ManagedDirectory $backupDirectory)
    }
    try { Move-Item -LiteralPath (Assert-ManagedDirectory $stagingDirectory) -Destination (Assert-ManagedDirectory $directory); $switched=$true }
    catch {
        if (Test-Path -LiteralPath $backupDirectory) { Move-Item -LiteralPath (Assert-ManagedDirectory $backupDirectory) -Destination (Assert-ManagedDirectory $directory) }
        throw
    }
    $startupPath = Join-Path ([Environment]::GetFolderPath('Startup')) ('Bank DP Taspen Agent ' + $setup.agent_id + '.lnk')
    if (Test-Path -LiteralPath $startupPath) { $previousStartup = [IO.File]::ReadAllBytes($startupPath) }
    $shortcut = (New-Object -ComObject WScript.Shell).CreateShortcut($startupPath)
    $shortcut.TargetPath = "$env:SystemRoot\System32\WindowsPowerShell\v1.0\powershell.exe"
    $shortcut.Arguments = '-NoProfile -NonInteractive -WindowStyle Hidden -ExecutionPolicy Bypass -File "' + (Join-Path $directory 'Watch-Agent.ps1') + '"'
    $shortcut.WorkingDirectory=$directory; $shortcut.WindowStyle=7
    if (-not $NoStartup) { $shortcut.Save() }

    Show-InstallStage 'Mengaktifkan kredensial pemasangan baru'
    try {
        Invoke-AgentRequest $client $config '/api/print-agent/activate-enrollment' @{agent_id=$config.agent_id;device_id=$config.device_id;token=$candidate.token} | Out-Null
        $activated=$true; $config.activation_pending=$false
        Write-AgentJson (Join-Path $directory 'config.json') $config
    } catch {
        if ($_.Exception.Data['HttpStatus'] -and [int]$_.Exception.Data['HttpStatus'] -lt 500) { throw }
        $activationUncertain=$true
        Write-Host 'Konfirmasi aktivasi belum diterima. Program akan mencoba lagi memakai kredensial yang sama; tidak mencetak ulang tugas lama.'
    }
    $setup.enrollment_key=$null
    try { Write-AgentJson (Join-Path $PSScriptRoot 'setup.json') $setup } catch { Write-Host 'Paket selesai dipakai. Hapus ZIP dan folder hasil ekstraksi untuk membersihkan kunci sementara.' }
    if ($activated -and (Test-Path -LiteralPath $backupDirectory)) { Remove-ManagedDirectory $backupDirectory }
    Write-Host 'Penghubung terpasang. Setelah printer siap, izin paket admin mengaktifkan cetak otomatis. Gunakan shortcut Buka Kiosk pada PC ini.'
    Write-Host ('Log pemasangan: ' + $logPath)
} catch {
    $failureMessage = $_.Exception.Message
    Add-Content -LiteralPath $logPath -Value ((Get-Date -Format o) + ' GAGAL tahap ' + $stage + ': ' + $failureMessage) -Encoding UTF8
    if ($switched -and -not $activated -and -not $activationUncertain) {
        Remove-ManagedDirectory $directory
        if (Test-Path -LiteralPath $backupDirectory) { Move-Item -LiteralPath (Assert-ManagedDirectory $backupDirectory) -Destination (Assert-ManagedDirectory $directory) }
        if ($startupPath -and -not $NoStartup) {
            if ($previousStartup) { [IO.File]::WriteAllBytes($startupPath, $previousStartup) }
            elseif (Test-Path -LiteralPath $startupPath) { Remove-Item -LiteralPath $startupPath }
        }
    }
    if ($stop -and (Test-Path -LiteralPath $stop)) { Remove-Item -LiteralPath $stop }
    if ($owns) { $mutex.ReleaseMutex(); $owns=$false }
    if ($watchStop -and (Test-Path -LiteralPath $watchStop)) { Remove-Item -LiteralPath $watchStop }
    if ($ownsWatch) { $watchMutex.ReleaseMutex(); $ownsWatch=$false }
    if ($stoppedExisting -and -not $NoStart -and -not $activated -and (Test-Path -LiteralPath (Join-Path $directory 'config.json'))) {
        $recover=Join-Path $directory 'Watch-Agent.ps1'; if (-not (Test-Path -LiteralPath $recover)) { $recover=Join-Path $directory 'Start-Agent.ps1' }
        Start-Process powershell.exe -ArgumentList @('-NoProfile','-NonInteractive','-WindowStyle','Hidden','-ExecutionPolicy','Bypass','-File',('"'+$recover+'"')) -WindowStyle Hidden | Out-Null
    }
    throw ('Pemasangan gagal pada tahap: ' + $stage + '. ' + $failureMessage + ' Log: ' + $logPath)
} finally {
    if ($client) { $client.Dispose() }
    if ($owns) { $mutex.ReleaseMutex() }
    if ($mutex) { $mutex.Dispose() }
    if ($watchStop -and (Test-Path -LiteralPath $watchStop)) { Remove-Item -LiteralPath $watchStop }
    if ($ownsWatch) { $watchMutex.ReleaseMutex() }
    if ($watchMutex) { $watchMutex.Dispose() }
    if ($ownsInstall) { $installMutex.ReleaseMutex() }
    if ($installMutex) { $installMutex.Dispose() }
    if ($stagingDirectory -and (Test-Path -LiteralPath $stagingDirectory)) { Remove-ManagedDirectory $stagingDirectory }
}
if (-not $NoStart) {
    Start-Process -FilePath $shortcut.TargetPath -ArgumentList $shortcut.Arguments -WindowStyle Hidden | Out-Null
}
if (-not $NoStartup) {
    try {
        $shell=New-Object -ComObject WScript.Shell
        $suffix=([Regex]::Replace([string]$setup.branch_code,'[^A-Za-z0-9_-]','_'))+'-'+$setup.agent_id.Substring(0,8)
        foreach ($entry in @(@('Buka Kiosk',''),@('Pilih browser kiosk',' -ChooseBrowser'),@('Ganti printer',' -Settings'))) {
            $link=$shell.CreateShortcut((Join-Path ([Environment]::GetFolderPath('Desktop')) ($entry[0]+' '+$suffix+'.lnk')))
            $link.TargetPath=$shortcut.TargetPath
            $link.Arguments='-NoProfile -STA -WindowStyle Hidden -ExecutionPolicy Bypass -File "'+(Join-Path $directory 'Open-Kiosk.ps1')+'"'+$entry[1]
            $link.WorkingDirectory=$directory; $link.WindowStyle=7; $link.Save()
        }
    } catch { Write-Host 'Penghubung sudah terpasang. Shortcut desktop belum dapat dibuat; jalankan Open-Kiosk.ps1 dari folder penghubung.' }
}
if (-not $NoStart) {
    Start-Process -FilePath $shortcut.TargetPath -ArgumentList @('-NoProfile','-STA','-WindowStyle','Hidden','-ExecutionPolicy','Bypass','-File',('"'+(Join-Path $directory 'Open-Kiosk.ps1')+'"'),'-ChooseBrowser') -WindowStyle Hidden | Out-Null
}
