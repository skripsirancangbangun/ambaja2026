<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Arus Kas (Metode Langsung)</title>
    <style>
        @page { margin: 25px 30px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; }
        .header { text-align: center; margin-bottom: 18px; }
        .header h1 { margin: 0; font-size: 18px; color: #15235F; }
        .header p { margin-top: 5px; color: #64748b; }
        .section-title { font-size: 11px; font-weight: bold; color: #15235F; border-bottom: 1px solid #cbd5e1; padding-bottom: 4px; margin-top: 15px; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 5px; }
        table.data td { padding: 5px 4px; border-bottom: 1px solid #e2e8f0; }
        .right { text-align: right; }
        .total-row { font-weight: bold; background: #f8fafc; border-top: 1px solid #cbd5e1; }
        .summary-box { margin-top: 20px; padding: 12px; border: 1px solid #cbd5e1; background: #f1f5f9; }
        .footer { margin-top: 20px; text-align: right; font-size: 8px; color: #64748b; }
    </style>
</head>
<body>

    <div class="header">
        <h1>LAPORAN ARUS KAS (METODE LANGSUNG)</h1>
        <p>CV Andika Mustika (Ambaja) | Periode: {{ date('d/m/Y', strtotime($tanggalMulai)) }} s/d {{ date('d/m/Y', strtotime($tanggalSelesai)) }}</p>
    </div>

    <div class="section-title">1. ARUS KAS DARI AKTIVITAS OPERASIONAL</div>
    <table class="data">
        <tr>
            <td style="padding-left: 10px;">Penerimaan Kas dari Pelanggan & Penjualan</td>
            <td class="right" style="color: #059669;">Rp {{ number_format($penerimaanPelanggan, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td style="padding-left: 10px;">Penerimaan Kas Operasional Lainnya</td>
            <td class="right" style="color: #059669;">Rp {{ number_format($penerimaanLainnya, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td style="padding-left: 10px;">Pembayaran Kas kepada Supplier / Pembelian</td>
            <td class="right" style="color: #e11d48;">- Rp {{ number_format($pembayaranSupplier, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td style="padding-left: 10px;">Pembayaran Kas untuk Beban Operasional & Administrasi</td>
            <td class="right" style="color: #e11d48;">- Rp {{ number_format($pembayaranBeban, 0, ',', '.') }}</td>
        </tr>
        <tr class="total-row">
            <td>Arus Kas Bersih Operasional</td>
            <td class="right" style="color: {{ $arusKasOperasional >= 0 ? '#059669' : '#e11d48' }};">
                Rp {{ number_format($arusKasOperasional, 0, ',', '.') }}
            </td>
        </tr>
    </table>

    <div class="section-title">2. ARUS KAS DARI AKTIVITAS INVESTASI</div>
    <table class="data">
        <tr>
            <td>Pembelian Aset Tetap / Peralatan</td>
            <td class="right" style="color: #e11d48;">
                {{ $arusKasInvestasi < 0 ? '-' : '' }} Rp {{ number_format(abs($arusKasInvestasi), 0, ',', '.') }}
            </td>
        </tr>
        <tr class="total-row">
            <td>Arus Kas Bersih Investasi</td>
            <td class="right" style="color: {{ $arusKasInvestasi >= 0 ? '#059669' : '#e11d48' }};">
                Rp {{ number_format($arusKasInvestasi, 0, ',', '.') }}
            </td>
        </tr>
    </table>

    <div class="section-title">3. ARUS KAS DARI AKTIVITAS PENDANAAN</div>
    <table class="data">
        <tr>
            <td>Setoran Modal Owner</td>
            <td class="right">Rp {{ number_format($setoranModal, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Pengambilan Prive Owner</td>
            <td class="right" style="color: #e11d48;">- Rp {{ number_format($penarikanPrive, 0, ',', '.') }}</td>
        </tr>
        <tr class="total-row">
            <td>Arus Kas Bersih Pendanaan</td>
            <td class="right" style="color: {{ $arusKasPendanaan >= 0 ? '#059669' : '#e11d48' }};">
                Rp {{ number_format($arusKasPendanaan, 0, ',', '.') }}
            </td>
        </tr>
    </table>

    <div class="summary-box">
        <table width="100%">
            <tr>
                <td><strong>Kenaikan / (Penurunan) Bersih Kas Periode Ini:</strong></td>
                <td class="right"><strong>Rp {{ number_format($kenaikanBersihKas, 0, ',', '.') }}</strong></td>
            </tr>
            <tr>
                <td>Saldo Kas & Bank Awal Periode:</td>
                <td class="right">Rp {{ number_format($saldoKasAwal, 0, ',', '.') }}</td>
            </tr>
            <tr style="font-size: 12px; color: #15235F;">
                <td><strong>Saldo Kas & Bank Akhir Periode:</strong></td>
                <td class="right"><strong>Rp {{ number_format($saldoKasAkhir, 0, ',', '.') }}</strong></td>
            </tr>
        </table>
    </div>

    <div class="footer">
        Dicetak pada {{ now()->format('d/m/Y H:i') }}
    </div>

</body>
</html>
