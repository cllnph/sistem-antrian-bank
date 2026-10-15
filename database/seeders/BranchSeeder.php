<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Queue;
use App\Models\ServiceSetting;
use App\Models\User;
use Illuminate\Database\Seeder;
use App\Models\Role;

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
                ...BranchContactSeeder::CONTACTS['pusat'],
                'operating_hours' => 'Senin – Jumat (08.00 – 15.00 WIB)',
            ],
            [
                'code' => '002',
                'slug' => 'bogor',
                'name' => 'Kantor Cabang Bogor',
                'type' => 'Kantor Cabang',
                ...BranchContactSeeder::CONTACTS['bogor'],
                'operating_hours' => 'Senin – Jumat (08.00 – 15.00 WIB)',
            ],
            [
                'code' => '003',
                'slug' => 'depok',
                'name' => 'Kantor Cabang Depok',
                'type' => 'Kantor Cabang',
                ...BranchContactSeeder::CONTACTS['depok'],
                'operating_hours' => 'Senin – Jumat (08.00 – 15.00 WIB)',
            ],
            [
                'code' => '004',
                'slug' => 'tangerang',
                'name' => 'Kantor Cabang Tangerang',
                'type' => 'Kantor Cabang',
                ...BranchContactSeeder::CONTACTS['tangerang'],
                'operating_hours' => 'Senin – Jumat (08.00 – 15.00 WIB)',
            ],
            [
                'code' => '005',
                'slug' => 'jaktim',
                'name' => 'Kantor Cabang Jakarta Timur',
                'type' => 'Kantor Cabang',
                ...BranchContactSeeder::CONTACTS['jaktim'],
                'operating_hours' => 'Senin – Jumat (08.00 – 15.00 WIB)',
            ],
            [
                'code' => '006',
                'slug' => 'karawang',
                'name' => 'Kantor Cabang Karawang',
                'type' => 'Kantor Cabang',
                ...BranchContactSeeder::CONTACTS['karawang'],
                'operating_hours' => 'Senin – Jumat (08.00 – 15.00 WIB)',
            ],
            [
                'code' => '007',
                'slug' => 'cikarang',
                'name' => 'Kantor Cabang Cikarang',
                'type' => 'Kantor Cabang',
                ...BranchContactSeeder::CONTACTS['cikarang'],
                'operating_hours' => 'Senin – Jumat (08.00 – 15.00 WIB)',
            ],
            [
                'code' => '008',
                'slug' => 'purwokerto',
                'name' => 'Kantor Cabang Purwokerto',
                'type' => 'Kantor Cabang',
                ...BranchContactSeeder::CONTACTS['purwokerto'],
                'operating_hours' => 'Senin – Jumat (08.00 – 15.00 WIB)',
            ],
            [
                'code' => '009',
                'slug' => 'bekasi',
                'name' => 'Kantor Kas Bekasi',
                'type' => 'Kantor Kas',
                ...BranchContactSeeder::CONTACTS['bekasi'],
                'operating_hours' => 'Senin – Jumat (08.00 – 15.00 WIB)',
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
