@extends('layouts.app')

@section('title', 'Transaksi Belum Teridentifikasi')

@section('content')
<div class="min-h-screen bg-slate-50">

    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">

        {{-- HEADER --}}
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h1 class="text-2xl font-bold text-slate-900">
                    Transaksi Belum Teridentifikasi
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Mutasi rekening bank yang belum diketahui transaksi internalnya.
                </p>
            </div>

            <div class="flex flex-wrap items-center gap-2">

                {{-- IMPORT REKENING KORAN --}}
                <a
    href="{{ route('rekening-koran-import.create') }}"
    class="inline-flex items-center justify-center rounded-lg border border-[#15235F] bg-white px-4 py-2 text-sm font-semibold text-[#15235F] transition hover:bg-slate-50"
>
    Import Rekening Koran
</a>

                {{-- TAMBAH MANUAL --}}
                <a
                    href="{{ route('transaksi-belum-teridentifikasi.create') }}"
                    class="inline-flex items-center justify-center rounded-lg bg-[#15235F] px-4 py-2 text-sm font-semibold text-white transition hover:opacity-90"
                >
                    + Tambah Transaksi Bank
                </a>

            </div>

        </div>


        {{-- ALERT SUCCESS --}}
        @if (session('success'))

            <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('success') }}
            </div>

        @endif


        {{-- ALERT ERROR --}}
        @if (session('error'))

            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ session('error') }}
            </div>

        @endif


        {{-- SUMMARY --}}
        <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-3">

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

                <div class="text-sm font-medium text-slate-500">
                    Jumlah Transaksi
                </div>

                <div class="mt-2 text-2xl font-bold text-[#15235F]">
                    {{ number_format($jumlahTransaksi, 0, ',', '.') }}
                </div>

            </div>


            <div class="rounded-xl border border-emerald-200 bg-white p-5 shadow-sm">

                <div class="text-sm font-medium text-slate-500">
                    Total Masuk
                </div>

                <div class="mt-2 text-2xl font-bold text-emerald-600">
                    Rp {{ number_format($totalMasuk, 0, ',', '.') }}
                </div>

            </div>


            <div class="rounded-xl border border-red-200 bg-white p-5 shadow-sm">

                <div class="text-sm font-medium text-slate-500">
                    Total Keluar
                </div>

                <div class="mt-2 text-2xl font-bold text-red-600">
                    Rp {{ number_format($totalKeluar, 0, ',', '.') }}
                </div>

            </div>

        </div>


        {{-- FILTER --}}
        <div class="mb-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

            <form
                method="GET"
                action="{{ route('transaksi-belum-teridentifikasi.index') }}"
            >

                <div class="grid grid-cols-1 gap-4 md:grid-cols-3 lg:grid-cols-6">

                    <div class="lg:col-span-2">

                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Pencarian
                        </label>

                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Nama, referensi, deskripsi..."
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                        >

                    </div>


                    <div>

                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Rekening Bank
                        </label>

                        <select
                            name="rekening_kas_bank_id"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                        >

                            <option value="">
                                Semua
                            </option>

                            @foreach ($rekeningBanks as $rekening)

                                <option
                                    value="{{ $rekening->id }}"
                                    @selected(
                                        (string) request('rekening_kas_bank_id')
                                        === (string) $rekening->id
                                    )
                                >
                                    {{ $rekening->nama_rekening }}
                                </option>

                            @endforeach

                        </select>

                    </div>


                    <div>

                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Jenis
                        </label>

                        <select
                            name="jenis"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                        >

                            <option value="">
                                Semua
                            </option>

                            <option
                                value="MASUK"
                                @selected(request('jenis') === 'MASUK')
                            >
                                Masuk
                            </option>

                            <option
                                value="KELUAR"
                                @selected(request('jenis') === 'KELUAR')
                            >
                                Keluar
                            </option>

                        </select>

                    </div>


                    <div>

                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Status
                        </label>

                        <select
                            name="status"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                        >

                            <option
                                value=""
                                @selected(!request()->filled('status'))
                            >
                                Belum Teridentifikasi
                            </option>

                            <option
                                value="BELUM_TERIDENTIFIKASI"
                                @selected(request('status') === 'BELUM_TERIDENTIFIKASI')
                            >
                                Belum Teridentifikasi
                            </option>

                            <option
                                value="TERIDENTIFIKASI"
                                @selected(request('status') === 'TERIDENTIFIKASI')
                            >
                                Teridentifikasi
                            </option>

                            <option
                                value="DIABAIKAN"
                                @selected(request('status') === 'DIABAIKAN')
                            >
                                Diabaikan
                            </option>

                        </select>

                    </div>


                    <div>

                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Tanggal Mulai
                        </label>

                        <input
                            type="date"
                            name="tanggal_mulai"
                            value="{{ request('tanggal_mulai') }}"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                        >

                    </div>


                    <div>

                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Tanggal Selesai
                        </label>

                        <input
                            type="date"
                            name="tanggal_selesai"
                            value="{{ request('tanggal_selesai') }}"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                        >

                    </div>

                </div>


                <div class="mt-4 flex gap-2">

                    <button
                        type="submit"
                        class="rounded-lg bg-[#15235F] px-5 py-2 text-sm font-semibold text-white"
                    >
                        Terapkan
                    </button>

                    <a
                        href="{{ route('transaksi-belum-teridentifikasi.index') }}"
                        class="rounded-lg border border-slate-300 px-5 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100"
                    >
                        Reset
                    </a>

                </div>

            </form>

        </div>


        {{-- TABLE --}}
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="overflow-x-auto">

                <table class="min-w-full divide-y divide-slate-200">

                    <thead class="bg-slate-100">

                        <tr>

                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">
                                No
                            </th>

                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">
                                Tanggal
                            </th>

                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">
                                Rekening
                            </th>

                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase text-slate-600">
                                Jenis
                            </th>

                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">
                                Pengirim / Penerima
                            </th>

                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">
                                Referensi Bank
                            </th>

                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-slate-600">
                                Jumlah
                            </th>

                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase text-slate-600">
                                Status
                            </th>

                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase text-slate-600">
                                Aksi
                            </th>

                        </tr>

                    </thead>


                    <tbody class="divide-y divide-slate-100">

                        @forelse ($transaksis as $transaksi)

                            <tr class="hover:bg-slate-50">

                                <td class="px-4 py-3 text-sm text-slate-600">
                                    {{ $transaksis->firstItem() + $loop->index }}
                                </td>


                                <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">
                                    {{ $transaksi->tanggal?->format('d/m/Y') }}
                                </td>


                                <td class="px-4 py-3">

                                    <div class="text-sm font-semibold text-slate-900">
                                        {{ $transaksi->rekeningKasBank?->nama_rekening ?? '-' }}
                                    </div>

                                    <div class="mt-1 text-xs text-slate-500">
                                        {{ $transaksi->rekeningKasBank?->nama_bank ?? '-' }}
                                    </div>

                                </td>


                                <td class="px-4 py-3 text-center">

                                    @if ($transaksi->jenis === 'MASUK')

                                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                            Masuk
                                        </span>

                                    @else

                                        <span class="rounded-full bg-red-100 px-2.5 py-1 text-xs font-semibold text-red-700">
                                            Keluar
                                        </span>

                                    @endif

                                </td>


                                <td class="px-4 py-3 text-sm text-slate-700">
                                    {{ $transaksi->nama_pengirim_penerima ?: '-' }}
                                </td>


                                <td class="px-4 py-3">

                                    <div class="text-sm text-slate-700">
                                        {{ $transaksi->nomor_referensi_bank ?: '-' }}
                                    </div>

                                    @if ($transaksi->deskripsi_bank)

                                        <div class="mt-1 max-w-xs truncate text-xs text-slate-500">
                                            {{ $transaksi->deskripsi_bank }}
                                        </div>

                                    @endif

                                </td>


                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-bold">

                                    @if ($transaksi->jenis === 'MASUK')

                                        <span class="text-emerald-600">
                                            Rp {{ number_format(
                                                (float) $transaksi->jumlah,
                                                0,
                                                ',',
                                                '.'
                                            ) }}
                                        </span>

                                    @else

                                        <span class="text-red-600">
                                            Rp {{ number_format(
                                                (float) $transaksi->jumlah,
                                                0,
                                                ',',
                                                '.'
                                            ) }}
                                        </span>

                                    @endif

                                </td>


                                <td class="px-4 py-3 text-center">

                                    @if (
                                        $transaksi->status
                                        === 'BELUM_TERIDENTIFIKASI'
                                    )

                                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">
                                            Belum Teridentifikasi
                                        </span>

                                    @elseif (
                                        $transaksi->status
                                        === 'TERIDENTIFIKASI'
                                    )

                                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                            Teridentifikasi
                                        </span>

                                    @else

                                        <span class="rounded-full bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                            Diabaikan
                                        </span>

                                    @endif

                                </td>


                                <td class="whitespace-nowrap px-4 py-3 text-center">

                                    <a
                                        href="{{ route(
                                            'transaksi-belum-teridentifikasi.show',
                                            $transaksi
                                        ) }}"
                                        class="text-sm font-semibold text-[#15235F] hover:underline"
                                    >
                                        Detail
                                    </a>

                                    @if (
                                        $transaksi->status
                                        === 'BELUM_TERIDENTIFIKASI'
                                    )

                                        <span class="mx-1 text-slate-300">
                                            |
                                        </span>

                                        <a
                                            href="{{ route(
                                                'transaksi-belum-teridentifikasi.edit',
                                                $transaksi
                                            ) }}"
                                            class="text-sm font-semibold text-[#32AAA6] hover:underline"
                                        >
                                            Edit
                                        </a>

                                    @endif

                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td
                                    colspan="9"
                                    class="px-4 py-12 text-center text-sm text-slate-500"
                                >
                                    Tidak ada transaksi belum teridentifikasi.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>

            </div>


            @if ($transaksis->hasPages())

                <div class="border-t border-slate-200 px-5 py-4">
                    {{ $transaksis->links() }}
                </div>

            @endif

        </div>

    </div>

</div>
@endsection