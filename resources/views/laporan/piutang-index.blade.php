@extends('layouts.app')

@section('title', 'Laporan Aging Piutang Dagang')

@section('content')

<div class="space-y-6">

    {{-- HEADER --}}
    <div>
        <h2 class="text-xl font-semibold text-slate-800">
            Laporan Aging Piutang Dagang (Umur Piutang)
        </h2>
        <p class="mt-1 text-sm text-slate-500">
            Monitoring saldo tagihan piutang customer berdasarkan kategori umur jatuh tempo.
        </p>
    </div>

    {{-- AGING METRICS --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-semibold uppercase tracking-wide text-slate-400">Total Piutang Belum Lunas</p>
            <p class="mt-2 text-xl font-bold text-[#15235F]">
                Rp {{ number_format($totalPiutang, 0, ',', '.') }}
            </p>
        </div>

        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-emerald-600">Lancar (0 - 30 Hari)</p>
            <p class="mt-2 text-xl font-bold text-emerald-700">
                Rp {{ number_format($aging['lancar'], 0, ',', '.') }}
            </p>
        </div>

        <div class="rounded-2xl border border-amber-100 bg-amber-50 p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-amber-600">31 - 60 Hari</p>
            <p class="mt-2 text-xl font-bold text-amber-700">
                Rp {{ number_format($aging['tempo_30'], 0, ',', '.') }}
            </p>
        </div>

        <div class="rounded-2xl border border-orange-100 bg-orange-50 p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-orange-600">61 - 90 Hari</p>
            <p class="mt-2 text-xl font-bold text-orange-700">
                Rp {{ number_format($aging['tempo_60'], 0, ',', '.') }}
            </p>
        </div>

        <div class="rounded-2xl border border-rose-100 bg-rose-50 p-5">
            <p class="text-xs font-semibold uppercase tracking-wide text-rose-500">&gt; 90 Hari (Jatuh Tempo)</p>
            <p class="mt-2 text-xl font-bold text-rose-600">
                Rp {{ number_format($aging['tempo_90plus'], 0, ',', '.') }}
            </p>
        </div>
    </div>

    {{-- FILTER --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('laporan.piutang') }}" class="space-y-4">
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                <div>
                    <label class="mb-2 block text-xs font-medium text-slate-600">Posisi Per Tanggal</label>
                    <input
                        type="date"
                        name="per_tanggal"
                        value="{{ $perTanggal }}"
                        class="w-full rounded-lg border border-slate-300 px-3 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >
                </div>

                <div>
                    <label class="mb-2 block text-xs font-medium text-slate-600">Customer</label>
                    <select
                        name="customer_id"
                        class="w-full rounded-lg border border-slate-300 bg-white px-3 py-2.5 text-sm outline-none focus:border-[#32AAA6] focus:ring-2 focus:ring-teal-100"
                    >
                        <option value="">Semua Customer</option>
                        @foreach($customers as $c)
                            <option value="{{ $c->id }}" {{ $customerId == $c->id ? 'selected' : '' }}>
                                {{ $c->nama_customer }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div class="flex justify-end gap-2">
                <a
                    href="{{ route('laporan.piutang') }}"
                    class="rounded-lg border border-slate-300 bg-white px-4 py-2.5 text-sm font-medium text-slate-600 transition hover:bg-slate-50"
                >
                    Reset
                </a>
                <button
                    type="submit"
                    class="rounded-lg bg-[#15235F] px-5 py-2.5 text-sm font-medium text-white transition hover:bg-[#101b4d]"
                >
                    Terapkan Filter
                </button>
            </div>
        </form>
    </div>

    {{-- TABLE --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-4">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-slate-700">Rincian Aging Piutang per Invoice</h3>
                    <p class="mt-1 text-xs text-slate-400">Posisi Per Tanggal {{ date('d/m/Y', strtotime($perTanggal)) }}</p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <a
                        href="{{ route('laporan.piutang.pdf', request()->query()) }}"
                        target="_blank"
                        class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-red-50 px-4 py-2.5 text-sm font-medium text-red-600 transition hover:border-red-300 hover:bg-red-100"
                    >
                        <x-icon name="file-text" class="h-4 w-4" />
                        Cetak PDF
                    </a>

                    <a
                        href="{{ route('laporan.piutang.excel', request()->query()) }}"
                        class="inline-flex items-center gap-2 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-medium text-emerald-600 transition hover:border-emerald-300 hover:bg-emerald-100"
                    >
                        <x-icon name="file-text" class="h-4 w-4" />
                        Export Excel
                    </a>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 text-xs font-semibold uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-4 py-3">No. Invoice</th>
                        <th class="px-4 py-3">Customer</th>
                        <th class="px-4 py-3">Tanggal Faktur</th>
                        <th class="px-4 py-3">Jatuh Tempo</th>
                        <th class="px-4 py-3 text-right">Lancar (0-30 Hari)</th>
                        <th class="px-4 py-3 text-right">31 - 60 Hari</th>
                        <th class="px-4 py-3 text-right">61 - 90 Hari</th>
                        <th class="px-4 py-3 text-right">&gt; 90 Hari</th>
                        <th class="px-4 py-3 text-right">Total Sisa Piutang</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100 text-slate-700">
                    @forelse($invoices as $inv)
                        @php
                            $cutoffDate = \Carbon\Carbon::parse($perTanggal)->startOfDay();
                            $dueDate = \Carbon\Carbon::parse(
                                $inv->tanggal_jatuh_tempo ?: $inv->tanggal_invoice
                            )->startOfDay();
                            $daysOverdue = $dueDate->greaterThan($cutoffDate)
                                ? 0
                                : (int) $dueDate->diffInDays($cutoffDate);

                            $lancar = $daysOverdue <= 30 ? $inv->sisa_piutang : 0;
                            $t30 = ($daysOverdue > 30 && $daysOverdue <= 60) ? $inv->sisa_piutang : 0;
                            $t60 = ($daysOverdue > 60 && $daysOverdue <= 90) ? $inv->sisa_piutang : 0;
                            $t90 = $daysOverdue > 90 ? $inv->sisa_piutang : 0;
                        @endphp
                        <tr class="hover:bg-slate-50">
                            <td class="whitespace-nowrap px-4 py-3 font-semibold text-[#15235F]">
                                <a href="{{ route('invoice-penjualan.show', $inv) }}" class="hover:underline">
                                    {{ $inv->nomor_invoice }}
                                </a>
                            </td>
                            <td class="px-4 py-3 font-medium text-slate-800">
                                {{ $inv->customer?->nama_customer ?? '-' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-xs">
                                {{ $inv->tanggal_invoice ? $inv->tanggal_invoice->format('d/m/Y') : '-' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-xs {{ $daysOverdue > 0 ? 'font-semibold text-red-500' : 'text-slate-600' }}">
                                {{ $inv->tanggal_jatuh_tempo ? $inv->tanggal_jatuh_tempo->format('d/m/Y') : '-' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-mono text-xs text-emerald-600 font-medium">
                                {{ $lancar > 0 ? 'Rp '.number_format($lancar, 0, ',', '.') : '-' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-mono text-xs text-amber-600 font-medium">
                                {{ $t30 > 0 ? 'Rp '.number_format($t30, 0, ',', '.') : '-' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-mono text-xs text-orange-600 font-medium">
                                {{ $t60 > 0 ? 'Rp '.number_format($t60, 0, ',', '.') : '-' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-mono text-xs text-rose-600 font-bold">
                                {{ $t90 > 0 ? 'Rp '.number_format($t90, 0, ',', '.') : '-' }}
                            </td>
                            <td class="whitespace-nowrap px-4 py-3 text-right font-mono text-xs font-bold text-slate-900">
                                Rp {{ number_format($inv->sisa_piutang, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-8 text-center text-slate-400">
                                Tidak ada saldo piutang per tanggal ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                <tfoot>
                    <tr class="border-t-2 border-slate-200 bg-slate-50 font-bold">
                        <td colspan="4" class="px-4 py-3 text-right text-slate-700">TOTAL:</td>
                        <td class="px-4 py-3 text-right text-emerald-700">Rp {{ number_format($aging['lancar'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-amber-700">Rp {{ number_format($aging['tempo_30'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-orange-700">Rp {{ number_format($aging['tempo_60'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-rose-700">Rp {{ number_format($aging['tempo_90plus'], 0, ',', '.') }}</td>
                        <td class="px-4 py-3 text-right text-[#15235F]">Rp {{ number_format($totalPiutang, 0, ',', '.') }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>

@endsection
