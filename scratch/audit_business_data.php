<?php

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\{Branch, Queue, Role, ServiceSetting, User};

$withoutRole = User::where('is_active', true)->doesntHave('roles')->count();
$counterMismatch = User::whereNotNull('service_setting_id')->whereHas('serviceSetting',
    fn ($query) => $query->whereColumn('service_settings.branch_id', '!=', 'users.branch_id'))->count();
$queueMismatch = Queue::withoutGlobalScopes()->whereHas('serviceSetting',
    fn ($query) => $query->whereColumn('service_settings.branch_id', '!=', 'queues.branch_id'))->count();
echo json_encode([
    'branches' => Branch::count(),
    'service_hours' => Branch::distinct()->pluck('operating_hours')->all(),
    'missing_system_roles' => array_values(array_diff(App\Services\IntegrityRules::SYSTEM_ROLES,
        Role::where('guard_name', 'web')->pluck('name')->all())),
    'active_accounts_without_role' => $withoutRole,
    'accounts_with_counter_office_mismatch' => $counterMismatch,
    'tickets_with_counter_office_mismatch' => $queueMismatch,
], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).PHP_EOL;
