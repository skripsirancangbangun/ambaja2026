@extends('layouts.app')

@section('title', 'Laporan Penjualan')

@section('content')

<div class="space-y-6">

    {{-- HEADER --}}
    <div>
        <h2 class="text-xl font-semibold text-slate-800">
            Laporan Penjualan
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            Ringkasan transaksi penjualan berdasarkan Invoice Penjualan.
        </p>
    </div>

    {{-- FILTER --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('laporan.penjualan') }}" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">

                {{-- TANGGAL MULAI --}}
                <div>
                    <label class="mb-2 block text-xs font-medium text-slate-600">Tanggal Mulai</label>
                    <input
                        type="date"
                        name="tanggal_mulai"
                        value="{{ request('tanggal_mulai') }}"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >
                </div>

                {{-- TANGGAL SELESAI --}}
                <div>
                    <label class="mb-2 block text-xs font-medium text-slate-600">Tanggal Selesai</label>
                    <input
                        type="date"
                        name="tanggal_selesai"
                        value="{{ request('tanggal_selesai') }}"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >
                </div>

                {{-- CUSTOMER --}}
                <div>
                    <label class="mb-2 block text-xs font-medium text-slate-600">Customer</label>
                    <select
                        name="customer_id"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >
                        <option value="">Semua Customer</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" {{ request('customer_id') == $c->id ? 'selected' : '' }}>
                                {{ $c->nama_customer }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- STATUS --}}
                <div>
                    <label class="mb-2 block text-xs font-medium text-slate-600">Status Pembayaran</label>
                    <select
                        name="status"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >
                        <option value="">Semua Status</option>
                        <option value="Belum Lunas" {{ request('status') === 'Belum Lunas' ? 'selected' : '' }}>Belum Lunas</option>
                        <option value="Sebagian" {{ request('status') === 'Sebagian' ? 'selected' : '' }}>Sebagian</option>
                        <option value="Lunas" {{ request('status') === 'Lunas' ? 'selected' : '' }}>Lunas</option>
                    </select>
                </div>

                {{-- SEARCH --}}
                <div>
                    <label class="mb-2 block text-xs font-medium text-slate-600">Pencarian</label>
                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Invoice / Customer"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >
                </div>

            </div>

            {{-- BUTTON FILTER --}}
            <div class="flex justify-end gap-2">
                <a
                    href="{{ route('laporan.penjualan') }}"
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

    {{-- SUMMARY --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

        {{-- JUMLAH INVOICE --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-slate-400">Jumlah Invoice</p>
            <p class="mt-2 text-2xl font-bold text-[#15235F]">{{ number_format($jumlahInvoice, 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-slate-400">Invoice penjualan</p>
        </div>

        {{-- TOTAL PENJUALAN --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-slate-400">Total Penjualan</p>
            <p class="mt-2 text-xl font-bold text-[#15235F]">Rp {{ number_format($totalPenjualan, 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-slate-400">Nilai seluruh invoice</p>
        </div>

        {{-- TOTAL DIBAYAR --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-slate-400">Total Dibayar</p>
            <p class="mt-2 text-xl font-bold text-emerald-600">Rp {{ number_format($totalDibayar, 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-slate-400">Penerimaan kas</p>
        </div>

        {{-- SISA PIUTANG --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs uppercase tracking-wide text-slate-400">Sisa Piutang</p>
            <p class="mt-2 text-xl font-bold text-red-500">Rp {{ number_format($totalSisaPiutang, 0, ',', '.') }}</p>
            <p class="mt-1 text-xs text-slate-400">Outstanding customer</p>
        </div>

    </div>

    {{-- TABLE --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        {{-- HEADER TABLE + EXPORT --}}
        <div class="border-b border-slate-200 px-6 py-4">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

                <div>
                    <h3 class="text-sm font-semibold text-slate-700">Detail Penjualan</h3>
                    <p class="mt-1 text-xs text-slate-400">{{ $jumlahInvoice }} invoice sesuai filter.</p>
                </div>

                {{-- EXPORT BUTTON --}}
                <div class="flex flex-wrap items-center gap-2">
                    {{-- PDF --}}
                    <a
                        href="{{ route('laporan.penjualan.pdf', request()->query()) }}"
                        target="_blank"
                        class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-medium text-red-600 transition hover:border-red-300 hover:bg-red-100"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5A3.375 3.375 0 0 0 10.125 2.25H8.25m0 12.75h7.5m-7.5 3h7.5M10.5 2.25H5.625A1.875 1.875 0 0 0 3.75 4.125v15.75c0 1.036.84 1.875 1.875 1.875h12.75a1.875 1.875 0 0 0 1.875-1.875V12.375a10.125 10.125 0 0 0-9.75-10.125Z" />
                        </svg>
                        Cetak PDF
                    </a>

                    {{-- EXCEL --}}
                    <a
                        href="{{ route('laporan.penjualan.excel', request()->query()) }}"
                        class="inline-flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-medium text-emerald-600 transition hover:border-emerald-300 hover:bg-emerald-100"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" class="h-4 w-4">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5A3.375 3.375 0 0 0 10.125 2.25H8.25m.75 12 3 3m0 0 3-3m-3 3v-6m-1.5-9H5.625A1.875 1.875 0 0 0 3.75 4.125v15.75c0 1.036.84 1.875 1.875 1.875h12.75a1.875 1.875 0 0 0 1.875-1.875V12.375a10.125 10.125 0 0 0-9.75-10.125Z" />
                        </svg>
                        Export Excel
                    </a>
                </div>
            </div>
        </div>

        {{-- TABEL DATA --}}
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-4 py-3">Tanggal</th>
                        <th class="px-4 py-3">No. Invoice</th>
                        <th class="px-4 py-3">Customer</th>
                        <th class="px-4 py-3">Gudang</th>
                        <th class="px-4 py-3">Tipe</th>
                        <th class="px-4 py-3 text-right">Subtotal</th>
                        <th class="px-4 py-3 text-right">Diskon</th>
                        <th class="px-4 py-3 text-right">Pajak</th>
                        <th class="px-4 py-3 text-right">Total</th>
                        <th class="px-4 py-3 text-right">Dibayar</th>
                        <th class="px-4 py-3 text-right">Sisa</th>
                        <th class="px-4 py-3">Status</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($invoices as $inv)
                        <tr class="hover:bg-slate-50">
                            <td class="whitespace-nowrap px-4 py-3 text-xs">
                                {{ $inv->tanggal_invoice ? $inv->tanggal_invoice->format('d/m/Y') : '-' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 font-medium text-[#15235F]">
                                <a href="{{ route('invoice-penjualan.show', $inv) }}" class="hover:underline">
                                    {{ $inv->nomor_invoice }}
                                </a>
                            </td>
                            <td class="px-4 py-3 font-medium text-slate-800">
                                {{ $inv->customer?->nama_customer ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-xs text-slate-500">
                                {{ $inv->gudang?->nama_gudang ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-xs">
                                {{ $inv->tipe_pembayaran }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-mono text-xs">
                                Rp {{ number_format($inv->subtotal, 0, ',', '.') }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-mono text-xs">
                                Rp {{ number_format($inv->diskon, 0, ',', '.') }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-mono text-xs">
                                Rp {{ number_format($inv->pajak, 0, ',', '.') }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-mono text-xs font-semibold text-slate-900">
                                Rp {{ number_format($inv->total, 0, ',', '.') }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-mono text-xs text-emerald-600">
                                Rp {{ number_format($inv->sudah_dibayar, 0, ',', '.') }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-mono text-xs font-semibold text-red-500">
                                Rp {{ number_format($inv->sisa_piutang, 0, ',', '.') }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3">
                                @if($inv->status_pembayaran === 'Lunas')
                                    <span class="inline-flex rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700">Lunas</span>
                                @else
                                    <span class="inline-flex rounded-full bg-red-50 px-2 py-0.5 text-xs font-medium text-red-700">Belum Lunas</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="12" class="px-6 py-8 text-center text-slate-400">
                                Tidak ada data penjualan.<br>
                                <span class="text-xs">Coba ubah periode atau filter laporan.</span>
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
