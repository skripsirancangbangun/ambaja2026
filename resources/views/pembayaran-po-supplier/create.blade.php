@extends('layouts.app')

@section('title', 'Pembayaran PO Supplier')

@section('content')

<div class="min-h-screen bg-slate-50">

    <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8">

        {{-- HEADER --}}
        <div class="mb-6">

            <a
                href="{{ route('purchase-order.show', $purchaseOrder) }}"
                class="inline-flex items-center text-sm font-medium text-slate-500 transition hover:text-[#0E194F]"
            >
                ← Kembali ke Purchase Order
            </a>

            <div class="mt-3">

                <h1 class="text-2xl font-bold text-slate-900">
                    Pembayaran PO Supplier
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Pembayaran dapat dilakukan sebagian maupun penuh walaupun barang belum seluruhnya diterima.
                </p>

            </div>

        </div>


        {{-- SUCCESS --}}
        @if(session('success'))

            <div class="mb-5 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('success') }}
            </div>

        @endif


        {{-- ERROR SESSION --}}
        @if(session('error'))

            <div class="mb-5 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ session('error') }}
            </div>

        @endif


        {{-- VALIDATION ERROR --}}
        @if($errors->any())

            <div class="mb-5 rounded-xl border border-red-200 bg-red-50 p-4">

                <div class="font-semibold text-red-700">
                    Data belum dapat disimpan.
                </div>

                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-600">

                    @foreach($errors->all() as $error)

                        <li>
                            {{ $error }}
                        </li>

                    @endforeach

                </ul>

            </div>

        @endif


        {{-- INFORMASI PO --}}
        <div class="mb-6 overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">

                <h2 class="font-bold text-slate-900">
                    Informasi Purchase Order
                </h2>

            </div>


            <div class="p-6">

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">

                    {{-- NOMOR PO --}}
                    <div>

                        <div class="text-xs font-medium uppercase tracking-wide text-slate-500">
                            Nomor PO
                        </div>

                        <div class="mt-1 font-semibold text-slate-900">
                            {{ $purchaseOrder->nomor_po }}
                        </div>

                    </div>


                    {{-- SUPPLIER --}}
                    <div>

                        <div class="text-xs font-medium uppercase tracking-wide text-slate-500">
                            Supplier
                        </div>

                        <div class="mt-1 font-semibold text-slate-900">
                            {{ $purchaseOrder->supplier?->nama_supplier ?? '-' }}
                        </div>

                    </div>


                    {{-- TANGGAL PO --}}
                    <div>

                        <div class="text-xs font-medium uppercase tracking-wide text-slate-500">
                            Tanggal PO
                        </div>

                        <div class="mt-1 font-semibold text-slate-900">
                            {{ optional($purchaseOrder->tanggal_po)->format('d/m/Y') }}
                        </div>

                    </div>


                    {{-- STATUS PO --}}
                    <div>

                        <div class="text-xs font-medium uppercase tracking-wide text-slate-500">
                            Status PO
                        </div>

                        <div class="mt-1">

                            <span class="inline-flex rounded-full bg-blue-100 px-3 py-1 text-xs font-bold text-blue-700">
                                {{ strtoupper($purchaseOrder->status) }}
                            </span>

                        </div>

                    </div>

                </div>


                {{-- RINGKASAN BARANG --}}
                <div class="mt-6 grid grid-cols-1 gap-4 border-t border-slate-100 pt-6 sm:grid-cols-2 lg:grid-cols-4">

                    <div class="rounded-lg bg-slate-50 p-4">

                        <div class="text-xs text-slate-500">
                            Qty Dipesan
                        </div>

                        <div class="mt-1 text-lg font-bold text-slate-900">
                            {{ number_format((float) $purchaseOrder->total_qty_pesan, 2, ',', '.') }}
                        </div>

                    </div>


                    <div class="rounded-lg bg-slate-50 p-4">

                        <div class="text-xs text-slate-500">
                            Qty Diterima
                        </div>

                        <div class="mt-1 text-lg font-bold text-emerald-700">
                            {{ number_format((float) $purchaseOrder->total_qty_diterima, 2, ',', '.') }}
                        </div>

                    </div>


                    <div class="rounded-lg bg-slate-50 p-4">

                        <div class="text-xs text-slate-500">
                            Sisa Barang
                        </div>

                        <div class="mt-1 text-lg font-bold text-amber-700">
                            {{ number_format((float) $purchaseOrder->total_qty_sisa, 2, ',', '.') }}
                        </div>

                    </div>


                    <div class="rounded-lg bg-slate-50 p-4">

                        <div class="text-xs text-slate-500">
                            Status Penerimaan
                        </div>

                        <div class="mt-1 font-bold text-slate-900">
                            {{ $purchaseOrder->status_penerimaan }}
                        </div>

                        <div class="mt-1 text-xs text-slate-500">
                            {{ number_format((float) $purchaseOrder->persentase_penerimaan, 2, ',', '.') }}%
                        </div>

                    </div>

                </div>

            </div>

        </div>


        {{-- RINGKASAN PEMBAYARAN --}}
        <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">

            {{-- TOTAL PO --}}
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="text-xs font-medium uppercase tracking-wide text-slate-500">
                    Total PO
                </div>

                <div class="mt-2 text-2xl font-bold text-slate-900">
                    Rp {{ number_format((float) $purchaseOrder->total, 0, ',', '.') }}
                </div>

            </div>


            {{-- SUDAH DIBAYAR --}}
            <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-5">

                <div class="text-xs font-medium uppercase tracking-wide text-emerald-700">
                    Sudah Dibayar
                </div>

                <div class="mt-2 text-2xl font-bold text-emerald-800">
                    Rp {{ number_format((float) $purchaseOrder->total_dibayar_po, 0, ',', '.') }}
                </div>

            </div>


            {{-- SISA --}}
            <div class="rounded-xl border border-blue-200 bg-blue-50 p-5">

                <div class="text-xs font-medium uppercase tracking-wide text-blue-700">
                    Sisa Pembayaran
                </div>

                <div class="mt-2 text-2xl font-bold text-blue-900">
                    Rp {{ number_format((float) $purchaseOrder->sisa_pembayaran_po, 0, ',', '.') }}
                </div>

            </div>

        </div>


        {{-- STATUS PEMBAYARAN --}}
        <div class="mb-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

                <div>

                    <div class="text-sm font-semibold text-slate-700">
                        Status Pembayaran
                    </div>

                    <div class="mt-1">

                        @if($purchaseOrder->status_pembayaran === 'LUNAS')

                            <span class="inline-flex rounded-full bg-emerald-100 px-3 py-1 text-xs font-bold text-emerald-700">
                                LUNAS
                            </span>

                        @elseif($purchaseOrder->status_pembayaran === 'SEBAGIAN')

                            <span class="inline-flex rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-700">
                                SEBAGIAN
                            </span>

                        @else

                            <span class="inline-flex rounded-full bg-slate-100 px-3 py-1 text-xs font-bold text-slate-600">
                                BELUM DIBAYAR
                            </span>

                        @endif

                    </div>

                </div>


                <div class="sm:text-right">

                    <div class="text-sm text-slate-500">
                        Progress Pembayaran
                    </div>

                    <div class="mt-1 font-bold text-slate-900">
                        {{ number_format((float) $purchaseOrder->persentase_pembayaran, 2, ',', '.') }}%
                    </div>

                </div>

            </div>


            {{-- PROGRESS BAR --}}
            <div class="mt-4 h-2 overflow-hidden rounded-full bg-slate-200">

                <div
                    class="h-full rounded-full bg-[#0E194F]"
                    style="width: {{ min(100, (float) $purchaseOrder->persentase_pembayaran) }}%"
                ></div>

            </div>

        </div>


        {{-- FORM PEMBAYARAN --}}
        <form
            method="POST"
            action="{{ route('pembayaran-po-supplier.store', $purchaseOrder) }}"
            class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm"
        >

            @csrf


            <div class="border-b border-slate-200 bg-slate-50 px-6 py-4">

                <h2 class="font-bold text-slate-900">
                    Data Pembayaran
                </h2>

                <p class="mt-1 text-xs text-slate-500">
                    Pembayaran akan disimpan sebagai Draft terlebih dahulu sebelum dicatat ke Kas/Bank.
                </p>

            </div>


            <div class="p-6">

                <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                    {{-- TANGGAL --}}
                    <div>

                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">
                            Tanggal Pembayaran
                            <span class="text-red-500">*</span>
                        </label>

                        <input
                            type="date"
                            name="tanggal_pembayaran"
                            value="{{ old('tanggal_pembayaran', now()->format('Y-m-d')) }}"
                            min="{{ optional($purchaseOrder->tanggal_po)->format('Y-m-d') }}"
                            required
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-[#0E194F] focus:ring-2 focus:ring-[#0E194F]/10"
                        >

                        @error('tanggal_pembayaran')

                            <div class="mt-1 text-xs text-red-600">
                                {{ $message }}
                            </div>

                        @enderror

                    </div>


                    {{-- REKENING --}}
                    <div>

                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">
                            Bayar Dari Kas / Bank
                            <span class="text-red-500">*</span>
                        </label>

                        <select
                            name="rekening_kas_bank_id"
                            required
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-[#0E194F] focus:ring-2 focus:ring-[#0E194F]/10"
                        >

                            <option value="">
                                -- Pilih Kas / Bank --
                            </option>

                            @foreach($rekeningKasBanks as $rekening)

                                <option
                                    value="{{ $rekening->id }}"
                                    @selected(
                                        old('rekening_kas_bank_id')
                                        == $rekening->id
                                    )
                                >
                                    {{ $rekening->nama_rekening }}

                                    @if($rekening->jenis === 'BANK' && $rekening->nama_bank)
                                        - {{ $rekening->nama_bank }}
                                    @endif
                                </option>

                            @endforeach

                        </select>

                        @error('rekening_kas_bank_id')

                            <div class="mt-1 text-xs text-red-600">
                                {{ $message }}
                            </div>

                        @enderror

                    </div>


                    {{-- JUMLAH BAYAR --}}
                    <div>

                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">
                            Jumlah Pembayaran
                            <span class="text-red-500">*</span>
                        </label>

                        <div class="relative">

                            <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-sm font-medium text-slate-500">
                                Rp
                            </div>

                            <input
                                id="jumlah_bayar"
                                type="number"
                                name="jumlah_bayar"
                                value="{{ old('jumlah_bayar', (float) $purchaseOrder->sisa_pembayaran_po) }}"
                                min="0.01"
                                max="{{ (float) $purchaseOrder->sisa_pembayaran_po }}"
                                step="0.01"
                                required
                                class="w-full rounded-lg border border-slate-300 bg-white py-2.5 pl-10 pr-3 text-sm text-slate-900 outline-none transition focus:border-[#0E194F] focus:ring-2 focus:ring-[#0E194F]/10"
                            >

                        </div>


                        <div class="mt-2 flex flex-wrap gap-2">

                            <button
                                type="button"
                                onclick="setBayarPenuh()"
                                class="rounded-lg border border-[#0E194F] px-3 py-1.5 text-xs font-semibold text-[#0E194F] transition hover:bg-[#0E194F] hover:text-white"
                            >
                                Bayar Penuh
                            </button>

                            <button
                                type="button"
                                onclick="setBayarPersen(50)"
                                class="rounded-lg border border-slate-300 px-3 py-1.5 text-xs font-semibold text-slate-600 transition hover:bg-slate-100"
                            >
                                50%
                            </button>

                        </div>


                        <p class="mt-2 text-xs text-slate-500">
                            Maksimal:
                            Rp {{ number_format((float) $purchaseOrder->sisa_pembayaran_po, 0, ',', '.') }}
                        </p>


                        @error('jumlah_bayar')

                            <div class="mt-1 text-xs text-red-600">
                                {{ $message }}
                            </div>

                        @enderror

                    </div>


                    {{-- METODE --}}
                    <div>

                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">
                            Metode Pembayaran
                        </label>

                        <select
                            name="metode_pembayaran"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-[#0E194F] focus:ring-2 focus:ring-[#0E194F]/10"
                        >

                            <option
                                value="Transfer"
                                @selected(
                                    old(
                                        'metode_pembayaran',
                                        'Transfer'
                                    ) === 'Transfer'
                                )
                            >
                                Transfer Bank
                            </option>

                            <option
                                value="Tunai"
                                @selected(
                                    old('metode_pembayaran')
                                    === 'Tunai'
                                )
                            >
                                Tunai
                            </option>

                            <option
                                value="Giro"
                                @selected(
                                    old('metode_pembayaran')
                                    === 'Giro'
                                )
                            >
                                Giro
                            </option>

                            <option
                                value="Lainnya"
                                @selected(
                                    old('metode_pembayaran')
                                    === 'Lainnya'
                                )
                            >
                                Lainnya
                            </option>

                        </select>

                    </div>


                    {{-- NOMOR REFERENSI --}}
                    <div class="md:col-span-2">

                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">
                            Nomor Referensi / Nomor Transfer
                        </label>

                        <input
                            type="text"
                            name="nomor_referensi"
                            value="{{ old('nomor_referensi') }}"
                            maxlength="100"
                            placeholder="Contoh: TRX-BCA-230926001"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-[#0E194F] focus:ring-2 focus:ring-[#0E194F]/10"
                        >

                        <p class="mt-1 text-xs text-slate-500">
                            Opsional. Bisa diisi nomor transaksi dari mobile banking atau bukti transfer.
                        </p>

                    </div>


                    {{-- KETERANGAN --}}
                    <div class="md:col-span-2">

                        <label class="mb-1.5 block text-sm font-semibold text-slate-700">
                            Keterangan
                        </label>

                        <textarea
                            name="keterangan"
                            rows="3"
                            maxlength="1000"
                            placeholder="Contoh: Pembayaran penuh PO. Barang akan diambil secara bertahap."
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-900 outline-none transition focus:border-[#0E194F] focus:ring-2 focus:ring-[#0E194F]/10"
                        >{{ old('keterangan') }}</textarea>

                    </div>

                </div>

            </div>


            {{-- FOOTER --}}
            <div class="flex flex-col-reverse gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:flex-row sm:justify-end">

                <a
                    href="{{ route('purchase-order.show', $purchaseOrder) }}"
                    class="inline-flex justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                >
                    Batal
                </a>

                <button
                    type="submit"
                    class="inline-flex justify-center rounded-lg bg-[#0E194F] px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-[#16266f]"
                >
                    Simpan Draft Pembayaran
                </button>

            </div>

        </form>

    </div>

</div>


<script>
    const sisaPembayaranPO =
        {{ (float) $purchaseOrder->sisa_pembayaran_po }};

    function setBayarPenuh() {
        const input =
            document.getElementById('jumlah_bayar');

        if (input) {
            input.value =
                sisaPembayaranPO.toFixed(2);
        }
    }

    function setBayarPersen(persen) {
        const input =
            document.getElementById('jumlah_bayar');

        if (!input) {
            return;
        }

        const nilai =
            sisaPembayaranPO
            * persen
            / 100;

        input.value =
            nilai.toFixed(2);
    }
</script>

@endsection