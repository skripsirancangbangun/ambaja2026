@extends('layouts.app')

@section('title', 'Detail Sales Order ' . $penjualan->nomor_penjualan)

@section('content')

<div class="mx-auto max-w-5xl space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('penjualan.index') }}" class="mb-2 inline-flex items-center gap-2 text-sm text-slate-500 hover:text-[#15235F]">
                ← Kembali ke Daftar
            </a>
            <div class="flex items-center gap-3">
                <h2 class="text-xl font-semibold text-slate-800">
                    {{ $penjualan->nomor_penjualan }}
                </h2>
                @php
                    $badges = [
                        'Draft' => 'bg-slate-100 text-slate-600',
                        'Dikonfirmasi' => 'bg-blue-50 text-blue-700',
                        'Diproses' => 'bg-amber-50 text-amber-700',
                        'Selesai' => 'bg-emerald-50 text-emerald-700',
                        'Dibatalkan' => 'bg-rose-50 text-rose-700',
                    ];
                @endphp
                <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold {{ $badges[$penjualan->status] ?? 'bg-slate-100 text-slate-600' }}">
                    {{ $penjualan->status }}
                </span>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a
                href="{{ route('penjualan.pdf', $penjualan) }}"
                target="_blank"
                class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
            >
                🖨️ Cetak PDF
            </a>
            @if(!in_array($penjualan->status, ['Selesai', 'Dibatalkan']))
                <a
                    href="{{ route('penjualan.edit', $penjualan) }}"
                    class="rounded-xl bg-teal-50 px-4 py-2 text-sm font-medium text-[#15235F] transition hover:bg-teal-100"
                >
                    Edit
                </a>
                <a
                    href="{{ route('invoice-penjualan.create', ['penjualan_id' => $penjualan->id]) }}"
                    class="rounded-xl bg-[#15235F] px-4 py-2 text-sm font-medium text-white transition hover:bg-[#101b4d]"
                >
                    Terbitkan Invoice
                </a>
            @endif
        </div>
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
                <p class="mt-1 text-base font-semibold text-slate-800">{{ $penjualan->customer->nama_customer ?? '-' }}</p>
                <p class="text-xs text-slate-500">{{ $penjualan->customer->alamat ?? '' }}</p>
                <p class="text-xs text-slate-500">Telp: {{ $penjualan->customer->telepon ?? '-' }}</p>
            </div>
            <div>
                <p class="text-xs font-medium uppercase text-slate-400">Gudang Pengeluaran</p>
                <p class="mt-1 text-sm font-medium text-slate-700">{{ $penjualan->gudang->nama_gudang ?? 'Semua Gudang' }}</p>
            </div>
        </div>

        <div class="space-y-3">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p class="text-xs font-medium uppercase text-slate-400">Tanggal Pesanan</p>
                    <p class="mt-1 text-sm font-semibold text-slate-700">{{ $penjualan->tanggal_penjualan ? $penjualan->tanggal_penjualan->format('d F Y') : '-' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase text-slate-400">Estimasi Kirim</p>
                    <p class="mt-1 text-sm font-semibold text-slate-700">{{ $penjualan->tanggal_estimasi_kirim ? $penjualan->tanggal_estimasi_kirim->format('d F Y') : '-' }}</p>
                </div>
            </div>
            <div>
                <p class="text-xs font-medium uppercase text-slate-400">Dibuat Oleh</p>
                <p class="mt-1 text-sm text-slate-600">{{ $penjualan->creator->name ?? '-' }} ({{ $penjualan->created_at->format('d/m/Y H:i') }})</p>
            </div>
            @if($penjualan->catatan)
                <div>
                    <p class="text-xs font-medium uppercase text-slate-400">Catatan</p>
                    <p class="mt-1 text-xs text-slate-600">{{ $penjualan->catatan }}</p>
                </div>
            @endif
        </div>
    </div>

    {{-- TABEL ITEM --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-4">
            <h3 class="text-sm font-semibold text-slate-700">Rincian Barang</h3>
        </div>

        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50">
                <tr class="text-xs uppercase tracking-wide text-slate-400">
                    <th class="px-6 py-3 font-medium">No</th>
                    <th class="px-6 py-3 font-medium">Kode & Nama Barang</th>
                    <th class="px-6 py-3 text-right font-medium">Qty</th>
                    <th class="px-6 py-3 text-right font-medium">Harga Satuan</th>
                    <th class="px-6 py-3 text-right font-medium">Diskon</th>
                    <th class="px-6 py-3 text-right font-medium">Subtotal</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($penjualan->items as $index => $item)
                    <tr>
                        <td class="px-6 py-4 text-slate-500">{{ $index + 1 }}</td>
                        <td class="px-6 py-4">
                            <div class="font-medium text-slate-800">{{ $item->barang->nama_barang ?? '-' }}</div>
                            <div class="text-xs text-slate-400">{{ $item->barang->kode_barang ?? '' }}</div>
                        </td>
                        <td class="px-6 py-4 text-right font-medium text-slate-700">
                            {{ number_format($item->qty, 0, ',', '.') }} {{ $item->barang->satuan->nama_satuan ?? '' }}
                        </td>
                        <td class="px-6 py-4 text-right text-slate-700">
                            Rp {{ number_format($item->harga_satuan, 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 text-right text-slate-700">
                            Rp {{ number_format($item->diskon, 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 text-right font-semibold text-slate-800">
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
                    <span>Subtotal:</span>
                    <span class="font-medium text-slate-800">Rp {{ number_format($penjualan->subtotal, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-slate-500">
                    <span>Diskon:</span>
                    <span class="font-medium text-slate-800">Rp {{ number_format($penjualan->diskon, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between text-slate-500">
                    <span>PPN ({{ $penjualan->pajak_persen }}%):</span>
                    <span class="font-medium text-slate-800">Rp {{ number_format($penjualan->pajak, 0, ',', '.') }}</span>
                </div>
                <div class="flex justify-between border-t border-slate-200 pt-2 text-base font-bold text-[#15235F]">
                    <span>Total:</span>
                    <span>Rp {{ number_format($penjualan->total, 0, ',', '.') }}</span>
                </div>
            </div>
        </div>
    </div>

</div>

@endsection
