<x-filament-panels::page>
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

    <!-- Staff & Activity Logs Grid -->
    <div class="grid gap-6 lg:grid-cols-2 mt-4">
        <!-- Staff List Section -->
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center gap-2">
                    <span class="font-bold">{{ __('app.active_staff') }}</span>
                </div>
            </x-slot>

            <div class="divide-y divide-gray-200 dark:divide-gray-800">
                @forelse($staff as $user)
                    @php
                        $userRoleName = $user->roles->first()?->name ?? '-';
                    @endphp
                    <div class="flex items-center justify-between py-3">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 rounded-full bg-indigo-600 text-white font-bold flex items-center justify-center text-xs">
                                {{ strtoupper(substr($user->name, 0, 2)) }}
                            </div>
                            <div>
                                <p class="font-semibold text-sm">{{ $user->name }}</p>
                                <p class="text-xs text-gray-500">{{ $user->email }}</p>
                            </div>
                        </div>
                        <span class="px-3 py-1 text-xs font-bold rounded-full {{ $userRoleName === 'cs' ? 'bg-purple-100 text-purple-700 dark:bg-purple-900/50 dark:text-purple-300' : 'bg-sky-100 text-sky-700 dark:bg-sky-900/50 dark:text-sky-300' }}">
                            {{ strtoupper($userRoleName) }}
                        </span>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 py-4 text-center">No staff found.</p>
                @endforelse
            </div>
        </x-filament::section>

        <!-- Recent Queue Activity Log Section -->
        <x-filament::section>
            <x-slot name="heading">
                <span class="font-bold">{{ __('app.queue_management') }}</span>
            </x-slot>

            <div class="divide-y divide-gray-200 dark:divide-gray-800">
                @forelse($recentQueues as $queue)
                    @php
                        $statusValue = is_object($queue->status) ? $queue->status->value : $queue->status;
                        $statusLabel = is_object($queue->status) ? $queue->status->getLabel() : __('app.status_' . $queue->status);
                    @endphp
                    <div class="flex items-center justify-between py-2.5">
                        <div class="flex items-center gap-3">
                            <span class="font-mono font-bold text-sm text-indigo-600 dark:text-indigo-400">{{ $queue->number }}</span>
                            <span class="text-xs text-gray-500 font-medium">{{ __('app.service') }} {{ strtoupper($queue->serviceSetting?->service ?? '-') }}</span>
                        </div>
                        <div class="flex items-center gap-3">
                            <span class="text-xs text-gray-400">{{ $queue->updated_at->format('H:i') }}</span>
                            @php
                                $statusColors = [
                                    'waiting' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
                                    'called' => 'bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300',
                                    'serving' => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300',
                                    'completed' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
                                    'skipped' => 'bg-rose-100 text-rose-700 dark:bg-rose-900/40 dark:text-rose-300',
                                ];
                            @endphp
                            <span class="px-2.5 py-0.5 text-xs font-semibold rounded-md {{ $statusColors[$statusValue] ?? 'bg-gray-100 text-gray-700' }}">
                                {{ $statusLabel }}
                            </span>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 py-4 text-center">No queue activities.</p>
                @endforelse
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
