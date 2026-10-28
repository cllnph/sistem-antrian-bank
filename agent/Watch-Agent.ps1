[CmdletBinding()]
param([string]$DataDirectory = '')
$ErrorActionPreference='Stop'
if (-not $DataDirectory) { $DataDirectory=$PSScriptRoot }
. (Join-Path $PSScriptRoot 'Agent.Common.ps1')
$configuration=Get-Content -LiteralPath (Join-Path $DataDirectory 'config.json') -Raw -Encoding UTF8 | ConvertFrom-Json
$guid=[Guid]::Empty
if (-not [Guid]::TryParse($configuration.agent_id,[ref]$guid)) { throw 'ID penghubung tidak valid.' }
$mutex=[Threading.Mutex]::new($false,'Local\BankDPTaspenAgentWatch.'+$configuration.agent_id)
$owns=$false
$stop=Join-Path (Split-Path $DataDirectory -Parent) ('.stop-'+$configuration.agent_id)
try {
    try { $owns=$mutex.WaitOne(0) } catch [Threading.AbandonedMutexException] { $owns=$true }
    if (-not $owns -or (Test-Path -LiteralPath $stop)) { exit 0 }
    $delay=2
    while (-not (Test-Path -LiteralPath $stop) -and -not (Test-Path -LiteralPath (Join-Path $DataDirectory 'stop.request'))) {
        $started=[DateTime]::UtcNow
        $process=Start-Process -FilePath (Join-Path $env:SystemRoot 'System32\WindowsPowerShell\v1.0\powershell.exe') -ArgumentList @('-NoProfile','-NonInteractive','-ExecutionPolicy','Bypass','-File',('"'+(Join-Path $DataDirectory 'Start-Agent.ps1')+'"'),'-DataDirectory',('"'+$DataDirectory+'"')) -WindowStyle Hidden -PassThru
        try {
            $process.WaitForExit()
            if ($process.ExitCode -eq 20) { Write-AgentLog $DataDirectory 'Pengawas dihentikan karena akses dicabut/tidak berlaku. Pasang ulang untuk melanjutkan.'; break }
        } finally { $process.Dispose() }
        if ((Test-Path -LiteralPath $stop) -or (Test-Path -LiteralPath (Join-Path $DataDirectory 'stop.request'))) { break }
        if (([DateTime]::UtcNow-$started).TotalSeconds -gt 60) { $delay=2 }
        Write-AgentLog $DataDirectory ('Penghubung terhenti. Memulai kembali dalam '+$delay+' detik; tugas lama tidak dicetak ulang.')
        for ($remaining=$delay; $remaining -gt 0 -and -not (Test-Path -LiteralPath $stop); $remaining--) { Start-Sleep -Seconds 1 }
        $delay=[Math]::Min(30,$delay*2)
    }
} finally { if ($owns) { $mutex.ReleaseMutex() }; $mutex.Dispose() }
