<!doctype html>
<html lang="id" class="h-full bg-slate-900">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Sign in — PT BPR DP TASPEN Admin Portal</title>
    
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
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background: radial-gradient(circle at top right, #1e3a8a 0%, #0f2b5c 40%, #040d1a 100%);
        }
    </style>
</head>
<body class="h-full flex flex-col justify-between items-center px-4 py-8 antialiased selection:bg-brand-gold selection:text-slate-900">

    <div class="w-full flex-grow flex items-center justify-center my-6">
        <div class="w-full max-w-md" x-data="{ showPassword: false, isSubmitting: false, showForgotModal: false }">
            
            <!-- Main Login Card -->
            <div class="bg-white/95 backdrop-blur-xl border border-slate-200/80 rounded-2xl shadow-2xl shadow-slate-950/40 p-8 sm:p-10 transition-all duration-300">
                
                <!-- TOP BRAND LOGO (Center) -->
                <div class="flex flex-col items-center justify-center mb-8">
                    <div class="bg-white p-4 px-8 rounded-2xl border border-slate-200 shadow-md mb-4 inline-flex items-center justify-center">
                        @include('components.bank-logo', ['size' => '56px'])
                    </div>
                    <h1 class="text-2xl font-extrabold text-slate-900 tracking-tight font-heading mt-2">Sign in</h1>
                    <p class="text-xs font-semibold text-slate-500 mt-1 text-center">Masuk ke Panel Administrasi & Operasional Perbankan</p>
                </div>

                <!-- SESSION STATUS MESSAGE -->
                @if (session('status'))
                    <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl p-4 text-xs font-semibold flex items-center gap-3">
                        <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ session('status') }}</span>
                    </div>
                @endif

                <!-- ERROR ALERT AREA -->
                @if (isset($errors) && $errors->any())
                    <div class="mb-6 bg-rose-50 border border-rose-200 text-rose-800 rounded-xl p-4 text-xs font-semibold flex flex-col gap-2">
                        <div class="flex items-center gap-2 font-bold text-rose-900">
                            <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span>Gagal Memproses Login</span>
                        </div>
                        <ul class="list-disc list-inside space-y-1 text-rose-700 pl-1">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <!-- AUTH FORM -->
                <form action="{{ route('admin.login.submit') }}" method="POST" @submit="isSubmitting = true" class="space-y-5">
                    @csrf

                    <!-- EMAIL INPUT -->
                    <div>
                        <label for="email" class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-2">
                            Email address <span class="text-rose-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 12a4 4 0 10-8 0 4 4 0 008 0zm0 0v1.5a2.5 2.5 0 005 0V12a9 9 0 10-9 9m4.5-1.206a8.959 8.959 0 01-4.5 1.207" />
                                </svg>
                            </div>
                            <input 
                                type="email" 
                                id="email" 
                                name="email" 
                                value="{{ old('email') }}" 
                                required 
                                autofocus 
                                autocomplete="username" 
                                placeholder="nama@bprdptaspen.co.id"
                                class="w-full pl-11 pr-4 py-3 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-900 focus:border-brand-900 transition-all"
                            />
                        </div>
                    </div>

                    <!-- PASSWORD INPUT WITH SHOW/HIDE TOGGLE -->
                    <div>
                        <div class="flex items-center justify-between mb-2">
                            <label for="password" class="block text-xs font-bold uppercase tracking-wider text-slate-700">
                                Password <span class="text-rose-500">*</span>
                            </label>
                            <button 
                                type="button" 
                                @click="showForgotModal = true" 
                                class="text-xs font-bold text-brand-900 hover:text-brand-800 hover:underline transition-all"
                            >
                                Forgot password?
                            </button>
                        </div>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                </svg>
                            </div>
                            <input 
                                :type="showPassword ? 'text' : 'password'" 
                                id="password" 
                                name="password" 
                                required 
                                minlength="8" 
                                autocomplete="current-password" 
                                placeholder="••••••••"
                                class="w-full pl-11 pr-12 py-3 bg-slate-50 border border-slate-300 rounded-xl text-sm font-medium text-slate-900 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-brand-900 focus:border-brand-900 transition-all"
                            />
                            
                            <!-- EYE TOGGLE ICON -->
                            <button 
                                type="button" 
                                @click="showPassword = !showPassword" 
                                tabIndex="-1"
                                class="absolute inset-y-0 right-0 pr-3.5 flex items-center text-slate-400 hover:text-slate-600 focus:outline-none"
                            >
                                <template x-if="!showPassword">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </template>
                                <template x-if="showPassword">
                                    <svg class="w-5 h-5 text-brand-900" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858-5.908a8.962 8.962 0 012.122-.363c4.478 0 8.268 2.943 9.542 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21M3 3l18 18" />
                                    </svg>
                                </template>
                            </button>
                        </div>
                    </div>

                    <!-- REMEMBER ME CHECKBOX -->
                    <div class="flex items-center justify-between pt-1">
                        <label class="inline-flex items-center cursor-pointer select-none">
                            <input 
                                type="checkbox" 
                                name="remember" 
                                {{ old('remember') ? 'checked' : '' }} 
                                class="w-4 h-4 text-brand-900 bg-slate-100 border-slate-300 rounded focus:ring-brand-900 focus:ring-2 transition cursor-pointer"
                            />
                            <span class="ml-2.5 text-xs font-semibold text-slate-700">Remember me</span>
                        </label>
                    </div>

                    <!-- SUBMIT BUTTON WITH LOADING SPINNER STATE -->
                    <button 
                        type="submit" 
                        :disabled="isSubmitting" 
                        class="w-full py-3.5 px-4 bg-brand-900 hover:bg-brand-950 text-white font-bold text-sm rounded-xl shadow-lg shadow-brand-900/30 active:scale-[0.99] transition-all flex items-center justify-center gap-2 cursor-pointer disabled:opacity-75 disabled:cursor-not-allowed"
                    >
                        <!-- Normal State Text -->
                        <span x-show="!isSubmitting">Sign in</span>

                        <!-- Loading State Spinner -->
                        <span x-show="isSubmitting" class="inline-flex items-center gap-2" style="display: none;">
                            <svg class="animate-spin h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Memproses Autentikasi...</span>
                        </span>
                    </button>
                </form>
            </div>

            <!-- FORGOT PASSWORD MODAL -->
            <div x-show="showForgotModal" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/70 backdrop-blur-sm" style="display: none;">
                <div @click.away="showForgotModal = false" class="bg-white rounded-2xl max-w-sm w-full p-6 shadow-2xl border border-slate-200">
                    <div class="text-center">
                        <div class="w-12 h-12 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center mx-auto mb-4 font-bold text-xl">🔑</div>
                        <h3 class="text-base font-extrabold text-slate-900 font-heading">Reset Kata Sandi Akun</h3>
                        <p class="text-xs text-slate-600 mt-2 leading-relaxed">
                            Untuk keamanan perbankan, pemulihan kata sandi dilakukan secara manual oleh <strong>Super Administrator</strong> atau <strong>IT Helpdesk PT BPR DP TASPEN</strong>.
                        </p>
                        <div class="mt-4 p-3 bg-slate-50 border border-slate-200 rounded-xl text-left font-mono text-xs text-slate-700 space-y-1">
                            <div>📧 Helpdesk: <strong>it@bprdptaspen.co.id</strong></div>
                            <div>📞 Ext. Internal: <strong>801 / 802</strong></div>
                        </div>
                    </div>
                    <button type="button" @click="showForgotModal = false" class="w-full mt-5 py-2.5 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs rounded-xl transition cursor-pointer">
                        Tutup
                    </button>
                </div>
            </div>

        </div>
    </div>

    <!-- FOOTER KECIL (Copyright & Version) -->
    <footer class="w-full text-center text-[11px] font-semibold text-slate-400/90 py-2 space-y-1 select-none">
        <div>PT BPR DP TASPEN © {{ date('Y') }} · Terdaftar & Diawasi OJK · Peserta LPS</div>
        <div class="text-slate-500/80">Sistem Antrean Bank Portal v1.0.0 Enterprise</div>
    </footer>

</body>
</html>
