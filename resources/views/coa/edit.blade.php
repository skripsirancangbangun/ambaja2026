@extends('layouts.app')

@section('title', 'Edit Akun COA')

@section('content')

<div class="max-w-3xl space-y-6">

    <div class="flex items-center justify-between">
        <div>
            <h2 class="text-xl font-semibold text-slate-800">Edit Akun {{ $akun->kode_akun }}</h2>
            <p class="mt-1 text-sm text-slate-500">
                Perbarui data akun Chart of Accounts.
            </p>
        </div>

        <a
            href="{{ route('coa.index') }}"
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
        <form action="{{ route('coa.update', $akun) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                {{-- Kode Akun --}}
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                        Kode Akun <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="kode_akun"
                        value="{{ old('kode_akun', $akun->kode_akun) }}"
                        required
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-mono text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                    >
                </div>

                {{-- Nama Akun --}}
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                        Nama Akun <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="text"
                        name="nama_akun"
                        value="{{ old('nama_akun', $akun->nama_akun) }}"
                        required
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                    >
                </div>

                {{-- Kategori Akun --}}
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                        Kategori Akun <span class="text-rose-500">*</span>
                    </label>
                    <select
                        name="kategori_akun"
                        required
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                    >
                        @foreach(['Aset', 'Kewajiban', 'Ekuitas', 'Pendapatan', 'Beban'] as $kat)
                            <option value="{{ $kat }}" {{ old('kategori_akun', $akun->kategori_akun) == $kat ? 'selected' : '' }}>{{ $kat }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Sub Kategori --}}
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                        Sub Kategori
                    </label>
                    <input
                        type="text"
                        name="sub_kategori"
                        value="{{ old('sub_kategori', $akun->sub_kategori) }}"
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                    >
                </div>

                {{-- Saldo Normal --}}
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                        Saldo Normal <span class="text-rose-500">*</span>
                    </label>
                    <select
                        name="saldo_normal"
                        required
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm font-semibold text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                    >
                        <option value="DEBIT" {{ old('saldo_normal', $akun->saldo_normal) == 'DEBIT' ? 'selected' : '' }}>DEBIT</option>
                        <option value="KREDIT" {{ old('saldo_normal', $akun->saldo_normal) == 'KREDIT' ? 'selected' : '' }}>KREDIT</option>
                    </select>
                </div>

                {{-- Saldo Awal --}}
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                        Saldo Awal (Rp)
                        @if(!($canEditOpeningBalance ?? true))
                            <span class="text-rose-500 font-normal lowercase">(🔒 Terkunci - Sistem Berjalan)</span>
                        @endif
                    </label>
                    <input
                        type="number"
                        name="saldo_awal"
                        value="{{ old('saldo_awal', $akun->saldo_awal) }}"
                        step="100"
                        {{ !($canEditOpeningBalance ?? true) ? 'disabled' : '' }}
                        class="mt-2 w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:border-[#32AAA6] focus:bg-white focus:outline-none {{ !($canEditOpeningBalance ?? true) ? 'cursor-not-allowed opacity-60' : '' }}"
                    >
                    @if(!($canEditOpeningBalance ?? true))
                        <p class="mt-1 text-[11px] text-rose-500">
                            Saldo awal dikunci karena transaksi operasional sudah berjalan. Penyesuaian saldo hanya dapat dilakukan melalui Jurnal Penyesuaian.
                        </p>
                    @endif
                </div>
            </div>

            {{-- Status Aktif --}}
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">
                    Status Akun
                </label>
                <div class="mt-2 flex items-center gap-6">
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="radio" name="is_active" value="1" {{ old('is_active', $akun->is_active ? '1' : '0') == '1' ? 'checked' : '' }} class="text-[#32AAA6] focus:ring-[#32AAA6]">
                        <span>Aktif</span>
                    </label>
                    <label class="inline-flex items-center gap-2 text-sm text-slate-700">
                        <input type="radio" name="is_active" value="0" {{ old('is_active', $akun->is_active ? '1' : '0') == '0' ? 'checked' : '' }} class="text-[#32AAA6] focus:ring-[#32AAA6]">
                        <span>Nonaktif</span>
                    </label>
                </div>
            </div>

            <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-5">
                <a
                    href="{{ route('coa.index') }}"
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
