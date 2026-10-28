<?php

namespace App\Filament\Pages;

use App\Services\BranchDashboard;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;

class BranchOperations extends Page
{
    protected static \BackedEnum|string|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static ?int $navigationSort = -20;

    protected static ?string $navigationLabel = 'Dashboard';

    protected static ?string $title = 'Dashboard';

    protected static ?string $slug = 'operasional-cabang';

    protected string $view = 'filament.pages.branch-operations';

    public ?string $selectedBranchId = null;

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user && $user->is_active && ($user->hasRole('admin') ||
            ($user->branch_id && $user->hasAnyRole(['admin_cabang', 'cs', 'teller'])));
    }

    public function mount(): void
    {
        if (! auth()->user()->hasRole('admin')) {
            $this->selectedBranchId = (string) auth()->user()->branch_id;
        }
    }

    protected function getViewData(): array
    {
        $dashboard = app(BranchDashboard::class);

        return [...$dashboard->data(auth()->user(), $this->selectedBranchId),
            'branchOptions' => $dashboard->branches(auth()->user())->orderBy('code')->get(['id', 'code', 'name'])];
    }
}
