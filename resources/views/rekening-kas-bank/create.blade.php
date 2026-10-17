@extends('layouts.app')

@section('title', 'Tambah Kas & Rekening Bank')

@section('content')
<div class="min-h-screen bg-slate-50">
    <div class="mx-auto max-w-4xl px-4 py-6 sm:px-6 lg:px-8">

        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900">
                Tambah Kas & Rekening Bank
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Setiap Kas/Rekening Bank wajib dihubungkan ke satu akun COA.
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
            method="POST"
            action="{{ route('rekening-kas-bank.store') }}"
            class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm"
        >
            @csrf

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                <div class="md:col-span-2">
                    <label
                        for="akun_id"
                        class="mb-1 block text-sm font-medium text-slate-700"
                    >
                        Akun COA
                        <span class="text-red-500">*</span>
                    </label>

                    <select
                        name="akun_id"
                        id="akun_id"
                        required
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-[#32AAA6]/20"
                    >
                        <option value="">
                            Pilih akun Kas/Bank pada Chart of Accounts
                        </option>

                        @foreach ($akunKasBanks as $akun)
                            <option
                                value="{{ $akun->id }}"
                                data-saldo-awal="{{ (float) $akun->saldo_awal }}"
                                @selected((string) old('akun_id') === (string) $akun->id)
                            >
                                {{ $akun->kode_akun }} - {{ $akun->nama_akun }}
                            </option>
                        @endforeach
                    </select>

                    <p class="mt-1.5 text-xs text-slate-500">
                        Saldo awal rekening akan mengikuti saldo awal akun COA agar saldo Kas/Bank dan Buku Besar tetap sama.
                    </p>
                </div>


                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        Kode Rekening
                    </label>

                    <input
                        type="text"
                        name="kode_rekening"
                        value="{{ old('kode_rekening') }}"
                        placeholder="Contoh: KAS-001"
                        required
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-[#32AAA6]/20"
                    >
                </div>


                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        Nama Rekening
                    </label>

                    <input
                        type="text"
                        name="nama_rekening"
                        value="{{ old('nama_rekening') }}"
                        placeholder="Contoh: Kas Toko / BCA"
                        required
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-[#32AAA6]/20"
                    >
                </div>


                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        Jenis
                    </label>

                    <select
                        name="jenis"
                        id="jenis"
                        required
                        onchange="toggleBankFields()"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-[#32AAA6]/20"
                    >
                        <option value="">Pilih Jenis</option>

                        <option value="KAS" @selected(old('jenis') === 'KAS')>
                            Kas
                        </option>

                        <option value="BANK" @selected(old('jenis') === 'BANK')>
                            Bank
                        </option>
                    </select>
                </div>


                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        Saldo Awal dari COA
                    </label>

                    <input
                        type="text"
                        id="saldo_awal_preview"
                        value="Rp 0"
                        readonly
                        class="w-full cursor-not-allowed rounded-lg border border-slate-200 bg-slate-50 px-3 py-2 text-sm font-semibold text-slate-700"
                    >
                </div>


                <div class="bank-field">
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        Nama Bank
                    </label>

                    <input
                        type="text"
                        name="nama_bank"
                        value="{{ old('nama_bank') }}"
                        placeholder="Contoh: BCA"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-[#32AAA6]/20"
                    >
                </div>


                <div class="bank-field">
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        Nomor Rekening
                    </label>

                    <input
                        type="text"
                        name="nomor_rekening"
                        value="{{ old('nomor_rekening') }}"
                        placeholder="Nomor rekening bank"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-[#32AAA6]/20"
                    >
                </div>


                <div class="bank-field">
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        Atas Nama
                    </label>

                    <input
                        type="text"
                        name="atas_nama"
                        value="{{ old('atas_nama') }}"
                        placeholder="Contoh: CV Andika Mustika"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-[#32AAA6]/20"
                    >
                </div>


                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        Status
                    </label>

                    <select
                        name="is_active"
                        required
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-[#32AAA6]/20"
                    >
                        <option value="1" @selected(old('is_active', '1') === '1')>
                            Aktif
                        </option>

                        <option value="0" @selected(old('is_active') === '0')>
                            Nonaktif
                        </option>
                    </select>
                </div>


                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        Catatan
                    </label>

                    <textarea
                        name="catatan"
                        rows="3"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-[#32AAA6]/20"
                    >{{ old('catatan') }}</textarea>
                </div>

            </div>


            <div class="mt-6 rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-700">
                Satu akun COA hanya digunakan untuk satu Kas/Rekening Bank. Contoh: Bank BCA → akun 1102 Bank BCA.
            </div>


            <div class="mt-6 flex justify-end gap-2">

                <a
                    href="{{ route('rekening-kas-bank.index') }}"
                    class="rounded-lg border border-slate-300 bg-white px-5 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100"
                >
                    Batal
                </a>

                <button
                    type="submit"
                    class="rounded-lg bg-[#15235F] px-5 py-2 text-sm font-semibold text-white hover:opacity-90"
                >
                    Simpan
                </button>

            </div>
        </form>

    </div>
</div>


<script>
function toggleBankFields() {
    const jenis = document.getElementById('jenis').value;
    const bankFields = document.querySelectorAll('.bank-field');

    bankFields.forEach(function (field) {
        field.style.display = jenis === 'BANK'
            ? 'block'
            : 'none';
    });
}

function updateSaldoAwalPreview() {
    const akunSelect = document.getElementById('akun_id');
    const preview = document.getElementById('saldo_awal_preview');

    const option =
        akunSelect.options[
            akunSelect.selectedIndex
        ];

    const saldo =
        option
            ? parseFloat(
                option.dataset.saldoAwal || '0'
            )
            : 0;

    preview.value =
        'Rp ' +
        new Intl.NumberFormat(
            'id-ID',
            {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }
        ).format(saldo);
}

document.addEventListener('DOMContentLoaded', function () {
    toggleBankFields();
    updateSaldoAwalPreview();

    document
        .getElementById('akun_id')
        .addEventListener(
            'change',
            updateSaldoAwalPreview
        );
});
</script>
@endsection
