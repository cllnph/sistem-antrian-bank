$ErrorActionPreference = 'Stop'
$projectRoot = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))
$private = Join-Path $PSScriptRoot 'multi-printer-audit\private'
New-Item -ItemType Directory -Path $private -Force | Out-Null
. (Join-Path $projectRoot 'agent\Agent.Common.ps1')
Protect-AgentDirectory $private
$database = Join-Path $private 'app.sqlite'
if (-not (Test-Path -LiteralPath $database)) { [IO.File]::WriteAllBytes($database, [byte[]]@()) }
$env:APP_ENV='testing'; $env:APP_URL='http://127.0.0.1:7110'; $env:DB_CONNECTION='sqlite'; $env:DB_DATABASE=$database; $env:DB_URL=''
$env:KIOSK_TEST_MODE_ENABLED='true'; $env:PRINT_TEST_FAKE='true'; $env:SESSION_DRIVER='database'; $env:CACHE_STORE='array'
& 'D:\php\php.exe' (Join-Path $PSScriptRoot 'setup_multi_printer_audit.php')
if ($LASTEXITCODE -ne 0) { throw 'Audit fixture setup failed.' }
$router = Join-Path $projectRoot 'vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php'
$server = Start-Process -FilePath 'D:\php\php.exe' -ArgumentList @('-S','127.0.0.1:7110',('"'+$router+'"')) -WorkingDirectory (Join-Path $projectRoot 'public') -WindowStyle Hidden -PassThru -RedirectStandardOutput (Join-Path $private 'server.log') -RedirectStandardError (Join-Path $private 'server-error.log')
$server.Id | Set-Content -LiteralPath (Join-Path $private 'server.pid')
Write-Output ('Audit server started: ' + $server.Id)
