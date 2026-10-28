<x-filament-panels::page>
    <div class="grid gap-6 lg:grid-cols-2" wire:poll.15s>
        <x-filament::section heading="Profil cabang">
            <dl class="space-y-4 text-sm">
                @foreach(['code'=>'Kode','name'=>'Nama','type'=>'Tipe','address'=>'Alamat','phone'=>'Telepon'] as $key=>$label)
                    <div><dt class="text-gray-500 dark:text-gray-400">{{ $label }}</dt><dd class="mt-1 break-words font-medium">{{ $branch->$key ?: '—' }}</dd></div>
                @endforeach
            </dl>
        </x-filament::section>
        <x-filament::section heading="Waktu operasional">
            <x-filament::badge :color="$operations['is_open'] ? 'success' : 'gray'">{{ $operations['is_open'] ? 'Layanan buka' : 'Layanan tutup' }}</x-filament::badge>
            <p class="mt-4 text-sm">{{ $operations['label'] }}</p>
            <dl class="mt-4 space-y-3 text-sm">
                <div><dt class="text-gray-500">Reset harian</dt><dd>{{ $operations['reset_time'] }} WIB</dd></div>
                <div><dt class="text-gray-500">Reset terakhir</dt><dd>{{ $operations['last_reset_at'] ? \Carbon\Carbon::parse($operations['last_reset_at'])->format('d M Y H:i').' WIB' : 'Belum reset sejak pembaruan' }}</dd></div>
                <div><dt class="text-gray-500">Reset berikutnya</dt><dd>{{ \Carbon\Carbon::parse($operations['next_reset_at'])->format('d M Y H:i') }} WIB</dd></div>
            </dl>
            <x-filament::button class="mt-4" color="gray" tag="a" :href="\App\Filament\Pages\OperatingHours::getUrl()">Lihat waktu operasional</x-filament::button>
        </x-filament::section>
        <x-filament::section heading="Loket layanan" class="lg:col-span-2">
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($branch->serviceSettings as $counter)
                    <div class="flex items-center justify-between gap-4 py-3"><span class="min-w-0 break-words text-sm">{{ $counter->counter_name }}</span><x-filament::badge :color="$counter->is_active ? 'success' : 'gray'">{{ $counter->is_active ? 'Buka' : 'Tutup' }}</x-filament::badge></div>
                @empty<p class="text-sm text-gray-500">Belum ada loket.</p>@endforelse
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>
