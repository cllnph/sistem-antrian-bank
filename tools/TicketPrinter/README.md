Private, read-only Windows printer check compiled from `scripts/native/CheckPrinter.cs`.
Build/install with `scripts/Install-TicketPrinterCheck.ps1` (also invoked by the printing installer).
The source SHA256 is recorded beside the executable. Binaries are excluded from Git.

Uses Windows Winspool OpenPrinter/GetPrinter/ClosePrinter directly. This avoids loading the
PowerShell PrintManagement module on every ticket request. It does not print or change settings.
Exit codes: 0 ready, 2 missing/virtual destination, 3 offline, 4 known printer problem, 5 check failed.
The PHP caller enforces a 10-second timeout; cached Windows status does not establish that paper emerged.

Windows API references:
- https://learn.microsoft.com/en-us/windows/win32/printdocs/getprinter
- https://learn.microsoft.com/en-us/windows/win32/printdocs/printer-info-2
