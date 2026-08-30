@extends('layouts.app')

@section('title', 'Tambah Gudang')

@section('content')

<div class="mx-auto max-w-3xl space-y-6">

    <div>
        <a
            href="{{ route('gudang.index') }}"
            class="mb-3 inline-flex items-center gap-2 text-sm text-slate-500 transition hover:text-[#15235F]"
        >
            <span>←</span>
            Kembali
        </a>

        <h2 class="text-xl font-semibold text-slate-800">
            Tambah Gudang
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            Tambahkan lokasi gudang baru.
        </p>
    </div>


    <form
        action="{{ route('gudang.store') }}"
        method="POST"
        class="rounded-2xl border border-slate-200 bg-white shadow-sm"
    >

        @csrf

        <div class="space-y-5 p-6">

            <div>
                <label for="kode_gudang" class="mb-2 block text-sm font-medium text-slate-700">
                    Kode Gudang
                    <span class="text-red-500">*</span>
                </label>

                <input
                    type="text"
                    id="kode_gudang"
                    name="kode_gudang"
                    value="{{ old('kode_gudang') }}"
                    placeholder="Contoh: GDG-01"
                    class="w-full rounded-lg border px-3.5 py-2.5 text-sm text-slate-700 outline-none transition
                    {{ $errors->has('kode_gudang')
                        ? 'border-red-300 focus:border-red-400 focus:ring-2 focus:ring-red-100'
                        : 'border-slate-300 focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100'
                    }}"
                >

                @error('kode_gudang')
                    <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>


            <div>
                <label for="nama_gudang" class="mb-2 block text-sm font-medium text-slate-700">
                    Nama Gudang
                    <span class="text-red-500">*</span>
                </label>

                <input
                    type="text"
                    id="nama_gudang"
                    name="nama_gudang"
                    value="{{ old('nama_gudang') }}"
                    placeholder="Contoh: Gudang Utama"
                    class="w-full rounded-lg border px-3.5 py-2.5 text-sm text-slate-700 outline-none transition
                    {{ $errors->has('nama_gudang')
                        ? 'border-red-300 focus:border-red-400 focus:ring-2 focus:ring-red-100'
                        : 'border-slate-300 focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100'
                    }}"
                >

                @error('nama_gudang')
                    <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>


            <div>
                <label for="alamat" class="mb-2 block text-sm font-medium text-slate-700">
                    Alamat
                </label>

                <textarea
                    id="alamat"
                    name="alamat"
                    rows="3"
                    placeholder="Lokasi atau alamat gudang"
                    class="w-full resize-none rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-700 outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                >{{ old('alamat') }}</textarea>

                @error('alamat')
                    <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>


            <div>
                <label for="keterangan" class="mb-2 block text-sm font-medium text-slate-700">
                    Keterangan
                </label>

                <textarea
                    id="keterangan"
                    name="keterangan"
                    rows="3"
                    placeholder="Keterangan gudang (opsional)"
                    class="w-full resize-none rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-700 outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                >{{ old('keterangan') }}</textarea>

                @error('keterangan')
                    <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                @enderror
            </div>


            <div>
                <label for="is_active" class="mb-2 block text-sm font-medium text-slate-700">
                    Status
                    <span class="text-red-500">*</span>
                </label>

                <select
                    id="is_active"
                    name="is_active"
                    class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-700 outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                >
                    <option value="1" {{ old('is_active', '1') == '1' ? 'selected' : '' }}>
                        Aktif
                    </option>

                    <option value="0" {{ old('is_active') == '0' ? 'selected' : '' }}>
                        Nonaktif
                    </option>
                </select>
            </div>

        </div>


        <div class="flex items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4">

            <a
                href="{{ route('gudang.index') }}"
                class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100"
            >
                Batal
            </a>

            <button
                type="submit"
                class="rounded-lg bg-[#15235F] px-5 py-2.5 text-sm font-medium text-white transition hover:bg-[#101b4d]"
            >
                Simpan Gudang
            </button>

        </div>

    </form>

</div>

@endsection