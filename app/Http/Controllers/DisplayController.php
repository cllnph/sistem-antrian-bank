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
        $settings = ServiceSetting::query()->get()->keyBy('service');

        return response()->json([
            'cs' => $this->currentQueue($settings->get('cs')?->id, $settings->get('cs')?->counter_name ?? 'Loket CS'),
            'teller' => $this->currentQueue($settings->get('teller')?->id, $settings->get('teller')?->counter_name ?? 'Loket Teller'),
            'umum' => $this->currentQueue($settings->get('umum')?->id, $settings->get('umum')?->counter_name ?? 'Loket Umum'),
            'waiting' => [
                'cs' => $this->waitingCount($settings->get('cs')?->id),
                'teller' => $this->waitingCount($settings->get('teller')?->id),
                'umum' => $this->waitingCount($settings->get('umum')?->id),
            ],
            'settings' => [
                'cs' => [
                    'label' => $settings->get('cs')?->label ?? 'Customer Service',
                    'counter_name' => $settings->get('cs')?->counter_name ?? 'Loket CS',
                    'is_active' => $settings->get('cs')?->is_active ?? true,
                ],
                'teller' => [
                    'label' => $settings->get('teller')?->label ?? 'Teller',
                    'counter_name' => $settings->get('teller')?->counter_name ?? 'Loket Teller',
                    'is_active' => $settings->get('teller')?->is_active ?? true,
                ],
                'umum' => [
                    'label' => $settings->get('umum')?->label ?? 'Layanan Umum',
                    'counter_name' => $settings->get('umum')?->counter_name ?? 'Loket Umum',
                    'is_active' => $settings->get('umum')?->is_active ?? true,
                ],
            ],
        ]);
    }

    private function waitingCount(?int $serviceSettingId): int
    {
        if (!$serviceSettingId) return 0;

        return Queue::query()
            ->where('service_setting_id', $serviceSettingId)
            ->where('status', QueueStatus::WAITING)
            ->count();
    }

    private function currentQueue(?int $serviceSettingId, string $defaultCounterName): ?array
    {
        if (!$serviceSettingId) return null;

        $queues = Queue::query()
            ->with(['servedBy.serviceSetting'])
            ->where('service_setting_id', $serviceSettingId)
            ->where('status', QueueStatus::CALLED)
            ->orderBy('called_at', 'desc')
            ->take(10)
            ->get();

        $latest = $queues->first();

        if (! $latest) {
            return null;
        }

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
