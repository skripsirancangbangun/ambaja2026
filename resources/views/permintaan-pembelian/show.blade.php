@extends('layouts.app')

@section('title', 'Detail Permintaan Pembelian')

@section('content')
@php
    $userRole = auth()->user()->role;
    $isPurchasing = in_array($userRole, ['Pembelian', 'Admin', 'Owner'], true);
    $statusClass = [
        'DIAJUKAN' => 'bg-amber-100 text-amber-800',
        'DISETUJUI' => 'bg-emerald-100 text-emerald-800',
        'DITOLAK' => 'bg-rose-100 text-rose-800',
        'PO_DIBUAT' => 'bg-blue-100 text-blue-800',
        'SELESAI' => 'bg-slate-100 text-slate-700',
    ];
@endphp

<div class="mx-auto max-w-6xl space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('permintaan-pembelian.index') }}" class="text-sm font-medium text-teal-700 hover:text-teal-900">← Kembali ke daftar</a>
            <h2 class="mt-3 text-xl font-semibold text-slate-800">{{ $permintaanPembelian->nomor_permintaan }}</h2>
            <p class="mt-1 text-sm text-slate-500">Diajukan {{ $permintaanPembelian->tanggal_permintaan?->format('d/m/Y') ?? '-' }} oleh {{ $permintaanPembelian->creator?->name ?? 'Pengguna tidak tersedia' }}.</p>
        </div>
        <span class="inline-flex w-fit rounded-full px-3 py-1.5 text-xs font-bold {{ $statusClass[$permintaanPembelian->status] ?? 'bg-slate-100 text-slate-700' }}">{{ str_replace('_', ' ', $permintaanPembelian->status) }}</span>
    </div>

    @if(session('success'))<div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>@endif
    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700"><ul class="list-disc space-y-1 pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
    @endif

    <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
        <div class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">Diajukan oleh</p><p class="mt-1 font-semibold text-slate-800">{{ $permintaanPembelian->creator?->name ?? '-' }}</p></div>
        <div class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">Diproses oleh</p><p class="mt-1 font-semibold text-slate-800">{{ $permintaanPembelian->reviewer?->name ?? '-' }}</p></div>
        <div class="rounded-xl border border-slate-200 bg-white p-4"><p class="text-xs text-slate-500">Tanggal diproses</p><p class="mt-1 font-semibold text-slate-800">{{ $permintaanPembelian->reviewed_at?->format('d/m/Y H:i') ?? '-' }}</p></div>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-100 px-5 py-4"><h3 class="font-semibold text-slate-800">Detail barang yang diminta</h3><p class="mt-1 text-xs text-slate-500">Stok pada saat pengajuan merupakan snapshot; tidak mengubah saldo persediaan.</p></div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-3">Barang</th><th class="px-5 py-3 text-right">Stok saat diajukan</th><th class="px-5 py-3 text-right">Minimum</th><th class="px-5 py-3 text-right">Diminta</th><th class="px-5 py-3 text-right">Disetujui</th></tr></thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($permintaanPembelian->details as $detail)
                        <tr>
                            <td class="px-5 py-4"><p class="font-semibold text-slate-800">{{ $detail->barang?->nama_barang ?? '-' }}</p><p class="mt-1 text-xs text-slate-500">{{ $detail->barang?->kode_barang ?? '-' }} · {{ $detail->barang?->satuan?->nama_satuan ?? '-' }}</p></td>
                            <td class="whitespace-nowrap px-5 py-4 text-right text-slate-600">{{ number_format((float) $detail->stok_saat_pengajuan, 2, ',', '.') }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-right text-slate-600">{{ number_format((float) $detail->minimum_stok, 2, ',', '.') }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-right font-semibold text-slate-800">{{ number_format((float) $detail->qty_diminta, 2, ',', '.') }}</td>
                            <td class="whitespace-nowrap px-5 py-4 text-right font-semibold text-emerald-700">{{ $detail->qty_disetujui === null ? '-' : number_format((float) $detail->qty_disetujui, 2, ',', '.') }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($permintaanPembelian->catatan)<div class="border-t border-slate-100 bg-slate-50 px-5 py-4 text-sm"><strong>Catatan Gudang:</strong> {{ $permintaanPembelian->catatan }}</div>@endif
        @if($permintaanPembelian->alasan_penolakan)<div class="border-t border-rose-100 bg-rose-50 px-5 py-4 text-sm text-rose-800"><strong>Alasan penolakan:</strong> {{ $permintaanPembelian->alasan_penolakan }}</div>@endif
    </div>

    @if($isPurchasing && $permintaanPembelian->status === 'DIAJUKAN')
        <div class="grid grid-cols-1 gap-5 xl:grid-cols-2">
            <form method="POST" action="{{ route('permintaan-pembelian.approve', $permintaanPembelian) }}" class="rounded-2xl border border-emerald-200 bg-white p-5 shadow-sm">
                @csrf @method('PATCH')
                <h3 class="font-semibold text-emerald-800">Setujui permintaan</h3>
                <p class="mt-1 text-xs text-slate-500">Jumlah disetujui boleh lebih kecil dari jumlah yang diminta, tetapi tidak boleh lebih besar. Isi 0 jika item tidak disetujui.</p>
                <div class="mt-4 space-y-3">
                    @foreach($permintaanPembelian->details as $detail)
                        <label class="block rounded-lg border border-slate-100 p-3">
                            <span class="block text-sm font-medium text-slate-800">{{ $detail->barang?->nama_barang }} <span class="text-xs text-slate-500">(diminta {{ number_format((float) $detail->qty_diminta, 2, ',', '.') }})</span></span>
                            <input type="number" name="items[{{ $detail->id }}][qty_disetujui]" min="0" max="{{ $detail->qty_diminta }}" step="0.01" value="{{ old("items.{$detail->id}.qty_disetujui", $detail->qty_diminta) }}" required class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-emerald-500 focus:outline-none focus:ring-2 focus:ring-emerald-100">
                        </label>
                    @endforeach
                </div>
                <button type="submit" onclick="return confirm('Setujui permintaan ini?')" class="mt-4 w-full rounded-lg bg-emerald-700 px-4 py-2.5 text-sm font-semibold text-white hover:bg-emerald-800">Setujui Permintaan</button>
            </form>

            <form method="POST" action="{{ route('permintaan-pembelian.reject', $permintaanPembelian) }}" class="rounded-2xl border border-rose-200 bg-white p-5 shadow-sm">
                @csrf @method('PATCH')
                <h3 class="font-semibold text-rose-800">Tolak permintaan</h3>
                <p class="mt-1 text-xs text-slate-500">Alasan penolakan akan dikirim sebagai notifikasi kepada Gudang.</p>
                <label for="alasan_penolakan" class="mt-4 block text-sm font-medium text-slate-700">Alasan penolakan</label>
                <textarea id="alasan_penolakan" name="alasan_penolakan" rows="5" required minlength="5" maxlength="2000" class="mt-2 w-full rounded-lg border border-slate-300 px-3 py-2 text-sm focus:border-rose-500 focus:outline-none focus:ring-2 focus:ring-rose-100">{{ old('alasan_penolakan') }}</textarea>
                <button type="submit" onclick="return confirm('Tolak permintaan ini?')" class="mt-4 w-full rounded-lg border border-rose-300 bg-rose-50 px-4 py-2.5 text-sm font-semibold text-rose-700 hover:bg-rose-100">Tolak Permintaan</button>
            </form>
        </div>
    @endif

    @if($isPurchasing && $permintaanPembelian->status === 'DISETUJUI')
        <div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-5">
            <h3 class="font-semibold text-emerald-900">Permintaan telah disetujui</h3>
            <p class="mt-1 text-sm text-emerald-800">Lanjutkan dengan memilih supplier dan menetapkan harga pada formulir Purchase Order.</p>
            <a href="{{ route('purchase-order.create', ['permintaan_pembelian_id' => $permintaanPembelian->id]) }}" class="mt-4 inline-flex rounded-lg bg-[#15235F] px-4 py-2.5 text-sm font-semibold text-white hover:bg-[#0F8B8D]">Buat Purchase Order</a>
        </div>
    @endif

    @if($permintaanPembelian->purchaseOrder)
        <div class="rounded-2xl border border-blue-200 bg-blue-50 p-5">
            <h3 class="font-semibold text-blue-900">Purchase Order sudah dibuat</h3>
            <p class="mt-1 text-sm text-blue-800">Nomor PO: {{ $permintaanPembelian->purchaseOrder->nomor_po }} · Status: {{ $permintaanPembelian->purchaseOrder->status }}</p>
            <a href="{{ route('purchase-order.show', $permintaanPembelian->purchaseOrder) }}" class="mt-3 inline-flex font-semibold text-blue-800 underline">Lihat Purchase Order</a>
        </div>
    @endif
</div>
@endsection
