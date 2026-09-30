# LAPORAN KULIAH KERJA PRAKTIK (KKP)
**PROGRAM STUDI SARJANA TEKNIK INFORMATIKA**  
**FAKULTAS TEKNOLOGI INFORMASI DAN DIGITAL**  
**UNIVERSITAS BANI SALEH, BEKASI**  
**TAHUN 2026**

---

# PERANCANGAN DAN IMPLEMENTASI SISTEM INFORMASI ANTREAN BANK BERBASIS WEB PADA PT BPR DP TASPEN KANTOR CABANG BEKASI

**Disusun Oleh:**  
- **Nama Mahasiswa** : Salsabila Puspa Pratiwi  
- **NIM** : 2301111110046  
- **Program Studi** : S1 - Teknik Informatika  
- **Fakultas** : Fakultas Teknologi Informasi dan Digital (FTID)  
- **Perguruan Tinggi** : Universitas Bani Saleh, Bekasi  
- **Instansi Tempat KKP** : PT BPR DP TASPEN Kantor Cabang Bekasi  

---

## LEMBAR PENGESAHAN

**LEMBAR PENGESAHAN LAPORAN KULIAH KERJA PRAKTEK**  
**PROGRAM STUDI SARJANA TEKNIK INFORMATIKA**  
**UNIVERSITAS BANI SALEH**

**Judul Laporan KKP:**  
*Perancangan dan Implementasi Sistem Informasi Antrean Bank Berbasis Web pada PT BPR DP TASPEN Kantor Cabang Bekasi*

**Identitas Mahasiswa:**  
- NIM : 2301111110046  
- Nama : Salsabila Puspa Pratiwi  

Bekasi, 21 September 2026  

| Menyetujui: | Menyetujui: |
| :--- | :--- |
| **Pembimbing Lapangan PT BPR DP TASPEN** | **Dosen Pembimbing KKP Universitas Bani Saleh** |
| *(Tanda Tangan & Stempel Instansi)* | *(Tanda Tangan)* |
| **Hendra Kurniawan, S.E.** | **Agus Suhartono, M.Kom.** |
| Supervisor Operasional Cabang | NIDN: 0415088201 |

**Mengetahui,**  
**Ketua Program Studi Sarjana Teknik Informatika / Dekan FTID**  
Universitas Bani Saleh  

*(Tanda Tangan)*  

**Rahmadi, S.Kom., M.Kom.**  

---

## KATA PENGANTAR

*Assalamu’alaikum Warahmatullahi Wabarakatuh.*

Puji dan syukur penulis panjatkan kehadirat Allah SWT yang telah melimpahkan rahmat, taufik, serta karunia-Nya, sehingga penulis dapat menyelesaikan kegiatan Kuliah Kerja Praktik (KKP) beserta penyusunan laporan yang berjudul **"Perancangan dan Implementasi Sistem Informasi Antrean Bank Berbasis Web pada PT BPR DP TASPEN Kantor Cabang Bekasi"** dengan baik dan tepat waktu.

Laporan Kuliah Kerja Praktik ini disusun guna memenuhi salah satu syarat kelulusan akademik dalam menempuh program pendidikan Strata Satu (S1) pada Program Studi Teknik Informatika, Fakultas Teknologi Informasi dan Digital (FTID), Universitas Bani Saleh, Bekasi.

Dalam penyusunan laporan ini, penulis menyampaikan ucapan terima kasih dan apresiasi yang setinggi-tingginya kepada:
1. **Bapak Rahmadi, S.Kom., M.Kom.**, selaku Dekan Fakultas Teknologi Informasi dan Digital Universitas Bani Saleh.
2. **Bapak/Ibu Ketua Program Studi Teknik Informatika** beserta jajaran dosen dan staf pengajar FTID Universitas Bani Saleh.
3. **Bapak Agus Suhartono, M.Kom.**, selaku Dosen Pembimbing KKP atas segala bimbingan, koreksi, dan arahan selama penulisan laporan ini.
4. **Bapak Hendra Kurniawan, S.E.**, selaku Supervisor Operasional sekaligus Pembimbing Lapangan di PT BPR DP TASPEN Kantor Cabang Bekasi atas pendampingan dan transfer wawasan industri perbankan yang berharga.
5. **Seluruh Staf dan Karyawan PT BPR DP TASPEN Kantor Cabang Bekasi**, khususnya divisi Teller kas, Customer Service, dan IT Support.
6. **Kedua Orang Tua dan Keluarga Tercinta** yang senantiasa memberikan doa, motivasi, dan dukungan yang tiada terhingga.
7. **Rekan-rekan Mahasiswa Teknik Informatika Angkatan 2023** Universitas Bani Saleh atas kerja sama dan kebersamaannya.

