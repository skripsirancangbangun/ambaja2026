@extends('layouts.app')

@section('title', 'Buat Invoice Penjualan')

@section('content')

<div
    class="mx-auto max-w-6xl space-y-6"
    x-data="invoiceForm()"
>

    {{-- ========================================================= --}}
    {{-- HEADER --}}
    {{-- ========================================================= --}}

    <div>
        <a
            href="{{ route('invoice-penjualan.index') }}"
            class="mb-3 inline-flex items-center gap-2 text-sm text-slate-500 hover:text-[#15235F]"
        >
            <x-icon name="arrow-left" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" /> Kembali ke Daftar Invoice
        </a>

        <h2 class="text-xl font-semibold text-slate-800">
            Buat Invoice Penjualan
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            Terbitkan faktur penjualan tunai atau kredit.
            Pengurangan stok gudang akan otomatis diproses.
        </p>
    </div>

    {{-- ========================================================= --}}
    {{-- ERROR VALIDATION --}}
    {{-- ========================================================= --}}

    @if($errors->any())
        <div
            class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"
        >
            <p class="font-semibold">
                Terjadi kesalahan pada input data:
            </p>

            <ul class="mt-2 list-inside list-disc space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- ========================================================= --}}
    {{-- SESSION ERROR --}}
    {{-- ========================================================= --}}

    @if(session('error'))
        <div
            class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"
        >
            <div class="font-semibold">
                Invoice gagal disimpan
            </div>

            <div class="mt-1">
                {{ session('error') }}
            </div>
        </div>
    @endif

    {{-- ========================================================= --}}
    {{-- FORM --}}
    {{-- ========================================================= --}}

    <form
        action="{{ route('invoice-penjualan.store') }}"
        method="POST"
        class="space-y-6"
        @submit="handleSubmit($event)"
    >
        @csrf

        {{-- ===================================================== --}}
        {{-- INFORMASI INVOICE --}}
        {{-- ===================================================== --}}

        <div
            class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
        >
            <h3 class="text-sm font-semibold text-slate-700">
                Informasi Faktur & Customer
            </h3>

            <div
                class="mt-4 grid grid-cols-1 gap-5 md:grid-cols-3"
            >

                {{-- ================================================= --}}
                {{-- NOMOR INVOICE --}}
                {{-- ================================================= --}}

                <div>
                    <label
                        class="block text-xs font-semibold uppercase tracking-wider text-slate-600"
                    >
                        Nomor Invoice
                    </label>

                    <input
                        type="text"
                        name="nomor_invoice"
                        value="{{ old('nomor_invoice', $nomorInvoice) }}"
                        required
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-semibold text-[#15235F] focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                    >
                </div>

                {{-- ================================================= --}}
                {{-- SALES ORDER --}}
                {{-- ================================================= --}}

                <div>
                    <label
                        class="block text-xs font-semibold uppercase tracking-wider text-slate-600"
                    >
                        Dari Sales Order (Opsional)
                    </label>

                    <select
                        name="penjualan_id"
                        x-model="penjualanId"
                        @change="onPenjualanChange()"
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                    >
                        <option value="">
                            -- Penjualan Langsung (Tanpa SO) --
                        </option>

                        @foreach($penjualans as $so)
                            <option
                                value="{{ $so->id }}"
                                {{ old('penjualan_id', request('penjualan_id')) == $so->id ? 'selected' : '' }}
                            >
                                {{ $so->nomor_penjualan }}
                                -
                                {{ $so->customer->nama_customer ?? '' }}
                                (Rp {{ number_format($so->total, 0, ',', '.') }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- ================================================= --}}
                {{-- CUSTOMER --}}
                {{-- ================================================= --}}

                <div>
                    <label
                        class="block text-xs font-semibold uppercase tracking-wider text-slate-600"
                    >
                        Customer
                        <span class="text-rose-500">*</span>
                    </label>

                    <select
                        name="customer_id"
                        x-model="customerId"
                        required
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                    >
                        <option value="">
                            -- Pilih Customer --
                        </option>

                        @foreach($customers as $customer)
                            <option
                                value="{{ $customer->id }}"
                                {{ old('customer_id', $selectedPenjualan->customer_id ?? '') == $customer->id ? 'selected' : '' }}
                            >
                                {{ $customer->kode_customer }}
                                -
                                {{ $customer->nama_customer }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- ================================================= --}}
                {{-- GUDANG --}}
                {{-- ================================================= --}}

                <div>
                    <label
                        class="block text-xs font-semibold uppercase tracking-wider text-slate-600"
                    >
                        Gudang Pengeluaran Stok
                        <span class="text-rose-500">*</span>
                    </label>

                    <select
                        name="gudang_id"
                        x-model="gudangId"
                        @change="onGudangChange()"
                        required
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                    >
                        <option value="">
                            -- Pilih Gudang --
                        </option>

                        @foreach($gudangs as $gudang)
                            <option
                                value="{{ $gudang->id }}"
                                {{ old('gudang_id', $selectedPenjualan->gudang_id ?? '') == $gudang->id ? 'selected' : '' }}
                            >
                                {{ $gudang->nama_gudang }}
                            </option>
                        @endforeach
                    </select>

                    <p class="mt-1 text-[11px] text-slate-400">
                        Stok barang akan dihitung berdasarkan gudang ini.
                    </p>
                </div>

                {{-- ================================================= --}}
                {{-- TANGGAL INVOICE --}}
                {{-- ================================================= --}}

                <div>
                    <label
                        class="block text-xs font-semibold uppercase tracking-wider text-slate-600"
                    >
                        Tanggal Invoice
                        <span class="text-rose-500">*</span>
                    </label>

                    <input
                        type="date"
                        name="tanggal_invoice"
                        value="{{ old('tanggal_invoice', date('Y-m-d')) }}"
                        required
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                    >
                </div>

                {{-- ================================================= --}}
                {{-- PAJAK --}}
                {{-- ================================================= --}}

                <div>
                    <label
                        class="block text-xs font-semibold uppercase tracking-wider text-slate-600"
                    >
                        Pajak PPN (%)
                    </label>

                    <input
                        type="number"
                        name="pajak_persen"
                        x-model.number="pajakPersen"
                        min="0"
                        max="100"
                        step="0.1"
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                    >
                </div>

            </div>

            {{-- ===================================================== --}}
            {{-- ALUR PEMBAYARAN NOTICE --}}
            {{-- ===================================================== --}}

            <div class="mt-4 rounded-xl border border-sky-200 bg-sky-50/80 p-3.5 text-xs text-sky-800 flex items-start gap-2.5">
                <span class="inline-block p-1 bg-sky-100 rounded-lg text-sky-700 font-bold">ℹ️</span>
                <div class="leading-relaxed">
                    <strong class="font-semibold text-sky-900">Alur 2 Langkah:</strong> Pada tahap ini faktur penjualan akan disimpan dan stok barang di gudang otomatis dikurangi. Penetapan cara pembayaran (apakah <span class="font-semibold text-emerald-800">Tunai / Langsung Lunas</span> atau <span class="font-semibold text-amber-800">Kredit / Piutang Tempo</span>) serta pembuatan jurnal penjualan akan dilakukan pada langkah selanjutnya di halaman detail invoice.
                </div>
            </div>

            {{-- ===================================================== --}}
            {{-- CATATAN --}}
            {{-- ===================================================== --}}

            <div class="mt-4">
                <label
                    class="block text-xs font-semibold uppercase tracking-wider text-slate-600"
                >
                    Catatan Invoice
                </label>

                <textarea
                    name="catatan"
                    rows="2"
                    placeholder="Keterangan transaksi invoice..."
                    class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                >{{ old('catatan') }}</textarea>
            </div>

        </div>

        {{-- ========================================================= --}}
        {{-- ITEMS --}}
        {{-- ========================================================= --}}

        <div
            class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm"
        >
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-slate-700">
                        Daftar Barang yang Dijual
                    </h3>

                    <p class="mt-0.5 text-xs text-slate-400">
                        Pilih barang dan tentukan kuantitas penjualan.
                    </p>
                </div>

                <button
                    type="button"
                    @click="addItem()"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-teal-50 px-4 py-2 text-xs font-semibold text-[#15235F] transition hover:bg-teal-100"
                >
                    + Tambah Baris
                </button>
            </div>

            {{-- ===================================================== --}}
            {{-- INFO GUDANG --}}
            {{-- ===================================================== --}}

            <div
                x-show="!gudangId"
                x-cloak
                class="mt-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-700"
            >
                Pilih gudang terlebih dahulu agar stok setiap barang dapat
                ditampilkan dengan benar.
            </div>

            {{-- ===================================================== --}}
            {{-- TABLE --}}
            {{-- ===================================================== --}}

            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">

                    <thead class="bg-slate-50">
                        <tr
                            class="text-xs uppercase tracking-wide text-slate-400"
                        >
                            <th
                                class="px-4 py-3 font-medium"
                                style="width: 35%"
                            >
                                Barang
                            </th>

                            <th
                                class="px-4 py-3 font-medium"
                                style="width: 15%"
                            >
                                Qty
                            </th>

                            <th
                                class="px-4 py-3 font-medium"
                                style="width: 20%"
                            >
                                Harga Jual (Rp)
                            </th>

                            <th
                                class="px-4 py-3 font-medium"
                                style="width: 15%"
                            >
                                Diskon (Rp)
                            </th>

                            <th
                                class="px-4 py-3 text-right font-medium"
                                style="width: 20%"
                            >
                                Subtotal (Rp)
                            </th>

                            <th
                                class="px-4 py-3 text-center"
                                style="width: 5%"
                            ></th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">

                        <template
                            x-for="(item, index) in items"
                            :key="index"
                        >
                            <tr>

                                {{-- ========================================= --}}
                                {{-- BARANG --}}
                                {{-- ========================================= --}}

                                <td class="px-4 py-3 align-top">

                                    <input
                                        type="hidden"
                                        :name="`items[${index}][barang_id]`"
                                        x-model="item.barang_id"
                                        required
                                    >

                                    {{-- JIKA SUDAH PILIH BARANG --}}
                                    <template x-if="item.barang_id && barangsData[item.barang_id]">
                                        <div class="rounded-xl border border-slate-200 bg-slate-50 p-2.5 shadow-sm">
                                            <div class="flex items-start justify-between gap-2">
                                                <div class="min-w-0">
                                                    <span class="inline-block rounded bg-[#15235F]/10 px-1.5 py-0.5 font-mono text-[10px] font-bold text-[#15235F]" x-text="barangsData[item.barang_id].kode_barang"></span>
                                                    <div class="mt-0.5 font-semibold text-slate-800 text-xs truncate" x-text="barangsData[item.barang_id].nama_barang"></div>
                                                    <div class="text-[11px] text-slate-400 mt-0.5 space-x-1">
                                                        <span x-text="barangsData[item.barang_id].kategori?.nama_kategori || 'Tanpa Kategori'"></span>
                                                        <span>•</span>
                                                        <span>Supplier: <strong class="text-slate-600" x-text="getSupplierNames(barangsData[item.barang_id])"></strong></span>
                                                    </div>
                                                </div>

                                                <button
                                                    type="button"
                                                    @click="openBarangModal(index)"
                                                    class="shrink-0 rounded-lg border border-slate-300 bg-white px-2 py-1 text-[11px] font-semibold text-slate-700 shadow-sm hover:bg-slate-100 hover:text-[#15235F] transition"
                                                >
                                                    ⇄ Ganti
                                                </button>
                                            </div>

                                            {{-- STOK INFO --}}
                                            <div class="mt-2 pt-2 border-t border-slate-200/60 flex items-center justify-between text-[11px]">
                                                <span class="text-slate-500">Stok Gudang:</span>
                                                <template x-if="!gudangId">
                                                    <span class="text-amber-600 font-medium">Pilih gudang</span>
                                                </template>
                                                <template x-if="gudangId">
                                                    <div>
                                                        <template x-if="getStokBarang(item.barang_id) <= 0">
                                                            <span class="inline-flex items-center gap-1 font-bold px-2 py-0.5 rounded text-[10px] bg-rose-100 text-rose-700">
                                                                <svg class="h-3 w-3 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                                <span>0 (Stok Kosong - Tidak Valid)</span>
                                                            </span>
                                                        </template>
                                                        <template x-if="getStokBarang(item.barang_id) > 0">
                                                            <div class="inline-flex items-center">
                                                                <span
                                                                    class="font-semibold px-1.5 py-0.5 rounded text-[10px]"
                                                                    :class="getStokBarang(item.barang_id) >= parseFloat(item.qty || 0) ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                                                                    x-text="formatQty(getStokBarang(item.barang_id)) + ' ' + (barangsData[item.barang_id].satuan?.nama_satuan || '')"
                                                                ></span>
                                                                <span
                                                                    x-show="parseFloat(item.qty || 0) > getStokBarang(item.barang_id)"
                                                                    class="ml-1 text-[10px] font-bold text-rose-600"
                                                                >
                                                                    Kurang!
                                                                </span>
                                                            </div>
                                                        </template>
                                                    </div>
                                                </template>
                                            </div>
                                        </div>
                                    </template>

                                    {{-- JIKA BELUM PILIH BARANG --}}
                                    <template x-if="!item.barang_id || !barangsData[item.barang_id]">
                                        <button
                                            type="button"
                                            @click="openBarangModal(index)"
                                            class="flex w-full items-center justify-center gap-2 rounded-xl border-2 border-dashed border-teal-300 bg-teal-50/40 px-3 py-3 text-xs font-semibold text-[#15235F] transition hover:border-[#32AAA6] hover:bg-teal-50"
                                        >
                                            <svg class="h-4 w-4 text-[#32AAA6]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                            </svg>
                                            <span>🔍 Pilih Barang (Pop-up)</span>
                                        </button>
                                    </template>

                                </td>

                                {{-- ========================================= --}}
                                {{-- QTY --}}
                                {{-- ========================================= --}}

                                <td class="px-4 py-3 align-top">

                                    <input
                                        type="number"
                                        :name="`items[${index}][qty]`"
                                        x-model.number="item.qty"
                                        min="0.01"
                                        step="any"
                                        required
                                        class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                                        :class="
                                            item.barang_id
                                            && gudangId
                                            && (
                                                getStokBarang(item.barang_id) <= 0
                                                || parseFloat(item.qty || 0) > getStokBarang(item.barang_id)
                                            )
                                                ? 'border-rose-300 bg-rose-50 text-rose-700'
                                                : ''
                                        "
                                    >

                                </td>

                                {{-- ========================================= --}}
                                {{-- HARGA --}}
                                {{-- ========================================= --}}

                                <td class="px-4 py-3 align-top">

                                    <input
                                        type="number"
                                        :name="`items[${index}][harga_satuan]`"
                                        x-model.number="item.harga_satuan"
                                        min="0"
                                        step="1"
                                        required
                                        class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                                    >

                                </td>

                                {{-- ========================================= --}}
                                {{-- DISKON --}}
                                {{-- ========================================= --}}

                                <td class="px-4 py-3 align-top">

                                    <input
                                        type="number"
                                        :name="`items[${index}][diskon]`"
                                        x-model.number="item.diskon"
                                        min="0"
                                        step="1"
                                        class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                                    >

                                    <div
                                        x-show="
                                            parseFloat(item.diskon || 0)
                                            >
                                            (
                                                parseFloat(item.qty || 0)
                                                *
                                                parseFloat(
                                                    item.harga_satuan || 0
                                                )
                                            )
                                        "
                                        class="mt-1 text-[11px] font-medium text-rose-600"
                                    >
                                        Diskon melebihi nilai barang.
                                    </div>

                                </td>

                                {{-- ========================================= --}}
                                {{-- SUBTOTAL --}}
                                {{-- ========================================= --}}

                                <td
                                    class="px-4 py-3 text-right align-top font-semibold text-slate-800"
                                >
                                    <span
                                        x-text="
                                            formatRupiah(
                                                getItemSubtotal(item)
                                            )
                                        "
                                    ></span>
                                </td>

                                {{-- ========================================= --}}
                                {{-- DELETE --}}
                                {{-- ========================================= --}}

                                <td
                                    class="px-4 py-3 text-center align-top"
                                >
                                    <button
                                        type="button"
                                        @click="removeItem(index)"
                                        x-show="items.length > 1"
                                        class="text-rose-500 hover:text-rose-700"
                                        title="Hapus baris"
                                    >
                                        <x-icon name="x" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" />
                                    </button>
                                </td>

                            </tr>
                        </template>

                    </tbody>
                </table>
            </div>

            {{-- ===================================================== --}}
            {{-- TOTAL SUMMARY --}}
            {{-- ===================================================== --}}

            <div
                class="mt-6 flex justify-end border-t border-slate-100 pt-4"
            >
                <div
                    class="w-full max-w-sm space-y-2 text-sm"
                >

                    <div
                        class="flex justify-between text-slate-500"
                    >
                        <span>
                            Subtotal:
                        </span>

                        <span
                            class="font-medium text-slate-800"
                            x-text="
                                formatRupiah(
                                    calculateSubtotal()
                                )
                            "
                        ></span>
                    </div>

                    <div
                        class="flex justify-between text-slate-500"
                    >
                        <span>
                            Total Diskon Item:
                        </span>

                        <span
                            class="font-medium text-slate-800"
                            x-text="
                                formatRupiah(
                                    calculateTotalDiskon()
                                )
                            "
                        ></span>
                    </div>

                    <div
                        class="flex justify-between text-slate-500"
                    >
                        <span>
                            Pajak PPN
                            (<span x-text="pajakPersen"></span>%):
                        </span>

                        <span
                            class="font-medium text-slate-800"
                            x-text="
                                formatRupiah(
                                    calculatePajak()
                                )
                            "
                        ></span>
                    </div>

                    <div
                        class="flex justify-between border-t border-slate-200 pt-2 text-base font-bold text-[#15235F]"
                    >
                        <span>
                            Grand Total:
                        </span>

                        <span
                            x-text="
                                formatRupiah(
                                    calculateGrandTotal()
                                )
                            "
                        ></span>
                    </div>

                </div>
            </div>

        </div>

        {{-- ========================================================= --}}
        {{-- STOCK WARNING --}}
        {{-- ========================================================= --}}

        <div
            x-show="hasInsufficientStock()"
            x-cloak
            class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
        >
            <div class="font-semibold flex items-center gap-1.5">
                <svg class="h-4 w-4 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span>Stok Tidak Mencukupi atau Kosong (0)</span>
            </div>

            <div class="mt-1 text-xs">
                Terdapat barang dengan stok 0 atau kuantitas penjualan melebihi stok yang tersedia di gudang terpilih. Barang dengan stok 0 tidak dapat diproses ke invoice.
            </div>
        </div>

        {{-- ========================================================= --}}
        {{-- ACTION --}}
        {{-- ========================================================= --}}

        <div
            class="flex items-center justify-end gap-3"
        >

            <a
                href="{{ route('invoice-penjualan.index') }}"
                class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50"
            >
                Batal
            </a>

            <button
                type="submit"
                :disabled="
                    !gudangId
                    || hasInsufficientStock()
                    || hasInvalidDiscount()
                "
                :class="
                    (
                        !gudangId
                        || hasInsufficientStock()
                        || hasInvalidDiscount()
                    )
                        ? 'cursor-not-allowed bg-slate-300'
                        : 'bg-[#15235F] hover:bg-[#101b4d]'
                "
                class="rounded-xl px-7 py-2.5 text-sm font-medium text-white shadow-sm transition"
            >
                Terbitkan Invoice
            </button>

        </div>

    </form>

    {{-- ========================================================= --}}
    {{-- MODAL POP-UP PILIH BARANG (CENTER OVERLAY) --}}
    {{-- ========================================================= --}}
    <div
        x-show="showBarangModal"
        x-cloak
        class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm flex items-center justify-center p-3 sm:p-4"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        @keydown.escape.window="closeBarangModal()"
    >
        <div
            class="relative w-full max-w-4xl bg-white rounded-2xl shadow-2xl overflow-hidden flex flex-col max-h-[88vh] border border-slate-100"
            @click.away="closeBarangModal()"
        >
            {{-- MODAL HEADER --}}
            <div class="px-6 py-4 bg-gradient-to-r from-[#15235F] to-[#1e348a] text-white flex items-center justify-between">
                <div>
                    <h3 class="text-base font-bold flex items-center gap-2">
                        <span>📦</span>
                        <span>Pilih Data Barang</span>
                    </h3>
                    <p class="text-xs text-slate-200 mt-0.5">
                        Cari barang berdasarkan nama, kode, kategori, atau supplier. Stok disesuaikan dengan gudang terpilih.
                    </p>
                </div>
                <button
                    type="button"
                    @click="closeBarangModal()"
                    class="rounded-xl p-1.5 text-white/80 hover:bg-white/10 hover:text-white transition"
                    title="Tutup"
                >
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- MODAL SEARCH & FILTER BAR --}}
            <div class="p-4 bg-slate-50 border-b border-slate-200 flex flex-col md:flex-row gap-3 items-stretch md:items-center">
                <div class="relative flex-1">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input
                        type="text"
                        x-ref="searchInput"
                        x-model="searchBarang"
                        placeholder="Ketik nama barang, kode, merk, supplier..."
                        class="w-full pl-9 pr-4 py-2 bg-white border border-slate-200 rounded-xl text-sm focus:border-[#32AAA6] focus:ring-1 focus:ring-[#32AAA6] focus:outline-none"
                    >
                </div>

                <div class="w-full md:w-48 shrink-0">
                    <select
                        x-model="filterKategori"
                        class="w-full py-2 px-3 bg-white border border-slate-200 rounded-xl text-sm focus:border-[#32AAA6] focus:outline-none"
                    >
                        <option value="">-- Semua Kategori --</option>
                        <template x-for="kat in getKategoriList()" :key="kat">
                            <option :value="kat" x-text="kat"></option>
                        </template>
                    </select>
                </div>

                <label class="flex items-center gap-2 text-xs font-medium text-slate-700 shrink-0 cursor-pointer select-none bg-white px-3 py-2 rounded-xl border border-slate-200 hover:bg-slate-50 transition">
                    <input type="checkbox" x-model="hanyaTersedia" class="rounded border-slate-300 text-[#32AAA6] focus:ring-[#32AAA6]">
                    <span>Hanya Stok > 0</span>
                </label>

                <div class="shrink-0 text-xs px-3 py-2 rounded-xl bg-teal-50 border border-teal-200 text-teal-800 font-medium">
                    Gudang: <span class="font-bold" x-text="getGudangNama()"></span>
                </div>
            </div>

            {{-- MODAL BODY (TABLE) --}}
            <div class="overflow-y-auto flex-1 p-4">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-100 text-xs uppercase text-slate-600 sticky top-0 z-10 shadow-sm">
                        <tr>
                            <th class="px-3 py-2.5 rounded-l-lg">Kode</th>
                            <th class="px-3 py-2.5">Nama Barang & Kategori</th>
                            <th class="px-3 py-2.5">Supplier</th>
                            <th class="px-3 py-2.5 text-center">Stok Gudang</th>
                            <th class="px-3 py-2.5 text-right">Harga Jual</th>
                            <th class="px-3 py-2.5 text-center rounded-r-lg">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="barang in getFilteredBarangs()" :key="barang.id">
                            <tr
                                :class="getStokBarang(barang.id) > 0 ? 'hover:bg-teal-50/40 cursor-pointer' : 'bg-slate-50/70 opacity-60 cursor-not-allowed select-none'"
                                @click="getStokBarang(barang.id) > 0 ? pilihBarang(barang) : null"
                            >
                                <td class="px-3 py-2.5 font-mono text-xs font-semibold" :class="getStokBarang(barang.id) > 0 ? 'text-[#15235F]' : 'text-slate-400'" x-text="barang.kode_barang"></td>
                                <td class="px-3 py-2.5">
                                    <div class="font-medium text-xs sm:text-sm" :class="getStokBarang(barang.id) > 0 ? 'text-slate-800' : 'text-slate-400'" x-text="barang.nama_barang"></div>
                                    <div class="text-[11px] text-slate-400" x-text="barang.kategori?.nama_kategori || 'Tanpa Kategori'"></div>
                                </td>
                                <td class="px-3 py-2.5 text-xs text-slate-500">
                                    <span x-text="getSupplierNames(barang)"></span>
                                </td>
                                <td class="px-3 py-2.5 text-center">
                                    <template x-if="getStokBarang(barang.id) > 0">
                                        <span
                                            class="inline-block px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-800"
                                            x-text="formatQty(getStokBarang(barang.id)) + ' ' + (barang.satuan?.nama_satuan || '')"
                                        ></span>
                                    </template>
                                    <template x-if="getStokBarang(barang.id) <= 0">
                                        <span
                                            class="inline-block px-2.5 py-0.5 rounded-full text-xs font-bold bg-rose-100 text-rose-700"
                                        >
                                            0 (Stok Kosong)
                                        </span>
                                    </template>
                                </td>
                                <td class="px-3 py-2.5 text-right font-semibold text-xs sm:text-sm" :class="getStokBarang(barang.id) > 0 ? 'text-slate-800' : 'text-slate-400'" x-text="formatRupiah(barang.harga_jual || 0)"></td>
                                <td class="px-3 py-2.5 text-center" @click.stop>
                                    <template x-if="getStokBarang(barang.id) > 0">
                                        <button
                                            type="button"
                                            @click="pilihBarang(barang)"
                                            class="inline-flex items-center gap-1 rounded-lg bg-[#15235F] px-3 py-1.5 text-xs font-semibold text-white shadow-sm hover:bg-[#32AAA6] transition"
                                        >
                                            Pilih
                                        </button>
                                    </template>
                                    <template x-if="getStokBarang(barang.id) <= 0">
                                        <button
                                            type="button"
                                            disabled
                                            class="inline-flex items-center gap-1 rounded-lg bg-slate-200 px-3 py-1.5 text-xs font-semibold text-slate-400 cursor-not-allowed border border-slate-300 shadow-none"
                                            title="Barang ini memiliki stok 0 di gudang yang dipilih dan tidak bisa dipilih"
                                        >
                                            Stok 0
                                        </button>
                                    </template>
                                </td>
                            </tr>
                        </template>
                        <template x-if="getFilteredBarangs().length === 0">
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-sm text-slate-400">
                                    Tidak ada data barang yang sesuai dengan filter pencarian.
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- MODAL FOOTER --}}
            <div class="px-6 py-3 bg-slate-50 border-t border-slate-200 flex items-center justify-between text-xs text-slate-500">
                <div>
                    Menampilkan <span class="font-bold text-slate-700" x-text="getFilteredBarangs().length"></span> barang
                </div>
                <button
                    type="button"
                    @click="closeBarangModal()"
                    class="rounded-xl border border-slate-300 bg-white px-4 py-2 font-semibold text-slate-700 hover:bg-slate-100 transition shadow-sm"
                >
                    Tutup
                </button>
            </div>
        </div>
    </div>

