<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class BankingPermissionSeeder extends Seeder
{
    /**
     * Seed all 44 banking operational permissions across 10 modules.
     */
    public function run(): void
    {
        $permissionsData = [
            // 1. Modul Dashboard
            ['name' => 'dashboard.admin', 'display_name' => 'Akses Admin', 'module' => 'Dashboard'],
            ['name' => 'dashboard.control_center', 'display_name' => 'Lihat Control Center', 'module' => 'Dashboard'],

            // 2. Modul User
            ['name' => 'user.view', 'display_name' => 'Lihat User', 'module' => 'Manajemen User'],
            ['name' => 'user.create', 'display_name' => 'Tambah User', 'module' => 'Manajemen User'],
            ['name' => 'user.edit', 'display_name' => 'Ubah User', 'module' => 'Manajemen User'],
            ['name' => 'user.delete', 'display_name' => 'Hapus User', 'module' => 'Manajemen User'],

            // 3. Modul Role & Hak Akses
            ['name' => 'role.view', 'display_name' => 'Lihat Role', 'module' => 'Role & Hak Akses'],
            ['name' => 'role.create', 'display_name' => 'Tambah Role', 'module' => 'Role & Hak Akses'],
            ['name' => 'role.edit', 'display_name' => 'Ubah Role', 'module' => 'Role & Hak Akses'],
            ['name' => 'role.delete', 'display_name' => 'Hapus Role', 'module' => 'Role & Hak Akses'],
            ['name' => 'role.manage_permissions', 'display_name' => 'Atur Hak Akses', 'module' => 'Role & Hak Akses'],

            // 4. Modul Loket
            ['name' => 'loket.view', 'display_name' => 'Lihat Loket', 'module' => 'Loket Layanan'],
            ['name' => 'loket.create', 'display_name' => 'Tambah Loket', 'module' => 'Loket Layanan'],
            ['name' => 'loket.edit', 'display_name' => 'Ubah Loket', 'module' => 'Loket Layanan'],
            ['name' => 'loket.delete', 'display_name' => 'Hapus Loket', 'module' => 'Loket Layanan'],
            ['name' => 'loket.assign_user', 'display_name' => 'Tetapkan User ke Loket', 'module' => 'Loket Layanan'],

            // 5. Modul Teller
            ['name' => 'teller.access', 'display_name' => 'Akses Menu Teller', 'module' => 'Teller Kas'],
            ['name' => 'teller.setor_tunai', 'display_name' => 'Transaksi Setor Tunai', 'module' => 'Teller Kas'],
            ['name' => 'teller.tarik_tunai', 'display_name' => 'Transaksi Tarik Tunai', 'module' => 'Teller Kas'],
            ['name' => 'teller.pembayaran_pensiun', 'display_name' => 'Transaksi Pembayaran Pensiun', 'module' => 'Teller Kas'],
            ['name' => 'teller.cetak_bukti', 'display_name' => 'Cetak Bukti Transaksi', 'module' => 'Teller Kas'],
            ['name' => 'teller.void', 'display_name' => 'Batalkan Transaksi (Void)', 'module' => 'Teller Kas'],

            // 6. Modul Customer Service (CS)
            ['name' => 'cs.access', 'display_name' => 'Akses Menu CS', 'module' => 'Customer Service'],
            ['name' => 'cs.buka_rekening', 'display_name' => 'Buka Rekening Baru', 'module' => 'Customer Service'],
            ['name' => 'cs.edit_nasabah', 'display_name' => 'Ubah Data Nasabah', 'module' => 'Customer Service'],
            ['name' => 'cs.cetak_buku', 'display_name' => 'Cetak Buku Tabungan', 'module' => 'Customer Service'],
            ['name' => 'cs.manage_card', 'display_name' => 'Aktivasi/Blokir Kartu', 'module' => 'Customer Service'],
            ['name' => 'cs.complaint', 'display_name' => 'Kelola Keluhan Nasabah', 'module' => 'Customer Service'],

            // 7. Modul Kredit/Pinjaman
            ['name' => 'kredit.view', 'display_name' => 'Lihat Pengajuan Kredit', 'module' => 'Kredit & Pinjaman'],
            ['name' => 'kredit.create', 'display_name' => 'Input Pengajuan Kredit', 'module' => 'Kredit & Pinjaman'],
            ['name' => 'kredit.approve', 'display_name' => 'Setujui/Tolak Kredit', 'module' => 'Kredit & Pinjaman'],
            ['name' => 'kredit.disburse', 'display_name' => 'Pencairan Kredit', 'module' => 'Kredit & Pinjaman'],
            ['name' => 'kredit.angsuran', 'display_name' => 'Kelola Angsuran', 'module' => 'Kredit & Pinjaman'],

            // 8. Modul Transaksi
            ['name' => 'transaksi.view', 'display_name' => 'Lihat Semua Transaksi', 'module' => 'Transaksi Perbankan'],
            ['name' => 'transaksi.approve', 'display_name' => 'Setujui Transaksi', 'module' => 'Transaksi Perbankan'],
            ['name' => 'transaksi.reversal', 'display_name' => 'Pembalikan Transaksi (Reversal)', 'module' => 'Transaksi Perbankan'],

            // 9. Modul Laporan
            ['name' => 'laporan.harian', 'display_name' => 'Lihat Laporan Harian', 'module' => 'Laporan & Audit'],
            ['name' => 'laporan.transaksi', 'display_name' => 'Lihat Laporan Transaksi', 'module' => 'Laporan & Audit'],
            ['name' => 'laporan.export', 'display_name' => 'Unduh Laporan (Excel/PDF)', 'module' => 'Laporan & Audit'],
            ['name' => 'laporan.keuangan', 'display_name' => 'Lihat Laporan Keuangan', 'module' => 'Laporan & Audit'],

            // 10. Modul Pengaturan Sistem
            ['name' => 'setting.general', 'display_name' => 'Kelola Konfigurasi Umum', 'module' => 'Pengaturan Sistem'],
            ['name' => 'setting.branch', 'display_name' => 'Kelola Cabang', 'module' => 'Pengaturan Sistem'],
            ['name' => 'setting.audit_log', 'display_name' => 'Log Aktivitas (Audit Log)', 'module' => 'Pengaturan Sistem'],
        ];

        $allPermNames = [];

        foreach ($permissionsData as $data) {
            $perm = Permission::findOrCreate($data['name'], 'web');
            $perm->update([
                'display_name' => $data['display_name'],
                'module' => $data['module'],
            ]);
            $allPermNames[] = $data['name'];
        }

        // Assign all permissions to Administrator
        $adminRole = Role::findOrCreate('admin', 'web');
        $adminRole->syncPermissions($allPermNames);

        // Assign Teller permissions
        $tellerRole = Role::findOrCreate('teller', 'web');
        $tellerRole->syncPermissions([
            'dashboard.admin', 'teller.access', 'teller.setor_tunai', 'teller.tarik_tunai', 'teller.pembayaran_pensiun', 'teller.cetak_bukti'
        ]);

        // Assign CS permissions
        $csRole = Role::findOrCreate('cs', 'web');
        $csRole->syncPermissions([
            'dashboard.admin', 'cs.access', 'cs.buka_rekening', 'cs.edit_nasabah', 'cs.cetak_buku', 'cs.manage_card', 'cs.complaint'
        ]);
    }
}