Penulis menyadari bahwa penulisan laporan ini masih memiliki ruang penyempurnaan. Kritik dan saran yang membangun sangat penulis harapkan demi kemajuan penelitian dan pengembangan teknologi di masa depan.

*Wassalamu’alaikum Warahmatullahi Wabarakatuh.*

Bekasi, 21 September 2026  
**Penulis,**  
**Salsabila Puspa Pratiwi (NIM: 2301111110046)**

---

## DAFTAR ISI

- **HALAMAN JUDUL (COVER DEPAN)** (Hal. i)
- **LEMBAR PENGESAHAN LAPORAN KKP** (Hal. ii)
- **KATA PENGANTAR** (Hal. iii)
- **DAFTAR ISI** (Hal. iv)
- **DAFTAR TABEL** (Hal. v)
- **DAFTAR GAMBAR** (Hal. vi)
- **DAFTAR SIMBOL** (Hal. vii)
- **BAB I PENDAHULUAN** (Hal. 1)
  - 1.1 Latar Belakang Masalah
  - 1.2 Identifikasi Masalah
  - 1.3 Batasan Masalah KKP (Ruang Lingkup & Batasan Masalah)
  - 1.4 Teknik Pengumpulan Data (Wawancara, Observasi, Studi Pustaka, Hardware & Software)
  - 1.5 Sistematika Penulisan
- **BAB II TINJAUAN ORGANISASI** (Hal. 10)
  - 2.1 Tinjauan Organisasi
    - a. Sejarah Instansi KKP
    - b. Visi dan Misi Instansi KKP
    - c. Struktur Organisasi Instansi KKP
    - d. Keterangan Tugas dan Wewenang Struktur Organisasi
  - 2.2 Penjelasan Unit Kegiatan Fokus KKP
- **BAB III PEMBAHASAN** (Hal. 18)
  - 3.1 Proses Bisnis
    - i. Prosedur Pelayanan dan Antrean Perbankan
    - ii. Proses Bisnis Beserta Activity Diagram
    - iii. Use Case Diagram dan Skenario Use Case
  - 3.2 Interface (Spesifikasi Antarmuka)
    - a. Model Layar / Tampilan Sistem (Kiosk, Display TV, Operator Box, Admin Center)
    - b. Model Masukan (Dokumen Input)
    - c. Model Keluaran (Dokumen Output)
  - 3.3 Rancangan Database
    - a. Class Diagram / ERD (Entity Relationship Diagram)
    - b. Spesifikasi Basis Data (Data Dictionary)
  - 3.4 Arsitektur Keamanan dan Otorisasi Sistem (Two-Layer Authorization)
  - 3.5 Pengujian Sistem (Black Box Testing)
- **BAB IV PENUTUP** (Hal. 47)
  - 4.1 Kesimpulan
  - 4.2 Saran Pengembangan
- **DAFTAR PUSTAKA** (Hal. 50)
- **LAMPIRAN** (Hal. 52)
  - Lampiran 1: Contoh-3 Lembar Daftar Hadir KKP
  - Lampiran 2: Contoh-4 Lembar Berita Acara Kegiatan & Nilai Lapangan
  - Lampiran 3: Contoh-5 Lembar Penilaian Dosen Pembimbing KKP
  - Lampiran 4: Kuisioner Umpan Balik Perusahaan Tempat KKP

---

# BAB I: PENDAHULUAN

### 1.1. Latar Belakang Masalah
Dunia perbankan nasional merupakan sektor industri jasa keuangan yang berlandaskan pada asas kepercayaan masyarakat (*fiduciary relationship*) serta menuntut standar kualitas pelayanan yang prima (*service excellence*). Tingkat kepuasan nasabah (*customer satisfaction*) sangat dipengaruhi oleh kecepatan, ketepatan, transparansi, serta kenyamanan fasilitas operasional yang dirasakan saat bertransaksi di kantor cabang bank. Di tengah era transformasi digital yang berkembang sangat pesat, perbankan diwajibkan tidak hanya memiliki keandalan dalam sistem inti perbankan (*Core Banking System*), melainkan juga harus mampu menyajikan pengalaman layanan fisik di kantor cabang yang terotomatisasi, tertib, dan humanis.

