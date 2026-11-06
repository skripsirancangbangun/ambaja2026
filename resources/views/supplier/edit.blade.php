@extends('layouts.app')

@section('title', 'Edit Supplier')

@section('content')

<div class="mx-auto max-w-4xl space-y-6">

    <div>
        <a
            href="{{ route('supplier.index') }}"
            class="mb-3 inline-flex items-center gap-2 text-sm text-slate-500 transition hover:text-[#15235F]"
        >
            <x-icon name="arrow-left" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" /> Kembali
        </a>

        <h2 class="text-xl font-semibold text-slate-800">Edit Supplier</h2>

        <p class="mt-1 text-sm text-slate-500">
            Perbarui data supplier.
        </p>
        @if($supplier->is_active)
            <a
                href="{{ route('supplier.barang.index', $supplier) }}"
                class="mt-3 inline-flex items-center gap-2 rounded-lg bg-teal-50 px-3 py-2 text-xs font-semibold text-[#15235F] transition hover:bg-teal-100"
            >
                Kelola Daftar Barang Supplier
            </a>
        @endif
    </div>

    <form
        action="{{ route('supplier.update', $supplier) }}"
        method="POST"
        class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm"
    >
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 gap-5 p-6 md:grid-cols-2">

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">
                    Kode Supplier <span class="text-red-500">*</span>
                </label>

                <input
                    type="text"
                    name="kode_supplier"
                    value="{{ old('kode_supplier', $supplier->kode_supplier) }}"
                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                >
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">
                    Nama Supplier <span class="text-red-500">*</span>
                </label>

                <input
                    type="text"
                    name="nama_supplier"
                    value="{{ old('nama_supplier', $supplier->nama_supplier) }}"
                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                >
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">NPWP</label>

                <input
                    type="text"
                    name="npwp"
                    value="{{ old('npwp', $supplier->npwp) }}"
                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                >
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Contact Person</label>

                <input
                    type="text"
                    name="contact_person"
                    value="{{ old('contact_person', $supplier->contact_person) }}"
                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                >
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Telepon</label>

                <input
                    type="text"
                    name="telepon"
                    value="{{ old('telepon', $supplier->telepon) }}"
                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                >
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Email</label>

                <input
                    type="email"
                    name="email"
                    value="{{ old('email', $supplier->email) }}"
                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                >
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Kota</label>

                <input
                    type="text"
                    name="kota"
                    value="{{ old('kota', $supplier->kota) }}"
                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                >
            </div>

            <div>
                <label class="mb-2 block text-sm font-medium text-slate-700">Status</label>

                <select
                    name="is_active"
                    class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                >
                    <option value="1" {{ old('is_active', $supplier->is_active ? '1' : '0') == '1' ? 'selected' : '' }}>
                        Aktif
                    </option>

                    <option value="0" {{ old('is_active', $supplier->is_active ? '1' : '0') == '0' ? 'selected' : '' }}>
                        Nonaktif
                    </option>
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="mb-2 block text-sm font-medium text-slate-700">Alamat</label>

                <textarea
                    name="alamat"
                    rows="3"
                    class="w-full resize-none rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                >{{ old('alamat', $supplier->alamat) }}</textarea>
            </div>

        </div>

        <div class="flex items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4">

            <a
                href="{{ route('supplier.index') }}"
                class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100"
            >
                Batal
            </a>

            <button
                type="submit"
                class="rounded-lg bg-[#15235F] px-5 py-2.5 text-sm font-medium text-white transition hover:bg-[#101b4d]"
            >
                Simpan Perubahan
            </button>

        </div>

    </form>

</div>

@endsection