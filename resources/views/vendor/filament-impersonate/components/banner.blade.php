@props(['style' => null, 'display' => null, 'fixed' => null, 'position' => null])
@php
    $impersonating = \STS\FilamentImpersonate\Facades\Impersonation::isImpersonating();
    $guardMatches = (\STS\FilamentImpersonate\Facades\Impersonation::getImpersonatorGuardUsingName() ?? config('auth.defaults.guard'))
        === \Filament\Facades\Filament::getAuthGuard();
@endphp
@if($impersonating && $guardMatches)
    <div id="impersonate-banner" role="status">
        <span>Mode petugas <span aria-hidden="true">·</span> Anda sedang menggunakan akun <strong>{{ $display ?? auth()->user()?->name }}</strong></span>
        <x-filament::button tag="a" :href="route('filament-impersonate.leave')" color="gray" size="sm">Kembali ke admin</x-filament::button>
    </div>
@endif
