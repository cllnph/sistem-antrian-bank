param([Parameter(Mandatory = $true)][string]$SettingsBase64)
$ErrorActionPreference = 'Stop'

function Get-DefaultPrinterDecision {
    param([string]$CurrentPrinter, [string]$PreviousPrinter, [string[]]$KioskPrinter)
    if ($CurrentPrinter -and $CurrentPrinter -notin $KioskPrinter) {
        return [pscustomobject]@{ Remember = $CurrentPrinter; Restore = $null }
    }
    $restore = if ($CurrentPrinter -in $KioskPrinter -and $PreviousPrinter -and $PreviousPrinter -notin $KioskPrinter) { $PreviousPrinter } else { $null }
    return [pscustomobject]@{ Remember = $PreviousPrinter; Restore = $restore }
}

$settings = [Text.Encoding]::UTF8.GetString([Convert]::FromBase64String($SettingsBase64)) | ConvertFrom-Json
$guardMutex = [Threading.Mutex]::new($false, 'Local\BankDPTaspenKioskPrinterDefaultGuard')
$ownsGuardMutex = $false
try {
    try { $ownsGuardMutex = $guardMutex.WaitOne(5000) } catch [Threading.AbandonedMutexException] { $ownsGuardMutex = $true }
    if (-not $ownsGuardMutex) { exit 0 }
    Add-Type -TypeDefinition @'
using System;
using System.Text;
using System.Runtime.InteropServices;
public static class KioskPrinterDefaults {
    [DllImport("winspool.drv", CharSet=CharSet.Unicode, SetLastError=true)]
    static extern bool GetDefaultPrinter(StringBuilder name, ref int length);
    [DllImport("winspool.drv", CharSet=CharSet.Unicode, SetLastError=true)]
    public static extern bool SetDefaultPrinter(string name);
    public static string ReadDefault() {
        int length=0;
        GetDefaultPrinter(null, ref length);
        if (length==0) return null;
        var buffer=new StringBuilder(length);
        return GetDefaultPrinter(buffer, ref length) ? buffer.ToString() : null;
    }
}
'@
    $previousPrinter = [string]$settings.original_printer
    $ownerProcessId = if ($settings.owner_pid) { [int]$settings.owner_pid } else { [int]$settings.browser_pid }
    do {
        $destinations = @($settings.kiosk_printer)
        if ($settings.config_path -and (Test-Path -LiteralPath $settings.config_path)) {
            try {
                $configuration = Get-Content -LiteralPath $settings.config_path -Raw -Encoding UTF8 | ConvertFrom-Json
                $destinations = @($configuration.printer_name) + @($configuration.branch_printers.PSObject.Properties | ForEach-Object { $_.Value })
            } catch { } # Keep protecting the previous set if an administrator is writing the JSON.
        }
        $decision = Get-DefaultPrinterDecision -CurrentPrinter ([KioskPrinterDefaults]::ReadDefault()) -PreviousPrinter $previousPrinter -KioskPrinter $destinations
        $previousPrinter = $decision.Remember
        if ($decision.Restore) { [KioskPrinterDefaults]::SetDefaultPrinter($decision.Restore) | Out-Null }
        $browserRunning = Get-Process -Id $ownerProcessId -ErrorAction SilentlyContinue
        if ($browserRunning) { Start-Sleep -Milliseconds 500 }
    } while ($browserRunning)
} finally {
    if ($ownsGuardMutex) { $guardMutex.ReleaseMutex() }
    $guardMutex.Dispose()
}
