<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

if (config('database.default') !== 'mysql') {
    throw new RuntimeException('Use the local MySQL connection for migration compatibility audit.');
}
$configuration = config('database.connections.mysql');
$auditDatabase = 'antrian_print_audit_'.bin2hex(random_bytes(5));
$pdo = new PDO('mysql:host='.$configuration['host'].';port='.$configuration['port'].';charset=utf8mb4', $configuration['username'], $configuration['password'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
$pdo->exec('CREATE DATABASE `'.$auditDatabase.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
try {
    config(['database.connections.mysql.database' => $auditDatabase]);
    DB::purge('mysql');
    $latest = 'database/migrations/2026_10_27_190000_support_device_print_routing.php';
    $earlier = array_values(array_filter(glob(base_path('database/migrations/*.php')), fn ($path) => basename($path) !== basename($latest)));
    Artisan::call('migrate', ['--path' => $earlier, '--realpath' => true, '--force' => true]);
    $branch = App\Models\Branch::create(['code' => 'AUDIT', 'slug' => 'mysql-printer-audit', 'name' => 'Audit only', 'type' => 'Kantor Pusat', 'is_active' => true]);
    $setting = App\Models\ServiceSetting::create(['branch_id' => $branch->id, 'service' => 'cs', 'label' => 'CS', 'counter_name' => 'CS', 'is_active' => true]);
    $queue = App\Models\Queue::create(['branch_id' => $branch->id, 'service_setting_id' => $setting->id, 'number' => 'CS-001', 'status' => 'waiting']);
    $agentId = (string) Str::uuid();
    DB::table('print_agents')->insert(['id' => $agentId, 'branch_id' => $branch->id, 'name' => 'Old', 'printer_name' => 'L6290']);
    foreach ([null, $agentId] as $id) {
        DB::table('print_jobs')->insert(['id' => (string) Str::uuid(), 'queue_id' => $queue->id, 'owner_hash' => str_repeat('a',64), 'snapshot' => json_encode(['ticket' => 'CS-001']), 'printer_name' => 'L6290', 'status' => 'submitted', 'print_agent_id' => $id]);
    }
    Artisan::call('migrate', ['--path' => $latest, '--force' => true]);
    $secondId = (string) Str::uuid();
    DB::table('print_agents')->insert(['id' => $secondId, 'branch_id' => $branch->id, 'name' => 'New', 'printer_name' => 'L3250']);
    if (DB::table('print_agents')->count() !== 2 || DB::table('print_jobs')->where('transport','server')->count() !== 1 || DB::table('print_jobs')->where('transport','agent')->count() !== 1) {
        throw new RuntimeException('MySQL upgrade lost data or transport backfill is incorrect.');
    }
    $migration = require base_path($latest);
    try { $migration->down(); throw new LogicException('Unsafe rollback was accepted.'); }
    catch (RuntimeException $error) {
        if (! str_contains($error->getMessage(), 'Rollback ditolak')) { throw $error; }
    }
    DB::table('print_agents')->where('id',$secondId)->delete();
    $migration->down();
    $migration->up();
    echo 'PASS MySQL upgrade, foreign-key index, multiple printers, existing job backfill, rollback guard and safe rollback/reapply. Operational database untouched.'.PHP_EOL;
} finally {
    DB::disconnect('mysql');
    if (! preg_match('/^antrian_print_audit_[a-f0-9]{10}$/', $auditDatabase) || $auditDatabase === $configuration['database']) {
        throw new RuntimeException('Unsafe audit database cleanup.');
    }
    $pdo->exec('DROP DATABASE `'.$auditDatabase.'`');
}
