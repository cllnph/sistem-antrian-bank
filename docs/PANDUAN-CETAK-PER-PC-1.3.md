> Pengaturan dashboard, jadwal, reset, dan retensi terbaru: [Panduan admin](PANDUAN-ADMIN-DAN-JADWAL.md). Struk hanya tersedia selama periode aktif.

# Cetak dari PC cabang, fotokopi, dan printer thermal

## Cetak biasa tanpa pemasangan

1. Pastikan printer dapat mencetak halaman uji dari Windows pada PC yang dipakai.
2. Buka alamat aplikasi yang dapat dijangkau dari PC itu, kemudian kiosk kantor yang benar.
3. Browser baru memakai kertas **A4**. Jika perlu mengganti ukuran, admin membuka **Dashboard → Detail Cabang → Cetak & Printer → Cetak lokal PC ini** pada PC tersebut, lalu memilih **A4**, **Thermal 58 mm**, atau **Thermal 80 mm**. Pilihan tersimpan untuk browser dan kantor ini.
4. Tekan **Ambil Tiket**. Pilih printer yang benar pada dialog Cetak PC tersebut.

Tidak perlu login admin, mengetahui nama perangkat penghubung, atau mendaftarkan nama printer. Komputer yang menjalankan aplikasi pusat menyimpan nomor antrean; mode ini menggunakan dialog printer pada PC pengunjung.

Jika dialog dibatalkan, gunakan **Cetak tiket terakhir** untuk nomor yang sama. Status `browser_ready` berarti struk tersedia, bukan bukti kertas keluar. Printer jaringan yang terlihat di dialog tetap mengikuti pengaturan Windows; pilih tujuan yang memang ingin digunakan.

## Cetak otomatis setelah pemasangan sekali

1. Admin membuka **Dashboard → Detail Cabang → Cetak & Printer → Pasang PC baru**, lalu mengunduh ZIP **1.3.0**. Cabang otomatis mengikuti halaman yang dibuka. Menu yang sama tersedia pada **Master Kantor Cabang → Cetak & Printer**. Unduhan boleh dilakukan dari PC admin pusat.
2. Pada PC yang memiliki printer, **Extract All / Ekstrak Semua**, kemudian jalankan **Install-Agent.cmd**. Paket harus digunakan dalam 15 menit. Gunakan akun Windows biasa yang akan mengoperasikan kiosk.
3. Pilih antrean printer yang terdeteksi dan ukuran kertas. Printer USB/jaringan/sharing harus sudah tersedia bagi akun Windows tersebut. Jika belum, gunakan **Cari / tambah printer** atau alamat sharing lengkap.
4. Installer memeriksa printer, menyiapkan kredensial, dan memulai penghubung. Setelah heartbeat mengonfirmasi printer siap, paket admin mengaktifkan cetak otomatis.
5. Gunakan shortcut desktop **Buka Kiosk**. Untuk mengganti browser, gunakan **Pilih browser kiosk**; pilihan berikutnya tersimpan.

Browser menerima kode pemasangan sementara, bukan kredensial agen. Setiap browser mempunyai identitas sendiri. Membuka shortcut memasangkan browser ke PC printer tanpa login admin. Aktifkan dari admin pusat hanya mengizinkan penghubung menerima tugas; tindakan itu tidak memasangkan browser admin pusat ke printer cabang.

Pasangan lama tidak langsung dipercaya setelah upgrade. Buka sekali melalui shortcut installer terbaru. Browser baru yang belum dipasangkan menggunakan cetak biasa. Jika pasangan yang sudah diverifikasi dicabut, dipindahkan, atau dipasang ulang pada PC lain, kiosk menjelaskan penyebabnya saat mengambil tiket. Untuk beralih secara sadar ke dialog lokal, admin memilih **Cetak lokal PC ini** pada halaman cetak cabang menggunakan PC dan browser kiosk tersebut.

## Menu cetak per cabang

