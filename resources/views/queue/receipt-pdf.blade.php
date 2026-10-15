<!doctype html>
<html lang="id"><head><meta charset="utf-8"><style>
@page { margin: 3mm; }
body { margin: 0; font-family: Helvetica, sans-serif; font-size: 8pt; line-height: 1.35; color: #000; text-align: center; }
.header { border-bottom: 1px dashed #000; padding-bottom: 2.5mm; }
.logo { width: 46mm; margin: 0 auto 2mm; }
.branch { font-size: 9pt; font-weight: bold; }
.address { margin-top: 1.5mm; line-height: 1.4; overflow-wrap: break-word; }
.date { margin-top: 2mm; }
.ticket { padding: 3mm 0; }
.service { font-size: 10pt; font-weight: bold; }
.label { margin-top: 2mm; letter-spacing: .5pt; }
.number { font-size: 34pt; font-weight: bold; line-height: 1.15; margin-top: 1mm; white-space: nowrap; }
.footer { border-top: 1px dashed #000; padding-top: 2mm; line-height: 1.5; }
</style></head><body>
<div class="header">
    <img class="logo" src="{{ $receipt['logo'] }}" alt="bank dp taspen">
    <div class="branch">{{ $receipt['branch_name'] }}</div>
    <div class="address">{{ $receipt['branch_address'] }}</div>
    @if($receipt['branch_phone'])<div>Telp: {{ $receipt['branch_phone'] }}</div>@endif
</div>
<div class="date">{{ $receipt['date_formatted'] }}</div>
<div class="ticket"><div class="service">{{ $receipt['service_name'] }}</div><div class="label">NOMOR ANTREAN</div><div class="number">{{ $receipt['ticket'] }}</div></div>
<div class="footer">Silakan menunggu nomor Anda dipanggil.<br>Terima kasih atas kunjungan Anda.</div>
</body></html>
