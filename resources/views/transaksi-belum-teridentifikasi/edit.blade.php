@extends('layouts.app')

@section('title', 'Edit Transaksi Belum Teridentifikasi')

@section('content')
<div class="min-h-screen bg-slate-50">
    <div class="mx-auto max-w-4xl px-4 py-6 sm:px-6 lg:px-8">

        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900">
                Edit Transaksi Belum Teridentifikasi
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Perbarui data mutasi rekening bank.
            </p>
        </div>

        @if ($errors->any())
            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form
            action="{{ route(
                'transaksi-belum-teridentifikasi.update',
                $transaksiBelumTeridentifikasi
            ) }}"
            method="POST"
            class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm"
        >
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        Tanggal
                    </label>

                    <input
                        type="date"
                        name="tanggal"
                        value="{{ old(
                            'tanggal',
                            $transaksiBelumTeridentifikasi->tanggal?->format('Y-m-d')
                        ) }}"
                        required
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    >
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        Rekening Bank
                    </label>

                    <select
                        name="rekening_kas_bank_id"
                        required
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    >
                        @foreach ($rekeningBanks as $rekening)
                            <option
                                value="{{ $rekening->id }}"
                                @selected(
                                    (string) old(
                                        'rekening_kas_bank_id',
                                        $transaksiBelumTeridentifikasi->rekening_kas_bank_id
                                    )
                                    === (string) $rekening->id
                                )
                            >
                                {{ $rekening->nama_rekening }}

                                @if ($rekening->nama_bank)
                                    - {{ $rekening->nama_bank }}
                                @endif

                                @if ($rekening->nomor_rekening)
                                    - {{ $rekening->nomor_rekening }}
                                @endif
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        Jenis Transaksi
                    </label>

                    <select
                        name="jenis"
                        required
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    >
                        <option
                            value="MASUK"
                            @selected(
                                old(
                                    'jenis',
                                    $transaksiBelumTeridentifikasi->jenis
                                ) === 'MASUK'
                            )
                        >
                            Uang Masuk
                        </option>

                        <option
                            value="KELUAR"
                            @selected(
                                old(
                                    'jenis',
                                    $transaksiBelumTeridentifikasi->jenis
                                ) === 'KELUAR'
                            )
                        >
                            Uang Keluar
                        </option>
                    </select>
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        Jumlah
                    </label>

                    <input
                        type="number"
                        name="jumlah"
                        value="{{ old(
                            'jumlah',
                            $transaksiBelumTeridentifikasi->jumlah
                        ) }}"
                        min="0.01"
                        step="0.01"
                        required
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    >
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        Nama Pengirim / Penerima
                    </label>

                    <input
                        type="text"
                        name="nama_pengirim_penerima"
                        value="{{ old(
                            'nama_pengirim_penerima',
                            $transaksiBelumTeridentifikasi->nama_pengirim_penerima
                        ) }}"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    >
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        Nomor Referensi Bank
                    </label>

                    <input
                        type="text"
                        name="nomor_referensi_bank"
                        value="{{ old(
                            'nomor_referensi_bank',
                            $transaksiBelumTeridentifikasi->nomor_referensi_bank
                        ) }}"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    >
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        Deskripsi Bank
                    </label>

                    <textarea
                        name="deskripsi_bank"
                        rows="4"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    >{{ old(
                        'deskripsi_bank',
                        $transaksiBelumTeridentifikasi->deskripsi_bank
                    ) }}</textarea>
                </div>

            </div>

            <div class="mt-6 flex justify-end gap-2">

                <a
                    href="{{ route(
                        'transaksi-belum-teridentifikasi.show',
                        $transaksiBelumTeridentifikasi
                    ) }}"
                    class="rounded-lg border border-slate-300 px-5 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100"
                >
                    Batal
                </a>

                <button
                    type="submit"
                    class="rounded-lg bg-[#15235F] px-5 py-2 text-sm font-semibold text-white hover:opacity-90"
                >
                    Simpan Perubahan
                </button>

            </div>

        </form>

    </div>
</div>
@endsection