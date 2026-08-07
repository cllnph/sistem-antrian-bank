<?php

namespace App\Filament\Pages;

use App\Enums\QueueStatus;
use App\Models\Queue;
use App\Models\ServiceSetting;
use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Pages\Page;

class AdminControlCenter extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-chart-bar';
    protected string $view = 'filament.pages.admin-control-center';

    public static function getNavigationLabel(): string
    {
        return __('app.control_center');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('app.control_center');
    }

    public static function canAccess(): bool
    {
        return auth()->user()?->can('view_control_center') ?? false;
    }

    public function resetService(string $serviceKey): void
    {
        if (! in_array($serviceKey, ['cs', 'teller'], true)) {
            return;
        }

        $setting = ServiceSetting::query()->where('service', $serviceKey)->first();
        if (! $setting) return;

        $count = Queue::query()
            ->where('service_setting_id', $setting->id)
            ->whereIn('status', [QueueStatus::WAITING, QueueStatus::CALLED])
            ->update(['status' => QueueStatus::SKIPPED]);

        Notification::make()
            ->title("Antrean {$serviceKey} direset")
            ->body("{$count} antrean aktif ditutup sebagai dilewati; riwayat tetap tersimpan.")
            ->success()
            ->send();
    }

    protected function getViewData(): array
    {
        $today = now()->startOfDay();
        $summary = [];
        $settings = ServiceSetting::query()->whereIn('service', ['cs', 'teller'])->get()->keyBy('service');

        foreach (['cs' => __('app.role_cs'), 'teller' => __('app.role_teller')] as $serviceKey => $label) {
            $setting = $settings->get($serviceKey);
            $settingId = $setting?->id;

            $summary[$serviceKey] = [
                'label' => $label,
                'waiting' => $settingId ? Queue::query()->where('service_setting_id', $settingId)->where('created_at', '>=', $today)->where('status', QueueStatus::WAITING)->count() : 0,
                'called' => $settingId ? Queue::query()->where('service_setting_id', $settingId)->where('created_at', '>=', $today)->where('status', QueueStatus::CALLED)->count() : 0,
                'completed' => $settingId ? Queue::query()->where('service_setting_id', $settingId)->where('created_at', '>=', $today)->where('status', QueueStatus::COMPLETED)->count() : 0,
                'skipped' => $settingId ? Queue::query()->where('service_setting_id', $settingId)->where('created_at', '>=', $today)->where('status', QueueStatus::SKIPPED)->count() : 0,
            ];
        }

        return [
            'summary' => $summary,
            'staff' => User::role(['cs', 'teller'])->with('roles')->get(),
            'recentQueues' => Queue::query()->with('serviceSetting')->latest()->limit(12)->get(),
        ];
    }
}
