@extends('layouts.app')

@section('title', 'Detail Penerimaan Barang')

@section('content')

<div class="mx-auto max-w-6xl space-y-6">

    {{-- HEADER --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

        <div>

            <a
                href="{{ route('penerimaan-barang.index') }}"
                class="mb-3 inline-flex items-center gap-2 text-sm text-slate-500 transition hover:text-[#15235F]"
            >
                ← Kembali
            </a>

            <h2 class="text-xl font-semibold text-slate-800">
                {{ $penerimaanBarang->nomor_penerimaan }}
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Detail transaksi penerimaan barang.
            </p>

        </div>


        <a
            href="{{ route('purchase-order.show', $penerimaanBarang->purchaseOrder) }}"
            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50 hover:text-[#15235F]"
        >
            Lihat Purchase Order
        </a>

    </div>


    {{-- ALERT --}}
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


    {{-- INFORMASI --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        {{-- INFORMASI PENERIMAAN --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">

            <div class="mb-5">

                <h3 class="text-sm font-semibold text-slate-700">
                    Informasi Penerimaan
                </h3>

                <p class="mt-1 text-xs text-slate-400">
                    Informasi transaksi penerimaan barang dari supplier.
                </p>

            </div>


            <div class="grid grid-cols-1 gap-x-8 gap-y-5 md:grid-cols-2">

                {{-- NOMOR PENERIMAAN --}}
                <div>

                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Nomor Penerimaan
                    </p>

                    <p class="mt-1 text-sm font-semibold text-[#15235F]">
                        {{ $penerimaanBarang->nomor_penerimaan }}
                    </p>

                </div>


                {{-- TANGGAL --}}
                <div>

                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Tanggal Penerimaan
                    </p>

                    <p class="mt-1 text-sm font-medium text-slate-700">
                        {{ $penerimaanBarang->tanggal_penerimaan?->format('d/m/Y') ?? '-' }}
                    </p>

                </div>


                {{-- PURCHASE ORDER --}}
                <div>

                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Purchase Order
                    </p>

                    <a
                        href="{{ route('purchase-order.show', $penerimaanBarang->purchaseOrder) }}"
                        class="mt-1 inline-flex text-sm font-semibold text-[#15235F] transition hover:text-[#32AAA6]"
                    >
                        {{ $penerimaanBarang->purchaseOrder?->nomor_po ?? '-' }}
                    </a>

                </div>


                {{-- SUPPLIER --}}
                <div>

                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Supplier
                    </p>

                    <p class="mt-1 text-sm font-medium text-slate-700">
                        {{ $penerimaanBarang->purchaseOrder?->supplier?->nama_supplier ?? '-' }}
                    </p>

                    @if($penerimaanBarang->purchaseOrder?->supplier?->kode_supplier)

                        <p class="mt-1 text-xs text-slate-400">
                            {{ $penerimaanBarang->purchaseOrder->supplier->kode_supplier }}
                        </p>

                    @endif

                </div>


                {{-- GUDANG --}}
                <div>

                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Gudang Tujuan
                    </p>

                    <p class="mt-1 text-sm font-medium text-slate-700">
                        {{ $penerimaanBarang->gudang?->nama_gudang ?? '-' }}
                    </p>

                    @if($penerimaanBarang->gudang?->kode_gudang)

                        <p class="mt-1 text-xs text-slate-400">
                            {{ $penerimaanBarang->gudang->kode_gudang }}
                        </p>

                    @endif

                </div>


                {{-- SURAT JALAN --}}
                <div>

                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Nomor Surat Jalan
                    </p>

                    <p class="mt-1 text-sm text-slate-700">
                        {{ $penerimaanBarang->nomor_surat_jalan ?: '-' }}
                    </p>

                </div>


                {{-- CREATED BY --}}
                <div>

                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Dicatat Oleh
                    </p>

                    <p class="mt-1 text-sm text-slate-700">
                        {{ $penerimaanBarang->creator?->name ?? '-' }}
                    </p>

                </div>


                {{-- CREATED AT --}}
                <div>

                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Waktu Pencatatan
                    </p>

                    <p class="mt-1 text-sm text-slate-700">
                        {{ $penerimaanBarang->created_at?->format('d/m/Y H:i') ?? '-' }}
                    </p>

                </div>

            </div>


            {{-- CATATAN --}}
            <div class="mt-6 border-t border-slate-100 pt-5">

                <p class="text-xs uppercase tracking-wide text-slate-400">
                    Catatan
                </p>

                @if($penerimaanBarang->catatan)

                    <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600">
                        {{ $penerimaanBarang->catatan }}
                    </p>

                @else

                    <p class="mt-2 text-sm text-slate-400">
                        Tidak ada catatan.
                    </p>

                @endif

            </div>

        </div>


        {{-- RINGKASAN --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <div class="mb-5">

                <h3 class="text-sm font-semibold text-slate-700">
                    Ringkasan
                </h3>

                <p class="mt-1 text-xs text-slate-400">
                    Ringkasan barang yang diterima.
                </p>

            </div>


            @php

                $totalJenisBarang =
                    $penerimaanBarang->items->count();

                $totalQty =
                    $penerimaanBarang->items->sum(
                        fn ($item) =>
                            (float) $item->qty_diterima
                    );

                $totalNilai =
                    $penerimaanBarang->items->sum(
                        fn ($item) =>
                            (float) $item->qty_diterima
                            *
                            (float) $item->harga_satuan
                    );

            @endphp


            <div class="space-y-4">

                <div class="flex items-center justify-between text-sm">

                    <span class="text-slate-500">
                        Jenis Barang
                    </span>

                    <span class="font-semibold text-slate-700">
                        {{ $totalJenisBarang }}
                    </span>

                </div>


                <div class="flex items-center justify-between text-sm">

                    <span class="text-slate-500">
                        Total Qty
                    </span>

                    <span class="font-semibold text-slate-700">
                        {{ number_format(
                            $totalQty,
                            2,
                            ',',
                            '.'
                        ) }}
                    </span>

                </div>


                <div class="border-t border-slate-200 pt-4">

                    <p class="text-xs text-slate-400">
                        Nilai Barang Diterima
                    </p>

                    <p class="mt-1 text-xl font-bold text-[#15235F]">

                        Rp {{ number_format(
                            $totalNilai,
                            0,
                            ',',
                            '.'
                        ) }}

                    </p>

                    <p class="mt-2 text-[11px] leading-4 text-slate-400">
                        Berdasarkan harga satuan pada Purchase Order.
                    </p>

                </div>

            </div>

        </div>

    </div>


    {{-- DETAIL BARANG --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-200 px-6 py-4">

            <h3 class="text-sm font-semibold text-slate-700">
                Detail Barang Diterima
            </h3>

            <p class="mt-1 text-xs text-slate-400">
                Daftar barang yang diterima pada transaksi ini.
            </p>

        </div>


        <div class="overflow-x-auto">

            <table class="w-full min-w-[1000px] text-sm">

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
                            Qty Diterima
                        </th>

                        <th class="px-5 py-3 text-right font-medium">
                            Harga PO
                        </th>

                        <th class="px-5 py-3 text-right font-medium">
                            Nilai
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-slate-100">

                    @forelse($penerimaanBarang->items as $item)

                        @php

                            $nilai =
                                (float) $item->qty_diterima
                                *
                                (float) $item->harga_satuan;

                        @endphp


                        <tr class="transition hover:bg-slate-50/70">

                            <td class="px-5 py-4 text-slate-500">
                                {{ $loop->iteration }}
                            </td>


                            <td class="px-5 py-4">

                                <p class="font-medium text-slate-700">

                                    {{ $item->barang?->kode_barang }}
                                    -
                                    {{ $item->barang?->nama_barang }}

                                </p>


                                @if($item->barang?->spesifikasi)

                                    <p class="mt-1 text-xs text-slate-400">

                                        {{ $item->barang->spesifikasi }}

                                        @if($item->barang->merek)

                                            - {{ $item->barang->merek }}

                                        @endif

                                    </p>

                                @endif

                            </td>


                            <td class="px-5 py-4 text-slate-500">

                                {{ $item->barang?->satuan?->nama_satuan ?? '-' }}

                            </td>


                            <td class="px-5 py-4 text-right">

                                <span class="font-semibold text-slate-700">

                                    {{ number_format(
                                        $item->qty_diterima,
                                        2,
                                        ',',
                                        '.'
                                    ) }}

                                </span>

                            </td>


                            <td class="whitespace-nowrap px-5 py-4 text-right text-slate-600">

                                Rp {{ number_format(
                                    $item->harga_satuan,
                                    0,
                                    ',',
                                    '.'
                                ) }}

                            </td>


                            <td class="whitespace-nowrap px-5 py-4 text-right font-semibold text-slate-700">

                                Rp {{ number_format(
                                    $nilai,
                                    0,
                                    ',',
                                    '.'
                                ) }}

                            </td>

                        </tr>


                    @empty

                        <tr>

                            <td
                                colspan="6"
                                class="px-6 py-12 text-center text-sm text-slate-400"
                            >
                                Tidak ada barang pada penerimaan ini.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>


    {{-- INFO --}}
    <div class="rounded-xl border border-blue-100 bg-blue-50 px-5 py-4">

        <p class="text-sm font-medium text-blue-800">
            Penerimaan barang sudah dicatat
        </p>

        <p class="mt-1 text-xs leading-5 text-blue-600">
            Transaksi penerimaan yang sudah disimpan tidak dapat diedit atau dihapus langsung. Jika terjadi kesalahan, nantinya koreksi dilakukan melalui mekanisme pembatalan atau penyesuaian stok agar riwayat transaksi tetap tercatat.
        </p>

    </div>


    <div class="flex justify-end">

        <a
            href="{{ route('penerimaan-barang.index') }}"
            class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100"
        >
            Kembali ke Daftar
        </a>

    </div>

</div>

@endsection