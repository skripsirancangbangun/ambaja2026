@extends('layouts.app')

@section('title', 'Retur Pembelian')

@section('content')

<div class="space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>
            <h2 class="text-xl font-semibold text-slate-800">
                Retur Pembelian
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Kelola pengembalian barang kepada supplier.
            </p>
        </div>

        <a
            href="{{ route('retur-pembelian.create') }}"
            class="inline-flex items-center justify-center rounded-lg bg-[#15235F] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#101b4d]"
        >
            + Buat Retur
        </a>

    </div>


    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif


    @if(session('error'))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif


    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

        <form
            method="GET"
            action="{{ route('retur-pembelian.index') }}"
        >

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">

                <div>

                    <label class="mb-2 block text-xs font-medium text-slate-500">
                        Pencarian
                    </label>

                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="No. retur / supplier / PO..."
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >

                </div>


                <div>

                    <label class="mb-2 block text-xs font-medium text-slate-500">
                        Status
                    </label>

                    <select
                        name="status"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-[#32AAA6]"
                    >

                        <option value="">
                            Semua Status
                        </option>

                        @foreach([
                            'DRAFT',
                            'DIPROSES',
                            'SELESAI',
                            'DIBATALKAN',
                        ] as $status)

                            <option
                                value="{{ $status }}"
                                {{ request('status') === $status ? 'selected' : '' }}
                            >
                                {{ $status }}
                            </option>

                        @endforeach

                    </select>

                </div>


                <div>

                    <label class="mb-2 block text-xs font-medium text-slate-500">
                        Dari Tanggal
                    </label>

                    <input
                        type="date"
                        name="tanggal_mulai"
                        value="{{ request('tanggal_mulai') }}"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-[#32AAA6]"
                    >

                </div>


                <div>

                    <label class="mb-2 block text-xs font-medium text-slate-500">
                        Sampai Tanggal
                    </label>

                    <input
                        type="date"
                        name="tanggal_selesai"
                        value="{{ request('tanggal_selesai') }}"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-[#32AAA6]"
                    >

                </div>

            </div>


            <div class="mt-4 flex justify-end gap-2">

                <a
                    href="{{ route('retur-pembelian.index') }}"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50"
                >
                    Reset
                </a>

                <button
                    type="submit"
                    class="rounded-lg bg-[#15235F] px-5 py-2 text-sm font-medium text-white hover:bg-[#101b4d]"
                >
                    Terapkan
                </button>

            </div>

        </form>

    </div>


    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-200 px-6 py-4">

            <h3 class="text-sm font-semibold text-slate-700">
                Daftar Retur
            </h3>

            <p class="mt-1 text-xs text-slate-400">
                Total {{ $returs->total() }} retur.
            </p>

        </div>


        <div class="overflow-x-auto">

            <table class="w-full min-w-[1200px] text-sm">

                <thead class="bg-slate-50">

                    <tr class="text-left text-xs uppercase tracking-wide text-slate-400">

                        <th class="px-5 py-3">No. Retur</th>
                        <th class="px-5 py-3">Tanggal</th>
                        <th class="px-5 py-3">Supplier</th>
                        <th class="px-5 py-3">Penerimaan</th>
                        <th class="px-5 py-3">Gudang</th>
                        <th class="px-5 py-3 text-right">Jenis Barang</th>
                        <th class="px-5 py-3 text-center">Status</th>
                        <th class="px-5 py-3 text-right">Aksi</th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-slate-100">

                    @forelse($returs as $retur)

                        @php
                            $statusClass = match($retur->status) {
                                'DRAFT' => 'bg-slate-100 text-slate-600',
                                'DIPROSES' => 'bg-amber-50 text-amber-700',
                                'SELESAI' => 'bg-emerald-50 text-emerald-700',
                                'DIBATALKAN' => 'bg-red-50 text-red-600',
                                default => 'bg-slate-100 text-slate-600',
                            };
                        @endphp

                        <tr class="transition hover:bg-slate-50/70">

                            <td class="px-5 py-4">

                                <a
                                    href="{{ route('retur-pembelian.show', $retur) }}"
                                    class="font-semibold text-[#15235F] hover:text-[#32AAA6]"
                                >
                                    {{ $retur->nomor_retur }}
                                </a>

                            </td>


                            <td class="whitespace-nowrap px-5 py-4 text-slate-500">

                                {{ $retur->tanggal_retur?->format('d/m/Y') ?? '-' }}

                            </td>


                            <td class="px-5 py-4 font-medium text-slate-700">

                                {{ $retur->supplier?->nama_supplier ?? '-' }}

                            </td>


                            <td class="px-5 py-4 text-slate-600">

                                {{ $retur->penerimaanBarang?->nomor_penerimaan ?? '-' }}

                            </td>


                            <td class="px-5 py-4 text-slate-600">

                                {{ $retur->gudang?->nama_gudang ?? '-' }}

                            </td>


                            <td class="px-5 py-4 text-right font-semibold text-slate-700">

                                {{ $retur->details->count() }}

                            </td>


                            <td class="px-5 py-4 text-center">

                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $statusClass }}">
                                    {{ $retur->status }}
                                </span>

                            </td>


                            <td class="px-5 py-4">

                                <div class="flex justify-end gap-2">

                                    <a
                                        href="{{ route('retur-pembelian.show', $retur) }}"
                                        class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-medium text-slate-600 hover:bg-slate-50"
                                    >
                                        Detail
                                    </a>


                                    @if($retur->status === 'DRAFT')

                                        <form
                                            method="POST"
                                            action="{{ route('retur-pembelian.destroy', $retur) }}"
                                            onsubmit="return confirm('Yakin ingin menghapus draft retur ini?')"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="rounded-lg border border-red-200 px-3 py-2 text-xs font-medium text-red-500 hover:bg-red-50"
                                            >
                                                Hapus
                                            </button>

                                        </form>

                                    @endif

                                </div>

                            </td>

                        </tr>


                    @empty

                        <tr>

                            <td
                                colspan="8"
                                class="px-6 py-14 text-center"
                            >

                                <p class="text-sm font-medium text-slate-500">
                                    Belum ada Retur Pembelian.
                                </p>

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