# Run with Windows PowerShell 5.1 as a normal user:
# powershell.exe -NoProfile -NonInteractive -ExecutionPolicy Bypass -File tests\Windows\AgentDirectoryPermissions.ps1
[CmdletBinding()]
param([string]$PackagePath = '')
$ErrorActionPreference = 'Stop'
Set-StrictMode -Version 2.0
$root = [IO.Path]::GetFullPath((Join-Path $PSScriptRoot '..\..'))

function Assert-Test([bool]$Condition, [string]$Message) {
    if (-not $Condition) { throw $Message }
}

function Assert-PrivateDirectory([string]$Path, [bool]$Inherited = $false) {
    $acl = (Get-Item -LiteralPath $Path -Force).GetAccessControl()
    $rules = @($acl.GetAccessRules($true, $true, [Security.Principal.SecurityIdentifier]))
    $expected = @([Security.Principal.WindowsIdentity]::GetCurrent().User.Value, 'S-1-5-18') | Sort-Object
    Assert-Test ($rules.Count -eq 2) 'Access must be limited to the current user and SYSTEM.'
    Assert-Test ((@($rules.IdentityReference.Value | Sort-Object) -join ',') -eq ($expected -join ',')) 'Unexpected principal in the private folder.'
    Assert-Test ($acl.AreAccessRulesProtected -eq (-not $Inherited)) 'Incorrect inheritance protection.'
    foreach ($rule in $rules) {
        Assert-Test ($rule.AccessControlType -eq [Security.AccessControl.AccessControlType]::Allow) 'Expected an allow rule.'
        Assert-Test ($rule.FileSystemRights -eq [Security.AccessControl.FileSystemRights]::FullControl) 'Expected full control.'
        Assert-Test ($rule.IsInherited -eq $Inherited) 'Incorrect inherited rule.'
        if (-not $Inherited) {
            Assert-Test ($rule.InheritanceFlags -eq [Security.AccessControl.InheritanceFlags]'ContainerInherit, ObjectInherit') 'Child files and folders must inherit protection.'
        }
    }
}

