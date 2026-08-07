<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use App\Filament\Pages\Dashboard;
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
            ->brandLogo(fn () => view('components.bank-logo', ['size' => '48px']))
            ->brandLogoHeight('4.2rem')
            ->login()
            ->darkMode(true)
            ->globalSearchKeybindings(['command+k', 'ctrl+k'])
            ->databaseNotifications()
            ->databaseNotificationsPolling('15s')
            ->sidebarCollapsibleOnDesktop()
            ->userMenuItems([
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
                'primary' => [
                    50  => '#eff6ff',
                    100 => '#dbeafe',
                    200 => '#bfdbfe',
                    300 => '#93c5fd',
                    400 => '#60a5fa',
                    500 => '#2563eb',
                    600 => '#1d4ed8',
                    700 => '#1e40af',
                    800 => '#1e3a8a',
                    900 => '#0f2b5c', // PT BPR DP TASPEN Corporate Navy
                    950 => '#0a1936',
                ],
                'amber' => Color::Amber,
                'gold' => Color::Yellow,
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
            ]);
    }

    public function boot(): void
    {
        \Filament\Support\Facades\FilamentView::registerRenderHook(
            'panels::head.done',
            fn (): string => \Illuminate\Support\Facades\Blade::render('
                <link rel="preconnect" href="https://fonts.googleapis.com">
                <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
                <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;700;800;900&family=Plus+Jakarta+Sans:ital,wght@0,400;0,500;0,600;0,700;0,800;1,400&display=swap" rel="stylesheet">
                <style>
                    /* PT BPR DP TASPEN Corporate Typography & Enterprise Theme */
                    body, html, .fi-body, .fi-main, .fi-sidebar, .fi-modal, input, select, textarea, button, table {
                        font-family: "Plus Jakarta Sans", system-ui, -apple-system, sans-serif !important;
                        -webkit-font-smoothing: antialiased !important;
                        -moz-osx-font-smoothing: grayscale !important;
                    }
                    h1, h2, h3, .fi-header-heading, .fi-modal-heading {
                        font-family: "Plus Jakarta Sans", sans-serif !important;
                        font-weight: 800 !important;
                        letter-spacing: -0.02em !important;
                    }
                    .fi-logo {
                        background: #ffffff !important;
                        padding: 10px 22px !important;
                        border-radius: 14px !important;
                        border: 1px solid #cbd5e1 !important;
                        box-shadow: 0 4px 12px rgba(15, 43, 92, 0.08) !important;
                        display: inline-flex !important;
                        align-items: center !important;
                    }
                    .fi-sidebar-header {
                        border-bottom: 2px solid #eab308 !important; /* Gold accent line */
                        padding-top: 14px !important;
                        padding-bottom: 14px !important;
                    }
                    .fi-sidebar-group-label {
                        text-transform: uppercase !important;
                        font-weight: 800 !important;
                        color: #64748b !important;
                        font-size: 0.75rem !important;
                        letter-spacing: 0.8px !important;
                    }
                    .fi-sidebar-item-active > a, .fi-sidebar-item-active > button {
                        background-color: #0f2b5c !important;
                        color: #ffffff !important;
                        border-left: 4px solid #eab308 !important; /* Gold accent indicator */
                        border-radius: 8px !important;
                        font-weight: 700 !important;
                    }
                    .fi-sidebar-item-active svg {
                        color: #eab308 !important; /* Gold active icon */
                    }
                    .fi-sidebar-item-button {
                        border-radius: 8px !important;
                        font-weight: 600 !important;
                    }
                    .fi-btn-primary {
                        background-color: #0f2b5c !important;
                        color: #ffffff !important;
                        border-radius: 8px !important;
                        font-weight: 700 !important;
                    }
                    .fi-btn-primary:hover {
                        background-color: #1e3a8a !important;
                    }
                    .fi-input, input[type="text"], input[type="email"], input[type="password"], select {
                        border-radius: 8px !important;
                        font-family: "Plus Jakarta Sans", sans-serif !important;
                    }
                    .fi-ta-header-cell-label {
                        font-weight: 700 !important;
                        text-transform: uppercase !important;
                        font-size: 0.725rem !important;
                        letter-spacing: 0.5px !important;
                    }
                </style>
            ')
        );
    }
}
