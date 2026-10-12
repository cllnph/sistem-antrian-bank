<?php

namespace App\Http\Controllers;

use App\Enums\QueueStatus;
use App\Models\Branch;
use App\Models\Queue;
use App\Models\QueueLog;
use App\Models\ServiceSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class QueueController extends Controller
{
    public function resolveBranch(Request $request, ?string $branchIdentifier = null): Branch
    {
        if ($branchIdentifier) {
            $branch = Branch::query()
                ->where('code', $branchIdentifier)
                ->orWhere('slug', $branchIdentifier)
                ->orWhere('id', $branchIdentifier)
                ->first();
            if ($branch) return $branch;
        }

        $param = $request->query('branch') ?? $request->query('cabang');
        if ($param) {
            $branch = Branch::query()
                ->where('code', $param)
                ->orWhere('slug', $param)
                ->orWhere('id', $param)
                ->first();
            if ($branch) return $branch;
        }

        if (auth()->check() && auth()->user()->branch_id) {
            $branch = auth()->user()->branch;
            if ($branch) return $branch;
        }

        return Branch::query()->where('code', '001')->first() 
            ?? Branch::query()->where('is_active', true)->first() 
            ?? Branch::first();
    }

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

    public function takeNumber(Request $request, ?string $branch = null): View
    {
        if ($request->has('preview_ticket')) {
            session()->flash('ticket', $request->query('preview_ticket'));
        }

        $currentBranch = $this->resolveBranch($request, $branch);
        $branchId = $currentBranch->id;

        $settings = ServiceSetting::query()
            ->where('branch_id', $branchId)
            ->get()
            ->keyBy('service');

        $today = now()->toDateString();

        $tellerSettingIds = ServiceSetting::query()
            ->where('branch_id', $branchId)
            ->where('service', 'teller')
            ->pluck('id');

        $csSettingIds = ServiceSetting::query()
            ->where('branch_id', $branchId)
            ->where('service', 'cs')
            ->pluck('id');

        // Teller Next Number & Wait Info
        $maxTellerSeq = empty($tellerSettingIds) ? 0 : (Queue::withoutGlobalScope(\App\Models\Scopes\ServiceSettingScope::class)
            ->where('branch_id', $branchId)
            ->whereIn('service_setting_id', $tellerSettingIds)
            ->whereDate('created_at', $today)
            ->where('number', 'like', 'T-%')
            ->get()
            ->map(function ($q) {
                $parts = explode('-', $q->number);
                return (int) end($parts);
            })
            ->max() ?? 0);
        $nextTellerNumber = sprintf('T-%03d', $maxTellerSeq + 1);

        $tellerWaiting = empty($tellerSettingIds) ? 0 : Queue::withoutGlobalScope(\App\Models\Scopes\ServiceSettingScope::class)
            ->where('branch_id', $branchId)
            ->whereIn('service_setting_id', $tellerSettingIds)
            ->whereDate('created_at', $today)
            ->where('status', QueueStatus::WAITING)
            ->count();
        $tellerEstWait = $tellerWaiting > 0 
            ? "~" . ($tellerWaiting * 3) . "–" . ($tellerWaiting * 4) . " Menit ({$tellerWaiting} Menunggu)"
            : "~1–3 Menit (Langsung)";

        // CS Next Number & Wait Info
        $maxCsSeq = empty($csSettingIds) ? 0 : (Queue::withoutGlobalScope(\App\Models\Scopes\ServiceSettingScope::class)
            ->where('branch_id', $branchId)
            ->whereIn('service_setting_id', $csSettingIds)
            ->whereDate('created_at', $today)
            ->where('number', 'like', 'CS-%')
            ->get()
            ->map(function ($q) {
                $parts = explode('-', $q->number);
                return (int) end($parts);
            })
            ->max() ?? 0);
        $nextCsNumber = sprintf('CS-%03d', $maxCsSeq + 1);

        $csWaiting = empty($csSettingIds) ? 0 : Queue::withoutGlobalScope(\App\Models\Scopes\ServiceSettingScope::class)
            ->where('branch_id', $branchId)
            ->whereIn('service_setting_id', $csSettingIds)
            ->whereDate('created_at', $today)
            ->where('status', QueueStatus::WAITING)
            ->count();
        $csEstWait = $csWaiting > 0 
            ? "~" . ($csWaiting * 8) . "–" . ($csWaiting * 10) . " Menit ({$csWaiting} Menunggu)"
            : "~1–5 Menit (Langsung)";

        $csActive = ServiceSetting::where('branch_id', $branchId)->where('service', 'cs')->where('is_active', true)->exists();
        $tellerActive = ServiceSetting::where('branch_id', $branchId)->where('service', 'teller')->where('is_active', true)->exists();

        $csCounterName = ServiceSetting::where('branch_id', $branchId)->where('service', 'cs')->where('is_active', true)->orderBy('id')->value('counter_name') ?? 'Loket CS - Kredit & Pensiun';
        $tellerCounterName = ServiceSetting::where('branch_id', $branchId)->where('service', 'teller')->where('is_active', true)->orderBy('id')->value('counter_name') ?? 'Loket Teller - Kas & Pensiun';

        // Currently Serving Number (Sedang Dilayani)
        $currentServingTeller = empty($tellerSettingIds) ? null : Queue::withoutGlobalScope(\App\Models\Scopes\ServiceSettingScope::class)
            ->where('branch_id', $branchId)
            ->whereIn('service_setting_id', $tellerSettingIds)
            ->whereDate('created_at', $today)
            ->whereIn('status', [QueueStatus::CALLED, QueueStatus::SERVING])
            ->latest('called_at')
            ->value('number');

        $currentServingCs = empty($csSettingIds) ? null : Queue::withoutGlobalScope(\App\Models\Scopes\ServiceSettingScope::class)
            ->where('branch_id', $branchId)
            ->whereIn('service_setting_id', $csSettingIds)
            ->whereDate('created_at', $today)
            ->whereIn('status', [QueueStatus::CALLED, QueueStatus::SERVING])
            ->latest('called_at')
            ->value('number');

        $allBranches = Branch::where('is_active', true)->orderBy('code')->get();

        return view('queue.take-number', [
            'branch' => $currentBranch,
            'allBranches' => $allBranches,
            'settings' => $settings,
            'nextTellerNumber' => $nextTellerNumber,
            'tellerWaiting' => $tellerWaiting,
            'tellerEstWait' => $tellerEstWait,
            'currentServingTeller' => $currentServingTeller,
            'nextCsNumber' => $nextCsNumber,
            'csWaiting' => $csWaiting,
            'csEstWait' => $csEstWait,
            'currentServingCs' => $currentServingCs,
            'csCounterName' => $csCounterName,
            'tellerCounterName' => $tellerCounterName,
            'csActive' => $csActive,
            'tellerActive' => $tellerActive,
        ]);
    }

    public function storeNumber(Request $request, ?string $branch = null): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'service' => ['required', 'string'],
            'branch_id' => ['nullable'],
        ]);

        $serviceKey = $data['service'];
        $currentBranch = $this->resolveBranch($request, $request->input('branch_id') ?? $branch);
        $branchId = $currentBranch->id;

        $serviceSettingIds = ServiceSetting::query()
            ->where('branch_id', $branchId)
            ->where('service', $serviceKey)
            ->pluck('id');

        $setting = ServiceSetting::query()
            ->where('branch_id', $branchId)
            ->where('service', $serviceKey)
            ->where('is_active', true)
            ->first();

        if (! $setting) {
            $setting = ServiceSetting::query()
                ->where('branch_id', $branchId)
                ->where('service', $serviceKey)
                ->first();
        }

        if (! $setting) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json(['success' => false, 'message' => 'Layanan tidak ditemukan untuk cabang ini.'], 404);
            }
            return back()->with('error', 'Layanan tidak ditemukan untuk cabang ini.');
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
                $queue = DB::transaction(function () use ($setting, $serviceSettingIds, $prefix, $branchId, $request) {
                    $todayDate = now()->toDateString();
                    $maxSeq = Queue::query()
                        ->withoutGlobalScope(\App\Models\Scopes\ServiceSettingScope::class)
                        ->where('branch_id', $branchId)
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

                    $newQueue = Queue::create([
                        'branch_id' => $branchId,
                        'number' => sprintf('%s-%03d', $prefix, $sequence),
                        'service_setting_id' => $setting->id,
                        'status' => QueueStatus::WAITING,
                    ]);

                    QueueLog::create([
                        'queue_id' => $newQueue->id,
                        'branch_id' => $branchId,
                        'action' => 'created',
                        'payload' => [
                            'ip' => $request->ip(),
                            'user_agent' => $request->userAgent(),
                        ],
                    ]);

                    return $newQueue;
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
                ->where('branch_id', $branchId)
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
                'branch_id' => $branchId,
                'branch_name' => $currentBranch->name,
                'branch_address' => $currentBranch->address,
                'branch_phone' => $currentBranch->phone,
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

        $user = auth()->user();
        $branchId = $user->branch_id ?? request('branch_id');

        $query = Queue::query();
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }
        $query->delete();

        \Illuminate\Support\Facades\Log::warning("Banking Security Alert: User #{$user->id} reset queues for branch: " . ($branchId ?? 'ALL'));

        if (auth()->check()) {
            \App\Services\NotificationService::sendQueueResetNotification(auth()->user());
        }

        $branchName = $branchId ? Branch::find($branchId)?->name : 'Semua Cabang';
        return back()->with('status', "Data antrean {$branchName} berhasil di-reset.");
    }

    private function resolveServiceSetting(string $serviceKey): ?ServiceSetting
    {
        $user = auth()->user();
        $branchId = $user?->branch_id;

        if ($user && $user->service_setting_id) {
            $userSetting = ServiceSetting::query()->find($user->service_setting_id);
            if ($userSetting && $userSetting->service === $serviceKey) {
                return $userSetting;
            }
        }

        $query = ServiceSetting::query()->where('service', $serviceKey);
        if ($branchId) {
            $query->where('branch_id', $branchId);
        }

        return (clone $query)->where('is_active', true)->first()
            ?? $query->first();
    }

    private function dashboard(string $serviceKey, string $title): View
    {
        $setting = $this->resolveServiceSetting($serviceKey);
        $settingId = $setting?->id;
        $branchId = auth()->user()?->branch_id ?? $setting?->branch_id;

        $allServiceSettingIds = ServiceSetting::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
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
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->whereIn('service_setting_id', $allServiceSettingIds)
                ->whereDate('created_at', now()->toDateString())
                ->where('status', QueueStatus::WAITING)
                ->count() : 0,
            'waitingQueues' => !empty($allServiceSettingIds) ? Queue::query()
                ->withoutGlobalScope(\App\Models\Scopes\ServiceSettingScope::class)
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->whereIn('service_setting_id', $allServiceSettingIds)
                ->whereDate('created_at', now()->toDateString())
                ->where('status', QueueStatus::WAITING)
                ->oldest('id')
                ->limit(10)
                ->get() : collect(),
            'recentQueues' => $settingId ? Queue::query()
                ->withoutGlobalScope(\App\Models\Scopes\ServiceSettingScope::class)
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
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

        $branchId = auth()->user()?->branch_id ?? $setting->branch_id;

        $allServiceSettingIds = ServiceSetting::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->where('service', $serviceKey)
            ->pluck('id')
            ->toArray();

        $queue = DB::transaction(function () use ($setting, $allServiceSettingIds, $branchId) {
            Queue::query()
                ->withoutGlobalScope(\App\Models\Scopes\ServiceSettingScope::class)
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->where('service_setting_id', $setting->id)
                ->where('served_by', auth()->id())
                ->whereIn('status', [QueueStatus::CALLED, QueueStatus::SERVING])
                ->update(['status' => QueueStatus::COMPLETED]);

            $next = Queue::query()
                ->withoutGlobalScope(\App\Models\Scopes\ServiceSettingScope::class)
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
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

                QueueLog::create([
                    'queue_id' => $next->id,
                    'branch_id' => $branchId,
                    'actor_id' => auth()->id(),
                    'action' => 'called',
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

        QueueLog::create([
            'queue_id' => $queue->id,
            'branch_id' => $queue->branch_id,
            'actor_id' => auth()->id(),
            'action' => 'completed',
        ]);

        return back()->with('status', "Antrean {$queue->number} selesai dilayani.");
    }

    private function skip(string $serviceKey): RedirectResponse
    {
        $setting = $this->resolveServiceSetting($serviceKey);
        $queue = $setting ? $this->activeQueue($setting->id) : null;
        if (! $queue) return back()->with('status', 'Belum ada antrean aktif.');

        $queue->update(['status' => QueueStatus::SKIPPED]);

        QueueLog::create([
            'queue_id' => $queue->id,
            'branch_id' => $queue->branch_id,
            'actor_id' => auth()->id(),
            'action' => 'skipped',
        ]);

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

        QueueLog::create([
            'queue_id' => $queue->id,
            'branch_id' => $queue->branch_id,
            'actor_id' => auth()->id(),
            'action' => 'recalled',
        ]);

        return back()->with('status', "Antrean {$queue->number} dipanggil ulang ke {$setting->counter_name}.");
    }

    private function activeQueue(?int $settingId): ?Queue
    {
        if (! $settingId) return null;

        $user = auth()->user();
        return Queue::query()
            ->withoutGlobalScope(\App\Models\Scopes\ServiceSettingScope::class)
            ->when($user?->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
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
            ->with(['serviceSetting', 'servedBy.serviceSetting', 'branch'])
            ->where('number', $number)
            ->latest('created_at')
            ->first();

        $waitingBefore = 0;
        if ($queue && $queue->status === QueueStatus::WAITING) {
            $waitingBefore = Queue::query()
                ->where('branch_id', $queue->branch_id)
                ->where('service_setting_id', $queue->service_setting_id)
                ->where('status', QueueStatus::WAITING)
                ->where('id', '<', $queue->id)
                ->count();
        }

        return view('queue.verify', compact('queue', 'number', 'waitingBefore'));
    }
}
