@extends('layouts.app')

@section('title', 'Buku Piutang Dagang')

@section('content')

<div class="space-y-6">

    {{-- HEADER --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-semibold text-slate-800">Piutang Dagang (Customer)</h2>
            <p class="mt-1 text-sm text-slate-500">
                Monitoring tagihan invoice penjualan, status pembayaran, dan histori tindak lanjut penagihan.
            </p>
        </div>

        <a
            href="{{ route('piutang.kartu') }}"
            class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
            <x-icon name="book-open" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" /> Buku Pembantu Piutang
        </a>
    </div>

    {{-- SUMMARY CARDS --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Total Piutang Belum Lunas</p>
            <p class="mt-2 text-2xl font-bold text-[#15235F]">
                Rp {{ number_format($totalPiutang, 0, ',', '.') }}
            </p>
            <p class="mt-1 text-xs text-slate-400">Dari {{ $totalInvoiceBelumLunas }} invoice terbuka</p>
        </div>

        <div class="rounded-2xl border border-rose-100 bg-rose-50 p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-rose-400">Lewat Jatuh Tempo</p>
            <p class="mt-2 text-2xl font-bold text-rose-600">
                Rp {{ number_format($totalPiutangJatuhTempo, 0, ',', '.') }}
            </p>
            <p class="mt-1 text-xs text-rose-500">Memerlukan penagihan segera</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Jumlah Faktur Belum Lunas</p>
            <p class="mt-2 text-2xl font-bold text-amber-600">
                {{ $totalInvoiceBelumLunas }}
            </p>
            <p class="mt-1 text-xs text-slate-400">Faktur Penjualan</p>
        </div>
    </div>

    {{-- FILTER --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('piutang.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Pencarian</label>
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="No. Invoice atau Customer..."
                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm text-slate-700 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                >
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Filter Customer</label>
                <select
                    name="customer_id"
                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm text-slate-700 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                >
                    <option value="">Semua Customer</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}" {{ request('customer_id') == $c->id ? 'selected' : '' }}>
                            {{ $c->nama_customer }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Status Piutang</label>
                <select
                    name="status_pembayaran"
                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm text-slate-700 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                >
                    <option value="BELUM_LUNAS_ALL" {{ ($statusPembayaran ?? '') == 'BELUM_LUNAS_ALL' ? 'selected' : '' }}>Belum Lunas & Sebagian (Default)</option>
                    <option value="Belum Lunas" {{ ($statusPembayaran ?? '') == 'Belum Lunas' ? 'selected' : '' }}>Belum Lunas Saja</option>
                    <option value="Sebagian" {{ ($statusPembayaran ?? '') == 'Sebagian' ? 'selected' : '' }}>Sebagian Saja</option>
                    <option value="Lunas" {{ ($statusPembayaran ?? '') == 'Lunas' ? 'selected' : '' }}>Sudah Lunas</option>
                    <option value="SEMUA" {{ ($statusPembayaran ?? '') == 'SEMUA' ? 'selected' : '' }}>Semua Status</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Jatuh Tempo</label>
                <select
                    name="jatuh_tempo"
                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm text-slate-700 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                >
                    <option value="">Semua</option>
                    <option value="lewat" {{ request('jatuh_tempo') == 'lewat' ? 'selected' : '' }}>Sudah Jatuh Tempo</option>
                    <option value="mendekati" {{ request('jatuh_tempo') == 'mendekati' ? 'selected' : '' }}>Jatuh Tempo 7 Hari Ke Depan</option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button
                    type="submit"
                    class="w-full rounded-xl bg-[#15235F] py-2.5 text-sm font-medium text-white transition hover:bg-[#101b4d]"
                >
                    Filter
                </button>
                @if(request()->hasAny(['search', 'customer_id', 'status_pembayaran', 'jatuh_tempo']))
                    <a
                        href="{{ route('piutang.index') }}"
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
            <h3 class="text-sm font-semibold text-slate-700">Daftar Tagihan Piutang Dagang</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-xs uppercase tracking-wide text-slate-400">
                        <th class="px-6 py-3 font-medium">No. Invoice</th>
                        <th class="px-6 py-3 font-medium">Customer</th>
                        <th class="px-6 py-3 font-medium">Tanggal Faktur</th>
                        <th class="px-6 py-3 font-medium">Jatuh Tempo</th>
                        <th class="px-6 py-3 text-right font-medium">Total Tagihan</th>
                        <th class="px-6 py-3 text-right font-medium">Sudah Dibayar</th>
                        <th class="px-6 py-3 text-right font-medium">Sisa Piutang</th>
                        <th class="px-6 py-3 font-medium">Status Status</th>
                        <th class="px-6 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($invoices as $invoice)
                        @php
                            $isOverdue = $invoice->tanggal_jatuh_tempo && $invoice->tanggal_jatuh_tempo->isPast() && $invoice->sisa_piutang > 0;
                            $daysDiff = $invoice->tanggal_jatuh_tempo ? (int) now()->diffInDays($invoice->tanggal_jatuh_tempo, false) : 0;
                        @endphp
                        <tr class="transition hover:bg-slate-50">
                            <td class="px-6 py-4 font-semibold text-[#15235F]">
                                <a href="{{ route('piutang.show', $invoice) }}" class="hover:underline">
                                    {{ $invoice->nomor_invoice }}
                                </a>
                            </td>
                            <td class="px-6 py-4 font-medium text-slate-800">
                                {{ $invoice->customer->nama_customer ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                {{ $invoice->tanggal_invoice ? $invoice->tanggal_invoice->format('d/m/Y') : '-' }}
                            </td>
                            <td class="px-6 py-4 {{ $isOverdue ? 'font-semibold text-rose-600' : 'text-slate-600' }}">
                                {{ $invoice->tanggal_jatuh_tempo ? $invoice->tanggal_jatuh_tempo->format('d/m/Y') : '-' }}
                            </td>
                            <td class="px-6 py-4 text-right text-slate-700">
                                Rp {{ number_format($invoice->total, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-right text-emerald-600">
                                Rp {{ number_format($invoice->sudah_dibayar, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-right font-bold {{ $invoice->sisa_piutang > 0 ? 'text-rose-600' : 'text-slate-500' }}">
                                Rp {{ number_format($invoice->sisa_piutang, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4">
                                @if($invoice->status_pembayaran === 'Lunas')
                                    <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">Lunas</span>
                                @elseif($isOverdue)
                                    <span class="inline-flex rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">
                                        Lewat {{ abs($daysDiff) }} hari
                                    </span>
                                @else
                                    <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">
                                        {{ $invoice->status_pembayaran }} ({{ $daysDiff }}hr lagi)
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a
                                    href="{{ route('piutang.show', $invoice) }}"
                                    class="inline-flex items-center gap-1.5 rounded-lg bg-[#15235F] px-3.5 py-1.5 text-xs font-semibold text-white transition hover:bg-[#101b4d]"
                                >
                                    <x-icon name="search" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" /> Detail & Penagihan
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-8 text-center text-slate-400">
                                Tidak ada data piutang dagang yang sesuai filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($invoices->hasPages())
            <div class="border-t border-slate-200 px-6 py-4">
                {{ $invoices->links() }}
            </div>
        @endif
    </div>

</div>

@endsection
