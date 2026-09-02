<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sales Order - {{ $penjualan->nomor_penjualan }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 12px; color: #333; margin: 0; padding: 20px; }
        .header { border-bottom: 2px solid #15235F; padding-bottom: 10px; margin-bottom: 20px; }
        .company-name { font-size: 18px; font-weight: bold; color: #15235F; }
        .doc-title { font-size: 16px; font-weight: bold; text-align: right; color: #15235F; }
        .meta-table { width: 100%; margin-bottom: 20px; }
        .meta-table td { vertical-align: top; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table th { background-color: #f8fafc; border-bottom: 2px solid #cbd5e1; padding: 8px; text-align: left; font-size: 11px; text-transform: uppercase; color: #64748b; }
        .items-table td { border-bottom: 1px solid #e2e8f0; padding: 8px; font-size: 11px; }
        .text-right { text-align: right; }
        .totals-table { width: 40%; margin-left: auto; border-collapse: collapse; }
        .totals-table td { padding: 4px 8px; font-size: 11px; }
        .totals-table .grand-total { font-size: 13px; font-weight: bold; border-top: 2px solid #15235F; color: #15235F; }
        .footer { margin-top: 40px; }
        .signature-table { width: 100%; text-align: center; }
        .signature-box { height: 60px; }
    </style>
</head>
<body>

    <div class="header">
        <table width="100%">
            <tr>
                <td>
                    <div class="company-name">CV AMBAJA (ANDIKA MUSTIKA)</div>
                    <div>Sistem Informasi Akuntansi & Distribusi</div>
                </td>
                <td class="text-right">
                    <div class="doc-title">SALES ORDER</div>
                    <div><strong>No:</strong> {{ $penjualan->nomor_penjualan }}</div>
                    <div><strong>Tanggal:</strong> {{ $penjualan->tanggal_penjualan ? $penjualan->tanggal_penjualan->format('d/m/Y') : '-' }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="meta-table">
        <tr>
            <td width="50%">
                <strong>Kepada Yth:</strong><br>
                {{ $penjualan->customer->nama_customer ?? '-' }}<br>
                {{ $penjualan->customer->alamat ?? '-' }}<br>
                Telp: {{ $penjualan->customer->telepon ?? '-' }}
            </td>
            <td width="50%" class="text-right">
                <strong>Status:</strong> {{ $penjualan->status }}<br>
                <strong>Gudang:</strong> {{ $penjualan->gudang->nama_gudang ?? '-' }}<br>
                <strong>Estimasi Kirim:</strong> {{ $penjualan->tanggal_estimasi_kirim ? $penjualan->tanggal_estimasi_kirim->format('d/m/Y') : '-' }}
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="45%">Deskripsi Barang</th>
                <th width="15%" class="text-right">Qty</th>
                <th width="15%" class="text-right">Harga (Rp)</th>
                <th width="20%" class="text-right">Subtotal (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($penjualan->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $item->barang->nama_barang ?? '-' }}</strong>
                        <br><small style="color: #64748b;">{{ $item->barang->kode_barang ?? '' }}</small>
                    </td>
                    <td class="text-right">{{ number_format($item->qty, 0, ',', '.') }} {{ $item->barang->satuan->nama_satuan ?? '' }}</td>
                    <td class="text-right">{{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
                    <td class="text-right">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td>Subtotal:</td>
            <td class="text-right">Rp {{ number_format($penjualan->subtotal, 0, ',', '.') }}</td>
        </tr>
        @if($penjualan->diskon > 0)
            <tr>
                <td>Diskon:</td>
                <td class="text-right">- Rp {{ number_format($penjualan->diskon, 0, ',', '.') }}</td>
            </tr>
        @endif
        @if($penjualan->pajak > 0)
            <tr>
                <td>PPN ({{ $penjualan->pajak_persen }}%):</td>
                <td class="text-right">Rp {{ number_format($penjualan->pajak, 0, ',', '.') }}</td>
            </tr>
        @endif
        <tr class="grand-total">
            <td>Grand Total:</td>
            <td class="text-right">Rp {{ number_format($penjualan->total, 0, ',', '.') }}</td>
        </tr>
    </table>

    @if($penjualan->catatan)
        <div style="margin-top: 20px; font-size: 11px;">
            <strong>Catatan:</strong> {{ $penjualan->catatan }}
        </div>
    @endif

    <div class="footer">
        <table class="signature-table">
            <tr>
                <td width="33%">
                    Dibuat Oleh,<br>
                    <div class="signature-box"></div>
                    ( {{ $penjualan->creator->name ?? 'Admin' }} )
                </td>
                <td width="33%">
                    Disetujui Oleh,<br>
                    <div class="signature-box"></div>
                    ( ................................ )
                </td>
                <td width="33%">
                    Diterima Oleh Customer,<br>
                    <div class="signature-box"></div>
                    ( ................................ )
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
