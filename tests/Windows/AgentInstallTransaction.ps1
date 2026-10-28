# Real installer with isolated server/printer adapters. No queue, driver, Startup entry or print is created.
[CmdletBinding()]
param([Parameter(Mandatory=$true)][string]$PackagePath)
$ErrorActionPreference='Stop'
$root=[IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..\..'))
$testRoot=Join-Path ([IO.Path]::GetTempPath()) ('BankAgentTransaction-'+[Guid]::NewGuid().ToString('N'))
$previousLocal=$env:LOCALAPPDATA
$global:BankTestTokenCounter=0
$global:BankTestActivationRejected=$false
function Assert-Test([bool]$Condition,[string]$Message) { if (-not $Condition) { throw $Message } }
try {
    New-Item -ItemType Directory -Path $testRoot | Out-Null
    $package=Join-Path $testRoot 'package'
    Expand-Archive -LiteralPath $PackagePath -DestinationPath $package
    foreach ($file in @('Install-Agent.ps1','Agent.Common.ps1','Printer.Setup.ps1','Printer.Inventory.ps1','Watch-Agent.ps1','Open-Kiosk.ps1','VERSION.txt')) {
        Assert-Test ((Get-FileHash -LiteralPath (Join-Path $package $file)).Hash -eq (Get-FileHash -LiteralPath (Join-Path $root ('agent\'+$file))).Hash) ('Downloaded file is stale: '+$file)
    }
    # Replace only external boundaries in this private copy, after verifying packaged sources.
    @'
function Get-AgentPrinterInventory { return [pscustomobject]@{ok=$true; printers=@([pscustomobject]@{Name='Fixture Epson';DriverName='Fixture physical';PortName='USB001'})} }
function Get-AgentPrinterStatus($Config,[string]$Directory) { return @{ready=$true;error=$null} }
function Invoke-AgentRequest($Client,$Config,[string]$Path,$Body=$null,[string]$Claim='',[switch]$Pdf) {
    switch ($Path) {
        '/api/print-agent/preflight' { return [pscustomobject]@{ok=$true;application='bank-dp-taspen-print-agent'} }
        '/api/print-agent/prepare' { $global:BankTestTokenCounter++; return [pscustomobject]@{token=('d'*63)+[string]$global:BankTestTokenCounter} }
        '/api/print-agent/activate-enrollment' {
            if ($global:BankTestActivationRejected) { $failure=[Exception]::new('Fixture activation rejected');$failure.Data['HttpStatus']=401;throw $failure }
            return [pscustomobject]@{ok=$true}
        }
        default { throw 'Unexpected API call; fixture never prints.' }
    }
}
'@ | Add-Content -LiteralPath (Join-Path $package 'Agent.Common.ps1') -Encoding UTF8
    $env:LOCALAPPDATA=Join-Path $testRoot 'local'
    $id=[Guid]::NewGuid().ToString()
    $directory=Join-Path $env:LOCALAPPDATA ('BankDPTaspenPrintAgent\'+$id)
    $setup=[pscustomobject]@{server_url='http://127.0.0.1:1';agent_id=$id;branch_code='TEST';printer_name='Fixture Epson';paper_profile='thermal58';enrollment_key=('c'*64);allow_http=$true;installer_version='1.3.0';enrollment_expires_at='2000-01-01T00:00:00+07:00'}
    # Old client timestamps must not override a successful authoritative server preflight.
    function Save-FixtureSetup { $setup.enrollment_key='c'*64; $setup | ConvertTo-Json | Set-Content -LiteralPath (Join-Path $package 'setup.json') -Encoding UTF8 }
    Save-FixtureSetup
    & (Join-Path $package 'Install-Agent.ps1') -PrinterName 'Fixture Epson' -NoStart -NoStartup
    $config=Get-Content -LiteralPath (Join-Path $directory 'config.json') -Raw -Encoding UTF8 | ConvertFrom-Json
    Assert-Test ($config.installer_version -eq '1.3.0' -and $config.paper_profile -eq 'thermal58' -and -not $config.activation_pending) 'A successful install must retain profile/version and confirm activation.'
    foreach ($file in @('CheckPrinter.exe','SumatraPDF.exe','Watch-Agent.ps1','Open-Kiosk.ps1','Printer.Inventory.ps1','credential.dpapi')) {
        Assert-Test (Test-Path -LiteralPath (Join-Path $directory $file)) ('Installed component missing: '+$file)
    }
    $journal=Join-Path $directory 'journal'
    New-Item -ItemType Directory -Path $journal | Out-Null
    $journalPath=Join-Path $journal 'fixture.json'
    '{"phase":"started","result":"unknown"}' | Set-Content -LiteralPath $journalPath -Encoding UTF8
    $journalHash=(Get-FileHash -LiteralPath $journalPath).Hash
    Save-FixtureSetup
    & (Join-Path $package 'Install-Agent.ps1') -PrinterName 'Fixture Epson' -NoStart -NoStartup
    Assert-Test ((Get-FileHash -LiteralPath $journalPath).Hash -eq $journalHash) 'Successful reinstall must preserve the one-shot print journal.'
    $oldConfig=(Get-FileHash -LiteralPath (Join-Path $directory 'config.json')).Hash
    $oldCredential=(Get-FileHash -LiteralPath (Join-Path $directory 'credential.dpapi')).Hash
    $global:BankTestActivationRejected=$true
    Save-FixtureSetup
    $rejected=$false
    try { & (Join-Path $package 'Install-Agent.ps1') -PrinterName 'Fixture Epson' -NoStart -NoStartup }
    catch { if ($_.Exception.Message -notlike '*Fixture activation rejected*') { throw }; $rejected=$true }
    Assert-Test $rejected 'Expected activation rejection.'
    Assert-Test ((Get-FileHash -LiteralPath (Join-Path $directory 'config.json')).Hash -eq $oldConfig) 'Rollback changed original config bytes.'
    Assert-Test ((Get-FileHash -LiteralPath (Join-Path $directory 'credential.dpapi')).Hash -eq $oldCredential) 'Rollback changed original DPAPI credential bytes.'
    Assert-Test ((Get-FileHash -LiteralPath $journalPath).Hash -eq $journalHash) 'Rollback changed journal bytes.'
    Assert-Test (-not (Test-Path -LiteralPath (Join-Path $directory 'stop.request'))) 'Rollback left the old agent stopped.'
    Assert-Test (-not (Test-Path -LiteralPath (Join-Path (Split-Path $directory -Parent) ('.stop-'+$id)))) 'Rollback left the supervisor stopped.'
    # Exercise shortcut/launch integration while replacing Windows Shell writes and process launches.
    $global:BankTestSavedLinks=[Collections.Generic.List[object]]::new()
    $global:BankTestLaunches=[Collections.Generic.List[object]]::new()
    function global:New-Object {
        [CmdletBinding()]
        param([string]$TypeName,[string]$ComObject,[object[]]$ArgumentList)
        if ($ComObject -ne 'WScript.Shell') { return Microsoft.PowerShell.Utility\New-Object @PSBoundParameters }
        $shell=[pscustomobject]@{}
        $shell | Add-Member ScriptMethod CreateShortcut {
            param($Path)
            $link=[pscustomobject]@{Path=$Path;TargetPath='';Arguments='';WorkingDirectory='';WindowStyle=0}
            $link | Add-Member ScriptMethod Save { $global:BankTestSavedLinks.Add($this) }
            return $link
        }
        return $shell
    }
    function global:Start-Process {
        [CmdletBinding()]
        param([string]$FilePath,[string[]]$ArgumentList,[string]$WindowStyle)
        $global:BankTestLaunches.Add([pscustomobject]@{File=$FilePath;Arguments=$ArgumentList;WindowStyle=$WindowStyle})
    }
    $global:BankTestActivationRejected=$false
    Save-FixtureSetup
    & (Join-Path $package 'Install-Agent.ps1') -PrinterName 'Fixture Epson'
    Assert-Test ($global:BankTestSavedLinks.Count -eq 4) 'Expected a supervisor Startup shortcut and three desktop shortcuts.'
    Assert-Test (@($global:BankTestSavedLinks | Where-Object {$_.Arguments -like '*Watch-Agent.ps1*'}).Count -eq 1) 'Startup must supervise the agent.'
    Assert-Test (@($global:BankTestSavedLinks | Where-Object {$_.Arguments -like '*-Settings*'}).Count -eq 1) 'Printer selection shortcut missing.'
    Assert-Test (@($global:BankTestSavedLinks | Where-Object {$_.Arguments -like '*-ChooseBrowser*'}).Count -eq 1) 'Browser chooser shortcut missing.'
    Assert-Test ($global:BankTestLaunches.Count -eq 2) 'Start the supervisor and browser chooser once.'
    Assert-Test (@($global:BankTestLaunches | Where-Object {$_.WindowStyle -ne 'Hidden'}).Count -eq 0) 'Background console windows must stay hidden.'
    foreach ($link in $global:BankTestSavedLinks) {
        Assert-Test ($link.Arguments -notmatch 'enrollment_key|credential|'+('d'*63)) 'Shortcuts must not contain credentials.'
    }
    Write-Output 'PASS downloaded installer: install/reinstall, clock tolerance, journal preservation, byte-exact rollback, supervisor/three shortcuts and hidden startup. External printer/server/Windows Shell adapters isolated; no printing or Startup changes.'
} finally {
    Remove-Item Function:\New-Object,Function:\Start-Process -ErrorAction SilentlyContinue
    $env:LOCALAPPDATA=$previousLocal
    Remove-Variable BankTestTokenCounter,BankTestActivationRejected -Scope Global -ErrorAction SilentlyContinue
    Remove-Variable BankTestSavedLinks,BankTestLaunches -Scope Global -ErrorAction SilentlyContinue
    $resolved=[IO.Path]::GetFullPath($testRoot)
    $temporary=[IO.Path]::GetFullPath([IO.Path]::GetTempPath()).TrimEnd('\')+'\'
    if (-not $resolved.StartsWith($temporary,[StringComparison]::OrdinalIgnoreCase) -or (Split-Path $resolved -Leaf) -notlike 'BankAgentTransaction-*') { throw 'Unsafe fixture cleanup path.' }
    if (Test-Path -LiteralPath $resolved) { Remove-Item -LiteralPath $resolved -Recurse -Force }
}
