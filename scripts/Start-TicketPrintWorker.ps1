[CmdletBinding()]
param([string]$PhpPath = 'D:\php\php.exe', [switch]$InstallStartup)
$ErrorActionPreference = 'Stop'
$projectDirectory = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..'))
if (-not (Test-Path -LiteralPath $PhpPath -PathType Leaf)) { throw 'PHP CLI tidak ditemukan.' }
if (-not (Test-Path -LiteralPath (Join-Path $projectDirectory 'tools\SumatraPDF\SumatraPDF.exe'))) { throw 'SumatraPDF portable belum terpasang.' }
if ($InstallStartup) {
    $startupDirectory = [Environment]::GetFolderPath('Startup')
    $shell = New-Object -ComObject WScript.Shell
    $shortcut = $shell.CreateShortcut((Join-Path $startupDirectory 'Bank DP Taspen Print Worker.lnk'))
    $shortcut.TargetPath = "$env:SystemRoot\System32\WindowsPowerShell\v1.0\powershell.exe"
    $shortcut.Arguments = '-NoProfile -NonInteractive -WindowStyle Hidden -ExecutionPolicy Bypass -File "' + $PSCommandPath + '" -PhpPath "' + $PhpPath + '"'
    $shortcut.WorkingDirectory = $projectDirectory
    $shortcut.WindowStyle = 7
    $shortcut.Description = 'Layanan cetak tiket antrean melalui PC server'
    $shortcut.Save()
    Start-Process -FilePath $shortcut.TargetPath -ArgumentList $shortcut.Arguments -WindowStyle Hidden | Out-Null
    Write-Output 'Startup worker terpasang dan worker dimulai.'
    exit 0
}
$mutex = [Threading.Mutex]::new($false, 'Local\BankDPTaspenServerPrintWorker')
$owns = $false
try {
    try { $owns = $mutex.WaitOne(0) } catch [Threading.AbandonedMutexException] { $owns = $true }
    if (-not $owns) { exit 0 }
    $settings = Get-Content -LiteralPath (Join-Path $projectDirectory 'kiosk.config.json') -Raw | ConvertFrom-Json
    # Read locally through Winspool: enumerating offline WSD printers via WMI can block startup.
    Add-Type -TypeDefinition @'
using System;
using System.Text;
using System.Runtime.InteropServices;
public static class WorkerPrinterDefault {
    [DllImport("winspool.drv", CharSet=CharSet.Unicode)] static extern bool GetDefaultPrinter(StringBuilder buffer, ref int length);
    public static string Read() {
        int length=0; GetDefaultPrinter(null, ref length); if(length==0) return null;
        var buffer=new StringBuilder(length); return GetDefaultPrinter(buffer, ref length) ? buffer.ToString() : null;
    }
}
'@
    $defaultPrinter = [WorkerPrinterDefault]::Read()
    $guardSettings = @{original_printer=$defaultPrinter; kiosk_printer=$settings.printer_name; owner_pid=$PID; config_path=(Join-Path $projectDirectory 'kiosk.config.json')}
    $encoded = [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes(($guardSettings | ConvertTo-Json -Compress)))
    Start-Process powershell.exe -WindowStyle Hidden -ArgumentList @('-NoProfile','-NonInteractive','-ExecutionPolicy','Bypass','-File',('"'+(Join-Path $PSScriptRoot 'Keep-PrinterDefault.ps1')+'"'),'-SettingsBase64',$encoded) | Out-Null
    $logDirectory = Join-Path $projectDirectory 'storage\logs'
    do {
        $process = Start-Process -FilePath $PhpPath -ArgumentList @(('"'+(Join-Path $projectDirectory 'artisan')+'"'),'tickets:work') -WorkingDirectory $projectDirectory -WindowStyle Hidden -PassThru -RedirectStandardOutput (Join-Path $logDirectory 'ticket-worker-output.log') -RedirectStandardError (Join-Path $logDirectory 'ticket-worker-error.log')
        $process.WaitForExit()
        Start-Sleep -Seconds 2
    } while ($true)
} finally {
    if ($owns) { $mutex.ReleaseMutex() }
    $mutex.Dispose()
}
