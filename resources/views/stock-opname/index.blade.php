@extends('layouts.app')

@section('title', 'Stock Opname')

@section('content')

<div class="space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>
            <h2 class="text-xl font-semibold text-slate-800">
                Stock Opname
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Catat hasil pemeriksaan fisik persediaan pada setiap gudang.
            </p>
        </div>

        <a
            href="{{ route('stock-opname.create') }}"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#15235F] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#101b4d]"
        >
            + Buat Stock Opname
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


    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-200 px-6 py-4">

            <h3 class="text-sm font-semibold text-slate-700">
                Daftar Stock Opname
            </h3>

            <p class="mt-1 text-xs text-slate-400">
                Total {{ $stockOpnames->total() }} dokumen.
            </p>

        </div>


        <div class="overflow-x-auto">

            <table class="w-full min-w-[1050px] text-sm">

                <thead class="bg-slate-50">

                    <tr class="text-left text-xs uppercase tracking-wide text-slate-400">

                        <th class="px-5 py-3 font-medium">
                            No. Opname
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Tanggal
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Gudang
                        </th>

                        <th class="px-5 py-3 text-right font-medium">
                            Jenis Barang
                        </th>

                        <th class="px-5 py-3 text-right font-medium">
                            Selisih
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

                    @forelse($stockOpnames as $opname)

                        @php
                            $statusClass = match($opname->status) {
                                'DRAFT' => 'bg-slate-100 text-slate-600',
                                'SELESAI' => 'bg-blue-50 text-blue-700',
                                'DISESUAIKAN' => 'bg-emerald-50 text-emerald-700',
                                default => 'bg-slate-100 text-slate-600',
                            };

                            $totalSelisih =
                                $opname->items->sum(
                                    fn($item) => abs((float) $item->selisih)
                                );
                        @endphp


                        <tr class="transition hover:bg-slate-50/70">

                            <td class="px-5 py-4">

                                <a
                                    href="{{ route('stock-opname.show', $opname) }}"
                                    class="font-semibold text-[#15235F] hover:text-[#32AAA6]"
                                >
                                    {{ $opname->nomor_opname }}
                                </a>

                            </td>


                            <td class="whitespace-nowrap px-5 py-4 text-slate-500">

                                {{ $opname->tanggal_opname?->format('d/m/Y') ?? '-' }}

                            </td>


                            <td class="px-5 py-4">

                                <p class="font-medium text-slate-700">
                                    {{ $opname->gudang?->nama_gudang ?? '-' }}
                                </p>

                                @if($opname->gudang?->kode_gudang)

                                    <p class="mt-1 text-xs text-slate-400">
                                        {{ $opname->gudang->kode_gudang }}
                                    </p>

                                @endif

                            </td>


                            <td class="px-5 py-4 text-right font-semibold text-slate-700">

                                {{ $opname->items->count() }}

                            </td>


                            <td class="px-5 py-4 text-right">

                                @if($totalSelisih > 0)

                                    <span class="font-semibold text-amber-600">
                                        {{ number_format(
                                            $totalSelisih,
                                            2,
                                            ',',
                                            '.'
                                        ) }}
                                    </span>

                                @else

                                    <span class="text-slate-400">
                                        0
                                    </span>

                                @endif

                            </td>


                            <td class="px-5 py-4 text-center">

                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $statusClass }}">
                                    {{ $opname->status }}
                                </span>

                            </td>


                            <td class="px-5 py-4">

                                <div class="flex justify-end gap-2">

                                    <a
                                        href="{{ route('stock-opname.show', $opname) }}"
                                        class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-medium text-slate-600 transition hover:bg-slate-50"
                                    >
                                        Detail
                                    </a>


                                    @if($opname->status === 'DRAFT')

                                        <a
                                            href="{{ route('stock-opname.edit', $opname) }}"
                                            class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-medium text-slate-600 transition hover:bg-teal-50 hover:text-[#15235F]"
                                        >
                                            Isi Fisik
                                        </a>


                                        <form
                                            action="{{ route('stock-opname.destroy', $opname) }}"
                                            method="POST"
                                            onsubmit="return confirm('Yakin ingin menghapus Stock Opname {{ $opname->nomor_opname }}?')"
                                        >
                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="rounded-lg border border-red-200 px-3 py-2 text-xs font-medium text-red-500 transition hover:bg-red-50"
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
                                colspan="7"
                                class="px-6 py-14 text-center"
                            >

                                <p class="text-sm font-medium text-slate-500">
                                    Belum ada Stock Opname.
                                </p>

                                <p class="mt-1 text-xs text-slate-400">
                                    Buat Stock Opname untuk mulai mencatat hasil pemeriksaan fisik.
                                </p>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        @if($stockOpnames->hasPages())

            <div class="border-t border-slate-200 px-6 py-4">
                {{ $stockOpnames->links() }}
            </div>

        @endif

    </div>

</div>

@endsection