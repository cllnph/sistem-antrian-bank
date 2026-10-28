<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$private = __DIR__.'/multi-printer-audit/private';
if (! app()->environment('testing') || config('database.default') !== 'sqlite'
    || realpath(config('database.connections.sqlite.database')) !== realpath($private.'/app.sqlite')
    || ! app(App\Services\Printing\PrinterGateway::class) instanceof App\Services\Printing\FakePrinterGateway) {
    throw new RuntimeException('Isolated multi-printer audit database and fake gateway required.');
}
Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
Illuminate\Support\Facades\Artisan::call('db:seed', ['--class' => Database\Seeders\RoleAndPermissionSeeder::class, '--force' => true]);
$password = bin2hex(random_bytes(16));
$admin = App\Models\User::factory()->create(['name' => 'Admin Audit Printer', 'email' => 'printer-audit@example.test', 'password' => Illuminate\Support\Facades\Hash::make($password), 'is_active' => true]);
$admin->assignRole('admin');
$branches = [];
foreach (['001' => 'Kantor Pusat', '002' => 'Kantor Cabang Audit'] as $code => $name) {
    $branch = App\Models\Branch::create(['code' => $code, 'slug' => 'audit-'.$code, 'name' => $name, 'type' => 'Kantor Pusat', 'address' => 'Alamat Audit '.$code, 'is_active' => true]);
    $branches[$code] = $branch;
    foreach (['cs', 'teller'] as $service) {
        App\Models\ServiceSetting::create(['branch_id' => $branch->id, 'service' => $service, 'label' => strtoupper($service), 'counter_name' => 'Loket '.strtoupper($service), 'is_active' => true]);
    }
}
$old = App\Models\PrintAgent::create(['id' => (string) Illuminate\Support\Str::uuid(), 'branch_id' => $branches['001']->id, 'name' => 'PC Pusat Lama', 'printer_name' => 'ITPRINTER (L6290 Series)', 'enabled' => false]);
$misplaced = App\Models\PrintAgent::create(['id' => (string) Illuminate\Support\Str::uuid(), 'branch_id' => $branches['002']->id, 'name' => 'Epson Salah Cabang', 'printer_name' => 'EPSON L3250 Series', 'enabled' => false]);
file_put_contents($private.'/fixture.json', json_encode(['email' => $admin->email, 'password' => $password, 'old_agent_id' => $old->id, 'misplaced_agent_id' => $misplaced->id], JSON_PRETTY_PRINT));
echo 'Isolated audit fixture ready; no physical printing.'.PHP_EOL;
