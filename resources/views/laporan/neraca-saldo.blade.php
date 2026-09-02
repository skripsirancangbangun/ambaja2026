@extends('layouts.app')

@section('title', 'Neraca Saldo (Trial Balance)')

@section('content')

<div class="space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-semibold text-slate-800">Neraca Saldo (Trial Balance)</h2>
            <p class="mt-1 text-sm text-slate-500">
                Rekapitulasi saldo akhir debit dan kredit seluruh akun COA.
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
        <form method="GET" action="{{ route('laporan.neraca-saldo') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
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
                    Tampilkan Neraca Saldo
                </button>
            </div>
        </form>
    </div>

    {{-- TABLE --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-4 flex justify-between items-center">
            <div>
                <h3 class="text-sm font-semibold text-slate-700">CV AMBAJA - Neraca Saldo</h3>
                <p class="text-xs text-slate-400">Periode: {{ date('d/m/Y', strtotime($tanggalMulai)) }} s/d {{ date('d/m/Y', strtotime($tanggalSelesai)) }}</p>
            </div>

            @php
                $isBalanced = abs($grandTotalDebit - $grandTotalKredit) < 0.01;
            @endphp
            <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $isBalanced ? 'bg-emerald-50 text-emerald-700' : 'bg-rose-50 text-rose-700' }}">
                {{ $isBalanced ? 'SEIMBANG (BALANCED)' : 'UNBALANCED' }}
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-xs uppercase tracking-wide text-slate-400">
                        <th class="px-6 py-3 font-medium">Kode Akun</th>
                        <th class="px-6 py-3 font-medium">Nama Akun</th>
                        <th class="px-6 py-3 font-medium">Kategori</th>
                        <th class="px-6 py-3 text-right font-medium">Debit (Rp)</th>
                        <th class="px-6 py-3 text-right font-medium">Kredit (Rp)</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @foreach($rows as $row)
                        <tr class="transition hover:bg-slate-50">
                            <td class="px-6 py-3.5 font-mono font-semibold text-[#15235F]">
                                {{ $row['akun']->kode_akun }}
                            </td>
                            <td class="px-6 py-3.5 font-medium text-slate-800">
                                {{ $row['akun']->nama_akun }}
                            </td>
                            <td class="px-6 py-3.5 text-xs text-slate-500">
                                {{ $row['akun']->kategori_akun }}
                            </td>
                            <td class="px-6 py-3.5 text-right font-semibold text-slate-800">
                                {{ $row['saldo_debit'] > 0 ? number_format($row['saldo_debit'], 0, ',', '.') : '-' }}
                            </td>
                            <td class="px-6 py-3.5 text-right font-semibold text-slate-800">
                                {{ $row['saldo_kredit'] > 0 ? number_format($row['saldo_kredit'], 0, ',', '.') : '-' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>

                <tfoot>
                    <tr class="border-t-2 border-slate-200 bg-slate-50 font-bold text-base">
                        <td colspan="3" class="px-6 py-4 text-right text-slate-700">TOTAL:</td>
                        <td class="px-6 py-4 text-right text-emerald-600">
                            Rp {{ number_format($grandTotalDebit, 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 text-right text-emerald-600">
                            Rp {{ number_format($grandTotalKredit, 0, ',', '.') }}
                        </td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>

@endsection
