> Panduan admin terbaru: [Jadwal dan antrean harian](PANDUAN-ADMIN-DAN-JADWAL.md). Untuk pemasangan baru gunakan Pasang PC baru pada Detail Cabang. Cabang perangkat yang terdaftar tidak dapat dipindahkan melalui form. Petunjuk perpindahan cabang di versi sebelumnya digantikan dengan pemasangan perangkat baru pada cabang tujuan.

# Penghubung cetak per cabang

**Versi 1.3.0:** gunakan [panduan cetak per PC](PANDUAN-CETAK-PER-PC-1.3.md) untuk alur terbaru. Menu berada pada **Dashboard → masing-masing cabang → Cetak & Printer**, juga tersedia pada **Master Kantor Cabang**. Pengaturan printer dan kertas tidak tampil di kiosk. Langkah Aktifkan lalu Gunakan pada PC ini di bawah merupakan alur versi lama; pasangan otomatis baru diverifikasi melalui launcher Buka Kiosk.

Untuk HP/tablet Android dengan Epson L6290 melalui Wi-Fi tanpa PC penghubung cabang, gunakan [panduan cetak Android](PANDUAN-CETAK-ANDROID.md). Aksi **Unduh Installer** pada panduan di bawah tetap khusus Windows; Android menggunakan **Unduh Aplikasi Android** dan **Unduh Paket Android**.

Aplikasi pusat dapat dijalankan pada hosting Linux atau Windows. Satu kantor dapat mempunyai beberapa PC dan printer, termasuk Kantor Pusat. Setiap penghubung menggunakan satu printer yang dipilih saat pemasangan. Tugas dikirim ke penghubung yang dipasangkan ke browser dan kantor tersebut, bukan ke penghubung pertama atau berdasarkan IP/Wi-Fi.

Browser baru yang belum dipasangkan selalu memakai **Printer PC ini** melalui dialog cetak lokal, tanpa installer. Printer harus sudah dapat dipakai dari Windows dan aplikasi harus bisa diakses. Untuk warnet/fotokopi di luar jaringan kantor, gunakan alamat aplikasi yang dapat diakses dari sana; alamat IP LAN kantor saja tidak cukup. Pada browser yang sudah dipasangkan, penghubung mencetak otomatis tanpa dialog. PC penghubung tidak memerlukan PHP/XAMPP, kredensial database, atau port masuk.

## Memasang pada cabang

1. Masuk sebagai admin pusat, buka **Cabang & Loket → Penghubung Cetak Cabang**.
2. Pilih **Tambah Penghubung**, pilih kantor dan isi nama PC. Nama printer boleh dikosongkan sampai pemasangan. Kantor yang sudah mempunyai penghubung tetap dapat dipilih lagi.
3. Klik **Unduh Installer**, periksa alamat aplikasi pusat, lalu unduh ZIP. Untuk aplikasi online gunakan alamat HTTPS yang dapat dibuka dari cabang.
4. Pada PC Windows yang akan mencetak, klik kanan ZIP → **Extract All / Ekstrak Semua**. Buka folder hasil ekstraksi, pastikan **Install-Agent.cmd** dan **Install-Agent.ps1** ada bersama, lalu jalankan **Install-Agent.cmd**. Jangan menjalankannya langsung dari dalam ZIP atau menyalin hanya file CMD. Pilih printer fisik pada jendela pemasangan; jika belum terhubung, gunakan **Cari / tambah printer** atau alamat sharing. Installer berlaku 15 menit; unduh ulang jika kedaluwarsa.
5. Tunggu status **Siap**, pilih **Kelola → Aktifkan**, lalu dari browser pada PC printer tersebut pilih **Kelola → Gunakan pada PC ini**. Mengaktifkan penghubung saja tidak memindahkan tujuan browser lain.
6. Buka kiosk kantor yang sesuai, misalnya `/ambil-nomor/002` untuk Bogor. Uji satu CS dan satu Teller serta cocokkan nomor, logo, alamat, dan footer pada kertas.

Pemasangan dilakukan sekali. Sesudahnya penghubung berjalan tersembunyi dan otomatis ketika pengguna Windows tersebut login. PC serta printer harus tetap aktif dan mempunyai akses internet ke aplikasi pusat.

