@extends('layouts.app')

@section('title', 'Detail Purchase Order')

@section('content')

@php
    /*
    |--------------------------------------------------------------------------
    | STATUS PO
    |--------------------------------------------------------------------------
    */

    $statusClass = match($purchaseOrder->status) {
        'Draft' =>
            'bg-slate-100 text-slate-600',

        'Dipesan' =>
            'bg-blue-50 text-blue-700',

        'Sebagian Diterima' =>
            'bg-amber-50 text-amber-700',

        'Selesai' =>
            'bg-emerald-50 text-emerald-700',

        'Dibatalkan' =>
            'bg-red-50 text-red-600',

        default =>
            'bg-slate-100 text-slate-600',
    };


    /*
    |--------------------------------------------------------------------------
    | PAJAK
    |--------------------------------------------------------------------------
    */

    $pajakPersen =
        (float) ($purchaseOrder->pajak_persen ?? 0);

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
    | PEMBAYARAN
    |--------------------------------------------------------------------------
    */

    $totalPO =
        (float) $purchaseOrder->total;

    $totalDibayar =
        (float) $purchaseOrder->total_dibayar_po;

    $sisaPembayaran =
        (float) $purchaseOrder->sisa_pembayaran_po;

    $persentasePembayaran =
        (float) $purchaseOrder->persentase_pembayaran;

    $statusPembayaran =
        $purchaseOrder->status_pembayaran;

    $statusPembayaranClass =
        match($statusPembayaran) {

            'LUNAS' =>
                'bg-emerald-100 text-emerald-700',

            'SEBAGIAN' =>
                'bg-amber-100 text-amber-700',

            default =>
                'bg-slate-100 text-slate-600',
        };


    /*
    |--------------------------------------------------------------------------
    | PENERIMAAN
    |--------------------------------------------------------------------------
    */

    $qtyPesan =
        (float) $purchaseOrder->total_qty_pesan;

    $qtyDiterima =
        (float) $purchaseOrder->total_qty_diterima;

    $qtySisa =
        (float) $purchaseOrder->total_qty_sisa;

    $persentasePenerimaan =
        (float) $purchaseOrder->persentase_penerimaan;

    $statusPenerimaan =
        $purchaseOrder->status_penerimaan;

    $statusPenerimaanClass =
        match($statusPenerimaan) {

            'LENGKAP' =>
                'bg-emerald-100 text-emerald-700',

            'SEBAGIAN' =>
                'bg-amber-100 text-amber-700',

            'DIBATALKAN' =>
                'bg-red-100 text-red-700',

            default =>
                'bg-slate-100 text-slate-600',
        };
@endphp


