<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">

    <title>
        {{ $purchaseOrder->nomor_po }}
    </title>

    <style>
        @page {
            margin: 28px 34px 32px 34px;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1e293b;
            line-height: 1.5;
        }

        table {
            border-collapse: collapse;
        }

        /*
        |--------------------------------------------------------------------------
        | HEADER
        |--------------------------------------------------------------------------
        */

        .header {
            width: 100%;
            padding-bottom: 13px;
            margin-bottom: 20px;
            border-bottom: 2px solid #15235F;
        }

        .header-table {
            width: 100%;
        }

        .header-table td {
            vertical-align: middle;
        }

        .brand-table {
            border-collapse: collapse;
        }

        .brand-logo-cell {
            width: 52px;
            padding-right: 10px;
            vertical-align: middle;
        }

        .brand-logo {
            width: 43px;
            height: auto;
        }

        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #15235F;
            letter-spacing: 0.2px;
        }

        .company-subtitle {
            margin-top: 3px;
            font-size: 8.5px;
            color: #64748b;
        }

        .document-title {
            text-align: right;
            font-size: 18px;
            font-weight: bold;
            color: #15235F;
            letter-spacing: 0.3px;
        }

        .document-number {
            margin-top: 4px;
            text-align: right;
            font-size: 9px;
            color: #64748b;
        }

        /*
        |--------------------------------------------------------------------------
        | SECTION
        |--------------------------------------------------------------------------
        */

        .section-title {
            margin-top: 16px;
            margin-bottom: 8px;
            font-size: 9px;
            font-weight: bold;
            color: #15235F;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }

        /*
        |--------------------------------------------------------------------------
        | INFO
        |--------------------------------------------------------------------------
        */

        .info-table {
            width: 100%;
            margin-bottom: 18px;
        }

        .info-table td {
            padding: 3px 0;
            vertical-align: top;
        }

        .label {
            width: 115px;
            color: #64748b;
        }

        .colon {
            width: 12px;
            color: #64748b;
        }

        .value {
            font-weight: bold;
            color: #1e293b;
        }

        /*
        |--------------------------------------------------------------------------
        | SUPPLIER
        |--------------------------------------------------------------------------
        */

        .supplier-box {
            margin-bottom: 18px;
            padding: 9px 11px;
            border: 1px solid #dbe2ea;
            background: #f8fafc;
        }

        .supplier-name {
            margin-bottom: 3px;
            font-weight: bold;
            color: #334155;
        }

        .supplier-detail {
            color: #64748b;
        }

        /*
        |--------------------------------------------------------------------------
        | ITEM TABLE
        |--------------------------------------------------------------------------
        */

        .items-table {
            width: 100%;
            margin-top: 6px;
        }

        .items-table th {
            padding: 7px 6px;
            border: 1px solid #cbd5e1;
            background: #eef2f7;
            color: #475569;
            font-size: 8px;
            font-weight: bold;
            text-transform: uppercase;
        }

        .items-table td {
            padding: 7px 6px;
            border: 1px solid #dbe2ea;
            vertical-align: top;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        .barang-name {
            font-weight: bold;
            color: #1e293b;
        }

        .barang-code {
            margin-bottom: 2px;
            font-size: 7.5px;
            color: #64748b;
        }

        .barang-spec {
            margin-top: 2px;
            font-size: 7.5px;
            color: #64748b;
        }

        /*
        |--------------------------------------------------------------------------
        | SUMMARY
        |--------------------------------------------------------------------------
        */

        .summary-wrapper {
            width: 100%;
            margin-top: 14px;
        }

        .summary-table {
            width: 300px;
            margin-left: auto;
        }

        .summary-table td {
            padding: 4px 0;
        }

        .summary-label {
            color: #64748b;
        }

        .summary-value {
            text-align: right;
            font-weight: bold;
            color: #334155;
        }

        .discount-value {
            color: #b91c1c;
        }

        .total-row td {
            padding-top: 8px;
            border-top: 1px solid #94a3b8;
            font-size: 12px;
            font-weight: bold;
            color: #15235F;
        }

        /*
        |--------------------------------------------------------------------------
        | NOTES
        |--------------------------------------------------------------------------
        */

        .notes {
            margin-top: 22px;
            padding: 10px 11px;
            border: 1px solid #dbe2ea;
            background: #f8fafc;
        }

        .notes-title {
            margin-bottom: 4px;
            font-size: 9px;
            font-weight: bold;
            color: #475569;
        }

        .notes-content {
            color: #64748b;
        }

        /*
        |--------------------------------------------------------------------------
        | SIGNATURE
        |--------------------------------------------------------------------------
        */

        .signature-table {
            width: 100%;
            margin-top: 42px;
        }

        .signature-table td {
            width: 33.33%;
            text-align: center;
            vertical-align: top;
        }

        .signature-title {
            font-size: 9px;
            color: #475569;
        }

        .signature-space {
            height: 55px;
        }

        .signature-line {
            margin: 0 14px;
            padding-top: 5px;
            border-top: 1px solid #64748b;
            font-size: 9px;
            color: #334155;
        }

        /*
        |--------------------------------------------------------------------------
        | FOOTER
        |--------------------------------------------------------------------------
        */

        .footer {
            position: fixed;
            bottom: -14px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 7px;
            color: #94a3b8;
        }
    </style>

</head>

<body>

    @php

        /*
        |--------------------------------------------------------------------------
        | NILAI PAJAK
        |--------------------------------------------------------------------------
        */

        $pajakPersen =
            (float) (
                $purchaseOrder->pajak_persen
                ?? 0
            );


        $pajakPersenFormatted =
            rtrim(
                rtrim(
                    number_format(
                        $pajakPersen,
                        2,
                        '.',
                        ''
                    ),
                    '0'
                ),
                '.'
            );


        /*
        |--------------------------------------------------------------------------
        | DASAR PAJAK
        |--------------------------------------------------------------------------
        */

        $dasarPajak =
            max(
                0,
                (float) (
                    $purchaseOrder->subtotal
                    ?? 0
                )
                -
                (float) (
                    $purchaseOrder->diskon
                    ?? 0
                )
            );

    @endphp


    {{-- ========================================================= --}}
    {{-- HEADER --}}
    {{-- ========================================================= --}}

    <div class="header">

        <table class="header-table">

            <tr>

                {{-- BRAND --}}
                <td style="width: 55%;">

                    <table class="brand-table">

                        <tr>

                            <td class="brand-logo-cell">

                                <img
                                    src="{{ public_path('images/LogoAMBAJA.png') }}"
                                    alt="Logo AMBAJA"
                                    class="brand-logo"
                                >

                            </td>


                            <td>

                                <div class="company-name">
                                    AMBAJA
                                </div>

                                <div class="company-subtitle">
                                    Sistem Informasi Akuntansi CV Andika Mustika
                                </div>

                            </td>

                        </tr>

                    </table>

                </td>


                {{-- DOCUMENT --}}
                <td style="width: 45%;">

                    <div class="document-title">
                        PURCHASE ORDER
                    </div>

                    <div class="document-number">
                        {{ $purchaseOrder->nomor_po }}
                    </div>

                </td>

            </tr>

        </table>

    </div>


    {{-- ========================================================= --}}
    {{-- INFORMASI PURCHASE ORDER --}}
    {{-- ========================================================= --}}

    <div class="section-title">
        Informasi Purchase Order
    </div>


    <table class="info-table">

        <tr>

            <td class="label">
                Nomor PO
            </td>

            <td class="colon">
                :
            </td>

            <td class="value">
                {{ $purchaseOrder->nomor_po }}
            </td>


            <td class="label">
                Supplier
            </td>

            <td class="colon">
                :
            </td>

            <td class="value">
                {{ $purchaseOrder->supplier?->nama_supplier ?? '-' }}
            </td>

        </tr>


        <tr>

            <td class="label">
                Tanggal PO
            </td>

            <td class="colon">
                :
            </td>

            <td>

                {{ $purchaseOrder
                    ->tanggal_po
                    ?->format('d/m/Y')
                    ?? '-'
                }}

            </td>


            <td class="label">
                Kode Supplier
            </td>

            <td class="colon">
                :
            </td>

            <td>
                {{ $purchaseOrder->supplier?->kode_supplier ?? '-' }}
            </td>

        </tr>


        <tr>

            <td class="label">
                Estimasi Kedatangan
            </td>

            <td class="colon">
                :
            </td>

            <td>

                {{ $purchaseOrder
                    ->tanggal_estimasi_datang
                    ?->format('d/m/Y')
                    ?? '-'
                }}

            </td>


            <td class="label">
                Status
            </td>

            <td class="colon">
                :
            </td>

            <td>
                {{ $purchaseOrder->status }}
            </td>

        </tr>


        <tr>

            <td class="label">
                Dibuat Oleh
            </td>

            <td class="colon">
                :
            </td>

            <td>

                {{ $purchaseOrder
                    ->creator
                    ?->name
                    ?? '-'
                }}

            </td>


            <td></td>
            <td></td>
            <td></td>

        </tr>

    </table>


    {{-- ========================================================= --}}
    {{-- DATA SUPPLIER --}}
    {{-- ========================================================= --}}

    <div class="section-title">
        Informasi Supplier
    </div>


    <div class="supplier-box">

        <div class="supplier-name">
            {{ $purchaseOrder->supplier?->nama_supplier ?? '-' }}
        </div>


        <div class="supplier-detail">

            @if($purchaseOrder->supplier?->alamat)

                {{ $purchaseOrder->supplier->alamat }}

            @else

                Alamat supplier belum tersedia.

            @endif


            @if($purchaseOrder->supplier?->kota)

                , {{ $purchaseOrder->supplier->kota }}

            @endif

        </div>


        @if($purchaseOrder->supplier?->telepon)

            <div class="supplier-detail">
                Telp:
                {{ $purchaseOrder->supplier->telepon }}
            </div>

        @endif


        @if($purchaseOrder->supplier?->email)

            <div class="supplier-detail">
                Email:
                {{ $purchaseOrder->supplier->email }}
            </div>

        @endif

    </div>


    {{-- ========================================================= --}}
    {{-- DETAIL BARANG --}}
    {{-- ========================================================= --}}

    <div class="section-title">
        Detail Barang
    </div>


    <table class="items-table">

        <thead>

            <tr>

                <th style="width: 28px;">
                    No
                </th>

                <th>
                    Barang
                </th>

                <th style="width: 58px;">
                    Satuan
                </th>

                <th style="width: 52px;">
                    Qty
                </th>

                <th style="width: 90px;">
                    Harga
                </th>

                <th style="width: 70px;">
                    Diskon
                </th>

                <th style="width: 100px;">
                    Subtotal
                </th>

            </tr>

        </thead>


        <tbody>

            @forelse($purchaseOrder->items as $item)

                <tr>

                    {{-- NO --}}
                    <td class="text-center">
                        {{ $loop->iteration }}
                    </td>


                    {{-- BARANG --}}
                    <td>

                        @if($item->barang?->kode_barang)

                            <div class="barang-code">
                                {{ $item->barang->kode_barang }}
                            </div>

                        @endif


                        <div class="barang-name">
                            {{ $item->barang?->nama_barang ?? '-' }}
                        </div>


                        @if($item->barang?->spesifikasi)

                            <div class="barang-spec">
                                {{ $item->barang->spesifikasi }}
                            </div>

                        @endif


                        @if($item->keterangan)

                            <div class="barang-spec">
                                Ket:
                                {{ $item->keterangan }}
                            </div>

                        @endif

                    </td>


                    {{-- SATUAN --}}
                    <td class="text-center">

                        {{ $item
                            ->barang
                            ?->satuan
                            ?->nama_satuan
                            ?? '-'
                        }}

                    </td>


                    {{-- QTY --}}
                    <td class="text-right">

                        {{ number_format(
                            $item->qty,
                            2,
                            ',',
                            '.'
                        ) }}

                    </td>


                    {{-- HARGA --}}
                    <td class="text-right">

                        Rp {{ number_format(
                            $item->harga_satuan,
                            0,
                            ',',
                            '.'
                        ) }}

                    </td>


                    {{-- DISKON --}}
                    <td class="text-right">

                        Rp {{ number_format(
                            $item->diskon ?? 0,
                            0,
                            ',',
                            '.'
                        ) }}

                    </td>


                    {{-- SUBTOTAL --}}
                    <td class="text-right">

                        Rp {{ number_format(
                            $item->subtotal,
                            0,
                            ',',
                            '.'
                        ) }}

                    </td>

                </tr>


            @empty

                <tr>

                    <td
                        colspan="7"
                        class="text-center"
                        style="padding: 15px;"
                    >
                        Tidak ada barang pada Purchase Order.
                    </td>

                </tr>

            @endforelse

        </tbody>

    </table>


    {{-- ========================================================= --}}
    {{-- RINGKASAN NILAI --}}
    {{-- ========================================================= --}}

    <div class="summary-wrapper">

        <table class="summary-table">

            {{-- SUBTOTAL --}}
            <tr>

                <td class="summary-label">
                    Subtotal Barang
                </td>

                <td class="summary-value">

                    Rp {{ number_format(
                        $purchaseOrder->subtotal ?? 0,
                        0,
                        ',',
                        '.'
                    ) }}

                </td>

            </tr>


            {{-- DISKON --}}
            <tr>

                <td class="summary-label">
                    Diskon PO
                </td>

                <td class="summary-value discount-value">

                    - Rp {{ number_format(
                        $purchaseOrder->diskon ?? 0,
                        0,
                        ',',
                        '.'
                    ) }}

                </td>

            </tr>


            {{-- DASAR PAJAK --}}
            <tr>

                <td class="summary-label">
                    Dasar Pajak
                </td>

                <td class="summary-value">

                    Rp {{ number_format(
                        $dasarPajak,
                        0,
                        ',',
                        '.'
                    ) }}

                </td>

            </tr>


            {{-- PAJAK --}}
            <tr>

                <td class="summary-label">

                    Pajak

                    @if($pajakPersen > 0)
                        ({{ $pajakPersenFormatted }}%)
                    @endif

                </td>

                <td class="summary-value">

                    Rp {{ number_format(
                        $purchaseOrder->pajak ?? 0,
                        0,
                        ',',
                        '.'
                    ) }}

                </td>

            </tr>


            {{-- TOTAL --}}
            <tr class="total-row">

                <td>
                    TOTAL
                </td>

                <td class="text-right">

                    Rp {{ number_format(
                        $purchaseOrder->total ?? 0,
                        0,
                        ',',
                        '.'
                    ) }}

                </td>

            </tr>

        </table>

    </div>


    {{-- ========================================================= --}}
    {{-- CATATAN --}}
    {{-- ========================================================= --}}

    <div class="notes">

        <div class="notes-title">
            Catatan
        </div>

        <div class="notes-content">

            {{ $purchaseOrder->catatan
                ?: 'Tidak ada catatan.'
            }}

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- TANDA TANGAN --}}
    {{-- ========================================================= --}}

    <table class="signature-table">

        <tr>

            {{-- DIBUAT --}}
            <td>

                <div class="signature-title">
                    Dibuat Oleh
                </div>

                <div class="signature-space"></div>

                <div class="signature-line">

                    {{ $purchaseOrder
                        ->creator
                        ?->name
                        ?? '-'
                    }}

                </div>

            </td>


            {{-- DISETUJUI --}}
            <td>

                <div class="signature-title">
                    Disetujui
                </div>

                <div class="signature-space"></div>

                <div class="signature-line">
                    &nbsp;
                </div>

            </td>


            {{-- SUPPLIER --}}
            <td>

                <div class="signature-title">
                    Supplier
                </div>

                <div class="signature-space"></div>

                <div class="signature-line">

                    {{ $purchaseOrder
                        ->supplier
                        ?->nama_supplier
                        ?? '-'
                    }}

                </div>

            </td>

        </tr>

    </table>


    {{-- ========================================================= --}}
    {{-- FOOTER --}}
    {{-- ========================================================= --}}

    <div class="footer">
        Dokumen ini dihasilkan oleh Sistem Informasi Akuntansi AMBAJA — CV Andika Mustika.
    </div>

</body>

</html>