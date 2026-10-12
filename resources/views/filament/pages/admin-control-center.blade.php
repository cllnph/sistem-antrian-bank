<x-filament-panels::page>
    <!-- Filter Cabang Bar (Khusus Admin Pusat) -->
    @if(auth()->user()?->hasRole('admin'))
    <div class="flex flex-wrap items-center justify-between gap-4 p-4 bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800">
        <div class="flex items-center gap-3">
            <div class="p-2 bg-primary-50 dark:bg-primary-950 rounded-lg text-primary-600 dark:text-primary-400">
                <x-heroicon-o-building-office-2 class="w-6 h-6" />
            </div>
            <div>
                <h3 class="font-bold text-base text-gray-900 dark:text-white">Pusat Kendali Operasional 9 Kantor Cabang</h3>
                <p class="text-xs text-gray-500">
                    {{ $selectedBranch ? "Sedang memantau: {$selectedBranch->name} (Kode {$selectedBranch->code})" : "Memantau data gabungan seluruh 9 kantor cabang secara real-time" }}
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <select wire:model.live="selectedBranchId" class="text-sm font-semibold rounded-lg border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-white shadow-sm focus:ring-primary-500">
                <option value="">🏢 Semua Cabang (Agregat Pusat)</option>
                @foreach($branches as $b)
                    <option value="{{ $b->id }}">📍 {{ $b->code }} - {{ $b->name }}</option>
                @endforeach
            </select>
        </div>
    </div>
    @endif

    <!-- Top Executive Summary Cards -->
    <div class="grid gap-6 md:grid-cols-2">
        @foreach ($summary as $service => $item)
            @php
                $isCs = $service === 'cs';
            @endphp
            <x-filament::section class="border-t-4 {{ $isCs ? 'border-purple-500' : 'border-sky-500' }}">
                <x-slot name="heading">
                    <div class="flex items-center justify-between">
                        <span class="font-extrabold text-lg">{{ $item['label'] }}</span>
                        <span class="px-3 py-1 text-xs font-semibold rounded-full {{ $isCs ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300' : 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300' }}">
                            {{ __('app.service') }} {{ strtoupper($service) }}
                        </span>
                    </div>
                </x-slot>

                <div class="grid grid-cols-4 gap-4 py-2 text-center">
                    <div class="p-3 bg-amber-500/10 rounded-xl border border-amber-500/20">
                        <span class="text-xs font-medium text-amber-600 dark:text-amber-400 uppercase tracking-wider">{{ __('app.status_waiting') }}</span>
                        <p class="text-2xl font-black text-amber-700 dark:text-amber-300 mt-1">{{ $item['waiting'] }}</p>
                    </div>

                    <div class="p-3 bg-sky-500/10 rounded-xl border border-sky-500/20">
                        <span class="text-xs font-medium text-sky-600 dark:text-sky-400 uppercase tracking-wider">{{ __('app.status_called') }}</span>
                        <p class="text-2xl font-black text-sky-700 dark:text-sky-300 mt-1">{{ $item['called'] }}</p>
                    </div>

                    <div class="p-3 bg-emerald-500/10 rounded-xl border border-emerald-500/20">
                        <span class="text-xs font-medium text-emerald-600 dark:text-emerald-400 uppercase tracking-wider">{{ __('app.status_completed') }}</span>
                        <p class="text-2xl font-black text-emerald-700 dark:text-emerald-300 mt-1">{{ $item['completed'] }}</p>
                    </div>

                    <div class="p-3 bg-rose-500/10 rounded-xl border border-rose-500/20">
                        <span class="text-xs font-medium text-rose-600 dark:text-rose-400 uppercase tracking-wider">{{ __('app.status_skipped') }}</span>
                        <p class="text-2xl font-black text-rose-700 dark:text-rose-300 mt-1">{{ $item['skipped'] }}</p>
                    </div>
                </div>

                <div class="mt-6 flex justify-end">
                    <x-filament::button 
                        color="danger" 
                        icon="heroicon-o-arrow-path" 
                        size="sm"
                        wire:click="resetService('{{ $service }}')" 
                        wire:confirm="Reset Confirmation: Reset active queues for {{ $item['label'] }}?">
                        {{ __('app.reset_queues') }} ({{ $item['label'] }})
                    </x-filament::button>
                </div>
            </x-filament::section>
        @endforeach
    </div>

    <!-- 9 Branches Performance Matrix (Visible for Admin) -->
    @if(auth()->user()?->hasRole('admin'))
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-table-cells class="w-5 h-5 text-gray-500" />
                <span class="font-bold">Matriks Performa 9 Kantor Cabang Hari Ini</span>
            </div>
        </x-slot>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-gray-600 dark:text-gray-300">
                <thead class="bg-gray-50 dark:bg-gray-800/50 text-xs uppercase text-gray-700 dark:text-gray-200">
                    <tr>
                        <th class="px-4 py-3">Kode</th>
                        <th class="px-4 py-3">Nama Kantor</th>
                        <th class="px-4 py-3">Tipe</th>
                        <th class="px-4 py-3 text-center">Menunggu</th>
                        <th class="px-4 py-3 text-center">Sedang Dilayani</th>
                        <th class="px-4 py-3 text-center">Selesai</th>
                        <th class="px-4 py-3 text-center">Total Tiket</th>
                        <th class="px-4 py-3 text-right">Aksi Pantau</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                    @foreach($branchStats as $bs)
                        <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-800/30">
                            <td class="px-4 py-3 font-mono font-bold text-primary-600">{{ $bs['code'] }}</td>
                            <td class="px-4 py-3 font-semibold text-gray-900 dark:text-white">{{ $bs['name'] }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-0.5 text-xs font-semibold rounded-md bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                                    {{ $bs['type'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300">
                                    {{ $bs['waiting'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-300">
                                    {{ $bs['called'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="px-2.5 py-1 text-xs font-bold rounded-full bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">
                                    {{ $bs['completed'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center font-bold text-gray-900 dark:text-white">
                                {{ $bs['total'] }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex items-center gap-2">
                                    <a href="{{ url('/ambil-nomor/' . $bs['code']) }}" target="_blank" class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950 dark:text-emerald-300 border border-emerald-200 dark:border-emerald-800">
                                        Kiosk
                                    </a>
                                    <a href="{{ url('/display/' . $bs['code']) }}" target="_blank" class="px-2.5 py-1 text-xs font-semibold rounded-lg bg-amber-50 text-amber-700 hover:bg-amber-100 dark:bg-amber-950 dark:text-amber-300 border border-amber-200 dark:border-amber-800">
                                        TV Display
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </x-filament::section>
    @endif

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
                    <div class="flex items-center justify-between py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-indigo-600 text-white font-bold flex items-center justify-center text-xs">
                                {{ strtoupper(substr($user->name, 0, 2)) }}
                            </div>
                            <div>
                                <p class="font-semibold text-sm">{{ $user->name }}</p>
                                <p class="text-xs text-gray-500">{{ $user->email }} &middot; <span class="font-medium text-primary-600">{{ $branchName }}</span></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="px-2.5 py-1 text-xs font-bold rounded-full {{ $userRoleName === 'cs' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300' : 'bg-sky-100 text-sky-700 dark:bg-sky-900/50 dark:text-sky-300' }}">
                                {{ strtoupper($userRoleName) }}
                            </span>
                            @if(auth()->user()?->hasRole('admin') || \STS\FilamentImpersonate\Facades\Impersonation::isImpersonating())
                                <a href="{{ route('admin.impersonate', $user) }}" class="px-2 py-1 text-xs font-semibold rounded-lg bg-amber-50 text-amber-700 hover:bg-amber-100 dark:bg-amber-950 dark:text-amber-300 border border-amber-200 dark:border-amber-800" title="Masuk sebagai {{ $user->name }}">
                                    🎭 Impersonate
                                </a>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 py-4 text-center">No staff found.</p>
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
                    <div class="flex items-center justify-between py-3">
                        <div class="flex items-center gap-3">
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

                        <span class="px-2.5 py-1 text-xs font-semibold rounded-full {{ 
                            $statusValue === 'completed' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' : 
                            ($statusValue === 'called' ? 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300' : 
                            ($statusValue === 'waiting' ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300' : 
                            'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300')) 
                        }}">
                            {{ $statusLabel }}
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 py-4 text-center">Belum ada aktivitas antrean.</p>
                @endforelse
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
