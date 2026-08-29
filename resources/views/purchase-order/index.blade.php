@extends('layouts.app')

@section('title', 'Purchase Order')

@section('content')

<div class="space-y-6">

    {{-- HEADER --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>
            <h2 class="text-xl font-semibold text-slate-800">
                Purchase Order
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Kelola pesanan pembelian kepada supplier.
            </p>
        </div>

        <a
            href="{{ route('purchase-order.create') }}"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#15235F] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#101b4d]"
        >
            <span class="text-base leading-none">+</span>
            Buat Purchase Order
        </a>

    </div>


    {{-- SUCCESS --}}
    @if(session('success'))

        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>

    @endif


    {{-- ERROR --}}
    @if(session('error'))

        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>

    @endif


    {{-- TABLE --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        {{-- HEADER TABLE --}}
        <div class="border-b border-slate-200 px-6 py-4">

            <h3 class="text-sm font-semibold text-slate-700">
                Daftar Purchase Order
            </h3>

            <p class="mt-1 text-xs text-slate-400">
                Total {{ $purchaseOrders->total() }} Purchase Order.
            </p>

        </div>


        <div class="overflow-x-auto">

            <table class="w-full min-w-[1050px] text-left text-sm">

                <thead class="bg-slate-50">

                    <tr class="text-xs uppercase tracking-wide text-slate-400">

                        <th class="px-5 py-3 font-medium">
                            No. PO
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Tanggal
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Supplier
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Estimasi Datang
                        </th>

                        <th class="px-5 py-3 text-right font-medium">
                            Total
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

                    @forelse($purchaseOrders as $po)

                        @php
                            $statusClass = match($po->status) {
                                'Draft' => 'bg-slate-100 text-slate-600',
                                'Dipesan' => 'bg-blue-50 text-blue-700',
                                'Sebagian Diterima' => 'bg-amber-50 text-amber-700',
                                'Selesai' => 'bg-emerald-50 text-emerald-700',
                                'Dibatalkan' => 'bg-red-50 text-red-700',
                                default => 'bg-slate-100 text-slate-600',
                            };
                        @endphp


                        <tr class="transition hover:bg-slate-50/70">

                            {{-- NOMOR --}}
                            <td class="whitespace-nowrap px-5 py-4">

                                <a
                                    href="{{ route('purchase-order.show', $po) }}"
                                    class="font-semibold text-[#15235F] transition hover:text-[#32AAA6]"
                                >
                                    {{ $po->nomor_po }}
                                </a>

                            </td>


                            {{-- TANGGAL --}}
                            <td class="whitespace-nowrap px-5 py-4 text-slate-500">
                                {{ $po->tanggal_po?->format('d/m/Y') ?? '-' }}
                            </td>


                            {{-- SUPPLIER --}}
                            <td class="px-5 py-4">

                                <p class="font-medium text-slate-700">
                                    {{ $po->supplier?->nama_supplier ?? '-' }}
                                </p>

                                @if($po->supplier?->kode_supplier)

                                    <p class="mt-1 text-xs text-slate-400">
                                        {{ $po->supplier->kode_supplier }}
                                    </p>

                                @endif

                            </td>


                            {{-- ESTIMASI --}}
                            <td class="whitespace-nowrap px-5 py-4 text-slate-500">

                                {{ $po->tanggal_estimasi_datang?->format('d/m/Y') ?? '-' }}

                            </td>


                            {{-- TOTAL --}}
                            <td class="whitespace-nowrap px-5 py-4 text-right font-semibold text-slate-700">

                                Rp {{ number_format(
                                    $po->total,
                                    0,
                                    ',',
                                    '.'
                                ) }}

                            </td>


                            {{-- STATUS --}}
                            <td class="px-5 py-4 text-center">

                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $statusClass }}">
                                    {{ $po->status }}
                                </span>

                            </td>


                            {{-- AKSI --}}
                            <td class="px-5 py-4">

                                <div class="flex items-center justify-end gap-2">

                                    {{-- DETAIL --}}
                                    <a
                                        href="{{ route('purchase-order.show', $po) }}"
                                        class="inline-flex h-9 items-center justify-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-600 transition hover:bg-slate-50 hover:text-[#15235F]"
                                    >
                                        Detail
                                    </a>


                                    @if($po->status === 'Draft')

                                        {{-- EDIT --}}
                                        <a
                                            href="{{ route('purchase-order.edit', $po) }}"
                                            class="inline-flex h-9 items-center justify-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-600 transition hover:border-[#32AAA6] hover:bg-teal-50 hover:text-[#15235F]"
                                        >
                                            Edit
                                        </a>


                                        {{-- KIRIM --}}
                                        <form
                                            action="{{ route('purchase-order.kirim', $po) }}"
                                            method="POST"
                                        >

                                            @csrf
                                            @method('PATCH')

                                            <button
                                                type="submit"
                                                onclick="return confirm('Kirim Purchase Order {{ $po->nomor_po }} ke supplier?')"
                                                class="inline-flex h-9 items-center justify-center rounded-lg bg-[#15235F] px-3 text-xs font-medium text-white transition hover:bg-[#101b4d]"
                                            >
                                                Kirim
                                            </button>

                                        </form>


                                        {{-- HAPUS --}}
                                        <form
                                            action="{{ route('purchase-order.destroy', $po) }}"
                                            method="POST"
                                            onsubmit="return confirm('Yakin ingin menghapus Purchase Order {{ $po->nomor_po }}? Data yang sudah dihapus tidak dapat dikembalikan.')"
                                        >

                                            @csrf
                                            @method('DELETE')

                                            <button
                                                type="submit"
                                                class="inline-flex h-9 items-center justify-center rounded-lg border border-red-200 bg-white px-3 text-xs font-medium text-red-500 transition hover:bg-red-50"
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

                                <div class="mx-auto max-w-sm">

                                    <p class="text-sm font-medium text-slate-500">
                                        Belum ada Purchase Order.
                                    </p>

                                    <p class="mt-1 text-xs text-slate-400">
                                        Buat Purchase Order pertama untuk mulai proses pembelian.
                                    </p>

                                    <a
                                        href="{{ route('purchase-order.create') }}"
                                        class="mt-4 inline-flex rounded-lg bg-[#15235F] px-4 py-2 text-xs font-medium text-white transition hover:bg-[#101b4d]"
                                    >
                                        + Buat Purchase Order
                                    </a>

                                </div>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- PAGINATION --}}
        @if($purchaseOrders->hasPages())

            <div class="border-t border-slate-200 px-6 py-4">
                {{ $purchaseOrders->links() }}
            </div>

        @endif

    </div>

</div>

@endsection