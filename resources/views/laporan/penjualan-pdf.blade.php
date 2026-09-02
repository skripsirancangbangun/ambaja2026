<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Penjualan</title>
    <style>
        @page { margin: 25px 30px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1e293b; }
        .header { text-align: center; margin-bottom: 18px; }
        .header h1 { margin: 0; font-size: 18px; color: #15235F; }
        .header p { margin-top: 5px; color: #64748b; }
        .summary { width: 100%; margin-bottom: 15px; border-spacing: 8px 0; }
        .summary td { border: 1px solid #cbd5e1; padding: 8px; }
        .summary-label { font-size: 8px; color: #64748b; }
        .summary-value { margin-top: 4px; font-size: 11px; font-weight: bold; color: #15235F; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th { background: #15235F; color: #ffffff; padding: 6px 4px; border: 1px solid #cbd5e1; font-size: 8px; }
        table.data td { padding: 5px 4px; border: 1px solid #cbd5e1; vertical-align: top; }
        .right { text-align: right; }
        .center { text-align: center; }
        .total-row { font-weight: bold; background: #f1f5f9; }
        .footer { margin-top: 12px; text-align: right; font-size: 8px; color: #64748b; }
    </style>
</head>
<body>

    <div class="header">
        <h1>LAPORAN PENJUALAN</h1>
        <p>CV Andika Mustika (Ambaja)</p>
    </div>

    <table class="summary">
        <tr>
            <td width="25%">
                <div class="summary-label">JUMLAH INVOICE</div>
                <div class="summary-value">{{ number_format($jumlahInvoice, 0, ',', '.') }}</div>
            </td>
            <td width="25%">
                <div class="summary-label">TOTAL PENJUALAN</div>
                <div class="summary-value">Rp {{ number_format($totalPenjualan, 0, ',', '.') }}</div>
            </td>
            <td width="25%">
                <div class="summary-label">TOTAL DIBAYAR</div>
                <div class="summary-value" style="color: #059669;">Rp {{ number_format($totalDibayar, 0, ',', '.') }}</div>
            </td>
            <td width="25%">
                <div class="summary-label">SISA PIUTANG</div>
                <div class="summary-value" style="color: #e11d48;">Rp {{ number_format($totalSisaPiutang, 0, ',', '.') }}</div>
            </td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th width="8%">Tanggal</th>
                <th width="12%">No. Invoice</th>
                <th width="18%">Customer</th>
                <th width="10%">Gudang</th>
                <th width="8%">Tipe</th>
                <th width="10%" class="right">Subtotal</th>
                <th width="8%" class="right">Diskon</th>
                <th width="8%" class="right">Pajak</th>
                <th width="10%" class="right">Total</th>
                <th width="10%" class="right">Dibayar</th>
                <th width="10%" class="right">Sisa</th>
                <th width="8%">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoices as $inv)
                <tr>
                    <td class="center">{{ $inv->tanggal_invoice ? $inv->tanggal_invoice->format('d/m/Y') : '-' }}</td>
                    <td><strong>{{ $inv->nomor_invoice }}</strong></td>
                    <td>{{ $inv->customer?->nama_customer ?? '-' }}</td>
                    <td>{{ $inv->gudang?->nama_gudang ?? '-' }}</td>
                    <td>{{ $inv->tipe_pembayaran }}</td>
                    <td class="right">Rp {{ number_format($inv->subtotal, 0, ',', '.') }}</td>
                    <td class="right">Rp {{ number_format($inv->diskon, 0, ',', '.') }}</td>
                    <td class="right">Rp {{ number_format($inv->pajak, 0, ',', '.') }}</td>
                    <td class="right"><strong>Rp {{ number_format($inv->total, 0, ',', '.') }}</strong></td>
                    <td class="right" style="color: #059669;">Rp {{ number_format($inv->sudah_dibayar, 0, ',', '.') }}</td>
                    <td class="right" style="color: #e11d48;">Rp {{ number_format($inv->sisa_piutang, 0, ',', '.') }}</td>
                    <td class="center">{{ $inv->status_pembayaran }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="5" class="right">TOTAL</td>
                <td class="right">Rp {{ number_format($invoices->sum('subtotal'), 0, ',', '.') }}</td>
                <td class="right">Rp {{ number_format($invoices->sum('diskon'), 0, ',', '.') }}</td>
                <td class="right">Rp {{ number_format($invoices->sum('pajak'), 0, ',', '.') }}</td>
                <td class="right">Rp {{ number_format($totalPenjualan, 0, ',', '.') }}</td>
                <td class="right">Rp {{ number_format($totalDibayar, 0, ',', '.') }}</td>
                <td class="right">Rp {{ number_format($totalSisaPiutang, 0, ',', '.') }}</td>
                <td></td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        Dicetak pada {{ now()->format('d/m/Y H:i') }}
    </div>

</body>
</html>
