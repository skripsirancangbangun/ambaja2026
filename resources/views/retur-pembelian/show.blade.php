@extends('layouts.app')

@section('title', 'Detail Retur Pembelian')

@section('content')

<div class="mx-auto max-w-6xl space-y-6">

    {{-- HEADER --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

        <div>

            <a
                href="{{ route('retur-pembelian.index') }}"
                class="mb-3 inline-flex text-sm text-slate-500 hover:text-[#15235F]"
            >
                ← Kembali
            </a>

            <h2 class="text-xl font-semibold text-slate-800">
                {{ $returPembelian->nomor_retur }}
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Detail Retur Pembelian.
            </p>

        </div>


        {{-- TOMBOL PROSES RETUR --}}
        @if($returPembelian->status === 'DRAFT')

            <form
                method="POST"
                action="{{ route('retur-pembelian.proses', $returPembelian) }}"
                onsubmit="return confirm('Proses retur ini? Stok gudang akan berkurang dan transaksi tidak dapat diedit lagi.')"
            >

                @csrf
                @method('PATCH')

                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-lg bg-[#15235F] px-5 py-2.5 text-sm font-medium text-white transition hover:bg-[#101b4d]"
                >
                    Proses Retur
                </button>

            </form>

        @endif

    </div>


    {{-- ALERT SUCCESS --}}
    @if(session('success'))

        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>

    @endif


    {{-- ALERT ERROR --}}
    @if(session('error'))

        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>

    @endif


    {{-- VALIDATION ERROR --}}
    @if($errors->any())

        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3">

            <p class="text-sm font-semibold text-red-700">
                Retur tidak dapat diproses.
            </p>

            <ul class="mt-2 list-disc space-y-1 pl-5 text-xs text-red-600">

                @foreach($errors->all() as $error)

                    <li>
                        {{ $error }}
                    </li>

                @endforeach

            </ul>

        </div>

    @endif


    @php

        $statusClass = match($returPembelian->status) {

            'DRAFT'
                => 'bg-slate-100 text-slate-600',

            'DIPROSES'
                => 'bg-amber-50 text-amber-700',

            'SELESAI'
                => 'bg-emerald-50 text-emerald-700',

            'DIBATALKAN'
                => 'bg-red-50 text-red-600',

            default
                => 'bg-slate-100 text-slate-600',
        };


        $totalQty =
            $returPembelian
                ->details
                ->sum(
                    fn ($detail) =>
                        (float) $detail->qty
                );


        $totalNilai =
            $returPembelian
                ->details
                ->sum(
                    fn ($detail) =>
                        (float) $detail->subtotal
                );

    @endphp


    {{-- INFORMASI + RINGKASAN --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">


        {{-- INFORMASI RETUR --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">

            <h3 class="mb-5 text-sm font-semibold text-slate-700">
                Informasi Retur
            </h3>


            <div class="grid grid-cols-1 gap-x-8 gap-y-5 md:grid-cols-2">


                {{-- NOMOR --}}
                <div>

                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        No. Retur
                    </p>

                    <p class="mt-1 text-sm font-semibold text-[#15235F]">
                        {{ $returPembelian->nomor_retur }}
                    </p>

                </div>


                {{-- STATUS --}}
                <div>

                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Status
                    </p>

                    <span
                        class="mt-1 inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $statusClass }}"
                    >
                        {{ $returPembelian->status }}
                    </span>

                </div>


                {{-- TANGGAL --}}
                <div>

                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Tanggal Retur
                    </p>

                    <p class="mt-1 text-sm text-slate-700">

                        {{ $returPembelian
                            ->tanggal_retur
                            ?->format('d/m/Y')
                            ?? '-'
                        }}

                    </p>

                </div>


                {{-- SUPPLIER --}}
                <div>

                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Supplier
                    </p>

                    <p class="mt-1 text-sm font-medium text-slate-700">

                        {{ $returPembelian
                            ->supplier
                            ?->nama_supplier
                            ?? '-'
                        }}

                    </p>

                </div>


                {{-- PENERIMAAN --}}
                <div>

                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Penerimaan Barang
                    </p>

                    <p class="mt-1 text-sm font-medium text-slate-700">

                        {{ $returPembelian
                            ->penerimaanBarang
                            ?->nomor_penerimaan
                            ?? '-'
                        }}

                    </p>

                </div>


                {{-- PO --}}
                <div>

                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Purchase Order
                    </p>

                    @if($returPembelian->purchaseOrder)

                        <a
                            href="{{ route(
                                'purchase-order.show',
                                $returPembelian->purchaseOrder
                            ) }}"
                            class="mt-1 inline-flex text-sm font-semibold text-[#15235F] hover:text-[#32AAA6]"
                        >

                            {{ $returPembelian
                                ->purchaseOrder
                                ->nomor_po
                            }}

                        </a>

                    @else

                        <p class="mt-1 text-sm text-slate-400">
                            -
                        </p>

                    @endif

                </div>


                {{-- GUDANG --}}
                <div>

                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Gudang
                    </p>

                    <p class="mt-1 text-sm font-medium text-slate-700">

                        {{ $returPembelian
                            ->gudang
                            ?->nama_gudang
                            ?? '-'
                        }}

                    </p>

                </div>


                {{-- CREATED BY --}}
                <div>

                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Dibuat Oleh
                    </p>

                    <p class="mt-1 text-sm text-slate-700">

                        {{ $returPembelian
                            ->creator
                            ?->name
                            ?? '-'
                        }}

                    </p>

                </div>

            </div>


            {{-- ALASAN --}}
            <div class="mt-6 border-t border-slate-100 pt-5">

                <p class="text-xs uppercase tracking-wide text-slate-400">
                    Alasan Retur
                </p>

                <p class="mt-2 text-sm font-medium text-slate-700">
                    {{ $returPembelian->alasan_retur }}
                </p>


                <p class="mt-5 text-xs uppercase tracking-wide text-slate-400">
                    Catatan
                </p>

                <p class="mt-2 text-sm text-slate-600">

                    {{ $returPembelian->catatan ?: '-' }}

                </p>

            </div>

        </div>


        {{-- RINGKASAN --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <h3 class="mb-5 text-sm font-semibold text-slate-700">
                Ringkasan
            </h3>


            <div class="space-y-4">

                <div class="flex justify-between text-sm">

                    <span class="text-slate-500">
                        Jenis Barang
                    </span>

                    <span class="font-semibold text-slate-700">

                        {{ $returPembelian
                            ->details
                            ->count()
                        }}

                    </span>

                </div>


                <div class="flex justify-between text-sm">

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
                        Nilai Retur
                    </p>

                    <p class="mt-1 text-xl font-bold text-[#15235F]">

                        Rp {{ number_format(
                            $totalNilai,
                            0,
                            ',',
                            '.'
                        ) }}

                    </p>

                </div>

            </div>

        </div>

    </div>


    {{-- DETAIL BARANG --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-200 px-6 py-4">

            <h3 class="text-sm font-semibold text-slate-700">
                Detail Barang
            </h3>

            <p class="mt-1 text-xs text-slate-400">
                Barang yang dikembalikan kepada supplier.
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
                            Qty
                        </th>

                        <th class="px-5 py-3 text-right font-medium">
                            Harga Pembelian
                        </th>

                        <th class="px-5 py-3 text-right font-medium">
                            Subtotal
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Keterangan
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-slate-100">

                    @forelse($returPembelian->details as $detail)

                        <tr class="transition hover:bg-slate-50/70">

                            {{-- NO --}}
                            <td class="px-5 py-4 text-slate-500">

                                {{ $loop->iteration }}

                            </td>


                            {{-- BARANG --}}
                            <td class="px-5 py-4">

                                <p class="font-semibold text-slate-700">

                                    {{ $detail
                                        ->barang
                                        ?->kode_barang
                                        ?? '-'
                                    }}

                                    -

                                    {{ $detail
                                        ->barang
                                        ?->nama_barang
                                        ?? '-'
                                    }}

                                </p>


                                @if($detail->barang?->spesifikasi)

                                    <p class="mt-1 text-xs text-slate-400">

                                        {{ $detail
                                            ->barang
                                            ->spesifikasi
                                        }}

                                    </p>

                                @endif

                            </td>


                            {{-- SATUAN --}}
                            <td class="px-5 py-4 text-slate-500">

                                {{ $detail
                                    ->barang
                                    ?->satuan
                                    ?->nama_satuan
                                    ?? '-'
                                }}

                            </td>


                            {{-- QTY --}}
                            <td class="px-5 py-4 text-right font-semibold text-slate-700">

                                {{ number_format(
                                    $detail->qty,
                                    2,
                                    ',',
                                    '.'
                                ) }}

                            </td>


                            {{-- HARGA --}}
                            <td class="px-5 py-4 text-right text-slate-600">

                                Rp {{ number_format(
                                    $detail->harga_satuan,
                                    0,
                                    ',',
                                    '.'
                                ) }}

                            </td>


                            {{-- SUBTOTAL --}}
                            <td class="px-5 py-4 text-right font-semibold text-slate-700">

                                Rp {{ number_format(
                                    $detail->subtotal,
                                    0,
                                    ',',
                                    '.'
                                ) }}

                            </td>


                            {{-- KETERANGAN --}}
                            <td class="px-5 py-4 text-slate-500">

                                {{ $detail->keterangan ?: '-' }}

                            </td>

                        </tr>


                    @empty

                        <tr>

                            <td
                                colspan="7"
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


    {{-- INFO DRAFT --}}
    @if($returPembelian->status === 'DRAFT')

        <div class="rounded-xl border border-amber-200 bg-amber-50 px-5 py-4">

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                <div>

                    <p class="text-sm font-semibold text-amber-800">
                        Retur masih berupa Draft
                    </p>

                    <p class="mt-1 text-xs leading-5 text-amber-700">
                        Stok belum berubah. Setelah barang benar-benar dikembalikan kepada supplier, proses retur untuk mengurangi stok gudang.
                    </p>

                </div>


                <form
                    method="POST"
                    action="{{ route(
                        'retur-pembelian.proses',
                        $returPembelian
                    ) }}"
                    onsubmit="return confirm('Yakin proses retur ini? Stok gudang akan berkurang.')"
                >

                    @csrf
                    @method('PATCH')


                    <button
                        type="submit"
                        class="whitespace-nowrap rounded-lg bg-[#15235F] px-5 py-2.5 text-sm font-medium text-white transition hover:bg-[#101b4d]"
                    >
                        Proses Retur
                    </button>

                </form>

            </div>

        </div>

    @endif


    {{-- INFO SELESAI --}}
    @if($returPembelian->status === 'SELESAI')

        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-5 py-4">

            <p class="text-sm font-semibold text-emerald-800">
                Retur telah selesai diproses
            </p>

            <p class="mt-1 text-xs leading-5 text-emerald-700">
                Stok gudang sudah berkurang dan transaksi sudah tercatat pada Kartu Stok sebagai RETUR KELUAR.
            </p>

        </div>

    @endif

</div>

@endsection