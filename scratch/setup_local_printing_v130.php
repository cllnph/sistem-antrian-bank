<?php

use App\Models\Branch;
use App\Models\PrintAgent;
use App\Models\ServiceSetting;
use App\Models\User;
use Database\Seeders\RoleAndPermissionSeeder;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$database = config('database.connections.sqlite.database');
if (! app()->environment('testing') || config('database.default') !== 'sqlite' || ! str_contains(str_replace('\\', '/', $database), '/scratch/local-printing-v130/private/')) {
    throw new RuntimeException('An isolated SQLite fixture is required.');
}
Artisan::call('migrate', ['--force' => true]);
Artisan::call('db:seed', ['--class' => RoleAndPermissionSeeder::class, '--force' => true]);
$admin = User::factory()->create(['email' => 'print130@example.test', 'is_active' => true, 'password' => bcrypt('print130-fixture-only')]);
$admin->assignRole('admin');
$branch = Branch::create(['code' => '130', 'slug' => 'print130', 'name' => 'Kantor Uji Cetak Lokal', 'type' => 'Kantor Cabang', 'address' => 'Alamat uji printer lokal, cabang, dan fotokopi', 'is_active' => true]);
foreach (['cs', 'teller'] as $service) {
    ServiceSetting::create(['branch_id' => $branch->id, 'service' => $service, 'label' => $service, 'counter_name' => $service, 'is_active' => true]);
}
$agents = [];
foreach (['PC-CABANG', 'LAPTOP-SERVER'] as $machine) {
    $token = bin2hex(random_bytes(32));
    $agent = PrintAgent::create(['id' => (string) Str::uuid(), 'branch_id' => $branch->id, 'name' => $machine,
        'machine_name' => $machine, 'device_id' => (string) Str::uuid(), 'printer_name' => 'EPSON L3250 Series (Copy 2)', 'paper_profile' => 'a4',
        'token_hash' => hash('sha256', $token), 'enabled' => true, 'revoked' => false, 'printer_ready' => true, 'heartbeat_at' => now(),
        'installed_printers' => ['EPSON L3250 Series (Copy 2)', 'Panda USB']]);
    $agents[$machine] = ['id' => $agent->id, 'device_id' => $agent->device_id, 'token' => $token];
}
$other = Branch::create(['code' => '131', 'slug' => 'other-print130', 'name' => 'Kantor Uji Cabang Lain', 'type' => 'Kantor Cabang', 'is_active' => true]);
$foreign = PrintAgent::create(['id' => (string) Str::uuid(), 'branch_id' => $other->id, 'name' => 'PC-CABANG-LAIN', 'paper_profile' => 'a4']);
$fixture = ['branch_id' => $branch->id, 'other_branch_id' => $other->id, 'agents' => $agents, 'database' => $database];
file_put_contents(dirname($database).'/fixture.json', json_encode($fixture, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "Isolated UI fixture ready. No native printing enabled.\n";
