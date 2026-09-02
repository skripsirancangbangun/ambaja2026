<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kwitansi Pembayaran - {{ $penerimaan->nomor_penerimaan }}</title>
    <style>
        @page { margin: 20px 25px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; }
        .header { border-bottom: 2px solid #15235F; padding-bottom: 10px; margin-bottom: 15px; }
        .title { font-size: 16px; font-weight: bold; color: #15235F; text-align: right; }
        .company { font-size: 14px; font-weight: bold; color: #15235F; }
        .info-table { width: 100%; margin-bottom: 15px; }
        .info-table td { padding: 4px 0; vertical-align: top; }
        table.detail { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.detail th { background: #15235F; color: #ffffff; padding: 6px; font-size: 9px; }
        table.detail td { padding: 6px; border-bottom: 1px solid #cbd5e1; }
        .right { text-align: right; }
        .total-box { margin-top: 15px; padding: 10px; border: 1px solid #15235F; background: #f8fafc; text-align: right; font-size: 12px; font-weight: bold; }
        .footer-sign { margin-top: 30px; width: 100%; text-align: center; }
    </style>
</head>
<body>

    <div class="header">
        <table width="100%">
            <tr>
                <td width="50%">
                    <div class="company">CV ANDIKA MUSTIKA (AMBAJA)</div>
                    <div style="font-size: 9px; color: #64748b;">Sistem Informasi Akuntansi Penjualan & Piutang</div>
                </td>
                <td width="50%" class="right">
                    <div class="title">KWITANSI BUKTI PELUNASAN</div>
                    <div style="font-size: 10px; color: #15235F;"><strong>No: {{ $penerimaan->nomor_penerimaan }}</strong></div>
                </td>
            </tr>
        </table>
    </div>

    <table class="info-table">
        <tr>
            <td width="20%"><strong>Telah Diterima Dari:</strong></td>
            <td width="80%">{{ $penerimaan->customer?->nama_customer }} ({{ $penerimaan->customer?->kode_customer }})</td>
        </tr>
        <tr>
            <td><strong>Tanggal Pembayaran:</strong></td>
            <td>{{ $penerimaan->tanggal ? $penerimaan->tanggal->format('d F Y') : '-' }}</td>
        </tr>
        <tr>
            <td><strong>Metode / Rekening:</strong></td>
            <td>{{ $penerimaan->rekeningKasBank?->nama_rekening }} ({{ $penerimaan->rekeningKasBank?->nomor_rekening }})</td>
        </tr>
        <tr>
            <td><strong>Catatan:</strong></td>
            <td>{{ $penerimaan->catatan ?: '-' }}</td>
        </tr>
    </table>

    <table class="detail">
        <thead>
            <tr>
                <th width="30%">No. Invoice</th>
                <th width="25%">Tanggal Faktur</th>
                <th width="45%" class="right">Nominal Alokasi Pembayaran (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($penerimaan->details as $d)
                <tr>
                    <td><strong>{{ $d->invoicePenjualan?->nomor_invoice }}</strong></td>
                    <td>{{ $d->invoicePenjualan?->tanggal_invoice ? $d->invoicePenjualan->tanggal_invoice->format('d/m/Y') : '-' }}</td>
                    <td class="right">Rp {{ number_format($d->nominal_dibayar, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-box">
        TOTAL PENERIMAAN KAS/BANK: <span style="color: #059669;">Rp {{ number_format($penerimaan->total_dibayar, 0, ',', '.') }}</span>
    </div>

    <table class="footer-sign">
        <tr>
            <td width="50%">
                Diterima Oleh,<br><br><br><br>
                <strong>( {{ $penerimaan->creator?->name ?? 'Kasir / Admin' }} )</strong>
            </td>
            <td width="50%">
                Disetujui Customer,<br><br><br><br>
                <strong>( {{ $penerimaan->customer?->nama_customer }} )</strong>
            </td>
        </tr>
    </table>

</body>
</html>
