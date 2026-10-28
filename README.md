# Sistem Antrean Bank DP Taspen

Aplikasi Laravel dan Filament untuk kiosk, pemanggilan CS/Teller, display cabang, serta cetak lokal dan penghubung printer per PC.

- [Panduan admin, jadwal, dan reset harian](docs/PANDUAN-ADMIN-DAN-JADWAL.md)
- [Panduan cetak per PC 1.3.0](docs/PANDUAN-CETAK-PER-PC-1.3.md)
- [Laporan audit admin](docs/LAPORAN-AUDIT-ADMIN-2026-10-28.md)
- [Manifest file yang diarsipkan](docs/CLEANUP-MANIFEST-2026-10-28.json)

Jalankan PHP, database, server aplikasi, dan scheduler menggunakan konfigurasi lokal yang sudah disiapkan. Jangan menyalin `.env`, backup SQL, atau kredensial penghubung ke folder publik.

Validasi perubahan: `php artisan test`, `php tests/Integration/verify_operations_mysql.php`, dan `npm.cmd run build`. Pengujian fisik printer memakai halaman uji Windows dan PC cabang yang sesuai; simulasi otomatis tidak membuktikan kertas sudah keluar.
