@extends('layouts.app')

@section('title', 'Detail Penyesuaian Stok')

@section('content')

<div class="mx-auto max-w-6xl space-y-6">

    <div>

        <a
            href="{{ route('penyesuaian-stok.index') }}"
            class="mb-3 inline-flex items-center gap-2 text-sm text-slate-500 hover:text-[#15235F]"
        >
            ← Kembali
        </a>

        <h2 class="text-xl font-semibold text-slate-800">
            {{ $penyesuaianStok->nomor_penyesuaian }}
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            Detail transaksi penyesuaian persediaan.
        </p>

    </div>


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

        $totalTambah =
            $penyesuaianStok->items
                ->where('jenis', 'TAMBAH')
                ->sum(
                    fn($item) =>
                        (float) $item->nilai_penyesuaian
                );

        $totalKurang =
            $penyesuaianStok->items
                ->where('jenis', 'KURANG')
                ->sum(
                    fn($item) =>
                        (float) $item->nilai_penyesuaian
                );

        $nilaiNetto =
            $totalTambah - $totalKurang;

    @endphp


    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">

            <h3 class="mb-5 text-sm font-semibold text-slate-700">
                Informasi Penyesuaian
            </h3>


            <div class="grid grid-cols-1 gap-x-8 gap-y-5 md:grid-cols-2">

                <div>

                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Nomor Penyesuaian
                    </p>

                    <p class="mt-1 text-sm font-semibold text-[#15235F]">
                        {{ $penyesuaianStok->nomor_penyesuaian }}
                    </p>

                </div>


                <div>

                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Tanggal
                    </p>

                    <p class="mt-1 text-sm text-slate-700">
                        {{ $penyesuaianStok->tanggal_penyesuaian?->format('d/m/Y') ?? '-' }}
                    </p>

                </div>


                <div>

                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Stock Opname
                    </p>

                    <a
                        href="{{ route('stock-opname.show', $penyesuaianStok->stockOpname) }}"
                        class="mt-1 inline-flex text-sm font-semibold text-[#15235F] hover:text-[#32AAA6]"
                    >
                        {{ $penyesuaianStok->stockOpname?->nomor_opname ?? '-' }}
                    </a>

                </div>


                <div>

                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Gudang
                    </p>

                    <p class="mt-1 text-sm font-medium text-slate-700">
                        {{ $penyesuaianStok->gudang?->nama_gudang ?? '-' }}
                    </p>

                </div>


                <div>

                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Dibuat Oleh
                    </p>

                    <p class="mt-1 text-sm text-slate-700">
                        {{ $penyesuaianStok->creator?->name ?? '-' }}
                    </p>

                </div>

            </div>


            <div class="mt-6 border-t border-slate-100 pt-5">

                <p class="text-xs uppercase tracking-wide text-slate-400">
                    Catatan
                </p>

                <p class="mt-2 text-sm text-slate-600">
                    {{ $penyesuaianStok->catatan ?: '-' }}
                </p>

            </div>

        </div>


        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <h3 class="mb-5 text-sm font-semibold text-slate-700">
                Ringkasan Nilai
            </h3>


            <div class="space-y-4">

                <div class="flex justify-between text-sm">

                    <span class="text-slate-500">
                        Barang Disesuaikan
                    </span>

                    <span class="font-semibold text-slate-700">
                        {{ $penyesuaianStok->items->count() }}
                    </span>

                </div>


                <div class="flex justify-between text-sm">

                    <span class="text-slate-500">
                        Penambahan
                    </span>

                    <span class="font-semibold text-emerald-600">
                        + Rp {{ number_format(
                            $totalTambah,
                            0,
                            ',',
                            '.'
                        ) }}
                    </span>

                </div>


                <div class="flex justify-between text-sm">

                    <span class="text-slate-500">
                        Pengurangan
                    </span>

                    <span class="font-semibold text-red-500">
                        - Rp {{ number_format(
                            $totalKurang,
                            0,
                            ',',
                            '.'
                        ) }}
                    </span>

                </div>


                <div class="border-t border-slate-200 pt-4">

                    <p class="text-xs text-slate-400">
                        Nilai Netto
                    </p>

                    <p class="mt-1 text-xl font-bold {{ $nilaiNetto < 0 ? 'text-red-500' : ($nilaiNetto > 0 ? 'text-emerald-600' : 'text-[#15235F]') }}">

                        {{ $nilaiNetto > 0 ? '+' : '' }}

                        Rp {{ number_format(
                            $nilaiNetto,
                            0,
                            ',',
                            '.'
                        ) }}

                    </p>

                </div>

            </div>

        </div>

    </div>


    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-200 px-6 py-4">

            <h3 class="text-sm font-semibold text-slate-700">
                Detail Penyesuaian
            </h3>

        </div>


        <div class="overflow-x-auto">

            <table class="w-full min-w-[1200px] text-sm">

                <thead class="bg-slate-50">

                    <tr class="text-left text-xs uppercase tracking-wide text-slate-400">

                        <th class="px-5 py-3">
                            Barang
                        </th>

                        <th class="px-5 py-3">
                            Jenis
                        </th>

                        <th class="px-5 py-3 text-right">
                            Sebelum
                        </th>

                        <th class="px-5 py-3 text-right">
                            Penyesuaian
                        </th>

                        <th class="px-5 py-3 text-right">
                            Sesudah
                        </th>

                        <th class="px-5 py-3">
                            Satuan
                        </th>

                        <th class="px-5 py-3 text-right">
                            Avg Cost
                        </th>

                        <th class="px-5 py-3 text-right">
                            Nilai
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-slate-100">

                    @foreach($penyesuaianStok->items as $item)

                        <tr>

                            <td class="px-5 py-4">

                                <p class="font-semibold text-slate-700">
                                    {{ $item->barang?->kode_barang }}
                                    -
                                    {{ $item->barang?->nama_barang }}
                                </p>

                                @if($item->barang?->spesifikasi)

                                    <p class="mt-1 text-xs text-slate-400">
                                        {{ $item->barang->spesifikasi }}
                                    </p>

                                @endif

                            </td>


                            <td class="px-5 py-4">

                                @if($item->jenis === 'TAMBAH')

                                    <span class="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                        TAMBAH
                                    </span>

                                @else

                                    <span class="rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-600">
                                        KURANG
                                    </span>

                                @endif

                            </td>


                            <td class="px-5 py-4 text-right text-slate-600">

                                {{ number_format(
                                    $item->qty_sebelum,
                                    2,
                                    ',',
                                    '.'
                                ) }}

                            </td>


                            <td class="px-5 py-4 text-right font-semibold {{ $item->jenis === 'TAMBAH' ? 'text-emerald-600' : 'text-red-500' }}">

                                {{ $item->jenis === 'TAMBAH' ? '+' : '-' }}

                                {{ number_format(
                                    $item->qty_penyesuaian,
                                    2,
                                    ',',
                                    '.'
                                ) }}

                            </td>


                            <td class="px-5 py-4 text-right font-semibold text-slate-700">

                                {{ number_format(
                                    $item->qty_sesudah,
                                    2,
                                    ',',
                                    '.'
                                ) }}

                            </td>


                            <td class="px-5 py-4 text-slate-500">

                                {{ $item->barang?->satuan?->nama_satuan ?? '-' }}

                            </td>


                            <td class="px-5 py-4 text-right text-slate-600">

                                Rp {{ number_format(
                                    $item->harga_rata_rata,
                                    2,
                                    ',',
                                    '.'
                                ) }}

                            </td>


                            <td class="px-5 py-4 text-right font-semibold {{ $item->jenis === 'TAMBAH' ? 'text-emerald-600' : 'text-red-500' }}">

                                {{ $item->jenis === 'TAMBAH' ? '+' : '-' }}

                                Rp {{ number_format(
                                    $item->nilai_penyesuaian,
                                    0,
                                    ',',
                                    '.'
                                ) }}

                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    </div>


    <div class="rounded-xl border border-blue-100 bg-blue-50 px-5 py-4">

        <p class="text-sm font-medium text-blue-800">
            Penyesuaian stok sudah diproses
        </p>

        <p class="mt-1 text-xs leading-5 text-blue-600">
            Saldo persediaan dan Kartu Stok sudah diperbarui. Transaksi ini tidak dapat diedit atau dihapus langsung untuk menjaga riwayat persediaan.
        </p>

    </div>

</div>

@endsection