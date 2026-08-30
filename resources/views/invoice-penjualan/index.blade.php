@extends('layouts.app')

@section('title', 'Invoice Penjualan')

@section('content')

<div class="space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-semibold text-slate-800">Invoice Penjualan (Faktur)</h2>
            <p class="mt-1 text-sm text-slate-500">
                Penerbitan faktur penjualan tunai & kredit serta monitoring status pembayaran.
            </p>
        </div>

        <a
            href="{{ route('invoice-penjualan.create') }}"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#15235F] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#101b4d]"
        >
            <span>+</span>
            Buat Invoice Penjualan
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
        <form method="GET" action="{{ route('invoice-penjualan.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
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
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Tipe Pembayaran</label>
                <select
                    name="tipe_pembayaran"
                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm text-slate-700 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                >
                    <option value="">Semua Tipe</option>
                    <option value="Tunai" {{ request('tipe_pembayaran') == 'Tunai' ? 'selected' : '' }}>Tunai (Cash)</option>
                    <option value="Kredit" {{ request('tipe_pembayaran') == 'Kredit' ? 'selected' : '' }}>Kredit (Tempo)</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Status Pembayaran</label>
                <select
                    name="status_pembayaran"
                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm text-slate-700 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                >
                    <option value="">Semua Status</option>
                    <option value="Belum Lunas" {{ request('status_pembayaran') == 'Belum Lunas' ? 'selected' : '' }}>Belum Lunas</option>
                    <option value="Sebagian" {{ request('status_pembayaran') == 'Sebagian' ? 'selected' : '' }}>Sebagian</option>
                    <option value="Lunas" {{ request('status_pembayaran') == 'Lunas' ? 'selected' : '' }}>Lunas</option>
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
            <h3 class="text-sm font-semibold text-slate-700">Daftar Faktur Penjualan</h3>
            <p class="mt-1 text-xs text-slate-400">Total {{ $invoices->total() }} invoice.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-xs uppercase tracking-wide text-slate-400">
                        <th class="px-6 py-3 font-medium">No. Invoice</th>
                        <th class="px-6 py-3 font-medium">Tanggal</th>
                        <th class="px-6 py-3 font-medium">Customer</th>
                        <th class="px-6 py-3 font-medium">Tipe</th>
                        <th class="px-6 py-3 text-right font-medium">Total Nilai</th>
                        <th class="px-6 py-3 text-right font-medium">Sisa Piutang</th>
                        <th class="px-6 py-3 font-medium">Status Bayar</th>
                        <th class="px-6 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($invoices as $invoice)
                        <tr class="transition hover:bg-slate-50">
                            <td class="px-6 py-4 font-semibold text-[#15235F]">
                                <a href="{{ route('invoice-penjualan.show', $invoice) }}" class="hover:underline">
                                    {{ $invoice->nomor_invoice }}
                                </a>
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                {{ $invoice->tanggal_invoice ? $invoice->tanggal_invoice->format('d/m/Y') : '-' }}
                            </td>
                            <td class="px-6 py-4 font-medium text-slate-800">
                                {{ $invoice->customer->nama_customer ?? '-' }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex rounded-lg px-2.5 py-1 text-xs font-medium {{ $invoice->tipe_pembayaran == 'Tunai' ? 'bg-teal-50 text-[#32AAA6]' : 'bg-slate-100 text-slate-700' }}">
                                    {{ $invoice->tipe_pembayaran }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right font-semibold text-slate-800">
                                Rp {{ number_format($invoice->total, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-right font-medium {{ $invoice->sisa_piutang > 0 ? 'text-rose-600' : 'text-slate-400' }}">
                                Rp {{ number_format($invoice->sisa_piutang, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4">
                                @if($invoice->status_pembayaran == 'Lunas')
                                    <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                        Lunas
                                    </span>
                                @elseif($invoice->status_pembayaran == 'Sebagian')
                                    <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700">
                                        Sebagian
                                    </span>
                                @else
                                    <span class="inline-flex rounded-full bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-700">
                                        Belum Lunas
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a
                                        href="{{ route('invoice-penjualan.show', $invoice) }}"
                                        class="rounded-lg bg-teal-50 px-3 py-1.5 text-xs font-medium text-[#15235F] transition hover:bg-teal-100"
                                    >
                                        Detail
                                    </a>
                                    <a
                                        href="{{ route('invoice-penjualan.pdf', $invoice) }}"
                                        target="_blank"
                                        class="rounded-lg border border-slate-200 px-3 py-1.5 text-xs font-medium text-slate-600 transition hover:bg-slate-50"
                                    >
                                        PDF
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-slate-400">
                                Belum ada data invoice penjualan.
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