Kebutuhan cabang: Windows 10/11 **64 bit**, Windows PowerShell 5.1 dengan .NET Framework, serta driver printer yang dapat dipakai akun Windows tersebut. Installer **v1.1.0** menyertakan SumatraPDF portable 3.6.1 resmi dan memeriksa checksum serta tanda tangannya. Tidak ada unduhan komponen tambahan dari PC. Hosting pembuat paket harus mempunyai executable resmi di `tools/SumatraPDF`; paket ditolak jika komponen tidak tersedia atau checksum berbeda. Helper printer dikompilasi dari sumber yang disertakan.

Paket, alamat aplikasi lengkap, koneksi, kunci pemasangan, dan kesiapan printer diperiksa sebelum instalasi lama dihentikan. Kredensial baru disiapkan terpisah dan baru diaktifkan setelah berkas lokal siap. Jika persiapan atau aktivasi ditolak, instalasi lama dipertahankan/dipulihkan. Jika respons aktivasi terputus, konfirmasi diulang memakai kredensial yang sama tanpa mencetak ulang tugas lama. Pesan gagal menyebut tahap dan lokasi log `%TEMP%\BankDPTaspen-Install-*.log`.

Jika muncul `Install-Agent.ps1 ... does not exist` atau `File pemasangan Install-Agent.ps1 tidak ditemukan`, pemasangan belum mulai dan belum memeriksa printer. Jalankan dari folder hasil **Extract All**, bukan dari tampilan isi ZIP; lokasi `%TEMP%\...zip...` biasanya menunjukkan file CMD dibuka langsung dari ZIP. Installer terbaru menampilkan petunjuk ekstraksi untuk kondisi ini. Jika paket sudah lewat 15 menit, unduh ulang pada baris penghubung yang sama lalu ekstrak ke folder baru.

### Memilih printer USB, Wi-Fi/LAN, atau sharing (v1.2.0)

Jendela pemilihan selalu muncul saat menjalankan `Install-Agent.cmd`, termasuk ketika nama pada aplikasi sudah cocok atau belum ada printer di laptop. Tidak perlu memilih printer default Windows terlebih dahulu. Nama dari aplikasi tetap ditampilkan sebagai petunjuk dan dapat diketik pada daftar; tujuan akhir harus cocok dengan printer yang benar-benar terhubung ke Windows.

- **USB langsung:** sambungkan printer dan pasang driver bila diminta Windows, lalu klik **Perbarui daftar**.
- **Wi-Fi/LAN:** klik **Cari / tambah printer**. Wizard Windows mencari printer yang dapat dijangkau. Bila tidak ditemukan, pilih opsi printer tidak terdaftar lalu masukkan IP/hostname printer dan driver yang sesuai. Setelah selesai, perbarui daftar pada installer.
- **USB pada komputer lain / sharing:** isi alamat lengkap seperti `\\PC-FC\PrinterFC` atau `\\192.168.1.20\PrinterFC`, lalu klik **Hubungkan**. Komputer pemilik printer harus hidup dan membagikan printer; jaringan serta izin Windows harus mengizinkan koneksi. Wizard **Cari / tambah printer** juga menyediakan pemasangan sharing bila perlu memasukkan kredensial/driver.

Pilih nama tujuan yang sudah terhubung, lalu **Pakai printer ini**. Nama perangkat seperti `Laptop FC` adalah label penghubung, bukan alamat printer. Mengetik nama/model printer saja tidak menemukan perangkat yang belum dikenal Windows dan tidak memilih printer terdekat secara otomatis. Windows menangani penemuan perangkat serta driver, dan mungkin meminta izin tambahan saat pemasangan driver. Installer tidak mengubah tujuan ke printer lain jika nama tidak ditemukan dan tidak melanjutkan enrollment sebelum tujuan terverifikasi.

Installer v1.1.0 yang berhenti dengan `Printer fisik belum terpasang pada Windows` perlu diganti dengan unduhan **v1.2.0** dari baris penghubung yang sama. Ekstrak ke folder baru; paket menyertakan helper `Printer.Setup.ps1`. Tidak perlu membuat penghubung baru.

### Alamat aplikasi pusat pada installer v1.0.3

