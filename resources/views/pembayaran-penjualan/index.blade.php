@extends('layouts.app')

@section('title', 'Penerimaan Pembayaran Piutang')

@section('content')

<div class="space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-semibold text-slate-800">Penerimaan Pembayaran (Pelunasan Piutang)</h2>
            <p class="mt-1 text-sm text-slate-500">
                Pencatatan pembayaran dari customer untuk melunasi faktur piutang dagang.
            </p>
        </div>

        <a
            href="{{ route('pembayaran-penjualan.create') }}"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#15235F] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#101b4d]"
        >
            <span>+</span>
            Terima Pembayaran
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
        <form method="GET" action="{{ route('pembayaran-penjualan.index') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Pencarian</label>
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="No. Bayar, Referensi, Customer..."
                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm text-slate-700 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                >
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
            <h3 class="text-sm font-semibold text-slate-700">Daftar Penerimaan Pembayaran</h3>
            <p class="mt-1 text-xs text-slate-400">Total {{ $pembayarans->total() }} pembayaran tercatat.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-xs uppercase tracking-wide text-slate-400">
                        <th class="px-6 py-3 font-medium">No. Bukti Bayar</th>
                        <th class="px-6 py-3 font-medium">Tanggal</th>
                        <th class="px-6 py-3 font-medium">Customer</th>
                        <th class="px-6 py-3 font-medium">Rekening Masuk</th>
                        <th class="px-6 py-3 font-medium">Metode</th>
                        <th class="px-6 py-3 text-right font-medium">Total Dibayar</th>
                        <th class="px-6 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($pembayarans as $pembayaran)
                        <tr class="transition hover:bg-slate-50">
                            <td class="px-6 py-4 font-semibold text-[#15235F]">
                                <a href="{{ route('pembayaran-penjualan.show', $pembayaran) }}" class="hover:underline">
                                    {{ $pembayaran->nomor_pembayaran }}
                                </a>
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                {{ $pembayaran->tanggal_pembayaran ? $pembayaran->tanggal_pembayaran->format('d/m/Y') : '-' }}
                            </td>
                            <td class="px-6 py-4 font-medium text-slate-800">
                                {{ $pembayaran->customer->nama_customer ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-slate-700">
                                {{ $pembayaran->rekeningKasBank->nama_rekening ?? '-' }}
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex rounded-lg bg-teal-50 px-2.5 py-1 text-xs font-medium text-[#15235F]">
                                    {{ $pembayaran->metode_pembayaran }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-emerald-600">
                                Rp {{ number_format($pembayaran->total_bayar, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a
                                    href="{{ route('pembayaran-penjualan.show', $pembayaran) }}"
                                    class="rounded-lg bg-teal-50 px-3 py-1.5 text-xs font-medium text-[#15235F] transition hover:bg-teal-100"
                                >
                                    Detail
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-slate-400">
                                Belum ada data penerimaan pembayaran piutang.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($pembayarans->hasPages())
            <div class="border-t border-slate-200 px-6 py-4">
                {{ $pembayarans->links() }}
            </div>
        @endif
    </div>

</div>

@endsection
