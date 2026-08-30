@extends('layouts.app')

@section('title', 'Laporan Utang')

@section('content')
<div class="min-h-screen bg-slate-50">
    <div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">

        {{-- HEADER --}}
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-bold text-slate-900">
                    Laporan Utang
                </h1>
                <p class="mt-1 text-sm text-slate-500">
                    Monitoring utang supplier berdasarkan invoice pembelian.
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a
                    href="{{ route('laporan.utang.pdf', request()->query()) }}"
                    class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 shadow-sm transition hover:bg-slate-100"
                >
                    Export PDF
                </a>

                <a
                    href="{{ route('laporan.utang.excel', request()->query()) }}"
                    class="inline-flex items-center justify-center rounded-lg bg-[#15235F] px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:opacity-90"
                >
                    Export Excel
                </a>
            </div>
        </div>


        {{-- ERROR --}}
        @if ($errors->any())
            <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">
                <ul class="list-disc space-y-1 pl-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif


        {{-- FILTER --}}
        <div class="mb-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">

            <form method="GET" action="{{ route('laporan.utang') }}">
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">

                    {{-- SEARCH --}}
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Pencarian
                        </label>

                        <input
                            type="text"
                            name="search"
                            value="{{ request('search') }}"
                            placeholder="Invoice, supplier, atau PO..."
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-[#32AAA6]/20"
                        >
                    </div>


                    {{-- SUPPLIER --}}
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Supplier
                        </label>

                        <select
                            name="supplier_id"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-[#32AAA6]/20"
                        >
                            <option value="">Semua Supplier</option>

                            @foreach ($suppliers as $supplier)
                                <option
                                    value="{{ $supplier->id }}"
                                    @selected((string) request('supplier_id') === (string) $supplier->id)
                                >
                                    {{ $supplier->nama_supplier }}
                                </option>
                            @endforeach
                        </select>
                    </div>


                    {{-- STATUS PEMBAYARAN --}}
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Status Pembayaran
                        </label>

                        <select
                            name="status"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-[#32AAA6]/20"
                        >
                            <option value="">Semua Status</option>

                            <option value="BELUM_LUNAS" @selected(request('status') === 'BELUM_LUNAS')>
                                Belum Lunas
                            </option>

                            <option value="SEBAGIAN" @selected(request('status') === 'SEBAGIAN')>
                                Sebagian
                            </option>

                            <option value="LUNAS" @selected(request('status') === 'LUNAS')>
                                Lunas
                            </option>
                        </select>
                    </div>


                    {{-- STATUS JATUH TEMPO --}}
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Status Jatuh Tempo
                        </label>

                        <select
                            name="jatuh_tempo"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-[#32AAA6]/20"
                        >
                            <option value="">Semua</option>

                            <option value="JATUH_TEMPO" @selected(request('jatuh_tempo') === 'JATUH_TEMPO')>
                                Sudah Jatuh Tempo
                            </option>

                            <option value="HARI_INI" @selected(request('jatuh_tempo') === 'HARI_INI')>
                                Jatuh Tempo Hari Ini
                            </option>

                            <option value="BELUM_JATUH_TEMPO" @selected(request('jatuh_tempo') === 'BELUM_JATUH_TEMPO')>
                                Belum Jatuh Tempo
                            </option>
                        </select>
                    </div>


                    {{-- TANGGAL INVOICE MULAI --}}
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Tanggal Invoice Mulai
                        </label>

                        <input
                            type="date"
                            name="tanggal_mulai"
                            value="{{ request('tanggal_mulai') }}"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-[#32AAA6]/20"
                        >
                    </div>


                    {{-- TANGGAL INVOICE SELESAI --}}
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Tanggal Invoice Selesai
                        </label>

                        <input
                            type="date"
                            name="tanggal_selesai"
                            value="{{ request('tanggal_selesai') }}"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-[#32AAA6]/20"
                        >
                    </div>


                    {{-- JATUH TEMPO MULAI --}}
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Jatuh Tempo Mulai
                        </label>

                        <input
                            type="date"
                            name="jatuh_tempo_mulai"
                            value="{{ request('jatuh_tempo_mulai') }}"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-[#32AAA6]/20"
                        >
                    </div>


                    {{-- JATUH TEMPO SELESAI --}}
                    <div>
                        <label class="mb-1 block text-sm font-medium text-slate-700">
                            Jatuh Tempo Selesai
                        </label>

                        <input
                            type="date"
                            name="jatuh_tempo_selesai"
                            value="{{ request('jatuh_tempo_selesai') }}"
                            class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 outline-none transition focus:border-[#32AAA6] focus:ring-2 focus:ring-[#32AAA6]/20"
                        >
                    </div>

                </div>


                <div class="mt-5 flex flex-wrap gap-2">
                    <button
                        type="submit"
                        class="rounded-lg bg-[#15235F] px-5 py-2 text-sm font-semibold text-white transition hover:opacity-90"
                    >
                        Terapkan Filter
                    </button>

                    <a
                        href="{{ route('laporan.utang') }}"
                        class="rounded-lg border border-slate-300 bg-white px-5 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100"
                    >
                        Reset
                    </a>
                </div>
            </form>

        </div>


        {{-- SUMMARY --}}
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">

            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">
                    Total Invoice
                </p>

                <p class="mt-2 text-2xl font-bold text-slate-900">
                    Rp {{ number_format($summary['total_invoice'], 0, ',', '.') }}
                </p>

                <p class="mt-1 text-xs text-slate-500">
                    {{ number_format($summary['jumlah_invoice'], 0, ',', '.') }} invoice
                </p>
            </div>


            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">
                    Sudah Dibayar
                </p>

                <p class="mt-2 text-2xl font-bold text-slate-900">
                    Rp {{ number_format($summary['total_dibayar'], 0, ',', '.') }}
                </p>
            </div>


            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-slate-500">
                    Sisa Utang
                </p>

                <p class="mt-2 text-2xl font-bold text-[#15235F]">
                    Rp {{ number_format($summary['total_utang'], 0, ',', '.') }}
                </p>

                <p class="mt-1 text-xs text-slate-500">
                    {{ number_format($summary['jumlah_belum_lunas'], 0, ',', '.') }} invoice belum lunas
                </p>
            </div>


            <div class="rounded-xl border border-red-200 bg-red-50 p-5 shadow-sm">
                <p class="text-sm font-medium text-red-600">
                    Utang Jatuh Tempo
                </p>

                <p class="mt-2 text-2xl font-bold text-red-700">
                    Rp {{ number_format($summary['utang_jatuh_tempo'], 0, ',', '.') }}
                </p>

                <p class="mt-1 text-xs text-red-600">
                    Saldo yang melewati tanggal jatuh tempo
                </p>
            </div>

        </div>


        {{-- TABLE --}}
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-5 py-4">
                <h2 class="font-semibold text-slate-900">
                    Daftar Utang Supplier
                </h2>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">

                    <thead class="bg-slate-100">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                                No
                            </th>

                            <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                                No Invoice
                            </th>

                            <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                                Invoice Supplier
                            </th>

                            <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                                Supplier
                            </th>

                            <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                                No PO
                            </th>

                            <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                                Tanggal Invoice
                            </th>

                            <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                                Jatuh Tempo
                            </th>

                            <th class="whitespace-nowrap px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">
                                Total
                            </th>

                            <th class="whitespace-nowrap px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">
                                Dibayar
                            </th>

                            <th class="whitespace-nowrap px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-slate-600">
                                Sisa Utang
                            </th>

                            <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                                Status
                            </th>

                            <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-slate-600">
                                Jatuh Tempo
                            </th>
                        </tr>
                    </thead>


                    <tbody class="divide-y divide-slate-100 bg-white">

                        @forelse ($invoices as $invoice)

                            @php
                                $sisaUtang = (float) $invoice->sisa_utang;
                                $today = \Carbon\Carbon::today();

                                $dueDate = $invoice->tanggal_jatuh_tempo
                                    ? \Carbon\Carbon::parse($invoice->tanggal_jatuh_tempo)->startOfDay()
                                    : null;

                                if ($sisaUtang <= 0) {
                                    $jatuhTempoLabel = 'Lunas';
                                    $jatuhTempoClass = 'bg-emerald-100 text-emerald-700';
                                } elseif (!$dueDate) {
                                    $jatuhTempoLabel = '-';
                                    $jatuhTempoClass = 'bg-slate-100 text-slate-600';
                                } elseif ($dueDate->lt($today)) {
                                    $hari = $dueDate->diffInDays($today);
                                    $jatuhTempoLabel = $hari . ' hari terlambat';
                                    $jatuhTempoClass = 'bg-red-100 text-red-700';
                                } elseif ($dueDate->equalTo($today)) {
                                    $jatuhTempoLabel = 'Hari ini';
                                    $jatuhTempoClass = 'bg-amber-100 text-amber-700';
                                } else {
                                    $hari = $today->diffInDays($dueDate);
                                    $jatuhTempoLabel = $hari . ' hari lagi';
                                    $jatuhTempoClass = 'bg-sky-100 text-sky-700';
                                }

                                $status = strtoupper((string) $invoice->status);

                                if ($status === 'LUNAS') {
                                    $statusLabel = 'Lunas';
                                    $statusClass = 'bg-emerald-100 text-emerald-700';
                                } elseif ($status === 'SEBAGIAN') {
                                    $statusLabel = 'Sebagian';
                                    $statusClass = 'bg-amber-100 text-amber-700';
                                } else {
                                    $statusLabel = 'Belum Lunas';
                                    $statusClass = 'bg-red-100 text-red-700';
                                }
                            @endphp

                            <tr class="hover:bg-slate-50">

                                <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-600">
                                    {{ $invoices->firstItem() + $loop->index }}
                                </td>


                                <td class="whitespace-nowrap px-4 py-3 text-sm font-semibold text-slate-900">
                                    {{ $invoice->nomor_invoice_internal }}
                                </td>


                                <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">
                                    {{ $invoice->nomor_invoice_supplier ?: '-' }}
                                </td>


                                <td class="min-w-[180px] px-4 py-3 text-sm text-slate-700">
                                    <div class="font-medium text-slate-900">
                                        {{ $invoice->supplier?->nama_supplier ?: '-' }}
                                    </div>

                                    @if ($invoice->supplier?->kode_supplier)
                                        <div class="mt-0.5 text-xs text-slate-500">
                                            {{ $invoice->supplier->kode_supplier }}
                                        </div>
                                    @endif
                                </td>


                                <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">
                                    {{ $invoice->purchaseOrder?->nomor_po ?: '-' }}
                                </td>


                                <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">
                                    {{ $invoice->tanggal_invoice?->format('d/m/Y') ?: '-' }}
                                </td>


                                <td class="whitespace-nowrap px-4 py-3 text-sm text-slate-700">
                                    {{ $invoice->tanggal_jatuh_tempo?->format('d/m/Y') ?: '-' }}
                                </td>


                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-slate-700">
                                    Rp {{ number_format((float) $invoice->total, 0, ',', '.') }}
                                </td>


                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-slate-700">
                                    Rp {{ number_format((float) $invoice->total_dibayar, 0, ',', '.') }}
                                </td>


                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-[#15235F]">
                                    Rp {{ number_format((float) $invoice->sisa_utang, 0, ',', '.') }}
                                </td>


                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $statusClass }}">
                                        {{ $statusLabel }}
                                    </span>
                                </td>


                                <td class="whitespace-nowrap px-4 py-3">
                                    <span class="inline-flex rounded-full px-2.5 py-1 text-xs font-semibold {{ $jatuhTempoClass }}">
                                        {{ $jatuhTempoLabel }}
                                    </span>
                                </td>

                            </tr>

                        @empty

                            <tr>
                                <td
                                    colspan="12"
                                    class="px-4 py-12 text-center text-sm text-slate-500"
                                >
                                    Tidak ada data utang yang sesuai dengan filter.
                                </td>
                            </tr>

                        @endforelse

                    </tbody>

                </table>
            </div>


            {{-- PAGINATION --}}
            @if ($invoices->hasPages())
                <div class="border-t border-slate-200 px-5 py-4">
                    {{ $invoices->links() }}
                </div>
            @endif

        </div>

    </div>
</div>
@endsection