<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <title>Laporan Persediaan</title>

    <style>
        @page {
            margin: 24px 28px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #1e293b;
        }

        .header {
            text-align: center;
            margin-bottom: 18px;
        }

        .header h1 {
            margin: 0;
            font-size: 18px;
            color: #15235F;
        }

        .header .company {
            margin-top: 4px;
            font-size: 11px;
            font-weight: bold;
            color: #334155;
        }

        .header .periode {
            margin-top: 4px;
            color: #64748b;
        }

        .summary {
            width: 100%;
            margin-bottom: 15px;
            border-spacing: 7px 0;
        }

        .summary td {
            width: 25%;
            border: 1px solid #cbd5e1;
            padding: 8px;
            vertical-align: top;
        }

        .summary-label {
            font-size: 7px;
            color: #64748b;
            text-transform: uppercase;
        }

        .summary-value {
            margin-top: 4px;
            font-size: 11px;
            font-weight: bold;
            color: #15235F;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
        }

        table.data th {
            background: #15235F;
            color: #ffffff;
            border: 1px solid #cbd5e1;
            padding: 6px 4px;
            font-size: 7px;
            text-align: center;
        }

        table.data td {
            border: 1px solid #cbd5e1;
            padding: 5px 4px;
            vertical-align: top;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        .bold {
            font-weight: bold;
        }

        .total-row {
            background: #f1f5f9;
            font-weight: bold;
        }

        .status-tersedia {
            color: #047857;
            font-weight: bold;
        }

        .status-menipis {
            color: #b45309;
            font-weight: bold;
        }

        .status-habis {
            color: #dc2626;
            font-weight: bold;
        }

        .footer {
            margin-top: 12px;
            text-align: right;
            font-size: 8px;
            color: #64748b;
        }
    </style>
</head>


<body>

    {{-- HEADER --}}
    <div class="header">

        <h1>
            LAPORAN PERSEDIAAN
        </h1>

        <div class="company">
            CV Andika Mustika
        </div>

        <div class="periode">
            Posisi persediaan per {{ now()->format('d/m/Y H:i') }}
        </div>

    </div>


    {{-- SUMMARY --}}
    <table class="summary">

        <tr>

            <td>

                <div class="summary-label">
                    Jenis Stok Aktif
                </div>

                <div class="summary-value">
                    {{ number_format(
                        $totalJenisStok,
                        0,
                        ',',
                        '.'
                    ) }}
                </div>

            </td>


            <td>

                <div class="summary-label">
                    Total Qty
                </div>

                <div class="summary-value">
                    {{ number_format(
                        $totalQty,
                        2,
                        ',',
                        '.'
                    ) }}
                </div>

            </td>


            <td>

                <div class="summary-label">
                    Nilai Persediaan
                </div>

                <div class="summary-value">
                    Rp {{ number_format(
                        $totalNilai,
                        0,
                        ',',
                        '.'
                    ) }}
                </div>

            </td>


            <td>

                <div class="summary-label">
                    Stok Habis
                </div>

                <div class="summary-value">
                    {{ number_format(
                        $stokHabis,
                        0,
                        ',',
                        '.'
                    ) }}
                </div>

            </td>

        </tr>

    </table>


    {{-- DETAIL PERSEDIAAN --}}
    <table class="data">

        <thead>

            <tr>

                <th>
                    Kode
                </th>

                <th>
                    Barang
                </th>

                <th>
                    Spesifikasi
                </th>

                <th>
                    Kategori
                </th>

                <th>
                    Gudang
                </th>

                <th>
                    Satuan
                </th>

                <th>
                    Qty
                </th>

                <th>
                    Harga Rata-rata
                </th>

                <th>
                    Nilai Persediaan
                </th>

                <th>
                    Status
                </th>

            </tr>

        </thead>


        <tbody>

            @forelse($stokBarangs as $stok)

                @php

                    $qty = (float) $stok->qty;

                    $minimumStok = (float) (
                        $stok->barang?->minimum_stok ?? 0
                    );


                    if ($qty <= 0) {

                        $statusLabel = 'Habis';

                        $statusClass =
                            'status-habis';

                    } elseif (
                        $minimumStok > 0
                        && $qty <= $minimumStok
                    ) {

                        $statusLabel = 'Menipis';

                        $statusClass =
                            'status-menipis';

                    } else {

                        $statusLabel = 'Tersedia';

                        $statusClass =
                            'status-tersedia';
                    }

                @endphp


                <tr>

                    {{-- KODE --}}
                    <td class="bold">
                        {{ $stok->barang?->kode_barang ?? '-' }}
                    </td>


                    {{-- BARANG --}}
                    <td>

                        {{ $stok->barang?->nama_barang ?? '-' }}

                        @if($stok->barang?->merek)

                            <br>

                            <span style="font-size:7px; color:#64748b;">
                                {{ $stok->barang->merek }}
                            </span>

                        @endif

                    </td>


                    {{-- SPESIFIKASI --}}
                    <td>
                        {{ $stok->barang?->spesifikasi ?: '-' }}
                    </td>


                    {{-- KATEGORI --}}
                    <td>
                        {{ $stok->barang?->kategori?->nama_kategori ?? '-' }}
                    </td>


                    {{-- GUDANG --}}
                    <td>

                        {{ $stok->gudang?->nama_gudang ?? '-' }}

                        @if($stok->gudang?->kode_gudang)

                            <br>

                            <span style="font-size:7px; color:#64748b;">
                                {{ $stok->gudang->kode_gudang }}
                            </span>

                        @endif

                    </td>


                    {{-- SATUAN --}}
                    <td class="center">
                        {{ $stok->barang?->satuan?->nama_satuan ?? '-' }}
                    </td>


                    {{-- QTY --}}
                    <td class="right">
                        {{ number_format(
                            $stok->qty,
                            2,
                            ',',
                            '.'
                        ) }}
                    </td>


                    {{-- HARGA RATA-RATA --}}
                    <td class="right">
                        Rp {{ number_format(
                            $stok->harga_rata_rata,
                            0,
                            ',',
                            '.'
                        ) }}
                    </td>


                    {{-- NILAI --}}
                    <td class="right bold">
                        Rp {{ number_format(
                            $stok->nilai_persediaan,
                            0,
                            ',',
                            '.'
                        ) }}
                    </td>


                    {{-- STATUS --}}
                    <td class="center {{ $statusClass }}">
                        {{ $statusLabel }}
                    </td>

                </tr>


            @empty

                <tr>

                    <td
                        colspan="10"
                        class="center"
                        style="padding:15px;"
                    >
                        Tidak ada data persediaan.
                    </td>

                </tr>

            @endforelse


            {{-- TOTAL --}}
            @if($stokBarangs->count() > 0)

                <tr class="total-row">

                    <td
                        colspan="6"
                        class="right"
                    >
                        TOTAL
                    </td>

                    <td class="right">
                        {{ number_format(
                            $stokBarangs->sum('qty'),
                            2,
                            ',',
                            '.'
                        ) }}
                    </td>

                    <td></td>

                    <td class="right">
                        Rp {{ number_format(
                            $stokBarangs->sum('nilai_persediaan'),
                            0,
                            ',',
                            '.'
                        ) }}
                    </td>

                    <td></td>

                </tr>

            @endif

        </tbody>

    </table>


    {{-- FOOTER --}}
    <div class="footer">

        Dicetak pada
        {{ now()->format('d/m/Y H:i') }}

    </div>

</body>

</html>