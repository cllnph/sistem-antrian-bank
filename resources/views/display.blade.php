<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Layar Informasi Pemanggilan Antrean - PT BPR DP TASPEN</title>
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
            justify-content: space-between;
            overflow: hidden;
        }

        /* TASPEN Corporate Ticker Bar */
        .ticker-bar {
            background: linear-gradient(90deg, #001A2E 0%, #002B49 50%, #001A2E 100%);
            border-bottom: 2px solid #D4AF37;
            color: #f8fafc;
            font-size: 13px;
            font-weight: 700;
            padding: 8px 0;
            overflow: hidden;
            white-space: nowrap;
            display: flex;
            align-items: center;
        }
        .ticker-content {
            display: inline-block;
            animation: ticker 40s linear infinite;
        }
        @keyframes ticker {
            0% { transform: translateX(100vw); }
            100% { transform: translateX(-100%); }
        }

        header.tv-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 40px;
            background: rgba(2, 18, 44, 0.7);
            backdrop-filter: blur(16px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }
        .brand { display: flex; align-items: center; gap: 18px; }
        .brand-logo {
            width: 56px;
            height: 56px;
            background: linear-gradient(135deg, #002B49 0%, #0072CE 100%);
            border: 2px solid rgba(212, 175, 55, 0.7);
            border-radius: 16px;
            display: grid;
            place-items: center;
            box-shadow: 0 0 25px rgba(0, 114, 206, 0.5);
        }
        .brand-logo svg { width: 30px; height: 30px; fill: #D4AF37; }
        .brand-info h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 25px;
            font-weight: 900;
            letter-spacing: -0.5px;
            color: #ffffff;
        }
        .brand-info h1 span { color: #F59E0B; }
        .brand-info p { font-size: 13px; color: #94a3b8; }

        .header-controls {
            display: flex;
            align-items: center;
            gap: 24px;
        }
        .audio-toggle {
            background: rgba(0, 114, 206, 0.15);
            border: 1px solid rgba(0, 114, 206, 0.4);
            color: #38bdf8;
            padding: 10px 20px;
            border-radius: 24px;
            font-family: 'Outfit', sans-serif;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .audio-toggle:hover { background: rgba(0, 114, 206, 0.3); color: white; }
        .audio-toggle.active { background: rgba(34, 197, 94, 0.2); border-color: rgba(34, 197, 94, 0.5); color: #4ade80; }

        /* Sound Wave Animation Visualizer */
        .sound-wave {
            display: flex;
            align-items: flex-end;
            gap: 3px;
            height: 16px;
        }
        .sound-wave span {
            width: 3px;
            height: 100%;
            background: #4ade80;
            border-radius: 3px;
            animation: wave 1s ease-in-out infinite;
        }
        .sound-wave span:nth-child(1) { animation-delay: 0.1s; }
        .sound-wave span:nth-child(2) { animation-delay: 0.3s; }
        .sound-wave span:nth-child(3) { animation-delay: 0.2s; }
        .sound-wave span:nth-child(4) { animation-delay: 0.4s; }
        @keyframes wave { 0%, 100% { height: 4px; } 50% { height: 16px; } }

        .clock-box { text-align: right; }
        .clock-time { font-family: 'Outfit', sans-serif; font-size: 26px; font-weight: 800; color: #fbbf24; font-variant-numeric: tabular-nums; }
        .clock-date { font-size: 12px; color: #94a3b8; }

        main.display-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 32px;
            padding: 32px 40px;
            flex-grow: 1;
            align-items: center;
        }

        .display-card {
            background: linear-gradient(145deg, rgba(15, 23, 42, 0.7), rgba(2, 18, 44, 0.9));
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 32px;
            padding: 40px;
            text-align: center;
            position: relative;
            overflow: hidden;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.6);
            transition: all 0.4s ease;
        }
        .display-card.call-flash {
            animation: callFlash 1.5s ease-in-out;
            border-color: rgba(245, 158, 11, 0.8);
        }
        @keyframes callFlash {
            0%, 100% { box-shadow: 0 25px 50px rgba(0, 0, 0, 0.6); }
            50% { box-shadow: 0 0 60px rgba(245, 158, 11, 0.6); transform: scale(1.02); }
        }

        .card-header-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 20px;
            border-radius: 24px;
            font-family: 'Outfit', sans-serif;
            font-size: 14px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 24px;
        }
        .badge-cs { background: rgba(245, 158, 11, 0.15); color: #fbbf24; border: 1px solid rgba(245, 158, 11, 0.3); }
        .badge-teller { background: rgba(14, 165, 233, 0.15); color: #38bdf8; border: 1px solid rgba(14, 165, 233, 0.3); }

        .counter-title {
            font-family: 'Outfit', sans-serif;
            font-size: 32px;
            font-weight: 800;
            color: #f1f5f9;
            margin-bottom: 6px;
        }
        .teller-name { font-size: 15px; color: #94a3b8; font-weight: 500; margin-bottom: 32px; }

        .number-box {
            background: rgba(2, 9, 22, 0.95);
            border: 2px solid rgba(255, 255, 255, 0.05);
            border-radius: 24px;
            padding: 36px 20px;
            margin-bottom: 28px;
            position: relative;
        }
        .number-label { font-size: 13px; text-transform: uppercase; letter-spacing: 2px; color: #64748b; font-weight: 700; margin-bottom: 8px; }
        .number-value {
            font-family: 'Outfit', sans-serif;
            font-size: clamp(60px, 7vw, 100px);
            font-weight: 900;
            letter-spacing: 2px;
            line-height: 1;
            background: linear-gradient(135deg, #38bdf8 0%, #fbbf24 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-shadow: 0 0 40px rgba(56, 189, 248, 0.4);
        }

        .card-footer-stats {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            font-size: 16px;
            color: #94a3b8;
        }
        .wait-highlight { font-weight: 800; color: #f8fafc; }

        footer.tv-footer {
            padding: 16px 40px;
            background: rgba(2, 18, 44, 0.6);
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            display: flex;
            align-items: center;
            justify-content: space-between;
            font-size: 13px;
            color: #94a3b8;
        }
        .regulatory-box { display: flex; align-items: center; gap: 16px; font-weight: 700; color: #cbd5e1; }

        @media (max-width: 900px) {
            main.display-grid { grid-template-columns: 1fr; padding: 20px; }
            header.tv-header { padding: 16px 20px; }
        }
    </style>
</head>
<body>
    <div class="ticker-bar">
        <div class="ticker-content">
            📢 BANK DP TASPEN KINI HADIR LEBIH DEKAT! HUBUNGI CALL CENTER BERSAMA MAYA DI 📞 150181 | TELEMARKETING BERSAMA LANI & DITO — MENAWARKAN SOLUSI FINANSIAL TERBAIK LANGSUNG KE PONSEL ANDA | KANTOR PUSAT OPERASIONAL: JL. RAYA PD. GEDE NO.9, JATIRAHAYU, BEKASI (TELP: 021-8467944). HARAP PERHATIKAN NOMOR ANTREAN ANDA.
        </div>
    </div>

    <header class="tv-header">
        <div class="brand">
            <div class="brand-logo-card" style="background: #ffffff; padding: 10px 20px; border-radius: 16px; border: 2px solid #eab308; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.4); display: flex; align-items: center;">
                @include('components.bank-logo', ['size' => '28px'])
            </div>
            <div class="brand-info">
                <p style="font-size: 14px; font-weight: 700; color: #cbd5e1; letter-spacing: 0.5px;">Layar Informasi Pemanggilan Antrean Nasabah</p>
            </div>
        </div>

        <div class="header-controls">
            <div class="clock-box">
                <div id="clockTime" class="clock-time">00:00:00</div>
                <div id="clockDate" class="clock-date">-</div>
            </div>
        </div>
    </header>

    <main class="display-grid">
        <!-- CS Card -->
        <section id="card-cs" class="display-card">
            <div class="card-header-badge badge-cs">
                <span>●</span> CS Kredit & Pensiun TASPEN
            </div>
            <div id="counter-cs" class="counter-title">Loket CS</div>
            <div id="servedby-cs" class="teller-name">Petugas Customer Service</div>

            <div class="number-box">
                <div class="number-label">Nomor Antrean Dipanggil</div>
                <div id="cs" class="number-value">-</div>
            </div>

            <div class="card-footer-stats">
                <span>Antrean Menunggu:</span>
                <span id="wait-cs" class="wait-highlight">0 Orang</span>
            </div>
        </section>

        <!-- Teller Card -->
        <section id="card-teller" class="display-card">
            <div class="card-header-badge badge-teller">
                <span>●</span> Teller Kas & Pensiun TASPEN
            </div>
            <div id="counter-teller" class="counter-title">Loket Teller</div>
            <div id="servedby-teller" class="teller-name">Petugas Teller</div>

            <div class="number-box">
                <div class="number-label">Nomor Antrean Dipanggil</div>
                <div id="teller" class="number-value">-</div>
            </div>

            <div class="card-footer-stats">
                <span>Antrean Menunggu:</span>
                <span id="wait-teller" class="wait-highlight">0 Orang</span>
            </div>
        </section>
    </main>

    <footer class="tv-footer">
        <div><strong>Call Center MAYA:</strong> <span style="color: #fbbf24; font-weight: 800;">150181</span> | <strong>Kantor Pusat Operasional:</strong> Jl. Raya Pd. Gede No.9, Jatirahayu, Bekasi (Telp: 021-8467944)</div>
        <div class="regulatory-box">
            <span>🛡️ Terdaftar & Diawasi OJK</span>
            <span>|</span>
            <span>🏦 Peserta Penjaminan LPS</span>
        </div>
    </footer>

    <script>
        let audioEnabled = true;
        let lastCalledTime = { cs: null, teller: null };

        function updateClock() {
            const now = new Date();
            document.getElementById('clockTime').textContent = now.toLocaleTimeString('id-ID', { hour12: false });
            document.getElementById('clockDate').textContent = now.toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
        }
        setInterval(updateClock, 1000);
        updateClock();

        function playChime() {
            try {
                const ctx = new (window.AudioContext || window.webkitAudioContext)();
                const now = ctx.currentTime;
                
                const osc1 = ctx.createOscillator();
                const gain1 = ctx.createGain();
                osc1.type = 'sine';
                osc1.frequency.setValueAtTime(523.25, now); // C5
                gain1.gain.setValueAtTime(0.3, now);
                gain1.gain.exponentialRampToValueAtTime(0.001, now + 0.8);
                osc1.connect(gain1);
                gain1.connect(ctx.destination);
                osc1.start(now);
                osc1.stop(now + 0.8);

                const osc2 = ctx.createOscillator();
                const gain2 = ctx.createGain();
                osc2.type = 'sine';
                osc2.frequency.setValueAtTime(659.25, now + 0.2); // E5
                gain2.gain.setValueAtTime(0.3, now + 0.2);
                gain2.gain.exponentialRampToValueAtTime(0.001, now + 1.0);
                osc2.connect(gain2);
                gain2.connect(ctx.destination);
                osc2.start(now + 0.2);
                osc2.stop(now + 1.0);
            } catch(e) {}
        }

        function speakText(text) {
            if ('speechSynthesis' in window) {
                window.speechSynthesis.cancel();
                const utterance = new SpeechSynthesisUtterance(text);
                utterance.lang = 'id-ID';
                utterance.rate = 0.9;
                window.speechSynthesis.speak(utterance);
            }
        }

        function announceQueue(number, counterName) {
            if (!audioEnabled || !number || number === '-') return;
            playChime();
            setTimeout(() => {
                const formattedNum = number.replace(/-/g, ' ');
                const speechText = `Nomor antrean ${formattedNum}, silakan menuju ${counterName}.`;
                speakText(speechText);
            }, 600);
        }

        let isFetching = false;
        async function refreshData() {
            if (isFetching) return;
            isFetching = true;
            try {
                const response = await fetch('/api/display/data');
                const data = await response.json();

                for (const service of ['cs', 'teller']) {
                    const queueData = data[service];
                    const numElement = document.getElementById(service);
                    const waitElement = document.getElementById('wait-' + service);
                    const counterElement = document.getElementById('counter-' + service);
                    const servedByElement = document.getElementById('servedby-' + service);
                    const cardElement = document.getElementById('card-' + service);

                    waitElement.textContent = (data.waiting[service] ?? 0) + ' Orang';

                    if (data.settings && data.settings[service]) {
                        counterElement.textContent = data.settings[service].counter_name || (service === 'cs' ? 'Loket CS - Kredit & Pensiun' : 'Loket Teller - Kas & Pensiun');
                    }

                    if (queueData && queueData.number) {
                        const isNewCall = lastCalledTime[service] !== queueData.called_at;
                        numElement.textContent = queueData.number;
                        if (servedByElement && queueData.served_by_name) {
                            servedByElement.textContent = 'Petugas: ' + queueData.served_by_name;
                        }

                        if (isNewCall && lastCalledTime[service] !== null) {
                            cardElement.classList.add('call-flash');
                            setTimeout(() => cardElement.classList.remove('call-flash'), 2000);
                            announceQueue(queueData.number, queueData.counter_name || (service === 'cs' ? 'Customer Service' : 'Teller'));
                        }
                        lastCalledTime[service] = queueData.called_at;
                    } else {
                        numElement.textContent = '-';
                    }
                }
            } catch(e) {
                console.error('Fetch error:', e);
            } finally {
                isFetching = false;
            }
        }

        refreshData();
        setInterval(refreshData, 3000);
    </script>
</body>
</html>
