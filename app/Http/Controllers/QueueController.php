<?php

namespace App\Http\Controllers;

use App\Enums\QueueStatus;
use App\Models\Queue;
use App\Models\ServiceSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class QueueController extends Controller
{
    public function csIndex(): View
    {
        return $this->dashboard('cs', 'Customer Service');
    }

    public function tellerIndex(): View
    {
        return $this->dashboard('teller', 'Teller');
    }

    public function umumIndex(): View
    {
        return $this->dashboard('umum', 'Layanan Umum');
    }

    public function nextCs(): RedirectResponse
    {
        return $this->callNext('cs');
    }

    public function nextTeller(): RedirectResponse
    {
        return $this->callNext('teller');
    }

    public function nextUmum(): RedirectResponse
    {
        return $this->callNext('umum');
    }

    public function takeNumber(Request $request): View
    {
        if ($request->has('preview_ticket')) {
            session()->flash('ticket', $request->query('preview_ticket'));
        }

        $settings = ServiceSetting::query()->get()->keyBy('service');
        $today = now()->toDateString();

        $tellerSettingIds = ServiceSetting::query()->where('service', 'teller')->pluck('id');
        $csSettingIds = ServiceSetting::query()->where('service', 'cs')->pluck('id');

        // Teller Next Number & Wait Info
        $maxTellerSeq = Queue::withoutGlobalScope(\App\Models\Scopes\ServiceSettingScope::class)
            ->whereIn('service_setting_id', $tellerSettingIds)
            ->whereDate('created_at', $today)
            ->where('number', 'like', 'T-%')
            ->get()
            ->map(function ($q) {
                $parts = explode('-', $q->number);
                return (int) end($parts);
            })
            ->max() ?? 0;
        $nextTellerNumber = sprintf('T-%03d', $maxTellerSeq + 1);

        $tellerWaiting = Queue::withoutGlobalScope(\App\Models\Scopes\ServiceSettingScope::class)
            ->whereIn('service_setting_id', $tellerSettingIds)
            ->whereDate('created_at', $today)
            ->where('status', QueueStatus::WAITING)
            ->count();
        $tellerEstWait = $tellerWaiting > 0 
            ? "~" . ($tellerWaiting * 3) . "–" . ($tellerWaiting * 4) . " Menit ({$tellerWaiting} Menunggu)"
            : "~1–3 Menit (Langsung)";

        // CS Next Number & Wait Info
        $maxCsSeq = Queue::withoutGlobalScope(\App\Models\Scopes\ServiceSettingScope::class)
            ->whereIn('service_setting_id', $csSettingIds)
            ->whereDate('created_at', $today)
            ->where('number', 'like', 'CS-%')
            ->get()
            ->map(function ($q) {
                $parts = explode('-', $q->number);
                return (int) end($parts);
            })
            ->max() ?? 0;
        $nextCsNumber = sprintf('CS-%03d', $maxCsSeq + 1);

        $csWaiting = Queue::withoutGlobalScope(\App\Models\Scopes\ServiceSettingScope::class)
            ->whereIn('service_setting_id', $csSettingIds)
            ->whereDate('created_at', $today)
            ->where('status', QueueStatus::WAITING)
            ->count();
        $csEstWait = $csWaiting > 0 
            ? "~" . ($csWaiting * 8) . "–" . ($csWaiting * 10) . " Menit ({$csWaiting} Menunggu)"
            : "~1–5 Menit (Langsung)";

        $csActive = ServiceSetting::where('service', 'cs')->where('is_active', true)->exists();
        $tellerActive = ServiceSetting::where('service', 'teller')->where('is_active', true)->exists();

        $csCounterName = ServiceSetting::where('service', 'cs')->where('is_active', true)->orderBy('id')->value('counter_name') ?? 'Loket CS - Kredit & Pensiun';
        $tellerCounterName = ServiceSetting::where('service', 'teller')->where('is_active', true)->orderBy('id')->value('counter_name') ?? 'Loket Teller - Kas & Pensiun';

        // Currently Serving Number (Sedang Dilayani)
        $currentServingTeller = Queue::withoutGlobalScope(\App\Models\Scopes\ServiceSettingScope::class)
            ->whereIn('service_setting_id', $tellerSettingIds)
            ->whereDate('created_at', $today)
            ->whereIn('status', [QueueStatus::CALLED, QueueStatus::SERVING])
            ->latest('called_at')
            ->value('number');

        $currentServingCs = Queue::withoutGlobalScope(\App\Models\Scopes\ServiceSettingScope::class)
            ->whereIn('service_setting_id', $csSettingIds)
            ->whereDate('created_at', $today)
            ->whereIn('status', [QueueStatus::CALLED, QueueStatus::SERVING])
            ->latest('called_at')
            ->value('number');

        return view('queue.take-number', compact(
            'settings',
            'nextTellerNumber',
            'tellerWaiting',
            'tellerEstWait',
            'currentServingTeller',
            'nextCsNumber',
            'csWaiting',
            'csEstWait',
            'currentServingCs',
            'csCounterName',
            'tellerCounterName',
            'csActive',
            'tellerActive'
        ));
    }

    public function storeNumber(Request $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $data = $request->validate(['service' => ['required', 'string']]);
        $serviceKey = $data['service'];

        $serviceSettingIds = ServiceSetting::query()->where('service', $serviceKey)->pluck('id');
        $setting = ServiceSetting::query()->where('service', $serviceKey)->where('is_active', true)->first();

        if (! $setting) {
            $setting = ServiceSetting::query()->where('service', $serviceKey)->first();
        }

        if (! $setting) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Layanan tidak ditemukan.'], 404);
            }
            return back()->with('error', 'Layanan tidak ditemukan.');
        }

        if (! $setting->is_active) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Layanan ini sedang tidak aktif.'], 422);
            }
            return back()->with('error', 'Layanan ini sedang tidak aktif.');
        }

        $prefix = match ($serviceKey) {
            'cs' => 'CS',
            'teller' => 'T',
            default => 'U',
        };

        $attempts = 0;
        $maxAttempts = 5;
        $queue = null;

        while ($attempts < $maxAttempts) {
            try {
                $queue = DB::transaction(function () use ($setting, $serviceSettingIds, $prefix) {
                    $todayDate = now()->toDateString();
                    $maxSeq = Queue::query()
                        ->withoutGlobalScope(\App\Models\Scopes\ServiceSettingScope::class)
                        ->whereIn('service_setting_id', $serviceSettingIds)
                        ->whereDate('created_at', $todayDate)
                        ->where('number', 'like', "{$prefix}-%")
                        ->lockForUpdate()
                        ->get()
                        ->map(function ($q) {
                            $parts = explode('-', $q->number);
                            return (int) end($parts);
                        })
                        ->max() ?? 0;

                    $sequence = $maxSeq + 1;

                    return Queue::create([
                        'number' => sprintf('%s-%03d', $prefix, $sequence),
                        'service_setting_id' => $setting->id,
                        'status' => QueueStatus::WAITING,
                    ]);
                });
                break;
            } catch (\Illuminate\Database\QueryException $e) {
                $attempts++;
                if ($attempts >= $maxAttempts) {
                    throw $e;
                }
                usleep(50000);
            }
        }

        if ($queue) {
            $waitingCount = Queue::query()
                ->where('service_setting_id', $setting->id)
                ->where('status', QueueStatus::WAITING)
                ->count();

            if ($waitingCount >= 10) {
                \App\Services\NotificationService::sendHighQueueVolumeNotification($setting->label, $waitingCount);
            }
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'ticket' => $queue->number,
                'id' => $queue->id,
                'service' => $serviceKey,
                'service_name' => $serviceKey === 'teller' ? 'TELLER' : 'CUSTOMER SERVICE',
                'counter_name' => $setting->counter_name,
                'waiting' => $waitingCount ?? 0,
                'created_at' => now()->format('Y-m-d H:i:s'),
                'date_formatted' => now()->translatedFormat('l, d F Y') . ' · ' . now()->format('H:i') . ' WIB',
            ]);
        }

        return back()->with('ticket', $queue->number);
    }

    public function completeCs(): RedirectResponse { return $this->complete('cs'); }
    public function completeTeller(): RedirectResponse { return $this->complete('teller'); }
    public function completeUmum(): RedirectResponse { return $this->complete('umum'); }
    public function skipCs(): RedirectResponse { return $this->skip('cs'); }
    public function skipTeller(): RedirectResponse { return $this->skip('teller'); }
    public function skipUmum(): RedirectResponse { return $this->skip('umum'); }
    public function recallCs(): RedirectResponse { return $this->recall('cs'); }
    public function recallTeller(): RedirectResponse { return $this->recall('teller'); }
    public function recallUmum(): RedirectResponse { return $this->recall('umum'); }

    public function resetQueues(): RedirectResponse
    {
        abort_if(!auth()->user()->can('reset_queues'), 403);

        Queue::query()->delete();

        \Illuminate\Support\Facades\Log::warning("Banking Security Alert: Admin User #" . auth()->id() . " reset all queues.");

        if (auth()->check()) {
            \App\Services\NotificationService::sendQueueResetNotification(auth()->user());
        }

        return back()->with('status', 'Semua data antrean berhasil di-reset.');
    }

    private function resolveServiceSetting(string $serviceKey): ?ServiceSetting
    {
        $user = auth()->user();
        if ($user && $user->service_setting_id) {
            $userSetting = ServiceSetting::query()->find($user->service_setting_id);
            if ($userSetting && $userSetting->service === $serviceKey) {
                return $userSetting;
            }
        }

        return ServiceSetting::query()->where('service', $serviceKey)->where('is_active', true)->first()
            ?? ServiceSetting::query()->where('service', $serviceKey)->first();
    }

    private function dashboard(string $serviceKey, string $title): View
    {
        $setting = $this->resolveServiceSetting($serviceKey);
        $settingId = $setting?->id;

        $allServiceSettingIds = ServiceSetting::query()
            ->where('service', $serviceKey)
            ->pluck('id')
            ->toArray();

        $counterName = $setting?->counter_name ?? "Petugas {$title}";

        return view('queue.dashboard', [
            'title' => $counterName,
            'service' => $serviceKey,
            'currentQueue' => $this->activeQueue($settingId),
            'waitingCount' => !empty($allServiceSettingIds) ? Queue::query()
                ->withoutGlobalScope(\App\Models\Scopes\ServiceSettingScope::class)
                ->whereIn('service_setting_id', $allServiceSettingIds)
                ->whereDate('created_at', now()->toDateString())
                ->where('status', QueueStatus::WAITING)
                ->count() : 0,
            'waitingQueues' => !empty($allServiceSettingIds) ? Queue::query()
                ->withoutGlobalScope(\App\Models\Scopes\ServiceSettingScope::class)
                ->whereIn('service_setting_id', $allServiceSettingIds)
                ->whereDate('created_at', now()->toDateString())
                ->where('status', QueueStatus::WAITING)
                ->oldest('id')
                ->limit(10)
                ->get() : collect(),
            'recentQueues' => $settingId ? Queue::query()
                ->withoutGlobalScope(\App\Models\Scopes\ServiceSettingScope::class)
                ->where('service_setting_id', $settingId)
                ->whereDate('created_at', now()->toDateString())
                ->whereIn('status', [QueueStatus::COMPLETED, QueueStatus::SKIPPED])
                ->latest('updated_at')
                ->limit(8)
                ->get() : collect(),
        ]);
    }

    private function callNext(string $serviceKey): RedirectResponse
    {
        $setting = $this->resolveServiceSetting($serviceKey);
        if (! $setting) return back()->with('status', 'Layanan tidak ditemukan.');

        $allServiceSettingIds = ServiceSetting::query()
            ->where('service', $serviceKey)
            ->pluck('id')
            ->toArray();

        $queue = DB::transaction(function () use ($setting, $allServiceSettingIds) {
            Queue::query()
                ->withoutGlobalScope(\App\Models\Scopes\ServiceSettingScope::class)
                ->where('service_setting_id', $setting->id)
                ->where('served_by', auth()->id())
                ->whereIn('status', [QueueStatus::CALLED, QueueStatus::SERVING])
                ->update(['status' => QueueStatus::COMPLETED]);

            $next = Queue::query()
                ->withoutGlobalScope(\App\Models\Scopes\ServiceSettingScope::class)
                ->whereIn('service_setting_id', $allServiceSettingIds)
                ->whereDate('created_at', now()->toDateString())
                ->where('status', QueueStatus::WAITING)
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if ($next) {
                $next->update([
                    'status' => QueueStatus::CALLED,
                    'called_at' => now(),
                    'served_by' => auth()->id(),
                    'service_setting_id' => $setting->id,
                ]);
            }

            return $next;
        });

        if (! $queue) {
            return back()->with('status', 'Tidak ada antrean yang menunggu.');
        }

        return back()->with('status', "Antrean {$queue->number} dipanggil ke {$setting->counter_name}.");
    }

    private function complete(string $serviceKey): RedirectResponse
    {
        $setting = $this->resolveServiceSetting($serviceKey);
        $queue = $setting ? $this->activeQueue($setting->id) : null;
        if (! $queue) return back()->with('status', 'Belum ada antrean aktif.');
        $queue->update(['status' => QueueStatus::COMPLETED]);
        return back()->with('status', "Antrean {$queue->number} selesai dilayani.");
    }

    private function skip(string $serviceKey): RedirectResponse
    {
        $setting = $this->resolveServiceSetting($serviceKey);
        $queue = $setting ? $this->activeQueue($setting->id) : null;
        if (! $queue) return back()->with('status', 'Belum ada antrean aktif.');
        $queue->update(['status' => QueueStatus::SKIPPED]);
        return $this->callNext($serviceKey);
    }

    private function recall(string $serviceKey): RedirectResponse
    {
        $setting = $this->resolveServiceSetting($serviceKey);
        $queue = $setting ? $this->activeQueue($setting->id) : null;
        if (! $queue) return back()->with('status', 'Belum ada antrean aktif.');
        $queue->update([
            'called_at' => now(),
            'service_setting_id' => $setting->id,
        ]);
        return back()->with('status', "Antrean {$queue->number} dipanggil ulang ke {$setting->counter_name}.");
    }

    private function activeQueue(?int $settingId): ?Queue
    {
        if (! $settingId) return null;

        return Queue::query()
            ->withoutGlobalScope(\App\Models\Scopes\ServiceSettingScope::class)
            ->where('service_setting_id', $settingId)
            ->where('served_by', auth()->id())
            ->whereIn('status', [QueueStatus::CALLED, QueueStatus::SERVING])
            ->latest('called_at')
            ->first();
    }

    public function verifyTicket(string $number): View
    {
        $number = strtoupper(trim($number));
        $queue = Queue::query()
            ->with(['serviceSetting', 'servedBy.serviceSetting'])
            ->where('number', $number)
            ->first();

        $waitingBefore = 0;
        if ($queue && $queue->status === QueueStatus::WAITING) {
            $waitingBefore = Queue::query()
                ->where('service_setting_id', $queue->service_setting_id)
                ->where('status', QueueStatus::WAITING)
                ->where('id', '<', $queue->id)
                ->count();
        }

        return view('queue.verify', compact('queue', 'number', 'waitingBefore'));
    }
}
