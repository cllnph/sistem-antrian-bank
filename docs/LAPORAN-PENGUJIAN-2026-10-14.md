# Laporan pengujian sistem antrean Bank DP Taspen

Tanggal awal: 14 Oktober 2026. Pembaruan cetak server: 15 Oktober 2026 (Asia/Jakarta).

## Kesimpulan

Pengujian otomatis aplikasi dan pemeriksaan browser pada lingkungan lokal lulus setelah perbaikan. Aplikasi dapat dilaporkan sebagai selesai pada tahap pengembangan dan siap untuk uji penerimaan pengguna (UAT). Kesiapan operasional mesin antrean tetap memerlukan pengujian printer, audio, dan konfigurasi server produksi.

## Hasil pengujian

| Pemeriksaan | Hasil |
| --- | --- |
| Suite otomatis lengkap terbaru | 138 tes lulus, 867 assertion |
| Uji ulang kiosk setelah pembersihan skrip | 4 tes lulus, 28 assertion |
| Sintaks PHP | 109 file diperiksa, tanpa kesalahan sintaks |
| Build aset produksi | `npm.cmd run build` berhasil |
| Template Blade | Kompilasi berhasil |
| Panel Filament native | 15 pemeriksaan menu pada empat peran lulus; tiga modal, dark mode, dan mobile diperiksa tanpa error JavaScript |
| Halaman login, kiosk Bogor, display Bogor | HTTP 200 |
| API display sembilan kantor | HTTP 200; kode kantor sesuai permintaan |
| Kode kantor tidak dikenal | HTTP 404 pada kiosk, display, dan API |
| Klik ambil tiket | Satu tiket dan satu job cetak server; tidak memanggil cetak browser atau membuka PDF |
| Ketukan ganda saat permintaan berlangsung | Satu permintaan, satu tiket, satu job |
| Layanan menolak penerbitan tiket | Tidak mencetak; tombol dapat digunakan kembali |
| Tiket PDF 58 mm dan 80 mm | Logo, alamat, nomor, dan footer tersedia; tanpa URL; telepon cabang tidak muncul |
| Kiosk mobile 390 px | Tidak ada overflow horizontal |
| Error JavaScript pada skenario browser | Tidak ditemukan |
| Nama loket berisi HTML pada respons display | Ditampilkan sebagai teks; tidak dieksekusi |
| Akun aktif dengan kata sandi demo `password` | 0 akun, diperiksa tanpa mengubah kredensial |

Suite otomatis mencakup isolasi cabang dan layanan, pembatasan admin cabang, pengelolaan pengguna/peran, akun nonaktif, penerbitan dan pemanggilan tiket, nomor di atas 999, pergantian hari, batas jam kiosk, verifikasi tiket, serta seeder yang mempertahankan akun dan status operasional.

## Masalah yang diperbaiki dalam audit ini

1. **Error tiket mengembalikan redirect/HTML kepada pemanggil JSON.** Permintaan tidak valid semula menghasilkan HTTP 302; cabang nonaktif menghasilkan HTML. Penanganan exception sekarang menghormati `Accept: application/json`. Tes regresi membuktikan penolakan yang tepat dan memastikan tiket tidak dibuat.
2. **Penguncian nomor tiket belum memiliki baris acuan tetap.** Transaksi kini mengunci baris kantor sebelum menghitung nomor berikutnya, termasuk ketika belum ada tiket hari itu. Perubahan ini memperkuat alokasi nomor antar-kiosk dalam cabang yang sama pada MySQL. Uji beban paralel pada lingkungan produksi belum dilakukan.
3. **Default status pengguna tidak langsung tersedia pada objek baru.** Default model disamakan dengan default database; pemeriksaan izin impersonasi selalu mengembalikan boolean. Status nonaktif yang diisi secara eksplisit tetap ditolak.
4. **Polling kiosk dapat menumpuk saat server lambat.** Permintaan dibatasi satu pada satu waktu, diberi timeout, dan kunci polling dilepas setelah permintaan berakhir.
5. **Teks loket dari API dimasukkan ke HTML display tanpa escaping.** Nilai teks sekarang di-escape sebelum dirender; simulasi payload HTML di browser tidak dapat membuat elemen atau menjalankan kode.
6. **Tes lama tidak sesuai dengan alur Filament dan multi-cabang.** Fixture diperjelas dengan akun aktif, cabang, serta waktu terkontrol. Pengujian rute CRUD lama diganti dengan pengujian aksi Filament aktual, termasuk izin edit loket, penolakan peran tidak valid, akun nonaktif, dan perubahan kata sandi tanpa kebocoran pada sesi/HTML.
7. **Kiosk masih memuat pustaka barcode/QR eksternal yang tidak dipakai.** Dependensi skrip tersebut dihapus agar tidak menghambat pembukaan kiosk saat CDN tidak tersedia. Jam juga diinisialisasi langsung saat halaman dibuka.

