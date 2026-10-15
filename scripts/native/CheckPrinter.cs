using System;
using System.Runtime.InteropServices;
using System.Text.RegularExpressions;

// Read-only Winspool check. No printing, settings changes, or UI.
public static class CheckPrinter
{
    [DllImport("winspool.drv", CharSet = CharSet.Unicode, SetLastError = true)]
    private static extern bool OpenPrinter(string name, out IntPtr handle, IntPtr defaults);
    [DllImport("winspool.drv", CharSet = CharSet.Unicode, SetLastError = true)]
    private static extern bool GetPrinter(IntPtr handle, uint level, IntPtr buffer, uint length, out uint needed);
    [DllImport("winspool.drv")]
    private static extern bool ClosePrinter(IntPtr handle);

    [StructLayout(LayoutKind.Sequential)]
    private struct PrinterInfo
    {
        public IntPtr Server, Name, Share, Port, Driver, Comment, Location, DevMode;
        public IntPtr Separator, Processor, DataType, Parameters, Security;
        public uint Attributes, Priority, DefaultPriority, StartTime, UntilTime, Status, Jobs, AveragePpm;
    }

    private static int Fail(int exitCode, string reason, int nativeError = 0)
    {
        Console.WriteLine("{\"ready\":false,\"code\":\"" + reason + "\",\"native_error\":" + nativeError + "}");
        return exitCode;
    }

    public static int Main(string[] args)
    {
        IntPtr handle = IntPtr.Zero, buffer = IntPtr.Zero;
        try
        {
            if (args.Length != 1 || String.IsNullOrWhiteSpace(args[0]) || Regex.IsMatch(args[0], "[\\x00-\\x1f\"]|PDF|XPS|OneNote|Fax", RegexOptions.IgnoreCase))
                return Fail(2, "invalid_destination");
            if (!OpenPrinter(args[0], out handle, IntPtr.Zero))
                return Fail(2, "printer_unavailable", Marshal.GetLastWin32Error());
            uint size;
            GetPrinter(handle, 2, IntPtr.Zero, 0, out size);
            if (size == 0) return Fail(5, "status_unavailable", Marshal.GetLastWin32Error());
            buffer = Marshal.AllocHGlobal(checked((int)size));
            if (!GetPrinter(handle, 2, buffer, size, out size))
                return Fail(5, "status_unavailable", Marshal.GetLastWin32Error());
            var info = (PrinterInfo)Marshal.PtrToStructure(buffer, typeof(PrinterInfo));
            string driver = Marshal.PtrToStringUni(info.Driver) ?? "";
            string port = Marshal.PtrToStringUni(info.Port) ?? "";
            if (Regex.IsMatch(driver, "PDF|XPS|OneNote|Fax", RegexOptions.IgnoreCase) || String.Equals(port, "PORTPROMPT:", StringComparison.OrdinalIgnoreCase))
                return Fail(2, "virtual_destination");
            if ((info.Status & 0x80) != 0 || (info.Attributes & 0x400) != 0)
                return Fail(3, "offline");
            // Paused, error, deleting, paper jam/out/problem, unavailable, no toner,
            // user intervention, out of memory, open door, unknown server.
            const uint blocked = 0x1 | 0x2 | 0x4 | 0x8 | 0x10 | 0x40 | 0x1000 | 0x40000 | 0x100000 | 0x200000 | 0x400000 | 0x800000;
            if ((info.Status & blocked) != 0) return Fail(4, "not_ready");
            Console.WriteLine("{\"ready\":true,\"status\":" + info.Status + "}");
            return 0;
        }
        catch { return Fail(5, "check_failed"); }
        finally
        {
            if (buffer != IntPtr.Zero) Marshal.FreeHGlobal(buffer);
            if (handle != IntPtr.Zero) ClosePrinter(handle);
        }
    }
}
