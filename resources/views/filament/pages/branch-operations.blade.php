<x-filament-panels::page>
    <div wire:poll.10s class="space-y-6">
        <x-filament::section>
            <x-slot name="heading">Ringkasan periode berjalan</x-slot>
            <x-slot name="description">Satu pilihan cabang berlaku untuk ringkasan layanan dan daftar di bawah.</x-slot>
            <label for="dashboard-branch" class="text-sm font-medium">Cabang</label>
            <x-filament::input.wrapper class="mt-2 max-w-xl">
                <x-filament::input.select id="dashboard-branch" wire:model.live="selectedBranchId">
                    @if(auth()->user()->hasRole('admin'))<option value="">Semua cabang</option>@endif
                    @foreach($branchOptions as $option)
                        <option value="{{ $option->id }}">{{ $option->code }} · {{ $option->name }}</option>
                    @endforeach
                </x-filament::input.select>
            </x-filament::input.wrapper>
        </x-filament::section>
        <div class="grid gap-6 xl:grid-cols-2">
            @foreach($summary as $item)
                <x-filament::section :heading="$item['label']">
                    <dl class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                        @foreach(['waiting'=>'Menunggu','called'=>'Dilayani','completed'=>'Selesai','skipped'=>'Dilewati'] as $key=>$label)
                            <div class="min-w-0 rounded-xl border border-gray-200 p-3 dark:border-gray-700">
                                <dt class="text-sm text-gray-500 dark:text-gray-400">{{ $label }}</dt>
                                <dd class="mt-2 text-3xl font-semibold">{{ $item[$key] }}</dd>
                            </div>
                        @endforeach
                    </dl>
                </x-filament::section>
            @endforeach
        </div>
        <x-filament::section heading="Ringkasan Antrean per Cabang" description="Buka Detail untuk profil, loket, jadwal, kiosk, layar, dan printer.">
            <div class="divide-y divide-gray-200 dark:divide-gray-700">
                @forelse($branches as $branch)
                    <div class="flex flex-col gap-4 py-4 sm:flex-row sm:items-center sm:justify-between" wire:key="branch-{{ $branch->id }}">
                        <div class="min-w-0 flex-1">
                            <p class="font-semibold break-words">{{ $branch->code }} · {{ $branch->name }}</p>
                            <div class="mt-2 flex flex-wrap items-center gap-2">
                                <x-filament::badge :color="$branch->service_open ? 'success' : 'gray'">{{ $branch->service_open ? 'Layanan buka' : ($branch->is_active ? 'Di luar jam layanan' : 'Cabang nonaktif') }}</x-filament::badge>
                                <span class="text-sm text-gray-500 dark:text-gray-400">{{ $branch->total_count }} tiket dalam periode</span>
                            </div>
                        </div>
                        <dl class="flex flex-wrap gap-x-5 gap-y-2 text-sm">
                            @foreach(['waiting_count'=>'Menunggu','called_count'=>'Dilayani','completed_count'=>'Selesai'] as $key=>$label)
                                <div><dt class="text-gray-500 dark:text-gray-400">{{ $label }}</dt><dd class="font-semibold">{{ $branch->$key }}</dd></div>
                            @endforeach
                        </dl>
                        <x-filament::button tag="a" color="gray" :href="\App\Filament\Pages\BranchDetail::getUrl(['branch'=>$branch->id])">Detail</x-filament::button>
                    </div>
                @empty
                    <p class="py-4 text-sm text-gray-500">Belum ada cabang untuk ditampilkan.</p>
                @endforelse
            </div>
        </x-filament::section>
        <p class="text-sm text-gray-500">Diperbarui setiap 10 detik · {{ now()->format('d M Y H:i') }} WIB</p>
    </div>
</x-filament-panels::page>
