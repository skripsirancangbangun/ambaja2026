@extends('layouts.app')

@section('title', 'Konversi Barang')

@section('content')

<div class="space-y-6">

    {{-- HEADER --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>

            <h2 class="text-xl font-semibold text-slate-800">
                Konversi Barang
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Mengubah barang menjadi bentuk atau ukuran lain dengan HPP
                yang dihitung otomatis berdasarkan nilai persediaan barang asal.
            </p>

        </div>


        <a
            href="{{ route('conversions.create') }}"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#15235F] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#101b4d]"
        >
            + Buat Konversi
        </a>

    </div>


    {{-- SUCCESS --}}
    @if(session('success'))

        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>

    @endif


    {{-- ERROR --}}
    @if(session('error'))

        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>

    @endif


    {{-- TABLE --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-200 px-6 py-4">

            <h3 class="text-sm font-semibold text-slate-700">
                Riwayat Konversi Barang
            </h3>

            <p class="mt-1 text-xs text-slate-400">
                Total {{ $conversions->total() }} dokumen konversi.
            </p>

        </div>


        <div class="overflow-x-auto">

            <table class="w-full min-w-[1100px] text-sm">

                <thead class="bg-slate-50">

                    <tr class="text-left text-xs uppercase tracking-wide text-slate-400">

                        <th class="px-5 py-3 font-medium">
                            Nomor Konversi
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Tanggal
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Gudang
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Barang Asal
                        </th>

                        <th class="px-5 py-3 text-right font-medium">
                            Barang Hasil
                        </th>

                        <th class="px-5 py-3 text-right font-medium">
                            Total Nilai
                        </th>

                        <th class="px-5 py-3 text-center font-medium">
                            Status
                        </th>

                        <th class="px-5 py-3 text-right font-medium">
                            Aksi
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-slate-100">

                    @forelse($conversions as $conversion)

                        @php
                            $inputItem = $conversion->items
                                ->first(fn($item) => $item->tipe === 'INPUT');

                            $outputCount = $conversion->items
                                ->where('tipe', 'OUTPUT')
                                ->count();
                        @endphp


                        <tr class="transition hover:bg-slate-50/70">

                            {{-- NOMOR --}}
                            <td class="px-5 py-4">

                                <a
                                    href="{{ route('conversions.show', $conversion) }}"
                                    class="font-semibold text-[#15235F] hover:text-[#32AAA6]"
                                >
                                    {{ $conversion->nomor_konversi }}
                                </a>

                            </td>


                            {{-- TANGGAL --}}
                            <td class="whitespace-nowrap px-5 py-4 text-slate-500">

                                {{ $conversion->tanggal?->format('d/m/Y H:i') ?? '-' }}

                            </td>


                            {{-- GUDANG --}}
                            <td class="px-5 py-4">

                                <span class="font-medium text-slate-700">
                                    {{ $conversion->gudang?->nama_gudang ?? '-' }}
                                </span>

                            </td>


                            {{-- BARANG INPUT --}}
                            <td class="px-5 py-4">

                                @if($inputItem?->barang)

                                    <p class="font-medium text-slate-700">
                                        {{ $inputItem->barang->nama_barang }}
                                    </p>

                                    <p class="mt-0.5 text-xs text-slate-400">
                                        {{ $inputItem->barang->kode_barang }}
                                    </p>

                                    <p class="mt-1 text-xs text-slate-500">

                                        {{ number_format(
                                            (float) $inputItem->qty,
                                            2,
                                            ',',
                                            '.'
                                        ) }}

                                        {{ $inputItem->satuan ?? '' }}

                                    </p>

                                @else

                                    <span class="text-slate-400">
                                        -
                                    </span>

                                @endif

                            </td>


                            {{-- OUTPUT --}}
                            <td class="px-5 py-4 text-right">

                                <span class="font-semibold text-slate-700">
                                    {{ $outputCount }}
                                </span>

                                <span class="text-xs text-slate-400">
                                    barang
                                </span>

                            </td>


                            {{-- NILAI --}}
                            <td class="whitespace-nowrap px-5 py-4 text-right">

                                <span class="font-semibold text-slate-700">

                                    Rp {{ number_format(
                                        (float) $conversion->total_nilai,
                                        0,
                                        ',',
                                        '.'
                                    ) }}

                                </span>

                            </td>


                            {{-- STATUS --}}
                            <td class="px-5 py-4 text-center">

                                @if($conversion->status === 'POSTED')

                                    <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-[11px] font-medium text-emerald-700">
                                        POSTED
                                    </span>

                                @elseif($conversion->status === 'DRAFT')

                                    <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-[11px] font-medium text-amber-700">
                                        DRAFT
                                    </span>

                                @elseif($conversion->status === 'CANCELLED')

                                    <span class="inline-flex rounded-full bg-red-50 px-2.5 py-1 text-[11px] font-medium text-red-700">
                                        CANCELLED
                                    </span>

                                @else

                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-[11px] font-medium text-slate-600">
                                        {{ $conversion->status }}
                                    </span>

                                @endif

                            </td>


                            {{-- AKSI --}}
                            <td class="px-5 py-4">

                                <div class="flex justify-end">

                                    <a
                                        href="{{ route('conversions.show', $conversion) }}"
                                        class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-medium text-slate-600 transition hover:bg-slate-50"
                                    >
                                        Detail
                                    </a>

                                </div>

                            </td>

                        </tr>


                    @empty

                        <tr>

                            <td
                                colspan="8"
                                class="px-6 py-14 text-center"
                            >

                                <p class="text-sm font-medium text-slate-500">
                                    Belum ada transaksi konversi barang.
                                </p>

                                <p class="mt-1 text-xs text-slate-400">
                                    Klik tombol "Buat Konversi" untuk membuat transaksi konversi baru.
                                </p>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- PAGINATION --}}
        @if($conversions->hasPages())

            <div class="border-t border-slate-200 px-6 py-4">

                {{ $conversions->links() }}

            </div>

        @endif

    </div>

</div>

@endsection