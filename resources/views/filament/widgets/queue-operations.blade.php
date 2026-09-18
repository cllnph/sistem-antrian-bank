<x-filament-widgets::widget>
    <div class="space-y-6">
        <!-- Hero Operational Banner Section -->
        <x-filament::section icon="heroicon-o-building-office-2">
            <x-slot name="heading">
                PT BPR DP TASPEN — Dashboard {{ auth()->user()?->hasRole('admin') ? 'Administrator' : (auth()->user()?->hasRole('cs') ? 'Customer Service' : 'Teller') }}
            </x-slot>

            <x-slot name="description">
                Selamat datang kembali, {{ auth()->user()?->name }}. Silakan pilih menu operasional di bawah ini.
            </x-slot>

            <div class="flex flex-wrap items-center gap-3 pt-2">
                <x-filament::button
                    tag="a"
                    color="warning"
                    href="/display"
                    target="_blank"
                    icon="heroicon-m-tv"
                >
                    Layar Monitor Display ↗
                </x-filament::button>

                <x-filament::button
                    tag="a"
                    color="primary"
                    href="/ambil-nomor"
                    target="_blank"
                    icon="heroicon-m-ticket"
                >
                    Kiosk Cetak Tiket ↗
                </x-filament::button>
            </div>
        </x-filament::section>

        <!-- Quick Access Operational Actions Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @if (auth()->user()?->can('access_teller_console') || auth()->user()?->can('view_any_queue'))
                <x-filament::section icon="heroicon-o-computer-desktop" class="h-full flex flex-col justify-between">
                    <x-slot name="heading">Dashboard Teller Kas & Pensiun TASPEN</x-slot>
                    <x-slot name="description">
                        Pencairan Gaji Pensiun TASPEN, Setor / Tarik Tunai Tabungan BPR, Bayar Angsuran Kredit, & Pemindahbukuan.
                    </x-slot>

                    <div class="pt-4 mt-auto">
                        <x-filament::button
                            tag="a"
                            color="info"
                            href="/dashboard/teller"
                            icon="heroicon-m-arrow-right"
                            class="w-full"
                        >
                            Buka Console Teller
                        </x-filament::button>
                    </div>
                </x-filament::section>
            @endif

            @if (auth()->user()?->can('access_cs_console') || auth()->user()?->can('view_any_queue'))
                <x-filament::section icon="heroicon-o-user-group" class="h-full flex flex-col justify-between">
                    <x-slot name="heading">Dashboard CS Kredit Pensiun & Administrasi</x-slot>
                    <x-slot name="description">
                        Pengajuan Kredit Pensiun TASPEN & Kredit Usaha/Pegawai, Pembukaan Tabungan BPR & Deposito, Otentikasi Taspen.
                    </x-slot>

                    <div class="pt-4 mt-auto">
                        <x-filament::button
                            tag="a"
                            color="warning"
                            href="/dashboard/cs"
                            icon="heroicon-m-arrow-right"
                            class="w-full"
                        >
                            Buka Console CS
                        </x-filament::button>
                    </div>
                </x-filament::section>
            @endif

            @if (auth()->user()?->can('view_control_center'))
                <x-filament::section icon="heroicon-o-chart-bar" class="h-full flex flex-col justify-between">
                    <x-slot name="heading">Kontrol & Laporan</x-slot>
                    <x-slot name="description">
                        Rekapitulasi aktivitas petugas, resetting status antrean, & laporan statistik.
                    </x-slot>

                    <div class="pt-4 mt-auto">
                        <x-filament::button
                            tag="a"
                            color="success"
                            href="{{ \App\Filament\Pages\AdminControlCenter::getUrl() }}"
                            icon="heroicon-m-arrow-right"
                            class="w-full"
                        >
                            Buka Kontrol & Laporan
                        </x-filament::button>
                    </div>
                </x-filament::section>
            @endif

            @if (auth()->user()?->can('view_any_user'))
                <x-filament::section icon="heroicon-o-users" class="h-full flex flex-col justify-between">
                    <x-slot name="heading">Manajemen User</x-slot>
                    <x-slot name="description">
                        Kelola akun administrator, petugas CS, Teller, dan role Spatie perbankan.
                    </x-slot>

                    <div class="pt-4 mt-auto">
                        <x-filament::button
                            tag="a"
                            color="gray"
                            href="{{ \App\Filament\Resources\UserResource::getUrl() }}"
                            icon="heroicon-m-arrow-right"
                            class="w-full"
                        >
                            Kelola User & Role
                        </x-filament::button>
                    </div>
                </x-filament::section>
            @endif

            @if (auth()->user()?->can('view_any_service_setting'))
                <x-filament::section icon="heroicon-o-cog-6-tooth" class="h-full flex flex-col justify-between">
                    <x-slot name="heading">Pengaturan Loket</x-slot>
                    <x-slot name="description">
                        Buka / tutup operasional loket CS dan Teller serta atur nama loket perbankan.
                    </x-slot>

                    <div class="pt-4 mt-auto">
                        <x-filament::button
                            tag="a"
                            color="gray"
                            href="{{ \App\Filament\Resources\ServiceSettingResource::getUrl() }}"
                            icon="heroicon-m-arrow-right"
                            class="w-full"
                        >
                            Kelola Pengaturan Loket
                        </x-filament::button>
                    </div>
                </x-filament::section>
            @endif
        </div>
    </div>
</x-filament-widgets::widget>
