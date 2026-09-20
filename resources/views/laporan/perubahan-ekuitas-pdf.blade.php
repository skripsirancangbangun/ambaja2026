<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Perubahan Ekuitas</title>
    <style>
        @page { margin: 25px 30px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; }
        .header { text-align: center; margin-bottom: 18px; }
        .header h1 { margin: 0; font-size: 18px; color: #15235F; }
        .header p { margin-top: 5px; color: #64748b; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 10px; }
        table.data th { background: #15235F; color: #ffffff; padding: 8px; border: 1px solid #cbd5e1; }
        table.data td { padding: 8px; border: 1px solid #cbd5e1; }
        .right { text-align: right; }
        .total-row { font-weight: bold; background: #f1f5f9; font-size: 11px; }
        .footer { margin-top: 20px; text-align: right; font-size: 8px; color: #64748b; }
    </style>
</head>
<body>

    <div class="header">
        <h1>LAPORAN PERUBAHAN EKUITAS</h1>
        <p>CV Andika Mustika (Ambaja) | Periode: {{ date('d/m/Y', strtotime($tanggalMulai)) }} s/d {{ date('d/m/Y', strtotime($tanggalSelesai)) }}</p>
    </div>

    <table class="data">
        <thead>
            <tr>
                <th width="70%">Komponen Ekuitas</th>
                <th width="30%" class="right">Nominal (Rp)</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td><strong>Modal Awal CV Ambaja (Per {{ date('d/m/Y', strtotime($tanggalMulai)) }})</strong></td>
                <td class="right"><strong>Rp {{ number_format($modalAwal, 0, ',', '.') }}</strong></td>
            </tr>
            <tr>
                <td style="padding-left: 20px;">
                    @if($labaBersihPeriode >= 0)
                        (+) Laba Bersih Periode Berjalan
                    @else
                        (-) Rugi Bersih Periode Berjalan
                    @endif
                </td>
                <td class="right" style="color: {{ $labaBersihPeriode >= 0 ? '#059669' : '#e11d48' }};">
                    {{ $labaBersihPeriode >= 0 ? '+' : '-' }} Rp {{ number_format(abs($labaBersihPeriode), 0, ',', '.') }}
                </td>
            </tr>
            <tr>
                <td style="padding-left: 20px;">(-) Pengambilan Prive Owner Periode Berjalan</td>
                <td class="right" style="color: #e11d48;">- Rp {{ number_format($privePeriode, 0, ',', '.') }}</td>
            </tr>
            <tr class="total-row">
                <td>
                    @if($perubahanNet >= 0)
                        Kenaikan Bersih Ekuitas Periode Ini
                    @else
                        Penurunan Bersih Ekuitas Periode Ini
                    @endif
                </td>
                <td class="right" style="color: {{ $perubahanNet >= 0 ? '#059669' : '#e11d48' }};">
                    {{ $perubahanNet >= 0 ? '+' : '-' }} Rp {{ number_format(abs($perubahanNet), 0, ',', '.') }}
                </td>
            </tr>
            <tr class="total-row" style="background: #e2e8f0;">
                <td style="color: #15235F;">MODAL AKHIR CV AMBAJA (Per {{ date('d/m/Y', strtotime($tanggalSelesai)) }})</td>
                <td class="right" style="color: #15235F;">Rp {{ number_format($modalAkhir, 0, ',', '.') }}</td>
            </tr>
        </tbody>
    </table>

    <div class="footer">
        Dicetak pada {{ now()->format('d/m/Y H:i') }}
    </div>

</body>
</html>
