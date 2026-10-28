> Pembaruan 28 Oktober 2026: tombol unduh Android sudah dicabut dari admin. Panduan pemasangan di bawah adalah referensi teknis versi sebelumnya. Perangkat Android yang sudah terpasang tetap didukung; source dan kunci penandatanganan tetap dipertahankan.

# Cetak langsung Android ke Epson L6290 melalui Wi-Fi

Jalur ini memakai HP/tablet Android sebagai penghubung printer cabang. PC Windows cabang dan SumatraPDF tidak diperlukan. Aplikasi pusat Laravel tetap harus berjalan: pada pengujian sekarang servernya `http://192.168.11.60:7100`, dan nantinya dapat diganti hosting HTTPS.

Target: Android 8.0 atau lebih baru, browser Chrome/Edge yang diperbarui, Epson L6290, serta Wi-Fi yang memungkinkan Android menjangkau printer dan aplikasi pusat. L6290 tidak menyediakan cetak Bluetooth. Perangkat yang menjadi penghubung perlu tetap aktif dan terhubung jaringan.

## Pemasangan

1. Hubungkan Android dan Epson L6290 ke Wi-Fi kantor yang sama. Nyalakan printer dan siapkan A4. Hindari jaringan tamu yang memblokir komunikasi antarperangkat.
2. Buka admin pusat, pilih **Cabang & Loket → Penghubung Cetak Cabang**.
3. Pada menu itu, klik **Unduh Aplikasi Android**, lalu pasang APK pada HP/tablet. Android dapat meminta izin memasang aplikasi dari browser/Files yang dipakai. Izin ini hanya untuk pemasangan APK; tidak ada konfirmasi Print setiap tiket.
4. Gunakan penghubung kantor yang sudah ada, atau **Tambah Penghubung** jika kantor belum memiliki penghubung. Isi nama perangkat, misalnya `Tablet Bogor`, dan nama printer, misalnya `Epson L6290 Wi-Fi`. Satu kantor memakai satu penghubung aktif.
5. Pada baris kantor tersebut klik **Unduh Paket Android**. Untuk pengujian sekarang, alamat aplikasi pusat adalah `http://192.168.11.60:7100`, tanpa `/admin` atau `/ambil-nomor`. Unduh ZIP pada Android; tidak perlu diekstrak. Kunci pemasangan hanya berlaku sekali dan 15 menit.
6. Buka aplikasi **Cetak Antrean Bank DP Taspen**. Tekan **1. Impor Paket Android** dan pilih ZIP tadi.
7. Isi IP printer yang terlihat pada pengaturan jaringan/Laporan Status Jaringan Epson. Saat pemeriksaan pada 15 Oktober 2026, printer kantor ditemukan pada `192.168.11.128`. Periksa kembali karena alamat IP dapat berubah; printer di cabang lain memakai alamatnya sendiri.
8. Tekan **2. Periksa Printer & Pasangkan**. Alamat IP biasa otomatis menggunakan koneksi terenkripsi, contohnya `ipps://192.168.11.128:631/ipp/print`. Cocokkan alamat dan sidik jari sertifikat dengan informasi printer/Web Config atau petugas jaringan, lalu konfirmasikan **Percayai Printer Ini**. Sertifikat tersebut dipatok hanya untuk printer ini; perubahan sertifikat ditolak, dan sertifikat server aplikasi tetap diverifikasi normal.
9. Setelah terpasang, tekan **3. Jalankan Penghubung**. Izinkan notifikasinya. Kembali ke menu admin, tunggu **Siap**, periksa kantor/perangkatnya, lalu **Aktifkan** jika belum aktif.
10. Tekan **Buka Kiosk Cabang** dari aplikasi Android. Contoh Bogor: `http://192.168.11.60:7100/ambil-nomor/002`. Coba satu tiket CS lalu satu Teller. Cocokkan nomor, logo, alamat, dan footer pada kertas dengan aplikasi.

Mengunduh APK/paket dapat dilakukan admin lalu dibagikan langsung ke petugas yang memasangnya. Jangan membagikan ZIP pemasangan ke pihak lain. Tidak perlu menjalankan `Install-Agent.cmd` pada Android.

## Penggunaan dan batas

