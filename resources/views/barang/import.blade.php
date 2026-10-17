@extends('layouts.app')

@section('title', 'Import Master Barang')

@section('content')

<div class="mx-auto max-w-5xl space-y-6">

    {{-- HEADER --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>

            <a
                href="{{ route('barang.index') }}"
                class="mb-2 inline-flex items-center gap-2 text-sm text-slate-500 hover:text-[#15235F]"
            >
                ← Kembali ke Barang
            </a>

            <h1 class="text-2xl font-semibold text-slate-800">
                Import Master Barang
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Import data barang dari file Excel ke database AMBAJA.
            </p>

        </div>

    </div>


    {{-- ALERT ERROR --}}
    @if(session('error'))

        <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4">

            <div class="flex gap-3">

                <div class="text-red-500">
                    ⚠
                </div>

                <div>

                    <p class="font-semibold text-red-700">
                        Import gagal
                    </p>

                    <p class="mt-1 text-sm text-red-600">
                        {{ session('error') }}
                    </p>

                </div>

            </div>

        </div>

    @endif


    {{-- ALERT SUCCESS --}}
    @if(session('success'))

        <div class="rounded-xl border border-green-200 bg-green-50 px-5 py-4">

            <div class="flex gap-3">

                <div class="text-green-500">
                    ✓
                </div>

                <div>

                    <p class="font-semibold text-green-700">
                        Berhasil
                    </p>

                    <p class="mt-1 text-sm text-green-600">
                        {{ session('success') }}
                    </p>

                </div>

            </div>

        </div>

    @endif


    {{-- VALIDATION ERROR --}}
    @if($errors->any())

        <div class="rounded-xl border border-red-200 bg-red-50 px-5 py-4">

            <p class="font-semibold text-red-700">
                Data belum dapat diproses
            </p>

            <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-600">

                @foreach($errors->all() as $error)

                    <li>
                        {{ $error }}
                    </li>

                @endforeach

            </ul>

        </div>

    @endif


    {{-- UPLOAD CARD --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        {{-- CARD HEADER --}}
        <div class="border-b border-slate-200 px-6 py-5">

            <h2 class="text-base font-semibold text-slate-800">
                Upload File Excel
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Pilih file Excel yang berisi daftar master barang.
            </p>

        </div>


        {{-- FORM --}}
        <form
            action="{{ route('barang.import.preview') }}"
            method="POST"
            enctype="multipart/form-data"
            class="p-6"
        >

            @csrf


            {{-- FILE INPUT --}}
            <div
                class="rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center transition hover:border-[#15235F]"
            >

                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-full bg-white text-2xl shadow-sm">
                    📊
                </div>


                <h3 class="mt-4 text-sm font-semibold text-slate-800">
                    Pilih file Excel barang
                </h3>


                <p class="mt-1 text-xs text-slate-500">
                    Format file yang didukung: XLSX dan XLS
                </p>


                <div class="mx-auto mt-5 max-w-lg">

                    <input
                        type="file"
                        name="file_barang"
                        id="file_barang"
                        accept=".xlsx,.xls"
                        required
                        class="block w-full cursor-pointer rounded-lg border border-slate-300 bg-white text-sm text-slate-600 file:mr-4 file:border-0 file:bg-[#15235F] file:px-4 file:py-2.5 file:text-sm file:font-medium file:text-white hover:file:bg-[#101b4d]"
                    >

                </div>


                <p class="mt-3 text-xs text-slate-400">
                    Maksimal ukuran file 20 MB.
                </p>

            </div>


            {{-- FORMAT DATA --}}
            <div class="mt-6 rounded-xl border border-blue-200 bg-blue-50 p-5">

                <h3 class="text-sm font-semibold text-blue-800">
                    Kolom Excel yang dibaca
                </h3>


                <div class="mt-4 grid gap-3 sm:grid-cols-2 lg:grid-cols-3">

                    <div class="rounded-lg bg-white px-4 py-3">

                        <p class="text-xs text-slate-400">
                            Kolom
                        </p>

                        <p class="mt-1 text-sm font-medium text-slate-700">
                            KODE ITEM
                        </p>

                    </div>


                    <div class="rounded-lg bg-white px-4 py-3">

                        <p class="text-xs text-slate-400">
                            Kolom
                        </p>

                        <p class="mt-1 text-sm font-medium text-slate-700">
                            NAMA ITEM
                        </p>

                    </div>


                    <div class="rounded-lg bg-white px-4 py-3">

                        <p class="text-xs text-slate-400">
                            Kolom
                        </p>

                        <p class="mt-1 text-sm font-medium text-slate-700">
                            JENIS
                        </p>

                    </div>


                    <div class="rounded-lg bg-white px-4 py-3">

                        <p class="text-xs text-slate-400">
                            Kolom
                        </p>

                        <p class="mt-1 text-sm font-medium text-slate-700">
                            STOK
                        </p>

                    </div>


                    <div class="rounded-lg bg-white px-4 py-3">

                        <p class="text-xs text-slate-400">
                            Kolom
                        </p>

                        <p class="mt-1 text-sm font-medium text-slate-700">
                            SATUAN
                        </p>

                    </div>


                    <div class="rounded-lg bg-white px-4 py-3">

                        <p class="text-xs text-slate-400">
                            Kolom
                        </p>

                        <p class="mt-1 text-sm font-medium text-slate-700">
                            HARGA POKOK
                        </p>

                    </div>


                    <div class="rounded-lg bg-white px-4 py-3">

                        <p class="text-xs text-slate-400">
                            Kolom
                        </p>

                        <p class="mt-1 text-sm font-medium text-slate-700">
                            HARGA JUAL
                        </p>

                    </div>

                </div>

            </div>


            {{-- INFORMATION --}}
            <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-5">

                <h3 class="text-sm font-semibold text-amber-800">
                    Perhatian
                </h3>


                <div class="mt-2 space-y-2 text-xs leading-5 text-amber-700">

                    <p>
                        File Excel akan diperiksa terlebih dahulu sebelum data dimasukkan ke database.
                    </p>

                    <p>
                        Jika kode barang sudah terdapat di database, data barang akan diperlakukan sebagai data yang diperbarui.
                    </p>

                    <p>
                        Jika kode barang belum ada, data akan dibuat sebagai barang baru.
                    </p>

                    <p class="font-semibold">
                        Kolom STOK pada Excel bersifat informatif dan tidak mengubah saldo stok gudang.
                    </p>

                    <p>
                        Untuk memasukkan saldo stok awal, gunakan menu <strong>Import Stok Awal</strong> yang memiliki template khusus gudang dan harga satuan.
                    </p>

                </div>

            </div>

            <div class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-slate-400">
                    Master barang dan stok awal dipisahkan agar saldo persediaan tetap tercatat per gudang.
                </p>
                <a
                    href="{{ route('stok-barang.template-stok-awal') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-xs font-medium text-slate-600 hover:bg-slate-50"
                >
                    Download Template Stok Awal
                </a>
            </div>


            {{-- BUTTON --}}
            <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">

                <a
                    href="{{ route('barang.index') }}"
                    class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50"
                >
                    Batal
                </a>


                <button
                    type="submit"
                    class="inline-flex items-center justify-center rounded-lg bg-[#15235F] px-5 py-2.5 text-sm font-medium text-white shadow-sm transition hover:bg-[#101b4d]"
                >
                    Preview Data
                </button>

            </div>

        </form>

    </div>

</div>

@endsection