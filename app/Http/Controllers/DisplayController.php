<?php

namespace App\Http\Controllers;

use App\Enums\QueueStatus;
use App\Models\Queue;
use App\Models\ServiceSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class DisplayController extends Controller
{
    public function index(): View
    {
        return view('display');
    }

    public function getDisplayData(): JsonResponse
    {
        $activeSettings = ServiceSetting::query()->where('is_active', true)->get();
        $allSettings = ServiceSetting::query()->get();
        $settingIds = $activeSettings->pluck('id')->toArray();

        $today = now()->toDateString();

        $csIds = $allSettings->where('service', 'cs')->pluck('id')->toArray();
        $tellerIds = $allSettings->where('service', 'teller')->pluck('id')->toArray();
        $umumIds = $allSettings->where('service', 'umum')->pluck('id')->toArray();

        $csWaitCount = empty($csIds) ? 0 : Queue::query()
            ->withoutGlobalScope(\App\Models\Scopes\ServiceSettingScope::class)
            ->whereIn('service_setting_id', $csIds)
            ->whereDate('created_at', $today)
            ->where('status', QueueStatus::WAITING)
            ->count();

        $tellerWaitCount = empty($tellerIds) ? 0 : Queue::query()
            ->withoutGlobalScope(\App\Models\Scopes\ServiceSettingScope::class)
            ->whereIn('service_setting_id', $tellerIds)
            ->whereDate('created_at', $today)
            ->where('status', QueueStatus::WAITING)
            ->count();

        $umumWaitCount = empty($umumIds) ? 0 : Queue::query()
            ->withoutGlobalScope(\App\Models\Scopes\ServiceSettingScope::class)
            ->whereIn('service_setting_id', $umumIds)
            ->whereDate('created_at', $today)
            ->where('status', QueueStatus::WAITING)
            ->count();

        // Single query for all CALLED queues grouped by service_setting_id
        $calledQueues = empty($settingIds) ? collect() : Queue::query()
            ->withoutGlobalScope(\App\Models\Scopes\ServiceSettingScope::class)
            ->with(['servedBy.serviceSetting'])
            ->whereIn('service_setting_id', $settingIds)
            ->whereDate('created_at', $today)
            ->where('status', QueueStatus::CALLED)
            ->orderBy('called_at', 'desc')
            ->get()
            ->groupBy('service_setting_id');

        $countersData = [];
        foreach ($activeSettings as $setting) {
            $queues = $calledQueues->get($setting->id);
            $latest = $queues?->first();

            $waitCount = match ($setting->service) {
                'cs' => $csWaitCount,
                'teller' => $tellerWaitCount,
                'umum' => $umumWaitCount,
                default => 0,
            };

            $labelName = match ($setting->service) {
                'cs' => 'CUSTOMER SERVICE & KREDIT PENSIUN',
                'teller' => 'TELLER TABUNGAN & PENSIUN TASPEN',
                'umum' => 'LAYANAN UMUM',
                default => strtoupper($setting->label ?? 'LAYANAN'),
            };

            $countersData[] = [
                'id' => $setting->id,
                'service' => $setting->service,
                'label' => $labelName,
                'counter_name' => $setting->counter_name,
                'is_active' => (bool) $setting->is_active,
                'current_number' => $latest?->number ?? '-',
                'served_by_name' => $latest?->servedBy?->name ?? 'Belum Dipanggil',
                'waiting_count' => $waitCount,
                'called_at' => $latest?->called_at?->toIso8601String(),
            ];
        }

        $csSetting = $activeSettings->firstWhere('service', 'cs');
        $tellerSetting = $activeSettings->firstWhere('service', 'teller');
        $umumSetting = $activeSettings->firstWhere('service', 'umum');

        return response()->json([
            'counters' => $countersData,
            'total_active' => count($countersData),
            'cs' => $this->formatQueueData($calledQueues->get($csSetting?->id), $csSetting?->counter_name ?? 'Loket CS'),
            'teller' => $this->formatQueueData($calledQueues->get($tellerSetting?->id), $tellerSetting?->counter_name ?? 'Loket Teller'),
            'umum' => $this->formatQueueData($calledQueues->get($umumSetting?->id), $umumSetting?->counter_name ?? 'Loket Umum'),
            'waiting' => [
                'cs' => $csWaitCount,
                'teller' => $tellerWaitCount,
                'umum' => $umumWaitCount,
            ],
            'settings' => [
                'cs' => [
                    'label' => $csSetting?->label ?? 'Customer Service',
                    'counter_name' => $csSetting?->counter_name ?? 'Loket CS',
                    'is_active' => $csSetting?->is_active ?? true,
                ],
                'teller' => [
                    'label' => $tellerSetting?->label ?? 'Teller',
                    'counter_name' => $tellerSetting?->counter_name ?? 'Loket Teller',
                    'is_active' => $tellerSetting?->is_active ?? true,
                ],
                'umum' => [
                    'label' => $umumSetting?->label ?? 'Layanan Umum',
                    'counter_name' => $umumSetting?->counter_name ?? 'Loket Umum',
                    'is_active' => $umumSetting?->is_active ?? true,
                ],
            ],
        ]);
    }

    private function formatQueueData(?\Illuminate\Support\Collection $queues, string $defaultCounterName): ?array
    {
        if (! $queues || $queues->isEmpty()) {
            return null;
        }

        $queues = $queues->take(10);
        $latest = $queues->first();

        return [
            'number' => $latest->number,
            'called_at' => $latest->called_at?->toIso8601String(),
            'counter_name' => $latest->servedBy?->serviceSetting?->counter_name ?? $defaultCounterName,
            'served_by_name' => $latest->servedBy?->name ?? 'Petugas',
            'all_called' => $queues->map(fn ($q) => [
                'id' => $q->id,
                'number' => $q->number,
                'called_at' => $q->called_at?->toIso8601String(),
                'counter_name' => $q->servedBy?->serviceSetting?->counter_name ?? $defaultCounterName,
                'served_by_id' => $q->served_by,
                'served_by_name' => $q->servedBy?->name ?? 'Petugas',
            ])->values()->toArray(),
        ];
    }
}
