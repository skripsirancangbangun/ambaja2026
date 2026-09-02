@extends('layouts.app')

@section('title', 'Laporan Neraca (Balance Sheet)')

@section('content')

<div class="mx-auto max-w-5xl space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-semibold text-slate-800">Laporan Neraca (Balance Sheet)</h2>
            <p class="mt-1 text-sm text-slate-500">
                Laporan Posisi Keuangan Kumulatif dari Awal hingga Per Tanggal End: Aset = Kewajiban + Ekuitas.
            </p>
        </div>

        <button
            type="button"
            onclick="window.print()"
            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
        >
            🖨️ Cetak Neraca
        </button>
    </div>

    {{-- FILTER --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('laporan.neraca') }}" class="flex items-center gap-4">
            <div class="w-full max-w-xs">
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Per Tanggal (End Date)</label>
                <input
                    type="date"
                    name="tanggal"
                    value="{{ $tanggal }}"
                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm text-slate-700 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                >
            </div>

            <div class="flex items-end pt-5">
                <button
                    type="submit"
                    class="rounded-xl bg-[#15235F] px-6 py-2 text-sm font-medium text-white transition hover:bg-[#101b4d]"
                >
                    Tampilkan Neraca
                </button>
            </div>
        </form>
    </div>

    {{-- REPORT SHEET --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm p-6 space-y-6">
        <div class="border-b border-slate-200 pb-4 text-center">
            <h2 class="text-xl font-bold text-[#15235F]">CV AMBAJA (ANDIKA MUSTIKA)</h2>
            <h3 class="text-base font-semibold text-slate-700">LAPORAN NERACA (POSISI KEUANGAN)</h3>
            <p class="text-xs text-slate-500">Posisi Per Tanggal {{ date('d F Y', strtotime($tanggal)) }}</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            {{-- AKTIVA / ASET --}}
            <div class="space-y-4">
                <div class="border-b-2 border-[#15235F] pb-2">
                    <h4 class="text-sm font-bold uppercase tracking-wider text-[#15235F]">ASET / AKTIVA</h4>
                </div>

                <div class="divide-y divide-slate-100">
                    @foreach($asetData as $row)
                        <div class="flex justify-between py-2 text-sm">
                            <span class="text-slate-700 font-medium">{{ $row['akun']->kode_akun }} - {{ $row['akun']->nama_akun }}</span>
                            <span class="font-semibold text-slate-800">Rp {{ number_format($row['nominal'], 0, ',', '.') }}</span>
                        </div>
                    @endforeach
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
                        <div class="flex justify-between py-2 text-sm italic">
                            <span class="text-slate-700 font-medium">Laba / (Rugi) Kumulatif s/d Tanggal Ini</span>
                            <span class="font-semibold text-slate-800">Rp {{ number_format($labaRugiKumulatif, 0, ',', '.') }}</span>
                        </div>
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

        {{-- BALANCE VERIFICATION --}}
        @php
            $isBalanced = abs($totalAset - ($totalKewajiban + $totalEkuitas)) < 0.01;
        @endphp
        <div class="mt-6 rounded-2xl border p-4 text-center font-bold text-sm {{ $isBalanced ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-rose-200 bg-rose-50 text-rose-700' }}">
            {{ $isBalanced ? '✓ NERACA SEIMBANG: Total Aset = Total Kewajiban + Total Ekuitas' : '⚠️ NERACA TIDAK SEIMBANG: Ada selisih pada saldo akun' }}
        </div>
    </div>

</div>

@endsection
