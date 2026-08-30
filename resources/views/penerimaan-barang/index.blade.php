@extends('layouts.app')

@section('title', 'Penerimaan Barang')

@section('content')

<div class="space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>
            <h2 class="text-xl font-semibold text-slate-800">
                Penerimaan Barang
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Catat barang yang diterima berdasarkan Purchase Order.
            </p>
        </div>

        <a
            href="{{ route('penerimaan-barang.create') }}"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#15235F] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#101b4d]"
        >
            + Buat Penerimaan
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
                Daftar Penerimaan Barang
            </h3>

            <p class="mt-1 text-xs text-slate-400">
                Total {{ $penerimaanBarangs->total() }} penerimaan.
            </p>

        </div>


        <div class="overflow-x-auto">

            <table class="w-full min-w-[1050px] text-sm">

                <thead class="bg-slate-50">

                    <tr class="text-left text-xs uppercase tracking-wide text-slate-400">

                        <th class="px-5 py-3 font-medium">
                            No. Penerimaan
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Tanggal
                        </th>

                        <th class="px-5 py-3 font-medium">
                            No. PO
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Supplier
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Gudang
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Surat Jalan
                        </th>

                        <th class="px-5 py-3 text-right font-medium">
                            Aksi
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-slate-100">

                    @forelse($penerimaanBarangs as $penerimaan)

                        <tr class="transition hover:bg-slate-50/70">

                            <td class="whitespace-nowrap px-5 py-4">

                                <a
                                    href="{{ route('penerimaan-barang.show', $penerimaan) }}"
                                    class="font-semibold text-[#15235F] transition hover:text-[#32AAA6]"
                                >
                                    {{ $penerimaan->nomor_penerimaan }}
                                </a>

                            </td>


                            <td class="whitespace-nowrap px-5 py-4 text-slate-500">

                                {{ $penerimaan->tanggal_penerimaan?->format('d/m/Y') ?? '-' }}

                            </td>


                            <td class="whitespace-nowrap px-5 py-4">

                                <span class="font-medium text-slate-700">
                                    {{ $penerimaan->purchaseOrder?->nomor_po ?? '-' }}
                                </span>

                            </td>


                            <td class="px-5 py-4">

                                <p class="font-medium text-slate-700">
                                    {{ $penerimaan->purchaseOrder?->supplier?->nama_supplier ?? '-' }}
                                </p>

                            </td>


                            <td class="px-5 py-4 text-slate-500">

                                {{ $penerimaan->gudang?->nama_gudang ?? '-' }}

                            </td>


                            <td class="px-5 py-4 text-slate-500">

                                {{ $penerimaan->nomor_surat_jalan ?: '-' }}

                            </td>


                            <td class="px-5 py-4">

                                <div class="flex justify-end">

                                    <a
                                        href="{{ route('penerimaan-barang.show', $penerimaan) }}"
                                        class="rounded-lg border border-slate-200 bg-white px-3 py-2 text-xs font-medium text-slate-600 transition hover:bg-slate-50 hover:text-[#15235F]"
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
                                    Belum ada penerimaan barang.
                                </p>

                                <p class="mt-1 text-xs text-slate-400">
                                    Buat penerimaan barang berdasarkan Purchase Order yang sudah dikirim.
                                </p>

                                <a
                                    href="{{ route('penerimaan-barang.create') }}"
                                    class="mt-4 inline-flex rounded-lg bg-[#15235F] px-4 py-2 text-xs font-medium text-white"
                                >
                                    + Buat Penerimaan
                                </a>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        @if($penerimaanBarangs->hasPages())

            <div class="border-t border-slate-200 px-6 py-4">
                {{ $penerimaanBarangs->links() }}
            </div>

        @endif

    </div>

</div>

@endsection