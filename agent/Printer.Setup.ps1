# Printer discovery and driver installation use Windows, including USB, Wi-Fi/LAN
# and shared printers. A display name alone is never treated as a reachable queue.
if (-not (Get-Command Get-AgentPrinterInventory -ErrorAction SilentlyContinue)) { . (Join-Path $PSScriptRoot 'Agent.Common.ps1') }
function Get-AgentSelectablePrinters {
    (Get-AgentPrinterInventory).printers |
        Where-Object { $_.Name -notmatch '[\x00-\x1f"]|PDF|XPS|OneNote|Fax' -and $_.DriverName -notmatch 'PDF|XPS|OneNote|Fax' } |
        Select-Object -ExpandProperty Name | Sort-Object -Unique
}

function Resolve-AgentPrinterSelection([string]$Name, [string[]]$InstalledPrinters) {
    $nameToFind = $Name.Trim()
    if (-not $nameToFind -or $nameToFind.Length -gt 255 -or $nameToFind -match '[\x00-\x1f"]|PDF|XPS|OneNote|Fax') {
        throw 'Pilih nama printer fisik yang terhubung ke laptop ini.'
    }
    foreach ($installed in $InstalledPrinters) {
        if ([string]::Equals($installed, $nameToFind, [StringComparison]::OrdinalIgnoreCase)) { return $installed }
    }
    throw ('Printer "' + $nameToFind + '" belum terhubung ke Windows. Gunakan Cari / tambah printer, atau hubungkan alamat sharing, lalu Perbarui daftar.')
}

function Connect-AgentSharedPrinter([string]$ConnectionName) {
    $address = $ConnectionName.Trim()
    if ($address.Length -gt 255 -or $address -match '[\x00-\x1f"]' -or $address -notmatch '^\\\\[^\\/:*?"<>|\s]+\\[^\\/:*?"<>|]+$') {
        throw 'Isi alamat sharing lengkap, misalnya \\PC-FC\PrinterFC atau \\192.168.1.20\PrinterFC. Nama printer saja belum cukup.'
    }
    Add-Printer -ConnectionName $address -ErrorAction Stop | Out-Null
    return $address
}

