@extends('layouts.app')

@section('title', 'Detail Transaksi Belum Teridentifikasi')

@section('content')
<div class="min-h-screen bg-slate-50">

    <div class="mx-auto max-w-5xl px-4 py-6 sm:px-6 lg:px-8">

        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

            <div>
                <h1 class="text-2xl font-bold text-slate-900">
                    Detail Transaksi Bank
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Mutasi rekening bank yang menunggu identifikasi.
                </p>
            </div>

            <a
                href="{{ route('transaksi-belum-teridentifikasi.index') }}"
                class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100"
            >
                Kembali
            </a>

        </div>

        @if (session('success'))
            <div class="mb-5 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                {{ session('success') }}
            </div>
        @endif

        @if (session('error'))
            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
                {{ session('error') }}
            </div>
        @endif

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-200 px-6 py-4">

                <div>
                    <div class="text-sm text-slate-500">
                        Tanggal
                    </div>

                    <div class="font-semibold text-slate-900">
                        {{ $transaksiBelumTeridentifikasi->tanggal?->format('d/m/Y') }}
                    </div>
                </div>

                <div>
                    @if (
                        $transaksiBelumTeridentifikasi->status
                        === 'BELUM_TERIDENTIFIKASI'
                    )
                        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-semibold text-amber-700">
                            BELUM TERIDENTIFIKASI
                        </span>

                    @elseif (
                        $transaksiBelumTeridentifikasi->status
                        === 'TERIDENTIFIKASI'
                    )
                        <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                            TERIDENTIFIKASI
                        </span>

                    @else
                        <span class="rounded-full bg-slate-200 px-3 py-1 text-xs font-semibold text-slate-600">
                            DIABAIKAN
                        </span>
                    @endif
                </div>

            </div>

            <div class="p-6">

                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

                    <div>
                        <div class="text-xs font-semibold uppercase text-slate-500">
                            Rekening Bank
                        </div>

                        <div class="mt-1 font-semibold text-slate-900">
                            {{ $transaksiBelumTeridentifikasi->rekeningKasBank?->nama_rekening ?? '-' }}
                        </div>

                        <div class="mt-1 text-sm text-slate-500">
                            {{ $transaksiBelumTeridentifikasi->rekeningKasBank?->nama_bank ?? '-' }}

                            @if (
                                $transaksiBelumTeridentifikasi
                                    ->rekeningKasBank
                                    ?->nomor_rekening
                            )
                                •
                                {{ $transaksiBelumTeridentifikasi
                                    ->rekeningKasBank
                                    ->nomor_rekening }}
                            @endif
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-semibold uppercase text-slate-500">
                            Jenis Transaksi
                        </div>

                        <div class="mt-2">
                            @if (
                                $transaksiBelumTeridentifikasi->jenis
                                === 'MASUK'
                            )
                                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                                    UANG MASUK
                                </span>
                            @else
                                <span class="rounded-full bg-red-100 px-3 py-1 text-xs font-semibold text-red-700">
                                    UANG KELUAR
                                </span>
                            @endif
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-semibold uppercase text-slate-500">
                            Jumlah
                        </div>

                        <div
                            class="mt-1 text-2xl font-bold
                                {{ $transaksiBelumTeridentifikasi->jenis === 'MASUK'
                                    ? 'text-emerald-600'
                                    : 'text-red-600'
                                }}"
                        >
                            Rp {{ number_format(
                                (float) $transaksiBelumTeridentifikasi->jumlah,
                                0,
                                ',',
                                '.'
                            ) }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-semibold uppercase text-slate-500">
                            Nama Pengirim / Penerima
                        </div>

                        <div class="mt-1 text-sm font-medium text-slate-900">
                            {{ $transaksiBelumTeridentifikasi->nama_pengirim_penerima ?: '-' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-semibold uppercase text-slate-500">
                            Nomor Referensi Bank
                        </div>

                        <div class="mt-1 text-sm text-slate-900">
                            {{ $transaksiBelumTeridentifikasi->nomor_referensi_bank ?: '-' }}
                        </div>
                    </div>

                    <div>
                        <div class="text-xs font-semibold uppercase text-slate-500">
                            Dibuat Oleh
                        </div>

                        <div class="mt-1 text-sm text-slate-900">
                            {{ $transaksiBelumTeridentifikasi->creator?->name ?? '-' }}
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <div class="text-xs font-semibold uppercase text-slate-500">
                            Deskripsi dari Rekening Koran
                        </div>

                        <div class="mt-2 rounded-lg border border-slate-200 bg-slate-50 p-4 text-sm text-slate-700">
                            {{ $transaksiBelumTeridentifikasi->deskripsi_bank ?: '-' }}
                        </div>
                    </div>

                </div>

                @if (
                    $transaksiBelumTeridentifikasi->status
                    === 'TERIDENTIFIKASI'
                )
                    <div class="mt-6 rounded-lg border border-emerald-200 bg-emerald-50 p-4">

                        <div class="font-semibold text-emerald-800">
                            Transaksi Sudah Teridentifikasi
                        </div>

                        <div class="mt-2 grid grid-cols-1 gap-4 text-sm md:grid-cols-2">

                            <div>
                                <div class="text-emerald-700">
                                    Referensi Internal
                                </div>

                                <div class="font-semibold text-emerald-900">
                                    {{ $transaksiBelumTeridentifikasi->matched_reference_type ?: '-' }}
                                    #{{ $transaksiBelumTeridentifikasi->matched_reference_id ?: '-' }}
                                </div>
                            </div>

                            <div>
                                <div class="text-emerald-700">
                                    Diidentifikasi Oleh
                                </div>

                                <div class="font-semibold text-emerald-900">
                                    {{ $transaksiBelumTeridentifikasi->identifier?->name ?? '-' }}
                                </div>

                                @if ($transaksiBelumTeridentifikasi->identified_at)
                                    <div class="mt-1 text-xs text-emerald-700">
                                        {{ $transaksiBelumTeridentifikasi
                                            ->identified_at
                                            ->format('d/m/Y H:i') }}
                                    </div>
                                @endif
                            </div>

                        </div>

                    </div>
                @endif

            </div>

            @if (
                $transaksiBelumTeridentifikasi->status
                === 'BELUM_TERIDENTIFIKASI'
            )

                <div class="flex flex-wrap justify-end gap-2 border-t border-slate-200 px-6 py-4">

                    <form
                        action="{{ route(
                            'transaksi-belum-teridentifikasi.destroy',
                            $transaksiBelumTeridentifikasi
                        ) }}"
                        method="POST"
                        onsubmit="return confirm('Hapus transaksi bank ini?')"
                    >
                        @csrf
                        @method('DELETE')

                        <button
                            type="submit"
                            class="rounded-lg border border-red-300 bg-white px-4 py-2 text-sm font-semibold text-red-600 hover:bg-red-50"
                        >
                            Hapus
                        </button>
                    </form>

                    <form
                        action="{{ route(
                            'transaksi-belum-teridentifikasi.abaikan',
                            $transaksiBelumTeridentifikasi
                        ) }}"
                        method="POST"
                        onsubmit="return confirm('Tandai transaksi ini sebagai diabaikan?')"
                    >
                        @csrf
                        @method('PATCH')

                        <button
                            type="submit"
                            class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100"
                        >
                            Abaikan
                        </button>
                    </form>

                    <a
                        href="{{ route(
                            'transaksi-belum-teridentifikasi.edit',
                            $transaksiBelumTeridentifikasi
                        ) }}"
                        class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100"
                    >
                        Edit
                    </a>

                </div>

            @else

                <div class="border-t border-slate-200 bg-slate-50 px-6 py-4 text-sm text-slate-600">
                    Transaksi ini sudah diproses dan tidak dapat diedit atau dihapus.
                </div>

            @endif

        </div>

    </div>

</div>
@endsection