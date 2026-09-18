<!doctype html>
<html lang="id" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard Operasional — PT BPR DP TASPEN</title>

    
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
      x-data="{ 
          darkMode: localStorage.getItem('theme') !== 'light',
          toggleTheme() {
              this.darkMode = !this.darkMode;
              localStorage.setItem('theme', this.darkMode ? 'dark' : 'light');
          }
      }"
      :class="darkMode ? 'bg-[#091322] text-slate-100' : 'bg-slate-100 text-slate-900'">

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
                    <!-- MENU 1: MENU USER -->
                    <a href="/admin/users" class="flex items-center gap-3 px-4 py-3 rounded-2xl font-bold text-xs transition"
                       :class="darkMode ? 'text-slate-300 hover:bg-slate-800/70 hover:text-white' : 'text-slate-700 hover:bg-slate-100 hover:text-slate-900'">
                        <svg class="w-5 h-5 text-brand-gold" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z" />
                        </svg>
                        <span class="text-sm font-extrabold font-heading">Menu User</span>
                    </a>

                    <!-- MENU 2: MENU ANTREAN (Active) -->
                    <a href="/admin" class="flex items-center gap-3 px-4 py-3 rounded-2xl font-bold text-xs bg-brand-900 text-white shadow-lg shadow-brand-900/40 border-l-4 border-brand-gold transition">
                        <svg class="w-5 h-5 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                        </svg>
                        <span class="text-sm font-extrabold font-heading">Menu Antrean</span>
                    </a>
                </nav>
            </div>

            <!-- Sidebar Footnote -->
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
                    <h2 class="text-xl font-extrabold tracking-tight font-heading" :class="darkMode ? 'text-white' : 'text-slate-900'">Dashboard Operasional Central</h2>
                    <span class="hidden sm:inline-block px-3 py-1 bg-brand-900/60 border border-brand-500/30 text-brand-100 text-xs font-bold rounded-full">
                        Sistem Perbankan Active
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
                                    <div class="font-bold text-slate-900">🔒 Audit Keamanan Sistem</div>
                                    <div class="text-slate-500 text-[11px] mt-0.5">Seluruh sesi operasional petugas berjalan normal.</div>
                                    <div class="text-[9px] text-slate-400 mt-1">2 menit yang lalu</div>
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
                                <div class="text-[10px] font-semibold text-brand-gold capitalize mt-1">{{ Auth::user()?->roles->first()?->name ?? 'Super Admin' }}</div>
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

            <!-- CONTAINER BODY -->
            <div class="p-6 sm:p-8 space-y-8 max-w-7xl w-full mx-auto">

                <!-- WELCOME BANNER -->
                <div class="bg-gradient-to-r from-brand-900 via-brand-950 to-[#040d1a] border border-brand-gold/30 rounded-3xl p-8 shadow-2xl relative overflow-hidden flex flex-col md:flex-row items-start md:items-center justify-between gap-6">
                    <div class="space-y-2 z-10">
                        <div class="inline-flex items-center gap-2 px-3 py-1 bg-brand-gold/20 text-brand-gold border border-brand-gold/30 rounded-full text-xs font-extrabold">
                            🏛️ PT BPR DP TASPEN — Official Admin Portal
                        </div>
                        <h1 class="text-2xl sm:text-3xl font-black text-white font-heading">
                            Selamat Datang, {{ Auth::user()?->name ?? 'Administrator' }}!
                        </h1>
                        <p class="text-xs sm:text-sm text-slate-300 max-w-2xl leading-relaxed">
                            Pusat kendali operasional antrean dan administrasi perbankan. Silakan pilih menu manajemen atau konsol petugas di bawah ini.
                        </p>
                    </div>

                    <div class="flex items-center gap-3 shrink-0 z-10">
                        <a href="/display" target="_blank" class="px-5 py-3 bg-brand-gold hover:bg-amber-400 text-slate-950 font-black text-xs rounded-xl shadow-lg transition transform hover:-translate-y-0.5">
                            📺 Layar TV Display ↗
                        </a>
                        <a href="/ambil-nomor" target="_blank" class="px-5 py-3 bg-slate-800 hover:bg-slate-700 text-white border border-slate-700 font-bold text-xs rounded-xl transition transform hover:-translate-y-0.5">
                            🎫 Kiosk Cetak Tiket ↗
                        </a>
                    </div>
                </div>

                <!-- OPERATIONAL MENU HUB GRID -->
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-black font-heading" :class="darkMode ? 'text-white' : 'text-slate-900'">Menu Operasional & Kendali Utama</h2>
                        <span class="text-xs font-bold" :class="darkMode ? 'text-slate-400' : 'text-slate-600'">Seluruh Menu Akses Terintegrasi</span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        
                        <!-- CARD 1: MANAJEMEN USER -->
                        <a href="/admin/users" class="group bg-white text-slate-900 rounded-3xl p-6 shadow-xl border border-slate-200 hover:shadow-2xl hover:border-brand-900 transition flex flex-col justify-between">
                            <div>
                                <div class="w-12 h-12 bg-brand-900 text-brand-gold rounded-2xl flex items-center justify-center text-2xl font-bold mb-4 shadow-md group-hover:scale-105 transition">
                                    👥
                                </div>
                                <h3 class="text-lg font-black text-slate-900 font-heading mb-2 group-hover:text-brand-900 transition">
                                    Manajemen User
                                </h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    Kelola akun administrator, petugas CS, Teller, penugasan loket, reset password, serta status aktif/nonaktif akun.
                                </p>
                            </div>
                            <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between text-xs font-extrabold text-brand-900">
                                <span>Buka Manajemen User</span>
                                <span class="group-hover:translate-x-1 transition">→</span>
                            </div>
                        </a>

                        <!-- CARD 2: KELOLA ROLE & HAK AKSES -->
                        <a href="/admin/roles" class="group bg-white text-slate-900 rounded-3xl p-6 shadow-xl border border-slate-200 hover:shadow-2xl hover:border-purple-600 transition flex flex-col justify-between">
                            <div>
                                <div class="w-12 h-12 bg-purple-900 text-purple-200 rounded-2xl flex items-center justify-center text-2xl font-bold mb-4 shadow-md group-hover:scale-105 transition">
                                    🛡️
                                </div>
                                <h3 class="text-lg font-black text-slate-900 font-heading mb-2 group-hover:text-purple-700 transition">
                                    Kelola Role & Hak Akses
                                </h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    Konfigurasi matriks hak akses otorisasi Spatie per modul (*Dashboard, User, Antrean, Loket*) untuk tiap role aktor perbankan.
                                </p>
                            </div>
                            <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between text-xs font-extrabold text-purple-700">
                                <span>Buka Kelola Role</span>
                                <span class="group-hover:translate-x-1 transition">→</span>
                            </div>
                        </a>

                        <!-- CARD 3: KELOLA LOKET LAYANAN -->
                        <a href="/admin/lokets" class="group bg-white text-slate-900 rounded-3xl p-6 shadow-xl border border-slate-200 hover:shadow-2xl hover:border-amber-500 transition flex flex-col justify-between">
                            <div>
                                <div class="w-12 h-12 bg-amber-500 text-slate-950 rounded-2xl flex items-center justify-center text-2xl font-bold mb-4 shadow-md group-hover:scale-105 transition">
                                    📌
                                </div>
                                <h3 class="text-lg font-black text-slate-900 font-heading mb-2 group-hover:text-amber-600 transition">
                                    Kelola Loket Layanan
                                </h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    Atur status operasional counter (Loket Buka/Tutup), penambahan counter baru, dan penugasan lokasi loket CS & Teller.
                                </p>
                            </div>
                            <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between text-xs font-extrabold text-amber-600">
                                <span>Buka Kelola Loket</span>
                                <span class="group-hover:translate-x-1 transition">→</span>
                            </div>
                        </a>

                        <!-- CARD 4: KONSOL PETUGAS CS -->
                        <a href="/dashboard/cs" class="group bg-white text-slate-900 rounded-3xl p-6 shadow-xl border border-slate-200 hover:shadow-2xl hover:border-emerald-600 transition flex flex-col justify-between">
                            <div>
                                <div class="w-12 h-12 bg-emerald-700 text-white rounded-2xl flex items-center justify-center text-2xl font-bold mb-4 shadow-md group-hover:scale-105 transition">
                                    🎧
                                </div>
                                <h3 class="text-lg font-black text-slate-900 font-heading mb-2 group-hover:text-emerald-700 transition">
                                    Konsol Petugas CS
                                </h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    Konsol operasional pemanggilan tiket antrean Customer Service (Pembukaan Rekening, Kredit Pensiun, dan Layanan Nasabah).
                                </p>
                            </div>
                            <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between text-xs font-extrabold text-emerald-700">
                                <span>Buka Konsol CS</span>
                                <span class="group-hover:translate-x-1 transition">→</span>
                            </div>
                        </a>

                        <!-- CARD 5: KONSOL PETUGAS TELLER -->
                        <a href="/dashboard/teller" class="group bg-white text-slate-900 rounded-3xl p-6 shadow-xl border border-slate-200 hover:shadow-2xl hover:border-sky-600 transition flex flex-col justify-between">
                            <div>
                                <div class="w-12 h-12 bg-sky-700 text-white rounded-2xl flex items-center justify-center text-2xl font-bold mb-4 shadow-md group-hover:scale-105 transition">
                                    💵
                                </div>
                                <h3 class="text-lg font-black text-slate-900 font-heading mb-2 group-hover:text-sky-700 transition">
                                    Konsol Petugas Teller
                                </h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    Konsol operasional pemanggilan tiket antrean Teller Kas (Setoran Tunai, Penarikan, dan Pembayaran Pensiun TASPEN).
                                </p>
                            </div>
                            <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between text-xs font-extrabold text-sky-700">
                                <span>Buka Konsol Teller</span>
                                <span class="group-hover:translate-x-1 transition">→</span>
                            </div>
                        </a>

                        <!-- CARD 6: LAYAR DISPLAY TV -->
                        <a href="/display" target="_blank" class="group bg-white text-slate-900 rounded-3xl p-6 shadow-xl border border-slate-200 hover:shadow-2xl hover:border-brand-900 transition flex flex-col justify-between">
                            <div>
                                <div class="w-12 h-12 bg-slate-900 text-brand-gold rounded-2xl flex items-center justify-center text-2xl font-bold mb-4 shadow-md group-hover:scale-105 transition">
                                    📺
                                </div>
                                <h3 class="text-lg font-black text-slate-900 font-heading mb-2 group-hover:text-brand-900 transition">
                                    Layar Display TV Public ↗
                                </h3>
                                <p class="text-xs text-slate-600 leading-relaxed">
                                    Tampilan layar utama TV monitor ruang tunggu nasabah yang menampilkan nomor panggilan aktif dan suara pemanggilan otomatis.
                                </p>
                            </div>
                            <div class="mt-6 pt-4 border-t border-slate-100 flex items-center justify-between text-xs font-extrabold text-brand-900">
                                <span>Buka Display TV</span>
                                <span class="group-hover:translate-x-1 transition">↗</span>
                            </div>
                        </a>

                    </div>
                </div>

    </div>

    <!-- LIVE REAL-TIME CLOCK SCRIPT -->
    <script>
        function liveClock() {
            return {
                timeString: '',
                dateString: '',
                init() {
                    this.updateClock();
                    setInterval(() => this.updateClock(), 1000);
                },
                updateClock() {
                    const now = new Date();
                    const h = String(now.getHours()).padStart(2, '0');
                    const m = String(now.getMinutes()).padStart(2, '0');
                    const s = String(now.getSeconds()).padStart(2, '0');
                    this.timeString = `${h}:${m}:${s} WIB`;

                    const days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
                    const months = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
                    
                    const dayName = days[now.getDay()];
                    const dayNum = now.getDate();
                    const monthName = months[now.getMonth()];
                    const yearNum = now.getFullYear();

                    this.dateString = `${dayName}, ${dayNum} ${monthName} ${yearNum}`;
                }
            }
        }
    </script>
</body>
</html>
