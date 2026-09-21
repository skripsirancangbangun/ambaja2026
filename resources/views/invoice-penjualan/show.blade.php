@extends('layouts.app')

@section('title', 'Detail Invoice ' . $invoicePenjualan->nomor_invoice)

@section('content')

@php

    /*
    |--------------------------------------------------------------------------
    | STATUS INVOICE
    |--------------------------------------------------------------------------
    */

    $statusInvoice =
        $invoicePenjualan->status_invoice
        ?? 'AKTIF';

    $isCancelled =
        $statusInvoice === 'DIBATALKAN';

    /*
    |--------------------------------------------------------------------------
    | PEMBAYARAN PIUTANG
    |--------------------------------------------------------------------------
    |
    | Invoice kredit yang sudah dibayar sebagian / lunas
    | tidak boleh langsung dibatalkan.
    |
    | Invoice tunai tetap boleh dibatalkan,
    | karena controller akan melakukan refund Kas/Bank.
    |
    */

    $hasCreditPayment =
        $invoicePenjualan->tipe_pembayaran === 'Kredit'
        &&
        (
            (float) $invoicePenjualan->sudah_dibayar > 0
            ||
            (
                isset($invoicePenjualan->penerimaanDetails)
                &&
                $invoicePenjualan->penerimaanDetails->isNotEmpty()
            )
        );

    /*
    |--------------------------------------------------------------------------
    | RETUR
    |--------------------------------------------------------------------------
    */

    $hasRetur =
        isset($invoicePenjualan->returPenjualans)
        &&
        $invoicePenjualan->returPenjualans->isNotEmpty();

    /*
    |--------------------------------------------------------------------------
    | BOLEH DIBATALKAN?
    |--------------------------------------------------------------------------
    */

    $canCancel =
        !$isCancelled
        &&
        !$hasCreditPayment
        &&
        !$hasRetur;

@endphp


<div
    class="mx-auto max-w-5xl space-y-6"
    x-data="{
        showEmailModal: false,
        showCancelModal: false,
        alasanPembatalan: ''
    }"
