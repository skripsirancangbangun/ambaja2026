@extends('layouts.app')

@section('title', 'Detail Uang Keluar')

@section('content')
<div class="min-h-screen bg-slate-50">
    <div class="mx-auto max-w-5xl px-4 py-6 sm:px-6 lg:px-8">

        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">
                    Detail Uang Keluar
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    {{ $uangKeluar->nomor_transaksi }}
                </p>
            </div>

            <a
                href="{{ route('uang-keluar.index') }}"
                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700"
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

        <div class="rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="flex items-center justify-between border-b border-slate-200 px-6 py-4">

                <div>
                    <h2 class="font-semibold text-slate-900">
                        {{ $uangKeluar->nomor_transaksi }}
                    </h2>

                    <p class="mt-1 text-sm text-slate-500">
                        {{ $uangKeluar->tanggal?->format('d/m/Y') }}
                    </p>
                </div>

                @if ($uangKeluar->status === 'TERCATAT')
                    <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                        TERCATAT
                    </span>
                @else
                    <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
                        DRAFT
                    </span>
                @endif

            </div>

            <div class="grid grid-cols-1 gap-6 p-6 md:grid-cols-2">

                <div>
                    <div class="text-xs font-semibold uppercase text-slate-500">
                        Kas / Rekening Bank
                    </div>

                    <div class="mt-1 font-semibold text-slate-900">
                        {{ $uangKeluar->rekeningKasBank?->nama_rekening ?? '-' }}
                    </div>

                    @if ($uangKeluar->rekeningKasBank?->jenis === 'BANK')
                        <div class="mt-1 text-sm text-slate-500">
                            {{ $uangKeluar->rekeningKasBank?->nama_bank }}
                            -
                            {{ $uangKeluar->rekeningKasBank?->nomor_rekening }}
                        </div>
                    @endif
                </div>

                <div>
                    <div class="text-xs font-semibold uppercase text-slate-500">
                        Total Pengeluaran
                    </div>

                    <div class="mt-1 text-xl font-bold text-slate-900">
                        Rp {{ number_format((float) $uangKeluar->jumlah, 0, ',', '.') }}
                    </div>
                </div>

                <div>
                    <div class="text-xs font-semibold uppercase text-slate-500">
                        Referensi
                    </div>

                    <div class="mt-1 text-sm text-slate-900">
                        {{ $uangKeluar->referensi ?: '-' }}
                    </div>
                </div>

                <div>
                    <div class="text-xs font-semibold uppercase text-slate-500">
                        Dibuat Oleh
                    </div>

                    <div class="mt-1 text-sm text-slate-700">
                        {{ $uangKeluar->creator?->name ?? '-' }}
                    </div>
                </div>

                <div class="md:col-span-2">
                    <div class="text-xs font-semibold uppercase text-slate-500">
                        Keterangan Umum
                    </div>

                    <div class="mt-1 text-sm text-slate-700">
                        {{ $uangKeluar->keterangan ?: '-' }}
                    </div>
                </div>

            </div>

            {{-- RINCIAN PENGELUARAN --}}
            <div class="border-t border-slate-200 px-6 py-5">

                <div class="mb-4">
                    <h3 class="font-semibold text-slate-900">
                        Rincian Pengeluaran
                    </h3>

                    <p class="mt-1 text-xs text-slate-500">
                        Daftar akun biaya yang dibayarkan dalam transaksi ini.
                    </p>
                </div>

                @if ($uangKeluar->details->isNotEmpty())

                    <div class="overflow-hidden rounded-xl border border-slate-200">

                        <div class="overflow-x-auto">

                            <table class="min-w-full divide-y divide-slate-200">

                                <thead class="bg-slate-100">

                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">
                                            No
                                        </th>

                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">
                                            Akun Biaya
                                        </th>

                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">
                                            Keterangan
                                        </th>

                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-600">
                                            Jumlah
                                        </th>
                                    </tr>

                                </thead>

                                <tbody class="divide-y divide-slate-100">

                                    @foreach ($uangKeluar->details as $detail)

                                        <tr>

                                            <td class="px-4 py-3 text-sm text-slate-500">
                                                {{ $loop->iteration }}
                                            </td>

                                            <td class="px-4 py-3 text-sm">

                                                @if ($detail->akun)

                                                    <div class="font-semibold text-slate-900">
                                                        {{ $detail->akun->kode_akun }}
                                                        -
                                                        {{ $detail->akun->nama_akun }}
                                                    </div>

                                                    <div class="mt-0.5 text-xs text-slate-500">
                                                        {{ $detail->akun->sub_kategori ?? $detail->akun->kategori_akun }}
                                                    </div>

                                                @else

                                                    <span class="text-slate-400">
                                                        Akun belum ditentukan
                                                    </span>

                                                @endif

                                            </td>

                                            <td class="px-4 py-3 text-sm text-slate-700">
                                                {{ $detail->tujuan }}
                                            </td>

                                            <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-slate-900">
                                                Rp {{ number_format((float) $detail->jumlah, 0, ',', '.') }}
                                            </td>

                                        </tr>

                                    @endforeach

                                </tbody>

                                <tfoot class="border-t border-slate-200 bg-slate-50">

                                    <tr>

                                        <td
                                            colspan="3"
                                            class="px-4 py-3 text-right text-sm font-semibold text-slate-700"
                                        >
                                            TOTAL
                                        </td>

                                        <td class="whitespace-nowrap px-4 py-3 text-right text-base font-bold text-[#15235F]">
                                            Rp {{ number_format((float) $uangKeluar->details->sum('jumlah'), 0, ',', '.') }}
                                        </td>

                                    </tr>

                                </tfoot>

                            </table>

                        </div>

                    </div>

                @else

                    {{-- KOMPATIBILITAS TRANSAKSI LAMA --}}
                    <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3">

                        <div class="text-xs font-semibold uppercase text-amber-700">
                            Transaksi Lama
                        </div>

                        <div class="mt-2 text-sm text-slate-700">
                            {{ $uangKeluar->tujuan ?: '-' }}
                        </div>

                        <div class="mt-1 font-semibold text-slate-900">
                            Rp {{ number_format((float) $uangKeluar->jumlah, 0, ',', '.') }}
                        </div>

                    </div>

                @endif

            </div>

            @if (
                $uangKeluar->status === 'TERCATAT'
                && $uangKeluar->mutasiKasBank
            )

                <div class="border-t border-slate-200 bg-slate-50 px-6 py-5">

                    <h3 class="mb-4 font-semibold text-slate-900">
                        Informasi Mutasi Kas/Bank
                    </h3>

                    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">

                        <div>
                            <div class="text-xs text-slate-500">
                                Debit
                            </div>

                            <div class="mt-1 font-semibold text-slate-900">
                                Rp {{ number_format((float) $uangKeluar->mutasiKasBank->debit, 0, ',', '.') }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs text-slate-500">
                                Kredit
                            </div>

                            <div class="mt-1 font-semibold text-red-600">
                                Rp {{ number_format((float) $uangKeluar->mutasiKasBank->kredit, 0, ',', '.') }}
                            </div>
                        </div>

                        <div>
                            <div class="text-xs text-slate-500">
                                Saldo Setelah Transaksi
                            </div>

                            <div class="mt-1 font-bold text-[#15235F]">
                                Rp {{ number_format((float) $uangKeluar->mutasiKasBank->saldo, 0, ',', '.') }}
                            </div>
                        </div>

                    </div>

                </div>

            @endif

            <div class="flex flex-wrap justify-end gap-2 border-t border-slate-200 px-6 py-4">

                @if ($uangKeluar->status === 'DRAFT')

                    <form
                        action="{{ route('uang-keluar.destroy', $uangKeluar) }}"
                        method="POST"
                        onsubmit="return confirm('Hapus draft Uang Keluar ini?')"
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
                        href="{{ route('uang-keluar.edit', $uangKeluar) }}"
                        class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100"
                    >
                        Edit
                    </a>

                    <form
                        action="{{ route('uang-keluar.catat', $uangKeluar) }}"
                        method="POST"
                        onsubmit="return confirm('Catat transaksi ini? Saldo Kas/Bank akan berkurang dan transaksi tidak dapat diedit lagi.')"
                    >
                        @csrf

                        <button
                            type="submit"
                            class="rounded-lg bg-[#15235F] px-5 py-2 text-sm font-semibold text-white hover:opacity-90"
                        >
                            Catat Transaksi
                        </button>

                    </form>

                @else

                    <span class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-sm font-medium text-emerald-700">
                        Transaksi sudah masuk Mutasi Kas/Bank
                    </span>

                @endif

            </div>

        </div>

    </div>
</div>
@endsection