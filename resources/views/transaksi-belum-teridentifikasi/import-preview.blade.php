@extends('layouts.app')

@section('title', 'Preview Import Rekening Koran')

@section('content')
<div class="min-h-screen bg-slate-50">

    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">

        {{-- HEADER --}}
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h1 class="text-2xl font-bold text-slate-900">
                    Preview Import Rekening Koran
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Periksa data rekening koran sebelum transaksi disimpan.
                </p>
            </div>

            <div class="text-left sm:text-right">

                <div class="text-sm font-semibold text-slate-800">
                    {{ $rekening->nama_rekening }}
                </div>

                <div class="mt-1 text-xs text-slate-500">
                    {{ $rekening->nama_bank ?? '-' }}
                    @if ($rekening->nomor_rekening)
                        • {{ $rekening->nomor_rekening }}
                    @endif
                </div>

            </div>

        </div>


        {{-- ERROR --}}
        @if ($errors->any())

            <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4">

                <div class="font-semibold text-red-700">
                    Import belum dapat dilakukan.
                </div>

                <ul class="mt-2 list-disc space-y-1 pl-5 text-sm text-red-600">

                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach

                </ul>

            </div>

        @endif


        {{-- INFORMASI FILE --}}
        <div class="mb-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

            <div class="grid gap-4 md:grid-cols-3">

                <div>

                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Nama File
                    </div>

                    <div class="mt-1 text-sm font-semibold text-slate-900">
                        {{ $import['nama_file'] ?? '-' }}
                    </div>

                </div>


                <div>

                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Batch Import
                    </div>

                    <div class="mt-1 break-all text-sm font-semibold text-slate-900">
                        {{ $import['batch'] ?? '-' }}
                    </div>

                </div>


                <div>

                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500">
                        Total Baris
                    </div>

                    <div class="mt-1 text-sm font-semibold text-slate-900">
                        {{ number_format($data->count(), 0, ',', '.') }} transaksi
                    </div>

                </div>

            </div>

        </div>


        {{-- SUMMARY --}}
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">

            {{-- SIAP --}}
            <div class="rounded-xl border border-emerald-200 bg-white p-5 shadow-sm">

                <div class="text-sm font-medium text-slate-500">
                    Siap Diimport
                </div>

                <div class="mt-2 text-3xl font-bold text-emerald-600">
                    {{ number_format($jumlahSiap, 0, ',', '.') }}
                </div>

                <p class="mt-1 text-xs text-slate-500">
                    Transaksi valid dan belum pernah diimport.
                </p>

            </div>


            {{-- DUPLIKAT --}}
            <div class="rounded-xl border border-amber-200 bg-white p-5 shadow-sm">

                <div class="text-sm font-medium text-slate-500">
                    Duplikat
                </div>

                <div class="mt-2 text-3xl font-bold text-amber-600">
                    {{ number_format($jumlahDuplikat, 0, ',', '.') }}
                </div>

                <p class="mt-1 text-xs text-slate-500">
                    Transaksi sudah pernah masuk sebelumnya.
                </p>

            </div>


            {{-- ERROR --}}
            <div class="rounded-xl border border-red-200 bg-white p-5 shadow-sm">

                <div class="text-sm font-medium text-slate-500">
                    Error
                </div>

                <div class="mt-2 text-3xl font-bold text-red-600">
                    {{ number_format($jumlahError, 0, ',', '.') }}
                </div>

                <p class="mt-1 text-xs text-slate-500">
                    Data tidak valid dan tidak akan diimport.
                </p>

            </div>

        </div>


        {{-- INFO --}}
        <div class="mb-6 rounded-xl border border-blue-200 bg-blue-50 p-4">

            <p class="text-sm font-semibold text-blue-900">
                Cara membaca transaksi
            </p>

            <div class="mt-2 flex flex-wrap gap-x-6 gap-y-2 text-sm text-blue-800">

                <span>
                    <strong>Debit</strong> rekening koran =
                    <strong>Uang Keluar</strong>
                </span>

                <span>
                    <strong>Kredit</strong> rekening koran =
                    <strong>Uang Masuk</strong>
                </span>

            </div>

        </div>


        {{-- TABLE --}}
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="overflow-x-auto">

                <table class="min-w-full divide-y divide-slate-200">

                    <thead class="bg-slate-100">

                        <tr>

                            <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">
                                Baris
                            </th>

                            <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">
                                Tanggal
                            </th>

                            <th class="whitespace-nowrap px-4 py-3 text-center text-xs font-semibold uppercase text-slate-600">
                                Jenis
                            </th>

                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">
                                Deskripsi
                            </th>

                            <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">
                                Referensi
                            </th>

                            <th class="whitespace-nowrap px-4 py-3 text-right text-xs font-semibold uppercase text-slate-600">
                                Jumlah
                            </th>

                            <th class="whitespace-nowrap px-4 py-3 text-center text-xs font-semibold uppercase text-slate-600">
                                Status
                            </th>

                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">
                                Keterangan
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-slate-100">

                        @foreach ($data as $row)

                            <tr
                                class="
                                    @if (($row['status_import'] ?? '') === 'ERROR')
                                        bg-red-50/40
                                    @elseif (($row['status_import'] ?? '') === 'DUPLIKAT')
                                        bg-amber-50/40
                                    @else
                                        hover:bg-slate-50
                                    @endif
                                "
                            >

                                {{-- BARIS --}}
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-500">
                                    {{ $row['baris'] ?? '-' }}
                                </td>


                                {{-- TANGGAL --}}
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">

                                    @if (!empty($row['tanggal']))

                                        {{ \Carbon\Carbon::parse($row['tanggal'])->format('d/m/Y') }}

                                    @else

                                        <span class="text-red-500">
                                            -
                                        </span>

                                    @endif

                                </td>


                                {{-- JENIS --}}
                                <td class="whitespace-nowrap px-4 py-3 text-center">

                                    @if (($row['jenis'] ?? null) === 'MASUK')

                                        <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                            Masuk
                                        </span>

                                    @elseif (($row['jenis'] ?? null) === 'KELUAR')

                                        <span class="inline-flex rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700">
                                            Keluar
                                        </span>

                                    @else

                                        <span class="text-sm text-slate-400">
                                            -
                                        </span>

                                    @endif

                                </td>


                                {{-- DESKRIPSI --}}
                                <td class="px-4 py-3">

                                    <div class="max-w-sm text-sm text-slate-700">
                                        {{ $row['deskripsi_bank'] ?: '-' }}
                                    </div>

                                </td>


                                {{-- REFERENSI --}}
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-600">
                                    {{ $row['nomor_referensi_bank'] ?: '-' }}
                                </td>


                                {{-- JUMLAH --}}
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-bold">

                                    @if (($row['jenis'] ?? null) === 'MASUK')

                                        <span class="text-emerald-600">
                                            Rp {{ number_format(
                                                (float) ($row['jumlah'] ?? 0),
                                                0,
                                                ',',
                                                '.'
                                            ) }}
                                        </span>

                                    @elseif (($row['jenis'] ?? null) === 'KELUAR')

                                        <span class="text-red-600">
                                            Rp {{ number_format(
                                                (float) ($row['jumlah'] ?? 0),
                                                0,
                                                ',',
                                                '.'
                                            ) }}
                                        </span>

                                    @else

                                        <span class="text-slate-400">
                                            -
                                        </span>

                                    @endif

                                </td>


                                {{-- STATUS --}}
                                <td class="whitespace-nowrap px-4 py-3 text-center">

                                    @if (($row['status_import'] ?? '') === 'SIAP')

                                        <span class="inline-flex rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                            Siap
                                        </span>

                                    @elseif (($row['status_import'] ?? '') === 'DUPLIKAT')

                                        <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">
                                            Duplikat
                                        </span>

                                    @else

                                        <span class="inline-flex rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700">
                                            Error
                                        </span>

                                    @endif

                                </td>


                                {{-- KETERANGAN --}}
                                <td class="px-4 py-3 text-sm">

                                    @if (!empty($row['pesan']))

                                        <span
                                            class="
                                                @if (($row['status_import'] ?? '') === 'ERROR')
                                                    text-red-600
                                                @elseif (($row['status_import'] ?? '') === 'DUPLIKAT')
                                                    text-amber-600
                                                @else
                                                    text-slate-600
                                                @endif
                                            "
                                        >
                                            {{ $row['pesan'] }}
                                        </span>

                                    @else

                                        <span class="text-emerald-600">
                                            Siap diimport
                                        </span>

                                    @endif

                                </td>

                            </tr>

                        @endforeach

                    </tbody>

                </table>

            </div>

        </div>


        {{-- CATATAN --}}
        @if ($jumlahDuplikat > 0 || $jumlahError > 0)

            <div class="mt-6 rounded-xl border border-amber-200 bg-amber-50 p-4">

                <p class="text-sm font-semibold text-amber-800">
                    Perhatian
                </p>

                <p class="mt-1 text-sm text-amber-700">
                    Hanya transaksi dengan status
                    <strong>Siap</strong> yang akan dimasukkan ke database.
                    Transaksi duplikat dan error otomatis dilewati.
                </p>

            </div>

        @endif


        {{-- ACTION --}}
        <div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">

            {{-- BATAL --}}
            <form
                action="{{ route('rekening-koran-import.cancel') }}"
                method="POST"
            >
                @csrf

                <button
                    type="submit"
                    class="w-full rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 sm:w-auto"
                >
                    Batalkan
                </button>

            </form>


            <div class="flex flex-col gap-2 sm:flex-row">

                {{-- UPLOAD ULANG --}}
                <form
                    action="{{ route('rekening-koran-import.cancel') }}"
                    method="POST"
                >
                    @csrf

                    <button
                        type="submit"
                        class="w-full rounded-lg border border-[#15235F] bg-white px-5 py-2.5 text-sm font-semibold text-[#15235F] transition hover:bg-slate-50 sm:w-auto"
                    >
                        Upload File Lain
                    </button>

                </form>


                {{-- IMPORT --}}
                <form
                    action="{{ route('rekening-koran-import.store') }}"
                    method="POST"
                    id="formImport"
                >
                    @csrf

                    <button
                        type="submit"
                        id="btnImport"
                        @disabled($jumlahSiap <= 0)
                        class="
                            w-full rounded-lg px-5 py-2.5 text-sm font-semibold text-white transition sm:w-auto
                            {{ $jumlahSiap > 0
                                ? 'bg-[#15235F] hover:opacity-90'
                                : 'cursor-not-allowed bg-slate-300'
                            }}
                        "
                    >
                        Import {{ $jumlahSiap }} Transaksi
                    </button>

                </form>

            </div>

        </div>

    </div>

</div>


<script>
    document.addEventListener('DOMContentLoaded', function () {

        const formImport =
            document.getElementById('formImport');

        const btnImport =
            document.getElementById('btnImport');


        if (formImport) {

            formImport.addEventListener('submit', function () {

                if (btnImport) {

                    btnImport.disabled = true;

                    btnImport.textContent =
                        'Mengimport...';

                    btnImport.classList.add(
                        'cursor-not-allowed',
                        'opacity-70'
                    );

                }

            });

        }

    });
</script>

@endsection