Daftar perangkat, pemasangan PC, pergantian printer, status koneksi, dan pilihan kertas berada pada **Cetak & Printer** masing-masing cabang. Menu gabungan **Penghubung Cetak Cabang** tidak lagi ditampilkan pada navigasi utama. Alamat menu lama mengarah ke daftar cabang; riwayat dan pemasangan yang sudah ada tetap tersimpan.

Tombol **Buka Kiosk** membuka kiosk cabang tersebut menggunakan pasangan browser yang sudah ada. Pada PC baru, verifikasi mode otomatis tetap dilakukan sekali melalui launcher **Buka Kiosk** dari installer. Tombol web tidak dapat membuktikan identitas pemasangan Windows atau menjalankan program lokal pada PC lain.

Halaman kiosk tidak menampilkan tujuan printer, pilihan kertas, atau menu pengaturan. Setelah tiket diambil, tombol kecil **Cetak tiket terakhir** tetap tersedia untuk membuka struk yang sama. Tombol pemulihan sesi hanya muncul jika diperlukan.

Hak akses pengaturan tetap mengikuti admin yang sudah diizinkan. Memindahkan menu tidak memberi izin pengaturan printer kepada nasabah atau operator.

## Mengganti Epson dengan printer kecil

Buka **Cetak & Printer** pada cabang, lalu pada perangkat yang sesuai pilih **Kelola → Ganti printer & kertas**. Pilih antrean Windows yang dilaporkan PC tersebut dan kertas **58 mm** atau **80 mm**. Tunggu konfirmasi kesiapan agen. Tidak perlu mengisi ulang nama PC, membuat penghubung baru, atau mengunduh ZIP lagi. Shortcut lokal **Ganti printer** tetap dapat digunakan sebagai bantuan saat printer baru belum terbaca; setelah menambahkan driver melalui Windows, tunggu pembaruan daftar pada halaman cabang.

Penggantian ditolak selama tugas masih diproses. Tugas yang menunggu dibatalkan agar tidak berpindah tujuan secara diam-diam. Tiket tetap tersedia sampai reset periode cabang. Tiket baru menggunakan ukuran baru; cetak ulang mempertahankan snapshot dan ukuran tiket aslinya.

Printer Panda harus memiliki driver Windows yang berfungsi dan ukuran kertas yang benar. Dukungan Bluetooth, pemotong otomatis, atau ESC/POS langsung belum dinyatakan cocok sebelum model perangkat diketahui. Android tetap memakai jalur printer Wi-Fi/IPP A4 yang sudah didukung; tombol **Buka Kiosk Cabang** kini memasangkan browser secara otomatis.

## Jika gagal

- **Forbidden / akses ditolak:** baca pesan dan kode kejadian. Identitas tiket baru bertahan melewati pergantian sesi selama cookie kiosk tetap ada. Browser lain dan cookie yang sudah dihapus tidak mendapatkan akses ke struk tersebut. Tiket lama yang dimiliki sesi lama dapat memerlukan bantuan petugas untuk cetak ulang.
- **Sesi kedaluwarsa:** pilih **Muat ulang sesi, pertahankan permintaan**, lalu tekan layanan yang sama. UUID permintaan dipertahankan agar aplikasi memeriksa nomor yang sama.
- **Printer belum ditemukan:** bandingkan daftar Windows pada akun pemasang. Nama pada label admin bukan bukti bahwa antrean itu sudah dipasang. Installer tidak menciptakan tujuan hanya dari nama yang diketik.
- **Pemasangan gagal:** pesan menyebut tahap dan log `%TEMP%\BankDPTaspen-Install-*.log`. Kegagalan persiapan mempertahankan instalasi sebelumnya.
- **Hasil cetak belum pasti:** periksa kertas dan antrean Windows sebelum meminta cetak ulang. Sistem mengulangi koneksi/laporan hasil, bukan pengiriman cetakan yang mungkin sudah keluar.

Penghubung Windows berjalan setelah pengguna pemasang login. Printer, jaringan, dan aplikasi pusat harus tetap tersedia. Di luar LAN kantor, alamat aplikasi harus dapat dijangkau dari jaringan tersebut; `localhost` menunjuk PC pembuka sendiri.