8. **Login admin cabang terhalang saat mencatat waktu login.** Pada permintaan browser dengan guard baru, observer pengelolaan akun menolak pembaruan metadata terhadap akun admin cabang sendiri. Pencatatan `last_login_at` sekarang dilakukan sebagai pembaruan internal tanpa observer, sementara larangan mengubah akun sendiri tetap diuji dan berlaku. Tes regresi serta login browser admin cabang lulus.

## Penyeragaman panel ke Filament native

Seluruh menu panel menggunakan gaya dan komponen bawaan Filament. Override CSS panel dan render hook CSS dihapus. Dashboard cabang dan pusat kendali menggunakan Page/Schema/Section/Grid/Stats/Infolist bawaan; daftar petugas dan aktivitas menggunakan TableWidget; banner mode petugas menggunakan Section dan Button bawaan. Template konsol lama yang tidak lagi digunakan dihapus.

Menu yang diperiksa: Dashboard, Kontrol & Laporan, Daftar Antrean, Master Kantor Cabang, Pengaturan Loket, Manajemen Pengguna, dan Peran & Hak Akses. Hak akses dan pembatasan cabang dipertahankan. Formulir cabang, akun, serta hak akses juga memakai pengaturan kolom native agar mengisi lebar modal dengan benar. File halaman display dan kiosk diverifikasi identik melalui SHA-256 sebelum dan sesudah perubahan panel.

## Hal yang harus diselesaikan sebelum operasional

- **Konfigurasi rilis:** lingkungan yang diperiksa masih `APP_ENV=local`, `APP_DEBUG=true`, dan `APP_URL` menunjuk alamat lokal pengembangan. Sesuaikan pada server tujuan menjadi lingkungan produksi, matikan debug, dan gunakan alamat aplikasi yang benar. Audit tidak mengubah `.env` atau kredensial.
- **Printer antrean:** model dan sistem operasi mesin belum ditetapkan. Cetak senyap memerlukan konfigurasi browser kiosk atau integrasi printer yang sesuai pada perangkat tersebut. PDF lulus tidak membuktikan kertas fisik berhasil keluar.
- **Audio panggilan:** perlu uji speaker, volume, izin audio browser, suara bahasa Indonesia, serta panggil ulang pada mesin display sebenarnya.
- **Beban dan jaringan:** perlu uji beberapa mesin/counter bersamaan pada server tujuan. Audit ini tidak membuktikan kapasitas produksi atau perilaku ketika jaringan terputus sesudah tiket tersimpan.
- **Jam operasional disepakati:** layanan Senin–Jumat 08.00–15.00 WIB; pengambilan tiket tetap 04.00–18.00 WIB. Data sembilan kantor dan default formulir telah disamakan, tanpa mengubah batas jam kiosk.
- **Runtime server:** dependensi Laravel yang terpasang membutuhkan PHP `^8.3`; lingkungan audit menggunakan PHP 8.5. Pastikan mesin server tujuan memenuhi kebutuhan dependensi.

## Metode dan batas pemeriksaan

Tes PHP memakai SQLite in-memory terpisah. Pengujian browser membaca halaman dan API pada server lokal sementara. Respons penerbitan tiket pada browser disimulasikan agar tidak menambahkan antrean operasional dan tidak mengirim cetakan fisik. Pemeriksaan display menggunakan data API kantor yang dibaca secara langsung, lalu respons polling diputar ulang agar pengujian tidak membebani server pengembangan.

