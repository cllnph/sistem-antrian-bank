<?php

namespace App\Console\Commands;

use App\Models\Branch;
use App\Models\User;
use App\Services\BranchAdminRole;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CreateBranchAdmins extends Command
{
    protected $signature = 'branches:create-admins';

    protected $description = 'Buat admin cabang yang belum ada tanpa mengubah akun atau password lama';

    public function handle(): int
    {
        $rows = DB::transaction(function () {
            $role = BranchAdminRole::ensure();
            $rows = [];
            foreach (Branch::where('is_active', true)->orderBy('code')->get() as $branch) {
                $existing = User::where('branch_id', $branch->id)->role('admin_cabang')->first();
                if ($existing) {
                    $rows[] = [$branch->code, $branch->name, $existing->email, '(akun sudah ada)'];
                    continue;
                }
                $email = 'admin.'.$branch->slug.'@banktaspen.co.id';
                if (User::where('email', $email)->exists()) {
                    throw new \RuntimeException('Email sudah digunakan: '.$email.'. Tidak ada akun yang diubah.');
                }
                $password = bin2hex(random_bytes(8));
                $user = User::create([
                    'name' => 'Admin - '.$branch->name,
                    'email' => $email,
                    'branch_id' => $branch->id,
                    'service_setting_id' => null,
                    'password' => $password,
                    'is_active' => true,
                ]);
                $user->assignRole($role);
                $rows[] = [$branch->code, $branch->name, $email, $password];
            }

            return $rows;
        });
        $this->table(['Kode', 'Cabang', 'Email', 'Password awal'], $rows);

        return self::SUCCESS;
    }
}
