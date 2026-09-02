@extends('layouts.app')

@section('title', 'Buku Besar (General Ledger)')

@section('content')

<div class="space-y-6">

    <div>
        <h2 class="text-xl font-semibold text-slate-800">Buku Besar (General Ledger)</h2>
        <p class="mt-1 text-sm text-slate-500">
            Laporan rincian mutasi debit/kredit dan saldo berjalan per akun COA.
        </p>
    </div>

    {{-- FILTER --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('buku-besar.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Pilih Akun <span class="text-rose-500">*</span></label>
                <select
                    name="akun_id"
                    required
                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                >
                    @foreach($akuns as $a)
                        <option value="{{ $a->id }}" {{ $selectedAkunId == $a->id ? 'selected' : '' }}>
                            {{ $a->kode_akun }} - {{ $a->nama_akun }} ({{ $a->saldo_normal }})
                        </option>
                    @endforeach
                </select>
            </div>

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
                    Tampilkan Buku Besar
                </button>
            </div>
        </form>
    </div>

    @if($selectedAkun)
        {{-- AKUN HEADER INFO --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <span class="inline-flex rounded-lg bg-teal-50 px-3 py-1 text-xs font-mono font-semibold text-[#15235F]">
                    Kode: {{ $selectedAkun->kode_akun }}
                </span>
                <h3 class="mt-2 text-lg font-bold text-slate-800">{{ $selectedAkun->nama_akun }}</h3>
                <p class="text-xs text-slate-500">Kategori: {{ $selectedAkun->kategori_akun }} | Saldo Normal: {{ $selectedAkun->saldo_normal }}</p>
            </div>

            <div class="rounded-xl border border-slate-100 bg-slate-50 p-4 text-right min-w-[200px]">
                <p class="text-xs font-medium uppercase text-slate-400">Saldo Awal Periode</p>
                <p class="mt-1 text-lg font-bold text-[#15235F]">
                    Rp {{ number_format($saldoAwalPeriod, 0, ',', '.') }}
                </p>
            </div>
        </div>

        {{-- MUTASI TABLE --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-4">
                <h3 class="text-sm font-semibold text-slate-700">Kartu Mutasi & Saldo Berjalan (Running Balance)</h3>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-xs uppercase tracking-wide text-slate-400">
                            <th class="px-6 py-3 font-medium">Tanggal</th>
                            <th class="px-6 py-3 font-medium">No. Jurnal</th>
                            <th class="px-6 py-3 font-medium">Keterangan</th>
                            <th class="px-6 py-3 text-right font-medium">Debit (Rp)</th>
                            <th class="px-6 py-3 text-right font-medium">Kredit (Rp)</th>
                            <th class="px-6 py-3 text-right font-medium">Saldo Berjalan (Rp)</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        {{-- Row Saldo Awal --}}
                        <tr class="bg-slate-50/50 font-medium">
                            <td class="px-6 py-3 text-slate-500">{{ date('d/m/Y', strtotime($tanggalMulai)) }}</td>
                            <td class="px-6 py-3 text-slate-400">-</td>
                            <td class="px-6 py-3 text-slate-700 font-semibold">SALDO AWAL PERIODE</td>
                            <td class="px-6 py-3 text-right text-slate-400">-</td>
                            <td class="px-6 py-3 text-right text-slate-400">-</td>
                            <td class="px-6 py-3 text-right font-bold text-[#15235F]">
                                Rp {{ number_format($saldoAwalPeriod, 0, ',', '.') }}
                            </td>
                        </tr>

                        @php
                            $currentBalance = $saldoAwalPeriod;
                            $totalDebit = 0;
                            $totalKredit = 0;
                        @endphp

                        @forelse($details as $detail)
                            @php
                                $totalDebit += $detail->debit;
                                $totalKredit += $detail->kredit;

                                if ($selectedAkun->saldo_normal === 'DEBIT') {
                                    $currentBalance += ($detail->debit - $detail->kredit);
                                } else {
                                    $currentBalance += ($detail->kredit - $detail->debit);
                                }
                            @endphp
                            <tr class="transition hover:bg-slate-50">
                                <td class="px-6 py-4 text-slate-600">
                                    {{ $detail->jurnal->tanggal ? $detail->jurnal->tanggal->format('d/m/Y') : '-' }}
                                </td>
                                <td class="px-6 py-4 font-mono font-semibold text-[#15235F]">
                                    <a href="{{ route('jurnal-umum.show', $detail->jurnal) }}" class="hover:underline">
                                        {{ $detail->jurnal->nomor_jurnal }}
                                    </a>
                                </td>
                                <td class="px-6 py-4 text-slate-800">
                                    {{ $detail->keterangan ?: $detail->jurnal->keterangan }}
                                </td>
                                <td class="px-6 py-4 text-right font-medium text-slate-700">
                                    {{ $detail->debit > 0 ? 'Rp ' . number_format($detail->debit, 0, ',', '.') : '-' }}
                                </td>
                                <td class="px-6 py-4 text-right font-medium text-slate-700">
                                    {{ $detail->kredit > 0 ? 'Rp ' . number_format($detail->kredit, 0, ',', '.') : '-' }}
                                </td>
                                <td class="px-6 py-4 text-right font-bold text-[#15235F]">
                                    Rp {{ number_format($currentBalance, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-6 text-center text-slate-400">
                                    Tidak ada mutasi transaksi pada rentang tanggal ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    <tfoot>
                        <tr class="border-t border-slate-200 bg-slate-50 font-bold">
                            <td colspan="3" class="px-6 py-4 text-right text-slate-700">Total Mutasi Periode Ini:</td>
                            <td class="px-6 py-4 text-right text-emerald-600">
                                Rp {{ number_format($totalDebit, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-right text-emerald-600">
                                Rp {{ number_format($totalKredit, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-right text-[#15235F]">
                                Rp {{ number_format($currentBalance, 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif

</div>

@endsection
