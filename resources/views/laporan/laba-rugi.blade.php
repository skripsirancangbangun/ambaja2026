@extends('layouts.app')

@section('title', 'Laporan Laba Rugi')

@section('content')

<div class="mx-auto max-w-4xl space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-semibold text-slate-800">Laporan Laba Rugi (Profit & Loss)</h2>
            <p class="mt-1 text-sm text-slate-500">
                Perhitungan total Pendapatan dikurangi total Beban operasional mutasi periode terpilih.
            </p>
        </div>

        <button
            type="button"
            onclick="window.print()"
            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
        >
            🖨️ Cetak Laporan
        </button>
    </div>

    {{-- FILTER --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('laporan.laba-rugi') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
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
                    Tampilkan Laporan
                </button>
            </div>
        </form>
    </div>

    {{-- REPORT CARD --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm p-6 space-y-6">
        <div class="border-b border-slate-200 pb-4 text-center">
            <h2 class="text-xl font-bold text-[#15235F]">CV AMBAJA (ANDIKA MUSTIKA)</h2>
            <h3 class="text-base font-semibold text-slate-700">LAPORAN LABA RUGI</h3>
            <p class="text-xs text-slate-500">Periode {{ date('d F Y', strtotime($tanggalMulai)) }} s/d {{ date('d F Y', strtotime($tanggalSelesai)) }}</p>
        </div>

        {{-- 1. PENDAPATAN --}}
        <div>
            <h4 class="text-sm font-bold uppercase tracking-wider text-[#15235F] border-b border-slate-100 pb-2">
                PENDAPATAN (REVENUE)
            </h4>
            <div class="divide-y divide-slate-100 mt-2">
                @foreach($pendapatanData as $row)
                    <div class="flex justify-between py-2 text-sm">
                        <span class="text-slate-700 font-medium">{{ $row['akun']->kode_akun }} - {{ $row['akun']->nama_akun }}</span>
                        <span class="font-semibold text-slate-800">Rp {{ number_format($row['nominal'], 0, ',', '.') }}</span>
                    </div>
                @endforeach
            </div>
            <div class="flex justify-between border-t border-slate-200 pt-2 font-bold text-sm text-emerald-700">
                <span>TOTAL PENDAPATAN</span>
                <span>Rp {{ number_format($totalPendapatan, 0, ',', '.') }}</span>
            </div>
        </div>

        {{-- 2. BEBAN --}}
        <div>
            <h4 class="text-sm font-bold uppercase tracking-wider text-[#15235F] border-b border-slate-100 pb-2">
                BEBAN (EXPENSES)
            </h4>
            <div class="divide-y divide-slate-100 mt-2">
                @foreach($bebanData as $row)
                    <div class="flex justify-between py-2 text-sm">
                        <span class="text-slate-700 font-medium">{{ $row['akun']->kode_akun }} - {{ $row['akun']->nama_akun }}</span>
                        <span class="font-semibold text-slate-800">Rp {{ number_format($row['nominal'], 0, ',', '.') }}</span>
                    </div>
                @endforeach
            </div>
            <div class="flex justify-between border-t border-slate-200 pt-2 font-bold text-sm text-rose-700">
                <span>TOTAL BEBAN</span>
                <span>Rp {{ number_format($totalBeban, 0, ',', '.') }}</span>
            </div>
        </div>

        {{-- 3. RESULT --}}
        <div class="rounded-2xl border p-5 {{ $labaRugiBersih >= 0 ? 'border-emerald-200 bg-emerald-50/50' : 'border-rose-200 bg-rose-50/50' }}">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="text-base font-bold uppercase {{ $labaRugiBersih >= 0 ? 'text-emerald-800' : 'text-rose-800' }}">
                        {{ $labaRugiBersih >= 0 ? 'LABA BERSIH (NET PROFIT)' : 'RUGI BERSIH (NET LOSS)' }}
                    </h3>
                    <p class="text-xs text-slate-500">Pendapatan Mutasi - Total Beban Mutasi</p>
                </div>
                <div class="text-2xl font-bold {{ $labaRugiBersih >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                    Rp {{ number_format($labaRugiBersih, 0, ',', '.') }}
                </div>
            </div>
        </div>
    </div>

</div>

@endsection
