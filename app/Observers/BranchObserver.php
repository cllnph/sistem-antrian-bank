<?php

namespace App\Observers;

use App\Enums\QueueStatus;
use App\Models\Branch;
use App\Services\IntegrityRules;
use Illuminate\Validation\ValidationException;

class BranchObserver
{
    public function saving(Branch $branch): void
    {
        if (! $branch->exists) {
            return;
        }
        if ($branch->isDirty(['code', 'slug']) && IntegrityRules::deletionReason($branch)) {
            throw ValidationException::withMessages(['code' => 'Kode dan slug kantor yang sudah digunakan tidak dapat diubah.']);
        }
        if ($branch->isDirty('is_active') && ! $branch->is_active
            && $branch->queues()->withoutGlobalScopes()
                ->whereIn('status', [QueueStatus::WAITING, QueueStatus::CALLED, QueueStatus::SERVING])->exists()) {
            throw ValidationException::withMessages(['is_active' => 'Selesaikan atau reset antrean aktif sebelum menonaktifkan kantor.']);
        }
    }

    public function deleting(Branch $branch): void
    {
        IntegrityRules::assertDeletable($branch);
    }
}
