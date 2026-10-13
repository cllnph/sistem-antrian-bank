<x-filament-widgets::widget>
    <x-filament::section heading="Akses Operasional" description="Pilih layanan sesuai tugas dan cabang Anda.">
        <div class="bank-actions">
            <x-filament::button tag="a" :href="\App\Filament\Pages\BranchOperations::getUrl()" :icon="\Filament\Support\Icons\Heroicon::OutlinedBuildingOffice2">Operasional Cabang</x-filament::button>
            <x-filament::button tag="a" :href="url('/admin/queues')" color="gray" :icon="\Filament\Support\Icons\Heroicon::OutlinedQueueList">Daftar Antrean</x-filament::button>
            <x-filament::button tag="a" :href="url('/ambil-nomor/'.(auth()->user()?->branch?->code ?? '001'))" target="_blank" rel="noopener" color="gray" :icon="\Filament\Support\Icons\Heroicon::OutlinedTicket">Buka Kiosk</x-filament::button>
            <x-filament::button tag="a" :href="url('/display/'.(auth()->user()?->branch?->code ?? '001'))" target="_blank" rel="noopener" color="gray" :icon="\Filament\Support\Icons\Heroicon::OutlinedTv">Buka Layar</x-filament::button>
            @if(auth()->user()?->can('view_control_center'))
                <x-filament::button tag="a" :href="\App\Filament\Pages\AdminControlCenter::getUrl()" color="gray" :icon="\Filament\Support\Icons\Heroicon::OutlinedChartBar">Kontrol &amp; Laporan</x-filament::button>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>
