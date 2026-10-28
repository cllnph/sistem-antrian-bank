<?php

// Additive migration audit on an isolated MySQL schema. No workers or printer calls.
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();

$original = config('database.default');
$source = DB::connection($original)->getDatabaseName();
$audit = 'antrian_print130_audit_'.bin2hex(random_bytes(4));
if (! preg_match('/^[a-zA-Z0-9_]+$/D', $source) || ! preg_match('/^antrian_print130_audit_[a-f0-9]{8}$/D', $audit) || $audit === $source) {
    throw new RuntimeException('Unsafe audit schema name.');
}
$connection = config('database.connections.'.$original);
$connection['database'] = $audit;
$connection['url'] = null;
config(['database.connections.printing_mysql_audit' => $connection]);
$created = false;
try {
    DB::connection($original)->statement('CREATE DATABASE `'.$audit.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $created = true;
    foreach (['print_agents', 'print_agent_enrollments', 'kiosk_print_bindings', 'print_jobs'] as $table) {
        DB::connection($original)->statement('CREATE TABLE `'.$audit.'`.`'.$table.'` LIKE `'.$source.'`.`'.$table.'`');
        DB::connection($original)->statement('INSERT INTO `'.$audit.'`.`'.$table.'` SELECT * FROM `'.$source.'`.`'.$table.'`');
    }
    DB::setDefaultConnection('printing_mysql_audit');
    // Allow repeatable audits after the real application has already been upgraded.
    foreach ([
        'print_agents' => ['paper_profile', 'auto_name', 'enrollment_auto_enable', 'auto_enable_pending', 'inventory_status'],
        'print_agent_enrollments' => ['paper_profile', 'machine_name', 'auto_enable'],
        'kiosk_print_bindings' => ['verified_at', 'proof_device_id', 'binding_source', 'paper_profile'],
        'print_jobs' => ['owner_type'],
    ] as $table => $added) {
        $present = array_values(array_intersect($added, Schema::getColumnListing($table)));
        if ($present) {
            Schema::table($table, fn ($blueprint) => $blueprint->dropColumn($present));
        }
    }
    $before = [];
    foreach (['print_agents' => 'id', 'print_agent_enrollments' => 'print_agent_id', 'kiosk_print_bindings' => 'id', 'print_jobs' => 'id'] as $table => $key) {
        $columns = Schema::getColumnListing($table);
        $before[$table] = ['key' => $key, 'columns' => $columns, 'rows' => DB::table($table)->orderBy($key)->get($columns)->toJson()];
    }
    $migration = require __DIR__.'/../database/migrations/2026_10_28_010000_verify_local_printing_and_paper_profiles.php';
    $migration->up();
    foreach ($before as $table => $state) {
        if (DB::table($table)->orderBy($state['key'])->get($state['columns'])->toJson() !== $state['rows']) {
            throw new RuntimeException('Migration changed original data in '.$table);
        }
    }
    if (DB::table('print_agents')->where('auto_enable_pending', true)->exists() || DB::table('kiosk_print_bindings')->whereNotNull('verified_at')->exists()) {
        throw new RuntimeException('Migration must not enable or verify existing installations.');
    }
    $migration->down();
    $migration->up();
    $id = (string) Str::uuid();
    DB::table('print_jobs')->insert(['id' => $id, 'queue_id' => 0, 'owner_hash' => str_repeat('a', 64), 'owner_type' => 'device', 'snapshot' => '{}', 'printer_name' => 'Audit only', 'transport' => 'browser', 'status' => 'browser_ready']);
    $rejected = false;
    try {
        $migration->down();
    } catch (RuntimeException $error) {
        $rejected = str_contains($error->getMessage(), 'Rollback ditolak');
    }
    if (! $rejected || ! Schema::hasTable('print_agent_browser_pairings')) {
        throw new RuntimeException('Unsafe rollback was not rejected before schema changes.');
    }
    echo "PASS MySQL: original rows unchanged, no automatic activation, legacy bindings unverified, reversible legacy upgrade, device-ticket rollback guarded.\n";
} finally {
    DB::setDefaultConnection($original);
    DB::purge('printing_mysql_audit');
    if ($created && preg_match('/^antrian_print130_audit_[a-f0-9]{8}$/D', $audit) && $audit !== $source) {
        DB::connection($original)->statement('DROP DATABASE `'.$audit.'`');
    }
}