Pengujian ini bukan sertifikasi keamanan menyeluruh, uji beban produksi, atau persetujuan operasional perangkat. Tidak ada publikasi/deployment maupun pesan ke pihak lain yang dilakukan.

Pemeriksaan menu panel tambahan menggunakan server sementara dengan database SQLite fixture terpisah untuk admin pusat, admin cabang, teller, dan CS; tidak memakai akun aplikasi operasional.

## Audit lanjutan logika bisnis

- Peran sistem `admin`, `admin_cabang`, `cs`, dan `teller` dilindungi dari penghapusan serta perubahan nama/guard. Peran tambahan yang masih dipakai akun aktif maupun nonaktif tidak dapat dihapus. Penolakan dilakukan sebelum paket permission melepas relasi akun dan izin.
- Kantor dengan loket, akun, atau riwayat tidak dapat dihapus. Kode/slug kantor yang sudah digunakan tidak dapat diganti. Loket dengan akun atau tiket tidak dapat dihapus atau dipindah kantor/jenis layanan.
- Akun sendiri dan administrator aktif terakhir tidak dapat dinonaktifkan, dihapus, atau dicabut peran adminnya. Akun dengan riwayat pelayanan tidak dapat dihapus; dapat dinonaktifkan setelah pelayanan selesai.
- Petugas yang masih memanggil/melayani tidak dapat dipindah atau diubah perannya. Loket tidak dapat ditutup saat melayani. Kantor tidak dapat dinonaktifkan sebelum antrean aktif selesai atau direset.
- Peran petugas, jenis loket, dan kantor harus sesuai. Formulir akun memakai transaksi: apabila perubahan peran ditolak, perubahan profil sebelumnya juga dibatalkan. Pesan validasi diarahkan ke kolom Filament yang benar.
- Tiket tidak dapat dihapus, diganti nomor/kantor, digandakan pada kantor/tanggal yang sama, atau dibuka kembali setelah selesai/dilewati. Penempatan ke kantor atau jenis layanan lain ditolak. Tiket manual selalu dimulai dengan status menunggu dan dicatat dalam log.
- Reset mengubah tiket menunggu/dipanggil/dilayani menjadi dilewati, mempertahankan tiket selesai dan riwayat, serta mencatat aktor. Izin, kantor, dan layanan dibatasi di server. Pemanggilan nomor baru tidak menyelesaikan nasabah sebelumnya secara otomatis.
- Tombol panggil, ulangi, selesai, lewati, dan status loket memeriksa izin. Petugas lain tidak dapat mengambil alih tiket aktif milik petugas pertama. Filter laporan tidak dapat dimanipulasi untuk membaca kantor lain oleh akun selain admin pusat.
- Tiket hari sebelumnya ditutup dengan log oleh petugas yang memiliki izin operasional; pembukaan daftar oleh akun hanya-baca tidak mengubah status tiket.
- Laporan operasional tetap dapat dibuka jika peran lama hilang. Pemeriksaan data aktual menemukan peran `cs` sudah tidak ada, tetapi tidak ada akun aktif tanpa peran, akun dengan kantor/loket berbeda, maupun tiket dengan kantor/loket berbeda. Audit tidak menebak atau menetapkan ulang peran akun lama.
- Browser: 15 menu pada admin pusat, admin cabang, teller, dan CS lulus; tiga modal native, dark mode, mobile, tanpa error JavaScript. Pemeriksaan memakai SQLite terpisah.
- Hash SHA-256 display, kiosk, dan verifikasi tiket identik dengan sebelum audit logika; tampilannya tidak diubah.

## Audit tambahan setelah pengaman integritas

Pemeriksaan kedua menemukan dan memperbaiki masalah berikut:

