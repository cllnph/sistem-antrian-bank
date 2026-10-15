@if(session()->has('impersonate_admin_id') && auth()->check())
    <x-filament::section heading="Mode petugas" :description="'Anda sedang menggunakan akun '.auth()->user()->name">
        <x-slot name="afterHeader">
            <x-filament::button tag="a" :href="route('admin.impersonate.leave')" color="gray" :icon="\Filament\Support\Icons\Heroicon::OutlinedArrowUturnLeft">Kembali ke admin</x-filament::button>
        </x-slot>
    </x-filament::section>
@endif
