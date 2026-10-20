@extends('layouts.app')

@section('title', 'Laporan Piutang Dagang')

@section('content')

<div class="space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-semibold text-slate-800">Laporan Piutang Dagang (Customer)</h2>
            <p class="mt-1 text-sm text-slate-500">
                Monitoring tagihan piutang dan analisis umur piutang (Aging Schedule).
            </p>
        </div>

        <button
            type="button"
            onclick="window.print()"
            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
        >
            <x-icon name="printer" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" /> Cetak Laporan
        </button>
    </div>

    {{-- AGING METRICS CARDS --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Total Piutang Belum Lunas</p>
            <p class="mt-2 text-xl font-bold text-[#15235F]">
                Rp {{ number_format($totalPiutang, 0, ',', '.') }}
            </p>
        </div>

        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-emerald-600">Lancar (0 - 30 Hari)</p>
            <p class="mt-2 text-xl font-bold text-emerald-700">
                Rp {{ number_format($aging['lancar'], 0, ',', '.') }}
            </p>
        </div>

        <div class="rounded-2xl border border-amber-100 bg-amber-50 p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-amber-600">Jatuh Tempo (31 - 60 Hari)</p>
            <p class="mt-2 text-xl font-bold text-amber-700">
                Rp {{ number_format($aging['tempo_30'], 0, ',', '.') }}
            </p>
        </div>

        <div class="rounded-2xl border border-orange-100 bg-orange-50 p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-orange-600">Jatuh Tempo (61 - 90 Hari)</p>
            <p class="mt-2 text-xl font-bold text-orange-700">
                Rp {{ number_format($aging['tempo_60'], 0, ',', '.') }}
            </p>
        </div>

        <div class="rounded-2xl border border-rose-100 bg-rose-50 p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-rose-500">Jatuh Tempo (> 90 Hari)</p>
            <p class="mt-2 text-xl font-bold text-rose-600">
                Rp {{ number_format($aging['tempo_90plus'], 0, ',', '.') }}
            </p>
        </div>
    </div>

    {{-- FILTER --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('laporan.piutang') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-3">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Per Tanggal</label>
                <input
                    type="date"
                    name="per_tanggal"
                    value="{{ $perTanggal }}"
                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm text-slate-700 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                >
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Filter Customer</label>
                <select
                    name="customer_id"
                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm text-slate-700 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                >
                    <option value="">Semua Customer</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}" {{ $customerId == $c->id ? 'selected' : '' }}>
                            {{ $c->nama_customer }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="flex items-end">
                <button
                    type="submit"
                    class="w-full rounded-xl bg-[#15235F] py-2 text-sm font-medium text-white transition hover:bg-[#101b4d]"
                >
                    Tampilkan Piutang
                </button>
            </div>
        </form>
    </div>

    {{-- TABLE --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-4 flex justify-between items-center">
            <div>
                <h3 class="text-sm font-semibold text-slate-700">Rincian Tagihan Piutang Customer</h3>
                <p class="text-xs text-slate-400">Posisi Per Tanggal: {{ date('d/m/Y', strtotime($perTanggal)) }}</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-xs uppercase tracking-wide text-slate-400">
                        <th class="px-6 py-3 font-medium">No. Invoice</th>
                        <th class="px-6 py-3 font-medium">Customer</th>
                        <th class="px-6 py-3 font-medium">Tanggal Faktur</th>
                        <th class="px-6 py-3 font-medium">Jatuh Tempo</th>
                        <th class="px-6 py-3 text-right font-medium">Total Faktur</th>
                        <th class="px-6 py-3 text-right font-medium">Sudah Dibayar</th>
                        <th class="px-6 py-3 text-right font-medium">Sisa Piutang</th>
                        <th class="px-6 py-3 font-medium">Umur Piutang</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($invoices as $inv)
                        @php
                            $today = \Carbon\Carbon::parse($perTanggal);
                            $dueDate = $inv->tanggal_jatuh_tempo ? \Carbon\Carbon::parse($inv->tanggal_jatuh_tempo) : \Carbon\Carbon::parse($inv->tanggal_invoice);
                            $daysOverdue = $dueDate->isPast() ? (int) $dueDate->diffInDays($today) : 0;
                        @endphp
                        <tr class="transition hover:bg-slate-50">
                            <td class="px-6 py-3.5 font-semibold text-[#15235F]">
                                <a href="{{ route('invoice-penjualan.show', $inv) }}" class="hover:underline">
                                    {{ $inv->nomor_invoice }}
                                </a>
                            </td>
                            <td class="px-6 py-3.5 font-medium text-slate-800">
                                {{ $inv->customer->nama_customer ?? '-' }}
                            </td>
                            <td class="px-6 py-3.5 text-slate-600">
                                {{ $inv->tanggal_invoice ? $inv->tanggal_invoice->format('d/m/Y') : '-' }}
                            </td>
                            <td class="px-6 py-3.5 {{ $daysOverdue > 0 ? 'font-semibold text-rose-600' : 'text-slate-600' }}">
                                {{ $inv->tanggal_jatuh_tempo ? $inv->tanggal_jatuh_tempo->format('d/m/Y') : '-' }}
                            </td>
                            <td class="px-6 py-3.5 text-right font-medium text-slate-700">
                                Rp {{ number_format($inv->total, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-3.5 text-right font-medium text-emerald-600">
                                Rp {{ number_format($inv->sudah_dibayar, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-3.5 text-right font-bold text-rose-600">
                                Rp {{ number_format($inv->sisa_piutang, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-3.5">
                                @if($daysOverdue <= 0)
                                    <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Lancar</span>
                                @elseif($daysOverdue <= 30)
                                    <span class="inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-semibold text-amber-700">{{ $daysOverdue }} hari</span>
                                @elseif($daysOverdue <= 90)
                                    <span class="inline-flex rounded-full bg-orange-50 px-2.5 py-1 text-xs font-semibold text-orange-700">{{ $daysOverdue }} hari</span>
                                @else
                                    <span class="inline-flex rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">&gt; 90 hari</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-8 text-center text-slate-400">
                                Tidak ada saldo piutang dagang per tanggal ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                <tfoot>
                    <tr class="border-t-2 border-slate-200 bg-slate-50 font-bold">
                        <td colspan="6" class="px-6 py-4 text-right text-slate-700">TOTAL PIUTANG DAGANG:</td>
                        <td class="px-6 py-4 text-right text-rose-600">
                            Rp {{ number_format($totalPiutang, 0, ',', '.') }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>

@endsection