PT Bank Perekonomian Rakyat Dana Pensiun TASPEN (PT BPR DP TASPEN) merupakan institusi perbankan yang memiliki posisi strategis dalam memberikan pelayanan finansial, penyaluran gaji pensiun Aparatur Sipil Negara (ASN), tabungan hari tua, deposito, serta fasilitas pinjaman kredit bagi pensiunan maupun masyarakat umum. Karakteristik nasabah utama pada kantor cabang PT BPR DP TASPEN, khususnya Kantor Cabang Bekasi, didominasi oleh kalangan lanjut usia (pensiunan ASN dan pejabat negara) yang memerlukan perhatian khusus dalam hal kemudahan aksesibilitas layanan, kejelasan informasi, serta kepastian waktu tunggu di ruang perbankan (*banking hall*).

Berdasarkan hasil observasi dan pengamatan langsung selama pelaksanaan Kuliah Kerja Praktik (KKP) di PT BPR DP TASPEN Kantor Cabang Bekasi, ditemukan bahwa pengelolaan antrean nasabah pada jam-jam sibuk—terutama pada periode awal bulan saat masa pencairan dana pensiun dan pembayaran angsuran kredit—masih menghadapi berbagai kendala operasional yang signifikan. Alur antrean konvensional yang berjalan selama ini masih mengandalkan pencetakan nomor antrean manual atau kartu antrean fisik berbahan dasar mika/kertas bernomor statis yang dibagikan secara manual oleh petugas keamanan (*security*) di pintu masuk. Kondisi ini menimbulkan berbagai ketidaknyamanan, antara lain:
1. **Ketidakpastian dan Kecemasan Waktu Tunggu Nasabah**: Nasabah yang duduk di ruang tunggu tidak memiliki visibilitas mengenai jumlah antrean di depannya, perkiraan waktu tunggu (*estimated waiting time*), ataupun kepastian loket mana yang sedang melayani. Hal ini memicu kecemasan, khususnya bagi nasabah pensiunan lansia yang memiliki keterbatasan fisik.
2. **Risiko Kesalahan Pemanggilan dan Ketiadaan Sinkronisasi Audio-Visual**: Pemanggilan nomor antrean yang masih dilakukan secara manual dengan pengeras suara portabel rentan terhadap salah dengar atau terlewat oleh nasabah saat ruang tunggu dalam keadaan ramai dan riuh.
3. **Ketidakseimbangan Beban Kerja Antar Loket (*Workload Imbalance*)**: Sering terjadi penumpukan antrean nasabah pada loket kas Teller (misalnya antrean transaksi pencairan dan setor tunai), sementara loket Customer Service (CS) mengalami masa senggang (*idle*), atau sebaliknya. Ketiadaan sistem terintegrasi menyulitkan supervisor untuk mengalihkan beban antrean secara fleksibel dan proporsional.
4. **Ketiadaan Pencatatan Waktu Layanan dan Rekapitulasi Audit Kinerja**: Manajemen operasional cabang tidak memiliki data riil mengenai durasi waktu tunggu nasabah (*waiting duration*), waktu pelayanan di loket (*serving time*), maupun rasio kehadiran nasabah (persentase nomor antrean yang dilayani vs dilewati). Ketiadaan pencatatan log otomatis menghambat evaluasi *Service Level Agreement* (SLA) dan perencanaan jumlah loket harian.

Untuk menjawab tantangan operasional tersebut, diperlukan perancangan dan implementasi Sistem Informasi Antrean Bank Berbasis Web yang handal, modern, dan berstandar keamanan enterprise. Sistem ini dibangun dengan memanfaatkan framework Laravel 11/12 sebagai fondasi backend, framework Filament v3 sebagai panel administrasi dan dasbor konsol petugas berbasis Livewire yang reaktif, Tailwind CSS untuk antarmuka yang elegan, serta manajemen hak akses berbasis peran (*Role-Based Access Control*) menggunakan Spatie Laravel-Permission.

Keunggulan utama dari sistem yang dikembangkan ini terletak pada penerapan arsitektur keamanan dua lapis (*Two-Layer Authorization Pattern*), yang mengombinasikan verifikasi hak akses fitur (*Permission Layer*) dengan kepemilikan loket operasional (*Data Assignment Layer*). Melalui mekanisme ini, setiap petugas kasir Teller dan Customer Service terikat secara eksklusif dengan nomor loket tugasnya, sehingga menjamin integritas data transaksi antrean dan mencegah terjadinya konflik pemanggilan nomor antar petugas di loket yang berbeda.

