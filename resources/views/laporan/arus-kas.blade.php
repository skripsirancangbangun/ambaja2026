@extends('layouts.app')

@section('title', 'Laporan Arus Kas')

@section('content')

<div class="mx-auto max-w-4xl space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-semibold text-slate-800">Laporan Arus Kas (Cash Flow Statement)</h2>
            <p class="mt-1 text-sm text-slate-500">
                Laporan penerimaan dan pengeluaran kas berdasarkan Aktivitas Operasional, Investasi, dan Pendanaan.
            </p>
        </div>

        <button
            type="button"
            onclick="window.print()"
            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
        >
            <x-icon name="printer" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" /> Cetak Laporan
        </button>
    </div>

    {{-- FILTER --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('laporan.arus-kas') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Dari Tanggal</label>
                <input
                    type="date"
                    name="tanggal_mulai"
                    value="{{ $tanggalMulai }}"
                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm text-slate-700 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                >
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Sampai Tanggal</label>
                <input
                    type="date"
                    name="tanggal_selesai"
                    value="{{ $tanggalSelesai }}"
                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm text-slate-700 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                >
            </div>

            <div class="flex items-end">
                <button
                    type="submit"
                    class="w-full rounded-xl bg-[#15235F] py-2 text-sm font-medium text-white transition hover:bg-[#101b4d]"
                >
                    Tampilkan Arus Kas
                </button>
            </div>
        </form>
    </div>

    {{-- REPORT SHEET --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm p-6 space-y-6">
        <div class="border-b border-slate-200 pb-4 text-center">
            <h2 class="text-xl font-bold text-[#15235F]">CV AMBAJA (ANDIKA MUSTIKA)</h2>
            <h3 class="text-base font-semibold text-slate-700">LAPORAN ARUS KAS (METODE LANGSUNG)</h3>
            <p class="text-xs text-slate-500">Periode {{ date('d F Y', strtotime($tanggalMulai)) }} s/d {{ date('d F Y', strtotime($tanggalSelesai)) }}</p>
        </div>

        {{-- 1. AKTIVITAS OPERASIONAL --}}
        <div class="space-y-3">
            <h4 class="text-sm font-bold uppercase tracking-wider text-[#15235F] border-b border-slate-100 pb-2">
                1. ARUS KAS DARI AKTIVITAS OPERASIONAL
            </h4>
            <div class="space-y-2 text-sm pl-4">
                <div class="flex justify-between text-slate-700">
                    <span>Penerimaan Kas dari Customer / Penjualan</span>
                    <span class="font-semibold text-slate-800">Rp {{ number_format($penerimaanPenjualan, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-slate-700">
                    <span>Penerimaan Kas Operasional Lainnya</span>
                    <span class="font-semibold text-slate-800">Rp {{ number_format($penerimaanLain, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-slate-700">
                    <span>Pembayaran Kas kepada Supplier / Pembelian</span>
                    <span class="font-semibold text-rose-600">- Rp {{ number_format($pembayaranPembelian, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-slate-700">
                    <span>Pembayaran Beban Operasional & Gaji</span>
                    <span class="font-semibold text-rose-600">- Rp {{ number_format($pembayaranBeban, 0, ',', '.') }}</span>
                </div>
            </div>
            <div class="flex justify-between border-t border-slate-200 pt-2 font-bold text-sm text-slate-800 pl-4">
                <span>Arus Kas Bersih dari Aktivitas Operasional</span>
                <span class="{{ $arusKasOperasional >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                    Rp {{ number_format($arusKasOperasional, 0, ',', '.') }}
                </span>
            </div>
        </div>

        {{-- 2. AKTIVITAS INVESTASI --}}
        <div class="space-y-3">
            <h4 class="text-sm font-bold uppercase tracking-wider text-[#15235F] border-b border-slate-100 pb-2">
                2. ARUS KAS DARI AKTIVITAS INVESTASI
            </h4>
            <div class="space-y-2 text-sm pl-4">
                <div class="flex justify-between text-slate-700">
                    <span>Pembelian Aset Tetap / Peralatan & Mesin</span>
                    <span class="font-semibold text-rose-600">- Rp {{ number_format($pembelianAset, 0, ',', '.') }}</span>
                </div>
            </div>
            <div class="flex justify-between border-t border-slate-200 pt-2 font-bold text-sm text-slate-800 pl-4">
                <span>Arus Kas Bersih dari Aktivitas Investasi</span>
                <span class="{{ $arusKasInvestasi >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                    Rp {{ number_format($arusKasInvestasi, 0, ',', '.') }}
                </span>
            </div>
        </div>

        {{-- 3. AKTIVITAS PENDANAAN --}}
        <div class="space-y-3">
            <h4 class="text-sm font-bold uppercase tracking-wider text-[#15235F] border-b border-slate-100 pb-2">
                3. ARUS KAS DARI AKTIVITAS PENDANAAN
            </h4>
            <div class="space-y-2 text-sm pl-4">
                <div class="flex justify-between text-slate-700">
                    <span>Setoran Modal Owner</span>
                    <span class="font-semibold text-slate-800">Rp {{ number_format($setoranModal, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-slate-700">
                    <span>Pengambilan Prive Owner</span>
                    <span class="font-semibold text-rose-600">- Rp {{ number_format($penarikanPrive, 0, ',', '.') }}</span>
                </div>
            </div>
            <div class="flex justify-between border-t border-slate-200 pt-2 font-bold text-sm text-slate-800 pl-4">
                <span>Arus Kas Bersih dari Aktivitas Pendanaan</span>
                <span class="{{ $arusKasPendanaan >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                    Rp {{ number_format($arusKasPendanaan, 0, ',', '.') }}
                </span>
            </div>
        </div>

        {{-- SUMMARY SUMMARY --}}
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 space-y-3">
            <div class="flex justify-between text-sm font-semibold text-slate-700">
                <span>Kenaikan / (Penurunan) Bersih Kas Periode Ini:</span>
                <span class="{{ $kenaikanBersihKas >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                    Rp {{ number_format($kenaikanBersihKas, 0, ',', '.') }}
                </span>
            </div>
            <div class="flex justify-between text-sm text-slate-600">
                <span>Saldo Kas & Bank Awal Periode:</span>
                <span>Rp {{ number_format($saldoKasAwal, 0, ',', '.') }}</span>
            </div>
            <div class="flex justify-between border-t border-slate-300 pt-2 text-base font-bold text-[#15235F]">
                <span>Saldo Kas & Bank Akhir Periode:</span>
                <span>Rp {{ number_format($saldoKasAkhir, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>

</div>

@endsection
