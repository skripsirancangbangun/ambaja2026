<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Posisi Persediaan</title>
    <style>
        @page { margin: 22px 24px; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 8px;
            color: #1f2937;
        }
        h1 {
            text-align: center;
            font-size: 16px;
            margin: 0 0 4px;
            color: #15235f;
        }
        .company, .subtitle { text-align: center; margin-bottom: 4px; }
        .company { font-size: 10px; font-weight: bold; }
        .subtitle { color: #64748b; font-size: 8px; }
        .filters { margin: 12px 0; padding: 7px; border: 1px solid #cbd5e1; }
        .summary { width: 100%; border-collapse: collapse; margin: 10px 0 14px; }
        .summary td { width: 25%; border: 1px solid #cbd5e1; padding: 7px; }
        .label { color: #64748b; font-size: 7px; text-transform: uppercase; }
        .value { font-size: 11px; font-weight: bold; margin-top: 4px; color: #15235f; }
        table.data { width: 100%; border-collapse: collapse; }
        .data thead { display: table-header-group; }
        .data th { background: #15235f; color: #fff; font-weight: bold; text-align: center; }
        .data th, .data td { border: 1px solid #cbd5e1; padding: 5px 4px; }
        .data tr { page-break-inside: avoid; }
        .right { text-align: right; }
        .center { text-align: center; }
        .total { background: #eef2f7; font-weight: bold; }
        .available { color: #047857; }
        .low { color: #b45309; }
        .empty { color: #dc2626; }
        .footer { margin-top: 10px; text-align: right; color: #64748b; font-size: 7px; }
    </style>
</head>
<body>
    <h1>LAPORAN POSISI PERSEDIAAN</h1>
    <div class="company">CV Andika Mustika</div>
    <div class="subtitle">Dicetak pada {{ $printedAt->format('d/m/Y H:i') }}</div>

    <div class="filters">
        <strong>Filter:</strong>
        Pencarian: {{ $filterSearch !== '' ? $filterSearch : 'Semua barang' }} |
        Gudang: {{ $filterGudang ? ('ID ' . $filterGudang) : 'Semua gudang' }} |
        Status: {{ $filterStatus !== '' ? ucfirst($filterStatus) : 'Semua status' }}
    </div>

    <table class="summary">
        <tr>
            <td><div class="label">Jenis stok tersedia</div><div class="value">{{ number_format($totalJenisStok, 0, ',', '.') }}</div></td>
            <td><div class="label">Total kuantitas</div><div class="value">{{ number_format($totalQty, 2, ',', '.') }}</div></td>
            <td><div class="label">Nilai persediaan</div><div class="value">Rp {{ number_format($totalNilai, 0, ',', '.') }}</div></td>
            <td><div class="label">Stok habis</div><div class="value">{{ number_format($stokHabis, 0, ',', '.') }}</div></td>
        </tr>
    </table>

    <table class="data">
        <thead>
            <tr>
                <th style="width:8%">Kode</th>
                <th style="width:15%">Nama barang</th>
                <th style="width:13%">Spesifikasi</th>
                <th style="width:10%">Kategori</th>
                <th style="width:11%">Gudang</th>
                <th style="width:7%">Satuan</th>
                <th style="width:7%">Qty</th>
                <th style="width:10%">Harga rata-rata</th>
                <th style="width:11%">Nilai persediaan</th>
                <th style="width:8%">Status</th>
            </tr>
        </thead>
        <tbody>
        @forelse ($stokBarangs as $stok)
            @php
                $qty = (float) $stok->qty;
                $minimumStok = (float) ($stok->barang?->minimum_stok ?? 0);
                if ($qty <= 0) {
                    $statusLabel = 'Habis';
                    $statusClass = 'empty';
                } elseif ($minimumStok > 0 && $qty <= $minimumStok) {
                    $statusLabel = 'Menipis';
                    $statusClass = 'low';
                } else {
                    $statusLabel = 'Tersedia';
                    $statusClass = 'available';
                }
            @endphp
            <tr>
                <td>{{ $stok->barang?->kode_barang ?? '-' }}</td>
                <td>
                    {{ $stok->barang?->nama_barang ?? '-' }}
                    @if ($stok->barang?->merek)
                        <br><span style="color:#64748b">{{ $stok->barang->merek }}</span>
                    @endif
                </td>
                <td>{{ $stok->barang?->spesifikasi ?: '-' }}</td>
                <td>{{ $stok->barang?->kategori?->nama_kategori ?? '-' }}</td>
                <td>{{ $stok->gudang?->nama_gudang ?? '-' }}</td>
                <td class="center">{{ $stok->barang?->satuan?->nama_satuan ?? '-' }}</td>
                <td class="right">{{ number_format((float) $stok->qty, 2, ',', '.') }}</td>
                <td class="right">Rp {{ number_format((float) $stok->harga_rata_rata, 0, ',', '.') }}</td>
                <td class="right">Rp {{ number_format((float) $stok->nilai_persediaan, 0, ',', '.') }}</td>
                <td class="center {{ $statusClass }}">{{ $statusLabel }}</td>
            </tr>
        @empty
            <tr><td colspan="10" class="center">Tidak ada data persediaan untuk filter ini.</td></tr>
        @endforelse
        </tbody>
        <tfoot>
            <tr class="total">
                <td colspan="6" class="right">TOTAL</td>
                <td class="right">{{ number_format($totalQty, 2, ',', '.') }}</td>
                <td></td>
                <td class="right">Rp {{ number_format($totalNilai, 0, ',', '.') }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">Laporan ini menggunakan saldo persediaan yang tersimpan di sistem AMBAJA.</div>
</body>
</html>
