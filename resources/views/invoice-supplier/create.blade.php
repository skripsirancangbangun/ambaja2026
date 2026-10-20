@extends('layouts.app')

@section('title', 'Tambah Invoice Supplier')

@section('content')

<div class="mx-auto max-w-6xl space-y-6">

    <div>

        <a
            href="{{ route('invoice-supplier.index') }}"
            class="mb-3 inline-flex items-center gap-2 text-sm text-slate-500 hover:text-[#15235F]"
        >
            <x-icon name="arrow-left" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" /> Kembali
        </a>

        <h2 class="text-xl font-semibold text-slate-800">
            Tambah Invoice Supplier
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            Catat tagihan supplier berdasarkan barang yang telah diterima.
        </p>

    </div>


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


    <form
        action="{{ route('invoice-supplier.store') }}"
        method="POST"
        class="space-y-6"
    >

        @csrf


        {{-- ========================================================= --}}
        {{-- INFORMASI INVOICE --}}
        {{-- ========================================================= --}}

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <div class="mb-5">

                <h3 class="text-sm font-semibold text-slate-700">
                    Informasi Invoice
                </h3>

            </div>


            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                {{-- NOMOR INTERNAL --}}
                <div>

                    <label class="mb-2 block text-sm font-medium text-slate-700">
                        Nomor Internal
                    </label>

                    <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5">

                        <span class="text-sm font-semibold text-[#15235F]">
                            {{ $previewNomorInvoice }}
                        </span>

                        <span class="rounded-full bg-teal-50 px-2.5 py-1 text-[10px] font-medium text-[#32AAA6]">
                            Otomatis
                        </span>

                    </div>

                </div>


                {{-- NOMOR INVOICE SUPPLIER --}}
                <div>

                    <label
                        for="nomor_invoice_supplier"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Nomor Invoice Supplier
                        <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="text"
                        id="nomor_invoice_supplier"
                        name="nomor_invoice_supplier"
                        value="{{ old('nomor_invoice_supplier') }}"
                        placeholder="Nomor invoice dari supplier"
                        required
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
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
                        class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
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
                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- INFORMASI PO --}}
                @if($selectedPurchaseOrder)

                    <div class="md:col-span-2">

                        <div class="grid grid-cols-1 gap-3 md:grid-cols-3">

                            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">

                                <p class="text-xs uppercase tracking-wide text-slate-400">
                                    Supplier
                                </p>

                                <p class="mt-1 text-sm font-semibold text-slate-700">
                                    {{ $selectedPurchaseOrder->supplier?->nama_supplier ?? '-' }}
                                </p>

                            </div>


                            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">

                                <p class="text-xs uppercase tracking-wide text-slate-400">
                                    Nomor PO
                                </p>

                                <p class="mt-1 text-sm font-semibold text-slate-700">
                                    {{ $selectedPurchaseOrder->nomor_po }}
                                </p>

                            </div>


                            <div class="rounded-xl border border-slate-200 bg-slate-50 px-4 py-3">

                                <p class="text-xs uppercase tracking-wide text-slate-400">
                                    Pajak PO
                                </p>

                                <p class="mt-1 text-sm font-semibold text-slate-700">

                                    {{ rtrim(
                                        rtrim(
                                            number_format(
                                                (float) (
                                                    $selectedPurchaseOrder->pajak_persen
                                                    ?? 0
                                                ),
                                                2,
                                                '.',
                                                ''
                                            ),
                                            '0'
                                        ),
                                        '.'
                                    ) }}%

                                </p>

                            </div>

                        </div>

                    </div>

                @endif


                {{-- TANGGAL INVOICE --}}
                <div>

                    <label
                        for="tanggal_invoice"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Tanggal Invoice
                        <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="date"
                        id="tanggal_invoice"
                        name="tanggal_invoice"
                        value="{{ old('tanggal_invoice', date('Y-m-d')) }}"
                        required
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >

                </div>


                {{-- JATUH TEMPO --}}
                <div>

                    <label
                        for="tanggal_jatuh_tempo"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Jatuh Tempo
                        <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="date"
                        id="tanggal_jatuh_tempo"
                        name="tanggal_jatuh_tempo"
                        value="{{ old('tanggal_jatuh_tempo') }}"
                        required
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >

                </div>

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- DETAIL BARANG --}}
        {{-- ========================================================= --}}

        @if($selectedPurchaseOrder)

            @php

                $invoiceItems = collect();

                foreach (
                    $selectedPurchaseOrder->penerimaanBarangs
                    as $penerimaan
                ) {

                    foreach (
                        $penerimaan->items
                        as $penerimaanItem
                    ) {

                        $qtySudahInvoice =
                            (float) $penerimaanItem
                                ->invoiceSupplierItems()
                                ->sum('qty');


                        $sisa =
                            (float) $penerimaanItem->qty_diterima
                            - $qtySudahInvoice;


                        if ($sisa > 0) {

                            $invoiceItems->push([

                                'item' =>
                                    $penerimaanItem,

                                'penerimaan' =>
                                    $penerimaan,

                                'sisa' =>
                                    $sisa,
                            ]);
                        }
                    }
                }

            @endphp


            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 px-6 py-4">

                    <h3 class="text-sm font-semibold text-slate-700">
                        Barang yang Ditagihkan
                    </h3>

                    <p class="mt-1 text-xs text-slate-400">
                        Qty invoice tidak boleh melebihi barang yang telah diterima dan belum pernah ditagihkan.
                    </p>

                </div>


                @if($invoiceItems->isEmpty())

                    <div class="px-6 py-14 text-center">

                        <p class="text-sm font-medium text-slate-500">
                            Semua penerimaan pada PO ini sudah ditagihkan.
                        </p>

                    </div>

                @else

                    <div class="overflow-x-auto">

                        <table class="w-full min-w-[1250px] text-sm">

                            <thead class="bg-slate-50">

                                <tr class="text-left text-xs uppercase tracking-wide text-slate-400">

                                    <th class="px-5 py-3">
                                        Penerimaan
                                    </th>

                                    <th class="px-5 py-3">
                                        Barang
                                    </th>

                                    <th class="px-5 py-3 text-right">
                                        Sisa Qty
                                    </th>

                                    <th class="px-5 py-3">
                                        Satuan
                                    </th>

                                    <th class="px-5 py-3">
                                        Qty Invoice
                                    </th>

                                    <th class="px-5 py-3">
                                        Harga Satuan
                                    </th>

                                    <th class="px-5 py-3">
                                        Diskon
                                    </th>

                                    <th class="px-5 py-3 text-right">
                                        Subtotal
                                    </th>

                                </tr>

                            </thead>


                            <tbody class="divide-y divide-slate-100">

                                @foreach($invoiceItems as $row)

                                    @php

                                        $item =
                                            $row['item'];

                                        $penerimaan =
                                            $row['penerimaan'];

                                        $sisa =
                                            $row['sisa'];

                                        $index =
                                            $loop->index;

                                    @endphp


                                    <tr class="invoice-item-row">

                                        {{-- PENERIMAAN --}}
                                        <td class="px-5 py-4">

                                            <input
                                                type="hidden"
                                                name="items[{{ $index }}][penerimaan_barang_item_id]"
                                                value="{{ $item->id }}"
                                            >

                                            <p class="font-medium text-slate-700">
                                                {{ $penerimaan->nomor_penerimaan }}
                                            </p>

                                        </td>


                                        {{-- BARANG --}}
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


                                        {{-- SISA QTY --}}
                                        <td class="px-5 py-4 text-right font-semibold text-slate-700">

                                            {{ number_format(
                                                $sisa,
                                                2,
                                                ',',
                                                '.'
                                            ) }}

                                        </td>


                                        {{-- SATUAN --}}
                                        <td class="px-5 py-4 text-slate-500">

                                            {{ $item
                                                ->barang
                                                ?->satuan
                                                ?->nama_satuan
                                                ?? '-'
                                            }}

                                        </td>


                                        {{-- QTY INVOICE --}}
                                        <td class="px-5 py-4">

                                            <input
                                                type="number"
                                                name="items[{{ $index }}][qty]"
                                                value="{{ old(
                                                    'items.' . $index . '.qty',
                                                    $sisa
                                                ) }}"
                                                min="0.01"
                                                max="{{ $sisa }}"
                                                step="0.01"
                                                required
                                                class="item-qty w-28 rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                                            >

                                        </td>


                                        {{-- HARGA SATUAN --}}
                                        <td class="px-5 py-4">

                                            <div class="relative">

                                                <span class="absolute left-3 top-1/2 -translate-y-1/2 text-xs text-slate-400">
                                                    Rp
                                                </span>

                                                <input
                                                    type="number"
                                                    name="items[{{ $index }}][harga_satuan]"
                                                    value="{{ old(
                                                        'items.' . $index . '.harga_satuan',
                                                        $item->harga_satuan
                                                    ) }}"
                                                    min="0"
                                                    step="0.01"
                                                    required
                                                    class="item-harga w-40 rounded-lg border border-slate-300 py-2 pl-9 pr-3 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                                                >

                                            </div>

                                        </td>


                                        {{-- DISKON ITEM --}}
                                        <td class="px-5 py-4">

                                            <input
                                                type="number"
                                                name="items[{{ $index }}][diskon]"
                                                value="{{ old(
                                                    'items.' . $index . '.diskon',
                                                    0
                                                ) }}"
                                                min="0"
                                                step="0.01"
                                                class="item-diskon w-32 rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                                            >

                                        </td>


                                        {{-- SUBTOTAL --}}
                                        <td class="item-subtotal whitespace-nowrap px-5 py-4 text-right font-semibold text-slate-700">
                                            Rp 0
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
                    Pilih Purchase Order terlebih dahulu.
                </p>

                <p class="mt-1 text-xs text-slate-400">
                    Barang yang sudah diterima akan muncul otomatis.
                </p>

            </div>

        @endif


        {{-- ========================================================= --}}
        {{-- NILAI INVOICE --}}
        {{-- ========================================================= --}}

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
                        rows="5"
                        class="w-full resize-none rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >{{ old('catatan') }}</textarea>

                </div>


                {{-- PERHITUNGAN --}}
                <div class="space-y-4">

                    {{-- DISKON INVOICE --}}
                    <div>

                        <label
                            for="diskon-invoice"
                            class="mb-2 block text-sm font-medium text-slate-700"
                        >
                            Diskon Invoice
                        </label>

                        <input
                            type="number"
                            id="diskon-invoice"
                            name="diskon"
                            value="{{ old('diskon', 0) }}"
                            min="0"
                            step="0.01"
                            class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                        >

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
                                value="{{ old(
                                    'pajak_persen',
                                    $selectedPurchaseOrder?->pajak_persen
                                    ?? 0
                                ) }}"
                                min="0"
                                max="100"
                                step="0.01"
                                placeholder="Contoh: 11"
                                class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 pr-10 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                            >

                            <span class="absolute right-3 top-1/2 -translate-y-1/2 text-sm text-slate-400">
                                %
                            </span>

                        </div>

                        @if($selectedPurchaseOrder)

                            <p class="mt-1.5 text-xs text-slate-400">
                                Otomatis mengikuti persentase pajak Purchase Order, namun dapat disesuaikan dengan invoice supplier.
                            </p>

                        @endif

                    </div>


                    {{-- SUMMARY --}}
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">

                        {{-- SUBTOTAL --}}
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


                        {{-- DISKON --}}
                        <div class="mt-3 flex justify-between text-sm text-slate-500">

                            <span>
                                Diskon Invoice
                            </span>

                            <span id="diskon-display">
                                Rp 0
                            </span>

                        </div>


                        {{-- DASAR PAJAK --}}
                        <div class="mt-3 flex justify-between text-sm text-slate-500">

                            <span>
                                Dasar Pajak
                            </span>

                            <span
                                id="dasar-pajak-display"
                                class="font-medium text-slate-700"
                            >
                                Rp 0
                            </span>

                        </div>


                        {{-- PAJAK --}}
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


                        {{-- TOTAL --}}
                        <div class="mt-4 flex justify-between border-t border-slate-200 pt-4">

                            <span class="font-semibold text-slate-700">
                                Total Invoice
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


        {{-- ========================================================= --}}
        {{-- ACTION --}}
        {{-- ========================================================= --}}

        <div class="flex justify-end gap-3">

            <a
                href="{{ route('invoice-supplier.index') }}"
                class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100"
            >
                Batal
            </a>

            <button
                type="submit"
                {{ !$selectedPurchaseOrder ? 'disabled' : '' }}
                class="rounded-lg bg-[#15235F] px-5 py-2.5 text-sm font-medium text-white hover:bg-[#101b4d] disabled:cursor-not-allowed disabled:opacity-40"
            >
                Simpan Invoice
            </button>

        </div>

    </form>

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


    if (poSelect) {

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
    }


    /*
    |--------------------------------------------------------------------------
    | FORMAT RUPIAH
    |--------------------------------------------------------------------------
    */

    function formatRupiah(value) {

        return new Intl.NumberFormat(
            'id-ID',
            {
                minimumFractionDigits: 0,
                maximumFractionDigits: 2
            }
        ).format(
            value || 0
        );
    }


    /*
    |--------------------------------------------------------------------------
    | FORMAT PERSENTASE
    |--------------------------------------------------------------------------
    */

    function formatPersen(value) {

        const number =
            parseFloat(value) || 0;


        return new Intl.NumberFormat(
            'id-ID',
            {
                minimumFractionDigits: 0,
                maximumFractionDigits: 2
            }
        ).format(number);
    }


    /*
    |--------------------------------------------------------------------------
    | HITUNG INVOICE
    |--------------------------------------------------------------------------
    */

    function calculateInvoice() {

        let subtotal =
            0;


        document
            .querySelectorAll(
                '.invoice-item-row'
            )
            .forEach(row => {

                const qty =
                    parseFloat(
                        row
                            .querySelector(
                                '.item-qty'
                            )
                            ?.value
                    ) || 0;


                const harga =
                    parseFloat(
                        row
                            .querySelector(
                                '.item-harga'
                            )
                            ?.value
                    ) || 0;


                const diskon =
                    parseFloat(
                        row
                            .querySelector(
                                '.item-diskon'
                            )
                            ?.value
                    ) || 0;


                let nilai =
                    (qty * harga)
                    - diskon;


                if (nilai < 0) {
                    nilai = 0;
                }


                const subtotalElement =
                    row.querySelector(
                        '.item-subtotal'
                    );


                if (subtotalElement) {

                    subtotalElement.textContent =
                        'Rp '
                        + formatRupiah(
                            nilai
                        );
                }


                subtotal +=
                    nilai;
            });


        /*
        |--------------------------------------------------------------------------
        | DISKON INVOICE
        |--------------------------------------------------------------------------
        */

        const diskonInvoice =
            parseFloat(
                document.getElementById(
                    'diskon-invoice'
                )?.value
            ) || 0;


        /*
        |--------------------------------------------------------------------------
        | DASAR PAJAK
        |--------------------------------------------------------------------------
        */

        let dasarPajak =
            subtotal
            - diskonInvoice;


        if (dasarPajak < 0) {
            dasarPajak = 0;
        }


        /*
        |--------------------------------------------------------------------------
        | PERSENTASE PAJAK
        |--------------------------------------------------------------------------
        */

        const pajakPersen =
            parseFloat(
                document.getElementById(
                    'pajak-persen'
                )?.value
            ) || 0;


        /*
        |--------------------------------------------------------------------------
        | NOMINAL PAJAK
        |--------------------------------------------------------------------------
        */

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


        if (total < 0) {
            total = 0;
        }


        /*
        |--------------------------------------------------------------------------
        | UPDATE DISPLAY
        |--------------------------------------------------------------------------
        */

        const subtotalDisplay =
            document.getElementById(
                'subtotal-display'
            );


        const diskonDisplay =
            document.getElementById(
                'diskon-display'
            );


        const dasarPajakDisplay =
            document.getElementById(
                'dasar-pajak-display'
            );


        const pajakPersenDisplay =
            document.getElementById(
                'pajak-persen-display'
            );


        const pajakDisplay =
            document.getElementById(
                'pajak-display'
            );


        const totalDisplay =
            document.getElementById(
                'total-display'
            );


        if (subtotalDisplay) {

            subtotalDisplay.textContent =
                'Rp '
                + formatRupiah(
                    subtotal
                );
        }


        if (diskonDisplay) {

            diskonDisplay.textContent =
                'Rp '
                + formatRupiah(
                    diskonInvoice
                );
        }


        if (dasarPajakDisplay) {

            dasarPajakDisplay.textContent =
                'Rp '
                + formatRupiah(
                    dasarPajak
                );
        }


        if (pajakPersenDisplay) {

            pajakPersenDisplay.textContent =
                '('
                + formatPersen(
                    pajakPersen
                )
                + '%)';
        }


        if (pajakDisplay) {

            pajakDisplay.textContent =
                'Rp '
                + formatRupiah(
                    pajakNominal
                );
        }


        if (totalDisplay) {

            totalDisplay.textContent =
                'Rp '
                + formatRupiah(
                    total
                );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | LISTENER DETAIL ITEM
    |--------------------------------------------------------------------------
    */

    document
        .querySelectorAll(
            '.item-qty, .item-harga, .item-diskon'
        )
        .forEach(input => {

            input.addEventListener(
                'input',
                calculateInvoice
            );
        });


    /*
    |--------------------------------------------------------------------------
    | LISTENER DISKON INVOICE
    |--------------------------------------------------------------------------
    */

    const diskonInvoiceInput =
        document.getElementById(
            'diskon-invoice'
        );


    if (diskonInvoiceInput) {

        diskonInvoiceInput.addEventListener(
            'input',
            calculateInvoice
        );
    }


    /*
    |--------------------------------------------------------------------------
    | LISTENER PAJAK
    |--------------------------------------------------------------------------
    */

    const pajakPersenInput =
        document.getElementById(
            'pajak-persen'
        );


    if (pajakPersenInput) {

        pajakPersenInput.addEventListener(
            'input',
            calculateInvoice
        );
    }


    /*
    |--------------------------------------------------------------------------
    | HITUNG AWAL
    |--------------------------------------------------------------------------
    */

    calculateInvoice();

</script>

@endsection