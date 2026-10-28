[CmdletBinding()]
param([switch]$Settings, [switch]$ChooseBrowser, [string]$DataDirectory = '')
$ErrorActionPreference='Stop'
if (-not $DataDirectory) { $DataDirectory=$PSScriptRoot }
. (Join-Path $PSScriptRoot 'Agent.Common.ps1')
. (Join-Path $PSScriptRoot 'Printer.Setup.ps1')

function Get-AgentBrowsers {
    $result=@([pscustomobject]@{Id='default'; Name='Browser default Windows'; Executable=$null})
    $seen=@{}
    foreach ($root in @('HKCU:\Software\Clients\StartMenuInternet','HKLM:\Software\Clients\StartMenuInternet','HKLM:\Software\WOW6432Node\Clients\StartMenuInternet')) {
        if (-not (Test-Path -LiteralPath $root)) { continue }
        foreach ($key in @(Get-ChildItem -LiteralPath $root)) {
            $commandKey=Get-Item -LiteralPath ($key.PSPath+'\shell\open\command') -ErrorAction SilentlyContinue
            if (-not $commandKey) { continue }
            $command=[string]$commandKey.GetValue('')
            $executable=$null
            if ($command -match '^\s*"([^"]+\.exe)"') { $executable=$Matches[1] }
            elseif ($command -match '^\s*(.+?\.exe)(?:\s|$)') { $executable=$Matches[1] }
            if ($executable -and (Test-Path -LiteralPath $executable -PathType Leaf) -and -not $seen.ContainsKey($executable)) {
                $seen[$executable]=$true
                $name=[string]$key.GetValue(''); if (-not $name) { $name=$key.PSChildName }
                $result += [pscustomobject]@{Id=$key.PSChildName; Name=$name; Executable=$executable}
            }
        }
    }
    return $result
}

function Select-AgentBrowser($Browsers, [string]$Preferred) {
    Add-Type -AssemblyName System.Windows.Forms
    $form=New-Object Windows.Forms.Form
    $form.Text='Browser kiosk PC ini'; $form.Width=460; $form.Height=180; $form.StartPosition='CenterScreen'; $form.FormBorderStyle='FixedDialog'; $form.MaximizeBox=$false
    $select=New-Object Windows.Forms.ComboBox
    $select.SetBounds(18,20,405,28); $select.DropDownStyle='DropDownList'
    $select.Items.AddRange([object[]]@($Browsers | ForEach-Object { $_.Name }))
    $select.SelectedIndex=0
    for ($i=0;$i -lt $Browsers.Count;$i++) { if ($Browsers[$i].Id -eq $Preferred) { $select.SelectedIndex=$i } }
    $accept=New-Object Windows.Forms.Button
    $accept.SetBounds(270,74,150,32); $accept.Text='Buka kiosk'; $accept.DialogResult='OK'
    $form.Controls.AddRange([Windows.Forms.Control[]]@($select,$accept)); $form.AcceptButton=$accept
    try { if ($form.ShowDialog() -ne 'OK') { return $null }; return $Browsers[$select.SelectedIndex] }
    finally { $form.Dispose() }
}

$client=$null
try {
    $config=Get-Content -LiteralPath (Join-Path $DataDirectory 'config.json') -Raw -Encoding UTF8 | ConvertFrom-Json
    $client=New-AgentAuthenticatedClient $config $DataDirectory
    if ($Settings) {
        $choice=Show-AgentPrinterSettings $config.printer_name $config.branch_code $config.paper_profile
        $status=Get-AgentPrinterStatus ([pscustomobject]@{printer_name=$choice.PrinterName}) $DataDirectory
        if (-not $status.ready) { throw $status.error }
        Invoke-AgentRequest $client $config '/api/print-agent/printer-selection' @{printer_name=$choice.PrinterName;paper_profile=$choice.PaperProfile;installed_printers=@(Get-AgentSelectablePrinters)} | Out-Null
        [Windows.Forms.MessageBox]::Show('Pilihan disimpan. Penghubung akan mengonfirmasi printer dan kertas baru. Tiket lama tidak dipindahkan atau dicetak ulang.','Pengaturan printer') | Out-Null
        exit 0
    }
    $browsers=@(Get-AgentBrowsers)
    $preferencesPath=Join-Path $DataDirectory 'browser.json'
    $preferred='default'
    if (Test-Path -LiteralPath $preferencesPath) {
        try { $preferred=[string](Get-Content -LiteralPath $preferencesPath -Raw -Encoding UTF8 | ConvertFrom-Json).browser_id } catch { }
    }
    $browser=$browsers | Where-Object { $_.Id -eq $preferred } | Select-Object -First 1
    if (-not $browser) { $browser=$browsers[0] }
    if ($ChooseBrowser) {
        $browser=Select-AgentBrowser $browsers $preferred
        if (-not $browser) { exit 0 }
        Write-AgentJson $preferencesPath @{browser_id=$browser.Id}
    }
    $reply=$null
    $deadline=[DateTime]::UtcNow.AddSeconds(20)
    do {
        try { $reply=Invoke-AgentRequest $client $config '/api/print-agent/browser-pairings' @{} }
        catch {
            if ($_.Exception.Data['HttpStatus'] -ne 409 -or [DateTime]::UtcNow -ge $deadline) { throw }
            Start-Sleep -Seconds 2
        }
    } while (-not $reply)
    $uri=[Uri]$reply.connect_url
    $base=[Uri]$config.server_url
    if ($uri.GetLeftPart([UriPartial]::Authority) -ne $base.GetLeftPart([UriPartial]::Authority) -or -not $uri.AbsolutePath.StartsWith($base.AbsolutePath.TrimEnd('/')+'/') -or $uri.Fragment -notmatch '^#[a-f0-9]{64}$') { throw 'Alamat pemasangan browser tidak valid.' }
    try {
        if ($browser.Executable) { Start-Process -FilePath $browser.Executable -ArgumentList @($uri.AbsoluteUri) -WindowStyle Normal | Out-Null }
        else { Start-Process -FilePath $uri.AbsoluteUri -WindowStyle Normal | Out-Null }
    } catch { throw 'Browser tidak dapat dibuka. Coba pilih browser lain melalui shortcut Pilih browser kiosk.' }
} catch {
    Add-Type -AssemblyName System.Windows.Forms
    [Windows.Forms.MessageBox]::Show($_.Exception.Message,'Kiosk belum dapat dibuka') | Out-Null
    exit 1
} finally { if ($client) { $client.Dispose() } }
