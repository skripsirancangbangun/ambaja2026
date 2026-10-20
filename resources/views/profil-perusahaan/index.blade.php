@extends('layouts.app')

@section('title', 'Profil Perusahaan — CV Andika Mustika')

@section('content')
<div class="mx-auto max-w-4xl space-y-6">

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-slate-800">Profil Perusahaan</h1>
            <p class="mt-1 text-sm text-slate-500">Kelola informasi identitas, alamat, kontak, dan nomor rekening resmi CV Andika Mustika.</p>
        </div>
    </div>

    <form action="{{ route('profil-perusahaan.update') }}" method="POST" class="space-y-6">
        @csrf
        @method('PUT')

        {{-- CARD IDENTITAS PERUSAHAAN --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-base font-semibold text-slate-800 border-b border-slate-100 pb-3 flex items-center gap-2">
                <x-icon name="building-2" class="h-4 w-4" /> Identitas Utama Perusahaan
            </h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">Nama Perusahaan <span class="text-rose-500">*</span></label>
                    <input type="text" name="nama_perusahaan" value="{{ old('nama_perusahaan', $profil->nama_perusahaan) }}" required class="mt-1 w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm text-slate-800 focus:border-[#32AAA6] focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">Nama Singkat / Brand <span class="text-rose-500">*</span></label>
                    <input type="text" name="nama_singkat" value="{{ old('nama_singkat', $profil->nama_singkat) }}" required class="mt-1 w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm text-slate-800 focus:border-[#32AAA6] focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">Kota / Kabupaten</label>
                    <input type="text" name="kota" value="{{ old('kota', $profil->kota) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm text-slate-800 focus:border-[#32AAA6] focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">Nomor Telepon / HP / WA</label>
                    <input type="text" name="telepon" value="{{ old('telepon', $profil->telepon) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm text-slate-800 focus:border-[#32AAA6] focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">Email Resmi</label>
                    <input type="email" name="email" value="{{ old('email', $profil->email) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm text-slate-800 focus:border-[#32AAA6] focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">Website</label>
                    <input type="text" name="website" value="{{ old('website', $profil->website) }}" class="mt-1 w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm text-slate-800 focus:border-[#32AAA6] focus:outline-none">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">Alamat Lengkap Kantor / Gudang Pusat</label>
                    <textarea name="alamat" rows="2" class="mt-1 w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm text-slate-800 focus:border-[#32AAA6] focus:outline-none">{{ old('alamat', $profil->alamat) }}</textarea>
                </div>
            </div>
        </div>

        {{-- CARD REKENING KAS & BANK DOKUMEN --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <h2 class="mb-4 text-base font-semibold text-slate-800 border-b border-slate-100 pb-3 flex items-center gap-2">
                <x-icon name="credit-card" class="h-4 w-4" /> Rekening Pembayaran Resmi Dokumen / Cetakan Surat
            </h2>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">Rekening BCA</label>
                    <input type="text" name="rekening_bca" value="{{ old('rekening_bca', $profil->rekening_bca) }}" placeholder="123-456-7890 a.n CV Andika Mustika" class="mt-1 w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm text-slate-800 focus:border-[#32AAA6] focus:outline-none">
                </div>
                <div>
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">Rekening Mandiri</label>
                    <input type="text" name="rekening_mandiri" value="{{ old('rekening_mandiri', $profil->rekening_mandiri) }}" placeholder="987-654-3210 a.n CV Andika Mustika" class="mt-1 w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm text-slate-800 focus:border-[#32AAA6] focus:outline-none">
                </div>
                <div class="sm:col-span-2">
                    <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">Catatan / Keterangan Default di Footer Faktur</label>
                    <textarea name="catatan_dokumen" rows="2" class="mt-1 w-full rounded-xl border border-slate-200 px-3.5 py-2 text-sm text-slate-800 focus:border-[#32AAA6] focus:outline-none">{{ old('catatan_dokumen', $profil->catatan_dokumen) }}</textarea>
                </div>
            </div>
        </div>

        <div class="flex justify-end">
            <button type="submit" class="rounded-xl bg-[#15235F] px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-[#101b4d]">
                <x-icon name="save" class="mr-1 inline-block h-4 w-4 align-[-0.125em]" /> Simpan Perubahan Profil
            </button>
        </div>
    </form>
</div>
@endsection
