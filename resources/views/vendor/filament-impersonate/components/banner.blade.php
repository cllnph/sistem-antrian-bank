@props(['style' => null, 'display' => null, 'fixed' => null, 'position' => null])
@php
    $impersonating = \STS\FilamentImpersonate\Facades\Impersonation::isImpersonating();
    $guardMatches = (\STS\FilamentImpersonate\Facades\Impersonation::getImpersonatorGuardUsingName() ?? config('auth.defaults.guard'))
        === \Filament\Facades\Filament::getAuthGuard();
@endphp
@if($impersonating && $guardMatches)
    <x-filament::section heading="Mode petugas" :description="'Anda sedang menggunakan akun '.($display ?? auth()->user()?->name)">
        <x-slot name="afterHeader">
            <x-filament::button tag="a" :href="route('filament-impersonate.leave')" color="gray" :icon="\Filament\Support\Icons\Heroicon::OutlinedArrowUturnLeft">Kembali ke admin</x-filament::button>
        </x-slot>
    </x-filament::section>
@endif
