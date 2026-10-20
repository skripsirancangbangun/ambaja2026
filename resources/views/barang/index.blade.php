@extends('layouts.app')

@section('title', 'Barang')

@section('content')

<div class="space-y-6">

    {{-- HEADER --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-semibold text-slate-800">Barang</h2>
            <p class="mt-1 text-sm text-slate-500">
                Kelola data barang yang digunakan dalam transaksi penjualan, pembelian, dan persediaan.
            </p>
        </div>

        {{-- BUTTON ACTION --}}
        <div class="flex items-center gap-3">
            {{-- IMPORT EXCEL --}}
            <a
                href="{{ route('barang.import.create') }}"
                class="inline-flex items-center justify-center gap-2 rounded-lg border border-[#15235F] bg-white px-4 py-2.5 text-sm font-medium text-[#15235F] transition hover:bg-slate-50"
            >
                <x-icon name="upload" class="h-4 w-4" />
                Import Excel
            </a>

            {{-- TAMBAH BARANG --}}
            <a
                href="{{ route('barang.create') }}"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#15235F] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#101b4d]"
            >
                <span class="text-lg leading-none">+</span>
                Tambah Barang
            </a>
        </div>
    </div>

    {{-- ALERT SUCCESS --}}
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    {{-- ALERT ERROR --}}
    @if(session('error'))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    {{-- VALIDATION ERRORS --}}
    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ $errors->first() }}
        </div>
    @endif

    {{-- SEARCH & FILTER --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('barang.index') }}" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                {{-- SEARCH --}}
                <div class="md:col-span-2">
                    <label for="q" class="mb-1.5 block text-xs font-medium text-slate-600">
                        Cari Barang
                    </label>
                    <input
                        type="search"
                        id="q"
                        name="q"
                        value="{{ request('q') }}"
                        placeholder="Kode, nama, spesifikasi, atau merek..."
                        maxlength="100"
                        class="w-full rounded-lg border border-slate-200 px-3 py-2.5 text-sm text-slate-700 outline-none focus:border-[#15235F] focus:ring-1 focus:ring-[#15235F]"
                    >
                </div>

                {{-- FILTER KATEGORI --}}
                <div>
                    <label for="kategori_barang_id" class="mb-1.5 block text-xs font-medium text-slate-600">
                        Kategori
                    </label>
                    <select
                        id="kategori_barang_id"
                        name="kategori_barang_id"
                        class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700"
                    >
                        <option value="">Semua Kategori</option>
                        @foreach($kategoris as $kategori)
                            <option
                                value="{{ $kategori->id }}"
                                {{ (string) request('kategori_barang_id') === (string) $kategori->id ? 'selected' : '' }}
                            >
                                {{ $kategori->nama_kategori }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- FILTER SATUAN --}}
                <div>
                    <label for="satuan_id" class="mb-1.5 block text-xs font-medium text-slate-600">
                        Satuan
                    </label>
                    <select
                        id="satuan_id"
                        name="satuan_id"
                        class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700"
                    >
                        <option value="">Semua Satuan</option>
                        @foreach($satuans as $satuan)
                            <option
                                value="{{ $satuan->id }}"
                                {{ (string) request('satuan_id') === (string) $satuan->id ? 'selected' : '' }}
                            >
                                {{ $satuan->nama_satuan }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- FILTER STATUS --}}
                <div>
                    <label for="status" class="mb-1.5 block text-xs font-medium text-slate-600">
                        Status Barang
                    </label>
                    <select
                        id="status"
                        name="status"
                        class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700"
                    >
                        <option value="">Semua Status</option>
                        <option value="aktif" {{ request('status') === 'aktif' ? 'selected' : '' }}>Aktif</option>
                        <option value="nonaktif" {{ request('status') === 'nonaktif' ? 'selected' : '' }}>Nonaktif</option>
                    </select>
                </div>

                {{-- JUMLAH DATA PER HALAMAN --}}
                <div>
                    <label for="per_page" class="mb-1.5 block text-xs font-medium text-slate-600">
                        Data per Halaman
                    </label>
                    <select
                        id="per_page"
                        name="per_page"
                        class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-700"
                    >
                        @foreach([25, 50, 100] as $jumlah)
                            <option
                                value="{{ $jumlah }}"
                                {{ (int) request('per_page', 25) === $jumlah ? 'selected' : '' }}
                            >
                                {{ $jumlah }} data
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            {{-- ACTION --}}
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 pt-4">
                <p class="text-xs text-slate-500">
                    Menampilkan {{ $barangs->firstItem() ?? 0 }}–{{ $barangs->lastItem() ?? 0 }}
                    dari {{ number_format($barangs->total(), 0, ',', '.') }} barang yang cocok.
                </p>
                <div class="flex flex-wrap items-center gap-2">
                    <a
                        href="{{ route('barang.index') }}"
                        class="rounded-lg border border-slate-200 bg-white px-4 py-2.5 text-sm text-slate-600 transition hover:bg-slate-50"
                    >
                        Reset
                    </a>
                    <button
                        type="submit"
                        class="rounded-lg bg-[#15235F] px-5 py-2.5 text-sm font-medium text-white transition hover:bg-[#101b4d]"
                    >
                        Terapkan Filter
                    </button>
                </div>
            </div>
        </form>
    </div>

    {{-- TABLE CARD --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        {{-- CARD HEADER --}}
        <div class="border-b border-slate-200 px-6 py-4">
            <h3 class="text-sm font-semibold text-slate-700">Daftar Barang</h3>
            <p class="mt-1 text-xs text-slate-400">
                Total {{ number_format($barangs->total(), 0, ',', '.') }} barang ditemukan.
            </p>
        </div>

        {{-- TABLE --}}
        <div class="overflow-x-auto">
            <table class="w-full min-w-[1200px] text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-left text-xs uppercase tracking-wide text-slate-400">
                        <th class="px-5 py-3 font-medium">No</th>
                        <th class="px-5 py-3 font-medium">Kode</th>
                        <th class="px-5 py-3 font-medium">Nama Barang</th>
                        <th class="px-5 py-3 font-medium">Kategori</th>
                        <th class="px-5 py-3 font-medium">Spesifikasi</th>
                        <th class="px-5 py-3 font-medium">Satuan</th>
                        <th class="px-5 py-3 text-right font-medium">Harga Beli</th>
                        <th class="px-5 py-3 text-right font-medium">Harga Jual</th>
                        <th class="px-5 py-3 text-right font-medium">Min. Stok</th>
                        <th class="px-5 py-3 text-center font-medium">Status</th>
                        <th class="px-5 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($barangs as $barang)
                        <tr class="transition hover:bg-slate-50/70">
                            {{-- NO --}}
                            <td class="px-5 py-4 text-slate-500">
                                {{ $barangs->firstItem() + $loop->index }}
                            </td>

                            {{-- KODE --}}
                            <td class="whitespace-nowrap px-5 py-4 font-medium text-[#15235F]">
                                {{ $barang->kode_barang }}
                            </td>

                            {{-- NAMA --}}
                            <td class="px-5 py-4 font-medium text-slate-700">
                                {{ $barang->nama_barang }}
                                @if($barang->merek)
                                    <p class="mt-1 text-xs font-normal text-slate-400">
                                        {{ $barang->merek }}
                                    </p>
                                @endif
                            </td>

                            {{-- KATEGORI --}}
                            <td class="px-5 py-4 text-slate-500">
                                {{ $barang->kategori?->nama_kategori ?? '-' }}
                            </td>

                            {{-- SPESIFIKASI --}}
                            <td class="px-5 py-4 text-slate-500">
                                {{ $barang->spesifikasi ?: '-' }}
                            </td>

                            {{-- SATUAN --}}
                            <td class="whitespace-nowrap px-5 py-4 text-slate-500">
                                {{ $barang->satuan?->nama_satuan ?? '-' }}
                            </td>

                            {{-- HARGA BELI --}}
                            <td class="whitespace-nowrap px-5 py-4 text-right font-medium text-slate-700">
                                @if((float) $barang->harga_beli_default > 0)
                                    Rp {{ number_format($barang->harga_beli_default, 0, ',', '.') }}
                                @else
                                    <span class="font-normal text-slate-400">Belum diisi</span>
                                @endif
                            </td>

                            {{-- HARGA JUAL --}}
                            <td class="whitespace-nowrap px-5 py-4 text-right font-medium text-slate-700">
                                @if((float) $barang->harga_jual > 0)
                                    Rp {{ number_format($barang->harga_jual, 0, ',', '.') }}
                                @else
                                    <span class="font-normal text-slate-400">Belum diisi</span>
                                @endif
                            </td>

                            {{-- MINIMUM STOK --}}
                            <td class="whitespace-nowrap px-5 py-4 text-right">
                                @if((float) $barang->minimum_stok > 0)
                                    <span class="font-medium text-slate-700">
                                        {{ number_format($barang->minimum_stok, 2, ',', '.') }}
                                    </span>
                                @else
                                    <span class="text-slate-400">Belum diisi</span>
                                @endif
                            </td>

                            {{-- STATUS --}}
                            <td class="px-5 py-4 text-center">
                                @if($barang->is_active)
                                    <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-600">
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-500">
                                        Nonaktif
                                    </span>
                                @endif
                            </td>

                            {{-- AKSI --}}
                            <td class="px-5 py-4">
                                <div class="flex items-center justify-end gap-2">
                                    {{-- EDIT --}}
                                    <a
                                        href="{{ route('barang.edit', $barang) }}"
                                        title="Edit Barang"
                                        aria-label="Edit {{ $barang->nama_barang }}"
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:border-[#32AAA6] hover:bg-teal-50 hover:text-[#15235F]"
                                    >
                                        <x-icon name="pencil" class="w-[15px] h-[15px]" />
                                    </a>

                                    {{-- DELETE --}}
                                    <form
                                        action="{{ route('barang.destroy', $barang) }}"
                                        method="POST"
                                        onsubmit="return confirm('Yakin ingin menghapus barang ini?')"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            title="Hapus Barang"
                                            aria-label="Hapus {{ $barang->nama_barang }}"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-400 transition hover:border-red-200 hover:bg-red-50 hover:text-red-500"
                                        >
                                            <x-icon name="trash-2" class="w-[15px] h-[15px]" />
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="px-6 py-12 text-center">
                                <p class="text-sm font-medium text-slate-500">
                                    Tidak ada barang yang cocok dengan pencarian atau filter.
                                </p>
                                <p class="mt-1 text-xs text-slate-400">
                                    Coba ubah kata kunci atau reset filter.
                                </p>
                                <a
                                    href="{{ route('barang.index') }}"
                                    class="mt-3 inline-flex text-sm font-medium text-[#15235F] hover:underline"
                                >
                                    Reset pencarian
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- PAGINATION --}}
        @if($barangs->hasPages())
            <div class="border-t border-slate-200 px-6 py-4">
                {{ $barangs->links() }}
            </div>
        @endif
    </div>
</div>

@endsection
