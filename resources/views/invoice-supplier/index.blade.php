@extends('layouts.app')

@section('title', 'Invoice Supplier')

@section('content')

<div class="space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>
            <h2 class="text-xl font-semibold text-slate-800">
                Invoice Supplier
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Kelola tagihan supplier berdasarkan barang yang telah diterima.
            </p>
        </div>

        <a
            href="{{ route('invoice-supplier.create') }}"
            class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#15235F] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#101b4d]"
        >
            + Tambah Invoice
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
                Daftar Invoice Supplier
            </h3>

            <p class="mt-1 text-xs text-slate-400">
                Total {{ $invoiceSuppliers->total() }} invoice.
            </p>

        </div>


        <div class="overflow-x-auto">

            <table class="w-full min-w-[1250px] text-sm">

                <thead class="bg-slate-50">

                    <tr class="text-left text-xs uppercase tracking-wide text-slate-400">
                        <th class="px-5 py-3 font-medium">No. Internal</th>
                        <th class="px-5 py-3 font-medium">No. Supplier</th>
                        <th class="px-5 py-3 font-medium">Supplier</th>
                        <th class="px-5 py-3 font-medium">PO</th>
                        <th class="px-5 py-3 font-medium">Tanggal</th>
                        <th class="px-5 py-3 font-medium">Jatuh Tempo</th>
                        <th class="px-5 py-3 text-right font-medium">Total</th>
                        <th class="px-5 py-3 text-right font-medium">Sisa Utang</th>
                        <th class="px-5 py-3 text-center font-medium">Status</th>
                        <th class="px-5 py-3 text-right font-medium">Aksi</th>
                    </tr>

                </thead>


                <tbody class="divide-y divide-slate-100">

                    @forelse($invoiceSuppliers as $invoice)

                        @php
                            $statusClass = match($invoice->status) {
                                'BELUM_LUNAS' => 'bg-red-50 text-red-700',
                                'SEBAGIAN' => 'bg-amber-50 text-amber-700',
                                'LUNAS' => 'bg-emerald-50 text-emerald-700',
                                default => 'bg-slate-100 text-slate-600',
                            };
                        @endphp

                        <tr class="transition hover:bg-slate-50/70">

                            <td class="px-5 py-4">

                                <a
                                    href="{{ route('invoice-supplier.show', $invoice) }}"
                                    class="font-semibold text-[#15235F] hover:text-[#32AAA6]"
                                >
                                    {{ $invoice->nomor_invoice_internal }}
                                </a>

                            </td>


                            <td class="px-5 py-4 font-medium text-slate-700">
                                {{ $invoice->nomor_invoice_supplier }}
                            </td>


                            <td class="px-5 py-4">
                                <p class="font-medium text-slate-700">
                                    {{ $invoice->supplier?->nama_supplier ?? '-' }}
                                </p>
                            </td>


                            <td class="px-5 py-4">

                                <span class="font-medium text-slate-700">
                                    {{ $invoice->purchaseOrder?->nomor_po ?? '-' }}
                                </span>

                            </td>


                            <td class="whitespace-nowrap px-5 py-4 text-slate-500">
                                {{ $invoice->tanggal_invoice?->format('d/m/Y') ?? '-' }}
                            </td>


                            <td class="whitespace-nowrap px-5 py-4 text-slate-500">
                                {{ $invoice->tanggal_jatuh_tempo?->format('d/m/Y') ?? '-' }}
                            </td>


                            <td class="whitespace-nowrap px-5 py-4 text-right font-semibold text-slate-700">
                                Rp {{ number_format($invoice->total, 0, ',', '.') }}
                            </td>


                            <td class="whitespace-nowrap px-5 py-4 text-right font-semibold text-red-500">
                                Rp {{ number_format($invoice->sisa_utang, 0, ',', '.') }}
                            </td>


                            <td class="px-5 py-4 text-center">

                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $statusClass }}">
                                    {{ str_replace('_', ' ', $invoice->status) }}
                                </span>

                            </td>


                            <td class="px-5 py-4">

                                <div class="flex justify-end gap-2">

                                    <a
                                        href="{{ route('invoice-supplier.show', $invoice) }}"
                                        class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-medium text-slate-600 transition hover:bg-slate-50"
                                    >
                                        Detail
                                    </a>

                                    @if((float) $invoice->total_dibayar <= 0)

                                        <form
                                            action="{{ route('invoice-supplier.destroy', $invoice) }}"
                                            method="POST"
                                            onsubmit="return confirm('Yakin ingin menghapus invoice {{ $invoice->nomor_invoice_internal }}?')"
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
                                colspan="10"
                                class="px-6 py-14 text-center"
                            >
                                <p class="text-sm font-medium text-slate-500">
                                    Belum ada Invoice Supplier.
                                </p>

                                <p class="mt-1 text-xs text-slate-400">
                                    Invoice dapat dibuat setelah barang dari Purchase Order diterima.
                                </p>
                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        @if($invoiceSuppliers->hasPages())
            <div class="border-t border-slate-200 px-6 py-4">
                {{ $invoiceSuppliers->links() }}
            </div>
        @endif

    </div>

</div>

@endsection