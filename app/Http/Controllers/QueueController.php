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

    public function takeNumber(): View
    {
        $settings = ServiceSetting::query()->get()->keyBy('service');
        $today = now()->format('Ymd');

        $tellerSetting = $settings->get('teller');
        $csSetting = $settings->get('cs');

        // Teller Next Number & Wait Info
        $lastTeller = $tellerSetting ? Queue::where('service_setting_id', $tellerSetting->id)
            ->where(fn($q) => $q->where('number', 'like', "T-{$today}-%")->orWhere('number', 'like', "T-%"))
            ->latest('id')->first() : null;

        $nextTellerSeq = 1;
        if ($lastTeller) {
            $parts = explode('-', $lastTeller->number);
            $nextTellerSeq = ((int) end($parts)) + 1;
        }
        $nextTellerNumber = sprintf('T-%03d', $nextTellerSeq);

        $tellerWaiting = $tellerSetting ? Queue::where('service_setting_id', $tellerSetting->id)->where('status', QueueStatus::WAITING)->count() : 0;
        $tellerEstWait = $tellerWaiting > 0 
            ? "~" . ($tellerWaiting * 3) . "–" . ($tellerWaiting * 4) . " Menit ({$tellerWaiting} Menunggu)"
            : "~1–3 Menit (Langsung)";

        // CS Next Number & Wait Info
        $lastCs = $csSetting ? Queue::where('service_setting_id', $csSetting->id)
            ->where(fn($q) => $q->where('number', 'like', "CS-{$today}-%")->orWhere('number', 'like', "CS-%"))
            ->latest('id')->first() : null;

        $nextCsSeq = 1;
        if ($lastCs) {
            $parts = explode('-', $lastCs->number);
            $nextCsSeq = ((int) end($parts)) + 1;
        }
        $nextCsNumber = sprintf('CS-%03d', $nextCsSeq);

        $csWaiting = $csSetting ? Queue::where('service_setting_id', $csSetting->id)->where('status', QueueStatus::WAITING)->count() : 0;
        $csEstWait = $csWaiting > 0 
            ? "~" . ($csWaiting * 8) . "–" . ($csWaiting * 10) . " Menit ({$csWaiting} Menunggu)"
            : "~1–5 Menit (Langsung)";

        return view('queue.take-number', compact(
            'settings',
            'nextTellerNumber',
            'tellerWaiting',
            'tellerEstWait',
            'nextCsNumber',
            'csWaiting',
            'csEstWait'
        ));
    }

    public function storeNumber(Request $request): RedirectResponse
    {
        $data = $request->validate(['service' => ['required', 'string']]);
        $serviceKey = $data['service'];

        $setting = ServiceSetting::query()->where('service', $serviceKey)->first();
        if (! $setting) {
            return back()->with('error', 'Layanan tidak ditemukan.');
        }

        if (! $setting->is_active) {
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
                $queue = DB::transaction(function () use ($setting, $prefix) {
                    $today = now()->format('Ymd');
                    $lastQueue = Queue::query()
                        ->where('service_setting_id', $setting->id)
                        ->where(function ($q) use ($prefix, $today) {
                            $q->where('number', 'like', "{$prefix}-{$today}-%")
                              ->orWhere('number', 'like', "{$prefix}-%");
                        })
                        ->lockForUpdate()
                        ->latest('id')
                        ->first();

                    $sequence = 1;
                    if ($lastQueue) {
                        $parts = explode('-', $lastQueue->number);
                        $lastSeq = (int) end($parts);
                        $sequence = $lastSeq + 1;
                    }

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

        return back()->with('status', 'Semua data antrean berhasil di-reset.');
    }

    private function dashboard(string $serviceKey, string $title): View
    {
        $user = auth()->user();
        $setting = ServiceSetting::query()->where('service', $serviceKey)->first();
        $settingId = $setting?->id;

        $counterName = $user?->serviceSetting?->counter_name ?? "Petugas {$title}";

        return view('queue.dashboard', [
            'title' => $counterName,
            'service' => $serviceKey,
            'currentQueue' => $this->activeQueue($settingId),
            'waitingCount' => $settingId ? Queue::query()->where('service_setting_id', $settingId)->where('status', QueueStatus::WAITING)->count() : 0,
            'waitingQueues' => $settingId ? Queue::query()->where('service_setting_id', $settingId)->where('status', QueueStatus::WAITING)->oldest('id')->limit(10)->get() : collect(),
            'recentQueues' => $settingId ? Queue::query()->where('service_setting_id', $settingId)->whereIn('status', [QueueStatus::COMPLETED, QueueStatus::SKIPPED])->latest('updated_at')->limit(8)->get() : collect(),
        ]);
    }

    private function callNext(string $serviceKey): RedirectResponse
    {
        $setting = ServiceSetting::query()->where('service', $serviceKey)->first();
        if (! $setting) return back()->with('status', 'Layanan tidak ditemukan.');

        $queue = DB::transaction(function () use ($setting) {
            Queue::query()
                ->where('service_setting_id', $setting->id)
                ->where('served_by', auth()->id())
                ->where('status', QueueStatus::CALLED)
                ->update(['status' => QueueStatus::COMPLETED]);

            $next = Queue::query()
                ->where('service_setting_id', $setting->id)
                ->where('status', QueueStatus::WAITING)
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if ($next) {
                $next->update([
                    'status' => QueueStatus::CALLED,
                    'called_at' => now(),
                    'served_by' => auth()->id(),
                ]);
            }

            return $next;
        });

        if (! $queue) {
            return back()->with('status', 'Tidak ada antrean yang menunggu.');
        }

        return back()->with('status', "Antrean {$queue->number} dipanggil.");
    }

    private function complete(string $serviceKey): RedirectResponse
    {
        $setting = ServiceSetting::query()->where('service', $serviceKey)->first();
        $queue = $setting ? $this->activeQueue($setting->id) : null;
        if (! $queue) return back()->with('status', 'Belum ada antrean aktif.');
        $queue->update(['status' => QueueStatus::COMPLETED]);
        return back()->with('status', "Antrean {$queue->number} selesai dilayani.");
    }

    private function skip(string $serviceKey): RedirectResponse
    {
        $setting = ServiceSetting::query()->where('service', $serviceKey)->first();
        $queue = $setting ? $this->activeQueue($setting->id) : null;
        if (! $queue) return back()->with('status', 'Belum ada antrean aktif.');
        $queue->update(['status' => QueueStatus::SKIPPED]);
        return $this->callNext($serviceKey);
    }

    private function recall(string $serviceKey): RedirectResponse
    {
        $setting = ServiceSetting::query()->where('service', $serviceKey)->first();
        $queue = $setting ? $this->activeQueue($setting->id) : null;
        if (! $queue) return back()->with('status', 'Belum ada antrean aktif.');
        $queue->update(['called_at' => now()]);
        return back()->with('status', "Antrean {$queue->number} dipanggil ulang.");
    }

    private function activeQueue(?int $settingId): ?Queue
    {
        if (! $settingId) return null;

        return Queue::query()
            ->where('service_setting_id', $settingId)
            ->where('served_by', auth()->id())
            ->where('status', QueueStatus::CALLED)
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
