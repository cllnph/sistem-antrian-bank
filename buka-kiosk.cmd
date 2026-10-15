@echo off
setlocal
powershell.exe -NoLogo -NoProfile -ExecutionPolicy Bypass -File "%~dp0scripts\Start-Kiosk.ps1" -CreateShortcut
if errorlevel 1 (
    echo Kiosk tidak dapat dibuka. Periksa pesan kesalahan di atas.
    pause
    exit /b 1
)
endlocal
