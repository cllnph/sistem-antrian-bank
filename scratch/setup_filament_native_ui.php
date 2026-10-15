<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
if (! app()->environment('testing') || config('database.default') !== 'sqlite') {
    throw new RuntimeException('UI fixtures require isolated SQLite testing environment.');
}
$path = base_path('scratch/filament-native-ui.sqlite');
if (realpath(config('database.connections.sqlite.database')) !== realpath($path)) {
    throw new RuntimeException('Unexpected UI testing database.');
}
Illuminate\Support\Facades\Artisan::call('migrate', ['--force' => true]);
Illuminate\Support\Facades\Artisan::call('db:seed', ['--force' => true]);
$bogor = App\Models\Branch::where('slug', 'bogor')->firstOrFail();
$role = App\Services\BranchAdminRole::ensure();
$user = App\Models\User::firstOrCreate(['email' => 'ui.branch@example.test'], [
    'name' => 'UI Branch Admin', 'branch_id' => $bogor->id, 'password' => 'password', 'is_active' => true,
]);
$user->assignRole($role);
echo "Isolated Filament UI fixture ready.\n";
