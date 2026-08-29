@extends('layouts.app')

@section('title', 'Laporan Pembelian')

@section('content')

<div class="space-y-6">

    {{-- HEADER --}}
    <div>
        <h2 class="text-xl font-semibold text-slate-800">
            Laporan Pembelian
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            Ringkasan transaksi pembelian berdasarkan Invoice Supplier.
        </p>
    </div>


    {{-- FILTER --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

        <form
            method="GET"
            action="{{ route('laporan.pembelian') }}"
            class="space-y-4"
        >

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">

                {{-- TANGGAL MULAI --}}
                <div>
                    <label class="mb-2 block text-xs font-medium text-slate-600">
                        Tanggal Mulai
                    </label>

                    <input
                        type="date"
                        name="tanggal_mulai"
                        value="{{ request('tanggal_mulai') }}"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >
                </div>


                {{-- TANGGAL SELESAI --}}
                <div>
                    <label class="mb-2 block text-xs font-medium text-slate-600">
                        Tanggal Selesai
                    </label>

                    <input
                        type="date"
                        name="tanggal_selesai"
                        value="{{ request('tanggal_selesai') }}"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >
                </div>


                {{-- SUPPLIER --}}
                <div>
                    <label class="mb-2 block text-xs font-medium text-slate-600">
                        Supplier
                    </label>

                    <select
                        name="supplier_id"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >
                        <option value="">
                            Semua Supplier
                        </option>

                        @foreach($suppliers as $supplier)

                            <option
                                value="{{ $supplier->id }}"
                                {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}
                            >
                                {{ $supplier->nama_supplier }}
                            </option>

                        @endforeach

                    </select>
                </div>


                {{-- STATUS --}}
                <div>
                    <label class="mb-2 block text-xs font-medium text-slate-600">
                        Status Pembayaran
                    </label>

                    <select
                        name="status"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >

                        <option value="">
                            Semua Status
                        </option>

                        <option
                            value="BELUM_LUNAS"
                            {{ request('status') === 'BELUM_LUNAS' ? 'selected' : '' }}
                        >
                            Belum Lunas
                        </option>

                        <option
                            value="SEBAGIAN"
                            {{ request('status') === 'SEBAGIAN' ? 'selected' : '' }}
                        >
                            Sebagian
                        </option>

                        <option
                            value="LUNAS"
                            {{ request('status') === 'LUNAS' ? 'selected' : '' }}
                        >
                            Lunas
                        </option>

                    </select>
                </div>


                {{-- SEARCH --}}
                <div>
                    <label class="mb-2 block text-xs font-medium text-slate-600">
                        Pencarian
                    </label>

                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Invoice / PO / Supplier"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >
                </div>

            </div>


            {{-- BUTTON FILTER --}}
            <div class="flex justify-end gap-2">

                <a
                    href="{{ route('laporan.pembelian') }}"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
                >
                    Reset
                </a>

                <button
                    type="submit"
                    class="rounded-lg bg-[#15235F] px-5 py-2.5 text-sm font-medium text-white transition hover:bg-[#101b4d]"
                >
                    Terapkan Filter
                </button>

            </div>

        </form>

    </div>


    {{-- SUMMARY --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

        {{-- JUMLAH INVOICE --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

            <p class="text-xs uppercase tracking-wide text-slate-400">
                Jumlah Invoice
            </p>

            <p class="mt-2 text-2xl font-bold text-[#15235F]">
                {{ number_format($jumlahInvoice, 0, ',', '.') }}
            </p>

            <p class="mt-1 text-xs text-slate-400">
                Invoice supplier
            </p>

        </div>


        {{-- TOTAL PEMBELIAN --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

            <p class="text-xs uppercase tracking-wide text-slate-400">
                Total Pembelian
            </p>

            <p class="mt-2 text-xl font-bold text-[#15235F]">
                Rp {{ number_format($totalPembelian, 0, ',', '.') }}
            </p>

            <p class="mt-1 text-xs text-slate-400">
                Nilai seluruh invoice
            </p>

        </div>


        {{-- TOTAL DIBAYAR --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

            <p class="text-xs uppercase tracking-wide text-slate-400">
                Total Dibayar
            </p>

            <p class="mt-2 text-xl font-bold text-emerald-600">
                Rp {{ number_format($totalDibayar, 0, ',', '.') }}
            </p>

            <p class="mt-1 text-xs text-slate-400">
                Pembayaran supplier
            </p>

        </div>


        {{-- SISA UTANG --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

            <p class="text-xs uppercase tracking-wide text-slate-400">
                Sisa Utang
            </p>

            <p class="mt-2 text-xl font-bold text-red-500">
                Rp {{ number_format($totalSisaUtang, 0, ',', '.') }}
            </p>

            <p class="mt-1 text-xs text-slate-400">
                Outstanding supplier
            </p>

        </div>

    </div>


    {{-- TABLE --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        {{-- HEADER TABLE + EXPORT --}}
        <div class="border-b border-slate-200 px-6 py-4">

            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

                <div>

                    <h3 class="text-sm font-semibold text-slate-700">
                        Detail Pembelian
                    </h3>

                    <p class="mt-1 text-xs text-slate-400">
                        {{ $jumlahInvoice }} invoice sesuai filter.
                    </p>

                </div>


                {{-- EXPORT BUTTON --}}
                <div class="flex flex-wrap items-center gap-2">

                    {{-- PDF --}}
                    <a
                        href="{{ route('laporan.pembelian.pdf', request()->query()) }}"
                        target="_blank"
                        class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-medium text-red-600 transition hover:border-red-300 hover:bg-red-100"
                    >

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.8"
                            stroke="currentColor"
                            class="h-4 w-4"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5A3.375 3.375 0 0 0 10.125 2.25H8.25m0 12.75h7.5m-7.5 3h7.5M10.5 2.25H5.625A1.875 1.875 0 0 0 3.75 4.125v15.75c0 1.036.84 1.875 1.875 1.875h12.75a1.875 1.875 0 0 0 1.875-1.875V12.375a10.125 10.125 0 0 0-9.75-10.125Z"
                            />
                        </svg>

                        Cetak PDF

                    </a>


                    {{-- EXCEL --}}
                    <a
                        href="{{ route('laporan.pembelian.excel', request()->query()) }}"
                        class="inline-flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-medium text-emerald-700 transition hover:border-emerald-300 hover:bg-emerald-100"
                    >

                        <svg
                            xmlns="http://www.w3.org/2000/svg"
                            fill="none"
                            viewBox="0 0 24 24"
                            stroke-width="1.8"
                            stroke="currentColor"
                            class="h-4 w-4"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M7.5 10.5 12 15m0 0 4.5-4.5M12 15V3"
                            />
                        </svg>

                        Export Excel

                    </a>

                </div>

            </div>

        </div>


        {{-- TABLE CONTENT --}}
        <div class="overflow-x-auto">

            <table class="w-full min-w-[1450px] text-sm">

                <thead class="bg-slate-50">

                    <tr class="text-left text-xs uppercase tracking-wide text-slate-400">

                        <th class="px-5 py-3">
                            Tanggal
                        </th>

                        <th class="px-5 py-3">
                            No. Invoice
                        </th>

                        <th class="px-5 py-3">
                            Invoice Supplier
                        </th>

                        <th class="px-5 py-3">
                            Supplier
                        </th>

                        <th class="px-5 py-3">
                            No. PO
                        </th>

                        <th class="px-5 py-3 text-right">
                            Subtotal
                        </th>

                        <th class="px-5 py-3 text-right">
                            Diskon
                        </th>

                        <th class="px-5 py-3 text-right">
                            Pajak
                        </th>

                        <th class="px-5 py-3 text-right">
                            Total
                        </th>

                        <th class="px-5 py-3 text-right">
                            Dibayar
                        </th>

                        <th class="px-5 py-3 text-right">
                            Sisa
                        </th>

                        <th class="px-5 py-3 text-center">
                            Status
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-slate-100">

                    @forelse($invoices as $invoice)

                        @php

                            $statusClass = match($invoice->status) {

                                'LUNAS' =>
                                    'bg-emerald-50 text-emerald-700',

                                'SEBAGIAN' =>
                                    'bg-amber-50 text-amber-700',

                                'BELUM_LUNAS' =>
                                    'bg-red-50 text-red-600',

                                default =>
                                    'bg-slate-100 text-slate-600',
                            };


                            $statusLabel = match($invoice->status) {

                                'LUNAS' =>
                                    'Lunas',

                                'SEBAGIAN' =>
                                    'Sebagian',

                                'BELUM_LUNAS' =>
                                    'Belum Lunas',

                                default =>
                                    $invoice->status,
                            };

                        @endphp


                        <tr class="transition hover:bg-slate-50/70">

                            {{-- TANGGAL --}}
                            <td class="whitespace-nowrap px-5 py-4 text-slate-500">

                                {{ $invoice->tanggal_invoice?->format('d/m/Y') ?? '-' }}

                            </td>


                            {{-- INTERNAL --}}
                            <td class="px-5 py-4">

                                <a
                                    href="{{ route('invoice-supplier.show', $invoice) }}"
                                    class="font-semibold text-[#15235F] transition hover:text-[#32AAA6]"
                                >
                                    {{ $invoice->nomor_invoice_internal }}
                                </a>

                            </td>


                            {{-- NOMOR INVOICE SUPPLIER --}}
                            <td class="px-5 py-4 text-slate-600">

                                {{ $invoice->nomor_invoice_supplier ?: '-' }}

                            </td>


                            {{-- SUPPLIER --}}
                            <td class="px-5 py-4">

                                <p class="font-medium text-slate-700">
                                    {{ $invoice->supplier?->nama_supplier ?? '-' }}
                                </p>

                                @if($invoice->supplier?->kode_supplier)

                                    <p class="mt-1 text-xs text-slate-400">
                                        {{ $invoice->supplier->kode_supplier }}
                                    </p>

                                @endif

                            </td>


                            {{-- PURCHASE ORDER --}}
                            <td class="px-5 py-4">

                                @if($invoice->purchaseOrder)

                                    <a
                                        href="{{ route('purchase-order.show', $invoice->purchaseOrder) }}"
                                        class="font-medium text-[#15235F] transition hover:text-[#32AAA6]"
                                    >
                                        {{ $invoice->purchaseOrder->nomor_po }}
                                    </a>

                                @else

                                    <span class="text-slate-400">
                                        -
                                    </span>

                                @endif

                            </td>


                            {{-- SUBTOTAL --}}
                            <td class="whitespace-nowrap px-5 py-4 text-right text-slate-600">

                                Rp {{ number_format(
                                    $invoice->subtotal,
                                    0,
                                    ',',
                                    '.'
                                ) }}

                            </td>


                            {{-- DISKON --}}
                            <td class="whitespace-nowrap px-5 py-4 text-right text-slate-500">

                                Rp {{ number_format(
                                    $invoice->diskon,
                                    0,
                                    ',',
                                    '.'
                                ) }}

                            </td>


                            {{-- PAJAK --}}
                            <td class="whitespace-nowrap px-5 py-4 text-right text-slate-500">

                                Rp {{ number_format(
                                    $invoice->pajak,
                                    0,
                                    ',',
                                    '.'
                                ) }}

                                @if((float) $invoice->pajak_persen > 0)

                                    <p class="mt-1 text-[10px] text-slate-400">

                                        {{ number_format(
                                            $invoice->pajak_persen,
                                            2,
                                            ',',
                                            '.'
                                        ) }}%

                                    </p>

                                @endif

                            </td>


                            {{-- TOTAL --}}
                            <td class="whitespace-nowrap px-5 py-4 text-right font-semibold text-slate-700">

                                Rp {{ number_format(
                                    $invoice->total,
                                    0,
                                    ',',
                                    '.'
                                ) }}

                            </td>


                            {{-- TOTAL DIBAYAR --}}
                            <td class="whitespace-nowrap px-5 py-4 text-right font-medium text-emerald-600">

                                Rp {{ number_format(
                                    $invoice->total_dibayar,
                                    0,
                                    ',',
                                    '.'
                                ) }}

                            </td>


                            {{-- SISA UTANG --}}
                            <td
                                class="whitespace-nowrap px-5 py-4 text-right font-semibold
                                {{ (float) $invoice->sisa_utang > 0
                                    ? 'text-red-500'
                                    : 'text-slate-400'
                                }}"
                            >

                                Rp {{ number_format(
                                    $invoice->sisa_utang,
                                    0,
                                    ',',
                                    '.'
                                ) }}

                            </td>


                            {{-- STATUS --}}
                            <td class="px-5 py-4 text-center">

                                <span
                                    class="inline-flex whitespace-nowrap rounded-full px-2.5 py-1 text-xs font-medium {{ $statusClass }}"
                                >
                                    {{ $statusLabel }}
                                </span>

                            </td>

                        </tr>


                    @empty

                        <tr>

                            <td
                                colspan="12"
                                class="px-6 py-14 text-center"
                            >

                                <p class="text-sm font-medium text-slate-500">
                                    Tidak ada data pembelian.
                                </p>

                                <p class="mt-1 text-xs text-slate-400">
                                    Coba ubah periode atau filter laporan.
                                </p>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- PAGINATION --}}
        @if($invoices->hasPages())

            <div class="border-t border-slate-200 px-6 py-4">

                {{ $invoices->links() }}

            </div>

        @endif

    </div>

</div>

@endsection