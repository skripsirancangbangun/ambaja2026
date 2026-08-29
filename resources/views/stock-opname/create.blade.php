@extends('layouts.app')

@section('title', 'Buat Stock Opname')

@section('content')

<div class="mx-auto max-w-5xl space-y-6">

    <div>

        <a
            href="{{ route('stock-opname.index') }}"
            class="mb-3 inline-flex items-center gap-2 text-sm text-slate-500 hover:text-[#15235F]"
        >
            ← Kembali
        </a>

        <h2 class="text-xl font-semibold text-slate-800">
            Buat Stock Opname
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            Pilih gudang untuk membuat snapshot stok yang akan diperiksa secara fisik.
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
        action="{{ route('stock-opname.store') }}"
        method="POST"
        class="space-y-6"
    >

        @csrf


        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <div class="mb-5">

                <h3 class="text-sm font-semibold text-slate-700">
                    Informasi Stock Opname
                </h3>

                <p class="mt-1 text-xs text-slate-400">
                    Stok sistem akan disalin sebagai snapshot ketika dokumen dibuat.
                </p>

            </div>


            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                <div>

                    <label class="mb-2 block text-sm font-medium text-slate-700">
                        Nomor Opname
                    </label>

                    <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5">

                        <span class="text-sm font-semibold text-[#15235F]">
                            {{ $previewNomorOpname }}
                        </span>

                        <span class="rounded-full bg-teal-50 px-2.5 py-1 text-[10px] font-medium text-[#32AAA6]">
                            Otomatis
                        </span>

                    </div>

                </div>


                <div>

                    <label
                        for="tanggal_opname"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Tanggal Opname
                        <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="date"
                        id="tanggal_opname"
                        name="tanggal_opname"
                        value="{{ old('tanggal_opname', date('Y-m-d')) }}"
                        required
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >

                </div>


                <div class="md:col-span-2">

                    <label
                        for="gudang_id"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Gudang
                        <span class="text-red-500">*</span>
                    </label>

                    <select
                        id="gudang_id"
                        name="gudang_id"
                        required
                        class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >

                        <option value="">
                            Pilih Gudang
                        </option>

                        @foreach($gudangs as $gudang)

                            <option
                                value="{{ $gudang->id }}"
                                {{ old('gudang_id', $selectedGudangId) == $gudang->id ? 'selected' : '' }}
                            >
                                {{ $gudang->kode_gudang }}
                                -
                                {{ $gudang->nama_gudang }}
                            </option>

                        @endforeach

                    </select>

                </div>


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
                        placeholder="Catatan Stock Opname (opsional)"
                        class="w-full resize-none rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >{{ old('catatan') }}</textarea>

                </div>

            </div>

        </div>


        @if($selectedGudangId)

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 px-6 py-4">

                    <h3 class="text-sm font-semibold text-slate-700">
                        Preview Stok Sistem
                    </h3>

                    <p class="mt-1 text-xs text-slate-400">
                        Data ini akan menjadi snapshot awal Stock Opname.
                    </p>

                </div>


                @if($stokBarangs->isEmpty())

                    <div class="px-6 py-12 text-center">

                        <p class="text-sm font-medium text-slate-500">
                            Gudang belum memiliki stok.
                        </p>

                    </div>

                @else

                    <div class="overflow-x-auto">

                        <table class="w-full min-w-[850px] text-sm">

                            <thead class="bg-slate-50">

                                <tr class="text-left text-xs uppercase tracking-wide text-slate-400">

                                    <th class="px-5 py-3">
                                        Barang
                                    </th>

                                    <th class="px-5 py-3">
                                        Kategori
                                    </th>

                                    <th class="px-5 py-3 text-right">
                                        Stok Sistem
                                    </th>

                                    <th class="px-5 py-3">
                                        Satuan
                                    </th>

                                    <th class="px-5 py-3 text-right">
                                        Avg Cost
                                    </th>

                                </tr>

                            </thead>


                            <tbody class="divide-y divide-slate-100">

                                @foreach($stokBarangs as $stok)

                                    <tr>

                                        <td class="px-5 py-4">

                                            <p class="font-semibold text-slate-700">
                                                {{ $stok->barang?->kode_barang }}
                                                -
                                                {{ $stok->barang?->nama_barang }}
                                            </p>

                                            @if($stok->barang?->spesifikasi)

                                                <p class="mt-1 text-xs text-slate-400">
                                                    {{ $stok->barang->spesifikasi }}
                                                </p>

                                            @endif

                                        </td>


                                        <td class="px-5 py-4 text-slate-500">

                                            {{ $stok->barang?->kategori?->nama_kategori ?? '-' }}

                                        </td>


                                        <td class="px-5 py-4 text-right font-semibold text-slate-700">

                                            {{ number_format(
                                                $stok->qty,
                                                2,
                                                ',',
                                                '.'
                                            ) }}

                                        </td>


                                        <td class="px-5 py-4 text-slate-500">

                                            {{ $stok->barang?->satuan?->nama_satuan ?? '-' }}

                                        </td>


                                        <td class="px-5 py-4 text-right text-slate-600">

                                            Rp {{ number_format(
                                                $stok->harga_rata_rata,
                                                2,
                                                ',',
                                                '.'
                                            ) }}

                                        </td>

                                    </tr>

                                @endforeach

                            </tbody>

                        </table>

                    </div>

                @endif

            </div>

        @endif


        <div class="flex justify-end gap-3">

            <a
                href="{{ route('stock-opname.index') }}"
                class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100"
            >
                Batal
            </a>

            <button
                type="submit"
                {{ !$selectedGudangId || $stokBarangs->isEmpty() ? 'disabled' : '' }}
                class="rounded-lg bg-[#15235F] px-5 py-2.5 text-sm font-medium text-white hover:bg-[#101b4d] disabled:cursor-not-allowed disabled:opacity-40"
            >
                Buat Draft Opname
            </button>

        </div>

    </form>

</div>


<script>
    document
        .getElementById('gudang_id')
        .addEventListener('change', function () {

            const url =
                new URL(window.location.href);

            if (this.value) {

                url.searchParams.set(
                    'gudang_id',
                    this.value
                );

            } else {

                url.searchParams.delete(
                    'gudang_id'
                );
            }

            window.location.href =
                url.toString();
        });
</script>

@endsection