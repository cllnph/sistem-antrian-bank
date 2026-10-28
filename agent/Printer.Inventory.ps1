[CmdletBinding()]
param([ValidateSet('All','Native','Management','Cim')][string]$Source = 'All')
$ErrorActionPreference = 'Stop'

function Read-InventorySource([string]$Kind) {
    switch ($Kind) {
        'Management' { return @(Get-Printer -ErrorAction Stop | Select-Object Name,DriverName,PortName) }
        'Cim' { return @(Get-CimInstance -ClassName Win32_Printer -ErrorAction Stop | Select-Object Name,DriverName,PortName) }
        'Native' {
            Add-Type -TypeDefinition @'
using System;
using System.Collections.Generic;
using System.Runtime.InteropServices;
public static class BankPrinterInventory {
    [DllImport("kernel32.dll", CharSet=CharSet.Unicode)] static extern IntPtr CreateJobObject(IntPtr attributes, string name);
    [DllImport("kernel32.dll")] static extern bool SetInformationJobObject(IntPtr job, int type, IntPtr info, uint length);
    [DllImport("kernel32.dll")] static extern bool AssignProcessToJobObject(IntPtr job, IntPtr process);
    [DllImport("kernel32.dll")] public static extern bool CloseHandle(IntPtr handle);
    public static IntPtr ChildLifetime() {
        IntPtr job=CreateJobObject(IntPtr.Zero,null);
        if(job==IntPtr.Zero) throw new Exception("Cannot create inventory lifetime");
        int size=IntPtr.Size==8?144:112; IntPtr info=Marshal.AllocHGlobal(size);
        try {
            for(int i=0;i<size;i++) Marshal.WriteByte(info,i,0);
            Marshal.WriteInt32(info,16,0x2000);
            if(!SetInformationJobObject(job,9,info,(uint)size)) { CloseHandle(job); throw new Exception("Cannot limit inventory lifetime"); }
        } finally { Marshal.FreeHGlobal(info); }
        return job;
    }
    public static bool Attach(IntPtr job, IntPtr process) { return AssignProcessToJobObject(job,process); }
    [DllImport("winspool.drv", CharSet=CharSet.Unicode, SetLastError=true)]
    static extern bool EnumPrinters(uint flags, string name, uint level, IntPtr buffer, uint size, out uint needed, out uint returned);
    [StructLayout(LayoutKind.Sequential)] struct Printer4 { public IntPtr Name, Server; public uint Attributes; }
    public static string[] Names() {
        uint bytes, count;
        EnumPrinters(6, null, 4, IntPtr.Zero, 0, out bytes, out count);
        if (bytes == 0) {
            int error=Marshal.GetLastWin32Error();
            if (error != 0 && error != 122) throw new System.ComponentModel.Win32Exception(error);
            return new string[0];
        }
        IntPtr buffer=Marshal.AllocHGlobal(checked((int)bytes));
        try {
            if (!EnumPrinters(6,null,4,buffer,bytes,out bytes,out count)) throw new System.ComponentModel.Win32Exception(Marshal.GetLastWin32Error());
            var result=new List<string>(); int size=Marshal.SizeOf(typeof(Printer4));
            for(int i=0;i<count;i++) {
                var info=(Printer4)Marshal.PtrToStructure(IntPtr.Add(buffer,i*size),typeof(Printer4));
                string name=Marshal.PtrToStringUni(info.Name); if(!String.IsNullOrWhiteSpace(name)) result.Add(name);
            }
            return result.ToArray();
        } finally { Marshal.FreeHGlobal(buffer); }
    }
}
'@
            return @([BankPrinterInventory]::Names() | ForEach-Object { [pscustomobject]@{Name=$_; DriverName=''; PortName=''} })
        }
    }
}

if ($Source -ne 'All') {
    try { @{ok=$true; printers=@(Read-InventorySource $Source)} | ConvertTo-Json -Depth 4 -Compress }
    catch { @{ok=$false; printers=@()} | ConvertTo-Json -Depth 4 -Compress }
    exit 0
}

# Run independent providers concurrently. A stuck network/WMI provider cannot block the others.
$reads = @()
$childJob=[IntPtr]::Zero
try {
    $native=@()
    $nativeOk=$false
    try { $native=@(Read-InventorySource 'Native'); $nativeOk=$true } catch { }
    try { $childJob=[BankPrinterInventory]::ChildLifetime() } catch { }
    $fallbacks=if ($childJob -ne [IntPtr]::Zero) { @('Management','Cim') } else { @() }
    foreach ($kind in $fallbacks) {
        $start = New-Object Diagnostics.ProcessStartInfo
        $start.FileName = Join-Path $env:SystemRoot 'System32\WindowsPowerShell\v1.0\powershell.exe'
        $start.Arguments = '-NoProfile -NonInteractive -ExecutionPolicy Bypass -File "' + $PSCommandPath + '" -Source ' + $kind
        $start.UseShellExecute=$false; $start.CreateNoWindow=$true; $start.RedirectStandardOutput=$true; $start.RedirectStandardError=$true
        $process = New-Object Diagnostics.Process
        $process.StartInfo=$start
        $process.Start() | Out-Null
        if (-not [BankPrinterInventory]::Attach($childJob,$process.Handle)) { $process.Kill(); $process.Dispose(); continue }
        $reads += [pscustomobject]@{Process=$process; Output=$process.StandardOutput.ReadToEndAsync(); Errors=$process.StandardError.ReadToEndAsync()}
    }
    $deadline=[DateTime]::UtcNow.AddSeconds(6)
    while (@($reads | Where-Object { -not $_.Process.HasExited }).Count -and [DateTime]::UtcNow -lt $deadline) { Start-Sleep -Milliseconds 50 }
    $names = [Collections.Generic.Dictionary[string,object]]::new([StringComparer]::OrdinalIgnoreCase)
    $successful=0
    if ($nativeOk) {
        $successful=1
        foreach ($printer in $native) { $names[$printer.Name]=$printer }
    }
    foreach ($read in $reads) {
        if (-not $read.Process.HasExited) { $read.Process.Kill(); continue }
        try {
            $value=$read.Output.GetAwaiter().GetResult() | ConvertFrom-Json
            if ($value.ok) {
                $successful++
                foreach ($printer in @($value.printers)) {
                    if ($printer.Name -and (-not $names.ContainsKey($printer.Name) -or $printer.DriverName)) { $names[$printer.Name]=$printer }
                }
            }
        } catch { }
    }
    $physical=@($names.Values | Where-Object { $_.Name -notmatch '[\x00-\x1f"]|PDF|XPS|OneNote|Fax' -and $_.DriverName -notmatch 'PDF|XPS|OneNote|Fax' -and $_.PortName -ne 'PORTPROMPT:' } | Sort-Object Name)
    @{ok=($successful -gt 0); printers=$physical; sources=$successful} | ConvertTo-Json -Depth 4 -Compress
} finally {
    foreach ($read in $reads) {
        if (-not $read.Process.HasExited) { $read.Process.Kill() }
        $read.Process.Dispose()
    }
    if ($childJob -ne [IntPtr]::Zero) { [BankPrinterInventory]::CloseHandle($childJob) | Out-Null }
}
