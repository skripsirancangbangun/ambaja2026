@extends('layouts.app')

@section('title', 'Buat Retur Pembelian')

@section('content')

<div class="mx-auto max-w-6xl space-y-6">

    <div>

        <a
            href="{{ route('retur-pembelian.index') }}"
            class="mb-3 inline-flex text-sm text-slate-500 hover:text-[#15235F]"
        >
            ← Kembali
        </a>

        <h2 class="text-xl font-semibold text-slate-800">
            Buat Retur Pembelian
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            Catat barang yang akan dikembalikan kepada supplier.
        </p>

    </div>


    @if($errors->any())

        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3">

            <ul class="list-disc space-y-1 pl-5 text-xs text-red-600">

                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach

            </ul>

        </div>

    @endif


    <form
        action="{{ route('retur-pembelian.store') }}"
        method="POST"
        class="space-y-6"
    >

        @csrf


        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <h3 class="mb-5 text-sm font-semibold text-slate-700">
                Informasi Retur
            </h3>


            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                <div>

                    <label class="mb-2 block text-sm font-medium text-slate-700">
                        Nomor Retur
                    </label>

                    <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5">

                        <span class="text-sm font-semibold text-[#15235F]">
                            {{ $previewNomorRetur }}
                        </span>

                        <span class="rounded-full bg-teal-50 px-2.5 py-1 text-[10px] font-medium text-[#32AAA6]">
                            Otomatis
                        </span>

                    </div>

                </div>


                <div>

                    <label
                        for="tanggal_retur"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Tanggal Retur *
                    </label>

                    <input
                        type="date"
                        id="tanggal_retur"
                        name="tanggal_retur"
                        value="{{ old('tanggal_retur', date('Y-m-d')) }}"
                        required
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >

                </div>


                <div class="md:col-span-2">

                    <label
                        for="penerimaan_barang_id"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Penerimaan Barang *
                    </label>

                    <select
                        id="penerimaan_barang_id"
                        name="penerimaan_barang_id"
                        required
                        class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-[#32AAA6]"
                    >

                        <option value="">
                            Pilih Penerimaan Barang
                        </option>

                        @foreach($penerimaans as $penerimaan)

                            <option
                                value="{{ $penerimaan->id }}"
                                {{ old('penerimaan_barang_id', $selectedPenerimaan?->id) == $penerimaan->id ? 'selected' : '' }}
                            >
                                {{ $penerimaan->nomor_penerimaan }}
                                -
                                {{ $penerimaan->purchaseOrder?->nomor_po }}
                                -
                                {{ $penerimaan->purchaseOrder?->supplier?->nama_supplier }}
                            </option>

                        @endforeach

                    </select>

                </div>


                @if($selectedPenerimaan)

                    <div>

                        <p class="text-xs uppercase tracking-wide text-slate-400">
                            Supplier
                        </p>

                        <p class="mt-1 text-sm font-semibold text-slate-700">
                            {{ $selectedPenerimaan->purchaseOrder?->supplier?->nama_supplier ?? '-' }}
                        </p>

                    </div>


                    <div>

                        <p class="text-xs uppercase tracking-wide text-slate-400">
                            Gudang
                        </p>

                        <p class="mt-1 text-sm font-semibold text-slate-700">
                            {{ $selectedPenerimaan->gudang?->nama_gudang ?? '-' }}
                        </p>

                    </div>

                @endif


                <div class="md:col-span-2">

                    <label
                        for="alasan_retur"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Alasan Retur *
                    </label>

                    <input
                        type="text"
                        id="alasan_retur"
                        name="alasan_retur"
                        value="{{ old('alasan_retur') }}"
                        placeholder="Contoh: barang rusak / tidak sesuai spesifikasi"
                        required
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-[#32AAA6]"
                    >

                </div>

            </div>

        </div>


        @if($selectedPenerimaan)

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 px-6 py-4">

                    <h3 class="text-sm font-semibold text-slate-700">
                        Barang yang Diretur
                    </h3>

                    <p class="mt-1 text-xs text-slate-400">
                        Masukkan jumlah barang yang akan dikembalikan.
                    </p>

                </div>


                @if($items->isEmpty())

                    <div class="px-6 py-14 text-center">

                        <p class="text-sm font-medium text-slate-500">
                            Tidak ada barang yang masih dapat diretur.
                        </p>

                    </div>

                @else

                    <div class="overflow-x-auto">

                        <table class="w-full min-w-[1100px] text-sm">

                            <thead class="bg-slate-50">

                                <tr class="text-left text-xs uppercase tracking-wide text-slate-400">

                                    <th class="px-5 py-3">Barang</th>
                                    <th class="px-5 py-3 text-right">Diterima</th>
                                    <th class="px-5 py-3 text-right">Sudah Retur</th>
                                    <th class="px-5 py-3 text-right">Bisa Retur</th>
                                    <th class="px-5 py-3">Satuan</th>
                                    <th class="px-5 py-3 text-right">Harga</th>
                                    <th class="px-5 py-3">Qty Retur</th>
                                    <th class="px-5 py-3">Keterangan</th>

                                </tr>

                            </thead>


                            <tbody class="divide-y divide-slate-100">

                                @foreach($items as $item)

                                    <tr>

                                        <td class="px-5 py-4">

                                            <input
                                                type="hidden"
                                                name="items[{{ $loop->index }}][penerimaan_barang_item_id]"
                                                value="{{ $item->id }}"
                                            >

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
                                                $item->qty_diterima,
                                                2,
                                                ',',
                                                '.'
                                            ) }}

                                        </td>


                                        <td class="px-5 py-4 text-right text-slate-500">

                                            {{ number_format(
                                                $item->qty_sudah_diretur,
                                                2,
                                                ',',
                                                '.'
                                            ) }}

                                        </td>


                                        <td class="px-5 py-4 text-right font-semibold text-slate-700">

                                            {{ number_format(
                                                $item->qty_bisa_diretur,
                                                2,
                                                ',',
                                                '.'
                                            ) }}

                                        </td>


                                        <td class="px-5 py-4 text-slate-500">

                                            {{ $item->barang?->satuan?->nama_satuan ?? '-' }}

                                        </td>


                                        <td class="px-5 py-4 text-right text-slate-600">

                                            Rp {{ number_format(
                                                $item->harga_satuan,
                                                0,
                                                ',',
                                                '.'
                                            ) }}

                                        </td>


                                        <td class="px-5 py-4">

                                            <input
                                                type="number"
                                                name="items[{{ $loop->index }}][qty]"
                                                value="{{ old('items.' . $loop->index . '.qty', 0) }}"
                                                min="0"
                                                max="{{ $item->qty_bisa_diretur }}"
                                                step="0.01"
                                                class="w-28 rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-[#32AAA6]"
                                            >

                                        </td>


                                        <td class="px-5 py-4">

                                            <input
                                                type="text"
                                                name="items[{{ $loop->index }}][keterangan]"
                                                value="{{ old('items.' . $loop->index . '.keterangan') }}"
                                                placeholder="Opsional"
                                                class="w-48 rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-[#32AAA6]"
                                            >

                                        </td>

                                    </tr>

                                @endforeach

                            </tbody>

                        </table>

                    </div>

                @endif

            </div>

        @else

            <div class="rounded-2xl border border-slate-200 bg-white px-6 py-14 text-center shadow-sm">

                <p class="text-sm font-medium text-slate-500">
                    Pilih Penerimaan Barang terlebih dahulu.
                </p>

            </div>

        @endif


        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <label class="mb-2 block text-sm font-medium text-slate-700">
                Catatan
            </label>

            <textarea
                name="catatan"
                rows="4"
                class="w-full resize-none rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-[#32AAA6]"
            >{{ old('catatan') }}</textarea>

        </div>


        <div class="flex justify-end gap-3">

            <a
                href="{{ route('retur-pembelian.index') }}"
                class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50"
            >
                Batal
            </a>

            <button
                type="submit"
                {{ !$selectedPenerimaan || $items->isEmpty() ? 'disabled' : '' }}
                class="rounded-lg bg-[#15235F] px-5 py-2.5 text-sm font-medium text-white hover:bg-[#101b4d] disabled:cursor-not-allowed disabled:opacity-40"
            >
                Simpan Draft
            </button>

        </div>

    </form>

</div>


<script>
    document
        .getElementById('penerimaan_barang_id')
        .addEventListener('change', function () {

            const url =
                new URL(window.location.href);

            if (this.value) {

                url.searchParams.set(
                    'penerimaan_barang_id',
                    this.value
                );

            } else {

                url.searchParams.delete(
                    'penerimaan_barang_id'
                );
            }

            window.location.href =
                url.toString();
        });
</script>

@endsection