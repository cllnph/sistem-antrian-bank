@if(session()->has('impersonate_admin_id') && auth()->check())
    @php
        $adminUser = \App\Models\User::find(session('impersonate_admin_id'));
        $currentUser = auth()->user();
        $branchName = $currentUser->branch?->name ?? 'Pusat';
        $roleName = $currentUser->roles->first()?->name ?? 'Petugas';
    @endphp
    <div style="background: linear-gradient(135deg, #d97706 0%, #b45309 100%); color: #ffffff; padding: 10px 24px; font-size: 13.5px; font-weight: 600; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 999999; box-shadow: 0 4px 20px rgba(180, 83, 9, 0.45); border-bottom: 2px solid #f59e0b;">
        <div style="display: flex; align-items: center; gap: 12px;">
            <span style="font-size: 20px; animation: pulse 2s infinite;">🎭</span>
            <div>
                <span style="background: rgba(0,0,0,0.2); padding: 3px 8px; border-radius: 6px; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px; margin-right: 6px;">MODE IMPERSONATE</span>
                <span>Anda sedang login sebagai <strong>{{ $currentUser->name }}</strong> ({{ strtoupper($roleName) }} &middot; {{ $branchName }}).</span>
                <span style="opacity: 0.85; font-size: 12px; margin-left: 6px;">(Akun Asli: {{ $adminUser?->name ?? 'Administrator' }})</span>
            </div>
        </div>
        <div>
            <a href="{{ route('admin.impersonate.leave') }}" style="background: #ffffff; color: #92400e; padding: 6px 16px; border-radius: 8px; font-size: 12.5px; font-weight: 800; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 2px 8px rgba(0,0,0,0.2); transition: all 0.2s ease;">
                <span>⬅️ Kembali ke Akun Admin</span>
            </a>
        </div>
    </div>
@endif