1. **Mode petugas bertingkat:** rute sebelumnya mengizinkan pergantian akun ketika sesi impersonasi masih aktif dan dapat menimpa identitas admin asal. Kini pergantian ditolak sampai kembali ke admin; akun sendiri/nonaktif ditolak; kegagalan masuk diperiksa dan ID sesi diperbarui. Rute alias kembali ke admin dibatasi agar tidak tertangkap sebagai ID pengguna.
2. **Kantor tiket berbeda dari URL kiosk:** `branch_id` pada POST kini harus sama dengan kantor dalam URL jika URL menyebut kantor. Permintaan yang tidak cocok ditolak sebelum menerbitkan tiket.
3. **Posisi antrean keliru:** halaman verifikasi kini menghitung seluruh loket dengan jenis layanan sama pada kantor dan tanggal tiket tersebut. Perhitungan tidak dipengaruhi sesi pengguna yang kebetulan berasal dari layanan atau kantor lain.
4. **Cakupan akun tanpa kantor:** daftar loket dan statistik petugas tidak lagi mengembalikan data kantor lain untuk akun tanpa penempatan kantor.
5. **Permission API dari guard berbeda:** API peran memvalidasi permission khusus guard `web`. Pembuatan role dan pemberian permission dilakukan dalam satu transaksi; kegagalan tidak meninggalkan role setengah jadi.
6. **Detail error internal:** API peran dan loket mencatat exception di server, tetapi mengirim pesan umum ke browser.
7. **Nama loket antar kantor:** API serta formulir native membatasi duplikasi nama dalam kantor yang sama, dan mengizinkan nama serupa di kantor berbeda. Jenis layanan API dibatasi pada CS, teller, atau umum.

Sepuluh tes regresi baru mencakup kasus penolakan, jalur yang tetap diizinkan, rollback, serta isolasi data. Tidak ada akun/permission atau antrean operasional yang diubah oleh audit ini. Pengujian memakai database terpisah; file tampilan kiosk dan display tetap identik berdasarkan SHA-256.

## Nama peran yang dapat diedit

Nama tampilan peran dipisahkan dari kode internal melalui kolom `roles.display_name`. Kolom "Nama Peran" pada formulir Filament kini dapat diedit untuk semua peran, termasuk admin, admin cabang, CS, dan teller. Mengubah nama tampilan tidak mengganti kode internal, ID role, izin, atau penugasan pengguna. Proteksi penghapusan peran sistem tetap berlaku.

Label diterapkan pada daftar peran, pilihan peran pengguna, badge pengguna, pengaturan hak akses, daftar petugas, dashboard cabang, dan ringkasan laporan. Role baru mendapat kode otomatis; perubahan label berikutnya mempertahankan kode tersebut. Data lama mendapat label awal melalui migrasi; inisialisasi label pada enam peran yang sudah ada telah dilakukan tanpa mengubah kode atau izin.

Enam tes baru membuktikan seluruh peran sistem dapat diubah namanya, CS tetap dapat memanggil antrean setelah nama diganti, label konsisten di tabel, role baru memperoleh kode, nama kosong ditolak, dan API menyimpan label terpisah dari kode. Bukti suite: `scratch/filament-native-audit/role-display-name.xml`.

UAT: buka Peran & Hak Akses ? Ubah pada Teller ? ganti Nama Peran menjadi "Petugas Kas" ? Simpan. Periksa label di Manajemen Pengguna dan dashboard, kemudian login teller dan panggil tiket. Kode teller dan izin akun harus tetap berfungsi.

## Pembukaan sementara kiosk untuk pengujian

Konfigurasi `KIOSK_TEST_MODE_ENABLED=true` diaktifkan pada `.env` lokal. Mode ini melewati pembatasan jam hanya jika lingkungan aplikasi `local` atau `testing`. Nilai bawaan konfigurasi adalah `false`; pada produksi dan staging, flag tidak mengubah jadwal Senin?Jumat 04.00?18.00 WIB.

Tombol CS/Teller dan endpoint penerbitan tiket tetap memakai `KioskHours::isOpen()` yang sama. Kantor atau loket nonaktif tetap ditolak. Tidak ada perubahan pada template kiosk/display, izin pengguna, maupun alur cetak. Tiket uji lewat browser tetap tiket biasa, sehingga tetap tersimpan dan mengikuti alur cetak.

Pemeriksaan GET langsung ke `http://192.168.1.7:7100/ambil-nomor` menghasilkan HTTP 200; tombol CS dan Teller keduanya aktif. Pemeriksaan ini tidak menerbitkan tiket operasional.

