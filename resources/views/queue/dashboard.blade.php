<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Operator Console {{ $title }} - PT BPR DP TASPEN</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Outfit:wght@500;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background: radial-gradient(circle at top right, #1e3a8a 0%, #0f2b5c 40%, #040d1a 100%);
            color: #f8fafc;
            min-height: 100vh;
        }

        /* Top Navigation Header */
        nav.navbar {
            background: rgba(2, 18, 44, 0.8);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding: 16px 36px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: sticky;
            top: 0;
            z-index: 50;
        }
        .nav-left { display: flex; align-items: center; gap: 20px; }
        .nav-brand {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: white;
        }
        .brand-icon {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #002B49, #0072CE);
            border: 1.5px solid rgba(212, 175, 55, 0.6);
            border-radius: 12px;
            display: grid;
            place-items: center;
            box-shadow: 0 0 15px rgba(0, 114, 206, 0.4);
        }
        .brand-icon svg { width: 22px; height: 22px; fill: #D4AF37; }
        .brand-title { font-family: 'Outfit', sans-serif; font-weight: 900; font-size: 18px; color: #ffffff; }
        .brand-title span { color: #F59E0B; }

        .counter-pill {
            background: rgba(0, 114, 206, 0.15);
            border: 1px solid rgba(0, 114, 206, 0.3);
            color: #38bdf8;
            padding: 5px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .nav-right { display: flex; align-items: center; gap: 20px; }
        .user-profile {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .avatar {
            width: 38px;
            height: 38px;
            border-radius: 50%;
            background: linear-gradient(135deg, #002B49, #0072CE);
            border: 1px solid #D4AF37;
            display: grid;
            place-items: center;
            font-weight: 800;
            color: #D4AF37;
            font-size: 14px;
        }
        .user-details { text-align: right; }
        .user-name { font-size: 14px; font-weight: 700; color: #f1f5f9; }
        .user-role { font-size: 11px; color: #94a3b8; text-transform: uppercase; font-weight: 600; }

        .nav-actions { display: flex; align-items: center; gap: 10px; }
        .nav-btn {
            padding: 8px 16px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            color: #cbd5e1;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            transition: all 0.2s ease;
        }
        .nav-btn:hover { background: rgba(255, 255, 255, 0.15); color: white; }
        .btn-logout {
            background: rgba(239, 68, 68, 0.15);
            border-color: rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            border: none;
            cursor: pointer;
        }
        .btn-logout:hover { background: rgba(239, 68, 68, 0.3); color: white; }

        /* Main Container Layout */
        main.wrap {
            max-width: 1240px;
            margin: 0 auto;
            padding: 32px 24px;
        }

        .alert-toast {
            background: rgba(34, 197, 94, 0.15);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #4ade80;
            padding: 14px 20px;
            border-radius: 16px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 28px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        /* Metrics Cards Row */
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 32px;
        }
        .metric-card {
            background: linear-gradient(145deg, rgba(15, 23, 42, 0.6), rgba(2, 18, 44, 0.8));
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 20px;
            padding: 20px 24px;
        }
        .metric-title { font-size: 12px; text-transform: uppercase; color: #64748b; font-weight: 700; letter-spacing: 0.5px; }
        .metric-val { font-family: 'Outfit', sans-serif; font-size: 28px; font-weight: 800; color: #f8fafc; margin-top: 4px; }

        /* Main Grid: Call Console + Queue List */
        .main-grid {
            display: grid;
            grid-template-columns: 1.3fr 0.7fr;
            gap: 28px;
        }

        .console-card {
            background: linear-gradient(145deg, rgba(15, 23, 42, 0.7), rgba(2, 18, 44, 0.9));
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 28px;
            padding: 36px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
        }

        .console-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }
        .console-header h2 { font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 800; }
        .badge-status {
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }
        .badge-calling { background: rgba(56, 189, 248, 0.15); color: #38bdf8; border: 1px solid rgba(56, 189, 248, 0.3); }

        .active-call-display {
            background: rgba(2, 9, 22, 0.95);
            border: 2px solid rgba(245, 158, 11, 0.3);
            border-radius: 24px;
            padding: 36px;
            text-align: center;
            margin-bottom: 28px;
            position: relative;
        }
        .call-label { font-size: 12px; text-transform: uppercase; letter-spacing: 2px; color: #fbbf24; font-weight: 700; margin-bottom: 8px; }
        .call-number {
            font-family: 'Outfit', sans-serif;
            font-size: 64px;
            font-weight: 900;
            color: #38bdf8;
            letter-spacing: 2px;
            text-shadow: 0 0 30px rgba(56, 189, 248, 0.4);
            line-height: 1;
            margin-bottom: 16px;
        }
        
        /* Service Timer Counter */
        .timer-box {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(255, 255, 255, 0.05);
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            color: #94a3b8;
            font-weight: 600;
        }
        .timer-val { color: #f43f5e; font-weight: 800; font-family: monospace; }

        /* Control Action Buttons */
        .controls-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .btn-ctrl {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 16px;
            font-family: 'Outfit', sans-serif;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .btn-next {
            background: linear-gradient(135deg, #002B49, #0072CE);
            color: white;
            box-shadow: 0 10px 25px -5px rgba(0, 114, 206, 0.4);
            grid-column: span 2;
            padding: 20px;
            font-size: 17px;
        }
        .btn-next:hover { transform: translateY(-2px); box-shadow: 0 15px 30px -5px rgba(0, 114, 206, 0.6); }

        .btn-recall {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #f1f5f9;
        }
        .btn-recall:hover { background: rgba(255, 255, 255, 0.18); color: white; }

        .btn-complete {
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
            box-shadow: 0 10px 25px -5px rgba(34, 197, 94, 0.4);
        }
        .btn-complete:hover { transform: translateY(-2px); box-shadow: 0 15px 30px -5px rgba(34, 197, 94, 0.6); }

        .btn-skip {
            background: rgba(239, 68, 68, 0.15);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
        }
        .btn-skip:hover { background: rgba(239, 68, 68, 0.35); color: white; }

        /* Sidebar Panels */
        .sidebar-stack {
            display: flex;
            flex-direction: column;
            gap: 24px;
        }
        .panel-card {
            background: linear-gradient(145deg, rgba(15, 23, 42, 0.6), rgba(2, 18, 44, 0.8));
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 24px;
            padding: 24px;
        }
        .panel-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 18px;
            padding-bottom: 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }
        .panel-title { font-family: 'Outfit', sans-serif; font-size: 16px; font-weight: 700; }
        .panel-chip {
            background: rgba(0, 114, 206, 0.2);
            color: #38bdf8;
            padding: 2px 10px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 700;
        }

        .list-group { list-style: none; }
        .list-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            background: rgba(15, 23, 42, 0.4);
            border-radius: 12px;
            margin-bottom: 8px;
            border: 1px solid rgba(255, 255, 255, 0.03);
        }
        .list-row-num { font-weight: 700; color: #f8fafc; font-size: 15px; }
        .list-row-sub { font-size: 12px; color: #64748b; }

        @media (max-width: 1024px) {
            .metrics-grid { grid-template-columns: 1fr 1fr; }
            .main-grid { grid-template-columns: 1fr; }
            nav.navbar { padding: 16px 20px; }
        }
    </style>
</head>
<body>
    <nav class="navbar">
        <div class="nav-left">
            <a href="/" class="nav-brand" style="background: #ffffff; padding: 6px 14px; border-radius: 12px; border: 1.5px solid #eab308; display: inline-flex; align-items: center; text-decoration: none;">
                @include('components.bank-logo', ['size' => '22px'])
            </a>
            <div class="counter-pill">
                <span>●</span>
                <span>Konsol Petugas {{ $title }}</span>
            </div>
        </div>

        <div class="nav-right">
            <div class="user-profile">
                <div class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</div>
                <div class="user-details">
                    <div class="user-name">{{ auth()->user()->name }}</div>
                    <div class="user-role">Petugas {{ auth()->user()->role }}</div>
                </div>
            </div>

            <div class="nav-actions">
                <a href="/display" target="_blank" class="nav-btn">Layar Display ↗</a>
                @if(auth()->user()->role === 'admin')
                    <a href="/admin" class="nav-btn">Admin Panel</a>
                @endif
                <form method="POST" action="{{ route('logout') }}" style="display:inline">
                    @csrf
                    <button type="submit" class="nav-btn btn-logout">Keluar</button>
                </form>
            </div>
        </div>
    </nav>

    <main class="wrap">
        @if(session('status'))
            <div class="alert-toast">
                <span>✓</span> {{ session('status') }}
            </div>
        @endif

        <!-- Metrics Overview Row -->
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-title">Menunggu Dalam Antrean</div>
                <div class="metric-val" style="color: #38bdf8;">{{ $waitingCount }}</div>
            </div>
            <div class="metric-card">
                <div class="metric-title">Status Loket Operasional</div>
                <div class="metric-val" style="color: #4ade80;">ONLINE</div>
            </div>
            <div class="metric-card">
                <div class="metric-title">Riwayat Selesai/Lewati</div>
                <div class="metric-val" style="color: #fbbf24;">{{ count($recentQueues) }}</div>
            </div>
            <div class="metric-card">
                <div class="metric-title">Waktu Sistem</div>
                <div id="liveTime" class="metric-val" style="font-size: 22px;">00:00:00</div>
            </div>
        </div>

        <!-- Main Call Console Grid -->
        <div class="main-grid">
            <div class="console-card">
                <div class="console-header">
                    <h2>Konsol Panggilan Aktif {{ $title }}</h2>
                    <span class="badge-status badge-calling">
                        {{ $currentQueue ? '● Melayani Nasabah' : '○ Standby' }}
                    </span>
                </div>

                <div class="active-call-display">
                    <div class="call-label">Nomor Antrean Dipanggil</div>
                    <div class="call-number">{{ $currentQueue?->number ?? '-' }}</div>

                    @if($currentQueue)
                        <div class="timer-box">
                            <span>Durasi Transaksi:</span>
                            <span id="elapsedTimer" class="timer-val">00:00</span>
                        </div>
                    @endif
                </div>

                <div class="controls-grid">
                    <form method="POST" action="{{ $service==='cs'?route('queue.next.cs'):route('queue.next.teller') }}" style="grid-column: span 2;">
                        @csrf
                        <button type="submit" class="btn-ctrl btn-next">
                            <span>PANGGIL ANTREAN BERIKUTNYA</span>
                            <span>→</span>
                        </button>
                    </form>

                    <form method="POST" action="{{ $service==='cs'?route('queue.recall.cs'):route('queue.recall.teller') }}">
                        @csrf
                        <button type="submit" class="btn-ctrl btn-recall">
                            <span>🔊 Panggil Ulang</span>
                        </button>
                    </form>

                    <form method="POST" action="{{ $service==='cs'?route('queue.complete.cs'):route('queue.complete.teller') }}">
                        @csrf
                        <button type="submit" class="btn-ctrl btn-complete">
                            <span>✓ Selesai Dilayani</span>
                        </button>
                    </form>

                    <form method="POST" action="{{ $service==='cs'?route('queue.skip.cs'):route('queue.skip.teller') }}" style="grid-column: span 2;">
                        @csrf
                        <button type="submit" class="btn-ctrl btn-skip">
                            <span>✕ Lewati Antrean Ini</span>
                        </button>
                    </form>
                </div>
            </div>

            <!-- Sidebar Lists -->
            <div class="sidebar-stack">
                <div class="panel-card">
                    <div class="panel-header">
                        <div class="panel-title">Daftar Menunggu {{ $title }}</div>
                        <span class="panel-chip">{{ count($waitingQueues) }} Teratas</span>
                    </div>
                    <ul class="list-group">
                        @forelse($waitingQueues as $queue)
                            <li class="list-row">
                                <span class="list-row-num">{{ $queue->number }}</span>
                                <span class="list-row-sub">Masuk {{ $queue->created_at->format('H:i') }}</span>
                            </li>
                        @empty
                            <li class="list-row" style="color: #64748b; justify-content: center;">Tidak ada antrean menunggu.</li>
                        @endforelse
                    </ul>
                </div>

                <div class="panel-card">
                    <div class="panel-header">
                        <div class="panel-title">Riwayat Panggilan Petugas</div>
                    </div>
                    <ul class="list-group">
                        @forelse($recentQueues as $queue)
                            <li class="list-row">
                                <span class="list-row-num">{{ $queue->number }}</span>
                                <span class="list-row-sub" style="color: {{ $queue->status === 'completed' ? '#4ade80' : '#fca5a5' }};">
                                    {{ $queue->status === 'completed' ? 'Selesai' : 'Dilewati' }}
                                </span>
                            </li>
                        @empty
                            <li class="list-row" style="color: #64748b; justify-content: center;">Belum ada riwayat.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </main>

    <script>
        function updateTime() {
            const now = new Date();
            const liveTimeElem = document.getElementById('liveTime');
            if (liveTimeElem) {
                liveTimeElem.textContent = now.toLocaleTimeString('id-ID', { hour12: false });
            }
        }
        setInterval(updateTime, 1000);
        updateTime();

        @if($currentQueue && $currentQueue->called_at)
            const calledTime = new Date("{{ $currentQueue->called_at->toIso8601String() }}").getTime();
            function updateTimer() {
                const now = new Date().getTime();
                const diffSec = Math.floor((now - calledTime) / 1000);
                const mins = String(Math.floor(diffSec / 60)).padStart(2, '0');
                const secs = String(diffSec % 60).padStart(2, '0');
                const timerElem = document.getElementById('elapsedTimer');
                if (timerElem) timerElem.textContent = `${mins}:${secs}`;
            }
            setInterval(updateTimer, 1000);
            updateTimer();
        @endif

        // Real-time Queue Polling: Auto-updates when new ticket is taken or queue changes
        let lastChecksum = null;
        function pollQueueUpdates() {
            fetch('/api/display/data')
                .then(response => response.json())
                .then(data => {
                    const currentChecksum = JSON.stringify({
                        csWaiting: data.cs?.waiting ?? 0,
                        csCalled: data.cs?.called ?? null,
                        tellerWaiting: data.teller?.waiting ?? 0,
                        tellerCalled: data.teller?.called ?? null,
                        allCalled: data.all_called?.length ?? 0
                    });
                    if (lastChecksum !== null && lastChecksum !== currentChecksum) {
                        window.location.reload();
                    }
                    lastChecksum = currentChecksum;
                })
                .catch(() => {});
        }
        setInterval(pollQueueUpdates, 2500);
        pollQueueUpdates();
    </script>
</body>
</html>
