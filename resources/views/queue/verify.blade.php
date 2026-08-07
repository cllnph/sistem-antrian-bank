<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Verifikasi Tiket Antrean - PT BPR DP TASPEN</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800;900&family=Outfit:wght@500;700;800;900&display=swap" rel="stylesheet">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background: radial-gradient(circle at top right, #1e3a8a 0%, #0f2b5c 40%, #040d1a 100%);
            color: #f8fafc;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 24px 16px;
        }

        .verify-card {
            width: min(440px, 100%);
            background: #ffffff;
            color: #0f172a;
            border-radius: 24px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.6);
            padding: 32px 24px;
            text-align: center;
            position: relative;
            overflow: hidden;
            animation: fadeIn 0.4s ease-out;
        }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }

        .top-gradient-stripe {
            height: 6px;
            background: linear-gradient(90deg, #002B49, #0072CE, #D4AF37);
            margin: -32px -24px 20px -24px;
        }

        .bank-logo-header {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 12px;
        }

        .verify-subtitle {
            font-size: 10.5px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            background: #002B49;
            color: #ffffff;
            padding: 4px 14px;
            border-radius: 20px;
            display: inline-block;
            margin-bottom: 20px;
        }

        .status-verified-badge {
            background: #ecfdf5;
            border: 1px solid #a7f3d0;
            color: #047857;
            padding: 10px 16px;
            border-radius: 14px;
            font-size: 12.5px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 20px;
        }

        .status-unverified-badge {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 10px 16px;
            border-radius: 14px;
            font-size: 12.5px;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-bottom: 20px;
        }

        .ticket-number-label {
            font-size: 11px;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .ticket-number-display {
            font-family: 'Outfit', sans-serif;
            font-size: 54px;
            font-weight: 900;
            color: #002B49;
            letter-spacing: 2px;
            margin: 4px 0 16px 0;
            line-height: 1;
        }

        .live-status-box {
            padding: 14px 18px;
            border-radius: 16px;
            margin-bottom: 20px;
            font-size: 13px;
            font-weight: 700;
        }
        .live-status-waiting { background: #fffbeb; border: 1px solid #fde68a; color: #b45309; }
        .live-status-called { background: #e0f2fe; border: 1px solid #7dd3fc; color: #0369a1; animation: pulse 2s infinite; }
        .live-status-serving { background: #e0e7ff; border: 1px solid #a5b4fc; color: #4338ca; }
        .live-status-completed { background: #f0fdf4; border: 1px solid #86efac; color: #15803d; }
        .live-status-skipped { background: #f1f5f9; border: 1px solid #cbd5e1; color: #64748b; }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.02); }
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            margin-bottom: 20px;
            text-align: left;
        }
        .info-table td {
            padding: 8px 12px;
            border-bottom: 1px dashed #e2e8f0;
        }
        .info-table td:first-child {
            color: #64748b;
            font-weight: 600;
            width: 45%;
        }
        .info-table td:last-child {
            color: #0f172a;
            font-weight: 800;
        }

        .btn-kiosk {
            display: inline-block;
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #002B49, #0072CE);
            color: #ffffff;
            border: none;
            border-radius: 14px;
            font-family: 'Outfit', sans-serif;
            font-size: 14px;
            font-weight: 800;
            text-decoration: none;
            cursor: pointer;
            box-shadow: 0 6px 16px rgba(0, 114, 206, 0.3);
            transition: all 0.2s ease;
        }
        .btn-kiosk:hover {
            filter: brightness(1.1);
            transform: translateY(-2px);
        }

        .footer-note {
            font-size: 10px;
            color: #94a3b8;
            margin-top: 16px;
            line-height: 1.4;
        }
    </style>
</head>
<body>
    <div class="verify-card">
        <div class="top-gradient-stripe"></div>

        <div class="bank-logo-header">
            @include('components.bank-logo', ['height' => '52px'])
        </div>

        <div class="verify-subtitle">Sistem Verifikasi Antrean Real-Time</div>

        @if($queue)
            <div class="status-verified-badge">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                <span>VERIFIED · TIKET SAH & RESMI</span>
            </div>

            <div class="ticket-number-label">NOMOR ANTREAN</div>
            <div class="ticket-number-display">{{ $queue->number }}</div>

            @php
                $statusKey = is_object($queue->status) ? $queue->status->value : $queue->status;
            @endphp

            @if($statusKey === 'waiting')
                <div class="live-status-box live-status-waiting">
                    ⏳ <strong>Sedang Menunggu Dipanggil</strong>
                    @if($waitingBefore > 0)
                        <div style="font-size: 11.5px; margin-top: 4px; font-weight: 600;">Sisa {{ $waitingBefore }} antrean lagi di depan Anda</div>
                    @else
                        <div style="font-size: 11.5px; margin-top: 4px; font-weight: 600;">Anda berada di urutan berikutnya! Silakan bersiap.</div>
                    @endif
                </div>
            @elseif($statusKey === 'called')
                <div class="live-status-box live-status-called">
                    📢 <strong>SEDANG DIPANGGIL DI LOKET!</strong>
                    <div style="font-size: 11.5px; margin-top: 4px; font-weight: 600;">Silakan segera menuju {{ $queue->serviceSetting?->counter_name ?? 'Loket Layanan' }}</div>
                </div>
            @elseif($statusKey === 'serving')
                <div class="live-status-box live-status-serving">
                    🖥️ <strong>Sedang Dilayani Petugas</strong>
                    <div style="font-size: 11.5px; margin-top: 4px; font-weight: 600;">{{ $queue->serviceSetting?->counter_name ?? 'Loket Layanan' }}</div>
                </div>
            @elseif($statusKey === 'completed')
                <div class="live-status-box live-status-completed">
                    ✅ <strong>Selesai Dilayani</strong>
                    <div style="font-size: 11.5px; margin-top: 4px; font-weight: 600;">Terima kasih atas kunjungan Anda</div>
                </div>
            @else
                <div class="live-status-box live-status-skipped">
                    ⏩ <strong>Status: Dilewati / Batal</strong>
                </div>
            @endif

            <table class="info-table">
                <tr>
                    <td>Layanan:</td>
                    <td>{{ $queue->serviceSetting?->label ?? 'Layanan Bank' }}</td>
                </tr>
                <tr>
                    <td>Loket Tujuan:</td>
                    <td>{{ $queue->serviceSetting?->counter_name ?? '-' }}</td>
                </tr>
                <tr>
                    <td>Waktu Pendaftaran:</td>
                    <td>{{ $queue->created_at?->format('d/m/Y H:i') }} WIB</td>
                </tr>
                @if($queue->called_at)
                    <tr>
                        <td>Waktu Dipanggil:</td>
                        <td>{{ $queue->called_at->format('H:i:s') }} WIB</td>
                    </tr>
                @endif
                @if($queue->servedBy)
                    <tr>
                        <td>Petugas Layanan:</td>
                        <td>{{ $queue->servedBy->name }}</td>
                    </tr>
                @endif
            </table>
        @else
            <div class="status-unverified-badge">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>
                <span>TIKET TIDAK DITEMUKAN</span>
            </div>
            <p style="font-size: 13px; color: #64748b; margin-bottom: 20px; line-height: 1.5;">
                Nomor tiket <strong>{{ $number }}</strong> tidak terdaftar pada sistem antrean operasional PT BPR DANA POS TASPEN.
            </p>
        @endif

        <a href="{{ route('queue.take-number') }}" class="btn-kiosk">
            🏠 Ambil Tiket Baru / Ke Kiosk
        </a>

        <div class="footer-note">
            PT BPR DANA POS TASPEN · Terdaftar & Diawasi OJK<br>
            📞 Call Center MAYA: <strong>150181</strong>
        </div>
    </div>

    @if($queue && in_array(is_object($queue->status) ? $queue->status->value : $queue->status, ['waiting', 'called'], true))
        <script>
            // Live Auto-Refresh every 3s to update call status automatically on smartphone screen
            setTimeout(function() {
                window.location.reload();
            }, 3000);
        </script>
    @endif
</body>
</html>
