[CmdletBinding()]
param([string]$JavaHome = 'C:\Program Files\Android\Android Studio\jbr')
$ErrorActionPreference='Stop'
$root=$PSScriptRoot
$sdk=Join-Path $root '.sdk'
$android=Join-Path $sdk 'platforms\android-35\android.jar'
$buildTools=Join-Path $sdk 'build-tools\35.0.0'
if(-not(Test-Path -LiteralPath $android)){throw 'Run python android-print-agent/tools/prepare_sdk.py first.'}
if(-not(Test-Path -LiteralPath (Join-Path $JavaHome 'bin\javac.exe'))){throw 'A JDK 17+ installation is required.'}
$work=Join-Path $root '.build'
$classes=Join-Path $work 'classes';$dex=Join-Path $work 'dex';$generated=Join-Path $work 'generated'
foreach($dir in @($work,$classes,$dex,$generated)){New-Item -ItemType Directory -Path $dir -Force|Out-Null}
function Invoke-BuildTool([string]$File,[string[]]$Arguments){& $File @Arguments;if($LASTEXITCODE -ne 0){throw ('Build failed: '+[IO.Path]::GetFileName($File))}}
$base=Join-Path $work 'base.apk'
Invoke-BuildTool (Join-Path $buildTools 'aapt2.exe') @('link','-I',$android,'--manifest',(Join-Path $root 'app\src\main\AndroidManifest.xml'),'--java',$generated,'-o',$base)
$sources=@(Get-ChildItem -LiteralPath (Join-Path $root 'app\src\main\java') -Recurse -Filter '*.java'|Select-Object -ExpandProperty FullName)
$argsFile=Join-Path $work 'sources.txt'
[IO.File]::WriteAllLines($argsFile,($sources|ForEach-Object{'"'+($_ -replace '\\','/')+'"'}),[Text.UTF8Encoding]::new($false))
Invoke-BuildTool (Join-Path $JavaHome 'bin\javac.exe') @('-encoding','UTF-8','--release','8','-classpath',$android,'-d',$classes,('@'+$argsFile))
$jar=Join-Path $work 'classes.jar'
Invoke-BuildTool (Join-Path $JavaHome 'bin\jar.exe') @('cf',$jar,'-C',$classes,'.')
Invoke-BuildTool (Join-Path $JavaHome 'bin\java.exe') @('-cp',(Join-Path $buildTools 'lib\d8.jar'),'com.android.tools.r8.D8','--min-api','26','--lib',$android,'--output',$dex,$jar)
Invoke-BuildTool (Join-Path $JavaHome 'bin\jar.exe') @('uf',$base,'-C',$dex,'classes.dex')
$aligned=Join-Path $work 'aligned.apk'
Invoke-BuildTool (Join-Path $buildTools 'zipalign.exe') @('-f','4',$base,$aligned)
$signing=Join-Path $root '.signing';New-Item -ItemType Directory -Path $signing -Force|Out-Null
. (Join-Path $root '..\agent\Agent.Common.ps1')
Protect-AgentDirectory $signing
$keystore=Join-Path $signing 'bank-agent.p12';$passwordFile=Join-Path $signing 'password.txt'
if(-not(Test-Path -LiteralPath $keystore)){
    $random=New-Object byte[] 32;$rng=[Security.Cryptography.RandomNumberGenerator]::Create();try{$rng.GetBytes($random)}finally{$rng.Dispose()}
    [IO.File]::WriteAllText($passwordFile,[Convert]::ToBase64String($random),[Text.Encoding]::ASCII)
    Invoke-BuildTool (Join-Path $JavaHome 'bin\keytool.exe') @('-genkeypair','-keystore',$keystore,'-storetype','PKCS12','-storepass:file',$passwordFile,'-alias','bank-agent','-keyalg','RSA','-keysize','3072','-validity','3650','-dname','CN=Bank DP Taspen Queue Print Agent, O=Bank DP Taspen, C=ID')
}
$output=Join-Path $root '..\storage\app\private\android';New-Item -ItemType Directory -Path $output -Force|Out-Null
$apk=[IO.Path]::GetFullPath((Join-Path $output 'bank-dp-taspen-print-agent.apk'))
$staged=[IO.Path]::GetFullPath((Join-Path $output 'verified-next.apk'))
Invoke-BuildTool (Join-Path $JavaHome 'bin\java.exe') @('-jar',(Join-Path $buildTools 'lib\apksigner.jar'),'sign','--ks',$keystore,'--ks-pass',('file:'+$passwordFile),'--ks-key-alias','bank-agent','--out',$staged,$aligned)
Invoke-BuildTool (Join-Path $JavaHome 'bin\java.exe') @('-jar',(Join-Path $buildTools 'lib\apksigner.jar'),'verify','--verbose',$staged)
if([IO.File]::Exists($apk)){
    $previous=$apk+'.previous'
    [IO.File]::Replace($staged,$apk,$previous)
    if([IO.File]::Exists($previous)){[IO.File]::Delete($previous)}
}else{[IO.File]::Move($staged,$apk)}
(Get-FileHash -LiteralPath $apk -Algorithm SHA256).Hash.ToLowerInvariant()|Set-Content -LiteralPath ($apk+'.sha256') -Encoding ASCII
Write-Output ('APK built and verified: '+[IO.Path]::GetFullPath($apk))
