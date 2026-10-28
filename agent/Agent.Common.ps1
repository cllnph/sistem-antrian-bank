Set-StrictMode -Version 2.0
$ErrorActionPreference = 'Stop'
Add-Type -AssemblyName System.Net.Http
[Net.ServicePointManager]::SecurityProtocol = [Net.SecurityProtocolType]::Tls12
$script:AgentComponentRoot = $PSScriptRoot

function Start-AgentInventoryRead {
    $start = New-Object Diagnostics.ProcessStartInfo
    $start.FileName = Join-Path $env:SystemRoot 'System32\WindowsPowerShell\v1.0\powershell.exe'
    $start.Arguments = '-NoProfile -NonInteractive -ExecutionPolicy Bypass -File "' + (Join-Path $script:AgentComponentRoot 'Printer.Inventory.ps1') + '"'
    $start.UseShellExecute=$false; $start.CreateNoWindow=$true; $start.RedirectStandardOutput=$true; $start.RedirectStandardError=$true
    $process = New-Object Diagnostics.Process
    $process.StartInfo=$start
    $process.Start() | Out-Null
    return [pscustomobject]@{Process=$process; Output=$process.StandardOutput.ReadToEndAsync(); Errors=$process.StandardError.ReadToEndAsync(); Deadline=[DateTime]::UtcNow.AddSeconds(15)}
}

function Receive-AgentInventoryRead($Read) {
    if (-not $Read.Process.HasExited -and [DateTime]::UtcNow -lt $Read.Deadline) { return $null }
    try {
        if (-not $Read.Process.HasExited) { $Read.Process.Kill(); throw 'Pembacaan daftar printer melewati batas waktu.' }
        $value=$Read.Output.GetAwaiter().GetResult() | ConvertFrom-Json
        if (-not $value.ok) { throw 'Daftar printer belum dapat dibaca. Periksa Print Spooler Windows.' }
        return $value
    } finally { $Read.Process.Dispose() }
}

function Get-AgentPrinterInventory {
    $read=Start-AgentInventoryRead
    do {
        $value=Receive-AgentInventoryRead $read
        if ($null -ne $value) { return $value }
        Start-Sleep -Milliseconds 50
    } while ($true)
}

function New-AgentAuthenticatedClient($Config, [string]$Directory) {
    $secure=(Get-Content -LiteralPath (Join-Path $Directory 'credential.dpapi') -Raw).Trim() | ConvertTo-SecureString
    $pointer=[Runtime.InteropServices.Marshal]::SecureStringToBSTR($secure)
    try { return New-AgentHttpClient $Config ([Runtime.InteropServices.Marshal]::PtrToStringBSTR($pointer)) }
    finally { [Runtime.InteropServices.Marshal]::ZeroFreeBSTR($pointer) }
}

function Protect-AgentDirectory([string]$Path) {
    $directory = Get-Item -LiteralPath $Path -Force
    if (-not ($directory -is [IO.DirectoryInfo])) { throw 'Folder penghubung tidak valid.' }

    # Persist only the DACL. Set-Acl copies the full security descriptor and can
    # request SeSecurityPrivilege for its audit section, even for a user's folder.
    # Keep ownership, group and auditing intact; installation needs no elevation.
    $acl = $directory.GetAccessControl([Security.AccessControl.AccessControlSections]::Access)
    $acl.SetAccessRuleProtection($true, $false)
    foreach ($rule in @($acl.GetAccessRules($true, $false, [Security.Principal.SecurityIdentifier]))) {
        $acl.RemoveAccessRuleSpecific($rule)
    }
    $identity = [Security.Principal.WindowsIdentity]::GetCurrent().User
    $system = [Security.Principal.SecurityIdentifier]::new('S-1-5-18')
    foreach ($principal in @($identity, $system)) {
        $acl.AddAccessRule([Security.AccessControl.FileSystemAccessRule]::new(
            $principal,
            [Security.AccessControl.FileSystemRights]::FullControl,
            [Security.AccessControl.InheritanceFlags]'ContainerInherit, ObjectInherit',
            [Security.AccessControl.PropagationFlags]::None,
            [Security.AccessControl.AccessControlType]::Allow))
    }
    $directory.SetAccessControl($acl)
}

