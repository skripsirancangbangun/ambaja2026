@extends('layouts.app')

@section('title', 'Stok Barang')

@section('content')

<div class="space-y-6">

    {{-- HEADER --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

        <div>

            <h2 class="text-xl font-semibold text-slate-800">
                Stok Barang
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Pantau stok dan nilai persediaan barang pada setiap gudang.
            </p>

        </div>

        <a
            href="{{ route('stok-barang.template-stok-awal') }}"
            class="inline-flex items-center justify-center rounded-lg border border-[#15235F] bg-white px-4 py-2.5 text-sm font-medium text-[#15235F] transition hover:bg-slate-50"
        >
            Download Template Stok Awal
        </a>

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

            <ul class="list-disc space-y-1 pl-5 text-xs text-red-600">

                @foreach($errors->all() as $error)

                    <li>
                        {{ $error }}
                    </li>

                @endforeach

            </ul>

        </div>

    @endif


    {{-- SUMMARY --}}
    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                Stok Aktif
            </p>

            <p class="mt-2 text-2xl font-bold text-[#15235F]">
                {{ number_format($totalJenisStok, 0, ',', '.') }}
            </p>

            <p class="mt-1 text-xs text-slate-400">
                Barang per gudang dengan stok tersedia
            </p>

        </div>


        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                Total Qty
            </p>

            <p class="mt-2 text-2xl font-bold text-[#15235F]">
                {{ number_format($totalQty, 2, ',', '.') }}
            </p>

            <p class="mt-1 text-xs text-slate-400">
                Total kuantitas seluruh gudang
            </p>

        </div>


        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                Nilai Persediaan
            </p>

            <p class="mt-2 text-2xl font-bold text-[#15235F]">
                Rp {{ number_format($totalNilai, 0, ',', '.') }}
            </p>

            <p class="mt-1 text-xs text-slate-400">
                Berdasarkan moving average
            </p>

        </div>

    </div>


    {{-- IMPORT STOK AWAL --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

        <div class="flex flex-col gap-5 lg:flex-row lg:items-end lg:justify-between">

            <div class="max-w-2xl">

                <h3 class="text-sm font-semibold text-slate-700">
                    Import Stok Awal
                </h3>

                <p class="mt-1 text-xs leading-5 text-slate-500">
                    Digunakan untuk memasukkan stok yang sudah dimiliki perusahaan
                    sebelum transaksi mulai dicatat di AMBAJA.
                </p>

                <div class="mt-3 rounded-lg bg-slate-50 px-4 py-3">

                    <p class="text-xs font-medium text-slate-600">
                        Format CSV:
                    </p>

                    <p class="mt-1 text-xs text-slate-500">
                        kode_barang, kode_gudang, qty, harga_satuan
                    </p>

                    <p class="mt-2 text-xs text-amber-700">
                        Stok awal hanya dapat diimport apabila barang pada gudang
                        tersebut belum memiliki riwayat mutasi stok.
                    </p>

                </div>

            </div>


            <form
                method="POST"
                action="{{ route('stok-barang.import-stok-awal') }}"
                enctype="multipart/form-data"
                class="w-full max-w-md"
                onsubmit="return confirm('Import stok awal sekarang? Pastikan file dan nilainya sudah benar.')"
            >

                @csrf

                <label
                    for="file_stok_awal"
                    class="mb-2 block text-xs font-medium text-slate-600"
                >
                    File CSV
                </label>

                <input
                    type="file"
                    id="file_stok_awal"
                    name="file_stok_awal"
                    accept=".csv,.txt"
                    required
                    class="block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-600 file:mr-4 file:rounded-md file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-xs file:font-medium file:text-slate-700"
                >

                <button
                    type="submit"
                    class="mt-3 w-full rounded-lg bg-[#15235F] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#101b4d]"
                >
                    Import Stok Awal
                </button>

            </form>

        </div>

    </div>


    {{-- FILTER --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

        <form
            method="GET"
            action="{{ route('stok-barang.index') }}"
        >

            <div class="grid grid-cols-1 gap-4 md:grid-cols-4">

                <div class="md:col-span-2">

                    <label
                        for="search"
                        class="mb-2 block text-xs font-medium text-slate-500"
                    >
                        Cari Barang
                    </label>

                    <input
                        type="text"
                        id="search"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Kode, nama, spesifikasi, atau merek..."
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-700 outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >

                </div>


                <div>

                    <label
                        for="gudang_id"
                        class="mb-2 block text-xs font-medium text-slate-500"
                    >
                        Gudang
                    </label>

                    <select
                        id="gudang_id"
                        name="gudang_id"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-700 outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >

                        <option value="">
                            Semua Gudang
                        </option>

                        @foreach($gudangs as $gudang)

                            <option
                                value="{{ $gudang->id }}"
                                {{ request('gudang_id') == $gudang->id ? 'selected' : '' }}
                            >
                                {{ $gudang->nama_gudang }}
                            </option>

                        @endforeach

                    </select>

                </div>


                <div>

                    <label
                        for="status"
                        class="mb-2 block text-xs font-medium text-slate-500"
                    >
                        Status Stok
                    </label>

                    <select
                        id="status"
                        name="status"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-700 outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >

                        <option value="">
                            Semua Status
                        </option>

                        <option
                            value="tersedia"
                            {{ request('status') === 'tersedia' ? 'selected' : '' }}
                        >
                            Tersedia
                        </option>

                        <option
                            value="menipis"
                            {{ request('status') === 'menipis' ? 'selected' : '' }}
                        >
                            Stok Menipis
                        </option>

                        <option
                            value="habis"
                            {{ request('status') === 'habis' ? 'selected' : '' }}
                        >
                            Habis
                        </option>

                    </select>

                </div>

            </div>


            <div class="mt-4 flex justify-end gap-2">

                <a
                    href="{{ route('stok-barang.index') }}"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
                >
                    Reset
                </a>

                <button
                    type="submit"
                    class="rounded-lg bg-[#15235F] px-4 py-2 text-sm font-medium text-white transition hover:bg-[#101b4d]"
                >
                    Terapkan Filter
                </button>

            </div>

        </form>

    </div>


    {{-- TABLE --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-200 px-6 py-4">

            <h3 class="text-sm font-semibold text-slate-700">
                Daftar Stok
            </h3>

            <p class="mt-1 text-xs text-slate-400">
                Menampilkan {{ $stokBarangs->total() }} data stok.
            </p>

        </div>


        <div class="overflow-x-auto">

            <table class="w-full min-w-[1200px] text-sm">

                <thead class="bg-slate-50">

                    <tr class="text-left text-xs uppercase tracking-wide text-slate-400">

                        <th class="px-5 py-3 font-medium">Barang</th>
                        <th class="px-5 py-3 font-medium">Kategori</th>
                        <th class="px-5 py-3 font-medium">Gudang</th>
                        <th class="px-5 py-3 text-right font-medium">Stok</th>
                        <th class="px-5 py-3 font-medium">Satuan</th>
                        <th class="px-5 py-3 text-right font-medium">Min. Stok</th>
                        <th class="px-5 py-3 text-right font-medium">Harga Rata-rata</th>
                        <th class="px-5 py-3 text-right font-medium">Nilai Persediaan</th>
                        <th class="px-5 py-3 text-center font-medium">Status</th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-slate-100">

                    @forelse($stokBarangs as $stok)

                        @php

                            $qty =
                                (float) $stok->qty;

                            $minStok =
                                (float) (
                                    $stok->barang?->min_stok
                                    ?? 0
                                );

                            if ($qty <= 0) {

                                $status =
                                    'Habis';

                                $statusClass =
                                    'bg-red-50 text-red-700';

                            } elseif (
                                $minStok > 0
                                &&
                                $qty <= $minStok
                            ) {

                                $status =
                                    'Menipis';

                                $statusClass =
                                    'bg-amber-50 text-amber-700';

                            } else {

                                $status =
                                    'Tersedia';

                                $statusClass =
                                    'bg-emerald-50 text-emerald-700';
                            }

                        @endphp


                        <tr class="transition hover:bg-slate-50/70">

                            <td class="px-5 py-4">

                                <p class="font-semibold text-slate-700">
                                    {{ $stok->barang?->kode_barang ?? '-' }}
                                </p>

                                <p class="mt-1 text-sm text-slate-600">
                                    {{ $stok->barang?->nama_barang ?? '-' }}
                                </p>

                                @if($stok->barang?->spesifikasi)

                                    <p class="mt-1 text-xs text-slate-400">

                                        {{ $stok->barang->spesifikasi }}

                                        @if($stok->barang->merek)
                                            - {{ $stok->barang->merek }}
                                        @endif

                                    </p>

                                @endif

                            </td>


                            <td class="px-5 py-4 text-slate-500">

                                {{ $stok->barang?->kategori?->nama_kategori ?? '-' }}

                            </td>


                            <td class="px-5 py-4">

                                <p class="font-medium text-slate-700">
                                    {{ $stok->gudang?->nama_gudang ?? '-' }}
                                </p>

                                @if($stok->gudang?->kode_gudang)

                                    <p class="mt-1 text-xs text-slate-400">
                                        {{ $stok->gudang->kode_gudang }}
                                    </p>

                                @endif

                            </td>


                            <td class="px-5 py-4 text-right">

                                <span class="font-semibold text-slate-700">
                                    {{ number_format(
                                        $stok->qty,
                                        2,
                                        ',',
                                        '.'
                                    ) }}
                                </span>

                            </td>


                            <td class="px-5 py-4 text-slate-500">

                                {{ $stok->barang?->satuan?->nama_satuan ?? '-' }}

                            </td>


                            <td class="px-5 py-4 text-right text-slate-500">

                                {{ number_format(
                                    $minStok,
                                    2,
                                    ',',
                                    '.'
                                ) }}

                            </td>


                            <td class="whitespace-nowrap px-5 py-4 text-right text-slate-600">

                                Rp {{ number_format(
                                    $stok->harga_rata_rata,
                                    2,
                                    ',',
                                    '.'
                                ) }}

                            </td>


                            <td class="whitespace-nowrap px-5 py-4 text-right font-semibold text-slate-700">

                                Rp {{ number_format(
                                    $stok->nilai_persediaan,
                                    0,
                                    ',',
                                    '.'
                                ) }}

                            </td>


                            <td class="px-5 py-4 text-center">

                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $statusClass }}">
                                    {{ $status }}
                                </span>

                            </td>

                        </tr>


                    @empty

                        <tr>

                            <td
                                colspan="9"
                                class="px-6 py-14 text-center"
                            >

                                <p class="text-sm font-medium text-slate-500">
                                    Belum ada stok barang.
                                </p>

                                <p class="mt-1 text-xs text-slate-400">
                                    Stok dapat berasal dari stok awal maupun transaksi penerimaan barang.
                                </p>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        @if($stokBarangs->hasPages())

            <div class="border-t border-slate-200 px-6 py-4">
                {{ $stokBarangs->links() }}
            </div>

        @endif

    </div>

</div>

@endsection