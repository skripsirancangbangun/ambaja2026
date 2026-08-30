@extends('layouts.app')

@section('title', 'Pembayaran Supplier')

@section('content')

<div class="space-y-6">

    {{-- HEADER --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">

        <div>
            <h2 class="text-xl font-semibold text-slate-800">
                Pembayaran Supplier
            </h2>

            <p class="mt-1 text-sm text-slate-500">
                Riwayat pembayaran invoice dan utang supplier.
            </p>
        </div>

        <a
            href="{{ route('pembayaran-supplier.create') }}"
            class="inline-flex items-center justify-center rounded-lg bg-[#15235F] px-4 py-2.5 text-sm font-medium text-white hover:bg-[#101b4d]"
        >
            + Tambah Pembayaran
        </a>

    </div>


    {{-- ALERT --}}
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


    {{-- FILTER --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

        <form
            method="GET"
            action="{{ route('pembayaran-supplier.index') }}"
        >

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">

                <div>

                    <label class="mb-2 block text-xs font-medium text-slate-500">
                        Pencarian
                    </label>

                    <input
                        type="text"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="Pembayaran / invoice / supplier..."
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >

                </div>


                <div>

                    <label class="mb-2 block text-xs font-medium text-slate-500">
                        Metode Pembayaran
                    </label>

                    <select
                        name="metode_pembayaran"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none focus:border-[#32AAA6]"
                    >

                        <option value="">
                            Semua Metode
                        </option>

                        @foreach([
                            'TRANSFER' => 'Transfer',
                            'TUNAI' => 'Tunai',
                            'GIRO' => 'Giro',
                            'LAINNYA' => 'Lainnya'
                        ] as $value => $label)

                            <option
                                value="{{ $value }}"
                                {{ request('metode_pembayaran') === $value ? 'selected' : '' }}
                            >
                                {{ $label }}
                            </option>

                        @endforeach

                    </select>

                </div>


                <div>

                    <label class="mb-2 block text-xs font-medium text-slate-500">
                        Dari Tanggal
                    </label>

                    <input
                        type="date"
                        name="tanggal_mulai"
                        value="{{ request('tanggal_mulai') }}"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-[#32AAA6]"
                    >

                </div>


                <div>

                    <label class="mb-2 block text-xs font-medium text-slate-500">
                        Sampai Tanggal
                    </label>

                    <input
                        type="date"
                        name="tanggal_selesai"
                        value="{{ request('tanggal_selesai') }}"
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none focus:border-[#32AAA6]"
                    >

                </div>

            </div>


            <div class="mt-4 flex justify-end gap-2">

                <a
                    href="{{ route('pembayaran-supplier.index') }}"
                    class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-medium text-slate-600 hover:bg-slate-50"
                >
                    Reset
                </a>

                <button
                    type="submit"
                    class="rounded-lg bg-[#15235F] px-5 py-2 text-sm font-medium text-white hover:bg-[#101b4d]"
                >
                    Terapkan
                </button>

            </div>

        </form>

    </div>


    {{-- TABLE --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-200 px-6 py-4">

            <h3 class="text-sm font-semibold text-slate-700">
                Riwayat Pembayaran
            </h3>

            <p class="mt-1 text-xs text-slate-400">
                Total {{ $pembayarans->total() }} transaksi.
            </p>

        </div>


        <div class="overflow-x-auto">

            <table class="w-full min-w-[1150px] text-sm">

                <thead class="bg-slate-50">

                    <tr class="text-left text-xs uppercase tracking-wide text-slate-400">

                        <th class="px-5 py-3">
                            No. Pembayaran
                        </th>

                        <th class="px-5 py-3">
                            Tanggal
                        </th>

                        <th class="px-5 py-3">
                            Supplier
                        </th>

                        <th class="px-5 py-3">
                            Invoice
                        </th>

                        <th class="px-5 py-3">
                            Metode
                        </th>

                        <th class="px-5 py-3">
                            Referensi
                        </th>

                        <th class="px-5 py-3 text-right">
                            Jumlah
                        </th>

                        <th class="px-5 py-3 text-right">
                            Aksi
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-slate-100">

                    @forelse($pembayarans as $pembayaran)

                        <tr class="transition hover:bg-slate-50/70">

                            <td class="px-5 py-4">

                                <a
                                    href="{{ route('pembayaran-supplier.show', $pembayaran) }}"
                                    class="font-semibold text-[#15235F] hover:text-[#32AAA6]"
                                >
                                    {{ $pembayaran->nomor_pembayaran }}
                                </a>

                            </td>


                            <td class="whitespace-nowrap px-5 py-4 text-slate-500">

                                {{ $pembayaran->tanggal_pembayaran?->format('d/m/Y') ?? '-' }}

                            </td>


                            <td class="px-5 py-4 font-medium text-slate-700">

                                {{ $pembayaran->supplier?->nama_supplier ?? '-' }}

                            </td>


                            <td class="px-5 py-4">

                                <p class="font-medium text-slate-700">
                                    {{ $pembayaran->invoiceSupplier?->nomor_invoice_internal ?? '-' }}
                                </p>

                                <p class="mt-1 text-xs text-slate-400">
                                    {{ $pembayaran->invoiceSupplier?->nomor_invoice_supplier ?? '-' }}
                                </p>

                            </td>


                            <td class="px-5 py-4">

                                <span class="rounded-full bg-slate-100 px-2.5 py-1 text-xs font-medium text-slate-600">
                                    {{ $pembayaran->metode_pembayaran }}
                                </span>

                            </td>


                            <td class="px-5 py-4 text-slate-500">

                                {{ $pembayaran->referensi ?: '-' }}

                            </td>


                            <td class="whitespace-nowrap px-5 py-4 text-right font-bold text-emerald-600">

                                Rp {{ number_format(
                                    $pembayaran->jumlah_bayar,
                                    0,
                                    ',',
                                    '.'
                                ) }}

                            </td>


                            <td class="px-5 py-4 text-right">

                                <a
                                    href="{{ route('pembayaran-supplier.show', $pembayaran) }}"
                                    class="rounded-lg border border-slate-200 px-3 py-2 text-xs font-medium text-slate-600 hover:bg-slate-50"
                                >
                                    Detail
                                </a>

                            </td>

                        </tr>

                    @empty

                        <tr>

                            <td
                                colspan="8"
                                class="px-6 py-14 text-center"
                            >

                                <p class="text-sm font-medium text-slate-500">
                                    Belum ada pembayaran supplier.
                                </p>

                                <p class="mt-1 text-xs text-slate-400">
                                    Pembayaran invoice supplier akan muncul di sini.
                                </p>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        @if($pembayarans->hasPages())

            <div class="border-t border-slate-200 px-6 py-4">
                {{ $pembayarans->links() }}
            </div>

        @endif

    </div>

</div>

@endsection