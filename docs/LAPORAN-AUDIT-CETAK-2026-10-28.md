# Audit dan perbaikan cetak per PC — 28 Oktober 2026

## Perubahan yang diterapkan

- Menu **Cetak & Printer** berada pada setiap cabang di Dashboard dan Master Kantor Cabang. Daftar serta aksi perangkat dibatasi pada cabang halaman; konteks Livewire dikunci. Menu gabungan tidak tampil pada sidebar. Pengaturan kertas/tujuan tidak muncul di kiosk; kontrol cetak ulang dan pemulihan sesi tetap tersedia saat diperlukan.
- **Ganti printer & kertas** tersedia pada setiap perangkat, menggunakan inventaris PC tersebut. Perubahan menunggu konfirmasi heartbeat, menolak tugas yang sedang diproses, serta membatalkan tugas yang masih menunggu. Pengaturan cetak lokal tetap khusus browser dan cabang yang dipilih; verifikasi pertama mode otomatis tetap melalui launcher lokal.
- PC/browser baru menggunakan dialog cetak lokal tanpa login admin, installer, nama penghubung, konfigurasi printer server, atau worker server.
- Pasangan lama belum dianggap terverifikasi. Data pasangan tetap disimpan; browser memperoleh petunjuk dan cetak lokal sampai dibuka melalui shortcut terbaru. Pasangan baru yang sudah diverifikasi tetapi kemudian tidak siap tidak dialihkan diam-diam ke printer lain.
- Mode otomatis dipasangkan menggunakan kode sekali pakai selama dua menit yang diterbitkan agen berautentikasi. Kode terikat ke perangkat dan kredensial pemasangan, dikonsumsi secara atomik, serta dikirim ke browser melalui fragment URL. Kredensial permanen tetap lokal dan terlindungi DPAPI.
- Tiket baru dimiliki identitas cookie kiosk terenkripsi, sehingga pergantian sesi/login/logout tetap dapat mengakses nomor yang sama. Tiket lama mempertahankan pemeriksaan sesi; izin cetak ulang petugas tetap berlaku. Status/struk memakai URL pada origin aplikasi yang sama. Respons Forbidden/CSRF menyebut alasan dan kode kejadian; UUID yang belum pasti tidak dibuang otomatis.
- Admin cukup memilih kantor pada **Pasang PC baru**. Installer **1.3.0** mengambil nama PC dari Windows dan menyediakan pilihan printer serta kertas. Izin paket admin baru mengaktifkan penghubung setelah printer dikonfirmasi siap; penonaktifan eksplisit membatalkan aktivasi tertunda.
- Enumerasi native printer lokal/sharing dilengkapi sumber PrintManagement/CIM dengan batas waktu. Kegagalan sumber tambahan tidak menghapus hasil native. Pembacaan inventaris berjalan terpisah dari heartbeat; proses pembaca dibatasi Windows Job Object agar tidak meninggalkan anak proses saat terhenti.
- Shortcut **Buka Kiosk**, **Pilih browser kiosk**, dan **Ganti printer** tersedia. Pengawas memulai ulang agen yang gagal dan berhenti pada kredensial yang dicabut. Versi heartbeat dibaca dari versi paket yang dipasang.
- Pergantian printer/kertas ditolak selama tugas diproses, membatalkan tugas menunggu, dan membutuhkan heartbeat konfirmasi. Saat enqueue, agen dikunci dan perangkat, tujuan, serta profil kertas diperiksa kembali untuk menolak permintaan yang terlambat setelah konfigurasi berubah.
- Profil **A4**, **58 × 150 mm**, dan **80 × 150 mm** disimpan pada snapshot tiket. Layout logo/nomor disesuaikan untuk thermal; cetak ulang tetap menggunakan snapshot asli.
- Tombol Android **Buka Kiosk Cabang** menggunakan pemasangan browser yang sama. APK **1.3.0** dibangun dan tanda tangan v2/v3 diverifikasi. Jalur Android tetap Wi-Fi/IPP A4.
- Worker server mencoba kembali koneksi database dengan jeda bertahap. Pembersihan status tidak menutupi kegagalan awal. Pengiriman cetakan yang hasilnya belum pasti tetap tidak diulang otomatis.

## Verifikasi

