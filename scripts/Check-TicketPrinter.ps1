param([Parameter(Mandatory=$true)][string]$PrinterName)
$ErrorActionPreference = 'Stop'
$checker = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..\tools\TicketPrinter\CheckPrinter.exe'))
if (-not (Test-Path -LiteralPath $checker -PathType Leaf)) { Write-Error 'Pemeriksa printer native belum terpasang.'; exit 5 }
& $checker $PrinterName
exit $LASTEXITCODE
