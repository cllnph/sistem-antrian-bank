$ErrorActionPreference = 'Stop'
$projectDirectory = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))
$sourcePath = Join-Path $PSScriptRoot 'native\CheckPrinter.cs'
$toolDirectory = Join-Path $projectDirectory 'tools\TicketPrinter'
$executablePath = Join-Path $toolDirectory 'CheckPrinter.exe'
$stampPath = Join-Path $toolDirectory 'source.sha256'
$sourceHash = (Get-FileHash -LiteralPath $sourcePath -Algorithm SHA256).Hash
New-Item -ItemType Directory -Path $toolDirectory -Force | Out-Null
if ((Test-Path -LiteralPath $executablePath) -and (Test-Path -LiteralPath $stampPath) -and (Get-Content -LiteralPath $stampPath -Raw).Trim() -eq $sourceHash) { exit 0 }
$temporaryPath = Join-Path $toolDirectory ('check-' + [Guid]::NewGuid().ToString('N') + '.exe')
try {
    Add-Type -TypeDefinition (Get-Content -LiteralPath $sourcePath -Raw) -OutputAssembly $temporaryPath -OutputType ConsoleApplication
    Move-Item -LiteralPath $temporaryPath -Destination $executablePath -Force
    Set-Content -LiteralPath $stampPath -Value $sourceHash -Encoding ASCII
    Write-Output 'Pemeriksa printer native terpasang.'
} finally {
    if (Test-Path -LiteralPath $temporaryPath) { Remove-Item -LiteralPath $temporaryPath }
}
