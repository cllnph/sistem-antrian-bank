# No printer queues, drivers, network connections or print jobs are created.
# powershell.exe -NoProfile -STA -ExecutionPolicy Bypass -File tests\Windows\AgentPrinterSelection.ps1
$ErrorActionPreference = 'Stop'
Set-StrictMode -Version 2.0
. (Join-Path $PSScriptRoot '..\..\agent\Printer.Setup.ps1')

function Assert-Test([bool]$Condition, [string]$Message) {
    if (-not $Condition) { throw $Message }
}
function Assert-Rejected([scriptblock]$Action, [string]$Expected) {
    $rejected = $false
    try { & $Action | Out-Null }
    catch {
        if ($_.Exception.Message -notlike ('*' + $Expected + '*')) { throw }
        $rejected = $true
    }
    Assert-Test $rejected ('Expected rejection: ' + $Expected)
}

# Windows APIs are replaced only in this test process.
$global:agentPrinterFixtures = @()
$global:agentPrinterReadFailure = $false
$global:agentSharedConnections = @()
$global:agentDiscoveryLaunch = $null
function global:Get-AgentPrinterInventory {
    [CmdletBinding()]
    param()
    if ($global:agentPrinterReadFailure) { throw 'Fixture spooler unavailable' }
    return [pscustomobject]@{ok=$true; printers=$global:agentPrinterFixtures}
}
function global:Add-Printer {
    [CmdletBinding()]
    param([string]$ConnectionName)
    $global:agentSharedConnections += $ConnectionName
    $global:agentPrinterFixtures += [pscustomobject]@{ Name = $ConnectionName; DriverName = 'Fixture USB shared driver' }
}
function global:Start-Process {
    [CmdletBinding()]
    param([string]$FilePath, [string[]]$ArgumentList, [string]$WindowStyle, [switch]$PassThru)
    $global:agentDiscoveryLaunch = [pscustomobject]@{ FilePath = $FilePath; Arguments = $ArgumentList; WindowStyle = $WindowStyle }
    $fake = [pscustomobject]@{ HasExited = $false; Disposed = $false }
    $fake | Add-Member -MemberType ScriptMethod -Name Dispose -Value { $this.Disposed = $true }
    return $fake
}

