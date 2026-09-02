@extends('layouts.app')

@section('title', 'Edit Customer')

@section('content')

<div class="max-w-4xl space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold text-slate-800">Edit Data Customer</h2>
            <p class="mt-1 text-sm text-slate-500">
                Perbarui rincian data pelanggan {{ $customer->nama_customer }}.
            </p>
        </div>

        <a
            href="{{ route('customer.index') }}"
            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
        >
            Kembali
        </a>
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

    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <form action="{{ route('customer.update', $customer) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                {{-- Kode Customer --}}
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                        Kode Customer <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="kode_customer"
                        value="{{ old('kode_customer', $customer->kode_customer) }}"
                        required
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                    >
                </div>

                {{-- Nama Customer --}}
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                        Nama Customer <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="nama_customer"
                        value="{{ old('nama_customer', $customer->nama_customer) }}"
                        required
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                    >
                </div>

                {{-- Contact Person --}}
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                        Contact Person (PIC)
                    </label>
                    <input
                        type="text"
                        name="contact_person"
                        value="{{ old('contact_person', $customer->contact_person) }}"
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                    >
                </div>

                {{-- Telepon --}}
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                        Nomor Telepon / WhatsApp
                    </label>
                    <input
                        type="text"
                        name="telepon"
                        value="{{ old('telepon', $customer->telepon) }}"
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                    >
                </div>

                {{-- Email --}}
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                        Email
                    </label>
                    <input
                        type="email"
                        name="email"
                        value="{{ old('email', $customer->email) }}"
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                    >
                </div>

                {{-- NPWP --}}
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                        NPWP
                    </label>
                    <input
                        type="text"
                        name="npwp"
                        value="{{ old('npwp', $customer->npwp) }}"
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                    >
                </div>

                {{-- Kota --}}
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                        Kota
                    </label>
                    <input
                        type="text"
                        name="kota"
                        value="{{ old('kota', $customer->kota) }}"
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                    >
                </div>

                {{-- Limit Piutang --}}
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                        Limit Piutang (Rp)
                    </label>
                    <input
                        type="number"
                        name="limit_piutang"
                        value="{{ old('limit_piutang', $customer->limit_piutang) }}"
                        min="0"
                        step="1000"
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                    >
                </div>
            </div>

            {{-- Alamat --}}
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                    Alamat Lengkap
                </label>
                <textarea
                    name="alamat"
                    rows="3"
                    class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                >{{ old('alamat', $customer->alamat) }}</textarea>
            </div>

            {{-- Status Aktif --}}
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                    Status Customer
                </label>
                <div class="mt-2 flex items-center gap-6">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="radio" name="is_active" value="1" {{ old('is_active', $customer->is_active ? '1' : '0') == '1' ? 'checked' : '' }} class="text-[#32AAA6] focus:ring-[#32AAA6]">
                        <span>Aktif</span>
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="radio" name="is_active" value="0" {{ old('is_active', $customer->is_active ? '1' : '0') == '0' ? 'checked' : '' }} class="text-[#32AAA6] focus:ring-[#32AAA6]">
                        <span>Nonaktif</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-5">
                <a
                    href="{{ route('customer.index') }}"
                    class="rounded-xl border border-slate-200 px-5 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
                >
                    Batal
                </a>
                <button
                    type="submit"
                    class="rounded-xl bg-[#15235F] px-6 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-[#101b4d]"
                >
                    Simpan Perubahan
                </button>
            </div>
        </form>
    </div>

</div>

@endsection
