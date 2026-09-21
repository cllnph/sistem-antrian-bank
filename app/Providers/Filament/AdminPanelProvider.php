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
            ->brandLogo(fn () => view('components.bank-logo', [
                'height' => '40px',
                'padding' => '4px 10px',
                'showSlogan' => !request()->is('admin/login*') && !request()->is('login*') && !request()->routeIs('filament.admin.auth.login'),
            ]))
            ->brandLogoHeight('48px')
            ->login()
            ->darkMode(true)
            ->globalSearchKeybindings(['command+k', 'ctrl+k'])
            ->databaseNotifications(fn () => auth()->check() && auth()->user()?->hasRole('admin'))
            ->databaseNotificationsPolling(fn () => (auth()->check() && auth()->user()?->hasRole('admin')) ? '15s' : null)
            ->sidebarCollapsibleOnDesktop()
            ->spa()
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
                \App\Http\Middleware\EnsureUserIsActive::class,
            ]);
    }

    public function boot(): void
    {
        \Filament\Support\Facades\FilamentView::registerRenderHook(
            'panels::head.done',
            fn (): string => \Illuminate\Support\Facades\Blade::render(<<<'HTML'
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
                        background: transparent !important;
                        border: none !important;
                        box-shadow: none !important;
                        display: inline-flex !important;
                        align-items: center !important;
                        text-decoration: none !important;
                        padding: 0 !important;
                    }
                    .fi-topbar .fi-logo {
                        padding: 0 !important;
                    }
                    .fi-topbar .bank-dp-taspen-exact-logo {
                        height: 40px !important;
                        max-height: 40px !important;
                    }
                    .fi-simple-layout .fi-logo {
                        padding: 16px 36px !important;
                        border-radius: 16px !important;
                    }
                    .fi-simple-layout .fi-logo img, 
                    .fi-simple-layout .bank-dp-taspen-exact-logo {
                        height: 76px !important;
                        max-height: 96px !important;
                    }
                    /* Sembunyikan slogan badge pada halaman login (simple layout) */
                    .fi-simple-layout .brand-slogan-badge {
                        display: none !important;
                    }
                    .fi-sidebar {
                        position: relative !important;
                    }
                    .fi-sidebar-header {
                        display: flex !important;
                        align-items: center !important;
                        justify-content: center !important;
                        padding: 10px 14px !important;
                        border-bottom: 2px solid #eab308 !important; /* Gold accent line */
                        position: relative !important;
                    }
                    .fi-sidebar-header .fi-logo {
                        background: transparent !important;
                        padding: 0 !important;
                        border: none !important;
                        box-shadow: none !important;
                        display: flex !important;
                        align-items: center !important;
                        justify-content: center !important;
                        width: 100% !important;
                        max-width: 100% !important;
                        margin: 0 auto !important;
                    }
                    .fi-sidebar-header .bank-dp-taspen-exact-logo,
                    .fi-sidebar-header .fi-logo img {
                        height: 40px !important;
                        max-height: 44px !important;
                        width: auto !important;
                        max-width: 100% !important;
                        object-fit: contain !important;
                        display: block !important;
                        margin: 0 auto !important;
                    }
                    /* When sidebar is collapsed, hide slogan badge so logo fits compactly */
                    .fi-sidebar-collapsed .brand-slogan-badge {
                        display: none !important;
                    }
                    .fi-sidebar-collapsed .bank-dp-taspen-exact-logo {
                        height: 28px !important;
                    }
                    /* Hide any leftover chevron / button / links inside header so only logo remains */
                    .fi-sidebar-header button,
                    .fi-sidebar-header a:not(.fi-logo),
                    .fi-sidebar-header svg:not(.fi-logo svg) {
                        display: none !important;
                    }

                    /* Floating Edge Collapse Toggle Button (Disamping Sidebar) */
                    #sidebar-edge-toggle {
                        position: absolute !important;
                        right: -13px !important;
                        top: 22px !important;
                        z-index: 50 !important;
                        width: 26px !important;
                        height: 26px !important;
                        border-radius: 50% !important;
                        background: #ffffff !important;
                        border: 1.5px solid #cbd5e1 !important;
                        color: #0f2b5c !important;
                        box-shadow: 0 2px 8px rgba(15, 43, 92, 0.15) !important;
                        display: flex !important;
                        align-items: center !important;
                        justify-content: center !important;
                        cursor: pointer !important;
                        outline: none !important;
                        transition: transform 0.2s ease, background 0.2s ease, border-color 0.2s ease !important;
                    }
                    #sidebar-edge-toggle:hover {
                        background: #0f2b5c !important;
                        color: #eab308 !important;
                        border-color: #eab308 !important;
                        transform: scale(1.15) !important;
                    }

                    /* Drag Resizer Bar Handle (Geser Kanan Kiri) */
                    #sidebar-resizer {
                        position: absolute !important;
                        top: 0 !important;
                        right: -4px !important;
                        width: 8px !important;
                        height: 100% !important;
                        cursor: col-resize !important;
                        z-index: 45 !important;
                        background: transparent !important;
                        transition: background 0.2s ease !important;
                    }
                    #sidebar-resizer:hover, #sidebar-resizer.is-dragging {
                        background: rgba(37, 99, 235, 0.4) !important;
                    }
                </style>
                <script>
                (function() {
                    function initSidebarAdjuster() {
                        const sidebar = document.querySelector('.fi-sidebar');
                        if (!sidebar) return;

                        // 1. Edge Collapse Toggle Button
                        let toggleBtn = document.getElementById('sidebar-edge-toggle');
                        if (!toggleBtn) {
                            toggleBtn = document.createElement('button');
                            toggleBtn.id = 'sidebar-edge-toggle';
                            toggleBtn.type = 'button';
                            toggleBtn.title = 'Buka / Tutup Sidebar';
                            toggleBtn.innerHTML = `
                                <svg id="edge-toggle-icon" class="w-3.5 h-3.5 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7" />
                                </svg>
                            `;
                            sidebar.appendChild(toggleBtn);
                        }

                        // 2. Drag Resizer Handle
                        let resizer = document.getElementById('sidebar-resizer');
                        if (!resizer) {
                            resizer = document.createElement('div');
                            resizer.id = 'sidebar-resizer';
                            resizer.title = 'Tarik kanan / kiri untuk mengatur lebar sidebar';
                            sidebar.appendChild(resizer);
                        }

                        // Restore saved custom width
                        const savedWidth = localStorage.getItem('filament_sidebar_custom_width');
                        if (savedWidth && !sidebar.classList.contains('fi-sidebar-collapsed')) {
                            sidebar.style.setProperty('width', savedWidth + 'px', 'important');
                        }

                        // Toggle Collapse
                        toggleBtn.onclick = function(e) {
                            e.preventDefault();
                            e.stopPropagation();

                            sidebar.classList.toggle('fi-sidebar-collapsed');
                            const isCollapsed = sidebar.classList.contains('fi-sidebar-collapsed');
                            
                            const icon = document.getElementById('edge-toggle-icon');
                            if (icon) {
                                icon.style.transform = isCollapsed ? 'rotate(180deg)' : 'rotate(0deg)';
                            }

                            if (isCollapsed) {
                                sidebar.style.removeProperty('width');
                            } else {
                                const width = localStorage.getItem('filament_sidebar_custom_width') || 280;
                                sidebar.style.setProperty('width', width + 'px', 'important');
                            }
                        };

                        // Resizer Drag Action (Geser Kanan Kiri)
                        let isResizing = false;
                        resizer.onmousedown = function(e) {
                            e.preventDefault();
                            isResizing = true;
                            resizer.classList.add('is-dragging');
                            document.body.style.cursor = 'col-resize';
                            document.body.style.userSelect = 'none';
                        };

                        document.onmousemove = function(e) {
                            if (!isResizing) return;
                            const newWidth = Math.max(200, Math.min(480, e.clientX));
                            sidebar.style.setProperty('width', newWidth + 'px', 'important');
                            localStorage.setItem('filament_sidebar_custom_width', newWidth);
                        };

                        document.onmouseup = function() {
                            if (isResizing) {
                                isResizing = false;
                                resizer.classList.remove('is-dragging');
                                document.body.style.cursor = '';
                                document.body.style.userSelect = '';
                            }
                        };
                    }

                    if (document.readyState === 'loading') {
                        document.addEventListener('DOMContentLoaded', initSidebarAdjuster);
                    } else {
                        initSidebarAdjuster();
                    }
                    setTimeout(initSidebarAdjuster, 600);
                })();
                </script>
                    .fi-sidebar-group-label {
                        text-transform: uppercase !important;
                        font-weight: 800 !important;
                        color: #64748b !important;
                        font-size: 0.725rem !important;
                        letter-spacing: 0.8px !important;
                        padding-left: 12px !important;
                    }
                    /* Clean Soft Active Item (Matching Reference Screenshot) */
                    .fi-sidebar-item-active > a, .fi-sidebar-item-active > button {
                        background-color: #eff6ff !important; /* Soft light brand tint */
                        color: #0f2b5c !important;
                        border-left: 3.5px solid #0f2b5c !important;
                        border-radius: 10px !important;
                        font-weight: 700 !important;
                        box-shadow: 0 1px 3px rgba(15, 43, 92, 0.05) !important;
                    }
                    .fi-sidebar-item-active svg {
                        color: #2563eb !important;
                    }
                    .fi-sidebar-item-button {
                        border-radius: 10px !important;
                        font-weight: 600 !important;
                        color: #475569 !important;
                        transition: all 0.15s ease !important;
                    }
                    .fi-sidebar-item-button:hover {
                        background-color: #f1f5f9 !important;
                        color: #0f2b5c !important;
                    }

                    /* Clean Off-white Page Background & White Elevated Cards */
                    .fi-main, body, html, .fi-body {
                        background-color: #f8fafc !important;
                    }
                    .fi-section, .fi-fo-field-wrp, .fi-ta-content, .fi-modal-window {
                        border-radius: 14px !important;
                        border: 1px solid #e2e8f0 !important;
                        box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.04) !important;
                    }
                    .fi-section-header {
                        border-bottom: 1px solid #f1f5f9 !important;
                        padding-bottom: 12px !important;
                    }

                    /* Buttons & Input Controls */
                    .fi-btn-primary {
                        background-color: #0f2b5c !important;
                        color: #ffffff !important;
                        border-radius: 10px !important;
                        font-weight: 700 !important;
                        box-shadow: 0 2px 6px rgba(15, 43, 92, 0.2) !important;
                        transition: all 0.2s ease !important;
                    }
                    .fi-btn-primary:hover {
                        background-color: #1e3a8a !important;
                        transform: translateY(-1px) !important;
                    }
                    .fi-input, input[type="text"], input[type="email"], input[type="password"], select, textarea {
                        border-radius: 10px !important;
                        border: 1px solid #cbd5e1 !important;
                        background-color: #ffffff !important;
                        font-family: "Plus Jakarta Sans", sans-serif !important;
                        transition: all 0.2s ease !important;
                    }
                    .fi-input:focus, input:focus, select:focus, textarea:focus {
                        border-color: #2563eb !important;
                        box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.12) !important;
                    }

                    /* Tables & Header Typography */
                    .fi-header-heading {
                        color: #0f172a !important;
                        font-weight: 800 !important;
                        font-size: 1.625rem !important;
                    }
                    .fi-ta-header-cell-label {
                        font-weight: 700 !important;
                        text-transform: uppercase !important;
                        font-size: 0.725rem !important;
                        letter-spacing: 0.5px !important;
                    }
                    /* Hide notification bell for non-admin staff (CS & Teller) */
                    .fi-topbar button[x-data*="databaseNotifications"],
                    .fi-topbar .fi-icon-btn[aria-label*="Notification"],
                    .fi-topbar .fi-icon-btn[aria-label*="notifikasi"],
                    .fi-topbar-database-notifications-btn,
                    .fi-topbar-database-notifications-btn-trigger,
                    [x-data*="databaseNotifications"] {
                        display: <?php echo (auth()->check() && auth()->user()?->hasRole('admin')) ? 'inline-flex' : 'none !important'; ?>;
                    }
                </style>
HTML
            )
        );
    }
}
