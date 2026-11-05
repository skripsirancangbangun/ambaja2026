@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
@php
    $formatRupiah = fn ($amount) => 'Rp ' . number_format((float) $amount, 0, ',', '.');
    $formatQty = fn ($qty) => number_format((float) $qty, 2, ',', '.');
    $user = auth()->user();
    $lowStockOnlyCount = max((int) $lowStockCount - (int) $outOfStockCount, 0);
@endphp

<div class="space-y-6">
    <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="text-sm font-medium text-teal-600">AMBAJA 2026</p>
            <h2 class="mt-1 text-2xl font-semibold text-slate-800">Selamat datang, {{ $user->name }}</h2>
            <p class="mt-1 text-sm text-slate-500">
                @if($user->role === 'Pembelian')
        <section class="rounded-2xl border border-amber-200 bg-amber-50 p-5 shadow-sm">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Tindak lanjut Gudang</p>
                    <h3 class="mt-1 text-lg font-semibold text-amber-950">{{ number_format($pendingPurchaseRequestsCount, 0, ',', '.') }} permintaan menunggu pemeriksaan</h3>
                    <p class="mt-1 text-sm text-amber-800">Periksa jumlah yang diminta dan setujui sebelum membuat Purchase Order.</p>
                </div>
                <a href="{{ route('permintaan-pembelian.index') }}" class="inline-flex w-fit items-center rounded-lg bg-[#15235F] px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-[#0F8B8D]">Periksa Permintaan</a>
            </div>
        </section>
    @endif

    @if($user->role === 'Owner')
                    Ringkasan keuangan dan operasional perusahaan.
                @else
                    Ringkasan operasional dan akuntansi sesuai hak akses akun Anda.
                @endif
            </p>
        </div>
        <span class="inline-flex w-fit items-center rounded-full bg-teal-50 px-3 py-1 text-sm font-semibold text-[#0F8B8D]">
            {{ $user->role }}
        </span>
    </div>

    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-6">
        @if($user->canAccessModule('sales') || $user->canAccessModule('reports.sales'))
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Penjualan Bulan Ini</p>
                <p class="mt-2 text-2xl font-bold text-[#15235F]">{{ $formatRupiah($salesThisMonth) }}</p>
                <p class="mt-2 text-xs text-slate-500">Total invoice aktif bulan berjalan</p>
            </div>
        @endif

        @if($user->canAccessModule('purchases') || $user->canAccessModule('reports.purchases'))
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Pembelian Bulan Ini</p>
                <p class="mt-2 text-2xl font-bold text-[#15235F]">{{ $formatRupiah($purchasesThisMonth) }}</p>
                <p class="mt-2 text-xs text-slate-500">Total invoice supplier bulan berjalan</p>
            </div>
        @endif

        @if($user->canAccessModule('receivables') || $user->canAccessModule('reports.receivables'))
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Piutang Belum Lunas</p>
                <p class="mt-2 text-2xl font-bold text-[#15235F]">{{ $formatRupiah($receivablesTotal) }}</p>
                <p class="mt-2 text-xs text-slate-500">{{ number_format($receivablesCount, 0, ',', '.') }} invoice terbuka</p>
            </div>
        @endif

        @if($user->canAccessModule('payables') || $user->canAccessModule('reports.payables'))
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Utang Belum Lunas</p>
                <p class="mt-2 text-2xl font-bold text-[#15235F]">{{ $formatRupiah($payablesTotal) }}</p>
                <p class="mt-2 text-xs text-slate-500">{{ number_format($payablesCount, 0, ',', '.') }} invoice terbuka</p>
            </div>
        @endif

        @if($user->canAccessModule('cashbank'))
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Kas & Bank</p>
                        <p class="mt-2 text-2xl font-bold text-[#15235F]">{{ $formatRupiah($cashBankTotal) }}</p>
                        <p class="mt-2 text-xs text-slate-600">{{ number_format($cashBankAccountCount, 0, ',', '.') }} rekening aktif</p>
                    </div>
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-50 text-[#0F8B8D]">
                        <x-icon name="landmark" class="h-5 w-5" />
                    </div>
                </div>
            </div>
        @endif

        @if($user->canAccessModule('inventory') || $user->canAccessModule('reports.inventory'))
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Nilai Persediaan</p>
                <p class="mt-2 text-2xl font-bold text-[#15235F]">{{ $formatRupiah($inventoryValue) }}</p>
                <p class="mt-2 text-xs text-slate-500">{{ number_format($activeItemsCount, 0, ',', '.') }} barang aktif</p>
            </div>
        @endif

        @if(!$user->canAccessModule('sales') && !$user->canAccessModule('reports.sales') && !$user->canAccessModule('purchases') && !$user->canAccessModule('reports.purchases') && !$user->canAccessModule('receivables') && !$user->canAccessModule('reports.receivables') && !$user->canAccessModule('payables') && !$user->canAccessModule('reports.payables') && !$user->canAccessModule('inventory') && !$user->canAccessModule('reports.inventory'))
            <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm sm:col-span-2 xl:col-span-5">
                <p class="text-sm text-slate-600">Dashboard belum memiliki ringkasan untuk role ini.</p>
            </div>
        @endif
    </div>

    @if($user->canAccessModule('receivables') || $user->canAccessModule('reports.receivables') || $user->canAccessModule('payables') || $user->canAccessModule('reports.payables') || $user->canAccessModule('inventory') || $user->canAccessModule('cashbank'))
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4">
                <h3 class="text-base font-semibold text-slate-800">Perlu Perhatian</h3>
                <p class="mt-1 text-xs text-slate-500">Peringatan dihitung langsung dari data transaksi saat ini.</p>
            </div>

            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-4">
                @if($user->canAccessModule('receivables') || $user->canAccessModule('reports.receivables'))
                    <div class="rounded-xl border border-amber-100 bg-amber-50 p-4">
                        <p class="text-sm font-semibold text-amber-800">Piutang jatuh tempo</p>
                        <p class="mt-2 text-lg font-bold text-amber-900">{{ $formatRupiah($overdueReceivables) }}</p>
                    </div>
                @endif

                @if($user->canAccessModule('payables') || $user->canAccessModule('reports.payables'))
                    <div class="rounded-xl border border-rose-100 bg-rose-50 p-4">
                        <p class="text-sm font-semibold text-rose-800">Utang jatuh tempo</p>
                        <p class="mt-2 text-lg font-bold text-rose-900">{{ $formatRupiah($overduePayables) }}</p>
                    </div>
                @endif

                @if($user->canAccessModule('inventory'))
                    <div class="rounded-xl border border-teal-100 bg-teal-50 p-4">
                        <p class="text-sm font-semibold text-teal-900">Barang stok minimum</p>
                        <p class="mt-2 text-lg font-bold text-teal-900">{{ number_format($lowStockCount, 0, ',', '.') }} barang</p>
                    </div>
                @endif

                @if($user->canAccessModule('cashbank'))
                    <div class="rounded-xl border border-indigo-100 bg-indigo-50 p-4">
                        <p class="text-sm font-semibold text-indigo-900">Bank belum teridentifikasi</p>
                        <p class="mt-2 text-lg font-bold text-indigo-900">{{ number_format($unidentifiedBankCount, 0, ',', '.') }} transaksi</p>
                        <p class="mt-1 text-xs text-indigo-700">{{ $formatRupiah($unidentifiedBankTotal) }}</p>
                    </div>
                @endif
            </div>
        </section>
    @endif

    @if($showWarehouseMonitoring)
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-3 border-b border-slate-100 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-base font-semibold text-slate-800">Monitoring Stok Menipis</h3>
                    <p class="mt-1 text-xs text-slate-500">
                        Pemantauan barang aktif berdasarkan batas minimum pada master barang.
                        Total stok merupakan akumulasi seluruh gudang.
                    </p>
                </div>
                <a href="{{ route('stok-barang.index') }}" class="inline-flex w-fit items-center rounded-lg bg-[#15235F] px-4 py-2 text-sm font-semibold text-white transition hover:bg-[#0F8B8D]">
                    Lihat data persediaan
                </a>
                @if($user->role === 'Gudang')
                    <a href="{{ route('permintaan-pembelian.create') }}" class="inline-flex w-fit items-center rounded-lg border border-teal-200 bg-teal-50 px-4 py-2 text-sm font-semibold text-teal-800 transition hover:bg-teal-100">
                        Ajukan Permintaan Pembelian
                    </a>
                    <a href="{{ route('permintaan-pembelian.index') }}" class="inline-flex w-fit items-center rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        Riwayat Permintaan
                    </a>
                @endif
            </div>

            <div class="grid grid-cols-1 gap-3 p-5 sm:grid-cols-3">
                <div class="rounded-xl border border-rose-100 bg-rose-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-rose-700">Stok habis / minus</p>
                    <p class="mt-2 text-2xl font-bold text-rose-900">{{ number_format($outOfStockCount, 0, ',', '.') }}</p>
                    <p class="mt-1 text-xs text-rose-700">Saldo total nol atau negatif</p>
                </div>
                <div class="rounded-xl border border-amber-100 bg-amber-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-amber-700">Stok menipis</p>
                    <p class="mt-2 text-2xl font-bold text-amber-900">{{ number_format($lowStockOnlyCount, 0, ',', '.') }}</p>
                    <p class="mt-1 text-xs text-amber-700">Saldo di atas nol sampai batas minimum</p>
                </div>
                <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-slate-600">Total perlu restock</p>
                    <p class="mt-2 text-2xl font-bold text-[#15235F]">{{ number_format($lowStockCount, 0, ',', '.') }}</p>
                    <p class="mt-1 text-xs text-slate-500">Termasuk stok habis dan menipis</p>
                </div>
            </div>

            <div class="border-t border-slate-100">
                <div class="flex items-center justify-between gap-3 px-5 py-4">
                    <div>
                        <h4 class="text-sm font-semibold text-slate-800">Barang paling kritis</h4>
                        <p class="mt-1 text-xs text-slate-500">Menampilkan maksimal 10 barang dengan persentase stok terendah terhadap batas minimum.</p>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ number_format($stockAlertItems->count(), 0, ',', '.') }} ditampilkan</span>
                </div>

                @if($stockAlertItems->isEmpty())
                    <div class="px-5 pb-6">
                        <div class="rounded-xl border border-emerald-100 bg-emerald-50 p-4 text-sm text-emerald-800">
                            Tidak ada barang aktif yang berada pada atau di bawah batas minimum stok.
                        </div>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-100 text-sm">
                            <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                                <tr>
                                    <th class="px-5 py-3 font-medium">Kode</th>
                                    <th class="px-5 py-3 font-medium">Nama barang</th>
                                    <th class="whitespace-nowrap px-5 py-3 text-right font-medium">Stok tersedia</th>
                                    <th class="whitespace-nowrap px-5 py-3 text-right font-medium">Minimum stok</th>
                                    <th class="px-5 py-3 font-medium">Status</th>
                                    <th class="min-w-52 px-5 py-3 font-medium">Sebaran gudang</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($stockAlertItems as $item)
                                    <tr class="align-top hover:bg-slate-50">
                                        <td class="whitespace-nowrap px-5 py-3 font-medium text-slate-700">{{ $item->kode_barang }}</td>
                                        <td class="min-w-56 px-5 py-3 text-slate-800">
                                            <div class="font-semibold">{{ $item->nama_barang }}</div>
                                            <div class="mt-1 text-xs text-slate-500">{{ $item->satuan?->nama_satuan ?? '-' }}</div>
                                        </td>
                                        <td class="whitespace-nowrap px-5 py-3 text-right font-semibold text-slate-800">
                                            {{ $formatQty($item->total_stok) }} {{ $item->satuan?->nama_satuan ?? '' }}
                                        </td>
                                        <td class="whitespace-nowrap px-5 py-3 text-right text-slate-600">
                                            {{ $formatQty($item->minimum_stok) }} {{ $item->satuan?->nama_satuan ?? '' }}
                                        </td>
                                        <td class="whitespace-nowrap px-5 py-3">
                                            @if($item->status_monitoring_stok === 'Negatif')
                                                <span class="inline-flex rounded-full bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-800">Saldo negatif</span>
                                            @elseif($item->status_monitoring_stok === 'Habis')
                                                <span class="inline-flex rounded-full bg-rose-100 px-2.5 py-1 text-xs font-semibold text-rose-800">Habis</span>
                                            @else
                                                <span class="inline-flex rounded-full bg-amber-100 px-2.5 py-1 text-xs font-semibold text-amber-800">Menipis</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3 text-xs text-slate-600">
                                            @forelse($item->stokGudangs as $stok)
                                                <div class="mb-1 last:mb-0">
                                                    {{ $stok->gudang?->nama_gudang ?? 'Gudang tidak diketahui' }}:
                                                    <span class="font-semibold">{{ $formatQty($stok->qty) }}</span>
                                                </div>
                                            @empty
                                                <span class="text-slate-400">Tidak ada saldo non-nol di gudang</span>
                                            @endforelse
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </section>
    @endif

    @if($user->role === 'Owner')
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="mb-4">
                <h3 class="text-base font-semibold text-slate-800">Akses Cepat Owner</h3>
                <p class="mt-1 text-xs text-slate-600">Buka laporan utama untuk memantau kondisi perusahaan.</p>
            </div>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <a href="{{ route('laporan.laba-rugi') }}" class="rounded-xl border border-slate-200 p-4 transition hover:border-[#0F8B8D] hover:bg-teal-50">
                    <p class="font-semibold text-[#15235F]">Laba Rugi</p>
                    <p class="mt-1 text-xs text-slate-600">Pantau pendapatan dan beban.</p>
                </a>
                <a href="{{ route('laporan.neraca') }}" class="rounded-xl border border-slate-200 p-4 transition hover:border-[#0F8B8D] hover:bg-teal-50">
                    <p class="font-semibold text-[#15235F]">Neraca</p>
                    <p class="mt-1 text-xs text-slate-600">Lihat posisi aset, kewajiban, dan ekuitas.</p>
                </a>
                <a href="{{ route('laporan.arus-kas') }}" class="rounded-xl border border-slate-200 p-4 transition hover:border-[#0F8B8D] hover:bg-teal-50">
                    <p class="font-semibold text-[#15235F]">Arus Kas</p>
                    <p class="mt-1 text-xs text-slate-600">Pantau aliran kas perusahaan.</p>
                </a>
                <a href="{{ route('buku-besar.index') }}" class="rounded-xl border border-slate-200 p-4 transition hover:border-[#0F8B8D] hover:bg-teal-50">
                    <p class="font-semibold text-[#15235F]">Buku Besar</p>
                    <p class="mt-1 text-xs text-slate-600">Telusuri detail mutasi akun.</p>
                </a>
            </div>
        </section>
    @endif

    @if($showSales)
        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
            <div class="flex flex-col gap-2 border-b border-slate-100 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-base font-semibold text-slate-800">Invoice Penjualan Terbaru</h3>
                    <p class="mt-1 text-xs text-slate-500">Lima invoice aktif terakhir.</p>
                </div>
                <a href="{{ route('invoice-penjualan.index') }}" class="text-sm font-semibold text-teal-700 hover:text-teal-900">Lihat semua</a>
            </div>

            @if($latestInvoices->isEmpty())
                <div class="p-8 text-center text-sm text-slate-500">Belum ada invoice penjualan aktif.</div>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100 text-sm">
                        <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500">
                            <tr>
                                <th class="px-5 py-3 font-medium">Tanggal</th>
                                <th class="px-5 py-3 font-medium">Nomor Invoice</th>
                                <th class="px-5 py-3 font-medium">Customer</th>
                                <th class="px-5 py-3 text-right font-medium">Total</th>
                                <th class="px-5 py-3 font-medium">Pembayaran</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($latestInvoices as $invoice)
                                <tr>
                                    <td class="whitespace-nowrap px-5 py-3 text-slate-600">{{ $invoice->tanggal_invoice?->format('d/m/Y') ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 font-medium text-slate-800">{{ $invoice->nomor_invoice }}</td>
                                    <td class="px-5 py-3 text-slate-600">{{ $invoice->customer?->nama_customer ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-right font-semibold text-slate-800">{{ $formatRupiah($invoice->total) }}</td>
                                    <td class="whitespace-nowrap px-5 py-3 text-slate-600">{{ $invoice->status_pembayaran }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    @endif
</div>
@endsection
