# Admin, jadwal cabang, dan antrean harian

Pembaruan 28 Oktober 2026. Pengaturan cetak per PC tetap menggunakan installer Windows 1.3.0.

## Dashboard dan cabang

Dashboard memuat ringkasan Customer Service dan Teller serta daftar ringkas cabang. Pilihan cabang di bagian atas berlaku pada kedua ringkasan dan daftar. Status **Dilayani** mencakup tiket dipanggil dan sedang dilayani. Angka menggunakan periode aktif masing-masing cabang, termasuk jika jam reset berbeda.

Pilih **Detail** untuk profil kantor, status loket, jadwal, reset berikutnya, **Buka Kiosk**, **Buka Layar**, dan **Cetak & Printer** sesuai izin. Daftar petugas dan widget antrean terkini sudah dicabut. Akun tetap dikelola melalui Manajemen Pengguna. Alamat lama `/admin/admin-control-center` mengarah ke dashboard.

## Cetak & Printer

1. Buka **Dashboard → Detail Cabang → Cetak & Printer**. Menu yang sama tersedia di Master Kantor Cabang.
2. Untuk cetak otomatis, pilih **Pasang PC baru**, unduh ZIP, ekstrak pada PC printer, lalu jalankan `Install-Agent.cmd`. Nama PC dan printer diambil dari Windows. Gunakan paket dalam 15 menit.
3. Gunakan shortcut lokal **Buka Kiosk** sekali pada browser yang akan dipakai agar pasangan PC terverifikasi. Browser menerima kode sementara, bukan kredensial agen.
4. **Kelola → Ubah label perangkat** hanya mengubah label. **Ganti printer & kertas** hanya menawarkan inventaris milik PC tersebut. Tambahkan driver/printer melalui Windows terlebih dahulu jika belum terlihat.
5. Pergantian printer menunggu cetakan berjalan selesai, membatalkan tugas menunggu, dan memerlukan konfirmasi kesiapan dari penghubung. Pilihan A4, thermal 58 mm, dan thermal 80 mm tetap tersedia. Cetak ulang dalam periode aktif memakai snapshot asli.

Mengulangi unduhan dengan konfirmasi pemasangan yang sama mengembalikan paket yang sama tanpa menambah perangkat sementara. Buka ulang **Pasang PC baru** untuk PC lain. Untuk perangkat yang sudah terdaftar gunakan **Kelola → Unduh Installer**. Kegagalan pembuatan paket membatalkan catatan baru.

PC tanpa installer tetap dapat mencetak melalui dialog browser pada printer yang tersedia di PC itu. Banyak printer dapat terpasang pada Windows; satu penghubung memakai printer yang dipilih saat itu. Kesamaan nama printer, cabang, atau Wi-Fi tidak memasangkan browser secara otomatis.

Tombol unduh Android dicabut dari admin. Penghubung Android yang sudah terpasang tetap didukung. Source dan kunci penandatanganan dipertahankan.

## Waktu Operasional

Admin utama membuka menu **Waktu Operasional** untuk seluruh cabang. Admin cabang melihat jadwal miliknya dan tidak dapat mengubahnya.

- Awal: Senin–Jumat **04.00–18.00 WIB**, Sabtu/Minggu tutup, reset **00.00 WIB**.
- Atur jam setiap hari atau tandai hari tutup. Jam layanan harus selesai pada hari yang sama.
- Jam reset harus di luar seluruh rentang layanan mingguan cabang. Contoh: reset 02.00 WIB sesuai jadwal awal; reset 09.00 WIB ditolak.
- Perubahan jam layanan berlaku setelah disimpan. Perubahan jam reset mengambil waktu berikutnya yang masih di masa depan, tanpa langsung membersihkan antrean.
- Kiosk tetap bisa dibuka ketika tutup; backend menolak penerbitan tiket. Halaman yang sudah terbuka memperbarui status dari waktu server. Petugas dapat menyelesaikan antrean yang tersisa sampai reset.
- **Reset sekarang** menutup seluruh layanan pada satu cabang. Konfirmasi memeriksa periode yang sedang dilihat. Klik ulang konfirmasi yang sama tidak membuat reset kedua.

Mode uji jam sudah dimatikan pada `.env` proyek. Jangan mengaktifkan `KIOSK_TEST_MODE_ENABLED` pada server operasional. Pengujian terisolasi dapat mengaktifkannya secara eksplisit.

Footer kiosk menampilkan informasi jam layanan nasabah yang sama untuk seluruh cabang: **Senin–Jumat 08.00–15.00 WIB**. Teks ini tetap saat halaman memperbarui status; jadwal pengambilan tiket dan reset tetap mengikuti pengaturan Waktu Operasional cabang.

## Periode dan retensi

