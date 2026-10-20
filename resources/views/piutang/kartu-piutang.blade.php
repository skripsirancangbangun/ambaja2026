@extends('layouts.app')

@section('title', 'Buku Pembantu Piutang (Kartu Piutang)')

@section('content')

<div class="space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-xl font-semibold text-slate-800">Buku Pembantu Piutang (Kartu Piutang)</h2>
            <p class="mt-1 text-sm text-slate-500">
                Rincian histori mutasi debit (faktur) dan kredit (pembayaran/retur) per customer.
            </p>
        </div>

        <button
            type="button"
            onclick="window.print()"
            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50"
        >
            <x-icon name="printer" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" /> Cetak Kartu Piutang
        </button>
    </div>

    {{-- FILTER CUSTOMER --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('piutang.kartu') }}" class="grid grid-cols-1 gap-4 sm:grid-cols-4 items-end">
            <div class="sm:col-span-2">
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Pilih Customer</label>
                <select
                    name="customer_id"
                    class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm text-slate-700 outline-none focus:border-[#32AAA6]"
                >
                    <option value="">-- Pilih Customer --</option>
                    @foreach($customers as $c)
                        <option value="{{ $c->id }}" {{ $customerId == $c->id ? 'selected' : '' }}>
                            {{ $c->nama_customer }} ({{ $c->kode_customer }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold uppercase tracking-wider text-slate-500">Dari Tanggal</label>
                <input type="date" name="tanggal_mulai" value="{{ $tanggalMulai }}" class="mt-1.5 w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2 text-sm text-slate-700 outline-none">
            </div>

            <div>
                <button type="submit" class="w-full rounded-xl bg-[#15235F] py-2.5 text-sm font-medium text-white hover:bg-[#101b4d]">
                    Tampilkan Kartu Piutang
                </button>
            </div>
        </form>
    </div>

    @if($selectedCustomer)
        {{-- TABLE CARD --}}
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm p-6 space-y-4">
            <div class="border-b border-slate-200 pb-3 flex justify-between items-center">
                <div>
                    <h3 class="text-base font-bold text-[#15235F]">KARTU PIUTANG: {{ strtoupper($selectedCustomer->nama_customer) }}</h3>
                    <p class="text-xs text-slate-400">Kode: {{ $selectedCustomer->kode_customer }} | Telepon: {{ $selectedCustomer->telepon ?: '-' }}</p>
                </div>
                <div class="text-right">
                    <span class="text-xs text-slate-400">Saldo Awal Periode:</span>
                    <p class="text-base font-bold text-slate-800">Rp {{ number_format($saldoAwal, 0, ',', '.') }}</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 text-xs uppercase tracking-wider text-slate-400">
                        <tr>
                            <th class="px-4 py-3">Tanggal</th>
                            <th class="px-4 py-3">No. Referensi</th>
                            <th class="px-4 py-3">Jenis Transaksi</th>
                            <th class="px-4 py-3">Keterangan</th>
                            <th class="px-4 py-3 text-right">Debit (Faktur)</th>
                            <th class="px-4 py-3 text-right">Kredit (Bayar/Retur)</th>
                            <th class="px-4 py-3 text-right">Saldo Akhir (Rp)</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">
                        <tr class="bg-slate-50/50 font-semibold italic text-slate-600">
                            <td class="px-4 py-3">{{ date('d/m/Y', strtotime($tanggalMulai)) }}</td>
                            <td class="px-4 py-3">-</td>
                            <td class="px-4 py-3">SALDO AWAL</td>
                            <td class="px-4 py-3">Saldo piutang awal periode</td>
                            <td class="px-4 py-3 text-right">-</td>
                            <td class="px-4 py-3 text-right">-</td>
                            <td class="px-4 py-3 text-right text-slate-800">Rp {{ number_format($saldoAwal, 0, ',', '.') }}</td>
                        </tr>

                        @php $runningBalance = $saldoAwal; @endphp
                        @forelse($rows as $r)
                            @php $runningBalance += ($r->debit - $r->kredit); @endphp
                            <tr class="hover:bg-slate-50">
                                <td class="px-4 py-3 text-slate-600">{{ $r->tanggal ? $r->tanggal->format('d/m/Y') : '-' }}</td>
                                <td class="px-4 py-3 font-mono font-semibold text-[#15235F]">{{ $r->nomor_referensi }}</td>
                                <td class="px-4 py-3 text-xs font-semibold">
                                    <span class="inline-flex rounded px-2 py-0.5 {{ $r->jenis_transaksi == 'PENJUALAN' ? 'bg-blue-50 text-blue-700' : 'bg-emerald-50 text-emerald-700' }}">
                                        {{ $r->jenis_transaksi }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-slate-600">{{ $r->keterangan }}</td>
                                <td class="px-4 py-3 text-right font-mono text-slate-800">{{ $r->debit > 0 ? 'Rp '.number_format($r->debit, 0, ',', '.') : '-' }}</td>
                                <td class="px-4 py-3 text-right font-mono text-emerald-600">{{ $r->kredit > 0 ? 'Rp '.number_format($r->kredit, 0, ',', '.') : '-' }}</td>
                                <td class="px-4 py-3 text-right font-bold text-[#15235F]">Rp {{ number_format($runningBalance, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-6 text-center text-slate-400">
                                    Tidak ada mutasi piutang pada periode ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                    <tfoot>
                        <tr class="border-t-2 border-slate-200 bg-slate-50 font-bold">
                            <td colspan="4" class="px-4 py-3 text-right text-slate-700">SALDO PIUTANG AKHIR:</td>
                            <td class="px-4 py-3 text-right text-slate-900">Rp {{ number_format($rows->sum('debit'), 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right text-emerald-600">Rp {{ number_format($rows->sum('kredit'), 0, ',', '.') }}</td>
                            <td class="px-4 py-3 text-right text-rose-600">Rp {{ number_format($runningBalance, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    @endif

</div>

@endsection
