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
                {{-- TIPE PEMBAYARAN --}}
                {{-- ================================================= --}}

                <div>
                    <label
                        class="block text-xs font-semibold uppercase tracking-wider text-slate-600"
                    >
                        Tipe Pembayaran
                        <span class="text-rose-500">*</span>
                    </label>

                    <select
                        name="tipe_pembayaran"
                        x-model="tipePembayaran"
                        required
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-semibold text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                    >
                        <option value="Tunai">
                            Tunai (Cash - Langsung Lunas)
                        </option>

                        <option value="Kredit">
                            Kredit / Tempo (Piutang Dagang)
                        </option>
                    </select>
                </div>

                {{-- ================================================= --}}
                {{-- REKENING KAS/BANK --}}
                {{-- ================================================= --}}

                <div
                    x-show="tipePembayaran === 'Tunai'"
                    x-cloak
                >
                    <label
                        class="block text-xs font-semibold uppercase tracking-wider text-slate-600"
                    >
                        Terima ke Kas / Rekening Bank
                        <span class="text-rose-500">*</span>
                    </label>

                    <select
                        name="rekening_kas_bank_id"
                        :required="tipePembayaran === 'Tunai'"
                        class="mt-2 w-full rounded-xl border border-teal-300 bg-teal-50/50 px-4 py-2.5 text-sm text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                    >
                        <option value="">
                            -- Pilih Rekening Kas/Bank --
                        </option>

                        @foreach($rekeningKasBanks as $rek)
                            <option
                                value="{{ $rek->id }}"
                                {{ old('rekening_kas_bank_id') == $rek->id ? 'selected' : '' }}
                            >
                                {{ $rek->nama_rekening }}
                                ({{ $rek->nama_bank ?? 'Kas' }})
                            </option>
                        @endforeach
                    </select>

                    <p class="mt-1 text-[11px] text-teal-600">
                        Saldo kas/bank akan bertambah otomatis saat disimpan.
                    </p>
                </div>

                {{-- ================================================= --}}
                {{-- JATUH TEMPO --}}
                {{-- ================================================= --}}

                <div
                    x-show="tipePembayaran === 'Kredit'"
                    x-cloak
                >
                    <label
                        class="block text-xs font-semibold uppercase tracking-wider text-slate-600"
                    >
                        Tanggal Jatuh Tempo
                        <span class="text-rose-500">*</span>
                    </label>

                    <input
                        type="date"
                        name="tanggal_jatuh_tempo"
                        value="{{ old(
                            'tanggal_jatuh_tempo',
                            date('Y-m-d', strtotime('+30 days'))
                        ) }}"
                        :required="tipePembayaran === 'Kredit'"
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

                                    <select
                                        :name="`items[${index}][barang_id]`"
                                        x-model="item.barang_id"
                                        @change="onBarangChange(index)"
                                        required
                                        class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                                    >
                                        <option value="">
                                            -- Pilih Barang --
                                        </option>

                                        @foreach($barangs as $barang)
                                            <option
                                                value="{{ $barang->id }}"
                                            >
                                                {{ $barang->kode_barang }}
                                                -
                                                {{ $barang->nama_barang }}
                                            </option>
                                        @endforeach
                                    </select>

                                    {{-- ===================================== --}}
                                    {{-- STOK GUDANG --}}
                                    {{-- ===================================== --}}

                                    <div
                                        class="mt-1.5 text-[11px]"
                                        x-show="item.barang_id"
                                    >

                                        <template x-if="!gudangId">
                                            <span class="text-amber-600">
                                                Pilih gudang terlebih dahulu.
                                            </span>
                                        </template>

                                        <template x-if="gudangId">
                                            <div>
                                                <span
                                                    :class="
                                                        getStokBarang(item.barang_id)
                                                        >= parseFloat(item.qty || 0)
                                                            ? 'text-emerald-600'
                                                            : 'text-rose-600'
                                                    "
                                                >
                                                    Stok gudang:

                                                    <span
                                                        class="font-semibold"
                                                        x-text="formatQty(
                                                            getStokBarang(
                                                                item.barang_id
                                                            )
                                                        )"
                                                    ></span>
                                                </span>

                                                <span
                                                    x-show="
                                                        parseFloat(item.qty || 0)
                                                        >
                                                        getStokBarang(
                                                            item.barang_id
                                                        )
                                                    "
                                                    class="ml-1 font-semibold text-rose-600"
                                                >
                                                    — Stok tidak mencukupi
                                                </span>
                                            </div>
                                        </template>

                                    </div>

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
                                            && parseFloat(item.qty || 0)
                                                >
                                                getStokBarang(
                                                    item.barang_id
                                                )
                                                ? 'border-rose-300 bg-rose-50'
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
            <div class="font-semibold">
                Stok tidak mencukupi
            </div>

            <div class="mt-1 text-xs">
                Ada barang dengan jumlah penjualan yang melebihi stok
                pada gudang yang dipilih.
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

        tipePembayaran:
            @json(
                old(
                    'tipe_pembayaran',
                    'Tunai'
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
                        qty > stok
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
    };
}
</script>

@endsection