@extends('layouts.app')

@section('title', 'Detail Pembayaran Supplier')

@section('content')

<div class="mx-auto max-w-6xl space-y-6">

    {{-- HEADER --}}
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">

        <div>
            <a
                href="{{ route('pembayaran-supplier.index') }}"
                class="mb-3 inline-flex text-sm text-slate-500 hover:text-[#15235F]"
            >
                <x-icon name="arrow-left" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" /> Kembali
            </a>

            <h1 class="text-2xl font-bold text-slate-800">
                Detail Pembayaran Supplier
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Informasi pembayaran supplier dan status pencatatannya ke Kas/Bank.
            </p>
        </div>

        <div>
            <span class="inline-flex rounded-full bg-emerald-50 px-4 py-2 text-xs font-semibold text-emerald-700">
                Tercatat
            </span>
        </div>

    </div>


    {{-- SUCCESS --}}
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif


    {{-- ERROR --}}
    @if(session('error'))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif


    {{-- DATA PEMBAYARAN --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

        <div class="mb-5">
            <h2 class="text-base font-semibold text-slate-800">
                Informasi Pembayaran
            </h2>

            <p class="mt-1 text-xs text-slate-400">
                Data utama pembayaran supplier.
            </p>
        </div>


        <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-4">

            {{-- NOMOR --}}
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">
                    Nomor Pembayaran
                </p>

                <p class="mt-1 text-sm font-semibold text-slate-800">
                    {{ $pembayaranSupplier->nomor_pembayaran ?? '-' }}
                </p>
            </div>


            {{-- TANGGAL --}}
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">
                    Tanggal Pembayaran
                </p>

                <p class="mt-1 text-sm font-semibold text-slate-800">
                    {{ $pembayaranSupplier->tanggal_pembayaran?->format('d/m/Y') ?? '-' }}
                </p>
            </div>


            {{-- SUPPLIER --}}
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">
                    Supplier
                </p>

                <p class="mt-1 text-sm font-semibold text-slate-800">
                    {{ $pembayaranSupplier->supplier?->nama_supplier ?? '-' }}
                </p>
            </div>


            {{-- JUMLAH --}}
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">
                    Jumlah Bayar
                </p>

                <p class="mt-1 text-lg font-bold text-[#15235F]">
                    Rp {{ number_format(
                        (float) $pembayaranSupplier->jumlah_bayar,
                        0,
                        ',',
                        '.'
                    ) }}
                </p>
            </div>


            {{-- METODE --}}
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">
                    Metode Pembayaran
                </p>

                <p class="mt-1 text-sm font-medium text-slate-700">
                    {{ str_replace(
                        '_',
                        ' ',
                        $pembayaranSupplier->metode_pembayaran ?? '-'
                    ) }}
                </p>
            </div>


            {{-- REKENING --}}
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">
                    Kas / Rekening Bank
                </p>

                <p class="mt-1 text-sm font-semibold text-slate-800">
                    {{ $pembayaranSupplier->rekeningKasBank?->nama_rekening ?? '-' }}
                </p>

                @if(
                    $pembayaranSupplier
                        ->rekeningKasBank
                        ?->nomor_rekening
                )
                    <p class="mt-0.5 text-xs text-slate-400">
                        {{ $pembayaranSupplier
                            ->rekeningKasBank
                            ->nomor_rekening }}
                    </p>
                @endif
            </div>


            {{-- REFERENSI --}}
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">
                    Referensi
                </p>

                <p class="mt-1 text-sm font-medium text-slate-700">
                    {{ $pembayaranSupplier->referensi ?? '-' }}
                </p>
            </div>


            {{-- DIBUAT OLEH --}}
            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">
                    Dibuat Oleh
                </p>

                <p class="mt-1 text-sm font-medium text-slate-700">
                    {{ $pembayaranSupplier->creator?->name ?? '-' }}
                </p>
            </div>


            {{-- CATATAN --}}
            <div class="md:col-span-2 lg:col-span-4">
                <p class="text-xs uppercase tracking-wide text-slate-400">
                    Catatan
                </p>

                <p class="mt-1 text-sm leading-6 text-slate-700">
                    {{ $pembayaranSupplier->catatan ?? '-' }}
                </p>
            </div>

        </div>

    </div>


    {{-- INFORMASI INVOICE --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

        <div class="mb-5">
            <h2 class="text-base font-semibold text-slate-800">
                Invoice Supplier
            </h2>

            <p class="mt-1 text-xs text-slate-400">
                Invoice yang dibayar melalui transaksi ini.
            </p>
        </div>


        @php
            $invoice =
                $pembayaranSupplier
                    ->invoiceSupplier;
        @endphp


        <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-4">

            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">
                    Nomor Invoice Internal
                </p>

                <p class="mt-1 text-sm font-semibold text-slate-800">
                    {{ $invoice?->nomor_invoice_internal ?? '-' }}
                </p>
            </div>


            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">
                    Invoice Supplier
                </p>

                <p class="mt-1 text-sm font-medium text-slate-700">
                    {{ $invoice?->nomor_invoice_supplier ?? '-' }}
                </p>
            </div>


            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">
                    Total Invoice
                </p>

                <p class="mt-1 text-sm font-semibold text-slate-800">
                    Rp {{ number_format(
                        (float) ($invoice?->total ?? 0),
                        0,
                        ',',
                        '.'
                    ) }}
                </p>
            </div>


            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">
                    Sisa Utang
                </p>

                <p class="mt-1 text-sm font-semibold text-slate-800">
                    Rp {{ number_format(
                        (float) ($invoice?->sisa_utang ?? 0),
                        0,
                        ',',
                        '.'
                    ) }}
                </p>
            </div>


            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">
                    Status Invoice
                </p>

                @php
                    $statusInvoice =
                        $invoice?->status ?? '-';
                @endphp

                @if($statusInvoice === 'LUNAS')

                    <span class="mt-1 inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">
                        Lunas
                    </span>

                @elseif($statusInvoice === 'SEBAGIAN')

                    <span class="mt-1 inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">
                        Sebagian
                    </span>

                @else

                    <span class="mt-1 inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                        {{ str_replace('_', ' ', $statusInvoice) }}
                    </span>

                @endif
            </div>


            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">
                    Purchase Order
                </p>

                <p class="mt-1 text-sm font-medium text-slate-700">
                    {{ $invoice?->purchaseOrder?->nomor_po ?? '-' }}
                </p>
            </div>

        </div>

    </div>


    {{-- STATUS KAS BANK --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

        <div class="mb-5 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">

            <div>
                <h2 class="text-base font-semibold text-slate-800">
                    Pencatatan Kas & Bank
                </h2>

                <p class="mt-1 text-xs text-slate-400">
                    Status mutasi Kas/Bank yang berasal dari pembayaran supplier ini.
                </p>
            </div>


            @if($sudahPostingKasBank)

                <span class="inline-flex w-fit rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700">
                    <x-icon name="check" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" /> Sudah Diposting
                </span>

            @else

                <span class="inline-flex w-fit rounded-full bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-600">
                    Belum Diposting
                </span>

            @endif

        </div>


        @if($sudahPostingKasBank && $mutasiKasBank)

            @php
                $nilaiMutasi =
                    (float) $mutasiKasBank->debit > 0
                        ? (float) $mutasiKasBank->debit
                        : (float) $mutasiKasBank->kredit;
            @endphp


            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-4">

                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Tanggal Mutasi
                    </p>

                    <p class="mt-1 text-sm font-semibold text-slate-800">
                        {{ $mutasiKasBank->tanggal?->format('d/m/Y') ?? '-' }}
                    </p>
                </div>


                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Tipe
                    </p>

                    <p class="mt-1 text-sm font-medium text-slate-700">
                        {{ str_replace(
                            '_',
                            ' ',
                            $mutasiKasBank->tipe ?? '-'
                        ) }}
                    </p>
                </div>


                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Arah
                    </p>

                    <span class="mt-1 inline-flex rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-600">
                        Keluar
                    </span>
                </div>


                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Nominal
                    </p>

                    <p class="mt-1 text-sm font-bold text-red-600">
                        Rp {{ number_format(
                            $nilaiMutasi,
                            0,
                            ',',
                            '.'
                        ) }}
                    </p>
                </div>


                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Rekening
                    </p>

                    <p class="mt-1 text-sm font-semibold text-slate-800">
                        {{ $mutasiKasBank
                            ->rekeningKasBank
                            ?->nama_rekening ?? '-' }}
                    </p>
                </div>


                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Nomor Referensi
                    </p>

                    <p class="mt-1 text-sm font-medium text-slate-700">
                        {{ $mutasiKasBank->nomor_referensi ?? '-' }}
                    </p>
                </div>


                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Reference Type
                    </p>

                    <p class="mt-1 text-sm font-medium text-slate-700">
                        {{ $mutasiKasBank->reference_type ?? '-' }}
                    </p>
                </div>


                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Reference ID
                    </p>

                    <p class="mt-1 text-sm font-medium text-slate-700">
                        {{ $mutasiKasBank->reference_id ?? '-' }}
                    </p>
                </div>


                <div class="md:col-span-2 lg:col-span-4">
                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Keterangan Mutasi
                    </p>

                    <p class="mt-1 text-sm leading-6 text-slate-700">
                        {{ $mutasiKasBank->keterangan ?? '-' }}
                    </p>
                </div>

            </div>


            <div class="mt-5 border-t border-slate-100 pt-5">

                <a
                    href="{{ route('mutasi-kas-bank.index', [
                        'rekening_kas_bank_id' =>
                            $mutasiKasBank->rekening_kas_bank_id
                    ]) }}"
                    class="inline-flex rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50"
                >
                    Lihat Mutasi Kas/Bank
                </a>

            </div>

        @else

            <div class="rounded-xl bg-red-50 p-4">

                <p class="text-sm font-semibold text-red-700">
                    Mutasi Kas/Bank tidak ditemukan.
                </p>

                <p class="mt-1 text-xs leading-5 text-red-600">
                    Pembayaran ini belum memiliki pencatatan Mutasi Kas/Bank dengan reference type PEMBAYARAN_SUPPLIER.
                </p>

            </div>

        @endif

    </div>


    {{-- STATUS REKONSILIASI --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

        <div class="mb-5 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">

            <div>
                <h2 class="text-base font-semibold text-slate-800">
                    Rekonsiliasi Bank
                </h2>

                <p class="mt-1 text-xs text-slate-400">
                    Pencocokan antara transaksi internal dengan mutasi rekening koran.
                </p>
            </div>


            @if($sudahDirekonsiliasi)

                <span class="inline-flex w-fit rounded-full bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-700">
                    <x-icon name="check" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" /> Sudah Direkonsiliasi
                </span>

            @elseif($sudahPostingKasBank)

                <span class="inline-flex w-fit rounded-full bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700">
                    Menunggu Rekonsiliasi
                </span>

            @else

                <span class="inline-flex w-fit rounded-full bg-slate-100 px-3 py-1.5 text-xs font-semibold text-slate-500">
                    Belum Tersedia
                </span>

            @endif

        </div>


        @if($sudahDirekonsiliasi && $transaksiBank)

            <div class="rounded-xl border border-emerald-200 bg-emerald-50/40 p-5">

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-4">

                    {{-- TANGGAL BANK --}}
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-400">
                            Tanggal Bank
                        </p>

                        <p class="mt-1 text-sm font-semibold text-slate-800">
                            {{ $transaksiBank->tanggal?->format('d/m/Y') ?? '-' }}
                        </p>
                    </div>


                    {{-- REKENING BANK --}}
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-400">
                            Rekening
                        </p>

                        <p class="mt-1 text-sm font-semibold text-slate-800">
                            {{ $transaksiBank
                                ->rekeningKasBank
                                ?->nama_rekening ?? '-' }}
                        </p>
                    </div>


                    {{-- JENIS --}}
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-400">
                            Jenis
                        </p>

                        @if($transaksiBank->jenis === 'KELUAR')

                            <span class="mt-1 inline-flex rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-600">
                                Keluar
                            </span>

                        @else

                            <span class="mt-1 inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-600">
                                Masuk
                            </span>

                        @endif
                    </div>


                    {{-- NOMINAL BANK --}}
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-400">
                            Nominal Bank
                        </p>

                        <p class="mt-1 text-sm font-bold text-slate-800">
                            Rp {{ number_format(
                                (float) $transaksiBank->jumlah,
                                0,
                                ',',
                                '.'
                            ) }}
                        </p>
                    </div>


                    {{-- NAMA BANK --}}
                    <div class="md:col-span-2">
                        <p class="text-xs uppercase tracking-wide text-slate-400">
                            Nama Pengirim / Penerima
                        </p>

                        <p class="mt-1 text-sm font-semibold text-[#15235F]">
                            {{ $transaksiBank->nama_pengirim_penerima ?? '-' }}
                        </p>
                    </div>


                    {{-- REFERENSI BANK --}}
                    <div class="md:col-span-2">
                        <p class="text-xs uppercase tracking-wide text-slate-400">
                            Referensi Bank
                        </p>

                        <p class="mt-1 text-sm font-medium text-slate-700">
                            {{ $transaksiBank->nomor_referensi_bank ?? '-' }}
                        </p>
                    </div>


                    {{-- DESKRIPSI BANK --}}
                    <div class="md:col-span-2 lg:col-span-4">
                        <p class="text-xs uppercase tracking-wide text-slate-400">
                            Deskripsi Rekening Koran
                        </p>

                        <p class="mt-1 text-sm leading-6 text-slate-700">
                            {{ $transaksiBank->deskripsi_bank ?? '-' }}
                        </p>
                    </div>


                    {{-- REKONSILIASI --}}
                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-400">
                            Tanggal Rekonsiliasi
                        </p>

                        <p class="mt-1 text-sm text-slate-700">
                            {{ $rekonsiliasi
                                ?->tanggal_rekonsiliasi
                                ?->format('d/m/Y H:i') ?? '-' }}
                        </p>
                    </div>


                    <div>
                        <p class="text-xs uppercase tracking-wide text-slate-400">
                            Direkonsiliasi Oleh
                        </p>

                        <p class="mt-1 text-sm text-slate-700">
                            {{ $rekonsiliasi
                                ?->creator
                                ?->name ?? '-' }}
                        </p>
                    </div>


                    @if($rekonsiliasi?->catatan)

                        <div class="md:col-span-2">
                            <p class="text-xs uppercase tracking-wide text-slate-400">
                                Catatan Rekonsiliasi
                            </p>

                            <p class="mt-1 text-sm text-slate-700">
                                {{ $rekonsiliasi->catatan }}
                            </p>
                        </div>

                    @endif

                </div>


                <div class="mt-5 border-t border-emerald-100 pt-5">

                    <a
                        href="{{ route(
                            'rekonsiliasi-bank.show',
                            $transaksiBank
                        ) }}"
                        class="inline-flex rounded-lg bg-[#15235F] px-4 py-2.5 text-sm font-medium text-white hover:bg-[#101b4d]"
                    >
                        Lihat Detail Rekonsiliasi
                    </a>

                </div>

            </div>


        @elseif($sudahPostingKasBank)

            <div class="rounded-xl border border-amber-200 bg-amber-50 p-5">

                <p class="text-sm font-semibold text-amber-700">
                    Pembayaran belum direkonsiliasi.
                </p>

                <p class="mt-1 text-xs leading-5 text-amber-600">
                    Mutasi Kas/Bank sudah terbentuk, tetapi belum dicocokkan dengan transaksi rekening koran.
                </p>


                <div class="mt-4">

                    <a
                        href="{{ route('rekonsiliasi-bank.index', [
                            'rekening_kas_bank_id' =>
                                $pembayaranSupplier
                                    ->rekening_kas_bank_id
                        ]) }}"
                        class="inline-flex rounded-lg bg-[#32AAA6] px-4 py-2.5 text-sm font-medium text-white hover:opacity-90"
                    >
                        Buka Rekonsiliasi Bank
                    </a>

                </div>

            </div>


        @else

            <div class="rounded-xl bg-slate-50 p-5">

                <p class="text-sm font-semibold text-slate-600">
                    Rekonsiliasi belum dapat dilakukan.
                </p>

                <p class="mt-1 text-xs leading-5 text-slate-500">
                    Pembayaran harus memiliki Mutasi Kas/Bank terlebih dahulu sebelum dapat direkonsiliasi dengan rekening koran.
                </p>

            </div>

        @endif

    </div>

</div>

@endsection