Alamat harus mencakup subfolder aplikasi jika ada. Untuk aplikasi lokal yang dibuka melalui Apache saat ini, gunakan `http://192.168.11.60/sistem-antrian-bank/public`, tanpa `/admin` atau `/ambil-nomor`. Mengisi hanya `http://192.168.11.60` menyebabkan layanan pemasangan tidak ditemukan (HTTP 404). Alamat `http://192.168.11.60:7100` hanya dapat dipakai jika server pada port 7100 sedang berjalan.

Unduhan Windows dan Android sekarang mengisi alamat dasar aplikasi beserta subfolder secara otomatis. Jika pemasangan gagal dengan pesan `Aplikasi pusat belum dapat melayani permintaan (HTTP 404)`, refresh halaman admin, unduh paket terbaru, ekstrak ke folder baru, lalu jalankan `Install-Agent.cmd` sebelum 15 menit. Jangan mengganti alamat menjadi `localhost` atau `127.0.0.1` pada PC lain. Perbaikan ini tidak mengubah tujuan printer atau mengaktifkan penghubung secara otomatis.

## Nama printer berbeda dan penggantian perangkat

Nama printer tiap cabang boleh berbeda. Printer cabang ditentukan pada PC cabang, dicatat saat pemasangan, dan tidak ditentukan oleh browser. Pada mode penghubung, nama printer dalam `kiosk.config.json` tidak menjadi tujuan fallback untuk cabang tersebut; file itu tetap menentukan ukuran PDF awal 80 × 150 mm.

Pilih **Kelola → Ubah** untuk mengganti nama perangkat, kantor, atau printer. Penghubung Windows v1.1.0 melaporkan daftar printer PC: pilih nama yang tersedia untuk mengganti printer tanpa memasang ulang, lalu tunggu status **Siap** setelah konfirmasi PC. Perubahan tujuan ditolak selama tugas masih diproses; tugas yang belum mulai dibatalkan agar tidak berpindah printer. Windows versi lama dan Android memerlukan pemasangan ulang untuk mengganti printer.

Jika salah memilih cabang sebelum pemasangan, hapus catatan sementara melalui Kelola dan gunakan Pasang PC baru pada cabang yang benar. Cabang penghubung yang sudah tercatat tidak dapat diubah melalui form edit. Label perangkat dapat diubah terpisah dari Ganti printer & kertas. Mengganti PC atau alamat aplikasi memerlukan pemasangan ulang melalui Unduh Installer pada perangkat tersebut. Hapus mencabut akses dan menghilangkan baris aktif; tiket tetap tersedia sampai reset periode cabang. Tugas yang mungkin sudah dikirim menjadi tidak pasti dan tidak dikirim ulang otomatis.

Folder program dan log:

```text
%LOCALAPPDATA%\BankDPTaspenPrintAgent\<ID penghubung>\
  config.json
  credential.dpapi
  agent.log
  journal\
```

Kredensial terlindungi memakai Windows DPAPI untuk pengguna Windows yang memasang program. Folder dibatasi untuk pengguna itu dan SYSTEM. Token permanen tidak dimasukkan ke shortcut atau halaman browser. Jangan menyalin folder ini untuk memindahkan PC; lakukan pemasangan ulang.

Pemasangan menggunakan akun Windows biasa yang akan menjalankan kiosk, tanpa **Run as administrator**. Jika installer lama berhenti dengan pesan `Set-Acl` / `SeSecurityPrivilege`, unduh installer terbaru pada baris penghubung kantor yang sama, ekstrak seluruh ZIP ke folder baru, lalu buka `Install-Agent.cmd` lagi. Folder percobaan yang sudah terbentuk dapat dipakai ulang; installer memperbaiki izin akses tanpa mengubah pemilik atau aturan audit Windows.

Perbaikan tersebut disertakan pada installer **v1.0.2**. Nama unduhan sekarang memuat versi dan waktu, misalnya `Penghubung-Cetak-002-v1.0.2-20261015-150000.zip`. Layar pemasangan menampilkan `installer v1.0.2` dan lokasi folder paket. Jangan menjalankan file dari folder `Penghubung-Cetak-002` lama atau mencampur berkas dua paket. Tidak perlu membuat data penghubung baru; unduh ulang pada baris kantor yang sudah ada. Kunci pemasangan tetap berlaku 15 menit sejak unduhan terbaru.

