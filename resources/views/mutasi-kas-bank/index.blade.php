@extends('layouts.app')

@section('title', 'Mutasi Kas & Bank')

@section('content')
<div class="min-h-screen bg-slate-50">

    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">

        {{-- HEADER --}}
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900">
                Mutasi Kas & Bank
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Riwayat seluruh transaksi yang memengaruhi saldo Kas dan Rekening Bank.
            </p>
        </div>


        {{-- SUMMARY --}}
        <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="text-sm font-medium text-slate-500">
                    Total Saldo Kas & Bank
                </div>

                <div class="mt-2 text-2xl font-bold text-[#15235F]">
                    Rp {{ number_format($totalSaldo, 0, ',', '.') }}
                </div>
            </div>

            <div class="rounded-xl border border-emerald-200 bg-white p-5 shadow-sm">
                <div class="text-sm font-medium text-slate-500">
                    Total Debit Hasil Filter
                </div>

                <div class="mt-2 text-2xl font-bold text-emerald-600">
                    Rp {{ number_format($totalDebit, 0, ',', '.') }}
                </div>

                <div class="mt-1 text-xs text-slate-500">
                    Penambahan saldo
                </div>
            </div>

            <div class="rounded-xl border border-red-200 bg-white p-5 shadow-sm">
                <div class="text-sm font-medium text-slate-500">
                    Total Kredit Hasil Filter
                </div>

                <div class="mt-2 text-2xl font-bold text-red-600">
                    Rp {{ number_format($totalKredit, 0, ',', '.') }}
                </div>

                <div class="mt-1 text-xs text-slate-500">
                    Pengurangan saldo
                </div>
            </div>

        </div>


        {{-- SALDO PER REKENING --}}
        <div class="mb-6 rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="font-semibold text-slate-900">
                    Saldo Kas & Rekening Bank
                </h2>
            </div>

            <div class="grid grid-cols-1 gap-4 p-5 md:grid-cols-2 lg:grid-cols-3">

                @forelse ($saldoRekenings as $rekening)

                    <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">

                        <div class="flex items-start justify-between gap-3">

                            <div>
                                <div class="font-semibold text-slate-900">
                                    {{ $rekening->nama_rekening }}
                                </div>

                                <div class="mt-1 text-xs text-slate-500">
                                    {{ $rekening->kode_rekening }}
                                    •
                                    {{ $rekening->jenis }}
                                </div>

                                @if ($rekening->jenis === 'BANK')
                                    <div class="mt-1 text-xs text-slate-500">
                                        {{ $rekening->nama_bank ?: '-' }}

                                        @if ($rekening->nomor_rekening)
                                            • {{ $rekening->nomor_rekening }}
                                        @endif
                                    </div>
                                @endif
                            </div>

                            @if ($rekening->is_active)
                                <span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-semibold text-emerald-700">
                                    Aktif
                                </span>
                            @else
                                <span class="rounded-full bg-slate-200 px-2 py-1 text-xs font-semibold text-slate-600">
                                    Nonaktif
                                </span>
                            @endif

                        </div>

                        <div class="mt-4">
                            <div class="text-xs text-slate-500">
                                Saldo Saat Ini
                            </div>

                            <div class="mt-1 text-lg font-bold text-[#15235F]">
                                Rp {{ number_format(
                                    (float) $rekening->saldo_saat_ini,
                                    2,
                                    ',',
                                    '.'
                                ) }}
                            </div>

                            <div class="mt-3 border-t border-slate-200 pt-3">
                                <div class="flex items-center justify-between gap-3 text-xs text-slate-500">
                                    <span>Saldo Buku Besar (COA)</span>
                                    @if ($rekening->saldo_coa_saat_ini === null)
                                        <span class="font-semibold text-amber-700">COA belum terhubung</span>
                                    @else
                                        <span class="font-semibold {{ $rekening->saldo_sinkron_dengan_coa ? 'text-emerald-700' : 'text-red-700' }}">
                                            {{ $rekening->saldo_sinkron_dengan_coa ? 'Cocok' : 'SELISIH' }}
                                        </span>
                                    @endif
                                </div>

                                @if ($rekening->saldo_coa_saat_ini !== null)
                                    <div class="mt-1 text-sm font-semibold text-slate-800">
                                        Rp {{ number_format((float) $rekening->saldo_coa_saat_ini, 2, ',', '.') }}
                                    </div>
                                    <div class="mt-1 text-xs {{ $rekening->saldo_sinkron_dengan_coa ? 'text-emerald-700' : 'text-red-700' }}">
                                        Selisih subledger - COA: Rp {{ number_format((float) $rekening->selisih_saldo_coa, 2, ',', '.') }}
                                    </div>
                                @endif
                            </div>
                        </div>

                    </div>

                @empty

                    <div class="col-span-full py-6 text-center text-sm text-slate-500">
                        Belum ada Kas atau Rekening Bank.
                    </div>

                @endforelse

            </div>

        </div>


        {{-- FILTER --}}
        <div class="mb-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

            <form
                method="GET"
                action="{{ route('mutasi-kas-bank.index') }}"
            >

                <div class="grid grid-cols-1 gap-4 md:grid-cols-3 lg:grid-cols-5">

                    {{-- SEARCH --}}
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Pencarian
                        </label>

                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="No referensi, keterangan..."
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                        >
                    </div>


                    {{-- REKENING --}}
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Kas / Bank
                        </label>

                        <select
                            name="rekening_kas_bank_id"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                        >
                            <option value="">
                                Semua
                            </option>

                            @foreach ($rekeningKasBanks as $rekening)

                                <option
                                    value="{{ $rekening->id }}"
                                    @selected(
                                        (string) request('rekening_kas_bank_id')
                                        === (string) $rekening->id
                                    )
                                >
                                    {{ $rekening->nama_rekening }}
                                </option>

                            @endforeach
                        </select>
                    </div>


                    {{-- TIPE --}}
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Tipe Mutasi
                        </label>

                        <select
                            name="tipe"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                        >
                            <option value="">
                                Semua
                            </option>

                            <option
                                value="UANG_MASUK"
                                @selected(request('tipe') === 'UANG_MASUK')
                            >
                                Uang Masuk
                            </option>

                            <option
                                value="UANG_KELUAR"
                                @selected(request('tipe') === 'UANG_KELUAR')
                            >
                                Uang Keluar
                            </option>

                            <option
                                value="TRANSFER_MASUK"
                                @selected(request('tipe') === 'TRANSFER_MASUK')
                            >
                                Transfer Masuk
                            </option>

                            <option
                                value="TRANSFER_KELUAR"
                                @selected(request('tipe') === 'TRANSFER_KELUAR')
                            >
                                Transfer Keluar
                            </option>

                            <option
                                value="PENERIMAAN_PELANGGAN"
                                @selected(request('tipe') === 'PENERIMAAN_PELANGGAN')
                            >
                                Penerimaan Pelanggan
                            </option>

                            <option
                                value="PEMBAYARAN_SUPPLIER"
                                @selected(request('tipe') === 'PEMBAYARAN_SUPPLIER')
                            >
                                Pembayaran Supplier
                            </option>

                            <option
                                value="PENYESUAIAN"
                                @selected(request('tipe') === 'PENYESUAIAN')
                            >
                                Penyesuaian
                            </option>

                        </select>
                    </div>


                    {{-- TANGGAL MULAI --}}
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Tanggal Mulai
                        </label>

                        <input
                            type="date"
                            name="tanggal_mulai"
                            value="{{ request('tanggal_mulai') }}"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                        >
                    </div>


                    {{-- TANGGAL SELESAI --}}
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Tanggal Selesai
                        </label>

                        <input
                            type="date"
                            name="tanggal_selesai"
                            value="{{ request('tanggal_selesai') }}"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                        >
                    </div>

                </div>


                <div class="mt-4 flex gap-2">

                    <button
                        type="submit"
                        class="rounded-lg bg-[#15235F] px-5 py-2 text-sm font-semibold text-white hover:opacity-90"
                    >
                        Terapkan
                    </button>

                    <a
                        href="{{ route('mutasi-kas-bank.index') }}"
                        class="rounded-lg border border-slate-300 px-5 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100"
                    >
                        Reset
                    </a>

                </div>

            </form>

        </div>


        {{-- TABLE --}}
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="font-semibold text-slate-900">
                    Riwayat Mutasi
                </h2>
            </div>

            <div class="overflow-x-auto">

                <table class="min-w-full divide-y divide-slate-200">

                    <thead class="bg-slate-100">

                        <tr>

                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">
                                No
                            </th>

                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">
                                Tanggal
                            </th>

                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">
                                Kas / Bank
                            </th>

                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">
                                Tipe
                            </th>

                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">
                                Referensi
                            </th>

                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">
                                Keterangan
                            </th>

                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-600">
                                Debit
                            </th>

                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-600">
                                Kredit
                            </th>

                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-600">
                                Saldo
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-slate-100">

                        @forelse ($mutasis as $mutasi)

                            <tr class="hover:bg-slate-50">

                                <td class="px-4 py-3 text-sm text-slate-600">
                                    {{ $mutasis->firstItem() + $loop->index }}
                                </td>

                                <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">
                                    {{ $mutasi->tanggal?->format('d/m/Y H:i') }}
                                </td>

                                <td class="px-4 py-3">

                                    <div class="text-sm font-semibold text-slate-900">
                                        {{ $mutasi->rekeningKasBank?->nama_rekening ?? '-' }}
                                    </div>

                                    <div class="mt-1 text-xs text-slate-500">
                                        {{ $mutasi->rekeningKasBank?->jenis ?? '-' }}
                                    </div>

                                </td>

                                <td class="px-4 py-3">

                                    @php
                                        $label = match ($mutasi->tipe) {
                                            'UANG_MASUK' => 'Uang Masuk',
                                            'UANG_KELUAR' => 'Uang Keluar',
                                            'TRANSFER_MASUK' => 'Transfer Masuk',
                                            'TRANSFER_KELUAR' => 'Transfer Keluar',
                                            'PENERIMAAN_PELANGGAN' => 'Penerimaan Pelanggan',
                                            'PEMBAYARAN_SUPPLIER' => 'Pembayaran Supplier',
                                            'SALDO_AWAL' => 'Saldo Awal',
                                            'PENYESUAIAN' => 'Penyesuaian',
                                            default => $mutasi->tipe,
                                        };
                                    @endphp

                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-700">
                                        {{ $label }}
                                    </span>

                                </td>

                                <td class="px-4 py-3">

                                    <div class="text-sm font-medium text-slate-900">
                                        {{ $mutasi->nomor_referensi ?: '-' }}
                                    </div>

                                    @if ($mutasi->reference_type)
                                        <div class="mt-1 text-xs text-slate-500">
                                            {{ $mutasi->reference_type }}
                                        </div>
                                    @endif

                                </td>

                                <td class="max-w-xs px-4 py-3 text-sm text-slate-600">
                                    {{ $mutasi->keterangan ?: '-' }}
                                </td>

                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold">

                                    @if ((float) $mutasi->debit > 0)
                                        <span class="text-emerald-600">
                                            Rp {{ number_format(
                                                (float) $mutasi->debit,
                                                0,
                                                ',',
                                                '.'
                                            ) }}
                                        </span>
                                    @else
                                        <span class="text-slate-400">
                                            -
                                        </span>
                                    @endif

                                </td>

                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold">

                                    @if ((float) $mutasi->kredit > 0)
                                        <span class="text-red-600">
                                            Rp {{ number_format(
                                                (float) $mutasi->kredit,
                                                0,
                                                ',',
                                                '.'
                                            ) }}
                                        </span>
                                    @else
                                        <span class="text-slate-400">
                                            -
                                        </span>
                                    @endif

                                </td>

                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-bold text-[#15235F]">
                                    Rp {{ number_format(
                                        (float) $mutasi->saldo,
                                        0,
                                        ',',
                                        '.'
                                    ) }}
                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td
                                    colspan="9"
                                    class="px-4 py-12 text-center text-sm text-slate-500"
                                >
                                    Belum ada mutasi Kas/Bank.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>


            @if ($mutasis->hasPages())

                <div class="border-t border-slate-200 px-5 py-4">
                    {{ $mutasis->links() }}
                </div>

            @endif

        </div>

    </div>

</div>
@endsection