Untuk mengakhiri pengujian, ubah `KIOSK_TEST_MODE_ENABLED=false` lalu jalankan `php artisan config:clear`. Tes otomatis menetapkan flag `false` sebagai bawaan di `phpunit.xml` agar `.env` lokal tidak mengubah hasil tes jam normal. Skenario mode pengujian mengaktifkannya secara eksplisit.

Suite lengkap: 116 tes, 749 assertion lulus. Pengujian kiosk: 20 kasus, 107 assertion lulus; mencakup tombol serta penerbitan CS/Teller pada malam hari dan akhir pekan di local/testing, aturan produksi saat flag aktif, mode mati, dan kantor/loket nonaktif. Semua penerbitan dalam tes menggunakan SQLite terpisah. Fixture pengujian operasional cabang juga menetapkan waktu 09.00 WIB agar tes normal tidak bergantung pada jam komputer saat dijalankan. Bukti suite lengkap: `scratch/filament-native-audit/kiosk-test-mode.xml`.

## Riwayat: cetak melalui shortcut kiosk (digantikan oleh cetak server)

Shortcut **Kiosk Antrean Bank DP Taspen** dibuat pada Desktop. Launcher membaca `kiosk.config.json`, membuka URL aktif melalui Chrome dengan profil khusus dan `--kiosk-printing`, serta menetapkan tujuan printer ITPRINTER (L6290 Series), satu salinan, tanpa header/footer browser. Printer yang hilang, virtual, offline/error, URL tidak valid, profil aktif, serta pembukaan bersamaan ditolak. Preferensi profil harian dan pengaturan otomatis printer Windows tidak diubah; helper selama kiosk berjalan mengembalikan default yang dialihkan otomatis Windows ke printer kiosk, sambil mengingat pilihan printer lain dari pengguna.

Regresi Laravel lulus: **116 tes, 749 assertion**. Pemeriksaan software launcher mencakup sembilan keadaan, preferensi printer, enam keputusan penjagaan printer default, serta jalur baca API Windows. Alur browser CS/Teller, klik ganda, penolakan penerbitan, dan kegagalan cetak lulus tanpa error JavaScript. Template kiosk/display tetap identik berdasarkan SHA-256.

Satu percobaan CS-001 dari database SQLite terpisah otomatis masuk ke antrean printer tanpa konfirmasi Print. Windows kemudian melaporkan error dengan 0 halaman tercetak. Pengguna menjelaskan printer fisik belum tersedia dan meminta uji fisik dilanjutkan besok. Job uji ID 2 telah dibatalkan dan tidak lagi berada dalam antrean. Printer default dikembalikan ke Microsoft Print to PDF; pengaturan otomatis Windows tetap aktif seperti semula. Cetak fisik CS dan Teller belum dinyatakan lulus.

Panduan penggunaan dan UAT: `docs/PANDUAN-CETAK-KIOSK.md`. Bukti software: `scratch/silent-print-audit/software-results.json`; percobaan printer: `scratch/silent-print-audit/physical-attempt.json`; suite regresi: `scratch/filament-native-audit/silent-printing.xml`.


## Cetak langsung dari server (15 Oktober 2026)

Implementasi ini menggantikan ketergantungan pada shortcut, profil Chrome khusus, dan `window.print()`. Edge biasa menerbitkan tiket lalu memantau job server; tidak ada preview atau tab PDF tambahan. Dompdf 3.1.6 menghasilkan PDF privat dengan aset lokal. SumatraPDF portable resmi 3.6.1 x64 terpasang privat; checksum dan tanda tangan Authenticode valid. Tidak ada asosiasi PDF default yang diubah.

Tabel `print_jobs` mencatat UUID, relasi tiket, snapshot struk, printer, status, waktu, kegagalan, dan percobaan cetak ulang. Tiket, ledger, log penerbitan, serta entri antrean database disimpan secara atomik. UUID yang sama dari sesi/kantor/layanan sama mengembalikan nomor/job lama. Pengiriman ulang setelah respons hilang tidak membuat nomor pengganti. Status hanya dapat dibaca sesi peminta atau petugas berizin.

