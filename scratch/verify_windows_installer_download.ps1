$ErrorActionPreference = 'Stop'
$root = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))
$private = Join-Path $PSScriptRoot 'branch-agent-audit\private'
$version = (Get-Content -LiteralPath (Join-Path $root 'agent\VERSION.txt') -Raw).Trim()
$package = Join-Path $private ('windows-v' + $version)
$setup = Get-Content -LiteralPath (Join-Path $package 'setup.json') -Raw | ConvertFrom-Json
if ($setup.server_url -ne 'http://127.0.0.1:7102' -or $setup.branch_code -ne '002' -or $setup.installer_version -ne $version) { throw 'Isolated installer fixture required.' }
$agentId = [Guid]::Parse($setup.agent_id).ToString()
$agentBase = Join-Path $env:LOCALAPPDATA 'BankDPTaspenPrintAgent'
$directory = [IO.Path]::GetFullPath((Join-Path $agentBase $agentId))
$startup = Join-Path ([Environment]::GetFolderPath('Startup')) ('Bank DP Taspen Agent ' + $agentId + '.lnk')
if ((Test-Path -LiteralPath $directory) -or (Test-Path -LiteralPath $startup)) { throw 'Fresh fixture agent required; no existing installation will be overwritten.' }
$defaultBefore = (Get-CimInstance Win32_Printer | Where-Object Default).Name
try {
    & powershell.exe -NoProfile -NonInteractive -ExecutionPolicy Bypass -File (Join-Path $package 'Install-Agent.ps1') -PrinterName 'ITPRINTER (L6290 Series)' -NoStart
    if ($LASTEXITCODE -ne 0) { throw 'Downloaded installer failed.' }
    if (-not (Test-Path -LiteralPath (Join-Path $directory 'credential.dpapi'))) { throw 'DPAPI credential not installed.' }
    if (-not (Test-Path -LiteralPath $startup)) { throw 'Startup shortcut not installed.' }
    $shortcut = (New-Object -ComObject WScript.Shell).CreateShortcut($startup)
    if ($shortcut.WindowStyle -ne 7 -or $shortcut.Arguments -notlike '*-WindowStyle Hidden*Start-Agent.ps1*') { throw 'Startup must be hidden.' }
    $acl = (Get-Item -LiteralPath $directory).GetAccessControl()
    if (-not $acl.AreAccessRulesProtected) { throw 'Private folder must be protected.' }
    $installedSetup = Get-Content -LiteralPath (Join-Path $package 'setup.json') -Raw | ConvertFrom-Json
    if ($installedSetup.enrollment_key) { throw 'Consumed enrollment key must be removed.' }
    & powershell.exe -NoProfile -NonInteractive -ExecutionPolicy Bypass -File (Join-Path $directory 'Start-Agent.ps1') -Once
    if ($LASTEXITCODE -ne 0) { throw 'Installed agent failed to start.' }
    if ((Get-CimInstance Win32_Printer | Where-Object Default).Name -ne $defaultBefore) { throw 'Default printer changed.' }
    & python (Join-Path $PSScriptRoot 'verify_windows_installer_status.py')
    if ($LASTEXITCODE -ne 0) { throw 'Heartbeat or native admin status verification failed.' }
    Write-Output 'PASS downloaded installer: standard-user installation, protected credentials, hidden startup, heartbeat and Siap status. No physical printing.'
} finally {
    # Remove only this generated fixture installation. Never touch live agents.
    $expectedDirectory = [IO.Path]::GetFullPath((Join-Path $agentBase $agentId))
    if ($directory -ne $expectedDirectory -or -not $directory.StartsWith([IO.Path]::GetFullPath($agentBase) + '\', [StringComparison]::OrdinalIgnoreCase)) { throw 'Unsafe fixture cleanup path.' }
    if (Test-Path -LiteralPath $startup) { Remove-Item -LiteralPath $startup }
    if (Test-Path -LiteralPath $directory) { Remove-Item -LiteralPath $directory -Recurse -Force }
}
