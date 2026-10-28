Built from `scripts/native/RunBranchOperations.cs` by `scripts/Install-BranchOperations.ps1`.
The executable is a Windows application rather than a console application. It runs PHP
with `UseShellExecute=false` and `CreateNoWindow=true`, and preserves the scheduler's
exit status. No PowerShell, CMD, or scripting host is used as the task's entry point.

The task still runs every minute and at logon, with a five-minute execution limit.
Output and launcher failures go to `storage/logs/branch-operations-launcher.log`.
Source hashes are checked during installation; generated binaries are excluded from Git.
`scripts/Run-BranchOperations.ps1` remains available for manual CLI diagnosis.

Windows process reference:
https://learn.microsoft.com/en-us/dotnet/api/system.diagnostics.processstartinfo.createnowindow
