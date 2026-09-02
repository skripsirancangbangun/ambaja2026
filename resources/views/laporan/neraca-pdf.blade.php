<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Neraca</title>
    <style>
        @page { margin: 25px 30px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 9px; color: #1e293b; }
        .header { text-align: center; margin-bottom: 18px; }
        .header h1 { margin: 0; font-size: 18px; color: #15235F; }
        .header p { margin-top: 5px; color: #64748b; }
        .section-title { font-size: 10px; font-weight: bold; color: #15235F; border-bottom: 1px solid #cbd5e1; padding-bottom: 3px; margin-top: 10px; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 4px; }
        table.data td { padding: 5px 4px; border-bottom: 1px solid #e2e8f0; }
        .right { text-align: right; }
        .total-row { font-weight: bold; background: #f8fafc; }
        .footer { margin-top: 15px; text-align: right; font-size: 8px; color: #64748b; }
    </style>
</head>
<body>

    <div class="header">
        <h1>LAPORAN NERACA (POSISI KEUANGAN)</h1>
        <p>CV Andika Mustika (Ambaja) | Per Tanggal: {{ date('d/m/Y', strtotime($tanggal)) }}</p>
    </div>

    <table width="100%">
        <tr>
            <td width="48%" style="vertical-align: top;">
                <div class="section-title">ASET / AKTIVA</div>
                <table class="data">
                    @foreach($asetData as $row)
                        <tr>
                            <td>{{ $row['akun']->kode_akun }} - {{ $row['akun']->nama_akun }}</td>
                            <td class="right">Rp {{ number_format($row['nominal'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td>TOTAL ASET</td>
                        <td class="right" style="color: #15235F;">Rp {{ number_format($totalAset, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </td>

            <td width="4%"></td>

            <td width="48%" style="vertical-align: top;">
                <div class="section-title">KEWAJIBAN & EKUITAS</div>
                <table class="data">
                    @foreach($kewajibanData as $row)
                        <tr>
                            <td>{{ $row['akun']->kode_akun }} - {{ $row['akun']->nama_akun }}</td>
                            <td class="right">Rp {{ number_format($row['nominal'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    <tr class="total-row">
                        <td>TOTAL KEWAJIBAN</td>
                        <td class="right">Rp {{ number_format($totalKewajiban, 0, ',', '.') }}</td>
                    </tr>

                    @foreach($ekuitasData as $row)
                        <tr>
                            <td>{{ $row['akun']->kode_akun }} - {{ $row['akun']->nama_akun }}</td>
                            <td class="right">Rp {{ number_format($row['nominal'], 0, ',', '.') }}</td>
                        </tr>
                    @endforeach
                    <tr>
                        <td>Laba / (Rugi) Kumulatif</td>
                        <td class="right">Rp {{ number_format($labaRugiKumulatif, 0, ',', '.') }}</td>
                    </tr>
                    <tr class="total-row">
                        <td>TOTAL EKUITAS</td>
                        <td class="right">Rp {{ number_format($totalEkuitas, 0, ',', '.') }}</td>
                    </tr>
                    <tr class="total-row" style="background: #e2e8f0;">
                        <td>TOTAL PASIVA</td>
                        <td class="right" style="color: #15235F;">Rp {{ number_format($totalKewajiban + $totalEkuitas, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="footer">
        Dicetak pada {{ now()->format('d/m/Y H:i') }}
    </div>

</body>
</html>
