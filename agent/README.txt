PENGHUBUNG CETAK CABANG BANK DP TASPEN

INSTALLER 1.3.0

PC baru/fotokopi tidak perlu installer untuk cetak biasa: buka kiosk, ambil tiket,
lalu pilih printer PC tersebut pada dialog Cetak. Browser baru memakai A4.
Pengaturan A4/58/80 mm tersedia di halaman Cetak & Printer cabang, bukan di kiosk.
Nama printer tidak perlu didaftarkan melalui admin untuk cetak biasa.

Untuk cetak otomatis tanpa dialog:
1. Admin membuka Dashboard > cabang yang benar > Cetak & Printer > Pasang PC baru.
2. Ekstrak seluruh ZIP pada PC printer, jalankan Install-Agent.cmd dengan akun Windows biasa.
3. Pilih printer dari daftar Windows dan ukuran kertasnya. Installer memeriksa tujuan fisik.
4. Setelah printer siap, izin paket admin mengaktifkan penghubung otomatis.
5. Gunakan shortcut Buka Kiosk. Pilih browser lain melalui Pilih browser kiosk.
6. Di halaman Cetak & Printer cabang, pilih perangkat > Kelola > Ganti printer & kertas.
   Tidak perlu paket baru. Bantuan Ganti printer dari shortcut lokal tetap tersedia.

Browser yang dibuka melalui shortcut dipasangkan hanya ke penghubung PC ini.
Nama printer sama di PC lain tidak menyamakan tujuan. Pasangan versi lama
harus diverifikasi sekali melalui shortcut. Status Windows running saja tidak
berarti agen sudah tersambung ke aplikasi pusat.

Pengawas berjalan saat pengguna Windows pemasang login dan memulai ulang agen
yang gagal. Akses yang dicabut menghentikan pengawas; pemasangan ulang diperlukan.
Kegagalan inventaris tidak mematikan heartbeat. Job yang mungkin sudah terkirim
tidak dicetak ulang otomatis. Periksa hasil, lalu minta cetak ulang nomor yang sama.

Paket berlaku 15 menit. Jangan menjalankan CMD langsung dari ZIP atau mencampur
berkas dari dua versi. Koneksi dan komponen diperiksa sebelum instalasi lama dihentikan.
Kredensial disimpan dengan Windows DPAPI untuk pengguna pemasang; jangan menyalin
folder program untuk memindahkan PC. Unduh paket pada penghubung yang sama untuk
pemasangan ulang. Mengganti PC/kantor/alamat aplikasi memerlukan pemasangan ulang.

Alamat aplikasi harus dapat dijangkau PC tujuan. localhost menunjuk PC itu sendiri.
Untuk produksi gunakan HTTPS; jangan mengabaikan kesalahan sertifikat.

Log pemasangan: %TEMP%\BankDPTaspen-Install-*.log
Program/log agen: %LOCALAPPDATA%\BankDPTaspenPrintAgent\<ID>\agent.log
Jalankan Open-Kiosk.ps1 pada folder program jika shortcut belum tersedia.