function New-AgentPrinterSelectionForm([string]$PreferredName, [string]$BranchCode, [string]$PaperProfile = 'a4') {
    Add-Type -AssemblyName System.Windows.Forms
    Add-Type -AssemblyName System.Drawing
    [Windows.Forms.Application]::EnableVisualStyles()
    $form = New-Object Windows.Forms.Form
    $form.Text = 'Pilih printer kantor ' + $BranchCode
    $form.ClientSize = [Drawing.Size]::new(670, 485)
    $form.StartPosition = 'CenterScreen'
    $form.FormBorderStyle = 'FixedDialog'
    $form.MaximizeBox = $false
    $form.AutoScaleMode = 'Dpi'
    $form.Font = [Drawing.Font]::new('Segoe UI', 9)

    $intro = New-Object Windows.Forms.Label
    $intro.SetBounds(18, 15, 634, 44)
    $intro.Text = 'Pilih printer USB, Wi-Fi/LAN, atau sharing. Tidak harus menjadi printer default. Jika belum terhubung, tambahkan printer melalui tombol di bawah.'
    $hint = New-Object Windows.Forms.Label
    $hint.SetBounds(18, 66, 634, 24)
    $hint.AutoEllipsis = $true
    $hint.Text = if ($PreferredName) { 'Nama dari aplikasi: ' + $PreferredName } else { 'Printer yang tersedia pada laptop ini:' }
    $select = New-Object Windows.Forms.ComboBox
    $select.Name = 'PrinterName'
    $select.SetBounds(18, 94, 634, 28)
    $select.DropDownStyle = 'DropDownList'

    $discover = New-Object Windows.Forms.Button
    $discover.Name = 'DiscoverPrinter'
    $discover.Text = 'Cari / tambah printer'
    $discover.SetBounds(18, 136, 200, 34)
    $refreshButton = New-Object Windows.Forms.Button
    $refreshButton.Name = 'RefreshPrinters'
    $refreshButton.Text = 'Perbarui daftar'
    $refreshButton.SetBounds(230, 136, 150, 34)
    $help = New-Object Windows.Forms.Label
    $help.SetBounds(18, 181, 634, 42)
    $help.Text = 'USB: sambungkan kabel dan pasang driver jika diminta. Wi-Fi/LAN: cari melalui Windows, atau tambah memakai IP/hostname printer. Selesai menambahkan, perbarui daftar.'

    $shareLabel = New-Object Windows.Forms.Label
    $shareLabel.Text = 'Printer USB di komputer lain / printer sharing:'
    $shareLabel.SetBounds(18, 232, 634, 24)
    $share = New-Object Windows.Forms.TextBox
    $share.Name = 'SharedPrinterAddress'
    $share.SetBounds(18, 259, 472, 27)
    $share.MaxLength = 255
    if ($PreferredName -like '\\*') { $share.Text = $PreferredName }
    $connect = New-Object Windows.Forms.Button
    $connect.Name = 'ConnectSharedPrinter'
    $connect.Text = 'Hubungkan'
    $connect.SetBounds(502, 256, 150, 32)
    $example = New-Object Windows.Forms.Label
    $example.Text = 'Contoh: \\PC-FC\PrinterFC atau \\192.168.1.20\PrinterFC'
    $example.SetBounds(18, 293, 634, 22)
    $status = New-Object Windows.Forms.Label
    $status.Name = 'PrinterStatus'
    $status.SetBounds(18, 381, 634, 48)
    $paperLabel = New-Object Windows.Forms.Label
    $paperLabel.Text = 'Kertas printer:'
    $paperLabel.SetBounds(18, 327, 130, 24)
    $paper = New-Object Windows.Forms.ComboBox
    $paper.Name = 'PaperProfile'
    $paper.SetBounds(154, 323, 250, 28)
    $paper.DropDownStyle = 'DropDownList'
    $paper.Items.AddRange([object[]]@('A4','Thermal 58 mm','Thermal 80 mm'))
    $profiles=@('a4','thermal58','thermal80')
    $paper.SelectedIndex = [Math]::Max(0, [Array]::IndexOf($profiles, $PaperProfile))

    $install = New-Object Windows.Forms.Button
    $install.Name = 'InstallAgent'
    $install.Text = 'Pakai printer ini'
    $install.SetBounds(358, 440, 170, 32)
    $cancel = New-Object Windows.Forms.Button
    $cancel.Text = 'Batal'
    $cancel.SetBounds(540, 440, 112, 32)
    $cancel.DialogResult = 'Cancel'
    $form.AcceptButton = $install
    $form.CancelButton = $cancel
    $form.Controls.AddRange([Windows.Forms.Control[]]@($intro, $hint, $select, $discover, $refreshButton, $help, $shareLabel, $share, $connect, $example, $paperLabel, $paper, $status, $install, $cancel))

    $state = [pscustomobject]@{ PrinterName = $null; PaperProfile = $PaperProfile; Wizard = $null; Refresh = $null }
    $form.Tag = $state
    $refresh = {
        param([string]$Preferred)
        try {
            $names = @(Get-AgentSelectablePrinters)
            if (-not $Preferred) { $Preferred = $select.Text }
            $select.Items.Clear()
            if ($names.Count) { $select.Items.AddRange([object[]]$names) }
            $select.SelectedIndex = -1
            if ($Preferred -in $names) { $select.SelectedItem = $names | Where-Object { $_ -eq $Preferred } | Select-Object -First 1 }
            elseif ($names.Count -eq 1) { $select.SelectedIndex = 0 }
            $install.Enabled = $select.SelectedIndex -ge 0
            if (-not $names.Count) {
                $status.Text = 'Belum ada printer terhubung. Klik Cari / tambah printer, atau isi alamat sharing. Jendela ini tetap terbuka sampai printer dipilih.'
            } elseif ($Preferred -and $Preferred -notin $names) {
                $status.Text = 'Nama dari aplikasi belum ditemukan. Hubungkan printer itu atau pilih nama yang tersedia pada daftar.'
            } else {
                $status.Text = [string]$names.Count + ' printer tersedia. Pilih pada daftar, lalu Pakai printer ini. Nama PC diambil otomatis dari Windows.'
            }
        } catch {
            $install.Enabled = $false
            $status.Text = 'Daftar printer belum dapat dibaca. Periksa layanan Print Spooler Windows, lalu Perbarui daftar. ' + $_.Exception.Message
        }
    }.GetNewClosure()
    $state.Refresh = $refresh
    $select.Add_SelectedIndexChanged({ $install.Enabled = $select.SelectedIndex -ge 0 }.GetNewClosure())
    $refreshButton.Add_Click({ & $refresh '' }.GetNewClosure())
    $install.Add_Click({
        try {
            $state.PrinterName = Resolve-AgentPrinterSelection $select.Text @(Get-AgentSelectablePrinters)
            $state.PaperProfile = $profiles[$paper.SelectedIndex]
            $form.DialogResult = 'OK'
            $form.Close()
        } catch { $status.Text = $_.Exception.Message }
    }.GetNewClosure())

    $timer = New-Object Windows.Forms.Timer
    $timer.Interval = 1500
    $timer.Add_Tick({
        if ($state.Wizard -and $state.Wizard.HasExited) {
            $timer.Stop()
            $state.Wizard.Dispose()
            $state.Wizard = $null
            $discover.Enabled = $true
            & $refresh ''
            $form.Activate()
        }
    }.GetNewClosure())
    $discover.Add_Click({
        try {
            $state.Wizard = Start-Process -FilePath (Join-Path $env:SystemRoot 'System32\rundll32.exe') -ArgumentList @('printui.dll,PrintUIEntry', '/il') -WindowStyle Normal -PassThru
            $discover.Enabled = $false
            $status.Text = 'Pilih/tambahkan printer pada jendela Windows. Gunakan opsi printer tidak terdaftar untuk IP/hostname atau alamat sharing. Setelah selesai, Perbarui daftar.'
            $timer.Start()
        } catch { $status.Text = 'Pencarian Windows belum dapat dibuka. ' + $_.Exception.Message }
    }.GetNewClosure())
    $connect.Add_Click({
        $connect.Enabled = $false
        $form.UseWaitCursor = $true
        $status.Text = 'Menghubungkan printer sharing. Windows mungkin meminta driver atau izin akses komputer printer.'
        $status.Refresh()
        try {
            $address = Connect-AgentSharedPrinter $share.Text
            & $refresh $address
        } catch { $status.Text = 'Belum terhubung. ' + $_.Exception.Message + ' Periksa alamat sharing, jaringan, dan izin akses; atau gunakan Cari / tambah printer.' }
        finally { $connect.Enabled = $true; $form.UseWaitCursor = $false }
    }.GetNewClosure())
    $form.Add_Disposed({
        $timer.Stop()
        $timer.Dispose()
        if ($state.Wizard) { $state.Wizard.Dispose() }
    }.GetNewClosure())
    & $refresh $PreferredName
    return $form
}

function Show-AgentPrinterSelection([string]$PreferredName, [string]$BranchCode) {
    return (Show-AgentPrinterSettings $PreferredName $BranchCode).PrinterName
}

function Show-AgentPrinterSettings([string]$PreferredName, [string]$BranchCode, [string]$PaperProfile = 'a4') {
    $form = New-AgentPrinterSelectionForm $PreferredName $BranchCode $PaperProfile
    try {
        if ($form.ShowDialog() -ne 'OK' -or -not $form.Tag.PrinterName) { throw 'Pemasangan dibatalkan. Instalasi lama tetap dipertahankan.' }
        return [pscustomobject]@{PrinterName=[string]$form.Tag.PrinterName; PaperProfile=[string]$form.Tag.PaperProfile}
    } finally { $form.Dispose() }
}
