# Audit admin, jadwal cabang, dan retensi antrean

Tanggal: 28 Oktober 2026 (WIB). Implementasi menggantikan rancangan dashboard dan reset berbasis tanggal. Perbaikan cetak per PC dan penghubung Windows 1.3.0 tetap digunakan.

## Hasil implementasi

| Area | Temuan | Perbaikan |
|---|---|---|
| Dashboard | Profil, loket, dan akun petugas dimuat sekaligus untuk banyak cabang. Ringkasan berada di halaman lain. | Dashboard berisi CS/Teller dan daftar cabang ringkas. Satu filter membatasi seluruh angka. Profil, jadwal, loket, dan akses perangkat dibuka melalui Detail Cabang. Query akun petugas dan antrean terkini dicabut. |
| Ringkasan | Beberapa hitungan hanya memasukkan status dipanggil dan memakai tanggal kalender. | Status Dilayani mencakup `called` dan `serving`. Semua hitungan memakai periode aktif cabang, termasuk display dan antrean petugas. |
| Halaman lama | Navigasi Kontrol & Laporan menggandakan fungsi dashboard. | Navigasinya dicabut; URL lama mengarah ke dashboard. Kode izin lama dipertahankan agar identitas peran tidak dihapus saat pembaruan. |
| Printer | Banyak kolom, label berulang, dan menu Kelola berpotensi melewati area tabel. | Layout Split/Stack native Filament, teks membungkus, nama PC yang sama tidak diulang, menu membuka dari sisi awal tombol dan tetap berada dalam viewport. Detail memuat diagnosis tambahan. Polling berhenti saat modal terbuka. |
| CRUD printer | Label dan nama antrean printer dapat tercampur dalam form edit. Klik ulang pemasangan dapat membuat placeholder lain. | Edit label dipisahkan dari pemilihan printer/kertas. Pilihan printer dibatasi inventaris PC. Cabang dan kredensial tidak ikut diperbarui dari form. Pemasangan memakai intent bertanda enkripsi dan kunci unik; unduhan ulang mengembalikan paket yang sama. Pembuatan paket gagal membatalkan catatan baru. |
| Android | Terdapat dua aksi unduh pada admin. | Kedua aksi dicabut. API, worker, source, dan kunci penandatanganan Android dipertahankan; tes protokol Android tetap lulus. |
| Jadwal | Jam pada kiosk dan profil berbeda; bypass mode uji masih aktif pada konfigurasi lokal. | Sumber jadwal mingguan per cabang, waktu server WIB, validasi POST, dan status tombol disamakan. `.env` memakai `KIOSK_TEST_MODE_ENABLED=false`. Awal sistem Senin–Jumat 04.00–18.00 WIB, akhir pekan tutup. Sesuai arahan lanjutan, informasi Jam Layanan pada footer kiosk dibuat tetap Senin–Jumat 08.00–15.00 WIB untuk seluruh cabang, terpisah dari jadwal sistem dan reset. |
| Reset | Perintah lama memakai `truncate`; nomor dan query bergantung tanggal. | State periode dan penghitung per layanan. Cabang dikunci sebelum alokasi, reset, dan pemberian izin cetak. Tambahan indeks unik nomor per periode. Reset manual wajib satu cabang; konfirmasi periode dan UUID membuat aksi berulang aman. |
| Query admin | Pembacaan tabel dapat menutup tiket lama dan menulis log. | Mutasi dikeluarkan dari query; layanan periode dan scheduler menangani batas periode. |
| Sesi dan UUID | Sesi yang berubah atau data dibersihkan dapat membuat pemeriksaan ulang kehilangan konteks. | Cookie identitas browser terenkripsi, UUID yang dipertahankan saat CSRF/koneksi gagal, dan `issuance_context` terenkripsi yang terikat pada browser/cabang/periode. Permintaan dari versi lama yang dimuat ulang mempertahankan UUID aslinya. |
| Struk kedaluwarsa | Lookup struk dapat bersaing dengan pembersihan dan menghasilkan 404/objek kosong. | Kepemilikan diperiksa, bukti teknis diperiksa ulang bila lookup berubah, dan pembacaan struk/status dikunci terhadap reset. Pemilik menerima 410 selama bukti masih tersedia; browser lain ditolak. Struk lama yang masih tersedia sebelum reset pertama mempertahankan aturan kepemilikan lama. |
| Retensi | Antrean, log, snapshot, dan artefak print tidak mempunyai pembersihan periode yang sama. | Reset membatalkan tugas menunggu. Pembersihan menghapus relasi sesuai urutan FK, payload worker yang sudah tidak berlaku, jejak nomor tiket perangkat, dan PDF. Identitas PC, pasangan browser, akun, dan konfigurasi tetap ada. |
| Cetak berjalan | Reset berpotensi menimpa proses yang sudah memperoleh izin pengiriman. | Proses yang sudah mendapat izin dipertahankan sampai selesai atau lease habis. Hasil tidak pasti tidak dikirim ulang. Worker server dimuat ulang saat tidak ada tugas server berjalan. |
| Favicon | Pengguna meminta seluruh favicon dihapus, termasuk pengganti SVG transparan. | Konfigurasi favicon admin/login, seluruh tautan ikon halaman, dan aset SVG dicabut. Permintaan `/favicon.ico` serta URL SVG lama mendapat respons 204 tanpa isi dan tanpa cache, sehingga tidak dialihkan ke ikon server. |
| Build | Build Vite/Tailwind sempat berhenti pada fase transform di host Windows ini. | Wrapper build menyiapkan pool thread native sebelum proses Node anak dimulai. Build standar `npm.cmd run build` selesai tanpa mengubah versi dependensi. |

