@extends('layouts.app')

@section('title', 'Penerimaan Pembayaran Piutang')

@section('content')

<div class="mx-auto max-w-5xl space-y-6" x-data="pembayaranPiutangForm()">

    <div>
        <a href="{{ route('pembayaran-penjualan.index') }}" class="mb-3 inline-flex items-center gap-2 text-sm text-slate-500 hover:text-[#15235F]">
            ← Kembali ke Daftar Pembayaran
        </a>
        <h2 class="text-xl font-semibold text-slate-800">Catat Penerimaan Pembayaran Customer</h2>
        <p class="mt-1 text-sm text-slate-500">
            Alokasikan penerimaan pembayaran untuk melunasi faktur/piutang customer. Saldo Kas/Bank akan otomatis bertambah.
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

    <form action="{{ route('pembayaran-penjualan.store') }}" method="POST" class="space-y-6">
        @csrf

        {{-- HEADER INFO --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-700">Informasi Pembayaran</h3>
            <div class="mt-4 grid grid-cols-1 gap-5 md:grid-cols-3">
                {{-- Nomor Pembayaran --}}
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">Nomor Bukti</label>
                    <input
                        type="text"
                        name="nomor_pembayaran"
                        value="{{ old('nomor_pembayaran', $nomorPembayaran) }}"
                        required
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-semibold text-[#15235F] focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                    >
                </div>

                {{-- Customer --}}
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                        Customer <span class="text-rose-500">*</span>
                    </label>
                    <select
                        name="customer_id"
                        onchange="window.location.href='{{ route('pembayaran-penjualan.create') }}?customer_id=' + this.value"
                        required
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                    >
                        <option value="">-- Pilih Customer --</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ (old('customer_id', $selectedCustomerId) == $customer->id) ? 'selected' : '' }}>
                                {{ $customer->kode_customer }} - {{ $customer->nama_customer }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Rekening Kas / Bank --}}
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                        Masuk ke Rekening Kas/Bank <span class="text-rose-500">*</span>
                    </label>
                    <select
                        name="rekening_kas_bank_id"
                        required
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                    >
                        <option value="">-- Pilih Rekening Kas/Bank --</option>
                        @foreach($rekeningKasBanks as $rek)
                            <option value="{{ $rek->id }}" {{ old('rekening_kas_bank_id') == $rek->id ? 'selected' : '' }}>
                                {{ $rek->nama_rekening }} ({{ $rek->nama_bank ?? 'Kas' }})
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Tanggal Pembayaran --}}
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                        Tanggal Penerimaan <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="date"
                        name="tanggal_pembayaran"
                        value="{{ old('tanggal_pembayaran', date('Y-m-d')) }}"
                        required
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                    >
                </div>

                {{-- Metode Pembayaran --}}
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                        Metode Pembayaran <span class="text-rose-500">*</span>
                    </label>
                    <select
                        name="metode_pembayaran"
                        required
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                    >
                        @foreach(['Transfer', 'Tunai', 'Giro', 'Cek'] as $metode)
                            <option value="{{ $metode }}" {{ old('metode_pembayaran') == $metode ? 'selected' : '' }}>
                                {{ $metode }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Referensi Transaksi --}}
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                        No. Referensi / Ref Bank
                    </label>
                    <input
                        type="text"
                        name="referensi_transaksi"
                        value="{{ old('referensi_transaksi') }}"
                        placeholder="Contoh: TRF-BCA-98212"
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                    >
                </div>
            </div>

            {{-- Catatan --}}
            <div class="mt-4">
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">Catatan</label>
                <textarea
                    name="catatan"
                    rows="2"
                    placeholder="Keterangan tambahan..."
                    class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                >{{ old('catatan') }}</textarea>
            </div>
        </div>

        {{-- UNPAID INVOICES TABLE --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-700">Daftar Tagihan / Invoice Belum Lunas</h3>
            <p class="mt-0.5 text-xs text-slate-400">Masukkan nominal pembayaran pada invoice yang ingin dilunasi atau dicicil.</p>

            <div class="mt-4 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50">
                        <tr class="text-xs uppercase tracking-wide text-slate-400">
                            <th class="px-4 py-3 font-medium">No. Invoice</th>
                            <th class="px-4 py-3 font-medium">Tanggal</th>
                            <th class="px-4 py-3 font-medium">Jatuh Tempo</th>
                            <th class="px-4 py-3 text-right font-medium">Total Tagihan</th>
                            <th class="px-4 py-3 text-right font-medium">Sisa Piutang</th>
                            <th class="px-4 py-3 text-right font-medium" style="width: 25%">Nominal Bayar (Rp)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @if($unpaidInvoices->isEmpty())
                            <tr>
                                <td colspan="6" class="px-4 py-8 text-center text-slate-400">
                                    @if($selectedCustomerId)
                                        Customer ini tidak memiliki tagihan/invoice yang belum lunas.
                                    @else
                                        Silakan pilih customer terlebih dahulu untuk memuat daftar tagihan.
                                    @endif
                                </td>
                            </tr>
                        @else
                            @foreach($unpaidInvoices as $index => $inv)
                                <tr>
                                    <td class="px-4 py-3 font-semibold text-[#15235F]">
                                        {{ $inv->nomor_invoice }}
                                        <input type="hidden" name="invoices[{{ $index }}][id]" value="{{ $inv->id }}">
                                    </td>
                                    <td class="px-4 py-3 text-slate-600">
                                        {{ $inv->tanggal_invoice ? $inv->tanggal_invoice->format('d/m/Y') : '-' }}
                                    </td>
                                    <td class="px-4 py-3 {{ ($inv->tanggal_jatuh_tempo && $inv->tanggal_jatuh_tempo->isPast()) ? 'font-semibold text-rose-600' : 'text-slate-600' }}">
                                        {{ $inv->tanggal_jatuh_tempo ? $inv->tanggal_jatuh_tempo->format('d/m/Y') : '-' }}
                                    </td>
                                    <td class="px-4 py-3 text-right text-slate-700">
                                        Rp {{ number_format($inv->total, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-right font-semibold text-rose-600">
                                        Rp {{ number_format($inv->sisa_piutang, 0, ',', '.') }}
                                    </td>
                                    <td class="px-4 py-3 text-right">
                                        <div class="flex items-center gap-2">
                                            <button
                                                type="button"
                                                @click="allocations[{{ $index }}] = {{ (float) $inv->sisa_piutang }}"
                                                class="rounded-lg bg-teal-50 px-2 py-1 text-[11px] font-semibold text-[#15235F] hover:bg-teal-100"
                                            >
                                                Penuh
                                            </button>
                                            <input
                                                type="number"
                                                name="invoices[{{ $index }}][nominal_bayar]"
                                                x-model.number="allocations[{{ $index }}]"
                                                max="{{ $inv->sisa_piutang }}"
                                                min="0"
                                                step="100"
                                                class="w-full rounded-lg border border-slate-200 bg-slate-50 px-3 py-1.5 text-right text-sm font-semibold text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                                            >
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        @endif
                    </tbody>
                </table>
            </div>

            {{-- TOTAL DIBAYAR --}}
            <div class="mt-6 flex justify-end border-t border-slate-100 pt-4">
                <div class="flex items-center gap-4 text-base font-bold text-[#15235F]">
                    <span>Total Diterima:</span>
                    <span class="text-xl text-emerald-600" x-text="formatRupiah(calculateTotal())"></span>
                </div>
            </div>
        </div>

        {{-- ACTIONS --}}
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('pembayaran-penjualan.index') }}" class="rounded-xl border border-slate-200 bg-white px-5 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50">
                Batal
            </a>
            <button
                type="submit"
                :disabled="calculateTotal() <= 0"
                class="rounded-xl bg-[#15235F] px-7 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-[#101b4d] disabled:opacity-50"
            >
                Simpan & Tambah Kas/Bank
            </button>
        </div>
    </form>

</div>

<script>
function pembayaranPiutangForm() {
    return {
        allocations: {
            @foreach($unpaidInvoices as $index => $inv)
                {{ $index }}: 0,
            @endforeach
        },
        calculateTotal() {
            return Object.values(this.allocations).reduce((acc, val) => acc + (parseFloat(val) || 0), 0);
        },
        formatRupiah(num) {
            return 'Rp ' + (Math.round(num) || 0).toLocaleString('id-ID');
        }
    };
}
</script>

@endsection
