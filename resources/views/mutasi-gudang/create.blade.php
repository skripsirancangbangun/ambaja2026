@extends('layouts.app')

@section('title', 'Buat Mutasi Gudang')

@section('content')

<div class="mx-auto max-w-6xl space-y-6">

    <div>
        <a
            href="{{ route('mutasi-gudang.index') }}"
            class="mb-3 inline-flex items-center gap-2 text-sm text-slate-500 hover:text-[#15235F]"
        >
            ← Kembali
        </a>

        <h2 class="text-xl font-semibold text-slate-800">
            Buat Mutasi Gudang
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            Pindahkan stok dari satu gudang ke gudang lainnya.
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
        action="{{ route('mutasi-gudang.store') }}"
        method="POST"
        class="space-y-6"
    >

        @csrf


        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <div class="mb-5">
                <h3 class="text-sm font-semibold text-slate-700">
                    Informasi Mutasi
                </h3>
            </div>


            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                <div>

                    <label class="mb-2 block text-sm font-medium text-slate-700">
                        Nomor Mutasi
                    </label>

                    <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5">

                        <span class="text-sm font-semibold text-[#15235F]">
                            {{ $previewNomorMutasi }}
                        </span>

                        <span class="rounded-full bg-teal-50 px-2.5 py-1 text-[10px] font-medium text-[#32AAA6]">
                            Otomatis
                        </span>

                    </div>

                </div>


                <div>

                    <label
                        for="tanggal_mutasi"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Tanggal Mutasi *
                    </label>

                    <input
                        type="date"
                        id="tanggal_mutasi"
                        name="tanggal_mutasi"
                        value="{{ old('tanggal_mutasi', date('Y-m-d')) }}"
                        required
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >

                </div>


                <div>

                    <label
                        for="gudang_asal_id"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Gudang Asal *
                    </label>

                    <select
                        id="gudang_asal_id"
                        name="gudang_asal_id"
                        required
                        class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >

                        <option value="">
                            Pilih Gudang Asal
                        </option>

                        @foreach($gudangs as $gudang)

                            <option
                                value="{{ $gudang->id }}"
                                {{ old('gudang_asal_id', $selectedGudangId) == $gudang->id ? 'selected' : '' }}
                            >
                                {{ $gudang->kode_gudang }}
                                -
                                {{ $gudang->nama_gudang }}
                            </option>

                        @endforeach

                    </select>

                </div>


                <div>

                    <label
                        for="gudang_tujuan_id"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Gudang Tujuan *
                    </label>

                    <select
                        id="gudang_tujuan_id"
                        name="gudang_tujuan_id"
                        required
                        class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >

                        <option value="">
                            Pilih Gudang Tujuan
                        </option>

                        @foreach($gudangs as $gudang)

                            <option
                                value="{{ $gudang->id }}"
                                {{ old('gudang_tujuan_id') == $gudang->id ? 'selected' : '' }}
                            >
                                {{ $gudang->kode_gudang }}
                                -
                                {{ $gudang->nama_gudang }}
                            </option>

                        @endforeach

                    </select>

                </div>

            </div>

        </div>


        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-6 py-4">

                <h3 class="text-sm font-semibold text-slate-700">
                    Barang yang Dipindahkan
                </h3>

                <p class="mt-1 text-xs text-slate-400">
                    Barang muncul berdasarkan stok gudang asal.
                </p>

            </div>


            @if(!$selectedGudangId)

                <div class="px-6 py-14 text-center">

                    <p class="text-sm font-medium text-slate-500">
                        Pilih gudang asal terlebih dahulu.
                    </p>

                    <p class="mt-1 text-xs text-slate-400">
                        Daftar stok akan muncul setelah gudang asal dipilih.
                    </p>

                </div>


            @elseif($stokBarangs->isEmpty())

                <div class="px-6 py-14 text-center">

                    <p class="text-sm font-medium text-slate-500">
                        Gudang ini belum memiliki stok.
                    </p>

                </div>


            @else

                <div class="overflow-x-auto">

                    <table class="w-full min-w-[900px] text-sm">

                        <thead class="bg-slate-50">

                            <tr class="text-left text-xs uppercase tracking-wide text-slate-400">
                                <th class="px-5 py-3 font-medium">Barang</th>
                                <th class="px-5 py-3 text-right font-medium">Stok</th>
                                <th class="px-5 py-3 font-medium">Satuan</th>
                                <th class="px-5 py-3 text-right font-medium">Average Cost</th>
                                <th class="px-5 py-3 font-medium">Qty Mutasi</th>
                            </tr>

                        </thead>


                        <tbody class="divide-y divide-slate-100">

                            @foreach($stokBarangs as $stok)

                                <tr>

                                    <td class="px-5 py-4">

                                        <input
                                            type="hidden"
                                            name="items[{{ $loop->index }}][barang_id]"
                                            value="{{ $stok->barang_id }}"
                                        >

                                        <p class="font-semibold text-slate-700">
                                            {{ $stok->barang?->kode_barang }}
                                            -
                                            {{ $stok->barang?->nama_barang }}
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


                                    <td class="px-5 py-4 text-right font-semibold text-slate-700">
                                        {{ number_format($stok->qty, 2, ',', '.') }}
                                    </td>


                                    <td class="px-5 py-4 text-slate-500">
                                        {{ $stok->barang?->satuan?->nama_satuan ?? '-' }}
                                    </td>


                                    <td class="px-5 py-4 text-right text-slate-600">
                                        Rp {{ number_format($stok->harga_rata_rata, 2, ',', '.') }}
                                    </td>


                                    <td class="px-5 py-4">

                                        <input
                                            type="number"
                                            name="items[{{ $loop->index }}][qty]"
                                            value="{{ old('items.' . $loop->index . '.qty', 0) }}"
                                            min="0"
                                            max="{{ $stok->qty }}"
                                            step="0.01"
                                            class="w-36 rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                                        >

                                        <p class="mt-1 text-[10px] text-slate-400">
                                            Maks. {{ number_format($stok->qty, 2, ',', '.') }}
                                        </p>

                                    </td>

                                </tr>

                            @endforeach

                        </tbody>

                    </table>

                </div>

            @endif

        </div>


        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

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
                placeholder="Catatan mutasi gudang (opsional)"
                class="w-full resize-none rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
            >{{ old('catatan') }}</textarea>

        </div>


        <div class="flex justify-end gap-3">

            <a
                href="{{ route('mutasi-gudang.index') }}"
                class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100"
            >
                Batal
            </a>

            <button
                type="submit"
                {{ !$selectedGudangId || $stokBarangs->isEmpty() ? 'disabled' : '' }}
                class="rounded-lg bg-[#15235F] px-5 py-2.5 text-sm font-medium text-white hover:bg-[#101b4d] disabled:cursor-not-allowed disabled:opacity-40"
            >
                Simpan Mutasi
            </button>

        </div>

    </form>

</div>


<script>
    const gudangAsal =
        document.getElementById('gudang_asal_id');


    gudangAsal.addEventListener(
        'change',
        function () {

            const url =
                new URL(window.location.href);


            if (this.value) {

                url.searchParams.set(
                    'gudang_asal_id',
                    this.value
                );

            } else {

                url.searchParams.delete(
                    'gudang_asal_id'
                );
            }


            window.location.href =
                url.toString();
        }
    );
</script>

@endsection