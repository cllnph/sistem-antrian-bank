$ErrorActionPreference = 'Stop'
$guardPath = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..\scripts\Keep-PrinterDefault.ps1'))
$parseTokens = $null
$parseErrors = $null
$tree = [System.Management.Automation.Language.Parser]::ParseFile($guardPath, [ref]$parseTokens, [ref]$parseErrors)
if ($parseErrors.Count) { throw 'Printer guard has syntax errors.' }
$definition = $tree.Find({ param($node) $node -is [System.Management.Automation.Language.FunctionDefinitionAst] -and $node.Name -eq 'Get-DefaultPrinterDecision' }, $true)
$decisionFunction = $definition.Body.GetScriptBlock()
$cases = @(
    @{ current = 'PDF'; previous = 'PDF'; remember = 'PDF'; restore = $null },
    @{ current = 'KIOSK'; previous = 'PDF'; remember = 'PDF'; restore = 'PDF' },
    @{ current = 'OTHER'; previous = 'PDF'; remember = 'OTHER'; restore = $null },
    @{ current = 'KIOSK'; previous = 'OTHER'; remember = 'OTHER'; restore = 'OTHER' },
    @{ current = 'KIOSK'; previous = 'KIOSK'; remember = 'KIOSK'; restore = $null },
    @{ current = ''; previous = 'PDF'; remember = 'PDF'; restore = $null }
)
foreach ($case in $cases) {
    $decision = & $decisionFunction -CurrentPrinter $case.current -PreviousPrinter $case.previous -KioskPrinter 'KIOSK'
    if ($decision.Remember -ne $case.remember -or $decision.Restore -ne $case.restore) { throw 'Unexpected printer default decision.' }
}
Write-Output 'PASS printer-default guard: six decisions'
foreach ($printer in @('BRANCH-A', 'BRANCH-B')) {
    $decision = & $decisionFunction -CurrentPrinter $printer -PreviousPrinter 'PDF' -KioskPrinter @('BRANCH-A','BRANCH-B')
    if ($decision.Restore -ne 'PDF') { throw 'Mapped branch printer changed the Windows default.' }
}
Write-Output 'PASS printer-default guard: both mapped branch printers'

# Exercise the Windows API read path once, with no live browser and no default change.
$currentDefault = (Get-CimInstance Win32_Printer | Where-Object Default).Name
$encoded = [Convert]::ToBase64String([Text.Encoding]::UTF8.GetBytes((@{
    original_printer = $currentDefault; kiosk_printer = 'ITPRINTER (L6290 Series)'; browser_pid = 2147483647
} | ConvertTo-Json -Compress)))
& $guardPath -SettingsBase64 $encoded
$after = (Get-CimInstance Win32_Printer | Where-Object Default).Name
if ($currentDefault -ne $after) { throw 'Windows default changed during the read-only guard test.' }
Write-Output 'PASS printer-default guard: Windows API read path and unchanged default'
