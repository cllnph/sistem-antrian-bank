<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Kiosk Antrean Tiket - PT BPR DP TASPEN</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
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
        @media (max-height: 560px), (max-width: 1024px) {
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

        #ticketPrintArea { display: none; }
        #receiptPaper {
            font-family: Arial, Helvetica, sans-serif;
            text-align: center;
            color: #000;
            line-height: 1.35;
            overflow-wrap: break-word;
        }
        #receiptPaper [hidden] { display: none !important; }
        .thermal-header { border-bottom: 1px dashed #000; padding-bottom: 2.5mm; }
        .thermal-bank-logo { display: block; width: 46mm; max-width: 100%; height: auto; margin: 0 auto 2mm; }
        .thermal-branch { font-size: 9pt; font-weight: 700; margin-top: 1mm; }
        .thermal-address { font-size: 8pt; line-height: 1.4; margin-top: 1.5mm; }
        .thermal-contact { font-size: 8pt; margin-top: 1mm; }
        .thermal-date { font-size: 8pt; margin-top: 2mm; }
        .thermal-ticket { padding: 3mm 0; break-inside: avoid; }
        .thermal-service { font-size: 10pt; font-weight: 700; text-transform: uppercase; }
        .thermal-label { font-size: 8pt; margin-top: 2mm; letter-spacing: 0.5pt; }
        .thermal-number { font-size: 34pt; font-weight: 900; line-height: 1.15; margin-top: 1mm; white-space: nowrap; }
        .thermal-footer { border-top: 1px dashed #000; padding-top: 2mm; font-size: 8pt; line-height: 1.5; }

        /* Thermal Printer POS Optimizations */
        @page { margin: 0; }
        @media print {
            html, body {
                width: 100% !important;
                height: auto !important;
                min-height: 0 !important;
                margin: 0 !important;
                padding: 0 !important;
                overflow: visible !important;
            }
            body {
                display: block !important;
                background: #ffffff !important;
                color: #000000 !important;
            }
            body > :not(#ticketPrintArea) { display: none !important; }
            #ticketPrintArea {
                display: block !important;
                width: 100% !important;
                max-width: 80mm !important;
                margin: 0 auto !important;
                position: static !important;
            }
            #ticketPrintArea, #ticketPrintArea * {
                animation: none !important;
                transition: none !important;
                opacity: 1 !important;
                visibility: visible !important;
                transform: none !important;
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
                height: auto !important;
                min-height: 0 !important;
                overflow: visible !important;
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
                padding: 3mm !important;
                border: none !important;
                margin: 0 !important;
                transform: none !important;
                animation: none !important;
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

        <dialog id="browserReceiptDialog" style="position:fixed;inset:0;margin:auto;width:min(460px,calc(100vw - 32px));max-height:90vh;overflow:auto;box-sizing:border-box;border:1px solid #cbd5e1;border-radius:16px;padding:20px;color:#0f172a;">
            <h2 id="browserReceiptTitle" style="font-size:20px;margin:0 0 8px;">Tiket siap dicetak</h2>
            <p id="browserReceiptMessage" role="status" style="font-size:14px;margin-bottom:12px;">Pilih printer PC ini pada dialog cetak. Jika dibatalkan, cetak kembali tiket yang sama di sini.</p>
            <iframe id="browserReceiptFrame" title="Struk tiket" style="width:100%;height:420px;border:1px solid #e2e8f0;background:white;"></iframe>
            <div style="display:flex;justify-content:flex-end;gap:12px;flex-wrap:wrap;margin-top:12px;">
                <button type="button" onclick="document.getElementById('browserReceiptDialog').close()" style="padding:10px 16px;cursor:pointer;">Tutup</button>
                <button type="button" id="browserReceiptPrint" onclick="printBrowserReceipt()" disabled style="padding:10px 16px;cursor:pointer;background:#1e40af;color:white;border:0;border-radius:8px;">Cetak tiket ini</button>
            </div>
        </dialog>

        @if(session('error'))
            <div style="background: #fef2f2; border: 1.5px solid #fecaca; color: #b91c1c; padding: 14px 20px; border-radius: 16px; margin-bottom: 20px; text-align: center; font-weight: 700; display: flex; align-items: center; justify-content: center; gap: 8px;">
                <x-heroicon-o-exclamation-triangle class="kiosk-icon kiosk-icon-lg" style="color: #dc2626;" />
                <span>{{ session('error') }}</span>
            </div>
        @endif

        @php
            $isServiceHours = $operations['is_open'];

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
                                    <span>Pengambilan tiket: {{ $operations['label'] }}.</span>
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
                                    <span>Pengambilan tiket: {{ $operations['label'] }}.</span>
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

    <!-- Hidden on screen; revealed only by the print stylesheet. -->
    <div id="ticketPrintArea">
        <div class="receipt-container">
            <!-- Thermal ticket: uses the printer's configured paper width. -->
            <div class="receipt-paper" id="receiptPaper">
                <div class="thermal-header">
                    <img class="thermal-bank-logo" src="{{ asset('images/bank-dp-taspen-logo.png') }}" alt="Bank DP Taspen" loading="eager">
                    <div id="receiptBranchName" class="thermal-branch">{{ $branch->name }}</div>
                    <div id="receiptBranchAddress" class="thermal-address" @if(!$branch->address) hidden @endif>{{ $branch->address }}</div>
                    <div id="receiptBranchPhone" class="thermal-contact" @if(!$branch->phone || $branch->type !== 'Kantor Pusat') hidden @endif>@if($branch->phone && $branch->type === 'Kantor Pusat')Telp: {{ $branch->phone }}@endif</div>
                </div>
                <div id="ticketModalDate" class="thermal-date">{{ now()->translatedFormat('l, d F Y') }} | {{ now()->format('H:i') }} WIB</div>
                <div class="thermal-ticket">
                    <div id="printReceiptServiceName" class="thermal-service">{{ session('ticket') && !str_starts_with(session('ticket'), 'T-') ? 'CUSTOMER SERVICE' : 'TELLER' }}</div>
                    <div class="thermal-label">NOMOR ANTREAN</div>
                    <div id="printReceiptNumber" class="thermal-number">{{ session('ticket') ?? 'T-001' }}</div>
                </div>
                <div class="thermal-footer">
                    <div>Silakan menunggu nomor Anda dipanggil.</div>
                    <div>Terima kasih atas kunjungan Anda.</div>
                </div>
            </div>
        </div>
    </div>

    <button type="button" id="startFreshRequest" hidden onclick="startFreshRequest()" style="padding:10px 16px;cursor:pointer;position:relative;z-index:20">Mulai permintaan baru</button>
    <!-- Bank DP Taspen Footer -->
    <footer class="kiosk-footer">
        <div class="footer-waiting-text">Tekan tombol untuk mengambil tiket</div>
        <div class="footer-hours-item">
            <x-heroicon-o-clock class="kiosk-icon kiosk-icon-sm text-emerald" />
            {{-- Informasi layanan nasabah; jadwal sistem tetap diatur per cabang. --}}
            <span><strong>Jam Layanan:</strong> Senin–Jumat 08.00–15.00 WIB</span>
        </div>
        <button type="button" id="lastBrowserTicket" hidden onclick="reopenLastReceipt()" style="cursor:pointer;color:#004B9C;background:transparent;border:0;text-decoration:underline;font-size:12px;">Cetak tiket terakhir</button>
        <button type="button" id="kioskRequestRecovery" hidden onclick="location.reload()" style="cursor:pointer;padding:8px;">Muat ulang sesi, pertahankan permintaan</button>
    </footer>

    <script>
        const issuanceContext = @json($issuanceContext);
        const pagePeriodId = @json($operations['period_id']);
        let operations = @json($operations);
        let serverOffset = Date.parse(operations.server_time) - Date.now();
        let periodExpired = false;
        function expiredPeriod() {
            periodExpired = true;
            document.getElementById('startFreshRequest').hidden = false;
            document.getElementById('kioskRequestRecovery').hidden = false;
        }
        function startFreshRequest() {
            if (!confirm('Periode permintaan lama telah berakhir. Muat halaman periode terbaru? Setelah dimuat, pilih layanan untuk mengambil nomor baru.')) return;
            sessionStorage.removeItem('ticket-request-{{ $branch->id }}');
            sessionStorage.removeItem('browser-ticket-{{ $branch->id }}');
            location.reload();
        }
        function applyOperations(data) {
            if (!data.operations) return;
            operations = data.operations;
            serverOffset = Date.parse(operations.server_time) - Date.now();
            if (operations.period_id !== pagePeriodId) {
                if (!isTakingTicket && !sessionStorage.getItem('ticket-request-{{ $branch->id }}') && !document.querySelector('dialog[open]')) { location.reload(); return; }
                expiredPeriod();
            }
            if (isTakingTicket) return;
            document.querySelectorAll('.btn-take').forEach(btn => {
                const active = !periodExpired && operations.is_open && data.settings?.[btn.dataset.service]?.is_active;
                btn.disabled = !active;
                btn.querySelector('span').textContent = active ? 'Ambil Tiket' : (periodExpired ? 'Periode berakhir' : 'Layanan Tutup');
                const card = btn.closest('.service-card');
                card.classList.toggle('disabled', !active);
                const note = card.querySelector('.closed-notice');
                if (note) note.style.display = active ? 'none' : 'flex';
            });
        }
        // Android browsers that support fetch may not yet expose AbortSignal.timeout.
        // Clear each timer when its request completes, including failed requests.
        function kioskFetch(address, options = {}, timeoutMs = 15000) {
            const controller = new AbortController();
            const timer = setTimeout(() => controller.abort(), timeoutMs);
            return fetch(address, {...options, signal: controller.signal})
                .finally(() => clearTimeout(timer));
        }

        function updateClock() {
            const now = new Date(Date.now() + serverOffset);
            document.getElementById('clockTime').textContent = new Intl.DateTimeFormat('id-ID', {timeZone:'Asia/Jakarta',hour:'2-digit',minute:'2-digit',hour12:false}).format(now) + ' WIB';
            document.getElementById('clockDate').textContent = now.toLocaleDateString('id-ID', {timeZone:'Asia/Jakarta',weekday:'long',day:'numeric',month:'long',year:'numeric'});
        }
        updateClock();
        setInterval(updateClock, 1000);
        // Simpan kode cabang ke LocalStorage agar perangkat Kiosk ini selalu mengingat cabangnya
        try {
            localStorage.setItem('bank_kiosk_branch_code', "{{ $branch->code }}");
        } catch (e) {}

        // Real-time Live Polling via /api/display/data/{branch_code} (Every 4 seconds)
        let isRefreshingQueueData = false;
        function refreshLiveQueueData() {
            if (isRefreshingQueueData) return;
            isRefreshingQueueData = true;
            return kioskFetch("{{ url('/api/display/data/' . $branch->code) }}")
                .then(res => res.json())
                .then(data => {
                    if (!data) return;
                    applyOperations(data);
                    
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
                .catch(err => console.debug('Live sync quiet fail:', err))
                .finally(() => { isRefreshingQueueData = false; });
        }
        setInterval(refreshLiveQueueData, 4000);

        @if(session('ticket'))
            document.addEventListener('DOMContentLoaded', () => {
                refreshLiveQueueData();
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

            const pendingKey = 'ticket-request-{{ $branch->id }}';
            let pending;
            try {
                pending = JSON.parse(sessionStorage.getItem(pendingKey) || 'null');
                if (pending && pending.service !== serviceKey) {
                    alert('Permintaan sebelumnya belum selesai. Tekan kembali tombol layanan sebelumnya untuk memeriksa nomor yang sama.');
                    btn.innerHTML = originalHtml; btn.disabled = false; btn.style.opacity = '1'; isTakingTicket = false;
                    return;
                }
                // A pending request from the earlier installer/UI has no context yet.
                // A deliberate reload supplies it while preserving the original UUID.
                if (pending && !pending.issuance_context) {
                    pending.issuance_context = issuanceContext;
                    pending.period_id = pagePeriodId;
                    sessionStorage.setItem(pendingKey, JSON.stringify(pending));
                }
                if (!pending) {
                    const bytes = crypto.getRandomValues(new Uint8Array(16));
                    bytes[6] = (bytes[6] & 15) | 64; bytes[8] = (bytes[8] & 63) | 128;
                    const hex = [...bytes].map(b => b.toString(16).padStart(2, '0')).join('');
                    pending = {service: serviceKey, issuance_context: issuanceContext, period_id: pagePeriodId, request_id: `${hex.slice(0,8)}-${hex.slice(8,12)}-${hex.slice(12,16)}-${hex.slice(16,20)}-${hex.slice(20)}`};
                    sessionStorage.setItem(pendingKey, JSON.stringify(pending));
                }
            } catch (error) {
                alert('Penyimpanan sesi browser tidak tersedia. Hubungi petugas sebelum mengambil tiket.');
                btn.innerHTML = originalHtml; btn.disabled = false; btn.style.opacity = '1'; isTakingTicket = false;
                return;
            }

            kioskFetch(@json(route('queue.store-number', ['branch' => $branch->code], false)), {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    "Accept": "application/json",
                    "X-CSRF-TOKEN": csrfToken
                },
                body: JSON.stringify({
                    service: serviceKey,
                    request_id: pending.request_id,
                    issuance_context: pending.issuance_context || '',
                    branch_id: {{ $branch->id }}
                })
            }, 20000)
            .then(async res => ({status: res.status, data: await res.json()}))
            .then(async ({status, data}) => {
                if (data && data.success) {
                    if (await printIssuedTicket(data.ticket, data)) sessionStorage.removeItem(pendingKey);
                    refreshLiveQueueData();
                } else {
                    if (![403, 409, 410, 419].includes(status) && (data?.success === false || status === 422)) sessionStorage.removeItem(pendingKey);
                    if (status === 410) expiredPeriod();
                    if ([403, 409, 419].includes(status)) document.getElementById('kioskRequestRecovery').hidden = false;
                    const explanation = status === 419 ? 'Sesi halaman kedaluwarsa. Muat ulang halaman lalu tekan layanan yang sama; permintaan sebelumnya tetap diperiksa.' : data?.message;
                    alert(explanation || 'Mohon maaf, layanan sedang sibuk. Silakan coba tekan tombol kembali.');
                }
            })
            .catch(err => {
                console.error("Gagal simpan antrean:", err);
                alert('Koneksi terputus. Tekan layanan yang sama untuk memeriksa permintaan sebelumnya; nomor baru tidak dibuat otomatis.');
            })
            .finally(() => {
                btn.innerHTML = originalHtml;
                btn.disabled = false;
                btn.style.opacity = '1';
                isTakingTicket = false;
                refreshLiveQueueData();
            });
        }

        const lastReceiptKey = 'browser-ticket-{{ $branch->id }}';
        let lastBrowserReceipt = null;
        try { lastBrowserReceipt = JSON.parse(sessionStorage.getItem(lastReceiptKey) || 'null'); } catch (error) {}
        if (lastBrowserReceipt) document.getElementById('lastBrowserTicket').hidden = false;

        function printBrowserReceipt() {
            const frame = document.getElementById('browserReceiptFrame');
            try {
                if (!frame.contentDocument.querySelector('meta[name="bank-ticket"]')) throw new Error('receipt');
                frame.contentWindow.focus();
                frame.contentWindow.print();
                document.getElementById('browserReceiptMessage').textContent = 'Periksa hasil pada printer. Jika dialog dibatalkan atau kertas belum keluar, gunakan Cetak tiket ini untuk nomor yang sama.';
            } catch (error) {
                document.getElementById('browserReceiptMessage').textContent = 'Struk belum dapat dibuka. Tekan Cetak tiket terakhir untuk memuat ulang nomor yang sama.';
            }
        }

        function openBrowserReceipt(receipt, autoPrint = false) {
            lastBrowserReceipt = receipt;
            try { sessionStorage.setItem(lastReceiptKey, JSON.stringify(receipt)); } catch (error) {}
            document.getElementById('lastBrowserTicket').hidden = false;
            document.getElementById('browserReceiptTitle').textContent = 'Tiket ' + receipt.ticket;
            const dialog = document.getElementById('browserReceiptDialog');
            if (!dialog.open) dialog.showModal();
            const frame = document.getElementById('browserReceiptFrame');
            document.getElementById('browserReceiptPrint').disabled = true;
            document.getElementById('browserReceiptMessage').textContent = 'Memuat struk. Pilih printer PC ini pada dialog cetak.';
            frame.onload = () => {
                try {
                    if (!frame.contentDocument.querySelector('meta[name="bank-ticket"]')) throw new Error('receipt');
                    document.getElementById('browserReceiptPrint').disabled = false;
                    if (autoPrint) printBrowserReceipt();
                } catch (error) {
                    document.getElementById('browserReceiptMessage').textContent = 'Struk belum dapat dimuat. Tekan Cetak tiket terakhir untuk mencoba lagi tanpa membuat nomor baru.';
                }
            };
            try {
                const status = awaitReceiptStatus(receipt.id);
                status.then(ok => { if (ok) frame.src = receipt.url; });
            } catch (error) { document.getElementById('browserReceiptMessage').textContent = 'Koneksi terputus. Periksa kembali nomor yang sama.'; }
        }

        async function awaitReceiptStatus(id) {
            try {
                const response = await kioskFetch(@json(url('/print-jobs')) + '/' + id, {headers:{Accept:'application/json'}});
                if (response.status === 410) {
                    expiredPeriod();
                    document.getElementById('browserReceiptMessage').textContent = 'Periode tiket sudah berakhir. Struk telah dibersihkan. Tutup dialog dan pilih Mulai permintaan baru.';
                    return false;
                }
                if (!response.ok) {
                    document.getElementById('browserReceiptMessage').textContent = 'Struk tidak dapat diakses dari browser ini. Periksa sesi atau hubungi petugas.';
                    return false;
                }
                return true;
            } catch (error) {
                document.getElementById('browserReceiptMessage').textContent = 'Koneksi terputus. Tekan Cetak tiket terakhir untuk memeriksa nomor yang sama.';
                return false;
            }
        }

        function reopenLastReceipt() {
            if (lastBrowserReceipt) openBrowserReceipt(lastBrowserReceipt);
        }

        async function printIssuedTicket(finalTicketNumber, data = null) {
            let job = data?.print_job;
            if (job?.transport === 'browser' && job.receipt_url) {
                // Replaying a response must not reopen a print dialog for a ticket already shown.
                const autoPrint = lastBrowserReceipt?.id !== job.id;
                openBrowserReceipt({id: job.id, ticket: finalTicketNumber, url: job.receipt_url}, autoPrint);
                return true;
            }
            if (!job?.status_url) {
                alert('Tiket ' + finalTicketNumber + ' sudah diterbitkan. Hubungi petugas untuk memeriksa cetaknya.');
                return false;
            }
            const deadline = Date.now() + 70000;
            while (['queued', 'processing'].includes(job.status) && Date.now() < deadline) {
                await new Promise(resolve => setTimeout(resolve, 1000));
                try {
                    const response = await kioskFetch(job.status_url, {headers: {'Accept': 'application/json'}}, 10000);
                    if (response.status === 410) { expiredPeriod(); alert('Periode tiket sudah berakhir. Pilih Mulai permintaan baru untuk periode berikutnya.'); return false; }
                    if (response.ok) job = (await response.json()).print_job;
                } catch (error) { /* Keep the same request; never issue a replacement. */ }
            }
            if (job.status === 'submitted') return true;
            alert('Tiket ' + finalTicketNumber + ': ' + (['failed', 'unknown', 'expired'].includes(job.status) ? job.message : 'Status cetak belum dapat dipastikan. Hubungi petugas atau tekan layanan yang sama untuk memeriksa nomor ini.'));
            return ['failed', 'unknown', 'expired'].includes(job.status);
        }
    </script>

    <!-- Blade Heroicon Templates (No Hardcoded SVGs) -->
    <div id="kioskHeroiconTemplates" style="display: none;">
        <div id="tplKioskCheckIcon"><x-heroicon-o-check-circle style="width: 20px; height: 20px;" /></div>
        <div id="tplKioskSuccessCheckIcon"><x-heroicon-o-check-circle style="width: 38px; height: 38px;" /></div>
    </div>
</body>
</html>
