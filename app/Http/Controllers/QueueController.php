<?php

namespace App\Http\Controllers;

use App\Enums\QueueStatus;
use App\Models\Branch;
use App\Models\PrintJob;
use App\Models\Queue;
use App\Models\QueueLog;
use App\Models\Scopes\ServiceSettingScope;
use App\Models\ServiceSetting;
use App\Services\BranchSchedule;
use App\Services\KioskDeviceHistory;
use App\Services\KioskHours;
use App\Services\NotificationService;
use App\Services\OperationStatus;
use App\Services\Printing\KioskPrintRouting;
use App\Services\Printing\TicketPrinting;
use App\Services\QueuePeriods;
use App\Services\QueueResetService;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class QueueController extends Controller
{
    public function resolveBranch(Request $request, ?string $branchIdentifier = null): Branch
    {
        $identifier = $branchIdentifier ?? $request->query('branch') ?? $request->query('cabang');
        if ($identifier !== null) {
            abort_unless(is_string($identifier) && $identifier !== '', 404);

            return Branch::where('is_active', true)
                ->where(fn ($query) => $query->where('code', $identifier)->orWhere('slug', $identifier))
                ->firstOrFail();
        }

        if (auth()->user()?->branch_id) {
            return Branch::where('is_active', true)->findOrFail(auth()->user()->branch_id);
        }

        return Branch::where('is_active', true)->where('code', '001')->first()
            ?? Branch::where('is_active', true)->orderBy('code')->firstOrFail();
    }

    public function csIndex(): RedirectResponse
    {
        return redirect('/admin/queues');
    }

    public function tellerIndex(): RedirectResponse
    {
        return redirect('/admin/queues');
    }

    public function umumIndex(): RedirectResponse
    {
        return redirect('/admin/queues');
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
        app(QueuePeriods::class)->ensure($currentBranch);

        $settings = ServiceSetting::query()
            ->where('branch_id', $branchId)
            ->get()
            ->keyBy('service');

        $tellerSettingIds = ServiceSetting::query()
            ->where('branch_id', $branchId)
            ->where('service', 'teller')
            ->pluck('id');

        $csSettingIds = ServiceSetting::query()
            ->where('branch_id', $branchId)
            ->where('service', 'cs')
            ->pluck('id');

        $nextTellerNumber = app(QueuePeriods::class)->nextNumber($currentBranch, 'teller');

        $tellerWaiting = empty($tellerSettingIds) ? 0 : Queue::withoutGlobalScope(ServiceSettingScope::class)
            ->where('branch_id', $branchId)
            ->whereIn('service_setting_id', $tellerSettingIds)
            ->currentPeriod()
            ->where('status', QueueStatus::WAITING)
            ->count();
        $tellerEstWait = $tellerWaiting > 0
            ? '~'.($tellerWaiting * 3).'–'.($tellerWaiting * 4)." Menit ({$tellerWaiting} Menunggu)"
            : '~1–3 Menit (Langsung)';

        $nextCsNumber = app(QueuePeriods::class)->nextNumber($currentBranch, 'cs');

        $csWaiting = empty($csSettingIds) ? 0 : Queue::withoutGlobalScope(ServiceSettingScope::class)
            ->where('branch_id', $branchId)
            ->whereIn('service_setting_id', $csSettingIds)
            ->currentPeriod()
            ->where('status', QueueStatus::WAITING)
            ->count();
        $csEstWait = $csWaiting > 0
            ? '~'.($csWaiting * 8).'–'.($csWaiting * 10)." Menit ({$csWaiting} Menunggu)"
            : '~1–5 Menit (Langsung)';

        $csActive = ServiceSetting::where('branch_id', $branchId)->where('service', 'cs')->where('is_active', true)->exists();
        $tellerActive = ServiceSetting::where('branch_id', $branchId)->where('service', 'teller')->where('is_active', true)->exists();

        $csCounterName = ServiceSetting::where('branch_id', $branchId)->where('service', 'cs')->where('is_active', true)->orderBy('id')->value('counter_name') ?? 'Loket CS - Kredit & Pensiun';
        $tellerCounterName = ServiceSetting::where('branch_id', $branchId)->where('service', 'teller')->where('is_active', true)->orderBy('id')->value('counter_name') ?? 'Loket Teller - Kas & Pensiun';

        // Currently Serving Number (Sedang Dilayani)
        $currentServingTeller = empty($tellerSettingIds) ? null : Queue::withoutGlobalScope(ServiceSettingScope::class)
            ->where('branch_id', $branchId)
            ->whereIn('service_setting_id', $tellerSettingIds)
            ->currentPeriod()
            ->whereIn('status', [QueueStatus::CALLED, QueueStatus::SERVING])
            ->latest('called_at')
            ->value('number');

        $currentServingCs = empty($csSettingIds) ? null : Queue::withoutGlobalScope(ServiceSettingScope::class)
            ->where('branch_id', $branchId)
            ->whereIn('service_setting_id', $csSettingIds)
            ->currentPeriod()
            ->whereIn('status', [QueueStatus::CALLED, QueueStatus::SERVING])
            ->latest('called_at')
            ->value('number');

        $allBranches = Branch::where('is_active', true)->orderBy('code')->get();

        app(KioskDeviceHistory::class)->opened($request, $currentBranch);

        return view('queue.take-number', [
            'branch' => $currentBranch,
            'operations' => app(OperationStatus::class)->forBranch($currentBranch),
            'issuanceContext' => app(QueuePeriods::class)->context($request, $currentBranch),
            'allBranches' => $allBranches,
            'printSelection' => app(KioskPrintRouting::class)->selection($currentBranch, $request),
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

    public function storeNumber(Request $request, ?string $branch = null): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'service' => ['required', 'string', 'in:cs,teller,umum'],
            'branch_id' => ['nullable', 'integer', 'exists:branches,id'],
            'request_id' => ['required', 'uuid'],
            'issuance_context' => ['nullable', 'string', 'max:4096'],
        ]);

        $serviceKey = $data['service'];
        $currentBranch = $branch !== null ? $this->resolveBranch($request, $branch) : (isset($data['branch_id'])
            ? Branch::where('is_active', true)->findOrFail($data['branch_id'])
            : $this->resolveBranch($request, $branch));
        if (isset($data['branch_id']) && (int) $data['branch_id'] !== (int) $currentBranch->id) {
            throw ValidationException::withMessages([
                'branch_id' => 'Kantor tiket harus sesuai dengan kantor pada halaman kiosk.',
            ]);
        }
        $branchId = $currentBranch->id;
        app(QueuePeriods::class)->ensure($currentBranch);

        $printing = app(TicketPrinting::class);
        $requestId = $data['request_id'];
        if ($existing = PrintJob::find($requestId)) {
            $printing->assertReplay($existing, $request, $branchId, $serviceKey);

            return response()->json($printing->response($existing));
        }
        app(QueuePeriods::class)->expiredRequest($requestId, $request, $branchId, $serviceKey);
        abort_unless(! empty($data['issuance_context']), 409, 'Muat ulang halaman kiosk sebelum mengambil nomor baru.');
        if (! KioskHours::isOpen($currentBranch)) {
            $message = 'Pengambilan tiket sedang ditutup. Jadwal: '.app(BranchSchedule::class)->label($currentBranch).'.';

            return $request->expectsJson() || $request->ajax()
                ? response()->json(['success' => false, 'message' => $message], 422)
                : back()->with('error', $message);
        }

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

        try {
            $printConfiguration = $printing->ready($currentBranch, $request);
        } catch (\RuntimeException $e) {
            return $request->expectsJson() || $request->ajax()
                ? response()->json(['success' => false, 'message' => $e->getMessage()], 503)
                : back()->with('error', $e->getMessage());
        }

        $attempts = 0;
        $maxAttempts = 5;
        $queue = null;
        $printJob = null;

        while ($attempts < $maxAttempts) {
            try {
                $queue = DB::transaction(function () use ($setting, $branchId, $request, $printing, $requestId, $serviceKey, $printConfiguration, $data, &$printJob) {
                    // Lock a stable row even when today's queue is empty. All kiosks
                    // in this branch must finish allocating a number before the next.
                    $lockedBranch = Branch::whereKey($branchId)->lockForUpdate()->firstOrFail();
                    if ($existing = PrintJob::find($requestId)) {
                        $printing->assertReplay($existing, $request, $branchId, $serviceKey);
                        $printJob = $existing;

                        return $existing->ticket;
                    }
                    abort_unless(Branch::whereKey($branchId)->where('is_active', true)->exists()
                        && ServiceSetting::whereKey($setting->id)->where('is_active', true)->exists(), 422, 'Kantor atau loket sedang tidak aktif.');

                    app(QueuePeriods::class)->expiredRequest($requestId, $request, $branchId, $serviceKey);
                    app(QueuePeriods::class)->assertContext($request, $lockedBranch, $data['issuance_context']);
                    abort_unless(KioskHours::isOpen($lockedBranch), 422, 'Jam pengambilan tiket sudah ditutup.');

                    $newQueue = Queue::create([
                        'branch_id' => $branchId,
                        // The observer allocates from the locked branch/service counter.
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

                    $printJob = $printing->enqueue($newQueue, $requestId, $printing->owner($request), $printConfiguration);

                    return $newQueue;
                });
                break;
            } catch (QueryException $e) {
                if ($existing = PrintJob::find($requestId)) {
                    $printing->assertReplay($existing, $request, $branchId, $serviceKey);

                    return response()->json($printing->response($existing));
                }
                $attempts++;
                if ($attempts >= $maxAttempts) {
                    throw $e;
                }
                usleep(50000);
            }
        }

        if ($queue) {
            if ($queue->wasRecentlyCreated && $printJob) {
                app(KioskDeviceHistory::class)->issued($request, $printJob);
            }
            $waitingCount = Queue::withoutGlobalScopes()
                ->where('branch_id', $branchId)
                ->whereIn('service_setting_id', $serviceSettingIds)
                ->currentPeriod()
                ->where('status', QueueStatus::WAITING)
                ->count();

            if ($queue->wasRecentlyCreated && $waitingCount >= 10) {
                NotificationService::sendHighQueueVolumeNotification($setting->label, $waitingCount);
            }
        }

        if ($request->expectsJson() || $request->ajax()) {
            return response()->json($printing->response($printJob));
        }

        return back()->with('ticket', $queue->number)->with('verification_url', route('queue.verify', ['queueId' => $queue->id]));
    }

    public function completeCs(): RedirectResponse
    {
        return $this->complete('cs');
    }

    public function completeTeller(): RedirectResponse
    {
        return $this->complete('teller');
    }

    public function completeUmum(): RedirectResponse
    {
        return $this->complete('umum');
    }

    public function skipCs(): RedirectResponse
    {
        return $this->skip('cs');
    }

    public function skipTeller(): RedirectResponse
    {
        return $this->skip('teller');
    }

    public function skipUmum(): RedirectResponse
    {
        return $this->skip('umum');
    }

    public function recallCs(): RedirectResponse
    {
        return $this->recall('cs');
    }

    public function recallTeller(): RedirectResponse
    {
        return $this->recall('teller');
    }

    public function recallUmum(): RedirectResponse
    {
        return $this->recall('umum');
    }

    public function resetQueues(): RedirectResponse
    {
        $data = request()->validate(['branch_id' => ['required', 'integer', 'exists:branches,id'],
            'period_id' => ['required', 'uuid'], 'request_id' => ['required', 'uuid']]);
        $count = QueueResetService::reset(auth()->user(), (int) $data['branch_id'], null, $data['period_id'], $data['request_id']);

        return back()->with('status', $count.' antrean periode lama ditutup dan dibersihkan.');
    }

    private function resolveServiceSetting(string $serviceKey): ?ServiceSetting
    {
        $user = auth()->user();
        abort_unless($user?->is_active && $user->can('edit_queue'), 403);
        $setting = $user?->serviceSetting;
        if (! $user?->branch_id || ! $user->branch?->is_active || ! $setting
            || (int) $setting->branch_id !== (int) $user->branch_id || $setting->service !== $serviceKey) {
            return null;
        }

        app(QueuePeriods::class)->ensure($user->branch);

        return $setting->is_active ? $setting : null;
    }

    private function callNext(string $serviceKey): RedirectResponse
    {
        $setting = $this->resolveServiceSetting($serviceKey);
        if (! $setting) {
            return back()->with('status', 'Layanan tidak ditemukan.');
        }

        $branchId = auth()->user()?->branch_id ?? $setting->branch_id;

        $allServiceSettingIds = ServiceSetting::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->where('service', $serviceKey)
            ->pluck('id')
            ->toArray();

        $queue = DB::transaction(function () use ($setting, $allServiceSettingIds, $branchId) {
            Branch::whereKey($branchId)->lockForUpdate()->firstOrFail();
            ServiceSetting::whereKey($setting->id)->lockForUpdate()->firstOrFail();
            $hasActive = Queue::withoutGlobalScopes()->where('service_setting_id', $setting->id)
                ->currentPeriod()
                ->whereIn('status', [QueueStatus::CALLED, QueueStatus::SERVING])->exists();
            if ($hasActive) {
                throw ValidationException::withMessages([
                    'queue' => 'Selesaikan pelayanan aktif di loket sebelum memanggil nomor berikutnya.',
                ]);
            }

            $next = Queue::query()
                ->withoutGlobalScope(ServiceSettingScope::class)
                ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
                ->whereIn('service_setting_id', $allServiceSettingIds)
                ->currentPeriod()
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

    private function mutateActive(string $serviceKey, string $action): ?Queue
    {
        $setting = $this->resolveServiceSetting($serviceKey);
        if (! $setting) {
            return null;
        }

        return DB::transaction(function () use ($setting, $action) {
            Branch::whereKey($setting->branch_id)->lockForUpdate()->firstOrFail();
            $queue = $this->activeQueue($setting->id);
            if (! $queue) {
                return null;
            }
            $queue->update(match ($action) {
                'completed' => ['status' => QueueStatus::COMPLETED],
                'skipped' => ['status' => QueueStatus::SKIPPED],
                default => ['called_at' => now(), 'service_setting_id' => $setting->id],
            });
            QueueLog::create(['queue_id' => $queue->id, 'branch_id' => $queue->branch_id, 'actor_id' => auth()->id(), 'action' => $action]);

            return $queue;
        });
    }

    private function complete(string $serviceKey): RedirectResponse
    {
        $queue = $this->mutateActive($serviceKey, 'completed');

        return back()->with('status', $queue ? 'Antrean '.$queue->number.' selesai dilayani.' : 'Belum ada antrean aktif.');
    }

    private function skip(string $serviceKey): RedirectResponse
    {
        return $this->mutateActive($serviceKey, 'skipped')
            ? $this->callNext($serviceKey) : back()->with('status', 'Belum ada antrean aktif.');
    }

    private function recall(string $serviceKey): RedirectResponse
    {
        $queue = $this->mutateActive($serviceKey, 'recalled');

        return back()->with('status', $queue ? 'Antrean '.$queue->number.' dipanggil ulang.' : 'Belum ada antrean aktif.');
    }

    private function activeQueue(?int $settingId): ?Queue
    {
        if (! $settingId) {
            return null;
        }

        $user = auth()->user();

        return Queue::query()
            ->withoutGlobalScope(ServiceSettingScope::class)
            ->when($user?->branch_id, fn ($q) => $q->where('branch_id', $user->branch_id))
            ->where('service_setting_id', $settingId)
            ->where('served_by', auth()->id())
            ->currentPeriod()
            ->whereIn('status', [QueueStatus::CALLED, QueueStatus::SERVING])
            ->latest('called_at')->lockForUpdate()
            ->first();
    }

    public function verifyTicket(int $queueId): View
    {
        $queue = Queue::withoutGlobalScope(ServiceSettingScope::class)
            ->currentPeriod()->with(['serviceSetting', 'servedBy.serviceSetting', 'branch'])
            ->find($queueId);
        $number = $queue?->number ?? (string) $queueId;

        $waitingBefore = 0;
        if ($queue && $queue->status === QueueStatus::WAITING) {
            $waitingBefore = Queue::withoutGlobalScopes()
                ->where('branch_id', $queue->branch_id)
                ->whereHas('serviceSetting', fn ($settings) => $settings->where('service', $queue->serviceSetting->service))
                ->currentPeriod()
                ->where('status', QueueStatus::WAITING)
                ->where('id', '<', $queue->id)
                ->count();
        }

        return view('queue.verify', compact('queue', 'number', 'waitingBefore'));
    }
}
