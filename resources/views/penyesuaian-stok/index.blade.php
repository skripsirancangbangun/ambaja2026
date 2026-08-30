@extends('layouts.app')

@section('title', 'Penyesuaian Stok')

@section('content')

<div class="space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>
            <h2 class="text-xl font-semibold text-slate-800">
                Penyesuaian Stok
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Proses selisih hasil Stock Opname ke saldo persediaan.
            </p>
        </div>

        <a
            href="{{ route('penyesuaian-stok.create') }}"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#15235F] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#101b4d]"
        >
            + Buat Penyesuaian
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
                Daftar Penyesuaian Stok
            </h3>

            <p class="mt-1 text-xs text-slate-400">
                Total {{ $penyesuaianStoks->total() }} dokumen.
            </p>

        </div>


        <div class="overflow-x-auto">

            <table class="w-full min-w-[1100px] text-sm">

                <thead class="bg-slate-50">

                    <tr class="text-left text-xs uppercase tracking-wide text-slate-400">

                        <th class="px-5 py-3 font-medium">
                            No. Penyesuaian
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Tanggal
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Stock Opname
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Gudang
                        </th>

                        <th class="px-5 py-3 text-right font-medium">
                            Barang
                        </th>

                        <th class="px-5 py-3 text-right font-medium">
                            Nilai Penyesuaian
                        </th>

                        <th class="px-5 py-3 text-right font-medium">
                            Aksi
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-slate-100">

                    @forelse($penyesuaianStoks as $penyesuaian)

                        @php
                            $totalNilai =
                                $penyesuaian->items->sum(
                                    fn($item) =>
                                        $item->jenis === 'TAMBAH'
                                            ? (float) $item->nilai_penyesuaian
                                            : -(float) $item->nilai_penyesuaian
                                );
                        @endphp


                        <tr class="transition hover:bg-slate-50/70">

                            <td class="px-5 py-4">

                                <a
                                    href="{{ route('penyesuaian-stok.show', $penyesuaian) }}"
                                    class="font-semibold text-[#15235F] hover:text-[#32AAA6]"
                                >
                                    {{ $penyesuaian->nomor_penyesuaian }}
                                </a>

                            </td>


                            <td class="whitespace-nowrap px-5 py-4 text-slate-500">

                                {{ $penyesuaian->tanggal_penyesuaian?->format('d/m/Y') ?? '-' }}

                            </td>


                            <td class="px-5 py-4">

                                <p class="font-medium text-slate-700">
                                    {{ $penyesuaian->stockOpname?->nomor_opname ?? '-' }}
                                </p>

                            </td>


                            <td class="px-5 py-4">

                                <p class="font-medium text-slate-700">
                                    {{ $penyesuaian->gudang?->nama_gudang ?? '-' }}
                                </p>

                            </td>


                            <td class="px-5 py-4 text-right font-semibold text-slate-700">

                                {{ $penyesuaian->items->count() }}

                            </td>


                            <td class="whitespace-nowrap px-5 py-4 text-right">

                                <span class="font-semibold {{ $totalNilai < 0 ? 'text-red-500' : ($totalNilai > 0 ? 'text-emerald-600' : 'text-slate-500') }}">

                                    {{ $totalNilai > 0 ? '+' : '' }}

                                    Rp {{ number_format(
                                        $totalNilai,
                                        0,
                                        ',',
                                        '.'
                                    ) }}

                                </span>

                            </td>


                            <td class="px-5 py-4">

                                <div class="flex justify-end">

                                    <a
                                        href="{{ route('penyesuaian-stok.show', $penyesuaian) }}"
                                        class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-medium text-slate-600 transition hover:bg-slate-50"
                                    >
                                        Detail
                                    </a>

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
                                    Belum ada Penyesuaian Stok.
                                </p>

                                <p class="mt-1 text-xs text-slate-400">
                                    Penyesuaian dibuat dari Stock Opname yang telah selesai dan memiliki selisih.
                                </p>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        @if($penyesuaianStoks->hasPages())

            <div class="border-t border-slate-200 px-6 py-4">
                {{ $penyesuaianStoks->links() }}
            </div>

        @endif

    </div>

</div>

@endsection