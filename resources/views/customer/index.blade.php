@extends('layouts.app')

@section('title', 'Customer')

@section('content')

<div class="space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-semibold text-slate-800">Data Customer</h2>
            <p class="mt-1 text-sm text-slate-500">
                Kelola data pelanggan untuk proses transaksi penjualan dan piutang.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-2" x-data="{ showImportModal: false }">
            <a
                href="{{ route('customer.import.template') }}"
                class="inline-flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-3.5 py-2.5 text-xs font-semibold text-emerald-700 transition hover:bg-emerald-100"
            >
                📥 Download Template Import
            </a>

            <button
                type="button"
                @click="showImportModal = true"
                class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-xs font-semibold text-slate-700 transition hover:bg-slate-50"
            >
                📊 Import Customer & Saldo Awal
            </button>

            <a
                href="{{ route('customer.create') }}"
                class="inline-flex items-center justify-center gap-2 rounded-lg bg-[#15235F] px-4 py-2.5 text-sm font-medium text-white transition hover:bg-[#101b4d]"
            >
                <span>+</span>
                Tambah Customer
            </a>

            {{-- MODAL IMPORT --}}
            <div
                x-show="showImportModal"
                x-cloak
                class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 p-4 backdrop-blur-sm"
            >
                <div class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl space-y-4 text-left">
                    <div class="border-b border-slate-100 pb-3">
                        <h3 class="text-base font-bold text-[#15235F]">Import Customer & Saldo Awal Piutang</h3>
                        <p class="text-xs text-slate-500">Gunakan file Excel (.xlsx / .csv) sesuai format template.</p>
                    </div>

                    <form action="{{ route('customer.import') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-xs font-semibold uppercase tracking-wider text-slate-600">Pilih File Excel</label>
                            <input
                                type="file"
                                name="file"
                                accept=".xlsx,.xls,.csv"
                                required
                                class="mt-1.5 w-full rounded-xl border border-slate-200 p-2 text-sm text-slate-700 focus:border-[#32AAA6]"
                            >
                        </div>

                        <div class="flex justify-end gap-2 pt-2">
                            <button
                                type="button"
                                @click="showImportModal = false"
                                class="rounded-xl border border-slate-200 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50"
                            >
                                Batal
                            </button>
                            <button
                                type="submit"
                                class="rounded-xl bg-[#15235F] px-5 py-2 text-sm font-semibold text-white hover:bg-[#101b4d]"
                            >
                                Process Import
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            {{ session('error') }}
        </div>
    @endif

    {{-- FILTER & PENCARIAN --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm">
        <form method="GET" action="{{ route('customer.index') }}" class="flex flex-col gap-3 sm:flex-row">
            <div class="relative flex-1">
                <input
                    type="text"
                    name="search"
                    value="{{ request('search') }}"
                    placeholder="Cari kode, nama customer, atau nomor telepon..."
                    class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-700 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                >
            </div>
            <button
                type="submit"
                class="rounded-xl bg-[#15235F] px-5 py-2.5 text-sm font-medium text-white transition hover:bg-[#101b4d]"
            >
                Cari
            </button>
            @if(request('search'))
                <a
                    href="{{ route('customer.index') }}"
                    class="rounded-xl border border-slate-200 bg-slate-100 px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-200"
                >
                    Reset
                </a>
            @endif
        </form>
    </div>

    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-4">
            <h3 class="text-sm font-semibold text-slate-700">Daftar Customer</h3>
            <p class="mt-1 text-xs text-slate-400">
                Total {{ $customers->total() }} customer terdaftar.
            </p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-xs uppercase tracking-wide text-slate-400">
                        <th class="px-6 py-3 font-medium">No</th>
                        <th class="px-6 py-3 font-medium">Kode</th>
                        <th class="px-6 py-3 font-medium">Nama Customer</th>
                        <th class="px-6 py-3 font-medium">Kontak Person</th>
                        <th class="px-6 py-3 font-medium">Telepon</th>
                        <th class="px-6 py-3 font-medium">Kota</th>
                        <th class="px-6 py-3 font-medium">Limit Piutang</th>
                        <th class="px-6 py-3 font-medium">Status</th>
                        <th class="px-6 py-3 text-right font-medium">Aksi</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($customers as $customer)
                        <tr class="transition hover:bg-slate-50">
                            <td class="px-6 py-4 text-slate-500">
                                {{ $customers->firstItem() + $loop->index }}
                            </td>
                            <td class="px-6 py-4 font-medium text-[#15235F]">
                                {{ $customer->kode_customer }}
                            </td>
                            <td class="px-6 py-4">
                                <div class="font-medium text-slate-800">
                                    {{ $customer->nama_customer }}
                                </div>
                                @if($customer->email)
                                    <div class="mt-0.5 text-xs text-slate-400">
                                        {{ $customer->email }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                {{ $customer->contact_person ?: '-' }}
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                {{ $customer->telepon ?: '-' }}
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                {{ $customer->kota ?: '-' }}
                            </td>
                            <td class="px-6 py-4 text-slate-700">
                                Rp {{ number_format($customer->limit_piutang, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-4">
                                @if($customer->is_active)
                                    <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700">
                                        Aktif
                                    </span>
                                @else
                                    <span class="inline-flex rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-500">
                                        Nonaktif
                                    </span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <a
                                        href="{{ route('customer.edit', $customer) }}"
                                        class="rounded-lg bg-teal-50 px-3 py-1.5 text-xs font-medium text-[#15235F] transition hover:bg-teal-100"
                                    >
                                        Edit
                                    </a>
                                    <form
                                        action="{{ route('customer.destroy', $customer) }}"
                                        method="POST"
                                        onsubmit="return confirm('Apakah Anda yakin ingin menghapus customer ini?')"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="rounded-lg bg-rose-50 px-3 py-1.5 text-xs font-medium text-rose-600 transition hover:bg-rose-100"
                                        >
                                            Hapus
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-8 text-center text-slate-400">
                                Belum ada data customer.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($customers->hasPages())
            <div class="border-t border-slate-200 px-6 py-4">
                {{ $customers->links() }}
            </div>
        @endif
    </div>

</div>

@endsection
