@extends('layouts.app')

@section('title', 'Daftar Penerimaan Piutang')

@section('content')

<div class="space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-semibold text-slate-800">Daftar Penerimaan Piutang Customer</h2>
            <p class="mt-1 text-sm text-slate-500">
                Riwayat pembayaran dan pelunasan faktur penjualan kredit.
            </p>
        </div>

        <div class="flex gap-2">
            <a
                href="{{ route('piutang.kartu') }}"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-medium text-slate-700 hover:bg-slate-50"
            >
                📖 Kartu Piutang
            </a>
            <a
                href="{{ route('piutang.penerimaan.create') }}"
                class="rounded-xl bg-[#15235F] px-4 py-2.5 text-sm font-medium text-white hover:bg-[#101b4d]"
            >
                + Penerimaan Piutang Baru
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
            ✓ {{ session('success') }}
        </div>
    @endif

    {{-- TABLE --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-4">
            <h3 class="text-sm font-semibold text-slate-700">Riwayat Kwitansi / Penerimaan Kas</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-400">
                    <tr>
                        <th class="px-6 py-3 font-medium">No. Kwitansi</th>
                        <th class="px-6 py-3 font-medium">Tanggal</th>
                        <th class="px-6 py-3 font-medium">Customer</th>
                        <th class="px-6 py-3 font-medium">Rekening Kas/Bank</th>
                        <th class="px-6 py-3 text-right font-medium">Total Dibayar (Rp)</th>
                        <th class="px-6 py-3 font-medium">Catatan</th>
                        <th class="px-6 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($penerimaans as $p)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4 font-mono font-semibold text-[#15235F]">
                                {{ $p->nomor_penerimaan }}
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                {{ $p->tanggal ? $p->tanggal->format('d/m/Y') : '-' }}
                            </td>
                            <td class="px-6 py-4 font-medium text-slate-800">
                                {{ $p->customer?->nama_customer ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                {{ $p->rekeningKasBank?->nama_rekening ?? '-' }}
                            </td>
                            <td class="px-6 py-4 text-right font-bold text-emerald-600">
                                Rp {{ number_format($p->total_dibayar, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-500 max-w-xs">
                                {{ $p->catatan ?: '-' }}
                            </td>
                            <td class="px-6 py-4 text-right">
                                <a
                                    href="{{ route('piutang.kwitansi.pdf', $p) }}"
                                    target="_blank"
                                    class="inline-flex items-center gap-1 rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-100"
                                >
                                    🖨️ Cetak Kwitansi
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-8 text-center text-slate-400">
                                Belum ada riwayat penerimaan piutang.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($penerimaans->hasPages())
            <div class="border-t border-slate-200 px-6 py-4">
                {{ $penerimaans->links() }}
            </div>
        @endif
    </div>

</div>

@endsection
