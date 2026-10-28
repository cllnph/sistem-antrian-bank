using System;
using System.Diagnostics;
using System.IO;
using System.Text;

// Compiled as a Windows application: Task Scheduler never allocates a console.
internal static class RunBranchOperations
{
    private static int Main()
    {
        string project = Path.GetFullPath(Path.Combine(AppDomain.CurrentDomain.BaseDirectory, "..", ".."));
        string log = Path.Combine(project, "storage", "logs", "branch-operations-launcher.log");
        try
        {
            string artisan = Path.Combine(project, "artisan");
            if (!File.Exists(artisan))
                throw new FileNotFoundException("Artisan tidak ditemukan pada direktori proyek.");

            string php = Environment.GetEnvironmentVariable("BANK_PHP_PATH");
            if (String.IsNullOrWhiteSpace(php))
                php = File.Exists(@"D:\php\php.exe") ? @"D:\php\php.exe" : "php.exe";

            var output = new StringBuilder();
            var error = new StringBuilder();
            using (var process = new Process())
            {
                process.StartInfo = new ProcessStartInfo
                {
                    FileName = php,
                    Arguments = "\"" + artisan + "\" schedule:run --no-interaction",
                    WorkingDirectory = project,
                    UseShellExecute = false,
                    CreateNoWindow = true,
                    WindowStyle = ProcessWindowStyle.Hidden,
                    RedirectStandardOutput = true,
                    RedirectStandardError = true,
                    RedirectStandardInput = true
                };
                process.OutputDataReceived += delegate(object sender, DataReceivedEventArgs e)
                {
                    if (e.Data != null && output.Length < 65536) output.AppendLine(e.Data);
                };
                process.ErrorDataReceived += delegate(object sender, DataReceivedEventArgs e)
                {
                    if (e.Data != null && error.Length < 65536) error.AppendLine(e.Data);
                };
                process.Start();
                process.StandardInput.Close();
                process.BeginOutputReadLine();
                process.BeginErrorReadLine();
                // The scheduled task enforces the existing five-minute execution limit.
                process.WaitForExit();
                Directory.CreateDirectory(Path.GetDirectoryName(log));
                File.AppendAllText(log, DateTimeOffset.Now.ToString("o") + " exit=" + process.ExitCode
                    + Environment.NewLine + output + error, Encoding.UTF8);
                return process.ExitCode;
            }
        }
        catch (Exception error)
        {
            try
            {
                Directory.CreateDirectory(Path.GetDirectoryName(log));
                File.AppendAllText(log, DateTimeOffset.Now.ToString("o") + " launcher_failed="
                    + error.GetType().Name + Environment.NewLine, Encoding.UTF8);
            }
            catch { }
            return 1;
        }
    }
}
