@extends('layouts.app')

@section('title', 'Nota Retur Penjualan')

@section('content')

<div class="space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-semibold text-slate-800">Nota Retur Penjualan</h2>
            <p class="mt-1 text-sm text-slate-500">
                Pencatatan pengembalian barang dari customer (Restock otomatis, Jurnal Retur, & Pengurangan PPN/Piutang).
            </p>
        </div>

        <a
            href="{{ route('retur-penjualan.create') }}"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#15235F] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#101b4d]"
        >
            + Buat Nota Retur Baru
        </a>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">
            <x-icon name="check" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" /> {{ session('success') }}
        </div>
    @endif

    {{-- TABLE --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-4">
            <h3 class="text-sm font-semibold text-slate-700">Daftar Nota Retur Penjualan</h3>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-400">
                    <tr>
                        <th class="px-6 py-3 font-medium">No. Retur</th>
                        <th class="px-6 py-3 font-medium">No. Invoice Asal</th>
                        <th class="px-6 py-3 font-medium">Tanggal Retur</th>
                        <th class="px-6 py-3 font-medium">Customer</th>
                        <th class="px-6 py-3 font-medium">Gudang Restock</th>
                        <th class="px-6 py-3 text-right font-medium">Subtotal</th>
                        <th class="px-6 py-3 text-right font-medium">PPN Retur</th>
                        <th class="px-6 py-3 text-right font-medium">Total Retur (Rp)</th>
                        <th class="px-6 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($returs as $r)
                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4 font-mono font-semibold text-[#15235F]">
                                <a href="{{ route('retur-penjualan.show', $r) }}" class="hover:underline">
                                    {{ $r->nomor_retur }}
                                </a>
                            </td>
                            <td class="px-6 py-4 font-mono font-medium text-slate-700">
                                @if($r->invoicePenjualan)
                                    <a href="{{ route('invoice-penjualan.show', $r->invoicePenjualan) }}" class="hover:underline hover:text-[#15235F]">
                                        {{ $r->invoicePenjualan->nomor_invoice }}
                                    </a>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                {{ $r->tanggal_retur ? $r->tanggal_retur->format('d/m/Y') : '-' }}
                            </td>
                            <td class="px-6 py-4 font-medium text-slate-800">
                                {{ $r->customer?->nama_customer }}
                            </td>
                            <td class="px-6 py-4 text-xs text-slate-500">
                                {{ $r->gudang?->nama_gudang }}
                            </td>
                            <td class="px-6 py-4 text-right font-mono text-xs text-slate-700">
                                Rp {{ number_format($r->subtotal, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-right font-mono text-xs text-rose-600">
                                Rp {{ number_format($r->pajak, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-right font-mono text-xs font-bold text-rose-600">
                                Rp {{ number_format($r->total, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-1.5">
                                    <a
                                        href="{{ route('retur-penjualan.show', $r) }}"
                                        class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50"
                                    >
                                        <x-icon name="eye" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" /> Detail
                                    </a>
                                    <a
                                        href="{{ route('retur-penjualan.pdf', ['returPenjualan' => $r, 'download' => 1]) }}"
                                        class="inline-flex items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 py-1 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                        title="Download Nota Retur PDF"
                                    >
                                        <x-icon name="download" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" /> Download
                                    </a>
                                    <a
                                        href="{{ route('retur-penjualan.pdf', $r) }}"
                                        target="_blank"
                                        class="inline-flex items-center gap-1 rounded-lg border border-rose-200 bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700 hover:bg-rose-100"
                                    >
                                        <x-icon name="printer" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" /> Cetak
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-8 text-center text-slate-400">
                                Belum ada transaksi retur penjualan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($returs->hasPages())
            <div class="border-t border-slate-200 px-6 py-4">
                {{ $returs->links() }}
            </div>
        @endif
    </div>

</div>

@endsection
