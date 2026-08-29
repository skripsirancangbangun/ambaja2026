@extends('layouts.app')

@section('title', 'Uang Keluar')

@section('content')
<div class="min-h-screen bg-slate-50">
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">

        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">
                    Uang Keluar
                </h1>

                <p class="mt-1 text-sm text-slate-500">
                    Catat pengeluaran uang dari Kas Kecil atau rekening bank perusahaan.
                </p>
            </div>

            <a
                href="{{ route('uang-keluar.create') }}"
                class="inline-flex items-center justify-center rounded-lg bg-[#15235F] px-4 py-2 text-sm font-semibold text-white shadow-sm hover:opacity-90"
            >
                + Tambah Uang Keluar
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

        <div class="mb-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <form method="GET" action="{{ route('uang-keluar.index') }}">

                <div class="grid grid-cols-1 gap-4 md:grid-cols-5">

                    <div class="md:col-span-2">
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Pencarian
                        </label>

                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Nomor transaksi, tujuan, referensi..."
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                        >
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Kas / Bank
                        </label>

                        <select
                            name="rekening_kas_bank_id"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                        >
                            <option value="">Semua</option>

                            @foreach ($rekeningKasBanks as $rekening)
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
                            Status
                        </label>

                        <select
                            name="status"
                            class="w-full rounded-lg border border-slate-300 px-3 py-2 text-sm"
                        >
                            <option value="">Semua</option>
                            <option value="DRAFT" @selected(request('status') === 'DRAFT')>
                                Draft
                            </option>
                            <option value="TERCATAT" @selected(request('status') === 'TERCATAT')>
                                Tercatat
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
                        href="{{ route('uang-keluar.index') }}"
                        class="rounded-lg border border-slate-300 px-5 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-100"
                    >
                        Reset
                    </a>
                </div>

            </form>
        </div>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">

                    <thead class="bg-slate-100">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">
                                No
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">
                                No Transaksi
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">
                                Tanggal
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">
                                Kas / Bank
                            </th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-600">
                                Tujuan
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

                        @forelse ($uangKeluars as $uangKeluar)

                            <tr class="hover:bg-slate-50">

                                <td class="px-4 py-3 text-sm text-slate-600">
                                    {{ $uangKeluars->firstItem() + $loop->index }}
                                </td>

                                <td class="whitespace-nowrap px-4 py-3 text-sm font-semibold text-slate-900">
                                    {{ $uangKeluar->nomor_transaksi }}
                                </td>

                                <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">
                                    {{ $uangKeluar->tanggal?->format('d/m/Y') }}
                                </td>

                                <td class="px-4 py-3 text-sm text-slate-700">
                                    <div class="font-medium">
                                        {{ $uangKeluar->rekeningKasBank?->nama_rekening ?? '-' }}
                                    </div>

                                    <div class="text-xs text-slate-500">
                                        {{ $uangKeluar->rekeningKasBank?->jenis ?? '-' }}
                                    </div>
                                </td>

                                <td class="px-4 py-3 text-sm text-slate-700">
                                    {{ $uangKeluar->tujuan }}
                                </td>

                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-slate-900">
                                    Rp {{ number_format((float) $uangKeluar->jumlah, 0, ',', '.') }}
                                </td>

                                <td class="px-4 py-3 text-center">
                                    @if ($uangKeluar->status === 'TERCATAT')
                                        <span class="rounded-full bg-emerald-100 px-2.5 py-1 text-xs font-semibold text-emerald-700">
                                            Tercatat
                                        </span>
                                    @else
                                        <span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-700">
                                            Draft
                                        </span>
                                    @endif
                                </td>

                                <td class="whitespace-nowrap px-4 py-3 text-center">
                                    <a
                                        href="{{ route('uang-keluar.show', $uangKeluar) }}"
                                        class="text-sm font-semibold text-[#15235F] hover:underline"
                                    >
                                        Detail
                                    </a>

                                    @if ($uangKeluar->status === 'DRAFT')
                                        <span class="mx-1 text-slate-300">|</span>

                                        <a
                                            href="{{ route('uang-keluar.edit', $uangKeluar) }}"
                                            class="text-sm font-semibold text-[#32AAA6] hover:underline"
                                        >
                                            Edit
                                        </a>
                                    @endif
                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td colspan="8" class="px-4 py-12 text-center text-sm text-slate-500">
                                    Belum ada transaksi Uang Keluar.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>
                </table>
            </div>

            @if ($uangKeluars->hasPages())
                <div class="border-t border-slate-200 px-5 py-4">
                    {{ $uangKeluars->links() }}
                </div>
            @endif

        </div>

    </div>
</div>
@endsection