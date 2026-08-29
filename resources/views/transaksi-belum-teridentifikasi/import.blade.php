@extends('layouts.app')

@section('title', 'Import Rekening Koran')

@section('content')
<div class="min-h-screen bg-slate-50">

    <div class="mx-auto max-w-5xl px-4 py-6 sm:px-6 lg:px-8">

        {{-- HEADER --}}
        <div class="mb-6">

            <h1 class="text-2xl font-bold text-slate-900">
                Import Rekening Koran
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Upload file rekening koran untuk dimasukkan ke transaksi bank
                sebelum dilakukan rekonsiliasi.
            </p>

        </div>


        {{-- ERROR --}}
        @if ($errors->any())

            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4">

                <div class="font-semibold text-red-700">
                    Data belum dapat diproses.
                </div>

                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-600">

                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach

                </ul>

            </div>

        @endif


        {{-- CARD --}}
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            {{-- CARD HEADER --}}
            <div class="border-b border-slate-200 px-6 py-5">

                <h2 class="text-base font-semibold text-slate-900">
                    Upload File Rekening Koran
                </h2>

                <p class="mt-1 text-sm text-slate-500">
                    Pilih rekening bank kemudian upload file Excel atau CSV.
                </p>

            </div>


            {{-- FORM --}}
            <form
                action="{{ route('rekening-koran-import.preview.process') }}"
                method="POST"
                enctype="multipart/form-data"
            >

                @csrf

                <div class="space-y-6 p-6">


                    {{-- REKENING BANK --}}
                    <div>

                        <label
                            for="rekening_kas_bank_id"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            Rekening Bank
                            <span class="text-red-500">*</span>
                        </label>

                        <select
                            name="rekening_kas_bank_id"
                            id="rekening_kas_bank_id"
                            required
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm text-slate-700 outline-none transition focus:border-[#15235F] focus:ring-2 focus:ring-[#15235F]/10"
                        >

                            <option value="">
                                -- Pilih Rekening Bank --
                            </option>

                            @foreach ($rekeningBanks as $rekening)

                                <option
                                    value="{{ $rekening->id }}"
                                    @selected(
                                        old('rekening_kas_bank_id')
                                        == $rekening->id
                                    )
                                >
                                    {{ $rekening->nama_rekening }}

                                    @if ($rekening->nama_bank)
                                        - {{ $rekening->nama_bank }}
                                    @endif

                                    @if ($rekening->nomor_rekening)
                                        ({{ $rekening->nomor_rekening }})
                                    @endif
                                </option>

                            @endforeach

                        </select>

                        <p class="mt-2 text-xs text-slate-500">
                            Hanya rekening bank aktif yang ditampilkan.
                        </p>

                    </div>


                    {{-- FILE --}}
                    <div>

                        <label
                            for="file_rekening_koran"
                            class="mb-2 block text-sm font-semibold text-slate-700"
                        >
                            File Rekening Koran
                            <span class="text-red-500">*</span>
                        </label>


                        <div
                            id="dropZone"
                            class="relative rounded-xl border-2 border-dashed border-slate-300 bg-slate-50 px-6 py-10 text-center transition hover:border-[#15235F] hover:bg-slate-100"
                        >

                            <input
                                type="file"
                                name="file_rekening_koran"
                                id="file_rekening_koran"
                                accept=".xlsx,.xls,.csv,.txt"
                                required
                                class="absolute inset-0 h-full w-full cursor-pointer opacity-0"
                            >


                            <div class="pointer-events-none">

                                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-[#15235F]/10">

                                    <svg
                                        xmlns="http://www.w3.org/2000/svg"
                                        fill="none"
                                        viewBox="0 0 24 24"
                                        stroke-width="1.5"
                                        stroke="currentColor"
                                        class="h-6 w-6 text-[#15235F]"
                                    >
                                        <path
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                            d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M7.5 9 12 4.5m0 0L16.5 9M12 4.5V15"
                                        />
                                    </svg>

                                </div>


                                <p class="mt-4 text-sm font-semibold text-slate-700">
                                    Klik atau drag file ke sini
                                </p>

                                <p class="mt-1 text-xs text-slate-500">
                                    XLSX, XLS, CSV atau TXT
                                </p>

                                <p class="mt-1 text-xs text-slate-400">
                                    Maksimal ukuran file 10 MB
                                </p>


                                <div
                                    id="fileInfo"
                                    class="mt-4 hidden"
                                >

                                    <span class="inline-flex rounded-lg bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-700">

                                        <span id="fileName"></span>

                                    </span>

                                </div>

                            </div>

                        </div>

                    </div>


                    {{-- FORMAT FILE --}}
                    <div class="rounded-xl border border-blue-200 bg-blue-50 p-5">

                        <h3 class="text-sm font-semibold text-blue-900">
                            Format Kolom Rekening Koran
                        </h3>

                        <p class="mt-2 text-sm text-blue-800">
                            Sistem akan membaca nama header secara otomatis.
                            File minimal harus memiliki kolom Tanggal, Debit, dan Kredit.
                        </p>


                        <div class="mt-4 overflow-hidden rounded-lg border border-blue-200">

                            <table class="min-w-full divide-y divide-blue-200 text-sm">

                                <thead class="bg-blue-100">

                                    <tr>

                                        <th class="px-4 py-2 text-left font-semibold text-blue-900">
                                            Kolom
                                        </th>

                                        <th class="px-4 py-2 text-left font-semibold text-blue-900">
                                            Keterangan
                                        </th>

                                    </tr>

                                </thead>


                                <tbody class="divide-y divide-blue-100 bg-white">

                                    <tr>

                                        <td class="px-4 py-2 font-medium text-slate-700">
                                            Tanggal
                                        </td>

                                        <td class="px-4 py-2 text-slate-600">
                                            Tanggal transaksi pada rekening koran
                                        </td>

                                    </tr>


                                    <tr>

                                        <td class="px-4 py-2 font-medium text-slate-700">
                                            Deskripsi
                                        </td>

                                        <td class="px-4 py-2 text-slate-600">
                                            Keterangan atau uraian transaksi
                                        </td>

                                    </tr>


                                    <tr>

                                        <td class="px-4 py-2 font-medium text-slate-700">
                                            Debit
                                        </td>

                                        <td class="px-4 py-2 text-slate-600">
                                            Uang keluar dari rekening perusahaan
                                        </td>

                                    </tr>


                                    <tr>

                                        <td class="px-4 py-2 font-medium text-slate-700">
                                            Kredit
                                        </td>

                                        <td class="px-4 py-2 text-slate-600">
                                            Uang masuk ke rekening perusahaan
                                        </td>

                                    </tr>


                                    <tr>

                                        <td class="px-4 py-2 font-medium text-slate-700">
                                            Referensi
                                        </td>

                                        <td class="px-4 py-2 text-slate-600">
                                            Nomor referensi transaksi bank
                                            (opsional)
                                        </td>

                                    </tr>

                                </tbody>

                            </table>

                        </div>

                    </div>


                    {{-- CONTOH --}}
                    <div class="rounded-xl border border-slate-200 bg-slate-50 p-5">

                        <h3 class="text-sm font-semibold text-slate-900">
                            Contoh Data
                        </h3>

                        <p class="mt-1 text-sm text-slate-500">
                            Contoh format sederhana yang dapat dibaca sistem.
                        </p>


                        <div class="mt-4 overflow-x-auto">

                            <table class="min-w-full divide-y divide-slate-200 overflow-hidden rounded-lg border border-slate-200 bg-white text-sm">

                                <thead class="bg-slate-100">

                                    <tr>

                                        <th class="whitespace-nowrap px-3 py-2 text-left font-semibold text-slate-700">
                                            Tanggal
                                        </th>

                                        <th class="whitespace-nowrap px-3 py-2 text-left font-semibold text-slate-700">
                                            Deskripsi
                                        </th>

                                        <th class="whitespace-nowrap px-3 py-2 text-right font-semibold text-slate-700">
                                            Debit
                                        </th>

                                        <th class="whitespace-nowrap px-3 py-2 text-right font-semibold text-slate-700">
                                            Kredit
                                        </th>

                                        <th class="whitespace-nowrap px-3 py-2 text-left font-semibold text-slate-700">
                                            Referensi
                                        </th>

                                    </tr>

                                </thead>


                                <tbody class="divide-y divide-slate-100">

                                    <tr>

                                        <td class="whitespace-nowrap px-3 py-2 text-slate-600">
                                            29/08/2026
                                        </td>

                                        <td class="px-3 py-2 text-slate-600">
                                            Transfer Pembayaran Supplier
                                        </td>

                                        <td class="whitespace-nowrap px-3 py-2 text-right text-red-600">
                                            5.000.000
                                        </td>

                                        <td class="whitespace-nowrap px-3 py-2 text-right text-slate-400">
                                            -
                                        </td>

                                        <td class="whitespace-nowrap px-3 py-2 text-slate-600">
                                            TRX001
                                        </td>

                                    </tr>


                                    <tr>

                                        <td class="whitespace-nowrap px-3 py-2 text-slate-600">
                                            29/08/2026
                                        </td>

                                        <td class="px-3 py-2 text-slate-600">
                                            Transfer Customer
                                        </td>

                                        <td class="whitespace-nowrap px-3 py-2 text-right text-slate-400">
                                            -
                                        </td>

                                        <td class="whitespace-nowrap px-3 py-2 text-right text-emerald-600">
                                            2.500.000
                                        </td>

                                        <td class="whitespace-nowrap px-3 py-2 text-slate-600">
                                            TRX002
                                        </td>

                                    </tr>

                                </tbody>

                            </table>

                        </div>

                    </div>


                    {{-- INFO MAPPING --}}
                    <div class="grid gap-4 sm:grid-cols-2">

                        <div class="rounded-xl border border-red-200 bg-red-50 p-4">

                            <p class="text-sm font-semibold text-red-800">
                                Debit
                            </p>

                            <p class="mt-1 text-sm text-red-700">
                                Akan dibaca oleh sistem sebagai transaksi
                                <strong>KELUAR</strong>.
                            </p>

                        </div>


                        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4">

                            <p class="text-sm font-semibold text-emerald-800">
                                Kredit
                            </p>

                            <p class="mt-1 text-sm text-emerald-700">
                                Akan dibaca oleh sistem sebagai transaksi
                                <strong>MASUK</strong>.
                            </p>

                        </div>

                    </div>


                    {{-- INFO PREVIEW --}}
                    <div class="rounded-xl border border-amber-200 bg-amber-50 p-4">

                        <div class="flex gap-3">

                            <div class="mt-0.5">

                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    fill="none"
                                    viewBox="0 0 24 24"
                                    stroke-width="1.5"
                                    stroke="currentColor"
                                    class="h-5 w-5 text-amber-600"
                                >
                                    <path
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                        d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"
                                    />
                                </svg>

                            </div>

                            <div>

                                <p class="text-sm font-semibold text-amber-800">
                                    Data belum langsung disimpan
                                </p>

                                <p class="mt-1 text-sm text-amber-700">
                                    Setelah file dibaca, sistem akan menampilkan
                                    preview terlebih dahulu. Kamu dapat memeriksa
                                    transaksi yang siap diimport, duplikat,
                                    maupun transaksi yang error sebelum data
                                    benar-benar disimpan.
                                </p>

                            </div>

                        </div>

                    </div>


                </div>


                {{-- FOOTER --}}
                <div class="flex flex-col-reverse gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">

                    <a
                        href="{{ route('transaksi-belum-teridentifikasi.index') }}"
                        class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                    >
                        Kembali
                    </a>


                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-lg bg-[#15235F] px-5 py-2.5 text-sm font-semibold text-white transition hover:opacity-90"
                    >
                        Preview Data
                    </button>

                </div>

            </form>

        </div>

    </div>

</div>


<script>
    document.addEventListener('DOMContentLoaded', function () {

        const fileInput =
            document.getElementById('file_rekening_koran');

        const fileInfo =
            document.getElementById('fileInfo');

        const fileName =
            document.getElementById('fileName');

        const dropZone =
            document.getElementById('dropZone');


        fileInput.addEventListener('change', function () {

            if (this.files.length > 0) {

                fileName.textContent =
                    this.files[0].name;

                fileInfo.classList.remove('hidden');

            } else {

                fileName.textContent = '';

                fileInfo.classList.add('hidden');

            }

        });


        fileInput.addEventListener('dragenter', function () {

            dropZone.classList.add(
                'border-[#15235F]',
                'bg-slate-100'
            );

        });


        fileInput.addEventListener('dragleave', function () {

            dropZone.classList.remove(
                'border-[#15235F]',
                'bg-slate-100'
            );

        });


        fileInput.addEventListener('drop', function () {

            dropZone.classList.remove(
                'border-[#15235F]',
                'bg-slate-100'
            );

        });

    });
</script>

@endsection