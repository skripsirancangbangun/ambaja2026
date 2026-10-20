@extends('layouts.app')

@section('title', 'Tambah Pembayaran Supplier')

@section('content')

<div class="mx-auto max-w-5xl space-y-6">

    {{-- HEADER --}}
    <div>

        <a
            href="{{ route('pembayaran-supplier.index') }}"
            class="mb-3 inline-flex text-sm text-slate-500 hover:text-[#15235F]"
        >
            <x-icon name="arrow-left" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" /> Kembali
        </a>

        <h2 class="text-xl font-semibold text-slate-800">
            Tambah Pembayaran Supplier
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            Catat pembayaran atas invoice supplier yang masih memiliki saldo utang.
        </p>

    </div>


    {{-- ERROR --}}
    @if($errors->any())

        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3">

            <p class="text-sm font-semibold text-red-700">
                Periksa kembali data pembayaran.
            </p>

            <ul class="mt-2 list-disc space-y-1 pl-5 text-xs text-red-600">

                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach

            </ul>

        </div>

    @endif


    <form
        action="{{ route('pembayaran-supplier.store') }}"
        method="POST"
        class="space-y-6"
    >

        @csrf

        <input
            type="hidden"
            name="idempotency_key"
            value="{{ old('idempotency_key', $idempotencyKey) }}"
        >

        {{-- INFORMASI PEMBAYARAN --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <h3 class="mb-5 text-sm font-semibold text-slate-700">
                Informasi Pembayaran
            </h3>


            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                {{-- NOMOR --}}
                <div>

                    <label class="mb-2 block text-sm font-medium text-slate-700">
                        Nomor Pembayaran
                    </label>

                    <div class="flex items-center justify-between rounded-lg border border-slate-200 bg-slate-50 px-3.5 py-2.5">

                        <span class="text-sm font-semibold text-[#15235F]">
                            {{ $previewNomorPembayaran }}
                        </span>

                        <span class="rounded-full bg-teal-50 px-2.5 py-1 text-[10px] font-medium text-[#32AAA6]">
                            Otomatis
                        </span>

                    </div>

                </div>


                {{-- TANGGAL --}}
                <div>

                    <label
                        for="tanggal_pembayaran"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Tanggal Pembayaran
                        <span class="text-red-500">*</span>
                    </label>

                    <input
                        type="date"
                        id="tanggal_pembayaran"
                        name="tanggal_pembayaran"
                        value="{{ old('tanggal_pembayaran', date('Y-m-d')) }}"
                        required
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >

                </div>


                {{-- INVOICE --}}
                <div class="md:col-span-2">

                    <label
                        for="invoice_supplier_id"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Invoice Supplier
                        <span class="text-red-500">*</span>
                    </label>

                    <select
                        id="invoice_supplier_id"
                        name="invoice_supplier_id"
                        required
                        class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >

                        <option value="">
                            Pilih Invoice Supplier
                        </option>

                        @foreach($invoices as $invoice)

                            <option
                                value="{{ $invoice->id }}"
                                {{ old(
                                    'invoice_supplier_id',
                                    $selectedInvoice?->id
                                ) == $invoice->id ? 'selected' : '' }}
                            >

                                {{ $invoice->nomor_invoice_internal }}
                                -
                                {{ $invoice->supplier?->nama_supplier }}
                                -
                                Sisa Rp {{ number_format(
                                    $invoice->sisa_utang,
                                    0,
                                    ',',
                                    '.'
                                ) }}

                            </option>

                        @endforeach

                    </select>

                    @error('invoice_supplier_id')
                        <p class="mt-1.5 text-xs text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                </div>

            </div>

        </div>


        {{-- DETAIL INVOICE --}}
        @if($selectedInvoice)

            @php

                $isOverdue =
                    $selectedInvoice->tanggal_jatuh_tempo
                    && $selectedInvoice
                        ->tanggal_jatuh_tempo
                        ->lt(today());

            @endphp


            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                <div class="mb-5 flex items-center justify-between">

                    <div>

                        <h3 class="text-sm font-semibold text-slate-700">
                            Detail Invoice
                        </h3>

                        <p class="mt-1 text-xs text-slate-400">
                            Informasi tagihan yang akan dibayar.
                        </p>

                    </div>


                    <span class="rounded-full px-3 py-1 text-xs font-medium
                        {{ $selectedInvoice->status === 'SEBAGIAN'
                            ? 'bg-amber-50 text-amber-600'
                            : 'bg-red-50 text-red-600'
                        }}
                    ">
                        {{ str_replace(
                            '_',
                            ' ',
                            $selectedInvoice->status
                        ) }}
                    </span>

                </div>


                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-4">

                    <div>

                        <p class="text-xs uppercase tracking-wide text-slate-400">
                            Supplier
                        </p>

                        <p class="mt-1 text-sm font-semibold text-slate-700">
                            {{ $selectedInvoice->supplier?->nama_supplier ?? '-' }}
                        </p>

                    </div>


                    <div>

                        <p class="text-xs uppercase tracking-wide text-slate-400">
                            Nomor Invoice Supplier
                        </p>

                        <p class="mt-1 text-sm font-medium text-slate-700">
                            {{ $selectedInvoice->nomor_invoice_supplier }}
                        </p>

                    </div>


                    <div>

                        <p class="text-xs uppercase tracking-wide text-slate-400">
                            Purchase Order
                        </p>

                        <p class="mt-1 text-sm font-medium text-slate-700">
                            {{ $selectedInvoice->purchaseOrder?->nomor_po ?? '-' }}
                        </p>

                    </div>


                    <div>

                        <p class="text-xs uppercase tracking-wide text-slate-400">
                            Jatuh Tempo
                        </p>

                        <p class="mt-1 text-sm font-medium {{ $isOverdue ? 'text-red-600' : 'text-slate-700' }}">

                            {{ $selectedInvoice->tanggal_jatuh_tempo?->format('d/m/Y') ?? '-' }}

                        </p>

                        @if($isOverdue)

                            <span class="mt-1 inline-flex rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-medium text-red-600">
                                Lewat Jatuh Tempo
                            </span>

                        @endif

                    </div>

                </div>


                {{-- RINGKASAN NILAI --}}
                <div class="mt-6 grid grid-cols-1 gap-4 md:grid-cols-3">

                    <div class="rounded-xl bg-slate-50 p-4">

                        <p class="text-xs text-slate-400">
                            Total Invoice
                        </p>

                        <p class="mt-1 text-lg font-bold text-slate-700">
                            Rp {{ number_format(
                                $selectedInvoice->total,
                                0,
                                ',',
                                '.'
                            ) }}
                        </p>

                    </div>


                    <div class="rounded-xl bg-emerald-50 p-4">

                        <p class="text-xs text-emerald-500">
                            Sudah Dibayar
                        </p>

                        <p class="mt-1 text-lg font-bold text-emerald-600">
                            Rp {{ number_format(
                                $selectedInvoice->total_dibayar,
                                0,
                                ',',
                                '.'
                            ) }}
                        </p>

                    </div>


                    <div class="rounded-xl bg-red-50 p-4">

                        <p class="text-xs text-red-500">
                            Sisa Utang
                        </p>

                        <p class="mt-1 text-lg font-bold text-red-600">
                            Rp {{ number_format(
                                $selectedInvoice->sisa_utang,
                                0,
                                ',',
                                '.'
                            ) }}
                        </p>

                    </div>

                </div>

            </div>

        @else

            <div class="rounded-2xl border border-slate-200 bg-white px-6 py-12 text-center shadow-sm">

                <p class="text-sm font-medium text-slate-500">
                    Pilih Invoice Supplier terlebih dahulu.
                </p>

                <p class="mt-1 text-xs text-slate-400">
                    Informasi saldo utang akan muncul otomatis.
                </p>

            </div>

        @endif


        {{-- DETAIL PEMBAYARAN --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <div class="mb-5">

                <h3 class="text-sm font-semibold text-slate-700">
                    Detail Pembayaran
                </h3>

                <p class="mt-1 text-xs text-slate-400">
                    Pembayaran akan langsung mengurangi saldo Kas atau Rekening Bank yang dipilih.
                </p>

            </div>


            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                {{-- JUMLAH --}}
                <div>

                    <label
                        for="jumlah_bayar"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Jumlah Bayar
                        <span class="text-red-500">*</span>
                    </label>

                    <div class="relative">

                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400">
                            Rp
                        </span>

                        <input
                            type="number"
                            id="jumlah_bayar"
                            name="jumlah_bayar"
                            value="{{ old(
                                'jumlah_bayar',
                                $selectedInvoice?->sisa_utang
                            ) }}"
                            min="0.01"
                            @if($selectedInvoice)
                                max="{{ $selectedInvoice->sisa_utang }}"
                            @endif
                            step="0.01"
                            required
                            class="w-full rounded-lg border border-slate-300 py-2.5 pl-10 pr-3.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                        >

                    </div>

                    @error('jumlah_bayar')
                        <p class="mt-1.5 text-xs text-red-600">
                            {{ $message }}
                        </p>
                    @enderror


                    @if($selectedInvoice)

                        <button
                            type="button"
                            id="bayar-penuh"
                            data-sisa="{{ $selectedInvoice->sisa_utang }}"
                            class="mt-2 text-xs font-medium text-[#32AAA6] hover:text-[#15235F]"
                        >
                            Bayar seluruh sisa utang
                        </button>

                    @endif

                </div>


                {{-- METODE --}}
                <div>

                    <label
                        for="metode_pembayaran"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Metode Pembayaran
                        <span class="text-red-500">*</span>
                    </label>

                    <select
                        id="metode_pembayaran"
                        name="metode_pembayaran"
                        required
                        class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >

                        <option
                            value="TRANSFER"
                            {{ old('metode_pembayaran', 'TRANSFER') === 'TRANSFER' ? 'selected' : '' }}
                        >
                            Transfer Bank
                        </option>

                        <option
                            value="TUNAI"
                            {{ old('metode_pembayaran') === 'TUNAI' ? 'selected' : '' }}
                        >
                            Tunai
                        </option>

                        <option
                            value="GIRO"
                            {{ old('metode_pembayaran') === 'GIRO' ? 'selected' : '' }}
                        >
                            Giro
                        </option>

                        <option
                            value="LAINNYA"
                            {{ old('metode_pembayaran') === 'LAINNYA' ? 'selected' : '' }}
                        >
                            Lainnya
                        </option>

                    </select>

                    @error('metode_pembayaran')
                        <p class="mt-1.5 text-xs text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                </div>


                {{-- REKENING --}}
                <div>

                    <label
                        for="rekening_kas_bank_id"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Bayar Dari Kas / Rekening Bank
                        <span class="text-red-500">*</span>
                    </label>

                    <select
                        id="rekening_kas_bank_id"
                        name="rekening_kas_bank_id"
                        required
                        class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >

                        <option value="">
                            Pilih Kas / Rekening Bank
                        </option>

                        @foreach($rekeningKasBanks as $rekening)

                            <option
                                value="{{ $rekening->id }}"
                                {{ (string) old('rekening_kas_bank_id') === (string) $rekening->id ? 'selected' : '' }}
                            >
                                {{ $rekening->kode_rekening }}
                                -
                                {{ $rekening->nama_rekening }}

                                @if($rekening->jenis === 'BANK' && $rekening->nama_bank)
                                    - {{ $rekening->nama_bank }}
                                @else
                                    - Kas
                                @endif
                            </option>

                        @endforeach

                    </select>

                    @error('rekening_kas_bank_id')
                        <p class="mt-1.5 text-xs text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                    <p class="mt-1.5 text-xs text-slate-400">
                        Saldo rekening akan berkurang setelah pembayaran berhasil disimpan.
                    </p>

                </div>


                {{-- REFERENSI --}}
                <div>

                    <label
                        for="referensi"
                        class="mb-2 block text-sm font-medium text-slate-700"
                    >
                        Referensi
                    </label>

                    <input
                        type="text"
                        id="referensi"
                        name="referensi"
                        value="{{ old('referensi') }}"
                        placeholder="No. transfer / giro / bukti pembayaran"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >

                    @error('referensi')
                        <p class="mt-1.5 text-xs text-red-600">
                            {{ $message }}
                        </p>
                    @enderror

                </div>


                {{-- CATATAN --}}
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
                        placeholder="Catatan pembayaran..."
                        class="w-full resize-none rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >{{ old('catatan') }}</textarea>

                </div>

            </div>


            {{-- INFO POSTING --}}
            <div class="mt-6 rounded-xl border border-teal-200 bg-teal-50 px-4 py-3">

                <p class="text-sm font-semibold text-slate-700">
                    Posting Kas & Bank
                </p>

                <p class="mt-1 text-xs leading-5 text-slate-600">
                    Saat pembayaran disimpan, sistem akan mencatat transaksi sebagai
                    <strong>Pembayaran Supplier</strong> pada Mutasi Kas/Bank dan mengurangi saldo rekening yang dipilih.
                </p>

            </div>

        </div>


        {{-- ACTION --}}
        <div class="flex justify-end gap-3">

            <a
                href="{{ route('pembayaran-supplier.index') }}"
                class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-100"
            >
                Batal
            </a>

            <button
                type="submit"
                {{ !$selectedInvoice ? 'disabled' : '' }}
                class="rounded-lg bg-[#15235F] px-5 py-2.5 text-sm font-medium text-white hover:bg-[#101b4d] disabled:cursor-not-allowed disabled:opacity-40"
            >
                Simpan & Catat Pembayaran
            </button>

        </div>

    </form>

</div>


<script>
    /*
    |--------------------------------------------------------------------------
    | PILIH INVOICE
    |--------------------------------------------------------------------------
    */

    const invoiceSelect =
        document.getElementById(
            'invoice_supplier_id'
        );

    if (invoiceSelect) {

        invoiceSelect.addEventListener(
            'change',
            function () {

                const url =
                    new URL(window.location.href);

                if (this.value) {

                    url.searchParams.set(
                        'invoice_supplier_id',
                        this.value
                    );

                } else {

                    url.searchParams.delete(
                        'invoice_supplier_id'
                    );
                }

                window.location.href =
                    url.toString();
            }
        );
    }


    /*
    |--------------------------------------------------------------------------
    | BAYAR PENUH
    |--------------------------------------------------------------------------
    */

    const bayarPenuh =
        document.getElementById(
            'bayar-penuh'
        );

    if (bayarPenuh) {

        bayarPenuh.addEventListener(
            'click',
            function () {

                document.getElementById(
                    'jumlah_bayar'
                ).value =
                    this.dataset.sisa;
            }
        );
    }
</script>

@endsection