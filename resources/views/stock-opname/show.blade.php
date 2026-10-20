@extends('layouts.app')

@section('title', 'Detail Stock Opname')

@section('content')

<div class="mx-auto max-w-6xl space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

        <div>

            <a
                href="{{ route('stock-opname.index') }}"
                class="mb-3 inline-flex items-center gap-2 text-sm text-slate-500 hover:text-[#15235F]"
            >
                <x-icon name="arrow-left" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" /> Kembali
            </a>

            <h2 class="text-xl font-semibold text-slate-800">
                {{ $stockOpname->nomor_opname }}
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Detail hasil Stock Opname.
            </p>

        </div>


        @if($stockOpname->status === 'DRAFT')

            <a
                href="{{ route('stock-opname.edit', $stockOpname) }}"
                class="rounded-lg bg-[#15235F] px-4 py-2.5 text-sm font-medium text-white hover:bg-[#101b4d]"
            >
                Isi Stok Fisik
            </a>

        @endif

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
        $statusClass = match($stockOpname->status) {
            'DRAFT' => 'bg-slate-100 text-slate-600',
            'SELESAI' => 'bg-blue-50 text-blue-700',
            'DISESUAIKAN' => 'bg-emerald-50 text-emerald-700',
            default => 'bg-slate-100 text-slate-600',
        };

        $totalSelisihQty =
            $stockOpname->items->sum(
                fn($item) => (float) $item->selisih
            );

        $totalNilaiSelisih =
            $stockOpname->items->sum(
                fn($item) => (float) $item->nilai_selisih
            );

        $barangSelisih =
            $stockOpname->items->filter(
                fn($item) => (float) $item->selisih != 0
            )->count();
    @endphp


    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">

            <h3 class="mb-5 text-sm font-semibold text-slate-700">
                Informasi Stock Opname
            </h3>


            <div class="grid grid-cols-1 gap-x-8 gap-y-5 md:grid-cols-2">

                <div>

                    <p class="text-xs uppercase text-slate-400">
                        Nomor Opname
                    </p>

                    <p class="mt-1 text-sm font-semibold text-[#15235F]">
                        {{ $stockOpname->nomor_opname }}
                    </p>

                </div>


                <div>

                    <p class="text-xs uppercase text-slate-400">
                        Status
                    </p>

                    <span class="mt-1 inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $statusClass }}">
                        {{ $stockOpname->status }}
                    </span>

                </div>


                <div>

                    <p class="text-xs uppercase text-slate-400">
                        Tanggal
                    </p>

                    <p class="mt-1 text-sm text-slate-700">
                        {{ $stockOpname->tanggal_opname?->format('d/m/Y') ?? '-' }}
                    </p>

                </div>


                <div>

                    <p class="text-xs uppercase text-slate-400">
                        Gudang
                    </p>

                    <p class="mt-1 text-sm font-medium text-slate-700">
                        {{ $stockOpname->gudang?->nama_gudang ?? '-' }}
                    </p>

                </div>


                <div>

                    <p class="text-xs uppercase text-slate-400">
                        Dibuat Oleh
                    </p>

                    <p class="mt-1 text-sm text-slate-700">
                        {{ $stockOpname->creator?->name ?? '-' }}
                    </p>

                </div>

            </div>


            <div class="mt-6 border-t border-slate-100 pt-5">

                <p class="text-xs uppercase text-slate-400">
                    Catatan
                </p>

                <p class="mt-2 text-sm text-slate-600">
                    {{ $stockOpname->catatan ?: '-' }}
                </p>

            </div>

        </div>


        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <h3 class="mb-5 text-sm font-semibold text-slate-700">
                Ringkasan Selisih
            </h3>


            <div class="space-y-4">

                <div class="flex justify-between text-sm">

                    <span class="text-slate-500">
                        Barang Dicek
                    </span>

                    <span class="font-semibold text-slate-700">
                        {{ $stockOpname->items->count() }}
                    </span>

                </div>


                <div class="flex justify-between text-sm">

                    <span class="text-slate-500">
                        Barang Selisih
                    </span>

                    <span class="font-semibold text-amber-600">
                        {{ $barangSelisih }}
                    </span>

                </div>


                <div class="flex justify-between text-sm">

                    <span class="text-slate-500">
                        Selisih Qty Netto
                    </span>

                    <span class="font-semibold {{ $totalSelisihQty < 0 ? 'text-red-500' : ($totalSelisihQty > 0 ? 'text-emerald-600' : 'text-slate-700') }}">

                        {{ $totalSelisihQty > 0 ? '+' : '' }}
                        {{ number_format(
                            $totalSelisihQty,
                            2,
                            ',',
                            '.'
                        ) }}

                    </span>

                </div>


                <div class="border-t border-slate-200 pt-4">

                    <p class="text-xs text-slate-400">
                        Nilai Selisih Netto
                    </p>

                    <p class="mt-1 text-xl font-bold {{ $totalNilaiSelisih < 0 ? 'text-red-500' : ($totalNilaiSelisih > 0 ? 'text-emerald-600' : 'text-[#15235F]') }}">

                        {{ $totalNilaiSelisih > 0 ? '+' : '' }}

                        Rp {{ number_format(
                            $totalNilaiSelisih,
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
                Detail Hasil Opname
            </h3>

        </div>


        <div class="overflow-x-auto">

            <table class="w-full min-w-[1200px] text-sm">

                <thead class="bg-slate-50">

                    <tr class="text-left text-xs uppercase tracking-wide text-slate-400">

                        <th class="px-5 py-3">
                            Barang
                        </th>

                        <th class="px-5 py-3 text-right">
                            Sistem
                        </th>

                        <th class="px-5 py-3 text-right">
                            Fisik
                        </th>

                        <th class="px-5 py-3 text-right">
                            Selisih
                        </th>

                        <th class="px-5 py-3">
                            Satuan
                        </th>

                        <th class="px-5 py-3 text-right">
                            Avg Cost
                        </th>

                        <th class="px-5 py-3 text-right">
                            Nilai Selisih
                        </th>

                        <th class="px-5 py-3">
                            Keterangan
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-slate-100">

                    @foreach($stockOpname->items as $item)

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


                            <td class="px-5 py-4 text-right text-slate-600">

                                {{ number_format(
                                    $item->stok_sistem,
                                    2,
                                    ',',
                                    '.'
                                ) }}

                            </td>


                            <td class="px-5 py-4 text-right font-semibold text-slate-700">

                                {{ number_format(
                                    $item->stok_fisik,
                                    2,
                                    ',',
                                    '.'
                                ) }}

                            </td>


                            <td class="px-5 py-4 text-right">

                                <span class="font-semibold {{ (float)$item->selisih < 0 ? 'text-red-500' : ((float)$item->selisih > 0 ? 'text-emerald-600' : 'text-slate-400') }}">

                                    {{ (float)$item->selisih > 0 ? '+' : '' }}

                                    {{ number_format(
                                        $item->selisih,
                                        2,
                                        ',',
                                        '.'
                                    ) }}

                                </span>

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


                            <td class="px-5 py-4 text-right font-semibold {{ (float)$item->nilai_selisih < 0 ? 'text-red-500' : ((float)$item->nilai_selisih > 0 ? 'text-emerald-600' : 'text-slate-400') }}">

                                {{ (float)$item->nilai_selisih > 0 ? '+' : '' }}

                                Rp {{ number_format(
                                    $item->nilai_selisih,
                                    0,
                                    ',',
                                    '.'
                                ) }}

                            </td>


                            <td class="px-5 py-4 text-slate-500">

                                {{ $item->keterangan ?: '-' }}

                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    </div>


    @if($stockOpname->status === 'SELESAI' && $barangSelisih > 0)

        <div class="rounded-xl border border-amber-200 bg-amber-50 px-5 py-4">

            <p class="text-sm font-medium text-amber-800">
                Ditemukan selisih persediaan
            </p>

            <p class="mt-1 text-xs leading-5 text-amber-700">
                Stock Opname ini belum mengubah stok sistem. Selisih akan diproses melalui Penyesuaian Stok.
            </p>

        </div>

    @endif

</div>

@endsection