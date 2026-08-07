<!doctype html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kelola Role & Hak Akses — PT BPR DP TASPEN</title>
    
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;700;800;900&family=Plus+Jakarta+Sans:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#eff6ff',
                            900: '#163b75',
                            950: '#0f2b5c',
                            gold: '#fdb913'
                        }
                    },
                    fontFamily: {
                        sans: ['Plus Jakarta Sans', 'system-ui', 'sans-serif'],
                        heading: ['Outfit', 'sans-serif']
                    }
                }
            }
        }
    </script>
    <style>
        body { font-family: 'Plus Jakarta Sans', system-ui, sans-serif; }
    </style>
</head>
<body class="h-full antialiased selection:bg-brand-gold selection:text-slate-900 transition-colors duration-300"
      x-data="roleManager()"
      :class="darkMode ? 'bg-[#091322] text-slate-100' : 'bg-slate-100 text-slate-900'">

    <div class="min-h-screen flex flex-col lg:flex-row" :class="darkMode ? 'bg-[#091322]' : 'bg-slate-100'">
        
        <!-- SIDEBAR -->
        <aside class="w-full lg:w-72 border-r flex-shrink-0 flex flex-col justify-between transition-colors duration-300"
               :class="darkMode ? 'bg-[#040d1a] border-slate-800' : 'bg-white border-slate-200 shadow-xl'">
            <div>
                <div class="p-6 border-b flex items-center justify-between"
                     :class="darkMode ? 'border-slate-800/80' : 'border-slate-100'">
                    <a href="/admin" class="bg-white p-2.5 px-5 rounded-2xl border border-slate-200 shadow-md inline-flex items-center justify-center">
                        @include('components.bank-logo', ['size' => '36px'])
                    </a>
                </div>

                <!-- Navigation Menu (Simplified: Menu User & Menu Antrean) -->
                <nav class="p-4 space-y-3">
                    <a href="/admin/users" class="flex items-center gap-3 px-4 py-3 rounded-2xl font-bold text-xs bg-brand-900 text-white shadow-lg shadow-brand-900/40 border-l-4 border-brand-gold transition">
                        <svg class="w-5 h-5 text-brand-gold" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <span class="text-sm font-extrabold font-heading">Menu User</span>
                    </a>

                    <a href="/admin" class="flex items-center gap-3 px-4 py-3 rounded-2xl font-bold text-xs transition"
                       :class="darkMode ? 'text-slate-300 hover:bg-slate-800/70 hover:text-white' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900'">
                        <svg class="w-5 h-5 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        <span class="text-sm font-extrabold font-heading">Menu Antrean</span>
                    </a>
                </nav>
            </div>
            <div class="p-4 border-t text-[11px] font-semibold text-center"
                 :class="darkMode ? 'border-slate-800 text-slate-400' : 'border-slate-100 text-slate-500'">
                PT BPR DP TASPEN © {{ date('Y') }}
            </div>
        </aside>

        <!-- MAIN CONTAINER -->
        <main class="flex-1 flex flex-col min-w-0" :class="darkMode ? 'bg-[#091322]' : 'bg-slate-100'">

            <!-- TOPBAR HEADER -->
            <header class="backdrop-blur-xl border-b px-6 py-4 flex items-center justify-between sticky top-0 z-30 transition-colors duration-300"
                    :class="darkMode ? 'bg-[#040d1a]/90 border-slate-800' : 'bg-white/90 border-slate-200 shadow-sm'">
                <div class="flex items-center gap-4">
                    <h2 class="text-xl font-extrabold tracking-tight font-heading" :class="darkMode ? 'text-white' : 'text-slate-900'">Kelola Role & Hak Akses (10 Modul Perbankan)</h2>
                </div>

                <div class="flex items-center gap-3">
                    
                    <!-- THEME TOGGLE BUTTON -->
                    <button @click="toggleTheme()" class="p-2.5 rounded-xl border transition cursor-pointer flex items-center justify-center"
                            :class="darkMode ? 'bg-slate-800 text-amber-400 border-slate-700 hover:bg-slate-700' : 'bg-slate-100 text-amber-600 border-slate-300 hover:bg-slate-200'"
                            title="Beralih Tema Terang / Gelap">
                        <span x-show="darkMode" class="flex items-center gap-1.5 text-xs font-bold">☀️ <span class="hidden sm:inline">Terang</span></span>
                        <span x-show="!darkMode" style="display:none;" class="flex items-center gap-1.5 text-xs font-bold">🌙 <span class="hidden sm:inline">Gelap</span></span>
                    </button>

                    <!-- USER PROFILE DROPDOWN -->
                    <div class="relative" x-data="{ openProfile: false }">
                        <button @click="openProfile = !openProfile" class="flex items-center gap-3 p-1.5 pr-3 rounded-xl border transition cursor-pointer"
                                :class="darkMode ? 'bg-slate-800 hover:bg-slate-700 border-slate-700 text-white' : 'bg-slate-100 hover:bg-slate-200 border-slate-300 text-slate-900'">
                            <div class="w-9 h-9 bg-brand-900 text-brand-gold font-extrabold text-sm rounded-lg flex items-center justify-center border border-brand-gold/40 shadow-inner">
                                {{ strtoupper(substr(Auth::user()?->name ?? 'Admin', 0, 2)) }}
                            </div>
                            <div class="text-left hidden md:block">
                                <div class="text-xs font-bold leading-none" :class="darkMode ? 'text-white' : 'text-slate-900'">{{ Auth::user()?->name ?? 'Administrator' }}</div>
                                <div class="text-[10px] font-semibold text-brand-gold capitalize mt-1">{{ Auth::user()?->role ?? 'Super Admin' }}</div>
                            </div>
                        </button>

                        <div x-show="openProfile" @click.away="openProfile = false" x-transition class="absolute right-0 mt-3 w-60 bg-white rounded-2xl shadow-2xl border border-slate-200 p-2 text-slate-900 z-50" style="display: none;">
                            <div class="p-3 border-b border-slate-100 bg-slate-50 rounded-xl mb-1">
                                <div class="font-extrabold text-xs text-slate-900">{{ Auth::user()?->name }}</div>
                                <div class="text-[11px] text-slate-500 truncate mt-0.5">{{ Auth::user()?->email }}</div>
                            </div>

                            <a href="/admin/users" class="flex items-center gap-2 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100 rounded-lg transition">
                                👥 Manajemen User
                            </a>

                            <button @click="toggleTheme()" type="button" class="w-full flex items-center justify-between px-3 py-2 text-xs font-bold text-slate-700 hover:bg-slate-100 rounded-lg transition cursor-pointer">
                                <span class="flex items-center gap-2">
                                    <span x-show="darkMode">☀️ Mode Terang</span>
                                    <span x-show="!darkMode" style="display:none;">🌙 Mode Gelap</span>
                                </span>
                                <span class="text-[10px] font-extrabold px-2 py-0.5 rounded-full" :class="darkMode ? 'bg-amber-100 text-amber-800' : 'bg-slate-200 text-slate-800'" x-text="darkMode ? 'Gelap' : 'Terang'"></span>
                            </button>
                            
                            <form action="{{ route('admin.logout') }}" method="POST" class="mt-1 pt-1 border-t border-slate-100">
                                @csrf
                                <button type="submit" class="w-full flex items-center gap-2 px-3 py-2 text-xs font-bold text-rose-600 hover:bg-rose-50 rounded-lg transition cursor-pointer">
                                    🚪 Logout
                                </button>
                            </form>
                        </div>
                    </div>

                </div>
            </header>

            <div class="p-6 sm:p-8 max-w-7xl w-full mx-auto space-y-6">

                <!-- SESSION STATUS ALERT -->
                @if (session('status'))
                    <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-600 rounded-2xl p-4 text-xs font-semibold flex items-center gap-2">
                        <span>✅ {{ session('status') }}</span>
                    </div>
                @endif

                @if (isset($errors) && $errors->has('role_error'))
                    <div class="bg-rose-500/10 border border-rose-500/30 text-rose-600 rounded-2xl p-4 text-xs font-semibold flex items-center gap-2">
                        <span>⚠️ {{ $errors->first('role_error') }}</span>
                    </div>
                @endif

                <!-- HEADER ACTIONS -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-black font-heading" :class="darkMode ? 'text-white' : 'text-slate-900'">Kelola Role & Hak Akses Perbankan</h1>
                        <p class="text-xs font-medium mt-1" :class="darkMode ? 'text-slate-400' : 'text-slate-600'">Konfigurasi 10 modul operasional perbankan (Dashboard, User, Role, Loket, Teller, CS, Kredit, Transaksi, Laporan, Pengaturan) secara granular.</p>
                    </div>

                    <button @click="openAddModal()" class="inline-flex items-center justify-center gap-2 px-5 py-3 bg-purple-900 hover:bg-purple-950 text-white font-extrabold text-xs rounded-xl shadow-lg border border-purple-400/30 cursor-pointer transition">
                        🛡️ + Tambah Role & Hak Akses Baru
                    </button>
                </div>

                <!-- ROLES GRID CARD -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($roles as $role)
                        @php
                            $roleNameLower = strtolower($role->name);
                            $isPrimaryAdmin = in_array($roleNameLower, ['admin', 'administrator', 'super_admin']);
                            $assignedUsersCount = \App\Models\User::where('role', $role->name)
                                ->orWhereHas('roles', fn ($q) => $q->where('roles.id', $role->id))
                                ->count();
                            $rolePerms = $role->permissions->pluck('name')->toArray();
                        @endphp

                        <div class="bg-white text-slate-900 rounded-2xl p-6 shadow-xl border border-slate-200 flex flex-col justify-between hover:shadow-2xl transition">
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <span class="px-3 py-1 bg-purple-100 text-purple-800 font-black text-xs rounded-lg border border-purple-200">
                                        🛡️ {{ ucfirst(str_replace('_', ' ', $role->name)) }}
                                    </span>
                                    <span class="text-[11px] font-bold text-slate-500">
                                        {{ $assignedUsersCount }} Pengguna Aktif
                                    </span>
                                </div>

                                <p class="text-xs text-slate-600 mb-4 line-clamp-2">
                                    Memiliki {{ $role->permissions->count() }} dari {{ $permissions->count() }} izin hak akses granular pada 10 modul perbankan.
                                </p>

                                <div class="space-y-1.5 mb-6">
                                    <div class="text-[10px] font-extrabold uppercase text-slate-400">Ringkasan Hak Akses:</div>
                                    <div class="flex flex-wrap gap-1.5">
                                        @forelse ($role->permissions->take(6) as $perm)
                                            <span class="px-2 py-0.5 bg-slate-100 text-slate-700 font-semibold text-[10px] rounded-md border border-slate-200">
                                                {{ $perm->display_name ?? $perm->name }}
                                            </span>
                                        @empty
                                            <span class="text-xs text-slate-400 italic">Belum ada hak akses dikonfigurasi</span>
                                        @endforelse
                                        @if ($role->permissions->count() > 6)
                                            <span class="px-2 py-0.5 bg-slate-200 text-slate-800 font-bold text-[10px] rounded-md">
                                                +{{ $role->permissions->count() - 6 }} Lainnya
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <div class="pt-4 border-t border-slate-100 flex items-center justify-between gap-2">
                                <button @click="openEditRoleModal({{ $role->id }}, '{{ ucfirst(str_replace('_', ' ', $role->name)) }}', {{ json_encode($rolePerms) }})" class="px-3.5 py-2 bg-slate-100 hover:bg-slate-200 text-slate-800 font-bold text-xs rounded-xl cursor-pointer transition">
                                    ✏️ Edit Hak Akses
                                </button>

                                @if (!$isPrimaryAdmin)
                                    <form action="{{ route('admin.roles.destroy', $role->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus role \'{{ ucfirst(str_replace('_', ' ', $role->name)) }}\'?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="px-3.5 py-2 bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold text-xs rounded-xl cursor-pointer transition border border-rose-200">
                                            🗑️ Hapus
                                        </button>
                                    </form>
                                @else
                                    <span class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider bg-slate-100 px-2.5 py-1 rounded-md">
                                        🔒 Admin Permanent
                                    </span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

            </div>
        </main>
    </div>

    <!-- MODAL TAMBAH & EDIT ROLE DENGAN ACCORDION 10 MODUL ALPINE.JS -->
    <div x-show="showRoleModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md" style="display: none;">
        <div @click.away="showRoleModal = false" class="bg-white text-slate-900 rounded-3xl max-w-3xl w-full p-6 sm:p-8 shadow-2xl border border-slate-200 max-h-[92vh] flex flex-col justify-between">
            
            <!-- MODAL HEADER -->
            <div class="flex items-center justify-between pb-4 border-b border-slate-100 shrink-0">
                <div>
                    <h3 class="text-xl font-black text-slate-900 font-heading" x-text="isEditMode ? '✏️ Edit Hak Akses Role' : '🛡️ Tambah Role & Hak Akses Baru'"></h3>
                    <p class="text-xs text-slate-500 mt-0.5">Konfigurasi matriks otorisasi granular untuk 10 modul operasional perbankan.</p>
                </div>
                <button type="button" @click="showRoleModal = false" class="text-slate-400 hover:text-slate-700 text-lg font-bold">✕</button>
            </div>

            <!-- MODAL BODY (SCROLLABLE) -->
            <div class="overflow-y-auto py-4 space-y-5 flex-1 pr-1">
                
                <!-- FORM INPUT NAMA ROLE -->
                <div x-show="!isEditMode">
                    <label class="block text-xs font-extrabold uppercase tracking-wider text-slate-700 mb-1">Nama Role / Aktor Perbankan *</label>
                    <input type="text" x-model="roleForm.name" required placeholder="Contoh: Supervisor Cabang, Kredit Officer..." class="w-full px-4 py-3 bg-slate-50 border border-slate-300 rounded-2xl text-xs font-bold outline-none focus:ring-2 focus:ring-purple-600">
                </div>

                <!-- QUICK PRESET TEMPLATES -->
                <div class="bg-slate-50 p-4 rounded-2xl border border-slate-200">
                    <div class="text-[11px] font-extrabold uppercase tracking-wider text-slate-500 mb-2">⚡ Gunakan Template Akses Cepat:</div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" @click="applyTemplate('teller')" class="px-3 py-1.5 bg-sky-100 hover:bg-sky-200 text-sky-800 text-xs font-bold rounded-xl border border-sky-300 transition">
                            💵 Teller
                        </button>
                        <button type="button" @click="applyTemplate('cs')" class="px-3 py-1.5 bg-emerald-100 hover:bg-emerald-200 text-emerald-800 text-xs font-bold rounded-xl border border-emerald-300 transition">
                            🎧 Customer Service
                        </button>
                        <button type="button" @click="applyTemplate('kredit')" class="px-3 py-1.5 bg-amber-100 hover:bg-amber-200 text-amber-800 text-xs font-bold rounded-xl border border-amber-300 transition">
                            💳 Kredit Officer
                        </button>
                        <button type="button" @click="applyTemplate('admin')" class="px-3 py-1.5 bg-purple-100 hover:bg-purple-200 text-purple-800 text-xs font-bold rounded-xl border border-purple-300 transition">
                            🛡️ Administrator Full
                        </button>
                        <button type="button" @click="roleForm.selectedPermissions = []" class="px-3 py-1.5 bg-slate-200 hover:bg-slate-300 text-slate-700 text-xs font-bold rounded-xl transition ml-auto">
                            🔄 Reset Semua
                        </button>
                    </div>
                </div>

                <!-- SEARCH BAR PERMISSIONS -->
                <div class="relative">
                    <input type="text" x-model="searchQuery" placeholder="🔍 Cari nama hak akses atau modul (misal: kredit, setor tunai)..." class="w-full px-4 py-2.5 bg-white border border-slate-300 rounded-2xl text-xs font-medium text-slate-900 focus:ring-2 focus:ring-purple-600 outline-none pl-9">
                    <span class="absolute left-3 top-2.5 text-slate-400 text-xs">🔍</span>
                </div>

                <!-- ACCORDION LIST PER MODUL (10 MODUL) -->
                <div class="space-y-3">
                    <template x-for="(perms, moduleName) in modulesMap" :key="moduleName">
                        <div x-show="matchesSearch(moduleName, perms)" class="border border-slate-200 rounded-2xl overflow-hidden shadow-sm transition">
                            
                            <!-- ACCORDION HEADER -->
                            <div class="bg-slate-100/90 px-4 py-3 flex items-center justify-between cursor-pointer select-none"
                                 @click="toggleAccordion(moduleName)">
                                <div class="flex items-center gap-3">
                                    <span class="text-base" x-text="getModuleIcon(moduleName)"></span>
                                    <span class="font-extrabold text-xs text-slate-900 font-heading" x-text="moduleName"></span>
                                    <span class="px-2 py-0.5 bg-purple-100 text-purple-800 text-[10px] font-black rounded-full"
                                          x-text="getSelectedCountInModule(perms) + '/' + perms.length + ' dipilih'"></span>
                                </div>

                                <div class="flex items-center gap-3" @click.stop>
                                    <!-- SELECT ALL CHECKBOX IN ACCORDION HEADER -->
                                    <label class="flex items-center gap-1.5 text-[11px] font-bold text-slate-600 hover:text-slate-900 cursor-pointer">
                                        <input type="checkbox"
                                               :checked="isModuleAllSelected(perms)"
                                               @change="toggleSelectAllModule(perms, $event.target.checked)"
                                               class="w-3.5 h-3.5 text-purple-700 border-slate-300 rounded focus:ring-purple-600">
                                        <span>Pilih Semua</span>
                                    </label>
                                    <span class="text-slate-400 text-xs font-bold" x-text="accordionStates[moduleName] ? '▲' : '▼'"></span>
                                </div>
                            </div>

                            <!-- ACCORDION CONTENT (CHECKLIST GRANULAR) -->
                            <div x-show="accordionStates[moduleName] || searchQuery.length > 0" x-transition class="p-4 bg-white grid grid-cols-1 sm:grid-cols-2 gap-2 text-xs">
                                <template x-for="p in perms" :key="p.name">
                                    <label x-show="p.display_name.toLowerCase().includes(searchQuery.toLowerCase()) || p.name.toLowerCase().includes(searchQuery.toLowerCase())"
                                           class="flex items-center gap-2.5 p-2 rounded-xl border border-slate-100 hover:bg-purple-50/50 hover:border-purple-200 transition cursor-pointer">
                                        <input type="checkbox"
                                               :value="p.name"
                                               x-model="roleForm.selectedPermissions"
                                               class="w-4 h-4 text-purple-700 border-slate-300 rounded focus:ring-purple-600">
                                        <div>
                                            <div class="font-bold text-slate-900" x-text="p.display_name"></div>
                                            <div class="text-[9px] font-mono text-slate-400" x-text="p.name"></div>
                                        </div>
                                    </label>
                                </template>
                            </div>

                        </div>
                    </template>
                </div>

            </div>

            <!-- MODAL FOOTER WITH TOTAL COUNTER & VALIDATION -->
            <form :action="isEditMode ? '/admin/roles/' + roleForm.id : '/admin/roles'" method="POST" class="pt-4 border-t border-slate-100 shrink-0 flex items-center justify-between">
                @csrf
                <template x-if="isEditMode">
                    <input type="hidden" name="_method" value="PUT">
                </template>
                <input type="hidden" name="name" :value="roleForm.name">
                <template x-for="p in roleForm.selectedPermissions" :key="p">
                    <input type="hidden" name="permissions[]" :value="p">
                </template>

                <div class="text-xs font-bold text-slate-600">
                    Total Selected: <span class="font-extrabold text-purple-700" x-text="roleForm.selectedPermissions.length"></span> dari <span x-text="totalPermissionsCount"></span> hak akses
                </div>

                <div class="flex items-center gap-2">
                    <button type="button" @click="showRoleModal = false" class="px-4 py-2.5 bg-slate-100 text-slate-700 font-bold text-xs rounded-xl">Batal</button>
                    <button type="submit" :disabled="roleForm.selectedPermissions.length === 0" class="px-6 py-2.5 bg-purple-900 hover:bg-purple-950 disabled:opacity-50 text-white font-extrabold text-xs rounded-xl shadow-lg border border-purple-400/30 transition cursor-pointer">
                        Simpan Role & Hak Akses
                    </button>
                </div>
            </form>

        </div>
    </div>

    <!-- ALPINE.JS CONTROLLER FOR 10-MODULE ACCORDION PERMISSIONS -->
    <script>
        function roleManager() {
            const modulesData = {
                'Dashboard': [
                    { name: 'dashboard.admin', display_name: 'Akses Admin' },
                    { name: 'dashboard.control_center', display_name: 'Lihat Control Center' }
                ],
                'Manajemen User': [
                    { name: 'user.view', display_name: 'Lihat User' },
                    { name: 'user.create', display_name: 'Tambah User' },
                    { name: 'user.edit', display_name: 'Ubah User' },
                    { name: 'user.delete', display_name: 'Hapus User' }
                ],
                'Role & Hak Akses': [
                    { name: 'role.view', display_name: 'Lihat Role' },
                    { name: 'role.create', display_name: 'Tambah Role' },
                    { name: 'role.edit', display_name: 'Ubah Role' },
                    { name: 'role.delete', display_name: 'Hapus Role' },
                    { name: 'role.manage_permissions', display_name: 'Atur Hak Akses' }
                ],
                'Loket Layanan': [
                    { name: 'loket.view', display_name: 'Lihat Loket' },
                    { name: 'loket.create', display_name: 'Tambah Loket' },
                    { name: 'loket.edit', display_name: 'Ubah Loket' },
                    { name: 'loket.delete', display_name: 'Hapus Loket' },
                    { name: 'loket.assign_user', display_name: 'Tetapkan User ke Loket' }
                ],
                'Teller Kas': [
                    { name: 'teller.access', display_name: 'Akses Menu Teller' },
                    { name: 'teller.setor_tunai', display_name: 'Transaksi Setor Tunai' },
                    { name: 'teller.tarik_tunai', display_name: 'Transaksi Tarik Tunai' },
                    { name: 'teller.pembayaran_pensiun', display_name: 'Transaksi Pembayaran Pensiun' },
                    { name: 'teller.cetak_bukti', display_name: 'Cetak Bukti Transaksi' },
                    { name: 'teller.void', display_name: 'Batalkan Transaksi (Void)' }
                ],
                'Customer Service': [
                    { name: 'cs.access', display_name: 'Akses Menu CS' },
                    { name: 'cs.buka_rekening', display_name: 'Buka Rekening Baru' },
                    { name: 'cs.edit_nasabah', display_name: 'Ubah Data Nasabah' },
                    { name: 'cs.cetak_buku', display_name: 'Cetak Buku Tabungan' },
                    { name: 'cs.manage_card', display_name: 'Aktivasi/Blokir Kartu' },
                    { name: 'cs.complaint', display_name: 'Kelola Keluhan Nasabah' }
                ],
                'Kredit & Pinjaman': [
                    { name: 'kredit.view', display_name: 'Lihat Pengajuan Kredit' },
                    { name: 'kredit.create', display_name: 'Input Pengajuan Kredit' },
                    { name: 'kredit.approve', display_name: 'Setujui/Tolak Kredit' },
                    { name: 'kredit.disburse', display_name: 'Pencairan Kredit' },
                    { name: 'kredit.angsuran', display_name: 'Kelola Angsuran' }
                ],
                'Transaksi Perbankan': [
                    { name: 'transaksi.view', display_name: 'Lihat Semua Transaksi' },
                    { name: 'transaksi.approve', display_name: 'Setujui Transaksi' },
                    { name: 'transaksi.reversal', display_name: 'Pembalikan Transaksi (Reversal)' }
                ],
                'Laporan & Audit': [
                    { name: 'laporan.harian', display_name: 'Lihat Laporan Harian' },
                    { name: 'laporan.transaksi', display_name: 'Lihat Laporan Transaksi' },
                    { name: 'laporan.export', display_name: 'Unduh Laporan (Excel/PDF)' },
                    { name: 'laporan.keuangan', display_name: 'Lihat Laporan Keuangan' }
                ],
                'Pengaturan Sistem': [
                    { name: 'setting.general', display_name: 'Kelola Konfigurasi Umum' },
                    { name: 'setting.branch', display_name: 'Kelola Cabang' },
                    { name: 'setting.audit_log', display_name: 'Log Aktivitas (Audit Log)' }
                ]
            };

            return {
                darkMode: localStorage.getItem('theme') !== 'light',
                toggleTheme() {
                    this.darkMode = !this.darkMode;
                    localStorage.setItem('theme', this.darkMode ? 'dark' : 'light');
                },

                showRoleModal: false,
                isEditMode: false,
                searchQuery: '',

                modulesMap: modulesData,
                accordionStates: { 'Dashboard': true, 'Manajemen User': true, 'Teller Kas': true, 'Customer Service': true },

                roleForm: {
                    id: null,
                    name: '',
                    selectedPermissions: []
                },

                get totalPermissionsCount() {
                    let total = 0;
                    for (let m in this.modulesMap) {
                        total += this.modulesMap[m].length;
                    }
                    return total;
                },

                openAddModal() {
                    this.isEditMode = false;
                    this.roleForm.id = null;
                    this.roleForm.name = '';
                    this.roleForm.selectedPermissions = ['dashboard.admin', 'user.view'];
                    this.showRoleModal = true;
                },

                openEditRoleModal(id, name, perms) {
                    this.isEditMode = true;
                    this.roleForm.id = id;
                    this.roleForm.name = name;
                    this.roleForm.selectedPermissions = perms || [];
                    this.showRoleModal = true;
                },

                toggleAccordion(moduleName) {
                    this.accordionStates[moduleName] = !this.accordionStates[moduleName];
                },

                getModuleIcon(moduleName) {
                    const icons = {
                        'Dashboard': '📊',
                        'Manajemen User': '👥',
                        'Role & Hak Akses': '🛡️',
                        'Loket Layanan': '📌',
                        'Teller Kas': '💵',
                        'Customer Service': '🎧',
                        'Kredit & Pinjaman': '💳',
                        'Transaksi Perbankan': '🔄',
                        'Laporan & Audit': '📈',
                        'Pengaturan Sistem': '⚙️'
                    };
                    return icons[moduleName] || '📂';
                },

                getSelectedCountInModule(perms) {
                    return perms.filter(p => this.roleForm.selectedPermissions.includes(p.name)).length;
                },

                isModuleAllSelected(perms) {
                    return perms.length > 0 && perms.every(p => this.roleForm.selectedPermissions.includes(p.name));
                },

                toggleSelectAllModule(perms, isChecked) {
                    perms.forEach(p => {
                        const idx = this.roleForm.selectedPermissions.indexOf(p.name);
                        if (isChecked && idx === -1) {
                            this.roleForm.selectedPermissions.push(p.name);
                        } else if (!isChecked && idx !== -1) {
                            this.roleForm.selectedPermissions.splice(idx, 1);
                        }
                    });
                },

                matchesSearch(moduleName, perms) {
                    if (!this.searchQuery) return true;
                    const q = this.searchQuery.toLowerCase();
                    if (moduleName.toLowerCase().includes(q)) return true;
                    return perms.some(p => p.display_name.toLowerCase().includes(q) || p.name.toLowerCase().includes(q));
                },

                applyTemplate(type) {
                    if (type === 'admin') {
                        let all = [];
                        for (let m in this.modulesMap) {
                            this.modulesMap[m].forEach(p => all.push(p.name));
                        }
                        this.roleForm.selectedPermissions = all;
                    } else if (type === 'teller') {
                        this.roleForm.selectedPermissions = [
                            'dashboard.admin', 'teller.access', 'teller.setor_tunai', 'teller.tarik_tunai', 'teller.pembayaran_pensiun', 'teller.cetak_bukti'
                        ];
                    } else if (type === 'cs') {
                        this.roleForm.selectedPermissions = [
                            'dashboard.admin', 'cs.access', 'cs.buka_rekening', 'cs.edit_nasabah', 'cs.cetak_buku', 'cs.manage_card', 'cs.complaint'
                        ];
                    } else if (type === 'kredit') {
                        this.roleForm.selectedPermissions = [
                            'dashboard.admin', 'kredit.view', 'kredit.create', 'kredit.approve', 'kredit.disburse', 'kredit.angsuran'
                        ];
                    }
                }
            }
        }
    </script>
</body>
</html>