Worker khusus `tickets:work` menjalankan antrean database `ticket-printing`, terpisah dari `QUEUE_CONNECTION=sync` aplikasi. Worker dipasang tersembunyi dan shortcut Startup pengguna Windows telah dibuat. Heartbeat aktif diverifikasi; supervisor memulihkan worker setelah prosesnya dihentikan untuk uji restart. Registrasi startup diperiksa tanpa reboot/login Windows ulang. Pengamanan default printer dijalankan bersama supervisor, mencakup printer yang dipetakan per cabang. Default tetap Microsoft Print to PDF dan pengaturan otomatis Windows tidak diubah.

Worker mengklaim job secara atomik; satu job paling banyak satu pengiriman otomatis. Job belum mulai setelah 60 detik kedaluwarsa. Timeout native dibatasi 20 detik dan menghentikan proses anak melalui Windows Job Object. Proses terputus/pengiriman tidak pasti menjadi `unknown`, tanpa retry otomatis. PDF sementara dibersihkan; file sisa worker terputus dibersihkan oleh worker berikutnya. Printer dan executable berasal dari konfigurasi server, bukan browser. Produksi menolak fallback lintas cabang dan memerlukan pemetaan kode kantor.

Aksi native Filament **Cetak Ulang** memeriksa izin dan kantor/layanan, menggunakan snapshot serta nomor lama, mencatat aktor dan percobaan baru, dan menolak percobaan aktif yang bersamaan.

Bukti:

- Suite lengkap: **138 tes, 867 assertion, 0 error, 0 failure** (`scratch/filament-native-audit/server-printing.xml`). Tambahan 22 tes server-print mencakup idempotensi, otorisasi, worker/printer gagal, rollback antrean, render gagal, timeout, restart, kedaluwarsa, reprint, konfigurasi produksi, dan input browser yang mencoba mengganti printer/file.
- Edge biasa tanpa opsi silent printing: CS/Teller mencapai `submitted` melalui pengganti printer, sementara `window.print()` dibuat melempar error. Jumlah panggilannya **0**, tanpa tab tambahan dan tanpa error JavaScript. Klik ganda hanya membuat satu tiket/job. Dua proses PHP bersamaan dengan UUID sama menghasilkan satu tiket/job. Respons yang hilang sesudah commit lalu diulang dari tombol tetap memakai tiket lama. Kegagalan penerbitan dan cetak ditampilkan dengan benar.
- PDF contoh diperiksa memakai PyMuPDF dan secara visual: **1 halaman, 80 ? 150 mm**, logo, alamat Bogor, tanggal, CS, nomor, footer; tanpa URL dan telepon cabang. Template khusus pusat mempertahankan telepon dan dibuktikan tes.
- Helper Windows diuji dengan executable pengganti: argumen `-print-to`, `-silent`, `noscale,1x,simplex`; proses normal exit 0, proses yang macet exit 124 setelah batas 20 detik dan tidak meninggalkan proses anak. Tidak ada job fisik yang dikirim dalam pengujian ini.
- Pemeriksaan printer native menolak nama tidak terdaftar dan Microsoft Print to PDF. Penjagaan default printer lulus enam keputusan dasar dan dua printer hasil pemetaan cabang.
- Pint pada file baru layanan cetak lulus, sintaks PHP dan seluruh helper PowerShell lulus. Pemeriksaan Pint seluruh dirty tree juga mendeteksi format lama di file dari audit sebelumnya; tidak dilakukan perubahan format massal di luar lingkup cetak.
- Hash display dan halaman verifikasi tetap sama. Perubahan kiosk berada pada JavaScript alur cetak; tata letak layar tetap dipertahankan.
- Database operasional hanya mendapat migrasi tabel layanan cetak; **0 print_job operasional** saat verifikasi. Penerbitan pengujian menggunakan SQLite terpisah. Gateway operasional tetap Windows, bukan pengganti pengujian.

**Cetak fisik belum diuji:** printer belum tersedia sesuai keterangan pengguna. `submitted` hanya berarti jalur pengiriman menerima job. Besok wajib mencetak satu CS dan satu Teller, mencocokkan nomor/logo/alamat/footer pada kertas dan memeriksa default printer. L6290 menggunakan ukuran struk asli pada kertas uji; driver/ukuran thermal diperiksa ketika mesin tersedia.

