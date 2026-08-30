@extends('layouts.app')

@section('title', 'Tambah Kategori Barang')

@section('content')

<div class="mx-auto max-w-3xl space-y-6">

    {{-- HEADER --}}
    <div>

        <a
            href="{{ route('kategori-barang.index') }}"
            class="mb-3 inline-flex items-center gap-2 text-sm text-slate-500 transition hover:text-[#15235F]"
        >
            <span>←</span>
            Kembali
        </a>

        <h2 class="text-xl font-semibold text-slate-800">
            Tambah Kategori Barang
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            Tambahkan kategori baru untuk pengelompokan barang.
        </p>

    </div>


    {{-- FORM --}}
    <form
        action="{{ route('kategori-barang.store') }}"
        method="POST"
        class="rounded-2xl border border-slate-200 bg-white shadow-sm"
    >

        @csrf


        <div class="space-y-5 p-6">

            {{-- KODE --}}
            <div>

                <label
                    for="kode_kategori"
                    class="mb-2 block text-sm font-medium text-slate-700"
                >
                    Kode Kategori
                    <span class="text-red-500">*</span>
                </label>

                <input
                    type="text"
                    id="kode_kategori"
                    name="kode_kategori"
                    value="{{ old('kode_kategori') }}"
                    placeholder="Contoh: KAT-001"
                    class="w-full rounded-lg border px-3.5 py-2.5 text-sm text-slate-700 outline-none transition
                    {{ $errors->has('kode_kategori')
                        ? 'border-red-300 focus:border-red-400 focus:ring-2 focus:ring-red-100'
                        : 'border-slate-300 focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100'
                    }}"
                >

                @error('kode_kategori')
                    <p class="mt-1.5 text-xs text-red-500">
                        {{ $message }}
                    </p>
                @enderror

            </div>


            {{-- NAMA --}}
            <div>

                <label
                    for="nama_kategori"
                    class="mb-2 block text-sm font-medium text-slate-700"
                >
                    Nama Kategori
                    <span class="text-red-500">*</span>
                </label>

                <input
                    type="text"
                    id="nama_kategori"
                    name="nama_kategori"
                    value="{{ old('nama_kategori') }}"
                    placeholder="Contoh: Besi Beton"
                    class="w-full rounded-lg border px-3.5 py-2.5 text-sm text-slate-700 outline-none transition
                    {{ $errors->has('nama_kategori')
                        ? 'border-red-300 focus:border-red-400 focus:ring-2 focus:ring-red-100'
                        : 'border-slate-300 focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100'
                    }}"
                >

                @error('nama_kategori')
                    <p class="mt-1.5 text-xs text-red-500">
                        {{ $message }}
                    </p>
                @enderror

            </div>


            {{-- KETERANGAN --}}
            <div>

                <label
                    for="keterangan"
                    class="mb-2 block text-sm font-medium text-slate-700"
                >
                    Keterangan
                </label>

                <textarea
                    id="keterangan"
                    name="keterangan"
                    rows="4"
                    placeholder="Keterangan kategori (opsional)"
                    class="w-full resize-none rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm text-slate-700 outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                >{{ old('keterangan') }}</textarea>

                @error('keterangan')
                    <p class="mt-1.5 text-xs text-red-500">
                        {{ $message }}
                    </p>
                @enderror

            </div>


            {{-- STATUS --}}
            <div>

                <label
                    for="is_active"
                    class="mb-2 block text-sm font-medium text-slate-700"
                >
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


        {{-- ACTION --}}
        <div class="flex items-center justify-end gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4">

            <a
                href="{{ route('kategori-barang.index') }}"
                class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-100"
            >
                Batal
            </a>

            <button
                type="submit"
                class="rounded-lg bg-[#15235F] px-5 py-2.5 text-sm font-medium text-white transition hover:bg-[#101b4d]"
            >
                Simpan Kategori
            </button>

        </div>

    </form>

</div>

@endsection