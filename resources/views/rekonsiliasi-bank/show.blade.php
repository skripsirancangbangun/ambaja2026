@extends('layouts.app')

@section('title', 'Detail Rekonsiliasi Bank')

@section('content')

<div class="mx-auto max-w-6xl space-y-6">

    {{-- HEADER --}}
    <div>
        <a
            href="{{ route('rekonsiliasi-bank.index') }}"
            class="mb-3 inline-flex text-sm text-slate-500 hover:text-[#15235F]"
        >
            ← Kembali
        </a>

        <h2 class="text-xl font-semibold text-slate-800">
            Detail Rekonsiliasi Bank
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            Cocokkan transaksi rekening koran dengan transaksi Kas/Bank yang tercatat di sistem.
        </p>
    </div>


    {{-- SUCCESS --}}
    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif


    {{-- ERROR --}}
    @if($errors->any())
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3">

            <p class="text-sm font-semibold text-red-700">
                Terjadi kesalahan.
            </p>

            <ul class="mt-2 list-disc space-y-1 pl-5 text-xs text-red-600">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>

        </div>
    @endif


    {{-- TRANSAKSI BANK --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

        <div class="mb-5 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">

            <div>
                <h3 class="text-sm font-semibold text-slate-700">
                    Transaksi Rekening Koran
                </h3>

                <p class="mt-1 text-xs text-slate-400">
                    Data transaksi yang berasal dari rekening koran bank.
                </p>
            </div>

            @if($transaksiBank->status === 'BELUM_TERIDENTIFIKASI')

                <span class="inline-flex w-fit rounded-full bg-amber-50 px-3 py-1 text-xs font-medium text-amber-600">
                    Belum Direkonsiliasi
                </span>

            @elseif($transaksiBank->status === 'TERIDENTIFIKASI')

                <span class="inline-flex w-fit rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-600">
                    Sudah Direkonsiliasi
                </span>

            @else

                <span class="inline-flex w-fit rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-500">
                    {{ str_replace('_', ' ', $transaksiBank->status) }}
                </span>

            @endif

        </div>


        <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-4">

            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">
                    Tanggal
                </p>

                <p class="mt-1 text-sm font-semibold text-slate-700">
                    {{ $transaksiBank->tanggal?->format('d/m/Y') ?? '-' }}
                </p>
            </div>


            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">
                    Rekening
                </p>

                <p class="mt-1 text-sm font-semibold text-slate-700">
                    {{ $transaksiBank->rekeningKasBank?->nama_rekening ?? '-' }}
                </p>

                @if($transaksiBank->rekeningKasBank?->nomor_rekening)
                    <p class="mt-0.5 text-xs text-slate-400">
                        {{ $transaksiBank->rekeningKasBank->nomor_rekening }}
                    </p>
                @endif
            </div>


            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">
                    Jenis
                </p>

                @if($transaksiBank->jenis === 'MASUK')

                    <span class="mt-1 inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-600">
                        Masuk
                    </span>

                @else

                    <span class="mt-1 inline-flex rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-600">
                        Keluar
                    </span>

                @endif
            </div>


            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">
                    Jumlah
                </p>

                <p class="mt-1 text-lg font-bold {{ $transaksiBank->jenis === 'MASUK' ? 'text-emerald-600' : 'text-red-600' }}">
                    {{ $transaksiBank->jenis === 'MASUK' ? '+' : '-' }}
                    Rp {{ number_format($transaksiBank->jumlah, 0, ',', '.') }}
                </p>
            </div>


            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">
                    Pengirim / Penerima
                </p>

                <p class="mt-1 text-sm font-medium text-slate-700">
                    {{ $transaksiBank->nama_pengirim_penerima ?? '-' }}
                </p>
            </div>


            <div>
                <p class="text-xs uppercase tracking-wide text-slate-400">
                    Referensi Bank
                </p>

                <p class="mt-1 text-sm font-medium text-slate-700">
                    {{ $transaksiBank->nomor_referensi_bank ?? '-' }}
                </p>
            </div>


            <div class="md:col-span-2">
                <p class="text-xs uppercase tracking-wide text-slate-400">
                    Deskripsi Bank
                </p>

                <p class="mt-1 text-sm leading-6 text-slate-700">
                    {{ $transaksiBank->deskripsi_bank ?? '-' }}
                </p>
            </div>

        </div>

    </div>


    {{-- SUDAH DIREKONSILIASI --}}
    @if($rekonsiliasi)

        @php
            $mutasi = $rekonsiliasi->mutasiKasBank;

            $nilaiMutasi =
                (float) ($mutasi?->debit ?? 0) > 0
                    ? (float) $mutasi->debit
                    : (float) ($mutasi?->kredit ?? 0);
        @endphp

        <div class="rounded-2xl border border-emerald-200 bg-white p-6 shadow-sm">

            <div class="mb-5">
                <h3 class="text-sm font-semibold text-emerald-700">
                    Hasil Rekonsiliasi
                </h3>

                <p class="mt-1 text-xs text-slate-400">
                    Transaksi rekening koran sudah dicocokkan dengan transaksi internal berikut.
                </p>
            </div>


            <div class="grid grid-cols-1 gap-5 md:grid-cols-2 lg:grid-cols-4">

                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Sumber Transaksi
                    </p>

                    <p class="mt-1 text-sm font-semibold text-slate-700">
                        {{ str_replace('_', ' ', $mutasi?->sumber_jenis ?? $mutasi?->reference_type ?? '-') }}
                    </p>
                </div>


                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Supplier / Pihak
                    </p>

                    <p class="mt-1 text-sm font-semibold text-slate-700">
                        {{ $mutasi?->sumber_nama ?? '-' }}
                    </p>
                </div>


                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Nomor Pembayaran
                    </p>

                    <p class="mt-1 text-sm font-medium text-slate-700">
                        {{ $mutasi?->sumber_nomor ?? $mutasi?->nomor_referensi ?? '-' }}
                    </p>
                </div>


                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Invoice
                    </p>

                    <p class="mt-1 text-sm font-medium text-slate-700">
                        {{ $mutasi?->sumber_invoice ?? '-' }}
                    </p>
                </div>


                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Tanggal Pembayaran
                    </p>

                    <p class="mt-1 text-sm font-medium text-slate-700">
                        {{ $mutasi?->sumber_tanggal?->format('d/m/Y') ?? $mutasi?->tanggal?->format('d/m/Y') ?? '-' }}
                    </p>
                </div>


                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Nominal
                    </p>

                    <p class="mt-1 text-sm font-bold text-slate-700">
                        Rp {{ number_format($nilaiMutasi, 0, ',', '.') }}
                    </p>
                </div>


                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Direkonsiliasi Pada
                    </p>

                    <p class="mt-1 text-sm text-slate-700">
                        {{ $rekonsiliasi->tanggal_rekonsiliasi?->format('d/m/Y H:i') ?? '-' }}
                    </p>
                </div>


                <div>
                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Oleh
                    </p>

                    <p class="mt-1 text-sm text-slate-700">
                        {{ $rekonsiliasi->creator?->name ?? '-' }}
                    </p>
                </div>


                <div class="md:col-span-2 lg:col-span-4">

                    <p class="text-xs uppercase tracking-wide text-slate-400">
                        Keterangan Mutasi
                    </p>

                    <p class="mt-1 text-sm leading-6 text-slate-700">
                        {{ $mutasi?->keterangan ?? '-' }}
                    </p>

                </div>


                @if($rekonsiliasi->catatan)

                    <div class="md:col-span-2 lg:col-span-4">
                        <p class="text-xs uppercase tracking-wide text-slate-400">
                            Catatan Rekonsiliasi
                        </p>

                        <p class="mt-1 text-sm text-slate-700">
                            {{ $rekonsiliasi->catatan }}
                        </p>
                    </div>

                @endif

            </div>


            <div class="mt-6 border-t border-slate-200 pt-5">

                <form
                    action="{{ route(
                        'rekonsiliasi-bank.batalkan',
                        $transaksiBank
                    ) }}"
                    method="POST"
                    onsubmit="return confirm('Batalkan rekonsiliasi transaksi ini?')"
                >

                    @csrf
                    @method('PATCH')

                    <button
                        type="submit"
                        class="rounded-lg border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-medium text-red-600 hover:bg-red-100"
                    >
                        Batalkan Rekonsiliasi
                    </button>

                </form>

            </div>

        </div>


    {{-- BELUM DIREKONSILIASI --}}
    @elseif($transaksiBank->status === 'BELUM_TERIDENTIFIKASI')

        <div class="rounded-2xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-6 py-5">

                <h3 class="text-sm font-semibold text-slate-700">
                    Kandidat Transaksi
                </h3>

                <p class="mt-1 text-xs leading-5 text-slate-400">
                    Nominal dan arah transaksi harus sama. Tanggal diperiksa dalam rentang ±3 hari. Nama supplier hanya digunakan sebagai tambahan penilaian.
                </p>

            </div>


            @if($kandidatMutasis->count() > 0)

                <div class="space-y-4 p-6">

                    @foreach($kandidatMutasis as $mutasi)

                        @php
                            $nilaiMutasi =
                                (float) $mutasi->debit > 0
                                    ? (float) $mutasi->debit
                                    : (float) $mutasi->kredit;

                            $score = $mutasi->match_score ?? 0;

                            $scoreClass =
                                $score >= 90
                                    ? 'border-emerald-200 bg-emerald-50 text-emerald-700'
                                    : (
                                        $score >= 80
                                            ? 'border-teal-200 bg-teal-50 text-teal-700'
                                            : (
                                                $score >= 70
                                                    ? 'border-amber-200 bg-amber-50 text-amber-700'
                                                    : 'border-red-200 bg-red-50 text-red-700'
                                            )
                                    );
                        @endphp


                        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

                            {{-- HEADER --}}
                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">

                                <div>

                                    <div class="flex flex-wrap items-center gap-2">

                                        <h4 class="text-base font-semibold text-slate-800">
                                            {{ $mutasi->sumber_nama ?? 'Transaksi Kas/Bank' }}
                                        </h4>

                                        <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                                            {{ str_replace('_', ' ', $mutasi->sumber_jenis ?? $mutasi->tipe) }}
                                        </span>

                                    </div>


                                    <p class="mt-1 text-xs text-slate-400">
                                        {{ $mutasi->sumber_nomor ?? $mutasi->nomor_referensi ?? '-' }}
                                    </p>

                                </div>


                                <div class="flex flex-wrap items-center gap-3">

                                    <div class="rounded-xl border px-4 py-2 text-center {{ $scoreClass }}">

                                        <p class="text-xl font-bold">
                                            {{ $score }}%
                                        </p>

                                        <p class="text-[10px] font-semibold uppercase tracking-wide">
                                            {{ $mutasi->match_label ?? '-' }}
                                        </p>

                                    </div>


                                    <button
                                        type="button"
                                        onclick="pilihMutasi(
                                            '{{ $mutasi->id }}',
                                            @js($mutasi->sumber_nomor ?? $mutasi->nomor_referensi ?? '-'),
                                            @js($mutasi->sumber_nama ?? '-'),
                                            @js(str_replace('_', ' ', $mutasi->sumber_jenis ?? $mutasi->tipe)),
                                            '{{ number_format($nilaiMutasi, 0, ',', '.') }}',
                                            '{{ $score }}',
                                            @js($mutasi->reference_type ?? ''),
                                            @js($namaBankTerdeteksi ?? '')
                                        )"
                                        class="rounded-lg bg-[#15235F] px-4 py-2.5 text-sm font-medium text-white hover:bg-[#101b4d]"
                                    >
                                        Pilih Kandidat
                                    </button>

                                </div>

                            </div>


                            {{-- DETAIL --}}
                            <div class="mt-5 grid grid-cols-1 gap-4 border-t border-slate-100 pt-5 md:grid-cols-2 lg:grid-cols-4">

                                <div>
                                    <p class="text-xs uppercase tracking-wide text-slate-400">
                                        Supplier / Pihak
                                    </p>

                                    <p class="mt-1 text-sm font-semibold text-slate-700">
                                        {{ $mutasi->sumber_nama ?? '-' }}
                                    </p>
                                </div>


                                <div>
                                    <p class="text-xs uppercase tracking-wide text-slate-400">
                                        Invoice
                                    </p>

                                    <p class="mt-1 text-sm font-medium text-slate-700">
                                        {{ $mutasi->sumber_invoice ?? '-' }}
                                    </p>
                                </div>


                                <div>
                                    <p class="text-xs uppercase tracking-wide text-slate-400">
                                        Tanggal Pembayaran
                                    </p>

                                    <p class="mt-1 text-sm font-medium text-slate-700">
                                        {{ $mutasi->sumber_tanggal?->format('d/m/Y') ?? $mutasi->tanggal?->format('d/m/Y') ?? '-' }}
                                    </p>
                                </div>


                                <div>
                                    <p class="text-xs uppercase tracking-wide text-slate-400">
                                        Nominal
                                    </p>

                                    <p class="mt-1 text-sm font-bold text-slate-800">
                                        Rp {{ number_format($nilaiMutasi, 0, ',', '.') }}
                                    </p>
                                </div>

                            </div>


                            {{-- MATCHING --}}
                            <div class="mt-5 rounded-xl bg-slate-50 p-4">

                                <p class="mb-3 text-xs font-semibold uppercase tracking-wide text-slate-500">
                                    Pemeriksaan Kecocokan
                                </p>


                                <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-4">

                                    {{-- NOMINAL --}}
                                    <div class="flex items-start gap-2">

                                        <span class="mt-0.5 inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-600">
                                            ✓
                                        </span>

                                        <div>
                                            <p class="text-xs font-medium text-slate-700">
                                                Nominal
                                            </p>

                                            <p class="text-xs text-emerald-600">
                                                Sama
                                            </p>
                                        </div>

                                    </div>


                                    {{-- ARAH --}}
                                    <div class="flex items-start gap-2">

                                        <span class="mt-0.5 inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-600">
                                            ✓
                                        </span>

                                        <div>
                                            <p class="text-xs font-medium text-slate-700">
                                                Arah Transaksi
                                            </p>

                                            <p class="text-xs text-emerald-600">
                                                Sesuai
                                            </p>
                                        </div>

                                    </div>


                                    {{-- TANGGAL --}}
                                    <div class="flex items-start gap-2">

                                        @if(($mutasi->selisih_hari ?? 0) === 0)

                                            <span class="mt-0.5 inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-600">
                                                ✓
                                            </span>

                                        @else

                                            <span class="mt-0.5 inline-flex h-5 w-5 items-center justify-center rounded-full bg-amber-100 text-xs font-bold text-amber-600">
                                                !
                                            </span>

                                        @endif

                                        <div>
                                            <p class="text-xs font-medium text-slate-700">
                                                Tanggal
                                            </p>

                                            @if(($mutasi->selisih_hari ?? 0) === 0)

                                                <p class="text-xs text-emerald-600">
                                                    Hari yang sama
                                                </p>

                                            @else

                                                <p class="text-xs text-amber-600">
                                                    Selisih {{ $mutasi->selisih_hari }} hari
                                                </p>

                                            @endif
                                        </div>

                                    </div>


                                    {{-- NAMA / ALIAS --}}
                                    <div class="flex items-start gap-2">

                                        @if($mutasi->nama_cocok)

                                            <span class="mt-0.5 inline-flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100 text-xs font-bold text-emerald-600">
                                                ✓
                                            </span>

                                            <div>
                                                <p class="text-xs font-medium text-slate-700">
                                                    Nama
                                                </p>

                                                <p class="text-xs text-emerald-600">
                                                    Nama supplier terdeteksi
                                                </p>
                                            </div>

                                        @elseif($mutasi->alias_cocok)

                                            <span class="mt-0.5 inline-flex h-5 w-5 items-center justify-center rounded-full bg-teal-100 text-xs font-bold text-teal-600">
                                                ✓
                                            </span>

                                            <div>
                                                <p class="text-xs font-medium text-slate-700">
                                                    Alias
                                                </p>

                                                <p class="text-xs text-teal-600">
                                                    Cocok melalui alias:
                                                    {{ $mutasi->alias_terdeteksi ?? '-' }}
                                                </p>
                                            </div>

                                        @else

                                            <span class="mt-0.5 inline-flex h-5 w-5 items-center justify-center rounded-full bg-slate-200 text-xs font-bold text-slate-500">
                                                -
                                            </span>

                                            <div>
                                                <p class="text-xs font-medium text-slate-700">
                                                    Nama
                                                </p>

                                                <p class="text-xs text-slate-500">
                                                    Belum dikenali
                                                </p>
                                            </div>

                                        @endif

                                    </div>

                                </div>

                            </div>


                            {{-- KETERANGAN --}}
                            @if($mutasi->keterangan)

                                <div class="mt-4">
                                    <p class="text-xs uppercase tracking-wide text-slate-400">
                                        Keterangan Mutasi
                                    </p>

                                    <p class="mt-1 text-xs leading-5 text-slate-600">
                                        {{ $mutasi->keterangan }}
                                    </p>
                                </div>

                            @endif

                        </div>

                    @endforeach

                </div>


                {{-- FORM KONFIRMASI --}}
                <form
                    id="form-rekonsiliasi"
                    action="{{ route(
                        'rekonsiliasi-bank.cocokkan',
                        $transaksiBank
                    ) }}"
                    method="POST"
                    class="hidden border-t border-slate-200 bg-slate-50 p-6"
                >

                    @csrf

                    <input
                        type="hidden"
                        id="mutasi_kas_bank_id"
                        name="mutasi_kas_bank_id"
                    >


                    <div class="rounded-2xl border border-teal-200 bg-white p-5">

                        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">

                            <div>
                                <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                                    Kandidat Dipilih
                                </p>

                                <h4
                                    id="selected-nama"
                                    class="mt-1 text-base font-semibold text-slate-800"
                                >
                                    -
                                </h4>
                            </div>


                            <div class="rounded-lg bg-teal-50 px-3 py-2 text-center">

                                <p
                                    id="selected-score"
                                    class="text-lg font-bold text-teal-700"
                                >
                                    -
                                </p>

                                <p class="text-[10px] font-medium uppercase tracking-wide text-teal-600">
                                    Skor Kecocokan
                                </p>

                            </div>

                        </div>


                        <div class="mt-5 grid grid-cols-1 gap-4 md:grid-cols-3">

                            <div>
                                <p class="text-xs text-slate-400">
                                    Referensi
                                </p>

                                <p
                                    id="selected-referensi"
                                    class="mt-1 text-sm font-semibold text-slate-700"
                                >
                                    -
                                </p>
                            </div>


                            <div>
                                <p class="text-xs text-slate-400">
                                    Jenis
                                </p>

                                <p
                                    id="selected-tipe"
                                    class="mt-1 text-sm font-semibold text-slate-700"
                                >
                                    -
                                </p>
                            </div>


                            <div>
                                <p class="text-xs text-slate-400">
                                    Jumlah
                                </p>

                                <p
                                    id="selected-jumlah"
                                    class="mt-1 text-sm font-bold text-[#15235F]"
                                >
                                    -
                                </p>
                            </div>

                        </div>


                        {{-- ALIAS SUPPLIER --}}
                        <div
                            id="alias-supplier-box"
                            class="mt-5 hidden rounded-xl border border-slate-200 bg-slate-50 p-4"
                        >

                            <div class="flex items-start gap-3">

                                <input
                                    type="checkbox"
                                    id="simpan_alias_supplier"
                                    name="simpan_alias_supplier"
                                    value="1"
                                    onchange="toggleAliasSupplier()"
                                    class="mt-1 h-4 w-4 rounded border-slate-300 text-[#32AAA6] focus:ring-[#32AAA6]"
                                >

                                <div class="flex-1">

                                    <label
                                        for="simpan_alias_supplier"
                                        class="cursor-pointer text-sm font-semibold text-slate-700"
                                    >
                                        Simpan nama rekening ini sebagai alias Supplier
                                    </label>

                                    <p class="mt-1 text-xs leading-5 text-slate-500">
                                        Gunakan apabila nama pada rekening koran berbeda dengan nama supplier di sistem.
                                        Alias akan membantu pencocokan transaksi berikutnya.
                                    </p>

                                </div>

                            </div>


                            <div
                                id="alias-input-wrapper"
                                class="mt-4 hidden"
                            >

                                <label
                                    for="nama_alias_supplier"
                                    class="mb-2 block text-sm font-medium text-slate-700"
                                >
                                    Nama Rekening / Alias
                                </label>

                                <input
                                    type="text"
                                    id="nama_alias_supplier"
                                    name="nama_alias_supplier"
                                    value="{{ old('nama_alias_supplier', $namaBankTerdeteksi ?? '') }}"
                                    placeholder="Contoh: BUDI SANTOSO"
                                    class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                                >

                                <p class="mt-2 text-xs text-slate-400">
                                    Isi hanya nama pemilik rekening atau nama yang biasa muncul pada mutasi bank.
                                </p>

                            </div>

                        </div>


                        {{-- CATATAN --}}
                        <div class="mt-5">

                            <label
                                for="catatan"
                                class="mb-2 block text-sm font-medium text-slate-700"
                            >
                                Catatan Rekonsiliasi
                            </label>

                            <textarea
                                id="catatan"
                                name="catatan"
                                rows="3"
                                placeholder="Opsional..."
                                class="w-full resize-none rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                            >{{ old('catatan') }}</textarea>

                        </div>


                        <div class="mt-5 flex flex-wrap justify-end gap-3">

                            <button
                                type="button"
                                onclick="batalPilihMutasi()"
                                class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50"
                            >
                                Batal Pilih
                            </button>


                            <button
                                type="submit"
                                onclick="return confirm('Yakin cocokkan transaksi rekening koran dengan transaksi ini?')"
                                class="rounded-lg bg-[#32AAA6] px-4 py-2.5 text-sm font-medium text-white hover:opacity-90"
                            >
                                Cocokkan Transaksi
                            </button>

                        </div>

                    </div>

                </form>


            @else

                <div class="px-6 py-12 text-center">

                    <p class="text-sm font-medium text-slate-500">
                        Tidak ditemukan kandidat transaksi yang cocok.
                    </p>

                    <p class="mx-auto mt-2 max-w-xl text-xs leading-5 text-slate-400">
                        Sistem mencari transaksi dengan rekening, arah, dan nominal yang sama dalam rentang tanggal ±3 hari.
                    </p>

                    <a
                        href="{{ route('mutasi-kas-bank.index', [
                            'rekening_kas_bank_id' =>
                                $transaksiBank->rekening_kas_bank_id
                        ]) }}"
                        class="mt-4 inline-flex rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50"
                    >
                        Lihat Mutasi Kas/Bank
                    </a>

                </div>

            @endif

        </div>

    @endif

