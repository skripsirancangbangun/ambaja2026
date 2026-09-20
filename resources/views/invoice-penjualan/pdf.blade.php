<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice - {{ $invoicePenjualan->nomor_invoice }}</title>
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
        .totals-table { width: 45%; margin-left: auto; border-collapse: collapse; }
        .totals-table td { padding: 4px 8px; font-size: 11px; }
        .totals-table .grand-total { font-size: 13px; font-weight: bold; border-top: 2px solid #15235F; color: #15235F; }
        .footer { margin-top: 40px; }
        .signature-table { width: 100%; text-align: center; }
        .signature-box { height: 60px; }
        .stamp { display: inline-block; padding: 4px 12px; font-weight: bold; font-size: 12px; border: 2px solid; border-radius: 4px; }
        .stamp-lunas { border-color: #059669; color: #059669; }
        .stamp-tempo { border-color: #e11d48; color: #e11d48; }
    </style>
</head>
<body>

    @php
        $profil = \App\Models\ProfilPerusahaan::getProfil();
    @endphp

    <div class="header">
        <table width="100%">
            <tr>
                <td>
                    <div class="company-name">{{ strtoupper($profil->nama_perusahaan) }}</div>
                    <div style="font-size: 11px; color: #475569;">{{ $profil->alamat }} | Telp: {{ $profil->telepon }}</div>
                </td>
                <td class="text-right">
                    <div class="doc-title">FAKTUR PENJUALAN</div>
                    <div><strong>No:</strong> {{ $invoicePenjualan->nomor_invoice }}</div>
                    <div><strong>Tanggal:</strong> {{ $invoicePenjualan->tanggal_invoice ? $invoicePenjualan->tanggal_invoice->format('d/m/Y') : '-' }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="meta-table">
        <tr>
            <td width="50%">
                <strong>Ditagihkan Kepada:</strong><br>
                <strong>{{ $invoicePenjualan->customer->nama_customer ?? '-' }}</strong><br>
                {{ $invoicePenjualan->customer->alamat ?? '-' }}<br>
                Telp: {{ $invoicePenjualan->customer->telepon ?? '-' }}
            </td>
            <td width="50%" class="text-right">
                <strong>Status Pembayaran:</strong> 
                @if($invoicePenjualan->status_pembayaran == 'Lunas')
                    <span class="stamp stamp-lunas">LUNAS</span>
                @else
                    <span class="stamp stamp-tempo">TEMPO (BELUM LUNAS)</span>
                @endif
                <br><br>
                <strong>Tipe Transaksi:</strong> {{ $invoicePenjualan->tipe_pembayaran }}<br>
                @if($invoicePenjualan->tipe_pembayaran == 'Kredit')
                    <strong>Jatuh Tempo:</strong> {{ $invoicePenjualan->tanggal_jatuh_tempo ? $invoicePenjualan->tanggal_jatuh_tempo->format('d/m/Y') : '-' }}<br>
                @endif
                <strong>Gudang:</strong> {{ $invoicePenjualan->gudang->nama_gudang ?? '-' }}
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
            @foreach($invoicePenjualan->items as $index => $item)
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
            <td class="text-right">Rp {{ number_format($invoicePenjualan->subtotal, 0, ',', '.') }}</td>
        </tr>
        @if($invoicePenjualan->diskon > 0)
            <tr>
                <td>Diskon:</td>
                <td class="text-right">- Rp {{ number_format($invoicePenjualan->diskon, 0, ',', '.') }}</td>
            </tr>
        @endif
        @if($invoicePenjualan->pajak > 0)
            <tr>
                <td>PPN ({{ $invoicePenjualan->pajak_persen }}%):</td>
                <td class="text-right">Rp {{ number_format($invoicePenjualan->pajak, 0, ',', '.') }}</td>
            </tr>
        @endif
        <tr class="grand-total">
            <td>Total Faktur:</td>
            <td class="text-right">Rp {{ number_format($invoicePenjualan->total, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>Sudah Dibayar:</td>
            <td class="text-right">Rp {{ number_format($invoicePenjualan->sudah_dibayar, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td><strong>Sisa Tagihan / Piutang:</strong></td>
            <td class="text-right"><strong>Rp {{ number_format($invoicePenjualan->sisa_piutang, 0, ',', '.') }}</strong></td>
        </tr>
    </table>

    @if($invoicePenjualan->catatan)
        <div style="margin-top: 20px; font-size: 11px;">
            <strong>Catatan:</strong> {{ $invoicePenjualan->catatan }}
        </div>
    @endif

    <div class="footer">
        <table class="signature-table">
            <tr>
                <td width="50%">
                    Hormat Kami,<br>
                    <div class="signature-box"></div>
                    ( {{ $invoicePenjualan->creator->name ?? 'Bagian Penjualan' }} )
                </td>
                <td width="50%">
                    Penerima / Customer,<br>
                    <div class="signature-box"></div>
                    ( {{ $invoicePenjualan->customer->nama_customer ?? '................................' }} )
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
