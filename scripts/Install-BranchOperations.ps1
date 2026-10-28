param([string]$TaskName = 'BankDPTaspen-Operations')
$ErrorActionPreference = 'Stop'
$projectPath = Split-Path -Parent $PSScriptRoot
$sourcePath = Join-Path $PSScriptRoot 'native\RunBranchOperations.cs'
$toolPath = Join-Path $projectPath 'tools\BranchOperations'
$launcherPath = Join-Path $toolPath 'RunBranchOperations.exe'
$stampPath = Join-Path $toolPath 'source.sha256'
if (-not (Test-Path -LiteralPath $sourcePath -PathType Leaf)) { throw 'Source peluncur scheduler tidak ditemukan.' }
$sourceHash = (Get-FileHash -LiteralPath $sourcePath -Algorithm SHA256).Hash
New-Item -ItemType Directory -Path $toolPath -Force | Out-Null
if (-not (Test-Path -LiteralPath $launcherPath -PathType Leaf) -or -not (Test-Path -LiteralPath $stampPath -PathType Leaf) -or (Get-Content -LiteralPath $stampPath -Raw).Trim() -ne $sourceHash) {
    $temporaryPath = Join-Path $toolPath ('launcher-' + [Guid]::NewGuid().ToString('N') + '.exe')
    try {
        Add-Type -TypeDefinition (Get-Content -LiteralPath $sourcePath -Raw -Encoding UTF8) -OutputAssembly $temporaryPath -OutputType WindowsApplication
        Move-Item -LiteralPath $temporaryPath -Destination $launcherPath -Force
        Set-Content -LiteralPath $stampPath -Value $sourceHash -Encoding ASCII
    } finally {
        if (Test-Path -LiteralPath $temporaryPath -PathType Leaf) { Remove-Item -LiteralPath $temporaryPath }
    }
}
$accountName = [Security.Principal.WindowsIdentity]::GetCurrent().Name
$action = New-ScheduledTaskAction -Execute $launcherPath -WorkingDirectory $projectPath
$minuteTrigger = New-ScheduledTaskTrigger -Once -At (Get-Date).AddMinutes(1) -RepetitionInterval (New-TimeSpan -Minutes 1)
$minuteTrigger.Repetition.StopAtDurationEnd = $false
$loginTrigger = New-ScheduledTaskTrigger -AtLogOn -User $accountName
$settings = New-ScheduledTaskSettingsSet -MultipleInstances IgnoreNew -StartWhenAvailable -AllowStartIfOnBatteries -DontStopIfGoingOnBatteries -ExecutionTimeLimit (New-TimeSpan -Minutes 5) -Hidden
$principal = New-ScheduledTaskPrincipal -UserId $accountName -LogonType Interactive -RunLevel Limited
Register-ScheduledTask -TaskName $TaskName -Action $action -Trigger @($minuteTrigger, $loginTrigger) -Settings $settings -Principal $principal -Description 'Reset per cabang dan pembersihan periode antrean setiap menit, tanpa jendela konsol.' -Force | Out-Null
Start-ScheduledTask -TaskName $TaskName
Write-Output ('Scheduler terpasang untuk akun ' + $accountName + '. Jalankan server pada akun Windows ini. Pemeriksaan reset sebelum operasi tetap aktif saat tugas terlewat.')
