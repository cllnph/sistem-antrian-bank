param([string]$JavaHome='C:\Program Files\Android\Android Studio\jbr')
$ErrorActionPreference='Stop'
$target=Join-Path $PSScriptRoot '.build\tests'; New-Item -ItemType Directory -Path $target -Force|Out-Null
$sources=@(Get-ChildItem -LiteralPath (Join-Path $PSScriptRoot 'app\src\main\java\id\bankdptaspen\printagent\core') -Filter '*.java'|Select-Object -ExpandProperty FullName)
$sources+=@(Get-ChildItem -LiteralPath (Join-Path $PSScriptRoot 'tests') -Filter '*.java' -Recurse|Select-Object -ExpandProperty FullName)
& (Join-Path $JavaHome 'bin\javac.exe') -encoding UTF-8 --release 8 -d $target @sources
if($LASTEXITCODE -ne 0){throw 'Android core compilation failed'}
& (Join-Path $JavaHome 'bin\java.exe') -cp $target id.bankdptaspen.printagent.core.CoreTests
if($LASTEXITCODE -ne 0){throw 'Android core tests failed'}