- Browser mengirim penerbitan tiket ke aplikasi pusat. Aplikasi Android mengambil tugas cabangnya, memeriksa hash PDF, lalu mengirim langsung melalui IPP/IPPS. Tidak memakai `window.print()` atau Android PrintManager.
- Pengujian L6290 memakai satu lembar **A4** dengan isi struk pada ukuran aslinya, ditempatkan di tengah atas. Tidak membesarkan struk ke seluruh A4. Printer thermal memerlukan penyesuaian ukuran dan dukungan protokol tersendiri.
- Printer diperiksa untuk dukungan A4 serta PWG Raster sRGB/format PDF. L6290 yang diperiksa menawarkan PWG Raster sRGB pada 300 dan 600 dpi; aplikasi memilih 300 dpi. PDF tidak dikirim mentah ke L6290 yang tidak mengiklankan dukungan PDF.
- Setelah Android dimulai ulang, buka aplikasi penghubung dan tekan **Jalankan Penghubung**. Aplikasi menampilkan notifikasi selama aktif. Pengaturan hemat baterai produsen dapat menghentikannya; bila demikian, periksa kembali status Siap sebelum digunakan.
- PC server lokal sekarang tetap harus menyala untuk menyediakan aplikasi pusat. Setelah hosting tersedia, server lokal tersebut dapat digantikan hosting. Yang dihilangkan oleh jalur ini adalah PC perantara printer di cabang.
- Satu kantor memakai satu perangkat penghubung. Pemasangan Android pada penghubung yang sama mengganti akses perangkat Windows/Android sebelumnya. Tugas yang sedang berlangsung dapat menjadi `unknown` dan tidak dikirim ulang otomatis.
- Timeout pengiriman native dibatasi 20 detik. Jurnal disimpan sebelum meminta izin cetak; restart, kehilangan jawaban izin, atau hilangnya koneksi setelah pengiriman tidak menyebabkan pencetakan ulang otomatis.
- `submitted` berarti jalur IPP menerima tugas, bukan bukti kertas keluar. Jika hasilnya belum pasti, periksa printer terlebih dahulu dan gunakan **Cetak Ulang** untuk nomor lama melalui petugas berizin.

## Build dan pengujian pengembang

JDK 17+ diperlukan. Skrip memakai JDK bawaan Android Studio pada mesin pengembangan ini. SDK Android API 35 dan Build Tools 35.0.0 diunduh dari repositori resmi Google dan checksum diperiksa.

```powershell
python android-print-agent/tools/prepare_sdk.py
powershell.exe -NoProfile -ExecutionPolicy Bypass -File android-print-agent/build.ps1
powershell.exe -NoProfile -ExecutionPolicy Bypass -File android-print-agent/test.ps1
php artisan test
```

APK tersimpan privat pada `storage/app/private/android/bank-dp-taspen-print-agent.apk`, tersedia melalui aksi admin **Unduh Aplikasi Android**. Saat memindahkan aplikasi ke hosting, sertakan APK ini agar aksi unduh tersedia. Simpan keystore pada `android-print-agent/.signing` secara privat untuk menandatangani pembaruan APK dengan identitas yang sama; jangan masukkan ke Git atau paket pemasangan.

Bukti pengujian ada pada `scratch/android-print-audit/`. Tes inti Java memakai printer/socket pengganti; pemeriksaan perangkat Epson asli hanya meminta atribut IPP, tidak mencetak kertas. Tampilan browser diuji dengan emulasi sentuh HP dan tablet. Pemasangan APK pada Android asli, operasi di latar belakang pada merek perangkat yang dipakai, serta keluarnya kertas masih perlu UAT.

Referensi: [antarmuka L6290](https://download.epson.com.sg/product_brochures/ink_tank_system/VN/Epson_EcoTank_L6260_L6270_L6290.pdf), [port IPP/IPPS L6290](https://download4.epson.biz/sec_pubs/l6290_series/useg/en/GUID-5F7CDE39-97A0-4BC3-A096-A9F11CC2E7C0.htm), [RFC 8010](https://www.rfc-editor.org/rfc/rfc8010), [PWG Raster 5102.4](https://ftp.pwg.org/pub/pwg/candidates/cs-ippraster10-20120420-5102.4.pdf).
