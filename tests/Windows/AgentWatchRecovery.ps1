# Isolated process fixture. Never connects to a server, printer, or Startup folder.
$ErrorActionPreference='Stop'
$root=[IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..\..'))
$testRoot=Join-Path ([IO.Path]::GetTempPath()) ('BankAgentWatchTest-'+[Guid]::NewGuid().ToString('N'))
$directory=Join-Path $testRoot ([Guid]::NewGuid().ToString())
try {
    New-Item -ItemType Directory -Path $directory -Force | Out-Null
    Copy-Item -LiteralPath (Join-Path $root 'agent\Watch-Agent.ps1') -Destination (Join-Path $directory 'Watch-Agent.ps1')
    Copy-Item -LiteralPath (Join-Path $root 'agent\Agent.Common.ps1') -Destination (Join-Path $directory 'Agent.Common.ps1')
    @{agent_id=(Split-Path $directory -Leaf)} | ConvertTo-Json | Set-Content -LiteralPath (Join-Path $directory 'config.json') -Encoding UTF8
    @'
$counter=Join-Path $PSScriptRoot 'counter.txt'
$count=if (Test-Path -LiteralPath $counter) { [int](Get-Content -LiteralPath $counter -Raw) } else { 0 }
($count+1) | Set-Content -LiteralPath $counter
if ($count -eq 0) { exit 1 }
exit 20
'@ | Set-Content -LiteralPath (Join-Path $directory 'Start-Agent.ps1') -Encoding UTF8
    $process=Start-Process -FilePath (Join-Path $env:SystemRoot 'System32\WindowsPowerShell\v1.0\powershell.exe') -ArgumentList @('-NoProfile','-NonInteractive','-ExecutionPolicy','Bypass','-File',('"'+(Join-Path $directory 'Watch-Agent.ps1')+'"')) -WindowStyle Hidden -PassThru
    try {
        if (-not $process.WaitForExit(45000)) { $process.Kill(); throw 'Supervisor fixture exceeded its deadline.' }
        if ($process.ExitCode -ne 0) { throw 'Supervisor fixture failed.' }
    } finally { $process.Dispose() }
    if ([int](Get-Content -LiteralPath (Join-Path $directory 'counter.txt') -Raw) -ne 2) { throw 'Expected one restart after a crash, then a terminal authentication stop.' }
    $log=Get-Content -LiteralPath (Join-Path $directory 'agent.log') -Raw
    if ($log -notlike '*Memulai kembali*' -or $log -notlike '*akses dicabut*') { throw 'Recovery and terminal-stop diagnostics missing.' }
    Write-Output 'PASS supervisor: restarts a crashed child, stops on revoked credentials, and never sends a print job.'
} finally {
    $resolved=[IO.Path]::GetFullPath($testRoot)
    $temporary=[IO.Path]::GetFullPath([IO.Path]::GetTempPath()).TrimEnd('\')+'\'
    if (-not $resolved.StartsWith($temporary,[StringComparison]::OrdinalIgnoreCase) -or (Split-Path $resolved -Leaf) -notlike 'BankAgentWatchTest-*') { throw 'Unsafe fixture cleanup path.' }
    if (Test-Path -LiteralPath $resolved) { Remove-Item -LiteralPath $resolved -Recurse -Force }
}
