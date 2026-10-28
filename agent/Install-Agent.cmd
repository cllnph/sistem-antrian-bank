@echo off
setlocal
title Pemasangan Penghubung Cetak Bank DP Taspen
if not exist "%~dp0Install-Agent.ps1" (
    echo.
    echo File pemasangan Install-Agent.ps1 tidak ditemukan di folder ini.
    echo Klik kanan file ZIP unduhan, lalu pilih Extract All / Ekstrak Semua.
    echo Buka folder hasil ekstraksi, lalu jalankan Install-Agent.cmd di sana.
    echo Jangan menjalankan Install-Agent.cmd langsung dari dalam ZIP.
    echo Jika unduhan sudah lewat 15 menit, unduh installer baru terlebih dahulu.
    echo.
    pause
    exit /b 1
)
powershell.exe -NoProfile -STA -ExecutionPolicy Bypass -File "%~dp0Install-Agent.ps1"
set "installResult=%errorlevel%"
if not "%installResult%"=="0" (
    echo.
    echo Pemasangan belum berhasil. Periksa pesan dan versi installer di atas.
    pause
)
exit /b %installResult%