</div>

{{-- =============================================================== --}}
{{-- ALPINE --}}
{{-- =============================================================== --}}

<script>
function invoiceForm() {
    return {

        /*
        |--------------------------------------------------------------------------
        | HEADER DATA
        |--------------------------------------------------------------------------
        */

        penjualanId:
            @json(
                (string) old(
                    'penjualan_id',
                    request(
                        'penjualan_id',
                        ''
                    )
                )
            ),

        customerId:
            @json(
                (string) old(
                    'customer_id',
                    $selectedPenjualan->customer_id ?? ''
                )
            ),

        gudangId:
            @json(
                (string) old(
                    'gudang_id',
                    $selectedPenjualan->gudang_id ?? ''
                )
            ),

        pajakPersen:
            @json(
                (float) old(
                    'pajak_persen',
                    $selectedPenjualan->pajak_persen ?? 0
                )
            ),

        /*
        |--------------------------------------------------------------------------
        | DATA FROM SERVER
        |--------------------------------------------------------------------------
        */

        items:
            @json(
                old(
                    'items',
                    $initialItems
                )
            ),

        penjualansData:
            @json(
                $penjualans
                    ->keyBy('id')
            ),

        barangsData:
            @json(
                $barangs
                    ->keyBy('id')
            ),

        gudangsData:
            @json(
                $gudangs
                    ->keyBy('id')
            ),

        /*
        |--------------------------------------------------------------------------
        | POP-UP MODAL BARANG STATE
        |--------------------------------------------------------------------------
        */

        showBarangModal: false,
        activeItemIndex: null,
        searchBarang: '',
        filterKategori: '',
        hanyaTersedia: false,

        openBarangModal(index) {
            if (!this.gudangId) {
                alert('Silakan pilih Gudang terlebih dahulu sebelum memilih barang.');
                return;
            }
            this.activeItemIndex = index;
            this.searchBarang = '';
            this.filterKategori = '';
            this.showBarangModal = true;
            this.$nextTick(() => {
                this.$refs.searchInput?.focus();
            });
        },

        closeBarangModal() {
            this.showBarangModal = false;
            this.activeItemIndex = null;
        },

        pilihBarang(barang) {
            const stok = this.getStokBarang(barang.id);
            if (stok <= 0) {
                alert(`Barang "${barang.nama_barang}" memiliki stok 0 di gudang yang dipilih dan tidak dapat ditambahkan ke invoice.`);
                return;
            }
            if (this.activeItemIndex !== null && this.items[this.activeItemIndex]) {
                this.items[this.activeItemIndex].barang_id = String(barang.id);
                this.onBarangChange(this.activeItemIndex);
            }
            this.closeBarangModal();
        },

        getSupplierNames(barang) {
            if (!barang) return '-';
            const sups = barang.suppliers || [];
            if (Array.isArray(sups) && sups.length > 0) {
                return sups.map(s => s.nama_supplier).filter(Boolean).join(', ');
            }
            return '-';
        },

        getGudangNama() {
            if (!this.gudangId) return 'Belum dipilih';
            return this.gudangsData[this.gudangId]?.nama_gudang || ('Gudang #' + this.gudangId);
        },

        getKategoriList() {
            const list = new Set();
            Object.values(this.barangsData).forEach(b => {
                if (b.kategori && b.kategori.nama_kategori) {
                    list.add(b.kategori.nama_kategori);
                }
            });
            return Array.from(list).sort();
        },

        getFilteredBarangs() {
            const q = (this.searchBarang || '').toLowerCase().trim();
            const kat = (this.filterKategori || '').toLowerCase().trim();

            return Object.values(this.barangsData).filter(b => {
                if (this.hanyaTersedia && this.getStokBarang(b.id) <= 0) {
                    return false;
                }

                if (kat) {
                    const bKat = (b.kategori?.nama_kategori || '').toLowerCase();
                    if (bKat !== kat) return false;
                }

                if (!q) return true;

                const nama = (b.nama_barang || '').toLowerCase();
                const kode = (b.kode_barang || '').toLowerCase();
                const kategori = (b.kategori?.nama_kategori || '').toLowerCase();
                const suppliers = (b.suppliers || []).map(s => (s.nama_supplier || '').toLowerCase()).join(' ');

                return nama.includes(q) || kode.includes(q) || kategori.includes(q) || suppliers.includes(q);
            });
        },

        /*
        |--------------------------------------------------------------------------
        | ADD ITEM
        |--------------------------------------------------------------------------
        */

        addItem() {
            this.items.push({
                barang_id: '',
                qty: 1,
                harga_satuan: 0,
                diskon: 0,
                catatan: '',
            });
        },

        /*
        |--------------------------------------------------------------------------
        | REMOVE ITEM
        |--------------------------------------------------------------------------
        */

        removeItem(index) {
            if (
                this.items.length
                <= 1
            ) {
                return;
            }

            this.items.splice(
                index,
                1
            );
        },

        /*
        |--------------------------------------------------------------------------
        | BARANG CHANGE
        |--------------------------------------------------------------------------
        */

        onBarangChange(index) {
            const barangId =
                this.items[index]
                    .barang_id;

            if (
                !barangId
                || !this.barangsData[
                    barangId
                ]
            ) {
                return;
            }

            const barang =
                this.barangsData[
                    barangId
                ];

            /*
            |--------------------------------------------------------------------------
            | Harga Jual
            |--------------------------------------------------------------------------
            */

            let harga =
                parseFloat(
                    barang.harga_jual
                    || 0
                );

            /*
            | Fallback jika harga jual belum ada.
            */

            if (
                harga <= 0
                &&
                parseFloat(
                    barang.harga_beli_default
                    || 0
                ) > 0
            ) {
                harga =
                    Math.round(
                        parseFloat(
                            barang.harga_beli_default
                        )
                        * 1.15
                    );
            }

            this.items[index]
                .harga_satuan =
                    harga;
        },

        /*
        |--------------------------------------------------------------------------
        | GUDANG CHANGE
        |--------------------------------------------------------------------------
        */

        onGudangChange() {
            /*
            | Tidak perlu mengubah barang.
            |
            | Stok pada setiap item otomatis
            | mengikuti gudang yang sedang dipilih.
            */
        },

        /*
        |--------------------------------------------------------------------------
        | GET STOK PER GUDANG
        |--------------------------------------------------------------------------
        */

        getStokBarang(barangId) {
            if (
                !barangId
                || !this.gudangId
            ) {
                return 0;
            }

            const barang =
                this.barangsData[
                    barangId
                ];

            if (!barang) {
                return 0;
            }

            /*
            |--------------------------------------------------------------------------
            | Laravel Relation
            |--------------------------------------------------------------------------
            |
            | Relasi stokGudangs pada JSON umumnya
            | menjadi stok_gudangs.
            |
            */

            const stokGudangs =
                barang.stok_gudangs
                || barang.stokGudangs
                || [];

            if (
                !Array.isArray(
                    stokGudangs
                )
            ) {
                return 0;
            }

            const stok =
                stokGudangs.find(
                    row =>
                        String(
                            row.gudang_id
                        )
                        ===
                        String(
                            this.gudangId
                        )
                );

            if (!stok) {
                return 0;
            }

            return parseFloat(
                stok.qty
                || 0
            );
        },

        /*
        |--------------------------------------------------------------------------
        | SALES ORDER CHANGE
        |--------------------------------------------------------------------------
        */

        onPenjualanChange() {
            /*
            |--------------------------------------------------------------------------
            | Tanpa SO
            |--------------------------------------------------------------------------
            */

            if (
                !this.penjualanId
            ) {
                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Ambil SO
            |--------------------------------------------------------------------------
            */

            const so =
                this.penjualansData[
                    this.penjualanId
                ];

            if (!so) {
                return;
            }

            /*
            |--------------------------------------------------------------------------
            | Customer
            |--------------------------------------------------------------------------
            */

            this.customerId =
                String(
                    so.customer_id
                    || ''
                );

            /*
            |--------------------------------------------------------------------------
            | Gudang
            |--------------------------------------------------------------------------
            */

            if (so.gudang_id) {
                this.gudangId =
                    String(
                        so.gudang_id
                    );
            }

            /*
            |--------------------------------------------------------------------------
            | PPN
            |--------------------------------------------------------------------------
            */

            this.pajakPersen =
                parseFloat(
                    so.pajak_persen
                    || 0
                );

            /*
            |--------------------------------------------------------------------------
            | Items
            |--------------------------------------------------------------------------
            |
            | Hanya masukkan sisa qty yang belum di-invoice.
            |
            */

            if (
                Array.isArray(
                    so.items
                )
                &&
                so.items.length > 0
            ) {
                const remainingItems =
                    so.items
                        .map(
                            item => {

                                const qtySO =
                                    parseFloat(
                                        item.qty
                                        || 0
                                    );

                                const qtyTerinvoice =
                                    parseFloat(
                                        item.qty_terinvoice
                                        || 0
                                    );

                                const sisaQty =
                                    Math.max(
                                        0,
                                        qtySO
                                        -
                                        qtyTerinvoice
                                    );

                                return {
                                    barang_id:
                                        String(
                                            item.barang_id
                                            || ''
                                        ),

                                    qty:
                                        sisaQty,

                                    harga_satuan:
                                        parseFloat(
                                            item.harga_satuan
                                            || 0
                                        ),

                                    diskon:
                                        parseFloat(
                                            item.diskon
                                            || 0
                                        ),

                                    catatan:
                                        item.catatan
                                        || '',
                                };
                            }
                        )
                        .filter(
                            item =>
                                item.qty > 0
                        );

                /*
                |--------------------------------------------------------------------------
                | Jika Ada Sisa
                |--------------------------------------------------------------------------
                */

                if (
                    remainingItems.length
                    > 0
                ) {
                    this.items =
                        remainingItems;
                } else {
                    this.items = [
                        {
                            barang_id: '',
                            qty: 1,
                            harga_satuan: 0,
                            diskon: 0,
                            catatan: '',
                        }
                    ];
                }
            }
        },

        /*
        |--------------------------------------------------------------------------
        | ITEM SUBTOTAL
        |--------------------------------------------------------------------------
        */

        getItemSubtotal(item) {
            const qty =
                parseFloat(
                    item.qty
                    || 0
                );

            const harga =
                parseFloat(
                    item.harga_satuan
                    || 0
                );

            const diskon =
                parseFloat(
                    item.diskon
                    || 0
                );

            return Math.max(
                0,
                (qty * harga)
                -
                diskon
            );
        },

        /*
        |--------------------------------------------------------------------------
        | SUBTOTAL
        |--------------------------------------------------------------------------
        */

        calculateSubtotal() {
            return this.items.reduce(
                (total, item) => {
                    return total
                        +
                        (
                            parseFloat(
                                item.qty
                                || 0
                            )
                            *
                            parseFloat(
                                item.harga_satuan
                                || 0
                            )
                        );
                },
                0
            );
        },

        /*
        |--------------------------------------------------------------------------
        | TOTAL DISKON
        |--------------------------------------------------------------------------
        */

        calculateTotalDiskon() {
            return this.items.reduce(
                (total, item) => {
                    return total
                        +
                        parseFloat(
                            item.diskon
                            || 0
                        );
                },
                0
            );
        },

        /*
        |--------------------------------------------------------------------------
        | DPP
        |--------------------------------------------------------------------------
        */

        calculateDpp() {
            return Math.max(
                0,
                this.calculateSubtotal()
                -
                this.calculateTotalDiskon()
            );
        },

        /*
        |--------------------------------------------------------------------------
        | PAJAK
        |--------------------------------------------------------------------------
        */

        calculatePajak() {
            const dpp =
                this.calculateDpp();

            const persen =
                parseFloat(
                    this.pajakPersen
                    || 0
                );

            return (
                dpp
                *
                persen
            ) / 100;
        },

        /*
        |--------------------------------------------------------------------------
        | GRAND TOTAL
        |--------------------------------------------------------------------------
        */

        calculateGrandTotal() {
            return (
                this.calculateDpp()
                +
                this.calculatePajak()
            );
        },

        /*
        |--------------------------------------------------------------------------
        | CEK STOK
        |--------------------------------------------------------------------------
        */

        hasInsufficientStock() {
            if (
                !this.gudangId
            ) {
                return false;
            }

            return this.items.some(
                item => {

                    if (
                        !item.barang_id
                    ) {
                        return false;
                    }

                    const qty =
                        parseFloat(
                            item.qty
                            || 0
                        );

                    const stok =
                        this.getStokBarang(
                            item.barang_id
                        );

                    return (
                        stok <= 0
                        || qty > stok
                    );
                }
            );
        },

        /*
        |--------------------------------------------------------------------------
        | CEK DISKON
        |--------------------------------------------------------------------------
        */

        hasInvalidDiscount() {
            return this.items.some(
                item => {

                    const qty =
                        parseFloat(
                            item.qty
                            || 0
                        );

                    const harga =
                        parseFloat(
                            item.harga_satuan
                            || 0
                        );

                    const diskon =
                        parseFloat(
                            item.diskon
                            || 0
                        );

                    return (
                        diskon
                        >
                        (
                            qty
                            *
                            harga
                        )
                    );
                }
            );
        },

        /*
        |--------------------------------------------------------------------------
        | FORMAT RUPIAH
        |--------------------------------------------------------------------------
        */

        formatRupiah(num) {
            const value =
                Math.round(
                    parseFloat(
                        num
                        || 0
                    )
                );

            return (
                'Rp '
                +
                value.toLocaleString(
                    'id-ID'
                )
            );
        },

        /*
        |--------------------------------------------------------------------------
        | FORMAT QTY
        |--------------------------------------------------------------------------
        */

        formatQty(num) {
            const value =
                parseFloat(
                    num
                    || 0
                );

            return value.toLocaleString(
                'id-ID',
                {
                    maximumFractionDigits: 4,
                }
            );
        },

        /*
        |--------------------------------------------------------------------------
        | HANDLE SUBMIT VALIDATION
        |--------------------------------------------------------------------------
        */

        handleSubmit(e) {
            if (!this.gudangId) {
                e.preventDefault();
                alert('Silakan pilih Gudang terlebih dahulu sebelum menerbitkan invoice.');
                return;
            }

            for (let i = 0; i < this.items.length; i++) {
                const item = this.items[i];
                if (!item.barang_id) {
                    e.preventDefault();
                    alert(`Baris ke-${i + 1} belum memilih barang.`);
                    return;
                }

                const stok = this.getStokBarang(item.barang_id);
                const b = this.barangsData[item.barang_id];
                const nama = b ? b.nama_barang : ('Barang #' + item.barang_id);

                if (stok <= 0) {
                    e.preventDefault();
                    alert(`Barang "${nama}" memiliki stok 0 di gudang yang dipilih. Barang dengan stok 0 tidak dapat diterbitkan ke invoice.`);
                    return;
                }

                const qty = parseFloat(item.qty || 0);
                if (qty > stok) {
                    e.preventDefault();
                    alert(`Kuantitas untuk "${nama}" (${qty}) melebihi stok yang tersedia (${stok}).`);
                    return;
                }
            }
        },
    };
}
</script>

@endsection