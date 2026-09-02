@extends('layouts.app')

@section('title', 'Laporan Perubahan Ekuitas')

@section('content')

<div class="mx-auto max-w-4xl space-y-6">

    {{-- HEADER --}}
    <div>
        <h2 class="text-xl font-semibold text-slate-800">
            Laporan Perubahan Ekuitas (Modal CV)
        </h2>
        <p class="mt-1 text-sm text-slate-500">
            Pertanggungjawaban perubahan struktur ekuitas CV Ambaja (Modal Awal + Laba Bersih - Prive Owner).
        </p>
    </div>

    {{-- FILTER --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('laporan.perubahan-ekuitas') }}" class="space-y-4">
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
                    href="{{ route('laporan.perubahan-ekuitas') }}"
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
                <p class="text-sm font-semibold text-slate-700">LAPORAN PERUBAHAN EKUITAS (MODAL OWNER)</p>
                <p class="text-xs text-slate-500">Periode {{ date('d/m/Y', strtotime($tanggalMulai)) }} s/d {{ date('d/m/Y', strtotime($tanggalSelesai)) }}</p>
            </div>

            {{-- EXPORT BUTTONS --}}
            <div class="flex flex-wrap items-center gap-2">
                <a
                    href="{{ route('laporan.perubahan-ekuitas.pdf', request()->query()) }}"
                    target="_blank"
                    class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-medium text-red-600 transition hover:border-red-300 hover:bg-red-100"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5A3.375 3.375 0 0 0 10.125 2.25H8.25m0 12.75h7.5m-7.5 3h7.5M10.5 2.25H5.625A1.875 1.875 0 0 0 3.75 4.125v15.75c0 1.036.84 1.875 1.875 1.875h12.75a1.875 1.875 0 0 0 1.875-1.875V12.375a10.125 10.125 0 0 0-9.75-10.125Z" />
                    </svg>
                    Cetak PDF
                </a>

                <a
                    href="{{ route('laporan.perubahan-ekuitas.excel', request()->query()) }}"
                    class="inline-flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-medium text-emerald-600 transition hover:border-emerald-300 hover:bg-emerald-100"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5A3.375 3.375 0 0 0 10.125 2.25H8.25m.75 12 3 3m0 0 3-3m-3 3v-6m-1.5-9H5.625A1.875 1.875 0 0 0 3.75 4.125v15.75c0 1.036.84 1.875 1.875 1.875h12.75a1.875 1.875 0 0 0 1.875-1.875V12.375a10.125 10.125 0 0 0-9.75-10.125Z" />
                    </svg>
                    Export Excel
                </a>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm divide-y divide-slate-200">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-6 py-3">Komponen Ekuitas</th>
                        <th class="px-6 py-3 text-right">Nominal (Rp)</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    <tr>
                        <td class="px-6 py-4 font-semibold text-slate-800">
                            Modal Awal CV Ambaja (Per {{ date('d/m/Y', strtotime($tanggalMulai)) }})
                        </td>
                        <td class="px-6 py-4 text-right font-bold text-[#15235F]">
                            Rp {{ number_format($modalAwal, 0, ',', '.') }}
                        </td>
                    </tr>

                    <tr>
                        <td class="px-6 py-4 text-slate-700 font-medium pl-8">
                            (+) Laba / (Rugi) Bersih Periode Berjalan
                        </td>
                        <td class="px-6 py-4 text-right font-semibold {{ $labaBersihPeriode >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ $labaBersihPeriode >= 0 ? '+' : '' }} Rp {{ number_format($labaBersihPeriode, 0, ',', '.') }}
                        </td>
                    </tr>

                    <tr>
                        <td class="px-6 py-4 text-slate-700 font-medium pl-8">
                            (-) Pengambilan Prive Owner Periode Berjalan
                        </td>
                        <td class="px-6 py-4 text-right font-semibold text-rose-600">
                            - Rp {{ number_format($privePeriode, 0, ',', '.') }}
                        </td>
                    </tr>

                    <tr class="bg-slate-50 font-bold">
                        <td class="px-6 py-4 text-slate-700">
                            Kenaikan / (Penurunan) Bersih Ekuitas Periode Ini
                        </td>
                        <td class="px-6 py-4 text-right {{ $perubahanNet >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ $perubahanNet >= 0 ? '+' : '' }} Rp {{ number_format($perubahanNet, 0, ',', '.') }}
                        </td>
                    </tr>
                </tbody>

                <tfoot>
                    <tr class="border-t-2 border-slate-200 bg-slate-100 font-bold text-base">
                        <td class="px-6 py-4 text-[#15235F]">
                            MODAL AKHIR CV AMBAJA (Per {{ date('d/m/Y', strtotime($tanggalSelesai)) }})
                        </td>
                        <td class="px-6 py-4 text-right text-[#15235F]">
                            Rp {{ number_format($modalAkhir, 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>

@endsection
