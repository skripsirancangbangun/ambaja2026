@extends('layouts.app')

@section('title', 'Tambah Penerimaan Barang')

@section('content')

<div class="mx-auto max-w-6xl space-y-6">

    <div>

        <a
            href="{{ route('penerimaan-barang.index') }}"
            class="mb-3 inline-flex items-center gap-2 text-sm text-slate-500 hover:text-[#15235F]"
        >
            <x-icon name="arrow-left" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" /> Kembali
        </a>

        <h2 class="text-xl font-semibold text-slate-800">
            Tambah Penerimaan Barang
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            Catat barang yang diterima berdasarkan Purchase Order.
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


    <form
        action="{{ route('penerimaan-barang.store') }}"
        method="POST"
        class="space-y-6"
    >

        @csrf


        {{-- INFORMASI PENERIMAAN --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <div class="mb-5">

                <h3 class="text-sm font-semibold text-slate-700">
                    Informasi Penerimaan
                </h3>

                <p class="mt-1 text-xs text-slate-400">
                    Pilih Purchase Order, gudang tujuan, dan informasi barang datang.
                </p>

            </div>


            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                {{-- NOMOR --}}
                <div>

                    <label class="mb-2 block text-sm font-medium text-slate-700">
                        Nomor Penerimaan
                    </label>

                    <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5">

                        <span class="text-sm font-semibold text-[#15235F]">
                            {{ $previewNomorPenerimaan }}
                        </span>

                        <span class="rounded-full bg-teal-50 px-2.5 py-1 text-[10px] font-medium text-[#32AAA6]">
                            Otomatis
                        </span>

                    </div>

                </div>


                {{-- TANGGAL --}}
                <div>

                    <label
                        for="tanggal_penerimaan"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Tanggal Penerimaan
                        <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="date"
                        id="tanggal_penerimaan"
                        name="tanggal_penerimaan"
                        value="{{ old('tanggal_penerimaan', date('Y-m-d')) }}"
                        required
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >

                </div>


                {{-- PURCHASE ORDER --}}
                <div class="md:col-span-2">

                    <label
                        for="purchase_order_id"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Purchase Order
                        <span class="text-red-500">*</span>
                    </label>

                    <select
                        id="purchase_order_id"
                        name="purchase_order_id"
                        required
                        class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >

                        <option value="">
                            Pilih Purchase Order
                        </option>

                        @foreach($purchaseOrders as $po)

                            <option
                                value="{{ $po->id }}"
                                {{ old(
                                    'purchase_order_id',
                                    $selectedPurchaseOrder?->id
                                ) == $po->id ? 'selected' : '' }}
                            >
                                {{ $po->nomor_po }}
                                -
                                {{ $po->supplier?->nama_supplier }}
                                -
                                {{ $po->status }}
                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- GUDANG --}}
                <div>

                    <label
                        for="gudang_id"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Gudang Tujuan
                        <span class="text-red-500">*</span>
                    </label>

                    <select
                        id="gudang_id"
                        name="gudang_id"
                        required
                        class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >

                        <option value="">
                            Pilih Gudang
                        </option>

                        @foreach($gudangs as $gudang)

                            <option
                                value="{{ $gudang->id }}"
                                {{ old('gudang_id') == $gudang->id ? 'selected' : '' }}
                            >
                                {{ $gudang->nama_gudang }}
                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- SURAT JALAN --}}
                <div>

                    <label
                        for="nomor_surat_jalan"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Nomor Surat Jalan
                    </label>

                    <input
                        type="text"
                        id="nomor_surat_jalan"
                        name="nomor_surat_jalan"
                        value="{{ old('nomor_surat_jalan') }}"
                        placeholder="Nomor surat jalan supplier"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >

                </div>

            </div>

        </div>


        {{-- INFORMASI PO --}}
        @if($selectedPurchaseOrder)

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                <div class="grid grid-cols-1 gap-5 md:grid-cols-3">

                    <div>

                        <p class="text-xs uppercase tracking-wide text-slate-400">
                            Supplier
                        </p>

                        <p class="mt-1 text-sm font-semibold text-slate-700">
                            {{ $selectedPurchaseOrder->supplier?->nama_supplier ?? '-' }}
                        </p>

                    </div>


                    <div>

                        <p class="text-xs uppercase tracking-wide text-slate-400">
                            Tanggal PO
                        </p>

                        <p class="mt-1 text-sm font-medium text-slate-700">
                            {{ $selectedPurchaseOrder->tanggal_po?->format('d/m/Y') ?? '-' }}
                        </p>

                    </div>


                    <div>

                        <p class="text-xs uppercase tracking-wide text-slate-400">
                            Status PO
                        </p>

                        <span class="mt-1 inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">
                            {{ $selectedPurchaseOrder->status }}
                        </span>

                    </div>

                </div>

            </div>

        @endif


        {{-- DETAIL BARANG --}}
        @if($selectedPurchaseOrder)

            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 px-6 py-4">

                    <h3 class="text-sm font-semibold text-slate-700">
                        Barang Purchase Order
                    </h3>

                    <p class="mt-1 text-xs text-slate-400">
                        Masukkan qty yang benar-benar diterima. Jika sisa barang tidak jadi dikirim supplier, gunakan tombol Batalkan Sisa.
                    </p>

                </div>


                <div class="overflow-x-auto">

                    <table class="w-full min-w-[1500px] text-sm">

                        <thead class="bg-slate-50">

                            <tr class="text-left text-xs uppercase tracking-wide text-slate-400">

                                <th class="px-4 py-3 font-medium">
                                    Barang
                                </th>

                                <th class="px-4 py-3 text-right font-medium">
                                    Qty PO
                                </th>

                                <th class="px-4 py-3 text-right font-medium">
                                    Sudah Diterima
                                </th>

                                <th class="px-4 py-3 text-right font-medium">
                                    Dibatalkan
                                </th>

                                <th class="px-4 py-3 text-right font-medium">
                                    Sisa
                                </th>

                                <th class="px-4 py-3 font-medium">
                                    Satuan
                                </th>

                                <th class="px-4 py-3 font-medium">
                                    Diterima Sekarang
                                </th>

                                <th class="px-4 py-3 text-right font-medium">
                                    Aksi
                                </th>

                            </tr>

                        </thead>


                        <tbody class="divide-y divide-slate-100">

                            @foreach($selectedPurchaseOrder->items as $item)

                                @php
                                    $qtyPO =
                                        (float) $item->qty;

                                    $qtyDiterima =
                                        (float) $item->qty_diterima;

                                    $qtyDibatalkan =
                                        (float) $item->qty_dibatalkan;

                                    $sisa =
                                        max(
                                            0,
                                            $qtyPO
                                            - $qtyDiterima
                                            - $qtyDibatalkan
                                        );
                                @endphp


                                <tr class="{{ $sisa <= 0 ? 'bg-slate-50/60' : '' }}">

                                    {{-- BARANG --}}
                                    <td class="px-4 py-4">

                                        <input
                                            type="hidden"
                                            name="items[{{ $loop->index }}][purchase_order_item_id]"
                                            value="{{ $item->id }}"
                                        >

                                        <p class="font-semibold text-slate-700">

                                            {{ $item->barang?->kode_barang ?? '-' }}
                                            -
                                            {{ $item->barang?->nama_barang ?? '-' }}

                                        </p>


                                        @if($item->barang?->spesifikasi)

                                            <p class="mt-1 text-xs text-slate-400">
                                                {{ $item->barang->spesifikasi }}
                                            </p>

                                        @endif

                                    </td>


                                    {{-- QTY PO --}}
                                    <td class="px-4 py-4 text-right font-semibold text-slate-700">

                                        {{ number_format(
                                            $qtyPO,
                                            2,
                                            ',',
                                            '.'
                                        ) }}

                                    </td>


                                    {{-- DITERIMA --}}
                                    <td class="px-4 py-4 text-right">

                                        <span class="font-medium text-emerald-600">

                                            {{ number_format(
                                                $qtyDiterima,
                                                2,
                                                ',',
                                                '.'
                                            ) }}

                                        </span>

                                    </td>


                                    {{-- DIBATALKAN --}}
                                    <td class="px-4 py-4 text-right">

                                        @if($qtyDibatalkan > 0)

                                            <span class="font-medium text-red-500">

                                                {{ number_format(
                                                    $qtyDibatalkan,
                                                    2,
                                                    ',',
                                                    '.'
                                                ) }}

                                            </span>

                                        @else

                                            <span class="text-slate-400">
                                                0
                                            </span>

                                        @endif

                                    </td>


                                    {{-- SISA --}}
                                    <td class="px-4 py-4 text-right">

                                        @if($sisa > 0)

                                            <span class="font-bold text-[#15235F]">

                                                {{ number_format(
                                                    $sisa,
                                                    2,
                                                    ',',
                                                    '.'
                                                ) }}

                                            </span>

                                        @else

                                            <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                                Selesai
                                            </span>

                                        @endif

                                    </td>


                                    {{-- SATUAN --}}
                                    <td class="px-4 py-4 text-slate-500">

                                        {{ $item->barang?->satuan?->nama_satuan ?? '-' }}

                                    </td>


                                    {{-- DITERIMA SEKARANG --}}
                                    <td class="px-4 py-4">

                                        @if($sisa > 0)

                                            <input
                                                type="number"
                                                name="items[{{ $loop->index }}][qty_diterima]"
                                                value="{{ old(
                                                    'items.' . $loop->index . '.qty_diterima',
                                                    0
                                                ) }}"
                                                min="0"
                                                max="{{ $sisa }}"
                                                step="0.01"
                                                class="w-32 rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                                            >

                                        @else

                                            <input
                                                type="hidden"
                                                name="items[{{ $loop->index }}][qty_diterima]"
                                                value="0"
                                            >

                                            <span class="text-xs text-slate-400">
                                                Tidak ada sisa
                                            </span>

                                        @endif

                                    </td>


                                    {{-- AKSI --}}
                                    <td class="px-4 py-4">

                                        <div class="flex justify-end">

                                            @if($sisa > 0)

                                                <button
                                                    type="button"
                                                    onclick="document.getElementById('form-batal-{{ $item->id }}').submit()"
                                                    class="inline-flex items-center justify-center rounded-lg border border-red-200 bg-white px-3 py-2 text-xs font-medium text-red-500 transition hover:bg-red-50"
                                                >
                                                    Batalkan Sisa
                                                </button>

                                            @else

                                                @if($qtyDibatalkan > 0)

                                                    <span class="inline-flex rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-600">
                                                        Ada Pembatalan
                                                    </span>

                                                @else

                                                    <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                                        Diterima Penuh
                                                    </span>

                                                @endif

                                            @endif

                                        </div>

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
                    Pilih Purchase Order terlebih dahulu.
                </p>

                <p class="mt-1 text-xs text-slate-400">
                    Daftar barang dan sisa penerimaan akan muncul otomatis.
                </p>

            </div>

        @endif


        {{-- CATATAN --}}
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
                placeholder="Catatan penerimaan barang (opsional)"
                class="w-full resize-none rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
            >{{ old('catatan') }}</textarea>

        </div>


        {{-- ACTION --}}
        <div class="flex justify-end gap-3">

            <a
                href="{{ route('penerimaan-barang.index') }}"
                class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100"
            >
                Batal
            </a>

            <button
                type="submit"
                {{ !$selectedPurchaseOrder ? 'disabled' : '' }}
                class="rounded-lg bg-[#15235F] px-5 py-2.5 text-sm font-medium text-white transition hover:bg-[#101b4d] disabled:cursor-not-allowed disabled:opacity-40"
            >
                Simpan Penerimaan
            </button>

        </div>

    </form>


    {{-- FORM BATAL SISA TERPISAH --}}
    @if($selectedPurchaseOrder)

        @foreach($selectedPurchaseOrder->items as $item)

            @php
                $sisaItem =
                    max(
                        0,
                        (float) $item->qty
                        - (float) $item->qty_diterima
                        - (float) $item->qty_dibatalkan
                    );
            @endphp

            @if($sisaItem > 0)

                <form
                    id="form-batal-{{ $item->id }}"
                    action="{{ route(
                        'purchase-order-item.batalkan-sisa',
                        $item
                    ) }}"
                    method="POST"
                    class="hidden"
                    onsubmit="return confirm(
                        'Yakin batalkan seluruh sisa {{ number_format($sisaItem, 2, ',', '.') }} untuk barang {{ addslashes($item->barang?->nama_barang ?? '') }}? Barang yang dibatalkan tidak dapat diterima lagi.'
                    )"
                >

                    @csrf
                    @method('PATCH')

                </form>

            @endif

        @endforeach

    @endif

</div>


<script>

    /*
    |--------------------------------------------------------------------------
    | GANTI PURCHASE ORDER
    |--------------------------------------------------------------------------
    */

    const poSelect =
        document.getElementById(
            'purchase_order_id'
        );


    poSelect.addEventListener(
        'change',
        function () {

            const url =
                new URL(
                    window.location.href
                );


            if (this.value) {

                url.searchParams.set(
                    'purchase_order_id',
                    this.value
                );

            } else {

                url.searchParams.delete(
                    'purchase_order_id'
                );
            }


            window.location.href =
                url.toString();
        }
    );

</script>

@endsection