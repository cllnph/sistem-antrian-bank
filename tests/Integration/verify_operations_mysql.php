<?php

// Standalone isolated MySQL audit. Never migrates or truncates the live database.
use App\Models\Branch;
use App\Models\Queue;
use App\Services\QueuePeriods;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

require __DIR__.'/../../vendor/autoload.php';
$app = require __DIR__.'/../../bootstrap/app.php';
$app->make(Kernel::class)->bootstrap();
$sourceConnection = config('database.default');
$source = DB::connection()->getDatabaseName();
$schema = ($argv[1] ?? '') === '--child' ? $argv[2] : 'antrian_operations_audit_'.bin2hex(random_bytes(4));
if (! preg_match('/^antrian_operations_audit_[a-f0-9]{8}$/D', $schema) || $schema === $source) {
    throw new RuntimeException('Unsafe isolated schema.');
}
$config = config('database.connections.'.$sourceConnection);
if (($config['driver'] ?? '') !== 'mysql') {
    throw new RuntimeException('This audit requires MySQL.');
}
$config['database'] = $schema;
$config['url'] = null;
config(['database.connections.operations_audit' => $config]);

if (($argv[1] ?? '') === '--child') {
    DB::setDefaultConnection('operations_audit');
    $branch = Branch::findOrFail((int) $argv[3]);
    $remaining = (float) $argv[4] - microtime(true);
    if ($remaining > 0) {
        usleep((int) ($remaining * 1_000_000));
    }
    if ($argv[5] === 'reset') {
        app(QueuePeriods::class)->reset($branch, null, $argv[6], (string) Str::uuid());
    } else {
        for ($i = 0; $i < 8; $i++) {
            app(QueuePeriods::class)->ensure($branch);
            DB::transaction(function () use ($branch) {
                Branch::whereKey($branch->id)->lockForUpdate()->firstOrFail();
                Queue::create(['branch_id' => $branch->id, 'service_setting_id' => $branch->serviceSettings()->where('service', 'cs')->value('id')]);
            }, 5);
        }
    }
    echo "OK\n";
    exit(0);
}

$created = false;
$children = [];
try {
    DB::connection($sourceConnection)->statement('CREATE DATABASE `'.$schema.'` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
    $created = true;
    DB::setDefaultConnection('operations_audit');
    $migrationPath = 'database/migrations/2026_10_28_070000_add_branch_operations_and_queue_periods.php';
    $older = array_values(array_filter(glob(base_path('database/migrations/*.php')), fn ($path) => basename($path) < basename($migrationPath)));
    Artisan::call('migrate', ['--database' => 'operations_audit', '--path' => $older, '--realpath' => true, '--force' => true]);
    $now = now();
    $branch = DB::table('branches')->insertGetId(['code' => '991', 'slug' => 'mysql-audit', 'name' => 'MySQL audit', 'type' => 'Kantor Cabang', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
    $setting = DB::table('service_settings')->insertGetId(['branch_id' => $branch, 'service' => 'cs', 'label' => 'CS', 'counter_name' => 'CS', 'is_active' => true, 'created_at' => $now, 'updated_at' => $now]);
    $ticket = DB::table('queues')->insertGetId(['branch_id' => $branch, 'service_setting_id' => $setting, 'number' => 'CS-042', 'status' => 'waiting', 'created_at' => $now, 'updated_at' => $now]);
    $old = DB::table('queues')->insertGetId(['branch_id' => $branch, 'service_setting_id' => $setting, 'number' => 'CS-001', 'status' => 'completed', 'created_at' => $now->copy()->subDay(), 'updated_at' => $now]);
    $agent = (string) Str::uuid();
    DB::table('print_agents')->insert(['id' => $agent, 'branch_id' => $branch, 'name' => 'Preserved PC', 'printer_name' => 'EPSON', 'token_hash' => str_repeat('a', 64)]);
    $before = DB::table('queues')->orderBy('id')->get()->toJson();
    $migration = require base_path($migrationPath);
    $migration->up();
    $columns = array_values(array_diff(Schema::getColumnListing('queues'), ['period_id']));
    if (DB::table('queues')->orderBy('id')->get($columns)->toJson() !== $before || DB::table('queue_counters')->where('branch_id', $branch)->where('service', 'cs')->value('sequence') !== 42) {
        throw new RuntimeException('Upgrade changed original data or did not seed active counters.');
    }
    if (DB::table('queues')->where('id', $old)->value('period_id') !== null || DB::table('branch_queue_states')->whereNotNull('last_reset_at')->exists()) {
        throw new RuntimeException('Upgrade prematurely reset a branch.');
    }
    $migration->down();
    $migration->up();
    $period = app(QueuePeriods::class)->ensure(Branch::findOrFail($branch))->period_id;
    $barrier = (string) (microtime(true) + 2);
    foreach (['issue', 'issue', 'reset', 'issue', 'issue'] as $operation) {
        $pipes = [];
        $process = proc_open([PHP_BINARY, __FILE__, '--child', $schema, (string) $branch, $barrier, $operation, $period],
            [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes, base_path(), null, ['bypass_shell' => true]);
        if (! is_resource($process)) {
            throw new RuntimeException('Unable to launch isolated concurrent audit.');
        }
        fclose($pipes[0]);
        $children[] = [$process, $pipes];
    }
    foreach ($children as [$process, $pipes]) {
        $output = stream_get_contents($pipes[1]);
        $error = stream_get_contents($pipes[2]);
        fclose($pipes[1]);
        fclose($pipes[2]);
        if (proc_close($process) !== 0 || trim($output) !== 'OK') {
            throw new RuntimeException('Concurrent audit worker failed: '.mb_substr($error.$output, 0, 2000));
        }
    }
    $children = [];
    $rows = Queue::withoutGlobalScopes()->currentPeriod()->where('branch_id', $branch)->get();
    $sequence = DB::table('queue_counters')->where('branch_id', $branch)->where('service', 'cs')->value('sequence');
    if ($rows->pluck('number')->unique()->count() !== $rows->count() || $sequence !== $rows->count() || DB::table('queues')->where('id', $ticket)->exists()
        || DB::table('print_agents')->where('id', $agent)->value('token_hash') !== str_repeat('a', 64)) {
        throw new RuntimeException('Allocation/reset lost isolation, numbering, or printer identity.');
    }
    $blocked = false;
    try {
        $migration->down();
    } catch (RuntimeException $error) {
        $blocked = str_contains($error->getMessage(), 'Rollback ditolak');
    }
    if (! $blocked) {
        throw new RuntimeException('Rollback after reset must be rejected.');
    }
    echo json_encode(['result' => 'passed', 'migration' => 'additive, counters seeded, no premature cleanup, guarded rollback',
        'concurrency' => '32 allocations across 4 processes raced against one reset', 'current_tickets' => $rows->count(), 'printer_identity' => 'preserved'])."\n";
} finally {
    foreach ($children as [$process, $pipes]) {
        if (is_resource($process)) {
            proc_terminate($process);
            proc_close($process);
        }
    }
    DB::setDefaultConnection($sourceConnection);
    DB::purge('operations_audit');
    if ($created && preg_match('/^antrian_operations_audit_[a-f0-9]{8}$/D', $schema) && $schema !== $source) {
        DB::connection($sourceConnection)->statement('DROP DATABASE `'.$schema.'`');
    }
}
