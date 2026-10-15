param(
    [Parameter(Mandatory=$true)][string]$Executable,
    [Parameter(Mandatory=$true)][string]$PdfPath,
    [Parameter(Mandatory=$true)][string]$PrinterName
)
$ErrorActionPreference = 'Stop'
# A Windows Job Object kills the native child if PHP/the helper is interrupted.
Add-Type -TypeDefinition @'
using System;
using System.Runtime.InteropServices;
using System.Text;
public static class TicketNative {
    [DllImport("kernel32.dll", CharSet=CharSet.Unicode)] public static extern IntPtr CreateJobObject(IntPtr attributes, string name);
    [DllImport("kernel32.dll")] public static extern bool SetInformationJobObject(IntPtr job, int type, IntPtr info, uint length);
    [DllImport("kernel32.dll")] public static extern bool AssignProcessToJobObject(IntPtr job, IntPtr process);
    [DllImport("kernel32.dll")] public static extern bool CloseHandle(IntPtr handle);
    [DllImport("winspool.drv", CharSet=CharSet.Unicode)] static extern bool GetDefaultPrinter(StringBuilder buffer, ref int length);
    [DllImport("winspool.drv", CharSet=CharSet.Unicode)] public static extern bool SetDefaultPrinter(string printer);
    public static string DefaultPrinter() {
        int length=0; GetDefaultPrinter(null, ref length); if (length==0) return null;
        var buffer=new StringBuilder(length); return GetDefaultPrinter(buffer, ref length) ? buffer.ToString() : null;
    }
    public static IntPtr KillOnCloseJob() {
        var job=CreateJobObject(IntPtr.Zero, null);
        // JOBOBJECT_EXTENDED_LIMIT_INFORMATION: LimitFlags offset16, native structure size144 on x64.
        int size=IntPtr.Size==8 ? 144 : 112;
        var info=Marshal.AllocHGlobal(size);
        try {
            for(int i=0;i<size;i++) Marshal.WriteByte(info,i,0);
            Marshal.WriteInt32(info,16,0x2000); // JOB_OBJECT_LIMIT_KILL_ON_JOB_CLOSE
            if(!SetInformationJobObject(job,9,info,(uint)size)) { CloseHandle(job); throw new Exception("Cannot enforce process lifetime"); }
        } finally { Marshal.FreeHGlobal(info); }
        return job;
    }
}
'@
$nativeJob = [IntPtr]::Zero
$previous = [TicketNative]::DefaultPrinter()
$result = 1
try {
    if ($PrinterName -match '[\x00-\x1f"]|PDF|XPS|OneNote|Fax' -or $Executable -match '["\r\n]' -or $PdfPath -match '["\r\n]') { throw 'Argumen cetak tidak valid.' }
    if (-not (Test-Path -LiteralPath $Executable -PathType Leaf) -or -not (Test-Path -LiteralPath $PdfPath -PathType Leaf)) { throw 'Berkas cetak tidak tersedia.' }
    $nativeJob = [TicketNative]::KillOnCloseJob()
    $arguments = @('-new-window', '-print-to', ('"' + $PrinterName + '"'), '-silent', '-print-settings', '"noscale,1x,simplex"', ('"' + $PdfPath + '"'))
    $process = Start-Process -FilePath $Executable -ArgumentList $arguments -WindowStyle Hidden -PassThru
    if (-not [TicketNative]::AssignProcessToJobObject($nativeJob, $process.Handle)) {
        Stop-Process -Id $process.Id -Force -ErrorAction SilentlyContinue
        throw 'Tidak dapat membatasi proses printer.'
    }
    if (-not $process.WaitForExit(20000)) {
        $result = 124
    } else {
        $process.Refresh()
        $result = $process.ExitCode
    }
} catch { Write-Error $_ -ErrorAction Continue; $result = 1 }
finally {
    if ($nativeJob -ne [IntPtr]::Zero) { [TicketNative]::CloseHandle($nativeJob) | Out-Null }
    # Respect a manual switch to a third printer; undo only an automatic switch to our destination.
    if ($previous -and $previous -ne $PrinterName -and [TicketNative]::DefaultPrinter() -eq $PrinterName) {
        [TicketNative]::SetDefaultPrinter($previous) | Out-Null
    }
}
exit $result
