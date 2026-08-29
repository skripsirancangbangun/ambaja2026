@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')

<div class="space-y-6">

    {{-- WELCOME --}}
    <div>
        <h2 class="text-2xl font-semibold text-slate-800">
            Dashboard Owner
        </h2>

        <p class="mt-1 text-sm text-slate-500">
            Ringkasan kondisi keuangan dan operasional CV Andika Mustika.
        </p>
    </div>


    {{-- SUMMARY CARDS --}}
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-4">

        {{-- Penjualan --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                        Penjualan Bulan Ini
                    </p>

                    <h3 class="mt-2 text-2xl font-bold text-[#15235F]">
                        Rp 285.000.000
                    </h3>

                    <p class="mt-2 text-xs text-emerald-600">
                        +8,4% dari bulan lalu
                    </p>
                </div>

                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-teal-50 text-[#32AAA6]">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                              d="M4 19V9m5 10V5m5 14v-7m5 7V3"/>
                    </svg>
                </div>
            </div>
        </div>


        {{-- Piutang --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                        Piutang Belum Lunas
                    </p>

                    <h3 class="mt-2 text-2xl font-bold text-[#15235F]">
                        Rp 42.750.000
                    </h3>

                    <p class="mt-2 text-xs text-amber-600">
                        7 invoice masih terbuka
                    </p>
                </div>

                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50 text-amber-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                              d="M12 8c-2.2 0-4 1-4 2.5S9.8 13 12 13s4 1 4 2.5S14.2 18 12 18m0-10V6m0 12v2"/>
                    </svg>
                </div>
            </div>
        </div>


        {{-- Utang --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                        Utang Belum Lunas
                    </p>

                    <h3 class="mt-2 text-2xl font-bold text-[#15235F]">
                        Rp 68.400.000
                    </h3>

                    <p class="mt-2 text-xs text-rose-600">
                        Rp 18.000.000 jatuh tempo
                    </p>
                </div>

                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-rose-50 text-rose-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                              d="M12 9v4m0 4h.01M10.3 4.5l-7 12.2A2 2 0 005 19h14a2 2 0 001.7-3.3l-7-12.2a2 2 0 00-3.4 0z"/>
                    </svg>
                </div>
            </div>
        </div>


        {{-- Persediaan --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-slate-400">
                        Nilai Persediaan
                    </p>

                    <h3 class="mt-2 text-2xl font-bold text-[#15235F]">
                        Rp 472.300.000
                    </h3>

                    <p class="mt-2 text-xs text-slate-500">
                        216 jenis barang aktif
                    </p>
                </div>

                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-600">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                              d="M4 7l8-4 8 4-8 4-8-4m-8 0v10l8 4 8-4V7M12 11v10"/>
                    </svg>
                </div>
            </div>
        </div>

    </div>


    {{-- CASH & BANK --}}
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-3">

        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm xl:col-span-2">

            <div class="mb-5 flex items-center justify-between">
                <div>
                    <h3 class="text-base font-semibold text-slate-800">
                        Kas & Rekening Bank
                    </h3>

                    <p class="mt-1 text-xs text-slate-400">
                        Posisi saldo kas dan bank saat ini.
                    </p>
                </div>

                <div class="rounded-lg bg-teal-50 px-3 py-2 text-xs font-semibold text-[#15235F]">
                    Total Rp 319.450.000
                </div>
            </div>


            <div class="overflow-x-auto">

                <table class="w-full text-left text-sm">

                    <thead>
                        <tr class="border-b border-slate-200 text-xs uppercase tracking-wide text-slate-400">
                            <th class="pb-3 font-medium">Rekening</th>
                            <th class="pb-3 font-medium">Jenis</th>
                            <th class="pb-3 text-right font-medium">Saldo</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">

                        <tr>
                            <td class="py-4 font-medium text-slate-700">Kas Toko</td>
                            <td class="py-4 text-slate-500">Kas</td>
                            <td class="py-4 text-right font-semibold text-slate-700">Rp 25.500.000</td>
                        </tr>

                        <tr>
                            <td class="py-4 font-medium text-slate-700">BCA</td>
                            <td class="py-4 text-slate-500">Bank</td>
                            <td class="py-4 text-right font-semibold text-slate-700">Rp 180.250.000</td>
                        </tr>

                        <tr>
                            <td class="py-4 font-medium text-slate-700">Mandiri</td>
                            <td class="py-4 text-slate-500">Bank</td>
                            <td class="py-4 text-right font-semibold text-slate-700">Rp 72.500.000</td>
                        </tr>

                        <tr>
                            <td class="py-4 font-medium text-slate-700">BRI</td>
                            <td class="py-4 text-slate-500">Bank</td>
                            <td class="py-4 text-right font-semibold text-slate-700">Rp 41.200.000</td>
                        </tr>

                    </tbody>

                </table>

            </div>

        </div>


        {{-- ALERTS --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <div class="mb-5">
                <h3 class="text-base font-semibold text-slate-800">
                    Perlu Perhatian
                </h3>

                <p class="mt-1 text-xs text-slate-400">
                    Transaksi yang membutuhkan tindak lanjut.
                </p>
            </div>


            <div class="space-y-3">

                <div class="rounded-xl border border-amber-100 bg-amber-50 p-4">
                    <p class="text-sm font-semibold text-amber-800">
                        5 transaksi bank belum dicocokkan
                    </p>

                    <p class="mt-1 text-xs text-amber-600">
                        Total Rp 28.750.000
                    </p>
                </div>


                <div class="rounded-xl border border-rose-100 bg-rose-50 p-4">
                    <p class="text-sm font-semibold text-rose-700">
                        3 piutang melewati jatuh tempo
                    </p>

                    <p class="mt-1 text-xs text-rose-500">
                        Total Rp 12.500.000
                    </p>
                </div>


                <div class="rounded-xl border border-teal-100 bg-teal-50 p-4">
                    <p class="text-sm font-semibold text-[#15235F]">
                        8 barang mencapai stok minimum
                    </p>

                    <p class="mt-1 text-xs text-[#32AAA6]">
                        Perlu evaluasi pembelian
                    </p>
                </div>

            </div>

        </div>

    </div>


    {{-- LOWER SECTION --}}
    <div class="grid grid-cols-1 gap-6 xl:grid-cols-2">

        {{-- SALES CHART PLACEHOLDER --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <div class="mb-6 flex items-start justify-between">

                <div>
                    <h3 class="text-base font-semibold text-slate-800">
                        Tren Penjualan
                    </h3>

                    <p class="mt-1 text-xs text-slate-400">
                        Penjualan enam bulan terakhir.
                    </p>
                </div>

                <span class="rounded-lg bg-slate-50 px-3 py-1.5 text-xs text-slate-500">
                    6 Bulan
                </span>

            </div>


            <div class="flex h-64 items-center justify-center rounded-xl border border-dashed border-slate-200 bg-slate-50">

                <div class="text-center">

                    <svg
                        class="mx-auto h-8 w-8 text-slate-300"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                              d="M4 19V9m5 10V5m5 14v-7m5 7V3"/>
                    </svg>

                    <p class="mt-3 text-sm font-medium text-slate-500">
                        Grafik penjualan
                    </p>

                    <p class="mt-1 text-xs text-slate-400">
                        Akan dihubungkan setelah data transaksi tersedia.
                    </p>

                </div>

            </div>

        </div>


        {{-- RECENT ACTIVITY --}}
        <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">

            <div class="mb-5">
                <h3 class="text-base font-semibold text-slate-800">
                    Aktivitas Terbaru
                </h3>

                <p class="mt-1 text-xs text-slate-400">
                    Ringkasan transaksi terbaru.
                </p>
            </div>


            <div class="space-y-4">

                <div class="flex gap-3">
                    <div class="mt-1 h-2.5 w-2.5 flex-shrink-0 rounded-full bg-[#32AAA6]"></div>

                    <div>
                        <p class="text-sm font-medium text-slate-700">
                            Pembayaran customer diterima
                        </p>

                        <p class="mt-1 text-xs text-slate-400">
                            INV-2026-0081 • Rp 20.000.000 • BCA
                        </p>
                    </div>
                </div>


                <div class="flex gap-3">
                    <div class="mt-1 h-2.5 w-2.5 flex-shrink-0 rounded-full bg-[#15235F]"></div>

                    <div>
                        <p class="text-sm font-medium text-slate-700">
                            Invoice penjualan dibuat
                        </p>

                        <p class="mt-1 text-xs text-slate-400">
                            INV-2026-0092 • CV Baja Sentosa
                        </p>
                    </div>
                </div>


                <div class="flex gap-3">
                    <div class="mt-1 h-2.5 w-2.5 flex-shrink-0 rounded-full bg-amber-500"></div>

                    <div>
                        <p class="text-sm font-medium text-slate-700">
                            Pembelian barang diterima
                        </p>

                        <p class="mt-1 text-xs text-slate-400">
                            PO-2026-0048 • PT Supplier Baja
                        </p>
                    </div>
                </div>


                <div class="flex gap-3">
                    <div class="mt-1 h-2.5 w-2.5 flex-shrink-0 rounded-full bg-[#E6007E]"></div>

                    <div>
                        <p class="text-sm font-medium text-slate-700">
                            Transaksi bank belum teridentifikasi
                        </p>

                        <p class="mt-1 text-xs text-slate-400">
                            Transfer masuk Rp 5.350.000 • BCA
                        </p>
                    </div>
                </div>

            </div>

        </div>

    </div>

</div>

@endsection