# Menjalankan semua cabang dalam satu aplikasi

Gunakan satu instalasi aplikasi dan satu database. Akun, loket, dan antrean
terhubung ke cabang masing-masing. Semua cabang dapat bekerja bersamaan.

## Penyiapan lokal

Setelah dependensi dan `.env` siap, jalankan:

```powershell
php artisan migrate --seed
php artisan serve --host=0.0.0.0 --port=8000
```

Tema panel memakai `resources/css/filament/admin/theme.css`. Setelah perubahan
tampilan atau penyalinan proyek ke server, jalankan `npm install` jika dependensi
belum tersedia, kemudian `npm run build`. Pastikan direktori `public/build`
ikut tersedia di server agar tata letak, widget, dan form dimuat dengan benar.

Gunakan PHP yang kompatibel dengan dependensi proyek; PHP bawaan XAMPP 8.1
di mesin ini berbeda dari PHP CLI 8.5 yang digunakan untuk pengujian.

Seeder membuat sembilan cabang, loket CS dan Teller setiap cabang, dan akun
demo di lingkungan `local` atau `testing`. Pengulangan seeder mempertahankan
kata sandi, status akun, profil cabang, dan status loket yang sudah ada.

## Pusat akses

Login di `/admin/login`. Admin otomatis diarahkan ke
`/admin/operasional-cabang`. Halaman ini menampilkan seluruh cabang,
akun petugas, status loket, antrean hari ini, dan tombol kiosk serta layar.
Data antrean diperbarui setiap sepuluh detik.

Petugas dapat membuka menu Operasional Cabang untuk melihat kantornya sendiri.
Untuk melayani nasabah, buka Konsol Petugas. Pilihan loket saat mengelola akun
mengikuti cabang yang dipilih; penempatan lintas cabang ditolak.

## Akun demo baru

- Admin: `admin@example.com`.
- Teller cabang: `teller.<slug>@banktaspen.co.id`.
- CS cabang: `cs.<slug>@banktaspen.co.id`.
- Kata sandi awal akun demo yang baru dibuat: `password`.

Contoh: `teller.bogor@banktaspen.co.id` dan `cs.depok@banktaspen.co.id`.
Kata sandi akun yang sudah ada tidak diubah. Akun demo tidak dibuat di produksi.

| Kode | Slug cabang | Kiosk | Layar |
| --- | --- | --- | --- |
| 001 | pusat | /ambil-nomor/001 | /display/001 |
| 002 | bogor | /ambil-nomor/002 | /display/002 |
| 003 | depok | /ambil-nomor/003 | /display/003 |
| 004 | tangerang | /ambil-nomor/004 | /display/004 |
| 005 | jaktim | /ambil-nomor/005 | /display/005 |
| 006 | karawang | /ambil-nomor/006 | /display/006 |
| 007 | cikarang | /ambil-nomor/007 | /display/007 |
| 008 | purwokerto | /ambil-nomor/008 | /display/008 |
| 009 | bekasi | /ambil-nomor/009 | /display/009 |

Slug juga dapat digunakan pada URL, misalnya `/ambil-nomor/bogor`.
Cabang yang tidak ditemukan atau nonaktif mengembalikan 404, sehingga perangkat
tidak berpindah diam-diam ke kantor pusat.

## Perangkat cabang

1. Buka alamat kiosk cabang pada perangkat pengambilan nomor.
2. Buka alamat layar cabang pada TV, lalu aktifkan audio bila browser memintanya.
3. Petugas login menggunakan akun cabangnya pada komputer masing-masing.
4. Nasabah mengambil nomor; petugas memanggil; layar cabang menampilkan panggilan.

Kiosk dan layar tidak memerlukan login. Keduanya dapat dibuka dari pusat akses
tanpa mengubah sesi admin. Untuk mencoba beberapa akun petugas bersamaan pada
satu komputer, gunakan profil browser berbeda karena tab berbagi sesi login.

Semua perangkat harus memakai alamat server yang dapat dijangkau, seperti
`http://<IP-server>:8000`, bukan `127.0.0.1`. Sesuaikan `APP_URL` dengan alamat
tersebut agar tautan cetak dan verifikasi tiket dapat dibuka dari perangkat lain.
Untuk cabang di jaringan berbeda, gunakan server bersama yang dapat diakses
melalui jaringan organisasi atau domain aplikasi.