Panduan terbaru: `docs/PANDUAN-CETAK-KIOSK.md`. Bukti Edge: `scratch/server-print-audit/browser-results.json`, screenshot `edge-kiosk.png`, contoh `receipt-cs.pdf`/`receipt-cs.png`.

## Bukti lokal

- Hasil suite panel sebelumnya: `scratch/filament-native-audit/phpunit.xml`
- Hasil suite audit tambahan: `scratch/filament-native-audit/additional-audit.xml`
- Hasil suite audit integritas sebelumnya: `scratch/filament-native-audit/business-integrity.xml`
- Ringkasan pengujian menu panel native: `scratch/filament-native-audit/results.json`
- Ringkasan pengujian browser: `scratch/final-audit/browser-results.json`
- Tiket thermal: `scratch/final-audit/ticket-bogor-58mm.pdf` dan `ticket-bogor-80mm.pdf`
- Tangkapan layar login, kiosk, display, dan mobile: `scratch/final-audit/`

## Ringkasan untuk atasan

### Penghubung cetak cabang untuk deployment terpusat

Pada 15 Oktober 2026 ditambahkan jalur penghubung Windows per cabang. Aplikasi pusat dapat menyajikan PDF dari hosting Linux/Windows; penghubung mengambil tugas melalui HTTPS dan mencetak menggunakan printer lokal cabang. Menu **Penghubung Cetak Cabang** menggunakan Filament native dan hanya dapat dikelola admin pusat. Nama printer dipilih saat pemasangan. Penghubung tidak memerlukan PHP/XAMPP atau port masuk di cabang.

Pemasangan memakai enrollment satu kali yang berlaku 15 menit. Token permanen hanya disimpan sebagai hash di server dan terenkripsi DPAPI pada PC cabang, dengan folder terbatas pengguna/SYSTEM. API memeriksa identitas perangkat dan cakupan cabang. HTTP ditolak di luar local/testing. Klaim job, unduhan PDF dengan SHA256, izin mulai satu kali, serta konfirmasi hasil dipisahkan; jurnal lokal dan lease mencegah pengiriman ulang otomatis setelah koneksi/proses terputus. Akses dapat dicabut dan installer baru dapat mengganti PC/printer. Jalur server lokal tetap aktif pada konfigurasi sekarang; belum ada cabang operasional yang dipindahkan oleh implementasi ini.

Bukti pengujian penghubung:

- **155 tes, 982 assertion**, 0 error/failure pada suite lengkap (`scratch/filament-native-audit/branch-agents.xml`). Tambahan 17 tes meliputi otorisasi, pemisahan cabang, enrollment, HTTPS, kondisi offline, izin mulai satu kali, timeout lease, idempotensi hasil, PDF, cetak ulang, installer, dan menu native.
- Dua program PowerShell sebenarnya untuk Bogor dan Depok menggunakan executable printer pengganti: masing-masing mengambil job sendiri dan mengirim satu pemanggilan native. Restart setelah izin mulai dan timeout native berakhir `unknown`, tanpa pengiriman kedua. Penghubung offline menghasilkan 503 tanpa tiket baru. Bukti `scratch/branch-agent-audit/client-results.json`.
- Browser Edge: membuat record melalui modal native dan mengunduh ZIP installer berhasil, tanpa error JavaScript. Aktivasi dan pencabutan akses juga lulus. Bukti `ui-results.json`, `installer-results.json`, serta screenshot pada folder yang sama.
- Installer yang diunduh dipasang pada profil Windows uji: SumatraPDF resmi terverifikasi, enrollment terkonsumsi, kredensial DPAPI dan ACL privat dibuat, shortcut startup tersembunyi terdaftar, dan heartbeat pemeriksaan printer nyata berhasil. Program tidak memproses job fisik; startup uji dibersihkan setelah verifikasi.
- Sintaks helper PowerShell/PHP dan Pint pada file baru/layanan cetak lulus. Layout kiosk dan display tidak diubah. Worker lokal yang sudah dipakai pengguna tetap sehat dan kiosk aktual menghasilkan HTTP 200; printer default Windows tetap Microsoft Print to PDF saat verifikasi.

