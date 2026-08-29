@extends('layouts.app')

@section('title', 'Utang Supplier')

@section('content')

<div class="space-y-6">

    {{-- HEADER --}}
    <div>

        <h2 class="text-xl font-semibold text-slate-800">
            Utang Supplier
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            Monitoring tagihan supplier yang masih memiliki saldo utang.
        </p>

    </div>


    {{-- SUMMARY --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                Total Utang
            </p>

            <p class="mt-2 text-xl font-bold text-[#15235F]">
                Rp {{ number_format($totalUtang, 0, ',', '.') }}
            </p>

        </div>


        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                Belum Lunas
            </p>

            <p class="mt-2 text-2xl font-bold text-red-500">
                {{ $belumLunas }}
            </p>

            <p class="mt-1 text-xs text-slate-400">
                Invoice
            </p>

        </div>


        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                Dibayar Sebagian
            </p>

            <p class="mt-2 text-2xl font-bold text-amber-500">
                {{ $sebagian }}
            </p>

            <p class="mt-1 text-xs text-slate-400">
                Invoice
            </p>

        </div>


        <div class="rounded-2xl border border-red-100 bg-red-50 p-5">

            <p class="text-xs font-medium uppercase tracking-wide text-red-400">
                Lewat Jatuh Tempo
            </p>

            <p class="mt-2 text-xl font-bold text-red-600">
                Rp {{ number_format($jatuhTempo, 0, ',', '.') }}
            </p>

        </div>

    </div>


    {{-- FILTER --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">

        <form
            method="GET"
            action="{{ route('utang.index') }}"
        >

            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-5">

                {{-- SEARCH --}}
                <div class="xl:col-span-2">

                    <label
                        for="search"
                        class="mb-2 block text-xs font-medium text-slate-500"
                    >
                        Pencarian
                    </label>

                    <input
                        type="text"
                        id="search"
                        name="search"
                        value="{{ request('search') }}"
                        placeholder="No. invoice / supplier..."
                        class="w-full rounded-lg border border-slate-300 px-3.5 py-2.5 text-sm outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >

                </div>


                {{-- SUPPLIER --}}
                <div>

                    <label
                        for="supplier_id"
                        class="mb-2 block text-xs font-medium text-slate-500"
                    >
                        Supplier
                    </label>

                    <select
                        id="supplier_id"
                        name="supplier_id"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >

                        <option value="">
                            Semua Supplier
                        </option>

                        @foreach($suppliers as $supplier)

                            <option
                                value="{{ $supplier->id }}"
                                {{ request('supplier_id') == $supplier->id ? 'selected' : '' }}
                            >
                                {{ $supplier->nama_supplier }}
                            </option>

                        @endforeach

                    </select>

                </div>


                {{-- STATUS --}}
                <div>

                    <label
                        for="status"
                        class="mb-2 block text-xs font-medium text-slate-500"
                    >
                        Status
                    </label>

                    <select
                        id="status"
                        name="status"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >

                        <option value="">
                            Semua Status
                        </option>

                        <option
                            value="BELUM_LUNAS"
                            {{ request('status') === 'BELUM_LUNAS' ? 'selected' : '' }}
                        >
                            Belum Lunas
                        </option>

                        <option
                            value="SEBAGIAN"
                            {{ request('status') === 'SEBAGIAN' ? 'selected' : '' }}
                        >
                            Sebagian
                        </option>

                    </select>

                </div>


                {{-- JATUH TEMPO --}}
                <div>

                    <label
                        for="jatuh_tempo"
                        class="mb-2 block text-xs font-medium text-slate-500"
                    >
                        Jatuh Tempo
                    </label>

                    <select
                        id="jatuh_tempo"
                        name="jatuh_tempo"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >

                        <option value="">
                            Semua
                        </option>

                        <option
                            value="lewat"
                            {{ request('jatuh_tempo') === 'lewat' ? 'selected' : '' }}
                        >
                            Sudah Lewat
                        </option>

                        <option
                            value="hari_ini"
                            {{ request('jatuh_tempo') === 'hari_ini' ? 'selected' : '' }}
                        >
                            Hari Ini
                        </option>

                        <option
                            value="7_hari"
                            {{ request('jatuh_tempo') === '7_hari' ? 'selected' : '' }}
                        >
                            7 Hari Ke Depan
                        </option>

                    </select>

                </div>

            </div>


            <div class="mt-4 flex justify-end gap-2">

                <a
                    href="{{ route('utang.index') }}"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
                >
                    Reset
                </a>

                <button
                    type="submit"
                    class="rounded-lg bg-[#15235F] px-5 py-2 text-sm font-medium text-white transition hover:bg-[#101b4d]"
                >
                    Terapkan Filter
                </button>

            </div>

        </form>

    </div>


    {{-- TABLE --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">

        <div class="border-b border-slate-200 px-6 py-4">

            <h3 class="text-sm font-semibold text-slate-700">
                Daftar Utang
            </h3>

            <p class="mt-1 text-xs text-slate-400">
                {{ $utangs->total() }} invoice masih memiliki saldo utang.
            </p>

        </div>


        <div class="overflow-x-auto">

            <table class="w-full min-w-[1350px] text-sm">

                <thead class="bg-slate-50">

                    <tr class="text-left text-xs uppercase tracking-wide text-slate-400">

                        <th class="px-5 py-3 font-medium">
                            Invoice
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Supplier
                        </th>

                        <th class="px-5 py-3 font-medium">
                            PO
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Tanggal
                        </th>

                        <th class="px-5 py-3 font-medium">
                            Jatuh Tempo
                        </th>

                        <th class="px-5 py-3 text-right font-medium">
                            Total
                        </th>

                        <th class="px-5 py-3 text-right font-medium">
                            Dibayar
                        </th>

                        <th class="px-5 py-3 text-right font-medium">
                            Sisa Utang
                        </th>

                        <th class="px-5 py-3 text-center font-medium">
                            Status
                        </th>

                        <th class="px-5 py-3 text-right font-medium">
                            Aksi
                        </th>

                    </tr>

                </thead>


                <tbody class="divide-y divide-slate-100">

                    @forelse($utangs as $utang)

                        @php

                            $isOverdue =
                                $utang->tanggal_jatuh_tempo
                                && $utang->tanggal_jatuh_tempo->lt(today());

                            $statusClass = match($utang->status) {
                                'BELUM_LUNAS'
                                    => 'bg-red-50 text-red-600',

                                'SEBAGIAN'
                                    => 'bg-amber-50 text-amber-600',

                                default
                                    => 'bg-slate-100 text-slate-600',
                            };

                        @endphp


                        <tr class="transition hover:bg-slate-50/70">

                            {{-- INVOICE --}}
                            <td class="px-5 py-4">

                                <a
                                    href="{{ route('invoice-supplier.show', $utang) }}"
                                    class="font-semibold text-[#15235F] transition hover:text-[#32AAA6]"
                                >
                                    {{ $utang->nomor_invoice_internal }}
                                </a>

                                <p class="mt-1 text-xs text-slate-400">
                                    {{ $utang->nomor_invoice_supplier }}
                                </p>

                            </td>


                            {{-- SUPPLIER --}}
                            <td class="px-5 py-4">

                                <p class="font-medium text-slate-700">
                                    {{ $utang->supplier?->nama_supplier ?? '-' }}
                                </p>

                                @if($utang->supplier?->kode_supplier)

                                    <p class="mt-1 text-xs text-slate-400">
                                        {{ $utang->supplier->kode_supplier }}
                                    </p>

                                @endif

                            </td>


                            {{-- PO --}}
                            <td class="px-5 py-4">

                                @if($utang->purchaseOrder)

                                    <a
                                        href="{{ route('purchase-order.show', $utang->purchaseOrder) }}"
                                        class="font-medium text-slate-600 transition hover:text-[#15235F]"
                                    >
                                        {{ $utang->purchaseOrder->nomor_po }}
                                    </a>

                                @else

                                    <span class="text-slate-400">
                                        -
                                    </span>

                                @endif

                            </td>


                            {{-- TANGGAL INVOICE --}}
                            <td class="whitespace-nowrap px-5 py-4 text-slate-500">

                                {{ $utang->tanggal_invoice?->format('d/m/Y') ?? '-' }}

                            </td>


                            {{-- JATUH TEMPO --}}
                            <td class="whitespace-nowrap px-5 py-4">

                                <p class="{{ $isOverdue ? 'font-semibold text-red-600' : 'text-slate-600' }}">

                                    {{ $utang->tanggal_jatuh_tempo?->format('d/m/Y') ?? '-' }}

                                </p>


                                @if($isOverdue)

                                    <span class="mt-1 inline-flex rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-medium text-red-600">
                                        Lewat Jatuh Tempo
                                    </span>

                                @endif

                            </td>


                            {{-- TOTAL --}}
                            <td class="whitespace-nowrap px-5 py-4 text-right font-medium text-slate-700">

                                Rp {{ number_format(
                                    $utang->total,
                                    0,
                                    ',',
                                    '.'
                                ) }}

                            </td>


                            {{-- DIBAYAR --}}
                            <td class="whitespace-nowrap px-5 py-4 text-right">

                                @if((float) $utang->total_dibayar > 0)

                                    <span class="font-semibold text-emerald-600">

                                        Rp {{ number_format(
                                            $utang->total_dibayar,
                                            0,
                                            ',',
                                            '.'
                                        ) }}

                                    </span>

                                @else

                                    <span class="text-slate-400">
                                        Rp 0
                                    </span>

                                @endif

                            </td>


                            {{-- SISA UTANG --}}
                            <td class="whitespace-nowrap px-5 py-4 text-right">

                                <p class="font-bold text-red-600">

                                    Rp {{ number_format(
                                        $utang->sisa_utang,
                                        0,
                                        ',',
                                        '.'
                                    ) }}

                                </p>

                            </td>


                            {{-- STATUS --}}
                            <td class="px-5 py-4 text-center">

                                <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-medium {{ $statusClass }}">

                                    {{ str_replace(
                                        '_',
                                        ' ',
                                        $utang->status
                                    ) }}

                                </span>

                            </td>


                            {{-- AKSI --}}
                            <td class="px-5 py-4">

                                <div class="flex items-center justify-end gap-2">

                                    {{-- DETAIL --}}
                                    <a
                                        href="{{ route('invoice-supplier.show', $utang) }}"
                                        class="inline-flex h-9 items-center justify-center rounded-lg border border-slate-200 bg-white px-3 text-xs font-medium text-slate-600 transition hover:bg-slate-50 hover:text-[#15235F]"
                                    >
                                        Detail
                                    </a>


                                    {{-- BAYAR --}}
                                    <a
                                        href="{{ route(
                                            'pembayaran-supplier.create',
                                            [
                                                'invoice_supplier_id' => $utang->id
                                            ]
                                        ) }}"
                                        class="inline-flex h-9 items-center justify-center rounded-lg bg-[#15235F] px-3 text-xs font-medium text-white transition hover:bg-[#101b4d]"
                                    >
                                        Bayar
                                    </a>

                                </div>

                            </td>

                        </tr>


                    @empty

                        <tr>

                            <td
                                colspan="10"
                                class="px-6 py-14 text-center"
                            >

                                <p class="text-sm font-medium text-slate-500">
                                    Tidak ada utang supplier.
                                </p>

                                <p class="mt-1 text-xs text-slate-400">
                                    Invoice yang masih memiliki saldo utang akan muncul di sini.
                                </p>

                            </td>

                        </tr>

                    @endforelse

                </tbody>

            </table>

        </div>


        {{-- PAGINATION --}}
        @if($utangs->hasPages())

            <div class="border-t border-slate-200 px-6 py-4">
                {{ $utangs->links() }}
            </div>

        @endif

    </div>

</div>

@endsection