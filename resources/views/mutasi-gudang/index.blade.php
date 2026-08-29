@extends('layouts.app')

@section('title', 'Mutasi Gudang')

@section('content')

<div class="space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>
            <h2 class="text-xl font-semibold text-slate-800">
                Mutasi Gudang
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Kelola perpindahan stok antar gudang.
            </p>
        </div>

        <a
            href="{{ route('mutasi-gudang.create') }}"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#15235F] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#101b4d]"
        >
            + Buat Mutasi
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
                Daftar Mutasi Gudang
            </h3>

            <p class="mt-1 text-xs text-slate-400">
                Total {{ $mutasiGudangs->total() }} mutasi.
            </p>
        </div>


        <div class="overflow-x-auto">

            <table class="w-full min-w-[1000px] text-sm">

                <thead class="bg-slate-50">

                    <tr class="text-left text-xs uppercase tracking-wide text-slate-400">
                        <th class="px-5 py-3 font-medium">No. Mutasi</th>
                        <th class="px-5 py-3 font-medium">Tanggal</th>
                        <th class="px-5 py-3 font-medium">Gudang Asal</th>
                        <th class="px-5 py-3 font-medium">Gudang Tujuan</th>
                        <th class="px-5 py-3 text-right font-medium">Jenis Barang</th>
                        <th class="px-5 py-3 text-right font-medium">Aksi</th>
                    </tr>

                </thead>


                <tbody class="divide-y divide-slate-100">

                    @forelse($mutasiGudangs as $mutasi)

                        <tr class="transition hover:bg-slate-50/70">

                            <td class="px-5 py-4">

                                <a
                                    href="{{ route('mutasi-gudang.show', $mutasi) }}"
                                    class="font-semibold text-[#15235F] hover:text-[#32AAA6]"
                                >
                                    {{ $mutasi->nomor_mutasi }}
                                </a>

                            </td>


                            <td class="px-5 py-4 text-slate-500">
                                {{ $mutasi->tanggal_mutasi?->format('d/m/Y') ?? '-' }}
                            </td>


                            <td class="px-5 py-4 font-medium text-slate-700">
                                {{ $mutasi->gudangAsal?->nama_gudang ?? '-' }}
                            </td>


                            <td class="px-5 py-4 font-medium text-slate-700">
                                {{ $mutasi->gudangTujuan?->nama_gudang ?? '-' }}
                            </td>


                            <td class="px-5 py-4 text-right font-semibold text-slate-700">
                                {{ $mutasi->items->count() }}
                            </td>


                            <td class="px-5 py-4">

                                <div class="flex justify-end">

                                    <a
                                        href="{{ route('mutasi-gudang.show', $mutasi) }}"
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
                                colspan="6"
                                class="px-6 py-14 text-center"
                            >
                                <p class="text-sm font-medium text-slate-500">
                                    Belum ada mutasi gudang.
                                </p>

                                <p class="mt-1 text-xs text-slate-400">
                                    Buat mutasi untuk memindahkan stok antar gudang.
                                </p>
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        @if($mutasiGudangs->hasPages())
            <div class="border-t border-slate-200 px-6 py-4">
                {{ $mutasiGudangs->links() }}
            </div>
        @endif

    </div>

</div>

@endsection