### 1.2. Identifikasi Masalah
Berdasarkan pengamatan di lapangan selama pelaksanaan KKP, identifikasi masalah yang ditemukan adalah:
1. Belum tersedianya sistem tiket antrean otomatis yang mandiri dan terstandarisasi, sehingga proses pembagian nomor antrean masih manual dan menimbulkan ketidaknyamanan bagi nasabah lansia.
2. Ketiadaan media informasi visual terpusat (Layar Monitor Display TV) dan notifikasi audio otomatis di ruang tunggu, yang menyebabkan nasabah sering tidak mendengar panggilan petugas atau merasa was-was mengenai giliran antreannya.
3. Penugasan dan pemanggilan antrean oleh petugas Teller dan Customer Service belum terintegrasi secara digital, yang berisiko menimbulkan tabrakan pemanggilan nomor atau ketidakteraturan aliran nasabah antar loket.
4. Belum adanya sistem pencatatan berbasis data relasional yang merekam histori antrean, durasi pemanggilan, serta waktu pelayanan, sehingga pihak manajemen cabang kesulitan mengukur efisiensi kerja petugas dan kinerja pelayanan harian.

### 1.3. Batasan Masalah KKP
**a. Ruang Lingkup KKP:**
Ruang lingkup KKP difokuskan pada perancangan arsitektur perangkat lunak berbasis web pada jaringan intranet lokal (*Local Area Network*) PT BPR DP TASPEN Kantor Cabang Bekasi, yang mencakup:
1. *Kiosk Tiket Mandiri* (`/ambil-nomor`): Antarmuka layar sentuh bagi nasabah untuk memilih layanan dan mencetak struk thermal berbarcode/QR Code.
2. *Layar Monitor Display TV Ruang Tunggu* (`/display`): Menampilkan status antrean aktif, running text pengumuman, dan audio sintesis pemanggilan suara.
3. *Konsol Petugas Operasional (Operator Box)*: Antarmuka kerja petugas Teller dan CS pada Filament Panel untuk memanggil (*next*), panggil ulang (*recall*), melayani (*serving*), selesai (*completed*), dan lewati (*skipped*).
4. *Admin Control Center*: Modul administratif untuk konfigurasi loket layanan, manajemen user/role, dan reset antrean harian.

**b. Batasan Masalah:**
1. Kategori layanan dibatasi pada dua layanan utama: Layanan Teller Kas & Pensiun TASPEN (Kode 'A') dan Layanan Customer Service & Kredit (Kode 'B').
2. Status antrean dibatasi pada 5 status baku: `waiting`, `called`, `serving`, `completed`, dan `skipped`.
3. Keamanan menggunakan *Two-Layer Authorization* (Fitur Permission + Loket Data Assignment).
4. Sistem tidak terhubung langsung dengan Core Banking System utama perbankan demi kepatuhan rahasia bank (*banking secrecy*).

### 1.4. Teknik Pengumpulan Data
1. **Wawancara (*Interview*)**: Tanya jawab langsung dengan Bapak Hendra Kurniawan, S.E. (Supervisor Operasional), petugas Teller kas, staf Customer Service, dan petugas IT Support.
2. **Observasi (*Observation*)**: Pengamatan terhadap alur fisik nasabah pensiun di banking hall, antrean manual, dan durasi transaksi per loket.
3. **Studi Pustaka (*Library Research*)**: Mengkaji referensi buku rekayasa perangkat lunak, UML, dokumentasi resmi Laravel Framework, Filament v3, dan Panduan KKP FTID Universitas Bani Saleh.
4. **Spesifikasi Hardware & Software**:
   - *Hardware*: PC Server Intel Core i5/16GB RAM/SSD, Kiosk Monitor Touchscreen 15.6" + Thermal Printer 58mm/80mm, Smart TV LED 43", Sound Amplifier & Speaker, PC Loket Desktop.
   - *Software*: Windows 11 Pro, Apache 2.4, MySQL 10.4 (XAMPP), PHP 8.2+, Laravel 11/12, Filament PHP v3, Tailwind CSS, Google Chrome.

---

# BAB II: TINJAUAN ORGANISASI

### 2.1. Tinjauan Organisasi
**a. Sejarah Instansi KKP:**  
PT Bank Perekonomian Rakyat Dana Pensiun TASPEN (PT BPR DP TASPEN) merupakan lembaga perbankan resmi yang diawasi oleh OJK dan dijamin oleh LPS. Institusi ini didirikan dengan komitmen utama memberikan solusi keuangan perbankan yang aman dan mudah diakses bagi para pensiunan ASN, purnawirawan, dan masyarakat umum. Kantor Cabang Bekasi didirikan untuk melayani kebutuhan finansial nasabah di wilayah Kota dan Kabupaten Bekasi melalui produk tabungan, deposito, kredit pensiun, serta penyaluran gaji pensiunan TASPEN.

