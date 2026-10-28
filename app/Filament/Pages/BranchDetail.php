<?php

namespace App\Filament\Pages;

use App\Filament\Resources\PrintAgentResource;
use App\Models\Branch;
use App\Services\BranchDashboard;
use App\Services\OperationStatus;
use App\Services\QueuePeriods;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Livewire\Attributes\Locked;

class BranchDetail extends Page
{
    protected static bool $shouldRegisterNavigation = false;

    protected static ?string $slug = 'cabang/{branch}';

    protected string $view = 'filament.pages.branch-detail';

    #[Locked]
    public int $branchId;

    public static function canAccess(): bool
    {
        return BranchOperations::canAccess();
    }

    public function mount(int|string $branch): void
    {
        $this->branchId = $branch;
        app(QueuePeriods::class)->ensure($this->getBranch());
    }

    public function getBranch(): Branch
    {
        return app(BranchDashboard::class)->branches(auth()->user())->findOrFail($this->branchId);
    }

    public function getTitle(): string
    {
        return 'Detail Cabang · '.$this->getBranch()->name;
    }

    protected function getHeaderActions(): array
    {
        return [Action::make('back')->label('Dashboard')->color('gray')->url(BranchOperations::getUrl()),
            Action::make('printing')->label('Cetak & Printer')->icon('heroicon-o-printer')
                ->url(fn () => PrintAgentResource::forBranchUrl($this->getBranch()))->visible(PrintAgentResource::canViewAny()),
            Action::make('kiosk')->label('Buka Kiosk')->url(fn () => url('/ambil-nomor/'.$this->getBranch()->code))->openUrlInNewTab()->visible(fn () => $this->getBranch()->is_active),
            Action::make('display')->label('Buka Layar')->color('gray')->url(fn () => url('/display/'.$this->getBranch()->code))->openUrlInNewTab()->visible(fn () => $this->getBranch()->is_active)];
    }

    protected function getViewData(): array
    {
        $branch = $this->getBranch()->load(['serviceSettings' => fn ($query) => $query->orderBy('service')->orderBy('id')]);

        return ['branch' => $branch, 'operations' => app(OperationStatus::class)->forBranch($branch)];
    }
}
