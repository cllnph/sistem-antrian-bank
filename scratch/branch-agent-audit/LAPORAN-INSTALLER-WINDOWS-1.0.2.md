# Pengujian installer Windows v1.0.2 — 15 Oktober 2026

Masalah pada gambar pengguna berasal dari paket yang menjalankan `Set-Acl -LiteralPath $directory -AclObject $acl`. Installer terbaru memakai `Protect-AgentDirectory`, yang membaca dan menulis bagian izin akses (DACL) saja. Pemilik, grup, dan aturan audit Windows tidak disalin atau diganti. Pemasangan tidak meminta hak `SeSecurityPrivilege` atau administrator.

Unduhan Windows sekarang mempunyai versi dan waktu pada nama ZIP. `VERSION.txt`, `setup.json`, dan banner pemasangan menunjukkan versi yang sama; campuran versi ditolak sebelum pemasangan. Pengunduhan ulang memakai baris penghubung yang sudah ada. Kunci pemasangan berlaku 15 menit dan sekali pakai.

## Bukti yang dijalankan

- PowerShell 5.1, akun Windows biasa; `whoami /priv` tidak mempunyai `SeSecurityPrivilege`.
- Unduh ZIP lewat aksi native Filament **Unduh Installer** pada server audit lokal `127.0.0.1:7102`. Database memakai SQLite terpisah dan layanan printer aplikasi memakai pengganti. Semua berkas dalam ZIP cocok byte demi byte dengan sumber proyek terbaru.
- Jalankan tes izin menggunakan **ZIP hasil unduhan**, bukan hanya sumber repo: folder baru, percobaan kedua pada folder terlindungi, perbaikan izin tambahan, izin anak folder/file, DPAPI, pemilik/grup tetap, serta penolakan paket campuran versi. Semua lulus. Tes ini berhenti sebelum pemilihan printer atau enrollment.
- Jalankan installer lengkap dari ZIP hasil unduhan: SumatraPDF resmi diperiksa checksum dan tanda tangan; helper printer dikompilasi; perangkat didaftarkan ke server audit; kredensial disimpan melalui DPAPI pada folder privat; shortcut startup tersembunyi dibuat; kunci pemasangan yang telah dipakai dihapus dari paket.
- Jalankan penghubung terpasang dengan `-Once`: heartbeat berhasil, nama printer cocok, dan menu admin menampilkan **Siap**. Jalur cabang audit tetap belum diaktifkan dan tidak ada job/tiket cetak. Shortcut dan instalasi sementara dihapus setelah pengujian.
- Printer default Windows tetap `Microsoft Print to PDF`; pengujian tidak mengirim cetakan fisik.
- Suite aplikasi: **167 tes, 1.092 assertions, seluruhnya lulus**. Pemeriksaan format PHP lulus. Pemeriksaan browser tidak menemukan error JavaScript.

## Artefak

- [Hasil pengunduhan dari menu](windows-installer-ui-results.json)
- [Hasil instalasi dan koneksi](windows-installer-runtime-results.json)
- [Hasil suite regresi](windows-installer-regression.xml)
- [Tampilan status Siap](windows-installer-ready.png)
- Skrip pemeriksaan: `tests/Windows/AgentDirectoryPermissions.ps1`, `scratch/audit_windows_installer.py`, `scratch/verify_windows_installer_download.ps1`, `scratch/verify_windows_installer_status.py`.

Paket berisi kunci sementara dan data sesi audit disimpan pada folder privat yang diabaikan Git. Keberhasilan ini membuktikan pemasangan pada PC pengembangan dengan pengguna biasa. Laptop teman masih perlu menjalankan ZIP v1.0.2 terbaru; hasil kertas dan koneksi dari laptop tersebut belum diverifikasi oleh audit ini.
