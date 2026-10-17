@extends('layouts.app')

@section('title', 'Detail Konversi Barang')

@section('content')

<div class="mx-auto max-w-7xl space-y-6">

    {{-- HEADER --}}
    <div>

        <a
            href="{{ route('conversions.index') }}"
            class="mb-3 inline-flex items-center gap-2 text-sm text-slate-500 hover:text-[#15235F]"
        >
            ← Kembali ke Konversi Barang
        </a>

        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

            <div>

                <h2 class="text-xl font-semibold text-slate-800">
                    {{ $conversion->nomor_konversi }}
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Detail transaksi konversi barang.
                </p>

            </div>


            @if($conversion->status === 'POSTED')

                <span class="inline-flex w-fit rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700">
                    POSTED
                </span>

            @elseif($conversion->status === 'CANCELLED')

                <span class="inline-flex w-fit rounded-full bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-700">
                    CANCELLED
                </span>

            @else

                <span class="inline-flex w-fit rounded-full bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700">
                    {{ $conversion->status }}
                </span>

            @endif

        </div>

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


    {{-- ==========================================================
        INFORMASI
    =========================================================== --}}

    <div class="grid grid-cols-1 gap-4 md:grid-cols-4">

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                Nomor Konversi
            </p>

            <p class="mt-2 text-sm font-bold text-[#15235F]">
                {{ $conversion->nomor_konversi }}
            </p>

        </div>


        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                Tanggal
            </p>

            <p class="mt-2 text-sm font-bold text-slate-700">
                {{ $conversion->tanggal?->format('d/m/Y H:i') ?? '-' }}
            </p>

        </div>


        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                Gudang
            </p>

            <p class="mt-2 text-sm font-bold text-slate-700">
                {{ $conversion->gudang?->nama_gudang ?? '-' }}
            </p>

        </div>


        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                Total Nilai
            </p>

            <p class="mt-2 text-sm font-bold text-[#15235F]">

                Rp {{ number_format(
                    (float) $conversion->total_nilai,
                    0,
                    ',',
                    '.'
                ) }}

            </p>

        </div>

    </div>


    {{-- ==========================================================
        INPUT
    =========================================================== --}}

    @php

        $inputItems =
            $conversion->items
                ->where('tipe', 'INPUT');

        $outputItems =
            $conversion->items
                ->where('tipe', 'OUTPUT');

        $wasteItems =
            $conversion->items
                ->where('tipe', 'WASTE');

    @endphp


    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">

        <div class="border-b border-slate-200 px-6 py-4">

            <h3 class="text-sm font-semibold text-slate-700">
                Barang Asal
            </h3>

            <p class="mt-1 text-xs text-slate-400">
                Barang yang dikeluarkan dari persediaan untuk proses konversi.
            </p>

        </div>


        <div class="overflow-x-auto">

            <table class="w-full min-w-[900px] text-sm">

                <thead class="bg-slate-50">

                    <tr class="text-left text-xs uppercase tracking-wide text-slate-400">

                        <th class="px-5 py-3 font-medium">
                            Barang
                        </th>

                        <th class="px-5 py-3 text-right font-medium">
                            Qty
                        </th>

                        <th class="px-5 py-3 text-right font-medium">
                            HPP
                        </th>

                        <th class="px-5 py-3 text-right font-medium">
                            Nilai
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-slate-100">

                    @forelse($inputItems as $item)

                        <tr>

                            <td class="px-5 py-4">

                                <p class="font-medium text-slate-700">
                                    {{ $item->barang?->nama_barang ?? '-' }}
                                </p>

                                <p class="mt-0.5 text-xs text-slate-400">
                                    {{ $item->barang?->kode_barang ?? '-' }}
                                </p>

                            </td>


                            <td class="px-5 py-4 text-right text-slate-700">

                                {{ number_format(
                                    (float) $item->qty,
                                    2,
                                    ',',
                                    '.'
                                ) }}

                                {{ $item->satuan ?? '' }}

                            </td>


                            <td class="px-5 py-4 text-right text-slate-700">

                                Rp {{ number_format(
                                    (float) $item->hpp,
                                    0,
                                    ',',
                                    '.'
                                ) }}

                            </td>


                            <td class="px-5 py-4 text-right font-semibold text-slate-700">

                                Rp {{ number_format(
                                    (float) $item->nilai,
                                    0,
                                    ',',
                                    '.'
                                ) }}

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="4"
                                class="px-5 py-8 text-center text-sm text-slate-400"
                            >
                                Data barang asal tidak tersedia.
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>

    </div>


    {{-- ==========================================================
        OUTPUT
    =========================================================== --}}

    <div class="rounded-2xl border border-slate-200 bg-white shadow-sm overflow-hidden">

        <div class="border-b border-slate-200 px-6 py-4">

            <h3 class="text-sm font-semibold text-slate-700">
                Barang Hasil Konversi
            </h3>

            <p class="mt-1 text-xs text-slate-400">
                HPP barang hasil dihitung otomatis berdasarkan alokasi nilai
                persediaan barang asal.
            </p>

        </div>


        <div class="overflow-x-auto">

            <table class="w-full min-w-[1000px] text-sm">

                <thead class="bg-slate-50">

                    <tr class="text-left text-xs uppercase tracking-wide text-slate-400">

                        <th class="px-5 py-3 font-medium">
                            Barang
                        </th>

                        <th class="px-5 py-3 text-right font-medium">
                            Qty
                        </th>

                        <th class="px-5 py-3 text-right font-medium">
                            Panjang
                        </th>

                        <th class="px-5 py-3 text-right font-medium">
                            Berat
                        </th>

                        <th class="px-5 py-3 text-right font-medium">
                            HPP
                        </th>

                        <th class="px-5 py-3 text-right font-medium">
                            Nilai
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-slate-100">

                    @forelse($outputItems as $item)

                        <tr>

                            <td class="px-5 py-4">

                                <p class="font-medium text-slate-700">
                                    {{ $item->barang?->nama_barang ?? '-' }}
                                </p>

                                <p class="mt-0.5 text-xs text-slate-400">
                                    {{ $item->barang?->kode_barang ?? '-' }}
                                </p>

                            </td>


                            <td class="px-5 py-4 text-right text-slate-700">

                                {{ number_format(
                                    (float) $item->qty,
                                    2,
                                    ',',
                                    '.'
                                ) }}

                                {{ $item->satuan ?? '' }}

                            </td>


                            <td class="px-5 py-4 text-right text-slate-500">

                                @if($item->panjang)

                                    {{ number_format(
                                        (float) $item->panjang,
                                        4,
                                        ',',
                                        '.'
                                    ) }}

                                @else

                                    -

                                @endif

                            </td>


                            <td class="px-5 py-4 text-right text-slate-500">

                                @if($item->berat)

                                    {{ number_format(
                                        (float) $item->berat,
                                        4,
                                        ',',
                                        '.'
                                    ) }}

                                @else

                                    -

                                @endif

                            </td>


                            <td class="px-5 py-4 text-right font-semibold text-[#15235F]">

                                Rp {{ number_format(
                                    (float) $item->hpp,
                                    0,
                                    ',',
                                    '.'
                                ) }}

                            </td>


                            <td class="px-5 py-4 text-right font-semibold text-slate-700">

                                Rp {{ number_format(
                                    (float) $item->nilai,
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
                                class="px-5 py-8 text-center text-sm text-slate-400"
                            >
                                Tidak ada barang hasil.
                            </td>

                        </tr>

                    @endforelse

                </tbody>


                @if($outputItems->count() > 0)

                    <tfoot class="bg-slate-50">

                        <tr>

                            <td
                                colspan="5"
                                class="px-5 py-4 text-right text-sm font-semibold text-slate-600"
                            >
                                Total Nilai Output
                            </td>

                            <td class="px-5 py-4 text-right text-sm font-bold text-[#15235F]">

                                Rp {{ number_format(
                                    $outputItems->sum(
                                        fn($item) =>
                                            (float) $item->nilai
                                    ),
                                    0,
                                    ',',
                                    '.'
                                ) }}

                            </td>

                        </tr>

                    </tfoot>

                @endif

            </table>

        </div>

    </div>


    {{-- ==========================================================
        WASTE
    =========================================================== --}}

    @if($wasteItems->count() > 0)

        <div class="rounded-2xl border border-amber-200 bg-amber-50 shadow-sm overflow-hidden">

            <div class="border-b border-amber-200 px-6 py-4">

                <h3 class="text-sm font-semibold text-amber-800">
                    Waste / Sisa
                </h3>

            </div>


            <div class="overflow-x-auto">

                <table class="w-full text-sm">

                    <thead>

                        <tr class="text-left text-xs uppercase tracking-wide text-amber-600">

                            <th class="px-5 py-3">
                                Barang
                            </th>

                            <th class="px-5 py-3 text-right">
                                Qty
                            </th>

                            <th class="px-5 py-3 text-right">
                                Nilai
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-amber-100">

                        @foreach($wasteItems as $item)

                            <tr>

                                <td class="px-5 py-4">
                                    {{ $item->barang?->nama_barang ?? '-' }}
                                </td>

                                <td class="px-5 py-4 text-right">
                                    {{ number_format(
                                        (float) $item->qty,
                                        2,
                                        ',',
                                        '.'
                                    ) }}
                                </td>

                                <td class="px-5 py-4 text-right">
                                    Rp {{ number_format(
                                        (float) $item->nilai,
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

    @endif


    {{-- ==========================================================
        KETERANGAN
    =========================================================== --}}

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <h3 class="text-sm font-semibold text-slate-700">
                Keterangan
            </h3>

            <p class="mt-3 text-sm leading-6 text-slate-600">

                {{ $conversion->keterangan ?: 'Tidak ada keterangan.' }}

            </p>

        </div>


        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <h3 class="text-sm font-semibold text-slate-700">
                Informasi Proses
            </h3>


            <dl class="mt-4 space-y-3 text-sm">

                <div class="flex justify-between gap-4">

                    <dt class="text-slate-400">
                        Dibuat oleh
                    </dt>

                    <dd class="font-medium text-slate-700">
                        {{ $conversion->creator?->name ?? '-' }}
                    </dd>

                </div>


                <div class="flex justify-between gap-4">

                    <dt class="text-slate-400">
                        Diposting oleh
                    </dt>

                    <dd class="font-medium text-slate-700">
                        {{ $conversion->poster?->name ?? '-' }}
                    </dd>

                </div>


                <div class="flex justify-between gap-4">

                    <dt class="text-slate-400">
                        Waktu posting
                    </dt>

                    <dd class="font-medium text-slate-700">
                        {{ $conversion->posted_at?->format('d/m/Y H:i:s') ?? '-' }}
                    </dd>

                </div>


                <div class="flex justify-between gap-4 border-t border-slate-100 pt-3">

                    <dt class="font-semibold text-slate-600">
                        Total Nilai
                    </dt>

                    <dd class="font-bold text-[#15235F]">

                        Rp {{ number_format(
                            (float) $conversion->total_nilai,
                            0,
                            ',',
                            '.'
                        ) }}

                    </dd>

                </div>

            </dl>

        </div>

    </div>


    {{-- FOOTER BUTTON --}}
    <div class="flex justify-end">

        <a
            href="{{ route('conversions.index') }}"
            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
        >
            Kembali
        </a>

    </div>

</div>

@endsection