function Assert-AgentServerUrl([string]$Address, [bool]$AllowHttp) {
    $uri = $null
    if (-not [Uri]::TryCreate($Address, [UriKind]::Absolute, [ref]$uri) -or $uri.UserInfo -or $uri.Query -or $uri.Fragment -or
        ($uri.Scheme -ne 'https' -and -not ($AllowHttp -and $uri.Scheme -eq 'http'))) { throw 'Alamat aplikasi harus HTTPS. HTTP hanya untuk pengujian lokal yang diizinkan.' }
}

function New-AgentHttpClient($Config, [string]$Token = '') {
    Assert-AgentServerUrl $Config.server_url ([bool]$Config.allow_http)
    $handler = [Net.Http.HttpClientHandler]::new()
    $handler.AllowAutoRedirect = $false
    $client = [Net.Http.HttpClient]::new($handler)
    $client.Timeout = [TimeSpan]::FromSeconds(15)
    $client.MaxResponseContentBufferSize = 2097152
    $client.DefaultRequestHeaders.Accept.ParseAdd('application/json')
    if ($Token) {
        $client.DefaultRequestHeaders.Authorization = [Net.Http.Headers.AuthenticationHeaderValue]::new('Bearer', $Token)
        $client.DefaultRequestHeaders.Add('X-Print-Agent', [string]$Config.agent_id)
        $client.DefaultRequestHeaders.Add('X-Print-Device', [string]$Config.device_id)
    }
    return $client
}

function Invoke-AgentRequest($Client, $Config, [string]$Path, $Body = $null, [string]$Claim = '', [switch]$Pdf) {
    $method = if ($Pdf) { [Net.Http.HttpMethod]::Get } else { [Net.Http.HttpMethod]::Post }
    $address = ([string]$Config.server_url).TrimEnd('/') + $Path
    $request = [Net.Http.HttpRequestMessage]::new($method, $address)
    if ($Claim) { $request.Headers.Add('X-Print-Claim', $Claim) }
    if (-not $Pdf) { $request.Content = [Net.Http.StringContent]::new(($Body | ConvertTo-Json -Depth 10 -Compress), [Text.Encoding]::UTF8, 'application/json') }
    $response = $null
    try {
        $response = $Client.SendAsync($request).GetAwaiter().GetResult()
        if (-not $response.IsSuccessStatusCode) {
            $message = switch ([int]$response.StatusCode) {
                401 { if ($Path -match '/(preflight|prepare|activate-enrollment)$') { 'Kunci pemasangan kedaluwarsa, sudah dipakai, atau diganti. Unduh installer baru.' } else { 'Akses penghubung tidak berlaku. Unduh installer baru dari admin pusat.' } }
                403 { 'Akses ditolak. Periksa alamat HTTPS dan pemasangan penghubung.' }
                404 { 'Alamat layanan tidak ditemukan (HTTP 404). Periksa alamat dasar aplikasi pusat lengkap, termasuk subfolder jika ada, lalu unduh installer baru.' }
                409 { 'Status tugas atau penghubung berubah. Periksa melalui admin pusat.' }
                422 { 'Data pemasangan atau printer tidak valid. Periksa printer yang dipilih dan gunakan paket terbaru.' }
                429 { 'Permintaan terlalu sering. Penghubung akan menunggu sebelum mencoba kembali.' }
                default { 'Aplikasi pusat belum dapat melayani permintaan (HTTP ' + [int]$response.StatusCode + ').' }
            }
            $errorObject = [Exception]::new($message)
            $errorObject.Data['HttpStatus'] = [int]$response.StatusCode
            throw $errorObject
        }
        if ($Pdf) {
            if ($response.Content.Headers.ContentType.MediaType -ne 'application/pdf') { throw 'Respons bukan PDF tiket.' }
            $hash = @($response.Headers.GetValues('X-Pdf-Sha256'))[0]
            $bytes = $response.Content.ReadAsByteArrayAsync().GetAwaiter().GetResult()
            if ($bytes.Length -lt 5 -or [Text.Encoding]::ASCII.GetString($bytes, 0, 5) -ne '%PDF-') { throw 'PDF tiket tidak valid.' }
            $sha = [Security.Cryptography.SHA256]::Create()
            try { $actual = ([BitConverter]::ToString($sha.ComputeHash($bytes))).Replace('-', '').ToLowerInvariant() } finally { $sha.Dispose() }
            if ($hash -notmatch '^[a-f0-9]{64}$' -or $actual -ne $hash) { throw 'Checksum PDF tiket tidak cocok.' }
            return @{ Bytes=$bytes; Hash=$hash }
        }
        return ($response.Content.ReadAsStringAsync().GetAwaiter().GetResult() | ConvertFrom-Json)
    } catch {
        if ($_.Exception.Data['HttpStatus']) { throw }
        $cause = $_.Exception
        while ($cause.InnerException) { $cause = $cause.InnerException }
        $message = if ($cause -is [Threading.Tasks.TaskCanceledException] -or $cause -is [OperationCanceledException]) {
            'Koneksi ke aplikasi pusat melewati batas waktu. Pastikan alamat dapat dibuka dari PC ini dan server sedang berjalan.'
        } elseif ($cause -is [Net.Sockets.SocketException]) {
            'Aplikasi pusat tidak dapat dihubungi. Periksa alamat, port, jaringan, dan apakah server sedang berjalan.'
        } elseif ($cause -is [Security.Authentication.AuthenticationException]) {
            'Sertifikat HTTPS aplikasi pusat tidak dapat diverifikasi. Periksa alamat dan sertifikat server.'
        } else { 'Permintaan ke aplikasi pusat gagal: ' + $cause.Message }
        throw [Exception]::new($message, $cause)
    } finally {
        if ($response) { $response.Dispose() }
        $request.Dispose()
    }
}

