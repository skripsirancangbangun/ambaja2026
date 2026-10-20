@extends('layouts.app')

@section('title', 'Input Penerimaan Piutang')

@section('content')

<div class="mx-auto max-w-4xl space-y-6">

    {{-- ========================================================= --}}
    {{-- HEADER --}}
    {{-- ========================================================= --}}

    <div class="flex items-center justify-between">

        <div>

            <h2 class="text-xl font-semibold text-slate-800">
                Form Penerimaan Piutang / Pelunasan
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Catat pembayaran piutang customer secara parsial (cicilan) atau lunas.
            </p>

        </div>

        <a
            href="{{ route('piutang.penerimaan.index') }}"
            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
        >
            Kembali
        </a>

    </div>

    {{-- ========================================================= --}}
    {{-- VALIDATION ERROR --}}
    {{-- ========================================================= --}}

    @if($errors->any())

        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">

            <p class="font-semibold">
                Terjadi kesalahan:
            </p>

            <ul class="mt-2 list-inside list-disc space-y-1">

                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach

            </ul>

        </div>

    @endif

    {{-- ========================================================= --}}
    {{-- SESSION ERROR --}}
    {{-- ========================================================= --}}

    @if(session('error'))

        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm font-medium text-rose-800">
            <x-icon name="alert-triangle" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" /> {{ session('error') }}
        </div>

    @endif

    {{-- ========================================================= --}}
    {{-- STEP 1 CUSTOMER --}}
    {{-- ========================================================= --}}

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

        <form
            method="GET"
            action="{{ route('piutang.penerimaan.create') }}"
            class="grid grid-cols-1 items-end gap-4 sm:grid-cols-3"
        >

            <div class="sm:col-span-2">

                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                    Pilih Customer
                    <span class="text-rose-500">*</span>
                </label>

                <select
                    name="customer_id"
                    onchange="this.form.submit()"
                    class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 outline-none focus:border-[#32AAA6]"
                >
                    <option value="">
                        -- Pilih Customer --
                    </option>

                    @foreach($customers as $c)

                        <option
                            value="{{ $c->id }}"
                            {{ $selectedCustomerId == $c->id ? 'selected' : '' }}
                        >
                            {{ $c->kode_customer }}
                            -
                            {{ $c->nama_customer }}
                        </option>

                    @endforeach

                </select>

            </div>

            <div>

                <button
                    type="submit"
                    class="w-full rounded-xl bg-slate-800 py-2.5 text-sm font-medium text-white hover:bg-slate-900"
                >
                    Cari Faktur Piutang
                </button>

            </div>

        </form>

    </div>

    {{-- ========================================================= --}}
    {{-- STEP 2 PEMBAYARAN --}}
    {{-- ========================================================= --}}

    @if($selectedCustomerId)

        <form
            method="POST"
            action="{{ route('piutang.penerimaan.store') }}"
            class="space-y-6"
        >

            @csrf

            <input
                type="hidden"
                name="customer_id"
                value="{{ $selectedCustomerId }}"
            >

            <div class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

                {{-- ================================================= --}}
                {{-- HEADER TRANSAKSI --}}
                {{-- ================================================= --}}

                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">

                    {{-- ============================================= --}}
                    {{-- NOMOR --}}
                    {{-- ============================================= --}}

                    <div>

                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                            No. Kwitansi
                            <span class="text-rose-500">*</span>
                        </label>

                        <input
                            type="text"
                            name="nomor_penerimaan"
                            value="{{ old('nomor_penerimaan', $nomorPenerimaan) }}"
                            required
                            class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 font-mono text-sm text-slate-800 outline-none focus:border-[#32AAA6]"
                        >

                    </div>

                    {{-- ============================================= --}}
                    {{-- TANGGAL --}}
                    {{-- ============================================= --}}

                    <div>

                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                            Tanggal Pembayaran
                            <span class="text-rose-500">*</span>
                        </label>

                        <input
                            type="date"
                            name="tanggal"
                            value="{{ old('tanggal', date('Y-m-d')) }}"
                            required
                            class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 outline-none focus:border-[#32AAA6]"
                        >

                    </div>

                    {{-- ============================================= --}}
                    {{-- REKENING --}}
                    {{-- ============================================= --}}

                    <div>

                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                            Rekening Kas / Bank Penerima
                            <span class="text-rose-500">*</span>
                        </label>

                        <select
                            name="rekening_kas_bank_id"
                            required
                            class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 outline-none focus:border-[#32AAA6]"
                        >

                            <option value="">
                                -- Pilih Kas / Bank --
                            </option>

                            @foreach($rekeningKasBanks as $rk)

                                <option
                                    value="{{ $rk->id }}"
                                    {{ old('rekening_kas_bank_id') == $rk->id ? 'selected' : '' }}
                                >
                                    {{ $rk->nama_rekening }}

                                    @if($rk->nomor_rekening)
                                        ({{ $rk->nomor_rekening }})
                                    @endif
                                </option>

                            @endforeach

                        </select>

                    </div>

                </div>

                {{-- ================================================= --}}
                {{-- DAFTAR PIUTANG --}}
                {{-- ================================================= --}}

                <div>

                    <h3 class="border-b border-slate-100 pb-2 text-sm font-bold uppercase tracking-wider text-[#15235F]">
                        Daftar Faktur Piutang Belum Lunas
                    </h3>

                    <div class="mt-4 overflow-x-auto">

                        <table class="w-full text-left text-sm">

                            <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-400">

                                <tr>

                                    <th class="px-4 py-3">
                                        No. Invoice
                                    </th>

                                    <th class="px-4 py-3">
                                        Tanggal Invoice
                                    </th>

                                    <th class="px-4 py-3">
                                        Jatuh Tempo
                                    </th>

                                    <th class="px-4 py-3 text-right">
                                        Total Invoice
                                    </th>

                                    <th class="px-4 py-3 text-right">
                                        Sisa Piutang
                                    </th>

                                    <th class="w-48 px-4 py-3 text-right">
                                        Nominal Bayar (Rp)
                                    </th>

                                </tr>

                            </thead>

                            <tbody class="divide-y divide-slate-100">

                                @forelse($unpaidInvoices as $idx => $inv)

                                    <tr>

                                        {{-- ================================= --}}
                                        {{-- INVOICE --}}
                                        {{-- ================================= --}}

                                        <td class="px-4 py-3 font-semibold text-[#15235F]">

                                            {{ $inv->nomor_invoice }}

                                            <input
                                                type="hidden"
                                                name="alokasi[{{ $idx }}][invoice_id]"
                                                value="{{ $inv->id }}"
                                            >

                                        </td>

                                        {{-- ================================= --}}
                                        {{-- TANGGAL --}}
                                        {{-- ================================= --}}

                                        <td class="px-4 py-3 text-slate-600">

                                            {{ $inv->tanggal_invoice
                                                ? $inv->tanggal_invoice->format('d/m/Y')
                                                : '-'
                                            }}

                                        </td>

                                        {{-- ================================= --}}
                                        {{-- JATUH TEMPO --}}
                                        {{-- ================================= --}}

                                        <td class="px-4 py-3 text-slate-600">

                                            {{ $inv->tanggal_jatuh_tempo
                                                ? $inv->tanggal_jatuh_tempo->format('d/m/Y')
                                                : '-'
                                            }}

                                        </td>

                                        {{-- ================================= --}}
                                        {{-- TOTAL --}}
                                        {{-- ================================= --}}

                                        <td class="px-4 py-3 text-right font-medium text-slate-800">

                                            Rp {{ number_format(
                                                $inv->total,
                                                0,
                                                ',',
                                                '.'
                                            ) }}

                                        </td>

                                        {{-- ================================= --}}
                                        {{-- SISA --}}
                                        {{-- ================================= --}}

                                        <td class="px-4 py-3 text-right font-bold text-rose-600">

                                            Rp {{ number_format(
                                                $inv->sisa_piutang,
                                                0,
                                                ',',
                                                '.'
                                            ) }}

                                        </td>

                                        {{-- ================================= --}}
                                        {{-- BAYAR --}}
                                        {{-- ================================= --}}

                                        <td class="px-4 py-3 text-right">

                                            <input
                                                type="number"
                                                name="alokasi[{{ $idx }}][bayar]"
                                                value="{{ old(
                                                    "alokasi.$idx.bayar",
                                                    0
                                                ) }}"
                                                max="{{ $inv->sisa_piutang }}"
                                                min="0"
                                                step="1"
                                                class="w-full rounded-xl border border-slate-300 px-3 py-1.5 text-right text-sm font-semibold text-emerald-700 outline-none focus:border-emerald-500"
                                            >

                                        </td>

                                    </tr>

                                @empty

                                    <tr>

                                        <td
                                            colspan="6"
                                            class="px-4 py-6 text-center text-slate-400"
                                        >
                                            Customer ini tidak memiliki tagihan piutang terbuka.
                                        </td>

                                    </tr>

                                @endforelse

                            </tbody>

                        </table>

                    </div>

                </div>

                {{-- ================================================= --}}
                {{-- CATATAN --}}
                {{-- ================================================= --}}

                <div>

                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                        Catatan Pembayaran
                    </label>

                    <textarea
                        name="catatan"
                        rows="2"
                        placeholder="Contoh: Pembayaran cicilan tahap 1 via Transfer Bank BCA..."
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-800 outline-none focus:border-[#32AAA6]"
                    >{{ old('catatan') }}</textarea>

                </div>

                {{-- ================================================= --}}
                {{-- SUBMIT --}}
                {{-- ================================================= --}}

                @if($unpaidInvoices->count() > 0)

                    <div class="flex justify-end border-t border-slate-100 pt-4">

                        <button
                            type="submit"
                            class="rounded-xl bg-[#15235F] px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-[#101b4d]"
                        >
                            <x-icon name="save" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" /> Simpan Penerimaan Piutang
                        </button>

                    </div>

                @endif

            </div>

        </form>

    @endif

</div>

@endsection