$form = $null
try {
    Assert-Test (@(Get-AgentSelectablePrinters).Count -eq 0) 'Empty inventory must be supported.'
    $form = New-AgentPrinterSelectionForm 'Printer FC' '001'
    Assert-Test ($form -is [Windows.Forms.Form]) 'Expected a selection window.'
    Assert-Test (-not $form.Controls['InstallAgent'].Enabled) 'Installation must wait for a real printer queue.'
    Assert-Test $form.Controls['DiscoverPrinter'].Enabled 'Discovery must be available without installed printers.'
    Assert-Test $form.Controls['ConnectSharedPrinter'].Enabled 'Sharing must be available without installed printers.'
    Assert-Test ($form.Controls['PrinterStatus'].Text -like '*Belum ada printer*') 'Explain the empty inventory inside the window.'
    Assert-Test ($form.Controls['PrinterName'].SelectedIndex -eq -1) 'An absent name must not look like a detected printer.'
    Assert-Test ($form.Controls['PrinterName'].DropDownStyle -eq 'DropDownList') 'Choose detected queues without requiring a typed name.'
    Write-Output 'PASS empty inventory: picker opens with discovery/sharing available and preserves the requested name.'

    $global:agentPrinterFixtures = @(
        [pscustomobject]@{ Name = 'Printer FC USB'; DriverName = 'USB driver' },
        [pscustomobject]@{ Name = 'Printer FC WiFi'; DriverName = 'Network driver' },
        [pscustomobject]@{ Name = '\\PC-FC\PrinterFC'; DriverName = 'Shared driver' },
        [pscustomobject]@{ Name = 'Microsoft Print to PDF'; DriverName = 'PDF driver' },
        [pscustomobject]@{ Name = 'Virtual queue'; DriverName = 'Microsoft XPS driver' }
    )
    $names = @(Get-AgentSelectablePrinters)
    Assert-Test ($names.Count -eq 3) 'Only the three physical queues should be selectable.'
    foreach ($name in @('Printer FC USB', 'Printer FC WiFi', '\\PC-FC\PrinterFC')) {
        Assert-Test ((Resolve-AgentPrinterSelection $name.ToLowerInvariant() $names) -eq $name) 'USB, network and sharing names must resolve exactly, ignoring case.'
    }
    Assert-Rejected { Resolve-AgentPrinterSelection 'Printer FC' $names } 'belum terhubung'
    Assert-Rejected { Resolve-AgentPrinterSelection 'Printer FC*' $names } 'belum terhubung'
    Assert-Rejected { Resolve-AgentPrinterSelection 'Microsoft Print to PDF' $names } 'printer fisik'
    Write-Output 'PASS destinations: USB, Wi-Fi/LAN and sharing work without a default printer; ambiguous names are rejected.'

    & $form.Tag.Refresh 'Printer FC WiFi'
    Assert-Test $form.Controls['InstallAgent'].Enabled 'After adding a printer, installation must be enabled.'
    Assert-Test ($form.Controls['PrinterName'].Items.Count -eq 3) 'Refresh must load newly connected printers.'
    Assert-Test ($form.Controls['PrinterName'].Text -eq 'Printer FC WiFi') 'Keep the explicitly chosen printer after refresh.'
    $onClick = [Windows.Forms.Button].GetMethod('OnClick', [Reflection.BindingFlags]'Instance, NonPublic')
    $onClick.Invoke($form.Controls['InstallAgent'], @([EventArgs]::Empty)) | Out-Null
    Assert-Test ($form.Tag.PrinterName -eq 'Printer FC WiFi') 'The chosen queue must be returned to the installer.'
    Assert-Test ($form.DialogResult -eq 'OK') 'Accept the chosen queue.'
    $form.Dispose(); $form = $null
    Write-Output 'PASS refresh and selection: a newly connected queue becomes selectable and is returned to installation.'

    $form = New-AgentPrinterSelectionForm 'Printer FC WiFi' '001'
    $onClick.Invoke($form.Controls['DiscoverPrinter'], @([EventArgs]::Empty)) | Out-Null
    Assert-Test ($global:agentDiscoveryLaunch.FilePath -eq (Join-Path $env:SystemRoot 'System32\rundll32.exe')) 'Use the built-in Windows printer wizard.'
    Assert-Test (($global:agentDiscoveryLaunch.Arguments -join ' ') -eq 'printui.dll,PrintUIEntry /il') 'Launch printer discovery without replacing arguments with a typed device name.'
    Assert-Test ($global:agentDiscoveryLaunch.WindowStyle -eq 'Normal') 'The printer wizard must be interactive.'
    Assert-Test (-not $form.Controls['DiscoverPrinter'].Enabled) 'Avoid duplicate discovery windows.'
    $wizard = $form.Tag.Wizard
    $form.Dispose(); $form = $null
    Assert-Test $wizard.Disposed 'Dispose discovery tracking when the picker closes.'
    Write-Output 'PASS discovery: the interactive Windows wizard opens with fixed arguments; tracking is disposed when the picker closes.'

    foreach ($invalid in @('Printer FC', '192.168.1.20', '\\PC-FC', '\\PC-FC\Printer"FC', '\\PC-FC\FC\extra')) {
        Assert-Rejected { Connect-AgentSharedPrinter $invalid } 'alamat sharing lengkap'
    }
    Assert-Test ($global:agentSharedConnections.Count -eq 0) 'Invalid sharing addresses must never reach Windows.'
    $address = Connect-AgentSharedPrinter '\\192.168.1.20\Printer FC'
    Assert-Test ($address -eq '\\192.168.1.20\Printer FC') 'Preserve a complete sharing address with spaces.'
    Assert-Test ($global:agentSharedConnections.Count -eq 1) 'Connect only the explicit sharing address.'
    Write-Output 'PASS sharing: complete computer/share addresses are accepted; bare names, IPs and invalid paths are rejected before connecting.'

    $global:agentPrinterReadFailure = $true
    $form = New-AgentPrinterSelectionForm 'Printer FC' '001'
    Assert-Test ($form.Controls['PrinterStatus'].Text -like '*Print Spooler*') 'Explain a Windows inventory failure inside the picker.'
    Assert-Test $form.Controls['DiscoverPrinter'].Enabled 'Windows discovery must remain available after an inventory failure.'
    Assert-Test (-not $form.Controls['InstallAgent'].Enabled) 'An unreadable inventory must not permit enrollment.'
    Assert-Test (-not $form.Tag.PrinterName) 'No destination should be invented.'
    Write-Output 'PASS spooler failure: instructions remain visible and enrollment waits for a verified destination.'
} finally {
    if ($form) { $form.Dispose() }
    Remove-Item Function:\Get-AgentPrinterInventory, Function:\Add-Printer, Function:\Start-Process
    Remove-Variable agentPrinterFixtures, agentPrinterReadFailure, agentSharedConnections, agentDiscoveryLaunch -Scope Global
}