- Suite PHP lengkap setelah pemisahan menu cabang: **204 tes lulus, 1.424 assertion**. Termasuk penolakan aksi terhadap perangkat cabang lain, konteks cabang yang dikunci, cabang tetap saat tambah/edit perangkat, pemisahan profil lokal, dan pergantian printer sampai heartbeat baru mengonfirmasi kesiapan.
- Edge, Chrome, dan Firefox: cetak lokal tanpa pendaftaran, cookie setelah logout/pergantian sesi, penolakan browser lain, pemulihan UUID setelah simulasi CSRF 419, pilihan kertas 58 mm, pemasangan browser tamu, serta dua agen bernama printer sama pada PC berbeda. Tidak ada error JavaScript.
- Edge pada halaman admin: navigasi cetak per cabang, daftar perangkat yang terpisah, pengaturan cetak lokal, pergantian printer/kertas melalui menu perangkat, dan unduhan installer lulus. Cabang installer langsung mengikuti halaman yang dibuka; versi serta komponen paket sesuai sumber 1.3.0.
- Windows PowerShell 5.1: pemilihan USB/jaringan/sharing dengan adapter uji, inventaris Windows nyata tanpa cetak, izin folder/DPAPI, paket campuran versi, kegagalan koneksi, pengawas yang pulih dari crash, dan penghentian pada akses yang dicabut.
- Installer hasil unduhan dijalankan dengan adapter server/printer/Windows Shell terisolasi: pemasangan berhasil, pemasangan ulang mempertahankan jurnal, jam PC tidak menggantikan validasi waktu server, kegagalan aktivasi memulihkan konfigurasi/kredensial byte demi byte, serta startup pengawas/tiga shortcut dan proses tersembunyi diverifikasi. Tidak ada shortcut atau startup operasional yang diubah oleh uji tersebut.
- PDF thermal 58 mm dari Edge/Chrome: satu halaman, logo tersedia, dan seluruh teks berada di dalam batas halaman. Layout ketiga ukuran juga diuji melalui renderer server.
- MySQL: upgrade diuji pada skema sementara dengan salinan tabel cetak; isi kolom lama tetap sama, tidak ada aktivasi otomatis, pasangan lama belum terverifikasi, rollback/reapply aman untuk data lama, dan rollback yang akan menghilangkan kepemilikan tiket baru ditolak. Skema sementara dihapus setelah pengujian.
- Android: **13 tes inti lulus**, APK dikompilasi dan signature diverifikasi. Belum dilakukan uji perangkat Android/printer fisik pada audit ini.
- Halaman koneksi aplikasi merespons HTTP 200 melalui port 7100 maupun Apache dengan subfolder. Format PHP, sintaks PowerShell, dan pemeriksaan whitespace lulus.

Artefak browser/PDF berada di `scratch/local-printing-v130/`. Data fixture, token uji, ZIP, database SQLite, dan log server berada pada subfolder `private` yang diabaikan Git dan dibatasi izin Windows. Server browser audit pada port **7114** dihentikan otomatis setelah pengujian. Hasil penerimaan agen pada browser audit disimulasikan melalui API; tidak ada perintah cetak native dikirim.

## Database aplikasi lokal

Migrasi `2026_10_28_010000_verify_local_printing_and_paper_profiles` diterapkan pada MySQL aplikasi, batch **10**. Sebelumnya dibuat cadangan SQL penuh beserta checksum pada:

```text
%LOCALAPPDATA%\BankDPTaspenBackups\printing-v130-20261028-010922\database.sql
```

Sesudah migrasi, jumlah data tetap **7 penghubung, 21 riwayat cetak, 196 antrean, dan 1 pasangan browser**. Tidak ada penghubung lama yang mendapat izin aktivasi tertunda atau pasangan lama yang diverifikasi otomatis. Riwayat operasional tidak dicetak ulang oleh audit.

## Pemeriksaan fisik yang masih perlu dilakukan pada perangkat

Gunakan [panduan cetak per PC](PANDUAN-CETAK-PER-PC-1.3.md). Pada PC cabang/fotokopi, cocokkan satu tiket CS dan Teller dengan kertas yang keluar dari printer PC tersebut, lalu uji pemulihan koneksi. Windows pada lingkungan audit hanya menyediakan printer virtual, sehingga keluarnya kertas pada Epson pengguna belum dibuktikan.

Untuk Panda, konfirmasikan model, sambungan, driver Windows, serta lebar kertas 58/80 mm. Dukungan pemotong otomatis, Bluetooth langsung, atau ESC/POS tidak diasumsikan dari mereknya saja. Printer harus terlebih dahulu dapat mencetak halaman uji Windows.
