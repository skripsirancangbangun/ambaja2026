@extends('layouts.app')

@section('title', 'Buat Konversi Barang')

@section('content')

@php

    /*
    |--------------------------------------------------------------------------
    | DATA UNTUK JAVASCRIPT
    |--------------------------------------------------------------------------
    */

    $stockDataForJs = $stokGudangs
        ->map(function ($stok) {
            return [
                'barang_id' => (int) $stok->barang_id,
                'gudang_id' => (int) $stok->gudang_id,
                'qty' => (float) $stok->qty,
                'hpp' => (float) $stok->harga_rata_rata,
                'nilai' => (float) $stok->nilai_persediaan,
            ];
        })
        ->values()
        ->toArray();


    $barangDataForJs = $barangs
        ->map(function ($barang) {
            return [
                'id' => (int) $barang->id,
                'kode' => $barang->kode_barang,
                'nama' => $barang->nama_barang,
                'satuan' => $barang->satuan?->nama_satuan,
            ];
        })
        ->values()
        ->toArray();

@endphp


<div class="mx-auto max-w-7xl space-y-6">

    {{-- ==========================================================
        HEADER
    =========================================================== --}}

    <div>

        <a
            href="{{ route('conversions.index') }}"
            class="mb-3 inline-flex items-center gap-2 text-sm text-slate-500 hover:text-[#15235F]"
        >
            ← Kembali
        </a>

        <h2 class="text-xl font-semibold text-slate-800">
            Buat Konversi Barang
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            Ubah barang asal menjadi bentuk atau ukuran lain dengan nilai
            persediaan yang tetap terjaga dan HPP barang hasil dihitung otomatis.
        </p>

    </div>


    {{-- ==========================================================
        VALIDATION ERROR
    =========================================================== --}}

    @if($errors->any())

        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3">

            <p class="text-sm font-semibold text-red-700">
                Periksa kembali data yang diinput.
            </p>

            <ul class="mt-2 list-disc space-y-1 pl-5 text-xs text-red-600">

                @foreach($errors->all() as $error)

                    <li>
                        {{ $error }}
                    </li>

                @endforeach

            </ul>

        </div>

    @endif


    {{-- ==========================================================
        SESSION ERROR
    =========================================================== --}}

    @if(session('error'))

        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>

    @endif


    {{-- ==========================================================
        FORM
    =========================================================== --}}

    <form
        action="{{ route('conversions.store') }}"
        method="POST"
        id="conversion-form"
        class="space-y-6"
    >

        @csrf


        {{-- ======================================================
            INFORMASI KONVERSI
        ======================================================= --}}

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <div class="mb-5">

                <h3 class="text-sm font-semibold text-slate-700">
                    Informasi Konversi
                </h3>

                <p class="mt-1 text-xs text-slate-400">
                    Tentukan gudang dan tanggal transaksi konversi.
                </p>

            </div>


            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">


                {{-- TANGGAL --}}
                <div>

                    <label
                        for="tanggal"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Tanggal Konversi
                    </label>

                    <input
                        type="date"
                        id="tanggal"
                        name="tanggal"
                        value="{{ old('tanggal', $tanggalDefault ?? now()->format('Y-m-d')) }}"
                        required
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-700 outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >

                </div>


                {{-- GUDANG --}}
                <div>

                    <label
                        for="gudang_id"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Gudang
                    </label>

                    <select
                        id="gudang_id"
                        name="gudang_id"
                        required
                        class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-700 outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >

                        <option value="">
                            -- Pilih Gudang --
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

            </div>

        </div>


        {{-- ======================================================
            BARANG ASAL
        ======================================================= --}}

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <div class="mb-5">

                <h3 class="text-sm font-semibold text-slate-700">
                    Barang Asal
                </h3>

                <p class="mt-1 text-xs text-slate-400">
                    Stok dan HPP diambil otomatis dari saldo persediaan
                    pada gudang yang dipilih.
                </p>

            </div>


            <div class="grid grid-cols-1 gap-5 lg:grid-cols-3">


                {{-- BARANG ASAL --}}
                <div class="lg:col-span-2">

                    <label
                        for="barang_input_id"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Barang Asal
                    </label>

                    <select
                        id="barang_input_id"
                        name="barang_input_id"
                        required
                        class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-700 outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >

                        <option value="">
                            -- Pilih Barang Asal --
                        </option>

                        @foreach($barangs as $barang)

                            <option
                                value="{{ $barang->id }}"
                                {{ old('barang_input_id') == $barang->id ? 'selected' : '' }}
                            >
                                {{ $barang->kode_barang }}
                                -
                                {{ $barang->nama_barang }}
                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- QTY INPUT --}}
                <div>

                    <label
                        for="qty_input"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Qty yang Dikonversi
                    </label>

                    <input
                        type="number"
                        id="qty_input"
                        name="qty_input"
                        value="{{ old('qty_input') }}"
                        min="0.01"
                        step="0.01"
                        required
                        placeholder="0"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-700 outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >

                </div>

            </div>


            {{-- ==================================================
                INFO STOK
            =================================================== --}}

            <div
                id="input-stock-info"
                class="mt-5 hidden rounded-xl border border-slate-200 bg-slate-50 p-4"
            >

                <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">


                    {{-- STOK --}}
                    <div>

                        <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">
                            Stok Tersedia
                        </p>

                        <p
                            id="available-stock"
                            class="mt-1 text-lg font-bold text-[#15235F]"
                        >
                            0
                        </p>

                    </div>


                    {{-- HPP --}}
                    <div>

                        <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">
                            HPP Rata-rata
                        </p>

                        <p
                            id="input-hpp"
                            class="mt-1 text-lg font-bold text-[#15235F]"
                        >
                            Rp 0
                        </p>

                    </div>


                    {{-- NILAI --}}
                    <div>

                        <p class="text-[11px] font-medium uppercase tracking-wide text-slate-400">
                            Nilai Konversi
                        </p>

                        <p
                            id="input-value"
                            class="mt-1 text-lg font-bold text-[#15235F]"
                        >
                            Rp 0
                        </p>

                    </div>

                </div>

            </div>

        </div>


        {{-- ======================================================
            BARANG HASIL
        ======================================================= --}}

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">

                <div>

                    <h3 class="text-sm font-semibold text-slate-700">
                        Barang Hasil Konversi
                    </h3>

                    <p class="mt-1 text-xs text-slate-400">
                        Tambahkan satu atau beberapa barang hasil.
                        HPP dihitung otomatis oleh sistem.
                    </p>

                </div>


                <button
                    type="button"
                    id="add-output"
                    class="inline-flex items-center justify-center rounded-lg border border-[#15235F] bg-white px-3.5 py-2 text-xs font-medium text-[#15235F] transition hover:bg-slate-50"
                >
                    + Tambah Barang Hasil
                </button>

            </div>


            {{-- OUTPUT CONTAINER --}}
            <div
                id="outputs-container"
                class="mt-6 space-y-4"
            >
            </div>


            {{-- ==================================================
                TOTAL OUTPUT
            =================================================== --}}

            <div class="mt-6 border-t border-slate-200 pt-5">

                <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">

                    <div>

                        <p class="text-sm font-semibold text-slate-700">
                            Total Nilai Barang Hasil
                        </p>

                        <p class="text-xs text-slate-400">
                            Harus sama dengan nilai barang asal.
                        </p>

                    </div>


                    <div class="text-left sm:text-right">

                        <p
                            id="output-total-value"
                            class="text-xl font-bold text-[#15235F]"
                        >
                            Rp 0
                        </p>

                        <p
                            id="value-difference"
                            class="mt-1 text-xs text-slate-400"
                        >
                            Selisih: Rp 0
                        </p>

                    </div>

                </div>

            </div>

        </div>


        {{-- ======================================================
            KETERANGAN
        ======================================================= --}}

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <label
                for="keterangan"
                class="mb-2 block text-sm font-medium text-slate-700"
            >
                Keterangan
            </label>

            <textarea
                id="keterangan"
                name="keterangan"
                rows="3"
                maxlength="1000"
                placeholder="Contoh: Pemotongan hollow 6 meter menjadi hollow 2 meter."
                class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-700 outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
            >{{ old('keterangan') }}</textarea>

        </div>


        {{-- ======================================================
            PERHATIAN
        ======================================================= --}}

        <div class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-4">

            <p class="text-sm font-semibold text-amber-800">
                Perhatian
            </p>

            <ul class="mt-2 list-disc space-y-1 pl-5 text-xs leading-5 text-amber-700">

                <li>
                    HPP barang hasil tidak perlu diinput manual.
                </li>

                <li>
                    Sistem menggunakan HPP moving average barang asal.
                </li>

                <li>
                    Nilai persediaan barang asal akan dialihkan ke barang hasil.
                </li>

                <li>
                    Pastikan qty yang dikonversi tidak melebihi stok tersedia.
                </li>

                <li>
                    Barang hasil tidak boleh sama dengan barang asal.
                </li>

                <li>
                    Setelah berhasil diproses, transaksi berstatus POSTED.
                </li>

            </ul>

        </div>


        {{-- ======================================================
            BUTTON
        ======================================================= --}}

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">

            <a
                href="{{ route('conversions.index') }}"
                class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
            >
                Batal
            </a>


            <button
                type="submit"
                id="submit-button"
                class="inline-flex items-center justify-center rounded-lg bg-[#15235F] px-5 py-2.5 text-sm font-medium text-white transition hover:bg-[#101b4d]"
            >
                Proses Konversi
            </button>

        </div>

    </form>

