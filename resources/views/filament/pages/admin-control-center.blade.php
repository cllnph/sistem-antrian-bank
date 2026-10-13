<x-filament-panels::page>
    <div wire:poll.10s class="space-y-6">
        @if(auth()->user()?->hasRole('admin'))
            <x-filament::section heading="Pusat Kendali Operasional Cabang" description="Pantau antrean hari ini pada seluruh cabang atau pilih satu kantor.">
                {{ $this->branchFilter }}
            </x-filament::section>
        @endif
        <div class="grid gap-6 xl:grid-cols-2">
            @foreach($summary as $service => $item)
                <x-filament::section :heading="$item['label']">
                    <div class="grid gap-4 sm:grid-cols-2">
                        {{ \Filament\Schemas\Schema::make($this)->components([\Filament\Widgets\StatsOverviewWidget\Stat::make(__('app.status_waiting'), $item['waiting'])->icon(\Filament\Support\Icons\Heroicon::OutlinedClock)])->columns(1) }}
                        {{ \Filament\Schemas\Schema::make($this)->components([\Filament\Widgets\StatsOverviewWidget\Stat::make(__('app.status_called'), $item['called'])->icon(\Filament\Support\Icons\Heroicon::OutlinedMegaphone)])->columns(1) }}
                        {{ \Filament\Schemas\Schema::make($this)->components([\Filament\Widgets\StatsOverviewWidget\Stat::make(__('app.status_completed'), $item['completed'])->icon(\Filament\Support\Icons\Heroicon::OutlinedCheckCircle)])->columns(1) }}
                        {{ \Filament\Schemas\Schema::make($this)->components([\Filament\Widgets\StatsOverviewWidget\Stat::make(__('app.status_skipped'), $item['skipped'])->icon(\Filament\Support\Icons\Heroicon::OutlinedForward)])->columns(1) }}
                    </div>
                </x-filament::section>
            @endforeach
        </div>
        @if(auth()->user()?->hasRole('admin'))
            {{ $this->table }}
        @endif
    </div>

    <!-- Staff & Activity Logs Grid -->
    <div class="grid gap-6 lg:grid-cols-2 mt-2">
        <!-- Staff List Section -->
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center justify-between">
                    <span class="font-bold">{{ __('app.active_staff') }}</span>
                    <span class="text-xs text-gray-500 font-normal">Total: {{ $staff->count() }} Petugas</span>
                </div>
            </x-slot>

            <div class="divide-y divide-gray-200 dark:divide-gray-800">
                @forelse($staff as $user)
                    @php
                        $userRoleName = $user->roles->first()?->name ?? '-';
                        $branchName = $user->branch?->name ?? 'Pusat';
                    @endphp
                    <div class="flex flex-wrap items-center justify-between gap-3 py-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-indigo-600 text-white font-bold flex items-center justify-center text-xs">
                                {{ strtoupper(substr($user->name, 0, 2)) }}
                            </div>
                            <div>
                                <p class="font-semibold text-sm">{{ $user->name }}</p>
                                <p class="text-xs break-all text-gray-500">{{ $user->email }} &middot; <span class="font-medium text-primary-600">{{ $branchName }}</span></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <x-filament::badge color="info">{{ strtoupper($userRoleName) }}</x-filament::badge>
                            @if(auth()->user()?->hasRole('admin'))
                                <x-filament::button tag="a" :href="route('admin.impersonate', $user)" size="sm" color="gray" :icon="\Filament\Support\Icons\Heroicon::OutlinedUserCircle">Mode Petugas</x-filament::button>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 py-4 text-center">Belum ada akun petugas.</p>
                @endforelse
            </div>
        </x-filament::section>

        <!-- Recent Queue Activity Log Section -->
        <x-filament::section>
            <x-slot name="heading">
                <span class="font-bold">{{ __('app.queue_management') }} Terkini</span>
            </x-slot>

            <div class="divide-y divide-gray-200 dark:divide-gray-800">
                @forelse($recentQueues as $queue)
                    @php
                        $statusValue = is_object($queue->status) ? $queue->status->value : $queue->status;
                        $statusLabel = is_object($queue->status) ? $queue->status->getLabel() : __('app.status_' . $queue->status);
                    @endphp
                    <div class="flex flex-wrap items-center justify-between gap-3 py-3">
                        <div class="flex min-w-0 items-center gap-3">
                            <div class="px-2.5 py-1 rounded-lg bg-gray-100 dark:bg-gray-800 font-mono font-bold text-sm text-primary-600">
                                {{ $queue->number }}
                            </div>
                            <div>
                                <p class="font-semibold text-sm">{{ $queue->serviceSetting?->counter_name ?? 'Loket' }}</p>
                                <p class="text-xs text-gray-500">
                                    {{ $queue->branch?->name ?? 'Kantor Pusat' }} &middot; {{ $queue->created_at->diffForHumans() }}
                                </p>
                            </div>
                        </div>

                        <x-filament::badge :color="match($statusValue) { 'completed' => 'success', 'called', 'serving' => 'info', 'waiting' => 'warning', default => 'gray' }">{{ $statusLabel }}</x-filament::badge>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 py-4 text-center">Belum ada aktivitas antrean.</p>
                @endforelse
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
