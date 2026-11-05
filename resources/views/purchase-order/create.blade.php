@extends('layouts.app')

@section('title', 'Buat Purchase Order')

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
            Buat Purchase Order
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            Buat pesanan pembelian baru kepada supplier.
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
        action="{{ route('purchase-order.store') }}"
        method="POST"
        class="space-y-6"
    >
        @csrf
        @if($permintaanPembelian)
            <input type="hidden" name="permintaan_pembelian_id" value="{{ old('permintaan_pembelian_id', $permintaanPembelian->id) }}">
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
                PO ini dibuat dari permintaan <strong>{{ $permintaanPembelian->nomor_permintaan }}</strong>. Barang dan kuantitas sudah dikunci sesuai persetujuan Pembelian; pilih supplier dan lengkapi harga.
            </div>
        @endif


        {{-- HEADER PO --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <div class="mb-5">
                <h3 class="text-sm font-semibold text-slate-700">
                    Informasi Purchase Order
                </h3>

                <p class="mt-1 text-xs text-slate-400">
                    Tentukan supplier dan informasi dasar pemesanan.
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
                            {{ $previewNomorPO }}
                        </span>

                        <span class="rounded-full bg-teal-50 px-2.5 py-1 text-[10px] font-medium text-[#32AAA6]">
                            Otomatis
                        </span>

                    </div>

                    <p class="mt-1.5 text-xs text-slate-400">
                        Nomor dibuat otomatis saat Purchase Order disimpan.
                    </p>
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
                        <option value="">
                            Pilih Supplier
                        </option>

                        @foreach($suppliers as $supplier)
                            <option
                                value="{{ $supplier->id }}"
                                {{ old('supplier_id') == $supplier->id ? 'selected' : '' }}
                            >
                                {{ $supplier->kode_supplier }} - {{ $supplier->nama_supplier }}
                            </option>
                        @endforeach
                    </select>

                    @error('supplier_id')
                        <p class="mt-1.5 text-xs text-red-500">
                            {{ $message }}
                        </p>
                    @enderror
                </div>


                {{-- TANGGAL PO --}}
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
                        value="{{ old('tanggal_po', date('Y-m-d')) }}"
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
                        value="{{ old('tanggal_estimasi_datang') }}"
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


        {{-- DETAIL BARANG --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

            <div class="flex flex-col gap-3 border-b border-slate-200 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">

                <div>
                    <h3 class="text-sm font-semibold text-slate-700">
                        Detail Barang
                    </h3>

                    <p class="mt-1 text-xs text-slate-400">
                        Harga otomatis diambil dari harga beli default master barang dan tetap dapat diubah.
                    </p>
                </div>

                <button
                    type="button"
                    id="add-item"
                    @disabled($permintaanPembelian)
                    class="inline-flex items-center justify-center gap-2 rounded-lg bg-teal-50 px-3 py-2 text-xs font-semibold text-[#15235F] transition hover:bg-teal-100 disabled:cursor-not-allowed disabled:opacity-40"
                >
                    <span class="text-base leading-none">+</span>
                    Tambah Barang
                </button>

            </div>


            <div class="overflow-x-auto">

                <table class="w-full min-w-[1050px] text-sm">

                    <thead class="bg-slate-50">
                        <tr class="text-left text-xs uppercase tracking-wide text-slate-400">

                            <th class="px-4 py-3 font-medium">
                                Barang
                            </th>

                            <th class="px-4 py-3 font-medium">
                                Qty
                            </th>

                            <th class="px-4 py-3 font-medium">
                                Harga Satuan
                            </th>

                            <th class="px-4 py-3 font-medium">
                                Diskon
                            </th>

                            <th class="px-4 py-3 font-medium">
                                Subtotal
                            </th>

                            <th class="px-4 py-3 font-medium">
                                Keterangan
                            </th>

                            <th class="px-4 py-3 text-right font-medium">
                                Aksi
                            </th>

                        </tr>
                    </thead>

                    <tbody
                        id="items-container"
                        class="divide-y divide-slate-100"
                    ></tbody>

                </table>

            </div>

        </div>


        {{-- CATATAN DAN TOTAL --}}
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
                        placeholder="Catatan Purchase Order (opsional)"
                        class="w-full resize-none rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-700 outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >{{ old('catatan') }}</textarea>

                    @error('catatan')
                        <p class="mt-1.5 text-xs text-red-500">
                            {{ $message }}
                        </p>
                    @enderror

                </div>


                {{-- TOTAL --}}
                <div class="space-y-4">

                    {{-- DISKON PO --}}
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
                                min="0"
                                step="0.01"
                                name="diskon"
                                id="diskon-po"
                                value="{{ old('diskon', 0) }}"
                                class="w-full rounded-lg border border-slate-300 py-2.5 pl-10 pr-3.5 text-sm text-slate-700 outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
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
                                min="0"
                                max="100"
                                step="0.01"
                                name="pajak_persen"
                                id="pajak-persen"
                                value="{{ old('pajak_persen', 0) }}"
                                placeholder="Contoh: 11"
                                class="w-full rounded-lg border border-slate-300 py-2.5 pl-3.5 pr-10 text-sm text-slate-700 outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                            >

                            <span class="absolute right-3.5 top-1/2 -translate-y-1/2 text-sm font-medium text-slate-400">
                                %
                            </span>

                        </div>

                        <p class="mt-1.5 text-xs text-slate-400">
                            Masukkan persentase pajak, misalnya 11 untuk 11%.
                        </p>

                        @error('pajak_persen')
                            <p class="mt-1.5 text-xs text-red-500">
                                {{ $message }}
                            </p>
                        @enderror
                    </div>


                    {{-- SUMMARY --}}
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">

                        <div class="flex items-center justify-between text-sm text-slate-500">

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


                        <div class="mt-3 flex items-center justify-between text-sm text-slate-500">

                            <span>
                                Diskon PO
                            </span>

                            <span id="diskon-display">
                                Rp 0
                            </span>

                        </div>


                        <div class="mt-3 flex items-center justify-between text-sm text-slate-500">

                            <span>
                                Dasar Pajak
                            </span>

                            <span id="dasar-pajak-display">
                                Rp 0
                            </span>

                        </div>


                        <div class="mt-3 flex items-center justify-between text-sm text-slate-500">

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


                        <div class="mt-4 flex items-center justify-between border-t border-slate-200 pt-4">

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
        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">

            <a
                href="{{ route('purchase-order.index') }}"
                class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100"
            >
                Batal
            </a>

            <button
                type="submit"
                class="inline-flex items-center justify-center rounded-lg bg-[#15235F] px-5 py-2.5 text-sm font-medium text-white transition hover:bg-[#101b4d]"
            >
                Simpan Draft
            </button>

        </div>

    </form>

</div>


{{-- DATA BARANG --}}
@php

    $barangData = $barangs->map(function ($barang) {

        return [
            'id' => $barang->id,
            'kode' => $barang->kode_barang,
            'nama' => $barang->nama_barang,
            'spesifikasi' => $barang->spesifikasi,
            'merek' => $barang->merek,
            'satuan' => $barang->satuan?->nama_satuan,
            'harga_beli_default' => (float) $barang->harga_beli_default,
        ];

    })->values();


    /*
    |--------------------------------------------------------------------------
    | OLD ITEMS
    |--------------------------------------------------------------------------
    */

    $isRequestBasedPO = $permintaanPembelian !== null;
    $oldItems = old('items', $initialItems ?? []);

@endphp


<script>

    const barangs =
        @json($barangData);


    const oldItems =
        @json($oldItems);

    const isRequestBasedPO = @json($isRequestBasedPO);

    let itemIndex = 0;


    const container =
        document.getElementById(
            'items-container'
        );


    /*
    |--------------------------------------------------------------------------
    | FORMAT RUPIAH
    |--------------------------------------------------------------------------
    */

    function formatRupiah(value) {

        return new Intl.NumberFormat(
            'id-ID',
            {
                maximumFractionDigits: 2
            }
        ).format(
            value || 0
        );
    }


    /*
    |--------------------------------------------------------------------------
    | ESCAPE HTML
    |--------------------------------------------------------------------------
    */

    function escapeHtml(value) {

        if (
            value === null ||
            value === undefined
        ) {
            return '';
        }


        return String(value)
            .replaceAll('&', '&amp;')
            .replaceAll('<', '&lt;')
            .replaceAll('>', '&gt;')
            .replaceAll('"', '&quot;')
            .replaceAll("'", '&#039;');
    }


    /*
    |--------------------------------------------------------------------------
    | OPTION BARANG
    |--------------------------------------------------------------------------
    */

    function barangOptions(
        selectedId = ''
    ) {

        let options =
            '<option value="">Pilih barang</option>';


        barangs.forEach(
            barang => {

                let label =
                    `${barang.kode} - ${barang.nama}`;


                if (barang.spesifikasi) {

                    label +=
                        ` - ${barang.spesifikasi}`;

                }


                if (barang.merek) {

                    label +=
                        ` - ${barang.merek}`;

                }


                if (barang.satuan) {

                    label +=
                        ` (${barang.satuan})`;

                }


                const selected =
                    String(selectedId)
                    ===
                    String(barang.id)
                        ? 'selected'
                        : '';


                options += `
                    <option
                        value="${barang.id}"
                        ${selected}
                    >
                        ${escapeHtml(label)}
                    </option>
                `;

            }
        );


        return options;
    }


    /*
    |--------------------------------------------------------------------------
    | FIND BARANG
    |--------------------------------------------------------------------------
    */

    function findBarang(
        barangId
    ) {

        return barangs.find(
            barang =>
                String(barang.id)
                ===
                String(barangId)
        );
    }


    /*
    |--------------------------------------------------------------------------
    | TAMBAH ROW
    |--------------------------------------------------------------------------
    */

    function addItem(
        data = {}
    ) {

        const index =
            itemIndex++;


        const barangId =
            data.barang_id ?? '';


        const qty =
            data.qty ?? 1;


        const diskon =
            data.diskon ?? 0;


        const keterangan =
            data.keterangan ?? '';


        /*
         * Jika old input tersedia,
         * harga lama dipertahankan.
         *
         * Untuk row baru harga default
         * akan masuk saat barang dipilih.
         */
        const harga =
            data.harga_satuan ?? 0;


        const row =
            document.createElement(
                'tr'
            );


        row.className =
            'po-item-row';


        row.innerHTML = `

            <td class="px-4 py-3 align-top">

                <select
                    name="items[${index}][barang_id]"
                    class="item-barang w-[390px] rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    required
                >
                    ${barangOptions(barangId)}
                </select>

            </td>


            <td class="px-4 py-3 align-top">

                <input
                    type="number"
                    name="items[${index}][qty]"
                    min="0.01"
                    step="0.01"
                    value="${escapeHtml(qty)}"
                    ${isRequestBasedPO ? 'readonly' : ''}
                    class="item-qty w-24 rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    required
                >

            </td>


            <td class="px-4 py-3 align-top">

                <div>

                    <div class="relative">

                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400">
                            Rp
                        </span>

                        <input
                            type="number"
                            name="items[${index}][harga_satuan]"
                            min="0"
                            step="0.01"
                            value="${escapeHtml(harga)}"
                            class="item-harga w-40 rounded-lg border border-slate-300 py-2 pl-9 pr-3 text-sm text-slate-700 outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                            required
                        >

                    </div>

                    <p class="harga-reference mt-1 text-[10px] text-slate-400"></p>

                </div>

            </td>


            <td class="px-4 py-3 align-top">

                <input
                    type="number"
                    name="items[${index}][diskon]"
                    min="0"
                    step="0.01"
                    value="${escapeHtml(diskon)}"
                    class="item-diskon w-32 rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                >

            </td>


            <td class="item-subtotal whitespace-nowrap px-4 py-5 align-top font-medium text-slate-700">
                Rp 0
            </td>


            <td class="px-4 py-3 align-top">

                <input
                    type="text"
                    name="items[${index}][keterangan]"
                    value="${escapeHtml(keterangan)}"
                    placeholder="Opsional"
                    class="w-48 rounded-lg border border-slate-300 px-3 py-2 text-sm text-slate-700 outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                >

            </td>


            <td class="px-4 py-3 text-right align-top">

                <button
                    type="button"
                    class="remove-item rounded-lg px-3 py-2 text-xs font-medium text-red-500 transition hover:bg-red-50 ${isRequestBasedPO ? 'hidden' : ''}"
                >
                    Hapus
                </button>

            </td>
        `;


        container.appendChild(
            row
        );


        bindRow(
            row
        );


        /*
        |--------------------------------------------------------------------------
        | OLD INPUT
        |--------------------------------------------------------------------------
        */

        if (barangId) {

            const barang =
                findBarang(
                    barangId
                );


            updateHargaReference(
                row,
                barang
            );

        }


        calculateTotals();
    }


    /*
    |--------------------------------------------------------------------------
    | REFERENSI HARGA DEFAULT
    |--------------------------------------------------------------------------
    */

    function updateHargaReference(
        row,
        barang
    ) {

        const reference =
            row.querySelector(
                '.harga-reference'
            );


        if (!barang) {

            reference.textContent =
                '';

            return;
        }


        if (
            Number(
                barang.harga_beli_default
            ) > 0
        ) {

            reference.textContent =
                'Harga default: Rp '
                + formatRupiah(
                    barang.harga_beli_default
                );

        } else {

            reference.textContent =
                'Harga beli default belum tersedia.';

        }
    }


    /*
    |--------------------------------------------------------------------------
    | ISI HARGA OTOMATIS
    |--------------------------------------------------------------------------
    */

    function isiHargaDefault(
        row,
        barangId
    ) {

        const barang =
            findBarang(
                barangId
            );


        const hargaInput =
            row.querySelector(
                '.item-harga'
            );


        if (!barang) {

            hargaInput.value =
                0;


            updateHargaReference(
                row,
                null
            );


            calculateTotals();


            return;
        }


        hargaInput.value =
            Number(
                barang.harga_beli_default
            ) || 0;


        updateHargaReference(
            row,
            barang
        );


        calculateTotals();
    }


    /*
    |--------------------------------------------------------------------------
    | BIND ROW EVENT
    |--------------------------------------------------------------------------
    */

    function bindRow(
        row
    ) {

        const barangSelect =
            row.querySelector(
                '.item-barang'
            );


        barangSelect.addEventListener(
            'change',
            function () {

                isiHargaDefault(
                    row,
                    this.value
                );

            }
        );


        row
            .querySelectorAll(
                '.item-qty, .item-harga, .item-diskon'
            )
            .forEach(
                input => {

                    input.addEventListener(
                        'input',
                        calculateTotals
                    );

                }
            );


        row
            .querySelector(
                '.remove-item'
            )
            .addEventListener(
                'click',
                () => {

                    if (
                        container
                            .querySelectorAll(
                                '.po-item-row'
                            )
                            .length
                        <= 1
                    ) {

                        alert(
                            'Purchase Order minimal memiliki satu barang.'
                        );


                        return;
                    }


                    row.remove();


                    calculateTotals();

                }
            );
    }


    /*
    |--------------------------------------------------------------------------
    | HITUNG TOTAL
    |--------------------------------------------------------------------------
    */

    function calculateTotals() {

        let subtotalPO =
            0;


        container
            .querySelectorAll(
                '.po-item-row'
            )
            .forEach(
                row => {

                    const qty =
                        parseFloat(
                            row
                                .querySelector(
                                    '.item-qty'
                                )
                                .value
                        ) || 0;


                    const harga =
                        parseFloat(
                            row
                                .querySelector(
                                    '.item-harga'
                                )
                                .value
                        ) || 0;


                    const diskon =
                        parseFloat(
                            row
                                .querySelector(
                                    '.item-diskon'
                                )
                                .value
                        ) || 0;


                    let subtotal =
                        (qty * harga)
                        - diskon;


                    if (
                        subtotal < 0
                    ) {

                        subtotal =
                            0;

                    }


                    row
                        .querySelector(
                            '.item-subtotal'
                        )
                        .textContent =
                            'Rp '
                            + formatRupiah(
                                subtotal
                            );


                    subtotalPO +=
                        subtotal;

                }
            );


        /*
        |--------------------------------------------------------------------------
        | DISKON PO
        |--------------------------------------------------------------------------
        */

        const diskonPO =
            parseFloat(
                document
                    .getElementById(
                        'diskon-po'
                    )
                    .value
            ) || 0;


        /*
        |--------------------------------------------------------------------------
        | DASAR PAJAK
        |--------------------------------------------------------------------------
        */

        let dasarPajak =
            subtotalPO
            - diskonPO;


        if (
            dasarPajak < 0
        ) {

            dasarPajak =
                0;

        }


        /*
        |--------------------------------------------------------------------------
        | PAJAK PERSENTASE
        |--------------------------------------------------------------------------
        */

        const pajakPersen =
            parseFloat(
                document
                    .getElementById(
                        'pajak-persen'
                    )
                    .value
            ) || 0;


        const pajakNominal =
            dasarPajak
            * pajakPersen
            / 100;


        /*
        |--------------------------------------------------------------------------
        | TOTAL
        |--------------------------------------------------------------------------
        */

        let total =
            dasarPajak
            + pajakNominal;


        if (
            total < 0
        ) {

            total =
                0;

        }


        /*
        |--------------------------------------------------------------------------
        | DISPLAY
        |--------------------------------------------------------------------------
        */

        document
            .getElementById(
                'subtotal-display'
            )
            .textContent =
                'Rp '
                + formatRupiah(
                    subtotalPO
                );


        document
            .getElementById(
                'diskon-display'
            )
            .textContent =
                'Rp '
                + formatRupiah(
                    diskonPO
                );


        document
            .getElementById(
                'dasar-pajak-display'
            )
            .textContent =
                'Rp '
                + formatRupiah(
                    dasarPajak
                );


        document
            .getElementById(
                'pajak-persen-display'
            )
            .textContent =
                '('
                + formatRupiah(
                    pajakPersen
                )
                + '%)';


        document
            .getElementById(
                'pajak-display'
            )
            .textContent =
                'Rp '
                + formatRupiah(
                    pajakNominal
                );


        document
            .getElementById(
                'total-display'
            )
            .textContent =
                'Rp '
                + formatRupiah(
                    total
                );
    }


    /*
    |--------------------------------------------------------------------------
    | EVENT
    |--------------------------------------------------------------------------
    */

    const addItemButton = document.getElementById('add-item');
    if (addItemButton) {
        addItemButton.addEventListener('click', () => addItem());
    }


    document
        .getElementById(
            'diskon-po'
        )
        .addEventListener(
            'input',
            calculateTotals
        );


    document
        .getElementById(
            'pajak-persen'
        )
        .addEventListener(
            'input',
            calculateTotals
        );


    /*
    |--------------------------------------------------------------------------
    | INITIAL ITEM
    |--------------------------------------------------------------------------
    */

    if (
        oldItems.length > 0
    ) {

        oldItems.forEach(
            item => {

                addItem(
                    item
                );

            }
        );

    } else {

        addItem();

    }


    calculateTotals();

</script>

@endsection