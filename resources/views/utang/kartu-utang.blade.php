@extends('layouts.app')

@section('title', 'Buku Pembantu Utang (Kartu Utang)')

@section('content')
<div class="space-y-6">
    <style>
        @media print {
            aside, header, .no-print { display: none !important; }
            body { background: #fff !important; }
            main { padding: 0 !important; margin: 0 !important; }
            .print-card { border: 1px solid #d1d5db !important; box-shadow: none !important; }
        }
    </style>

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-semibold text-slate-800">Buku Pembantu Utang (Kartu Utang)</h2>
            <p class="mt-1 text-sm text-slate-500">
                Rincian histori mutasi debit (pembayaran/retur) dan kredit (faktur) per supplier.
            </p>
        </div>

        <button
            type="button"
            onclick="window.print()"
            class="no-print rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
            <x-icon name="printer" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" /> Cetak Kartu Utang
        </button>
    </div>

    {{-- FILTER SUPPLIER --}}
    <div class="no-print rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('utang.kartu') }}" class="grid grid-cols-1 items-end gap-4 sm:grid-cols-5">
            <div class="sm:col-span-2">
                <label for="supplier_id" class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Pilih Supplier</label>
                <select
                    id="supplier_id"
                    name="supplier_id"
                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm text-slate-700 outline-none focus:border-[#32AAA6]"
                >
                    <option value="">-- Pilih Supplier --</option>
                    @foreach($suppliers as $supplier)
                        <option value="{{ $supplier->id }}" {{ (string) $supplierId === (string) $supplier->id ? 'selected' : '' }}>
                            {{ $supplier->nama_supplier }} ({{ $supplier->kode_supplier }}){{ !$supplier->is_active ? ' — Nonaktif' : '' }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="tanggal_mulai" class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Dari Tanggal</label>
                <input
                    id="tanggal_mulai"
                    type="date"
                    name="tanggal_mulai"
                    value="{{ $tanggalMulai }}"
                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm text-slate-700 outline-none focus:border-[#32AAA6]"
                >
            </div>

            <div>
                <label for="tanggal_selesai" class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Sampai Tanggal</label>
                <input
                    id="tanggal_selesai"
                    type="date"
                    name="tanggal_selesai"
                    value="{{ $tanggalSelesai }}"
                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm text-slate-700 outline-none focus:border-[#32AAA6]"
                >
            </div>

            <div>
                <button type="submit" class="w-full rounded-xl bg-[#15235F] py-2.5 text-sm font-medium text-white hover:bg-[#101b4d]">
                    Tampilkan Kartu Utang
                </button>
            </div>
        </form>
        @error('tanggal_selesai')
            <p class="mt-3 text-sm font-medium text-rose-700">{{ $message }}</p>
        @enderror
    </div>

    @if($selectedSupplier)
        {{-- TABLE CARD --}}
        <div class="print-card space-y-4 overflow-hidden rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="flex items-center justify-between border-b border-slate-200 pb-3">
                <div>
                    <h3 class="text-base font-bold text-[#15235F]">KARTU UTANG: {{ strtoupper($selectedSupplier->nama_supplier) }}</h3>
                    <p class="text-xs text-slate-400">
                        Kode: {{ $selectedSupplier->kode_supplier }} | Telepon: {{ $selectedSupplier->telepon ?: '-' }}
                    </p>
                    <p class="mt-1 text-xs text-slate-400">
                        Periode: {{ \Carbon\Carbon::parse($tanggalMulai)->format('d/m/Y') }} – {{ \Carbon\Carbon::parse($tanggalSelesai)->format('d/m/Y') }}
                    </p>
                </div>
                <div class="text-right">
                    <span class="text-xs text-slate-400">Saldo Awal Periode:</span>
                    <p class="text-base font-bold text-slate-800">Rp {{ number_format($saldoAwal, 0, ',', '.') }}</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[900px] text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-400">
                        <tr>
                            <th class="whitespace-nowrap px-4 py-3">Tanggal</th>
                            <th class="whitespace-nowrap px-4 py-3">No. Referensi</th>
                            <th class="whitespace-nowrap px-4 py-3">Jenis Transaksi</th>
                            <th class="px-4 py-3">Keterangan</th>
                            <th class="whitespace-nowrap px-4 py-3 text-right">Debit (Bayar/Retur)</th>
                            <th class="whitespace-nowrap px-4 py-3 text-right">Kredit (Faktur)</th>
                            <th class="whitespace-nowrap px-4 py-3 text-right">Saldo Akhir (Rp)</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        <tr class="bg-slate-50/50 font-semibold italic text-slate-600">
                            <td class="whitespace-nowrap px-4 py-3">{{ \Carbon\Carbon::parse($tanggalMulai)->format('d/m/Y') }}</td>
                            <td class="px-4 py-3">-</td>
                            <td class="px-4 py-3">SALDO AWAL</td>
                            <td class="px-4 py-3">Saldo utang awal periode</td>
                            <td class="px-4 py-3 text-right">-</td>
                            <td class="px-4 py-3 text-right">-</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-slate-800">Rp {{ number_format($saldoAwal, 0, ',', '.') }}</td>
                        </tr>

                        @forelse($rows as $row)
                            @php
                                $typeClass = match ($row->jenis_transaksi) {
                                    'INVOICE SUPPLIER' => 'bg-blue-50 text-blue-700',
                                    'PEMBAYARAN SUPPLIER' => 'bg-emerald-50 text-emerald-700',
                                    'ALOKASI UANG MUKA' => 'bg-sky-50 text-sky-700',
                                    'RETUR PEMBELIAN' => 'bg-amber-50 text-amber-800',
                                    default => 'bg-slate-100 text-slate-700',
                                };
                            @endphp
                            <tr class="transition hover:bg-slate-50">
                                <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ \Carbon\Carbon::parse($row->tanggal)->format('d/m/Y') }}</td>
                                <td class="whitespace-nowrap px-4 py-3 font-mono text-xs font-semibold text-[#15235F]">{{ $row->nomor_referensi }}</td>
                                <td class="px-4 py-3 text-xs font-semibold">
                                    <span class="inline-flex whitespace-nowrap rounded px-2 py-0.5 {{ $typeClass }}">{{ $row->jenis_transaksi }}</span>
                                </td>
                                <td class="min-w-[250px] px-4 py-3 text-xs text-slate-600">{{ $row->keterangan }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-mono text-emerald-600">{{ $row->debit > 0 ? 'Rp '.number_format($row->debit, 0, ',', '.') : '-' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-mono text-slate-800">{{ $row->kredit > 0 ? 'Rp '.number_format($row->kredit, 0, ',', '.') : '-' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-bold text-[#15235F]">Rp {{ number_format($row->saldo, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-6 text-center text-slate-400">
                                    Tidak ada mutasi utang pada periode ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    <tfoot>
                        <tr class="border-t-2 border-slate-200 bg-slate-50 font-bold">
                            <td colspan="4" class="px-4 py-3 text-right text-slate-700">TOTAL MUTASI PERIODE:</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-emerald-600">Rp {{ number_format($totalPengurangPeriode, 0, ',', '.') }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-slate-900">Rp {{ number_format($totalInvoicePeriode, 0, ',', '.') }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-right text-[#15235F]">Rp {{ number_format($saldoAkhirPeriode, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>

            @if($kreditSupplierSaatIni > 0)
                <p class="text-xs text-slate-500">
                    Kredit supplier saat ini akibat retur lebih bayar: <span class="font-semibold text-slate-700">Rp {{ number_format($kreditSupplierSaatIni, 0, ',', '.') }}</span>.
                    Nilai ini dipisahkan dari saldo utang agar kewajiban tidak terhitung negatif.
                </p>
            @endif
        </div>
    @endif
</div>
@endsection
