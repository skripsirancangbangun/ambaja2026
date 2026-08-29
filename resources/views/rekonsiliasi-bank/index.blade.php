@extends('layouts.app')

@section('title', 'Rekonsiliasi Bank')

@section('content')

<div class="space-y-6">

    {{-- HEADER --}}
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">

        <div>
            <h2 class="text-xl font-semibold text-slate-800">
                Rekonsiliasi Bank
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Cocokkan transaksi rekening koran dengan mutasi Kas/Bank yang sudah tercatat di sistem.
            </p>
        </div>

        <a
            href="{{ route('transaksi-belum-teridentifikasi.index') }}"
            class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50"
        >
            Transaksi Belum Teridentifikasi
        </a>

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


    {{-- SUMMARY --}}
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2">

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                Belum Direkonsiliasi
            </p>

            <p class="mt-2 text-2xl font-bold text-amber-600">
                {{ number_format($jumlahBelumDirekonsiliasi) }}
            </p>

            <p class="mt-1 text-xs text-slate-400">
                Transaksi rekening koran yang masih perlu dicocokkan.
            </p>

        </div>


        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                Sudah Direkonsiliasi
            </p>

            <p class="mt-2 text-2xl font-bold text-emerald-600">
                {{ number_format($jumlahSudahDirekonsiliasi) }}
            </p>

            <p class="mt-1 text-xs text-slate-400">
                Transaksi yang sudah berhasil dicocokkan.
            </p>

        </div>

    </div>


    {{-- FILTER --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

        <form
            method="GET"
            action="{{ route('rekonsiliasi-bank.index') }}"
            class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-6"
        >

            {{-- SEARCH --}}
            <div class="lg:col-span-2">

                <label class="mb-1.5 block text-xs font-medium text-slate-600">
                    Cari
                </label>

                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Nama, referensi, deskripsi..."
                    class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                >

            </div>


            {{-- REKENING --}}
            <div>

                <label class="mb-1.5 block text-xs font-medium text-slate-600">
                    Rekening Bank
                </label>

                <select
                    name="rekening_kas_bank_id"
                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                >

                    <option value="">
                        Semua Bank
                    </option>

                    @foreach($rekeningBanks as $rekening)

                        <option
                            value="{{ $rekening->id }}"
                            {{ (string) request('rekening_kas_bank_id') === (string) $rekening->id ? 'selected' : '' }}
                        >
                            {{ $rekening->nama_rekening }}
                        </option>

                    @endforeach

                </select>

            </div>


            {{-- JENIS --}}
            <div>

                <label class="mb-1.5 block text-xs font-medium text-slate-600">
                    Jenis
                </label>

                <select
                    name="jenis"
                    class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                >

                    <option value="">
                        Semua
                    </option>

                    <option
                        value="MASUK"
                        {{ request('jenis') === 'MASUK' ? 'selected' : '' }}
                    >
                        Masuk
                    </option>

                    <option
                        value="KELUAR"
                        {{ request('jenis') === 'KELUAR' ? 'selected' : '' }}
                    >
                        Keluar
                    </option>

                </select>

            </div>


            {{-- TANGGAL MULAI --}}
            <div>

                <label class="mb-1.5 block text-xs font-medium text-slate-600">
                    Tanggal Mulai
                </label>

                <input
                    type="date"
                    name="tanggal_mulai"
                    value="{{ request('tanggal_mulai') }}"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                >

            </div>


            {{-- TANGGAL SELESAI --}}
            <div>

                <label class="mb-1.5 block text-xs font-medium text-slate-600">
                    Tanggal Selesai
                </label>

                <input
                    type="date"
                    name="tanggal_selesai"
                    value="{{ request('tanggal_selesai') }}"
                    class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                >

            </div>


            {{-- ACTION --}}
            <div class="flex items-end gap-2 lg:col-span-6">

                <button
                    type="submit"
                    class="rounded-lg bg-[#15235F] px-4 py-2.5 text-sm font-medium text-white hover:bg-[#101b4d]"
                >
                    Terapkan Filter
                </button>

                <a
                    href="{{ route('rekonsiliasi-bank.index') }}"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 hover:bg-slate-50"
                >
                    Reset
                </a>

            </div>

        </form>

    </div>


    {{-- TABLE --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-200 px-5 py-4">

            <h3 class="text-sm font-semibold text-slate-700">
                Transaksi Menunggu Rekonsiliasi
            </h3>

        </div>


        <div class="overflow-x-auto">

            <table class="min-w-full divide-y divide-slate-200">

                <thead class="bg-slate-50">

                    <tr>

                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Tanggal
                        </th>

                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Rekening
                        </th>

                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Jenis
                        </th>

                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Pengirim / Penerima
                        </th>

                        <th class="px-5 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Referensi Bank
                        </th>

                        <th class="px-5 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Jumlah
                        </th>

                        <th class="px-5 py-3 text-center text-xs font-semibold uppercase tracking-wide text-slate-500">
                            Aksi
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-slate-100 bg-white">

                    @forelse($transaksis as $transaksi)

                        <tr class="hover:bg-slate-50">

                            <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">

                                {{ $transaksi->tanggal?->format('d/m/Y') ?? '-' }}

                            </td>


                            <td class="px-5 py-4">

                                <p class="text-sm font-medium text-slate-700">
                                    {{ $transaksi->rekeningKasBank?->nama_rekening ?? '-' }}
                                </p>

                                @if($transaksi->rekeningKasBank?->nomor_rekening)

                                    <p class="mt-0.5 text-xs text-slate-400">
                                        {{ $transaksi->rekeningKasBank->nomor_rekening }}
                                    </p>

                                @endif

                            </td>


                            <td class="px-5 py-4">

                                @if($transaksi->jenis === 'MASUK')

                                    <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-600">
                                        Masuk
                                    </span>

                                @else

                                    <span class="inline-flex rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-600">
                                        Keluar
                                    </span>

                                @endif

                            </td>


                            <td class="px-5 py-4 text-sm text-slate-600">

                                {{ $transaksi->nama_pengirim_penerima ?? '-' }}

                            </td>


                            <td class="px-5 py-4">

                                <p class="text-sm text-slate-600">
                                    {{ $transaksi->nomor_referensi_bank ?? '-' }}
                                </p>

                                @if($transaksi->deskripsi_bank)

                                    <p class="mt-1 max-w-xs truncate text-xs text-slate-400">
                                        {{ $transaksi->deskripsi_bank }}
                                    </p>

                                @endif

                            </td>


                            <td class="whitespace-nowrap px-5 py-4 text-right">

                                <span class="text-sm font-semibold {{ $transaksi->jenis === 'MASUK' ? 'text-emerald-600' : 'text-red-600' }}">

                                    {{ $transaksi->jenis === 'MASUK' ? '+' : '-' }}

                                    Rp {{ number_format(
                                        $transaksi->jumlah,
                                        0,
                                        ',',
                                        '.'
                                    ) }}

                                </span>

                            </td>


                            <td class="whitespace-nowrap px-5 py-4 text-center">

                                <a
                                    href="{{ route(
                                        'rekonsiliasi-bank.show',
                                        $transaksi
                                    ) }}"
                                    class="inline-flex rounded-lg bg-[#15235F] px-3.5 py-2 text-xs font-medium text-white hover:bg-[#101b4d]"
                                >
                                    Rekonsiliasi
                                </a>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="7"
                                class="px-5 py-12 text-center"
                            >

                                <p class="text-sm font-medium text-slate-500">
                                    Tidak ada transaksi yang menunggu rekonsiliasi.
                                </p>

                                <p class="mt-1 text-xs text-slate-400">
                                    Transaksi rekening koran yang belum teridentifikasi akan muncul di sini.
                                </p>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        @if($transaksis->hasPages())

            <div class="border-t border-slate-200 px-5 py-4">
                {{ $transaksis->links() }}
            </div>

        @endif

    </div>

</div>

@endsection