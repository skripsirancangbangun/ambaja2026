@extends('layouts.app')

@section('title', 'Laporan Laba Rugi (PSAK)')

@section('content')

<div class="mx-auto max-w-4xl space-y-6">

    {{-- HEADER --}}
    <div>
        <h2 class="text-xl font-semibold text-slate-800">
            Laporan Laba Rugi (Profit & Loss Statement)
        </h2>
        <p class="mt-1 text-sm text-slate-500">
            Format Laporan Keuangan Multi-Step Sesuai Standar Akuntansi Keuangan (PSAK).
        </p>
    </div>

    {{-- FILTER --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('laporan.laba-rugi') }}" class="space-y-4">
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
                    href="{{ route('laporan.laba-rugi') }}"
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
                <p class="text-sm font-semibold text-slate-700">LAPORAN LABA RUGI (PSAK)</p>
                <p class="text-xs text-slate-500">Periode {{ date('d/m/Y', strtotime($tanggalMulai)) }} s/d {{ date('d/m/Y', strtotime($tanggalSelesai)) }}</p>
            </div>

            {{-- EXPORT BUTTONS --}}
            <div class="flex flex-wrap items-center gap-2">
                <a
                    href="{{ route('laporan.laba-rugi.pdf', request()->query()) }}"
                    target="_blank"
                    class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-medium text-red-600 transition hover:border-red-300 hover:bg-red-100"
                >
                    <x-icon name="file-text" class="h-4 w-4" />
                    Cetak PDF
                </a>

                <a
                    href="{{ route('laporan.laba-rugi.excel', request()->query()) }}"
                    class="inline-flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-medium text-emerald-600 transition hover:border-emerald-300 hover:bg-emerald-100"
                >
                    <x-icon name="file-text" class="h-4 w-4" />
                    Export Excel
                </a>
            </div>
        </div>

        {{-- 1. PENJUALAN --}}
        <div>
            <h4 class="text-xs font-bold uppercase tracking-wider text-[#15235F] border-b border-slate-200 pb-2">
                1. PENJUALAN (REVENUE)
            </h4>
            <div class="divide-y divide-slate-100 mt-2 text-sm">
                @foreach($penjualanKotorData as $row)
                    <div class="flex justify-between py-1.5">
                        <span class="text-slate-700 font-medium pl-2">{{ $row['akun']->kode_akun }} - {{ $row['akun']->nama_akun }}</span>
                        <span class="font-medium text-slate-800">Rp {{ number_format($row['nominal'], 0, ',', '.') }}</span>
                    </div>
                @endforeach
                @foreach($diskonPenjualanData as $row)
                    <div class="flex justify-between py-1.5 text-rose-600">
                        <span class="pl-2">(-) {{ $row['akun']->kode_akun }} - {{ $row['akun']->nama_akun }}</span>
                        <span class="font-medium">(Rp {{ number_format($row['nominal'], 0, ',', '.') }})</span>
                    </div>
                @endforeach
                @foreach($returPenjualanData as $row)
                    <div class="flex justify-between py-1.5 text-rose-600">
                        <span class="pl-2">(-) {{ $row['akun']->kode_akun }} - {{ $row['akun']->nama_akun }}</span>
                        <span class="font-medium">(Rp {{ number_format($row['nominal'], 0, ',', '.') }})</span>
                    </div>
                @endforeach
            </div>
            <div class="flex justify-between border-t border-slate-300 mt-1 pt-2 font-bold text-sm text-slate-900 bg-slate-50 p-2 rounded-lg">
                <span>PENJUALAN BERSIH (NET SALES)</span>
                <span class="text-emerald-700">Rp {{ number_format($penjualanBersih, 0, ',', '.') }}</span>
            </div>
        </div>

        {{-- 2. HARGA POKOK PENJUALAN --}}
        <div>
            <h4 class="text-xs font-bold uppercase tracking-wider text-[#15235F] border-b border-slate-200 pb-2">
                2. HARGA POKOK PENJUALAN (HPP / COGS)
            </h4>
            <div class="divide-y divide-slate-100 mt-2 text-sm">
                @foreach($hppUtamaData as $row)
                    <div class="flex justify-between py-1.5">
                        <span class="text-slate-700 font-medium pl-2">{{ $row['akun']->kode_akun }} - {{ $row['akun']->nama_akun }}</span>
                        <span class="font-medium text-slate-800">Rp {{ number_format($row['nominal'], 0, ',', '.') }}</span>
                    </div>
                @endforeach
                @foreach($biayaAngkutData as $row)
                    <div class="flex justify-between py-1.5">
                        <span class="text-slate-700 pl-2">(+) {{ $row['akun']->kode_akun }} - {{ $row['akun']->nama_akun }}</span>
                        <span class="font-medium text-slate-800">Rp {{ number_format($row['nominal'], 0, ',', '.') }}</span>
                    </div>
                @endforeach
                @foreach($biayaBongkarData as $row)
                    <div class="flex justify-between py-1.5">
                        <span class="text-slate-700 pl-2">(+) {{ $row['akun']->kode_akun }} - {{ $row['akun']->nama_akun }}</span>
                        <span class="font-medium text-slate-800">Rp {{ number_format($row['nominal'], 0, ',', '.') }}</span>
                    </div>
                @endforeach
                @foreach($potonganPembelianData as $row)
                    <div class="flex justify-between py-1.5 text-emerald-600">
                        <span class="pl-2">(-) {{ $row['akun']->kode_akun }} - {{ $row['akun']->nama_akun }}</span>
                        <span class="font-medium">(Rp {{ number_format($row['nominal'], 0, ',', '.') }})</span>
                    </div>
                @endforeach
                @foreach($returPembelianData as $row)
                    <div class="flex justify-between py-1.5 text-emerald-600">
                        <span class="pl-2">(-) {{ $row['akun']->kode_akun }} - {{ $row['akun']->nama_akun }}</span>
                        <span class="font-medium">(Rp {{ number_format($row['nominal'], 0, ',', '.') }})</span>
                    </div>
                @endforeach
            </div>
            <div class="flex justify-between border-t border-slate-300 mt-1 pt-2 font-bold text-sm text-slate-900 bg-slate-50 p-2 rounded-lg">
                <span>TOTAL HARGA POKOK PENJUALAN</span>
                <span class="text-rose-700">Rp {{ number_format($totalHpp, 0, ',', '.') }}</span>
            </div>
        </div>

        {{-- 3. LABA KOTOR --}}
        <div class="rounded-xl border border-blue-200 bg-blue-50/60 p-3.5 flex justify-between items-center font-bold text-sm">
            <span class="text-[#15235F] uppercase">{{ $labaKotor >= 0 ? '3. LABA KOTOR (GROSS PROFIT)' : '3. RUGI KOTOR (GROSS LOSS)' }}</span>
            <span class="{{ $labaKotor >= 0 ? 'text-emerald-700' : 'text-rose-700' }} text-base">
                Rp {{ number_format($labaKotor, 0, ',', '.') }}
            </span>
        </div>

        {{-- 4. BEBAN OPERASIONAL --}}
        <div>
            <h4 class="text-xs font-bold uppercase tracking-wider text-[#15235F] border-b border-slate-200 pb-2">
                4. BEBAN OPERASIONAL (OPERATING EXPENSES)
            </h4>
            <div class="divide-y divide-slate-100 mt-2 text-sm">
                @foreach($bebanOpsData as $row)
                    <div class="flex justify-between py-1.5">
                        <span class="text-slate-700 font-medium pl-2">{{ $row['akun']->kode_akun }} - {{ $row['akun']->nama_akun }}</span>
                        <span class="font-medium text-slate-800">Rp {{ number_format($row['nominal'], 0, ',', '.') }}</span>
                    </div>
                @endforeach
            </div>
            <div class="flex justify-between border-t border-slate-300 mt-1 pt-2 font-bold text-sm text-slate-900 bg-slate-50 p-2 rounded-lg">
                <span>TOTAL BEBAN OPERASIONAL</span>
                <span class="text-rose-700">Rp {{ number_format($totalBebanOps, 0, ',', '.') }}</span>
            </div>
        </div>

        {{-- 5. LABA OPERASIONAL --}}
        <div class="rounded-xl border border-indigo-200 bg-indigo-50/60 p-3.5 flex justify-between items-center font-bold text-sm">
            <span class="text-[#15235F] uppercase">{{ $labaOperasional >= 0 ? '5. LABA OPERASIONAL / LABA USAHA' : '5. RUGI OPERASIONAL' }}</span>
            <span class="{{ $labaOperasional >= 0 ? 'text-emerald-700' : 'text-rose-700' }} text-base">
                Rp {{ number_format($labaOperasional, 0, ',', '.') }}
            </span>
        </div>

        {{-- 6. PENDAPATAN & BEBAN LAIN-LAIN --}}
        <div>
            <h4 class="text-xs font-bold uppercase tracking-wider text-[#15235F] border-b border-slate-200 pb-2">
                6. PENDAPATAN & BEBAN LAIN-LAIN
            </h4>
            <div class="divide-y divide-slate-100 mt-2 text-sm">
                @foreach($pendLainData as $row)
                    <div class="flex justify-between py-1.5 text-emerald-700">
                        <span class="pl-2">(+) {{ $row['akun']->kode_akun }} - {{ $row['akun']->nama_akun }}</span>
                        <span class="font-medium">Rp {{ number_format($row['nominal'], 0, ',', '.') }}</span>
                    </div>
                @endforeach
                @foreach($bebanLainData as $row)
                    <div class="flex justify-between py-1.5 text-rose-600">
                        <span class="pl-2">(-) {{ $row['akun']->kode_akun }} - {{ $row['akun']->nama_akun }}</span>
                        <span class="font-medium">(Rp {{ number_format($row['nominal'], 0, ',', '.') }})</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- 7. LABA SEBELUM PAJAK --}}
        <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-3.5 flex justify-between items-center font-bold text-sm">
            <span class="text-amber-900 uppercase">{{ $labaSebelumPajak >= 0 ? '7. LABA BERSIH SEBELUM PAJAK (EBT)' : '7. RUGI SEBELUM PAJAK' }}</span>
            <span class="{{ $labaSebelumPajak >= 0 ? 'text-emerald-700' : 'text-rose-700' }} text-base">
                Rp {{ number_format($labaSebelumPajak, 0, ',', '.') }}
            </span>
        </div>

        {{-- 8. BEBAN PAJAK PENGHASILAN --}}
        <div>
            <h4 class="text-xs font-bold uppercase tracking-wider text-[#15235F] border-b border-slate-200 pb-2">
                8. BEBAN PAJAK PENGHASILAN (PPh)
            </h4>
            <div class="divide-y divide-slate-100 mt-2 text-sm">
                @foreach($bebanPajakData as $row)
                    <div class="flex justify-between py-1.5 text-rose-600">
                        <span class="pl-2">(-) {{ $row['akun']->kode_akun }} - {{ $row['akun']->nama_akun }}</span>
                        <span class="font-medium">(Rp {{ number_format($row['nominal'], 0, ',', '.') }})</span>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- 9. LABA BERSIH SETELAH PAJAK RESULT BOX --}}
        <div class="rounded-2xl border p-5 shadow-sm {{ $labaSetelahPajak >= 0 ? 'border-emerald-300 bg-emerald-50' : 'border-rose-300 bg-rose-50' }}">
            <div class="flex justify-between items-center">
                <div>
                    <h3 class="text-base font-bold uppercase tracking-wide {{ $labaSetelahPajak >= 0 ? 'text-emerald-900' : 'text-rose-900' }}">
                        {{ $labaSetelahPajak >= 0 ? '9. LABA BERSIH SETELAH PAJAK (NET PROFIT AFTER TAX)' : '9. RUGI BERSIH SETELAH PAJAK (NET LOSS AFTER TAX)' }}
                    </h3>
                    <p class="text-xs text-slate-500">Hasil Akhir Sesuai Perhitungan Standar PSAK Akuntansi</p>
                </div>
                <div class="text-2xl font-black {{ $labaSetelahPajak >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                    Rp {{ number_format($labaSetelahPajak, 0, ',', '.') }}
                </div>
            </div>
        </div>

    </div>

</div>

@endsection
