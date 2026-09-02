@extends('layouts.app')

@section('title', 'Laporan Penjualan')

@section('content')

<div class="space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-semibold text-slate-800">Laporan Penjualan</h2>
            <p class="mt-1 text-sm text-slate-500">
                Rekapitulasi omzet dan rincian transaksi faktur penjualan barang.
            </p>
        </div>

        <button
            type="button"
            onclick="window.print()"
            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
        >
            🖨️ Cetak Laporan
        </button>
    </div>

    {{-- METRIC CARDS --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Total Omzet Penjualan</p>
            <p class="mt-2 text-2xl font-bold text-[#15235F]">
                Rp {{ number_format($totalOmzet, 0, ',', '.') }}
            </p>
            <p class="mt-1 text-xs text-slate-400">Dari {{ $invoices->count() }} faktur penjualan</p>
        </div>

        <div class="rounded-2xl border border-emerald-100 bg-emerald-50 p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-emerald-600">Total Kas Masuk (Lunas)</p>
            <p class="mt-2 text-2xl font-bold text-emerald-700">
                Rp {{ number_format($totalSudahDibayar, 0, ',', '.') }}
            </p>
            <p class="mt-1 text-xs text-emerald-600">{{ $countTunai }} Transaksi Tunai</p>
        </div>

        <div class="rounded-2xl border border-rose-100 bg-rose-50 p-5">
            <p class="text-xs font-medium uppercase tracking-wide text-rose-500">Sisa Piutang Penjualan</p>
            <p class="mt-2 text-2xl font-bold text-rose-600">
                Rp {{ number_format($totalSisaPiutang, 0, ',', '.') }}
            </p>
            <p class="mt-1 text-xs text-rose-500">{{ $countKredit }} Transaksi Tempo</p>
        </div>

        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <p class="text-xs font-medium uppercase tracking-wide text-slate-400">Diskon & PPN</p>
            <p class="mt-2 text-sm font-semibold text-slate-700">
                Diskon: Rp {{ number_format($totalDiskon, 0, ',', '.') }}
            </p>
            <p class="text-sm font-semibold text-slate-700">
                PPN: Rp {{ number_format($totalPajak, 0, ',', '.') }}
            </p>
        </div>
    </div>

    {{-- FILTER --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('laporan.penjualan') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Dari Tanggal</label>
                <input
                    type="date"
                    name="tanggal_mulai"
                    value="{{ $tanggalMulai }}"
                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm text-slate-700 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                >
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Sampai Tanggal</label>
                <input
                    type="date"
                    name="tanggal_selesai"
                    value="{{ $tanggalSelesai }}"
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

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Tipe Pembayaran</label>
                <select
                    name="tipe_pembayaran"
                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm text-slate-700 focus:border-[#32AAA6] focus:bg-white focus:outline-none"
                >
                    <option value="">Semua Tipe</option>
                    <option value="Tunai" {{ $tipePembayaran == 'Tunai' ? 'selected' : '' }}>Tunai (Cash)</option>
                    <option value="Kredit" {{ $tipePembayaran == 'Kredit' ? 'selected' : '' }}>Kredit (Tempo)</option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button
                    type="submit"
                    class="w-full rounded-xl bg-[#15235F] py-2 text-sm font-medium text-white transition hover:bg-[#101b4d]"
                >
                    Tampilkan
                </button>
            </div>
        </form>
    </div>

    {{-- TABLE --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-4 flex justify-between items-center">
            <div>
                <h3 class="text-sm font-semibold text-slate-700">Rincian Faktur Penjualan</h3>
                <p class="text-xs text-slate-400">Periode: {{ date('d/m/Y', strtotime($tanggalMulai)) }} s/d {{ date('d/m/Y', strtotime($tanggalSelesai)) }}</p>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50">
                    <tr class="text-xs uppercase tracking-wide text-slate-400">
                        <th class="px-6 py-3 font-medium">No. Invoice</th>
                        <th class="px-6 py-3 font-medium">Tanggal</th>
                        <th class="px-6 py-3 font-medium">Customer</th>
                        <th class="px-6 py-3 font-medium">Gudang</th>
                        <th class="px-6 py-3 font-medium">Tipe</th>
                        <th class="px-6 py-3 text-right font-medium">Total (Rp)</th>
                        <th class="px-6 py-3 text-right font-medium">Dibayar (Rp)</th>
                        <th class="px-6 py-3 text-right font-medium">Sisa Piutang</th>
                        <th class="px-6 py-3 font-medium">Status</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($invoices as $inv)
                        <tr class="transition hover:bg-slate-50">
                            <td class="px-6 py-3.5 font-semibold text-[#15235F]">
                                <a href="{{ route('invoice-penjualan.show', $inv) }}" class="hover:underline">
                                    {{ $inv->nomor_invoice }}
                                </a>
                            </td>
                            <td class="px-6 py-3.5 text-slate-600">
                                {{ $inv->tanggal_invoice ? $inv->tanggal_invoice->format('d/m/Y') : '-' }}
                            </td>
                            <td class="px-6 py-3.5 font-medium text-slate-800">
                                {{ $inv->customer->nama_customer ?? '-' }}
                            </td>
                            <td class="px-6 py-3.5 text-slate-600">
                                {{ $inv->gudang->nama_gudang ?? '-' }}
                            </td>
                            <td class="px-6 py-3.5">
                                <span class="inline-flex rounded px-2 py-0.5 text-xs font-medium {{ $inv->tipe_pembayaran == 'Tunai' ? 'bg-teal-50 text-[#32AAA6]' : 'bg-slate-100 text-slate-700' }}">
                                    {{ $inv->tipe_pembayaran }}
                                </span>
                            </td>
                            <td class="px-6 py-3.5 text-right font-semibold text-slate-800">
                                Rp {{ number_format($inv->total, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-3.5 text-right font-medium text-emerald-600">
                                Rp {{ number_format($inv->sudah_dibayar, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-3.5 text-right font-semibold {{ $inv->sisa_piutang > 0 ? 'text-rose-600' : 'text-slate-400' }}">
                                Rp {{ number_format($inv->sisa_piutang, 0, ',', '.') }}
                            </td>
                            <td class="px-6 py-3.5">
                                @if($inv->status_pembayaran == 'Lunas')
                                    <span class="inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-semibold text-emerald-700">Lunas</span>
                                @else
                                    <span class="inline-flex rounded-full bg-rose-50 px-2.5 py-1 text-xs font-semibold text-rose-700">Belum Lunas</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-6 py-8 text-center text-slate-400">
                                Tidak ada transaksi penjualan pada periode dan filter ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>

                <tfoot>
                    <tr class="border-t-2 border-slate-200 bg-slate-50 font-bold">
                        <td colspan="5" class="px-6 py-4 text-right text-slate-700">TOTAL PENJUALAN:</td>
                        <td class="px-6 py-4 text-right text-[#15235F]">
                            Rp {{ number_format($totalOmzet, 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 text-right text-emerald-600">
                            Rp {{ number_format($totalSudahDibayar, 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 text-right text-rose-600">
                            Rp {{ number_format($totalSisaPiutang, 0, ',', '.') }}
                        </td>
                        <td></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

</div>

@endsection