$testId = [Guid]::NewGuid().ToString()
$testRoot = Join-Path ([IO.Path]::GetTempPath()) ('BankAgentAclTest-' + $testId)
$agentRoot = Join-Path $env:LOCALAPPDATA 'BankDPTaspenPrintAgent'
$installDirectory = Join-Path $agentRoot $testId
try {
    New-Item -ItemType Directory -Path $testRoot | Out-Null
    $package = Join-Path $testRoot 'package'
    if ($PackagePath) {
        Expand-Archive -LiteralPath $PackagePath -DestinationPath $package
    } else {
        New-Item -ItemType Directory -Path $package | Out-Null
        foreach ($file in @('Install-Agent.ps1', 'Agent.Common.ps1', 'Printer.Setup.ps1', 'VERSION.txt')) {
            Copy-Item -LiteralPath (Join-Path $root ('agent\' + $file)) -Destination (Join-Path $package $file)
        }
        foreach ($file in @('Start-Agent.ps1','Watch-Agent.ps1','Open-Kiosk.ps1','Printer.Inventory.ps1')) { Copy-Item -LiteralPath (Join-Path $root ('agent\' + $file)) -Destination (Join-Path $package $file) }
        foreach ($file in @('Print-TicketPdf.ps1', 'Keep-PrinterDefault.ps1')) { Copy-Item -LiteralPath (Join-Path $root ('scripts\' + $file)) -Destination (Join-Path $package $file) }
        Copy-Item -LiteralPath (Join-Path $root 'scripts\native\CheckPrinter.cs') -Destination (Join-Path $package 'CheckPrinter.cs')
        Copy-Item -LiteralPath (Join-Path $root 'tools\SumatraPDF\SumatraPDF.exe') -Destination (Join-Path $package 'SumatraPDF.exe')
    }
    foreach ($file in @('Install-Agent.ps1', 'Agent.Common.ps1', 'Printer.Setup.ps1', 'VERSION.txt')) {
        Assert-Test ((Get-FileHash -LiteralPath (Join-Path $package $file)).Hash -eq (Get-FileHash -LiteralPath (Join-Path $root ('agent\' + $file))).Hash) ('Installer package is stale: ' + $file)
    }
    . (Join-Path $package 'Agent.Common.ps1')
    $version = (Get-Content -LiteralPath (Join-Path $package 'VERSION.txt') -Raw).Trim()
    Write-Output ('Testing installer v' + $version + ' from ' + $(if ($PackagePath) { 'downloaded ZIP' } else { 'repository' }))

    $fresh = Join-Path $testRoot 'Folder with spaces'
    New-Item -ItemType Directory -Path $fresh | Out-Null
    $before = (Get-Item -LiteralPath $fresh).GetAccessControl()
    Protect-AgentDirectory $fresh
    Assert-PrivateDirectory $fresh
    $after = (Get-Item -LiteralPath $fresh).GetAccessControl()
    Assert-Test ($before.GetOwner([Security.Principal.SecurityIdentifier]) -eq $after.GetOwner([Security.Principal.SecurityIdentifier])) 'Owner must be preserved.'
    Assert-Test ($before.GetGroup([Security.Principal.SecurityIdentifier]) -eq $after.GetGroup([Security.Principal.SecurityIdentifier])) 'Group must be preserved.'
    Write-Output 'PASS new folder: protected DACL, owner and group preserved.'

    $child = Join-Path $fresh 'child'
    New-Item -ItemType Directory -Path $child | Out-Null
    Assert-PrivateDirectory $child $true
    $credential = Join-Path $child 'credential.dpapi'
    'fixture-credential' | ConvertTo-SecureString -AsPlainText -Force | ConvertFrom-SecureString | Set-Content -LiteralPath $credential -Encoding ASCII
    $encrypted = Get-Content -LiteralPath $credential -Raw
    $secure = ConvertTo-SecureString $encrypted.Trim()
    $pointer = [Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure)
    try { Assert-Test ([Runtime.InteropServices.Marshal]::PtrToStringBSTR($pointer) -eq 'fixture-credential') 'DPAPI round-trip failed.' }
    finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($pointer); $secure.Dispose() }
    Assert-PrivateDirectory $credential $true
    Protect-AgentDirectory $fresh
    Assert-PrivateDirectory $fresh
    Assert-Test ((Get-Content -LiteralPath $credential -Raw) -eq $encrypted) 'Reinstallation must preserve existing credentials.'
    Write-Output 'PASS reinstall: protected folder, inherited file access and DPAPI credentials preserved.'

    $acl = (Get-Item -LiteralPath $fresh).GetAccessControl([Security.AccessControl.AccessControlSections]::Access)
    $acl.AddAccessRule([Security.AccessControl.FileSystemAccessRule]::new([Security.Principal.SecurityIdentifier]::new('S-1-1-0'), 'ReadAndExecute', 'ContainerInherit, ObjectInherit', 'None', 'Allow'))
    (Get-Item -LiteralPath $fresh).SetAccessControl($acl)
    Protect-AgentDirectory $fresh
    Assert-PrivateDirectory $fresh
    Assert-PrivateDirectory $credential $true
    Write-Output 'PASS repair: extra explicit permissions removed, child permissions restricted.'

    # A failed preflight must leave the old installation and credentials untouched.
    New-Item -ItemType Directory -Path $installDirectory -Force | Out-Null
    Protect-AgentDirectory $installDirectory
    Write-AgentJson (Join-Path $installDirectory 'config.json') @{old_installation=$true}
    'old-credential' | ConvertTo-SecureString -AsPlainText -Force | ConvertFrom-SecureString | Set-Content -LiteralPath (Join-Path $installDirectory 'credential.dpapi') -Encoding ASCII
    $oldConfigHash = (Get-FileHash -LiteralPath (Join-Path $installDirectory 'config.json')).Hash
    $oldCredentialHash = (Get-FileHash -LiteralPath (Join-Path $installDirectory 'credential.dpapi')).Hash
    Write-AgentJson (Join-Path $package 'setup.json') ([pscustomobject]@{
        server_url='http://127.0.0.1:1'; allow_http=$true; agent_id=$testId; branch_code='TEST';
        printer_name='Fixture Printer'; enrollment_key=('a' * 64); installer_version=$version
    })
    foreach ($attempt in @(1, 2)) {
        $checkpoint = $false
        try { & (Join-Path $package 'Install-Agent.ps1') -NoStart -NoStartup }
        catch {
            if ($_.Exception.Message -notlike '*Pemasangan gagal pada tahap: Memeriksa koneksi*') { throw }
            $checkpoint = $true
        }
        Assert-Test $checkpoint 'Installer must diagnose the failed connection.'
        Assert-PrivateDirectory $installDirectory
        Assert-Test ((Get-FileHash -LiteralPath (Join-Path $installDirectory 'config.json')).Hash -eq $oldConfigHash) 'Old configuration was changed.'
        Assert-Test ((Get-FileHash -LiteralPath (Join-Path $installDirectory 'credential.dpapi')).Hash -eq $oldCredentialHash) 'Old credentials were changed.'
        Assert-Test (-not (Test-Path -LiteralPath (Join-Path $installDirectory 'stop.request'))) 'A failed preflight must not stop the old program.'
    }
    Write-Output 'PASS real installer: failed connection and retry preserve the old installation and explain the failing stage.'

    $setup = Get-Content -LiteralPath (Join-Path $package 'setup.json') -Raw | ConvertFrom-Json
    $setup.installer_version = '0.0.0'
    Write-AgentJson (Join-Path $package 'setup.json') $setup
    $rejected = $false
    try { & (Join-Path $package 'Install-Agent.ps1') -NoStart -NoStartup }
    catch {
        if ($_.Exception.Message -notlike '*Versi berkas installer tidak cocok.*') { throw }
        $rejected = $true
    }
    Assert-Test $rejected 'Mixed installer versions must fail before installation.'
    Write-Output 'PASS mixed versions: rejected before printer selection or enrollment.'
} finally {
    foreach ($cleanupPath in @($installDirectory, $testRoot)) {
        $resolved = [IO.Path]::GetFullPath($cleanupPath)
        $allowedAgent = [IO.Path]::GetFullPath((Join-Path $agentRoot $testId))
        $allowedTemp = [IO.Path]::GetFullPath((Join-Path ([IO.Path]::GetTempPath()) ('BankAgentAclTest-' + $testId)))
        if (($resolved -ne $allowedAgent -and $resolved -ne $allowedTemp) -or $testId -notmatch '^[a-f0-9-]{36}$') { throw 'Unsafe test cleanup path.' }
        if (Test-Path -LiteralPath $resolved) { Remove-Item -LiteralPath $resolved -Recurse -Force }
    }
}
