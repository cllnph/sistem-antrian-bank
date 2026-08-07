<!doctype html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kelola Loket Layanan — PT BPR DP TASPEN</title>
    
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
      x-data="{ 
          darkMode: localStorage.getItem('theme') !== 'light',
          toggleTheme() {
              this.darkMode = !this.darkMode;
              localStorage.setItem('theme', this.darkMode ? 'dark' : 'light');
          },
          showAddLoketModal: false 
      }"
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
                    <h2 class="text-xl font-extrabold tracking-tight font-heading" :class="darkMode ? 'text-white' : 'text-slate-900'">Kelola Loket Layanan</h2>
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
                    <div class="bg-emerald-500/10 border border-emerald-500/30 text-emerald-600 rounded-2xl p-4 text-xs font-semibold">
                        {{ session('status') }}
                    </div>
                @endif

                <!-- HEADER ACTIONS -->
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h1 class="text-2xl font-black font-heading" :class="darkMode ? 'text-white' : 'text-slate-900'">Kelola Loket & Counter Layanan</h1>
                        <p class="text-xs font-medium mt-1" :class="darkMode ? 'text-slate-400' : 'text-slate-600'">Atur daftar loket CS & Teller, status operasional counter, serta penugasan petugas perbankan.</p>
                    </div>

                    <button @click="showAddLoketModal = true" class="inline-flex items-center justify-center gap-2 px-5 py-3 bg-brand-900 hover:bg-brand-950 text-white font-extrabold text-xs rounded-xl shadow-lg border border-brand-gold/30 cursor-pointer transition">
                        + Tambah Loket Baru
                    </button>
                </div>

                <!-- LOKET GRID CARD -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ($lokets as $loket)
                        <div class="bg-white text-slate-900 rounded-2xl p-6 shadow-xl border border-slate-200 flex flex-col justify-between">
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <span class="px-3 py-1 bg-slate-900 text-white font-black text-xs rounded-lg shadow-sm">
                                        📌 {{ $loket->counter_name }}
                                    </span>
                                    <span class="px-2.5 py-0.5 rounded-full text-[10px] font-extrabold {{ $loket->service === 'cs' ? 'bg-emerald-100 text-emerald-800' : 'bg-sky-100 text-sky-800' }}">
                                        {{ strtoupper($loket->service) }}
                                    </span>
                                </div>

                                <h3 class="text-sm font-bold text-slate-900 mb-1">{{ $loket->label ?? 'Loket Operasional' }}</h3>
                                <p class="text-xs text-slate-500 mb-4">
                                    Ditugaskan ke {{ $loket->users_count }} petugas perbankan aktif.
                                </p>
                            </div>

                            <div class="pt-4 border-t border-slate-100 flex items-center justify-between">
                                <form action="{{ route('admin.lokets.toggle-status', $loket->id) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="inline-flex items-center gap-2 text-xs font-bold cursor-pointer">
                                        <span class="w-3 h-3 rounded-full {{ $loket->is_active ? 'bg-emerald-500 animate-pulse' : 'bg-slate-300' }}"></span>
                                        <span class="{{ $loket->is_active ? 'text-emerald-700' : 'text-slate-400' }}">
                                            {{ $loket->is_active ? 'Loket Buka' : 'Loket Tutup' }}
                                        </span>
                                    </button>
                                </form>

                                <form action="{{ route('admin.lokets.destroy', $loket->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus loket \'{{ $loket->counter_name }}\'?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-3 py-1.5 bg-rose-50 hover:bg-rose-100 text-rose-600 font-bold text-xs rounded-xl cursor-pointer transition">
                                        🗑️ Hapus
                                    </button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>

            </div>
        </main>
    </div>

    <!-- MODAL TAMBAH LOKET -->
    <div x-show="showAddLoketModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm" style="display: none;">
        <div @click.away="showAddLoketModal = false" class="bg-white text-slate-900 rounded-2xl max-w-md w-full p-6 shadow-2xl border border-slate-200">
            <h3 class="text-lg font-black text-slate-900 font-heading mb-4">Tambah Loket Layanan Baru</h3>
            <form action="{{ route('admin.lokets.store') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Nama / Nomor Loket *</label>
                    <input type="text" name="counter_name" required placeholder="Contoh: Counter Teller 3 / Loket CS 2" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium outline-none focus:ring-2 focus:ring-brand-900">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Jenis Layanan *</label>
                    <select name="service" required class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-bold outline-none focus:ring-2 focus:ring-brand-900">
                        <option value="teller">Teller (Transaksi Kas)</option>
                        <option value="cs">Customer Service (Layanan Rekening)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1">Label Kiosk (Opsional)</label>
                    <input type="text" name="label" placeholder="Contoh: Layanan Teller Utama" class="w-full px-3 py-2 bg-slate-50 border border-slate-300 rounded-xl text-xs font-medium outline-none focus:ring-2 focus:ring-brand-900">
                </div>
                <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-100">
                    <button type="button" @click="showAddLoketModal = false" class="px-4 py-2 bg-slate-100 text-slate-700 font-bold text-xs rounded-xl">Batal</button>
                    <button type="submit" class="px-5 py-2 bg-brand-900 text-white font-bold text-xs rounded-xl shadow">Simpan Loket</button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>
