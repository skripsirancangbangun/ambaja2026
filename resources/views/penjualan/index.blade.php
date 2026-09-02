@extends('layouts.app')

@section('title', 'Pesanan Penjualan (Sales Order)')

@section('content')

<div class="space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-semibold text-slate-800">Pesanan Penjualan (Sales Order)</h2>
            <p class="mt-1 text-sm text-slate-500">
                Pencatatan pesanan penjualan dari customer sebelum penerbitan faktur.
            </p>
        </div>

        <a
            href="{{ route('penjualan.create') }}"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#15235F] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#101b4d]"
        >
            <span>+</span>
            Buat Sales Order
        </a>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    {{-- FILTER --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('penjualan.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Pencarian</label>
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="No. SO atau Customer..."
                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm text-slate-700 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                >
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Status</label>
                <select
                    name="status"
                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm text-slate-700 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                >
                    <option value="">Semua Status</option>
                    @foreach(['Draft', 'Dikonfirmasi', 'Diproses', 'Selesai', 'Dibatalkan'] as $st)
                        <option value="{{ $st }}" {{ request('status') == $st ? 'selected' : '' }}>{{ $st }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Dari Tanggal</label>
                <input
                    type="date"
                    name="tanggal_mulai"
                    value="{{ request('tanggal_mulai') }}"
                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm text-slate-700 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                >
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Sampai Tanggal</label>
                <div class="mt-1.5 flex gap-2">
                    <input
                        type="date"
                        name="tanggal_selesai"
                        value="{{ request('tanggal_selesai') }}"
                        class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm text-slate-700 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                    >
                    <button
                        type="submit"
                        class="rounded-xl bg-[#15235F] px-4 py-2 text-sm font-medium text-white transition hover:bg-[#101b4d]"
                    >
                        Filter
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- TABLE --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-4">
            <h3 class="text-sm font-semibold text-slate-700">Daftar Sales Order</h3>
            <p class="mt-1 text-xs text-slate-400">Total {{ $penjualans->total() }} pesanan.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-xs uppercase tracking-wide text-slate-400">
                        <th class="px-6 py-3 font-medium">No. SO</th>
                        <th class="px-6 py-3 font-medium">Tanggal</th>
                        <th class="px-6 py-3 font-medium">Customer</th>
                        <th class="px-6 py-3 font-medium">Total Nilai</th>
                        <th class="px-6 py-3 font-medium">Status</th>
                        <th class="px-6 py-3 font-medium">Dibuat Oleh</th>
                        <th class="px-6 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($penjualans as $penjualan)
                        <tr class="transition hover:bg-slate-50">
                            <td class="px-6 py-4 font-semibold text-[#15235F]">
                                <a href="{{ route('penjualan.show', $penjualan) }}" class="hover:underline">
                                    {{ $penjualan->nomor_penjualan }}
                                </a>
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                {{ $penjualan->tanggal_penjualan ? $penjualan->tanggal_penjualan->format('d/m/Y') : '-' }}
                            </td>
                            <td class="px-6 py-4 font-medium text-slate-800">
                                {{ $penjualan->customer->nama_customer ?? '-' }}
                            </td>
                            <td class="px-6 py-4 font-semibold text-slate-800">
                                Rp {{ number_format($penjualan->total, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $badges = [
                                        'Draft' => 'bg-slate-100 text-slate-600',
                                        'Dikonfirmasi' => 'bg-blue-50 text-blue-700',
                                        'Diproses' => 'bg-amber-50 text-amber-700',
                                        'Selesai' => 'bg-emerald-50 text-emerald-700',
                                        'Dibatalkan' => 'bg-rose-50 text-rose-700',
                                    ];
                                @endphp
                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $badges[$penjualan->status] ?? 'bg-slate-100 text-slate-600' }}">
                                    {{ $penjualan->status }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-500">
                                {{ $penjualan->creator->name ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a
                                        href="{{ route('penjualan.show', $penjualan) }}"
                                        class="rounded-lg bg-teal-50 px-3 py-1.5 text-xs font-medium text-[#15235F] transition hover:bg-teal-100"
                                    >
                                        Detail
                                    </a>
                                    @if(!in_array($penjualan->status, ['Selesai', 'Dibatalkan']))
                                        <a
                                            href="{{ route('invoice-penjualan.create', ['penjualan_id' => $penjualan->id]) }}"
                                            class="rounded-lg bg-[#15235F] px-3 py-1.5 text-xs font-medium text-white transition hover:bg-[#101b4d]"
                                            title="Terbitkan Faktur"
                                        >
                                            Buat Invoice
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-slate-400">
                                Belum ada data pesanan penjualan (Sales Order).
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($penjualans->hasPages())
            <div class="border-t border-slate-200 px-6 py-4">
                {{ $penjualans->links() }}
            </div>
        @endif
    </div>

</div>

@endsection
