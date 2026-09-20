<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Laba Rugi</title>
    <style>
        @page { margin: 20px 25px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8.5pt; color: #0f172a; line-height: 1.2; }
        
        .header { text-align: center; margin-bottom: 8px; }
        .header h1 { margin: 0; font-size: 13pt; color: #0f172a; font-weight: bold; text-transform: uppercase; }
        .header h2 { margin: 2px 0 0 0; font-size: 9.5pt; color: #334155; font-weight: bold; }
        .header p { margin-top: 2px; font-size: 8pt; color: #64748b; }
        .divider { border-bottom: 1.5px solid #0f172a; margin-top: 5px; margin-bottom: 8px; }
        
        table.report { width: 100%; border-collapse: collapse; }
        table.report td { padding: 2.5px 4px; vertical-align: top; }
        .code-col { width: 14%; color: #475569; font-size: 8pt; }
        .desc-col { width: 58%; }
        .amount-col { width: 28%; text-align: right; }
        
        .indent { padding-left: 12px !important; }
        .section-header { font-weight: bold; padding-top: 5px; padding-bottom: 1px; font-size: 8.5pt; color: #0f172a; text-transform: uppercase; }
        .subtotal-row td { font-weight: bold; border-top: 1px solid #cbd5e1; border-bottom: 1px solid #cbd5e1; background: #f8fafc; }
        .summary-row td { font-weight: bold; border-top: 1px solid #0f172a; border-bottom: 1px solid #0f172a; background: #f1f5f9; padding-top: 3px; padding-bottom: 3px; }
        .grand-total-row td { font-weight: bold; font-size: 9.5pt; border-top: 1.5px solid #0f172a; border-bottom: 2.5px double #0f172a; background: #f8fafc; padding-top: 4px; padding-bottom: 4px; }
        
        .footer { margin-top: 12px; text-align: right; font-size: 7.5pt; color: #94a3b8; border-top: 0.5px solid #e2e8f0; padding-top: 3px; }
    </style>
</head>
<body>

    <div class="header">
        <h1>CV AMBAJA (ANDIKA MUSTIKA)</h1>
        <h2>LAPORAN LABA RUGI</h2>
        <p>Periode: {{ date('d/m/Y', strtotime($tanggalMulai)) }} s/d {{ date('d/m/Y', strtotime($tanggalSelesai)) }}</p>
        <div class="divider"></div>
    </div>

    <table class="report">
        {{-- 1. PENJUALAN --}}
        <tr>
            <td colspan="3" class="section-header">1. PENJUALAN</td>
        </tr>
        @foreach($penjualanKotorData as $row)
            <tr>
                <td class="code-col">{{ $row['akun']->kode_akun }}</td>
                <td class="desc-col indent">{{ $row['akun']->nama_akun }}</td>
                <td class="amount-col">Rp {{ number_format($row['nominal'], 0, ',', '.') }}</td>
            </tr>
        @endforeach
        @foreach($diskonPenjualanData as $row)
            <tr>
                <td class="code-col">{{ $row['akun']->kode_akun }}</td>
                <td class="desc-col indent">(-) {{ $row['akun']->nama_akun }}</td>
                <td class="amount-col">(Rp {{ number_format($row['nominal'], 0, ',', '.') }})</td>
            </tr>
        @endforeach
        @foreach($returPenjualanData as $row)
            <tr>
                <td class="code-col">{{ $row['akun']->kode_akun }}</td>
                <td class="desc-col indent">(-) {{ $row['akun']->nama_akun }}</td>
                <td class="amount-col">(Rp {{ number_format($row['nominal'], 0, ',', '.') }})</td>
            </tr>
        @endforeach
        <tr class="subtotal-row">
            <td colspan="2" class="indent">PENJUALAN BERSIH</td>
            <td class="amount-col">Rp {{ number_format($penjualanBersih, 0, ',', '.') }}</td>
        </tr>

        {{-- 2. HPP --}}
        <tr>
            <td colspan="3" class="section-header" style="padding-top: 6px;">2. HARGA POKOK PENJUALAN (HPP)</td>
        </tr>
        @foreach($hppUtamaData as $row)
            <tr>
                <td class="code-col">{{ $row['akun']->kode_akun }}</td>
                <td class="desc-col indent">{{ $row['akun']->nama_akun }}</td>
                <td class="amount-col">Rp {{ number_format($row['nominal'], 0, ',', '.') }}</td>
            </tr>
        @endforeach
        @foreach($biayaAngkutData as $row)
            <tr>
                <td class="code-col">{{ $row['akun']->kode_akun }}</td>
                <td class="desc-col indent">(+) {{ $row['akun']->nama_akun }}</td>
                <td class="amount-col">Rp {{ number_format($row['nominal'], 0, ',', '.') }}</td>
            </tr>
        @endforeach
        @foreach($biayaBongkarData as $row)
            <tr>
                <td class="code-col">{{ $row['akun']->kode_akun }}</td>
                <td class="desc-col indent">(+) {{ $row['akun']->nama_akun }}</td>
                <td class="amount-col">Rp {{ number_format($row['nominal'], 0, ',', '.') }}</td>
            </tr>
        @endforeach
        @foreach($potonganPembelianData as $row)
            <tr>
                <td class="code-col">{{ $row['akun']->kode_akun }}</td>
                <td class="desc-col indent">(-) {{ $row['akun']->nama_akun }}</td>
                <td class="amount-col">(Rp {{ number_format($row['nominal'], 0, ',', '.') }})</td>
            </tr>
        @endforeach
        @foreach($returPembelianData as $row)
            <tr>
                <td class="code-col">{{ $row['akun']->kode_akun }}</td>
                <td class="desc-col indent">(-) {{ $row['akun']->nama_akun }}</td>
                <td class="amount-col">(Rp {{ number_format($row['nominal'], 0, ',', '.') }})</td>
            </tr>
        @endforeach
        <tr class="subtotal-row">
            <td colspan="2" class="indent">TOTAL HARGA POKOK PENJUALAN</td>
            <td class="amount-col">Rp {{ number_format($totalHpp, 0, ',', '.') }}</td>
        </tr>

        {{-- 3. LABA / RUGI KOTOR --}}
        <tr class="summary-row">
            <td colspan="2">{{ $labaKotor >= 0 ? 'LABA KOTOR' : 'RUGI KOTOR' }}</td>
            <td class="amount-col">Rp {{ number_format($labaKotor, 0, ',', '.') }}</td>
        </tr>

        {{-- 4. BEBAN OPERASIONAL --}}
        <tr>
            <td colspan="3" class="section-header" style="padding-top: 6px;">3. BEBAN OPERASIONAL</td>
        </tr>
        @foreach($bebanOpsData as $row)
            <tr>
                <td class="code-col">{{ $row['akun']->kode_akun }}</td>
                <td class="desc-col indent">{{ $row['akun']->nama_akun }}</td>
                <td class="amount-col">Rp {{ number_format($row['nominal'], 0, ',', '.') }}</td>
            </tr>
        @endforeach
        <tr class="subtotal-row">
            <td colspan="2" class="indent">TOTAL BEBAN OPERASIONAL</td>
            <td class="amount-col">Rp {{ number_format($totalBebanOps, 0, ',', '.') }}</td>
        </tr>

        {{-- 5. LABA / RUGI OPERASIONAL --}}
        <tr class="summary-row">
            <td colspan="2">{{ $labaOperasional >= 0 ? 'LABA OPERASIONAL (LABA USAHA)' : 'RUGI OPERASIONAL' }}</td>
            <td class="amount-col">Rp {{ number_format($labaOperasional, 0, ',', '.') }}</td>
        </tr>

        {{-- 6. PENDAPATAN & BEBAN LAIN-LAIN --}}
        @if(count($pendLainData) > 0 || count($bebanLainData) > 0)
            <tr>
                <td colspan="3" class="section-header" style="padding-top: 6px;">4. PENDAPATAN & BEBAN LAIN-LAIN</td>
            </tr>
            @foreach($pendLainData as $row)
                <tr>
                    <td class="code-col">{{ $row['akun']->kode_akun }}</td>
                    <td class="desc-col indent">(+) {{ $row['akun']->nama_akun }}</td>
                    <td class="amount-col">Rp {{ number_format($row['nominal'], 0, ',', '.') }}</td>
                </tr>
            @endforeach
            @foreach($bebanLainData as $row)
                <tr>
                    <td class="code-col">{{ $row['akun']->kode_akun }}</td>
                    <td class="desc-col indent">(-) {{ $row['akun']->nama_akun }}</td>
                    <td class="amount-col">(Rp {{ number_format($row['nominal'], 0, ',', '.') }})</td>
                </tr>
            @endforeach
            <tr class="summary-row">
                <td colspan="2">{{ $labaSebelumPajak >= 0 ? 'LABA BERSIH SEBELUM PAJAK' : 'RUGI SEBELUM PAJAK' }}</td>
                <td class="amount-col">Rp {{ number_format($labaSebelumPajak, 0, ',', '.') }}</td>
            </tr>
        @endif

        {{-- 7. PAJAK PENGHASILAN --}}
        @if(count($bebanPajakData) > 0)
            <tr>
                <td colspan="3" class="section-header" style="padding-top: 6px;">5. BEBAN PAJAK PENGHASILAN</td>
            </tr>
            @foreach($bebanPajakData as $row)
                <tr>
                    <td class="code-col">{{ $row['akun']->kode_akun }}</td>
                    <td class="desc-col indent">(-) {{ $row['akun']->nama_akun }}</td>
                    <td class="amount-col">(Rp {{ number_format($row['nominal'], 0, ',', '.') }})</td>
                </tr>
            @endforeach
        @endif

        {{-- 8. LABA BERSIH SETELAH PAJAK --}}
        <tr class="grand-total-row">
            <td colspan="2">{{ $labaSetelahPajak >= 0 ? 'LABA BERSIH SETELAH PAJAK' : 'RUGI BERSIH SETELAH PAJAK' }}</td>
            <td class="amount-col">Rp {{ number_format($labaSetelahPajak, 0, ',', '.') }}</td>
        </tr>
    </table>

    <div class="footer">
        Dicetak pada: {{ now()->format('d/m/Y H:i') }} | CV Ambaja
    </div>

</body>
</html>
