@extends('layouts.app')

@section('title', 'Tambah Uang Keluar')

@section('content')
<div class="min-h-screen bg-slate-50">
    <div class="mx-auto max-w-6xl px-4 py-6 sm:px-6 lg:px-8">

        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900">
                Tambah Uang Keluar
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Catat satu atau beberapa pengeluaran dari Kas Kecil atau rekening bank.
            </p>
        </div>

        @if ($errors->any())
            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form
            action="{{ route('uang-keluar.store') }}"
            method="POST"
            class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm"
        >
            @csrf

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        Tanggal
                    </label>

                    <input
                        type="date"
                        name="tanggal"
                        value="{{ old('tanggal', now()->format('Y-m-d')) }}"
                        required
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    >
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        Kas / Rekening Bank
                    </label>

                    <select
                        name="rekening_kas_bank_id"
                        required
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    >
                        <option value="">
                            Pilih Kas / Bank
                        </option>

                        @foreach ($rekeningKasBanks as $rekening)
                            <option
                                value="{{ $rekening->id }}"
                                @selected(
                                    (string) old('rekening_kas_bank_id')
                                    === (string) $rekening->id
                                )
                            >
                                {{ $rekening->nama_rekening }}

                                @if ($rekening->jenis === 'BANK')
                                    - {{ $rekening->nama_bank }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        Referensi
                    </label>

                    <input
                        type="text"
                        name="referensi"
                        value="{{ old('referensi') }}"
                        placeholder="Nomor bukti / invoice / referensi lainnya"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    >
                </div>

            </div>

            <div class="mt-7 border-t border-slate-200 pt-6">

                <div class="mb-4 flex items-center justify-between gap-4">

                    <div>
                        <h2 class="font-semibold text-slate-900">
                            Rincian Pengeluaran
                        </h2>

                        <p class="mt-1 text-xs text-slate-500">
                            Pilih akun biaya, isi keterangan, lalu masukkan nominalnya.
                        </p>
                    </div>

                    <button
                        type="button"
                        id="btnTambahDetail"
                        class="rounded-lg border border-[#15235F] bg-white px-4 py-2 text-sm font-semibold text-[#15235F] hover:bg-slate-50"
                    >
                        + Tambah Pengeluaran
                    </button>

                </div>

                <div class="overflow-hidden rounded-xl border border-slate-200">

                    <div class="hidden grid-cols-12 gap-3 bg-slate-100 px-4 py-3 text-xs font-semibold uppercase text-slate-600 md:grid">
                        <div class="col-span-4">
                            Akun Biaya
                        </div>

                        <div class="col-span-4">
                            Keterangan
                        </div>

                        <div class="col-span-3">
                            Jumlah
                        </div>

                        <div class="col-span-1"></div>
                    </div>

                    <div
                        id="detailContainer"
                        class="divide-y divide-slate-100"
                    >
                        @php
                            $oldDetails = old('details', [
                                [
                                    'akun_id' => '',
                                    'tujuan' => '',
                                    'jumlah' => '',
                                ]
                            ]);
                        @endphp

                        @foreach ($oldDetails as $index => $detail)

                            <div class="detail-row grid grid-cols-1 gap-3 p-4 md:grid-cols-12 md:items-center">

                                <div class="md:col-span-4">

                                    <label class="mb-1 block text-xs font-medium text-slate-500 md:hidden">
                                        Akun Biaya
                                    </label>

                                    <select
                                        name="details[{{ $index }}][akun_id]"
                                        required
                                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                                    >
                                        <option value="">
                                            Pilih Akun Biaya
                                        </option>

                                        @foreach ($akunBebans as $akun)
                                            <option
                                                value="{{ $akun->id }}"
                                                @selected(
                                                    (string) ($detail['akun_id'] ?? '')
                                                    === (string) $akun->id
                                                )
                                            >
                                                {{ $akun->kode_akun }} - {{ $akun->nama_akun }}
                                            </option>
                                        @endforeach
                                    </select>

                                </div>

                                <div class="md:col-span-4">

                                    <label class="mb-1 block text-xs font-medium text-slate-500 md:hidden">
                                        Keterangan
                                    </label>

                                    <input
                                        type="text"
                                        name="details[{{ $index }}][tujuan]"
                                        value="{{ $detail['tujuan'] ?? '' }}"
                                        required
                                        maxlength="150"
                                        placeholder="Contoh: Tagihan listrik September"
                                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                                    >

                                </div>

                                <div class="md:col-span-3">

                                    <label class="mb-1 block text-xs font-medium text-slate-500 md:hidden">
                                        Jumlah
                                    </label>

                                    <input
                                        type="number"
                                        name="details[{{ $index }}][jumlah]"
                                        value="{{ $detail['jumlah'] ?? '' }}"
                                        required
                                        min="0.01"
                                        step="0.01"
                                        placeholder="0"
                                        class="detail-jumlah w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                                    >

                                </div>

                                <div class="flex justify-end md:col-span-1">

                                    <button
                                        type="button"
                                        class="btn-hapus-detail rounded-lg border border-red-200 px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-50"
                                    >
                                        ×
                                    </button>

                                </div>

                            </div>

                        @endforeach
                    </div>

                </div>

                <div class="mt-4 flex justify-end">

                    <div class="w-full rounded-xl bg-slate-50 px-5 py-4 md:w-80">

                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-slate-600">
                                Total Pengeluaran
                            </span>

                            <span
                                id="totalPengeluaran"
                                class="text-xl font-bold text-[#15235F]"
                            >
                                Rp 0
                            </span>
                        </div>

                    </div>

                </div>

            </div>

            <div class="mt-6">

                <label class="mb-1 block text-sm font-medium text-slate-700">
                    Keterangan Umum
                </label>

                <textarea
                    name="keterangan"
                    rows="3"
                    placeholder="Keterangan tambahan transaksi (opsional)"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                >{{ old('keterangan') }}</textarea>

            </div>

            <div class="mt-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                Transaksi akan disimpan sebagai <strong>Draft</strong>.
                Saldo Kas/Bank belum berkurang sampai transaksi dicatat.
            </div>

            <div class="mt-6 flex justify-end gap-2">

                <a
                    href="{{ route('uang-keluar.index') }}"
                    class="rounded-lg border border-slate-300 px-5 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50"
                >
                    Batal
                </a>

                <button
                    type="submit"
                    class="rounded-lg bg-[#15235F] px-5 py-2 text-sm font-semibold text-white hover:opacity-90"
                >
                    Simpan Draft
                </button>

            </div>

        </form>

    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const container = document.getElementById('detailContainer');
    const btnTambah = document.getElementById('btnTambahDetail');
    const totalElement = document.getElementById('totalPengeluaran');

    let nextIndex = {{ count($oldDetails) }};

    const akunOptions = @json(
        $akunBebans->map(function ($akun) {
            return [
                'id' => $akun->id,
                'label' => $akun->kode_akun . ' - ' . $akun->nama_akun,
            ];
        })->values()
    );

    function formatRupiah(angka) {
        return new Intl.NumberFormat('id-ID', {
            maximumFractionDigits: 2
        }).format(angka);
    }

    function buildAkunOptions() {
        let html = '<option value="">Pilih Akun Biaya</option>';

        akunOptions.forEach(function (akun) {
            html += `<option value="${akun.id}">${akun.label}</option>`;
        });

        return html;
    }

    function hitungTotal() {
        let total = 0;

        container.querySelectorAll('.detail-jumlah').forEach(function (input) {
            const nilai = parseFloat(input.value);

            if (!isNaN(nilai)) {
                total += nilai;
            }
        });

        totalElement.textContent = 'Rp ' + formatRupiah(total);
    }

    function updateTombolHapus() {
        const rows = container.querySelectorAll('.detail-row');

        rows.forEach(function (row) {
            const tombol = row.querySelector('.btn-hapus-detail');

            if (rows.length <= 1) {
                tombol.disabled = true;
                tombol.classList.add('opacity-40', 'cursor-not-allowed');
            } else {
                tombol.disabled = false;
                tombol.classList.remove('opacity-40', 'cursor-not-allowed');
            }
        });
    }

    function buatBarisBaru() {
        const row = document.createElement('div');

        row.className =
            'detail-row grid grid-cols-1 gap-3 p-4 md:grid-cols-12 md:items-center';

        row.innerHTML = `
            <div class="md:col-span-4">

                <label class="mb-1 block text-xs font-medium text-slate-500 md:hidden">
                    Akun Biaya
                </label>

                <select
                    name="details[${nextIndex}][akun_id]"
                    required
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                >
                    ${buildAkunOptions()}
                </select>

            </div>

            <div class="md:col-span-4">

                <label class="mb-1 block text-xs font-medium text-slate-500 md:hidden">
                    Keterangan
                </label>

                <input
                    type="text"
                    name="details[${nextIndex}][tujuan]"
                    required
                    maxlength="150"
                    placeholder="Contoh: Tagihan internet September"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                >

            </div>

            <div class="md:col-span-3">

                <label class="mb-1 block text-xs font-medium text-slate-500 md:hidden">
                    Jumlah
                </label>

                <input
                    type="number"
                    name="details[${nextIndex}][jumlah]"
                    required
                    min="0.01"
                    step="0.01"
                    placeholder="0"
                    class="detail-jumlah w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                >

            </div>

            <div class="flex justify-end md:col-span-1">

                <button
                    type="button"
                    class="btn-hapus-detail rounded-lg border border-red-200 px-3 py-2 text-sm font-semibold text-red-600 hover:bg-red-50"
                >
                    ×
                </button>

            </div>
        `;

        container.appendChild(row);

        nextIndex++;

        updateTombolHapus();
    }

    btnTambah.addEventListener('click', function () {
        buatBarisBaru();
    });

    container.addEventListener('input', function (event) {
        if (event.target.classList.contains('detail-jumlah')) {
            hitungTotal();
        }
    });

    container.addEventListener('click', function (event) {
        const tombol = event.target.closest('.btn-hapus-detail');

        if (!tombol || tombol.disabled) {
            return;
        }

        tombol.closest('.detail-row').remove();

        updateTombolHapus();
        hitungTotal();
    });

    updateTombolHapus();
    hitungTotal();

});
</script>
@endsection