@extends('layouts.app')

@section('title', 'Detail Transfer Antar Rekening')

@section('content')
<div class="min-h-screen bg-slate-50">

    <div class="mx-auto max-w-5xl px-4 py-6 sm:px-6 lg:px-8">

        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h1 class="text-2xl font-bold text-slate-900">
                    Detail Transfer Antar Rekening
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    {{ $transferKasBank->nomor_transaksi }}
                </p>
            </div>

            <a
                href="{{ route('transfer-kas-bank.index') }}"
                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100"
            >
                Kembali
            </a>

        </div>

        @if (session('success'))
            <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ session('error') }}
            </div>
        @endif

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">

                <div>
                    <div class="font-semibold text-slate-900">
                        {{ $transferKasBank->nomor_transaksi }}
                    </div>

                    <div class="mt-1 text-sm text-slate-500">
                        {{ $transferKasBank->tanggal?->format('d/m/Y') }}
                    </div>
                </div>

                @if ($transferKasBank->status === 'TERCATAT')

                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                        TERCATAT
                    </span>

                @else

                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
                        DRAFT
                    </span>

                @endif

            </div>

            <div class="p-6">

                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">

                    <div>
                        <div class="text-xs font-semibold uppercase text-slate-500">
                            Rekening Asal
                        </div>

                        <div class="mt-1 font-semibold text-slate-900">
                            {{ $transferKasBank->rekeningAsal?->nama_rekening ?? '-' }}
                        </div>

                        <div class="mt-1 text-sm text-slate-500">
                            {{ $transferKasBank->rekeningAsal?->jenis }}

                            @if ($transferKasBank->rekeningAsal?->jenis === 'BANK')
                                • {{ $transferKasBank->rekeningAsal?->nama_bank }}
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center justify-center">
                        <div class="rounded-full bg-slate-100 px-5 py-3 text-xl font-bold text-[#15235F]">
                            →
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-semibold uppercase text-slate-500">
                            Rekening Tujuan
                        </div>

                        <div class="mt-1 font-semibold text-slate-900">
                            {{ $transferKasBank->rekeningTujuan?->nama_rekening ?? '-' }}
                        </div>

                        <div class="mt-1 text-sm text-slate-500">
                            {{ $transferKasBank->rekeningTujuan?->jenis }}

                            @if ($transferKasBank->rekeningTujuan?->jenis === 'BANK')
                                • {{ $transferKasBank->rekeningTujuan?->nama_bank }}
                            @endif
                        </div>
                    </div>

                </div>

                <div class="my-6 border-t border-slate-200"></div>

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

                    <div>
                        <div class="text-xs font-semibold uppercase text-slate-500">
                            Jumlah Transfer
                        </div>

                        <div class="mt-1 text-2xl font-bold text-[#15235F]">
                            Rp {{ number_format((float) $transferKasBank->jumlah, 0, ',', '.') }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-semibold uppercase text-slate-500">
                            Referensi
                        </div>

                        <div class="mt-1 text-sm text-slate-900">
                            {{ $transferKasBank->referensi ?: '-' }}
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <div class="text-xs font-semibold uppercase text-slate-500">
                            Keterangan
                        </div>

                        <div class="mt-1 text-sm text-slate-700">
                            {{ $transferKasBank->keterangan ?: '-' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-semibold uppercase text-slate-500">
                            Dibuat Oleh
                        </div>

                        <div class="mt-1 text-sm text-slate-700">
                            {{ $transferKasBank->creator?->name ?? '-' }}
                        </div>
                    </div>

                </div>

            </div>

            @if (
                $transferKasBank->status === 'TERCATAT'
                && $transferKasBank->mutasiKeluar
                && $transferKasBank->mutasiMasuk
            )

                <div class="border-t border-slate-200 bg-slate-50 px-6 py-5">

                    <h3 class="mb-4 font-semibold text-slate-900">
                        Mutasi Kas / Bank
                    </h3>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">

                        <div class="rounded-lg border border-red-200 bg-white p-4">

                            <div class="text-xs font-semibold uppercase text-slate-500">
                                Rekening Asal
                            </div>

                            <div class="mt-1 font-semibold text-slate-900">
                                {{ $transferKasBank->rekeningAsal?->nama_rekening }}
                            </div>

                            <div class="mt-4 text-xs text-slate-500">
                                Kredit
                            </div>

                            <div class="font-semibold text-red-600">
                                Rp {{ number_format(
                                    (float) $transferKasBank->mutasiKeluar->kredit,
                                    0,
                                    ',',
                                    '.'
                                ) }}
                            </div>

                            <div class="mt-3 text-xs text-slate-500">
                                Saldo Setelah Transfer
                            </div>

                            <div class="font-bold text-slate-900">
                                Rp {{ number_format(
                                    (float) $transferKasBank->mutasiKeluar->saldo,
                                    0,
                                    ',',
                                    '.'
                                ) }}
                            </div>

                        </div>

                        <div class="rounded-lg border border-emerald-200 bg-white p-4">

                            <div class="text-xs font-semibold uppercase text-slate-500">
                                Rekening Tujuan
                            </div>

                            <div class="mt-1 font-semibold text-slate-900">
                                {{ $transferKasBank->rekeningTujuan?->nama_rekening }}
                            </div>

                            <div class="mt-4 text-xs text-slate-500">
                                Debit
                            </div>

                            <div class="font-semibold text-emerald-600">
                                Rp {{ number_format(
                                    (float) $transferKasBank->mutasiMasuk->debit,
                                    0,
                                    ',',
                                    '.'
                                ) }}
                            </div>

                            <div class="mt-3 text-xs text-slate-500">
                                Saldo Setelah Transfer
                            </div>

                            <div class="font-bold text-slate-900">
                                Rp {{ number_format(
                                    (float) $transferKasBank->mutasiMasuk->saldo,
                                    0,
                                    ',',
                                    '.'
                                ) }}
                            </div>

                        </div>

                    </div>

                </div>

            @endif

            <div class="flex flex-wrap justify-end gap-2 border-t border-slate-200 px-6 py-4">

                @if ($transferKasBank->status === 'DRAFT')

                    <form
                        action="{{ route('transfer-kas-bank.destroy', $transferKasBank) }}"
                        method="POST"
                        onsubmit="return confirm('Hapus draft transfer ini?')"
                    >
                        @csrf
                        @method('DELETE')

                        <button
                            type="submit"
                            class="rounded-lg border border-red-300 bg-white px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-50"
                        >
                            Hapus
                        </button>
                    </form>

                    <a
                        href="{{ route('transfer-kas-bank.edit', $transferKasBank) }}"
                        class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100"
                    >
                        Edit
                    </a>

                    <form
                        action="{{ route('transfer-kas-bank.catat', $transferKasBank) }}"
                        method="POST"
                        onsubmit="return confirm('Catat transfer ini? Saldo rekening asal akan berkurang dan rekening tujuan akan bertambah.')"
                    >
                        @csrf

                        <button
                            type="submit"
                            class="rounded-lg bg-[#15235F] px-5 py-2 text-sm font-semibold text-white hover:opacity-90"
                        >
                            Catat Transfer
                        </button>
                    </form>

                @else

                    <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-700">
                        Transfer sudah tercatat di Mutasi Kas/Bank.
                    </div>

                @endif

            </div>

        </div>

    </div>

</div>
@endsection