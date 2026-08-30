@extends('layouts.app')

@section('title', 'Buat Retur Penjualan')

@section('content')

<div class="mx-auto max-w-5xl space-y-6" x-data="returPenjualanForm()">

    <div>
        <a href="{{ route('retur-penjualan.index') }}" class="mb-3 inline-flex items-center gap-2 text-sm text-slate-500 hover:text-[#15235F]">
            ← Kembali ke Daftar Retur
        </a>
        <h2 class="text-xl font-semibold text-slate-800">Buat Retur Penjualan</h2>
        <p class="mt-1 text-sm text-slate-500">
            Pencatatan barang yang dikembalikan oleh pelanggan. Stok barang akan otomatis dikembalikan ke gudang.
        </p>
    </div>

    @if($errors->any())
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
            <p class="font-semibold">Terjadi kesalahan pada input data:</p>
            <ul class="mt-2 list-inside list-disc space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('retur-penjualan.store') }}" method="POST" class="space-y-6">
        @csrf

        {{-- HEADER INFO --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-700">Informasi Retur</h3>
            <div class="mt-4 grid grid-cols-1 gap-5 md:grid-cols-3">
                {{-- Nomor Retur --}}
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">Nomor Retur</label>
                    <input
                        type="text"
                        name="nomor_retur"
                        value="{{ old('nomor_retur', $nomorRetur) }}"
                        required
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-semibold text-[#15235F] focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                    >
                </div>

                {{-- Invoice Asal --}}
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                        Pilih Faktur/Invoice Penjualan <span class="text-rose-500">*</span>
                    </label>
                    <select
                        name="invoice_penjualan_id"
                        onchange="window.location.href='{{ route('retur-penjualan.create') }}?invoice_id=' + this.value"
                        required
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                    >
                        <option value="">-- Pilih Faktur Penjualan --</option>
                        @foreach($invoices as $inv)
                            <option value="{{ $inv->id }}" {{ (old('invoice_penjualan_id', $selectedInvoice->id ?? request('invoice_id')) == $inv->id) ? 'selected' : '' }}>
                                {{ $inv->nomor_invoice }} - {{ $inv->customer->nama_customer ?? '' }} ({{ $inv->tanggal_invoice ? $inv->tanggal_invoice->format('d/m/Y') : '' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Gudang Masuk --}}
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                        Gudang Pengembalian Stok <span class="text-rose-500">*</span>
                    </label>
                    <select
                        name="gudang_id"
                        required
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                    >
                        <option value="">-- Pilih Gudang --</option>
                        @foreach($gudangs as $gudang)
                            <option value="{{ $gudang->id }}" {{ (old('gudang_id', $selectedInvoice->gudang_id ?? '') == $gudang->id) ? 'selected' : '' }}>
                                {{ $gudang->nama_gudang }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Tanggal Retur --}}
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                        Tanggal Retur <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="date"
                        name="tanggal_retur"
                        value="{{ old('tanggal_retur', date('Y-m-d')) }}"
                        required
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                    >
                </div>
            </div>

            {{-- Alasan Retur --}}
            <div class="mt-4">
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">Alasan Retur Umum</label>
                <textarea
                    name="alasan"
                    rows="2"
                    placeholder="Contoh: Barang cacat produksi / salah ukuran / rusak saat pengiriman"
                    class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                >{{ old('alasan') }}</textarea>
            </div>
        </div>

        {{-- ITEMS TABLE --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-700">Daftar Barang Faktur yang Diretur</h3>
            <p class="mt-0.5 text-xs text-slate-400">Tentukan jumlah (qty) barang yang dikembalikan.</p>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-xs uppercase tracking-wide text-slate-400">
                            <th class="px-4 py-3 font-medium">Barang</th>
                            <th class="px-4 py-3 text-right font-medium">Qty Faktur</th>
                            <th class="px-4 py-3 text-right font-medium">Harga Satuan</th>
                            <th class="px-4 py-3 text-right font-medium" style="width: 15%">Qty Retur</th>
                            <th class="px-4 py-3 font-medium" style="width: 25%">Alasan per Barang</th>
                            <th class="px-4 py-3 text-right font-medium">Subtotal Retur</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @if(!$selectedInvoice || $selectedInvoice->items->isEmpty())
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-slate-400">
                                    Silakan pilih Faktur Penjualan terlebih dahulu untuk memuat daftar barang.
                                </td>
                            </tr>
                        @else
                            @foreach($selectedInvoice->items as $index => $item)
                                <tr>
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-slate-800">{{ $item->barang->nama_barang ?? '-' }}</div>
                                        <div class="text-xs text-slate-400">{{ $item->barang->kode_barang ?? '' }}</div>
                                        <input type="hidden" name="items[{{ $index }}][barang_id]" value="{{ $item->barang_id }}">
                                        <input type="hidden" name="items[{{ $index }}][harga_satuan]" value="{{ $item->harga_satuan }}">
                                    </td>
                                    <td class="px-4 py-3 text-right text-slate-600">
                                        {{ number_format($item->qty, 0, ',', '.') }} {{ $item->barang->satuan->nama_satuan ?? '' }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-slate-600">
                                        Rp {{ number_format($item->harga_satuan, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <input
                                            type="number"
                                            name="items[{{ $index }}][qty]"
                                            x-model.number="qtys[{{ $index }}]"
                                            max="{{ $item->qty }}"
                                            min="0"
                                            step="any"
                                            class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-right text-sm font-semibold text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                                        >
                                    </td>
                                    <td class="px-4 py-3">
                                        <input
                                            type="text"
                                            name="items[{{ $index }}][alasan]"
                                            placeholder="Keterangan..."
                                            class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                                        >
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold text-rose-600">
                                        <span x-text="formatRupiah((qtys[{{ $index }}] || 0) * {{ (float) $item->harga_satuan }})"></span>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>

            {{-- TOTAL RETUR --}}
            <div class="mt-6 flex justify-end border-t border-slate-100 pt-4">
                <div class="flex items-center gap-4 text-base font-bold text-[#15235F]">
                    <span>Total Nilai Retur:</span>
                    <span class="text-xl text-rose-600" x-text="formatRupiah(calculateTotal())"></span>
                </div>
            </div>
        </div>

        {{-- ACTIONS --}}
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('retur-penjualan.index') }}" class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">
                Batal
            </a>
            <button
                type="submit"
                :disabled="calculateTotal() <= 0"
                class="rounded-xl bg-[#15235F] px-7 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-[#101b4d] disabled:opacity-50"
            >
                Proses Retur & Kembalikan Stok
            </button>
        </div>
    </form>

</div>

<script>
function returPenjualanForm() {
    return {
        qtys: {
            @if($selectedInvoice)
                @foreach($selectedInvoice->items as $index => $item)
                    {{ $index }}: 0,
                @endforeach
            @endif
        },
        prices: {
            @if($selectedInvoice)
                @foreach($selectedInvoice->items as $index => $item)
                    {{ $index }}: {{ (float) $item->harga_satuan }},
                @endforeach
            @endif
        },
        calculateTotal() {
            let total = 0;
            for (let k in this.qtys) {
                total += (parseFloat(this.qtys[k]) || 0) * (parseFloat(this.prices[k]) || 0);
            }
            return total;
        },
        formatRupiah(num) {
            return 'Rp ' + (Math.round(num) || 0).toLocaleString('id-ID');
        }
    };
}
</script>

@endsection
