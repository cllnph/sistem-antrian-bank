<x-filament-panels::page>
    <div class="bank-page" wire:poll.10s>
        <x-filament::section
            :heading="$isAdmin ? 'Seluruh cabang, satu pusat kendali' : 'Layanan cabang Anda'"
            :description="$isAdmin ? 'Pantau antrean, loket, dan akun petugas setiap cabang.' : 'Pantau antrean dan status loket di kantor Anda.'"
        >
            <div class="bank-intro">
                <div class="bank-actions">
                    @if($isAdmin || auth()->user()?->hasRole('admin_cabang'))
                        <x-filament::button tag="a" :href="\App\Filament\Resources\UserResource::getUrl()" color="gray" :icon="\Filament\Support\Icons\Heroicon::OutlinedUsers">Kelola Akun</x-filament::button>
                        <x-filament::button tag="a" :href="\App\Filament\Resources\ServiceSettingResource::getUrl()" color="gray">Kelola Loket</x-filament::button>
                        @if($isAdmin)
                            <x-filament::button tag="a" :href="\App\Filament\Resources\BranchResource::getUrl()" color="gray">Kelola Cabang</x-filament::button>
                        @endif
                    @endif
                    <x-filament::button tag="a" :href="url('/admin/queues')" :icon="\Filament\Support\Icons\Heroicon::OutlinedQueueList">{{ $isAdmin ? 'Riwayat Antrean' : 'Konsol Petugas' }}</x-filament::button>
                </div>
            </div>
        </x-filament::section>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4" aria-label="Ringkasan operasional hari ini">
            {{ \Filament\Schemas\Schema::make($this)->components([\Filament\Widgets\StatsOverviewWidget\Stat::make('Cabang aktif', $branches->where('is_active', true)->count())->icon(\Filament\Support\Icons\Heroicon::OutlinedBuildingOffice2)])->columns(1) }}
            {{ \Filament\Schemas\Schema::make($this)->components([\Filament\Widgets\StatsOverviewWidget\Stat::make('Antrean menunggu', $branches->sum('waiting_count'))->icon(\Filament\Support\Icons\Heroicon::OutlinedClock)])->columns(1) }}
            {{ \Filament\Schemas\Schema::make($this)->components([\Filament\Widgets\StatsOverviewWidget\Stat::make('Sedang dilayani', $branches->sum('called_count'))->icon(\Filament\Support\Icons\Heroicon::OutlinedMegaphone)])->columns(1) }}
            {{ \Filament\Schemas\Schema::make($this)->components([\Filament\Widgets\StatsOverviewWidget\Stat::make('Selesai hari ini', $branches->sum('completed_count'))->icon(\Filament\Support\Icons\Heroicon::OutlinedCheckCircle)])->columns(1) }}
        </div>

        <div class="bank-branch-grid {{ $branches->count() === 1 ? 'is-single' : '' }}">
            @forelse($branches as $branch)
                <x-filament::section :heading="$branch->code.' - '.$branch->name" :description="$branch->type.' - '.$branch->operating_hours" :icon="\Filament\Support\Icons\Heroicon::OutlinedBuildingOffice2">
                    <x-slot name="afterHeader">
                        <x-filament::badge :color="$branch->is_active ? 'success' : 'gray'">{{ $branch->is_active ? 'Aktif' : 'Nonaktif' }}</x-filament::badge>
                    </x-slot>
                    <div class="space-y-6">

                        <div class="grid gap-3 sm:grid-cols-3">
                            {{ \Filament\Schemas\Schema::make($this)->components([\Filament\Widgets\StatsOverviewWidget\Stat::make('Menunggu', $branch->waiting_count)->color('warning')])->columns(1) }}
                            {{ \Filament\Schemas\Schema::make($this)->components([\Filament\Widgets\StatsOverviewWidget\Stat::make('Dilayani', $branch->called_count)->color('info')])->columns(1) }}
                            {{ \Filament\Schemas\Schema::make($this)->components([\Filament\Widgets\StatsOverviewWidget\Stat::make('Selesai hari ini', $branch->completed_count)->color('success')])->columns(1) }}
                        </div>
                        @if($branch->is_active)
                            <div class="bank-actions">
                                <x-filament::button tag="a" :href="url('/ambil-nomor/'.$branch->code)" target="_blank" rel="noopener" color="primary" :icon="\Filament\Support\Icons\Heroicon::OutlinedTicket">Buka Kiosk</x-filament::button>
                                <x-filament::button tag="a" :href="url('/display/'.$branch->code)" target="_blank" rel="noopener" color="gray" :icon="\Filament\Support\Icons\Heroicon::OutlinedTv">Buka Layar</x-filament::button>
                            </div>
                        @endif
                        <x-filament::section compact secondary heading="Loket layanan" :description="$branch->serviceSettings->count().' loket'">
                            @forelse($branch->serviceSettings as $setting)
                                <div class="bank-counter-row"><span>{{ $setting->counter_name }}</span><x-filament::badge :color="$setting->is_active ? 'success' : 'gray'">{{ $setting->is_active ? 'Buka' : 'Tutup' }}</x-filament::badge></div>
                            @empty
                                <p class="bank-empty">Belum ada loket layanan.</p>
                            @endforelse
                        </x-filament::section>
                        <x-filament::section compact secondary heading="Akun petugas" :description="$branch->users->count().' akun'">
                            <div class="bank-staff-list">
                                @forelse($branch->users as $staff)
                                    <x-filament::section compact :heading="$staff->hasRole('teller') ? 'Teller' : 'Customer Service'" :description="$staff->name">
                                        <x-slot name="afterHeader"><x-filament::badge :color="$staff->is_active ? 'success' : 'gray'">{{ $staff->is_active ? 'Aktif' : 'Nonaktif' }}</x-filament::badge></x-slot>
                                        <p class="text-sm break-all text-gray-500 dark:text-gray-400">{{ $staff->email }}</p>
                                        <p class="mt-2 text-sm">{{ $staff->serviceSetting?->counter_name ?? 'Belum ditugaskan ke loket' }}</p>
                                    </x-filament::section>
                                @empty
                                    <p class="bank-empty">Belum ada akun petugas.</p>
                                @endforelse
                            </div>
                        </x-filament::section>
                    </div>
                </x-filament::section>
            @empty
                <div class="bank-empty">Belum ada cabang. Tambahkan kantor melalui Kelola Cabang.</div>
            @endforelse
        </div>
        <p class="bank-caption">Antrean hari ini &middot; Diperbarui otomatis setiap 10 detik</p>
    </div>
</x-filament-panels::page>
