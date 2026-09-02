@extends('layouts.app')

@section('title', 'Chart of Accounts (Bagan Akun)')

@section('content')

<div class="space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-semibold text-slate-800">Chart of Accounts (COA)</h2>
            <p class="mt-1 text-sm text-slate-500">
                Master Bagan Akun standar akuntansi CV Ambaja untuk pengklasifikasian transaksi.
            </p>
        </div>

        <div class="flex items-center gap-3">
            @if($canEditOpeningBalance ?? true)
                <span class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 py-2 text-xs font-semibold text-emerald-800">
                    🟢 Mode Migrasi (Saldo Awal Aktif)
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 rounded-xl border border-rose-200 bg-rose-50 px-3.5 py-2 text-xs font-semibold text-rose-800" title="Saldo awal tidak bisa diubah karena sistem sudah memiliki transaksi operasional">
                    🔒 Saldo Awal Terkunci (Sistem Berjalan)
                </span>
            @endif

            <a
                href="{{ route('coa.create') }}"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#15235F] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#101b4d]"
            >
                <span>+</span>
                Tambah Akun Baru
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {{ session('error') }}
        </div>
    @endif

    {{-- FILTER --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('coa.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Pencarian</label>
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Kode atau Nama Akun..."
                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm text-slate-700 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                >
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Kategori Akun</label>
                <select
                    name="kategori_akun"
                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm text-slate-700 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                >
                    <option value="">Semua Kategori</option>
                    @foreach(['Aset', 'Kewajiban', 'Ekuitas', 'Pendapatan', 'Beban'] as $kat)
                        <option value="{{ $kat }}" {{ request('kategori_akun') == $kat ? 'selected' : '' }}>{{ $kat }}</option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button
                    type="submit"
                    class="w-full rounded-xl bg-[#15235F] py-2.5 text-sm font-medium text-white transition hover:bg-[#101b4d]"
                >
                    Filter
                </button>
                @if(request()->hasAny(['search', 'kategori_akun']))
                    <a
                        href="{{ route('coa.index') }}"
                        class="rounded-xl border border-slate-200 bg-slate-100 px-3.5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-200"
                    >
                        Reset
                    </a>
                @endif
            </div>
        </form>
    </div>

    {{-- TABLE --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-4">
            <h3 class="text-sm font-semibold text-slate-700">Daftar Akun</h3>
            <p class="mt-1 text-xs text-slate-400">Total {{ $akuns->total() }} akun terdaftar.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-xs uppercase tracking-wide text-slate-400">
                        <th class="px-6 py-3 font-medium">Kode Akun</th>
                        <th class="px-6 py-3 font-medium">Nama Akun</th>
                        <th class="px-6 py-3 font-medium">Kategori</th>
                        <th class="px-6 py-3 font-medium">Sub Kategori</th>
                        <th class="px-6 py-3 font-medium">Saldo Normal</th>
                        <th class="px-6 py-3 text-right font-medium">Saldo Saat Ini</th>
                        <th class="px-6 py-3 font-medium">Status</th>
                        <th class="px-6 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($akuns as $akun)
                        <tr class="transition hover:bg-slate-50">
                            <td class="px-6 py-4 font-mono font-semibold text-[#15235F]">
                                {{ $akun->kode_akun }}
                            </td>
                            <td class="px-6 py-4 font-medium text-slate-800">
                                {{ $akun->nama_akun }}
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $katBadges = [
                                        'Aset' => 'bg-blue-50 text-blue-700',
                                        'Kewajiban' => 'bg-rose-50 text-rose-700',
                                        'Ekuitas' => 'bg-purple-50 text-purple-700',
                                        'Pendapatan' => 'bg-emerald-50 text-emerald-700',
                                        'Beban' => 'bg-amber-50 text-amber-700',
                                    ];
                                @endphp
                                <span class="inline-flex rounded-lg px-2.5 py-1 text-xs font-semibold {{ $katBadges[$akun->kategori_akun] ?? 'bg-slate-100 text-slate-700' }}">
                                    {{ $akun->kategori_akun }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-500">
                                {{ $akun->sub_kategori ?: '-' }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex rounded px-2 py-0.5 text-xs font-mono font-medium {{ $akun->saldo_normal == 'DEBIT' ? 'bg-teal-50 text-[#15235F]' : 'bg-slate-100 text-slate-700' }}">
                                    {{ $akun->saldo_normal }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right font-semibold text-slate-800">
                                Rp {{ number_format($akun->saldo_saat_ini, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4">
                                @if($akun->is_active)
                                    <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">Aktif</span>
                                @else
                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-500">Nonaktif</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a
                                        href="{{ route('coa.edit', $akun) }}"
                                        class="rounded-lg bg-teal-50 px-3 py-1.5 text-xs font-medium text-[#15235F] transition hover:bg-teal-100"
                                    >
                                        Edit
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-slate-400">
                                Belum ada data akun COA.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($akuns->hasPages())
            <div class="border-t border-slate-200 px-6 py-4">
                {{ $akuns->links() }}
            </div>
        @endif
    </div>

</div>

@endsection
