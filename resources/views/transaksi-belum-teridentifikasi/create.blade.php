@extends('layouts.app')

@section('title', 'Tambah Transaksi Belum Teridentifikasi')

@section('content')
<div class="min-h-screen bg-slate-50">
    <div class="mx-auto max-w-4xl px-4 py-6 sm:px-6 lg:px-8">

        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900">
                Tambah Transaksi Belum Teridentifikasi
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Catat mutasi dari rekening koran bank yang belum diketahui transaksi internalnya.
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
            action="{{ route('transaksi-belum-teridentifikasi.store') }}"
            method="POST"
            class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm"
        >
            @csrf

            <div class="grid grid-cols-1 gap-5 md:grid-cols-2">

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        Tanggal
                    </label>

                    <input
                        type="date"
                        name="tanggal"
                        value="{{ old('tanggal', now()->format('Y-m-d')) }}"
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
                        <option value="">
                            Pilih Rekening Bank
                        </option>

                        @foreach ($rekeningBanks as $rekening)
                            <option
                                value="{{ $rekening->id }}"
                                @selected(
                                    (string) old('rekening_kas_bank_id')
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
                        <option value="">
                            Pilih Jenis
                        </option>

                        <option
                            value="MASUK"
                            @selected(old('jenis') === 'MASUK')
                        >
                            Uang Masuk
                        </option>

                        <option
                            value="KELUAR"
                            @selected(old('jenis') === 'KELUAR')
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
                        value="{{ old('jumlah') }}"
                        min="0.01"
                        step="0.01"
                        required
                        placeholder="0"
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
                        value="{{ old('nama_pengirim_penerima') }}"
                        placeholder="Contoh: BUDI SANTOSO"
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
                        value="{{ old('nomor_referensi_bank') }}"
                        placeholder="Nomor referensi dari rekening koran"
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
                        placeholder="Contoh: TRSF E-BANKING CR 2808/FTSCY/..."
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    >{{ old('deskripsi_bank') }}</textarea>
                </div>

            </div>

            <div class="mt-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                Transaksi ini belum menambah atau mengurangi saldo pembukuan. Data hanya dicatat sebagai mutasi bank yang menunggu proses identifikasi dan rekonsiliasi.
            </div>

            <div class="mt-6 flex justify-end gap-2">

                <a
                    href="{{ route('transaksi-belum-teridentifikasi.index') }}"
                    class="rounded-lg border border-slate-300 px-5 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100"
                >
                    Batal
                </a>

                <button
                    type="submit"
                    class="rounded-lg bg-[#15235F] px-5 py-2 text-sm font-semibold text-white hover:opacity-90"
                >
                    Simpan
                </button>

            </div>

        </form>

    </div>
</div>
@endsection