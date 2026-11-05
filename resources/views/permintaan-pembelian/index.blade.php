@extends('layouts.app')

@section('title', 'Permintaan Pembelian')

@section('content')
@php
    $isGudang = auth()->user()->role === 'Gudang';
    $statusClass = [
        'DIAJUKAN' => 'bg-amber-100 text-amber-800',
        'DISETUJUI' => 'bg-emerald-100 text-emerald-800',
        'DITOLAK' => 'bg-rose-100 text-rose-800',
        'PO_DIBUAT' => 'bg-blue-100 text-blue-800',
        'SELESAI' => 'bg-slate-100 text-slate-700',
    ];
@endphp

<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-semibold text-slate-800">{{ $isGudang ? 'Riwayat Permintaan Pembelian' : 'Permintaan dari Gudang' }}</h2>
            <p class="mt-1 text-sm text-slate-500">Permintaan pembelian berdasarkan monitoring stok gabungan seluruh gudang.</p>
        </div>
        @if($isGudang)
            <a href="{{ route('permintaan-pembelian.create') }}" class="inline-flex items-center justify-center rounded-lg bg-[#15235F] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#0F8B8D]">+ Ajukan Permintaan</a>
        @endif
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ session('error') }}</div>
    @endif

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Menunggu pemeriksaan</p>
            <p class="mt-2 text-2xl font-bold text-amber-900">{{ number_format($pendingCount, 0, ',', '.') }}</p>
        </div>
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">Disetujui</p>
            <p class="mt-2 text-2xl font-bold text-emerald-900">{{ number_format($approvedCount, 0, ',', '.') }}</p>
        </div>
        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-blue-700">PO dibuat</p>
            <p class="mt-2 text-2xl font-bold text-blue-900">{{ number_format($poCreatedCount, 0, ',', '.') }}</p>
        </div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4">
            <h3 class="font-semibold text-slate-800">Daftar permintaan</h3>
            <p class="mt-1 text-xs text-slate-500">{{ $requests->total() }} permintaan tercatat.</p>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                    <tr>
                        <th class="px-5 py-3">Nomor</th>
                        <th class="px-5 py-3">Tanggal</th>
                        @unless($isGudang)<th class="px-5 py-3">Diajukan oleh</th>@endunless
                        <th class="px-5 py-3 text-center">Jumlah barang</th>
                        <th class="px-5 py-3">Status</th>
                        <th class="px-5 py-3">PO</th>
                        <th class="px-5 py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($requests as $item)
                        <tr class="hover:bg-slate-50">
                            <td class="whitespace-nowrap px-5 py-4 font-semibold text-[#15235F]">{{ $item->nomor_permintaan }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $item->tanggal_permintaan?->format('d/m/Y') ?? '-' }}</td>
                            @unless($isGudang)<td class="px-5 py-4 text-slate-700">{{ $item->creator?->name ?? '-' }}</td>@endunless
                            <td class="px-5 py-4 text-center text-slate-700">{{ number_format($item->details_count, 0, ',', '.') }}</td>
                            <td class="px-5 py-4"><span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass[$item->status] ?? 'bg-slate-100 text-slate-700' }}">{{ str_replace('_', ' ', $item->status) }}</span></td>
                            <td class="whitespace-nowrap px-5 py-4 text-slate-600">{{ $item->purchaseOrder?->nomor_po ?? '-' }}</td>
                            <td class="px-5 py-4 text-right"><a href="{{ route('permintaan-pembelian.show', $item) }}" class="font-semibold text-teal-700 hover:text-teal-900">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="{{ $isGudang ? 6 : 7 }}" class="px-5 py-10 text-center text-slate-500">Belum ada permintaan pembelian.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($requests->hasPages())<div class="border-t border-slate-100 px-5 py-4">{{ $requests->links() }}</div>@endif
    </div>
</div>
@endsection
