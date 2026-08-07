<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kiosk Antrean Tiket - PT BPR DP TASPEN</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Outfit:wght@400;600;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background: radial-gradient(circle at top right, #1e3a8a 0%, #0f2b5c 40%, #040d1a 100%);
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            overflow-y: auto;
            overflow-x: auto;
            position: relative;
            transition: font-size 0.2s ease;
        }

        /* TASPEN Corporate Glow Backgrounds */
        .ambient-glow-1 {
            position: absolute;
            top: -10%;
            left: -10%;
            width: 55vw;
            height: 55vw;
            background: radial-gradient(circle, rgba(0, 114, 206, 0.2) 0%, rgba(0, 0, 0, 0) 70%);
            pointer-events: none;
            z-index: 0;
        }
        .ambient-glow-2 {
            position: absolute;
            bottom: -10%;
            right: -10%;
            width: 55vw;
            height: 55vw;
            background: radial-gradient(circle, rgba(212, 175, 55, 0.15) 0%, rgba(0, 0, 0, 0) 70%);
            pointer-events: none;
            z-index: 0;
        }

        header.kiosk-header {
            position: relative;
            z-index: 10;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 12px 32px;
            background: rgba(2, 18, 44, 0.7);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .brand-info h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 20px;
            font-weight: 900;
            letter-spacing: -0.5px;
            color: #ffffff;
        }
        .brand-info p { font-size: 12px; color: #cbd5e1; font-weight: 600; margin-top: 1px; }

        .header-meta {
            display: flex;
            align-items: center;
            gap: 16px;
        }

        .btn-senior-mode {
            background: rgba(245, 158, 11, 0.15);
            border: 1px solid rgba(245, 158, 11, 0.4);
            color: #fbbf24;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-senior-mode:hover {
            background: rgba(245, 158, 11, 0.3);
            transform: scale(1.03);
        }
        .btn-senior-mode.active {
            background: #f59e0b;
            color: #02122c;
            border-color: #f59e0b;
        }

        .status-chip {
            display: flex;
            align-items: center;
            gap: 6px;
            background: rgba(34, 197, 94, 0.12);
            border: 1px solid rgba(34, 197, 94, 0.3);
            color: #4ade80;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }
        .status-dot { width: 7px; height: 7px; background: #4ade80; border-radius: 50%; box-shadow: 0 0 8px #4ade80; }

        .clock-display { text-align: right; }
        .clock-time { font-family: 'Outfit', sans-serif; font-size: 20px; font-weight: 700; color: #38bdf8; font-variant-numeric: tabular-nums; }
        .clock-date { font-size: 11px; color: #64748b; }

        main.kiosk-content {
            position: relative;
            z-index: 10;
            max-width: 1180px;
            margin: 0 auto;
            width: 100%;
            padding: 12px 24px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .kiosk-title {
            text-align: center;
            margin-bottom: 12px;
        }
        .kiosk-title h2 {
            font-family: 'Outfit', sans-serif;
            font-size: 26px;
            font-weight: 800;
            margin-bottom: 4px;
            color: #f8fafc;
        }
        .kiosk-title p {
            font-size: 14px;
            color: #94a3b8;
            max-width: 650px;
            margin: 0 auto;
        }

        .services-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
        }

        .service-card {
            background: linear-gradient(145deg, rgba(15, 23, 42, 0.8) 0%, rgba(2, 18, 44, 0.9) 100%);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 20px;
            padding: 20px 24px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4);
        }
        .service-card:hover:not(.disabled) {
            transform: translateY(-4px);
            border-color: rgba(245, 158, 11, 0.5);
            box-shadow: 0 20px 40px -15px rgba(0, 114, 206, 0.3);
        }
        .service-card.disabled {
            opacity: 0.6;
            filter: grayscale(0.4);
            border-color: rgba(239, 68, 68, 0.2);
        }

        .card-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        .service-icon {
            width: 44px;
            height: 44px;
            border-radius: 14px;
            display: grid;
            place-items: center;
        }
        .icon-teller { background: linear-gradient(135deg, rgba(14, 165, 233, 0.2), rgba(56, 189, 248, 0.05)); border: 1px solid rgba(56, 189, 248, 0.3); color: #38bdf8; }
        .icon-cs { background: linear-gradient(135deg, rgba(245, 158, 11, 0.2), rgba(251, 191, 36, 0.05)); border: 1px solid rgba(245, 158, 11, 0.3); color: #fbbf24; }
        .service-icon svg { width: 24px; height: 24px; fill: currentColor; }

        .service-status-tag {
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .tag-active { background: rgba(34, 197, 94, 0.15); color: #4ade80; border: 1px solid rgba(34, 197, 94, 0.3); }
        .tag-inactive { background: rgba(239, 68, 68, 0.15); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.3); }

        .service-body h3 {
            font-family: 'Outfit', sans-serif;
            font-size: 20px;
            font-weight: 800;
            margin-bottom: 4px;
            color: #f8fafc;
        }
        .service-body p {
            font-size: 12.5px;
            color: #94a3b8;
            line-height: 1.45;
            margin-bottom: 10px;
        }

        .service-stats {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 8px 14px;
            background: rgba(15, 23, 42, 0.7);
            border-radius: 12px;
            margin-bottom: 10px;
            border: 1px solid rgba(255, 255, 255, 0.05);
        }
        .stat-item {
            display: flex;
            flex-direction: column;
            flex: 1;
        }
        .stat-label { font-size: 10px; text-transform: uppercase; color: #64748b; font-weight: 600; letter-spacing: 0.5px; }
        .stat-value { font-size: 13.5px; font-weight: 700; color: #e2e8f0; margin-top: 1px; }

        .next-ticket-preview {
            display: flex;
            align-items: center;
            justify-content: space-between;
            background: rgba(2, 18, 44, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 8px 14px;
            border-radius: 12px;
            margin-bottom: 10px;
            font-size: 12px;
            color: #cbd5e1;
        }
        .next-ticket-preview strong {
            font-family: 'Outfit', sans-serif;
            font-size: 16px;
            font-weight: 800;
            color: #ffffff;
        }

        .closed-notice {
            background: rgba(239, 68, 68, 0.12);
            border: 1px solid rgba(239, 68, 68, 0.3);
            color: #fca5a5;
            padding: 8px;
            border-radius: 10px;
            margin-bottom: 8px;
            text-align: center;
            font-size: 12px;
            font-weight: 600;
        }

        .btn-take {
            width: 100%;
            padding: 14px;
            border: none;
            border-radius: 14px;
            font-family: 'Outfit', sans-serif;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }
        .btn-teller { background: linear-gradient(135deg, #0284c7 0%, #0369a1 100%); color: white; box-shadow: 0 8px 20px -5px rgba(2, 132, 199, 0.4); }
        .btn-cs { background: linear-gradient(135deg, #d97706 0%, #b45309 100%); color: white; box-shadow: 0 8px 20px -5px rgba(217, 119, 6, 0.4); }
        .btn-take:hover:not(:disabled) {
            transform: scale(1.02);
            filter: brightness(1.1);
        }
        .btn-take:disabled { background: #334155; color: #64748b; box-shadow: none; cursor: not-allowed; opacity: 0.6; }

        /* Senior Accessibility Text Expansion */
        body.senior-mode .brand-info p { font-size: 14px !important; }
        body.senior-mode .kiosk-title h2 { font-size: 32px !important; }
        body.senior-mode .kiosk-title p { font-size: 16px !important; }
        body.senior-mode .service-body h3 { font-size: 24px !important; }
        body.senior-mode .service-body p { font-size: 14px !important; }
        body.senior-mode .stat-label { font-size: 11px !important; }
        body.senior-mode .stat-value { font-size: 16px !important; }
        body.senior-mode .next-ticket-preview { font-size: 14px !important; }
        body.senior-mode .next-ticket-preview strong { font-size: 18px !important; }
        body.senior-mode .btn-take { font-size: 18px !important; padding: 18px !important; }
        body.senior-mode .kiosk-footer { font-size: 13px !important; }

        /* Ticket Thermal Paper Modal View */
        .ticket-modal-overlay {
            position: fixed;
            inset: 0;
            z-index: 100;
            background: rgba(2, 11, 24, 0.92);
            backdrop-filter: blur(12px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            overflow-y: auto;
            animation: fadeIn 0.3s ease;
        }
        @media (max-height: 800px) {
            .ticket-modal-overlay {
                align-items: flex-start;
            }
        }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        .receipt-container {
            width: min(440px, 100%);
            margin: auto;
            animation: slideDown 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        @keyframes slideDown { from { transform: translateY(-30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        .receipt-paper {
            background: #ffffff;
            color: #0f172a;
            padding: 32px 28px;
            border-radius: 20px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.7);
            text-align: center;
            position: relative;
            font-family: 'Plus Jakarta Sans', sans-serif;
        }

        .receipt-header { margin-bottom: 20px; border-bottom: 2px dashed #cbd5e1; padding-bottom: 16px; }
        .receipt-bank-name { font-family: 'Outfit', sans-serif; font-weight: 900; font-size: 21px; color: #002B49; letter-spacing: -0.5px; }
        .receipt-subtitle { font-size: 11px; color: #64748b; margin-top: 3px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }

        .receipt-ticket-label { font-size: 12px; text-transform: uppercase; color: #64748b; font-weight: 700; margin-bottom: 4px; }
        .receipt-ticket-number {
            font-family: 'Outfit', sans-serif;
            font-size: 52px;
            font-weight: 900;
            color: #002B49;
            letter-spacing: 2px;
            margin: 6px 0;
            line-height: 1;
        }
        .receipt-service-badge {
            display: inline-block;
            background: #f1f5f9;
            color: #0072CE;
            padding: 5px 14px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 800;
            margin-bottom: 16px;
        }

        .receipt-info {
            font-size: 12px;
            color: #64748b;
            border-top: 1px solid #f1f5f9;
            padding-top: 14px;
            margin-bottom: 16px;
            display: flex;
            justify-content: space-between;
        }

        .qr-placeholder {
            margin: 14px auto;
            width: 100px;
            height: 100px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 8px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .qr-placeholder svg { width: 100%; height: 100%; }

        .receipt-footer { font-size: 11px; color: #64748b; line-height: 1.4; font-weight: 500; }

        .modal-actions {
            margin-top: 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .btn-modal-print {
            padding: 16px;
            background: linear-gradient(135deg, #002B49, #0072CE);
            color: white;
            border: none;
            border-radius: 14px;
            font-family: 'Outfit', sans-serif;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 8px 20px -4px rgba(0, 114, 206, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-modal-print:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px -4px rgba(0, 114, 206, 0.5);
            filter: brightness(1.1);
        }
        .btn-modal-digital {
            padding: 16px;
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            color: white;
            border: none;
            border-radius: 14px;
            font-family: 'Outfit', sans-serif;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 8px 20px -4px rgba(16, 185, 129, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-modal-digital:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px -4px rgba(16, 185, 129, 0.5);
            filter: brightness(1.08);
        }
        .btn-modal-cancel {
            padding: 14px;
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #cbd5e1;
            border-radius: 14px;
            font-family: 'Outfit', sans-serif;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            display: block;
        }
        .btn-modal-cancel:hover {
            background: rgba(255, 255, 255, 0.15);
            color: #ffffff;
        }

        /* Thermal Printer POS Optimizations */
        @media print {
            body {
                background: #ffffff !important;
                color: #000000 !important;
            }
            .ambient-glow-1, .ambient-glow-2, header.kiosk-header, footer.kiosk-footer, main.kiosk-content, .modal-actions {
                display: none !important;
            }
            .ticket-modal-overlay {
                position: static !important;
                background: none !important;
                backdrop-filter: none !important;
                padding: 0 !important;
            }
            .receipt-container {
                width: 100% !important;
                max-width: 80mm !important;
                margin: 0 auto !important;
                animation: none !important;
            }
            .receipt-paper {
                box-shadow: none !important;
                border-radius: 0 !important;
                padding: 10px !important;
                border: none !important;
            }
            .receipt-bank-name, .receipt-ticket-number {
                color: #000000 !important;
            }
            .receipt-service-badge {
                background: #f1f5f9 !important;
                color: #000000 !important;
                border: 1px solid #cbd5e1 !important;
            }
        }

        footer.kiosk-footer {
            position: relative;
            z-index: 10;
            padding: 8px 32px;
            background: rgba(2, 18, 44, 0.7);
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 11px;
            color: #64748b;
            gap: 12px;
            flex-wrap: wrap;
        }
        .footer-info-item {
            display: flex;
            align-items: center;
            gap: 6px;
            color: #94a3b8;
            font-weight: 500;
        }
        .regulatory-badges {
            display: flex;
            align-items: center;
            gap: 16px;
            font-weight: 700;
            color: #94a3b8;
        }

        @media (max-width: 900px) {
            .services-grid { grid-template-columns: 1fr; }
            header.kiosk-header { padding: 16px 24px; }
            footer.kiosk-footer { padding: 16px 24px; flex-direction: column; gap: 10px; text-align: center; }
        }
    </style>
</head>
<body>
    <div class="ambient-glow-1"></div>
    <div class="ambient-glow-2"></div>

    <header class="kiosk-header">
        <div class="brand">
            <a href="/" style="background: #ffffff; padding: 10px 22px; border-radius: 14px; border: 1px solid #cbd5e1; box-shadow: 0 4px 12px rgba(15, 43, 92, 0.08); display: inline-flex; align-items: center; text-decoration: none;">
                @include('components.bank-logo', ['size' => '44px'])
            </a>
            <div class="brand-info">
                <p>Mitra Terpercaya Solusi Keuangan & Layanan Pensiunan TASPEN</p>
            </div>
        </div>

        <div class="header-meta">
            <button type="button" id="seniorToggleBtn" class="btn-senior-mode" onclick="toggleSeniorText()">
                🔍 Mode Lansia (Teks Besar)
            </button>
            <div class="status-chip">
                <span class="status-dot"></span>
                <span>Kiosk Layanan Aktif</span>
            </div>
            <div class="clock-display">
                <div id="clockTime" class="clock-time">00:00:00</div>
                <div id="clockDate" class="clock-date">-</div>
            </div>
        </div>
    </header>

    <main class="kiosk-content">
        <div class="kiosk-title">
            <h2>{{ __('app.welcome_kiosk') }}</h2>
            <p>{{ __('app.kiosk_instruction') }}</p>
        </div>

        @if(session('error'))
            <div style="background: rgba(239,68,68,0.15); border: 1px solid rgba(239,68,68,0.3); color: #fca5a5; padding: 14px 20px; border-radius: 16px; margin-bottom: 24px; text-align: center; font-weight: 600;">
                ⚠️ {{ session('error') }}
            </div>
        @endif

        @php
            $tellerActive = isset($settings['teller']) ? $settings['teller']->is_active : true;
            $csActive = isset($settings['cs']) ? $settings['cs']->is_active : true;
            $tellerCounterName = isset($settings['teller']) ? $settings['teller']->counter_name : 'Loket Teller';
            $csCounterName = isset($settings['cs']) ? $settings['cs']->counter_name : 'Loket CS';
        @endphp

        <form method="POST" action="{{ route('queue.store-number') }}" id="queueForm">
            @csrf
            <input type="hidden" name="service" id="serviceInput" value="">
            <div class="services-grid">
                <!-- Card Teller -->
                <div class="service-card {{ !$tellerActive ? 'disabled' : '' }}">
                    <div class="card-top">
                        <div class="service-icon icon-teller">
                            <svg viewBox="0 0 24 24"><path d="M21 18v1c0 1.1-.9 2-2 2H5c-1.11 0-2-.9-2-2V5c0-1.1.89-2 2-2h14c1.1 0 2 .9 2 2v1h-9c-1.11 0-2 .9-2 2v8c0 1.1.89 2 2 2h9zm-9-2h10V8H12v8zm4-2.5c-.83 0-1.5-.67-1.5-1.5s.67-1.5 1.5-1.5 1.5.67 1.5 1.5-.67 1.5-1.5 1.5z"/></svg>
                        </div>
                        <span class="service-status-tag {{ $tellerActive ? 'tag-active' : 'tag-inactive' }}">
                            {{ $tellerActive ? '● Loket Buka' : '○ Loket Tutup' }}
                        </span>
                    </div>
                    <div class="service-body">
                        <h3>Teller Kas & Pensiun TASPEN</h3>
                        <p>Pencairan Gaji Pensiun TASPEN, Setor / Tarik Tunai Tabungan BPR, Bayar Angsuran Kredit, & Pemindahbukuan.</p>
                    </div>

                    <div class="service-stats">
                        <div class="stat-item">
                            <span class="stat-label">Estimasi Waktu Tunggu</span>
                            <span class="stat-value" style="color: #38bdf8;">{{ $tellerEstWait }}</span>
                        </div>
                        <div class="stat-item" style="border-left: 1px solid rgba(255,255,255,0.08); padding-left: 16px;">
                            <span class="stat-label">Loket Tujuan</span>
                            <span class="stat-value" style="color: #fbbf24;">{{ $tellerCounterName }}</span>
                        </div>
                    </div>

                    <!-- 1. Preview Nomor Tiket Berikutnya -->
                    <div class="next-ticket-preview">
                        <span>Nomor Tiket Berikutnya:</span>
                        <strong style="color: #38bdf8;">{{ $nextTellerNumber }}</strong>
                    </div>

                    @if(!$tellerActive)
                        <div class="closed-notice">⚠️ Layanan sedang tidak tersedia saat ini.</div>
                    @endif

                    <button type="button" class="btn-take btn-teller" onclick="showConfirmModal('teller', 'Teller Kas & Pensiun TASPEN', '{{ $tellerCounterName }}', '{{ $tellerEstWait }}', '{{ $nextTellerNumber }}')" {{ !$tellerActive ? 'disabled' : '' }}>
                        <span>Ambil Tiket Teller</span>
                        <span>→</span>
                    </button>
                </div>

                <!-- Card Customer Service -->
                <div class="service-card {{ !$csActive ? 'disabled' : '' }}">
                    <div class="card-top">
                        <div class="service-icon icon-cs">
                            <svg viewBox="0 0 24 24" fill="currentColor">
                                <path d="M19 3H5c-1.11 0-2 .89-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.11-.9-2-2-2zm-2 10h-4v4h-2v-4H7v-2h4V7h2v4h4v2z"/>
                            </svg>
                        </div>
                        <span class="service-status-tag {{ $csActive ? 'tag-active' : 'tag-inactive' }}">
                            {{ $csActive ? '● Loket Buka' : '○ Loket Tutup' }}
                        </span>
                    </div>
                    <div class="service-body">
                        <h3>CS Kredit & Administrasi Pensiun</h3>
                        <p>Pengajuan Kredit Pensiun TASPEN & Kredit Usaha/Pegawai, Pembukaan Tabungan BPR & Deposito, Otentikasi Taspen.</p>
                    </div>

                    <div class="service-stats">
                        <div class="stat-item">
                            <span class="stat-label">Estimasi Waktu Tunggu</span>
                            <span class="stat-value" style="color: #fbbf24;">{{ $csEstWait }}</span>
                        </div>
                        <div class="stat-item" style="border-left: 1px solid rgba(255,255,255,0.08); padding-left: 16px;">
                            <span class="stat-label">Loket Tujuan</span>
                            <span class="stat-value" style="color: #4ade80;">{{ $csCounterName }}</span>
                        </div>
                    </div>

                    <!-- 1. Preview Nomor Tiket Berikutnya -->
                    <div class="next-ticket-preview">
                        <span>Nomor Tiket Berikutnya:</span>
                        <strong style="color: #fbbf24;">{{ $nextCsNumber }}</strong>
                    </div>

                    @if(!$csActive)
                        <div class="closed-notice">⚠️ Layanan sedang tidak tersedia saat ini.</div>
                    @endif

                    <button type="button" class="btn-take btn-cs" onclick="showConfirmModal('cs', 'CS Kredit & Administrasi Pensiun', '{{ $csCounterName }}', '{{ $csEstWait }}', '{{ $nextCsNumber }}')" {{ !$csActive ? 'disabled' : '' }}>
                        <span>Ambil Tiket CS</span>
                        <span>→</span>
                    </button>
                </div>
            </div>
        </form>
    </main>

    <!-- Pre-Confirmation Modal (No ticket in DB until user confirms) -->
    <div class="ticket-modal-overlay" id="confirmModalOverlay" style="display: none; z-index: 9999;">
        <div class="receipt-container" style="max-width: 440px;">
            <div class="receipt-paper" style="padding: 28px 24px; text-align: center;">
                <div style="margin-bottom: 12px; display: flex; justify-content: center; align-items: center;">
                    @include('components.bank-logo', ['height' => '48px'])
                </div>
                
                <h3 style="font-family: 'Outfit', sans-serif; font-size: 20px; font-weight: 900; color: #002B49; margin-bottom: 4px;">Konfirmasi Pengambilan Tiket</h3>
                <p style="font-size: 12.5px; color: #64748b; margin-bottom: 18px; font-weight: 600;">Apakah Anda yakin ingin mengambil nomor antrean ini?</p>

                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; padding: 16px; margin-bottom: 20px; text-align: left;">
                    <div style="font-size: 10.5px; text-transform: uppercase; color: #64748b; font-weight: 700; margin-bottom: 2px;">Layanan Dipilih:</div>
                    <div id="confirmServiceName" style="font-size: 15px; font-weight: 800; color: #002B49; font-family: 'Outfit', sans-serif; margin-bottom: 10px;">-</div>

                    <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px dashed #cbd5e1; padding-top: 10px;">
                        <div>
                            <div style="font-size: 10px; text-transform: uppercase; color: #64748b; font-weight: 700;">Loket Tujuan:</div>
                            <div id="confirmCounterName" style="font-size: 13px; font-weight: 800; color: #0072CE;">-</div>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 10px; text-transform: uppercase; color: #64748b; font-weight: 700;">Perkiraan Nomor:</div>
                            <div id="confirmNextNumber" style="font-size: 18px; font-weight: 900; color: #f59e0b; font-family: 'Outfit', sans-serif;">-</div>
                        </div>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <button type="button" onclick="submitQueueForm()" class="btn-modal-digital" style="width: 100%; justify-content: center; background: linear-gradient(135deg, #059669 0%, #10b981 100%); font-size: 15px; padding: 16px; font-weight: 800; border: none; cursor: pointer;">
                        ✅ Ya, Konfirmasi Ambil Tiket
                    </button>
                    <button type="button" onclick="closeConfirmModal()" class="btn-modal-cancel" style="width: 100%; justify-content: center; background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; font-weight: 700; cursor: pointer;">
                        ❌ Batal / Kembali ke Kiosk
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Ticket Thermal Print & Digital Modal -->
    @if(session('ticket'))
        <div class="ticket-modal-overlay" id="ticketModalOverlay">
            <div class="receipt-container">
                <div class="receipt-paper" id="receiptPaper">
                    <div class="receipt-header" style="margin-top: 8px;">
                        <div style="margin-bottom: 12px; display: flex; justify-content: center; align-items: center;">
                            @include('components.bank-logo', ['height' => '52px'])
                        </div>
                        <div style="font-size: 10px; color: #64748b; margin-top: 4px; line-height: 1.4;">Jl. Raya Pd. Gede No.9, Jatirahayu, Pd. Melati, Kota Bekasi 17414<br>Telp: 021-8467944</div>
                        <div class="receipt-subtitle" style="margin-top: 10px; background: #002B49; color: #ffffff; padding: 5px 14px; border-radius: 20px; display: inline-block; font-size: 10.5px; letter-spacing: 0.8px;">TIKET ANTREAN NASABAH</div>
                    </div>

                    <div class="receipt-ticket-label" style="font-size: 11px; tracking: 1px; color: #64748b;">NOMOR ANTREAN ANDA</div>
                    <div class="receipt-ticket-number" style="color: #002B49; text-shadow: 0 2px 4px rgba(0, 43, 73, 0.1);">{{ session('ticket') }}</div>
                    
                    @php
                        $isTellerTicket = str_starts_with(session('ticket'), 'T-');
                        $serviceName = $isTellerTicket ? 'Teller Kas & Pensiun TASPEN' : 'CS Kredit & Administrasi Pensiun';
                        $counterDest = $isTellerTicket ? $tellerCounterName : $csCounterName;
                    @endphp
                    <div class="receipt-service-badge" style="background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd;">{{ $serviceName }}</div>
                    <div style="font-size: 12.5px; font-weight: 800; color: #0072CE; margin-bottom: 14px;">Loket Tujuan: {{ $counterDest }}</div>

                    <!-- REAL FUNCTIONAL BARCODE & QR CODE BOX -->
                    <div class="barcode-qr-box" style="margin: 14px 0; padding: 14px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 16px; display: flex; flex-direction: column; align-items: center; gap: 10px;">
                        <!-- 1D Barcode CODE128 -->
                        <div style="width: 100%; display: flex; justify-content: center; overflow: hidden;">
                            <svg id="ticketBarcode" style="max-width: 100%; height: auto;"></svg>
                        </div>
                        
                        <!-- 2D QR Code + Detail -->
                        <div style="display: flex; align-items: center; justify-content: center; gap: 12px; width: 100%; border-top: 1px dashed #cbd5e1; padding-top: 10px; margin-top: 2px;">
                            <div id="ticketQrCode" style="padding: 6px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.04);"></div>
                            <div style="text-align: left;">
                                <div style="font-size: 10px; font-weight: 800; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px;">Verifikasi Keamanan Digital</div>
                                <div style="font-size: 13px; font-weight: 900; color: #002B49; font-family: 'Outfit', sans-serif;">{{ session('ticket') }}</div>
                                <div style="font-size: 9.5px; color: #94a3b8; margin-top: 1px;">Pindai untuk konfirmasi pemanggilan</div>
                            </div>
                        </div>
                    </div>

                    <div class="receipt-info">
                        <span>Waktu Pengambilan: {{ now()->format('d/m/Y H:i') }} WIB</span>
                        <span>{{ request('kiosk', session('kiosk_name', 'Kiosk #01')) }}</span>
                    </div>

                    <div class="receipt-footer">
                        Tiket Anda telah tersimpan. Mohon perhatikan layar monitor panggilan di ruang tunggu nasabah.<br>
                        📞 Call Center MAYA: <strong>150181</strong> · Terima kasih.
                    </div>
                </div>

                <!-- Modal Action Buttons: Digital Take Primary, Print Optional, Back -->
                <div class="modal-actions" id="modalActions">
                    <button type="button" class="btn-modal-digital" onclick="completeWithoutPrint()" style="font-size: 15px; padding: 16px; background: linear-gradient(135deg, #059669 0%, #10b981 100%);">
                        📱 Ambil Tiket Digital (Tanpa Cetak Kertas)
                    </button>
                    <button type="button" class="btn-modal-print" onclick="window.print()" style="background: rgba(255, 255, 255, 0.12); border: 1px solid rgba(255, 255, 255, 0.2); box-shadow: none; font-size: 14px; padding: 14px;">
                        🖨️ Cetak Struk Kertas (Opsional)
                    </button>
                    <button type="button" class="btn-modal-cancel" onclick="closeModal()">
                        🏠 Kembali ke Menu Utama
                    </button>
                </div>
            </div>
        </div>
    @endif

    <!-- Footer Informasi Tambahan -->
    <footer class="kiosk-footer">
        <div class="footer-info-item">
            <span>📍 <strong>Kantor Pusat Operasional:</strong> Jl. Raya Pd. Gede No.9, RT.002/RW.001, Jatirahayu, Kec. Pd. Melati, Kota Bekasi, Jawa Barat 17414</span>
        </div>
        <div class="footer-info-item">
            <span>📞 <strong>Call Center MAYA:</strong> <strong style="color: #fbbf24;">150181</strong> | ☎️ <strong>Telp:</strong> 021-8467944 | 🕒 <strong>Jam Operasional:</strong> Senin – Jumat (08.00 – 15.00 WIB)</span>
        </div>
        <div class="regulatory-badges">
            <span>🛡️ Terdaftar & Diawasi OJK</span>
            <span>|</span>
            <span>🏦 Peserta Penjaminan LPS</span>
        </div>
    </footer>

    <script>
        function updateClock() {
            const now = new Date();
            document.getElementById('clockTime').textContent = now.toLocaleTimeString('id-ID', { hour12: false });
            document.getElementById('clockDate').textContent = now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        }
        setInterval(updateClock, 1000);
        updateClock();

        // Toggle Mode Lansia (Teks Besar)
        function toggleSeniorText() {
            const body = document.body;
            const btn = document.getElementById('seniorToggleBtn');
            body.classList.toggle('senior-mode');
            
            if (body.classList.contains('senior-mode')) {
                btn.classList.add('active');
                btn.innerHTML = '🔍 Mode Normal (Teks Standar)';
                localStorage.setItem('kiosk_senior_mode', 'true');
            } else {
                btn.classList.remove('active');
                btn.innerHTML = '🔍 Mode Lansia (Teks Besar)';
                localStorage.setItem('kiosk_senior_mode', 'false');
            }
        }

        // Restore Senior Mode Preference
        if (localStorage.getItem('kiosk_senior_mode') === 'true') {
            document.body.classList.add('senior-mode');
            const btn = document.getElementById('seniorToggleBtn');
            if (btn) {
                btn.classList.add('active');
                btn.innerHTML = '🔍 Mode Normal (Teks Standar)';
            }
        }

        // Render Functional Barcode & QR Code for Ticket
        @if(session('ticket'))
            const ticketNum = "{{ session('ticket') }}";

            function renderBarcode() {
                const svg = document.getElementById("ticketBarcode");
                if (!svg) return;
                if (typeof JsBarcode === 'function') {
                    try {
                        JsBarcode(svg, ticketNum, {
                            format: "CODE128",
                            lineColor: "#002B49",
                            width: 2,
                            height: 44,
                            displayValue: true,
                            fontSize: 11,
                            fontOptions: "bold",
                            font: "Plus Jakarta Sans",
                            margin: 4
                        });
                        return;
                    } catch(e) { console.error(e); }
                }
                drawFallbackBarcode('ticketBarcode', ticketNum);
            }

            function renderQrCode() {
                const qrElem = document.getElementById("ticketQrCode");
                if (!qrElem) return;
                const verifyUrl = "{{ url('/verifikasi') }}/" + ticketNum;
                if (typeof QRCode === 'function') {
                    qrElem.innerHTML = '';
                    new QRCode(qrElem, {
                        text: verifyUrl,
                        width: 68,
                        height: 68,
                        colorDark: "#002B49",
                        colorLight: "#ffffff",
                        correctLevel: QRCode.CorrectLevel.H
                    });
                } else {
                    drawFallbackQrCode(qrElem, verifyUrl);
                }
            }

            // Fallback Code128 generator if offline
            function drawFallbackBarcode(svgId, text) {
                const svg = document.getElementById(svgId);
                if (!svg) return;
                
                const code128Patterns = [
                    "212222","222122","222221","121223","121322","131222","122213","122312","132212","221213",
                    "221312","231212","112232","122132","122231","113222","123122","123221","223211","221132",
                    "221231","213212","223112","312131","311222","321122","321221","312212","322112","322211",
                    "212123","212321","201121","111223","111322","131122","121123","121321","131221","112213",
                    "112312","132112","112213","132211","212212","212311","211123","211321","213121","211213"
                ];

                let patternStr = "211214";
                let checksum = 104;

                for (let i = 0; i < text.length; i++) {
                    const charCode = text.charCodeAt(i);
                    const val = charCode - 32;
                    if (val >= 0 && val < code128Patterns.length) {
                        patternStr += code128Patterns[val];
                        checksum += val * (i + 1);
                    }
                }

                const checkIndex = checksum % 103;
                patternStr += (code128Patterns[checkIndex] || "211214");
                patternStr += "2331112";

                let x = 10;
                let svgContent = '';
                let isBar = true;

                for (let i = 0; i < patternStr.length; i++) {
                    const width = parseInt(patternStr[i], 10) * 2;
                    if (isBar) {
                        svgContent += `<rect x="${x}" y="4" width="${width}" height="40" fill="#002B49" />`;
                    }
                    x += width;
                    isBar = !isBar;
                }

                svgContent += `<text x="${x/2}" y="56" text-anchor="middle" font-family="'Plus Jakarta Sans', sans-serif" font-size="11" font-weight="800" fill="#002B49">${text}</text>`;
                svg.setAttribute("viewBox", `0 0 ${x + 10} 64`);
                svg.innerHTML = svgContent;
            }

            // Fallback QR code generator if offline
            function drawFallbackQrCode(container, text) {
                container.innerHTML = `
                    <svg viewBox="0 0 68 68" width="68" height="68" fill="#002B49">
                        <rect x="0" y="0" width="20" height="20" rx="3"/>
                        <rect x="3.5" y="3.5" width="13" height="13" fill="#fff" rx="2"/>
                        <rect x="7" y="7" width="6" height="6" fill="#002B49" rx="1"/>
                        
                        <rect x="48" y="0" width="20" height="20" rx="3"/>
                        <rect x="51.5" y="3.5" width="13" height="13" fill="#fff" rx="2"/>
                        <rect x="55" y="7" width="6" height="6" fill="#002B49" rx="1"/>
                        
                        <rect x="0" y="48" width="20" height="20" rx="3"/>
                        <rect x="3.5" y="51.5" width="13" height="13" fill="#fff" rx="2"/>
                        <rect x="7" y="55" width="6" height="6" fill="#002B49" rx="1"/>
                        
                        <rect x="26" y="8" width="8" height="8" rx="1"/>
                        <rect x="30" y="28" width="12" height="12" rx="2"/>
                        <rect x="48" y="48" width="10" height="10" rx="1"/>
                        <rect x="28" y="48" width="12" height="6" rx="1"/>
                    </svg>
                `;
            }

            // Function to take ticket digitally without printing
            function completeWithoutPrint() {
                const modalPaper = document.getElementById('receiptPaper');
                const modalActions = document.getElementById('modalActions');
                
                if (modalPaper && modalActions) {
                    modalPaper.innerHTML = `
                        <div style="padding: 24px 12px; text-align: center; animation: fadeIn 0.3s ease;">
                            <div style="margin-bottom: 14px; display: flex; justify-content: center; align-items: center;">
                                @include('components.bank-logo', ['height' => '44px'])
                            </div>
                            <div style="width: 68px; height: 68px; background: #dcfce7; border: 2px solid #22c55e; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 16px auto; box-shadow: 0 10px 25px rgba(34, 197, 94, 0.25);">
                                <svg style="width: 36px; height: 36px; color: #16a34a;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7" />
                                </svg>
                            </div>
                            <h3 style="font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 900; color: #002B49; margin-bottom: 6px;">Tiket Digital Berhasil Terdaftar!</h3>
                            <p style="font-size: 13px; color: #64748b; margin-bottom: 16px; font-weight: 600;">
                                Nomor Antrean Resmi Anda:
                                <span style="display: block; font-family: 'Outfit', sans-serif; font-size: 46px; font-weight: 900; color: #0072CE; margin-top: 4px; letter-spacing: 1px; text-shadow: 0 2px 4px rgba(0,114,206,0.1);">${ticketNum}</span>
                            </p>
                            <div style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 14px; padding: 16px; font-size: 13px; color: #047857; font-weight: 800; margin-bottom: 8px; line-height: 1.6; text-align: center;">
                                Terima kasih.
                                <div style="font-size: 12.5px; font-weight: 600; color: #065f46; margin-top: 6px;">Tiket Anda telah tersimpan. Mohon perhatikan layar monitor panggilan di ruang tunggu nasabah.</div>
                            </div>
                        </div>
                    `;
                    
                    modalActions.innerHTML = `
                        <button type="button" onclick="closeModal()" class="btn-modal-digital" style="width: 100%; justify-content: center; background: linear-gradient(135deg, #059669 0%, #10b981 100%); font-size: 16px; padding: 16px;">
                            ✅ Selesai (Kembali ke Menu Utama Kiosk)
                        </button>
                    `;
                }
            }

            document.addEventListener("DOMContentLoaded", function() {
                setTimeout(function() {
                    renderBarcode();
                    renderQrCode();
                }, 100);
            });
        @endif

        let selectedServiceKey = '';

        function showConfirmModal(serviceKey, serviceLabel, counterName, waitTime, nextNumber) {
            selectedServiceKey = serviceKey;
            document.getElementById('confirmServiceName').textContent = serviceLabel;
            document.getElementById('confirmCounterName').textContent = counterName;
            document.getElementById('confirmNextNumber').textContent = nextNumber;
            
            const overlay = document.getElementById('confirmModalOverlay');
            if (overlay) {
                overlay.style.display = 'flex';
                overlay.style.opacity = '1';
            }
        }

        function closeConfirmModal() {
            const overlay = document.getElementById('confirmModalOverlay');
            if (overlay) {
                overlay.style.display = 'none';
            }
            selectedServiceKey = '';
        }

        function submitQueueForm() {
            if (!selectedServiceKey) return;
            
            const hiddenInput = document.getElementById('serviceInput');
            if (hiddenInput) {
                hiddenInput.value = selectedServiceKey;
            }
            
            closeConfirmModal();
            const form = document.getElementById('queueForm');
            if (form) {
                const btns = form.querySelectorAll('.btn-take');
                btns.forEach(btn => {
                    btn.disabled = true;
                    btn.style.opacity = '0.6';
                });
                form.submit();
            }
        }

        function closeModal() {
            const overlay = document.getElementById('ticketModalOverlay');
            if (overlay) {
                overlay.style.opacity = '0';
                setTimeout(() => overlay.remove(), 200);
            }
            if (window.history.replaceState) {
                window.history.replaceState(null, null, window.location.pathname);
            }
        }
    </script>
</body>
</html>
