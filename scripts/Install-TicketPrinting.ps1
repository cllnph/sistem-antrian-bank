[CmdletBinding()]
param([string]$PhpPath = 'D:\php\php.exe')
$ErrorActionPreference = 'Stop'
$projectDirectory = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))
$toolDirectory = Join-Path $projectDirectory 'tools\SumatraPDF'
$executable = Join-Path $toolDirectory 'SumatraPDF.exe'
$expectedHash = '719F689B34F47BE8CA105CE8484948474DAFDE0E106BAB599E4A89326070C3D0'
New-Item -ItemType Directory -Path $toolDirectory -Force | Out-Null
if (-not (Test-Path -LiteralPath $executable)) {
    $archive = Join-Path $toolDirectory 'portable.zip'
    Invoke-WebRequest -Uri 'https://www.sumatrapdfreader.org/dl/rel/3.6.1/SumatraPDF-3.6.1-64.zip' -OutFile $archive
    Expand-Archive -LiteralPath $archive -DestinationPath $toolDirectory -Force
    Move-Item -LiteralPath (Join-Path $toolDirectory 'SumatraPDF-3.6.1-64.exe') -Destination $executable
    Remove-Item -LiteralPath $archive
}
if ((Get-FileHash -LiteralPath $executable -Algorithm SHA256).Hash -ne $expectedHash) { throw 'Checksum SumatraPDF tidak cocok dengan versi portable 3.6.1 yang diverifikasi.' }
if ((Get-AuthenticodeSignature -LiteralPath $executable).Status -ne 'Valid') { throw 'Tanda tangan resmi SumatraPDF tidak valid.' }
& powershell.exe -NoProfile -NonInteractive -ExecutionPolicy Bypass -File (Join-Path $PSScriptRoot 'Install-TicketPrinterCheck.ps1')
if ($LASTEXITCODE -ne 0) { throw 'Pemeriksa printer native gagal dipasang.' }
Push-Location $projectDirectory
try {
    & $PhpPath artisan config:clear
    if ($LASTEXITCODE -ne 0) { throw 'Cache konfigurasi gagal dibersihkan.' }
    & $PhpPath artisan migrate --force
    if ($LASTEXITCODE -ne 0) { throw 'Migrasi layanan cetak gagal.' }
    & (Join-Path $PSScriptRoot 'Start-TicketPrintWorker.ps1') -PhpPath $PhpPath -InstallStartup
} finally { Pop-Location }
