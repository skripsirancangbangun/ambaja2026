<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Laba Rugi</title>
    <style>
        @page { margin: 25px 30px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; }
        .header { text-align: center; margin-bottom: 18px; }
        .header h1 { margin: 0; font-size: 18px; color: #15235F; }
        .header p { margin-top: 5px; color: #64748b; }
        .section-title { font-size: 11px; font-weight: bold; color: #15235F; border-bottom: 1px solid #cbd5e1; padding-bottom: 4px; margin-top: 15px; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 5px; }
        table.data td { padding: 6px 4px; border-bottom: 1px solid #e2e8f0; }
        .right { text-align: right; }
        .total-row { font-weight: bold; background: #f8fafc; border-top: 1px solid #cbd5e1; }
        .result-box { margin-top: 20px; padding: 12px; border: 1px solid #cbd5e1; text-align: center; font-size: 14px; font-weight: bold; background: #f1f5f9; }
        .footer { margin-top: 20px; text-align: right; font-size: 8px; color: #64748b; }
    </style>
</head>
<body>

    <div class="header">
        <h1>LAPORAN LABA RUGI</h1>
        <p>CV Andika Mustika (Ambaja) | Periode: {{ date('d/m/Y', strtotime($tanggalMulai)) }} s/d {{ date('d/m/Y', strtotime($tanggalSelesai)) }}</p>
    </div>

    <div class="section-title">PENDAPATAN (REVENUE)</div>
    <table class="data">
        @foreach($pendapatanData as $row)
            <tr>
                <td width="20%">{{ $row['akun']->kode_akun }}</td>
                <td width="55%">{{ $row['akun']->nama_akun }}</td>
                <td width="25%" class="right">Rp {{ number_format($row['nominal'], 0, ',', '.') }}</td>
            </tr>
        @endforeach
        <tr class="total-row">
            <td colspan="2">TOTAL PENDAPATAN</td>
            <td class="right" style="color: #059669;">Rp {{ number_format($totalPendapatan, 0, ',', '.') }}</td>
        </tr>
    </table>

    <div class="section-title">BEBAN (EXPENSES)</div>
    <table class="data">
        @foreach($bebanData as $row)
            <tr>
                <td width="20%">{{ $row['akun']->kode_akun }}</td>
                <td width="55%">{{ $row['akun']->nama_akun }}</td>
                <td width="25%" class="right">Rp {{ number_format($row['nominal'], 0, ',', '.') }}</td>
            </tr>
        @endforeach
        <tr class="total-row">
            <td colspan="2">TOTAL BEBAN</td>
            <td class="right" style="color: #e11d48;">Rp {{ number_format($totalBeban, 0, ',', '.') }}</td>
        </tr>
    </table>

    <div class="result-box">
        {{ $labaRugiBersih >= 0 ? 'LABA BERSIH: ' : 'RUGI BERSIH: ' }}
        <span style="color: {{ $labaRugiBersih >= 0 ? '#059669' : '#e11d48' }};">
            Rp {{ number_format($labaRugiBersih, 0, ',', '.') }}
        </span>
    </div>

    <div class="footer">
        Dicetak pada {{ now()->format('d/m/Y H:i') }}
    </div>

</body>
</html>
