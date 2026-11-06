<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Nota Retur - {{ $returPenjualan->nomor_retur }}</title>
    <style>
        @page {
            size: a5 landscape;
            margin: 8mm 10mm;
        }
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 8.5pt; color: #1e293b; margin: 0; padding: 0; line-height: 1.25; }
        .header { border-bottom: 1.5px solid #be123c; padding-bottom: 6px; margin-bottom: 8px; }
        .company-name { font-size: 13pt; font-weight: bold; color: #15235F; }
        .doc-title { font-size: 13pt; font-weight: bold; text-align: right; color: #be123c; }
        .meta-table { width: 100%; margin-bottom: 8px; font-size: 8pt; }
        .meta-table td { vertical-align: top; }
        .meta-box { background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; padding: 5px 8px; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        .items-table th { background-color: #be123c; color: #ffffff; border: 1px solid #be123c; padding: 4px 6px; text-align: left; font-size: 7.5pt; text-transform: uppercase; }
        .items-table td { border: 1px solid #cbd5e1; padding: 3px 6px; font-size: 8pt; }
        .items-table tr:nth-child(even) { background-color: #f8fafc; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .font-mono { font-family: 'Courier New', Courier, monospace; }
        .totals-table { width: 100%; border-collapse: collapse; font-size: 8pt; }
        .totals-table td { padding: 2px 4px; }
        .totals-table .grand-total { font-size: 9pt; font-weight: bold; border-top: 1.5px solid #be123c; border-bottom: 1.5px solid #be123c; color: #be123c; background-color: #fff1f2; }
        .reason-box { background-color: #fffbebfb; border: 1px solid #fef3c7; padding: 5px 8px; border-radius: 4px; font-size: 7.5pt; }
        .signature-table { width: 100%; text-align: center; margin-top: 10px; font-size: 7.5pt; }
        .signature-table td { vertical-align: top; padding: 0 10px; }
        .signature-space { height: 36px; }
        .signature-line { border-top: 1px dotted #64748b; margin-top: 2px; font-weight: bold; }
    </style>
</head>
<body>

    @php
        $profil = \App\Models\ProfilPerusahaan::getProfil();
    @endphp

    <div class="header">
        <table width="100%">
            <tr>
                <td width="55%">
                    <div class="company-name">{{ strtoupper($profil->nama_perusahaan ?? 'CV AMBAJA') }}</div>
                    <div style="font-size: 7.5pt; color: #475569;">{{ $profil->alamat }} @if(!empty($profil->telepon)) | Telp: {{ $profil->telepon }} @endif</div>
                </td>
                <td width="45%" class="text-right">
                    <div class="doc-title">NOTA RETUR PENJUALAN</div>
                    <div style="font-size: 8.5pt;"><strong>No:</strong> <span class="font-mono">{{ $returPenjualan->nomor_retur }}</span></div>
                    <div style="font-size: 8pt;"><strong>Tanggal:</strong> {{ $returPenjualan->tanggal_retur ? $returPenjualan->tanggal_retur->format('d/m/Y') : '-' }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="meta-table">
        <tr>
            <td width="48%">
                <div class="meta-box">
                    <div style="font-size: 7pt; color: #64748b; font-weight: bold; text-transform: uppercase;">Customer:</div>
                    <div style="font-weight: bold; font-size: 9pt; color: #0f172a;">{{ $returPenjualan->customer->nama_customer ?? '-' }}</div>
                    <div style="color: #475569;">{{ $returPenjualan->customer->alamat ?? '-' }}</div>
                    <div style="color: #475569;">Telp: {{ $returPenjualan->customer->telepon ?? '-' }}</div>
                </div>
            </td>
            <td width="4%"></td>
            <td width="48%">
                <div class="meta-box">
                    <table width="100%">
                        <tr>
                            <td width="45%"><strong>No. Invoice Asal:</strong></td>
                            <td width="55%" class="font-mono font-bold">{{ $returPenjualan->invoicePenjualan?->nomor_invoice ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Gudang Restock:</strong></td>
                            <td>{{ $returPenjualan->gudang?->nama_gudang ?? '-' }}</td>
                        </tr>
                        <tr>
                            <td><strong>Petugas:</strong></td>
                            <td>{{ $returPenjualan->creator?->name ?? 'Admin' }}</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th width="4%" class="text-center">No</th>
                <th width="16%">Kode Barang</th>
                <th width="38%">Nama Barang</th>
                <th width="12%" class="text-right">Qty Retur</th>
                <th width="14%" class="text-right">Harga Jual</th>
                <th width="16%" class="text-right">Subtotal Bruto</th>
            </tr>
        </thead>
        <tbody>
            @foreach($returPenjualan->items as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="font-mono">{{ $item->barang->kode_barang ?? '-' }}</td>
                    <td><strong>{{ $item->barang->nama_barang ?? '-' }}</strong></td>
                    <td class="text-right font-mono font-bold">{{ number_format($item->qty, 0, ',', '.') }} {{ $item->barang->satuan->nama_satuan ?? '' }}</td>
                    <td class="text-right font-mono">Rp {{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
                    <td class="text-right font-mono font-bold">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table width="100%" style="margin-top: 4px;">
        <tr>
            <td width="55%" style="vertical-align: top; padding-right: 15px;">
                @if($returPenjualan->alasan || $returPenjualan->alasan_retur)
                    <div class="reason-box">
                        <strong>Alasan Retur:</strong> {{ $returPenjualan->alasan ?? $returPenjualan->alasan_retur }}
                    </div>
                @endif
                <div style="font-size: 7.5pt; color: #64748b; margin-top: 4px;">
                    Barang fisik telah diterima kembali ke gudang dan di-restock ke persediaan.
                </div>
            </td>
            <td width="45%" style="vertical-align: top;">
                <table class="totals-table">
                    @php
                        $bruto = $returPenjualan->items->sum('subtotal');
                    @endphp
                    <tr>
                        <td>Nilai Barang (Bruto):</td>
                        <td class="text-right font-mono">Rp {{ number_format($bruto, 0, ',', '.') }}</td>
                    </tr>
                    @if($returPenjualan->nominal_potongan > 0 || $returPenjualan->potongan_persen > 0)
                        <tr>
                            <td style="color: #be123c;">Potongan Retur ({{ number_format($returPenjualan->potongan_persen, 0) }}%):</td>
                            <td class="text-right font-mono" style="color: #be123c;">- Rp {{ number_format($returPenjualan->nominal_potongan, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td>Subtotal Netto:</td>
                        <td class="text-right font-mono">Rp {{ number_format($returPenjualan->subtotal, 0, ',', '.') }}</td>
                    </tr>
                    @if($returPenjualan->pajak > 0)
                        <tr>
                            <td>PPN Retur:</td>
                            <td class="text-right font-mono">Rp {{ number_format($returPenjualan->pajak, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    <tr class="grand-total">
                        <td style="padding: 3px 4px;">TOTAL NOTA RETUR:</td>
                        <td class="text-right font-mono" style="padding: 3px 4px;">Rp {{ number_format($returPenjualan->total, 0, ',', '.') }}</td>
                    </tr>
                    @if($returPenjualan->potong_piutang > 0)
                        <tr>
                            <td style="padding-top: 4px; font-size: 7.5pt;">• Potong Piutang Invoice:</td>
                            <td class="text-right font-mono" style="padding-top: 4px; font-size: 7.5pt;">Rp {{ number_format($returPenjualan->potong_piutang, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                    @if($returPenjualan->pengembalian_kas > 0)
                        <tr>
                            <td style="font-size: 7.5pt;">• Refund Kas/Bank:</td>
                            <td class="text-right font-mono" style="font-size: 7.5pt;">Rp {{ number_format($returPenjualan->pengembalian_kas, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                </table>
            </td>
        </tr>
    </table>

    <table class="signature-table">
        <tr>
            <td width="33%">
                Penerima / Customer
                <div class="signature-space"></div>
                <div class="signature-line">
                    ( {{ $returPenjualan->customer->nama_customer ?? 'Customer' }} )
                </div>
            </td>
            <td width="33%">
                Gudang Restock
                <div class="signature-space"></div>
                <div class="signature-line">
                    ( ............................... )
                </div>
            </td>
            <td width="34%">
                Hormat Kami (Admin / Kasir)
                <div class="signature-space"></div>
                <div class="signature-line">
                    ( {{ $returPenjualan->creator->name ?? 'CV Ambaja' }} )
                </div>
            </td>
        </tr>
    </table>

</body>
</html>
