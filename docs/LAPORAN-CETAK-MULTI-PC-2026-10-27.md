# Perbaikan cetak multi-PC — 27 Oktober 2026

## Hasil

Satu kantor, termasuk Pusat, dapat mempunyai beberapa penghubung dan printer. Menu **Kelola** di sebelah kiri setiap baris menyediakan Detail, Ubah, Hapus, Aktifkan/Nonaktifkan, Cabut Akses, unduhan, dan **Gunakan pada PC ini**.

Browser baru memakai **Printer PC ini** melalui dialog cetak lokal tanpa installer. Mode otomatis menggunakan pasangan browser–kantor–penghubung. IP dan Wi-Fi tidak menentukan tujuan. Penghubung yang tidak siap/dicabut/dihapus tidak mengalihkan tugas ke server atau printer lain.

Printer Windows penghubung v1.1.0 dapat diganti dari daftar yang dilaporkan PC, dengan konfirmasi sebelum kembali Siap. Perubahan tujuan menolak tugas yang masih diproses dan membatalkan tugas menunggu. Hapus mempertahankan riwayat. Struk browser dan cetak ulang memakai nomor/snapshot asli; penutupan dialog bukan bukti kertas keluar.

## Verifikasi

- Suite lengkap: **183 tes lulus, 1.214 assertions**, memakai SQLite terpisah dan gateway printer pengganti.
- Audit MySQL terpisah: upgrade dari skema lama, indeks foreign key, dua printer pada cabang sama, backfill jalur tugas lama, penolakan rollback yang kehilangan data, serta rollback/reapply yang aman. Database audit dihapus setelah pemeriksaan.
- Browser Edge: memindahkan Epson dari cabang salah ke Pusat yang sudah berisi printer; tambah tanpa nama printer; detail/hapus; mengunduh ZIP v1.1.0 beserta SumatraPDF; cetak lokal pada dua browser; membuka kembali nomor sama; menolak akses struk dari sesi lain; memasangkan PC dan kembali ke mode lokal. Tidak ada error JavaScript. Dialog juga diperiksa pada layar 390 px.
- Windows PowerShell 5.1: ZIP hasil unduhan berhasil dipasang pada akun biasa; status printer fisik dibaca tanpa mengirim cetakan; heartbeat v1.1.0 dan inventaris printer diterima server audit.
- Penolakan aktivasi disimulasikan setelah persiapan selesai: konfigurasi dan kredensial instalasi lama pulih byte demi byte, token server lama tetap berlaku, dan tidak ada `stop.request` tertinggal.
- Izin folder/DPAPI, paket campuran versi, dan kegagalan koneksi diuji dari ZIP unduhan. Koneksi gagal sebelum pemasangan tidak mengubah instalasi lama.
- Format PHP dan pemeriksaan whitespace lulus.

Bukti browser/Windows dan tangkapan layar berada di `scratch/multi-printer-audit/`. Paket, sesi, dan database audit berada pada subfolder `private` yang dilindungi izin Windows dan diabaikan Git.

Server audit pada port 7110 dan proxy kesalahan pada port 7111 sudah dihentikan. Penghapusan folder instalasi uji `%LOCALAPPDATA%\BankDPTaspenPrintAgent\ef542cef-b3a2-45df-8a15-99d6b2ae93b7` ditolak pemeriksaan otomatis (`blocked by policy`); folder tersebut tidak terdaftar di Startup dan tidak menjalankan penghubung latar belakang.

## Aplikasi yang digunakan pengguna

Migrasi `2026_10_27_190000_support_device_print_routing` sudah diterapkan ke MySQL aplikasi, batch 9. Cadangan SQL lengkap dibuat terlebih dahulu pada folder privat `%LOCALAPPDATA%\BankDPTaspenBackups\print-routing-20261027-201131\`. Lima penghubung operasional dan riwayat cetak dipertahankan; pengujian tidak memindahkan atau menghapus data Epson operasional secara otomatis.

Halaman Apache `/sistem-antrian-bank/public/ambil-nomor/001` merespons HTTP 200 dan menampilkan tujuan lokal untuk browser baru. Server lama pada port 7100 tidak sedang berjalan saat pemeriksaan; paket baru harus diunduh dari alamat aplikasi yang benar-benar digunakan.

## Cara mencoba

1. Muat ulang aplikasi. Untuk Epson yang salah cabang, pilih **Kelola → Ubah → Kantor Pusat → Simpan**.
2. Pada PC lain yang sudah dapat mencetak dari Windows, buka kiosk dan tekan **Ambil Tiket**. Pilih printer PC tersebut pada dialog, lalu Cetak. Tidak memerlukan penghubung untuk cara ini.
3. Untuk mode otomatis, unduh paket **v1.1.0**, ekstrak seluruhnya, jalankan **Install-Agent.cmd**, tunggu Siap, Aktifkan, lalu pilih **Gunakan pada PC ini** dari browser pada PC printer tersebut.

Tidak ada cetakan fisik dikirim oleh audit ini. Kertas dari Epson L3250 pada PC lain, nomor CS/Teller, logo, alamat, footer, dan ukuran kertas masih perlu diperiksa pada perangkat tujuan. Akses dari warnet/fotokopi di luar LAN memerlukan alamat aplikasi yang dapat dijangkau dari sana.