**b. Visi dan Misi Perusahaan:**  
- **Visi**: *"Menjadi Bank Perekonomian Rakyat yang Sehat, Terpercaya, Profesional, dan Unggul dalam Memberikan Pelayanan Finansial Prima bagi Kalangan Pensiunan dan Masyarakat Umum."*  
- **Misi**:
  1. Memberikan pelayanan perbankan yang ramah, cepat, tepat, dan beretika tinggi bagi nasabah pensiunan.
  2. Menyediakan produk simpanan tabungan dan deposito yang aman dan menguntungkan.
  3. Menyalurkan kredit yang tepat sasaran untuk kebutuhan finansial dan wirausaha pensiunan.
  4. Menerapkan tata kelola Good Corporate Governance dan digitalisasi proses bisnis.

**c. Struktur Organisasi Cabang Bekasi:**  
Struktur dipimpin oleh Pemimpin Cabang (*Branch Manager*) yang membawahkan Supervisor Operasional & Layanan serta Supervisor Bisnis & Kredit Pensiun.

**d. Uraian Tugas dan Wewenang:**  
- **Branch Manager**: Bertanggung jawab penuh atas kinerja operasional, kepatuhan regulasi OJK/LPS, dan target bisnis cabang.
- **Supervisor Operasional**: Mengawasi transaksi kas harian, khasanah kas, antrean banking hall, dan kepatuhan SOP loket.
- **Supervisor Bisnis & Kredit**: Mengelola strategi penyaluran pinjaman pensiun dan penghimpunan dana pihak ketiga.
- **Teller Kas & Pensiun**: Melayani transaksi tunai nasabah dan mengoperasikan konsol antrean Teller.
- **Customer Service**: Melayani pembukaan rekening, administrasi pensiun, informasi produk, dan konsol antrean CS.
- **IT Support & Administrator**: Memelihara jaringan LAN, PC loket, kiosk, TV display, akun pengguna, dan reset antrean harian.

### 2.2. Penjelasan Unit Kegiatan Fokus KKP
Fokus kegiatan KKP berada pada **Divisi Operasional Layanan Nasabah (Front Office)** dan **Divisi Teknologi Informasi (IT Support)** yang secara langsung bersentuhan dengan alur antrean nasabah dan implementasi perangkat lunak perbankan.

---

# BAB III: PEMBAHASAN

### 3.1. Proses Bisnis Sistem Antrean
1. **Tahap Pengambilan Tiket**: Nasabah memilih layanan di Kiosk (`/ambil-nomor`), sistem mengenerasi nomor unik (Prefix A untuk Teller, B untuk CS) dan mencetak struk thermal dengan barcode dan QR Code (Status: `waiting`).
2. **Tahap Menunggu & Display TV**: Nasabah duduk di ruang tunggu memperhatikan Display TV (`/display`) yang menampilkan nomor antrean aktif, running text, dan suara panggilan.
3. **Tahap Pemanggilan Tiket**: Petugas menekan "PANGGIL ANTREAN BERIKUTNYA", status berubah menjadi `called`, audio chime & suara panggilan otomatis berbunyi di banking hall.
4. **Tahap Pelayanan & Penyelesaian**: Petugas melayani transaksi (Status: `serving`), lalu menekan "Selesai Dilayani" (Status: `completed`). Jika nasabah tidak hadir setelah dipanggil berulang kali, petugas menekan "Lewati Antrean" (Status: `skipped`).

### 3.2. Pemodelan UML
- **Use Case Diagram**: Memetakan aktor Nasabah, Petugas Teller/CS, dan Administrator terhadap fungsionalitas sistem.
- **Activity Diagram**: Menggambarkan alur eksekusi pengambilan tiket di Kiosk dan pemanggilan antrean di loket.

### 3.3. Spesifikasi Basis Data (Data Dictionary)
Tabel utama basis data meliputi:
1. `users`: Menyimpan akun petugas dan admin, relasi ke `service_setting_id`, status keaktifan, dan waktu login terakhir.
2. `service_settings`: Menyimpan konfigurasi loket fisik (`counter_name`, `counter_code`, `service`, `is_active`).
3. `queues`: Menyimpan transaksi antrean (`number`, `service`, `status`, `called_at`, `completed_at`, `served_by`).
4. `roles` & `permissions`: Mengatur RBAC fitur via Spatie Laravel-Permission.
5. `notifications`: Mengelola riwayat notifikasi sistem.

