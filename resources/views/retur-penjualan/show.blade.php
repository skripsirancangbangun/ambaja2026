@extends('layouts.app')

@section('title', 'Nota Retur Penjualan ' . $returPenjualan->nomor_retur)

@section('content')

<div class="mx-auto max-w-5xl space-y-6">

    {{-- HEADER & ACTIONS --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('retur-penjualan.index') }}" class="mb-2 inline-flex items-center gap-2 text-sm text-slate-500 hover:text-[#15235F]">
                ← Kembali ke Daftar Nota Retur
            </a>
            <div class="flex items-center gap-3">
                <h2 class="text-xl font-semibold text-slate-800">
                    Nota Retur #{{ $returPenjualan->nomor_retur }}
                </h2>
                <span class="inline-flex rounded-full bg-rose-50 px-3 py-1 text-xs font-semibold text-rose-700">
                    Retur Penjualan
                </span>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a
                href="{{ route('retur-penjualan.pdf', $returPenjualan) }}"
                target="_blank"
                class="inline-flex items-center gap-2 rounded-xl bg-rose-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-rose-700 shadow-sm"
            >
                🖨️ Cetak Nota Retur PDF
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            ✓ {{ session('success') }}
        </div>
    @endif

    {{-- INFORMATION CARDS --}}
    <div class="grid grid-cols-1 gap-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm md:grid-cols-2">
        <div class="space-y-4">
            <div>
                <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Customer</p>
                <p class="mt-1 text-base font-semibold text-slate-800">{{ $returPenjualan->customer->nama_customer ?? '-' }}</p>
                <p class="text-xs text-slate-500">{{ $returPenjualan->customer->alamat ?? '' }}</p>
                <p class="text-xs text-slate-500">Telp: {{ $returPenjualan->customer->telepon ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Gudang Restock</p>
                <p class="mt-1 text-sm font-medium text-slate-700">{{ $returPenjualan->gudang->nama_gudang ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Invoice Asal</p>
                @if($returPenjualan->invoicePenjualan)
                    <a href="{{ route('invoice-penjualan.show', $returPenjualan->invoicePenjualan) }}" class="mt-1 inline-block text-sm font-semibold text-[#15235F] hover:underline">
                        {{ $returPenjualan->invoicePenjualan->nomor_invoice }}
                    </a>
                @else
                    <p class="mt-1 text-sm text-slate-600">-</p>
                @endif
            </div>
        </div>

        <div class="space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Tanggal Retur</p>
                    <p class="mt-1 text-sm font-semibold text-slate-700">{{ $returPenjualan->tanggal_retur ? $returPenjualan->tanggal_retur->format('d F Y') : '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Dibuat Oleh</p>
                    <p class="mt-1 text-sm font-medium text-slate-700">{{ $returPenjualan->creator->name ?? 'Admin' }}</p>
                </div>
            </div>

            <div>
                <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Alokasi Retur</p>
                <div class="mt-1 space-y-1 text-xs">
                    <p class="font-medium text-slate-700">
                        • Potong Piutang: <span class="font-bold text-slate-800">Rp {{ number_format($returPenjualan->potong_piutang, 0, ',', '.') }}</span>
                    </p>
                    <p class="font-medium text-slate-700">
                        • Pengembalian Kas/Bank: <span class="font-bold text-emerald-600">Rp {{ number_format($returPenjualan->pengembalian_kas, 0, ',', '.') }}</span>
                        @if($returPenjualan->rekeningKasBank)
                            ({{ $returPenjualan->rekeningKasBank->nama_rekening }})
                        @endif
                    </p>
                </div>
            </div>

            <div>
                <p class="text-xs font-medium uppercase tracking-wider text-slate-400">Alasan Retur</p>
                <p class="mt-1 rounded-xl bg-amber-50 p-3 text-xs font-medium text-amber-900 border border-amber-200/60">
                    {{ $returPenjualan->alasan ?? $returPenjualan->alasan_retur ?? '-' }}
                </p>
            </div>
        </div>
    </div>

    {{-- TABEL ITEM RETUR --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-4">
            <h3 class="text-sm font-semibold text-slate-700">Daftar Barang yang Dikambahkan (Restock Automatic)</h3>
        </div>

        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50">
                <tr class="text-xs uppercase tracking-wider text-slate-400">
                    <th class="px-6 py-3 font-medium">No</th>
                    <th class="px-6 py-3 font-medium">Kode & Nama Barang</th>
                    <th class="px-6 py-3 text-right font-medium">Qty Retur</th>
                    <th class="px-6 py-3 text-right font-medium">Harga Satuan</th>
                    <th class="px-6 py-3 text-right font-medium">Subtotal Retur</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($returPenjualan->items as $index => $item)
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4 text-slate-500">{{ $index + 1 }}</td>
                        <td class="px-6 py-4">
                            <div class="font-medium text-slate-800">{{ $item->barang->nama_barang ?? '-' }}</div>
                            <div class="text-xs text-slate-400">{{ $item->barang->kode_barang ?? '' }}</div>
                        </td>
                        <td class="px-6 py-4 text-right font-bold text-rose-600">
                            {{ number_format($item->qty, 0, ',', '.') }} {{ $item->barang->satuan->nama_satuan ?? '' }}
                        </td>
                        <td class="px-6 py-4 text-right text-slate-700 font-mono text-xs">
                            Rp {{ number_format($item->harga_satuan, 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 text-right font-bold text-slate-800 font-mono text-xs">
                            Rp {{ number_format($item->subtotal, 0, ',', '.') }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        {{-- SUMMARY FOOTER --}}
        <div class="border-t border-slate-200 bg-slate-50/50 p-6">
            <div class="ml-auto max-w-xs space-y-2 text-sm">
                <div class="flex justify-between text-slate-500">
                    <span>Subtotal Barang Retur:</span>
                    <span class="font-medium text-slate-800">Rp {{ number_format($returPenjualan->subtotal, 0, ',', '.') }}</span>
                </div>
                @if($returPenjualan->pajak > 0)
                    <div class="flex justify-between text-slate-500">
                        <span>PPN Retur:</span>
                        <span class="font-medium text-rose-600">Rp {{ number_format($returPenjualan->pajak, 0, ',', '.') }}</span>
                    </div>
                @endif
                <div class="flex justify-between border-t border-slate-200 pt-2 text-base font-bold text-rose-600">
                    <span>Total Retur Penjualan:</span>
                    <span>Rp {{ number_format($returPenjualan->total, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection
