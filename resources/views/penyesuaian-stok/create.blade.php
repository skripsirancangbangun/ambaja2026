@extends('layouts.app')

@section('title', 'Buat Penyesuaian Stok')

@section('content')

<div class="mx-auto max-w-6xl space-y-6">

    <div>

        <a
            href="{{ route('penyesuaian-stok.index') }}"
            class="mb-3 inline-flex items-center gap-2 text-sm text-slate-500 hover:text-[#15235F]"
        >
            <x-icon name="arrow-left" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" /> Kembali
        </a>

        <h2 class="text-xl font-semibold text-slate-800">
            Buat Penyesuaian Stok
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            Terapkan selisih hasil Stock Opname ke saldo stok sistem.
        </p>

    </div>


    @if($errors->any())

        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3">

            <p class="text-sm font-semibold text-red-700">
                Periksa kembali data yang diinput.
            </p>

            <ul class="mt-2 list-disc space-y-1 pl-5 text-xs text-red-600">

                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach

            </ul>

        </div>

    @endif


    <form
        action="{{ route('penyesuaian-stok.store') }}"
        method="POST"
        class="space-y-6"
    >

        @csrf


        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <div class="mb-5">

                <h3 class="text-sm font-semibold text-slate-700">
                    Informasi Penyesuaian
                </h3>

                <p class="mt-1 text-xs text-slate-400">
                    Pilih Stock Opname yang sudah selesai dan memiliki selisih.
                </p>

            </div>


            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                {{-- NOMOR --}}
                <div>

                    <label class="mb-2 block text-sm font-medium text-slate-700">
                        Nomor Penyesuaian
                    </label>

                    <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5">

                        <span class="text-sm font-semibold text-[#15235F]">
                            {{ $previewNomorPenyesuaian }}
                        </span>

                        <span class="rounded-full bg-teal-50 px-2.5 py-1 text-[10px] font-medium text-[#32AAA6]">
                            Otomatis
                        </span>

                    </div>

                </div>


                {{-- TANGGAL --}}
                <div>

                    <label
                        for="tanggal_penyesuaian"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Tanggal Penyesuaian
                        <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="date"
                        id="tanggal_penyesuaian"
                        name="tanggal_penyesuaian"
                        value="{{ old('tanggal_penyesuaian', date('Y-m-d')) }}"
                        required
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >

                </div>


                {{-- STOCK OPNAME --}}
                <div class="md:col-span-2">

                    <label
                        for="stock_opname_id"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Stock Opname
                        <span class="text-red-500">*</span>
                    </label>

                    <select
                        id="stock_opname_id"
                        name="stock_opname_id"
                        required
                        class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >

                        <option value="">
                            Pilih Stock Opname
                        </option>

                        @foreach($stockOpnames as $opname)

                            <option
                                value="{{ $opname->id }}"
                                {{ old('stock_opname_id', $selectedStockOpname?->id) == $opname->id ? 'selected' : '' }}
                            >
                                {{ $opname->nomor_opname }}
                                -
                                {{ $opname->gudang?->nama_gudang }}
                                -
                                {{ $opname->tanggal_opname?->format('d/m/Y') }}
                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- CATATAN --}}
                <div class="md:col-span-2">

                    <label
                        for="catatan"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Catatan
                    </label>

                    <textarea
                        id="catatan"
                        name="catatan"
                        rows="4"
                        placeholder="Catatan penyesuaian stok (opsional)"
                        class="w-full resize-none rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >{{ old('catatan') }}</textarea>

                </div>

            </div>

        </div>


        @if($selectedStockOpname)

            @php
                $itemsSelisih =
                    $selectedStockOpname->items->filter(
                        fn($item) => (float) $item->selisih != 0
                    );

                $totalNilaiNetto =
                    $itemsSelisih->sum(
                        fn($item) => (float) $item->nilai_selisih
                    );
            @endphp


            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">

                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Gudang
                    </p>

                    <p class="mt-2 font-semibold text-slate-700">
                        {{ $selectedStockOpname->gudang?->nama_gudang ?? '-' }}
                    </p>

                </div>


                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Barang Selisih
                    </p>

                    <p class="mt-2 text-2xl font-bold text-[#15235F]">
                        {{ $itemsSelisih->count() }}
                    </p>

                </div>


                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Nilai Selisih Netto
                    </p>

                    <p class="mt-2 text-xl font-bold {{ $totalNilaiNetto < 0 ? 'text-red-500' : 'text-emerald-600' }}">

                        {{ $totalNilaiNetto > 0 ? '+' : '' }}

                        Rp {{ number_format(
                            $totalNilaiNetto,
                            0,
                            ',',
                            '.'
                        ) }}

                    </p>

                </div>

            </div>


            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 px-6 py-4">

                    <h3 class="text-sm font-semibold text-slate-700">
                        Selisih yang Akan Disesuaikan
                    </h3>

                    <p class="mt-1 text-xs text-slate-400">
                        Penyesuaian dibuat otomatis berdasarkan hasil Stock Opname.
                    </p>

                </div>


                <div class="overflow-x-auto">

                    <table class="w-full min-w-[1100px] text-sm">

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
                                    Jenis
                                </th>

                                <th class="px-5 py-3 text-right">
                                    Avg Cost
                                </th>

                                <th class="px-5 py-3 text-right">
                                    Nilai Selisih
                                </th>

                            </tr>

                        </thead>


                        <tbody class="divide-y divide-slate-100">

                            @foreach($itemsSelisih as $item)

                                @php
                                    $isTambah =
                                        (float) $item->selisih > 0;
                                @endphp


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


                                    <td class="px-5 py-4 text-right font-semibold {{ $isTambah ? 'text-emerald-600' : 'text-red-500' }}">

                                        {{ $isTambah ? '+' : '' }}

                                        {{ number_format(
                                            $item->selisih,
                                            2,
                                            ',',
                                            '.'
                                        ) }}

                                    </td>


                                    <td class="px-5 py-4">

                                        @if($isTambah)

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

                                        Rp {{ number_format(
                                            $item->harga_rata_rata,
                                            2,
                                            ',',
                                            '.'
                                        ) }}

                                    </td>


                                    <td class="px-5 py-4 text-right font-semibold {{ $isTambah ? 'text-emerald-600' : 'text-red-500' }}">

                                        {{ $isTambah ? '+' : '' }}

                                        Rp {{ number_format(
                                            $item->nilai_selisih,
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

        @else

            <div class="rounded-2xl border border-slate-200 bg-white px-6 py-14 text-center shadow-sm">

                <p class="text-sm font-medium text-slate-500">
                    Pilih Stock Opname terlebih dahulu.
                </p>

                <p class="mt-1 text-xs text-slate-400">
                    Detail selisih yang akan disesuaikan akan muncul otomatis.
                </p>

            </div>

        @endif


        <div class="flex justify-end gap-3">

            <a
                href="{{ route('penyesuaian-stok.index') }}"
                class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100"
            >
                Batal
            </a>

            <button
                type="submit"
                {{ !$selectedStockOpname ? 'disabled' : '' }}
                onclick="return confirm('Proses Penyesuaian Stok? Saldo persediaan akan berubah sesuai hasil Stock Opname.')"
                class="rounded-lg bg-[#15235F] px-5 py-2.5 text-sm font-medium text-white hover:bg-[#101b4d] disabled:cursor-not-allowed disabled:opacity-40"
            >
                Proses Penyesuaian
            </button>

        </div>

    </form>

</div>


<script>
    document
        .getElementById('stock_opname_id')
        .addEventListener('change', function () {

            const url =
                new URL(window.location.href);

            if (this.value) {

                url.searchParams.set(
                    'stock_opname_id',
                    this.value
                );

            } else {

                url.searchParams.delete(
                    'stock_opname_id'
                );
            }

            window.location.href =
                url.toString();
        });
</script>

@endsection