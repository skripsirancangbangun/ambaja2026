@extends('layouts.app')

@section('title', 'Barang Supplier')

@section('content')
<div class="mx-auto max-w-6xl space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <a href="{{ route('supplier.index') }}" class="mb-3 inline-flex items-center gap-2 text-sm text-slate-500 hover:text-[#15235F]">
                <x-icon name="arrow-left" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" /> Kembali ke Supplier
            </a>
            <h2 class="text-xl font-semibold text-slate-800">Daftar Barang Supplier</h2>
            <p class="mt-1 text-sm text-slate-500">
                {{ $supplier->kode_supplier }} - {{ $supplier->nama_supplier }}
            </p>
        </div>
        <div class="rounded-xl border border-teal-100 bg-teal-50 px-4 py-3 text-sm text-teal-800">
            <span class="font-semibold">{{ count($assignedBarangIds) }}</span> barang terdaftar pada supplier ini.
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">{{ session('error') }}</div>
    @endif

    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('supplier.barang.index', $supplier) }}" class="flex flex-col gap-3 sm:flex-row">
            <div class="relative flex-1">
                <x-icon name="search" class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" />
                <input
                    type="search"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Cari kode, nama, spesifikasi, atau merek barang..."
                    class="w-full rounded-lg border border-slate-300 py-2.5 pl-10 pr-3 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                >
            </div>
            <button type="submit" class="rounded-lg bg-[#15235F] px-5 py-2.5 text-sm font-medium text-white hover:bg-[#101b4d]">Cari Barang</button>
            @if(request()->filled('search'))
                <a href="{{ route('supplier.barang.index', $supplier) }}" class="rounded-lg border border-slate-300 px-5 py-2.5 text-center text-sm font-medium text-slate-600 hover:bg-slate-50">Reset</a>
            @endif
        </form>
        <p class="mt-3 text-xs text-slate-400">Centang barang yang memang dapat dipasok oleh supplier ini. Relasi ini akan menjadi dasar filter barang pada Purchase Order.</p>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-xs uppercase tracking-wide text-slate-400">
                        <th class="px-5 py-3 font-medium">Kode Barang</th>
                        <th class="px-5 py-3 font-medium">Nama Barang</th>
                        <th class="px-5 py-3 font-medium">Spesifikasi / Merek</th>
                        <th class="px-5 py-3 font-medium">Satuan</th>
                        <th class="px-5 py-3 font-medium">Status Relasi</th>
                        <th class="px-5 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($barangs as $barang)
                        @php($isAssigned = in_array((int) $barang->id, $assignedBarangIds, true))
                        <tr class="hover:bg-slate-50">
                            <td class="whitespace-nowrap px-5 py-4 font-semibold text-[#15235F]">{{ $barang->kode_barang }}</td>
                            <td class="px-5 py-4 font-medium text-slate-700">
                                {{ $barang->nama_barang }}
                                @if(!$barang->is_active)
                                    <span class="ml-1 rounded-full bg-slate-100 px-2 py-0.5 text-[10px] font-medium text-slate-500">Nonaktif</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-slate-500">
                                {{ collect([$barang->spesifikasi, $barang->merek])->filter()->implode(' / ') ?: '-' }}
                            </td>
                            <td class="px-5 py-4 text-slate-500">{{ $barang->satuan?->nama_satuan ?? '-' }}</td>
                            <td class="px-5 py-4">
                                @if($isAssigned)
                                    <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">Terdaftar</span>
                                @else
                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-500">Belum terdaftar</span>
                                @endif
                            </td>
                            <td class="px-5 py-4 text-right">
                                @if($isAssigned)
                                    <form method="POST" action="{{ route('supplier.barang.detach', [$supplier, $barang]) }}" onsubmit="return confirm('Keluarkan barang ini dari daftar supplier? Transaksi PO yang sudah dibuat tidak dihapus.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg border border-red-200 px-3 py-2 text-xs font-medium text-red-600 hover:bg-red-50">Keluarkan</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('supplier.barang.attach', [$supplier, $barang]) }}">
                                        @csrf
                                        <button type="submit" class="rounded-lg bg-teal-50 px-3 py-2 text-xs font-semibold text-[#15235F] hover:bg-teal-100">+ Tambahkan</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center text-sm text-slate-500">Barang tidak ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($barangs->hasPages())
            <div class="border-t border-slate-200 px-5 py-4">{{ $barangs->links() }}</div>
        @endif
    </div>
</div>
@endsection
