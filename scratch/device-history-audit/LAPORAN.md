# Riwayat perangkat kiosk — 15 Oktober 2026

- Jalur operasional saat pemeriksaan: `server`, 0 penghubung cabang aktif. Tugas cetak terbaru menuju ITPRINTER melalui server.
- Migrasi MySQL berhasil diterapkan. Riwayat 14 penerbitan tiket dipulihkan menjadi 5 sesi browser berdasarkan owner sesi dan metadata QueueLog yang sudah tersimpan. Pengulangan importer memulihkan 0 tiket tambahan; tidak menggandakan riwayat.
- Regresi aplikasi: **166 tes, 1.080 assertions**, lulus (`../device-history-audit-regression.xml`).
- Setelah perbaikan alias query filter Filament (`filters`), 8 tes fitur riwayat dijalankan ulang: **61 assertions**, lulus.
- Browser Edge nyata dengan SQLite khusus: dua konteks dengan IP dan User-Agent sama tetap menjadi dua perangkat. Cookie pengenal mempertahankan perangkat saat sesi diganti. Konteks Android terpisah tercatat sebagai perangkat ketiga.
- Navigasi melalui Penghubung Cetak Cabang, pemberian nama, serta tautan Riwayat Aktivitas dan filter per perangkat lulus, tanpa error JavaScript (`browser-results.json`, `devices.png`, `events.png`).
- Audit browser hanya membuka kiosk/admin; tidak menerbitkan tiket, tidak mencetak, dan tidak mendaftarkan PrintAgent otomatis. Fixture privat terpisah dari database operasional.
- Jenis perangkat merupakan perkiraan dari browser. Riwayat lama per sesi tidak membuktikan jumlah perangkat fisik; kunjungan sebelum pencatatan aktif yang tidak memiliki log tidak dibuat-buat.
