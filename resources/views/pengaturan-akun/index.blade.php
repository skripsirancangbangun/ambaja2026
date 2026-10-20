@extends('layouts.app')

@section('title', 'Pengaturan Akun Sistem')

@section('content')

<div class="mx-auto max-w-5xl space-y-6" x-data="{ activeTab: 'penjualan' }">

    {{-- HEADER --}}
    <div>
        <h2 class="text-xl font-semibold text-slate-800">
            Pengaturan Pemetaan Akun Sistem
        </h2>
        <p class="mt-1 text-sm text-slate-500">
            Tentukan pemetaan akun Bagan Akun (COA) otomatis yang dikelompokkan berdasarkan modul transaksi (Penjualan, Retur Penjualan, Pembelian, dan Retur Pembelian/Kas).
        </p>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
            <x-icon name="check" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" /> {{ session('success') }}
        </div>
    @endif

    {{-- TAB NAVIGATION --}}
    <div class="flex flex-wrap gap-2 border-b border-slate-200 pb-2">
        <button
            @click="activeTab = 'penjualan'"
            :class="activeTab === 'penjualan' ? 'border-[#32AAA6] bg-[#15235F] text-white' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
            class="rounded-xl border px-4 py-2.5 text-xs font-semibold shadow-sm transition"
        >
            Part 1: Penjualan & Piutang
        </button>
        <button
            @click="activeTab = 'retur_penjualan'"
            :class="activeTab === 'retur_penjualan' ? 'border-[#32AAA6] bg-[#15235F] text-white' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
            class="rounded-xl border px-4 py-2.5 text-xs font-semibold shadow-sm transition"
        >
            Part 2: Retur Penjualan
        </button>
        <button
            @click="activeTab = 'pembelian'"
            :class="activeTab === 'pembelian' ? 'border-[#32AAA6] bg-[#15235F] text-white' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
            class="rounded-xl border px-4 py-2.5 text-xs font-semibold shadow-sm transition"
        >
            Part 3: Pembelian & Utang
        </button>
        <button
            @click="activeTab = 'retur_pembelian'"
            :class="activeTab === 'retur_pembelian' ? 'border-[#32AAA6] bg-[#15235F] text-white' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
            class="rounded-xl border px-4 py-2.5 text-xs font-semibold shadow-sm transition"
        >
            Part 4: Retur Pembelian & Kas/Bank
        </button>
        <button
            @click="activeTab = 'saldo_awal'"
            :class="activeTab === 'saldo_awal' ? 'border-[#32AAA6] bg-[#15235F] text-white' : 'border-slate-200 bg-white text-slate-600 hover:bg-slate-50'"
            class="rounded-xl border px-4 py-2.5 text-xs font-semibold shadow-sm transition"
        >
            Part 5: Saldo Awal Persediaan
        </button>
    </div>

    {{-- FORM PENGATURAN --}}
    <form method="POST" action="{{ route('pengaturan-akun.update') }}" class="space-y-6">
        @csrf
        @method('PUT')

        @php
            $part1Keys = ['AKUN_PIUTANG_DAGANG', 'AKUN_PENDAPATAN_PENJUALAN', 'AKUN_DISKON_PENJUALAN', 'AKUN_PERSEDIAAN', 'AKUN_HPP'];
            $part2Keys = ['AKUN_RETUR_PENJUALAN', 'AKUN_PPN_PENJUALAN'];
            $part3Keys = ['AKUN_UTANG_DAGANG', 'AKUN_PEMBELIAN_BARANG', 'AKUN_DISKON_PEMBELIAN'];
            $part4Keys = ['AKUN_RETUR_PEMBELIAN', 'AKUN_KAS_UTAMA'];
            $part5Keys = ['AKUN_SALDO_AWAL'];
        @endphp

        {{-- PART 1 --}}
        <div x-show="activeTab === 'penjualan'" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-4 bg-slate-50">
                <h3 class="text-sm font-semibold text-slate-800">Part 1: Akun Penjualan & Piutang Dagang</h3>
                <p class="text-xs text-slate-500">Pemetaan akun otomatis saat penerbitan Invoice Penjualan dan pengakuan HPP.</p>
            </div>
            <div class="divide-y divide-slate-100 p-6 space-y-4">
                @foreach($pengaturanAkuns->filter(fn($i) => in_array($i->kunci_pengaturan, $part1Keys)) as $item)
                    @include('pengaturan-akun._row', ['item' => $item, 'akuns' => $akuns])
                @endforeach
            </div>
        </div>

        {{-- PART 2 --}}
        <div x-show="activeTab === 'retur_penjualan'" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-4 bg-slate-50">
                <h3 class="text-sm font-semibold text-slate-800">Part 2: Akun Retur Penjualan & Pajak</h3>
                <p class="text-xs text-slate-500">Pemetaan akun otomatis saat transaksi Retur Penjualan dari Customer.</p>
            </div>
            <div class="divide-y divide-slate-100 p-6 space-y-4">
                @foreach($pengaturanAkuns->filter(fn($i) => in_array($i->kunci_pengaturan, $part2Keys)) as $item)
                    @include('pengaturan-akun._row', ['item' => $item, 'akuns' => $akuns])
                @endforeach
            </div>
        </div>

        {{-- PART 3 --}}
        <div x-show="activeTab === 'pembelian'" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-4 bg-slate-50">
                <h3 class="text-sm font-semibold text-slate-800">Part 3: Akun Pembelian & Utang Dagang</h3>
                <p class="text-xs text-slate-500">Pemetaan akun otomatis saat penerbitan Purchase Order / Invoice Supplier.</p>
            </div>
            <div class="divide-y divide-slate-100 p-6 space-y-4">
                @foreach($pengaturanAkuns->filter(fn($i) => in_array($i->kunci_pengaturan, $part3Keys)) as $item)
                    @include('pengaturan-akun._row', ['item' => $item, 'akuns' => $akuns])
                @endforeach
            </div>
        </div>

        {{-- PART 4 --}}
        <div x-show="activeTab === 'retur_pembelian'" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-4 bg-slate-50">
                <h3 class="text-sm font-semibold text-slate-800">Part 4: Akun Retur Pembelian & Kas/Bank Utama</h3>
                <p class="text-xs text-slate-500">Pemetaan akun untuk Retur Pembelian ke Supplier dan Kas Utama Toko.</p>
            </div>
            <div class="divide-y divide-slate-100 p-6 space-y-4">
                @foreach($pengaturanAkuns->filter(fn($i) => in_array($i->kunci_pengaturan, $part4Keys)) as $item)
                    @include('pengaturan-akun._row', ['item' => $item, 'akuns' => $akuns])
                @endforeach
            </div>
        </div>

        {{-- PART 5: SALDO AWAL PERSEDIAAN --}}
        <div x-show="activeTab === 'saldo_awal'" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="border-b border-slate-200 px-6 py-4 bg-slate-50">
                <h3 class="text-sm font-semibold text-slate-800">Part 5: Akun Lawan Saldo Awal Persediaan</h3>
                <p class="text-xs text-slate-500">Digunakan sebagai akun kredit lawan untuk jurnal pembukaan stok awal. Pilih akun Ekuitas pembukaan sesuai kebijakan akuntansi perusahaan; jangan gunakan akun pendapatan atau pembelian.</p>
            </div>
            <div class="divide-y divide-slate-100 p-6 space-y-4">
                @if($akunsEkuitas->isEmpty())
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800">
                        Belum ada akun Ekuitas aktif pada COA. Tambahkan atau aktifkan akun Ekuitas yang sesuai sebelum mengimpor stok awal.
                    </div>
                @endif

                @forelse($pengaturanAkuns->filter(fn($i) => in_array($i->kunci_pengaturan, $part5Keys)) as $item)
                    @include('pengaturan-akun._row', ['item' => $item, 'akuns' => $akunsEkuitas])
                @empty
                    <div class="rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                        Konfigurasi AKUN_SALDO_AWAL belum ada di database. Jalankan migration perbaikan yang disertakan, lalu muat ulang halaman ini.
                    </div>
                @endforelse
            </div>
        </div>

        <div class="border-t border-slate-200 bg-slate-50 px-6 py-4 flex justify-end rounded-2xl">
            <button
                type="submit"
                class="rounded-xl bg-[#15235F] px-7 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-[#101b4d]"
            >
                <x-icon name="save" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" /> Simpan Seluruh Pengaturan Akun
            </button>
        </div>
    </form>

</div>

@endsection
