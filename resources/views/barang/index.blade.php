@extends('layouts.app')

@section('title', 'Barang')

@section('content')

<div class="space-y-6">

    {{-- HEADER --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>
            <h2 class="text-xl font-semibold text-slate-800">
                Barang
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Kelola data barang yang digunakan dalam transaksi penjualan, pembelian, dan persediaan.
            </p>
        </div>

        <a
            href="{{ route('barang.create') }}"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#15235F] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#101b4d]"
        >
            <span class="text-lg leading-none">+</span>
            Tambah Barang
        </a>

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


    {{-- TABLE CARD --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        {{-- CARD HEADER --}}
        <div class="border-b border-slate-200 px-6 py-4">

            <h3 class="text-sm font-semibold text-slate-700">
                Daftar Barang
            </h3>

            <p class="mt-1 text-xs text-slate-400">
                Total {{ $barangs->total() }} barang.
            </p>

        </div>


        {{-- TABLE --}}
        <div class="overflow-x-auto">

            <table class="w-full min-w-[1200px] text-sm">

                <thead class="bg-slate-50">

                    <tr class="text-left text-xs uppercase tracking-wide text-slate-400">

                        <th class="px-5 py-3 font-medium">
                            No
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Kode
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Nama Barang
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Kategori
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Spesifikasi
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Satuan
                        </th>

                        <th class="px-5 py-3 text-right font-medium">
                            Harga Beli
                        </th>

                        <th class="px-5 py-3 text-right font-medium">
                            Harga Jual
                        </th>

                        <th class="px-5 py-3 text-right font-medium">
                            Min. Stok
                        </th>

                        <th class="px-5 py-3 text-center font-medium">
                            Status
                        </th>

                        <th class="px-5 py-3 text-right font-medium">
                            Aksi
                        </th>

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

                                    Rp {{ number_format(
                                        $barang->harga_beli_default,
                                        0,
                                        ',',
                                        '.'
                                    ) }}

                                @else

                                    <span class="font-normal text-slate-400">
                                        Belum diisi
                                    </span>

                                @endif

                            </td>


                            {{-- HARGA JUAL --}}
                            <td class="whitespace-nowrap px-5 py-4 text-right font-medium text-slate-700">

                                @if((float) $barang->harga_jual > 0)

                                    Rp {{ number_format(
                                        $barang->harga_jual,
                                        0,
                                        ',',
                                        '.'
                                    ) }}

                                @else

                                    <span class="font-normal text-slate-400">
                                        Belum diisi
                                    </span>

                                @endif

                            </td>


                            {{-- MINIMUM STOK --}}
                            <td class="whitespace-nowrap px-5 py-4 text-right">

                                @if((float) $barang->minimum_stok > 0)

                                    <span class="font-medium text-slate-700">
                                        {{ number_format(
                                            $barang->minimum_stok,
                                            2,
                                            ',',
                                            '.'
                                        ) }}
                                    </span>

                                @else

                                    <span class="text-slate-400">
                                        Belum diisi
                                    </span>

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
                                        class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-500 transition hover:border-[#32AAA6] hover:bg-teal-50 hover:text-[#15235F]"
                                    >
                                        <svg
                                            xmlns="http://www.w3.org/2000/svg"
                                            width="15"
                                            height="15"
                                            viewBox="0 0 24 24"
                                            fill="none"
                                            stroke="currentColor"
                                            stroke-width="1.8"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        >
                                            <path d="M12 20h9"/>
                                            <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4Z"/>
                                        </svg>
                                    </a>


                                    {{-- DELETE --}}
                                    <form
                                        action="{{ route('barang.destroy', $barang) }}"
                                        method="POST"
                                        onsubmit="return confirm('Yakin ingin menghapus barang {{ addslashes($barang->nama_barang) }}?')"
                                    >
                                        @csrf
                                        @method('DELETE')

                                        <button
                                            type="submit"
                                            title="Hapus Barang"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-lg border border-slate-200 text-slate-400 transition hover:border-red-200 hover:bg-red-50 hover:text-red-500"
                                        >
                                            <svg
                                                xmlns="http://www.w3.org/2000/svg"
                                                width="15"
                                                height="15"
                                                viewBox="0 0 24 24"
                                                fill="none"
                                                stroke="currentColor"
                                                stroke-width="1.8"
                                                stroke-linecap="round"
                                                stroke-linejoin="round"
                                            >
                                                <path d="M3 6h18"/>
                                                <path d="M8 6V4h8v2"/>
                                                <path d="M19 6l-1 14H6L5 6"/>
                                                <path d="M10 11v5"/>
                                                <path d="M14 11v5"/>
                                            </svg>
                                        </button>

                                    </form>

                                </div>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="11"
                                class="px-6 py-12 text-center"
                            >

                                <p class="text-sm font-medium text-slate-500">
                                    Belum ada data barang.
                                </p>

                                <p class="mt-1 text-xs text-slate-400">
                                    Tambahkan barang untuk mulai menggunakan transaksi.
                                </p>

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