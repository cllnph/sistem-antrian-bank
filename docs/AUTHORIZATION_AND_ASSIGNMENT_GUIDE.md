# Panduan Sistem Otorisasi & Assignment Data Loket — PT BPR DP TASPEN

Dokumen ini menjelaskan arsitektur keamanan dua lapis (*two-layer authorization*) yang memisahkan antara **Hak Akses Fitur (Permission/Shield)** dan **Kepemilikan Data (Data Assignment)** pada Aplikasi Sistem Antrean Perbankan PT BPR DP TASPEN.

---

## 🛡️ Lapisan 1: Fitur Permission (Spatie Laravel-Permission / Shield)

Peran: **Mengontrol akses ke menu, rute, dan tindakan fitur** (Apakah user BOLEH menggunakan fitur ini?).

- **Tabel Database**: `roles`, `permissions`, `model_has_roles`, `role_has_permissions`.
- **Contoh Permission**:
  - `access_teller_console`: Mengizinkan user membuka konsol pemanggilan Teller (`/dashboard/teller`).
  - `edit_service_setting`: Mengizinkan user mengubah konfigurasi loket layanan.
  - `view_any_user`: Mengizinkan user membuka manajemen pengguna.

---

## 🏛️ Lapisan 2: Assignment Data Loket (Data Ownership)

Peran: **Mengontrol lingkup data loket spesifik yang menjadi tanggung jawab user** (Loket MANA yang boleh diakses/dikelola user ini?).

- **Tabel Database**: Kolom `service_setting_id` pada tabel `users` yang terhubung ke `service_settings.id`.
- **Aturan Otorisasi Lapis Kedua**:
  - **Petugas Teller / Customer Service**: Terikat secara statis ke loket tugasnya (`service_setting_id`). Saat mengakses rute/kebijakan counter, sistem memverifikasi `auth()->user()->service_setting_id === $setting->id` (`abort(403)` jika mencoba mengakses/mengedit loket petugas lain).
  - **Administrator**: Di-exclude dari batasan assignment data. Administrator dapat mengelola **seluruh loket** tanpa pembatasan `service_setting_id`.

---

## 💡 Contoh Implementasi Kode (2-Layer Check Pattern)

```php
// Layer 1: Feature Permission Check
if (! auth()->user()->can('edit_service_setting')) {
    abort(403, 'Anda tidak memiliki hak akses fitur ini');
}

// Layer 2: Data Ownership Assignment Check (Bypassed for Admin)
if (! auth()->user()->hasRole('admin') && $counter->id !== auth()->user()->service_setting_id) {
    abort(403, 'Anda tidak berwenang mengelola counter ini');
}
```

---

## 📋 Ringkasan Penggunaan untuk Pengembang (Developers)

1. **Membuat User Baru**: Pilihlah Role yang sesuai. Jika Role adalah **Teller** atau **Customer Service**, pilihlah **Loket / Counter Tugas** pada field yang tampil secara kondisional di form `UserResource`.
2. **Menambah Fitur Baru**: Selalu terapkan pengecekan `can('nama_permission')` terlebih dahulu, diikuti dengan pengecekan `service_setting_id` jika fitur tersebut menyentuh data spesifik counter/loket perbankan.
