@extends('layouts.app')

@section('title', 'Detail Invoice Supplier')

@section('content')

<div class="mx-auto max-w-6xl space-y-6">

    {{-- ========================================================= --}}
    {{-- HEADER --}}
    {{-- ========================================================= --}}

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>

            <a
                href="{{ route('invoice-supplier.index') }}"
                class="mb-3 inline-flex text-sm text-slate-500 transition hover:text-[#15235F]"
            >
                <x-icon name="arrow-left" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" /> Kembali
            </a>

            <h2 class="text-xl font-semibold text-slate-800">
                {{ $invoiceSupplier->nomor_invoice_internal }}
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Detail Invoice Supplier.
            </p>

        </div>


        @if(
            in_array(
                $invoiceSupplier->status,
                ['BELUM_LUNAS', 'SEBAGIAN']
            )
            && (float) $invoiceSupplier->sisa_utang > 0
        )

            <a
                href="{{ route(
                    'pembayaran-supplier.create',
                    [
                        'invoice_supplier_id' => $invoiceSupplier->id
                    ]
                ) }}"
                class="inline-flex h-10 items-center justify-center rounded-lg bg-[#15235F] px-4 text-sm font-medium text-white transition hover:bg-[#101b4d]"
            >
                Bayar Invoice
            </a>

        @endif

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


    @php

        $statusClass = match($invoiceSupplier->status) {
            'BELUM_LUNAS' => 'bg-red-50 text-red-700',
            'SEBAGIAN' => 'bg-amber-50 text-amber-700',
            'LUNAS' => 'bg-emerald-50 text-emerald-700',
            default => 'bg-slate-100 text-slate-600',
        };


        $isOverdue =
            $invoiceSupplier->tanggal_jatuh_tempo
            &&
            $invoiceSupplier->tanggal_jatuh_tempo->lt(today())
            &&
            $invoiceSupplier->status !== 'LUNAS';


        /*
        |--------------------------------------------------------------------------
        | PAJAK
        |--------------------------------------------------------------------------
        */

        $pajakPersen =
            (float) (
                $invoiceSupplier->pajak_persen
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
                    $invoiceSupplier->subtotal
                    ?? 0
                )
                -
                (float) (
                    $invoiceSupplier->diskon
                    ?? 0
                )
            );

    @endphp


    {{-- ========================================================= --}}
    {{-- INFORMASI INVOICE --}}
    {{-- ========================================================= --}}

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-100 px-6 py-5">

            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">

                <div>

                    <h3 class="text-sm font-semibold text-slate-800">
                        Informasi Invoice
                    </h3>

                    <p class="mt-1 text-xs text-slate-400">
                        Informasi utama tagihan supplier.
                    </p>

                </div>


                <span class="inline-flex w-fit rounded-full px-3 py-1.5 text-xs font-semibold {{ $statusClass }}">
                    {{ str_replace('_', ' ', $invoiceSupplier->status) }}
                </span>

            </div>

        </div>


        <div class="px-6 py-6">

            <div class="grid grid-cols-1 gap-x-10 gap-y-6 md:grid-cols-2 xl:grid-cols-4">

                <div>

                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">
                        Nomor Internal
                    </p>

                    <p class="mt-1.5 text-sm font-semibold text-[#15235F]">
                        {{ $invoiceSupplier->nomor_invoice_internal }}
                    </p>

                </div>


                <div>

                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">
                        Nomor Supplier
                    </p>

                    <p class="mt-1.5 text-sm font-medium text-slate-700">
                        {{ $invoiceSupplier->nomor_invoice_supplier ?: '-' }}
                    </p>

                </div>


                <div>

                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">
                        Supplier
                    </p>

                    <p class="mt-1.5 text-sm font-semibold text-slate-700">
                        {{ $invoiceSupplier->supplier?->nama_supplier ?? '-' }}
                    </p>

                    @if($invoiceSupplier->supplier?->kode_supplier)

                        <p class="mt-1 text-xs text-slate-400">
                            {{ $invoiceSupplier->supplier->kode_supplier }}
                        </p>

                    @endif

                </div>


                <div>

                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">
                        Purchase Order
                    </p>

                    @if($invoiceSupplier->purchaseOrder)

                        <a
                            href="{{ route(
                                'purchase-order.show',
                                $invoiceSupplier->purchaseOrder
                            ) }}"
                            class="mt-1.5 inline-flex text-sm font-semibold text-[#15235F] transition hover:text-[#32AAA6]"
                        >
                            {{ $invoiceSupplier->purchaseOrder->nomor_po }}
                        </a>

                    @else

                        <p class="mt-1.5 text-sm text-slate-400">
                            -
                        </p>

                    @endif

                </div>


                <div>

                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">
                        Tanggal Invoice
                    </p>

                    <p class="mt-1.5 text-sm font-medium text-slate-700">
                        {{ $invoiceSupplier->tanggal_invoice?->format('d/m/Y') ?? '-' }}
                    </p>

                </div>


                <div>

                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">
                        Jatuh Tempo
                    </p>

                    <p class="mt-1.5 text-sm font-medium {{ $isOverdue ? 'text-red-600' : 'text-slate-700' }}">
                        {{ $invoiceSupplier->tanggal_jatuh_tempo?->format('d/m/Y') ?? '-' }}
                    </p>

                    @if($isOverdue)

                        <span class="mt-1 inline-flex rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-medium text-red-600">
                            Lewat Jatuh Tempo
                        </span>

                    @endif

                </div>


                <div>

                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">
                        Dibuat Oleh
                    </p>

                    <p class="mt-1.5 text-sm font-medium text-slate-700">
                        {{ $invoiceSupplier->creator?->name ?? '-' }}
                    </p>

                </div>


                <div>

                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">
                        Status Pembayaran
                    </p>

                    <span class="mt-1.5 inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $statusClass }}">
                        {{ str_replace('_', ' ', $invoiceSupplier->status) }}
                    </span>

                </div>

            </div>


            <div class="mt-7 border-t border-slate-100 pt-5">

                <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">
                    Catatan
                </p>

                @if($invoiceSupplier->catatan)

                    <p class="mt-2 text-sm leading-6 text-slate-600">
                        {{ $invoiceSupplier->catatan }}
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
    {{-- DETAIL BARANG --}}
    {{-- ========================================================= --}}

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-200 px-6 py-4">

            <h3 class="text-sm font-semibold text-slate-700">
                Detail Barang
            </h3>

            <p class="mt-1 text-xs text-slate-400">
                Barang yang tercantum pada Invoice Supplier.
            </p>

        </div>


        <div class="overflow-x-auto">

            <table class="w-full min-w-[1150px] text-sm">

                <thead class="bg-slate-50">

                    <tr class="text-left text-xs uppercase tracking-wide text-slate-400">

                        <th class="px-5 py-3 font-medium">
                            No
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Barang
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Penerimaan
                        </th>

                        <th class="px-5 py-3 text-right font-medium">
                            Qty
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Satuan
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

                    @forelse($invoiceSupplier->items as $item)

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


                            <td class="px-5 py-4 text-slate-600">

                                {{ $item
                                    ->penerimaanBarangItem
                                    ?->penerimaanBarang
                                    ?->nomor_penerimaan
                                    ?? '-'
                                }}

                            </td>


                            <td class="px-5 py-4 text-right font-semibold text-slate-700">

                                {{ number_format(
                                    $item->qty ?? 0,
                                    2,
                                    ',',
                                    '.'
                                ) }}

                            </td>


                            <td class="px-5 py-4 text-slate-500">
                                {{ $item->barang?->satuan?->nama_satuan ?? '-' }}
                            </td>


                            <td class="whitespace-nowrap px-5 py-4 text-right text-slate-600">

                                Rp {{ number_format(
                                    $item->harga_satuan ?? 0,
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
                                    $item->subtotal ?? 0,
                                    0,
                                    ',',
                                    '.'
                                ) }}

                            </td>

                        </tr>


                    @empty

                        <tr>

                            <td
                                colspan="8"
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
    {{-- KPI RINGKASAN --}}
    {{-- ========================================================= --}}

    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">

        {{-- TOTAL INVOICE --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                Total Invoice
            </p>

            <p class="mt-2 text-xl font-bold text-[#15235F]">

                Rp {{ number_format(
                    $invoiceSupplier->total ?? 0,
                    0,
                    ',',
                    '.'
                ) }}

            </p>

        </div>


        {{-- SUDAH DIBAYAR --}}
        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-5">

            <p class="text-xs font-medium uppercase tracking-wide text-emerald-500">
                Sudah Dibayar
            </p>

            <p class="mt-2 text-xl font-bold text-emerald-600">

                Rp {{ number_format(
                    $invoiceSupplier->total_dibayar ?? 0,
                    0,
                    ',',
                    '.'
                ) }}

            </p>

        </div>


        {{-- SISA UTANG --}}
        <div class="rounded-2xl border border-red-100 bg-red-50 p-5">

            <p class="text-xs font-medium uppercase tracking-wide text-red-500">
                Sisa Utang
            </p>

            <p class="mt-2 text-xl font-bold text-red-600">

                Rp {{ number_format(
                    $invoiceSupplier->sisa_utang ?? 0,
                    0,
                    ',',
                    '.'
                ) }}

            </p>

        </div>


        {{-- JATUH TEMPO --}}
        <div
            class="rounded-2xl border p-5
                {{ $isOverdue
                    ? 'border-red-100 bg-red-50'
                    : 'border-slate-200 bg-white'
                }}"
        >

            <p
                class="text-xs font-medium uppercase tracking-wide
                    {{ $isOverdue
                        ? 'text-red-500'
                        : 'text-slate-400'
                    }}"
            >
                Jatuh Tempo
            </p>

            <p
                class="mt-2 text-xl font-bold
                    {{ $isOverdue
                        ? 'text-red-600'
                        : 'text-slate-700'
                    }}"
            >
                {{ $invoiceSupplier->tanggal_jatuh_tempo?->format('d/m/Y') ?? '-' }}
            </p>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- RINCIAN NILAI INVOICE --}}
    {{-- ========================================================= --}}

    <div class="flex justify-end">

        <div class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm md:w-[430px]">

            <h3 class="mb-5 text-sm font-semibold text-slate-700">
                Rincian Nilai Invoice
            </h3>


            <div class="space-y-4">

                {{-- SUBTOTAL --}}
                <div class="flex items-center justify-between text-sm">

                    <span class="text-slate-500">
                        Subtotal Barang
                    </span>

                    <span class="font-medium text-slate-700">

                        Rp {{ number_format(
                            $invoiceSupplier->subtotal ?? 0,
                            0,
                            ',',
                            '.'
                        ) }}

                    </span>

                </div>


                {{-- DISKON --}}
                <div class="flex items-center justify-between text-sm">

                    <span class="text-slate-500">
                        Diskon Invoice
                    </span>

                    <span class="font-medium text-red-500">

                        - Rp {{ number_format(
                            $invoiceSupplier->diskon ?? 0,
                            0,
                            ',',
                            '.'
                        ) }}

                    </span>

                </div>


                {{-- DASAR PAJAK --}}
                <div class="flex items-center justify-between text-sm">

                    <span class="text-slate-500">
                        Dasar Pajak
                    </span>

                    <span class="font-medium text-slate-700">

                        Rp {{ number_format(
                            $dasarPajak,
                            0,
                            ',',
                            '.'
                        ) }}

                    </span>

                </div>


                {{-- PAJAK --}}
                <div class="flex items-center justify-between text-sm">

                    <span class="text-slate-500">

                        Pajak

                        @if($pajakPersen > 0)

                            ({{ $pajakPersenFormatted }}%)

                        @endif

                    </span>

                    <span class="font-medium text-slate-700">

                        Rp {{ number_format(
                            $invoiceSupplier->pajak ?? 0,
                            0,
                            ',',
                            '.'
                        ) }}

                    </span>

                </div>


                {{-- TOTAL --}}
                <div class="flex items-center justify-between border-t border-slate-200 pt-4">

                    <span class="font-semibold text-slate-700">
                        Total Invoice
                    </span>

                    <span class="text-xl font-bold text-[#15235F]">

                        Rp {{ number_format(
                            $invoiceSupplier->total ?? 0,
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
    {{-- STATUS INFORMATION --}}
    {{-- ========================================================= --}}

    @if($invoiceSupplier->status === 'BELUM_LUNAS')

        <div class="rounded-xl border border-red-100 bg-red-50 px-5 py-4">

            <p class="text-sm font-semibold text-red-700">
                Invoice belum dibayar
            </p>

            <p class="mt-1 text-xs leading-5 text-red-600">

                Invoice ini masih memiliki saldo utang sebesar

                Rp {{ number_format(
                    $invoiceSupplier->sisa_utang ?? 0,
                    0,
                    ',',
                    '.'
                ) }}.

            </p>

        </div>


    @elseif($invoiceSupplier->status === 'SEBAGIAN')

        <div class="rounded-xl border border-amber-200 bg-amber-50 px-5 py-4">

            <p class="text-sm font-semibold text-amber-800">
                Invoice telah dibayar sebagian
            </p>

            <p class="mt-1 text-xs leading-5 text-amber-700">

                Masih terdapat sisa utang sebesar

                Rp {{ number_format(
                    $invoiceSupplier->sisa_utang ?? 0,
                    0,
                    ',',
                    '.'
                ) }}.

            </p>

        </div>


    @elseif($invoiceSupplier->status === 'LUNAS')

        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4">

            <p class="text-sm font-semibold text-emerald-800">
                Invoice telah lunas
            </p>

            <p class="mt-1 text-xs leading-5 text-emerald-700">
                Seluruh tagihan pada Invoice Supplier ini telah diselesaikan.
            </p>

        </div>

    @endif

</div>

@endsection