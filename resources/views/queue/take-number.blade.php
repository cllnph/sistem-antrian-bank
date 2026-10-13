<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kiosk Antrean Tiket - PT BPR DP TASPEN</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        html, body {
            width: 100%;
            height: 100%;
            max-width: 100%;
            overflow: hidden;
        }
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background: radial-gradient(circle at top right, #1e3a8a 0%, #0f2b5c 40%, #040d1a 100%);
            color: #0f172a;
            height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
            transition: font-size 0.2s ease;
        }
        @media (max-height: 560px), (max-width: 900px) {
            html, body {
                height: auto;
                min-height: 100%;
                overflow-y: auto;
            }
            body {
                height: auto;
                min-height: 100vh;
            }
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
        ::-webkit-scrollbar-thumb:hover {
            background: rgba(243, 179, 19, 0.9);
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

        /* Bank DP Taspen Clean Corporate Header */
        header.kiosk-header {
            position: relative;
            z-index: 10;
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            padding: 12px 36px;
            background: rgba(255, 255, 255, 0.96);
            backdrop-filter: blur(16px);
            border-bottom: 3px solid #F3B313;
            box-shadow: 0 4px 20px rgba(0, 20, 50, 0.12);
            flex-shrink: 0;
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
            text-decoration: none;
        }
        .brand-info {
            display: flex;
            align-items: center;
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

        /* Header Center Title: Pengambilan Tiket Antrean (Konsisten dengan Display) */
        .header-center-title {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
            text-align: center;
            justify-self: center;
        }
        .kiosk-header-title {
            font-family: 'Inter', sans-serif;
            font-size: clamp(26px, 2.5vw, 36px);
            font-weight: 900;
            color: #002B49;
            letter-spacing: -0.6px;
            margin: 0;
            line-height: 1.15;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .kiosk-header-subtitle {
            font-size: clamp(11.5px, 1vw, 13px);
            color: #475569;
            font-weight: 600;
            margin: 0;
            line-height: 1.2;
            letter-spacing: -0.1px;
        }

        .header-meta {
            display: flex;
            align-items: center;
            gap: 16px;
            justify-self: end;
        }

        .btn-senior-mode {
            background: #f8fafc;
            border: 1.5px solid #cbd5e1;
            color: #002B49;
            padding: 7px 16px;
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
            background: #e2e8f0;
            border-color: #94a3b8;
            transform: scale(1.02);
        }
        .btn-senior-mode.active {
            background: #F3B313;
            color: #002B49;
            border-color: #d97706;
            box-shadow: 0 2px 8px rgba(243, 179, 19, 0.4);
        }

        .status-chip {
            display: flex;
            align-items: center;
            gap: 6px;
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #047857;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }
        .status-dot {
            width: 8px;
            height: 8px;
            background: #22c55e;
            border-radius: 50%;
            box-shadow: 0 0 8px #22c55e;
            animation: pulseDot 2s infinite;
        }
        @keyframes pulseDot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.6; transform: scale(1.2); }
        }

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

        /* Main Content */
        main.kiosk-content {
            position: relative;
            z-index: 10;
            max-width: 1180px;
            margin: 0 auto;
            width: 100%;
            padding: 10px 24px;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            box-sizing: border-box;
            min-height: 0;
        }

        #queueForm {
            width: 100%;
            display: flex;
            justify-content: center;
        }

        /* 2-Column Grid: Teller & CS */
        .services-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 24px;
            width: 100%;
            max-width: 1180px;
        }

        /* Bank DP Taspen Clean White Surface Cards */
        .service-card {
            background: #ffffff;
            border-radius: 20px;
            padding: 18px 22px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            gap: 12px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            box-shadow: 0 16px 36px -8px rgba(0, 30, 75, 0.28), 0 4px 12px rgba(0, 0, 0, 0.05);
            border: 2px solid rgba(255, 255, 255, 0.95);
            box-sizing: border-box;
        }
        .service-card:hover:not(.disabled) {
            transform: translateY(-2px);
            box-shadow: 0 20px 40px -8px rgba(0, 75, 156, 0.38);
        }
        .service-card.card-teller,
        .service-card.card-cs {
            border-top: 5px solid #004B9C;
        }
        .service-card.disabled {
            opacity: 0.65;
            filter: grayscale(0.5);
            border-top-color: #94a3b8;
        }

        .card-body-content {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .card-top {
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            margin-bottom: 0;
        }
        .service-heading {
            text-align: center;
            width: 100%;
        }
        .service-heading h3 {
            font-family: 'Inter', sans-serif;
            font-size: 24px;
            font-weight: 900;
            color: #002B49;
            line-height: 1.2;
            text-align: center;
            letter-spacing: -0.2px;
        }

        /* Hero Ticket Box ("Nomor Anda Berikutnya") */
        .hero-ticket-box {
            border-radius: 16px;
            padding: 12px 16px;
            text-align: center;
            margin-bottom: 0;
            position: relative;
            border: 2.5px solid #004B9C;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }
        .teller-hero-box,
        .cs-hero-box {
            background: linear-gradient(180deg, #f0f7ff 0%, #e0f2fe 100%);
            border-color: #004B9C;
            box-shadow: inset 0 2px 8px rgba(0, 75, 156, 0.08);
        }
        .hero-ticket-label {
            font-size: 11.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #64748b;
            margin-bottom: 2px;
        }
        .hero-ticket-number {
            font-family: 'Inter', sans-serif;
            font-size: 44px;
            font-weight: 900;
            line-height: 1.1;
            letter-spacing: 1px;
        }
        .hero-ticket-sub {
            font-size: 11px;
            color: #64748b;
            font-weight: 600;
            margin-top: 2px;
        }

        /* LIVE QUEUE MONITORING BOARD (Sebelum Ambil Nomor) */
        .queue-status-board {
            background: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-radius: 14px;
            padding: 8px 12px;
            margin-bottom: 0;
        }

        .board-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }
        .board-cell {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 6px 10px;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
        }
        .cell-label {
            font-size: 10.5px;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 700;
            letter-spacing: 0.4px;
            margin-bottom: 1px;
        }
        .cell-number {
            font-family: 'Inter', sans-serif;
            font-size: 17px;
            font-weight: 900;
            line-height: 1.1;
        }
        .cell-sub {
            font-size: 10.5px;
            color: #94a3b8;
            margin-top: 1px;
            font-weight: 600;
        }
        .text-teller,
        .text-cs { 
            color: #004B9C; 
        }

        .cell-serving,
        .cell-serving.cs-serving {
            background: #f0f7ff;
            border-color: #bae6fd;
        }

        .cell-next {
            background: #fafaf9;
            border-color: #e7e5e4;
        }

        .closed-notice {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #b91c1c;
            padding: 8px;
            border-radius: 10px;
            margin-bottom: 0;
            text-align: center;
            font-size: 11.5px;
            font-weight: 700;
        }

        /* Buttons matching Bank DP Taspen Royal Blue - Kiosk Touchscreen Optimized */
        .btn-take {
            width: 100%;
            min-height: 60px;
            padding: 12px 20px;
            border: none;
            border-radius: 16px;
            font-family: 'Inter', sans-serif;
            font-size: 18px;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            letter-spacing: 0.3px;
            touch-action: manipulation;
            user-select: none;
            -webkit-user-select: none;
            margin-top: 4px;
        }
        .btn-teller,
        .btn-cs {
            background: linear-gradient(135deg, #004B9C 0%, #002B49 100%);
            color: #ffffff;
            box-shadow: 0 6px 20px -4px rgba(0, 75, 156, 0.45);
        }
        .btn-take:hover:not(:disabled) {
            transform: translateY(-2px) scale(1.005);
            filter: brightness(1.08);
            box-shadow: 0 10px 24px -4px rgba(0, 30, 75, 0.45);
        }
        .btn-take:active:not(:disabled) {
            transform: scale(0.98);
        }
        .btn-take:disabled {
            background: #94a3b8;
            color: #e2e8f0;
            box-shadow: none;
            cursor: not-allowed;
            opacity: 0.65;
        }
        .btn-take.is-loading {
            pointer-events: none;
            opacity: 0.9;
            box-shadow: 0 4px 14px rgba(0, 75, 156, 0.3);
        }

        /* Senior Mode Accessibility Expansion */
        body.senior-mode .brand-info p { font-size: 13.5px !important; }
        body.senior-mode .kiosk-title h2 { font-size: 32px !important; }
        body.senior-mode .kiosk-title p { font-size: 16px !important; }
        body.senior-mode .service-heading h3 { font-size: 25px !important; }
        body.senior-mode .service-desc { font-size: 14.5px !important; min-height: auto; }
        body.senior-mode .cell-label { font-size: 11.5px !important; }
        body.senior-mode .cell-number { font-size: 30px !important; }
        body.senior-mode .cell-value { font-size: 14.5px !important; }
        body.senior-mode .cell-sub { font-size: 11px !important; }
        body.senior-mode .btn-take { font-size: 18px !important; padding: 18px !important; }
        body.senior-mode .kiosk-footer { font-size: 13px !important; }

        /* Confirmation & Ticket Modal Overlay */
        .ticket-modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            z-index: 10000;
            background: rgba(2, 11, 24, 0.92);
            backdrop-filter: blur(12px);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
            overflow-y: auto;
            box-sizing: border-box;
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        .receipt-container {
            width: 100%;
            max-width: 400px;
            margin: auto;
            box-sizing: border-box;
            animation: slideDown 0.4s cubic-bezier(0.16, 1, 0.3, 1);
        }
        #confirmModalOverlay .receipt-container {
            max-width: 530px;
        }
        #ticketModalOverlay .receipt-container {
            max-width: 480px;
        }
        @media (max-width: 560px) {
            #confirmModalOverlay .receipt-container h3 {
                white-space: normal !important;
                font-size: 18px !important;
            }
        }
        @keyframes slideDown { from { transform: translateY(-30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }

        .receipt-paper {
            background: #ffffff;
            color: #0f172a;
            padding: 24px 22px;
            border-radius: 20px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.7);
            text-align: center;
            position: relative;
            font-family: 'Plus Jakarta Sans', sans-serif;
            width: 100%;
            box-sizing: border-box;
        }

        @media (max-width: 480px) {
            .ticket-modal-overlay {
                padding: 16px 12px;
            }
            .receipt-paper {
                padding: 22px 16px;
            }
        }

        .spinner-ring {
            position: absolute;
            width: 74px;
            height: 74px;
            border: 5px solid #e2e8f0;
            border-top: 5px solid #004B9C;
            border-right: 5px solid #F3B313;
            border-radius: 50%;
            animation: spinRing 1s linear infinite;
        }
        @keyframes spinRing {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        @keyframes pulseDot {
            0%, 100% { opacity: 1; transform: scale(1); }
            50% { opacity: 0.35; transform: scale(0.75); }
        }
        .countdown-number {
            font-family: 'Inter', sans-serif;
            font-size: 24px;
            font-weight: 900;
            color: #002B49;
            z-index: 2;
        }

        .receipt-header { margin-bottom: 18px; border-bottom: 2px dashed #cbd5e1; padding-bottom: 14px; }
        .receipt-bank-name { font-family: 'Inter', sans-serif; font-weight: 900; font-size: 21px; color: #002B49; letter-spacing: -0.5px; }
        .receipt-subtitle { font-size: 11px; color: #64748b; margin-top: 3px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; }

        .receipt-ticket-label { font-size: 12px; text-transform: uppercase; color: #64748b; font-weight: 700; margin-bottom: 4px; }
        .receipt-ticket-number {
            font-family: 'Inter', sans-serif;
            font-size: 52px;
            font-weight: 900;
            color: #002B49;
            letter-spacing: 2px;
            margin: 6px 0;
            line-height: 1;
        }
        .receipt-service-badge {
            display: inline-block;
            background: #e0f2fe;
            color: #004B9C;
            padding: 5px 14px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 800;
            margin-bottom: 14px;
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

        .receipt-footer { font-size: 11px; color: #64748b; line-height: 1.45; font-weight: 500; }

        .modal-actions {
            margin-top: 18px;
            display: flex;
            flex-direction: column;
            gap: 10px;
        }
        .btn-modal-print {
            padding: 15px;
            background: linear-gradient(135deg, #002B49, #004B9C);
            color: white;
            border: none;
            border-radius: 14px;
            font-family: 'Inter', sans-serif;
            font-size: 15px;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-modal-print:hover {
            transform: translateY(-2px);
            filter: brightness(1.1);
        }
        .btn-modal-digital {
            padding: 15px;
            background: linear-gradient(135deg, #059669 0%, #10b981 100%);
            color: white;
            border: none;
            border-radius: 14px;
            font-family: 'Inter', sans-serif;
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
            filter: brightness(1.08);
        }
        .btn-modal-cancel {
            padding: 13px;
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            color: #475569;
            border-radius: 14px;
            font-family: 'Inter', sans-serif;
            font-size: 14px;
            font-weight: 700;
            text-decoration: none;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }
        .btn-modal-cancel:hover {
            background: #e2e8f0;
            color: #0f172a;
        }

        /* Thermal Printer POS Optimizations */
        @media print {
            body {
                background: #ffffff !important;
                color: #000000 !important;
            }
            .kiosk-background-layer, .kiosk-background-overlay, .ambient-glow-1, .ambient-glow-2, header.kiosk-header, footer.kiosk-footer, main.kiosk-content, .modal-actions, #ticketChoiceCard, #confirmModalOverlay {
                display: none !important;
            }
            .ticket-modal-overlay {
                position: static !important;
                background: none !important;
                backdrop-filter: none !important;
                padding: 0 !important;
                display: block !important;
            }
            .receipt-container {
                width: 100% !important;
                max-width: 80mm !important;
                margin: 0 auto !important;
                animation: none !important;
            }
            #receiptPaper {
                display: block !important;
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

        /* Bank DP Taspen Footer */
        footer.kiosk-footer {
            position: relative;
            z-index: 10;
            padding: clamp(8px, 1.2vh, 12px) 24px;
            background: rgba(255, 255, 255, 0.96);
            border-top: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            gap: 3px;
            box-shadow: 0 -2px 10px rgba(0, 0, 0, 0.04);
            flex-shrink: 0;
        }
        .footer-waiting-text {
            font-family: 'Inter', sans-serif;
            font-size: clamp(14px, 1.2vw, 16px);
            font-weight: 800;
            color: #002B49;
            letter-spacing: 0.3px;
        }
        .footer-hours-item {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: clamp(11px, 0.9vw, 12.5px);
            color: #475569;
            font-weight: 600;
        }
        .footer-hours-item svg,
        .kiosk-icon {
            width: 15px;
            height: 15px;
            flex-shrink: 0;
            display: inline-block;
            vertical-align: middle;
        }
        .kiosk-icon-sm { width: 14px; height: 14px; }
        .kiosk-icon-lg { width: 20px; height: 20px; }
        .text-emerald { color: #10b981; }

        @media (max-width: 1024px) {
            .services-grid { grid-template-columns: 1fr; }
            header.kiosk-header {
                grid-template-columns: 1fr;
                padding: 14px 20px;
                gap: 10px;
                text-align: center;
            }
            .brand {
                justify-self: center;
                justify-content: center;
                align-items: center;
                text-align: center;
                gap: 8px;
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
            .kiosk-header-title {
                font-size: clamp(24px, 3.2vw, 30px);
                white-space: normal;
                text-align: center;
            }
            .kiosk-header-subtitle {
                text-align: center;
            }
            .header-meta {
                justify-self: center;
                justify-content: center;
                width: 100%;
            }
            .clock-display {
                text-align: center;
                width: 100%;
            }
            footer.kiosk-footer {
                padding: 14px 20px;
                flex-direction: column;
                gap: 8px;
                text-align: center;
                align-items: center;
            }
            .regulatory-badges {
                justify-content: center;
            }
        }

        @media (max-width: 640px) {
            header.kiosk-header {
                padding: 12px 14px;
                gap: 8px;
                width: 100%;
                max-width: 100%;
                box-sizing: border-box;
            }
            .brand-info p {
                font-size: 10.5px;
                line-height: 1.3;
                word-break: break-word;
            }
            .clock-time {
                font-size: 18px;
            }
            .clock-date {
                font-size: 10.5px;
            }
            main.kiosk-content {
                padding: 14px 12px;
                width: 100%;
                max-width: 100%;
                box-sizing: border-box;
                overflow-x: hidden;
            }
            .kiosk-title h2 {
                font-size: 20px;
                word-break: break-word;
            }
            .kiosk-title p {
                font-size: 12.5px;
            }
            .services-grid {
                width: 100%;
                max-width: 100%;
            }
            .service-card {
                padding: 16px 14px;
                min-height: auto;
                border-radius: 18px;
                width: 100%;
                max-width: 100%;
                box-sizing: border-box;
            }
            .hero-ticket-box {
                padding: 12px 10px;
                margin-bottom: 12px;
                width: 100%;
                box-sizing: border-box;
            }
            .hero-ticket-number {
                font-size: 32px;
            }
            .queue-status-board {
                width: 100%;
                box-sizing: border-box;
            }
            .board-grid {
                grid-template-columns: 1fr 1fr;
                gap: 8px;
                width: 100%;
                box-sizing: border-box;
            }
            .board-cell {
                padding: 6px 6px;
                min-width: 0;
            }
            .cell-number {
                font-size: 18px;
            }
            .btn-take {
                padding: 13px 16px;
                font-size: 14px;
                width: 100%;
            }
            footer.kiosk-footer {
                padding: 12px 14px;
                width: 100%;
                max-width: 100%;
                box-sizing: border-box;
            }
            .footer-hours-item {
                flex-wrap: wrap;
                text-align: center;
                font-size: 11px;
            }
            .ticket-modal-overlay {
                padding: 14px 10px;
            }
            .receipt-container {
                width: 100%;
                max-width: 360px;
            }
            .receipt-paper {
                padding: 20px 14px;
            }
        }
    </style>
    <link rel="stylesheet" href="{{ asset('css/bank-ui.css') }}?v=20261012">
</head>
<body>
    <!-- Background Layer with Photo Building & Dark Royal Navy Overlay -->
    <div style="position: fixed; inset: 0; overflow: hidden; pointer-events: none; z-index: 0;">
        <div class="kiosk-background-layer"></div>
        <div class="kiosk-background-overlay"></div>
        <div class="ambient-glow-1"></div>
        <div class="ambient-glow-2"></div>
    </div>

    <header class="kiosk-header">
        <div class="brand">
            <a href="{{ url('/ambil-nomor/' . $branch->code) }}" class="brand-logo-wrap">
                @include('components.bank-logo', ['height' => '42px'])
            </a>
        </div>

        <div class="header-center-title">
            <h1 class="kiosk-header-title">PENGAMBILAN TIKET ANTREAN</h1>
            <p class="kiosk-header-subtitle">Silakan sentuh tombol layanan di bawah untuk mengambil nomor antrean</p>
        </div>

        <div class="header-meta">
            <div class="clock-display">
                <div id="clockTime" class="clock-time">00.00 WIB</div>
                <div id="clockDate" class="clock-date">-</div>
            </div>
        </div>
    </header>

    <main class="kiosk-content">

        @if(session('error'))
            <div style="background: #fef2f2; border: 1.5px solid #fecaca; color: #b91c1c; padding: 14px 20px; border-radius: 16px; margin-bottom: 20px; text-align: center; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 8px;">
                <x-heroicon-o-exclamation-triangle class="kiosk-icon kiosk-icon-lg" style="color: #dc2626;" />
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @php
            $isServiceHours = \App\Services\KioskHours::isOpen();

            $tellerActive = ($tellerActive ?? (isset($settings['teller']) ? $settings['teller']->is_active : true)) && $isServiceHours;
            $csActive = ($csActive ?? (isset($settings['cs']) ? $settings['cs']->is_active : true)) && $isServiceHours;
            $tellerCounterName = $tellerCounterName ?? (isset($settings['teller']) ? $settings['teller']->counter_name : 'Loket Teller - Kas & Pensiun');
            $csCounterName = $csCounterName ?? (isset($settings['cs']) ? $settings['cs']->counter_name : 'Loket CS - Kredit & Pensiun');
            $currentServingTeller = (!empty($currentServingTeller) && $currentServingTeller !== '-' && stripos($currentServingTeller, 'belum') === false) ? $currentServingTeller : 'belum ada antrean';
            $currentServingCs = (!empty($currentServingCs) && $currentServingCs !== '-' && stripos($currentServingCs, 'belum') === false) ? $currentServingCs : 'belum ada antrean';
        @endphp

        <form method="POST" action="{{ route('queue.store-number') }}" id="queueForm">
            @csrf
            <input type="hidden" name="service" id="serviceInput" value="">
            <div class="services-grid">
                <!-- 1. KOLOM CUSTOMER SERVICE (Konsisten dengan Layar Display: CS Kolom 1) -->
                <div class="service-card card-cs {{ !$csActive ? 'disabled' : '' }}">
                    <div class="card-body-content">
                        <div class="card-top">
                            <div class="service-heading">
                                <h3>Customer Service</h3>
                            </div>
                        </div>

                        <!-- Hero Ticket Box: NOMOR TIKET BERIKUTNYA -->
                        <div class="hero-ticket-box cs-hero-box">
                            <span class="hero-ticket-label">NOMOR TIKET BERIKUTNYA</span>
                            <div class="hero-ticket-number text-cs" id="liveNextCs">{{ $nextCsNumber }}</div>
                        </div>

                        <!-- Live Queue Status: Sedang Dilayani & Antrean Menunggu -->
                        <div class="queue-status-board">
                            <div class="board-grid">
                                <div class="board-cell cell-serving cs-serving">
                                    <span class="cell-label">Sedang Dilayani</span>
                                    <span class="cell-number text-cs" id="liveServingCs">{{ $currentServingCs }}</span>
                                    <span class="cell-sub">Panggilan aktif</span>
                                </div>
                                <div class="board-cell">
                                    <span class="cell-label">Antrean Menunggu</span>
                                    <div style="display: flex; align-items: baseline; justify-content: center; gap: 4px;">
                                        <span class="cell-number" id="liveWaitingCs">{{ $csWaiting }}</span>
                                        <span class="cell-sub" style="font-size: 12px; font-weight: 700; color: #475569;">Orang</span>
                                    </div>
                                    <span class="cell-sub">Dalam antrean loket</span>
                                </div>
                            </div>
                        </div>

                        @if(!$csActive)
                            <div class="closed-notice" style="display: flex; align-items: center; justify-content: center; gap: 6px;">
                                @if(!$isServiceHours)
                                    <x-heroicon-o-clock class="kiosk-icon" style="color: #dc2626;" />
                                    <span>Pengambilan tiket dibuka Senin - Jumat (04.00 - 18.00 WIB).</span>
                                @else
                                    <x-heroicon-o-exclamation-triangle class="kiosk-icon" style="color: #dc2626;" />
                                    <span>Layanan Customer Service sedang tidak aktif saat ini.</span>
                                @endif
                            </div>
                        @endif
                    </div>

                    <button type="button" class="btn-take btn-cs" data-service="cs" data-label="Customer Service & Kredit Pensiun" data-counter="{{ $csCounterName }}" data-next="{{ $nextCsNumber }}" onclick="takeTicketInstant(this)" {{ !$csActive ? 'disabled' : '' }}>
                        <x-heroicon-o-ticket style="width: 28px; height: 28px; flex-shrink: 0;" />
                        <span>{{ $csActive ? 'Ambil Tiket' : 'Layanan Tutup' }}</span>
                    </button>
                </div>

                <!-- 2. KOLOM TELLER (Konsisten dengan Layar Display: Teller Kolom 2) -->
                <div class="service-card card-teller {{ !$tellerActive ? 'disabled' : '' }}">
                    <div class="card-body-content">
                        <div class="card-top">
                            <div class="service-heading">
                                <h3>Teller</h3>
                            </div>
                        </div>

                        <!-- Hero Ticket Box: NOMOR TIKET BERIKUTNYA -->
                        <div class="hero-ticket-box teller-hero-box">
                            <span class="hero-ticket-label">NOMOR TIKET BERIKUTNYA</span>
                            <div class="hero-ticket-number text-teller" id="liveNextTeller">{{ $nextTellerNumber }}</div>
                        </div>

                        <!-- Live Queue Status: Sedang Dilayani & Antrean Menunggu -->
                        <div class="queue-status-board">
                            <div class="board-grid">
                                <div class="board-cell cell-serving">
                                    <span class="cell-label">Sedang Dilayani</span>
                                    <span class="cell-number text-teller" id="liveServingTeller">{{ $currentServingTeller }}</span>
                                    <span class="cell-sub">Panggilan aktif</span>
                                </div>
                                <div class="board-cell">
                                    <span class="cell-label">Antrean Menunggu</span>
                                    <div style="display: flex; align-items: baseline; justify-content: center; gap: 4px;">
                                        <span class="cell-number" id="liveWaitingTeller">{{ $tellerWaiting }}</span>
                                        <span class="cell-sub" style="font-size: 12px; font-weight: 700; color: #475569;">Orang</span>
                                    </div>
                                    <span class="cell-sub">Dalam antrean loket</span>
                                </div>
                            </div>
                        </div>

                        @if(!$tellerActive)
                            <div class="closed-notice" style="display: flex; align-items: center; justify-content: center; gap: 6px;">
                                @if(!$isServiceHours)
                                    <x-heroicon-o-clock class="kiosk-icon" style="color: #dc2626;" />
                                    <span>Pengambilan tiket dibuka Senin - Jumat (04.00 - 18.00 WIB).</span>
                                @else
                                    <x-heroicon-o-exclamation-triangle class="kiosk-icon" style="color: #dc2626;" />
                                    <span>Layanan Teller sedang tidak aktif saat ini.</span>
                                @endif
                            </div>
                        @endif
                    </div>

                    <button type="button" class="btn-take btn-teller" data-service="teller" data-label="Teller Kas & Pensiun TASPEN" data-counter="{{ $tellerCounterName }}" data-next="{{ $nextTellerNumber }}" onclick="takeTicketInstant(this)" {{ !$tellerActive ? 'disabled' : '' }}>
                        <x-heroicon-o-ticket style="width: 28px; height: 28px; flex-shrink: 0;" />
                        <span>{{ $tellerActive ? 'Ambil Tiket' : 'Layanan Tutup' }}</span>
                    </button>
                </div>
            </div>
        </form>
    </main>

    <!-- Pre-Confirmation Modal (Pop up sebelum ambil tiket) -->
    <div class="ticket-modal-overlay" id="confirmModalOverlay" style="display: none; z-index: 9999;">
        <div class="receipt-container" style="max-width: 530px;">
            <div class="receipt-paper" id="confirmPaper" style="padding: 28px 26px; text-align: center; border-top: 5px solid #004B9C;">
                <h3 style="font-family: 'Inter', sans-serif; font-size: clamp(18px, 2.2vw, 21px); font-weight: 900; color: #002B49; margin-bottom: 22px; white-space: nowrap;">Konfirmasi Pengambilan Nomor Antrean</h3>

                <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 18px; padding: 22px 20px; margin-bottom: 22px; text-align: center;">
                    <!-- Baris 1: Layanan Dipilih (Center) -->
                    <div style="margin-bottom: 16px;">
                        <div style="font-size: 11.5px; text-transform: uppercase; color: #64748b; font-weight: 700; letter-spacing: 0.5px; margin-bottom: 3px;">Layanan Dipilih:</div>
                        <div id="confirmServiceName" style="font-size: 18px; font-weight: 800; color: #002B49; font-family: 'Inter', sans-serif;">-</div>
                    </div>

                    <!-- Baris 2: Nomor Antrean (Center, Tanpa Garis) -->
                    <div>
                        <div style="font-size: 11.5px; text-transform: uppercase; color: #64748b; font-weight: 800; letter-spacing: 0.5px; margin-bottom: 4px;">Nomor Antrean:</div>
                        <div id="confirmNextNumber" style="font-size: 38px; font-weight: 900; color: #004B9C; font-family: 'Inter', sans-serif; line-height: 1;">-</div>
                    </div>
                </div>

                <div style="display: flex; flex-direction: column; gap: 10px;">
                    <button type="button" id="btnConfirmYes" onclick="onConfirmYesClicked()" class="btn-modal-digital" style="width: 100%; justify-content: center; background: linear-gradient(135deg, #059669 0%, #10b981 100%); font-size: 15px; padding: 16px; font-weight: 800; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 8px 20px -4px rgba(16, 185, 129, 0.4);">
                        <x-heroicon-o-check-circle style="width: 20px; height: 20px;" />
                        <span>Ya, Ambil Nomor Antrean</span>
                    </button>
                    <button type="button" id="btnConfirmCancel" onclick="closeConfirmModal()" class="btn-modal-cancel" style="width: 100%; justify-content: center;">
                        <x-heroicon-o-x-circle style="width: 18px; height: 18px;" />
                        <span>Batal / Kembali ke Menu</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Ticket Result Modal (Langsung Tampilkan Hasil Tiket Fisik Saja) -->
    <div class="ticket-modal-overlay" id="ticketModalOverlay" style="{{ session('ticket') ? 'display: flex;' : 'display: none;' }}; z-index: 10001;">
        <div class="receipt-container" style="max-width: 440px;">
            <!-- Modal Card Utama: Menampilkan Hasil Struk Fisik -->
            <div class="confirm-modal-paper" id="ticketChoiceCard" style="background: #ffffff; color: #0f172a; padding: 24px 20px; border-radius: 20px; box-shadow: 0 25px 60px rgba(0, 0, 0, 0.7); text-align: center; border-top: 5px solid #004B9C;">
                
                <!-- Tampilan Struk Tiket Fisik (Identik dengan Hasil Cetak Struk Kertas) -->
                <div id="ticketSuccessCard" style="display: block; padding: 2px 0;">
                    <div style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 14px; padding: 22px 18px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05); margin-bottom: 16px; text-align: center;">
                        <!-- 1. Header: Nomor Antrean & Tanggal -->
                        <div style="margin-bottom: 12px; border-bottom: 1.5px dashed #000000; padding-bottom: 10px;">
                            <div style="font-family: 'Inter', sans-serif; font-weight: 900; font-size: 22px; color: #000000; letter-spacing: 0.5px;">Nomor Antrean</div>
                            <div id="digitalTicketDate" style="font-size: 11px; color: #333333; font-weight: 600; margin-top: 4px;">{{ now()->translatedFormat('l, d F Y') }} · {{ now()->format('H:i') }} WIB</div>
                        </div>

                        <!-- 2. Layanan, Label Nomor Antrean, & Nomor Tiket Besar -->
                        <div style="margin: 12px 0;">
                            <div id="digitalTicketServiceName" style="font-family: 'Inter', sans-serif; font-size: 17px; font-weight: 900; color: #000000; text-transform: uppercase; letter-spacing: 0.5px;">
                                {{ session('ticket') ? (str_starts_with(session('ticket'), 'T-') ? 'TELLER' : 'CUSTOMER SERVICE') : 'TELLER' }}
                            </div>
                            <div style="font-size: 11px; letter-spacing: 1.2px; color: #333333; font-weight: 700; margin-top: 8px;">NOMOR ANTREAN</div>
                            <div id="digitalTicketNumber" style="font-family: 'Inter', sans-serif; font-size: 60px; font-weight: 900; color: #000000; line-height: 1; letter-spacing: 2px; margin: 8px 0 10px;">
                                {{ session('ticket') ?? 'T-001' }}
                            </div>
                        </div>

                        <!-- 3. Footer: Pesan Menunggu & Terima Kasih -->
                        <div style="border-top: 1.5px dashed #000000; padding-top: 10px; margin-top: 10px; font-size: 11px; color: #333333; font-weight: 600; line-height: 1.45;">
                            <div>Silakan menunggu nomor Anda dipanggil.</div>
                            <div style="margin-top: 3px;">Terima kasih atas kunjungan Anda</div>
                        </div>
                    </div>

                    <!-- Tombol Aksi: Cetak Struk Kertas & Kembali ke Menu Utama -->
                    <div style="display: flex; flex-direction: column; gap: 10px;">
                        <button type="button" class="btn-modal-print" onclick="window.print()" style="width: 100%; justify-content: center; background: linear-gradient(135deg, #002B49, #004B9C); padding: 14px; font-weight: 800; border-radius: 14px; color: white; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 8px 20px -4px rgba(0, 43, 73, 0.4);">
                            <x-heroicon-o-printer style="width: 20px; height: 20px;" />
                            <span>Cetak Struk Kertas</span>
                        </button>
                        <button type="button" class="btn-modal-cancel" onclick="closeModal()" style="width: 100%; justify-content: center; font-weight: 800; padding: 13px;">
                            <x-heroicon-o-home style="width: 18px; height: 18px;" />
                            <span>Kembali ke Menu Utama</span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Struk Cetak Thermal (Khusus untuk @media print, Tanpa Gambar Logo/Barcode) -->
            <div class="receipt-paper" id="receiptPaper" style="display: none; text-align: center;">
                <div class="receipt-header" style="margin-bottom: 8px; border-bottom: 1.5px dashed #000000; padding-bottom: 6px;">
                    <div style="font-family: 'Inter', sans-serif; font-weight: 900; font-size: 15px; color: #000000; letter-spacing: 0.5px;">PT BPR DP TASPEN</div>
                    <div id="receiptBranchName" style="font-family: 'Inter', sans-serif; font-weight: 800; font-size: 12px; color: #000000; text-transform: uppercase;">{{ $branch->name }}</div>
                    @if($branch->address)
                    <div id="receiptBranchAddress" style="font-size: 8.5px; color: #222222; margin-top: 2px; line-height: 1.2;">{{ $branch->address }}</div>
                    @endif
                    @if($branch->phone)
                    <div id="receiptBranchPhone" style="font-size: 8.5px; color: #222222;">Telp: {{ $branch->phone }}</div>
                    @endif
                    <div id="ticketModalDate" style="font-size: 9.5px; color: #333333; font-weight: 600; margin-top: 4px;">{{ now()->translatedFormat('l, d F Y') }} · {{ now()->format('H:i') }} WIB</div>
                </div>
                <div style="margin-bottom: 4px;">
                    <div id="printReceiptServiceName" style="font-family: 'Inter', sans-serif; font-size: 14px; font-weight: 800; color: #000000; text-transform: uppercase;">TELLER</div>
                </div>
                <div style="margin: 6px 0;">
                    <div style="font-size: 10px; letter-spacing: 1px; color: #333333; font-weight: 700;">NOMOR ANTREAN</div>
                    <div id="printReceiptNumber" style="font-family: 'Inter', sans-serif; font-size: 46px; font-weight: 900; color: #000000; line-height: 1; letter-spacing: 2px; margin: 4px 0;">T-001</div>
                </div>
                <a id="ticketVerificationLink" @if(session('verification_url')) href="{{ session('verification_url') }}" @else hidden @endif style="font-size: 10px; overflow-wrap: anywhere;">{{ session('verification_url') }}</a>
                <div class="receipt-footer" style="border-top: 1.5px dashed #000000; padding-top: 6px; margin-top: 8px; font-size: 9px; color: #333333; line-height: 1.3;">
                    <div>Silakan menunggu nomor Anda dipanggil.</div>
                    <div style="margin-top: 2px;">Terima kasih atas kunjungan Anda</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bank DP Taspen Footer -->
    <footer class="kiosk-footer">
        <div class="footer-waiting-text">Tekan tombol untuk mengambil tiket</div>
        <div class="footer-hours-item">
            <x-heroicon-o-clock class="kiosk-icon kiosk-icon-sm text-emerald" />
            <span><strong>Jam Layanan:</strong> Senin - Jumat (08.00 - 15.00 WIB)</span>
        </div>
    </footer>

    <script>
        function updateClock() {
            const now = new Date();
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            document.getElementById('clockTime').textContent = `${hours}.${minutes} WIB`;
            document.getElementById('clockDate').textContent = now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        }
        setInterval(updateClock, 1000);
        // Simpan kode cabang ke LocalStorage agar perangkat Kiosk ini selalu mengingat cabangnya
        try {
            localStorage.setItem('bank_kiosk_branch_code', "{{ $branch->code }}");
        } catch (e) {}

        // Real-time Live Polling via /api/display/data/{branch_code} (Every 4 seconds)
        function refreshLiveQueueData() {
            fetch("{{ url('/api/display/data/' . $branch->code) }}")
                .then(res => res.json())
                .then(data => {
                    if (!data) return;
                    
                    // Update Teller Live Stats
                    const tellerServingElem = document.getElementById('liveServingTeller');
                    const tellerWaitElem = document.getElementById('liveWaitingTeller');
                    const tellerNextElem = document.getElementById('liveNextTeller');
                    
                    const tellerWait = data.teller_wait_count ?? data.waiting?.teller;
                    if (tellerWait !== undefined && tellerWaitElem) {
                        tellerWaitElem.textContent = tellerWait;
                    }
                    if (data.next_teller_number && tellerNextElem) {
                        tellerNextElem.textContent = data.next_teller_number;
                    }

                    // Update CS Live Stats
                    const csServingElem = document.getElementById('liveServingCs');
                    const csWaitElem = document.getElementById('liveWaitingCs');
                    const csNextElem = document.getElementById('liveNextCs');
                    
                    const csWait = data.cs_wait_count ?? data.waiting?.cs;
                    if (csWait !== undefined && csWaitElem) {
                        csWaitElem.textContent = csWait;
                    }
                    if (data.next_cs_number && csNextElem) {
                        csNextElem.textContent = data.next_cs_number;
                    }

                    // Currently serving numbers from counters
                    let latestTellerCall = null;
                    let latestTellerTime = 0;
                    let latestCsCall = null;
                    let latestCsTime = 0;

                    if (data.counters && Array.isArray(data.counters)) {
                        data.counters.forEach(c => {
                            const hasActive = c.current_number && c.current_number !== '-' && !String(c.current_number).toLowerCase().includes('belum');
                            if (hasActive) {
                                const time = c.called_at ? new Date(c.called_at).getTime() : 1;
                                if (c.service === 'teller' && time >= latestTellerTime) {
                                    latestTellerTime = time;
                                    latestTellerCall = c.current_number;
                                }
                                if (c.service === 'cs' && time >= latestCsTime) {
                                    latestCsTime = time;
                                    latestCsCall = c.current_number;
                                }
                            }
                        });
                    }

                    // Fallback ke data.teller / data.cs jika ada
                    if (!latestTellerCall && data.teller && data.teller.number && !String(data.teller.number).toLowerCase().includes('belum')) {
                        latestTellerCall = data.teller.number;
                    }
                    if (!latestCsCall && data.cs && data.cs.number && !String(data.cs.number).toLowerCase().includes('belum')) {
                        latestCsCall = data.cs.number;
                    }

                    if (tellerServingElem) {
                        tellerServingElem.textContent = latestTellerCall || 'belum ada antrean';
                    }
                    if (csServingElem) {
                        csServingElem.textContent = latestCsCall || 'belum ada antrean';
                    }
                })
                .catch(err => console.debug('Live sync quiet fail:', err));
        }
        setInterval(refreshLiveQueueData, 4000);

        // Render Functional Barcode & QR Code for Ticket
        function renderBarcodeForNumber(ticketNum) {
            const svg = document.getElementById("ticketBarcode");
            if (!svg || !ticketNum) return;
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

        @if(session('ticket'))
            document.addEventListener("DOMContentLoaded", function() {
                setTimeout(function() {
                    renderBarcodeForNumber("{{ session('ticket') }}");
                }, 100);
            });
        @endif

        let selectedServiceKey = '';
        let selectedServiceLabel = '';
        let selectedCounterName = '';
        let selectedNextNumber = '';
        let yaCountdownInterval = null;
        let finalLoadingInterval = null;

        function resetTakeButtons() {
            document.querySelectorAll('.btn-take').forEach(b => {
                const card = b.closest('.service-card');
                if (card && !card.classList.contains('disabled')) {
                    b.disabled = false;
                }
                b.classList.remove('is-loading');
                const orig = b.getAttribute('data-original-html');
                if (orig) {
                    b.innerHTML = orig;
                }
            });
        }

        let isTakingTicket = false;
        let autoCloseCountdownTimer = null;

        function playKioskTapTone() {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const osc = ctx.createOscillator();
                const gain = ctx.createGain();
                osc.type = 'sine';
                osc.frequency.setValueAtTime(659.25, ctx.currentTime); // E5
                osc.frequency.exponentialRampToValueAtTime(880, ctx.currentTime + 0.1); // A5
                gain.gain.setValueAtTime(0.2, ctx.currentTime);
                gain.gain.exponentialRampToValueAtTime(0.01, ctx.currentTime + 0.1);
                osc.connect(gain);
                gain.connect(ctx.destination);
                osc.start();
                osc.stop(ctx.currentTime + 0.1);
            } catch (e) {}
        }

        // 1-Tap Instant Ticket: Sekali sentuh tombol langsung menerbitkan & mencetak tiket
        function takeTicketInstant(btn) {
            if (isTakingTicket) return;
            isTakingTicket = true;

            const serviceKey = btn.getAttribute('data-service');
            const serviceLabel = btn.getAttribute('data-label');
            const counterName = btn.getAttribute('data-counter');
            selectedServiceKey = serviceKey;
            selectedServiceLabel = serviceLabel;
            selectedCounterName = counterName;

            playKioskTapTone();

            // Ubah tampilan tombol saat proses
            const originalHtml = btn.innerHTML;
            btn.disabled = true;
            btn.style.opacity = '0.85';
            btn.innerHTML = `
                <div style="width: 22px; height: 22px; border: 2.5px solid rgba(255,255,255,0.4); border-top-color: white; border-radius: 50%; animation: spinRing 0.8s linear infinite;"></div>
                <span>Mencetak Tiket...</span>
            `;

            const csrfToken = document.querySelector('input[name="_token"]')?.value 
                || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

            fetch("{{ url('/ambil-nomor') }}", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-CSRF-TOKEN": csrfToken
                },
                body: JSON.stringify({
                    service: serviceKey,
                    branch_id: {{ $branch->id }}
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data && data.success) {
                    showPhysicalTicketResult(data.ticket, data);
                    refreshLiveQueueData();
                } else {
                    alert(data?.message || 'Mohon maaf, layanan sedang sibuk. Silakan coba tekan tombol kembali.');
                }
            })
            .catch(err => {
                console.error("Gagal simpan antrean:", err);
                alert('Mohon maaf, terjadi gangguan koneksi ke server loket. Silakan tekan tombol sekali lagi.');
            })
            .finally(() => {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
                btn.style.opacity = '1';
                isTakingTicket = false;
            });
        }

        // Tampilkan struk fisik di layar & cetak kertas otomatis
        function showPhysicalTicketResult(finalTicketNumber, data = null) {
            const ticketOverlay = document.getElementById('ticketModalOverlay');
            const sName = (data && data.service_name) ? data.service_name : (selectedServiceKey === 'teller' ? 'TELLER' : 'CUSTOMER SERVICE');

            const digitalService = document.getElementById('digitalTicketServiceName');
            const digitalNum = document.getElementById('digitalTicketNumber');
            const printSName = document.getElementById('printReceiptServiceName');
            const printNum = document.getElementById('printReceiptNumber');

            if (digitalService) digitalService.textContent = sName;
            if (digitalNum) digitalNum.textContent = finalTicketNumber;
            if (printSName) printSName.textContent = sName;
            if (printNum) printNum.textContent = finalTicketNumber;

            const verificationLink = document.getElementById('ticketVerificationLink');
            if (verificationLink) {
                verificationLink.hidden = !data?.verification_url;
                if (data?.verification_url) {
                    verificationLink.href = data.verification_url;
                    verificationLink.textContent = data.verification_url;
                }
            }

            if (data) {
                const branchNameEl = document.getElementById('receiptBranchName');
                const branchAddressEl = document.getElementById('receiptBranchAddress');
                const branchPhoneEl = document.getElementById('receiptBranchPhone');
                const ticketDateEl = document.getElementById('ticketModalDate');

                if (branchNameEl && data.branch_name) branchNameEl.textContent = data.branch_name;
                if (branchAddressEl && data.branch_address) branchAddressEl.textContent = data.branch_address;
                if (branchPhoneEl && data.branch_phone) branchPhoneEl.textContent = 'Telp: ' + data.branch_phone;
                if (ticketDateEl && data.date_formatted) ticketDateEl.textContent = data.date_formatted;
            }

            if (ticketOverlay) {
                ticketOverlay.style.display = 'flex';
                ticketOverlay.style.opacity = '1';
            }

            // Otomatis cetak ke printer thermal kertas
            try {
                window.print();
            } catch (e) {
                console.log("Print:", e);
            }

            // Countdown 6 detik otomatis kembali ke layar utama
            let remainingSeconds = 6;
            const cancelBtn = document.querySelector('.btn-modal-cancel span');
            if (autoCloseCountdownTimer) clearInterval(autoCloseCountdownTimer);
            autoCloseCountdownTimer = setInterval(() => {
                remainingSeconds--;
                if (cancelBtn) {
                    cancelBtn.textContent = `Kembali ke Layar Utama (${remainingSeconds}s)`;
                }
                if (remainingSeconds <= 0) {
                    clearInterval(autoCloseCountdownTimer);
                    closeModal();
                }
            }, 1000);
        }

        function closeModal() {
            if (autoCloseCountdownTimer) clearInterval(autoCloseCountdownTimer);
            const ticketOverlay = document.getElementById('ticketModalOverlay');
            if (ticketOverlay) {
                ticketOverlay.style.display = 'none';
            }
            const cancelBtn = document.querySelector('.btn-modal-cancel span');
            if (cancelBtn) {
                cancelBtn.textContent = 'Kembali ke Menu Utama';
            }
        }

        // URL preview handlers for testing modal & loading screen
        document.addEventListener("DOMContentLoaded", function() {
            const urlParams = new URLSearchParams(window.location.search);
            if (urlParams.has('preview_confirm')) {
                showConfirmModal('teller', 'Teller (Setoran, Penarikan, Transaksi Tunai)', 'Loket 1 / 2', '', 'T-005');
            } else if (urlParams.has('preview_ya')) {
                showConfirmModal('teller', 'Teller (Setoran, Penarikan, Transaksi Tunai)', 'Loket 1 / 2', '', 'T-005');
                onConfirmYesClicked();
            } else if (urlParams.has('preview_ticket')) {
                selectedServiceKey = 'cs';
                selectedServiceLabel = 'Customer Service & Kredit Pensiun';
                selectedCounterName = 'Loket CS - Kredit & Pensiun';
                selectedNextNumber = 'CS-014';
                showPhysicalTicketResult('CS-014');
            } else if (urlParams.has('preview_digital') || urlParams.has('preview_loading')) {
                selectedServiceKey = 'teller';
                selectedServiceLabel = 'Teller (Setoran, Penarikan, Transaksi Tunai)';
                selectedCounterName = 'Loket 1 / 2';
                selectedNextNumber = 'T-019';
                showPhysicalTicketResult('T-019');
            }
        });
    </script>

    <!-- Blade Heroicon Templates (No Hardcoded SVGs) -->
    <div id="kioskHeroiconTemplates" style="display: none;">
        <div id="tplKioskCheckIcon"><x-heroicon-o-check-circle style="width: 20px; height: 20px;" /></div>
        <div id="tplKioskSuccessCheckIcon"><x-heroicon-o-check-circle style="width: 38px; height: 38px;" /></div>
    </div>
</body>
</html>
