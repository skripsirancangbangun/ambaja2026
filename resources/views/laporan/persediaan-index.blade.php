@extends('layouts.app')

@section('title', 'Laporan Persediaan')

@section('content')

<div class="space-y-6">

    {{-- HEADER --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>
            <h2 class="text-xl font-semibold text-slate-800">
                Laporan Persediaan
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Lihat posisi stok saat ini dan mutasi persediaan berdasarkan periode.
            </p>
        </div>

    </div>


    {{-- ERROR VALIDASI --}}
    @if($errors->any())

        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3">

            <p class="text-sm font-medium text-red-700">
                Terdapat data yang perlu diperbaiki.
            </p>

            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-600">
                @foreach($errors->all() as $error)
                    <li>
                        {{ $error }}
                    </li>
                @endforeach
            </ul>

        </div>

    @endif


    {{-- TAB --}}
    <div class="border-b border-slate-200">

        <div class="flex gap-6">

            <a
                href="{{ route('laporan.persediaan', array_merge(request()->except(['tab', 'posisi_page', 'tanggal_mulai', 'tanggal_selesai']), ['tab' => 'posisi'])) }}"
                class="border-b-2 px-1 pb-3 text-sm font-medium transition
                    {{ $tab === 'posisi'
                        ? 'border-[#15235F] text-[#15235F]'
                        : 'border-transparent text-slate-400 hover:text-slate-600'
                    }}"
            >
                Posisi Persediaan
            </a>

            <a
                href="{{ route('laporan.persediaan', array_merge(request()->except(['tab', 'posisi_page', 'status']), ['tab' => 'mutasi'])) }}"
                class="border-b-2 px-1 pb-3 text-sm font-medium transition
                    {{ $tab === 'mutasi'
                        ? 'border-[#15235F] text-[#15235F]'
                        : 'border-transparent text-slate-400 hover:text-slate-600'
                    }}"
            >
                Mutasi Persediaan
            </a>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- POSISI PERSEDIAAN --}}
    {{-- ========================================================= --}}

    @if($tab === 'posisi')

        {{-- FILTER --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

            <form
                method="GET"
                action="{{ route('laporan.persediaan') }}"
                class="space-y-4"
            >

                <input
                    type="hidden"
                    name="tab"
                    value="posisi"
                >

                <div class="grid grid-cols-1 gap-4 lg:grid-cols-4">

                    {{-- SEARCH --}}
                    <div class="lg:col-span-2">

                        <label
                            for="search"
                            class="mb-2 block text-sm font-medium text-slate-700"
                        >
                            Pencarian
                        </label>

                        <input
                            type="text"
                            id="search"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Kode, nama barang, spesifikasi, merek..."
                            class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-700 outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                        >

                    </div>


                    {{-- GUDANG --}}
                    <div>

                        <label
                            for="gudang_id"
                            class="mb-2 block text-sm font-medium text-slate-700"
                        >
                            Gudang
                        </label>

                        <select
                            id="gudang_id"
                            name="gudang_id"
                            class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-700 outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                        >

                            <option value="">
                                Semua Gudang
                            </option>

                            @foreach($gudangs as $gudang)

                                <option
                                    value="{{ $gudang->id }}"
                                    {{ (string) request('gudang_id') === (string) $gudang->id ? 'selected' : '' }}
                                >
                                    {{ $gudang->kode_gudang }}
                                    -
                                    {{ $gudang->nama_gudang }}
                                </option>

                            @endforeach

                        </select>

                    </div>


                    {{-- STATUS --}}
                    <div>

                        <label
                            for="status"
                            class="mb-2 block text-sm font-medium text-slate-700"
                        >
                            Status Stok
                        </label>

                        <select
                            id="status"
                            name="status"
                            class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-700 outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
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
                                Menipis
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


                <div class="flex flex-wrap justify-end gap-2">

                    <a
                        href="{{ route('laporan.persediaan', ['tab' => 'posisi']) }}"
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


        {{-- SUMMARY --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                    Jenis Stok Tersedia
                </p>

                <p class="mt-2 text-2xl font-semibold text-slate-800">
                    {{ number_format($totalJenisStok, 0, ',', '.') }}
                </p>

            </div>


            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                    Total Qty
                </p>

                <p class="mt-2 text-2xl font-semibold text-slate-800">
                    {{ number_format($totalQty, 2, ',', '.') }}
                </p>

            </div>


            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                    Nilai Persediaan
                </p>

                <p class="mt-2 text-2xl font-semibold text-slate-800">
                    Rp {{ number_format($totalNilai, 0, ',', '.') }}
                </p>

            </div>


            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                    Stok Habis
                </p>

                <p class="mt-2 text-2xl font-semibold text-red-500">
                    {{ number_format($stokHabis, 0, ',', '.') }}
                </p>

            </div>

        </div>


        {{-- EXPORT --}}
        <div class="flex flex-wrap justify-end gap-2">

            <a
                href="{{ route('laporan.persediaan.pdf', request()->query()) }}"
                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
            >
                Export PDF
            </a>

            <a
                href="{{ route('laporan.persediaan.excel', request()->query()) }}"
                class="rounded-lg bg-[#32AAA6] px-4 py-2 text-sm font-medium text-white transition hover:bg-teal-600"
            >
                Export Excel
            </a>

        </div>


        {{-- TABLE POSISI --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-6 py-4">

                <h3 class="text-sm font-semibold text-slate-700">
                    Posisi Persediaan
                </h3>

                <p class="mt-1 text-xs text-slate-400">
                    Menampilkan saldo stok terkini per barang dan gudang.
                </p>

            </div>


            <div class="overflow-x-auto">

                <table class="w-full min-w-[1350px] text-sm">

                    <thead class="bg-slate-50">

                        <tr class="text-left text-xs uppercase tracking-wide text-slate-400">

                            <th class="px-4 py-3 font-medium">
                                Kode
                            </th>

                            <th class="px-4 py-3 font-medium">
                                Barang
                            </th>

                            <th class="px-4 py-3 font-medium">
                                Spesifikasi
                            </th>

                            <th class="px-4 py-3 font-medium">
                                Kategori
                            </th>

                            <th class="px-4 py-3 font-medium">
                                Gudang
                            </th>

                            <th class="px-4 py-3 font-medium">
                                Satuan
                            </th>

                            <th class="px-4 py-3 text-right font-medium">
                                Qty
                            </th>

                            <th class="px-4 py-3 text-right font-medium">
                                Harga Rata-rata
                            </th>

                            <th class="px-4 py-3 text-right font-medium">
                                Nilai Persediaan
                            </th>

                            <th class="px-4 py-3 font-medium">
                                Status
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-slate-100">

                        @forelse($stokBarangs as $stok)

                            @php

                                $qty = (float) $stok->qty;

                                $minimumStok =
                                    (float) (
                                        $stok->barang?->minimum_stok
                                        ?? 0
                                    );

                                if ($qty <= 0) {
                                    $statusStok = 'Habis';
                                } elseif (
                                    $minimumStok > 0
                                    &&
                                    $qty <= $minimumStok
                                ) {
                                    $statusStok = 'Menipis';
                                } else {
                                    $statusStok = 'Tersedia';
                                }

                            @endphp


                            <tr class="transition hover:bg-slate-50/70">

                                <td class="px-4 py-4 font-medium text-slate-700">
                                    {{ $stok->barang?->kode_barang ?? '-' }}
                                </td>

                                <td class="px-4 py-4">

                                    <p class="font-medium text-slate-700">
                                        {{ $stok->barang?->nama_barang ?? '-' }}
                                    </p>

                                    @if($stok->barang?->merek)

                                        <p class="mt-1 text-xs text-slate-400">
                                            {{ $stok->barang->merek }}
                                        </p>

                                    @endif

                                </td>

                                <td class="px-4 py-4 text-slate-500">
                                    {{ $stok->barang?->spesifikasi ?: '-' }}
                                </td>

                                <td class="px-4 py-4 text-slate-500">
                                    {{ $stok->barang?->kategori?->nama_kategori ?? '-' }}
                                </td>

                                <td class="px-4 py-4 text-slate-600">

                                    <p class="font-medium">
                                        {{ $stok->gudang?->nama_gudang ?? '-' }}
                                    </p>

                                    @if($stok->gudang?->kode_gudang)

                                        <p class="mt-1 text-xs text-slate-400">
                                            {{ $stok->gudang->kode_gudang }}
                                        </p>

                                    @endif

                                </td>

                                <td class="px-4 py-4 text-slate-500">
                                    {{ $stok->barang?->satuan?->nama_satuan ?? '-' }}
                                </td>

                                <td class="px-4 py-4 text-right font-semibold text-slate-700">
                                    {{ number_format($stok->qty, 2, ',', '.') }}
                                </td>

                                <td class="px-4 py-4 text-right text-slate-600">
                                    Rp {{ number_format($stok->harga_rata_rata, 0, ',', '.') }}
                                </td>

                                <td class="px-4 py-4 text-right font-semibold text-slate-700">
                                    Rp {{ number_format($stok->nilai_persediaan, 0, ',', '.') }}
                                </td>

                                <td class="px-4 py-4">

                                    @if($statusStok === 'Tersedia')

                                        <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-600">
                                            Tersedia
                                        </span>

                                    @elseif($statusStok === 'Menipis')

                                        <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-600">
                                            Menipis
                                        </span>

                                    @else

                                        <span class="inline-flex rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-500">
                                            Habis
                                        </span>

                                    @endif

                                </td>

                            </tr>


                        @empty

                            <tr>

                                <td
                                    colspan="10"
                                    class="px-6 py-14 text-center"
                                >

                                    <p class="text-sm font-medium text-slate-500">
                                        Data persediaan tidak ditemukan.
                                    </p>

                                    <p class="mt-1 text-xs text-slate-400">
                                        Coba ubah filter pencarian.
                                    </p>

                                </td>

                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>


            @if($stokBarangs->hasPages())

                <div class="border-t border-slate-200 px-5 py-4">
                    {{ $stokBarangs->links() }}
                </div>

            @endif

        </div>


    {{-- ========================================================= --}}
    {{-- MUTASI PERSEDIAAN --}}
    {{-- ========================================================= --}}

    @elseif($tab === 'mutasi')

        {{-- FILTER --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

            <form
                method="GET"
                action="{{ route('laporan.persediaan') }}"
                class="space-y-4"
            >

                <input
                    type="hidden"
                    name="tab"
                    value="mutasi"
                >


                <div class="grid grid-cols-1 gap-4 lg:grid-cols-5">

                    {{-- SEARCH --}}
                    <div class="lg:col-span-2">

                        <label
                            for="search"
                            class="mb-2 block text-sm font-medium text-slate-700"
                        >
                            Pencarian
                        </label>

                        <input
                            type="text"
                            id="search"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Kode, nama barang, spesifikasi, merek..."
                            class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-700 outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                        >

                    </div>


                    {{-- GUDANG --}}
                    <div>

                        <label
                            for="gudang_id"
                            class="mb-2 block text-sm font-medium text-slate-700"
                        >
                            Gudang
                        </label>

                        <select
                            id="gudang_id"
                            name="gudang_id"
                            class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-700 outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                        >

                            <option value="">
                                Semua Gudang
                            </option>

                            @foreach($gudangs as $gudang)

                                <option
                                    value="{{ $gudang->id }}"
                                    {{ (string) request('gudang_id') === (string) $gudang->id ? 'selected' : '' }}
                                >
                                    {{ $gudang->kode_gudang }}
                                    -
                                    {{ $gudang->nama_gudang }}
                                </option>

                            @endforeach

                        </select>

                    </div>


                    {{-- TANGGAL MULAI --}}
                    <div>

                        <label
                            for="tanggal_mulai"
                            class="mb-2 block text-sm font-medium text-slate-700"
                        >
                            Dari Tanggal
                        </label>

                        <input
                            type="date"
                            id="tanggal_mulai"
                            name="tanggal_mulai"
                            value="{{ $tanggalMulai }}"
                            class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-700 outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                        >

                    </div>


                    {{-- TANGGAL SELESAI --}}
                    <div>

                        <label
                            for="tanggal_selesai"
                            class="mb-2 block text-sm font-medium text-slate-700"
                        >
                            Sampai Tanggal
                        </label>

                        <input
                            type="date"
                            id="tanggal_selesai"
                            name="tanggal_selesai"
                            value="{{ $tanggalSelesai }}"
                            class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-700 outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                        >

                    </div>

                </div>


                <div class="flex flex-wrap justify-end gap-2">

                    <a
                        href="{{ route('laporan.persediaan', ['tab' => 'mutasi']) }}"
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


        {{-- PERIODE --}}
        <div class="rounded-xl border border-blue-100 bg-blue-50 px-5 py-4">

            <p class="text-sm font-medium text-blue-800">
                Periode Laporan
            </p>

            <p class="mt-1 text-xs text-blue-600">
                {{ date('d/m/Y', strtotime($tanggalMulai)) }}
                s/d
                {{ date('d/m/Y', strtotime($tanggalSelesai)) }}

                @if(request('gudang_id'))

                    @php
                        $selectedGudang =
                            $gudangs->firstWhere(
                                'id',
                                (int) request('gudang_id')
                            );
                    @endphp

                    @if($selectedGudang)
                        · {{ $selectedGudang->nama_gudang }}
                    @endif

                @else
                    · Semua Gudang
                @endif

            </p>

        </div>


        {{-- SUMMARY MUTASI --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                    Stok Awal
                </p>

                <p class="mt-2 text-2xl font-semibold text-slate-800">
                    {{ number_format($mutasiSummary['stok_awal'], 2, ',', '.') }}
                </p>

                <p class="mt-2 text-xs text-slate-400">
                    Rp {{ number_format($mutasiSummary['nilai_awal'], 0, ',', '.') }}
                </p>

            </div>


            <div class="rounded-2xl border border-emerald-100 bg-white p-5 shadow-sm">

                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                    Stok Masuk
                </p>

                <p class="mt-2 text-2xl font-semibold text-emerald-600">
                    +{{ number_format($mutasiSummary['stok_masuk'], 2, ',', '.') }}
                </p>

                <p class="mt-2 text-xs text-slate-400">
                    Rp {{ number_format($mutasiSummary['nilai_masuk'], 0, ',', '.') }}
                </p>

            </div>


            <div class="rounded-2xl border border-red-100 bg-white p-5 shadow-sm">

                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                    Stok Keluar
                </p>

                <p class="mt-2 text-2xl font-semibold text-red-500">
                    -{{ number_format($mutasiSummary['stok_keluar'], 2, ',', '.') }}
                </p>

                <p class="mt-2 text-xs text-slate-400">
                    Rp {{ number_format($mutasiSummary['nilai_keluar'], 0, ',', '.') }}
                </p>

            </div>


            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                    Stok Akhir
                </p>

                <p class="mt-2 text-2xl font-semibold text-[#15235F]">
                    {{ number_format($mutasiSummary['stok_akhir'], 2, ',', '.') }}
                </p>

                <p class="mt-2 text-xs font-medium text-slate-500">
                    Rp {{ number_format($mutasiSummary['nilai_akhir'], 0, ',', '.') }}
                </p>

            </div>

        </div>


        {{-- EXPORT --}}
        <div class="flex flex-wrap justify-end gap-2">

            <a
                href="{{ route('laporan.persediaan.mutasi.pdf', request()->query()) }}"
                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
            >
                Export PDF
            </a>

            <a
                href="{{ route('laporan.persediaan.mutasi.excel', request()->query()) }}"
                class="rounded-lg bg-[#32AAA6] px-4 py-2 text-sm font-medium text-white transition hover:bg-teal-600"
            >
                Export Excel
            </a>

        </div>


        {{-- TABLE MUTASI --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-6 py-4">

                <h3 class="text-sm font-semibold text-slate-700">
                    Mutasi Persediaan
                </h3>

                <p class="mt-1 text-xs text-slate-400">

                    @if(request('gudang_id'))
                        Menampilkan mutasi persediaan pada gudang yang dipilih.
                    @else
                        Menampilkan mutasi persediaan gabungan seluruh gudang.
                    @endif

                </p>

            </div>


            <div class="overflow-x-auto">

                <table class="w-full min-w-[1700px] text-sm">

                    <thead class="bg-slate-50">

                        <tr class="text-left text-xs uppercase tracking-wide text-slate-400">

                            <th class="px-4 py-3 font-medium">
                                Kode
                            </th>

                            <th class="px-4 py-3 font-medium">
                                Barang
                            </th>

                            <th class="px-4 py-3 font-medium">
                                Spesifikasi
                            </th>

                            <th class="px-4 py-3 font-medium">
                                Satuan
                            </th>

                            <th class="px-4 py-3 text-right font-medium">
                                Stok Awal
                            </th>

                            <th class="px-4 py-3 text-right font-medium">
                                Nilai Awal
                            </th>

                            <th class="px-4 py-3 text-right font-medium">
                                Masuk
                            </th>

                            <th class="px-4 py-3 text-right font-medium">
                                Nilai Masuk
                            </th>

                            <th class="px-4 py-3 text-right font-medium">
                                Keluar
                            </th>

                            <th class="px-4 py-3 text-right font-medium">
                                Nilai Keluar
                            </th>

                            <th class="px-4 py-3 text-right font-medium">
                                Stok Akhir
                            </th>

                            <th class="px-4 py-3 text-right font-medium">
                                Nilai Akhir
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-slate-100">

                        @forelse($mutasiPersediaan as $item)

                            <tr class="transition hover:bg-slate-50/70">

                                <td class="px-4 py-4 font-medium text-slate-700">
                                    {{ $item->kode_barang }}
                                </td>

                                <td class="px-4 py-4 font-medium text-slate-700">
                                    {{ $item->nama_barang }}
                                </td>

                                <td class="px-4 py-4 text-slate-500">
                                    {{ $item->spesifikasi ?: '-' }}
                                </td>

                                <td class="px-4 py-4 text-slate-500">
                                    {{ $item->satuan ?: '-' }}
                                </td>

                                <td class="px-4 py-4 text-right text-slate-600">
                                    {{ number_format($item->stok_awal, 2, ',', '.') }}
                                </td>

                                <td class="px-4 py-4 text-right text-slate-600">
                                    Rp {{ number_format($item->nilai_awal, 0, ',', '.') }}
                                </td>

                                <td class="px-4 py-4 text-right font-medium text-emerald-600">

                                    @if((float) $item->stok_masuk > 0)
                                        +
                                    @endif

                                    {{ number_format($item->stok_masuk, 2, ',', '.') }}

                                </td>

                                <td class="px-4 py-4 text-right text-emerald-600">
                                    Rp {{ number_format($item->nilai_masuk, 0, ',', '.') }}
                                </td>

                                <td class="px-4 py-4 text-right font-medium text-red-500">

                                    @if((float) $item->stok_keluar > 0)
                                        -
                                    @endif

                                    {{ number_format($item->stok_keluar, 2, ',', '.') }}

                                </td>

                                <td class="px-4 py-4 text-right text-red-500">
                                    Rp {{ number_format($item->nilai_keluar, 0, ',', '.') }}
                                </td>

                                <td class="px-4 py-4 text-right font-semibold text-slate-700">
                                    {{ number_format($item->stok_akhir, 2, ',', '.') }}
                                </td>

                                <td class="px-4 py-4 text-right font-semibold text-[#15235F]">
                                    Rp {{ number_format($item->nilai_akhir, 0, ',', '.') }}
                                </td>

                            </tr>


                        @empty

                            <tr>

                                <td
                                    colspan="12"
                                    class="px-6 py-14 text-center"
                                >

                                    <p class="text-sm font-medium text-slate-500">
                                        Tidak ada mutasi persediaan pada periode ini.
                                    </p>

                                    <p class="mt-1 text-xs text-slate-400">
                                        Coba ubah periode, gudang, atau pencarian barang.
                                    </p>

                                </td>

                            </tr>

                        @endforelse

                    </tbody>


                    @if($mutasiPersediaan->isNotEmpty())

                        <tfoot class="border-t-2 border-slate-200 bg-slate-50">

                            <tr class="font-semibold text-slate-700">

                                <td
                                    colspan="4"
                                    class="px-4 py-4 text-right"
                                >
                                    TOTAL
                                </td>

                                <td class="px-4 py-4 text-right">
                                    {{ number_format($mutasiSummary['stok_awal'], 2, ',', '.') }}
                                </td>

                                <td class="px-4 py-4 text-right">
                                    Rp {{ number_format($mutasiSummary['nilai_awal'], 0, ',', '.') }}
                                </td>

                                <td class="px-4 py-4 text-right text-emerald-600">
                                    {{ number_format($mutasiSummary['stok_masuk'], 2, ',', '.') }}
                                </td>

                                <td class="px-4 py-4 text-right text-emerald-600">
                                    Rp {{ number_format($mutasiSummary['nilai_masuk'], 0, ',', '.') }}
                                </td>

                                <td class="px-4 py-4 text-right text-red-500">
                                    {{ number_format($mutasiSummary['stok_keluar'], 2, ',', '.') }}
                                </td>

                                <td class="px-4 py-4 text-right text-red-500">
                                    Rp {{ number_format($mutasiSummary['nilai_keluar'], 0, ',', '.') }}
                                </td>

                                <td class="px-4 py-4 text-right">
                                    {{ number_format($mutasiSummary['stok_akhir'], 2, ',', '.') }}
                                </td>

                                <td class="px-4 py-4 text-right text-[#15235F]">
                                    Rp {{ number_format($mutasiSummary['nilai_akhir'], 0, ',', '.') }}
                                </td>

                            </tr>

                        </tfoot>

                    @endif

                </table>

            </div>

        </div>

    @endif

</div>

@endsection