</div>


{{-- ================================================================
    JAVASCRIPT
================================================================ --}}

<script>

document.addEventListener('DOMContentLoaded', function () {


    /* =============================================================
       ELEMENT
    ============================================================= */

    const gudangSelect =
        document.getElementById('gudang_id');

    const barangInputSelect =
        document.getElementById('barang_input_id');

    const qtyInput =
        document.getElementById('qty_input');

    const outputsContainer =
        document.getElementById('outputs-container');

    const addOutputButton =
        document.getElementById('add-output');

    const inputStockInfo =
        document.getElementById('input-stock-info');

    const availableStock =
        document.getElementById('available-stock');

    const inputHpp =
        document.getElementById('input-hpp');

    const inputValue =
        document.getElementById('input-value');

    const outputTotalValue =
        document.getElementById('output-total-value');

    const valueDifference =
        document.getElementById('value-difference');

    const conversionForm =
        document.getElementById('conversion-form');

    const submitButton =
        document.getElementById('submit-button');


    /* =============================================================
       DATA DARI SERVER
    ============================================================= */

    const stockData =
        {!! json_encode($stockDataForJs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!};


    const barangData =
        {!! json_encode($barangDataForJs, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) !!};


    let outputIndex = 0;


    /* =============================================================
       FORMAT ANGKA
    ============================================================= */

    function formatNumber(number) {

        return new Intl.NumberFormat(
            'id-ID',
            {
                minimumFractionDigits: 0,
                maximumFractionDigits: 2
            }
        ).format(number || 0);

    }


    /* =============================================================
       FORMAT RUPIAH
    ============================================================= */

    function formatCurrency(number) {

        return 'Rp ' +
            new Intl.NumberFormat(
                'id-ID',
                {
                    minimumFractionDigits: 0,
                    maximumFractionDigits: 0
                }
            ).format(number || 0);

    }


    /* =============================================================
       CARI STOK
    ============================================================= */

    function getInputStock() {

        const gudangId =
            parseInt(
                gudangSelect.value || 0
            );


        const barangId =
            parseInt(
                barangInputSelect.value || 0
            );


        if (
            !gudangId ||
            !barangId
        ) {

            return null;

        }


        return stockData.find(function (stock) {

            return (
                parseInt(stock.gudang_id)
                ===
                gudangId
            )
            &&
            (
                parseInt(stock.barang_id)
                ===
                barangId
            );

        }) || null;

    }


    /* =============================================================
       UPDATE INFO BARANG INPUT
    ============================================================= */

    function updateInputInfo() {

        const stock =
            getInputStock();


        if (!stock) {

            inputStockInfo.classList.add('hidden');

            availableStock.textContent =
                '0';

            inputHpp.textContent =
                'Rp 0';

            inputValue.textContent =
                'Rp 0';

            calculateOutputs();

            return;

        }


        inputStockInfo.classList.remove('hidden');


        availableStock.textContent =
            formatNumber(
                stock.qty
            );


        inputHpp.textContent =
            formatCurrency(
                stock.hpp
            );


        const qty =
            parseFloat(
                qtyInput.value || 0
            );


        const value =
            qty * stock.hpp;


        inputValue.textContent =
            formatCurrency(
                value
            );


        calculateOutputs();

    }


    /* =============================================================
       AMBIL BARIS OUTPUT
    ============================================================= */

    function getOutputRows() {

        return Array.from(
            outputsContainer.querySelectorAll(
                '.output-row'
            )
        );

    }


    /* =============================================================
       HITUNG OUTPUT
    ============================================================= */

    function calculateOutputs() {

        const stock =
            getInputStock();


        const qtyInputValue =
            parseFloat(
                qtyInput.value || 0
            );


        const inputTotalValue =
            stock
                ? qtyInputValue * stock.hpp
                : 0;


        const rows =
            getOutputRows();


        let totalWeight =
            0;


        const rowWeights =
            [];


        /*
         * Tentukan bobot setiap output.
         *
         * Prioritas:
         *
         * 1. Berat
         * 2. Panjang x Qty
         * 3. Qty
         */

        rows.forEach(function (row) {

            const qty =
                parseFloat(
                    row.querySelector(
                        '.output-qty'
                    )?.value || 0
                );


            const panjang =
                parseFloat(
                    row.querySelector(
                        '.output-panjang'
                    )?.value || 0
                );


            const berat =
                parseFloat(
                    row.querySelector(
                        '.output-berat'
                    )?.value || 0
                );


            let weight =
                0;


            if (berat > 0) {

                weight =
                    berat;

            }
            else if (panjang > 0) {

                weight =
                    panjang * qty;

            }
            else {

                weight =
                    qty;

            }


            rowWeights.push(
                weight
            );


            totalWeight +=
                weight;

        });


        let totalOutputValue =
            0;


        /*
         * Alokasikan nilai input ke masing-masing output.
         */

        rows.forEach(function (row, index) {

            const qty =
                parseFloat(
                    row.querySelector(
                        '.output-qty'
                    )?.value || 0
                );


            const hppElement =
                row.querySelector(
                    '.output-hpp'
                );


            const valueElement =
                row.querySelector(
                    '.output-value'
                );


            let value =
                0;


            if (
                inputTotalValue > 0
                &&
                totalWeight > 0
            ) {

                value =
                    inputTotalValue
                    *
                    (
                        rowWeights[index]
                        /
                        totalWeight
                    );

            }


            const hpp =
                qty > 0
                    ? value / qty
                    : 0;


            totalOutputValue +=
                value;


            if (hppElement) {

                hppElement.textContent =
                    formatCurrency(
                        hpp
                    );

            }


            if (valueElement) {

                valueElement.textContent =
                    formatCurrency(
                        value
                    );

            }

        });


        outputTotalValue.textContent =
            formatCurrency(
                totalOutputValue
            );


        const difference =
            inputTotalValue
            -
            totalOutputValue;


        valueDifference.textContent =
            'Selisih: '
            +
            formatCurrency(
                Math.abs(difference)
            );


        if (
            Math.abs(difference)
            <=
            0.02
        ) {

            valueDifference.className =
                'mt-1 text-xs text-emerald-600';

        }
        else {

            valueDifference.className =
                'mt-1 text-xs text-red-600';

        }

    }


    /* =============================================================
       OPTION BARANG
    ============================================================= */

    function barangOptions(
        selected = ''
    ) {

        let html =
            '<option value="">-- Pilih Barang Hasil --</option>';


        barangData.forEach(function (barang) {

            html +=
                '<option value="'
                +
                barang.id
                +
                '" '
                +
                (
                    String(selected)
                    ===
                    String(barang.id)
                        ? 'selected'
                        : ''
                )
                +
                '>'
                +
                barang.kode
                +
                ' - '
                +
                barang.nama
                +
                '</option>';

        });


        return html;

    }


    /* =============================================================
       TAMBAH BARIS OUTPUT
    ============================================================= */

    function addOutputRow(
        selectedBarang = '',
        qty = '',
        panjang = '',
        berat = ''
    ) {

        const index =
            outputIndex++;


        const row =
            document.createElement(
                'div'
            );


        row.className =
            'output-row rounded-xl border border-slate-200 bg-slate-50 p-4';


        row.dataset.index =
            index;


        row.innerHTML = `

            <div class="flex items-center justify-between gap-3">

                <div>

                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Barang Hasil #${index + 1}
                    </p>

                </div>


                <button
                    type="button"
                    class="remove-output rounded-lg px-2.5 py-1.5 text-xs font-medium text-red-500 transition hover:bg-red-50"
                >
                    Hapus
                </button>

            </div>


            <div class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">


                {{-- BARANG --}}

                <div class="xl:col-span-2">

                    <label class="mb-2 block text-xs font-medium text-slate-600">
                        Barang Hasil
                    </label>

                    <select
                        name="outputs[${index}][barang_id]"
                        required
                        class="output-barang w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >

                        ${barangOptions(selectedBarang)}

                    </select>

                </div>


                {{-- QTY --}}

                <div>

                    <label class="mb-2 block text-xs font-medium text-slate-600">
                        Qty
                    </label>

                    <input
                        type="number"
                        name="outputs[${index}][qty]"
                        value="${qty}"
                        min="0.01"
                        step="0.01"
                        required
                        class="output-qty w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                        placeholder="0"
                    >

                </div>


                {{-- PANJANG --}}

                <div>

                    <label class="mb-2 block text-xs font-medium text-slate-600">
                        Panjang
                    </label>

                    <input
                        type="number"
                        name="outputs[${index}][panjang]"
                        value="${panjang}"
                        min="0"
                        step="0.0001"
                        class="output-panjang w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                        placeholder="Opsional"
                    >

                </div>


                {{-- BERAT --}}

                <div>

                    <label class="mb-2 block text-xs font-medium text-slate-600">
                        Berat
                    </label>

                    <input
                        type="number"
                        name="outputs[${index}][berat]"
                        value="${berat}"
                        min="0"
                        step="0.0001"
                        class="output-berat w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                        placeholder="Opsional"
                    >

                </div>

            </div>


            {{-- HASIL PERHITUNGAN --}}

            <div class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-2">


                <div class="rounded-lg border border-slate-200 bg-white px-3.5 py-3">

                    <p class="text-[10px] font-medium uppercase tracking-wide text-slate-400">
                        HPP Otomatis
                    </p>

                    <p class="output-hpp mt-1 text-sm font-bold text-[#15235F]">
                        Rp 0
                    </p>

                </div>


                <div class="rounded-lg border border-slate-200 bg-white px-3.5 py-3">

                    <p class="text-[10px] font-medium uppercase tracking-wide text-slate-400">
                        Nilai Persediaan
                    </p>

                    <p class="output-value mt-1 text-sm font-bold text-[#15235F]">
                        Rp 0
                    </p>

                </div>

            </div>

        `;


        outputsContainer.appendChild(
            row
        );


        /*
         * Event input output
         */

        row
            .querySelectorAll(
                'input, select'
            )
            .forEach(function (element) {

                element.addEventListener(
                    'input',
                    calculateOutputs
                );


                element.addEventListener(
                    'change',
                    calculateOutputs
                );

            });


        /*
         * Event tombol hapus
         */

        row
            .querySelector(
                '.remove-output'
            )
            .addEventListener(
                'click',
                function () {

                    row.remove();

                    calculateOutputs();

                }
            );


        calculateOutputs();

    }


    /* =============================================================
       EVENT GUDANG
    ============================================================= */

    gudangSelect.addEventListener(
        'change',
        function () {

            updateInputInfo();

        }
    );


    /* =============================================================
       EVENT BARANG INPUT
    ============================================================= */

    barangInputSelect.addEventListener(
        'change',
        function () {

            updateInputInfo();

        }
    );


    /* =============================================================
       EVENT QTY INPUT
    ============================================================= */

    qtyInput.addEventListener(
        'input',
        function () {

            updateInputInfo();

        }
    );


    /* =============================================================
       TAMBAH OUTPUT
    ============================================================= */

    addOutputButton.addEventListener(
        'click',
        function () {

            addOutputRow();

        }
    );


    /* =============================================================
       VALIDASI SUBMIT
    ============================================================= */

    conversionForm.addEventListener(
        'submit',
        function (event) {


            const stock =
                getInputStock();


            const qty =
                parseFloat(
                    qtyInput.value || 0
                );


            const rows =
                getOutputRows();


            /*
             * Validasi gudang + barang
             */

            if (!stock) {

                event.preventDefault();

                alert(
                    'Pilih gudang dan barang asal terlebih dahulu.'
                );

                return;

            }


            /*
             * Validasi qty
             */

            if (qty <= 0) {

                event.preventDefault();

                alert(
                    'Qty barang asal harus lebih dari 0.'
                );

                return;

            }


            /*
             * Validasi stok
             */

            if (qty > stock.qty) {

                event.preventDefault();

                alert(
                    'Qty barang asal melebihi stok tersedia.'
                );

                return;

            }


            /*
             * Validasi output
             */

            if (rows.length === 0) {

                event.preventDefault();

                alert(
                    'Tambahkan minimal satu barang hasil konversi.'
                );

                return;

            }


            /*
             * Barang input
             */

            const inputBarangId =
                parseInt(
                    barangInputSelect.value
                );


            let invalidSameBarang =
                false;


            /*
             * Cek apakah output sama
             * dengan barang input
             */

            rows.forEach(function (row) {

                const outputBarangId =
                    parseInt(
                        row
                            .querySelector(
                                '.output-barang'
                            )
                            .value
                    );


                if (
                    outputBarangId
                    &&
                    outputBarangId
                    ===
                    inputBarangId
                ) {

                    invalidSameBarang =
                        true;

                }

            });


            if (invalidSameBarang) {

                event.preventDefault();

                alert(
                    'Barang hasil tidak boleh sama dengan barang asal.'
                );

                return;

            }


            /*
             * Pastikan setiap output punya qty
             */

            let invalidQty =
                false;


            rows.forEach(function (row) {

                const outputQty =
                    parseFloat(
                        row
                            .querySelector(
                                '.output-qty'
                            )
                            .value
                    );


                const outputBarang =
                    row
                        .querySelector(
                            '.output-barang'
                        )
                        .value;


                if (
                    !outputBarang
                    ||
                    !outputQty
                    ||
                    outputQty <= 0
                ) {

                    invalidQty =
                        true;

                }

            });


            if (invalidQty) {

                event.preventDefault();

                alert(
                    'Pastikan semua barang hasil sudah dipilih dan qty lebih dari 0.'
                );

                return;

            }


            /*
             * Disable tombol agar tidak double submit.
             */

            submitButton.disabled =
                true;


            submitButton.classList.add(
                'opacity-60',
                'cursor-not-allowed'
            );


            submitButton.textContent =
                'Memproses Konversi...';

        }
    );


    /* =============================================================
       DEFAULT 1 BARIS OUTPUT
    ============================================================= */

    addOutputRow();


    /* =============================================================
       UPDATE AWAL
    ============================================================= */

    updateInputInfo();

});

</script>

@endsection