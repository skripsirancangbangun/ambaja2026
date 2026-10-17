@extends('layouts.app')

@section('title', 'Kartu Stok')

@section('content')

<div class="space-y-6">

    {{-- HEADER --}}
    <div>
        <h2 class="text-xl font-semibold text-slate-800">
            Kartu Stok
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            Riwayat seluruh pergerakan stok barang pada setiap gudang.
        </p>
    </div>


    {{-- FILTER --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

        <form
            method="GET"
            action="{{ route('kartu-stok.index') }}"
        >

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-6">

                {{-- SEARCH --}}
                <div class="xl:col-span-2">

                    <label
                        for="search"
                        class="mb-2 block text-xs font-medium text-slate-500"
                    >
                        Cari
                    </label>

                    <input
                        type="text"
                        id="search"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Barang, referensi, keterangan..."
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >

                </div>


                {{-- BARANG --}}
                <div>

                    <label
                        for="barang_id"
                        class="mb-2 block text-xs font-medium text-slate-500"
                    >
                        Barang
                    </label>

                    <select
                        id="barang_id"
                        name="barang_id"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >

                        <option value="">
                            Semua Barang
                        </option>

                        @foreach($barangs as $barang)

                            <option
                                value="{{ $barang->id }}"
                                {{ request('barang_id') == $barang->id ? 'selected' : '' }}
                            >
                                {{ $barang->kode_barang }}
                                -
                                {{ $barang->nama_barang }}
                                @if($barang->spesifikasi)
                                    - {{ $barang->spesifikasi }}
                                @endif
                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- GUDANG --}}
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
                        class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
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


                {{-- TIPE --}}
                <div>

                    <label
                        for="tipe"
                        class="mb-2 block text-xs font-medium text-slate-500"
                    >
                        Jenis Mutasi
                    </label>

                    <select
                        id="tipe"
                        name="tipe"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >

                        <option value="">
                            Semua
                        </option>

                        @foreach([
                            'MASUK',
                            'KELUAR',
                            'PENYESUAIAN',
                            'MUTASI_MASUK',
                            'MUTASI_KELUAR',
                            'RETUR_MASUK',
                            'RETUR_KELUAR',
                        ] as $tipe)

                            <option
                                value="{{ $tipe }}"
                                {{ request('tipe') === $tipe ? 'selected' : '' }}
                            >
                                {{ str_replace('_', ' ', $tipe) }}
                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- TANGGAL MULAI --}}
                <div>

                    <label
                        for="tanggal_mulai"
                        class="mb-2 block text-xs font-medium text-slate-500"
                    >
                        Dari Tanggal
                    </label>

                    <input
                        type="date"
                        id="tanggal_mulai"
                        name="tanggal_mulai"
                        value="{{ request('tanggal_mulai') }}"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >

                </div>


                {{-- TANGGAL SELESAI --}}
                <div>

                    <label
                        for="tanggal_selesai"
                        class="mb-2 block text-xs font-medium text-slate-500"
                    >
                        Sampai Tanggal
                    </label>

                    <input
                        type="date"
                        id="tanggal_selesai"
                        name="tanggal_selesai"
                        value="{{ request('tanggal_selesai') }}"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >

                </div>

            </div>


            <div class="mt-4 flex justify-end gap-2">

                <a
                    href="{{ route('kartu-stok.index') }}"
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
                Riwayat Mutasi Stok
            </h3>

            <p class="mt-1 text-xs text-slate-400">
                Menampilkan {{ $mutasiStoks->total() }} transaksi stok.
            </p>

        </div>


        <div class="overflow-x-auto">

            <table class="w-full min-w-[1500px] text-sm">

                <thead class="bg-slate-50">

                    <tr class="text-left text-xs uppercase tracking-wide text-slate-400">

                        <th class="px-5 py-3 font-medium">
                            Tanggal
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Barang
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Gudang
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Jenis
                        </th>

                        <th class="px-5 py-3 text-right font-medium">
                            Masuk
                        </th>

                        <th class="px-5 py-3 text-right font-medium">
                            Keluar
                        </th>

                        <th class="px-5 py-3 text-right font-medium">
                            Harga
                        </th>

                        <th class="px-5 py-3 text-right font-medium">
                            Nilai
                        </th>

                        <th class="px-5 py-3 text-right font-medium">
                            Saldo Qty
                        </th>

                        <th class="px-5 py-3 text-right font-medium">
                            Saldo Nilai
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Referensi
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Keterangan
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-slate-100">

                    @forelse($mutasiStoks as $mutasi)

                        @php

                            $tipeClass = match($mutasi->tipe) {

                                'MASUK',
                                'MUTASI_MASUK',
                                'RETUR_MASUK'
                                    => 'bg-emerald-50 text-emerald-700',

                                'KELUAR',
                                'MUTASI_KELUAR',
                                'RETUR_KELUAR'
                                    => 'bg-red-50 text-red-700',

                                'PENYESUAIAN'
                                    => 'bg-amber-50 text-amber-700',

                                default
                                    => 'bg-slate-100 text-slate-600',
                            };

                        @endphp


                        <tr class="transition hover:bg-slate-50/70">

                            {{-- TANGGAL --}}
                            <td class="whitespace-nowrap px-5 py-4">

                                <p class="font-medium text-slate-700">
                                    {{ $mutasi->tanggal?->format('d/m/Y') ?? '-' }}
                                </p>

                                <p class="mt-1 text-xs text-slate-400">
                                    {{ $mutasi->tanggal?->format('H:i') ?? '' }}
                                </p>

                            </td>


                            {{-- BARANG --}}
                            <td class="px-5 py-4">

                                <p class="font-semibold text-slate-700">
                                    {{ $mutasi->barang?->kode_barang ?? '-' }}
                                </p>

                                <p class="mt-1 text-sm text-slate-600">
                                    {{ $mutasi->barang?->nama_barang ?? '-' }}
                                </p>

                                @if($mutasi->barang?->spesifikasi)

                                    <p class="mt-1 text-xs text-slate-400">
                                        {{ $mutasi->barang->spesifikasi }}

                                        @if($mutasi->barang->merek)
                                            - {{ $mutasi->barang->merek }}
                                        @endif
                                    </p>

                                @endif

                            </td>


                            {{-- GUDANG --}}
                            <td class="px-5 py-4 text-slate-600">

                                {{ $mutasi->gudang?->nama_gudang ?? '-' }}

                            </td>


                            {{-- TIPE --}}
                            <td class="px-5 py-4">

                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $tipeClass }}">
                                    {{ str_replace('_', ' ', $mutasi->tipe) }}
                                </span>

                            </td>


                            {{-- MASUK --}}
                            <td class="px-5 py-4 text-right">

                                @if((float) $mutasi->qty_masuk > 0)

                                    <span class="font-semibold text-emerald-600">
                                        +{{ number_format(
                                            $mutasi->qty_masuk,
                                            2,
                                            ',',
                                            '.'
                                        ) }}
                                    </span>

                                @else

                                    <span class="text-slate-300">
                                        -
                                    </span>

                                @endif

                            </td>


                            {{-- KELUAR --}}
                            <td class="px-5 py-4 text-right">

                                @if((float) $mutasi->qty_keluar > 0)

                                    <span class="font-semibold text-red-500">
                                        -{{ number_format(
                                            $mutasi->qty_keluar,
                                            2,
                                            ',',
                                            '.'
                                        ) }}
                                    </span>

                                @else

                                    <span class="text-slate-300">
                                        -
                                    </span>

                                @endif

                            </td>


                            {{-- HARGA --}}
                            <td class="whitespace-nowrap px-5 py-4 text-right text-slate-600">

                                Rp {{ number_format(
                                    $mutasi->harga_satuan,
                                    2,
                                    ',',
                                    '.'
                                ) }}

                            </td>


                            {{-- NILAI --}}
                            <td class="whitespace-nowrap px-5 py-4 text-right text-slate-600">

                                Rp {{ number_format(
                                    $mutasi->nilai,
                                    0,
                                    ',',
                                    '.'
                                ) }}

                            </td>


                            {{-- SALDO QTY --}}
                            <td class="px-5 py-4 text-right font-semibold text-slate-700">

                                {{ number_format(
                                    $mutasi->saldo_qty,
                                    2,
                                    ',',
                                    '.'
                                ) }}

                            </td>


                            {{-- SALDO NILAI --}}
                            <td class="whitespace-nowrap px-5 py-4 text-right font-semibold text-slate-700">

                                Rp {{ number_format(
                                    $mutasi->saldo_nilai,
                                    0,
                                    ',',
                                    '.'
                                ) }}

                            </td>


                            {{-- REFERENSI --}}
                            <td class="px-5 py-4">

                                @php

                                    $referenceType = strtoupper(
                                        trim(
                                            (string) $mutasi->reference_type
                                        )
                                    );

                                    $referenceId = (int) $mutasi->reference_id;

                                    $referenceKey =
                                        $referenceType
                                        . ':'
                                        . $referenceId;

                                    $referensi =
                                        $referensiMap[$referenceKey]
                                        ?? null;

                                @endphp

                                @if($referensi)

                                    <p class="text-[10px] font-semibold uppercase tracking-wide text-slate-400">
                                        {{ $referensi['label'] }}
                                    </p>

                                    <p
                                        class="mt-0.5 max-w-[220px] truncate text-sm font-semibold text-slate-700"
                                        title="{{ $referensi['nama'] }}"
                                    >
                                        {{ $referensi['nama'] }}
                                    </p>

                                    <p class="mt-1 text-xs font-medium text-[#15235F]">
                                        {{ $referensi['nomor'] }}
                                    </p>

                                @elseif($mutasi->reference_type)

                                    <p class="text-xs font-medium text-slate-600">
                                        {{ str_replace(
                                            '_',
                                            ' ',
                                            $mutasi->reference_type
                                        ) }}
                                    </p>

                                    @if($mutasi->reference_id)

                                        <p class="mt-1 text-xs text-slate-400">
                                            ID #{{ $mutasi->reference_id }}
                                        </p>

                                    @endif

                                @else

                                    <span class="text-slate-400">
                                        -
                                    </span>

                                @endif

                            </td>


                            {{-- KETERANGAN --}}
                            <td class="max-w-xs px-5 py-4 text-slate-500">

                                {{ $mutasi->keterangan ?: '-' }}

                            </td>

                        </tr>


                    @empty

                        <tr>

                            <td
                                colspan="12"
                                class="px-6 py-14 text-center"
                            >

                                <p class="text-sm font-medium text-slate-500">
                                    Belum ada mutasi stok.
                                </p>

                                <p class="mt-1 text-xs text-slate-400">
                                    Mutasi stok akan terbentuk otomatis dari transaksi persediaan.
                                </p>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        @if($mutasiStoks->hasPages())

            <div class="border-t border-slate-200 px-6 py-4">
                {{ $mutasiStoks->links() }}
            </div>

        @endif

    </div>

</div>

@endsection