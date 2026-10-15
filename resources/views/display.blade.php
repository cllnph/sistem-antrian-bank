<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Layar Informasi Pemanggilan Antrean - PT BPR DP TASPEN</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html {
            width: 100%;
            height: 100%;
        }
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background: radial-gradient(circle at top right, #1e3a8a 0%, #0f2b5c 40%, #040d1a 100%);
            color: #0f172a;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow-x: hidden;
            overflow-y: auto;
        }

        /* Custom Sleek Scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }
        ::-webkit-scrollbar-track {
            background: rgba(7, 42, 94, 0.4);
        }
        ::-webkit-scrollbar-thumb {
            background: rgba(243, 179, 19, 0.6);
            border-radius: 4px;
        }
        /* Custom Bank DP Taspen Building Photo Background (Dark Corporate Mode with Building Visible) */
        .kiosk-background-layer {
            position: fixed;
            inset: 0;
            background-image: url('{{ asset('images/bg-ambil-nomor.png') }}');
            background-size: cover;
            background-position: center center;
            background-repeat: no-repeat;
            filter: brightness(0.68) contrast(1.12);
            z-index: 0;
        }
        .kiosk-background-overlay {
            position: fixed;
            inset: 0;
            background: linear-gradient(180deg, rgba(3, 16, 42, 0.52) 0%, rgba(7, 32, 75, 0.42) 45%, rgba(2, 10, 28, 0.62) 100%);
            z-index: 0;
        }

        /* Subtle Ambient Glows */
        .ambient-glow-1 {
            position: absolute;
            top: -10%;
            left: -10%;
            width: 55vw;
            height: 55vw;
            background: radial-gradient(circle, rgba(0, 114, 206, 0.15) 0%, rgba(0, 0, 0, 0) 70%);
            pointer-events: none;
            z-index: 0;
        }
        .ambient-glow-2 {
            position: absolute;
            bottom: -10%;
            right: -10%;
            width: 55vw;
            height: 55vw;
            background: radial-gradient(circle, rgba(243, 179, 19, 0.1) 0%, rgba(0, 0, 0, 0) 70%);
            pointer-events: none;
            z-index: 0;
        }

        /* General Icon Utility Classes (Blade Heroicons Component Styling) */
        .kiosk-icon {
            width: 15px;
            height: 15px;
            display: inline-block;
            vertical-align: middle;
            flex-shrink: 0;
        }
        .text-gold { color: #F3B313; }
        .text-emerald { color: #10b981; }
        .text-blue { color: #004B9C; }
        .text-amber { color: #f59e0b; }
        .officer-icon {
            width: 14px;
            height: 14px;
            color: #64748b;
            display: inline-block;
            vertical-align: middle;
            flex-shrink: 0;
        }

        /* Bank DP Taspen Ticker Marquee Bar */
        .ticker-bar {
            position: relative;
            z-index: 20;
            background: #001f3f;
            border-bottom: 2px solid #F3B313;
            color: #ffffff;
            font-size: 12px;
            padding: 5px 0;
            overflow: hidden;
            white-space: nowrap;
            display: flex;
            align-items: center;
        }
        .ticker-content {
            display: inline-block;
            animation: tickerAnimation 60s linear infinite;
        }
        .ticker-bar:hover .ticker-content {
            animation-play-state: paused;
        }
        .ticker-item {
            display: inline-flex;
            align-items: center;
            gap: 7px;
            margin-right: 44px;
            font-weight: 600;
        }
        .ticker-item strong {
            color: #F3B313;
            font-weight: 800;
        }
        @keyframes tickerAnimation {
            0% { transform: translateX(100vw); }
            100% { transform: translateX(-100%); }
        }

        /* Bank DP Taspen Clean Corporate Header */
        header.display-header {
            position: relative;
            z-index: 10;
            display: grid;
            grid-template-columns: minmax(280px, 1fr) minmax(0, auto) minmax(280px, 1fr);
            column-gap: 16px;
            align-items: center;
            padding: 12px 36px;
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(16px);
            border-bottom: 3px solid #F3B313;
            box-shadow: 0 4px 20px rgba(0, 20, 50, 0.12);
        }
        .brand {
            display: flex;
            align-items: center;
            gap: 16px;
            justify-self: start;
        }
        .brand-logo-wrap {
            display: inline-flex;
            align-items: center;
        }
        .brand-info {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .brand-tagline-wrap {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .brand-slogan-badge {
            background: #F3B313;
            color: #002B49;
            font-family: 'Plus Jakarta Sans', 'Inter', sans-serif;
            font-size: 12px;
            font-weight: 800;
            font-style: italic;
            padding: 3px 12px;
            border-radius: 8px;
            letter-spacing: -0.2px;
            box-shadow: 0 2px 8px rgba(243, 179, 19, 0.4);
            white-space: nowrap;
        }
        .brand-info p {
            font-size: 11.5px;
            color: #475569;
            font-weight: 600;
        }

        /* Header Center Title: Informasi Pemanggilan Antrean */
        .header-center-title {
            text-align: center;
            justify-self: center;
        }
        .display-header-title {
            font-family: 'Inter', sans-serif;
            font-size: clamp(26px, 2.5vw, 36px);
            font-weight: 900;
            color: #002B49;
            letter-spacing: -0.6px;
            margin: 0;
            line-height: 1.15;
            text-transform: uppercase;
            white-space: normal;
        }

        .header-meta {
            display: flex;
            align-items: center;
            gap: 16px;
            justify-self: end;
        }

        /* Audio Toggle Button with Blade Heroicon (Icon Only) */
        .audio-toggle {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #f8fafc;
            border: 1.5px solid #cbd5e1;
            color: #64748b;
            cursor: pointer;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0;
            flex-shrink: 0;
        }
        .audio-toggle:hover {
            background: #e2e8f0;
            border-color: #94a3b8;
            transform: scale(1.08);
        }
        .audio-toggle.active {
            background: #ecfdf5;
            color: #059669;
            border-color: #a7f3d0;
            box-shadow: 0 2px 10px rgba(16, 185, 129, 0.25);
        }
        .audio-toggle .kiosk-icon {
            width: 20px;
            height: 20px;
        }

        /* Clock Display */
        .clock-display { text-align: right; }
        .clock-time {
            font-family: 'Inter', sans-serif;
            font-size: 21px;
            font-weight: 800;
            color: #004B9C;
            font-variant-numeric: tabular-nums;
            line-height: 1.1;
        }
        .clock-date { font-size: 11px; color: #64748b; font-weight: 600; }

        /* Main Display Container */
        main.display-container {
            position: relative;
            z-index: 10;
            width: 100%;
            max-width: 1620px;
            margin: 0 auto;
            padding: 20px 32px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        /* Cards Grid - Modern Responsive Auto-wrapping */
        .display-grid {
            display: grid;
            gap: 24px;
            width: 100%;
            align-items: stretch;
            justify-content: center;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        }

        /* Responsive Layout Presets based on counter count */
        .display-grid.count-1 {
            grid-template-columns: minmax(340px, 580px);
            justify-content: center;
        }
        .display-grid.count-2 {
            grid-template-columns: repeat(auto-fit, minmax(360px, 560px));
            justify-content: center;
            gap: 32px;
        }
        .display-grid.count-3 {
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
        }
        .display-grid.count-4 {
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
        }
        .display-grid.count-5 {
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        }

        /* Large TV / Widescreen (>= 1300px) */
        @media (min-width: 1300px) {
            .display-grid.count-5 {
                grid-template-columns: repeat(5, 1fr);
            }
            .display-grid.count-4 {
                grid-template-columns: repeat(4, 1fr);
            }
            .display-grid.count-3 {
                grid-template-columns: repeat(3, 1fr);
            }
            .display-grid.count-2 {
                grid-template-columns: repeat(2, minmax(380px, 600px));
                justify-content: center;
            }
        }

        /* Clean White Surface Cards */
        .service-card {
            background: #ffffff;
            border-radius: 24px;
            padding: 26px 24px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 20px 45px -10px rgba(0, 30, 75, 0.32), 0 4px 12px rgba(0, 0, 0, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.95);
            border-top: 6px solid #004B9C;
        }
        .service-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 26px 55px -10px rgba(0, 75, 156, 0.42);
        }

        /* Calling Animation when a number is actively called (Highlight berkedip dan bersinar) */
        .service-card.is-calling {
            border-color: #F3B313 !important;
            border-top: 6px solid #F3B313 !important;
            animation: cardCallingGlow 1.2s ease-in-out infinite;
        }
        @keyframes cardCallingGlow {
            0%, 100% {
                box-shadow: 0 25px 60px -10px rgba(243, 179, 19, 0.55), 0 0 25px rgba(243, 179, 19, 0.4);
                transform: translateY(-3px) scale(1.015);
            }
            50% {
                box-shadow: 0 20px 45px -10px rgba(0, 75, 156, 0.35), 0 0 15px rgba(0, 75, 156, 0.25);
                transform: translateY(0) scale(1);
            }
        }

        .card-top {
            text-align: center;
            margin-bottom: 14px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }

        /* Counter Name */
        .counter-name {
            font-family: 'Inter', sans-serif;
            font-size: clamp(22px, 2vw, 28px);
            font-weight: 900;
            color: #002B49;
            line-height: 1.25;
            letter-spacing: -0.3px;
            margin: 0;
            text-align: center;
        }

        /* Hero Ticket Box ("Nomor Antrean Dipanggil") */
        .hero-ticket-box {
            background: linear-gradient(180deg, #f0f7ff 0%, #e0f2fe 100%);
            border: 2.5px solid #004B9C;
            border-radius: 20px;
            padding: 20px 14px;
            min-height: 155px;
            text-align: center;
            margin: 8px 0 16px 0;
            position: relative;
            box-shadow: inset 0 2px 8px rgba(0, 75, 156, 0.08);
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transition: all 0.35s ease;
        }
        .hero-ticket-box.is-empty {
            background: #f8fafc;
            border: 2px dashed #cbd5e1;
            box-shadow: none;
        }
        .hero-ticket-box.is-calling {
            animation: heroCallingBlink 1.2s ease-in-out infinite;
        }
        @keyframes heroCallingBlink {
            0%, 100% {
                background: linear-gradient(135deg, #FEF08A 0%, #FDE047 50%, #F59E0B 100%);
                border-color: #D97706;
                box-shadow: 0 0 0 5px rgba(245, 158, 11, 0.35), 0 8px 25px rgba(217, 119, 6, 0.35);
            }
            50% {
                background: linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 50%, #BFDBFE 100%);
                border-color: #004B9C;
                box-shadow: 0 0 0 4px rgba(0, 75, 156, 0.25), 0 6px 18px rgba(0, 75, 156, 0.2);
            }
        }

        .hero-ticket-label {
            font-size: 13px;
            font-weight: 900;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #475569;
            margin-bottom: 6px;
        }
        .hero-ticket-number {
            font-family: 'Inter', sans-serif;
            font-size: clamp(50px, 4.8vw, 72px);
            font-weight: 900;
            line-height: 1.1;
            letter-spacing: 1.5px;
            color: #004B9C;
            transition: all 0.3s ease;
        }
        .hero-ticket-number.text-empty {
            font-size: clamp(18px, 1.7vw, 24px);
            font-weight: 700;
            color: #64748b;
            letter-spacing: 0.2px;
            text-transform: none;
        }
        .hero-ticket-box.is-calling .hero-ticket-number {
            color: #002B49;
            animation: numberScalePulse 0.6s ease-in-out infinite alternate;
        }
        @keyframes numberScalePulse {
            0% { transform: scale(1); }
            100% { transform: scale(1.08); }
        }

        /* Live Queue Status Board ("Antrean Menunggu" & "Antrean Berikutnya") */
        .queue-status-board {
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 16px;
            padding: 16px 20px;
            margin-top: 4px;
        }
        .status-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 38px;
        }
        .status-label {
            font-size: 13.5px;
            text-transform: uppercase;
            color: #1e293b;
            font-weight: 900;
            letter-spacing: 0.4px;
        }
        .status-value-wrap {
            display: flex;
            align-items: baseline;
            gap: 5px;
        }
        .status-number {
            font-family: 'Inter', sans-serif;
            font-size: 26px;
            font-weight: 900;
            color: #002B49;
            line-height: 1;
        }
        .status-unit {
            font-size: 13.5px;
            color: #475569;
            font-weight: 800;
        }
        .status-divider {
            height: 1px;
            background: #e2e8f0;
            margin: 12px 0;
        }
        .next-ticket-badge {
            font-family: 'Inter', sans-serif;
            font-size: 16px;
            font-weight: 900;
            color: #004B9C;
            background: #eff6ff;
            padding: 5px 16px;
            border-radius: 10px;
            border: 1.5px solid #bfdbfe;
            letter-spacing: 0.5px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }
        .next-ticket-badge.is-empty {
            font-size: 13px;
            font-weight: 700;
            color: #64748b;
            background: #f1f5f9;
            border-color: #e2e8f0;
            letter-spacing: 0.2px;
            text-transform: none;
            padding: 4px 12px;
        }

        /* Bank DP Taspen Corporate Footer (Clean & Centered) */
        footer.display-footer {
            position: relative;
            z-index: 10;
            padding: 12px 36px;
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(16px);
            border-top: 1px solid #e2e8f0;
            box-shadow: 0 -4px 20px rgba(0, 20, 50, 0.08);
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        .footer-primary-text {
            font-family: 'Inter', sans-serif;
            font-size: 15px;
            font-weight: 800;
            color: #002B49;
            letter-spacing: 0.3px;
            text-align: center;
        }

        /* Medium Displays / Laptops / Resized Windows (1025px to 1299px) */
        @media (max-width: 1299px) and (min-width: 1025px) {
            .display-grid.count-5 {
                grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
                gap: 16px;
            }
            .display-grid.count-4 {
                grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
                gap: 16px;
            }
        }

        /* Tablets & Resized / Split-screen Windows (<= 1024px) */
        @media (max-width: 1024px) {
            header.display-header {
                grid-template-columns: 1fr;
                justify-items: center;
                text-align: center;
                padding: 14px 20px;
                gap: 12px;
                width: 100%;
                box-sizing: border-box;
            }
            .brand {
                justify-self: center;
                justify-content: center;
                width: 100%;
            }
            .brand-logo-wrap {
                display: flex;
                justify-content: center;
                align-items: center;
            }
            .brand-info {
                align-items: center;
                justify-content: center;
            }
            .header-center-title {
                justify-self: center;
                width: 100%;
            }
            .display-header-title {
                font-size: clamp(24px, 3.2vw, 30px);
                text-align: center;
            }
            .header-meta {
                justify-self: center;
                justify-content: center;
                width: 100%;
            }
            .clock-display {
                text-align: center;
            }
            .clock-time {
                font-size: 21px;
                font-weight: 800;
                color: #004B9C;
                line-height: 1.1;
            }
            .clock-date {
                font-size: 11px;
                color: #64748b;
                font-weight: 600;
            }
            main.display-container {
                padding: 16px 20px;
                justify-content: flex-start;
            }
            .display-grid,
            .display-grid.count-1,
            .display-grid.count-2,
            .display-grid.count-3,
            .display-grid.count-4,
            .display-grid.count-5 {
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 16px;
            }
            footer.display-footer {
                padding: 12px 16px;
                text-align: center;
                justify-content: center;
                align-items: center;
            }
        }

        /* Mobile Phones (< 640px) */
        @media (max-width: 639px) {
            .ticker-bar {
                font-size: 11px;
                padding: 5px 8px;
            }
            .ticker-item {
                margin-right: 28px;
                gap: 5px;
            }
            header.display-header {
                padding: 12px 14px;
                gap: 10px;
            }
            .display-header-title {
                font-size: clamp(22px, 5vw, 28px);
                white-space: normal;
                text-align: center;
                line-height: 1.25;
            }
            .clock-time {
                font-size: 19px;
            }
            .clock-date {
                font-size: 10.5px;
            }
            .audio-toggle {
                width: 36px;
                height: 36px;
            }
            .audio-toggle .kiosk-icon {
                width: 18px;
                height: 18px;
            }
            main.display-container {
                padding: 12px 14px;
                width: 100%;
                box-sizing: border-box;
                justify-content: flex-start;
            }
            .display-grid,
            .display-grid.count-1,
            .display-grid.count-2,
            .display-grid.count-3,
            .display-grid.count-4,
            .display-grid.count-5 {
                grid-template-columns: 1fr !important;
                gap: 14px;
                width: 100%;
                box-sizing: border-box;
            }
            .service-card {
                padding: 20px 16px;
                border-radius: 20px;
                width: 100%;
                box-sizing: border-box;
            }
            .counter-name {
                font-size: 20px;
                line-height: 1.25;
            }
            .hero-ticket-box {
                padding: 16px 12px;
                min-height: 130px;
                margin: 8px 0 14px 0;
                width: 100%;
                box-sizing: border-box;
            }
            .hero-ticket-number {
                font-size: 42px;
            }
            .hero-ticket-number.text-empty {
                font-size: 18px;
            }
            .queue-status-board {
                padding: 12px 16px;
                width: 100%;
                box-sizing: border-box;
            }
            .status-row {
                display: flex;
                align-items: center;
                justify-content: space-between;
                width: 100%;
                gap: 8px;
            }
            .status-label {
                font-size: 12.5px;
                font-weight: 900;
                white-space: nowrap;
            }
            .status-value-wrap {
                display: inline-flex;
                align-items: baseline;
                gap: 4px;
                flex-shrink: 0;
            }
            .status-number {
                font-size: 22px;
                font-weight: 900;
            }
            .status-unit {
                font-size: 12px;
                font-weight: 800;
            }
            footer.display-footer {
                padding: 12px 14px;
                text-align: center;
                justify-content: center;
                align-items: center;
                width: 100%;
                box-sizing: border-box;
            }
            .footer-primary-text {
                font-size: 13px;
                text-align: center;
            }
        }
    </style>
    <link rel="stylesheet" href="{{ asset('css/bank-ui.css') }}?v=20261012">
</head>
<body>
    <!-- Blade Heroicon Templates for Dynamic Components (No Hardcoded SVG / Emojis) -->
    <div id="heroiconTemplates" style="display: none;">
        <div id="tplIconOfficer"><x-heroicon-o-user class="officer-icon" /></div>
        <div id="tplIconWarning"><x-heroicon-o-exclamation-triangle class="kiosk-icon text-amber" /></div>
        <div id="tplIconSpeakerWave"><x-heroicon-o-speaker-wave class="kiosk-icon" /></div>
        <div id="tplIconSpeakerMute"><x-heroicon-o-speaker-x-mark class="kiosk-icon" /></div>
    </div>

    <!-- Background Layer with Photo Building & Dark Royal Navy Overlay -->
    <div style="position: fixed; inset: 0; overflow: hidden; pointer-events: none; z-index: 0;">
        <div class="kiosk-background-layer"></div>
        <div class="kiosk-background-overlay"></div>
        <div class="ambient-glow-1"></div>
        <div class="ambient-glow-2"></div>
    </div>

    <!-- Ticker Running Text Bar with Blade Heroicons -->
    <div class="ticker-bar">
        <div class="ticker-content">
            <span class="ticker-item">
                <x-heroicon-o-megaphone class="kiosk-icon text-gold" />
                <span><strong>BANK DP TASPEN KINI HADIR LEBIH DEKAT!</strong> Solusi Keuangan & Layanan Pensiunan TASPEN</span>
            </span>
            <span class="ticker-item">
                <x-heroicon-o-phone class="kiosk-icon text-gold" />
                <span><strong>CALL CENTER BERSAMA MAYA:</strong> 150181</span>
            </span>
            <span class="ticker-item">
                <x-heroicon-o-chat-bubble-left-right class="kiosk-icon text-gold" />
                <span><strong>TELEMARKETING BERSAMA LANI & DITO:</strong> Solusi Finansial Terbaik Langsung ke Ponsel Anda</span>
            </span>
            <span class="ticker-item">
                <x-heroicon-o-building-office-2 class="kiosk-icon text-gold" />
                <span id="tickerBranchInfo"><strong>{{ strtoupper($branch->name) }}:</strong> {{ $branch->address }}@if($branch->phone) (Telp: {{ $branch->phone }})@endif</span>
            </span>
            <span class="ticker-item">
                <x-heroicon-o-bell-alert class="kiosk-icon text-gold" />
                <span><strong>MOHON PERHATIKAN NOMOR ANTREAN DAN MENUJU LOKET SAAT DIPANGGIL</strong></span>
            </span>
        </div>
    </div>

    <!-- Bank DP Taspen Clean Corporate Header -->
    <header class="display-header">
        <div class="brand">
            <div class="brand-logo-wrap">
                @include('components.bank-logo', ['height' => '40px'])
            </div>
        </div>

        <div class="header-center-title">
            <h1 class="display-header-title">INFORMASI PEMANGGILAN ANTREAN</h1>
        </div>

        <div class="header-meta">
            <!-- Audio Call Toggle with Heroicon (Icon Only) -->
            <button type="button" id="audioToggleBtn" class="audio-toggle active" onclick="toggleAudio()" title="Suara Panggilan Aktif (Klik untuk bisukan)">
                <span id="audioIconHolder" style="display: inline-flex; align-items: center; justify-content: center;">
                    <x-heroicon-o-speaker-wave class="kiosk-icon" />
                </span>
            </button>

            <!-- Real-time Clock -->
            <div class="clock-display">
                <div id="clockTime" class="clock-time">00.00 WIB</div>
                <div id="clockDate" class="clock-date">-</div>
            </div>
        </div>
    </header>

    <!-- Main Content Area: Cards Grid -->
    <main class="display-container">
        <div class="display-grid count-{{ count($initialData['counters'] ?? []) }}" id="displayGrid">
            @forelse(($initialData['counters'] ?? []) as $c)
                @php
                    $curNum = trim((string)($c['current_number'] ?? ''));
                    $hasActiveNumber = !empty($curNum) && $curNum !== '-' && stripos($curNum, 'belum') === false;

                    $nextNum = trim((string)($c['next_waiting_number'] ?? ''));
                    $hasNextNumber = !empty($nextNum) && $nextNum !== '-' && stripos($nextNum, 'belum') === false;
                @endphp
                <div id="card-{{ $c['id'] }}" class="service-card">
                    <div class="card-top">
                        <h3 class="counter-name" title="{{ $c['counter_name'] }}">{{ $c['counter_name'] }}</h3>
                    </div>

                    <!-- Hero Ticket Box: Nomor Antrean Dipanggil -->
                    <div class="hero-ticket-box {{ $hasActiveNumber ? 'has-ticket' : 'is-empty' }}">
                        <span class="hero-ticket-label">Nomor Antrean Dipanggil</span>
                        <div class="hero-ticket-number {{ $hasActiveNumber ? '' : 'text-empty' }}">
                            {{ $hasActiveNumber ? $c['current_number'] : 'belum ada antrean' }}
                        </div>
                    </div>

                    <!-- Queue Status Board: Antrean Menunggu & Antrean Berikutnya -->
                    <div class="queue-status-board">
                        <div class="status-row">
                            <span class="status-label">Antrean Menunggu</span>
                            <div class="status-value-wrap">
                                <span class="status-number">{{ $c['waiting_count'] }}</span>
                                <span class="status-unit">Orang</span>
                            </div>
                        </div>
                        <div class="status-divider"></div>
                        <div class="status-row">
                            <span class="status-label">Antrean Berikutnya</span>
                            <span class="next-ticket-badge {{ $hasNextNumber ? 'has-next' : 'is-empty' }}">
                                {{ $hasNextNumber ? $c['next_waiting_number'] : 'belum ada antrean' }}
                            </span>
                        </div>
                    </div>
                </div>
            @empty
                <div style="grid-column: 1/-1; text-align: center; font-size: 17px; font-weight: 700; color: #ffffff; padding: 48px; background: rgba(255,255,255,0.1); border-radius: 20px; backdrop-filter: blur(10px); display: flex; align-items: center; justify-content: center; gap: 8px;">
                    <x-heroicon-o-exclamation-triangle class="kiosk-icon text-amber" />
                    <span>Belum ada loket layanan yang diaktifkan di Admin Panel.</span>
                </div>
            @endforelse
        </div>
    </main>

    <!-- Bank DP Taspen Corporate Footer (Clean & Centered) -->
    <footer class="display-footer">
        <div class="footer-primary-text">Silakan Menunggu Panggilan Nomor Antrean Anda</div>
    </footer>

    <script>
        let audioEnabled = true;
        let lastCalledTimes = {
            @foreach(($initialData['counters'] ?? []) as $c)
                @if(!empty($c['called_at']))
                    {{ $c['id'] }}: @json($c['called_at']),
                @endif
            @endforeach
        };
        let lastCalledNumbers = {
            @foreach(($initialData['counters'] ?? []) as $c)
                @if(!empty($c['current_number']))
                    {{ $c['id'] }}: @json($c['current_number']),
                @endif
            @endforeach
        };
        let isInitialLoad = true;
        let audioContext = null;

        // Audio Queue state to prevent overlapping calls
        let audioCallQueue = [];
        let isAudioPlaying = false;
        let activeUtterance = null;
        let audioWatchdogTimer = null;

        function updateClock() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            document.getElementById('clockTime').textContent = `${hours}.${minutes} WIB`;
            document.getElementById('clockDate').textContent = now.toLocaleDateString('id-ID', {
                weekday: 'long',
                day: 'numeric',
                month: 'long',
                year: 'numeric'
            });
        }
        setInterval(updateClock, 1000);
        updateClock();

        function initAudioContext() {
            if (!audioContext) {
                try {
                    audioContext = new (window.AudioContext || window.webkitAudioContext)();
                } catch(e) {}
            }
            if (audioContext && audioContext.state === 'suspended') {
                audioContext.resume();
            }
        }
        document.addEventListener('click', initAudioContext, { once: true });

        function toggleAudio() {
            initAudioContext();
            audioEnabled = !audioEnabled;
            const btn = document.getElementById('audioToggleBtn');
            const iconHolder = document.getElementById('audioIconHolder');
            const tplWave = document.getElementById('tplIconSpeakerWave');
            const tplMute = document.getElementById('tplIconSpeakerMute');

            if (btn) {
                if (audioEnabled) {
                    btn.classList.add('active');
                    btn.setAttribute('title', 'Suara Panggilan Aktif (Klik untuk bisukan)');
                    if (iconHolder && tplWave) iconHolder.innerHTML = tplWave.innerHTML;
                } else {
                    btn.classList.remove('active');
                    btn.setAttribute('title', 'Suara Panggilan Senyap (Klik untuk aktifkan)');
                    if (iconHolder && tplMute) iconHolder.innerHTML = tplMute.innerHTML;
                    if ('speechSynthesis' in window) window.speechSynthesis.cancel();
                    audioCallQueue = [];
                    isAudioPlaying = false;
                }
            }
        }

        // Harmonious 4-tone Bank Chime (C5 -> E5 -> G5 -> C6)
        function playChime() {
            return new Promise((resolve) => {
                try {
                    initAudioContext();
                    if (!audioContext) {
                        resolve();
                        return;
                    }
                    const now = audioContext.currentTime;

                    const playTone = (freq, startTime, duration, vol = 0.22) => {
                        const osc = audioContext.createOscillator();
                        const gain = audioContext.createGain();
                        osc.type = 'sine';
                        osc.frequency.setValueAtTime(freq, startTime);
                        gain.gain.setValueAtTime(vol, startTime);
                        gain.gain.exponentialRampToValueAtTime(0.0001, startTime + duration);
                        osc.connect(gain);
                        gain.connect(audioContext.destination);
                        osc.start(startTime);
                        osc.stop(startTime + duration);
                    };

                    playTone(523.25, now, 0.45, 0.22);         // C5
                    playTone(659.25, now + 0.16, 0.45, 0.24);  // E5
                    playTone(783.99, now + 0.32, 0.6, 0.26);   // G5
                    playTone(1046.50, now + 0.50, 0.8, 0.28);  // C6

                    setTimeout(resolve, 1100);
                } catch(e) {
                    resolve();
                }
            });
        }

        let activeCallingExpiry = {};

        function triggerCallingHighlight(counterId) {
            activeCallingExpiry[counterId] = Date.now() + 15000;
            const card = document.getElementById(`card-${counterId}`);
            if (card) {
                card.classList.add('is-calling');
                const box = card.querySelector('.hero-ticket-box');
                if (box) {
                    box.classList.add('is-calling');
                }
            }
        }

        function isCounterCalling(counterId) {
            return Boolean(activeCallingExpiry[counterId] && Date.now() < activeCallingExpiry[counterId]);
        }

        // Sequential Audio Queue: Panggilan dimainkan satu per satu secara berurutan (Anti-Bentrok)
        function enqueueCall(callData) {
            if (!audioEnabled || !callData.number || callData.number === '-' || String(callData.number).toLowerCase().includes('belum')) return;

            // Langsung aktifkan highlight visual pada kartu loket
            triggerCallingHighlight(callData.counterId);

            audioCallQueue.push(callData);
            processAudioQueue();
        }

        async function processAudioQueue() {
            if (isAudioPlaying || audioCallQueue.length === 0) return;
            if (!audioEnabled) {
                audioCallQueue = [];
                return;
            }

            isAudioPlaying = true;
            const current = audioCallQueue.shift();

            // Perpanjang highlight visual kartu saat suara gilirannya berbunyi
            triggerCallingHighlight(current.counterId);

            try {
                // 1. Putar Nada Bel Ding-Dong Bank
                await playChime();

                // 2. Susun Kalimat Suara (Bedakan Panggilan Pertama vs Panggilan Ulang)
                const formattedNum = String(current.number).replace(/-/g, ' ');
                let speechText = '';
                if (current.isRecall) {
                    speechText = `Panggilan ulang. Nomor antrean ${formattedNum}, silakan menuju ${current.counterName}.`;
                } else {
                    speechText = `Nomor antrean ${formattedNum}, silakan menuju ${current.counterName}.`;
                }

                // 3. Ucapkan Kalimat Melalui Web Speech API
                await new Promise((resolve) => {
                    if (!('speechSynthesis' in window)) {
                        resolve();
                        return;
                    }

                    if (audioWatchdogTimer) clearTimeout(audioWatchdogTimer);

                    // Watchdog: Timeout pengaman 12 detik jika browser speech synthesis macet
                    audioWatchdogTimer = setTimeout(() => {
                        console.warn('Speech synthesis timeout watchdog triggered');
                        resolve();
                    }, 12000);

                    const utterance = new SpeechSynthesisUtterance(speechText);
                    utterance.lang = 'id-ID';
                    utterance.rate = 0.88; // Tempo artikulasi jelas dan nyaman didengar
                    utterance.pitch = 1.0;
                    activeUtterance = utterance; // Referensi global pencegah GC browser bug

                    utterance.onend = () => {
                        if (audioWatchdogTimer) clearTimeout(audioWatchdogTimer);
                        activeUtterance = null;
                        resolve();
                    };

                    utterance.onerror = (err) => {
                        console.warn('SpeechSynthesis error:', err);
                        if (audioWatchdogTimer) clearTimeout(audioWatchdogTimer);
                        activeUtterance = null;
                        resolve();
                    };

                    window.speechSynthesis.speak(utterance);
                });
            } catch (err) {
                console.error('Audio playback error:', err);
            } finally {
                // Jeda 800ms antar panggilan agar pemanggilan berikutnya terdengar rapi dan tidak terburu-buru
                setTimeout(() => {
                    isAudioPlaying = false;
                    processAudioQueue();
                }, 800);
            }
        }

        function escapeDisplayText(value) {
            const element = document.createElement('span');
            element.textContent = String(value ?? '');
            return element.innerHTML.replaceAll('"', '&quot;').replaceAll("'", '&#39;');
        }

        let isFetching = false;
        async function refreshData() {
            if (isFetching) return;
            isFetching = true;
            try {
                // Fetch live data khusus cabang ini
                const response = await fetch("{{ url('/api/display/data/' . $branch->code) }}");
                if (!response.ok) throw new Error("HTTP error " + response.status);
                const data = await response.json();

                const grid = document.getElementById('displayGrid');
                if (!grid) return;

                const counters = data.counters || [];
                const iconWarningHtml = document.getElementById('tplIconWarning')?.innerHTML || '';

                if (counters.length === 0) {
                    grid.innerHTML = `
                        <div style="grid-column: 1/-1; text-align: center; font-size: 17px; font-weight: 700; color: #ffffff; padding: 48px; background: rgba(255,255,255,0.1); border-radius: 20px; backdrop-filter: blur(10px); display: flex; align-items: center; justify-content: center; gap: 8px;">
                            ${iconWarningHtml}
                            <span>Belum ada loket layanan yang diaktifkan di Admin Panel.</span>
                        </div>
                    `;
                    return;
                }

                // Terapkan class responsive murni berbasis CSS media query tanpa inline override
                grid.className = 'display-grid count-' + counters.length;
                grid.style.gridTemplateColumns = '';
                grid.style.justifyContent = '';

                counters.forEach(c => {
                    const hasActiveNumber = c.current_number && c.current_number !== '-' && !String(c.current_number).toLowerCase().includes('belum');
                    const lastTime = lastCalledTimes[c.id];
                    const lastNum = lastCalledNumbers[c.id];

                    // Panggilan baru terdeteksi jika waktu panggil berubah
                    const isNewCall = !isInitialLoad && Boolean(c.called_at && lastTime !== undefined && lastTime !== c.called_at);

                    if (isNewCall && hasActiveNumber) {
                        const isRecall = Boolean(lastNum && lastNum === c.current_number);
                        enqueueCall({
                            number: c.current_number,
                            counterName: c.counter_name,
                            isRecall: isRecall,
                            counterId: c.id
                        });
                    } else if (isInitialLoad && c.called_at && hasActiveNumber) {
                        // Cek jika nomor baru saja dipanggil dalam 15 detik terakhir
                        const callAgeMs = Date.now() - new Date(c.called_at).getTime();
                        if (callAgeMs >= 0 && callAgeMs < 15000) {
                            activeCallingExpiry[c.id] = Date.now() + (15000 - callAgeMs);
                        }
                    }

                    if (c.called_at) {
                        lastCalledTimes[c.id] = c.called_at;
                    }
                    if (c.current_number) {
                        lastCalledNumbers[c.id] = c.current_number;
                    }
                });

                grid.innerHTML = counters.map(c => {
                    const calling = isCounterCalling(c.id);
                    const hasActiveNumber = c.current_number && c.current_number !== '-' && !String(c.current_number).toLowerCase().includes('belum');
                    const displayCurrentNumber = hasActiveNumber ? c.current_number : 'belum ada antrean';

                    const hasNextNumber = c.next_waiting_number && c.next_waiting_number !== '-' && !String(c.next_waiting_number).toLowerCase().includes('belum');
                    const displayNextNumber = hasNextNumber ? c.next_waiting_number : 'belum ada antrean';

                    return `
                        <div id="card-${c.id}" class="service-card ${calling ? 'is-calling' : ''}">
                            <div class="card-top">
                                <h3 class="counter-name" title="${escapeDisplayText(c.counter_name)}">${escapeDisplayText(c.counter_name)}</h3>
                            </div>

                            <!-- Hero Ticket Box: Nomor Antrean Dipanggil -->
                            <div class="hero-ticket-box ${hasActiveNumber ? 'has-ticket' : 'is-empty'} ${calling ? 'is-calling' : ''}">
                                <span class="hero-ticket-label">Nomor Antrean Dipanggil</span>
                                <div class="hero-ticket-number ${hasActiveNumber ? '' : 'text-empty'}">${escapeDisplayText(displayCurrentNumber)}</div>
                            </div>

                            <!-- Queue Status Board: Antrean Menunggu & Antrean Berikutnya -->
                            <div class="queue-status-board">
                                <div class="status-row">
                                    <span class="status-label">Antrean Menunggu</span>
                                    <div class="status-value-wrap">
                                        <span class="status-number">${escapeDisplayText(c.waiting_count)}</span>
                                        <span class="status-unit">Orang</span>
                                    </div>
                                </div>
                                <div class="status-divider"></div>
                                <div class="status-row">
                                    <span class="status-label">Antrean Berikutnya</span>
                                    <span class="next-ticket-badge ${hasNextNumber ? 'has-next' : 'is-empty'}">${escapeDisplayText(displayNextNumber)}</span>
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');
                isInitialLoad = false;
            } catch(e) {
                console.error('Fetch error:', e);
            } finally {
                isFetching = false;
            }
        }

        setInterval(() => {
            const now = Date.now();
            for (const id in activeCallingExpiry) {
                if (now >= activeCallingExpiry[id]) {
                    delete activeCallingExpiry[id];
                    const card = document.getElementById(`card-${id}`);
                    if (card) {
                        card.classList.remove('is-calling');
                        const box = card.querySelector('.hero-ticket-box');
                        if (box) box.classList.remove('is-calling');
                    }
                }
            }
        }, 500);

        refreshData();
        setInterval(refreshData, 3000);
    </script>
</body>
</html>