Reset menutup periode lama dan memulai kembali CS-001/T-001. Pergantian tanggal tidak menambah reset apabila cabang memakai jam reset lain. Cabang lain tidak terpengaruh.

Antrean lama, log pelayanan, snapshot/tugas cetak, jejak nomor tiket pada riwayat perangkat, dan PDF sementara dibersihkan. Cetakan yang sudah memperoleh izin pengiriman dipertahankan hingga selesai atau batas waktunya habis. Hasil yang belum pasti tidak dicetak ulang otomatis. Identitas PC, pasangan browser, konfigurasi, akun, dan akses agen tetap ada.

UUID permintaan disimpan sebagai hash teknis selama tujuh hari, beserta bukti pemilik, cabang, dan layanan. Bukti tersebut tidak menyimpan nomor tiket, isi struk, atau riwayat pelayanan. Antrean baru memerlukan UUID dan konteks terenkripsi yang terikat pada browser, cabang, serta periode.

Ketika koneksi/CSRF/sesi bermasalah, tekan layanan yang sama untuk memeriksa permintaan sebelumnya. UUID dipertahankan. Untuk tiket yang periodenya berakhir, pemilik menerima **410**. Pilih **Mulai permintaan baru**, muat halaman terbaru, lalu pilih layanan secara sengaja. Browser lain tetap ditolak. Data periode lama tidak dapat dipakai untuk cetak ulang setelah dibersihkan.

## Scheduler Windows

Pemasang: `powershell.exe -NoProfile -ExecutionPolicy Bypass -File scripts/Install-BranchOperations.ps1`.

Tugas **BankDPTaspen-Operations** menjalankan `artisan schedule:run` setiap menit dan saat akun Windows masuk melalui peluncur native tanpa konsol. Pemasang mengompilasi `scripts/native/RunBranchOperations.cs` menjadi `tools/BranchOperations/RunBranchOperations.exe`; status keluar diteruskan ke Task Scheduler dan log disimpan di `storage/logs/branch-operations-launcher.log`. Cara ini menggantikan peluncuran PowerShell langsung yang sempat memunculkan Windows Terminal setiap menit meskipun memakai `-WindowStyle Hidden`. `scripts/Run-BranchOperations.ps1` tetap tersedia untuk diagnosis manual.

Tugas menggunakan akun Windows yang memasangnya, tanpa menyimpan kata sandi dalam proyek. Pada instalasi ini PHP dicari di `D:\php\php.exe`; instalasi lain dapat menggunakan `BANK_PHP_PATH` atau PHP pada PATH.

Menu Waktu Operasional menampilkan eksekusi terakhir, kegagalan, dan peringatan bila heartbeat lebih dari tiga menit. Perintah `php artisan branches:process-operations` menjalankan reset yang jatuh tempo dan pembersihan. Permintaan aplikasi juga memeriksa reset terlewat sebelum operasi antrean, sehingga server yang baru hidup tidak menerbitkan tiket pada periode lama.

Reset manual CLI wajib menentukan satu cabang: `php artisan queue:reset --branch=001`. Perintah tanpa cabang ditolak dan tidak memakai `truncate`.

## Backup dan pemulihan

Backup SQL dan source sebelum migrasi berada di `%LOCALAPPDATA%\BankDPTaspenBackups\operations-rollout-20261028-070004`. Folder dilindungi untuk akun Windows pemilik dan SYSTEM. Jangan salin backup berisi konfigurasi/kredensial ke direktori web publik.

Migrasi mempertahankan data lama. Penghapusan dimulai pada reset berikutnya. Rollback migrasi ditolak setelah reset terjadi; pemulihan setelah itu memerlukan backup dan koordinasi agar tiket lama tidak dikirim ulang ke printer.

File yang dicabut diarsipkan di luar proyek. Lihat [manifest pembersihan](CLEANUP-MANIFEST-2026-10-28.json) dan [laporan audit](LAPORAN-AUDIT-ADMIN-2026-10-28.md).

## Pengujian

Jalankan `php artisan test`, `php tests/Integration/verify_operations_mysql.php`, dan `npm.cmd run build` pada Windows. Uji MySQL membuat schema acak terpisah dan menghapus schema uji sendiri; tidak memakai reset/truncate database operasional. `scripts/build.mjs` membatasi pool native plugin pada host kecil dan menghormati konfigurasi thread dari CI.

Pengujian browser terisolasi: `python scratch/run_local_printing_v130.py`. Server fixture dihentikan dalam blok `finally`; proses cetak native diganti simulasi. Untuk hanya browser tertentu atur `PRINT_AUDIT_BROWSERS` ke `edge`, `chrome`, atau `firefox`; nilai `none` menjalankan bagian admin saja. Hasil dan tangkapan layar berada di `scratch/local-printing-v130/`.
