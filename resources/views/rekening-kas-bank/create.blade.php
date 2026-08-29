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
                Tambahkan Kas Kecil atau rekening bank perusahaan.
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
                        placeholder="Contoh: Kas Kecil"
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
                        Saldo Awal
                    </label>

                    <input
                        type="number"
                        step="0.01"
                        min="0"
                        name="saldo_awal"
                        value="{{ old('saldo_awal', 0) }}"
                        required
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-[#32AAA6]/20"
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
        if (jenis === 'BANK') {
            field.style.display = 'block';
        } else {
            field.style.display = 'none';
        }
    });
}

document.addEventListener('DOMContentLoaded', function () {
    toggleBankFields();
});
</script>
@endsection