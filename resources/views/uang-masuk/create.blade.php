@extends('layouts.app')

@section('title', 'Tambah Uang Masuk')

@section('content')
<div class="min-h-screen bg-slate-50">
    <div class="mx-auto max-w-4xl px-4 py-6 sm:px-6 lg:px-8">

        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900">
                Tambah Uang Masuk
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                Catat penerimaan kas/bank yang bukan berasal dari modul penjualan/piutang.
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
            action="{{ route('uang-masuk.store') }}"
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
                        Kas / Rekening Bank
                    </label>

                    <select
                        name="rekening_kas_bank_id"
                        required
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    >
                        <option value="">
                            Pilih Kas / Bank
                        </option>

                        @foreach ($rekeningKasBanks as $rekening)
                            <option
                                value="{{ $rekening->id }}"
                                @selected((string) old('rekening_kas_bank_id') === (string) $rekening->id)
                            >
                                {{ $rekening->nama_rekening }}

                                @if ($rekening->jenis === 'BANK')
                                    - {{ $rekening->nama_bank }}
                                @endif

                                @if ($rekening->akun)
                                    ({{ $rekening->akun->kode_akun }})
                                @endif
                            </option>
                        @endforeach
                    </select>

                    <p class="mt-1.5 text-xs text-slate-500">
                        Rekening harus sudah terhubung dengan akun COA.
                    </p>
                </div>


                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        Jumlah
                    </label>

                    <input
                        type="number"
                        name="jumlah"
                        step="0.01"
                        min="0.01"
                        value="{{ old('jumlah') }}"
                        required
                        placeholder="0"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    >
                </div>


                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        Sumber Uang
                    </label>

                    <input
                        type="text"
                        name="sumber"
                        value="{{ old('sumber') }}"
                        required
                        placeholder="Contoh: Setoran Owner"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    >
                </div>


                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        Akun Lawan
                        <span class="text-red-500">*</span>
                    </label>

                    <select
                        name="akun_lawan_id"
                        required
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    >
                        <option value="">
                            Pilih akun lawan
                        </option>

                        @foreach ($akunLawans as $akun)
                            <option
                                value="{{ $akun->id }}"
                                @selected((string) old('akun_lawan_id') === (string) $akun->id)
                            >
                                {{ $akun->kode_akun }} - {{ $akun->nama_akun }}
                            </option>
                        @endforeach
                    </select>

                    <div class="mt-2 rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs text-blue-700">
                        Contoh: setoran owner → Modal Owner, pendapatan lain → Pendapatan Lain-lain, pinjaman → Utang Bank.
                        Untuk transfer antar kas/bank gunakan menu Transfer Antar Rekening.
                    </div>
                </div>


                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        Referensi
                    </label>

                    <input
                        type="text"
                        name="referensi"
                        value="{{ old('referensi') }}"
                        placeholder="Nomor bukti / nomor transfer / referensi lainnya"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    >
                </div>


                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        Keterangan
                    </label>

                    <textarea
                        name="keterangan"
                        rows="4"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    >{{ old('keterangan') }}</textarea>
                </div>

            </div>


            <div class="mt-6 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-700">
                Transaksi disimpan sebagai <strong>Draft</strong>. Saat tombol Catat dijalankan, sistem akan membuat mutasi Kas/Bank dan jurnal:
                <strong>Dr Kas/Bank — Cr Akun Lawan</strong>.
            </div>


            <div class="mt-6 flex justify-end gap-2">
                <a
                    href="{{ route('uang-masuk.index') }}"
                    class="rounded-lg border border-slate-300 px-5 py-2 text-sm font-semibold text-slate-700"
                >
                    Batal
                </a>

                <button
                    type="submit"
                    class="rounded-lg bg-[#15235F] px-5 py-2 text-sm font-semibold text-white"
                >
                    Simpan Draft
                </button>
            </div>
        </form>

    </div>
</div>
@endsection
