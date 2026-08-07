<!doctype html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manajemen User — PT BPR DP TASPEN Admin Portal</title>
    
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
                            100: '#dbeafe',
                            500: '#2563eb',
                            600: '#1d4ed8',
                            800: '#1e3a8a',
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
      x-data="userManager()"
      :class="darkMode ? 'bg-[#091322] text-slate-100' : 'bg-slate-100 text-slate-900'">

    <!-- TOAST NOTIFICATION -->
    <div x-show="toast.show" x-transition class="fixed top-5 right-5 z-[100] px-5 py-3 rounded-2xl shadow-2xl border text-xs font-bold flex items-center gap-3"
         :class="toast.type === 'success' ? 'bg-emerald-900/90 border-emerald-500 text-white' : 'bg-rose-900/90 border-rose-500 text-white'"
         style="display: none;">
        <span x-text="toast.type === 'success' ? '✅' : '⚠️'"></span>
        <span x-text="toast.message"></span>
    </div>

    <div class="min-h-screen flex flex-col lg:flex-row" :class="darkMode ? 'bg-[#091322]' : 'bg-slate-100'">

        <!-- SIDEBAR NAVIGASI -->
        <aside class="w-full lg:w-72 border-r flex-shrink-0 flex flex-col justify-between transition-colors duration-300"
               :class="darkMode ? 'bg-[#040d1a] border-slate-800' : 'bg-white border-slate-200 shadow-xl'">
            <div>
                <!-- Brand Header -->
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

        <!-- MAIN CONTENT AREA -->
        <main class="flex-1 flex flex-col min-w-0" :class="darkMode ? 'bg-[#091322]' : 'bg-slate-100'">

            <!-- TOPBAR HEADER -->
            <header class="backdrop-blur-xl border-b px-6 py-4 flex items-center justify-between sticky top-0 z-30 transition-colors duration-300"
                    :class="darkMode ? 'bg-[#040d1a]/90 border-slate-800' : 'bg-white/90 border-slate-200 shadow-sm'">
                <div class="flex items-center gap-4">
                    <h2 class="text-xl font-extrabold tracking-tight font-heading" :class="darkMode ? 'text-white' : 'text-slate-900'">Manajemen User</h2>
                    <span class="hidden sm:inline-block px-3 py-1 bg-brand-900/60 border border-brand-500/30 text-brand-100 text-xs font-bold rounded-full">
                        Total: {{ $users->total() }} Pengguna
                    </span>
                </div>

                <div class="flex items-center gap-3">
                    
                    <!-- REAL-TIME LIVE DIGITAL CLOCK WIDGET -->
                    <div x-data="liveClock()" class="hidden sm:flex items-center gap-2.5 px-3.5 py-1.5 rounded-xl border shadow-inner transition"
                         :class="darkMode ? 'bg-slate-900/90 text-brand-gold border-slate-700/80' : 'bg-slate-100 text-slate-800 border-slate-200'">
                        <div class="w-2 h-2 rounded-full bg-emerald-500 animate-ping"></div>
                        <div class="text-left font-mono">
                            <div class="text-xs font-black tracking-widest leading-none" x-text="timeString"></div>
                            <div class="text-[9px] font-extrabold opacity-75 mt-0.5 tracking-tight font-sans" x-text="dateString"></div>
                        </div>
                    </div>

                    <!-- THEME TOGGLE BUTTON -->
                    <button @click="toggleTheme()" class="p-2.5 rounded-xl border transition cursor-pointer flex items-center justify-center"
                            :class="darkMode ? 'bg-slate-800 text-amber-400 border-slate-700 hover:bg-slate-700' : 'bg-slate-100 text-amber-600 border-slate-300 hover:bg-slate-200'"
                            title="Beralih Tema Terang / Gelap">
                        <span x-show="darkMode" class="flex items-center gap-1.5 text-xs font-bold">☀️ <span class="hidden sm:inline">Terang</span></span>
                        <span x-show="!darkMode" style="display:none;" class="flex items-center gap-1.5 text-xs font-bold">🌙 <span class="hidden sm:inline">Gelap</span></span>
                    </button>

                    <!-- NOTIFICATION BELL ICON -->
                    <div class="relative" x-data="{ openNotify: false }">
                        <button @click="openNotify = !openNotify" class="relative p-2.5 rounded-xl border transition cursor-pointer"
                                :class="darkMode ? 'bg-slate-800 hover:bg-slate-700 text-slate-300 border-slate-700' : 'bg-slate-100 hover:bg-slate-200 text-slate-700 border-slate-300'">
                            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                            </svg>
                            <span class="absolute -top-1 -right-1 w-5 h-5 bg-rose-500 text-white text-[10px] font-black rounded-full flex items-center justify-center border-2 border-[#040d1a] animate-pulse">
                                3
                            </span>
                        </button>

                        <div x-show="openNotify" @click.away="openNotify = false" x-transition class="absolute right-0 mt-3 w-80 bg-white rounded-2xl shadow-2xl border border-slate-200 p-4 text-slate-900 z-50" style="display: none;">
                            <div class="flex items-center justify-between pb-3 border-b border-slate-100">
                                <span class="font-extrabold text-sm text-slate-900 font-heading">Notifikasi Sistem</span>
                                <span class="text-[10px] font-bold bg-rose-100 text-rose-700 px-2 py-0.5 rounded-full">3 Baru</span>
                            </div>
                            <div class="divide-y divide-slate-100 max-h-64 overflow-y-auto text-xs py-1">
                                <div class="py-2.5">
                                    <div class="font-bold text-slate-900">🔒 Security Audit Warning</div>
                                    <div class="text-slate-500 text-[11px] mt-0.5">Percobaan login gagal terdeteksi dari IP 127.0.0.1.</div>
                                    <div class="text-[9px] text-slate-400 mt-1">5 menit yang lalu</div>
                                </div>
                            </div>
                        </div>
                    </div>

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
                            <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                            </svg>
                        </button>

                        <div x-show="openProfile" @click.away="openProfile = false" x-transition class="absolute right-0 mt-3 w-60 bg-white rounded-2xl shadow-2xl border border-slate-200 p-2 text-slate-900 z-50" style="display: none;">
                            <div class="p-3 border-b border-slate-100 bg-slate-50 rounded-xl mb-1">
                                <div class="font-extrabold text-xs text-slate-900">{{ Auth::user()?->name }}</div>
                                <div class="text-[11px] text-slate-500 truncate mt-0.5">{{ Auth::user()?->email }}</div>
                            </div>

                            <a href="/admin" class="flex items-center gap-2 px-3 py-2 text-xs font-semibold text-slate-700 hover:bg-slate-100 rounded-lg transition">
                                👤 Profil Saya
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

            <!-- MAIN CONTAINER BODY -->
            <div class="p-6 sm:p-8 space-y-6 max-w-7xl w-full mx-auto">

                <!-- SESSION STATUS ALERT -->
                @if (session('status'))
                    <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-600 rounded-2xl p-4 text-xs font-semibold flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>{{ session('status') }}</span>
                        </div>
                    </div>
                @endif

                <!-- TOP BAR ACTION -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-black font-heading" :class="darkMode ? 'text-white' : 'text-slate-900'">Daftar Pengguna Sistem</h1>
                        <p class="text-xs font-medium mt-1" :class="darkMode ? 'text-slate-400' : 'text-slate-600'">Kelola data pengguna, hak akses role dinamis, penugasan loket, dan status akun perbankan.</p>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="{{ route('admin.roles.index') }}" class="px-4 py-3 bg-purple-950/70 hover:bg-purple-900 text-purple-200 border border-purple-500/40 font-bold text-xs rounded-xl shadow transition">
                            🛡️ Kelola Role
                        </a>
                        <a href="{{ route('admin.lokets.index') }}" class="px-4 py-3 bg-amber-950/70 hover:bg-amber-900 text-amber-200 border border-amber-500/40 font-bold text-xs rounded-xl shadow transition">
                            📌 Kelola Loket
                        </a>
                        <button @click="showAddModal = true" class="inline-flex items-center justify-center gap-2 px-5 py-3 bg-brand-900 hover:bg-brand-950 text-white font-extrabold text-xs rounded-xl shadow-lg border border-brand-gold/30 active:scale-[0.98] transition cursor-pointer">
                            <svg class="w-4 h-4 text-brand-gold" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>+ Tambah Pengguna Baru</span>
                        </button>
                    </div>
                </div>

                <!-- FILTER BAR -->
                <div class="bg-white rounded-2xl p-5 shadow-xl border border-slate-200 text-slate-900">
                    <form action="{{ route('admin.users.index') }}" method="GET" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                        <div class="lg:col-span-2">
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">Cari Nama / Email</label>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Ketik nama atau email..." class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium text-slate-900 focus:ring-2 focus:ring-brand-900 outline-none" />
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">Filter Role</label>
                            <select name="role" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold text-slate-900 focus:ring-2 focus:ring-brand-900 outline-none">
                                <option value="">Semua Role</option>
                                @foreach ($roles as $r)
                                    <option value="{{ $r->name }}" {{ request('role') === $r->name ? 'selected' : '' }}>
                                        {{ ucfirst(str_replace('_', ' ', $r->name)) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold uppercase tracking-wider text-slate-500 mb-1.5">Filter Status</label>
                            <select name="status" class="w-full px-3 py-2.5 bg-slate-50 border border-slate-300 rounded-xl text-xs font-semibold text-slate-900 focus:ring-2 focus:ring-brand-900 outline-none">
                                <option value="">Semua Status</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Aktif</option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                            </select>
                        </div>

                        <div class="flex items-end gap-2">
                            <button type="submit" class="flex-1 py-2.5 bg-brand-900 hover:bg-brand-950 text-white font-bold text-xs rounded-xl shadow transition cursor-pointer">
                                Filter
                            </button>
                            <a href="{{ route('admin.users.index') }}" class="px-3 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold text-xs rounded-xl transition text-center">
                                Reset
                            </a>
                        </div>
                    </form>
                </div>

                <!-- TABEL USER -->
                <div class="bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden text-slate-900">
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse">
                            <thead>
                                <tr class="bg-slate-100/80 border-b border-slate-200 text-[11px] font-extrabold uppercase tracking-wider text-slate-600">
                                    <th class="p-4 w-10 text-center">
                                        <input type="checkbox" x-model="selectAll" @change="toggleSelectAll()" class="w-4 h-4 text-brand-900 border-slate-300 rounded focus:ring-brand-900 cursor-pointer">
                                    </th>
                                    <th class="p-4">Nama & Email</th>
                                    <th class="p-4">Role</th>
                                    <th class="p-4">Loket Tugas</th>
                                    <th class="p-4 text-center">Status</th>
                                    <th class="p-4">Login Terakhir</th>
                                    <th class="p-4">Tanggal Dibuat</th>
                                    <th class="p-4 text-center">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-200/80 text-xs font-medium">
                                @forelse ($users as $user)
                                    <tr class="hover:bg-slate-50/80 transition">
                                        <td class="p-4 text-center">
                                            <input type="checkbox" :value="{{ $user->id }}" x-model="selectedRows" class="w-4 h-4 text-brand-900 border-slate-300 rounded focus:ring-brand-900 cursor-pointer">
                                        </td>

                                        <td class="p-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-9 h-9 bg-brand-900 text-brand-gold font-black rounded-xl flex items-center justify-center shadow-sm">
                                                    {{ strtoupper(substr($user->name, 0, 2)) }}
                                                </div>
                                                <div>
                                                    <div class="font-bold text-slate-900 text-sm">{{ $user->name }}</div>
                                                    <div class="text-slate-500 text-[11px]">{{ $user->email }}</div>
                                                </div>
                                            </div>
                                        </td>

                                        <td class="p-4">
                                            @php $roleName = strtolower($user->roles->first()?->name ?? ''); @endphp
                                            @if ($roleName === 'admin' || $roleName === 'super admin' || $roleName === 'administrator')
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-extrabold bg-purple-100 text-purple-800 border border-purple-200">
                                                    🛡️ Administrator
                                                </span>
                                            @elseif ($roleName === 'cs' || $roleName === 'customer service')
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-extrabold bg-emerald-100 text-emerald-800 border border-emerald-200">
                                                    🎧 Customer Service
                                                </span>
                                            @elseif ($roleName === 'teller')
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-extrabold bg-sky-100 text-sky-800 border border-sky-200">
                                                    💵 Teller
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-extrabold bg-slate-100 text-slate-800 border border-slate-200">
                                                    {{ ucfirst(str_replace('_', ' ', $user->roles->first()?->name ?? 'User')) }}
                                                </span>
                                            @endif
                                        </td>

                                        <td class="p-4">
                                            @if ($user->serviceSetting)
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-bold bg-slate-900 text-white shadow-sm">
                                                    📌 {{ $user->serviceSetting->counter_name }}
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[11px] font-medium bg-slate-100 text-slate-500 border border-slate-200 italic">
                                                    Tidak ditugaskan
                                                </span>
                                            @endif
                                        </td>

                                        <td class="p-4 text-center">
                                            <form action="{{ route('admin.users.toggle-status', $user->id) }}" method="POST" class="inline-flex items-center gap-2">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors ease-in-out {{ $user->is_active ? 'bg-emerald-500' : 'bg-slate-300' }}">
                                                    <span class="inline-block h-5 w-5 transform rounded-full bg-white shadow transition {{ $user->is_active ? 'translate-x-5' : 'translate-x-0' }}"></span>
                                                </button>
                                                <span class="text-[11px] font-extrabold {{ $user->is_active ? 'text-emerald-700' : 'text-slate-400' }}">
                                                    {{ $user->is_active ? 'Aktif' : 'Nonaktif' }}
                                                </span>
                                            </form>
                                        </td>

                                        <td class="p-4 text-slate-600 font-mono text-[11px]">
                                            @if ($user->last_login_at)
                                                {{ $user->last_login_at->format('d M Y, H:i') }} WIB
                                            @else
                                                <span class="text-slate-400 italic">Belum pernah</span>
                                            @endif
                                        </td>

                                        <td class="p-4 text-slate-600 font-mono text-[11px]">
                                            {{ $user->created_at?->format('d M Y') }}
                                        </td>

                                        <td class="p-4 text-center">
                                            <div class="relative inline-block text-left" x-data="{ openMenu: false }">
                                                <button @click="openMenu = !openMenu" class="p-2 hover:bg-slate-200 rounded-xl transition text-slate-600 font-bold">
                                                    •••
                                                </button>

                                                <div x-show="openMenu" @click.away="openMenu = false" x-transition class="absolute right-0 mt-1 w-48 bg-white rounded-2xl shadow-2xl border border-slate-200 p-2 text-left z-40 text-xs font-semibold text-slate-800" style="display: none;">
                                                    <button @click="openEditModal({{ json_encode($user) }}); openMenu = false" class="w-full flex items-center gap-2 px-3 py-2 hover:bg-slate-100 rounded-lg text-slate-700">
                                                        ✏️ Edit Pengguna
                                                    </button>
                                                    <form action="{{ route('admin.users.reset-password', $user->id) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" onclick="return confirm('Reset kata sandi pengguna ini ke default Taspen123!?')" class="w-full flex items-center gap-2 px-3 py-2 hover:bg-slate-100 rounded-lg text-amber-700">
                                                            🔑 Reset Password
                                                        </button>
                                                    </form>
                                                    <form action="{{ route('admin.users.toggle-status', $user->id) }}" method="POST">
                                                        @csrf
                                                        @method('PATCH')
                                                        <button type="submit" class="w-full flex items-center gap-2 px-3 py-2 hover:bg-slate-100 rounded-lg text-slate-700">
                                                            🔄 {{ $user->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                                        </button>
                                                    </form>
                                                    <div class="border-t border-slate-100 mt-1 pt-1">
                                                        <button @click="confirmDelete({{ $user->id }}, '{{ $user->name }}'); openMenu = false" class="w-full flex items-center gap-2 px-3 py-2 hover:bg-rose-50 text-rose-600 rounded-lg font-bold">
                                                            🗑️ Hapus Pengguna
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="p-12 text-center">
                                            <div class="max-w-sm mx-auto flex flex-col items-center">
                                                <div class="w-16 h-16 bg-slate-100 text-slate-400 rounded-full flex items-center justify-center text-3xl mb-4">🔍</div>
                                                <h3 class="text-base font-bold text-slate-900 font-heading">Tidak Ada Data Pengguna Ditemukan</h3>
                                                <p class="text-xs text-slate-500 mt-1">Coba ubah kata kunci pencarian atau reset filter Anda.</p>
                                                <a href="{{ route('admin.users.index') }}" class="mt-4 px-4 py-2 bg-brand-900 text-white font-bold text-xs rounded-xl shadow">Reset Filter</a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="p-4 bg-slate-50 border-t border-slate-200">
                        {{ $users->links() }}
                    </div>
                </div>

            </div>
        </main>
    </div>

    <!-- MODAL ADD USER (WITH INLINE ADD ROLE & ADD LOKET BUTTONS) -->
    <div x-show="showAddModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm" style="display: none;">
        <div @click.away="showAddModal = false" class="bg-white text-slate-900 rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
            <h3 class="text-lg font-black text-slate-900 font-heading mb-4">Tambah Pengguna Baru</h3>
            <form action="{{ route('admin.users.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap *</label>
                    <input type="text" name="name" required placeholder="Budi Santoso" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium outline-none focus:ring-2 focus:ring-brand-900">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Alamat Email *</label>
                    <input type="email" name="email" required placeholder="budi@bprdptaspen.co.id" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium outline-none focus:ring-2 focus:ring-brand-900">
                </div>
                
                <!-- FIELD ROLE WITH EXPLICIT + TAMBAH ROLE BUTTON -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-bold text-slate-700">Role *</label>
                        <button type="button" @click="showAddRoleModal = true" class="text-[11px] font-extrabold text-purple-700 hover:text-purple-900 hover:underline flex items-center gap-1 cursor-pointer">
                            🛡️ + Tambah Role Baru
                        </button>
                    </div>
                    <select name="role" x-model="addUserRole" @change="if ($event.target.value === '__add_new_role') { showAddRoleModal = true; addUserRole = 'admin'; }" required class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-bold outline-none focus:ring-2 focus:ring-brand-900">
                        <template x-for="r in rolesList" :key="r.name">
                            <option :value="r.name" x-text="r.display_name"></option>
                        </template>
                        <option value="__add_new_role" class="font-black text-purple-700 bg-purple-50">+ Tambah Role Baru...</option>
                    </select>
                </div>

                <!-- FIELD LOKET WITH EXPLICIT + TAMBAH LOKET BUTTON -->
                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="block text-xs font-bold text-slate-700">Penugasan Loket</label>
                        <button type="button" @click="showAddLoketModal = true" class="text-[11px] font-extrabold text-amber-700 hover:text-amber-900 hover:underline flex items-center gap-1 cursor-pointer">
                            📌 + Tambah Loket Baru
                        </button>
                    </div>
                    <select name="service_setting_id" x-model="addUserLoket" @change="if ($event.target.value === '__add_new_loket') { showAddLoketModal = true; addUserLoket = ''; }" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium outline-none focus:ring-2 focus:ring-brand-900">
                        <option value="">Tidak Ditugaskan</option>
                        <template x-for="l in loketsList" :key="l.id">
                            <option :value="l.id" x-text="l.counter_name"></option>
                        </template>
                        <option value="__add_new_loket" class="font-black text-amber-700 bg-amber-50">+ Tambah Loket Baru...</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Password (Min 8 Karakter) *</label>
                    <input type="password" name="password" required minlength="8" placeholder="••••••••" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium outline-none focus:ring-2 focus:ring-brand-900">
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="showAddModal = false" class="px-4 py-2 bg-slate-100 text-slate-700 font-bold text-xs rounded-xl">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-brand-900 text-white font-bold text-xs rounded-xl shadow">Simpan</button>
                </div>
            </form>
        </div>
    </div>

    <!-- SUB-MODAL TAMBAH ROLE BARU (ACCORDION 10 MODUL) -->
    <div x-show="showAddRoleModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md" style="display: none;">
        <div @click.away="showAddRoleModal = false" class="bg-white text-slate-900 rounded-3xl max-w-2xl w-full p-6 shadow-2xl border border-slate-200 max-h-[90vh] flex flex-col justify-between">
            <div class="flex items-center justify-between pb-3 border-b border-slate-100 shrink-0">
                <h3 class="text-base font-black text-slate-900 font-heading">🛡️ Tambah Role & Hak Akses Baru</h3>
                <button type="button" @click="showAddRoleModal = false" class="text-slate-400 hover:text-slate-700 text-sm font-bold">✕</button>
            </div>
            
            <div class="overflow-y-auto py-3 space-y-4 flex-1 pr-1">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nama Role / Aktor *</label>
                    <input type="text" x-model="newRoleForm.name" required placeholder="Misal: Supervisor Cabang, Kredit Officer..." class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-bold outline-none focus:ring-2 focus:ring-purple-600">
                </div>

                <!-- PRESET TEMPLATES -->
                <div class="bg-slate-50 p-3 rounded-xl border border-slate-200">
                    <div class="text-[10px] font-extrabold uppercase text-slate-500 mb-1.5">⚡ Template Akses Cepat:</div>
                    <div class="flex flex-wrap gap-1.5">
                        <button type="button" @click="applySubTemplate('teller')" class="px-2.5 py-1 bg-sky-100 text-sky-800 text-[11px] font-bold rounded-lg">Teller</button>
                        <button type="button" @click="applySubTemplate('cs')" class="px-2.5 py-1 bg-emerald-100 text-emerald-800 text-[11px] font-bold rounded-lg">Customer Service</button>
                        <button type="button" @click="applySubTemplate('kredit')" class="px-2.5 py-1 bg-amber-100 text-amber-800 text-[11px] font-bold rounded-lg">Kredit Officer</button>
                        <button type="button" @click="applySubTemplate('admin')" class="px-2.5 py-1 bg-purple-100 text-purple-800 text-[11px] font-bold rounded-lg">Admin Full</button>
                    </div>
                </div>

                <!-- ACCORDION PER MODUL -->
                <div class="space-y-2">
                    <template x-for="(perms, moduleName) in subModulesMap" :key="moduleName">
                        <div class="border border-slate-200 rounded-xl overflow-hidden shadow-sm">
                            <div class="bg-slate-100/90 px-3 py-2 flex items-center justify-between cursor-pointer select-none" @click="toggleSubAccordion(moduleName)">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs" x-text="getSubModuleIcon(moduleName)"></span>
                                    <span class="font-extrabold text-xs text-slate-900" x-text="moduleName"></span>
                                    <span class="px-1.5 py-0.5 bg-purple-100 text-purple-800 text-[9px] font-black rounded-full" x-text="getSubSelectedCount(perms) + '/' + perms.length"></span>
                                </div>
                                <div class="flex items-center gap-2" @click.stop>
                                    <label class="flex items-center gap-1 text-[10px] font-bold text-slate-600 cursor-pointer">
                                        <input type="checkbox" :checked="isSubModuleAllSelected(perms)" @change="toggleSubModuleAll(perms, $event.target.checked)" class="w-3 h-3 text-purple-700 rounded">
                                        <span>Pilih Semua</span>
                                    </label>
                                    <span class="text-slate-400 text-[10px]" x-text="subAccordionStates[moduleName] ? '▲' : '▼'"></span>
                                </div>
                            </div>
                            <div x-show="subAccordionStates[moduleName]" x-transition class="p-3 bg-white grid grid-cols-1 sm:grid-cols-2 gap-1.5 text-xs">
                                <template x-for="p in perms" :key="p.name">
                                    <label class="flex items-center gap-2 p-1.5 rounded-lg border border-slate-100 hover:bg-purple-50 transition cursor-pointer">
                                        <input type="checkbox" :value="p.name" x-model="newRoleForm.permissions" class="w-3.5 h-3.5 text-purple-700 rounded">
                                        <div>
                                            <div class="font-bold text-slate-900 text-[11px]" x-text="p.display_name"></div>
                                            <div class="text-[8px] font-mono text-slate-400" x-text="p.name"></div>
                                        </div>
                                    </label>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="pt-3 border-t border-slate-100 shrink-0 flex items-center justify-between">
                <div class="text-[11px] font-bold text-slate-600">
                    Selected: <span class="font-extrabold text-purple-700" x-text="newRoleForm.permissions.length"></span> hak akses
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" @click="showAddRoleModal = false" class="px-4 py-2 bg-slate-100 text-slate-700 font-bold text-xs rounded-xl">Batal</button>
                    <button type="button" @click="submitNewRole()" :disabled="isSubmittingRole || newRoleForm.permissions.length === 0" class="px-5 py-2 bg-purple-900 hover:bg-purple-950 text-white font-bold text-xs rounded-xl shadow flex items-center gap-2 cursor-pointer">
                        <span x-show="isSubmittingRole" class="animate-spin">⏳</span>
                        <span>Simpan Role</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- SUB-MODAL TAMBAH LOKET BARU (POP-UP COUNTER DESK) -->
    <div x-show="showAddLoketModal" class="fixed inset-0 z-[60] flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md" style="display: none;">
        <div @click.away="showAddLoketModal = false" class="bg-white text-slate-900 rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
            <div class="flex items-center justify-between mb-4 pb-2 border-b border-slate-100">
                <h3 class="text-base font-black text-slate-900 font-heading">📌 Tambah Loket Layanan Baru</h3>
                <button type="button" @click="showAddLoketModal = false" class="text-slate-400 hover:text-slate-700 text-sm font-bold">✕</button>
            </div>
            
            <form @submit.prevent="submitNewLoket()" class="space-y-4">
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nama / Nomor Loket *</label>
                    <input type="text" x-model="newLoketForm.counter_name" required placeholder="Contoh: Loket CS 3 / Counter Teller 4" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium outline-none focus:ring-2 focus:ring-brand-900">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Jenis Layanan *</label>
                    <select x-model="newLoketForm.service" required class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-bold outline-none focus:ring-2 focus:ring-brand-900">
                        <option value="cs">Customer Service (CS)</option>
                        <option value="teller">Teller Kas</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Label Kiosk (Opsional)</label>
                    <input type="text" x-model="newLoketForm.label" placeholder="Contoh: Layanan Nasabah Utama" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium outline-none focus:ring-2 focus:ring-brand-900">
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="showAddLoketModal = false" class="px-4 py-2 bg-slate-100 text-slate-700 font-bold text-xs rounded-xl">Batal</button>
                    <button type="submit" :disabled="isSubmittingLoket" class="px-5 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs rounded-xl shadow flex items-center gap-2 cursor-pointer">
                        <span x-show="isSubmittingLoket" class="animate-spin">⏳</span>
                        <span>Simpan Loket</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- MODAL EDIT & DELETE -->
    <div x-show="showEditModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm" style="display: none;">
        <div @click.away="showEditModal = false" class="bg-white text-slate-900 rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
            <h3 class="text-lg font-black text-slate-900 font-heading mb-4">Edit Data Pengguna</h3>
            <form :action="'/admin/users/' + editForm.id" method="POST" class="space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nama Lengkap *</label>
                    <input type="text" name="name" x-model="editForm.name" required class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium outline-none focus:ring-2 focus:ring-brand-900">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Alamat Email *</label>
                    <input type="email" name="email" x-model="editForm.email" required class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium outline-none focus:ring-2 focus:ring-brand-900">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Role *</label>
                    <select name="role" x-model="editForm.role" required class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-bold outline-none focus:ring-2 focus:ring-brand-900">
                        <template x-for="r in rolesList" :key="r.name">
                            <option :value="r.name" x-text="r.display_name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Penugasan Loket</label>
                    <select name="service_setting_id" x-model="editForm.service_setting_id" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium outline-none focus:ring-2 focus:ring-brand-900">
                        <option value="">Tidak Ditugaskan</option>
                        <template x-for="l in loketsList" :key="l.id">
                            <option :value="l.id" x-text="l.counter_name"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Password Baru (Opsional)</label>
                    <input type="password" name="password" minlength="8" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium outline-none focus:ring-2 focus:ring-brand-900">
                </div>
                <div class="flex items-center justify-end gap-2 pt-2">
                    <button type="button" @click="showEditModal = false" class="px-4 py-2 bg-slate-100 text-slate-700 font-bold text-xs rounded-xl">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-brand-900 text-white font-bold text-xs rounded-xl shadow">Perbarui</button>
                </div>
            </form>
        </div>
    </div>

    <div x-show="showDeleteModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm" style="display: none;">
        <div @click.away="showDeleteModal = false" class="bg-white text-slate-900 rounded-2xl max-w-sm w-full p-6 shadow-2xl border border-slate-200 text-center">
            <div class="w-14 h-14 bg-rose-100 text-rose-600 rounded-full flex items-center justify-center mx-auto mb-4 text-2xl font-bold">⚠️</div>
            <h3 class="text-base font-black text-slate-900 font-heading">Konfirmasi Hapus Pengguna</h3>
            <p class="text-xs text-slate-600 mt-2">Apakah Anda yakin ingin menghapus pengguna <strong x-text="deleteUserName"></strong>?</p>
            <form :action="'/admin/users/' + deleteUserId" method="POST" class="mt-6 flex items-center gap-2">
                @csrf
                @method('DELETE')
                <button type="button" @click="showDeleteModal = false" class="flex-1 py-2.5 bg-slate-100 text-slate-700 font-bold text-xs rounded-xl">Batal</button>
                <button type="submit" class="flex-1 py-2.5 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-xl shadow">Ya, Hapus</button>
            </form>
        </div>
    </div>

    <!-- ALPINE.JS CONTROLLER WITH INLINE AJAX ROLE & LOKET CREATION -->
    <script>
        function userManager() {
            const subModulesData = {
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

                toast: { show: false, message: '', type: 'success' },
                showToast(msg, type = 'success') {
                    this.toast.message = msg;
                    this.toast.type = type;
                    this.toast.show = true;
                    setTimeout(() => { this.toast.show = false; }, 3500);
                },

                selectedRows: [],
                selectAll: false,
                showAddModal: false,
                showEditModal: false,
                showDeleteModal: false,

                showAddRoleModal: false,
                isSubmittingRole: false,
                subModulesMap: subModulesData,
                subAccordionStates: { 'Dashboard': true, 'Manajemen User': true, 'Teller Kas': true, 'Customer Service': true },
                newRoleForm: { name: '', permissions: ['dashboard.admin', 'user.view'] },

                showAddLoketModal: false,
                isSubmittingLoket: false,
                newLoketForm: { counter_name: '', service: 'cs', label: '' },

                addUserRole: 'admin',
                addUserLoket: '',

                rolesList: [
                    { name: 'admin', display_name: 'Administrator' },
                    { name: 'cs', display_name: 'Customer Service' },
                    { name: 'teller', display_name: 'Teller' }
                ],

                loketsList: [
                    @foreach ($serviceSettings as $setting)
                        { id: {{ $setting->id }}, counter_name: "{{ $setting->counter_name }}" },
                    @endforeach
                ],

                deleteUserId: null,
                deleteUserName: '',
                editForm: { id: null, name: '', email: '', role: '', service_setting_id: '' },

                toggleSubAccordion(moduleName) {
                    this.subAccordionStates[moduleName] = !this.subAccordionStates[moduleName];
                },

                getSubModuleIcon(moduleName) {
                    const icons = {
                        'Dashboard': '📊', 'Manajemen User': '👥', 'Role & Hak Akses': '🛡️',
                        'Loket Layanan': '📌', 'Teller Kas': '💵', 'Customer Service': '🎧',
                        'Kredit & Pinjaman': '💳', 'Transaksi Perbankan': '🔄', 'Laporan & Audit': '📈', 'Pengaturan Sistem': '⚙️'
                    };
                    return icons[moduleName] || '📂';
                },

                getSubSelectedCount(perms) {
                    return perms.filter(p => this.newRoleForm.permissions.includes(p.name)).length;
                },

                isSubModuleAllSelected(perms) {
                    return perms.length > 0 && perms.every(p => this.newRoleForm.permissions.includes(p.name));
                },

                toggleSubModuleAll(perms, isChecked) {
                    perms.forEach(p => {
                        const idx = this.newRoleForm.permissions.indexOf(p.name);
                        if (isChecked && idx === -1) {
                            this.newRoleForm.permissions.push(p.name);
                        } else if (!isChecked && idx !== -1) {
                            this.newRoleForm.permissions.splice(idx, 1);
                        }
                    });
                },

                applySubTemplate(type) {
                    if (type === 'admin') {
                        let all = [];
                        for (let m in this.subModulesMap) {
                            this.subModulesMap[m].forEach(p => all.push(p.name));
                        }
                        this.newRoleForm.permissions = all;
                    } else if (type === 'teller') {
                        this.newRoleForm.permissions = ['dashboard.admin', 'teller.access', 'teller.setor_tunai', 'teller.tarik_tunai', 'teller.pembayaran_pensiun', 'teller.cetak_bukti'];
                    } else if (type === 'cs') {
                        this.newRoleForm.permissions = ['dashboard.admin', 'cs.access', 'cs.buka_rekening', 'cs.edit_nasabah', 'cs.cetak_buku', 'cs.manage_card', 'cs.complaint'];
                    } else if (type === 'kredit') {
                        this.newRoleForm.permissions = ['dashboard.admin', 'kredit.view', 'kredit.create', 'kredit.approve', 'kredit.disburse', 'kredit.angsuran'];
                    }
                },

                async submitNewRole() {
                    if (!this.newRoleForm.name || this.newRoleForm.permissions.length === 0) return;
                    this.isSubmittingRole = true;
                    try {
                        const res = await fetch('/admin/api/roles', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(this.newRoleForm)
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.rolesList.push(data.role);
                            this.addUserRole = data.role.name;
                            this.showAddRoleModal = false;
                            this.newRoleForm.name = '';
                            this.showToast(data.message, 'success');
                        } else {
                            this.showToast(data.message || 'Gagal membuat role', 'error');
                        }
                    } catch (e) {
                        this.showToast('Terjadi kesalahan jaringan', 'error');
                    } finally {
                        this.isSubmittingRole = false;
                    }
                },

                async submitNewLoket() {
                    if (!this.newLoketForm.counter_name) return;
                    this.isSubmittingLoket = true;
                    try {
                        const res = await fetch('/admin/api/lokets', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(this.newLoketForm)
                        });
                        const data = await res.json();
                        if (data.success) {
                            this.loketsList.push(data.loket);
                            this.addUserLoket = data.loket.id;
                            this.showAddLoketModal = false;
                            this.newLoketForm.counter_name = '';
                            this.showToast(data.message, 'success');
                        } else {
                            this.showToast(data.message || 'Gagal membuat loket', 'error');
                        }
                    } catch (e) {
                        this.showToast('Terjadi kesalahan jaringan', 'error');
                    } finally {
                        this.isSubmittingLoket = false;
                    }
                },

                toggleSelectAll() {
                    if (this.selectAll) {
                        const checkboxes = document.querySelectorAll('input[type="checkbox"][value]');
                        this.selectedRows = Array.from(checkboxes).map(cb => parseInt(cb.value));
                    } else {
                        this.selectedRows = [];
                    }
                },

                openEditModal(user) {
                    this.editForm.id = user.id;
                    this.editForm.name = user.name;
                    this.editForm.email = user.email;
                    this.editForm.role = user.role || '';
                    this.editForm.service_setting_id = user.service_setting_id || '';
                    this.showEditModal = true;
                },

                confirmDelete(id, name) {
                    this.deleteUserId = id;
                    this.deleteUserName = name;
                    this.showDeleteModal = true;
                }
            }
        }
    </script>
</body>
</html>
