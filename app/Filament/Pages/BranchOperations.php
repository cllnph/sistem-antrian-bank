<?php

namespace App\Filament\Pages;

use App\Enums\QueueStatus;
use App\Models\Branch;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;

class BranchOperations extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?int $navigationSort = -20;

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $title = 'Operasional Seluruh Cabang';

    protected static ?string $slug = 'operasional-cabang';

    protected string $view = 'filament.pages.branch-operations';

    public function getTitle(): string
    {
        return auth()->user()?->hasRole('admin') ? 'Operasional Cabang' : 'Operasional Cabang Saya';
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && $user->is_active && ($user->hasRole('admin') ||
            ($user->branch_id && $user->hasAnyRole(['admin_cabang', 'cs', 'teller'])));
    }

    protected function getViewData(): array
    {
        $user = auth()->user();
        $today = now()->startOfDay();
        $tomorrow = $today->copy()->addDay();
        $dailyQueues = fn (Builder $query) => $query->withoutGlobalScopes()
            ->where('created_at', '>=', $today)->where('created_at', '<', $tomorrow);

        $branches = Branch::query()
            ->when(! $user->hasRole('admin'), fn (Builder $query) => $query->whereKey($user->branch_id))
            ->with([
                'serviceSettings' => fn ($query) => $query->orderBy('service')->orderBy('id'),
                'users' => fn ($query) => $query->role(['cs', 'teller'])
                    ->with(['roles', 'serviceSetting'])->orderBy('name'),
            ])
            ->withCount([
                'queues as waiting_count' => fn (Builder $query) => $dailyQueues($query)->where('status', QueueStatus::WAITING),
                'queues as called_count' => fn (Builder $query) => $dailyQueues($query)->whereIn('status', [QueueStatus::CALLED, QueueStatus::SERVING]),
                'queues as completed_count' => fn (Builder $query) => $dailyQueues($query)->where('status', QueueStatus::COMPLETED),
            ])
            ->orderBy('code')->get();

        return ['branches' => $branches, 'isAdmin' => $user->hasRole('admin')];
    }
}