</div>


<script>
    function pilihMutasi(
        id,
        referensi,
        nama,
        tipe,
        jumlah,
        score,
        referenceType,
        namaBank
    ) {
        document.getElementById(
            'mutasi_kas_bank_id'
        ).value = id;

        document.getElementById(
            'selected-referensi'
        ).textContent = referensi;

        document.getElementById(
            'selected-nama'
        ).textContent = nama;

        document.getElementById(
            'selected-tipe'
        ).textContent = tipe;

        document.getElementById(
            'selected-jumlah'
        ).textContent = 'Rp ' + jumlah;

        document.getElementById(
            'selected-score'
        ).textContent = score + '%';


        const aliasBox =
            document.getElementById(
                'alias-supplier-box'
            );

        const aliasCheckbox =
            document.getElementById(
                'simpan_alias_supplier'
            );

        const aliasInput =
            document.getElementById(
                'nama_alias_supplier'
            );

        const aliasWrapper =
            document.getElementById(
                'alias-input-wrapper'
            );


        aliasCheckbox.checked = false;

        aliasWrapper.classList.add(
            'hidden'
        );


        /*
        |--------------------------------------------------------------------------
        | ALIAS HANYA UNTUK PEMBAYARAN SUPPLIER
        |--------------------------------------------------------------------------
        */

        if (
            referenceType ===
            'PEMBAYARAN_SUPPLIER'
        ) {

            aliasBox.classList.remove(
                'hidden'
            );

            if (namaBank) {
                aliasInput.value =
                    namaBank;
            }

        } else {

            aliasBox.classList.add(
                'hidden'
            );

            aliasInput.value = '';
        }


        const form =
            document.getElementById(
                'form-rekonsiliasi'
            );

        form.classList.remove(
            'hidden'
        );

        form.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });
    }


    function toggleAliasSupplier()
    {
        const checkbox =
            document.getElementById(
                'simpan_alias_supplier'
            );

        const wrapper =
            document.getElementById(
                'alias-input-wrapper'
            );


        if (checkbox.checked) {

            wrapper.classList.remove(
                'hidden'
            );

        } else {

            wrapper.classList.add(
                'hidden'
            );
        }
    }


    function batalPilihMutasi()
    {
        document.getElementById(
            'mutasi_kas_bank_id'
        ).value = '';

        document.getElementById(
            'form-rekonsiliasi'
        ).classList.add(
            'hidden'
        );

        document.getElementById(
            'alias-supplier-box'
        ).classList.add(
            'hidden'
        );

        document.getElementById(
            'alias-input-wrapper'
        ).classList.add(
            'hidden'
        );

        document.getElementById(
            'simpan_alias_supplier'
        ).checked = false;
    }
</script>

@endsection