@extends('layouts.app')

@section('title', 'Edit Uang Keluar')

@section('content')
<div class="min-h-screen bg-slate-50">
    <div class="mx-auto max-w-4xl px-4 py-6 sm:px-6 lg:px-8">

        <div class="mb-6">
            <h1 class="text-2xl font-bold text-slate-900">
                Edit Uang Keluar
            </h1>

            <p class="mt-1 text-sm text-slate-500">
                {{ $uangKeluar->nomor_transaksi }}
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
            action="{{ route('uang-keluar.update', $uangKeluar) }}"
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
                        value="{{ old('tanggal', $uangKeluar->tanggal?->format('Y-m-d')) }}"
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
                        @foreach ($rekeningKasBanks as $rekening)
                            <option
                                value="{{ $rekening->id }}"
                                @selected(
                                    (string) old(
                                        'rekening_kas_bank_id',
                                        $uangKeluar->rekening_kas_bank_id
                                    )
                                    === (string) $rekening->id
                                )
                            >
                                {{ $rekening->nama_rekening }}

                                @if ($rekening->jenis === 'BANK')
                                    - {{ $rekening->nama_bank }}
                                @endif
                            </option>
                        @endforeach
                    </select>
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
                        value="{{ old('jumlah', $uangKeluar->jumlah) }}"
                        required
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    >
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        Tujuan Pengeluaran
                    </label>

                    <input
                        type="text"
                        name="tujuan"
                        value="{{ old('tujuan', $uangKeluar->tujuan) }}"
                        required
                        class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                    >
                </div>

                <div class="md:col-span-2">
                    <label class="mb-1 block text-sm font-medium text-slate-700">
                        Referensi
                    </label>

                    <input
                        type="text"
                        name="referensi"
                        value="{{ old('referensi', $uangKeluar->referensi) }}"
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
                    >{{ old('keterangan', $uangKeluar->keterangan) }}</textarea>
                </div>

            </div>

            <div class="mt-6 flex justify-end gap-2">

                <a
                    href="{{ route('uang-keluar.show', $uangKeluar) }}"
                    class="rounded-lg border border-slate-300 px-5 py-2 text-sm font-semibold text-slate-700"
                >
                    Batal
                </a>

                <button
                    type="submit"
                    class="rounded-lg bg-[#15235F] px-5 py-2 text-sm font-semibold text-white"
                >
                    Simpan Perubahan
                </button>

            </div>

        </form>

    </div>
</div>
@endsection