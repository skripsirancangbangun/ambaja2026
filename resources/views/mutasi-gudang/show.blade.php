@extends('layouts.app')

@section('title', 'Detail Mutasi Gudang')

@section('content')

<div class="mx-auto max-w-6xl space-y-6">

    <div>

        <a
            href="{{ route('mutasi-gudang.index') }}"
            class="mb-3 inline-flex items-center gap-2 text-sm text-slate-500 hover:text-[#15235F]"
        >
            <x-icon name="arrow-left" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" /> Kembali
        </a>

        <h2 class="text-xl font-semibold text-slate-800">
            {{ $mutasiGudang->nomor_mutasi }}
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            Detail perpindahan stok antar gudang.
        </p>

    </div>


    @if(session('error'))

        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>

    @endif


    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm lg:col-span-2">

            <h3 class="mb-5 text-sm font-semibold text-slate-700">
                Informasi Mutasi
            </h3>


            <div class="grid grid-cols-1 gap-x-8 gap-y-5 md:grid-cols-2">

                <div>
                    <p class="text-xs uppercase text-slate-400">
                        Nomor Mutasi
                    </p>

                    <p class="mt-1 text-sm font-semibold text-[#15235F]">
                        {{ $mutasiGudang->nomor_mutasi }}
                    </p>
                </div>


                <div>
                    <p class="text-xs uppercase text-slate-400">
                        Tanggal
                    </p>

                    <p class="mt-1 text-sm text-slate-700">
                        {{ $mutasiGudang->tanggal_mutasi?->format('d/m/Y') ?? '-' }}
                    </p>
                </div>


                <div>
                    <p class="text-xs uppercase text-slate-400">
                        Gudang Asal
                    </p>

                    <p class="mt-1 text-sm font-medium text-slate-700">
                        {{ $mutasiGudang->gudangAsal?->nama_gudang ?? '-' }}
                    </p>
                </div>


                <div>
                    <p class="text-xs uppercase text-slate-400">
                        Gudang Tujuan
                    </p>

                    <p class="mt-1 text-sm font-medium text-slate-700">
                        {{ $mutasiGudang->gudangTujuan?->nama_gudang ?? '-' }}
                    </p>
                </div>


                <div>
                    <p class="text-xs uppercase text-slate-400">
                        Dibuat Oleh
                    </p>

                    <p class="mt-1 text-sm text-slate-700">
                        {{ $mutasiGudang->creator?->name ?? '-' }}
                    </p>
                </div>

            </div>


            <div class="mt-6 border-t border-slate-100 pt-5">

                <p class="text-xs uppercase text-slate-400">
                    Catatan
                </p>

                <p class="mt-2 text-sm text-slate-600">
                    {{ $mutasiGudang->catatan ?: '-' }}
                </p>

            </div>

        </div>


        @php
            $totalNilai =
                $mutasiGudang->items->sum(
                    fn($item) => (float) $item->nilai
                );

            $totalQty =
                $mutasiGudang->items->sum(
                    fn($item) => (float) $item->qty
                );
        @endphp


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
                        {{ $mutasiGudang->items->count() }}
                    </span>

                </div>


                <div class="flex justify-between text-sm">

                    <span class="text-slate-500">
                        Total Qty
                    </span>

                    <span class="font-semibold text-slate-700">
                        {{ number_format($totalQty, 2, ',', '.') }}
                    </span>

                </div>


                <div class="border-t border-slate-200 pt-4">

                    <p class="text-xs text-slate-400">
                        Nilai Mutasi
                    </p>

                    <p class="mt-1 text-xl font-bold text-[#15235F]">
                        Rp {{ number_format($totalNilai, 0, ',', '.') }}
                    </p>

                </div>

            </div>

        </div>

    </div>


    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-200 px-6 py-4">

            <h3 class="text-sm font-semibold text-slate-700">
                Detail Barang
            </h3>

        </div>


        <div class="overflow-x-auto">

            <table class="w-full min-w-[900px] text-sm">

                <thead class="bg-slate-50">

                    <tr class="text-left text-xs uppercase tracking-wide text-slate-400">
                        <th class="px-5 py-3">No</th>
                        <th class="px-5 py-3">Barang</th>
                        <th class="px-5 py-3">Satuan</th>
                        <th class="px-5 py-3 text-right">Qty</th>
                        <th class="px-5 py-3 text-right">Average Cost</th>
                        <th class="px-5 py-3 text-right">Nilai</th>
                    </tr>

                </thead>


                <tbody class="divide-y divide-slate-100">

                    @foreach($mutasiGudang->items as $item)

                        <tr>

                            <td class="px-5 py-4 text-slate-500">
                                {{ $loop->iteration }}
                            </td>


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


                            <td class="px-5 py-4 text-slate-500">
                                {{ $item->barang?->satuan?->nama_satuan ?? '-' }}
                            </td>


                            <td class="px-5 py-4 text-right font-semibold text-slate-700">
                                {{ number_format($item->qty, 2, ',', '.') }}
                            </td>


                            <td class="px-5 py-4 text-right text-slate-600">
                                Rp {{ number_format($item->harga_rata_rata, 2, ',', '.') }}
                            </td>


                            <td class="px-5 py-4 text-right font-semibold text-slate-700">
                                Rp {{ number_format($item->nilai, 0, ',', '.') }}
                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>

    </div>


    <div class="rounded-xl border border-blue-100 bg-blue-50 px-5 py-4">

        <p class="text-sm font-medium text-blue-800">
            Mutasi stok sudah dicatat
        </p>

        <p class="mt-1 text-xs leading-5 text-blue-600">
            Transaksi mutasi yang sudah disimpan tidak dapat diedit atau dihapus langsung agar riwayat persediaan tetap konsisten.
        </p>

    </div>

</div>

@endsection