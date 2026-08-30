@extends('layouts.app')

@section('title', 'Detail Retur ' . $returPenjualan->nomor_retur)

@section('content')

<div class="mx-auto max-w-5xl space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('retur-penjualan.index') }}" class="mb-2 inline-flex items-center gap-2 text-sm text-slate-500 hover:text-[#15235F]">
                ← Kembali ke Daftar Retur
            </a>
            <div class="flex items-center gap-3">
                <h2 class="text-xl font-semibold text-slate-800">
                    {{ $returPenjualan->nomor_retur }}
                </h2>
                <span class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                    {{ $returPenjualan->status }} (Stok Dikembalikan)
                </span>
            </div>
        </div>

        <button
            type="button"
            onclick="window.print()"
            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
        >
            🖨️ Cetak Nota Retur
        </button>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    {{-- CARD INFORMASI --}}
    <div class="grid grid-cols-1 gap-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm md:grid-cols-2">
        <div class="space-y-3">
            <div>
                <p class="text-xs font-medium uppercase text-slate-400">Customer</p>
                <p class="mt-1 text-base font-semibold text-slate-800">{{ $returPenjualan->customer->nama_customer ?? '-' }}</p>
                <p class="text-xs text-slate-500">{{ $returPenjualan->customer->alamat ?? '' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium uppercase text-slate-400">Faktur Asal</p>
                <a href="{{ route('invoice-penjualan.show', $returPenjualan->invoicePenjualan) }}" class="mt-1 inline-block text-sm font-semibold text-[#15235F] hover:underline">
                    {{ $returPenjualan->invoicePenjualan->nomor_invoice ?? '-' }}
                </a>
            </div>
            <div>
                <p class="text-xs font-medium uppercase text-slate-400">Gudang Penerima</p>
                <p class="mt-1 text-sm font-medium text-slate-700">{{ $returPenjualan->gudang->nama_gudang ?? '-' }}</p>
            </div>
        </div>

        <div class="space-y-3">
            <div>
                <p class="text-xs font-medium uppercase text-slate-400">Tanggal Retur</p>
                <p class="mt-1 text-sm font-semibold text-slate-700">{{ $returPenjualan->tanggal_retur ? $returPenjualan->tanggal_retur->format('d F Y') : '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium uppercase text-slate-400">Diproses Oleh</p>
                <p class="mt-1 text-sm text-slate-600">{{ $returPenjualan->creator->name ?? '-' }} ({{ $returPenjualan->created_at->format('d/m/Y H:i') }})</p>
            </div>
            @if($returPenjualan->alasan)
                <div>
                    <p class="text-xs font-medium uppercase text-slate-400">Alasan Retur</p>
                    <p class="mt-1 text-xs text-slate-600">{{ $returPenjualan->alasan }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- TABEL ITEM RETUR --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-4">
            <h3 class="text-sm font-semibold text-slate-700">Rincian Barang yang Diretur</h3>
        </div>

        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50">
                <tr class="text-xs uppercase tracking-wide text-slate-400">
                    <th class="px-6 py-3 font-medium">No</th>
                    <th class="px-6 py-3 font-medium">Kode & Nama Barang</th>
                    <th class="px-6 py-3 text-right font-medium">Qty Diretur</th>
                    <th class="px-6 py-3 text-right font-medium">Harga Satuan</th>
                    <th class="px-6 py-3 font-medium">Alasan Khusus</th>
                    <th class="px-6 py-3 text-right font-medium">Subtotal Retur</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($returPenjualan->details as $index => $detail)
                    <tr>
                        <td class="px-6 py-4 text-slate-500">{{ $index + 1 }}</td>
                        <td class="px-6 py-4">
                            <div class="font-medium text-slate-800">{{ $detail->barang->nama_barang ?? '-' }}</div>
                            <div class="text-xs text-slate-400">{{ $detail->barang->kode_barang ?? '' }}</div>
                        </td>
                        <td class="px-6 py-4 text-right font-medium text-slate-700">
                            {{ number_format($detail->qty, 0, ',', '.') }} {{ $detail->barang->satuan->nama_satuan ?? '' }}
                        </td>
                        <td class="px-6 py-4 text-right text-slate-700">
                            Rp {{ number_format($detail->harga_satuan, 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 text-slate-500 text-xs">
                            {{ $detail->alasan ?: '-' }}
                        </td>
                        <td class="px-6 py-4 text-right font-semibold text-rose-600">
                            Rp {{ number_format($detail->subtotal, 0, ',', '.') }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t border-slate-200 bg-slate-50 font-bold">
                    <td colspan="5" class="px-6 py-4 text-right text-slate-700">Total Nilai Retur:</td>
                    <td class="px-6 py-4 text-right text-base text-rose-600">
                        Rp {{ number_format($returPenjualan->total_retur, 0, ',', '.') }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

</div>

@endsection
