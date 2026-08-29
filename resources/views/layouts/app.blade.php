<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>

    <meta charset="utf-8">

    <meta
        name="viewport"
        content="width=device-width, initial-scale=1"
    >

    <meta
        name="csrf-token"
        content="{{ csrf_token() }}"
    >

    <title>
        {{ config('app.name', 'AMBAJA 2026') }}
    </title>

    @vite([
        'resources/css/app.css',
        'resources/js/app.js'
    ])

</head>


<body class="bg-[#F6F8FB] text-slate-800">

<div class="flex min-h-screen">


    {{-- ============================================================
        SIDEBAR
    ============================================================ --}}

    <aside
        class="
            sticky top-0
            flex h-screen w-64 flex-shrink-0 flex-col
            border-r border-slate-200
            bg-white
        "
    >


        {{-- LOGO / BRAND --}}
        <div
            class="
                flex h-20 flex-shrink-0
                items-center
                border-b border-slate-200
                px-5
            "
        >

            <div class="flex items-center gap-3">

                <div
                    class="
                        flex h-11 w-10
                        items-center justify-center
                    "
                >

                    <img
                        src="{{ asset('images/LogoAMBAJA.png') }}"
                        alt="Logo CV Andika Mustika"
                        class="max-h-full max-w-full object-contain"
                    >

                </div>


                <div>

                    <div
                        class="
                            text-[15px]
                            font-bold
                            leading-tight
                            text-[#15235F]
                        "
                    >
                        AMBAJA
                    </div>

                    <div class="mt-0.5 text-[10px] text-slate-400">
                        CV Andika Mustika
                    </div>

                </div>

            </div>

        </div>



        {{-- ========================================================
            MENU
        ======================================================== --}}

        <nav
            class="
                flex-1
                overflow-y-auto
                px-3 py-4
            "
        >


            {{-- DASHBOARD --}}
            <a
                href="{{ route('dashboard') }}"
                class="
                    mb-2
                    flex w-full
                    items-center gap-3
                    rounded-lg
                    px-3 py-2.5
                    text-sm font-medium
                    transition

                    {{ request()->routeIs('dashboard')
                        ? 'border-l-[3px] border-[#32AAA6] bg-teal-50 text-[#15235F]'
                        : 'text-slate-600 hover:bg-slate-50 hover:text-[#15235F]'
                    }}
                "
            >

                <svg
                    class="h-[18px] w-[18px] flex-shrink-0"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >

                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="1.8"
                        d="
                            M3 11.5L12 4l9 7.5
                            M5.5 10v10h13V10
                            M9.5 20v-6h5v6
                        "
                    />

                </svg>

                <span>
                    Dashboard
                </span>

            </a>



            {{-- ====================================================
                MASTER DATA
            ==================================================== --}}

            <details class="group mb-1">

                <summary
                    class="
                        flex w-full cursor-pointer
                        items-center justify-between
                        rounded-lg
                        px-3 py-2.5
                        text-sm font-medium
                        text-slate-600
                        transition
                        hover:bg-slate-50
                        hover:text-[#15235F]
                    "
                >

                    <div class="flex items-center gap-3">

                        <svg
                            class="h-[18px] w-[18px] flex-shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="
                                    M4 6h16
                                    M4 12h16
                                    M4 18h16
                                "
                            />

                        </svg>

                        <span>
                            Master Data
                        </span>

                    </div>


                    <svg
                        class="
                            h-4 w-4
                            text-slate-400
                            transition-transform
                            duration-200
                            group-open:rotate-90
                        "
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 5l7 7-7 7"
                        />

                    </svg>

                </summary>


                <div
                    class="
                        ml-[21px] mt-1
                        space-y-1
                        border-l border-slate-200
                        pl-3
                    "
                >

                    <a
                    href="{{ route('barang.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-slate-500 transition hover:bg-teal-50 hover:text-[#15235F]"
                    >
                        Barang
                    </a>

                    <a
                    href="{{ route('kategori-barang.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-slate-500 transition hover:bg-teal-50 hover:text-[#15235F]"
                    >
                        Kategori Barang
                    </a>

                    <a
                    href="{{ route('satuan.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-slate-500 transition hover:bg-teal-50 hover:text-[#15235F]"
                    >
                        Satuan
                    </a>

                    <a
                    href="{{ route('gudang.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-slate-500 transition hover:bg-teal-50 hover:text-[#15235F]"
                    >
                        Gudang
                    </a>

                    <a
                        href="#"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-slate-500 transition hover:bg-teal-50 hover:text-[#15235F]"
                    >
                        Customer
                    </a>

                    <a
                    href="{{ route('supplier.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-slate-500 transition hover:bg-teal-50 hover:text-[#15235F]"
                    >
                        Supplier
                    </a>

                    <a
                    href="{{ route('rekening-kas-bank.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-slate-500 transition hover:bg-teal-50 hover:text-[#15235F]"
                    >
                        Kas & Rekening Bank
                    </a>

                    <a
                        href="#"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-slate-500 transition hover:bg-teal-50 hover:text-[#15235F]"
                    >
                        Chart of Accounts
                    </a>


                    @if(in_array(auth()->user()->role, ['Owner', 'Admin']))

                        <a
                            href="#"
                            class="block w-full rounded-lg px-3 py-2 text-[13px] text-slate-500 transition hover:bg-teal-50 hover:text-[#15235F]"
                        >
                            User Management
                        </a>

                    @endif

                </div>

            </details>



            {{-- ====================================================
                PENJUALAN
            ==================================================== --}}

            <details class="group mb-1">

                <summary
                    class="
                        flex w-full cursor-pointer
                        items-center justify-between
                        rounded-lg
                        px-3 py-2.5
                        text-sm font-medium
                        text-slate-600
                        transition
                        hover:bg-slate-50
                        hover:text-[#15235F]
                    "
                >

                    <div class="flex items-center gap-3">

                        <svg
                            class="h-[18px] w-[18px] flex-shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="
                                    M3 4h2l2.2 10.5
                                    a2 2 0 002 1.5h7.8
                                    a2 2 0 001.9-1.4
                                    L21 8H7
                                "
                            />

                            <circle
                                cx="10"
                                cy="20"
                                r="1"
                            />

                            <circle
                                cx="18"
                                cy="20"
                                r="1"
                            />

                        </svg>

                        <span>
                            Penjualan
                        </span>

                    </div>


                    <svg
                        class="h-4 w-4 text-slate-400 transition-transform duration-200 group-open:rotate-90"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 5l7 7-7 7"
                        />

                    </svg>

                </summary>


                <div
                    class="
                        ml-[21px] mt-1
                        space-y-1
                        border-l border-slate-200
                        pl-3
                    "
                >

                    <a
                        href="#"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-slate-500 transition hover:bg-teal-50 hover:text-[#15235F]"
                    >
                        Penjualan
                    </a>

                    <a
                        href="#"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-slate-500 transition hover:bg-teal-50 hover:text-[#15235F]"
                    >
                        Invoice
                    </a>

                    <a
                        href="#"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-slate-500 transition hover:bg-teal-50 hover:text-[#15235F]"
                    >
                        Penerimaan Pembayaran
                    </a>

                    <a
                        href="#"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-slate-500 transition hover:bg-teal-50 hover:text-[#15235F]"
                    >
                        Retur Penjualan
                    </a>

                    <a
                        href="#"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-slate-500 transition hover:bg-teal-50 hover:text-[#15235F]"
                    >
                        Piutang
                    </a>

                </div>

            </details>



            {{-- ====================================================
                PEMBELIAN
            ==================================================== --}}

            <details class="group mb-1">

                <summary
                    class="
                        flex w-full cursor-pointer
                        items-center justify-between
                        rounded-lg
                        px-3 py-2.5
                        text-sm font-medium
                        text-slate-600
                        transition
                        hover:bg-slate-50
                        hover:text-[#15235F]
                    "
                >

                    <div class="flex items-center gap-3">

                        <svg
                            class="h-[18px] w-[18px] flex-shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="
                                    M12 3v12
                                    M8 11l4 4 4-4
                                    M5 20h14
                                "
                            />

                        </svg>

                        <span>
                            Pembelian
                        </span>

                    </div>


                    <svg
                        class="h-4 w-4 text-slate-400 transition-transform duration-200 group-open:rotate-90"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 5l7 7-7 7"
                        />

                    </svg>

                </summary>


                <div
                    class="
                        ml-[21px] mt-1
                        space-y-1
                        border-l border-slate-200
                        pl-3
                    "
                >

                    <a
                    href="{{ route('purchase-order.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-slate-500 transition hover:bg-teal-50 hover:text-[#15235F]"
                    >
                        Purchase Order
                    </a>

                    <a
                    href="{{ route('penerimaan-barang.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-slate-500 transition hover:bg-teal-50 hover:text-[#15235F]"
                    >
                        Penerimaan Barang
                    </a>

                    <a
                    href="{{ route('invoice-supplier.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-slate-500 transition hover:bg-teal-50 hover:text-[#15235F]"
                    >
                        Invoice Supplier
                    </a>

                    <a
                    href="{{ route('pembayaran-supplier.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-slate-500 transition hover:bg-teal-50 hover:text-[#15235F]"
                    >
                        Pembayaran Supplier
                    </a>

                    <a
                    href="{{ route('retur-pembelian.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-slate-500 transition hover:bg-teal-50 hover:text-[#15235F]"
                    >
                        Retur Pembelian
                    </a>

                    <a
                    href="{{ route('utang.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-slate-500 transition hover:bg-teal-50 hover:text-[#15235F]"
                    >
                        Utang
                    </a>

                </div>

            </details>



            {{-- ====================================================
                PERSEDIAAN
            ==================================================== --}}

            <details class="group mb-1">

                <summary
                    class="
                        flex w-full cursor-pointer
                        items-center justify-between
                        rounded-lg
                        px-3 py-2.5
                        text-sm font-medium
                        text-slate-600
                        transition
                        hover:bg-slate-50
                        hover:text-[#15235F]
                    "
                >

                    <div class="flex items-center gap-3">

                        <svg
                            class="h-[18px] w-[18px] flex-shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="
                                    M4 7l8-4 8 4-8 4-8-4
                                    M4 7v10l8 4 8-4V7
                                    M12 11v10
                                "
                            />

                        </svg>

                        <span>
                            Persediaan
                        </span>

                    </div>


                    <svg
                        class="h-4 w-4 text-slate-400 transition-transform duration-200 group-open:rotate-90"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 5l7 7-7 7"
                        />

                    </svg>

                </summary>


                <div
                    class="
                        ml-[21px] mt-1
                        space-y-1
                        border-l border-slate-200
                        pl-3
                    "
                >

                    <a
                    href="{{ route('stok-barang.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-slate-500 transition hover:bg-teal-50 hover:text-[#15235F]"
                    >
                        Stok Barang
                    </a>

                    <a
                    href="{{ route('kartu-stok.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-slate-500 transition hover:bg-teal-50 hover:text-[#15235F]"
                    >
                        Kartu Stok
                    </a>

                    <a
                    href="{{ route('mutasi-gudang.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-slate-500 transition hover:bg-teal-50 hover:text-[#15235F]"
                    >
                        Mutasi Gudang
                    </a>

                    <a
                    href="{{ route('stock-opname.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-slate-500 transition hover:bg-teal-50 hover:text-[#15235F]"
                    >
                        Stock Opname
                    </a>

                    <a
                    href="{{ route('penyesuaian-stok.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-slate-500 transition hover:bg-teal-50 hover:text-[#15235F]"
                    >
                        Penyesuaian Stok
                    </a>

                </div>

            </details>



            {{-- ====================================================
                KAS & BANK
            ==================================================== --}}

            <details class="group mb-1">

                <summary
                    class="
                        flex w-full cursor-pointer
                        items-center justify-between
                        rounded-lg
                        px-3 py-2.5
                        text-sm font-medium
                        text-slate-600
                        transition
                        hover:bg-slate-50
                        hover:text-[#15235F]
                    "
                >

                    <div class="flex items-center gap-3">

                        <svg
                            class="h-[18px] w-[18px] flex-shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="
                                    M3 7h18
                                    M5 7v10h14V7
                                    M8 11h4
                                    M8 14h3
                                "
                            />

                        </svg>

                        <span>
                            Kas & Bank
                        </span>

                    </div>


                    <svg
                        class="h-4 w-4 text-slate-400 transition-transform duration-200 group-open:rotate-90"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 5l7 7-7 7"
                        />

                    </svg>

                </summary>


                <div
                    class="
                        ml-[21px] mt-1
                        space-y-1
                        border-l border-slate-200
                        pl-3
                    "
                >

                    <a
                    href="{{ route('uang-masuk.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-slate-500 transition hover:bg-teal-50 hover:text-[#15235F]"
                    >
                        Uang Masuk
                    </a>

                    <a
                    href="{{ route('uang-keluar.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-slate-500 transition hover:bg-teal-50 hover:text-[#15235F]"
                    >
                        Uang Keluar
                    </a>

                    <a
                    href="{{ route('transfer-kas-bank.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-slate-500 transition hover:bg-teal-50 hover:text-[#15235F]"
                    >
                        Transfer Antar Rekening
                    </a>

                    <a
                    href="{{ route('mutasi-kas-bank.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-slate-500 transition hover:bg-teal-50 hover:text-[#15235F]"
                    >
                        Mutasi Kas / Bank
                    </a>

                    <a
                    href="{{ route('rekonsiliasi-bank.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-slate-500 transition hover:bg-teal-50 hover:text-[#15235F]"
                    >
                        Rekonsiliasi Bank
                    </a>

                    <a
                    href="{{ route('transaksi-belum-teridentifikasi.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] leading-5 text-slate-500 transition hover:bg-teal-50 hover:text-[#15235F]"
                    >
                        Transaksi Belum Teridentifikasi
                    </a>

                </div>

            </details>



            {{-- ====================================================
                ACCOUNTING
            ==================================================== --}}

            <details class="group mb-1">

                <summary
                    class="
                        flex w-full cursor-pointer
                        items-center justify-between
                        rounded-lg
                        px-3 py-2.5
                        text-sm font-medium
                        text-slate-600
                        transition
                        hover:bg-slate-50
                        hover:text-[#15235F]
                    "
                >

                    <div class="flex items-center gap-3">

                        <svg
                            class="h-[18px] w-[18px] flex-shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="
                                    M5 3h14v18H5z
                                    M8 7h8
                                    M8 11h8
                                    M8 15h5
                                "
                            />

                        </svg>

                        <span>
                            Accounting
                        </span>

                    </div>


                    <svg
                        class="h-4 w-4 text-slate-400 transition-transform duration-200 group-open:rotate-90"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 5l7 7-7 7"
                        />

                    </svg>

                </summary>


                <div
                    class="
                        ml-[21px] mt-1
                        space-y-1
                        border-l border-slate-200
                        pl-3
                    "
                >

                    <a
                        href="#"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-slate-500 transition hover:bg-teal-50 hover:text-[#15235F]"
                    >
                        Jurnal
                    </a>

                    <a
                        href="#"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-slate-500 transition hover:bg-teal-50 hover:text-[#15235F]"
                    >
                        Buku Besar
                    </a>

                    <a
                        href="#"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-slate-500 transition hover:bg-teal-50 hover:text-[#15235F]"
                    >
                        Neraca Saldo
                    </a>

                    <a
                        href="#"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-slate-500 transition hover:bg-teal-50 hover:text-[#15235F]"
                    >
                        Penyesuaian
                    </a>

                    <a
                        href="#"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-slate-500 transition hover:bg-teal-50 hover:text-[#15235F]"
                    >
                        Tutup Periode
                    </a>

                </div>

            </details>



            {{-- ====================================================
                LAPORAN
            ==================================================== --}}

            <details class="group mb-1">

                <summary
                    class="
                        flex w-full cursor-pointer
                        items-center justify-between
                        rounded-lg
                        px-3 py-2.5
                        text-sm font-medium
                        text-slate-600
                        transition
                        hover:bg-slate-50
                        hover:text-[#15235F]
                    "
                >

                    <div class="flex items-center gap-3">

                        <svg
                            class="h-[18px] w-[18px] flex-shrink-0"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="
                                    M5 20V10
                                    M10 20V4
                                    M15 20v-7
                                    M20 20V7
                                "
                            />

                        </svg>

                        <span>
                            Laporan
                        </span>

                    </div>


                    <svg
                        class="h-4 w-4 text-slate-400 transition-transform duration-200 group-open:rotate-90"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 5l7 7-7 7"
                        />

                    </svg>

                </summary>


                <div
                    class="
                        ml-[21px] mt-1
                        space-y-1
                        border-l border-slate-200
                        pl-3
                    "
                >

                    <a
                        href="#"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-slate-500 transition hover:bg-teal-50 hover:text-[#15235F]"
                    >
                        Penjualan
                    </a>

                    <a
                    href="{{ route('laporan.pembelian') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-slate-500 transition hover:bg-teal-50 hover:text-[#15235F]"
                    >
                        Pembelian
                    </a>

                    <a
                    href="{{ route('laporan.persediaan') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-slate-500 transition hover:bg-teal-50 hover:text-[#15235F]"
                    >
                        Persediaan
                    </a>

                    <a
                        href="#"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-slate-500 transition hover:bg-teal-50 hover:text-[#15235F]"
                    >
                        Piutang
                    </a>

                    <a
                    href="{{ route('laporan.utang') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-slate-500 transition hover:bg-teal-50 hover:text-[#15235F]"
                    >
                        Utang
                    </a>

                    <a
                        href="#"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-slate-500 transition hover:bg-teal-50 hover:text-[#15235F]"
                    >
                        Kas & Bank
                    </a>


                    <div class="my-2 border-t border-slate-200"></div>


                    <a
                        href="#"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-slate-500 transition hover:bg-teal-50 hover:text-[#15235F]"
                    >
                        Laba Rugi
                    </a>

                    <a
                        href="#"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-slate-500 transition hover:bg-teal-50 hover:text-[#15235F]"
                    >
                        Posisi Keuangan
                    </a>

                    <a
                        href="#"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-slate-500 transition hover:bg-teal-50 hover:text-[#15235F]"
                    >
                        Arus Kas
                    </a>

                    <a
                        href="#"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-slate-500 transition hover:bg-teal-50 hover:text-[#15235F]"
                    >
                        Perubahan Ekuitas
                    </a>

                </div>

            </details>


        </nav>



        {{-- ========================================================
            USER AREA
        ======================================================== --}}

        <div
            class="
                flex-shrink-0
                border-t border-slate-200
                bg-white
                p-3
            "
        >

            <div
                class="
                    flex items-center gap-3
                    rounded-xl
                    px-2 py-2
                "
            >

                {{-- AVATAR --}}
                <div
                    class="
                        flex h-9 w-9
                        flex-shrink-0
                        items-center justify-center
                        rounded-full
                        bg-[#15235F]
                        text-sm font-semibold
                        text-white
                    "
                >
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>


                {{-- USER --}}
                <div class="min-w-0 flex-1">

                    <div
                        class="
                            truncate
                            text-sm font-medium
                            text-slate-700
                        "
                    >
                        {{ auth()->user()->name }}
                    </div>

                    <div class="text-xs text-slate-400">
                        {{ auth()->user()->role }}
                    </div>

                </div>


                {{-- LOGOUT --}}
                <form
                    method="POST"
                    action="{{ route('logout') }}"
                >

                    @csrf

                    <button
                        type="submit"
                        title="Logout"
                        class="
                            flex h-8 w-8
                            items-center justify-center
                            rounded-lg
                            text-slate-400
                            transition
                            hover:bg-red-50
                            hover:text-red-500
                        "
                    >

                        <svg
                            class="h-[17px] w-[17px]"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >

                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="1.8"
                                d="
                                    M10 17l5-5-5-5
                                    M15 12H3
                                    M12 3h6a2 2 0 012 2v14a2 2 0 01-2 2h-6
                                "
                            />

                        </svg>

                    </button>

                </form>

            </div>

        </div>

    </aside>



    {{-- ============================================================
        MAIN AREA
    ============================================================ --}}

    <div class="min-w-0 flex-1">


        {{-- ========================================================
            TOPBAR
        ======================================================== --}}

        <header
            class="
                sticky top-0 z-20
                flex h-20
                items-center justify-between
                border-b border-slate-200
                bg-white
                px-8
            "
        >


            {{-- TITLE --}}
            <div>

                <h1
                    class="
                        text-xl
                        font-semibold
                        text-[#15235F]
                    "
                >
                    @yield('title', 'Dashboard')
                </h1>

                <p
                    class="
                        mt-1
                        text-xs
                        text-slate-400
                    "
                >
                    Sistem Informasi Akuntansi CV Andika Mustika
                </p>

            </div>



            {{-- TOPBAR RIGHT --}}
            <div class="flex items-center gap-3">


                {{-- ACCOUNTING PERIOD --}}
                <div
                    class="
                        hidden
                        items-center gap-2
                        rounded-lg
                        border border-slate-200
                        bg-slate-50
                        px-3 py-2
                        md:flex
                    "
                >

                    <svg
                        class="h-4 w-4 text-slate-400"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="
                                M8 3v4
                                M16 3v4
                                M5 9h14
                                M5 5h14v16H5z
                            "
                        />

                    </svg>


                    <span
                        class="
                            text-xs
                            font-medium
                            text-slate-600
                        "
                    >
                        {{ now()->translatedFormat('F Y') }}
                    </span>

                </div>



                {{-- NOTIFICATION --}}
                <button
                    type="button"
                    title="Notifikasi"
                    class="
                        relative
                        flex h-10 w-10
                        items-center justify-center
                        rounded-lg
                        border border-slate-200
                        bg-white
                        text-slate-500
                        transition
                        hover:bg-slate-50
                        hover:text-[#15235F]
                    "
                >

                    <svg
                        class="h-[18px] w-[18px]"
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                    >

                        <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                            d="
                                M18 8a6 6 0 10-12 0
                                c0 7-3 7-3 9h18
                                c0-2-3-2-3-9
                                M13.73 21a2 2 0 01-3.46 0
                            "
                        />

                    </svg>


                    {{-- Notification indicator --}}
                    <span
                        class="
                            absolute
                            right-[9px] top-[8px]
                            h-2 w-2
                            rounded-full
                            bg-[#E6007E]
                        "
                    ></span>

                </button>

            </div>

        </header>



        {{-- ========================================================
            PAGE CONTENT
        ======================================================== --}}

        <main class="p-8">

            @yield('content')

        </main>

    </div>

</div>

</body>

</html>