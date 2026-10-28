# Pemeriksaan cetak Android — 15 Oktober 2026

- APK Android API 26–35 dibangun dari sumber lokal dan diverifikasi memakai APK Signature Scheme v2/v3. APK disimpan privat dan tersedia melalui aksi admin.
- Regresi aplikasi: **157 tes, 1.015 assertions**, tanpa error/failure (`regression.xml`).
- Tambahan aksi unduh Paket Android native Filament: **1 tes, 6 assertions**, lulus, dijalankan setelah regresi penuh.
- Inti Android/Java: **13 tes**, lulus. Meliputi RLE PWG dengan decoder independen, format IPP, socket timeout tanpa replay, kehilangan respons izin mulai, kegagalan render/jurnal, restart tidak pasti, dan hilangnya ACK.
- Emulasi browser sentuh pada 360×800, 1024×768, dan 800×1280: CS/Teller, klik ganda, tanpa AbortSignal.timeout, tanpa dialog print/tab tambahan, tanpa error JavaScript (`browser-results.json`). Penerbitan pada audit browser dicegat oleh pengganti layanan; tidak membuat tiket operasional.
- Epson L6290 asli ditemukan melalui mDNS pada `192.168.11.128`, port 631, resource `ipp/print`. Pemeriksaan IPP biasa menerima HTTP 426 (TLS diwajibkan). Pemeriksaan IPPS dengan fingerprint yang cocok berhasil.
- Printer mengiklankan A4, PWG Raster, sRGB 8-bit, serta resolusi 300 dan 600 dpi. Tidak mengiklankan dukungan PDF langsung; jalur Android merender PDF menjadi PWG Raster.
- Pemeriksaan printer asli hanya Get-Printer-Attributes. **Tidak mengirim Print-Job atau mencetak kertas.**

Belum dibuktikan: instalasi APK/izin pada Android fisik, layanan latar belakang pada merek HP/tablet sasaran, dan hasil kertas CS/Teller dari perangkat Android. Hal tersebut tetap memerlukan UAT pengguna.
