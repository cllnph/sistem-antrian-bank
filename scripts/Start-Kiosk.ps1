[CmdletBinding()]
param([string]$ConfigPath = '', [switch]$CreateShortcut)
$ErrorActionPreference = 'Stop'
$projectDirectory = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))
if (-not $ConfigPath) { $ConfigPath = Join-Path $projectDirectory 'kiosk.config.json' }
$settings = Get-Content -LiteralPath $ConfigPath -Raw -Encoding UTF8 | ConvertFrom-Json
$address = $null
if (-not [Uri]::TryCreate($settings.url, [UriKind]::Absolute, [ref]$address) -or $address.Scheme -notin @('http','https') -or $settings.url -match '["\r\n]') { throw 'Alamat kiosk tidak valid.' }
if ($CreateShortcut) {
    $shell = New-Object -ComObject WScript.Shell
    $shortcut = $shell.CreateShortcut((Join-Path ([Environment]::GetFolderPath('Desktop')) 'Kiosk Antrean Bank DP Taspen.lnk'))
    $shortcut.TargetPath = "$env:SystemRoot\System32\WindowsPowerShell\v1.0\powershell.exe"
    $shortcut.Arguments = '-NoProfile -WindowStyle Hidden -ExecutionPolicy Bypass -File "' + $PSCommandPath + '"'
    $shortcut.WorkingDirectory = $projectDirectory
    $shortcut.WindowStyle = 7
    $shortcut.Description = 'Buka pengambilan tiket; cetak ditangani server'
    $shortcut.Save()
}
# Optional convenience only. Ordinary Edge tabs use the same server-printing path.
Start-Process -FilePath $settings.url
