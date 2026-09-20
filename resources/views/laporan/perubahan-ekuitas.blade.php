@extends('layouts.app')

@section('title', 'Laporan Perubahan Ekuitas')

@section('content')

<div class="mx-auto max-w-4xl space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-semibold text-slate-800">Laporan Perubahan Ekuitas (Modal CV)</h2>
            <p class="mt-1 text-sm text-slate-500">
                Pertanggungjawaban perubahan struktur modal CV Ambaja (Modal Awal + Laba Bersih - Prive).
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
        <form method="GET" action="{{ route('laporan.perubahan-ekuitas') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
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

    {{-- REPORT SHEET --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm p-6 space-y-6">
        <div class="border-b border-slate-200 pb-4 text-center">
            <h2 class="text-xl font-bold text-[#15235F]">CV AMBAJA (ANDIKA MUSTIKA)</h2>
            <h3 class="text-base font-semibold text-slate-700">LAPORAN PERUBAHAN EKUITAS (MODAL OWNER)</h3>
            <p class="text-xs text-slate-500">Periode {{ date('d F Y', strtotime($tanggalMulai)) }} s/d {{ date('d F Y', strtotime($tanggalSelesai)) }}</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr class="text-xs uppercase tracking-wide text-slate-400">
                        <th class="px-6 py-3 font-medium">Komponen Ekuitas</th>
                        <th class="px-6 py-3 text-right font-medium">Nominal (Rp)</th>
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
                            @if($labaBersihPeriode >= 0)
                                (+) Laba Bersih Periode Berjalan
                            @else
                                (-) Rugi Bersih Periode Berjalan
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right font-semibold {{ $labaBersihPeriode >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ $labaBersihPeriode >= 0 ? '+' : '-' }} Rp {{ number_format(abs($labaBersihPeriode), 0, ',', '.') }}
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
                            @if($perubahanNet >= 0)
                                Kenaikan Bersih Ekuitas Periode Ini
                            @else
                                Penurunan Bersih Ekuitas Periode Ini
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right {{ $perubahanNet >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                            {{ $perubahanNet >= 0 ? '+' : '-' }} Rp {{ number_format(abs($perubahanNet), 0, ',', '.') }}
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
