<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Nota Retur - {{ $returPenjualan->nomor_retur }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 11px; color: #333; margin: 0; padding: 20px; }
        .header { border-bottom: 2px solid #be123c; padding-bottom: 10px; margin-bottom: 20px; }
        .company-name { font-size: 18px; font-weight: bold; color: #15235F; }
        .doc-title { font-size: 16px; font-weight: bold; text-align: right; color: #be123c; }
        .meta-table { width: 100%; margin-bottom: 20px; }
        .meta-table td { vertical-align: top; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table th { background-color: #f8fafc; border-bottom: 2px solid #cbd5e1; padding: 8px; text-align: left; font-size: 10px; text-transform: uppercase; color: #64748b; }
        .items-table td { border-bottom: 1px solid #e2e8f0; padding: 8px; font-size: 10px; }
        .text-right { text-align: right; }
        .totals-table { width: 50%; margin-left: auto; border-collapse: collapse; }
        .totals-table td { padding: 4px 8px; font-size: 10px; }
        .totals-table .grand-total { font-size: 12px; font-weight: bold; border-top: 2px solid #be123c; color: #be123c; }
        .reason-box { background-color: #fffbebfb; border: 1px solid #fef3c7; padding: 8px; border-radius: 4px; font-size: 10px; margin-bottom: 20px; }
        .footer { margin-top: 40px; }
        .signature-table { width: 100%; text-align: center; }
        .signature-box { height: 60px; }
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
                    <div style="font-size: 10px; color: #475569;">{{ $profil->alamat }} | Telp: {{ $profil->telepon }}</div>
                </td>
                <td class="text-right">
                    <div class="doc-title">NOTA RETUR PENJUALAN</div>
                    <div><strong>No. Retur:</strong> {{ $returPenjualan->nomor_retur }}</div>
                    <div><strong>Tanggal:</strong> {{ $returPenjualan->tanggal_retur ? $returPenjualan->tanggal_retur->format('d/m/Y') : '-' }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="meta-table">
        <tr>
            <td width="50%">
                <strong>Customer:</strong><br>
                <strong>{{ $returPenjualan->customer->nama_customer ?? '-' }}</strong><br>
                {{ $returPenjualan->customer->alamat ?? '-' }}<br>
                Telp: {{ $returPenjualan->customer->telepon ?? '-' }}
            </td>
            <td width="50%" class="text-right">
                <strong>No. Invoice Asal:</strong> {{ $returPenjualan->invoicePenjualan?->nomor_invoice ?? '-' }}<br>
                <strong>Gudang Restock:</strong> {{ $returPenjualan->gudang?->nama_gudang ?? '-' }}<br>
                <strong>Petugas:</strong> {{ $returPenjualan->creator?->name ?? 'Admin' }}
            </td>
        </tr>
    </table>

    @if($returPenjualan->alasan || $returPenjualan->alasan_retur)
        <div class="reason-box">
            <strong>Alasan Retur:</strong> {{ $returPenjualan->alasan ?? $returPenjualan->alasan_retur }}
        </div>
    @endif

    <table class="items-table">
        <thead>
            <tr>
                <th width="5%">No</th>
                <th width="20%">Kode Barang</th>
                <th width="35%">Nama Barang</th>
                <th width="12%" class="text-right">Qty Retur</th>
                <th width="13%" class="text-right">Harga Satuan</th>
                <th width="15%" class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($returPenjualan->items as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $item->barang->kode_barang ?? '-' }}</td>
                    <td>{{ $item->barang->nama_barang ?? '-' }}</td>
                    <td class="text-right"><strong>{{ number_format($item->qty, 0, ',', '.') }}</strong> {{ $item->barang->satuan->nama_satuan ?? '' }}</td>
                    <td class="text-right">Rp {{ number_format($item->harga_satuan, 0, ',', '.') }}</td>
                    <td class="text-right">Rp {{ number_format($item->subtotal, 0, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals-table">
        <tr>
            <td>Subtotal Barang Retur:</td>
            <td class="text-right">Rp {{ number_format($returPenjualan->subtotal, 0, ',', '.') }}</td>
        </tr>
        @if($returPenjualan->pajak > 0)
            <tr>
                <td>PPN Retur:</td>
                <td class="text-right">Rp {{ number_format($returPenjualan->pajak, 0, ',', '.') }}</td>
            </tr>
        @endif
        <tr class="grand-total">
            <td>TOTAL RETUR:</td>
            <td class="text-right">Rp {{ number_format($returPenjualan->total, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td style="padding-top: 10px;">• Alokasi Potong Piutang:</td>
            <td class="text-right" style="padding-top: 10px;">Rp {{ number_format($returPenjualan->potong_piutang, 0, ',', '.') }}</td>
        </tr>
        <tr>
            <td>• Alokasi Pengembalian Kas/Bank:</td>
            <td class="text-right">Rp {{ number_format($returPenjualan->pengembalian_kas, 0, ',', '.') }}</td>
        </tr>
    </table>

    <div class="footer">
        <table class="signature-table">
            <tr>
                <td width="33%">
                    Hormat Kami,<br>
                    <div class="signature-box"></div>
                    ( ____________________ )
                </td>
                <td width="33%">
                    Gudang Restock,<br>
                    <div class="signature-box"></div>
                    ( ____________________ )
                </td>
                <td width="33%">
                    Penerima / Customer,<br>
                    <div class="signature-box"></div>
                    ( <strong>{{ $returPenjualan->customer->nama_customer ?? 'Customer' }}</strong> )
                </td>
            </tr>
        </table>
    </div>

</body>
</html>
