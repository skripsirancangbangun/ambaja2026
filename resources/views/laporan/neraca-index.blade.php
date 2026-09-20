@extends('layouts.app')

@section('title', 'Laporan Neraca (Balance Sheet)')

@section('content')

<div class="mx-auto max-w-5xl space-y-6">

    {{-- HEADER --}}
    <div>
        <h2 class="text-xl font-semibold text-slate-800">
            Laporan Neraca (Posisi Keuangan)
        </h2>
        <p class="mt-1 text-sm text-slate-500">
            Posisi Aset, Kewajiban, dan Ekuitas CV Ambaja kumulatif per tanggal tertentu.
        </p>
    </div>

    {{-- FILTER --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('laporan.neraca') }}" class="space-y-4">
            <div class="w-full max-w-xs">
                <label class="mb-2 block text-xs font-medium text-slate-600">Per Tanggal (End Date)</label>
                <input
                    type="date"
                    name="tanggal"
                    value="{{ $tanggal }}"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                >
            </div>

            <div class="flex justify-end gap-2">
                <a
                    href="{{ route('laporan.neraca') }}"
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
                <p class="text-sm font-semibold text-slate-700">LAPORAN NERACA (POSISI KEUANGAN)</p>
                <p class="text-xs text-slate-500">Posisi Per Tanggal {{ date('d/m/Y', strtotime($tanggal)) }}</p>
            </div>

            {{-- EXPORT BUTTONS --}}
            <div class="flex flex-wrap items-center gap-2">
                <a
                    href="{{ route('laporan.neraca.pdf', request()->query()) }}"
                    target="_blank"
                    class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-medium text-red-600 transition hover:border-red-300 hover:bg-red-100"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5A3.375 3.375 0 0 0 10.125 2.25H8.25m0 12.75h7.5m-7.5 3h7.5M10.5 2.25H5.625A1.875 1.875 0 0 0 3.75 4.125v15.75c0 1.036.84 1.875 1.875 1.875h12.75a1.875 1.875 0 0 0 1.875-1.875V12.375a10.125 10.125 0 0 0-9.75-10.125Z" />
                    </svg>
                    Cetak PDF
                </a>

                <a
                    href="{{ route('laporan.neraca.excel', request()->query()) }}"
                    class="inline-flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-medium text-emerald-600 transition hover:border-emerald-300 hover:bg-emerald-100"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5A3.375 3.375 0 0 0 10.125 2.25H8.25m.75 12 3 3m0 0 3-3m-3 3v-6m-1.5-9H5.625A1.875 1.875 0 0 0 3.75 4.125v15.75c0 1.036.84 1.875 1.875 1.875h12.75a1.875 1.875 0 0 0 1.875-1.875V12.375a10.125 10.125 0 0 0-9.75-10.125Z" />
                    </svg>
                    Export Excel
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            {{-- ASET / AKTIVA --}}
            <div class="space-y-6">
                <div class="border-b-2 border-[#15235F] pb-2">
                    <h4 class="text-sm font-bold uppercase tracking-wider text-[#15235F]">ASET / AKTIVA</h4>
                </div>

                {{-- ASET LANCAR --}}
                <div class="space-y-3">
                    <h5 class="text-xs font-bold uppercase tracking-wider text-teal-800 border-b border-slate-100 pb-1">ASET LANCAR (CURRENT ASSETS)</h5>
                    <div class="divide-y divide-slate-100">
                        @foreach($asetLancarData as $row)
                            <div class="flex justify-between py-1.5 text-sm">
                                <span class="text-slate-700 font-medium">{{ $row['akun']->kode_akun }} - {{ $row['akun']->nama_akun }}</span>
                                <span class="font-semibold text-slate-800">Rp {{ number_format($row['nominal'], 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="flex justify-between border-t border-slate-200 pt-2 font-bold text-xs text-teal-800 bg-teal-50/50 p-2 rounded-lg">
                        <span>TOTAL ASET LANCAR</span>
                        <span>Rp {{ number_format($totalAsetLancar, 0, ',', '.') }}</span>
                    </div>
                </div>

                {{-- ASET TETAP --}}
                <div class="space-y-3">
                    <h5 class="text-xs font-bold uppercase tracking-wider text-indigo-800 border-b border-slate-100 pb-1">ASET TETAP (FIXED ASSETS)</h5>
                    <div class="divide-y divide-slate-100">
                        @foreach($asetTetapData as $row)
                            <div class="flex justify-between py-1.5 text-sm">
                                <span class="text-slate-700 font-medium">{{ $row['akun']->kode_akun }} - {{ $row['akun']->nama_akun }}</span>
                                <span class="font-semibold text-slate-800">Rp {{ number_format($row['nominal'], 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="flex justify-between border-t border-slate-200 pt-2 font-bold text-xs text-indigo-800 bg-indigo-50/50 p-2 rounded-lg">
                        <span>TOTAL ASET TETAP</span>
                        <span>Rp {{ number_format($totalAsetTetap, 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="flex justify-between border-t-2 border-slate-200 pt-3 font-bold text-base text-[#15235F]">
                    <span>TOTAL ASET</span>
                    <span>Rp {{ number_format($totalAset, 0, ',', '.') }}</span>
                </div>
            </div>

            {{-- PASIVA (KEWAJIBAN & EKUITAS) --}}
            <div class="space-y-6">
                {{-- KEWAJIBAN --}}
                <div class="space-y-4">
                    <div class="border-b-2 border-rose-600 pb-2">
                        <h4 class="text-sm font-bold uppercase tracking-wider text-rose-700">KEWAJIBAN / UTANG</h4>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @foreach($kewajibanData as $row)
                            <div class="flex justify-between py-2 text-sm">
                                <span class="text-slate-700 font-medium">{{ $row['akun']->kode_akun }} - {{ $row['akun']->nama_akun }}</span>
                                <span class="font-semibold text-slate-800">Rp {{ number_format($row['nominal'], 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="flex justify-between border-t border-slate-200 pt-2 font-bold text-sm text-rose-700">
                        <span>TOTAL KEWAJIBAN</span>
                        <span>Rp {{ number_format($totalKewajiban, 0, ',', '.') }}</span>
                    </div>
                </div>

                {{-- EKUITAS --}}
                <div class="space-y-4">
                    <div class="border-b-2 border-purple-600 pb-2">
                        <h4 class="text-sm font-bold uppercase tracking-wider text-purple-700">EKUITAS / MODAL</h4>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @foreach($ekuitasData as $row)
                            <div class="flex justify-between py-2 text-sm">
                                <span class="text-slate-700 font-medium">{{ $row['akun']->kode_akun }} - {{ $row['akun']->nama_akun }}</span>
                                <span class="font-semibold text-slate-800">Rp {{ number_format($row['nominal'], 0, ',', '.') }}</span>
                            </div>
                        @endforeach
                    </div>
                    <div class="flex justify-between border-t border-slate-200 pt-2 font-bold text-sm text-purple-700">
                        <span>TOTAL EKUITAS</span>
                        <span>Rp {{ number_format($totalEkuitas, 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="flex justify-between border-t-2 border-slate-200 pt-3 font-bold text-base text-[#15235F]">
                    <span>TOTAL KEWAJIBAN & EKUITAS</span>
                    <span>Rp {{ number_format($totalKewajiban + $totalEkuitas, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>

        {{-- BALANCE STATUS --}}
        @php $isBalanced = abs($totalAset - ($totalKewajiban + $totalEkuitas)) < 0.01; @endphp
        <div class="mt-6 rounded-2xl border p-4 text-center font-bold text-sm {{ $isBalanced ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-rose-200 bg-rose-50 text-rose-700' }}">
            {{ $isBalanced ? '✓ Neraca Seimbang' : '⚠️ Neraca Tidak Seimbang' }}
        </div>
    </div>

</div>

@endsection
