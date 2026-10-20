@extends('layouts.app')

@section('title', 'Buat Nota Retur Penjualan')

@section('content')

<div class="mx-auto max-w-4xl space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold text-slate-800">Form Nota Retur Penjualan</h2>
            <p class="mt-1 text-sm text-slate-500">
                Pilih invoice asal untuk memuat daftar barang yang dikembalikan.
            </p>
        </div>

        <a
            href="{{ route('retur-penjualan.index') }}"
            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
        >
            Kembali
        </a>
    </div>

    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm font-medium text-rose-800">
            <x-icon name="alert-triangle" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" /> {{ session('error') }}
        </div>
    @endif

    {{-- STEP 1: PILIH INVOICE --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <form method="GET" action="{{ route('retur-penjualan.create') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-3 items-end">
            <div class="sm:col-span-2">
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                    Pilih Invoice Penjualan Asal <span class="text-rose-500">*</span>
                </label>
                <select
                    name="invoice_id"
                    onchange="this.form.submit()"
                    class="mt-2 w-full rounded-xl border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-800 outline-none focus:border-[#32AAA6]"
                >
                    <option value="">-- Pilih Invoice --</option>
                    @foreach($invoices as $inv)
                        <option value="{{ $inv->id }}" {{ $selectedInvoice?->id == $inv->id ? 'selected' : '' }}>
                            {{ $inv->nomor_invoice }} - {{ $inv->customer?->nama_customer }} (Rp {{ number_format($inv->total, 0, ',', '.') }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <button
                    type="submit"
                    class="w-full rounded-xl bg-slate-800 py-2.5 text-sm font-medium text-white hover:bg-slate-900"
                >
                    Muat Item Barang
                </button>
            </div>
        </form>
    </div>

    {{-- STEP 2: FORM RETUR ITEM --}}
    @if($selectedInvoice)
        <form method="POST" action="{{ route('retur-penjualan.store') }}" class="space-y-6">
            @csrf

            <input type="hidden" name="invoice_penjualan_id" value="{{ $selectedInvoice->id }}">

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-6">
                <div class="grid grid-cols-1 gap-6 md:grid-cols-3">
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                            No. Nota Retur <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="text"
                            name="nomor_retur"
                            value="{{ old('nomor_retur', $nomorRetur) }}"
                            required
                            class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-mono text-slate-800 outline-none focus:border-[#32AAA6]"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                            Tanggal Retur <span class="text-rose-500">*</span>
                        </label>
                        <input
                            type="date"
                            name="tanggal_retur"
                            value="{{ old('tanggal_retur', date('Y-m-d')) }}"
                            required
                            class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 outline-none focus:border-[#32AAA6]"
                        >
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">Gudang Restock</label>
                        <input
                            type="text"
                            value="{{ $selectedInvoice->gudang?->nama_gudang }}"
                            disabled
                            class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-700 opacity-80"
                        >
                    </div>
                </div>

                <div>
                    <h3 class="text-sm font-bold uppercase tracking-wider text-[#15235F] border-b border-slate-100 pb-2">
                        Pilih Qty Barang yang Dikembalikan
                    </h3>

                    <div class="overflow-x-auto mt-4">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-400">
                                <tr>
                                    <th class="px-4 py-3">Nama Barang</th>
                                    <th class="px-4 py-3 text-right">Harga Satuan</th>
                                    <th class="px-4 py-3 text-right">Qty Beli</th>
                                    <th class="px-4 py-3 text-right w-40">Qty Retur</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100">
                                @foreach($selectedInvoice->items as $idx => $item)
                                    <tr>
                                        <td class="px-4 py-3 font-semibold text-slate-800">
                                            {{ $item->barang?->nama_barang }}
                                            <input type="hidden" name="items[{{ $idx }}][barang_id]" value="{{ $item->barang_id }}">
                                            <input type="hidden" name="items[{{ $idx }}][harga_satuan]" value="{{ $item->harga_satuan }}">
                                        </td>
                                        <td class="px-4 py-3 text-right text-slate-600">
                                            Rp {{ number_format($item->harga_satuan, 0, ',', '.') }}
                                        </td>
                                        <td class="px-4 py-3 text-right font-medium text-slate-700">
                                            {{ $item->qty }}
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <input
                                                type="number"
                                                name="items[{{ $idx }}][qty]"
                                                value="0"
                                                max="{{ $item->qty }}"
                                                min="0"
                                                step="0.01"
                                                class="w-full rounded-xl border border-slate-300 px-3 py-1.5 text-right text-sm font-bold text-rose-600 outline-none focus:border-rose-500"
                                            >
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">Alasan Retur / Catatan</label>
                    <textarea
                        name="alasan_retur"
                        rows="2"
                        placeholder="Contoh: Barang cacat fisik / rusak saat pengiriman..."
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-800 outline-none focus:border-[#32AAA6]"
                    ></textarea>
                </div>

                <div class="flex justify-end pt-4 border-t border-slate-100">
                    <button
                        type="submit"
                        class="rounded-xl bg-rose-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-700"
                    >
                        <x-icon name="refresh-cw" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" /> Terbitkan Nota Retur
                    </button>
                </div>
            </div>
        </form>
    @endif

</div>

@endsection
