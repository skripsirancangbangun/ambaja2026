<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Aging Piutang Dagang</title>
    <style>
        @page { margin: 25px 30px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1e293b; }
        .header { text-align: center; margin-bottom: 18px; }
        .header h1 { margin: 0; font-size: 18px; color: #15235F; }
        .header p { margin-top: 5px; color: #64748b; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 10px; }
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
        <h1>LAPORAN AGING PIUTANG DAGANG</h1>
        <p>CV Andika Mustika (Ambaja) | Posisi Per Tanggal: {{ date('d/m/Y', strtotime($perTanggal)) }}</p>
    </div>

    <table class="data">
        <thead>
            <tr>
                <th width="12%">No. Invoice</th>
                <th width="20%">Customer</th>
                <th width="10%">Tanggal</th>
                <th width="10%">Jatuh Tempo</th>
                <th width="12%" class="right">Lancar (0-30 Hari)</th>
                <th width="12%" class="right">31 - 60 Hari</th>
                <th width="12%" class="right">61 - 90 Hari</th>
                <th width="12%" class="right">&gt; 90 Hari</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoices as $inv)
                @php
                    $today = \Carbon\Carbon::parse($perTanggal);
                    $dueDate = $inv->tanggal_jatuh_tempo ? \Carbon\Carbon::parse($inv->tanggal_jatuh_tempo) : \Carbon\Carbon::parse($inv->tanggal_invoice);
                    $daysOverdue = $dueDate->isPast() ? (int) $dueDate->diffInDays($today) : 0;

                    $lancar = $daysOverdue <= 30 ? $inv->sisa_piutang : 0;
                    $t30 = ($daysOverdue > 30 && $daysOverdue <= 60) ? $inv->sisa_piutang : 0;
                    $t60 = ($daysOverdue > 60 && $daysOverdue <= 90) ? $inv->sisa_piutang : 0;
                    $t90 = $daysOverdue > 90 ? $inv->sisa_piutang : 0;
                @endphp
                <tr>
                    <td><strong>{{ $inv->nomor_invoice }}</strong></td>
                    <td>{{ $inv->customer?->nama_customer ?? '-' }}</td>
                    <td class="center">{{ $inv->tanggal_invoice ? $inv->tanggal_invoice->format('d/m/Y') : '-' }}</td>
                    <td class="center">{{ $inv->tanggal_jatuh_tempo ? $inv->tanggal_jatuh_tempo->format('d/m/Y') : '-' }}</td>
                    <td class="right">{{ $lancar > 0 ? 'Rp '.number_format($lancar, 0, ',', '.') : '-' }}</td>
                    <td class="right">{{ $t30 > 0 ? 'Rp '.number_format($t30, 0, ',', '.') : '-' }}</td>
                    <td class="right">{{ $t60 > 0 ? 'Rp '.number_format($t60, 0, ',', '.') : '-' }}</td>
                    <td class="right" style="color: #e11d48;">{{ $t90 > 0 ? 'Rp '.number_format($t90, 0, ',', '.') : '-' }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="4" class="right">TOTAL AGING PIUTANG</td>
                <td class="right">Rp {{ number_format($aging['lancar'], 0, ',', '.') }}</td>
                <td class="right">Rp {{ number_format($aging['tempo_30'], 0, ',', '.') }}</td>
                <td class="right">Rp {{ number_format($aging['tempo_60'], 0, ',', '.') }}</td>
                <td class="right" style="color: #e11d48;">Rp {{ number_format($aging['tempo_90plus'], 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        Dicetak pada {{ now()->format('d/m/Y H:i') }}
    </div>

</body>
</html>