>

    {{-- ========================================================= --}}
    {{-- HEADER --}}
    {{-- ========================================================= --}}

    <div
        class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"
    >

        <div>

            <a
                href="{{ route('invoice-penjualan.index') }}"
                class="mb-2 inline-flex items-center gap-2 text-sm text-slate-500 hover:text-[#15235F]"
            >
                ← Kembali ke Daftar Invoice
            </a>

            <div class="flex flex-wrap items-center gap-3">

                <h2 class="text-xl font-semibold text-slate-800">
                    {{ $invoicePenjualan->nomor_invoice }}
                </h2>

                {{-- ================================================= --}}
                {{-- STATUS INVOICE --}}
                {{-- ================================================= --}}

                @if($isCancelled)

                    <span
                        class="inline-flex rounded-full border border-rose-200 bg-rose-100 px-3 py-1 text-xs font-bold text-rose-700"
                    >
                        DIBATALKAN
                    </span>

                @else

                    {{-- ============================================= --}}
                    {{-- STATUS PEMBAYARAN --}}
                    {{-- ============================================= --}}

                    @if($invoicePenjualan->status_pembayaran == 'Lunas')

                        <span
                            class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700"
                        >
                            Lunas
                        </span>

                    @elseif($invoicePenjualan->status_pembayaran == 'Sebagian')

                        <span
                            class="inline-flex rounded-full bg-amber-50 px-3 py-1 text-xs font-semibold text-amber-700"
                        >
                            Sebagian
                        </span>

                    @else

                        <span
                            class="inline-flex rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700"
                        >
                            Belum Lunas
                        </span>

                    @endif

                @endif

                {{-- ================================================= --}}
                {{-- EMAIL STATUS --}}
                {{-- ================================================= --}}

                @if(
                    !$isCancelled
                    &&
                    $invoicePenjualan->is_email_sent
                )

                    <span
                        class="inline-flex items-center gap-1 rounded-full border border-teal-200 bg-teal-50 px-3 py-1 text-xs font-semibold text-teal-700"
                        title="Terkirim ke {{ $invoicePenjualan->email_sent_to }}"
                    >
                        ✓ Email Terkirim

                        @if($invoicePenjualan->email_sent_at)

                            (
                            {{ $invoicePenjualan->email_sent_at->format('d/m/Y H:i') }}
                            )

                        @endif
                    </span>

                @endif

            </div>

        </div>


        {{-- ========================================================= --}}
        {{-- ACTION BUTTONS --}}
        {{-- ========================================================= --}}

        <div class="flex flex-wrap items-center gap-2">

            {{-- ===================================================== --}}
            {{-- EMAIL --}}
            {{-- ===================================================== --}}

            @if(!$isCancelled)

                <button
                    type="button"
                    @click="showEmailModal = true"
                    class="rounded-xl border border-teal-200 bg-teal-50 px-4 py-2 text-sm font-medium text-teal-800 transition hover:bg-teal-100"
                >
                    📧 Kirim Email Bukti Invoice
                </button>

            @endif


            {{-- ===================================================== --}}
            {{-- PDF --}}
            {{-- ===================================================== --}}

            <a
                href="{{ route(
                    'invoice-penjualan.pdf',
                    $invoicePenjualan
                ) }}"
                target="_blank"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
            >
                🖨️ Cetak PDF
            </a>


            {{-- ===================================================== --}}
            {{-- PEMBAYARAN --}}
            {{-- ===================================================== --}}

            @if(
                !$isCancelled
                &&
                $invoicePenjualan->tipe_pembayaran === 'Kredit'
                &&
                $invoicePenjualan->status_pembayaran != 'Lunas'
                &&
                (float) $invoicePenjualan->sisa_piutang > 0
            )

                <a
                    href="{{ route(
                        'pembayaran-penjualan.create',
                        [
                            'customer_id' =>
                                $invoicePenjualan->customer_id
                        ]
                    ) }}"
                    class="rounded-xl bg-[#15235F] px-4 py-2 text-sm font-medium text-white transition hover:bg-[#101b4d]"
                >
                    💰 Catat Pembayaran
                </a>

            @endif


            {{-- ===================================================== --}}
            {{-- RETUR --}}
            {{-- ===================================================== --}}

            @if(!$isCancelled)

                <a
                    href="{{ route(
                        'retur-penjualan.create',
                        [
                            'invoice_id' =>
                                $invoicePenjualan->id
                        ]
                    ) }}"
                    class="rounded-xl bg-rose-50 px-4 py-2 text-sm font-medium text-rose-700 transition hover:bg-rose-100"
                >
                    ↩️ Buat Retur
                </a>

            @endif


            {{-- ===================================================== --}}
            {{-- PEMBATALAN RESMI --}}
            {{-- ===================================================== --}}

            @if($canCancel)

                <button
                    type="button"
                    @click="showCancelModal = true"
                    class="rounded-xl border border-rose-300 bg-white px-4 py-2 text-sm font-semibold text-rose-600 transition hover:bg-rose-50"
                >
                    ✕ Batalkan Invoice
                </button>

            @endif

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- SUCCESS MESSAGE --}}
    {{-- ========================================================= --}}

    @if(session('success'))

        <div
            class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700"
        >
            {{ session('success') }}
        </div>

    @endif


    {{-- ========================================================= --}}
    {{-- ERROR MESSAGE --}}
    {{-- ========================================================= --}}

    @if(session('error'))

        <div
            class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"
        >
            {{ session('error') }}
        </div>

    @endif


    {{-- ========================================================= --}}
    {{-- CANCELLED INFORMATION --}}
    {{-- ========================================================= --}}

    @if($isCancelled)

        <div
            class="rounded-2xl border border-rose-200 bg-rose-50 p-5"
        >

            <div class="flex items-start gap-3">

                <div class="text-xl">
                    ⚠️
                </div>

                <div>

                    <p
                        class="font-semibold text-rose-800"
                    >
                        Invoice ini telah dibatalkan
                    </p>

                    <p
                        class="mt-1 text-sm text-rose-600"
                    >
                        Invoice sudah tidak aktif dan tidak dapat menerima
                        pembayaran maupun retur baru.
                    </p>

                </div>

            </div>

        </div>

    @endif


    {{-- ========================================================= --}}
    {{-- KETERANGAN PEMBATALAN TIDAK TERSEDIA --}}
    {{-- ========================================================= --}}

    @if(
        !$isCancelled
        &&
        !$canCancel
    )

        @if($hasCreditPayment)

            <div
                class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-700"
            >
                Invoice tidak dapat dibatalkan langsung karena sudah
                memiliki pembayaran piutang. Pembayaran harus dikoreksi
                terlebih dahulu.
            </div>

        @elseif($hasRetur)

            <div
                class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-700"
            >
                Invoice tidak dapat dibatalkan karena sudah memiliki
                transaksi retur penjualan.
            </div>

        @endif

    @endif


    {{-- ========================================================= --}}
    {{-- CARD INFORMASI --}}
    {{-- ========================================================= --}}

    <div
        class="grid grid-cols-1 gap-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm md:grid-cols-2"
    >

        {{-- ===================================================== --}}
        {{-- LEFT --}}
        {{-- ===================================================== --}}

        <div class="space-y-3">

            <div>

                <p
                    class="text-xs font-medium uppercase text-slate-400"
                >
                    Customer
                </p>

                <p
                    class="mt-1 text-base font-semibold text-slate-800"
                >
                    {{ $invoicePenjualan->customer->nama_customer ?? '-' }}
                </p>

                <p class="text-xs text-slate-500">
                    {{ $invoicePenjualan->customer->alamat ?? '' }}
                </p>

                <p class="text-xs text-slate-500">
                    Telp:
                    {{ $invoicePenjualan->customer->telepon ?? '-' }}
                </p>

            </div>


            <div>

                <p
                    class="text-xs font-medium uppercase text-slate-400"
                >
                    Gudang Pengeluaran
                </p>

                <p
                    class="mt-1 text-sm font-medium text-slate-700"
                >
                    {{ $invoicePenjualan->gudang->nama_gudang ?? '-' }}
                </p>

            </div>


            @if($invoicePenjualan->penjualan)

                <div>

                    <p
                        class="text-xs font-medium uppercase text-slate-400"
                    >
                        Sales Order Terkait
                    </p>

                    <a
                        href="{{ route(
                            'penjualan.show',
                            $invoicePenjualan->penjualan
                        ) }}"
                        class="mt-1 inline-block text-sm font-semibold text-[#15235F] hover:underline"
                    >
                        {{ $invoicePenjualan->penjualan->nomor_penjualan }}
                    </a>

                </div>

            @endif

        </div>


        {{-- ===================================================== --}}
        {{-- RIGHT --}}
        {{-- ===================================================== --}}

        <div class="space-y-3">

            <div class="grid grid-cols-2 gap-4">

                <div>

                    <p
                        class="text-xs font-medium uppercase text-slate-400"
                    >
                        Tanggal Invoice
                    </p>

                    <p
                        class="mt-1 text-sm font-semibold text-slate-700"
                    >
                        {{
                            $invoicePenjualan->tanggal_invoice
                                ? $invoicePenjualan
                                    ->tanggal_invoice
                                    ->format('d F Y')
                                : '-'
                        }}
                    </p>

                </div>


                <div>

                    <p
                        class="text-xs font-medium uppercase text-slate-400"
                    >
                        Jatuh Tempo
                    </p>

                    <p
                        class="mt-1 text-sm font-semibold
                        {{
                            (
                                !$isCancelled
                                &&
                                $invoicePenjualan->sisa_piutang > 0
                                &&
                                $invoicePenjualan->tanggal_jatuh_tempo
                                &&
                                $invoicePenjualan
                                    ->tanggal_jatuh_tempo
                                    ->isPast()
                            )
                                ? 'text-rose-600'
                                : 'text-slate-700'
                        }}"
                    >

                        {{
                            $invoicePenjualan->tanggal_jatuh_tempo
                                ? $invoicePenjualan
                                    ->tanggal_jatuh_tempo
                                    ->format('d F Y')
                                : '-'
                        }}

                    </p>

                </div>

            </div>


            <div>

                <p
                    class="text-xs font-medium uppercase text-slate-400"
                >
                    Tipe Pembayaran
                </p>

                <p
                    class="mt-1 text-sm font-medium text-slate-700"
                >

                    {{ $invoicePenjualan->tipe_pembayaran }}

                    @if($invoicePenjualan->rekeningKasBank)

                        (
                        Masuk ke
                        {{ $invoicePenjualan->rekeningKasBank->nama_rekening }}
                        )

                    @endif

                </p>

            </div>


            <div>

                <p
                    class="text-xs font-medium uppercase text-slate-400"
                >
                    Dibuat Oleh
                </p>

                <p class="mt-1 text-sm text-slate-600">

                    {{
                        $invoicePenjualan->creator->name
                        ?? '-'
                    }}

                    @if($invoicePenjualan->created_at)

                        (
                        {{
                            $invoicePenjualan
                                ->created_at
                                ->format('d/m/Y H:i')
                        }}
                        )

                    @endif

                </p>

            </div>


            @if($invoicePenjualan->catatan)

                <div>

                    <p
                        class="text-xs font-medium uppercase text-slate-400"
                    >
                        Catatan
                    </p>

                    <p
                        class="mt-1 whitespace-pre-line text-xs text-slate-600"
                    >
                        {{ $invoicePenjualan->catatan }}
                    </p>

                </div>

            @endif

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- ITEM --}}
    {{-- ========================================================= --}}

    <div
        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
    >

        <div
            class="border-b border-slate-200 px-6 py-4"
        >
            <h3
                class="text-sm font-semibold text-slate-700"
            >
                Rincian Barang Faktur
            </h3>
        </div>


        <div class="overflow-x-auto">

            <table class="w-full text-left text-sm">

                <thead class="bg-slate-50">

                    <tr
                        class="text-xs uppercase tracking-wide text-slate-400"
                    >

                        <th class="px-6 py-3 font-medium">
                            No
                        </th>

                        <th class="px-6 py-3 font-medium">
                            Kode & Nama Barang
                        </th>

                        <th class="px-6 py-3 text-right font-medium">
                            Qty
                        </th>

                        <th class="px-6 py-3 text-right font-medium">
                            Harga Satuan
                        </th>

                        <th class="px-6 py-3 text-right font-medium">
                            Diskon
                        </th>

                        <th class="px-6 py-3 text-right font-medium">
                            Subtotal
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-slate-100">

                    @foreach(
                        $invoicePenjualan->items
                        as $index => $item
                    )

                        <tr>

                            <td class="px-6 py-4 text-slate-500">
                                {{ $index + 1 }}
                            </td>


                            <td class="px-6 py-4">

                                <div
                                    class="font-medium text-slate-800"
                                >
                                    {{
                                        $item->barang->nama_barang
                                        ?? '-'
                                    }}
                                </div>

                                <div
                                    class="text-xs text-slate-400"
                                >
                                    {{
                                        $item->barang->kode_barang
                                        ?? ''
                                    }}
                                </div>

                            </td>


                            <td
                                class="px-6 py-4 text-right font-medium text-slate-700"
                            >

                                {{
                                    number_format(
                                        $item->qty,
                                        0,
                                        ',',
                                        '.'
                                    )
                                }}

                                {{
                                    $item
                                        ->barang
                                        ->satuan
                                        ->nama_satuan
                                    ?? ''
                                }}

                            </td>


                            <td
                                class="px-6 py-4 text-right text-slate-700"
                            >
                                Rp
                                {{
                                    number_format(
                                        $item->harga_satuan,
                                        0,
                                        ',',
                                        '.'
                                    )
                                }}
                            </td>


                            <td
                                class="px-6 py-4 text-right text-slate-700"
                            >
                                Rp
                                {{
                                    number_format(
                                        $item->diskon,
                                        0,
                                        ',',
                                        '.'
                                    )
                                }}
                            </td>


                            <td
                                class="px-6 py-4 text-right font-semibold text-slate-800"
                            >
                                Rp
                                {{
                                    number_format(
                                        $item->subtotal,
                                        0,
                                        ',',
                                        '.'
                                    )
                                }}
                            </td>

                        </tr>

                    @endforeach

                </tbody>

            </table>

        </div>


        {{-- ===================================================== --}}
        {{-- SUMMARY --}}
        {{-- ===================================================== --}}

        <div
            class="border-t border-slate-200 bg-slate-50/50 p-6"
        >

            <div
                class="ml-auto max-w-xs space-y-2 text-sm"
            >

                <div
                    class="flex justify-between text-slate-500"
                >
                    <span>
                        Subtotal:
                    </span>

                    <span
                        class="font-medium text-slate-800"
                    >
                        Rp
                        {{
                            number_format(
                                $invoicePenjualan->subtotal,
                                0,
                                ',',
                                '.'
                            )
                        }}
                    </span>
                </div>


                @if($invoicePenjualan->diskon > 0)

                    <div
                        class="flex justify-between text-slate-500"
                    >
                        <span>
                            Diskon:
                        </span>

                        <span
                            class="font-medium text-slate-800"
                        >
                            Rp
                            {{
                                number_format(
                                    $invoicePenjualan->diskon,
                                    0,
                                    ',',
                                    '.'
                                )
                            }}
                        </span>
                    </div>

                @endif


                @if($invoicePenjualan->pajak > 0)

                    <div
                        class="flex justify-between text-slate-500"
                    >
                        <span>
                            PPN
                            (
                            {{ $invoicePenjualan->pajak_persen }}%
                            ):
                        </span>

                        <span
                            class="font-medium text-slate-800"
                        >
                            Rp
                            {{
                                number_format(
                                    $invoicePenjualan->pajak,
                                    0,
                                    ',',
                                    '.'
                                )
                            }}
                        </span>
                    </div>

                @endif


                <div
                    class="flex justify-between border-t border-slate-200 pt-2 text-base font-bold text-[#15235F]"
                >
                    <span>
                        Total Faktur:
                    </span>

                    <span>
                        Rp
                        {{
                            number_format(
                                $invoicePenjualan->total,
                                0,
                                ',',
                                '.'
                            )
                        }}
                    </span>
                </div>


                <div
                    class="flex justify-between text-emerald-600"
                >
                    <span>
                        Sudah Dibayar:
                    </span>

                    <span class="font-semibold">
                        Rp
                        {{
                            number_format(
                                $invoicePenjualan->sudah_dibayar,
                                0,
                                ',',
                                '.'
                            )
                        }}
                    </span>
                </div>


                <div
                    class="flex justify-between text-base font-bold
                    {{
                        (
                            !$isCancelled
                            &&
                            $invoicePenjualan->sisa_piutang > 0
                        )
                            ? 'text-rose-600'
                            : 'text-slate-700'
                    }}"
                >

                    <span>
                        Sisa Piutang:
                    </span>

                    <span>
                        Rp
                        {{
                            number_format(
                                $invoicePenjualan->sisa_piutang,
                                0,
                                ',',
                                '.'
                            )
                        }}
                    </span>

                </div>

            </div>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- RIWAYAT PEMBAYARAN --}}
    {{-- ========================================================= --}}

    @if(
        isset($invoicePenjualan->pembayaranDetails)
        &&
        $invoicePenjualan->pembayaranDetails->isNotEmpty()
    )

        <div
            class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
        >

            <div
                class="border-b border-slate-200 px-6 py-4"
            >
                <h3
                    class="text-sm font-semibold text-slate-700"
                >
                    Riwayat Pembayaran Piutang
                </h3>
            </div>


            <div class="overflow-x-auto">

                <table class="w-full text-left text-sm">

                    <thead class="bg-slate-50">

                        <tr
                            class="text-xs uppercase tracking-wide text-slate-400"
                        >

                            <th
                                class="px-6 py-3 font-medium"
                            >
                                No. Bukti Bayar
                            </th>

                            <th
                                class="px-6 py-3 font-medium"
                            >
                                Tanggal
                            </th>

                            <th
                                class="px-6 py-3 font-medium"
                            >
                                Rekening Masuk
                            </th>

                            <th
                                class="px-6 py-3 text-right font-medium"
                            >
                                Nominal Dibayar
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-slate-100">

                        @foreach(
                            $invoicePenjualan->pembayaranDetails
                            as $detail
                        )

                            <tr>

                                <td
                                    class="px-6 py-4 font-semibold text-[#15235F]"
                                >

                                    @if($detail->pembayaranPenjualan)

                                        <a
                                            href="{{ route(
                                                'pembayaran-penjualan.show',
                                                $detail->pembayaranPenjualan
                                            ) }}"
                                            class="hover:underline"
                                        >
                                            {{
                                                $detail
                                                    ->pembayaranPenjualan
                                                    ->nomor_pembayaran
                                                ?? '-'
                                            }}
                                        </a>

                                    @else

                                        -

                                    @endif

                                </td>


                                <td
                                    class="px-6 py-4 text-slate-600"
                                >

                                    {{
                                        (
                                            $detail->pembayaranPenjualan
                                            &&
                                            $detail
                                                ->pembayaranPenjualan
                                                ->tanggal_pembayaran
                                        )
                                            ? $detail
                                                ->pembayaranPenjualan
                                                ->tanggal_pembayaran
                                                ->format('d/m/Y')
                                            : '-'
                                    }}

                                </td>


                                <td
                                    class="px-6 py-4 text-slate-600"
                                >

                                    {{
                                        $detail
                                            ->pembayaranPenjualan
                                            ?->rekeningKasBank
                                            ?->nama_rekening
                                        ?? '-'
                                    }}

                                </td>


                                <td
                                    class="px-6 py-4 text-right font-semibold text-emerald-600"
                                >
                                    Rp
                                    {{
                                        number_format(
                                            $detail->nominal_bayar,
                                            0,
                                            ',',
                                            '.'
                                        )
                                    }}
                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        </div>

    @endif


    {{-- ========================================================= --}}
    {{-- EMAIL MODAL --}}
    {{-- ========================================================= --}}

    <div
        x-show="showEmailModal"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 p-4 backdrop-blur-sm"
    >

        <div
            class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl"
            @click.away="showEmailModal = false"
        >

            <div
                class="flex items-center justify-between border-b border-slate-100 pb-3"
            >

                <h3
                    class="text-base font-semibold text-slate-800"
                >
                    Kirim Bukti Invoice via Email
                </h3>

                <button
                    type="button"
                    @click="showEmailModal = false"
                    class="text-slate-400 hover:text-slate-600"
                >
                    ✕
                </button>

            </div>


            <form
                action="{{ route(
                    'invoice-penjualan.kirim-email',
                    $invoicePenjualan
                ) }}"
                method="POST"
                class="mt-4 space-y-4"
            >

                @csrf


                <div>

                    <label
                        class="block text-xs font-semibold uppercase tracking-wider text-slate-600"
                    >
                        Email Pengirim
                    </label>

                    <input
                        type="email"
                        value="ezicraft88@gmail.com"
                        readonly
                        class="mt-1 w-full rounded-xl border border-slate-200 bg-slate-100 px-3.5 py-2 text-sm text-slate-600"
                    >

                </div>


                <div>

                    <label
                        class="block text-xs font-semibold uppercase tracking-wider text-slate-600"
                    >
                        Email Pelanggan / Penerima
                        <span class="text-rose-500">*</span>
                    </label>

                    <input
                        type="email"
                        name="email_tujuan"
                        value="{{ old(
                            'email_tujuan',
                            $invoicePenjualan->customer->email
                            ?? 'ezicraft88@gmail.com'
                        ) }}"
                        required
                        class="mt-1 w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm text-slate-800 focus:border-[#32AAA6] focus:outline-none"
                        placeholder="nama@pelanggan.com"
                    >

                    <p
                        class="mt-1 text-[11px] text-slate-400"
                    >
                        File PDF Invoice
                        {{ $invoicePenjualan->nomor_invoice }}
                        akan disertakan otomatis.
                    </p>

                </div>


                <div
                    class="flex justify-end gap-2 pt-2"
                >

                    <button
                        type="button"
                        @click="showEmailModal = false"
                        class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs font-medium text-slate-600 hover:bg-slate-50"
                    >
                        Batal
                    </button>

                    <button
                        type="submit"
                        class="rounded-xl bg-[#15235F] px-5 py-2 text-xs font-semibold text-white shadow-sm hover:bg-[#101b4d]"
                    >
                        📤 Kirim Email Sekarang
                    </button>

                </div>

            </form>

        </div>

    </div>


    {{-- ========================================================= --}}
    {{-- CANCEL MODAL --}}
    {{-- ========================================================= --}}

    @if($canCancel)

        <div
            x-show="showCancelModal"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm"
        >

            <div
                class="w-full max-w-lg rounded-2xl bg-white shadow-2xl"
                @click.away="showCancelModal = false"
            >

                {{-- ================================================= --}}
                {{-- HEADER --}}
                {{-- ================================================= --}}

                <div
                    class="flex items-center justify-between border-b border-slate-100 px-6 py-4"
                >

                    <div>

                        <h3
                            class="font-semibold text-slate-800"
                        >
                            Batalkan Invoice
                        </h3>

                        <p
                            class="mt-0.5 text-xs text-slate-400"
                        >
                            {{ $invoicePenjualan->nomor_invoice }}
                        </p>

                    </div>


                    <button
                        type="button"
                        @click="showCancelModal = false"
                        class="text-slate-400 hover:text-slate-600"
                    >
                        ✕
                    </button>

                </div>


                {{-- ================================================= --}}
                {{-- CONTENT --}}
                {{-- ================================================= --}}

                <form
                    action="{{ route(
                        'invoice-penjualan.batal',
                        [
                            'invoice' =>
                                $invoicePenjualan->id
                        ]
                    ) }}"
                    method="POST"
                >

                    @csrf


                    <div class="space-y-5 p-6">

                        {{-- ========================================= --}}
                        {{-- WARNING --}}
                        {{-- ========================================= --}}

                        <div
                            class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700"
                        >

                            <p class="font-semibold">
                                Pembatalan akan memengaruhi transaksi terkait.
                            </p>

                            <ul
                                class="mt-2 list-inside list-disc space-y-1 text-xs"
                            >

                                <li>
                                    Barang akan dikembalikan ke stok.
                                </li>

                                @if(
                                    $invoicePenjualan
                                        ->tipe_pembayaran
                                    === 'Kredit'
                                )

                                    <li>
                                        Piutang customer akan dibatalkan.
                                    </li>

                                @else

                                    <li>
                                        Kas/Bank akan dikurangi kembali sebesar
                                        Rp
                                        {{
                                            number_format(
                                                $invoicePenjualan->total,
                                                0,
                                                ',',
                                                '.'
                                            )
                                        }}.
                                    </li>

                                @endif

                                <li>
                                    Jurnal accounting akan dibuat reversal.
                                </li>

                                @if($invoicePenjualan->penjualan_id)

                                    <li>
                                        Status Sales Order terkait akan diperbarui.
                                    </li>

                                @endif

                            </ul>

                        </div>


                        {{-- ========================================= --}}
                        {{-- ALASAN --}}
                        {{-- ========================================= --}}

                        <div>

                            <label
                                class="block text-xs font-semibold uppercase tracking-wider text-slate-600"
                            >
                                Alasan Pembatalan
                                <span class="text-rose-500">*</span>
                            </label>

                            <textarea
                                name="alasan_pembatalan"
                                x-model="alasanPembatalan"
                                rows="4"
                                minlength="5"
                                maxlength="1000"
                                required
                                placeholder="Contoh: Salah input customer / transaksi dibatalkan pelanggan / kesalahan harga..."
                                class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm text-slate-800 outline-none focus:border-rose-400 focus:bg-white"
                            ></textarea>

                            <div
                                class="mt-1 flex justify-between text-[11px] text-slate-400"
                            >
                                <span>
                                    Minimal 5 karakter.
                                </span>

                                <span
                                    x-text="
                                        alasanPembatalan.length
                                        + '/1000'
                                    "
                                ></span>
                            </div>

                        </div>

                    </div>


                    {{-- ================================================= --}}
                    {{-- FOOTER --}}
                    {{-- ================================================= --}}

                    <div
                        class="flex justify-end gap-3 border-t border-slate-100 bg-slate-50 px-6 py-4"
                    >

                        <button
                            type="button"
                            @click="showCancelModal = false"
                            class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50"
                        >
                            Jangan Batalkan
                        </button>


                        <button
                            type="submit"
                            :disabled="alasanPembatalan.trim().length < 5"
                            class="rounded-xl bg-rose-600 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-700 disabled:cursor-not-allowed disabled:bg-slate-300"
                        >
                            Ya, Batalkan Invoice
                        </button>

                    </div>

                </form>

            </div>

        </div>

    @endif


    {{-- ========================================================= --}}
    {{-- HISTORI RETUR PENJUALAN --}}
    {{-- ========================================================= --}}

    @if(
        $invoicePenjualan->returPenjualans
        &&
        $invoicePenjualan->returPenjualans->isNotEmpty()
    )

        <div
            class="overflow-hidden rounded-2xl border border-rose-200 bg-white shadow-sm"
        >

            <div
                class="flex items-center justify-between border-b border-rose-200 bg-rose-50/50 px-6 py-4"
            >

                <div>

                    <h3
                        class="text-sm font-bold uppercase tracking-wider text-rose-800"
                    >
                        Histori Retur Penjualan
                    </h3>

                    <p
                        class="text-xs text-rose-600"
                    >
                        Daftar pengembalian barang yang memotong piutang /
                        pengembalian kas untuk faktur ini.
                    </p>

                </div>

            </div>


            <div class="overflow-x-auto">

                <table
                    class="w-full text-left text-sm"
                >

                    <thead
                        class="bg-rose-50/30 text-xs uppercase tracking-wider text-rose-700"
                    >

                        <tr>

                            <th
                                class="px-6 py-3 font-medium"
                            >
                                No. Retur
                            </th>

                            <th
                                class="px-6 py-3 font-medium"
                            >
                                Tanggal Retur
                            </th>

                            <th
                                class="px-6 py-3 text-right font-medium"
                            >
                                Total Retur (Rp)
                            </th>

                            <th
                                class="px-6 py-3 text-right font-medium"
                            >
                                Potong Piutang (Rp)
                            </th>

                            <th
                                class="px-6 py-3 text-right font-medium"
                            >
                                Refund Kas (Rp)
                            </th>

                            <th
                                class="px-6 py-3 font-medium"
                            >
                                Alasan
                            </th>

                            <th
                                class="px-6 py-3 text-right font-medium"
                            >
                                Aksi
                            </th>

                        </tr>

                    </thead>


                    <tbody
                        class="divide-y divide-slate-100"
                    >

                        @foreach(
                            $invoicePenjualan->returPenjualans
                            as $retur
                        )

                            <tr
                                class="hover:bg-slate-50"
                            >

                                <td
                                    class="px-6 py-4 font-mono font-bold text-[#15235F]"
                                >

                                    <a
                                        href="{{ route(
                                            'retur-penjualan.show',
                                            $retur
                                        ) }}"
                                        class="hover:underline"
                                    >
                                        {{ $retur->nomor_retur }}
                                    </a>

                                </td>


                                <td
                                    class="px-6 py-4 text-slate-600"
                                >
                                    {{
                                        $retur->tanggal_retur
                                            ? $retur
                                                ->tanggal_retur
                                                ->format('d/m/Y')
                                            : '-'
                                    }}
                                </td>


                                <td
                                    class="px-6 py-4 text-right font-mono text-xs font-bold text-rose-600"
                                >
                                    Rp
                                    {{
                                        number_format(
                                            $retur->total,
                                            0,
                                            ',',
                                            '.'
                                        )
                                    }}
                                </td>


                                <td
                                    class="px-6 py-4 text-right font-mono text-xs font-bold text-slate-700"
                                >
                                    Rp
                                    {{
                                        number_format(
                                            $retur->potong_piutang,
                                            0,
                                            ',',
                                            '.'
                                        )
                                    }}
                                </td>


                                <td
                                    class="px-6 py-4 text-right font-mono text-xs font-bold text-emerald-600"
                                >
                                    Rp
                                    {{
                                        number_format(
                                            $retur->pengembalian_kas,
                                            0,
                                            ',',
                                            '.'
                                        )
                                    }}
                                </td>


                                <td
                                    class="max-w-xs px-6 py-4 text-xs text-slate-600"
                                >
                                    {{
                                        $retur->alasan
                                        ??
                                        $retur->alasan_retur
                                        ??
                                        '-'
                                    }}
                                </td>


                                <td
                                    class="whitespace-nowrap px-6 py-4 text-right"
                                >

                                    <a
                                        href="{{ route(
                                            'retur-penjualan.pdf',
                                            $retur
                                        ) }}"
                                        target="_blank"
                                        class="inline-flex items-center gap-1 rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-100"
                                    >
                                        🖨️ Nota Retur PDF
                                    </a>

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        </div>

    @endif

</div>

@endsection