## Menyiapkan aplikasi pusat untuk hosting

Gunakan konfigurasi produksi yang sesuai:

```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://alamat-aplikasi-bank.example
TICKET_PRINT_TRANSPORT=browser
KIOSK_TEST_MODE_ENABLED=false
```

Alamat di atas contoh; isi dengan domain aplikasi sebenarnya. Lakukan `composer install --no-dev`, build aset, `php artisan migrate --force`, lalu cache konfigurasi sesuai prosedur deployment Laravel. Server memerlukan ekstensi ZIP untuk membuat installer, dependensi Dompdf, database, dan folder storage privat yang dapat ditulis. Jalankan scheduler Laravel setiap menit (`php artisan schedule:run`) untuk menutup job yang kedaluwarsa dan membersihkan file sementara.

Untuk jalur browser/penghubung, hosting tidak memerlukan Windows atau worker `tickets:work`. SumatraPDF resmi tetap perlu disediakan sebagai berkas paket jika hosting akan melayani unduhan installer Windows. Tugas cetak browser/penghubung tidak dimasukkan ke worker cetak server.

Jika HTTPS dihentikan pada reverse proxy/load balancer, isi `TRUSTED_PROXIES` dengan IP/CIDR proxy yang benar, dipisahkan koma, agar Laravel mengenali HTTPS. Secara bawaan tidak ada proxy tambahan yang dipercaya. Jangan mengisi seluruh jaringan tanpa kebutuhan. Endpoint penghubung menolak HTTP di luar lingkungan local/testing dan klien tidak mengabaikan kesalahan sertifikat atau mengikuti pengalihan alamat.

Pengambilan tiket publik tetap mengikuti aturan aplikasi yang sudah ada. Pengelola deployment harus menentukan akses kiosk melalui perangkat/jaringan yang diizinkan sesuai kebijakan kantor sebelum membukanya ke internet umum.

## Mode lokal yang sekarang

Pada menu **Penghubung Cetak Cabang**, tombol **Riwayat Perangkat Kiosk** menampilkan browser yang membuka kiosk/mengambil tiket secara otomatis. Pilih **Riwayat Aktivitas** untuk melihat cabang, waktu, IP, nomor tiket, tujuan printer, jalur server/penghubung, serta status cetaknya. **Beri Nama** hanya mengubah penanda, misalnya `Laptop Rina`; tidak memberikan izin atau mengaktifkan penghubung printer.

Identitas baru mengikuti cookie browser terenkripsi, bukan IP. IP yang sama tidak menggabungkan dua browser; pergantian IP tidak otomatis membuat perangkat baru. Penghapusan cookie, mode privat, atau browser lain dapat menghasilkan entri berbeda. Jenis perangkat/sistem dikenali dari informasi browser, sehingga tidak menjamin nama/model perangkat fisik yang sebenarnya. **Terakhir Tercatat** berarti aktivitas kiosk terakhir, bukan status online.

Riwayat lama yang memiliki log penerbitan tiket dipulihkan dengan `php artisan kiosk:import-device-history`. Pengelompokannya per sesi permintaan yang tersimpan, ditandai **Sesi lama** / **Log tiket lama**. Tidak menebak perangkat berdasarkan IP dan tidak mengarang kunjungan sebelum pencatatan aktif. Perintah dapat diulang tanpa menggandakan tiket atau riwayat. Riwayat ini hanya dapat dilihat admin pusat.

Pilihan jalur sekarang disimpan per browser dan kantor. Browser baru selalu memakai dialog cetak lokal, termasuk jika konfigurasi lama masih berisi `TICKET_PRINT_TRANSPORT=server` atau `agent`. Nilai tersebut tidak memasangkan browser secara otomatis. Jalur server lama tetap tersedia melalui aksi admin **Gunakan printer server di PC ini**.

Halaman kiosk menampilkan **Tujuan cetak**. Pilih **Pakai printer PC ini** untuk melepas pasangan kantor tersebut dari browser ini. **Cetak tiket terakhir** membuka struk bernomor sama setelah dialog dibatalkan atau halaman dimuat ulang pada tab yang sama. Status `browser_ready` berarti struk siap dicetak, bukan bukti kertas keluar. Nama printer yang akhirnya dipilih pada dialog browser tidak dapat dibaca aplikasi. Jalur Android tetap ditujukan ke L6290 Wi-Fi dan A4.

