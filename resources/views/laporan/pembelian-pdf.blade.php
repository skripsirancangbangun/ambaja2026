<!DOCTYPE html>
<html lang="id">

<head>

    <meta charset="UTF-8">

    <title>Laporan Pembelian</title>

    <style>

        @page {
            margin: 25px 30px;
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

        .header p {
            margin-top: 5px;
            color: #64748b;
        }

        .summary {
            width: 100%;
            margin-bottom: 15px;
            border-spacing: 8px 0;
        }

        .summary td {
            border: 1px solid #cbd5e1;
            padding: 8px;
        }

        .summary-label {
            font-size: 8px;
            color: #64748b;
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
            padding: 6px 4px;
            border: 1px solid #cbd5e1;
            font-size: 8px;
        }

        table.data td {
            padding: 5px 4px;
            border: 1px solid #cbd5e1;
            vertical-align: top;
        }

        .right {
            text-align: right;
        }

        .center {
            text-align: center;
        }

        .total-row {
            font-weight: bold;
            background: #f1f5f9;
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
            LAPORAN PEMBELIAN
        </h1>

        <p>
            CV Andika Mustika
        </p>

    </div>


    {{-- SUMMARY --}}
    <table class="summary">

        <tr>

            <td width="25%">

                <div class="summary-label">
                    JUMLAH INVOICE
                </div>

                <div class="summary-value">
                    {{ number_format($jumlahInvoice, 0, ',', '.') }}
                </div>

            </td>


            <td width="25%">

                <div class="summary-label">
                    TOTAL PEMBELIAN
                </div>

                <div class="summary-value">
                    Rp {{ number_format($totalPembelian, 0, ',', '.') }}
                </div>

            </td>


            <td width="25%">

                <div class="summary-label">
                    TOTAL DIBAYAR
                </div>

                <div class="summary-value">
                    Rp {{ number_format($totalDibayar, 0, ',', '.') }}
                </div>

            </td>


            <td width="25%">

                <div class="summary-label">
                    SISA UTANG
                </div>

                <div class="summary-value">
                    Rp {{ number_format($totalSisaUtang, 0, ',', '.') }}
                </div>

            </td>

        </tr>

    </table>


    {{-- DATA --}}
    <table class="data">

        <thead>

            <tr>

                <th>
                    Tanggal
                </th>

                <th>
                    No. Invoice
                </th>

                <th>
                    Invoice Supplier
                </th>

                <th>
                    Supplier
                </th>

                <th>
                    No. PO
                </th>

                <th>
                    Subtotal
                </th>

                <th>
                    Diskon
                </th>

                <th>
                    Pajak
                </th>

                <th>
                    Total
                </th>

                <th>
                    Dibayar
                </th>

                <th>
                    Sisa
                </th>

                <th>
                    Status
                </th>

            </tr>

        </thead>


        <tbody>

            @forelse($invoices as $invoice)

                <tr>

                    <td class="center">
                        {{ $invoice->tanggal_invoice?->format('d/m/Y') ?? '-' }}
                    </td>

                    <td>
                        {{ $invoice->nomor_invoice_internal }}
                    </td>

                    <td>
                        {{ $invoice->nomor_invoice_supplier ?: '-' }}
                    </td>

                    <td>
                        {{ $invoice->supplier?->nama_supplier ?? '-' }}
                    </td>

                    <td>
                        {{ $invoice->purchaseOrder?->nomor_po ?? '-' }}
                    </td>

                    <td class="right">
                        {{ number_format($invoice->subtotal, 0, ',', '.') }}
                    </td>

                    <td class="right">
                        {{ number_format($invoice->diskon, 0, ',', '.') }}
                    </td>

                    <td class="right">
                        {{ number_format($invoice->pajak, 0, ',', '.') }}
                    </td>

                    <td class="right">
                        {{ number_format($invoice->total, 0, ',', '.') }}
                    </td>

                    <td class="right">
                        {{ number_format($invoice->total_dibayar, 0, ',', '.') }}
                    </td>

                    <td class="right">
                        {{ number_format($invoice->sisa_utang, 0, ',', '.') }}
                    </td>

                    <td class="center">

                        @switch($invoice->status)

                            @case('BELUM_LUNAS')
                                Belum Lunas
                                @break

                            @case('SEBAGIAN')
                                Sebagian
                                @break

                            @case('LUNAS')
                                Lunas
                                @break

                            @default
                                {{ $invoice->status }}

                        @endswitch

                    </td>

                </tr>


            @empty

                <tr>

                    <td
                        colspan="12"
                        class="center"
                    >
                        Tidak ada data pembelian.
                    </td>

                </tr>

            @endforelse


            @if($invoices->count() > 0)

                <tr class="total-row">

                    <td colspan="5" class="right">
                        TOTAL
                    </td>

                    <td class="right">
                        {{ number_format($invoices->sum('subtotal'), 0, ',', '.') }}
                    </td>

                    <td class="right">
                        {{ number_format($invoices->sum('diskon'), 0, ',', '.') }}
                    </td>

                    <td class="right">
                        {{ number_format($invoices->sum('pajak'), 0, ',', '.') }}
                    </td>

                    <td class="right">
                        {{ number_format($invoices->sum('total'), 0, ',', '.') }}
                    </td>

                    <td class="right">
                        {{ number_format($invoices->sum('total_dibayar'), 0, ',', '.') }}
                    </td>

                    <td class="right">
                        {{ number_format($invoices->sum('sisa_utang'), 0, ',', '.') }}
                    </td>

                    <td></td>

                </tr>

            @endif

        </tbody>

    </table>


    <div class="footer">
        Dicetak pada {{ now()->format('d/m/Y H:i') }}
    </div>


</body>

</html>