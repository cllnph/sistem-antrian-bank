<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static \BackedEnum|string|null $navigationIcon = 'heroicon-o-home';
    protected static ?int $navigationSort = -10;

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
        return true;
    }
}
