<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Faktur Penjualan - {{ $invoicePenjualan->nomor_invoice }}</title>
    <style>
        @page {
            size: a5 landscape;
            margin: 8mm 10mm;
        }
        * {
            box-sizing: border-box;
        }
        body {
            font-family: Arial, Helvetica, sans-serif;
            font-size: 9pt;
            color: #1e293b;
            line-height: 1.25;
            margin: 0;
            padding: 0;
        }
        .header {
            border-bottom: 1.5px solid #15235F;
            padding-bottom: 6px;
            margin-bottom: 8px;
        }
        .company-name {
            font-size: 13pt;
            font-weight: bold;
            color: #15235F;
            letter-spacing: 0.5px;
        }
        .company-sub {
            font-size: 7.5pt;
            color: #64748b;
            margin-top: 2px;
        }
        .doc-title {
            font-size: 13pt;
            font-weight: bold;
            color: #15235F;
            text-align: right;
            letter-spacing: 0.5px;
        }
        .meta-table {
            width: 100%;
            margin-bottom: 8px;
            font-size: 8.5pt;
        }
        .meta-table td {
            vertical-align: top;
            padding: 1px 0;
        }
        .meta-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 5px 8px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        .items-table th {
            background-color: #15235F;
            color: #ffffff;
            font-size: 8pt;
            font-weight: bold;
            text-transform: uppercase;
            padding: 4px 6px;
            border: 1px solid #15235F;
        }
        .items-table td {
            border: 1px solid #cbd5e1;
            padding: 3.5px 6px;
            font-size: 8.5pt;
        }
        .items-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-mono { font-family: 'Courier New', Courier, monospace; }

        .summary-container {
            width: 100%;
            margin-top: 4px;
        }
        .notes-box {
            font-size: 8pt;
            background-color: #f8fafc;
            border: 1px dashed #cbd5e1;
            border-radius: 4px;
            padding: 4px 8px;
        }
        .totals-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5pt;
        }
        .totals-table td {
            padding: 2px 4px;
        }
        .grand-total-row {
            font-weight: bold;
            font-size: 9.5pt;
            color: #15235F;
            border-top: 1.5px solid #15235F;
            border-bottom: 1.5px solid #15235F;
            background-color: #f1f5f9;
        }

        .stamp {
            display: inline-block;
            padding: 2px 8px;
            font-weight: bold;
            font-size: 8pt;
            border: 1.5px solid;
            border-radius: 3px;
            text-transform: uppercase;
        }
        .stamp-lunas { border-color: #059669; color: #059669; }
        .stamp-kredit { border-color: #d97706; color: #d97706; }
        .stamp-waiting { border-color: #0284c7; color: #0284c7; }
        .stamp-batal { border-color: #e11d48; color: #e11d48; }

        .signature-table {
            width: 100%;
            margin-top: 10px;
            text-align: center;
            font-size: 8pt;
        }
        .signature-table td {
            vertical-align: top;
            padding: 0 10px;
        }
        .signature-space {
            height: 38px;
        }
        .signature-line {
            border-top: 1px dotted #64748b;
            margin-top: 2px;
            font-weight: bold;
        }
    </style>
</head>
<body>

    @php
        $profil = \App\Models\ProfilPerusahaan::getProfil();
    @endphp

    {{-- HEADER --}}
    <div class="header">
        <table width="100%">
            <tr>
                <td width="55%">
                    <div class="company-name">{{ strtoupper($profil->nama_perusahaan ?? 'CV AMBAJA') }}</div>
                    <div class="company-sub">
                        {{ $profil->alamat ?? 'Jl. Raya No. 123' }}
                        @if(!empty($profil->telepon)) | Telp: {{ $profil->telepon }} @endif
                    </div>
                </td>
                <td width="45%" class="text-right">
                    <div class="doc-title">FAKTUR PENJUALAN (NCR)</div>
                    <div style="font-size: 8.5pt; margin-top: 2px;">
                        <strong>No. Faktur:</strong> <span class="font-mono">{{ $invoicePenjualan->nomor_invoice }}</span>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    {{-- META INFO --}}
    <table class="meta-table">
        <tr>
            <td width="48%">
                <div class="meta-box">
                    <div style="font-size: 7.5pt; text-transform: uppercase; color: #64748b; font-weight: bold;">Kepada Yth:</div>
                    <div style="font-weight: bold; font-size: 9.5pt; color: #0f172a;">{{ $invoicePenjualan->customer->nama_customer ?? '-' }}</div>
                    @if(!empty($invoicePenjualan->customer->alamat))
                        <div style="font-size: 8pt; color: #475569;">{{ $invoicePenjualan->customer->alamat }}</div>
                    @endif
                    @if(!empty($invoicePenjualan->customer->telepon))
                        <div style="font-size: 8pt; color: #475569;">Telp: {{ $invoicePenjualan->customer->telepon }}</div>
                    @endif
                </div>
            </td>
            <td width="4%"></td>
            <td width="48%">
                <div class="meta-box">
                    <table width="100%" style="font-size: 8pt;">
                        <tr>
                            <td width="40%"><strong>Tanggal:</strong></td>
                            <td width="60%">{{ $invoicePenjualan->tanggal_invoice ? $invoicePenjualan->tanggal_invoice->format('d/m/Y') : '-' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Gudang:</strong></td>
                            <td>{{ $invoicePenjualan->gudang->nama_gudang ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Pembayaran:</strong></td>
                            <td>
                                @if($invoicePenjualan->status_invoice === 'DIBATALKAN')
                                    <span class="stamp stamp-batal">DIBATALKAN</span>
                                @elseif($invoicePenjualan->status_pembayaran === 'Lunas')
                                    <span class="stamp stamp-lunas">LUNAS (TUNAI)</span>
                                @elseif($invoicePenjualan->tipe_pembayaran === 'Kredit')
                                    <span class="stamp stamp-kredit">KREDIT (TEMPO)</span>
                                    @if($invoicePenjualan->tanggal_jatuh_tempo)
                                        <span style="font-size: 7.5pt; color: #b45309;">(JT: {{ $invoicePenjualan->tanggal_jatuh_tempo->format('d/m/Y') }})</span>
                                    @endif
                                @else
                                    <span class="stamp stamp-waiting">MENUNGGU PEMBAYARAN</span>
                                @endif
                            </td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    {{-- ITEMS TABLE --}}
    <table class="items-table">
        <thead>
            <tr>
                <th width="4%" class="text-center">No</th>
                <th width="14%">Kode</th>
                <th width="36%">Nama Barang & Spesifikasi</th>
                <th width="10%" class="text-right">Qty</th>
                <th width="8%" class="text-center">Satuan</th>
                <th width="13%" class="text-right">Harga (Rp)</th>
                <th width="15%" class="text-right">Subtotal (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoicePenjualan->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="font-mono" style="font-size: 8pt;">{{ $item->barang->kode_barang ?? '-' }}</td>
                    <td>
                        <strong>{{ $item->barang->nama_barang ?? '-' }}</strong>
                        @if($item->diskon > 0)
                            <span style="font-size: 7.5pt; color: #e11d48;">(Disc: Rp {{ number_format($item->diskon, 0, ',', '.') }})</span>
                        @endif
                    </td>
                    <td class="text-right font-mono font-bold">{{ number_format($item->qty, 0, ',', '.') }}</td>
                    <td class="text-center">{{ $item->barang->satuan->nama_satuan ?? $item->barang->satuan->kode_satuan ?? '-' }}</td>
                    <td class="text-right font-mono">{{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
                    <td class="text-right font-mono font-bold">{{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- SUMMARY & TOTALS --}}
    <table class="summary-container">
        <tr>
            <td width="55%" style="vertical-align: top; padding-right: 15px;">
                <div class="notes-box">
                    <strong>Catatan:</strong> {{ $invoicePenjualan->catatan ?: 'Barang yang sudah dibeli dan diterima dalam kondisi baik tidak dapat ditukar/dikembalikan tanpa nota retur resmi.' }}
                </div>
            </td>
            <td width="45%" style="vertical-align: top;">
                <table class="totals-table">
                    <tr>
                        <td>Subtotal:</td>
                        <td class="text-right font-mono">Rp {{ number_format($invoicePenjualan->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @if($invoicePenjualan->diskon > 0)
                        <tr>
                            <td>Diskon:</td>
                            <td class="text-right font-mono text-rose-600">- Rp {{ number_format($invoicePenjualan->diskon, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    @if($invoicePenjualan->pajak > 0)
                        <tr>
                            <td>PPN ({{ $invoicePenjualan->pajak_persen }}%):</td>
                            <td class="text-right font-mono">Rp {{ number_format($invoicePenjualan->pajak, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    <tr class="grand-total-row">
                        <td style="padding: 3px 4px;">TOTAL FAKTUR:</td>
                        <td class="text-right font-mono" style="padding: 3px 4px;">Rp {{ number_format($invoicePenjualan->total, 0, ',', '.') }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    {{-- 3 SIGNATURE BOXES FOR NCR INVOICE --}}
    <table class="signature-table">
        <tr>
            <td width="33%">
                Penerima / Customer
                <div class="signature-space"></div>
                <div class="signature-line">
                    ( {{ $invoicePenjualan->customer->nama_customer ?? '...............................' }} )
                </div>
            </td>
            <td width="33%">
                Pengirim / Sopir
                <div class="signature-space"></div>
                <div class="signature-line">
                    ( ............................... )
                </div>
            </td>
            <td width="34%">
                Hormat Kami (Admin / Kasir)
                <div class="signature-space"></div>
                <div class="signature-line">
                    ( {{ $invoicePenjualan->creator->name ?? 'CV Ambaja' }} )
                </div>
            </td>
        </tr>
    </table>

</body>
</html>
