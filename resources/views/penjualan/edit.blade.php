@extends('layouts.app')

@section('title', 'Edit Sales Order ' . $penjualan->nomor_penjualan)

@section('content')

<div class="mx-auto max-w-6xl space-y-6" x-data="salesOrderEditForm()">

    <div>
        <a href="{{ route('penjualan.show', $penjualan) }}" class="mb-3 inline-flex items-center gap-2 text-sm text-slate-500 hover:text-[#15235F]">
            ← Batal & Kembali
        </a>
        <h2 class="text-xl font-semibold text-slate-800">Edit Sales Order ({{ $penjualan->nomor_penjualan }})</h2>
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

    <form action="{{ route('penjualan.update', $penjualan) }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- HEADER --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-700">Informasi Pemesanan</h3>
            <div class="mt-4 grid grid-cols-1 gap-5 md:grid-cols-3">
                {{-- Nomor SO --}}
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">Nomor SO</label>
                    <input
                        type="text"
                        name="nomor_penjualan"
                        value="{{ old('nomor_penjualan', $penjualan->nomor_penjualan) }}"
                        required
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-medium text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                    >
                </div>

                {{-- Customer --}}
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                        Customer <span class="text-rose-500">*</span>
                    </label>
                    <select
                        name="customer_id"
                        required
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                    >
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ old('customer_id', $penjualan->customer_id) == $customer->id ? 'selected' : '' }}>
                                {{ $customer->kode_customer }} - {{ $customer->nama_customer }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Status (Read-Only) --}}
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">Status SO</label>
                    <div class="mt-2.5">
                        <span class="inline-flex rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 border border-blue-200">
                            {{ $penjualan->status }} (Otomatis)
                        </span>
                    </div>
                </div>

                {{-- Gudang --}}
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">Gudang</label>
                    <select
                        name="gudang_id"
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                    >
                        <option value="">-- Pilih Gudang (Opsional) --</option>
                        @foreach($gudangs as $gudang)
                            <option value="{{ $gudang->id }}" {{ old('gudang_id', $penjualan->gudang_id) == $gudang->id ? 'selected' : '' }}>
                                {{ $gudang->nama_gudang }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Tanggal Penjualan --}}
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">Tanggal Pesanan</label>
                    <input
                        type="date"
                        name="tanggal_penjualan"
                        value="{{ old('tanggal_penjualan', $penjualan->tanggal_penjualan ? $penjualan->tanggal_penjualan->format('Y-m-d') : date('Y-m-d')) }}"
                        required
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                    >
                </div>

                {{-- Pajak PPN % --}}
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">Pajak PPN (%)</label>
                    <input
                        type="number"
                        name="pajak_persen"
                        x-model.number="pajakPersen"
                        min="0"
                        max="100"
                        step="0.1"
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                    >
                </div>
            </div>

            {{-- Catatan --}}
            <div class="mt-4">
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">Catatan Pemesanan</label>
                <textarea
                    name="catatan"
                    rows="2"
                    class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                >{{ old('catatan', $penjualan->catatan) }}</textarea>
            </div>
        </div>

        {{-- ITEMS TABLE --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-slate-700">Daftar Barang Pesanan</h3>
                </div>
                <button
                    type="button"
                    @click="addItem()"
                    class="inline-flex items-center gap-1.5 rounded-xl bg-teal-50 px-4 py-2 text-xs font-semibold text-[#15235F] transition hover:bg-teal-100"
                >
                    + Tambah Baris
                </button>
            </div>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-xs uppercase tracking-wide text-slate-400">
                            <th class="px-4 py-3 font-medium" style="width: 35%">Barang</th>
                            <th class="px-4 py-3 font-medium" style="width: 15%">Qty</th>
                            <th class="px-4 py-3 font-medium" style="width: 20%">Harga Jual (Rp)</th>
                            <th class="px-4 py-3 font-medium" style="width: 15%">Diskon (Rp)</th>
                            <th class="px-4 py-3 font-medium text-right" style="width: 20%">Subtotal (Rp)</th>
                            <th class="px-4 py-3 text-center" style="width: 5%"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="(item, index) in items" :key="index">
                            <tr>
                                <td class="px-4 py-3">
                                    <select
                                        :name="`items[${index}][barang_id]`"
                                        x-model="item.barang_id"
                                        @change="onBarangChange(index)"
                                        required
                                        class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                                    >
                                        <option value="">-- Pilih Barang --</option>
                                        @foreach($barangs as $barang)
                                            @php $stokTotal = $barang->stokGudangs->sum('qty'); @endphp
                                            <option value="{{ $barang->id }}" data-harga="{{ $barang->harga_jual }}" data-stok="{{ $stokTotal }}">
                                                {{ $barang->kode_barang }} - {{ $barang->nama_barang }} (Stok: {{ number_format($stokTotal, 0, ',', '.') }})
                                            </option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-4 py-3">
                                    <input
                                        type="number"
                                        :name="`items[${index}][qty]`"
                                        x-model.number="item.qty"
                                        min="0.01"
                                        step="any"
                                        required
                                        class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                                    >
                                </td>
                                <td class="px-4 py-3">
                                    <input
                                        type="number"
                                        :name="`items[${index}][harga_satuan]`"
                                        x-model.number="item.harga_satuan"
                                        min="0"
                                        step="100"
                                        required
                                        class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                                    >
                                </td>
                                <td class="px-4 py-3">
                                    <input
                                        type="number"
                                        :name="`items[${index}][diskon]`"
                                        x-model.number="item.diskon"
                                        min="0"
                                        step="100"
                                        class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                                    >
                                </td>
                                <td class="px-4 py-3 text-right font-semibold text-slate-800">
                                    <span x-text="formatRupiah((item.qty * item.harga_satuan) - (item.diskon || 0))"></span>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <button
                                        type="button"
                                        @click="removeItem(index)"
                                        x-show="items.length > 1"
                                        class="text-rose-500 hover:text-rose-700"
                                    >
                                        ✕
                                    </button>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            {{-- TOTALS SUMMARY --}}
            <div class="mt-6 flex justify-end border-t border-slate-100 pt-4">
                <div class="w-full max-w-sm space-y-2 text-sm">
                    <div class="flex justify-between text-slate-500">
                        <span>Subtotal:</span>
                        <span class="font-medium text-slate-800" x-text="formatRupiah(calculateSubtotal())"></span>
                    </div>
                    <div class="flex justify-between text-slate-500">
                        <span>Total Diskon Item:</span>
                        <span class="font-medium text-slate-800" x-text="formatRupiah(calculateTotalDiskon())"></span>
                    </div>
                    <div class="flex justify-between text-slate-500">
                        <span>Pajak PPN (<span x-text="pajakPersen"></span>%):</span>
                        <span class="font-medium text-slate-800" x-text="formatRupiah(calculatePajak())"></span>
                    </div>
                    <div class="flex justify-between border-t border-slate-200 pt-2 text-base font-bold text-[#15235F]">
                        <span>Grand Total:</span>
                        <span x-text="formatRupiah(calculateGrandTotal())"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- ACTIONS --}}
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('penjualan.show', $penjualan) }}" class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">
                Batal
            </a>
            <button
                type="submit"
                class="rounded-xl bg-[#15235F] px-7 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-[#101b4d]"
            >
                Simpan Perubahan
            </button>
        </div>
    </form>