<div class="mx-auto max-w-6xl space-y-6">

    {{-- ========================================================= --}}
    {{-- HEADER --}}
    {{-- ========================================================= --}}

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>

            <a
                href="{{ route('purchase-order.index') }}"
                class="mb-3 inline-flex text-sm text-slate-500 transition hover:text-[#15235F]"
            >
                ← Kembali
            </a>

            <h2 class="text-xl font-semibold text-slate-800">
                {{ $purchaseOrder->nomor_po }}
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Detail Purchase Order
            </p>

        </div>


        {{-- ACTION --}}
        <div class="flex flex-wrap items-center gap-2">

            {{-- CETAK --}}
            <a
                href="{{ route('purchase-order.pdf', $purchaseOrder) }}"
                target="_blank"
                class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
            >
                Cetak PDF
            </a>


            {{-- BAYAR SUPPLIER --}}
            @if($purchaseOrder->bisa_dibayar_po)

                <a
                    href="{{ route('pembayaran-po-supplier.create', $purchaseOrder) }}"
                    class="inline-flex h-10 items-center justify-center rounded-lg bg-emerald-600 px-4 text-sm font-semibold text-white transition hover:bg-emerald-700"
                >
                    Bayar Supplier
                </a>

            @endif


            {{-- DRAFT ACTION --}}
            @if($purchaseOrder->status === 'Draft')

                <a
                    href="{{ route('purchase-order.edit', $purchaseOrder) }}"
                    class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                >
                    Edit
                </a>

                <form
                    action="{{ route('purchase-order.kirim', $purchaseOrder) }}"
                    method="POST"
                    class="m-0"
                    onsubmit="return confirm('Kirim Purchase Order ini?')"
                >
                    @csrf
                    @method('PATCH')

                    <button
                        type="submit"
                        class="inline-flex h-10 items-center justify-center rounded-lg bg-[#15235F] px-4 text-sm font-medium text-white transition hover:bg-[#101b4d]"
                    >
                        Kirim PO
                    </button>

                </form>

            @endif

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- ALERT --}}
    {{-- ========================================================= --}}

    @if(session('success'))

        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>

    @endif


    @if(session('error'))

        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>

    @endif


    {{-- ========================================================= --}}
    {{-- INFORMASI PURCHASE ORDER --}}
    {{-- ========================================================= --}}

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-100 px-6 py-5">

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                <div>

                    <h3 class="text-sm font-semibold text-slate-800">
                        Informasi Purchase Order
                    </h3>

                    <p class="mt-1 text-xs text-slate-400">
                        Informasi utama dokumen pemesanan pembelian.
                    </p>

                </div>

                <span
                    class="inline-flex w-fit rounded-full px-3 py-1.5 text-xs font-semibold {{ $statusClass }}"
                >
                    {{ $purchaseOrder->status }}
                </span>

            </div>

        </div>


        <div class="px-6 py-6">

            <div class="grid grid-cols-1 gap-x-10 gap-y-6 md:grid-cols-2 xl:grid-cols-4">

                <div>

                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">
                        Nomor PO
                    </p>

                    <p class="mt-1.5 text-sm font-semibold text-[#15235F]">
                        {{ $purchaseOrder->nomor_po }}
                    </p>

                </div>


                <div>

                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">
                        Tanggal PO
                    </p>

                    <p class="mt-1.5 text-sm font-medium text-slate-700">
                        {{ $purchaseOrder->tanggal_po?->format('d/m/Y') ?? '-' }}
                    </p>

                </div>


                <div>

                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">
                        Supplier
                    </p>

                    <p class="mt-1.5 text-sm font-semibold text-slate-700">
                        {{ $purchaseOrder->supplier?->nama_supplier ?? '-' }}
                    </p>

                    @if($purchaseOrder->supplier?->kode_supplier)

                        <p class="mt-1 text-xs text-slate-400">
                            {{ $purchaseOrder->supplier->kode_supplier }}
                        </p>

                    @endif

                </div>


                <div>

                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">
                        Estimasi Kedatangan
                    </p>

                    <p class="mt-1.5 text-sm font-medium text-slate-700">
                        {{ $purchaseOrder->tanggal_estimasi_datang?->format('d/m/Y') ?? '-' }}
                    </p>

                </div>


                <div>

                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">
                        Dibuat Oleh
                    </p>

                    <p class="mt-1.5 text-sm font-medium text-slate-700">
                        {{ $purchaseOrder->creator?->name ?? '-' }}
                    </p>

                </div>

            </div>


            <div class="mt-7 border-t border-slate-100 pt-5">

                <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">
                    Catatan
                </p>

                @if($purchaseOrder->catatan)

                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        {{ $purchaseOrder->catatan }}
                    </p>

                @else

                    <p class="mt-2 text-sm italic text-slate-400">
                        Tidak ada catatan.
                    </p>

                @endif

            </div>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- PROGRESS PEMBAYARAN & PENERIMAAN --}}
    {{-- ========================================================= --}}

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">


        {{-- ===================================================== --}}
        {{-- PEMBAYARAN --}}
        {{-- ===================================================== --}}

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <div class="flex items-start justify-between gap-4">

                <div>

                    <h3 class="text-sm font-semibold text-slate-800">
                        Pembayaran Supplier
                    </h3>

                    <p class="mt-1 text-xs text-slate-400">
                        Progress pembayaran berdasarkan nilai Purchase Order.
                    </p>

                </div>

                <span
                    class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusPembayaranClass }}"
                >
                    {{ $statusPembayaran }}
                </span>

            </div>


            <div class="mt-6">

                <div class="mb-2 flex items-center justify-between text-xs">

                    <span class="text-slate-500">
                        Progress Pembayaran
                    </span>

                    <span class="font-semibold text-slate-700">
                        {{ number_format(
                            $persentasePembayaran,
                            2,
                            ',',
                            '.'
                        ) }}%
                    </span>

                </div>


                <div class="h-2.5 overflow-hidden rounded-full bg-slate-100">

                    <div
                        class="h-full rounded-full bg-emerald-500 transition-all"
                        style="width: {{ min(100, $persentasePembayaran) }}%"
                    ></div>

                </div>

            </div>


            <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">

                <div class="rounded-xl bg-slate-50 p-4">

                    <p class="text-xs text-slate-400">
                        Total PO
                    </p>

                    <p class="mt-1 text-sm font-semibold text-slate-700">
                        Rp {{ number_format(
                            $totalPO,
                            0,
                            ',',
                            '.'
                        ) }}
                    </p>

                </div>


                <div class="rounded-xl bg-emerald-50 p-4">

                    <p class="text-xs text-emerald-600">
                        Sudah Dibayar
                    </p>

                    <p class="mt-1 text-sm font-semibold text-emerald-700">
                        Rp {{ number_format(
                            $totalDibayar,
                            0,
                            ',',
                            '.'
                        ) }}
                    </p>

                </div>


                <div class="rounded-xl bg-amber-50 p-4">

                    <p class="text-xs text-amber-600">
                        Sisa Pembayaran
                    </p>

                    <p class="mt-1 text-sm font-semibold text-amber-700">
                        Rp {{ number_format(
                            $sisaPembayaran,
                            0,
                            ',',
                            '.'
                        ) }}
                    </p>

                </div>

            </div>


            @if($purchaseOrder->bisa_dibayar_po)

                <div class="mt-5">

                    <a
                        href="{{ route('pembayaran-po-supplier.create', $purchaseOrder) }}"
                        class="inline-flex h-10 w-full items-center justify-center rounded-lg bg-emerald-600 px-4 text-sm font-semibold text-white transition hover:bg-emerald-700"
                    >
                        Bayar Supplier
                    </a>

                </div>

            @elseif($statusPembayaran === 'LUNAS')

                <div class="mt-5 rounded-xl border border-emerald-100 bg-emerald-50 px-4 py-3">

                    <p class="text-xs font-medium text-emerald-700">
                        Purchase Order ini sudah dibayar lunas.
                    </p>

                </div>

            @elseif($purchaseOrder->status === 'Draft')

                <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">

                    <p class="text-xs text-slate-500">
                        PO harus dikirim terlebih dahulu sebelum dapat dibayar.
                    </p>

                </div>

            @endif

        </div>


        {{-- ===================================================== --}}
        {{-- PENERIMAAN --}}
        {{-- ===================================================== --}}

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <div class="flex items-start justify-between gap-4">

                <div>

                    <h3 class="text-sm font-semibold text-slate-800">
                        Penerimaan Barang
                    </h3>

                    <p class="mt-1 text-xs text-slate-400">
                        Progress barang yang sudah diterima dari supplier.
                    </p>

                </div>

                <span
                    class="rounded-full px-3 py-1 text-xs font-semibold {{ $statusPenerimaanClass }}"
                >
                    {{ $statusPenerimaan }}
                </span>

            </div>


            <div class="mt-6">

                <div class="mb-2 flex items-center justify-between text-xs">

                    <span class="text-slate-500">
                        Progress Penerimaan
                    </span>

                    <span class="font-semibold text-slate-700">
                        {{ number_format(
                            $persentasePenerimaan,
                            2,
                            ',',
                            '.'
                        ) }}%
                    </span>

                </div>


                <div class="h-2.5 overflow-hidden rounded-full bg-slate-100">

                    <div
                        class="h-full rounded-full bg-[#15235F] transition-all"
                        style="width: {{ min(100, $persentasePenerimaan) }}%"
                    ></div>

                </div>

            </div>


            <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-3">

                <div class="rounded-xl bg-slate-50 p-4">

                    <p class="text-xs text-slate-400">
                        Qty Dipesan
                    </p>

                    <p class="mt-1 text-lg font-semibold text-slate-700">
                        {{ number_format(
                            $qtyPesan,
                            2,
                            ',',
                            '.'
                        ) }}
                    </p>

                </div>


                <div class="rounded-xl bg-blue-50 p-4">

                    <p class="text-xs text-blue-600">
                        Qty Diterima
                    </p>

                    <p class="mt-1 text-lg font-semibold text-blue-700">
                        {{ number_format(
                            $qtyDiterima,
                            2,
                            ',',
                            '.'
                        ) }}
                    </p>

                </div>


                <div class="rounded-xl bg-amber-50 p-4">

                    <p class="text-xs text-amber-600">
                        Sisa
                    </p>

                    <p class="mt-1 text-lg font-semibold text-amber-700">
                        {{ number_format(
                            $qtySisa,
                            2,
                            ',',
                            '.'
                        ) }}
                    </p>

                </div>

            </div>


            <div class="mt-5 rounded-xl border border-blue-100 bg-blue-50 px-4 py-3">

                <p class="text-xs leading-5 text-blue-700">
                    Status penerimaan barang tidak bergantung pada status pembayaran.
                    PO dapat sudah lunas walaupun barang masih diterima sebagian.
                </p>

            </div>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- DETAIL BARANG --}}
    {{-- ========================================================= --}}

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-200 px-6 py-4">

            <h3 class="text-sm font-semibold text-slate-700">
                Detail Barang
            </h3>

            <p class="mt-1 text-xs text-slate-400">
                Daftar barang yang dipesan melalui Purchase Order.
            </p>

        </div>


        <div class="overflow-x-auto">

            <table class="w-full min-w-[1050px] text-sm">

                <thead class="bg-slate-50">

                    <tr class="text-left text-xs uppercase tracking-wide text-slate-400">

                        <th class="px-5 py-3 font-medium">
                            No
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Barang
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Satuan
                        </th>

                        <th class="px-5 py-3 text-right font-medium">
                            Qty
                        </th>

                        <th class="px-5 py-3 text-right font-medium">
                            Diterima
                        </th>

                        <th class="px-5 py-3 text-right font-medium">
                            Sisa
                        </th>

                        <th class="px-5 py-3 text-right font-medium">
                            Harga
                        </th>

                        <th class="px-5 py-3 text-right font-medium">
                            Diskon
                        </th>

                        <th class="px-5 py-3 text-right font-medium">
                            Subtotal
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-slate-100">

                    @forelse($purchaseOrder->items as $item)

                        <tr class="transition hover:bg-slate-50/70">

                            <td class="px-5 py-4 text-slate-500">
                                {{ $loop->iteration }}
                            </td>


                            <td class="px-5 py-4">

                                <p class="font-semibold text-slate-700">

                                    {{ $item->barang?->kode_barang ?? '-' }}
                                    -
                                    {{ $item->barang?->nama_barang ?? '-' }}

                                </p>

                                @if($item->barang?->spesifikasi)

                                    <p class="mt-1 text-xs text-slate-400">
                                        {{ $item->barang->spesifikasi }}
                                    </p>

                                @endif

                            </td>


                            <td class="px-5 py-4 text-slate-500">

                                {{ $item
                                    ->barang
                                    ?->satuan
                                    ?->nama_satuan
                                    ?? '-'
                                }}

                            </td>


                            <td class="px-5 py-4 text-right font-medium text-slate-700">

                                {{ number_format(
                                    $item->qty,
                                    2,
                                    ',',
                                    '.'
                                ) }}

                            </td>


                            <td class="px-5 py-4 text-right">

                                <span
                                    class="{{ (float) $item->qty_diterima >= (float) $item->qty
                                        ? 'font-semibold text-emerald-600'
                                        : 'text-slate-600'
                                    }}"
                                >

                                    {{ number_format(
                                        $item->qty_diterima,
                                        2,
                                        ',',
                                        '.'
                                    ) }}

                                </span>

                            </td>


                            <td class="px-5 py-4 text-right font-medium text-amber-600">

                                {{ number_format(
                                    $item->sisa_penerimaan,
                                    2,
                                    ',',
                                    '.'
                                ) }}

                            </td>


                            <td class="whitespace-nowrap px-5 py-4 text-right text-slate-600">

                                Rp {{ number_format(
                                    $item->harga_satuan,
                                    0,
                                    ',',
                                    '.'
                                ) }}

                            </td>


                            <td class="whitespace-nowrap px-5 py-4 text-right text-slate-500">

                                Rp {{ number_format(
                                    $item->diskon ?? 0,
                                    0,
                                    ',',
                                    '.'
                                ) }}

                            </td>


                            <td class="whitespace-nowrap px-5 py-4 text-right font-semibold text-slate-700">

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
                                colspan="9"
                                class="px-6 py-12 text-center text-sm text-slate-400"
                            >
                                Tidak ada detail barang.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- RIWAYAT PEMBAYARAN --}}
    {{-- ========================================================= --}}

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        <div class="flex flex-col gap-3 border-b border-slate-200 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">

            <div>

                <h3 class="text-sm font-semibold text-slate-700">
                    Riwayat Pembayaran PO
                </h3>

                <p class="mt-1 text-xs text-slate-400">
                    Pembayaran supplier yang dilakukan berdasarkan Purchase Order ini.
                </p>

            </div>


            @if($purchaseOrder->bisa_dibayar_po)

                <a
                    href="{{ route('pembayaran-po-supplier.create', $purchaseOrder) }}"
                    class="inline-flex h-9 items-center justify-center rounded-lg border border-emerald-200 bg-emerald-50 px-3 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100"
                >
                    + Pembayaran
                </a>

            @endif

        </div>


        <div class="overflow-x-auto">

            <table class="w-full min-w-[950px] text-sm">

                <thead class="bg-slate-50">

                    <tr class="text-left text-xs uppercase tracking-wide text-slate-400">

                        <th class="px-5 py-3 font-medium">
                            No
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Nomor Pembayaran
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Tanggal
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Kas / Bank
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Referensi
                        </th>

                        <th class="px-5 py-3 text-right font-medium">
                            Jumlah
                        </th>

                        <th class="px-5 py-3 text-center font-medium">
                            Status
                        </th>

                        <th class="px-5 py-3 text-center font-medium">
                            Aksi
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-slate-100">

                    @forelse($purchaseOrder->pembayaranPOSuppliers as $pembayaran)

                        @php
                            $paymentStatusClass =
                                match($pembayaran->status) {

                                    'TERCATAT' =>
                                        'bg-emerald-100 text-emerald-700',

                                    'DRAFT' =>
                                        'bg-slate-100 text-slate-600',

                                    'DIBATALKAN' =>
                                        'bg-red-100 text-red-700',

                                    default =>
                                        'bg-slate-100 text-slate-600',
                                };
                        @endphp

                        <tr class="transition hover:bg-slate-50/70">

                            <td class="px-5 py-4 text-slate-500">
                                {{ $loop->iteration }}
                            </td>


                            <td class="px-5 py-4">

                                <a
                                    href="{{ route('pembayaran-po-supplier.show', $pembayaran) }}"
                                    class="font-semibold text-[#15235F] hover:underline"
                                >
                                    {{ $pembayaran->nomor_pembayaran }}
                                </a>

                            </td>


                            <td class="whitespace-nowrap px-5 py-4 text-slate-600">

                                {{ $pembayaran
                                    ->tanggal_pembayaran
                                    ?->format('d/m/Y')
                                    ?? '-'
                                }}

                            </td>


                            <td class="px-5 py-4">

                                <p class="font-medium text-slate-700">
                                    {{ $pembayaran->rekeningKasBank?->nama_rekening ?? '-' }}
                                </p>

                            </td>


                            <td class="px-5 py-4 text-slate-500">

                                {{ $pembayaran->nomor_referensi ?: '-' }}

                            </td>


                            <td class="whitespace-nowrap px-5 py-4 text-right font-semibold text-slate-700">

                                Rp {{ number_format(
                                    $pembayaran->jumlah_bayar,
                                    0,
                                    ',',
                                    '.'
                                ) }}

                            </td>


                            <td class="px-5 py-4 text-center">

                                <span
                                    class="inline-flex rounded-full px-2.5 py-1 text-[11px] font-semibold {{ $paymentStatusClass }}"
                                >
                                    {{ $pembayaran->status }}
                                </span>

                            </td>


                            <td class="px-5 py-4 text-center">

                                <a
                                    href="{{ route('pembayaran-po-supplier.show', $pembayaran) }}"
                                    class="text-xs font-semibold text-[#15235F] hover:underline"
                                >
                                    Detail
                                </a>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="8"
                                class="px-6 py-12 text-center"
                            >

                                <p class="text-sm font-medium text-slate-500">
                                    Belum ada pembayaran PO
                                </p>

                                <p class="mt-1 text-xs text-slate-400">
                                    Pembayaran supplier untuk Purchase Order ini belum tercatat.
                                </p>

                                @if($purchaseOrder->bisa_dibayar_po)

                                    <a
                                        href="{{ route('pembayaran-po-supplier.create', $purchaseOrder) }}"
                                        class="mt-4 inline-flex h-9 items-center justify-center rounded-lg bg-emerald-600 px-4 text-xs font-semibold text-white transition hover:bg-emerald-700"
                                    >
                                        Bayar Supplier
                                    </a>

                                @endif

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- RINGKASAN PO --}}
    {{-- ========================================================= --}}

    <div class="flex justify-end">

        <div class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm md:w-[440px]">

            <h3 class="mb-5 text-sm font-semibold text-slate-700">
                Ringkasan Purchase Order
            </h3>


            <div class="space-y-4">

                <div class="flex items-center justify-between text-sm">

                    <span class="text-slate-500">
                        Subtotal Barang
                    </span>

                    <span class="font-medium text-slate-700">

                        Rp {{ number_format(
                            $purchaseOrder->subtotal,
                            0,
                            ',',
                            '.'
                        ) }}

                    </span>

                </div>


                <div class="flex items-center justify-between text-sm">

                    <span class="text-slate-500">
                        Diskon PO
                    </span>

                    <span class="font-medium text-red-500">

                        - Rp {{ number_format(
                            $purchaseOrder->diskon,
                            0,
                            ',',
                            '.'
                        ) }}

                    </span>

                </div>


                <div class="flex items-center justify-between text-sm">

                    <span class="text-slate-500">
                        Dasar Pajak
                    </span>

                    <span class="font-medium text-slate-700">

                        Rp {{ number_format(
                            max(
                                0,
                                (float) $purchaseOrder->subtotal
                                -
                                (float) $purchaseOrder->diskon
                            ),
                            0,
                            ',',
                            '.'
                        ) }}

                    </span>

                </div>


                <div class="flex items-center justify-between text-sm">

                    <span class="text-slate-500">

                        Pajak

                        @if($pajakPersen > 0)
                            ({{ $pajakPersenFormatted }}%)
                        @endif

                    </span>

                    <span class="font-medium text-slate-700">

                        Rp {{ number_format(
                            $purchaseOrder->pajak,
                            0,
                            ',',
                            '.'
                        ) }}

                    </span>

                </div>


                <div class="flex items-center justify-between border-t border-slate-200 pt-4">

                    <span class="font-semibold text-slate-700">
                        Total Purchase Order
                    </span>

                    <span class="text-xl font-bold text-[#15235F]">

                        Rp {{ number_format(
                            $purchaseOrder->total,
                            0,
                            ',',
                            '.'
                        ) }}

                    </span>

                </div>


                <div class="flex items-center justify-between border-t border-slate-100 pt-4 text-sm">

                    <span class="text-slate-500">
                        Sudah Dibayar
                    </span>

                    <span class="font-semibold text-emerald-600">

                        Rp {{ number_format(
                            $totalDibayar,
                            0,
                            ',',
                            '.'
                        ) }}

                    </span>

                </div>


                <div class="flex items-center justify-between text-sm">

                    <span class="font-semibold text-slate-700">
                        Sisa Pembayaran
                    </span>

                    <span
                        class="font-bold {{ $sisaPembayaran <= 0
                            ? 'text-emerald-600'
                            : 'text-amber-600'
                        }}"
                    >

                        Rp {{ number_format(
                            $sisaPembayaran,
                            0,
                            ',',
                            '.'
                        ) }}

                    </span>

                </div>

            </div>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- INFO STATUS --}}
    {{-- ========================================================= --}}

    @if($purchaseOrder->status === 'Draft')

        <div class="rounded-xl border border-amber-200 bg-amber-50 px-5 py-4">

            <p class="text-sm font-semibold text-amber-800">
                Purchase Order masih Draft
            </p>

            <p class="mt-1 text-xs leading-5 text-amber-700">
                Periksa kembali data Purchase Order. Setelah data benar,
                klik Kirim PO agar PO dapat digunakan untuk proses
                pembayaran supplier maupun penerimaan barang.
            </p>

        </div>


    @elseif($purchaseOrder->status === 'Dipesan')

        <div class="rounded-xl border border-blue-100 bg-blue-50 px-5 py-4">

            <p class="text-sm font-semibold text-blue-800">
                Purchase Order telah dikirim
            </p>

            <p class="mt-1 text-xs leading-5 text-blue-600">
                Purchase Order sudah aktif dan dapat digunakan untuk
                pembayaran supplier maupun penerimaan barang.
            </p>

        </div>


    @elseif($purchaseOrder->status === 'Sebagian Diterima')

        <div class="rounded-xl border border-amber-200 bg-amber-50 px-5 py-4">

            <p class="text-sm font-semibold text-amber-800">
                Barang diterima sebagian
            </p>

            <p class="mt-1 text-xs leading-5 text-amber-700">
                Masih terdapat barang pada Purchase Order yang belum
                diterima seluruhnya. Status pembayaran tetap dihitung
                secara terpisah.
            </p>

        </div>


    @elseif($purchaseOrder->status === 'Selesai')

        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4">

            <p class="text-sm font-semibold text-emerald-800">
                Penerimaan Purchase Order selesai
            </p>

            <p class="mt-1 text-xs leading-5 text-emerald-700">
                Seluruh barang efektif pada Purchase Order telah diterima.
                Periksa status pembayaran untuk memastikan kewajiban
                pembayaran juga telah diselesaikan.
            </p>

        </div>

    @endif

</div>

@endsection