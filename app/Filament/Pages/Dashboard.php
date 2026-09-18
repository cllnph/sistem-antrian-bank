<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector;

class Dashboard extends BaseDashboard
{
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-home';
    protected static ?int $navigationSort = -10;

    public function mount(): RedirectResponse|Redirector|null
    {
        $user = auth()->user();

        if (! $user || ! $user->hasRole('admin')) {
            return redirect()->to('/admin/queues');
        }

        return null;
    }

    public static function getNavigationLabel(): string
    {
        return __('app.dashboard');
    }

    public function getTitle(): string|\Illuminate\Contracts\Support\Htmlable
    {
        return __('app.dashboard');
    }

    public static function shouldRegisterNavigation(): bool
    {
        $user = auth()->user();

        return (bool) ($user && $user->hasRole('admin'));
    }
}