### 3.4. Arsitektur Keamanan Dua Lapis (Two-Layer Authorization)
1. **Layer 1 - Feature Permission**: Mengontrol akses menu dan route (contoh: `access_teller_console`, `reset_queues`).
2. **Layer 2 - Loket Data Ownership Assignment**: Mengunci penugasan data loket berdasarkan `users.service_setting_id === service_settings.id`. Teller di Loket 1 tidak dapat mengakses atau memanggil antrean di Loket 2, kecuali peran Administrator yang memiliki bypass wewenang penuh.

### 3.5. Pengujian Sistem (Black Box Testing)
Pengujian fungsionalitas mencakup 10 kasus uji (Kiosk Ambil Tiket Teller/CS, Login Petugas, Panggilan Next, Display TV Real-time, Recall, Complete, Skip, Two-Layer Security Lock, dan Reset Antrean Harian). Hasil pengujian menunjukkan tingkat keberhasilan **100% Valid**.

---

# BAB IV: PENUTUP

### 4.1. Kesimpulan
1. Sistem Informasi Antrean Bank Berbasis Web berhasil dibangun menggunakan framework Laravel, Filament v3, dan MySQL untuk mentransformasikan antrean konvensional menjadi otomatis, tertib, dan transparan.
2. Kiosk Mandiri dan Monitor Display TV dengan audio chime suara otomatis berhasil memberikan kenyamanan dan kepastian bagi nasabah, khususnya kalangan lansia pensiunan TASPEN.
3. Arsitektur keamanan dua lapis terbukti andal dalam mencegah konflik pemanggilan antrean antar loket operasional.
4. Rekapitulasi waktu layanan (*called_at* dan *completed_at*) mempermudah evaluasi SLA dan efisiensi kinerja petugas cabang.

### 4.2. Saran Pengembangan
1. Penambahan fitur pendaftaran antrean daring (*Remote Online Queue*) via aplikasi mobile atau WhatsApp Bot.
2. Integrasi mesin *Text-to-Speech* berbasis AI dengan variasi multi-bahasa daerah (Bahasa Indonesia & Sunda).
3. Penerapan SMS/WhatsApp Gateway pengingat saat nomor antrean mendekati giliran.

---

# DAFTAR PUSTAKA
- Fowler, Martin. (2018). *UML Distilled: Panduan Singkat Bahasa Pemodelan Objek Standar*. Edisi Ketiga. Yogyakarta: Penerbit Andi.
- Kendall, Kenneth E., dan Julie E. Kendall. (2020). *Systems Analysis and Design*. 10th Edition. New York: Pearson Education.
- Pressman, Roger S., dan Bruce R. Maxim. (2020). *Software Engineering: A Practitioner's Approach*. 9th Edition. New York: McGraw-Hill Education.
- Rosa, A. S., dan M. Shalahuddin. (2019). *Rekayasa Perangkat Lunak Terstruktur dan Berorientasi Objek*. Bandung: Informatika Bandung.
- Situmorang, Ferryanto. (2022). *Tuntunan Praktis Pemrograman Web Modern Berbasis Framework PHP*. Jakarta: Elex Media Komputindo.
- Sommerville, Ian. (2019). *Software Engineering*. 10th Edition. Harlow: Pearson Education Limited.
- Filament PHP Documentation. (2026). *Filament v3 Documentation*. https://filamentphp.com/docs (akses pada 21 September 2026).
- Laravel Framework Documentation. (2026). *Laravel Framework Documentation*. https://laravel.com/docs (akses pada 21 September 2026).
- Spatie Documentation. (2026). *Laravel-Permission Documentation*. https://spatie.be/docs/laravel-permission (akses pada 21 September 2026).

---

# LAMPIRAN RESMI FTID UNIVERSITAS BANI SALEH
1. **Lampiran 1**: Lembar Daftar Hadir KKP (Contoh-3)
2. **Lampiran 2**: Lembar Berita Acara Kegiatan & Nilai Pembimbing Lapangan (Contoh-4)
3. **Lampiran 3**: Lembar Penilaian Dosen Pembimbing KKP (Contoh-5)
4. **Lampiran 4**: Kuisioner Evaluasi & Umpan Balik Perusahaan Tempat KKP
