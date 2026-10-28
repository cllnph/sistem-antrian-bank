<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}"><meta name="referrer" content="no-referrer">
    <title>Menyambungkan printer PC ini</title>
    <style>body{font:16px system-ui;background:#f1f5f9;color:#0f172a;margin:0;padding:10vh 24px}main{max-width:520px;margin:auto;padding:32px;background:white;border-radius:16px}h1{font-size:24px}p{line-height:1.6}</style>
</head>
<body><main><h1>Menyiapkan kiosk PC ini</h1><p id="status" role="status">Memverifikasi penghubung printer…</p><button id="retry" hidden type="button">Coba lagi</button></main>
<script>
    const token = location.hash.slice(1);
    history.replaceState(null, '', location.pathname);
    const statusText = document.getElementById('status');
    async function pair() {
        document.getElementById('retry').hidden = true;
        if (!/^[a-f0-9]{64}$/.test(token)) { statusText.textContent = 'Buka halaman ini melalui shortcut Buka Kiosk pada PC printer.'; return; }
        try {
            const response = await fetch(@json(route('kiosk-connect.pair', [], false)), {
                method: 'POST', credentials: 'same-origin', headers: {'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':document.querySelector('meta[name=csrf-token]').content},
                body: JSON.stringify({token})
            });
            const data = await response.json();
            if (!response.ok) throw new Error(data.message || 'Pemasangan gagal. Buka kembali shortcut Buka Kiosk.');
            if (typeof data.redirect !== 'string' || !data.redirect.startsWith('/') || data.redirect.startsWith('//')) throw new Error('Alamat kiosk tidak valid.');
            location.replace(data.redirect);
        } catch (error) {
            statusText.textContent = error.message || 'Koneksi terputus. Buka kembali shortcut Buka Kiosk.';
            document.getElementById('retry').hidden = false;
        }
    }
    document.getElementById('retry').addEventListener('click', pair);
    pair();
</script></body></html>
