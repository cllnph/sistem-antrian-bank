<?php
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
$count = 0;
foreach (App\Models\Role::whereNull('display_name')->get() as $role) {
    $role->update(['display_name' => $role->display_name]);
    $count++;
}
echo "Initialized labels: {$count} (codes and permissions unchanged).".PHP_EOL;
