$ErrorActionPreference='Stop'
$root=[IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))
$private=Join-Path $PSScriptRoot 'device-history-audit\private'
New-Item -ItemType Directory -Path $private -Force | Out-Null
. (Join-Path $root 'agent\Agent.Common.ps1')
Protect-AgentDirectory $private
$database=Join-Path $private 'app.sqlite'
if(-not(Test-Path -LiteralPath $database)){[IO.File]::WriteAllBytes($database,[byte[]]@())}
$env:APP_ENV='testing';$env:DB_CONNECTION='sqlite';$env:DB_DATABASE=$database;$env:DB_URL=''
$env:KIOSK_TEST_MODE_ENABLED='true';$env:PRINT_TEST_FAKE='true';$env:SESSION_DRIVER='database';$env:CACHE_STORE='array'
& 'D:\php\php.exe' (Join-Path $PSScriptRoot 'setup_device_history_audit.php')
if($LASTEXITCODE -ne 0){throw 'Audit fixture setup failed'}
$router=Join-Path $root 'vendor\laravel\framework\src\Illuminate\Foundation\resources\server.php'
$server=Start-Process -FilePath 'D:\php\php.exe' -ArgumentList @('-S','127.0.0.1:7102',('"'+$router+'"')) -WorkingDirectory (Join-Path $root 'public') -WindowStyle Hidden -PassThru -RedirectStandardOutput (Join-Path $private 'server.log') -RedirectStandardError (Join-Path $private 'server-error.log')
$server.Id | Set-Content -LiteralPath (Join-Path $private 'server.pid')
Write-Output ('Isolated read-only browser audit server started: '+$server.Id)
