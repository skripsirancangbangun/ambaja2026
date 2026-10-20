@extends('layouts.app')

@section('title', 'Laporan Arus Kas')

@section('content')

<div class="mx-auto max-w-4xl space-y-6">

    {{-- HEADER --}}
    <div>
        <h2 class="text-xl font-semibold text-slate-800">
            Laporan Arus Kas (Cash Flow)
        </h2>
        <p class="mt-1 text-sm text-slate-500">
            Metode Langsung (Direct Method) berdasarkan penerimaan & pengeluaran kas riil operasional, investasi, dan pendanaan.
        </p>
    </div>

    {{-- FILTER --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('laporan.arus-kas') }}" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">

                {{-- TANGGAL MULAI --}}
                <div>
                    <label class="mb-2 block text-xs font-medium text-slate-600">Tanggal Mulai</label>
                    <input
                        type="date"
                        name="tanggal_mulai"
                        value="{{ $tanggalMulai }}"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >
                </div>

                {{-- TANGGAL SELESAI --}}
                <div>
                    <label class="mb-2 block text-xs font-medium text-slate-600">Tanggal Selesai</label>
                    <input
                        type="date"
                        name="tanggal_selesai"
                        value="{{ $tanggalSelesai }}"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >
                </div>

            </div>

            {{-- BUTTON FILTER --}}
            <div class="flex justify-end gap-2">
                <a
                    href="{{ route('laporan.arus-kas') }}"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
                >
                    Reset
                </a>
                <button
                    type="submit"
                    class="rounded-lg bg-[#15235F] px-5 py-2.5 text-sm font-medium text-white transition hover:bg-[#101b4d]"
                >
                    Terapkan Filter
                </button>
            </div>
        </form>
    </div>

    {{-- REPORT SHEET --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm p-6 space-y-6">

        {{-- HEADER SHEET + EXPORT BUTTONS --}}
        <div class="flex flex-col gap-4 border-b border-slate-200 pb-4 md:flex-row md:items-center md:justify-between">
            <div>
                <h3 class="text-lg font-bold text-[#15235F]">CV AMBAJA (ANDIKA MUSTIKA)</h3>
                <p class="text-sm font-semibold text-slate-700">LAPORAN ARUS KAS (METODE LANGSUNG)</p>
                <p class="text-xs text-slate-500">Periode {{ date('d/m/Y', strtotime($tanggalMulai)) }} s/d {{ date('d/m/Y', strtotime($tanggalSelesai)) }}</p>
            </div>

            {{-- EXPORT BUTTONS --}}
            <div class="flex flex-wrap items-center gap-2">
                <a
                    href="{{ route('laporan.arus-kas.pdf', request()->query()) }}"
                    target="_blank"
                    class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-medium text-red-600 transition hover:border-red-300 hover:bg-red-100"
                >
                    <x-icon name="file-text" class="h-4 w-4" />
                    Cetak PDF
                </a>

                <a
                    href="{{ route('laporan.arus-kas.excel', request()->query()) }}"
                    class="inline-flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-medium text-emerald-600 transition hover:border-emerald-300 hover:bg-emerald-100"
                >
                    <x-icon name="file-text" class="h-4 w-4" />
                    Export Excel
                </a>
            </div>
        </div>

        {{-- 1. OPERASIONAL (DIRECT) --}}
        <div class="space-y-3">
            <h4 class="text-sm font-bold uppercase tracking-wider text-[#15235F] border-b border-slate-100 pb-2">
                1. ARUS KAS DARI AKTIVITAS OPERASIONAL
            </h4>
            <div class="space-y-2 text-sm pl-4">
                <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 pt-1">
                    Penerimaan Kas Operasional:
                </div>

                <div class="flex justify-between text-slate-700 pl-4">
                    <span>Penerimaan Kas dari Pelanggan & Penjualan</span>
                    <span class="font-semibold text-emerald-600">
                        Rp {{ number_format($penerimaanPelanggan, 0, ',', '.') }}
                    </span>
                </div>

                <div class="flex justify-between text-slate-700 pl-4">
                    <span>Penerimaan Kas Operasional Lainnya</span>
                    <span class="font-semibold text-emerald-600">
                        Rp {{ number_format($penerimaanLainnya, 0, ',', '.') }}
                    </span>
                </div>

                <div class="text-xs font-semibold uppercase tracking-wider text-slate-400 pt-2">
                    Pengeluaran Kas Operasional:
                </div>

                <div class="flex justify-between text-slate-700 pl-4">
                    <span>Pembayaran Kas kepada Supplier / Pembelian</span>
                    <span class="font-semibold text-rose-600">
                        - Rp {{ number_format($pembayaranSupplier, 0, ',', '.') }}
                    </span>
                </div>

                <div class="flex justify-between text-slate-700 pl-4">
                    <span>Pembayaran Kas untuk Beban Operasional & Administrasi</span>
                    <span class="font-semibold text-rose-600">
                        - Rp {{ number_format($pembayaranBeban, 0, ',', '.') }}
                    </span>
                </div>
            </div>

            <div class="flex justify-between border-t border-slate-200 pt-2 font-bold text-sm text-slate-800 pl-4">
                <span>Arus Kas Bersih dari Aktivitas Operasional</span>
                <span class="{{ $arusKasOperasional >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                    Rp {{ number_format($arusKasOperasional, 0, ',', '.') }}
                </span>
            </div>
        </div>

        {{-- 2. INVESTASI --}}
        <div class="space-y-3">
            <h4 class="text-sm font-bold uppercase tracking-wider text-[#15235F] border-b border-slate-100 pb-2">
                2. ARUS KAS DARI AKTIVITAS INVESTASI
            </h4>
            <div class="space-y-2 text-sm pl-4">
                <div class="flex justify-between text-slate-700">
                    <span>Pembelian Aset Tetap / Peralatan</span>
                    <span class="font-semibold text-rose-600">
                        {{ $arusKasInvestasi < 0 ? '-' : '' }} Rp {{ number_format(abs($arusKasInvestasi), 0, ',', '.') }}
                    </span>
                </div>
            </div>
            <div class="flex justify-between border-t border-slate-200 pt-2 font-bold text-sm text-slate-800 pl-4">
                <span>Arus Kas Bersih dari Aktivitas Investasi</span>
                <span class="{{ $arusKasInvestasi >= 0 ? 'text-emerald-700' : 'text-rose-700' }}">
                    Rp {{ number_format($arusKasInvestasi, 0, ',', '.') }}
                </span>
            </div>
        </div>

        {{-- 3. PENDANAAN --}}
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

        {{-- SUMMARY RECONCILIATION --}}
        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 space-y-3">
            <div class="flex justify-between text-sm font-semibold text-slate-700">
                <span>Kenaikan / (Penurunan) Bersih Kas & Bank:</span>
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
