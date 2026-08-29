@extends('layouts.app')

@section('title', 'Detail Purchase Order')

@section('content')

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
                Detail Purchase Order.
            </p>

        </div>


        {{-- ACTION --}}
        <div class="flex flex-wrap items-center gap-2">

            {{-- CETAK PDF --}}
            <a
                href="{{ route('purchase-order.pdf', $purchaseOrder) }}"
                target="_blank"
                class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
            >
                Cetak PDF
            </a>


            @if($purchaseOrder->status === 'Draft')

                {{-- EDIT --}}
                <a
                    href="{{ route('purchase-order.edit', $purchaseOrder) }}"
                    class="inline-flex h-10 items-center justify-center rounded-lg border border-slate-300 bg-white px-4 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
                >
                    Edit
                </a>


                {{-- KIRIM PO --}}
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
    {{-- STATUS --}}
    {{-- ========================================================= --}}

    @php

        $statusClass = match($purchaseOrder->status) {

            'Draft'
                => 'bg-slate-100 text-slate-600',

            'Dipesan'
                => 'bg-blue-50 text-blue-700',

            'Sebagian Diterima'
                => 'bg-amber-50 text-amber-700',

            'Selesai'
                => 'bg-emerald-50 text-emerald-700',

            'Dibatalkan'
                => 'bg-red-50 text-red-600',

            default
                => 'bg-slate-100 text-slate-600',
        };


        /*
        |--------------------------------------------------------------------------
        | FORMAT PAJAK PERSEN
        |--------------------------------------------------------------------------
        */

        $pajakPersen = (float) ($purchaseOrder->pajak_persen ?? 0);

        $pajakPersenFormatted = rtrim(
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

    @endphp


    {{-- ========================================================= --}}
    {{-- INFORMASI PURCHASE ORDER --}}
    {{-- ========================================================= --}}

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        {{-- HEADER CARD --}}
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


        {{-- BODY --}}
        <div class="px-6 py-6">

            <div class="grid grid-cols-1 gap-x-10 gap-y-6 md:grid-cols-2 xl:grid-cols-4">

                {{-- NOMOR PO --}}
                <div>

                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">
                        Nomor PO
                    </p>

                    <p class="mt-1.5 text-sm font-semibold text-[#15235F]">
                        {{ $purchaseOrder->nomor_po }}
                    </p>

                </div>


                {{-- TANGGAL PO --}}
                <div>

                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">
                        Tanggal PO
                    </p>

                    <p class="mt-1.5 text-sm font-medium text-slate-700">

                        {{ $purchaseOrder
                            ->tanggal_po
                            ?->format('d/m/Y')
                            ?? '-'
                        }}

                    </p>

                </div>


                {{-- SUPPLIER --}}
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


                {{-- ESTIMASI KEDATANGAN --}}
                <div>

                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">
                        Estimasi Kedatangan
                    </p>

                    <p class="mt-1.5 text-sm font-medium text-slate-700">

                        {{ $purchaseOrder
                            ->tanggal_estimasi_datang
                            ?->format('d/m/Y')
                            ?? '-'
                        }}

                    </p>

                </div>


                {{-- DIBUAT OLEH --}}
                <div>

                    <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">
                        Dibuat Oleh
                    </p>

                    <p class="mt-1.5 text-sm font-medium text-slate-700">
                        {{ $purchaseOrder->creator?->name ?? '-' }}
                    </p>

                </div>

            </div>


            {{-- CATATAN --}}
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

                            {{-- NO --}}
                            <td class="px-5 py-4 text-slate-500">
                                {{ $loop->iteration }}
                            </td>


                            {{-- BARANG --}}
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


                            {{-- SATUAN --}}
                            <td class="px-5 py-4 text-slate-500">

                                {{ $item
                                    ->barang
                                    ?->satuan
                                    ?->nama_satuan
                                    ?? '-'
                                }}

                            </td>


                            {{-- QTY --}}
                            <td class="px-5 py-4 text-right font-medium text-slate-700">

                                {{ number_format(
                                    $item->qty,
                                    2,
                                    ',',
                                    '.'
                                ) }}

                            </td>


                            {{-- DITERIMA --}}
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


                            {{-- HARGA --}}
                            <td class="whitespace-nowrap px-5 py-4 text-right text-slate-600">

                                Rp {{ number_format(
                                    $item->harga_satuan,
                                    0,
                                    ',',
                                    '.'
                                ) }}

                            </td>


                            {{-- DISKON --}}
                            <td class="whitespace-nowrap px-5 py-4 text-right text-slate-500">

                                Rp {{ number_format(
                                    $item->diskon ?? 0,
                                    0,
                                    ',',
                                    '.'
                                ) }}

                            </td>


                            {{-- SUBTOTAL --}}
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
    {{-- RINGKASAN --}}
    {{-- ========================================================= --}}

    <div class="flex justify-end">

        <div class="w-full rounded-2xl border border-slate-200 bg-white p-6 shadow-sm md:w-[420px]">

            <h3 class="mb-5 text-sm font-semibold text-slate-700">
                Ringkasan Purchase Order
            </h3>


            <div class="space-y-4">

                {{-- SUBTOTAL --}}
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


                {{-- DISKON --}}
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


                {{-- DASAR PAJAK --}}
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
                            $purchaseOrder->pajak,
                            0,
                            ',',
                            '.'
                        ) }}

                    </span>

                </div>


                {{-- TOTAL --}}
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
                Periksa kembali data Purchase Order. Setelah data benar, klik Kirim PO agar dapat digunakan pada proses Penerimaan Barang.
            </p>

        </div>

    @elseif($purchaseOrder->status === 'Dipesan')

        <div class="rounded-xl border border-blue-100 bg-blue-50 px-5 py-4">

            <p class="text-sm font-semibold text-blue-800">
                Purchase Order telah dikirim
            </p>

            <p class="mt-1 text-xs leading-5 text-blue-600">
                Purchase Order sudah aktif dan dapat digunakan pada proses Penerimaan Barang.
            </p>

        </div>

    @elseif($purchaseOrder->status === 'Sebagian Diterima')

        <div class="rounded-xl border border-amber-200 bg-amber-50 px-5 py-4">

            <p class="text-sm font-semibold text-amber-800">
                Barang diterima sebagian
            </p>

            <p class="mt-1 text-xs leading-5 text-amber-700">
                Masih terdapat barang pada Purchase Order yang belum diterima seluruhnya.
            </p>

        </div>

    @elseif($purchaseOrder->status === 'Selesai')

        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4">

            <p class="text-sm font-semibold text-emerald-800">
                Purchase Order selesai
            </p>

            <p class="mt-1 text-xs leading-5 text-emerald-700">
                Seluruh barang pada Purchase Order telah diterima.
            </p>

        </div>

    @endif

</div>

@endsection