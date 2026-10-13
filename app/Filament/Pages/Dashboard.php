<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class Dashboard extends BaseDashboard
{
    protected static \BackedEnum|string|null $navigationIcon = Heroicon::OutlinedHome;

    protected static ?int $navigationSort = -10;

    public function mount(): RedirectResponse|Redirector|null
    {
        $user = auth()->user();

        if (! $user || ! $user->hasAnyRole(['admin', 'admin_cabang'])) {
            return redirect()->to('/admin/queues');
        }

        return redirect()->to(BranchOperations::getUrl());
    }

    public static function getNavigationLabel(): string
    {
        return __('app.dashboard');
    }

    public function getTitle(): string|Htmlable
    {
        return __('app.dashboard');
    }

    public static function shouldRegisterNavigation(): bool
    {
        return false;
    }
}
