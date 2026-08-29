<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">

    <title>Laporan Utang</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #1e293b;
        }

        .header {
            margin-bottom: 16px;
            border-bottom: 2px solid #15235F;
            padding-bottom: 10px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header-table td {
            border: none;
            vertical-align: middle;
        }

        .logo {
            width: 80px;
        }

        .company-name {
            font-size: 16px;
            font-weight: bold;
            color: #15235F;
        }

        .report-title {
            margin-top: 3px;
            font-size: 13px;
            font-weight: bold;
            color: #334155;
        }

        .print-info {
            margin-top: 3px;
            font-size: 8px;
            color: #64748b;
        }

        .summary-table {
            width: 100%;
            margin-bottom: 15px;
            border-collapse: collapse;
        }

        .summary-table td {
            width: 25%;
            padding: 8px;
            border: 1px solid #cbd5e1;
            vertical-align: top;
        }

        .summary-label {
            margin-bottom: 3px;
            font-size: 8px;
            color: #64748b;
        }

        .summary-value {
            font-size: 11px;
            font-weight: bold;
            color: #0f172a;
        }

        .danger {
            color: #b91c1c;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
        }

        .report-table th {
            padding: 6px 4px;
            border: 1px solid #94a3b8;
            background: #e2e8f0;
            font-size: 7.5px;
            text-align: center;
            color: #334155;
        }

        .report-table td {
            padding: 5px 4px;
            border: 1px solid #cbd5e1;
            vertical-align: middle;
        }

        .text-left {
            text-align: left;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .font-bold {
            font-weight: bold;
        }

        .supplier-code {
            margin-top: 2px;
            font-size: 7px;
            color: #64748b;
        }

        .status {
            display: inline-block;
            padding: 2px 5px;
            border-radius: 8px;
            font-size: 7px;
            font-weight: bold;
        }

        .status-lunas {
            background: #dcfce7;
            color: #166534;
        }

        .status-sebagian {
            background: #fef3c7;
            color: #92400e;
        }

        .status-belum {
            background: #fee2e2;
            color: #991b1b;
        }

        .status-overdue {
            color: #b91c1c;
            font-weight: bold;
        }

        .status-today {
            color: #b45309;
            font-weight: bold;
        }

        .status-future {
            color: #0369a1;
        }

        .total-row td {
            background: #f1f5f9;
            font-weight: bold;
        }

        .footer {
            margin-top: 12px;
            font-size: 7px;
            color: #64748b;
        }

        .page-number:after {
            content: counter(page);
        }
    </style>
</head>

<body>

    {{-- HEADER --}}
    <div class="header">
        <table class="header-table">
            <tr>
                <td style="width: 95px;">
                    @php
                        $logoPath = public_path('images/LogoAMBAJA.png');
                    @endphp

                    @if (file_exists($logoPath))
                        <img
                            src="{{ $logoPath }}"
                            class="logo"
                            alt="Logo"
                        >
                    @endif
                </td>

                <td>
                    <div class="company-name">
                        CV ANDIKA MUSTIKA
                    </div>

                    <div class="report-title">
                        LAPORAN UTANG SUPPLIER
                    </div>

                    <div class="print-info">
                        Dicetak pada:
                        {{ now()->format('d/m/Y H:i') }}
                    </div>
                </td>
            </tr>
        </table>
    </div>


    {{-- SUMMARY --}}
    <table class="summary-table">
        <tr>
            <td>
                <div class="summary-label">
                    Total Invoice
                </div>

                <div class="summary-value">
                    Rp {{ number_format($summary['total_invoice'], 0, ',', '.') }}
                </div>

                <div class="summary-label" style="margin-top: 3px;">
                    {{ number_format($summary['jumlah_invoice'], 0, ',', '.') }} invoice
                </div>
            </td>

            <td>
                <div class="summary-label">
                    Sudah Dibayar
                </div>

                <div class="summary-value">
                    Rp {{ number_format($summary['total_dibayar'], 0, ',', '.') }}
                </div>
            </td>

            <td>
                <div class="summary-label">
                    Sisa Utang
                </div>

                <div class="summary-value">
                    Rp {{ number_format($summary['total_utang'], 0, ',', '.') }}
                </div>

                <div class="summary-label" style="margin-top: 3px;">
                    {{ number_format($summary['jumlah_belum_lunas'], 0, ',', '.') }}
                    invoice belum lunas
                </div>
            </td>

            <td>
                <div class="summary-label">
                    Utang Jatuh Tempo
                </div>

                <div class="summary-value danger">
                    Rp {{ number_format($summary['utang_jatuh_tempo'], 0, ',', '.') }}
                </div>
            </td>
        </tr>
    </table>


    {{-- TABLE --}}
    <table class="report-table">

        <thead>
            <tr>
                <th style="width: 22px;">
                    No
                </th>

                <th style="width: 78px;">
                    No Invoice
                </th>

                <th style="width: 78px;">
                    Invoice Supplier
                </th>

                <th>
                    Supplier
                </th>

                <th style="width: 70px;">
                    No PO
                </th>

                <th style="width: 52px;">
                    Tgl Invoice
                </th>

                <th style="width: 52px;">
                    Jatuh Tempo
                </th>

                <th style="width: 72px;">
                    Total
                </th>

                <th style="width: 72px;">
                    Dibayar
                </th>

                <th style="width: 72px;">
                    Sisa Utang
                </th>

                <th style="width: 58px;">
                    Status
                </th>

                <th style="width: 80px;">
                    Umur Utang
                </th>
            </tr>
        </thead>


        <tbody>

            @forelse ($invoices as $invoice)

                @php
                    $status = strtoupper((string) $invoice->status);

                    if ($status === 'LUNAS') {
                        $statusLabel = 'Lunas';
                        $statusClass = 'status-lunas';
                    } elseif ($status === 'SEBAGIAN') {
                        $statusLabel = 'Sebagian';
                        $statusClass = 'status-sebagian';
                    } else {
                        $statusLabel = 'Belum Lunas';
                        $statusClass = 'status-belum';
                    }

                    if ($invoice->status_jatuh_tempo === 'JATUH_TEMPO') {
                        $umurClass = 'status-overdue';
                    } elseif ($invoice->status_jatuh_tempo === 'HARI_INI') {
                        $umurClass = 'status-today';
                    } elseif ($invoice->status_jatuh_tempo === 'BELUM_JATUH_TEMPO') {
                        $umurClass = 'status-future';
                    } else {
                        $umurClass = '';
                    }
                @endphp

                <tr>
                    <td class="text-center">
                        {{ $loop->iteration }}
                    </td>

                    <td>
                        {{ $invoice->nomor_invoice_internal }}
                    </td>

                    <td>
                        {{ $invoice->nomor_invoice_supplier ?: '-' }}
                    </td>

                    <td>
                        <div class="font-bold">
                            {{ $invoice->supplier?->nama_supplier ?: '-' }}
                        </div>

                        @if ($invoice->supplier?->kode_supplier)
                            <div class="supplier-code">
                                {{ $invoice->supplier->kode_supplier }}
                            </div>
                        @endif
                    </td>

                    <td>
                        {{ $invoice->purchaseOrder?->nomor_po ?: '-' }}
                    </td>

                    <td class="text-center">
                        {{ $invoice->tanggal_invoice?->format('d/m/Y') ?: '-' }}
                    </td>

                    <td class="text-center">
                        {{ $invoice->tanggal_jatuh_tempo?->format('d/m/Y') ?: '-' }}
                    </td>

                    <td class="text-right">
                        {{ number_format((float) $invoice->total, 0, ',', '.') }}
                    </td>

                    <td class="text-right">
                        {{ number_format((float) $invoice->total_dibayar, 0, ',', '.') }}
                    </td>

                    <td class="text-right font-bold">
                        {{ number_format((float) $invoice->sisa_utang, 0, ',', '.') }}
                    </td>

                    <td class="text-center">
                        <span class="status {{ $statusClass }}">
                            {{ $statusLabel }}
                        </span>
                    </td>

                    <td class="text-center {{ $umurClass }}">
                        {{ $invoice->label_jatuh_tempo }}
                    </td>
                </tr>

            @empty

                <tr>
                    <td
                        colspan="12"
                        class="text-center"
                        style="padding: 20px;"
                    >
                        Tidak ada data utang.
                    </td>
                </tr>

            @endforelse


            @if ($invoices->count() > 0)
                <tr class="total-row">

                    <td
                        colspan="7"
                        class="text-right"
                    >
                        TOTAL
                    </td>

                    <td class="text-right">
                        {{ number_format($summary['total_invoice'], 0, ',', '.') }}
                    </td>

                    <td class="text-right">
                        {{ number_format($summary['total_dibayar'], 0, ',', '.') }}
                    </td>

                    <td class="text-right">
                        {{ number_format($summary['total_utang'], 0, ',', '.') }}
                    </td>

                    <td colspan="2"></td>
                </tr>
            @endif

        </tbody>

    </table>


    {{-- FOOTER --}}
    <div class="footer">
        Laporan Utang Supplier — CV Andika Mustika
    </div>

</body>
</html>