function Write-AgentJson([string]$Path, $Value) {
    $bytes = [Text.Encoding]::UTF8.GetBytes(($Value | ConvertTo-Json -Depth 10 -Compress))
    $temporary = $Path + '.' + [Guid]::NewGuid().ToString('N') + '.tmp'
    $file = [IO.FileStream]::new($temporary, [IO.FileMode]::CreateNew, [IO.FileAccess]::Write, [IO.FileShare]::None, 4096, [IO.FileOptions]::WriteThrough)
    try { $file.Write($bytes, 0, $bytes.Length); $file.Flush($true) } finally { $file.Dispose() }
    if ([IO.File]::Exists($Path)) {
        $backup = $Path + '.bak'
        [IO.File]::Replace($temporary, $Path, $backup)
        [IO.File]::Delete($backup)
    } else { [IO.File]::Move($temporary, $Path) }
}

function Get-AgentPrinterStatus($Config, [string]$Directory) {
    $start = New-Object Diagnostics.ProcessStartInfo
    $start.FileName = Join-Path $Directory 'CheckPrinter.exe'
    $start.Arguments = '"' + $Config.printer_name + '"'
    $start.UseShellExecute = $false; $start.CreateNoWindow = $true
    $start.RedirectStandardOutput = $true
    $process = New-Object Diagnostics.Process
    $process.StartInfo = $start
    try {
        $process.Start() | Out-Null
        if (-not $process.WaitForExit(10000)) { $process.Kill(); return @{ ready=$false; error='Printer tidak merespons. Periksa koneksi.' } }
        $message = switch ($process.ExitCode) {
            0 { $null }
            2 { 'Printer tidak ditemukan atau bukan printer fisik.' }
            3 { 'Printer offline. Periksa daya dan koneksi.' }
            4 { 'Printer belum siap. Periksa kertas, penutup, dan antrean printer.' }
            default { 'Status printer belum dapat diperiksa.' }
        }
        return @{ ready=($process.ExitCode -eq 0); error=$message }
    } finally { $process.Dispose() }
}

function Write-AgentLog([string]$Directory, [string]$Message) {
    $path = Join-Path $Directory 'agent.log'
    if ((Test-Path -LiteralPath $path) -and (Get-Item -LiteralPath $path).Length -gt 1048576) { Move-Item -LiteralPath $path -Destination ($path+'.previous') -Force }
    Add-Content -LiteralPath $path -Value ((Get-Date -Format o) + ' ' + $Message) -Encoding UTF8
}
