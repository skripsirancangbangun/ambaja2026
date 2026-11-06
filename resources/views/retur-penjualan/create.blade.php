@extends('layouts.app')

@section('title', 'Buat Nota Retur Penjualan')

@section('content')

<div class="mx-auto max-w-4xl space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold text-slate-800">Form Nota Retur Penjualan</h2>
            <p class="mt-1 text-sm text-slate-500">
                Pilih invoice asal untuk memuat daftar barang yang dikembalikan. Tiap retur dipotong 20% dari harga jual (persentase dapat disesuaikan).
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
        @php
            $itemsJson = $selectedInvoice->items->map(function($item) {
                return [
                    'barang_id' => $item->barang_id,
                    'nama_barang' => $item->barang?->nama_barang ?? '-',
                    'kode_barang' => $item->barang?->kode_barang ?? '-',
                    'satuan' => $item->barang?->satuan?->nama_satuan ?? '',
                    'harga_satuan' => (float) $item->harga_satuan,
                    'max_qty' => (float) $item->qty,
                    'qty' => 0,
                ];
            });

            $invoicePajakPersen = ($selectedInvoice->pajak > 0 && $selectedInvoice->subtotal > 0)
                ? round(($selectedInvoice->pajak / $selectedInvoice->subtotal) * 100, 2)
                : 0;
        @endphp

        <form
            method="POST"
            action="{{ route('retur-penjualan.store') }}"
            class="space-y-6"
            x-data="returForm()"
        >
            @csrf

            <input type="hidden" name="invoice_penjualan_id" value="{{ $selectedInvoice->id }}">

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-6">
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 md:grid-cols-4">
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
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                            Potongan Retur (%)
                        </label>
                        <div class="relative mt-2">
                            <input
                                type="number"
                                name="potongan_persen"
                                x-model.number="potonganPersen"
                                min="0"
                                max="100"
                                step="0.5"
                                required
                                class="w-full rounded-xl border border-amber-300 bg-amber-50/50 px-4 py-2.5 pr-8 text-sm font-bold text-amber-900 outline-none focus:border-amber-500 focus:bg-white"
                            >
                            <span class="absolute inset-y-0 right-3 flex items-center text-xs font-bold text-amber-700 pointer-events-none">%</span>
                        </div>
                        <p class="mt-1 text-[11px] text-amber-700">Dipotong dari harga jual barang.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">Gudang Restock</label>
                        <input
                            type="text"
                            value="{{ $selectedInvoice->gudang?->nama_gudang ?? '-' }}"
                            disabled
                            class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-100 px-4 py-2.5 text-sm font-semibold text-slate-700 opacity-80"
                        >
                    </div>
                </div>

                {{-- TABLE ITEM RETUR --}}
                <div>
                    <h3 class="text-sm font-bold uppercase tracking-wider text-[#15235F] border-b border-slate-100 pb-2">
                        Pilih Qty Barang yang Dikembalikan
                    </h3>

                    <div class="overflow-x-auto mt-4">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-400">
                                <tr>
                                    <th class="px-4 py-3">Nama Barang</th>
                                    <th class="px-4 py-3 text-right">Harga Jual</th>
                                    <th class="px-4 py-3 text-right">Qty Beli</th>
                                    <th class="px-4 py-3 text-right w-40">Qty Retur</th>
                                    <th class="px-4 py-3 text-right">Subtotal Bruto</th>
                                </tr>
                            </thead>

                            <tbody class="divide-y divide-slate-100">
                                <template x-for="(item, index) in items" :key="index">
                                    <tr>
                                        <td class="px-4 py-3">
                                            <div class="font-semibold text-slate-800" x-text="item.nama_barang"></div>
                                            <div class="text-[11px] font-mono text-slate-400" x-text="item.kode_barang"></div>
                                            <input type="hidden" :name="`items[${index}][barang_id]`" :value="item.barang_id">
                                            <input type="hidden" :name="`items[${index}][harga_satuan]`" :value="item.harga_satuan">
                                        </td>
                                        <td class="px-4 py-3 text-right text-slate-600 font-mono" x-text="formatRupiah(item.harga_satuan)"></td>
                                        <td class="px-4 py-3 text-right font-medium text-slate-700">
                                            <span x-text="item.max_qty"></span> <span class="text-xs text-slate-400" x-text="item.satuan"></span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <input
                                                type="number"
                                                :name="`items[${index}][qty]`"
                                                x-model.number="item.qty"
                                                :max="item.max_qty"
                                                min="0"
                                                step="0.01"
                                                class="w-full rounded-xl border border-slate-300 px-3 py-1.5 text-right text-sm font-bold text-rose-600 outline-none focus:border-rose-500"
                                            >
                                        </td>
                                        <td class="px-4 py-3 text-right font-mono font-bold text-slate-800" x-text="formatRupiah(item.qty * item.harga_satuan)"></td>
                                    </tr>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- BREAKDOWN PERHITUNGAN POTONGAN --}}
                <div class="rounded-xl border border-amber-200 bg-amber-50/60 p-4">
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div class="text-xs text-amber-900 leading-relaxed">
                            <span class="font-bold">Ketentuan Pemotongan Retur:</span>
                            Setiap barang yang diretur dikenakan potongan sebesar <strong x-text="potonganPersen + '%'"></strong> dari harga jual awal.
                            Barang fisik akan di-restock ke gudang sesuai dengan HPP persediaan.
                        </div>

                        <div class="w-full sm:w-80 space-y-1.5 text-xs">
                            <div class="flex justify-between text-slate-600">
                                <span>Nilai Barang (Bruto):</span>
                                <span class="font-mono font-semibold" x-text="formatRupiah(calculateBruto())"></span>
                            </div>
                            <div class="flex justify-between text-rose-600">
                                <span>Potongan (<span x-text="potonganPersen"></span>%):</span>
                                <span class="font-mono font-semibold" x-text="'- ' + formatRupiah(calculateNominalPotongan())"></span>
                            </div>
                            <div class="flex justify-between text-slate-800 font-bold border-t border-amber-200 pt-1">
                                <span>Nilai Retur Netto:</span>
                                <span class="font-mono" x-text="formatRupiah(calculateNetto())"></span>
                            </div>
                            <template x-if="pajakPersen > 0">
                                <div class="flex justify-between text-slate-600">
                                    <span>PPN Retur (<span x-text="pajakPersen"></span>%):</span>
                                    <span class="font-mono" x-text="formatRupiah(calculatePajak())"></span>
                                </div>
                            </template>
                            <div class="flex justify-between text-emerald-800 font-extrabold text-sm border-t border-amber-300 pt-1.5">
                                <span>Total Nota Retur:</span>
                                <span class="font-mono" x-text="formatRupiah(calculateTotal())"></span>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- REKENING REFUND KAS (OPSIONAL JIKA TUNAI) --}}
                @if($selectedInvoice->tipe_pembayaran === 'Tunai')
                    <div>
                        <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                            Pengembalian Dana Tunai Dari Rekening Kas/Bank
                        </label>
                        <select
                            name="rekening_kas_bank_id"
                            class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 outline-none focus:border-[#32AAA6]"
                        >
                            <option value="">-- Rekening Kas/Bank Default Invoice --</option>
                            @foreach($rekeningKasBanks as $rek)
                                <option value="{{ $rek->id }}" {{ $selectedInvoice->rekening_kas_bank_id == $rek->id ? 'selected' : '' }}>
                                    {{ $rek->nama_rekening }} ({{ $rek->nama_bank ?? 'Kas' }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                        Alasan Retur / Catatan <span class="text-rose-500">*</span>
                    </label>
                    <textarea
                        name="alasan_retur"
                        rows="2"
                        required
                        placeholder="Contoh: Barang rusak saat pengiriman / salah ukuran..."
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 p-3 text-sm text-slate-800 outline-none focus:border-[#32AAA6]"
                    >{{ old('alasan_retur') }}</textarea>
                </div>

                <div class="flex justify-end pt-4 border-t border-slate-100">
                    <button
                        type="submit"
                        :disabled="calculateBruto() <= 0"
                        class="rounded-xl bg-rose-600 px-6 py-2.5 text-sm font-semibold text-white transition hover:bg-rose-700 disabled:bg-slate-300 disabled:cursor-not-allowed shadow-sm"
                    >
                        <x-icon name="refresh-cw" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" /> Terbitkan Nota Retur
                    </button>
                </div>
            </div>
        </form>

        <script>
        function returForm() {
            return {
                potonganPersen: {{ $defaultPotonganPersen ?? 20.00 }},
                pajakPersen: {{ $invoicePajakPersen }},
                items: @json($itemsJson),

                calculateBruto() {
                    return this.items.reduce((total, item) => {
                        const qty = parseFloat(item.qty || 0);
                        const harga = parseFloat(item.harga_satuan || 0);
                        return total + (qty * harga);
                    }, 0);
                },

                calculateNominalPotongan() {
                    const bruto = this.calculateBruto();
                    const persen = parseFloat(this.potonganPersen || 0);
                    return (bruto * persen) / 100;
                },

                calculateNetto() {
                    return Math.max(0, this.calculateBruto() - this.calculateNominalPotongan());
                },

                calculatePajak() {
                    return (this.calculateNetto() * parseFloat(this.pajakPersen || 0)) / 100;
                },

                calculateTotal() {
                    return this.calculateNetto() + this.calculatePajak();
                },

                formatRupiah(num) {
                    const val = Math.round(parseFloat(num || 0));
                    return 'Rp ' + val.toLocaleString('id-ID');
                }
            };
        }
        </script>
    @endif

</div>

@endsection
