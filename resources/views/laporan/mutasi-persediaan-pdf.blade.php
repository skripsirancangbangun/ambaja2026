<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <title>Laporan Mutasi Persediaan</title>

    <style>
        @page {
            margin: 22px 24px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 7.5px;
            color: #1e293b;
        }

        .header {
            text-align: center;
            margin-bottom: 16px;
        }

        .header h1 {
            margin: 0;
            font-size: 17px;
            color: #15235F;
        }

        .header .company {
            margin-top: 4px;
            font-size: 10px;
            font-weight: bold;
            color: #334155;
        }

        .header .periode {
            margin-top: 4px;
            color: #64748b;
        }

        .summary {
            width: 100%;
            margin-bottom: 14px;
            border-spacing: 6px 0;
        }

        .summary td {
            width: 25%;
            border: 1px solid #cbd5e1;
            padding: 7px;
            vertical-align: top;
        }

        .summary-label {
            font-size: 7px;
            color: #64748b;
            text-transform: uppercase;
        }

        .summary-qty {
            margin-top: 4px;
            font-size: 10px;
            font-weight: bold;
            color: #15235F;
        }

        .summary-value {
            margin-top: 2px;
            font-size: 8px;
            color: #475569;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
        }

        table.data th {
            background: #15235F;
            color: #ffffff;
            border: 1px solid #cbd5e1;
            padding: 5px 3px;
            font-size: 6.2px;
            text-align: center;
        }

        table.data td {
            border: 1px solid #cbd5e1;
            padding: 4px 3px;
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

        .masuk {
            color: #047857;
        }

        .keluar {
            color: #dc2626;
        }

        .akhir {
            color: #15235F;
            font-weight: bold;
        }

        .footer {
            margin-top: 10px;
            text-align: right;
            font-size: 7px;
            color: #64748b;
        }
    </style>
</head>


<body>

    {{-- HEADER --}}
    <div class="header">

        <h1>
            LAPORAN MUTASI PERSEDIAAN
        </h1>

        <div class="company">
            CV Andika Mustika
        </div>

        <div class="periode">
            Periode
            {{ \Carbon\Carbon::parse($tanggalMulai)->format('d/m/Y') }}
            s/d
            {{ \Carbon\Carbon::parse($tanggalSelesai)->format('d/m/Y') }}
        </div>

    </div>


    {{-- SUMMARY --}}
    <table class="summary">

        <tr>

            <td>
                <div class="summary-label">
                    Persediaan Awal
                </div>

                <div class="summary-qty">
                    {{ number_format(
                        $mutasiSummary['stok_awal'],
                        2,
                        ',',
                        '.'
                    ) }}
                </div>

                <div class="summary-value">
                    Rp {{ number_format(
                        $mutasiSummary['nilai_awal'],
                        0,
                        ',',
                        '.'
                    ) }}
                </div>
            </td>


            <td>
                <div class="summary-label">
                    Persediaan Masuk
                </div>

                <div class="summary-qty masuk">
                    {{ number_format(
                        $mutasiSummary['stok_masuk'],
                        2,
                        ',',
                        '.'
                    ) }}
                </div>

                <div class="summary-value masuk">
                    Rp {{ number_format(
                        $mutasiSummary['nilai_masuk'],
                        0,
                        ',',
                        '.'
                    ) }}
                </div>
            </td>


            <td>
                <div class="summary-label">
                    Persediaan Keluar
                </div>

                <div class="summary-qty keluar">
                    {{ number_format(
                        $mutasiSummary['stok_keluar'],
                        2,
                        ',',
                        '.'
                    ) }}
                </div>

                <div class="summary-value keluar">
                    Rp {{ number_format(
                        $mutasiSummary['nilai_keluar'],
                        0,
                        ',',
                        '.'
                    ) }}
                </div>
            </td>


            <td>
                <div class="summary-label">
                    Persediaan Akhir
                </div>

                <div class="summary-qty akhir">
                    {{ number_format(
                        $mutasiSummary['stok_akhir'],
                        2,
                        ',',
                        '.'
                    ) }}
                </div>

                <div class="summary-value akhir">
                    Rp {{ number_format(
                        $mutasiSummary['nilai_akhir'],
                        0,
                        ',',
                        '.'
                    ) }}
                </div>
            </td>

        </tr>

    </table>


    {{-- DETAIL --}}
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
                    Satuan
                </th>

                <th>
                    Stok Awal
                </th>

                <th>
                    Nilai Awal
                </th>

                <th>
                    Masuk
                </th>

                <th>
                    Nilai Masuk
                </th>

                <th>
                    Keluar
                </th>

                <th>
                    Nilai Keluar
                </th>

                <th>
                    Stok Akhir
                </th>

                <th>
                    Nilai Akhir
                </th>

            </tr>

        </thead>


        <tbody>

            @forelse($mutasiPersediaan as $item)

                <tr>

                    {{-- KODE --}}
                    <td class="bold">
                        {{ $item->kode_barang }}
                    </td>


                    {{-- BARANG --}}
                    <td>
                        {{ $item->nama_barang }}
                    </td>


                    {{-- SPESIFIKASI --}}
                    <td>
                        {{ $item->spesifikasi ?: '-' }}
                    </td>


                    {{-- SATUAN --}}
                    <td class="center">
                        {{ $item->satuan ?: '-' }}
                    </td>


                    {{-- STOK AWAL --}}
                    <td class="right">
                        {{ number_format(
                            $item->stok_awal,
                            2,
                            ',',
                            '.'
                        ) }}
                    </td>


                    {{-- NILAI AWAL --}}
                    <td class="right">
                        Rp {{ number_format(
                            $item->nilai_awal,
                            0,
                            ',',
                            '.'
                        ) }}
                    </td>


                    {{-- MASUK --}}
                    <td class="right masuk">
                        {{ number_format(
                            $item->stok_masuk,
                            2,
                            ',',
                            '.'
                        ) }}
                    </td>


                    {{-- NILAI MASUK --}}
                    <td class="right masuk">
                        Rp {{ number_format(
                            $item->nilai_masuk,
                            0,
                            ',',
                            '.'
                        ) }}
                    </td>


                    {{-- KELUAR --}}
                    <td class="right keluar">
                        {{ number_format(
                            $item->stok_keluar,
                            2,
                            ',',
                            '.'
                        ) }}
                    </td>


                    {{-- NILAI KELUAR --}}
                    <td class="right keluar">
                        Rp {{ number_format(
                            $item->nilai_keluar,
                            0,
                            ',',
                            '.'
                        ) }}
                    </td>


                    {{-- STOK AKHIR --}}
                    <td class="right akhir">
                        {{ number_format(
                            $item->stok_akhir,
                            2,
                            ',',
                            '.'
                        ) }}
                    </td>


                    {{-- NILAI AKHIR --}}
                    <td class="right akhir">
                        Rp {{ number_format(
                            $item->nilai_akhir,
                            0,
                            ',',
                            '.'
                        ) }}
                    </td>

                </tr>


            @empty

                <tr>

                    <td
                        colspan="12"
                        class="center"
                        style="padding: 15px;"
                    >
                        Tidak ada mutasi persediaan pada periode ini.
                    </td>

                </tr>

            @endforelse


            {{-- TOTAL --}}
            @if($mutasiPersediaan->count() > 0)

                <tr class="total-row">

                    <td
                        colspan="4"
                        class="right"
                    >
                        TOTAL
                    </td>


                    <td class="right">
                        {{ number_format(
                            $mutasiSummary['stok_awal'],
                            2,
                            ',',
                            '.'
                        ) }}
                    </td>


                    <td class="right">
                        Rp {{ number_format(
                            $mutasiSummary['nilai_awal'],
                            0,
                            ',',
                            '.'
                        ) }}
                    </td>


                    <td class="right masuk">
                        {{ number_format(
                            $mutasiSummary['stok_masuk'],
                            2,
                            ',',
                            '.'
                        ) }}
                    </td>


                    <td class="right masuk">
                        Rp {{ number_format(
                            $mutasiSummary['nilai_masuk'],
                            0,
                            ',',
                            '.'
                        ) }}
                    </td>


                    <td class="right keluar">
                        {{ number_format(
                            $mutasiSummary['stok_keluar'],
                            2,
                            ',',
                            '.'
                        ) }}
                    </td>


                    <td class="right keluar">
                        Rp {{ number_format(
                            $mutasiSummary['nilai_keluar'],
                            0,
                            ',',
                            '.'
                        ) }}
                    </td>


                    <td class="right akhir">
                        {{ number_format(
                            $mutasiSummary['stok_akhir'],
                            2,
                            ',',
                            '.'
                        ) }}
                    </td>


                    <td class="right akhir">
                        Rp {{ number_format(
                            $mutasiSummary['nilai_akhir'],
                            0,
                            ',',
                            '.'
                        ) }}
                    </td>

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