**Batas penerimaan:** pengujian penghubung memakai database SQLite terpisah, printer pengganti untuk job, dan server uji lokal. Belum ada deployment ke domain hosting sebenarnya atau pengujian jaringan kantor lain. UAT kertas pada penghubung cabang sesudah deployment tetap diperlukan; bukti fisik yang sebelumnya dikonfirmasi pengguna berasal dari jalur server lokal. Panduan pemasangan/deployment: `docs/PANDUAN-PENGHUBUNG-CABANG.md`.

Perbaikan pemeriksaan printer 15 Oktober 2026: pengguna memastikan ITPRINTER menyala dan terhubung. Pemeriksaan melalui PowerShell pernah melewati timeout 10 detik, walaupun pemeriksaan lain melaporkan siap. Pemeriksa native Winspool ditambahkan dan dipasang melalui installer. Uji baca langsung berhasil pada ITPRINTER (status 0, sekitar 0,5 detik); nama tidak terdaftar, PDF, dan Fax ditolak. Jalur PHP beserta pemeriksaan worker berhasil sekitar 0,6 detik. Tidak ada cetakan fisik yang dikirim oleh pemeriksaan status ini. Bukti: `scratch/server-print-audit/native-check-results.json`.

Regresi setelah perubahan pemeriksa printer: **22 tes server-printing, 118 assertion lulus**. Pint pada file PHP yang diubah dan sintaks installer lulus. Suite lengkap 138 tes di atas adalah hasil sebelum perubahan pemeriksa native; tidak dijalankan ulang untuk perubahan ini. Konfirmasi keluarnya kertas masih menunggu hasil percobaan pengguna melalui tombol kiosk.

Pembaruan UAT fisik 15 Oktober 2026: pengguna menjawab **"Tiket sudah keluar"** setelah diminta memuat ulang dan menekan Ambil Tiket. Pembacaan ledger menunjukkan percobaan terbaru T-003 dan T-004 (Teller, Kantor Pusat Operasional) berstatus `submitted`, tanpa kegagalan tercatat. Percobaan awal T-002 berstatus `unknown`; tidak ada retry otomatis. Pengguna belum menyebut nomor kertas secara spesifik, mengonfirmasi cetak CS, atau menilai logo/alamat/footer. Konfirmasi kertas sudah keluar dicatat sebagai keberhasilan cetak fisik, dengan penerimaan lengkap kedua layanan masih menunggu UAT tersebut. Tidak ada cetakan tambahan yang dikirim oleh pemeriksaan ini.

Pemeriksaan ulang 15 Oktober 2026: worker cetak memiliki heartbeat aktif, shortcut Startup tetap terpasang, dan executable cetak tersedia. IP Wi-Fi PC berubah menjadi `192.168.11.60`; konfigurasi kiosk dan panduan disesuaikan, lalu server dibuka pada alamat aktif tersebut. GET `http://192.168.11.60:7100/ambil-nomor` menghasilkan HTTP 200 dengan alur cetak server dan tanpa `window.print()`. Pemeriksaan ulang ini tidak menerbitkan tiket atau mengirim cetakan fisik. Hasil 138 tes/867 assertion di atas berasal dari suite implementasi sebelumnya; suite tidak diulang untuk perubahan alamat ini.

Pengembangan sistem antrean multi-cabang telah selesai dan telah melalui pengujian otomatis serta pemeriksaan browser. Sebanyak 138 tes dengan 867 assertion lulus pada suite implementasi; regresi setelah pemeriksa native berubah meluluskan 22 tes/118 assertion. Jalur cetak kini berjalan melalui server dari Edge biasa, dan pengguna mengonfirmasi tiket fisik sudah keluar. Temuan pada penanganan error tiket, status pengguna, penguncian nomor, polling, dan keamanan tampilan telah diperbaiki. UAT lengkap masih perlu mencakup kedua layanan, isi struk, mesin thermal, audio panggilan, serta konfigurasi server produksi sebelum operasional.
