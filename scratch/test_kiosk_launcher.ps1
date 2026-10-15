param([string]$Case = 'ready')
$ErrorActionPreference = 'Stop'
$projectDirectory = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))
$testDirectory = Join-Path $PSScriptRoot 'silent-print-audit\launcher-tests'
[IO.Directory]::CreateDirectory($testDirectory) | Out-Null
$env:LOCALAPPDATA = Join-Path $testDirectory $Case
$mockProfile = Join-Path $env:LOCALAPPDATA 'BankDPTaspenKiosk'
if ($Case -eq 'preserve') {
    $defaultDirectory = Join-Path $mockProfile 'Default'
    [IO.Directory]::CreateDirectory($defaultDirectory) | Out-Null
    '{"kiosk_audit_preserved_setting":"keep","printing":{"print_preview_sticky_settings":{"appState":"{}"}}}' |
        Set-Content -LiteralPath (Join-Path $defaultDirectory 'Preferences') -Encoding UTF8
}
$settings = [ordered]@{ url = 'http://127.0.0.1:7102/ambil-nomor'; printer_name = 'ITPRINTER (L6290 Series)' }
if ($Case -eq 'invalid-url') { $settings.url = 'javascript:alert(1)' }
if ($Case -eq 'virtual') { $settings.printer_name = 'Microsoft Print to PDF' }
$testConfig = Join-Path $testDirectory ($Case + '.json')
$settings | ConvertTo-Json | Set-Content -LiteralPath $testConfig -Encoding UTF8

function Get-CimInstance {
    param([string]$ClassName, [string]$Filter)
    if ($ClassName -eq 'Win32_Printer' -and $Case -ne 'missing') {
        return @(
            [pscustomobject]@{ Name = $settings.printer_name; WorkOffline = ($Case -eq 'offline'); PrinterStatus = 3; Default = $false },
            [pscustomobject]@{ Name = 'Microsoft Print to PDF'; WorkOffline = $false; PrinterStatus = 3; Default = $true }
        )
    }
    if ($ClassName -eq 'Win32_Process' -and $Case -eq 'running') {
        return [pscustomobject]@{ CommandLine = 'chrome.exe --user-data-dir="' + $mockProfile + '"' }
    }
}
function Get-Printer {
    param([string]$Name)
    return [pscustomobject]@{ PrinterStatus = $(if ($Case -eq 'error') { 'Error' } else { 'Normal' }) }
}
function Start-Process {
    param($FilePath, $ArgumentList, $WindowStyle, [switch]$PassThru)
    $outputName = if ($FilePath.EndsWith('chrome.exe')) { 'launch.json' } else { 'guard-launch.json' }
    [pscustomobject]@{ executable = $FilePath; arguments = $ArgumentList; window_style = $WindowStyle } |
        ConvertTo-Json -Depth 10 | Set-Content -LiteralPath (Join-Path $testDirectory $outputName) -Encoding UTF8
    if ($PassThru) { return [pscustomobject]@{ Id = $PID } }
}

$helperPath = Join-Path $projectDirectory 'scripts\Start-Kiosk.ps1'
if ($Case -in @('ready', 'preserve')) {
    & $helperPath -ConfigPath $testConfig
} else {
    & $helperPath -ConfigPath $testConfig -PrepareOnly
}
exit $LASTEXITCODE