</div>

<script>
function salesOrderEditForm() {
    return {
        pajakPersen: {{ $penjualan->pajak_persen ?? 0 }},
        items: @json($initialItems),
        barangsData: @json($barangs->keyBy('id')),

        addItem() {
            this.items.push({ barang_id: '', qty: 1, harga_satuan: 0, diskon: 0 });
        },
        removeItem(index) {
            this.items.splice(index, 1);
        },
        onBarangChange(index) {
            const barangId = this.items[index].barang_id;
            if (barangId && this.barangsData[barangId]) {
                const b = this.barangsData[barangId];
                let harga = parseFloat(b.harga_jual) || 0;
                if (harga <= 0 && parseFloat(b.harga_beli_default) > 0) {
                    harga = Math.round(parseFloat(b.harga_beli_default) * 1.15);
                }
                this.items[index].harga_satuan = harga;
            }
        },
        calculateSubtotal() {
            return this.items.reduce((acc, item) => acc + (parseFloat(item.qty || 0) * parseFloat(item.harga_satuan || 0)), 0);
        },
        calculateTotalDiskon() {
            return this.items.reduce((acc, item) => acc + parseFloat(item.diskon || 0), 0);
        },
        calculatePajak() {
            const dpp = this.calculateSubtotal() - this.calculateTotalDiskon();
            return (dpp * parseFloat(this.pajakPersen || 0)) / 100;
        },
        calculateGrandTotal() {
            const dpp = this.calculateSubtotal() - this.calculateTotalDiskon();
            return dpp + this.calculatePajak();
        },
        formatRupiah(num) {
            return 'Rp ' + (Math.round(num) || 0).toLocaleString('id-ID');
        }
    };
}
</script>

@endsection
