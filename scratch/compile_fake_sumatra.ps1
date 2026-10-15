param([string]$OutputPath)
$ErrorActionPreference = 'Stop'
Add-Type -TypeDefinition @"
using System;
using System.IO;
public class FakeSumatra {
 public static int Main(string[] args) {
  File.WriteAllLines(Environment.GetEnvironmentVariable("PRINT_FAKE_ARGUMENTS"), args);
  if(Environment.GetEnvironmentVariable("PRINT_FAKE_TIMEOUT")=="1") System.Threading.Thread.Sleep(30000);
  return 0;
 }
}
"@ -OutputAssembly $OutputPath -OutputType ConsoleApplication
