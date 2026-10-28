@php
    $narrow = (float) $receipt['paper']['width_mm'] <= 58;
    $contentWidth = $narrow ? 48 : 74;
    $numberSize = min(34, max(14, floor(($narrow ? 160 : 230) / max(1, mb_strlen($receipt['ticket'])))));
@endphp
<!doctype html>
<html lang="id"><head>
    <meta charset="utf-8"><title>Tiket {{ $receipt['ticket'] }}</title><meta name="bank-ticket" content="{{ $receipt['ticket'] }}"><style>
@page { margin: 3mm; }
body { margin: 0; font-family: Helvetica, sans-serif; font-size: 8pt; line-height: 1.35; color: #000; text-align: center; }
.header { border-bottom: 1px dashed #000; padding-bottom: 2.5mm; }
.logo { width: {{ $narrow ? 42 : 46 }}mm; max-width: 100%; margin: 0 auto 2mm; }
.receipt { width: {{ $contentWidth }}mm; max-width: 100%; margin: 0 auto; }
.branch { font-size: 9pt; font-weight: bold; }
.address { margin-top: 1.5mm; line-height: 1.4; overflow-wrap: break-word; }
.date { margin-top: 2mm; }
.ticket { padding: 3mm 0; }
.service { font-size: 10pt; font-weight: bold; }
.label { margin-top: 2mm; letter-spacing: .5pt; }
.number { font-size: {{ $numberSize }}pt; font-weight: bold; line-height: 1.15; margin-top: 1mm; white-space: nowrap; }
.footer { border-top: 1px dashed #000; padding-top: 2mm; line-height: 1.5; }
@if($browser ?? false)
@page { size: {{ (float) $receipt['paper']['width_mm'] }}mm {{ (float) $receipt['paper']['height_mm'] }}mm; margin: 3mm; }
@media screen { body { width: {{ $contentWidth }}mm; max-width: calc(100% - 24px); margin: 16px auto; padding: 12px; background: #fff; } }
.browser-controls { margin-bottom: 16px; padding: 12px; background: #eff6ff; font: 14px sans-serif; }
.browser-controls button { padding: 10px; cursor: pointer; }
@media print { .browser-controls { display: none; } }
@endif
</style></head><body>
@if($controls ?? false)
<div class="browser-controls"><p>Pilih printer pada PC ini. Pembatalan dialog tidak menghapus nomor tiket.</p><button type="button" onclick="window.print()">Cetak tiket ini</button><p><a href="{{ route('filament.admin.resources.queues.index') }}">Kembali ke daftar antrean</a></p></div>
@endif
<div class="receipt"><div class="header">
    <img class="logo" src="{{ $receipt['logo'] }}" alt="bank dp taspen">
    <div class="branch">{{ $receipt['branch_name'] }}</div>
    <div class="address">{{ $receipt['branch_address'] }}</div>
    @if($receipt['branch_phone'])<div>Telp: {{ $receipt['branch_phone'] }}</div>@endif
</div>
<div class="date">{{ $receipt['date_formatted'] }}</div>
<div class="ticket"><div class="service">{{ $receipt['service_name'] }}</div><div class="label">NOMOR ANTREAN</div><div class="number">{{ $receipt['ticket'] }}</div></div>
<div class="footer">Silakan menunggu nomor Anda dipanggil.<br>Terima kasih atas kunjungan Anda.</div>
</div>
@if(($browser ?? false) && ($autoPrint ?? false))
<script>window.addEventListener('load', () => window.print(), {once: true});</script>
@endif
</body></html>
