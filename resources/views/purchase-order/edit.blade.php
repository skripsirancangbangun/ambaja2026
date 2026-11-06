@extends('layouts.app')

@section('title', 'Edit Purchase Order')

@section('content')

<div class="mx-auto max-w-6xl space-y-6">

    <div>
        <a
            href="{{ route('purchase-order.index') }}"
            class="mb-3 inline-flex items-center gap-2 text-sm text-slate-500 hover:text-[#15235F]"
        >
            <x-icon name="arrow-left" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" /> Kembali
        </a>

        <h2 class="text-xl font-semibold text-slate-800">
            Edit Purchase Order
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            Perbarui Purchase Order yang masih berstatus Draft.
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
        action="{{ route('purchase-order.update', $purchaseOrder) }}"
        method="POST"
        class="space-y-6"
    >

        @csrf
        @method('PUT')


        {{-- HEADER --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <div class="mb-5">

                <h3 class="text-sm font-semibold text-slate-700">
                    Informasi Purchase Order
                </h3>

                <p class="mt-1 text-xs text-slate-400">
                    Nomor Purchase Order tidak dapat diubah.
                </p>

            </div>


            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                {{-- NOMOR PO --}}
                <div>

                    <label class="mb-2 block text-sm font-medium text-slate-700">
                        Nomor PO
                    </label>

                    <div class="flex w-full items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5">

                        <span class="text-sm font-semibold text-[#15235F]">
                            {{ $purchaseOrder->nomor_po }}
                        </span>

                        <span class="rounded-full bg-slate-200 px-2.5 py-1 text-[10px] font-medium text-slate-500">
                            Permanen
                        </span>

                    </div>

                </div>


                {{-- SUPPLIER --}}
                <div>

                    <label
                        for="supplier_id"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Supplier
                        <span class="text-red-500">*</span>
                    </label>

                    <select
                        id="supplier_id"
                        name="supplier_id"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-700 outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                        required
                    >

                        @foreach($suppliers as $supplier)

                            <option
                                value="{{ $supplier->id }}"
                                {{ old(
                                    'supplier_id',
                                    $purchaseOrder->supplier_id
                                ) == $supplier->id ? 'selected' : '' }}
                            >
                                {{ $supplier->kode_supplier }}
                                -
                                {{ $supplier->nama_supplier }}
                            </option>

                        @endforeach

                    </select>

                    @error('supplier_id')
                        <p class="mt-1.5 text-xs text-red-500">
                            {{ $message }}
                        </p>
                    @enderror

                </div>


                {{-- TANGGAL --}}
                <div>

                    <label
                        for="tanggal_po"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Tanggal PO
                        <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="date"
                        id="tanggal_po"
                        name="tanggal_po"
                        value="{{ old(
                            'tanggal_po',
                            $purchaseOrder->tanggal_po?->format('Y-m-d')
                        ) }}"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-700 outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                        required
                    >

                    @error('tanggal_po')
                        <p class="mt-1.5 text-xs text-red-500">
                            {{ $message }}
                        </p>
                    @enderror

                </div>


                {{-- ESTIMASI --}}
                <div>

                    <label
                        for="tanggal_estimasi_datang"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Estimasi Kedatangan
                    </label>

                    <input
                        type="date"
                        id="tanggal_estimasi_datang"
                        name="tanggal_estimasi_datang"
                        value="{{ old(
                            'tanggal_estimasi_datang',
                            $purchaseOrder->tanggal_estimasi_datang?->format('Y-m-d')
                        ) }}"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-700 outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >

                    @error('tanggal_estimasi_datang')
                        <p class="mt-1.5 text-xs text-red-500">
                            {{ $message }}
                        </p>
                    @enderror

                </div>

            </div>

        </div>


        {{-- ITEM --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">

                <div>

                    <h3 class="text-sm font-semibold text-slate-700">
                        Detail Barang
                    </h3>

                    <p class="mt-1 text-xs text-slate-400">
                        Tambahkan atau ubah barang pada Purchase Order.
                    </p>

                </div>


                <button
                    type="button"
                    id="add-item"
                    class="rounded-lg bg-teal-50 px-3 py-2 text-xs font-semibold text-[#15235F] transition hover:bg-teal-100"
                >
                    + Tambah Barang
                </button>

            </div>


            <div class="overflow-x-auto">

                <table class="w-full min-w-[1050px] text-sm">

                    <thead class="bg-slate-50">

                        <tr class="text-left text-xs uppercase tracking-wide text-slate-400">

                            <th class="px-4 py-3">
                                Barang
                            </th>

                            <th class="px-4 py-3">
                                Qty
                            </th>

                            <th class="px-4 py-3">
                                Harga
                            </th>

                            <th class="px-4 py-3">
                                Diskon
                            </th>

                            <th class="px-4 py-3">
                                Subtotal
                            </th>

                            <th class="px-4 py-3">
                                Keterangan
                            </th>

                            <th class="px-4 py-3"></th>

                        </tr>

                    </thead>


                    <tbody
                        id="items-container"
                        class="divide-y divide-slate-100"
                    ></tbody>

                </table>

            </div>

        </div>


        {{-- TOTAL --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">

                {{-- CATATAN --}}
                <div>

                    <label
                        for="catatan"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Catatan
                    </label>

                    <textarea
                        id="catatan"
                        name="catatan"
                        rows="6"
                        class="w-full resize-none rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >{{ old('catatan', $purchaseOrder->catatan) }}</textarea>

                    @error('catatan')
                        <p class="mt-1.5 text-xs text-red-500">
                            {{ $message }}
                        </p>
                    @enderror

                </div>


                {{-- NILAI --}}
                <div class="space-y-4">

                    {{-- DISKON --}}
                    <div>

                        <label
                            for="diskon-po"
                            class="mb-2 block text-sm font-medium text-slate-700"
                        >
                            Diskon PO
                        </label>

                        <div class="relative">

                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400">
                                Rp
                            </span>

                            <input
                                type="number"
                                id="diskon-po"
                                name="diskon"
                                min="0"
                                step="0.01"
                                value="{{ old('diskon', $purchaseOrder->diskon) }}"
                                class="w-full rounded-lg border border-slate-300 py-2.5 pl-10 pr-3.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                            >

                        </div>

                        @error('diskon')
                            <p class="mt-1.5 text-xs text-red-500">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>


                    {{-- PAJAK PERSEN --}}
                    <div>

                        <label
                            for="pajak-persen"
                            class="mb-2 block text-sm font-medium text-slate-700"
                        >
                            Pajak
                        </label>

                        <div class="relative">

                            <input
                                type="number"
                                id="pajak-persen"
                                name="pajak_persen"
                                min="0"
                                max="100"
                                step="0.01"
                                value="{{ old(
                                    'pajak_persen',
                                    $purchaseOrder->pajak_persen ?? 0
                                ) }}"
                                placeholder="Contoh: 11"
                                class="w-full rounded-lg border border-slate-300 py-2.5 pl-3.5 pr-10 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                            >

                            <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-sm font-medium text-slate-400">
                                %
                            </span>

                        </div>

                        <p class="mt-1.5 text-xs text-slate-400">
                            Contoh: isi 11 untuk pajak 11%.
                        </p>

                        @error('pajak_persen')
                            <p class="mt-1.5 text-xs text-red-500">
                                {{ $message }}
                            </p>
                        @enderror

                    </div>


                    {{-- SUMMARY --}}
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">

                        <div class="flex justify-between text-sm text-slate-500">

                            <span>
                                Subtotal Barang
                            </span>

                            <span
                                id="subtotal-display"
                                class="font-medium text-slate-700"
                            >
                                Rp 0
                            </span>

                        </div>


                        <div class="mt-3 flex justify-between text-sm text-slate-500">

                            <span>
                                Diskon PO
                            </span>

                            <span id="diskon-display">
                                Rp 0
                            </span>

                        </div>


                        <div class="mt-3 flex justify-between text-sm text-slate-500">

                            <span>
                                Dasar Pajak
                            </span>

                            <span id="dasar-pajak-display">
                                Rp 0
                            </span>

                        </div>


                        <div class="mt-3 flex justify-between text-sm text-slate-500">

                            <span>
                                Pajak
                                <span id="pajak-persen-display">
                                    (0%)
                                </span>
                            </span>

                            <span id="pajak-display">
                                Rp 0
                            </span>

                        </div>


                        <div class="mt-4 flex justify-between border-t border-slate-200 pt-4">

                            <span class="font-semibold text-slate-700">
                                Total Purchase Order
                            </span>

                            <span
                                id="total-display"
                                class="text-xl font-bold text-[#15235F]"
                            >
                                Rp 0
                            </span>

                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- ACTION --}}
        <div class="flex justify-end gap-3">

            <a
                href="{{ route('purchase-order.show', $purchaseOrder) }}"
                class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100"
            >
                Batal
            </a>

            <button
                type="submit"
                class="rounded-lg bg-[#15235F] px-5 py-2.5 text-sm font-medium text-white transition hover:bg-[#101b4d]"
            >
                Simpan Perubahan
            </button>

        </div>

    </form>

</div>


@php
    $isRequestBasedPO = false;
@endphp

@include('purchase-order.partials.barang-picker')

@endsection