Pasangan browser yang penghubungnya tidak siap, dinonaktifkan, dicabut, dihapus, atau dipindahkan kantor akan menolak penerbitan sampai diperbaiki atau pengguna memilih **Pakai printer PC ini**. Tidak ada pengalihan diam-diam ke server atau penghubung lain. Menghapus cookie atau memakai browser lain memerlukan pemasangan browser kembali untuk mode otomatis.

## Kegagalan dan cetak ulang

- Heartbeat printer/penghubung yang sudah lama atau printer belum siap menyebabkan permintaan ditolak sebelum tiket diterbitkan.
- Setelah tiket tersimpan, nomor tetap dipertahankan. Permintaan UUID yang sama mengembalikan tiket/job lama.
- Satu tugas hanya memperoleh izin mulai cetak sekali. File PDF diunduh dengan pemeriksaan SHA256. Tujuan printer dan executable berasal dari konfigurasi lokal penghubung, bukan dari perintah server/browser.
- Job belum mulai setelah 60 detik menjadi `expired`. Job yang sudah diambil dan tidak selesai dalam lease 90 detik menjadi `unknown`.
- Penghubung mencatat jurnal sebelum meminta izin pengiriman. Saat program hidup kembali, jurnal hanya digunakan untuk melaporkan hasil yang belum pasti, tidak untuk mencetak lagi. Konfirmasi hasil boleh diulang tanpa mengulang cetakan.
- Proses cetak native dibatasi 20 detik; waktu menjalankan helper dipisahkan dari waktu native. Timeout dan kegagalan setelah pengiriman mungkin dimulai menjadi `unknown`.
- Periksa kertas dan antrean Windows sebelum memakai **Daftar Antrean → Cetak Ulang**. Aksi tersebut menggunakan nomor serta snapshot lama, mencatat percobaan baru, dan memeriksa izin petugas serta kantor/layanan.

Status `submitted` berarti jalur cetak menerima tugas. Bukti kertas keluar tetap berasal dari pengamatan pada printer cabang.

## Bukti pengujian

- Installer v1.2.0, 27 Oktober 2026: pengujian Windows `tests/Windows/AgentPrinterSelection.ps1` mencakup laptop tanpa printer, pilihan USB/Wi-Fi/sharing tanpa printer default, pemuatan ulang daftar, wizard Windows, validasi alamat sharing, dan kegagalan spooler. API Windows diganti fixture; tidak menambahkan printer atau mencetak fisik. Pengujian paket unduhan/admin lulus 3 tes dan 34 assertions. Pengujian izin direktori, kredensial, kegagalan koneksi, serta paket campuran juga lulus. Pratinjau jendela: `scratch/printer-selection-v1.2.0.png`.

- Multi-PC dan installer v1.1.0, 27 Oktober 2026: [laporan terbaru](LAPORAN-CETAK-MULTI-PC-2026-10-27.md). Suite lengkap 183 tes lulus; audit browser, MySQL, paket unduhan, heartbeat Windows, dan pemulihan instalasi lulus. Tidak mengirim cetakan fisik.

- Installer Windows v1.0.2: `scratch/branch-agent-audit/LAPORAN-INSTALLER-WINDOWS-1.0.2.md`. ZIP yang diunduh melalui menu telah diuji pada akun Windows biasa hingga enrollment, startup tersembunyi, heartbeat, dan status **Siap**; suite terbaru 167 tes lulus. Tidak mengirim cetakan fisik.
- Suite aplikasi: `scratch/filament-native-audit/branch-agents.xml`.
- Dua program PowerShell cabang dengan executable printer pengganti: `scratch/branch-agent-audit/client-results.json`.
- Form native Filament dan pengunduhan installer: `scratch/branch-agent-audit/ui-results.json`.
- Pengujian memakai SQLite terpisah. Tidak ada tugas atau konfigurasi cabang operasional yang dipindahkan ke penghubung oleh audit.
- Penghubung lintas jaringan kantor/hosting sebenarnya serta hasil kertas dari jalur baru masih memerlukan UAT setelah deployment. Keberhasilan cetak server lokal yang sebelumnya dikonfirmasi pengguna tetap merupakan bukti jalur server lokal.
