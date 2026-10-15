# Cetak tiket langsung melalui server

Buka `http://192.168.11.60:7100/ambil-nomor` dari tab Edge biasa. Tekan **Ambil Tiket** pada Customer Service atau Teller. Server menerbitkan nomor, membuat PDF privat, lalu mengirim satu salinan ke printer. Tidak ada preview browser, pemilihan printer, tab PDF, atau tombol Print tambahan. Shortcut Desktop tetap tersedia sebagai cara cepat membuka URL; cetak tidak bergantung pada shortcut.

## Kesiapan server

Layanan cetak dan shortcut Startup sudah dipasang pada PC server ini. Worker berjalan tersembunyi sebagai pengguna Windows yang memasang printer, dimulai sekarang dan otomatis ketika pengguna tersebut login. PC server dan aplikasi harus tetap hidup; pengguna Windows pemilik printer harus sudah login. Menutup Edge tidak menghentikan worker.

Pemasangan ulang pada server lain dilakukan sekali oleh pengelola setelah `composer install`:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File scripts\Install-TicketPrinting.ps1 -PhpPath D:\php\php.exe
```

Installer memverifikasi checksum dan tanda tangan SumatraPDF portable 3.6.1, menjalankan migrasi, memasang Startup worker, lalu menjalankannya. Aplikasi PDF default, profil Edge/Chrome harian, dan pengaturan otomatis printer Windows tidak diubah. Helper menjaga printer default selama worker berjalan dan memulihkannya setelah pengiriman; pergantian manual ke printer lain tetap dihormati.

Pemeriksaan kesiapan printer menggunakan helper Winspool privat `tools/TicketPrinter/CheckPrinter.exe`, dikompilasi dari `scripts/native/CheckPrinter.cs` oleh installer. Pemeriksaan ini tidak mencetak atau mengubah pengaturan printer dan menghindari pemuatan modul PowerShell pada setiap permintaan. Jika helper belum terpasang, pengelola dapat menjalankan `scripts/Install-TicketPrinterCheck.ps1` sekali. Pesan aplikasi membedakan printer tidak ditemukan, offline, bermasalah, dan tidak merespons.

Antrean cetak memakai koneksi database `printing` dan antrean `ticket-printing`, terpisah dari antrean aplikasi biasa. Jangan menjalankan worker generik untuk antrean ini. Perintah worker khusus: `php artisan tickets:work`. Log supervisor berada di `storage/logs/ticket-worker-output.log` dan `ticket-worker-error.log`.

## Konfigurasi printer

`kiosk.config.json`:

```json
{
  "url": "http://192.168.11.60:7100/ambil-nomor",
  "printer_name": "ITPRINTER (L6290 Series)",
  "paper": {"width_mm": 80, "height_mm": 150},
  "branch_printers": {}
}
```

Pada `local`/`testing`, semua cabang boleh memakai `printer_name` untuk UAT. Produksi wajib memetakan setiap **kode kantor** di `branch_printers`, misalnya `{"001": "Printer Pusat", "002": "Printer Bogor"}`. Printer harus terpasang dan dapat diakses dari akun Windows worker di server; printer USB pada PC klien tidak otomatis dapat diakses server. Tujuan PDF/XPS/Fax/OneNote ditolak. Browser tidak boleh menentukan printer, executable, atau lokasi file.

Ukuran PDF awal 80 × 150 mm, satu halaman dan satu salinan tanpa pembesaran. Printer L6290 dipakai untuk uji; pada kertas A4 isi struk tetap berukuran asli. Ukuran dan driver printer thermal diperiksa saat perangkat tersedia. SumatraPDF privat berada di `tools/SumatraPDF`; PDF sementara berada di `storage/app/private/ticket-printing` dan dihapus setelah pemrosesan. Restart worker setelah perubahan kode/config PHP; JSON printer dibaca pada setiap permintaan.

## Status dan pemulihan

UUID permintaan mencegah penerbitan nomor/job ganda saat koneksi terputus dan permintaan diulang. Browser menyimpan permintaan yang belum pasti pada sesi tab; tekan **layanan yang sama** untuk memeriksa nomor sebelumnya. Klik ganda ditahan. Kegagalan cetak tidak menerbitkan nomor pengganti otomatis.

| Status | Arti dan tindakan |
|---|---|
| `queued` | Menunggu worker, maksimum 60 detik sebelum mulai. |
| `processing` | Worker menyiapkan/mengirim cetakan. |
| `submitted` | Jalur pengiriman menerima cetakan; tetap periksa kertas. |
| `failed` | Gagal sebelum pengiriman, misalnya render atau printer tidak siap. |
| `unknown` | Timeout, proses terputus, atau hasil pengiriman tidak pasti. Periksa printer dan antrean Windows sebelum cetak ulang. |
| `expired` | Belum mulai setelah 60 detik; tidak dicetak otomatis. |

Worker tidak mencoba ulang job otomatis. Job terputus menjadi `unknown` saat worker pulih; bukan dicetak ulang. Endpoint status dibatasi untuk sesi peminta atau petugas berizin. Ledger `print_jobs` menyimpan nomor/tiket, snapshot struk, printer, waktu, kegagalan, dan identitas petugas cetak ulang.

Jika printer/konfigurasi/worker tidak siap sebelum penerbitan, aplikasi menampilkan pesan dan tidak membuat tiket. Jika gagal sesudah tiket tersimpan, pesan menyebut nomor tiket tersebut. Petugas dengan izin edit antrean pada kantor/layanan yang sesuai dapat memakai **Daftar Antrean → Cetak Ulang**. Admin pusat dapat mengakses seluruh cabang. Tindakan ini meminta konfirmasi, menggunakan snapshot dan nomor lama, serta mencatat percobaan baru. Cetak ulang ditolak selama masih ada percobaan aktif.

## UAT saat printer tersedia

1. Nyalakan L6290, siapkan kertas, dan hubungkan ke PC server. Pastikan Windows tidak melaporkan printer offline/error.
2. Buka URL biasa di Edge; tidak perlu membuka shortcut khusus atau mengubah pengaturan browser.
3. Tekan CS sekali. Kertas harus keluar tanpa dialog; cocokkan nomor dengan Daftar Antrean.
4. Tekan Teller sekali dan periksa kembali nomor, logo, kantor, alamat, tanggal, serta footer. Telepon hanya tampil untuk pusat. URL browser tidak tercetak.
5. Uji klik ganda: satu permintaan menghasilkan satu tiket/job. Periksa printer default Windows tetap seperti sebelumnya.
6. Jika hasil tidak pasti, periksa antrean printer terlebih dahulu; gunakan Cetak Ulang untuk nomor lama jika diperlukan.

Pengujian otomatis saat implementasi memakai SQLite terpisah dan pengganti layanan printer. Pada 15 Oktober 2026, setelah printer tersedia dan pemeriksa native dipasang, pengguna mengonfirmasi **tiket sudah keluar** melalui tombol kiosk. Job yang tersimpan dalam percobaan terbaru adalah Teller: T-003 dan T-004 berstatus `submitted`; percobaan awal T-002 berstatus `unknown` dan tidak dicoba ulang otomatis. Konfirmasi ini belum menyebut nomor pada kertas secara spesifik. UAT fisik Customer Service serta pemeriksaan logo, alamat, footer, dan kecocokan nomor masih perlu dilengkapi. `submitted` sendiri tidak membuktikan kertas keluar.

Bukti otomatis: `scratch/filament-native-audit/server-printing.xml`. Bukti Edge biasa: `scratch/server-print-audit/browser-results.json`. Contoh PDF: `scratch/server-print-audit/receipt-cs.pdf`.
