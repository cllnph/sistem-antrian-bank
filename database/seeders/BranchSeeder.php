<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Queue;
use App\Models\ServiceSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class BranchSeeder extends Seeder
{
    public function run(): void
    {
        $branchesData = [
            [
                'code' => '001',
                'slug' => 'pusat',
                'name' => 'Kantor Pusat Operasional',
                'type' => 'Kantor Pusat',
                'address' => 'Jl. Raya Pd. Gede No.9, Jatirahayu, Kec. Pondok Melati, Kota Bekasi, Jawa Barat',
                'phone' => '021-8467944',
                'operating_hours' => 'Senin – Jumat (08.00 – 16.00 WIB)',
            ],
            [
                'code' => '002',
                'slug' => 'bogor',
                'name' => 'Kantor Cabang Bogor',
                'type' => 'Kantor Cabang',
                'address' => 'Jl. Pajajaran No. 45, Baranangsiang, Kota Bogor, Jawa Barat',
                'phone' => '0251-8321098',
                'operating_hours' => 'Senin – Jumat (08.00 – 16.00 WIB)',
            ],
            [
                'code' => '003',
                'slug' => 'depok',
                'name' => 'Kantor Cabang Depok',
                'type' => 'Kantor Cabang',
                'address' => 'Jl. Margonda Raya No. 120, Kemiri Muka, Beji, Kota Depok, Jawa Barat',
                'phone' => '021-77203456',
                'operating_hours' => 'Senin – Jumat (08.00 – 16.00 WIB)',
            ],
            [
                'code' => '004',
                'slug' => 'tangerang',
                'name' => 'Kantor Cabang Tangerang',
                'type' => 'Kantor Cabang',
                'address' => 'Jl. Daan Mogot No. 88, Sukarasa, Kec. Tangerang, Kota Tangerang, Banten',
                'phone' => '021-5524312',
                'operating_hours' => 'Senin – Jumat (08.00 – 16.00 WIB)',
            ],
            [
                'code' => '005',
                'slug' => 'jaktim',
                'name' => 'Kantor Cabang Jakarta Timur',
                'type' => 'Kantor Cabang',
                'address' => 'Jl. Pemuda No. 72, Rawamangun, Kec. Pulo Gadung, Jakarta Timur, DKI Jakarta',
                'phone' => '021-47865432',
                'operating_hours' => 'Senin – Jumat (08.00 – 16.00 WIB)',
            ],
            [
                'code' => '006',
                'slug' => 'karawang',
                'name' => 'Kantor Cabang Karawang',
                'type' => 'Kantor Cabang',
                'address' => 'Jl. Tuparev No. 105, Nagasari, Kec. Karawang Barat, Karawang, Jawa Barat',
                'phone' => '0267-8452134',
                'operating_hours' => 'Senin – Jumat (08.00 – 16.00 WIB)',
            ],
            [
                'code' => '007',
                'slug' => 'cikarang',
                'name' => 'Kantor Cabang Cikarang',
                'type' => 'Kantor Cabang',
                'address' => 'Ruko Cikarang Central City, Jl. Raya Cikarang - Cibarusah No. 18, Sukaresmi, Cikarang Selatan, Bekasi',
                'phone' => '021-89901234',
                'operating_hours' => 'Senin – Jumat (08.00 – 16.00 WIB)',
            ],
            [
                'code' => '008',
                'slug' => 'purwokerto',
                'name' => 'Kantor Cabang Purwokerto',
                'type' => 'Kantor Cabang',
                'address' => 'Jl. Jend. Soedirman No. 250, Purwokerto Lor, Kec. Purwokerto Timur, Banyumas, Jawa Tengah',
                'phone' => '0281-635421',
                'operating_hours' => 'Senin – Jumat (08.00 – 16.00 WIB)',
            ],
            [
                'code' => '009',
                'slug' => 'bekasi',
                'name' => 'Kantor Kas Bekasi',
                'type' => 'Kantor Kas',
                'address' => 'Jl. Ir. H. Juanda No. 80, Margahayu, Kec. Bekasi Timur, Kota Bekasi, Jawa Barat',
                'phone' => '021-8805678',
                'operating_hours' => 'Senin – Jumat (08.00 – 15.30 WIB)',
            ],
        ];

        $tellerRole = Role::findOrCreate('teller');
        $csRole = Role::findOrCreate('cs');

        foreach ($branchesData as $bData) {
            $branch = Branch::firstOrCreate(
                ['code' => $bData['code']],
                $bData
            );

            // Adopt legacy counters before creating the defaults, preserving their IDs.
            if ($branch->code === '001') {
                ServiceSetting::whereNull('branch_id')->update(['branch_id' => $branch->id]);
            }

            // Create missing default counters without reopening existing closed counters.
            $csCounterName = $branch->code === '001'
                ? 'Loket CS - Kredit & Pensiun'
                : 'Loket CS '.$branch->name;
            $tellerCounterName = $branch->code === '001'
                ? 'Loket Teller - Kas & Pensiun'
                : 'Loket Teller '.$branch->name;

            $csSetting = ServiceSetting::firstOrCreate(
                [
                    'branch_id' => $branch->id,
                    'service' => 'cs',
                ],
                [
                    'label' => 'Customer Service & Kredit Pensiun',
                    'counter_name' => $csCounterName,
                    'is_active' => true,
                ]
            );

            $tellerSetting = ServiceSetting::firstOrCreate(
                [
                    'branch_id' => $branch->id,
                    'service' => 'teller',
                ],
                [
                    'label' => 'Teller Tabungan & Pensiun TASPEN',
                    'counter_name' => $tellerCounterName,
                    'is_active' => true,
                ]
            );

            if (! app()->environment(['local', 'testing'])) {
                continue;
            }

            // Create demo operators only when the account does not exist.
            $branchSlug = $branch->slug;

            // Teller account
            $tellerUser = User::firstOrCreate(
                ['email' => "teller.{$branchSlug}@banktaspen.co.id"],
                [
                    'name' => "Teller - {$branch->name}",
                    'branch_id' => $branch->id,
                    'service_setting_id' => $tellerSetting->id,
                    'password' => 'password',
                    'is_active' => true,
                ]
            );
            if ($tellerUser->wasRecentlyCreated) {
                $tellerUser->syncRoles([$tellerRole]);
            }

            // CS account
            $csUser = User::firstOrCreate(
                ['email' => "cs.{$branchSlug}@banktaspen.co.id"],
                [
                    'name' => "CS - {$branch->name}",
                    'branch_id' => $branch->id,
                    'service_setting_id' => $csSetting->id,
                    'password' => 'password',
                    'is_active' => true,
                ]
            );
            if ($csUser->wasRecentlyCreated) {
                $csUser->syncRoles([$csRole]);
            }
        }

        // Recover branch ownership from the assigned counter for legacy records.
        foreach (ServiceSetting::whereNotNull('branch_id')->get() as $setting) {
            User::whereNull('branch_id')->where('service_setting_id', $setting->id)
                ->update(['branch_id' => $setting->branch_id]);
            Queue::withoutGlobalScopes()->whereNull('branch_id')->where('service_setting_id', $setting->id)
                ->update(['branch_id' => $setting->branch_id]);
        }
    }
}
