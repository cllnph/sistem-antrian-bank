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
        return view('display', [
            'initialData' => $this->buildDisplayData(),
        ]);
    }

    public function getDisplayData(): JsonResponse
    {
        return response()->json($this->buildDisplayData());
    }

    public function buildDisplayData(): array
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

        // Earliest waiting ticket to be called next
        $nextCsWaiting = empty($csIds) ? null : Queue::query()
            ->withoutGlobalScope(\App\Models\Scopes\ServiceSettingScope::class)
            ->whereIn('service_setting_id', $csIds)
            ->whereDate('created_at', $today)
            ->where('status', QueueStatus::WAITING)
            ->orderBy('id', 'asc')
            ->value('number');

        $nextTellerWaiting = empty($tellerIds) ? null : Queue::query()
            ->withoutGlobalScope(\App\Models\Scopes\ServiceSettingScope::class)
            ->whereIn('service_setting_id', $tellerIds)
            ->whereDate('created_at', $today)
            ->where('status', QueueStatus::WAITING)
            ->orderBy('id', 'asc')
            ->value('number');

        $nextUmumWaiting = empty($umumIds) ? null : Queue::query()
            ->withoutGlobalScope(\App\Models\Scopes\ServiceSettingScope::class)
            ->whereIn('service_setting_id', $umumIds)
            ->whereDate('created_at', $today)
            ->where('status', QueueStatus::WAITING)
            ->orderBy('id', 'asc')
            ->value('number');

        // Single query for all CALLED/SERVING queues grouped by service_setting_id
        $calledQueues = empty($settingIds) ? collect() : Queue::query()
            ->withoutGlobalScope(\App\Models\Scopes\ServiceSettingScope::class)
            ->with(['servedBy.serviceSetting'])
            ->whereIn('service_setting_id', $settingIds)
            ->whereDate('created_at', $today)
            ->whereIn('status', [QueueStatus::CALLED, QueueStatus::SERVING])
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

            $nextWaiting = match ($setting->service) {
                'cs' => $nextCsWaiting ?? 'belum ada antrean',
                'teller' => $nextTellerWaiting ?? 'belum ada antrean',
                'umum' => $nextUmumWaiting ?? 'belum ada antrean',
                default => 'belum ada antrean',
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
                'current_number' => $latest?->number ?? 'belum ada antrean',
                'served_by_name' => $latest?->servedBy?->name ?? 'Belum Dipanggil',
                'waiting_count' => $waitCount,
                'next_waiting_number' => $nextWaiting,
                'called_at' => $latest?->called_at?->toIso8601String(),
            ];
        }

        $csSetting = $activeSettings->firstWhere('service', 'cs');
        $tellerSetting = $activeSettings->firstWhere('service', 'teller');
        $umumSetting = $activeSettings->firstWhere('service', 'umum');

        $maxTellerSeq = Queue::query()
            ->withoutGlobalScope(\App\Models\Scopes\ServiceSettingScope::class)
            ->whereIn('service_setting_id', $tellerIds)
            ->whereDate('created_at', $today)
            ->where('number', 'like', 'T-%')
            ->get()
            ->map(function ($q) {
                $parts = explode('-', $q->number);
                return (int) end($parts);
            })
            ->max() ?? 0;
        $nextTellerNumber = sprintf('T-%03d', $maxTellerSeq + 1);

        $maxCsSeq = Queue::query()
            ->withoutGlobalScope(\App\Models\Scopes\ServiceSettingScope::class)
            ->whereIn('service_setting_id', $csIds)
            ->whereDate('created_at', $today)
            ->where('number', 'like', 'CS-%')
            ->get()
            ->map(function ($q) {
                $parts = explode('-', $q->number);
                return (int) end($parts);
            })
            ->max() ?? 0;
        $nextCsNumber = sprintf('CS-%03d', $maxCsSeq + 1);

        $allCsCalled = $calledQueues->filter(fn ($val, $key) => in_array($key, $csIds))->flatten(1)->sortByDesc('called_at');
        $allTellerCalled = $calledQueues->filter(fn ($val, $key) => in_array($key, $tellerIds))->flatten(1)->sortByDesc('called_at');
        $allUmumCalled = $calledQueues->filter(fn ($val, $key) => in_array($key, $umumIds))->flatten(1)->sortByDesc('called_at');

        return [
            'counters' => $countersData,
            'total_active' => count($countersData),
            'cs' => $this->formatQueueData($allCsCalled, $csSetting?->counter_name ?? 'Loket CS'),
            'teller' => $this->formatQueueData($allTellerCalled, $tellerSetting?->counter_name ?? 'Loket Teller'),
            'umum' => $this->formatQueueData($allUmumCalled, $umumSetting?->counter_name ?? 'Loket Umum'),
            'teller_wait_count' => $tellerWaitCount,
            'cs_wait_count' => $csWaitCount,
            'next_teller_number' => $nextTellerNumber,
            'next_cs_number' => $nextCsNumber,
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
        ];
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