## Bukti pengujian

| Pemeriksaan | Hasil |
|---|---|
| Suite regresi lengkap | **218 tes, 1.503 assertions, lulus**. Hasil mesin: `storage/logs/operations-regression.json`. |
| MySQL terisolasi | Migrasi sebelum/sesudah, inisialisasi nomor dari data aktif, data lama tidak dibersihkan saat migrasi, rollback aman sebelum reset dan ditolak setelah reset: lulus. |
| Konkurensi MySQL | Empat proses menjalankan 32 alokasi bersamaan dengan satu reset. Tidak ada nomor ganda pada periode aktif; konfigurasi printer tetap ada. Schema uji dihapus pada akhir pemeriksaan. |
| Periode dan akses | Konteks hilang/palsu, browser lain, replay UUID, sesi berubah, konteks periode lama, respons 410, reset berulang, pergantian tanggal dengan reset 03.00, serta retensi bukti tujuh hari: lulus. |
| Pembersihan | Antrean, log, snapshot, payload worker terkait, dan jejak nomor perangkat dibersihkan; pekerjaan lain dan pasangan printer dipertahankan. Proses kirim yang belum selesai tidak dikirim ulang: lulus. |
| Edge, Chrome, Firefox | Cetak lokal tanpa pendaftaran, akses struk setelah logout, pemulihan CSRF dengan UUID yang sama, thermal 58 mm, pemasangan browser, dan pemisahan dua PC bernama printer identik: lulus, tanpa error JavaScript. |
| Responsivitas admin | Dashboard, Detail Cabang, Cetak & Printer, dan Waktu Operasional pada **390, 768, 1366, 1920 px**: 16 kombinasi lulus tanpa overflow horizontal elemen utama. |
| Modal dan Kelola | Dropdown berada di viewport; label yang sedang diketik bertahan melewati interval pembaruan; form jadwal dan pilihan printer/kertas dapat digunakan: lulus. |
| Pemulihan versi lama | Permintaan tertunda tanpa konteks dari versi sebelumnya dimuat ulang, memperoleh konteks halaman terbaru, dan tetap menerbitkan satu tiket menggunakan UUID semula: lulus pada browser. |
| Unduhan installer | ZIP 1.3.0 berisi helper dan launcher yang sesuai source; nama printer ditentukan saat pemasangan, profil awal A4: lulus. |
| Format dan aset | Pint pada source yang disentuh, kompilasi Blade, build Vite, dan pemeriksaan sintaks 171 file PHP: lulus. |
| Verifikasi perubahan akhir | Pemeriksaan terarah halaman cabang/printer, periode, label peran, dan pemasangan lokal: 41 tes dengan 369 assertions lulus. |
| Scheduler lokal | Tugas Windows `BankDPTaspen-Operations` memakai peluncur native Windows tanpa konsol; heartbeat database berstatus `ok` dan hasil tugas Windows 0 telah diperiksa. |
| Jendela terminal berulang | Reproduksi peluncur PowerShell lama memunculkan Windows Terminal/OpenConsole. Setelah peluncur diganti, uji manual dan satu putaran jadwal otomatis tidak menemukan jendela konsol baru; hasil tugas 0. Bukti putaran otomatis: `storage/logs/scheduler-console-check.json`. Fixture terpisah memastikan kode keluar PHP 0/23 diteruskan dan output tercatat. Konfigurasi tugas sebelumnya dicadangkan di `%LOCALAPPDATA%\BankDPTaspenBackups\scheduler-console-fix`. |

Hasil browser dan daftar ukuran layar: `scratch/local-printing-v130/results.json`. Tangkapan layar tersedia sebagai `operations-dashboard-*`, `operations-detail-*`, `operations-printers-*`, `operations-schedule-*`, serta modal terkait di direktori yang sama. Database dan kredensial fixture berada pada subdirektori `private` yang dibatasi ACL dan diabaikan Git. Tidak ada perintah cetak native yang dijalankan oleh audit browser.

Tes printer fisik CS/Teller pada PC cabang, PC fotokopi, dan printer kecil **belum dilakukan dalam lingkungan ini**. Simulasi protokol tidak membuktikan kertas keluar, ukuran aktual driver, maupun pemotongan kertas Panda. Model Panda dan driver Windows perlu dipastikan pada uji perangkat.

## Peluncuran dan keadaan data

