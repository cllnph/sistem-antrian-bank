<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use App\Filament\Pages\Dashboard;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use App\Filament\Widgets\QueueOperationsWidget;
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
                \Filament\Navigation\MenuItem::make()
                    ->label('Mesin Kiosk Tiket (Ambil Nomor) ↗')
                    ->url('/ambil-nomor', shouldOpenInNewTab: true)
                    ->icon('heroicon-o-ticket'),
                \Filament\Navigation\MenuItem::make()
                    ->label('Layar Display Antrean (Public TV) ↗')
                    ->url('/display', shouldOpenInNewTab: true)
                    ->icon('heroicon-o-tv'),
                \Filament\Navigation\MenuItem::make()
                    ->label('Konsol Petugas CS 🖥️')
                    ->url('/dashboard/cs')
                    ->icon('heroicon-o-user-group'),
                \Filament\Navigation\MenuItem::make()
                    ->label('Konsol Petugas Teller 🖥️')
                    ->url('/dashboard/teller')
                    ->icon('heroicon-o-banknotes'),
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
                \App\Filament\Widgets\QueueStatsWidget::class,
                \App\Filament\Widgets\QueueOperationsWidget::class,
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
                \App\Http\Middleware\EnsureUserIsActive::class,
            ]);
    }

    public function boot(): void
    {
        \Filament\Support\Facades\FilamentView::registerRenderHook(
            'panels::body.end',
            function (): string {
                $isAdmin = auth()->check() && auth()->user()?->hasRole('admin');
                $display = $isAdmin ? 'inline-flex' : 'none !important';

                return <<<HTML
                <style>
                    /* Hide NProgress and top loading lines completely */
                    #nprogress,
                    .nprogress {
                        display: none !important;
                    }

                    /* Prevent flash of unstyled content */
                    [x-cloak]:not(.fi-modal *),
                    .fi-modal[data-modal-open="false"] [x-cloak] {
                        display: none !important;
                    }

                    /* Brand Logo dark mode robustness & sizing matching SIPENA */
                    .fi-logo {
                        max-height: 2.8rem;
                        max-width: 100%;
                        object-fit: contain;
                    }
                    html.dark .fi-logo.dark\:hidden {
                        display: none !important;
                    }
                    html.dark .fi-logo.dark\:inline-flex {
                        display: inline-flex !important;
                    }
                    html:not(.dark) .fi-logo.dark\:inline-flex {
                        display: none !important;
                    }
                    html:not(.dark) .fi-logo.dark\:hidden {
                        display: inline-flex !important;
                    }

                    /* Table dark mode hover safety: ensure rows never flash bright white */
                    html.dark tr.hover\:bg-gray-50:hover,
                    html.dark tr:hover,
                    html.dark .fi-ta-row:hover,
                    html.dark table tbody tr:hover {
                        background-color: rgba(255, 255, 255, 0.05) !important;
                        color: inherit !important;
                    }
                    html.dark table tbody tr:hover td {
                        background-color: transparent !important;
                        color: inherit !important;
                    }

                    /* Topbar notifications display rule */
                    .fi-topbar button[x-data*="databaseNotifications"],
                    .fi-topbar .fi-icon-btn[aria-label*="Notification"],
                    .fi-topbar .fi-icon-btn[aria-label*="notifikasi"],
                    .fi-topbar-database-notifications-btn,
                    .fi-topbar-database-notifications-btn-trigger,
                    [x-data*="databaseNotifications"] {
                        display: {$display};
                    }
                </style>
HTML;
            }
        );
    }
}
