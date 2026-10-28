$ErrorActionPreference='Stop'
$root=[IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))
$private=Join-Path $PSScriptRoot 'branch-agent-audit\private'
$fixtures=Get-Content -LiteralPath (Join-Path $private 'fixtures.json') -Raw|ConvertFrom-Json
Add-Type -TypeDefinition @'
using System; using System.IO;
public class MockSumatraAgent {
 public static int Main(string[] args) {
  var folder=AppDomain.CurrentDomain.BaseDirectory;
  File.AppendAllText(Path.Combine(folder,"native-calls.txt"),String.Join("|",args)+Environment.NewLine);
  if(File.Exists(Path.Combine(folder,"mock-timeout"))) System.Threading.Thread.Sleep(30000);
  return 0;
 }
}
'@ -OutputAssembly (Join-Path $private 'MockSumatra.exe') -OutputType ConsoleApplication
Add-Type -TypeDefinition @'
using System;
public class MockPrinterCheck { public static int Main(string[] args) { Console.WriteLine("{\"ready\":true,\"status\":0}"); return 0; } }
'@ -OutputAssembly (Join-Path $private 'MockCheck.exe') -OutputType ConsoleApplication
foreach($property in $fixtures.PSObject.Properties){
 $config=$property.Value
 $directory=Join-Path $private $property.Name
 New-Item -ItemType Directory -Path $directory -Force|Out-Null
 foreach($file in @('Start-Agent.ps1','Agent.Common.ps1')){Copy-Item -LiteralPath (Join-Path $root ('agent\'+$file)) -Destination (Join-Path $directory $file) -Force}
 foreach($file in @('Print-TicketPdf.ps1','Keep-PrinterDefault.ps1')){Copy-Item -LiteralPath (Join-Path $root ('scripts\'+$file)) -Destination (Join-Path $directory $file) -Force}
 Copy-Item -LiteralPath (Join-Path $private 'MockSumatra.exe') -Destination (Join-Path $directory 'SumatraPDF.exe') -Force
 Copy-Item -LiteralPath (Join-Path $private 'MockCheck.exe') -Destination (Join-Path $directory 'CheckPrinter.exe') -Force
 $config.token|ConvertTo-SecureString -AsPlainText -Force|ConvertFrom-SecureString|Set-Content -LiteralPath (Join-Path $directory 'credential.dpapi') -Encoding ASCII
 $config.PSObject.Properties.Remove('token')
 $config|ConvertTo-Json|Set-Content -LiteralPath (Join-Path $directory 'config.json') -Encoding UTF8
}
. (Join-Path $root 'agent\Agent.Common.ps1')
$config=[pscustomobject]@{server_url='http://127.0.0.1:7102';allow_http=$true}
$client=New-AgentHttpClient $config
Write-Output ('HTTP client: '+$client.GetType().Name)
$client.Dispose()
Write-Output 'Client fixtures use mock executables only; no physical printing.'
