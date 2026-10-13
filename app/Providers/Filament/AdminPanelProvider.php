<?php

namespace App\Providers\Filament;

use App\Filament\Pages\Dashboard;
use App\Filament\Widgets\QueueOperationsWidget;
use App\Filament\Widgets\QueueStatsWidget;
use App\Http\Middleware\EnsureUserIsActive;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\Width;
use Filament\Support\Facades\FilamentView;
use Filament\Support\Icons\Heroicon;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->maxContentWidth(Width::Full)
            ->brandName('Sistem Antrean Bank - PT BPR DP TASPEN')
            ->brandLogo(asset('images/logo.png?v=2'))
            ->darkModeBrandLogo(asset('images/logo-dark.png?v=2'))
            ->brandLogoHeight('2.8rem')
            ->login()
            ->darkMode(true)
            ->globalSearchKeybindings(['command+k', 'ctrl+k'])
            ->databaseNotifications(fn () => auth()->check() && auth()->user()?->hasRole('admin'))
            ->databaseNotificationsPolling(fn () => (auth()->check() && auth()->user()?->hasRole('admin')) ? '15s' : null)
            ->collapsibleNavigationGroups(false)
            ->navigationGroups([
                NavigationGroup::make()
                    ->label(fn () => __('app.nav_group_queue')),
                NavigationGroup::make()
                    ->label(fn () => __('app.nav_group_org')),
                NavigationGroup::make()
                    ->label(fn () => __('app.nav_group_user')),
            ])
            ->userMenuItems([
                MenuItem::make()
                    ->label('Buka Kiosk Cabang')
                    ->url(fn () => url('/ambil-nomor/'.(auth()->user()?->branch?->code ?? '001')), shouldOpenInNewTab: true)
                    ->icon(Heroicon::OutlinedTicket),
                MenuItem::make()
                    ->label('Buka Layar Cabang')
                    ->url(fn () => url('/display/'.(auth()->user()?->branch?->code ?? '001')), shouldOpenInNewTab: true)
                    ->icon(Heroicon::OutlinedTv),
                MenuItem::make()
                    ->label('Konsol Customer Service')
                    ->url('/dashboard/cs')
                    ->icon(Heroicon::OutlinedUserGroup),
                MenuItem::make()
                    ->label('Konsol Teller')
                    ->url('/dashboard/teller')
                    ->icon(Heroicon::OutlinedBanknotes),
            ])
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                QueueStatsWidget::class,
                QueueOperationsWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                EnsureUserIsActive::class,
            ]);
    }

    public function boot(): void
    {
        FilamentView::registerRenderHook(
            'panels::body.end',
            function (): string {
                $isAdmin = auth()->check() && auth()->user()?->hasRole('admin');
                $display = $isAdmin ? 'inline-flex' : 'none !important';

                return '<style>.fi-topbar-database-notifications-btn { display: '.$display.'; }</style>';
            }
        );
    }
}
