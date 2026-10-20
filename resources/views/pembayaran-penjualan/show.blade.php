@extends('layouts.app')

@section('title', 'Bukti Pembayaran ' . $pembayaranPenjualan->nomor_pembayaran)

@section('content')

<div class="mx-auto max-w-4xl space-y-6">

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <a href="{{ route('pembayaran-penjualan.index') }}" class="mb-2 inline-flex items-center gap-2 text-sm text-slate-500 hover:text-[#15235F]">
                <x-icon name="arrow-left" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" /> Kembali ke Daftar Pembayaran
            </a>
            <div class="flex items-center gap-3">
                <h2 class="text-xl font-semibold text-slate-800">
                    {{ $pembayaranPenjualan->nomor_pembayaran }}
                </h2>
                <span class="inline-flex rounded-full bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">
                    Diterima (Kas Masuk)
                </span>
            </div>
        </div>

        <button
            type="button"
            onclick="window.print()"
            class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
        >
            <x-icon name="printer" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" /> Cetak Kuitansi
        </button>
    </div>

    @if(session('success'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('success') }}
        </div>
    @endif

    {{-- CARD DETAIL --}}
    <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
            <div class="space-y-3">
                <div>
                    <p class="text-xs font-medium uppercase text-slate-400">Customer</p>
                    <p class="mt-1 text-base font-semibold text-slate-800">{{ $pembayaranPenjualan->customer->nama_customer ?? '-' }}</p>
                    <p class="text-xs text-slate-500">{{ $pembayaranPenjualan->customer->alamat ?? '' }}</p>
                </div>
                <div>
                    <p class="text-xs font-medium uppercase text-slate-400">Masuk ke Rekening</p>
                    <p class="mt-1 text-sm font-semibold text-[#15235F]">{{ $pembayaranPenjualan->rekeningKasBank->nama_rekening ?? '-' }}</p>
                </div>
            </div>

            <div class="space-y-3">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <p class="text-xs font-medium uppercase text-slate-400">Tanggal Penerimaan</p>
                        <p class="mt-1 text-sm font-semibold text-slate-700">{{ $pembayaranPenjualan->tanggal_pembayaran ? $pembayaranPenjualan->tanggal_pembayaran->format('d F Y') : '-' }}</p>
                    </div>
                    <div>
                        <p class="text-xs font-medium uppercase text-slate-400">Metode Pembayaran</p>
                        <p class="mt-1 text-sm font-semibold text-slate-700">{{ $pembayaranPenjualan->metode_pembayaran }}</p>
                    </div>
                </div>
                @if($pembayaranPenjualan->referensi_transaksi)
                    <div>
                        <p class="text-xs font-medium uppercase text-slate-400">No. Referensi Bank</p>
                        <p class="mt-1 text-sm text-slate-700 font-mono">{{ $pembayaranPenjualan->referensi_transaksi }}</p>
                    </div>
                @endif
                <div>
                    <p class="text-xs font-medium uppercase text-slate-400">Diterima Oleh</p>
                    <p class="mt-1 text-sm text-slate-600">{{ $pembayaranPenjualan->creator->name ?? '-' }} ({{ $pembayaranPenjualan->created_at->format('d/m/Y H:i') }})</p>
                </div>
            </div>
        </div>
    </div>

    {{-- ALOKASI INVOICE --}}
    <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
        <div class="border-b border-slate-200 px-6 py-4">
            <h3 class="text-sm font-semibold text-slate-700">Alokasi Pelunasan Faktur</h3>
        </div>

        <table class="w-full text-left text-sm">
            <thead class="bg-slate-50">
                <tr class="text-xs uppercase tracking-wide text-slate-400">
                    <th class="px-6 py-3 font-medium">No</th>
                    <th class="px-6 py-3 font-medium">No. Invoice</th>
                    <th class="px-6 py-3 font-medium">Tanggal Invoice</th>
                    <th class="px-6 py-3 text-right font-medium">Total Invoice</th>
                    <th class="px-6 py-3 text-right font-medium">Nominal Dibayar</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @foreach($pembayaranPenjualan->details as $index => $detail)
                    <tr>
                        <td class="px-6 py-4 text-slate-500">{{ $index + 1 }}</td>
                        <td class="px-6 py-4 font-semibold text-[#15235F]">
                            <a href="{{ route('invoice-penjualan.show', $detail->invoicePenjualan) }}" class="hover:underline">
                                {{ $detail->invoicePenjualan->nomor_invoice ?? '-' }}
                            </a>
                        </td>
                        <td class="px-6 py-4 text-slate-600">
                            {{ $detail->invoicePenjualan->tanggal_invoice ? $detail->invoicePenjualan->tanggal_invoice->format('d/m/Y') : '-' }}
                        </td>
                        <td class="px-6 py-4 text-right text-slate-700">
                            Rp {{ number_format($detail->invoicePenjualan->total ?? 0, 0, ',', '.') }}
                        </td>
                        <td class="px-6 py-4 text-right font-bold text-emerald-600">
                            Rp {{ number_format($detail->nominal_bayar, 0, ',', '.') }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr class="border-t border-slate-200 bg-slate-50 font-bold">
                    <td colspan="4" class="px-6 py-4 text-right text-slate-700">Total Diterima:</td>
                    <td class="px-6 py-4 text-right text-base text-emerald-600">
                        Rp {{ number_format($pembayaranPenjualan->total_bayar, 0, ',', '.') }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

</div>

@endsection
