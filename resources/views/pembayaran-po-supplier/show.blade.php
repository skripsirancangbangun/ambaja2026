@extends('layouts.app')

@section('title', 'Detail Pembayaran PO Supplier')

@section('content')

<div class="min-h-screen bg-slate-50">

    <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8">

        {{-- HEADER --}}
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">

            <div>

                <a
                    href="{{ route('purchase-order.show', $pembayaranPOSupplier->purchaseOrder) }}"
                    class="inline-flex items-center text-sm font-medium text-slate-500 transition hover:text-[#0E194F]"
                >
                    <x-icon name="arrow-left" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" /> Kembali ke Purchase Order
                </a>

                <h1 class="mt-3 text-2xl font-bold text-slate-900">
                    Detail Pembayaran PO
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    {{ $pembayaranPOSupplier->nomor_pembayaran }}
                </p>

            </div>


            {{-- STATUS --}}
            <div>

                @if($pembayaranPOSupplier->status === 'DRAFT')

                    <span class="inline-flex rounded-full bg-amber-100 px-4 py-1.5 text-xs font-bold text-amber-700">
                        DRAFT
                    </span>

                @elseif($pembayaranPOSupplier->status === 'TERCATAT')

                    <span class="inline-flex rounded-full bg-emerald-100 px-4 py-1.5 text-xs font-bold text-emerald-700">
                        TERCATAT
                    </span>

                @elseif($pembayaranPOSupplier->status === 'DIBATALKAN')

                    <span class="inline-flex rounded-full bg-red-100 px-4 py-1.5 text-xs font-bold text-red-700">
                        DIBATALKAN
                    </span>

                @else

                    <span class="inline-flex rounded-full bg-slate-100 px-4 py-1.5 text-xs font-bold text-slate-700">
                        {{ $pembayaranPOSupplier->status }}
                    </span>

                @endif

            </div>

        </div>


        {{-- SUCCESS --}}
        @if(session('success'))

            <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('success') }}
            </div>

        @endif


        {{-- ERROR --}}
        @if(session('error'))

            <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ session('error') }}
            </div>

        @endif


        {{-- INFORMASI UTAMA --}}
        <div class="mb-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">

                <h2 class="font-bold text-slate-900">
                    Informasi Pembayaran
                </h2>

            </div>


            <div class="p-6">

                <div class="grid grid-cols-1 gap-x-8 gap-y-5 sm:grid-cols-2 lg:grid-cols-3">

                    {{-- NOMOR --}}
                    <div>

                        <div class="text-xs font-medium uppercase tracking-wide text-slate-500">
                            Nomor Pembayaran
                        </div>

                        <div class="mt-1 font-semibold text-slate-900">
                            {{ $pembayaranPOSupplier->nomor_pembayaran }}
                        </div>

                    </div>


                    {{-- PO --}}
                    <div>

                        <div class="text-xs font-medium uppercase tracking-wide text-slate-500">
                            Purchase Order
                        </div>

                        <div class="mt-1 font-semibold text-slate-900">
                            {{ $pembayaranPOSupplier->purchaseOrder?->nomor_po ?? '-' }}
                        </div>

                    </div>


                    {{-- SUPPLIER --}}
                    <div>

                        <div class="text-xs font-medium uppercase tracking-wide text-slate-500">
                            Supplier
                        </div>

                        <div class="mt-1 font-semibold text-slate-900">
                            {{ $pembayaranPOSupplier->supplier?->nama_supplier ?? '-' }}
                        </div>

                    </div>


                    {{-- TANGGAL --}}
                    <div>

                        <div class="text-xs font-medium uppercase tracking-wide text-slate-500">
                            Tanggal Pembayaran
                        </div>

                        <div class="mt-1 font-semibold text-slate-900">
                            {{ optional($pembayaranPOSupplier->tanggal_pembayaran)->format('d/m/Y') }}
                        </div>

                    </div>


                    {{-- METODE --}}
                    <div>

                        <div class="text-xs font-medium uppercase tracking-wide text-slate-500">
                            Metode Pembayaran
                        </div>

                        <div class="mt-1 font-semibold text-slate-900">
                            {{ $pembayaranPOSupplier->metode_pembayaran ?: '-' }}
                        </div>

                    </div>


                    {{-- REFERENSI --}}
                    <div>

                        <div class="text-xs font-medium uppercase tracking-wide text-slate-500">
                            Nomor Referensi
                        </div>

                        <div class="mt-1 font-semibold text-slate-900">
                            {{ $pembayaranPOSupplier->nomor_referensi ?: '-' }}
                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- JUMLAH PEMBAYARAN --}}
        <div class="mb-6 rounded-xl border border-blue-200 bg-blue-50 p-6">

            <div class="text-sm font-semibold text-blue-700">
                Jumlah Pembayaran
            </div>

            <div class="mt-2 text-3xl font-bold text-blue-950">
                Rp {{ number_format((float) $pembayaranPOSupplier->jumlah_bayar, 0, ',', '.') }}
            </div>

        </div>


        {{-- KAS BANK --}}
        <div class="mb-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">

                <h2 class="font-bold text-slate-900">
                    Kas / Rekening Bank
                </h2>

            </div>


            <div class="p-6">

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                    <div>

                        <div class="text-xs font-medium uppercase tracking-wide text-slate-500">
                            Rekening
                        </div>

                        <div class="mt-1 font-semibold text-slate-900">
                            {{ $pembayaranPOSupplier->rekeningKasBank?->nama_rekening ?? '-' }}
                        </div>


                        @if(
                            $pembayaranPOSupplier
                                ->rekeningKasBank
                                ?->jenis === 'BANK'
                        )

                            <div class="mt-1 text-sm text-slate-500">

                                {{ $pembayaranPOSupplier->rekeningKasBank?->nama_bank ?? '' }}

                                @if($pembayaranPOSupplier->rekeningKasBank?->nomor_rekening)

                                    ·
                                    {{ $pembayaranPOSupplier->rekeningKasBank->nomor_rekening }}

                                @endif

                            </div>

                        @endif

                    </div>


                    <div>

                        <div class="text-xs font-medium uppercase tracking-wide text-slate-500">
                            Akun COA
                        </div>


                        @if($pembayaranPOSupplier->rekeningKasBank?->akun)

                            <div class="mt-1 font-semibold text-slate-900">

                                {{ $pembayaranPOSupplier->rekeningKasBank->akun->kode_akun }}

                                -

                                {{ $pembayaranPOSupplier->rekeningKasBank->akun->nama_akun }}

                            </div>

                        @else

                            <div class="mt-1 font-semibold text-red-600">
                                Belum terhubung COA
                            </div>

                        @endif

                    </div>

                </div>

            </div>

        </div>


        {{-- PROGRESS PO --}}
        @if($pembayaranPOSupplier->purchaseOrder)

            @php
                $po =
                    $pembayaranPOSupplier
                        ->purchaseOrder;
            @endphp

            <div class="mb-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">

                    <h2 class="font-bold text-slate-900">
                        Progress Purchase Order
                    </h2>

                </div>


                <div class="p-6">

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">

                        <div class="rounded-lg bg-slate-50 p-4">

                            <div class="text-xs text-slate-500">
                                Total PO
                            </div>

                            <div class="mt-1 text-lg font-bold text-slate-900">
                                Rp {{ number_format((float) $po->total, 0, ',', '.') }}
                            </div>

                        </div>


                        <div class="rounded-lg bg-emerald-50 p-4">

                            <div class="text-xs text-emerald-700">
                                Total Pembayaran Tercatat
                            </div>

                            <div class="mt-1 text-lg font-bold text-emerald-800">
                                Rp {{ number_format((float) $po->total_dibayar_po, 0, ',', '.') }}
                            </div>

                        </div>


                        <div class="rounded-lg bg-blue-50 p-4">

                            <div class="text-xs text-blue-700">
                                Sisa Pembayaran
                            </div>

                            <div class="mt-1 text-lg font-bold text-blue-900">
                                Rp {{ number_format((float) $po->sisa_pembayaran_po, 0, ',', '.') }}
                            </div>

                        </div>

                    </div>


                    <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-2">

                        {{-- PEMBAYARAN --}}
                        <div>

                            <div class="flex items-center justify-between">

                                <span class="text-sm font-semibold text-slate-700">
                                    Pembayaran
                                </span>

                                <span class="text-sm font-bold text-slate-900">
                                    {{ number_format((float) $po->persentase_pembayaran, 2, ',', '.') }}%
                                </span>

                            </div>


                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-200">

                                <div
                                    class="h-full rounded-full bg-[#0E194F]"
                                    style="width: {{ min(100, (float) $po->persentase_pembayaran) }}%"
                                ></div>

                            </div>


                            <div class="mt-2 text-xs text-slate-500">
                                Status:
                                <span class="font-semibold text-slate-700">
                                    {{ $po->status_pembayaran }}
                                </span>
                            </div>

                        </div>


                        {{-- PENERIMAAN --}}
                        <div>

                            <div class="flex items-center justify-between">

                                <span class="text-sm font-semibold text-slate-700">
                                    Penerimaan Barang
                                </span>

                                <span class="text-sm font-bold text-slate-900">
                                    {{ number_format((float) $po->persentase_penerimaan, 2, ',', '.') }}%
                                </span>

                            </div>


                            <div class="mt-2 h-2 overflow-hidden rounded-full bg-slate-200">

                                <div
                                    class="h-full rounded-full bg-emerald-600"
                                    style="width: {{ min(100, (float) $po->persentase_penerimaan) }}%"
                                ></div>

                            </div>


                            <div class="mt-2 text-xs text-slate-500">
                                Status:
                                <span class="font-semibold text-slate-700">
                                    {{ $po->status_penerimaan }}
                                </span>
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        @endif


        {{-- KETERANGAN --}}
        @if($pembayaranPOSupplier->keterangan)

            <div class="mb-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">

                <div class="text-xs font-medium uppercase tracking-wide text-slate-500">
                    Keterangan
                </div>

                <div class="mt-2 whitespace-pre-line text-sm leading-relaxed text-slate-700">
                    {{ $pembayaranPOSupplier->keterangan }}
                </div>

            </div>

        @endif


        {{-- DRAFT ACTION --}}
        @if($pembayaranPOSupplier->status === 'DRAFT')

            <div class="mb-6 overflow-hidden rounded-xl border border-amber-200 bg-amber-50">

                <div class="p-6">

                    <h3 class="font-bold text-amber-900">
                        Pembayaran belum dicatat
                    </h3>

                    <p class="mt-2 text-sm leading-relaxed text-amber-800">
                        Pembayaran ini masih Draft sehingga belum mengurangi saldo Kas/Bank dan belum membentuk jurnal.
                    </p>


                    <div class="mt-4 rounded-lg bg-white/70 p-4 text-sm text-amber-900">

                        Setelah dicatat:

                        <div class="mt-2 font-mono text-sm">

                            Dr Uang Muka Pembelian
                            <br>

                            <span class="pl-6">
                                Cr Kas / Bank
                            </span>

                        </div>

                    </div>


                    <form
                        method="POST"
                        action="{{ route('pembayaran-po-supplier.catat', $pembayaranPOSupplier) }}"
                        class="mt-5"
                        onsubmit="
                            return confirm(
                                'Catat pembayaran ini?\n\n'
                                + 'Saldo Kas/Bank akan berkurang sebesar Rp {{ number_format((float) $pembayaranPOSupplier->jumlah_bayar, 0, ',', '.') }}.'
                            );
                        "
                    >

                        @csrf

                        <button
                            type="submit"
                            class="inline-flex rounded-lg bg-[#0E194F] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#16266f]"
                        >
                            Catat Pembayaran
                        </button>

                    </form>

                </div>

            </div>

        @endif


        {{-- SUDAH TERCATAT --}}
        @if($pembayaranPOSupplier->status === 'TERCATAT')

            <div class="mb-6 overflow-hidden rounded-xl border border-emerald-200 bg-emerald-50">

                <div class="p-6">

                    <div class="flex items-start gap-3">

                        <div class="flex h-8 w-8 flex-none items-center justify-center rounded-full bg-emerald-600 text-sm font-bold text-white">
                            <x-icon name="check" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" />
                        </div>

                        <div>

                            <h3 class="font-bold text-emerald-900">
                                Pembayaran telah dicatat
                            </h3>

                            <p class="mt-1 text-sm text-emerald-700">
                                Saldo Kas/Bank telah berkurang dan jurnal accounting telah dibuat.
                            </p>

                        </div>

                    </div>

                </div>

            </div>

        @endif


        {{-- MUTASI KAS BANK --}}
        @if($pembayaranPOSupplier->mutasiKasBank)

            <div class="mb-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">

                    <h2 class="font-bold text-slate-900">
                        Mutasi Kas / Bank
                    </h2>

                </div>


                <div class="p-6">

                    <div class="grid grid-cols-1 gap-5 md:grid-cols-3">

                        <div>

                            <div class="text-xs uppercase text-slate-500">
                                Tipe
                            </div>

                            <div class="mt-1 font-semibold text-slate-900">
                                {{ $pembayaranPOSupplier->mutasiKasBank->tipe }}
                            </div>

                        </div>


                        <div>

                            <div class="text-xs uppercase text-slate-500">
                                Kredit
                            </div>

                            <div class="mt-1 font-semibold text-red-700">
                                Rp {{ number_format((float) $pembayaranPOSupplier->mutasiKasBank->kredit, 0, ',', '.') }}
                            </div>

                        </div>


                        <div>

                            <div class="text-xs uppercase text-slate-500">
                                Saldo Setelah Transaksi
                            </div>

                            <div class="mt-1 font-semibold text-slate-900">
                                Rp {{ number_format((float) $pembayaranPOSupplier->mutasiKasBank->saldo, 0, ',', '.') }}
                            </div>

                        </div>

                    </div>

                </div>

            </div>

        @endif


        {{-- JURNAL --}}
        @if($pembayaranPOSupplier->jurnal)

            <div class="mb-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

                <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">

                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">

                        <div>

                            <h2 class="font-bold text-slate-900">
                                Jurnal Accounting
                            </h2>

                            <p class="mt-1 text-xs text-slate-500">
                                {{ $pembayaranPOSupplier->jurnal->nomor_jurnal }}
                            </p>

                        </div>


                        <span class="inline-flex w-fit rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">
                            POSTED
                        </span>

                    </div>

                </div>


                <div class="overflow-x-auto">

                    <table class="min-w-full divide-y divide-slate-200">

                        <thead class="bg-slate-50">

                            <tr>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Akun
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Debit
                                </th>

                                <th class="px-6 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Kredit
                                </th>

                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Keterangan
                                </th>

                            </tr>

                        </thead>


                        <tbody class="divide-y divide-slate-100 bg-white">

                            @foreach($pembayaranPOSupplier->jurnal->details as $detail)

                                <tr>

                                    <td class="whitespace-nowrap px-6 py-4">

                                        <div class="text-sm font-semibold text-slate-900">

                                            {{ $detail->akun?->kode_akun ?? '-' }}

                                            -

                                            {{ $detail->akun?->nama_akun ?? '-' }}

                                        </div>

                                    </td>


                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-slate-700">

                                        @if((float) $detail->debit > 0)

                                            Rp {{ number_format((float) $detail->debit, 0, ',', '.') }}

                                        @else

                                            -

                                        @endif

                                    </td>


                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm text-slate-700">

                                        @if((float) $detail->kredit > 0)

                                            Rp {{ number_format((float) $detail->kredit, 0, ',', '.') }}

                                        @else

                                            -

                                        @endif

                                    </td>


                                    <td class="px-6 py-4 text-sm text-slate-600">
                                        {{ $detail->keterangan ?? '-' }}
                                    </td>

                                </tr>

                            @endforeach

                        </tbody>


                        <tfoot class="border-t-2 border-slate-300 bg-slate-50">

                            <tr>

                                <td class="px-6 py-4 font-bold text-slate-900">
                                    Total
                                </td>

                                <td class="px-6 py-4 text-right font-bold text-slate-900">
                                    Rp {{ number_format((float) $pembayaranPOSupplier->jurnal->total_debit, 0, ',', '.') }}
                                </td>

                                <td class="px-6 py-4 text-right font-bold text-slate-900">
                                    Rp {{ number_format((float) $pembayaranPOSupplier->jurnal->total_kredit, 0, ',', '.') }}
                                </td>

                                <td></td>

                            </tr>

                        </tfoot>

                    </table>

                </div>

            </div>

        @endif


        {{-- FOOTER --}}
        <div class="flex justify-end">

            <a
                href="{{ route('purchase-order.show', $pembayaranPOSupplier->purchaseOrder) }}"
                class="rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
            >
                Kembali ke Purchase Order
            </a>

        </div>

    </div>

</div>

@endsection