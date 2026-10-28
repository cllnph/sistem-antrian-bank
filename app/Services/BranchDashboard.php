<?php

namespace App\Services;

use App\Models\Branch;
use App\Models\BranchQueueState;
use App\Models\Queue;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class BranchDashboard
{
    public function branches(User $user): Builder
    {
        abort_unless($user->is_active, 403);

        return Branch::query()->when(! $user->hasRole('admin'), fn ($query) => $query->whereKey($user->branch_id ?? 0));
    }

    public function data(User $user, ?string $selected = null): array
    {
        $query = $this->branches($user);
        if ($selected !== null && $selected !== '') {
            abort_unless(ctype_digit($selected), 422);
            abort_unless((clone $query)->whereKey((int) $selected)->exists(), 403);
            $query->whereKey((int) $selected);
        }
        $branches = $query->orderBy('code')->get();
        foreach ($branches as $branch) {
            if (! BranchQueueState::find($branch->id)) {
                app(QueuePeriods::class)->ensure($branch);
            }
        }
        $counts = Queue::withoutGlobalScopes()->currentPeriod()->whereIn('queues.branch_id', $branches->modelKeys())
            ->join('service_settings', 'service_settings.id', '=', 'queues.service_setting_id')
            ->selectRaw('queues.branch_id, service_settings.service, queues.status, COUNT(*) as total')
            ->groupBy('queues.branch_id', 'service_settings.service', 'queues.status')->toBase()->get();
        $total = fn ($rows, $statuses) => (int) $rows->whereIn('status', $statuses)->sum('total');
        $summary = [];
        foreach (['cs' => 'Customer Service', 'teller' => 'Teller'] as $service => $label) {
            $rows = $counts->where('service', $service);
            $label = Role::where('name', $service)->where('guard_name', 'web')->value('display_name') ?: $label;
            $summary[$service] = ['label' => $label, 'waiting' => $total($rows, ['waiting']),
                'called' => $total($rows, ['called', 'serving']), 'completed' => $total($rows, ['completed']), 'skipped' => $total($rows, ['skipped'])];
        }
        foreach ($branches as $branch) {
            $rows = $counts->where('branch_id', $branch->id);
            $branch->setAttribute('waiting_count', $total($rows, ['waiting']));
            $branch->setAttribute('called_count', $total($rows, ['called', 'serving']));
            $branch->setAttribute('completed_count', $total($rows, ['completed']));
            $branch->setAttribute('total_count', (int) $rows->sum('total'));
            $branch->setAttribute('service_open', app(BranchSchedule::class)->isOpen($branch));
        }

        return compact('branches', 'summary');
    }
}
