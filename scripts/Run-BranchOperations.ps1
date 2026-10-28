$ErrorActionPreference = 'Stop'
$projectPath = Split-Path -Parent $PSScriptRoot
$phpPath = if ($env:BANK_PHP_PATH) { $env:BANK_PHP_PATH } elseif (Test-Path -LiteralPath 'D:\php\php.exe') { 'D:\php\php.exe' } else { (Get-Command php.exe -ErrorAction Stop).Source }
Set-Location -LiteralPath $projectPath
# Manual CLI fallback. The scheduled task uses the native Windows launcher to avoid console flashes.
& $phpPath (Join-Path $projectPath 'artisan') schedule:run --no-interaction
exit $LASTEXITCODE