Backup SQL serta source sebelum migrasi disimpan di `%LOCALAPPDATA%\BankDPTaspenBackups\operations-rollout-20261028-070004`, di luar web root dan dilindungi ACL untuk pemilik Windows serta SYSTEM. SQL berukuran 4.603.338 byte. Backup sebelum perubahan juga tersedia pada direktori `operations-admin-20261028-063654` di lokasi induk yang sama.

Migrasi `2026_10_28_070000_add_branch_operations_and_queue_periods` telah diterapkan pada database proyek. Pemeriksaan sesudah migrasi: sembilan cabang memiliki sembilan state periode, dua tiket aktif pada periode berjalan, dan 196 tiket lama masih tersedia untuk pembersihan terjadwal pertama. Sembilan catatan penghubung dan tiga pasangan browser tetap ada. Tidak ada antrean tanpa cabang.

Reset awal berikutnya adalah **29 Oktober 2026 pukul 00.00 WIB** untuk seluruh cabang, selama admin belum mengubahnya. Tidak ada reset manual pada data operasional selama audit ini. Perubahan jadwal reset selalu memilih waktu berikutnya di masa depan.

Pada kelanjutan sesi, MySQL lokal ditemukan berhenti setelah shutdown normal yang tercatat 07.20 WIB. Proses MySQL dengan konfigurasi XAMPP yang sama telah dijalankan kembali, koneksi dipastikan pulih, dan scheduler kembali melaporkan sukses. Layanan Windows MySQL terdaftar otomatis, tetapi kontrol service ditolak bagi sesi pengguna ini; menjalankan proses lokal tidak mengubah konfigurasi atau hak service tersebut.

## Pembersihan proyek

**17 file** diarsipkan dan diverifikasi SHA-256 sebelum dihapus dari proyek: empat widget, satu view widget, tiga controller admin tanpa route, empat gambar cadangan/pratinjau, empat hasil uji lama, dan favicon ICO lama. Arsip berada pada `%LOCALAPPDATA%\BankDPTaspenBackups\operations-cleanup-20261028\retired-files.zip`.

[Manifest lengkap](CLEANUP-MANIFEST-2026-10-28.json) memuat path, alasan, hasil pemeriksaan referensi, ukuran, dan hash. Migrasi, database, `.env`, dependensi, helper printer, harness uji yang masih dipakai, source Android, serta kunci penandatanganan tidak dihapus. Dokumen historis tetap menjadi catatan versi sebelumnya; panduan aktif mengarah ke [panduan admin terbaru](PANDUAN-ADMIN-DAN-JADWAL.md).

## Saran berdasarkan pemeriksaan

1. Pertahankan database dan scheduler pada akun/mesin server yang tetap aktif saat cabang melayani. Kejadian MySQL berhenti menunjukkan bahwa aplikasi tetap memerlukan proses database yang berjalan. Untuk layanan sepanjang hari, pastikan administrator Windows memverifikasi service yang sudah terdaftar, bukan bergantung pada jendela XAMPP yang dapat ditutup.
2. Jadikan halaman Waktu Operasional sebagai pemeriksaan awal harian: jadwal cabang, reset berikutnya, dan heartbeat scheduler. Jika heartbeat lewat tiga menit, periksa status tugas Windows dan database. Pemeriksaan reset pada request menjaga periode, tetapi server yang mati tetap tidak dapat menerima tiket.
3. Gunakan HTTPS dan akun database khusus aplikasi saat peluncuran produksi. Lingkungan yang diaudit memakai alamat HTTP LAN dan akun database lokal `root`; izin paket HTTP hanya untuk konfigurasi lokal/pengujian yang sudah ada. Konfigurasi produksi dan hak akses database lintas aplikasi perlu dipisahkan oleh pengelola server.
4. Lakukan uji halaman Windows, tiket CS dan Teller, pergantian Epson ke thermal, batas kertas panjang, dan pemotongan pada perangkat asli sebelum pemakaian cabang. Cocokkan tujuan fisik dengan PC kiosk, bukan nama printer saja.
5. Tetapkan retensi dan akses backup di luar aplikasi. Antrean runtime dibersihkan per periode; backup pemulihan memang berisi data sebelum pembersihan dan harus dikelola secara terpisah. Bukti permintaan teknis pada database runtime dibatasi tujuh hari. Setelah bukti itu habis, URL yang tidak lagi dikenal dapat menghasilkan 404; konteks periode lama tetap tidak dapat menerbitkan tiket baru.

Rujukan teknis build: laporan upstream [Vite 8/Tailwind pada Rolldown](https://github.com/rolldown/rolldown/issues/10664) menunjukkan pola hang serupa. Kaitan dengan kasus lokal merupakan inferensi; keberhasilan wrapper thread pada host ini diverifikasi melalui build, tanpa menyatakan semua penyebab hang sama. Untuk perilaku pengulangan tugas Windows, lihat [dokumentasi Microsoft Task Scheduler](https://learn.microsoft.com/en-us/windows/win32/taskschd/repeating-a-task).
