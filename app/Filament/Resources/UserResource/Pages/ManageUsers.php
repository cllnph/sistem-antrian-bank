<?php

namespace App\Filament\Resources\UserResource\Pages;

use App\Filament\Resources\UserResource;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ManageRecords;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class ManageUsers extends ManageRecords
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('manage_roles_slideover')
                ->visible(fn () => auth()->user()?->hasRole('admin') ?? false)
                ->label('Kelola Peran & Hak Akses')
                ->icon(Heroicon::OutlinedShieldCheck)
                ->color('gray')
                ->slideOver()
                ->modalHeading('Manajemen Peran & Hak Akses (Permissions)')
                ->modalDescription('Atur hak akses perbankan per peran / aktor secara terkelompok.')
                ->mountUsing(function ($form) {
                    $form->fill([
                        'selected_role_id' => null,
                        'select_all_perms' => false,
                        'perm_system' => [],
                        'perm_user' => [],
                        'perm_queue' => [],
                        'perm_counter' => [],
                        'perm_report' => [],
                    ]);
                })
                ->form([
                    Select::make('selected_role_id')
                        ->label('Pilih Peran / Aktor')
                        ->placeholder('Cari atau pilih peran...')
                        ->searchable()
                        ->preload()
                        ->options(fn () => Role::all()->pluck('name', 'id')->map(fn ($name) => match ($name) {
                            'cs' => __('app.role_cs'),
                            'teller' => __('app.role_teller'),
                            'admin' => __('app.role_admin'),
                            'supervisor' => __('app.role_supervisor'),
                            default => ucfirst(str_replace('_', ' ', $name)),
                        }))
                        ->reactive()
                        ->afterStateUpdated(function ($state, $set) {
                            if ($state) {
                                $role = Role::find($state);
                                $permNames = $role?->permissions->pluck('name')->toArray() ?? [];
                                $set('perm_system', array_values(array_intersect($permNames, ['access_admin_panel', 'access_teller_console', 'access_cs_console'])));
                                $set('perm_user', array_values(array_intersect($permNames, ['view_any_user', 'create_user', 'edit_user', 'delete_user'])));
                                $set('perm_queue', array_values(array_intersect($permNames, ['view_any_queue', 'create_queue', 'edit_queue', 'delete_queue', 'reset_queues'])));
                                $set('perm_counter', array_values(array_intersect($permNames, ['view_any_service_setting', 'edit_service_setting'])));
                                $set('perm_report', array_values(array_intersect($permNames, ['view_control_center'])));
                                $set('select_all_perms', count($permNames) >= 15);
                            } else {
                                $set('perm_system', []);
                                $set('perm_user', []);
                                $set('perm_queue', []);
                                $set('perm_counter', []);
                                $set('perm_report', []);
                                $set('select_all_perms', false);
                            }
                        })
                        ->required(),

                    Toggle::make('select_all_perms')
                        ->label('Pilih Semua Akses (Administrator Full Access)')
                        ->helperText('Aktifkan untuk memilih seluruh wewenang perbankan sekaligus.')
                        ->reactive()
                        ->afterStateUpdated(function ($state, $set) {
                            if ($state) {
                                $set('perm_system', ['access_admin_panel', 'access_teller_console', 'access_cs_console']);
                                $set('perm_user', ['view_any_user', 'create_user', 'edit_user', 'delete_user']);
                                $set('perm_queue', ['view_any_queue', 'create_queue', 'edit_queue', 'delete_queue', 'reset_queues']);
                                $set('perm_counter', ['view_any_service_setting', 'edit_service_setting']);
                                $set('perm_report', ['view_control_center']);
                            } else {
                                $set('perm_system', []);
                                $set('perm_user', []);
                                $set('perm_queue', []);
                                $set('perm_counter', []);
                                $set('perm_report', []);
                            }
                        }),

                    Section::make('1. Panel & Akses Sistem')
                        ->description('Kontrol hak masuk ke panel admin dan konsol operasional petugas.')
                        ->schema([
                            CheckboxList::make('perm_system')
                                ->hiddenLabel()
                                ->default([])
                                ->options([
                                    'access_admin_panel' => 'Akses Panel Admin',
                                    'access_teller_console' => 'Akses Konsol Teller',
                                    'access_cs_console' => 'Akses Konsol Layanan Pelanggan',
                                ])
                                ->columns(2),
                        ]),

                    Section::make('2. Manajemen Pengguna')
                        ->description('Hak pengelolaan data dan pembuatan akun pengguna.')
                        ->schema([
                            CheckboxList::make('perm_user')
                                ->hiddenLabel()
                                ->default([])
                                ->options([
                                    'view_any_user' => 'Lihat Data Pengguna',
                                    'create_user' => 'Buat Pengguna Baru',
                                    'edit_user' => 'Edit Pengguna',
                                    'delete_user' => 'Hapus Pengguna',
                                ])
                                ->columns(2),
                        ]),

                    Section::make('3. Manajemen Antrean')
                        ->description('Hak pemanggilan, pembuatan, dan pengeditan tiket antrean.')
                        ->schema([
                            CheckboxList::make('perm_queue')
                                ->hiddenLabel()
                                ->default([])
                                ->options([
                                    'view_any_queue' => 'Lihat Data Antrean',
                                    'create_queue' => 'Buat Tiket Antrean',
                                    'edit_queue' => 'Edit / Panggil Antrean',
                                    'delete_queue' => 'Hapus Antrean',
                                    'reset_queues' => 'Reset Status Antrean',
                                ])
                                ->columns(2),
                        ]),

                    Section::make('4. Pengaturan Loket')
                        ->description('Pengaturan status buka/tutup dan nama loket perbankan.')
                        ->schema([
                            CheckboxList::make('perm_counter')
                                ->hiddenLabel()
                                ->default([])
                                ->options([
                                    'view_any_service_setting' => 'Lihat Pengaturan Loket',
                                    'edit_service_setting' => 'Edit Loket Layanan',
                                ])
                                ->columns(2),
                        ]),

                    Section::make('5. Kontrol & Laporan Operasional')
                        ->description('Akses ke dasbor pusat kendali dan laporan aktivitas.')
                        ->schema([
                            CheckboxList::make('perm_report')
                                ->hiddenLabel()
                                ->default([])
                                ->options([
                                    'view_control_center' => 'Lihat Pusat Kendali',
                                ])
                                ->columns(2),
                        ]),
                ])
                ->action(function (array $data) {
                    abort_unless(auth()->user()?->hasRole('admin'), 403);
                    if (! empty($data['selected_role_id'])) {
                        $role = Role::find($data['selected_role_id']);
                        if ($role) {
                            $allPermNames = array_values(array_filter(array_merge(
                                (array) ($data['perm_system'] ?? []),
                                (array) ($data['perm_user'] ?? []),
                                (array) ($data['perm_queue'] ?? []),
                                (array) ($data['perm_counter'] ?? []),
                                (array) ($data['perm_report'] ?? [])
                            )));

                            if (empty($allPermNames)) {
                                Notification::make()
                                    ->title('Peringatan: Minimal 1 Permission Wajib Dipilih')
                                    ->body('Role tidak dapat disimpan tanpa izin sama sekali.')
                                    ->warning()
                                    ->send();

                                return;
                            }

                            $permModels = [];
                            foreach ($allPermNames as $permName) {
                                $permModels[] = Permission::findOrCreate($permName);
                            }

                            $role->syncPermissions($permModels);

                            $recipients = User::where('is_active', true)->get();
                            Notification::make()
                                ->title('Hak Akses Role Berhasil Diperbarui')
                                ->body('Hak akses untuk role '.ucfirst(str_replace('_', ' ', $role->name)).'telah disimpan ke database oleh '.auth()->user()?->name)
                                ->success()
                                ->send()
                                ->sendToDatabase($recipients);
                        }
                    }
                }),
            CreateAction::make()->label(fn () => __('app.create_user')),
        ];
    }
}
