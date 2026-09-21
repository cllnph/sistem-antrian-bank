<div wire:poll.3s class="w-full flex justify-center py-2">
    @php
        $page = $page ?? ($this ?? null);
        $user = auth()->user();
        $isAdmin = $user?->hasRole('admin') ?? false;
        $setting = $page?->activeSetting;
        $active = $page?->activeQueue;
        $next = $page?->nextWaitingQueue;
        $waitingCount = $page?->waitingCount ?? 0;
        $completedCount = $page?->completedTodayCount ?? 0;
        $isCs = ($setting?->service ?? '') === 'cs';
    @endphp

    <div class="w-full max-w-[450px]">
        <x-filament::section
            :icon="$isCs ? 'heroicon-o-user-group' : 'heroicon-o-banknotes'"
            icon-color="primary"
        >
            <x-slot name="heading">
                {{ $setting?->counter_name ?? 'Loket Operasional' }}
            </x-slot>

            <x-slot name="description">
                Petugas: <span class="font-semibold text-gray-900 dark:text-gray-100">{{ $user?->name }}</span>
            </x-slot>

            <x-slot name="afterHeader">
                @if ($active)
                    <x-filament::badge color="warning" icon="heroicon-m-clock">
                        SEDANG MELAYANI
                    </x-filament::badge>
                @else
                    <x-filament::badge color="success" icon="heroicon-m-check-circle">
                        SIAP MELAYANI
                    </x-filament::badge>
                @endif
            </x-slot>

            @if ($isAdmin)
                <div class="mb-4 pb-3 border-b border-gray-100 dark:border-gray-800">
                    <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-1.5">
                        Pilih Loket Operasional (Mode Administrator)
                    </label>
                    <x-filament::input.wrapper>
                        <x-filament::input.select wire:model.live="selectedSettingId">
                            @foreach (\App\Models\ServiceSetting::where('is_active', true)->orderBy('id')->get() as $s)
                                <option value="{{ $s->id }}">{{ $s->counter_name }} ({{ strtoupper($s->service) }})</option>
                            @endforeach
                        </x-filament::input.select>
                    </x-filament::input.wrapper>
                </div>
            @endif

            <!-- Hero Kotak Nomor Antrean (Garis Lurus Solid konsisten Display TV) -->
            <div class="my-3 text-center rounded-2xl p-4 bg-blue-50/60 dark:bg-gray-800/80" style="border: 2.5px solid #004B9C;">
                @if ($active)
                    <div class="text-xs font-extrabold uppercase tracking-widest text-[#004B9C] dark:text-blue-400 mb-1">
                        NOMOR ANTREAN SAAT INI
                    </div>
                    <div class="text-6xl font-black tracking-wider text-[#002B49] dark:text-white my-1">
                        {{ $active->number }}
                    </div>
                    <div class="mt-2 text-xs font-medium text-gray-500 dark:text-gray-400 flex items-center justify-center gap-2">
                        <span>🕒 {{ $active->called_at ? \Carbon\Carbon::parse($active->called_at)->format('H:i') . ' WIB' : '-' }}</span>
                        <span>•</span>
                        <span>Status: <strong class="text-amber-600 dark:text-amber-400 uppercase font-bold">{{ $active->status->getLabel() }}</strong></span>
                    </div>
                @elseif ($next)
                    <div class="text-xs font-extrabold uppercase tracking-widest text-gray-600 dark:text-gray-300 mb-1">
                        ANTREAN BERIKUTNYA SIAP DIPANGGIL
                    </div>
                    <div class="text-6xl font-black tracking-wider text-[#004B9C] dark:text-blue-400 my-1">
                        {{ $next->number }}
                    </div>
                    <div class="mt-2 text-xs font-medium text-gray-500 dark:text-gray-400">
                        <span>⏳ Menunggu giliran pemanggilan di loket</span>
                    </div>
                @else
                    <div class="text-xs font-extrabold uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-1">
                        STATUS OPERASIONAL LOKET
                    </div>
                    <div class="text-3xl font-black text-gray-400 dark:text-gray-600 my-3">
                        BELUM ADA ANTREAN
                    </div>
                    <div class="text-xs text-gray-500 dark:text-gray-400">
                        Semua nasabah telah selesai dilayani
                    </div>
                @endif
            </div>

            <!-- Mini Summary: Antrean Menunggu & Selesai Hari Ini -->
            <div class="grid grid-cols-2 gap-2.5 my-3">
                <div class="rounded-xl border border-gray-200/80 dark:border-gray-800 bg-gray-50/70 dark:bg-gray-800/50 p-2.5 text-center">
                    <span class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-0.5">Antrean Menunggu</span>
                    <span class="text-lg font-black text-amber-600 dark:text-amber-400">{{ $waitingCount }} <span class="text-xs font-semibold text-gray-500">Nasabah</span></span>
                </div>
                <div class="rounded-xl border border-gray-200/80 dark:border-gray-800 bg-gray-50/70 dark:bg-gray-800/50 p-2.5 text-center">
                    <span class="block text-[11px] font-bold uppercase tracking-wider text-gray-500 dark:text-gray-400 mb-0.5">Selesai Hari Ini</span>
                    <span class="text-lg font-black text-emerald-600 dark:text-emerald-400">{{ $completedCount }} <span class="text-xs font-semibold text-gray-500">Nasabah</span></span>
                </div>
            </div>

            <!-- Tombol-Tombol Fitur Aksi (Native Filament Buttons) -->
            <div class="pt-2">
                @if ($active)
                    <div class="flex flex-col gap-2.5">
                        <div class="grid grid-cols-2 gap-2.5">
                            <x-filament::button
                                color="warning"
                                icon="heroicon-m-speaker-wave"
                                wire:click="recallActiveQueue"
                                size="lg"
                                class="w-full"
                            >
                                Panggil Ulang
                            </x-filament::button>

                            <x-filament::button
                                color="success"
                                icon="heroicon-m-check"
                                wire:click="completeActiveQueue"
                                size="lg"
                                class="w-full"
                            >
                                Selesai Dilayani
                            </x-filament::button>
                        </div>

                        <x-filament::button
                            color="danger"
                            icon="heroicon-m-forward"
                            wire:click="skipActiveQueue"
                            wire:confirm="Yakin ingin melewati nomor antrean {{ $active->number }}?"
                            outlined
                            size="sm"
                            class="w-full"
                        >
                            Lewati Antrean (Nasabah Tidak Hadir)
                        </x-filament::button>
                    </div>
                @else
                    <div>
                        @if ($next)
                            <x-filament::button
                                color="primary"
                                icon="heroicon-m-bell-alert"
                                wire:click="callNextQueue"
                                size="xl"
                                class="w-full py-4 text-base font-black shadow-md"
                            >
                                PANGGIL ANTREAN BERIKUTNYA ({{ $next->number }})
                            </x-filament::button>
                        @else
                            <x-filament::button
                                color="gray"
                                icon="heroicon-m-clock"
                                disabled
                                size="lg"
                                class="w-full"
                            >
                                Menunggu Tiket Baru Dari Kiosk...
                            </x-filament::button>
                        @endif
                    </div>
                @endif
            </div>
        </x-filament::section>
    </div>
</div>
