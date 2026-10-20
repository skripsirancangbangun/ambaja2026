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
            border-r border-white/10
            bg-[#0F8B8D]
            text-white
            shadow-xl
        "
    >


        {{-- LOGO / BRAND --}}
        <div
            class="
                flex h-20 flex-shrink-0
                items-center
                border-b border-white/15
                px-5
            "
        >

            <a href="{{ route('profil-perusahaan.index') }}" class="flex items-center gap-3 group transition hover:opacity-90" title="Kelola Profil Perusahaan (CV Andika Mustika)">

                <div
                    class="
                        flex h-11 w-10
                        items-center justify-center
                        rounded-xl bg-white p-1.5
                        shadow-sm ring-1 ring-white/20
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
                            text-white
                            group-hover:text-white
                            transition
                        "
                    >
                        AMBAJA
                    </div>

                    <div class="mt-0.5 text-[10px] text-white/90 group-hover:text-white transition">
                        CV Andika Mustika
                    </div>

                </div>

            </a>

        </div>



        {{-- ========================================================
            MENU
        ======================================================== --}}

        <nav
            class="
                flex-1
                overflow-y-auto
                px-3 py-4
                [scrollbar-color:rgba(255,255,255,0.24)_transparent]
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
                        ? 'border-l-[3px] border-[#15235F] bg-white text-[#0F8B8D] shadow-sm'
                        : 'text-white hover:bg-white/10 hover:text-white'
                    }}
                "
            >

                <x-icon name="home" class="h-[18px] w-[18px] flex-shrink-0" />

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
                        text-white
                        transition
                        hover:bg-white/10
                        hover:text-white
                    "
                >

                    <div class="flex items-center gap-3">

                        <x-icon name="menu" class="h-[18px] w-[18px] flex-shrink-0" />

                        <span>
                            Master Data
                        </span>

                    </div>


                    <x-icon name="chevron-right" class="h-4 w-4
                            text-white/90
                            transition-transform
                            duration-200
                            group-open:rotate-90" />

                </summary>


                <div
                    class="
                        ml-[21px] mt-1
                        space-y-1
                        border-l border-white/10
                        pl-3
                    "
                >

                    <a
                        href="{{ route('profil-perusahaan.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-white hover:bg-white/10 hover:text-white transition {{ request()->routeIs('profil-perusahaan.*') ? 'bg-white font-semibold text-[#0F8B8D] shadow-sm' : '' }}"
                    >
                        Profil Perusahaan
                    </a>

                    <a
                    href="{{ route('barang.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-white hover:bg-white/10 hover:text-white transition"
                    >
                        Barang
                    </a>

                    <a
                    href="{{ route('kategori-barang.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-white hover:bg-white/10 hover:text-white transition"
                    >
                        Kategori Barang
                    </a>

                    <a
                    href="{{ route('satuan.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-white hover:bg-white/10 hover:text-white transition"
                    >
                        Satuan
                    </a>

                    <a
                    href="{{ route('gudang.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-white hover:bg-white/10 hover:text-white transition"
                    >
                        Gudang
                    </a>

                    <a
                        href="{{ route('customer.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-white hover:bg-white/10 hover:text-white transition {{ request()->routeIs('customer.*') ? 'bg-white font-semibold text-[#0F8B8D] shadow-sm' : '' }}"
                    >
                        Customer
                    </a>

                    <a
                    href="{{ route('supplier.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-white hover:bg-white/10 hover:text-white transition"
                    >
                        Supplier
                    </a>

                    <a
                    href="{{ route('rekening-kas-bank.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-white hover:bg-white/10 hover:text-white transition"
                    >
                        Kas & Rekening Bank
                    </a>

                    <a
                        href="{{ route('coa.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-white hover:bg-white/10 hover:text-white transition {{ request()->routeIs('coa.*') ? 'bg-white font-semibold text-[#0F8B8D] shadow-sm' : '' }}"
                    >
                        Chart of Accounts
                    </a>


                    {{-- USER MANAGEMENT --}}
                    @if(auth()->check() && in_array(auth()->user()->role, ['Owner', 'Admin'], true))
                        <a
                            href="{{ route('users.index') }}"
                            class="block w-full rounded-lg px-3 py-2 text-[13px] text-white hover:bg-white/10 hover:text-white transition {{ request()->routeIs('users.*') ? 'bg-white font-semibold text-[#0F8B8D] shadow-sm' : '' }}"
                        >
                            User Management
                        </a>
                    @endif


                </div>

            </details>



            {{-- ====================================================
                PENJUALAN
            ==================================================== --}}

            <details class="group mb-1" {{ request()->routeIs(['penjualan.*', 'invoice-penjualan.*', 'pembayaran-penjualan.*', 'retur-penjualan.*', 'piutang.*']) ? 'open' : '' }}>

                <summary
                    class="
                        flex w-full cursor-pointer
                        items-center justify-between
                        rounded-lg
                        px-3 py-2.5
                        text-sm font-medium
                        text-white
                        transition
                        hover:bg-white/10
                        hover:text-white
                    "
                >

                    <div class="flex items-center gap-3">

                        <x-icon name="shopping-cart" class="h-[18px] w-[18px] flex-shrink-0" />

                        <span>
                            Penjualan
                        </span>

                    </div>


                    <x-icon name="chevron-right" class="h-4 w-4 text-slate-400 transition-transform duration-200 group-open:rotate-90" />

                </summary>


                <div
                    class="
                        ml-[21px] mt-1
                        space-y-1
                        border-l border-white/10
                        pl-3
                    "
                >

                    <a
                        href="{{ route('penjualan.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-white hover:bg-white/10 hover:text-white transition {{ request()->routeIs('penjualan.*') ? 'bg-white font-semibold text-[#0F8B8D] shadow-sm' : '' }}"
                    >
                        Sales Order
                    </a>

                    <a
                        href="{{ route('invoice-penjualan.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-white hover:bg-white/10 hover:text-white transition {{ request()->routeIs('invoice-penjualan.*') ? 'bg-white font-semibold text-[#0F8B8D] shadow-sm' : '' }}"
                    >
                        Invoice Penjualan
                    </a>

                    <a
                        href="{{ route('pembayaran-penjualan.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-white hover:bg-white/10 hover:text-white transition {{ request()->routeIs('pembayaran-penjualan.*') ? 'bg-white font-semibold text-[#0F8B8D] shadow-sm' : '' }}"
                    >
                        Penerimaan Pembayaran
                    </a>

                    <a
                        href="{{ route('retur-penjualan.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-white hover:bg-white/10 hover:text-white transition {{ request()->routeIs('retur-penjualan.*') ? 'bg-white font-semibold text-[#0F8B8D] shadow-sm' : '' }}"
                    >
                        Retur Penjualan
                    </a>

                    <a
                        href="{{ route('piutang.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-white hover:bg-white/10 hover:text-white transition {{ request()->routeIs('piutang.*') && !request()->routeIs('piutang.kartu') ? 'bg-white font-semibold text-[#0F8B8D] shadow-sm' : '' }}"
                    >
                        Piutang Dagang (Daftar Tagihan)
                    </a>

                    <a
                        href="{{ route('piutang.kartu') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-white hover:bg-white/10 hover:text-white transition {{ request()->routeIs('piutang.kartu') ? 'bg-white font-semibold text-[#0F8B8D] shadow-sm' : '' }}"
                    >
                        Buku Pembantu Piutang
                    </a>

                </div>

            </details>



            {{-- ====================================================
                PEMBELIAN
            ==================================================== --}}

            <details class="group mb-1" {{ request()->routeIs('utang.*') ? 'open' : '' }}>

                <summary
                    class="
                        flex w-full cursor-pointer
                        items-center justify-between
                        rounded-lg
                        px-3 py-2.5
                        text-sm font-medium
                        text-white
                        transition
                        hover:bg-white/10
                        hover:text-white
                    "
                >

                    <div class="flex items-center gap-3">

                        <x-icon name="package-open" class="h-[18px] w-[18px] flex-shrink-0" />

                        <span>
                            Pembelian
                        </span>

                    </div>


                    <x-icon name="chevron-right" class="h-4 w-4 text-slate-400 transition-transform duration-200 group-open:rotate-90" />

                </summary>


                <div
                    class="
                        ml-[21px] mt-1
                        space-y-1
                        border-l border-white/10
                        pl-3
                    "
                >

                    <a
                    href="{{ route('purchase-order.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-white hover:bg-white/10 hover:text-white transition"
                    >
                        Purchase Order
                    </a>

                    <a
                    href="{{ route('penerimaan-barang.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-white hover:bg-white/10 hover:text-white transition"
                    >
                        Penerimaan Barang
                    </a>

                    <a
                    href="{{ route('invoice-supplier.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-white hover:bg-white/10 hover:text-white transition"
                    >
                        Invoice Supplier
                    </a>

                    <a
                    href="{{ route('pembayaran-supplier.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-white hover:bg-white/10 hover:text-white transition"
                    >
                        Pembayaran Supplier
                    </a>

                    <a
                    href="{{ route('retur-pembelian.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-white hover:bg-white/10 hover:text-white transition"
                    >
                        Retur Pembelian
                    </a>

                    <a
                        href="{{ route('utang.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-white transition hover:bg-white/10 hover:text-white {{ request()->routeIs('utang.index') ? 'bg-white font-bold text-[#0F8B8D] shadow-sm' : '' }}"
                    >
                        Utang
                    </a>

                    <a
                        href="{{ route('utang.kartu') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-white transition hover:bg-white/10 hover:text-white {{ request()->routeIs('utang.kartu') ? 'bg-white font-bold text-[#0F8B8D] shadow-sm' : '' }}"
                    >
                        Kartu Utang
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
                        text-white
                        transition
                        hover:bg-white/10
                        hover:text-white
                    "
                >

                    <div class="flex items-center gap-3">

                        <x-icon name="package" class="h-[18px] w-[18px] flex-shrink-0" />

                        <span>
                            Persediaan
                        </span>

                    </div>


                    <x-icon name="chevron-right" class="h-4 w-4 text-slate-400 transition-transform duration-200 group-open:rotate-90" />

                </summary>


                <div
                    class="
                        ml-[21px] mt-1
                        space-y-1
                        border-l border-white/10
                        pl-3
                    "
                >

                    <a
                    href="{{ route('stok-barang.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-white hover:bg-white/10 hover:text-white transition"
                    >
                        Stok Barang
                    </a>

                    <a
                    href="{{ route('kartu-stok.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-white hover:bg-white/10 hover:text-white transition"
                    >
                        Kartu Stok
                    </a>

                    <a
                    href="{{ route('mutasi-gudang.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-white hover:bg-white/10 hover:text-white transition"
                    >
                        Mutasi Gudang
                    </a>

                    <a
                    href="{{ route('stock-opname.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-white hover:bg-white/10 hover:text-white transition"
                    >
                        Stock Opname
                    </a>

                    <a
                    href="{{ route('penyesuaian-stok.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-white hover:bg-white/10 hover:text-white transition"
                    >
                        Penyesuaian Stok
                    </a>

                    <a
                        href="{{ route('conversions.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-white hover:bg-white/10 hover:text-white transition {{ request()->routeIs('conversions.*') ? 'bg-white font-semibold text-[#0F8B8D] shadow-sm' : '' }}"
                    >
                        Konversi Barang
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
                        text-white
                        transition
                        hover:bg-white/10
                        hover:text-white
                    "
                >

                    <div class="flex items-center gap-3">

                        <x-icon name="wallet" class="h-[18px] w-[18px] flex-shrink-0" />

                        <span>
                            Kas & Bank
                        </span>

                    </div>


                    <x-icon name="chevron-right" class="h-4 w-4 text-slate-400 transition-transform duration-200 group-open:rotate-90" />

                </summary>


                <div
                    class="
                        ml-[21px] mt-1
                        space-y-1
                        border-l border-white/10
                        pl-3
                    "
                >

                    <a
                    href="{{ route('uang-masuk.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-white hover:bg-white/10 hover:text-white transition"
                    >
                        Uang Masuk
                    </a>

                    <a
                    href="{{ route('uang-keluar.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-white hover:bg-white/10 hover:text-white transition"
                    >
                        Uang Keluar
                    </a>

                    <a
                    href="{{ route('transfer-kas-bank.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-white hover:bg-white/10 hover:text-white transition"
                    >
                        Transfer Antar Rekening
                    </a>

                    <a
                    href="{{ route('mutasi-kas-bank.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-white hover:bg-white/10 hover:text-white transition"
                    >
                        Mutasi Kas / Bank
                    </a>

                    <a
                    href="{{ route('rekonsiliasi-bank.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-white hover:bg-white/10 hover:text-white transition"
                    >
                        Rekonsiliasi Bank
                    </a>

                    <a
                    href="{{ route('transaksi-belum-teridentifikasi.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] leading-5 text-white hover:bg-white/10 hover:text-white transition"
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
                        text-white
                        transition
                        hover:bg-white/10
                        hover:text-white
                    "
                >

                    <div class="flex items-center gap-3">

                        <x-icon name="book-text" class="h-[18px] w-[18px] flex-shrink-0" />

                        <span>
                            Accounting
                        </span>

                    </div>


                    <x-icon name="chevron-right" class="h-4 w-4 text-slate-400 transition-transform duration-200 group-open:rotate-90" />

                </summary>


                <div
                    class="
                        ml-[21px] mt-1
                        space-y-1
                        border-l border-white/10
                        pl-3
                    "
                >

                    <a
                        href="{{ route('jurnal-umum.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-white hover:bg-white/10 hover:text-white transition {{ request()->routeIs('jurnal-umum.*') ? 'bg-white font-semibold text-[#0F8B8D] shadow-sm' : '' }}"
                    >
                        Jurnal Umum
                    </a>

                    <a
                        href="{{ route('buku-besar.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-white hover:bg-white/10 hover:text-white transition {{ request()->routeIs('buku-besar.*') ? 'bg-white font-semibold text-[#0F8B8D] shadow-sm' : '' }}"
                    >
                        Buku Besar
                    </a>

                    <a
                        href="{{ route('laporan.neraca-saldo') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-white hover:bg-white/10 hover:text-white transition {{ request()->routeIs('laporan.neraca-saldo') ? 'bg-white font-semibold text-[#0F8B8D] shadow-sm' : '' }}"
                    >
                        Neraca Saldo
                    </a>

                    <div class="my-1 border-t border-white/10"></div>

                    <a
                        href="{{ route('pengaturan-akun.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-white hover:bg-white/10 hover:text-white transition {{ request()->routeIs('pengaturan-akun.*') ? 'bg-white font-semibold text-[#0F8B8D] shadow-sm' : '' }}"
                    >
                        Pengaturan Akun Sistem
                    </a>

                    <a
                        href="{{ route('penutupan-periode.index') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-white hover:bg-white/10 hover:text-white transition {{ request()->routeIs('penutupan-periode.*') ? 'bg-white font-semibold text-[#0F8B8D] shadow-sm' : '' }}"
                    >
                        Cut-Off (Penutupan Periode)
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
                        text-white
                        transition
                        hover:bg-white/10
                        hover:text-white
                    "
                >

                    <div class="flex items-center gap-3">

                        <x-icon name="chart-no-axes-column" class="h-[18px] w-[18px] flex-shrink-0" />

                        <span>
                            Laporan
                        </span>

                    </div>


                    <x-icon name="chevron-right" class="h-4 w-4 text-slate-400 transition-transform duration-200 group-open:rotate-90" />

                </summary>


                <div
                    class="
                        ml-[21px] mt-1
                        space-y-1
                        border-l border-white/10
                        pl-3
                    "
                >

                    <a
                        href="{{ route('laporan.penjualan') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-white hover:bg-white/10 hover:text-white transition {{ request()->routeIs('laporan.penjualan') ? 'bg-white font-semibold text-[#0F8B8D] shadow-sm' : '' }}"
                    >
                        Penjualan
                    </a>

                    <a
                    href="{{ route('laporan.pembelian') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-white hover:bg-white/10 hover:text-white transition"
                    >
                        Pembelian
                    </a>

                    <a
                    href="{{ route('laporan.persediaan') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-white hover:bg-white/10 hover:text-white transition"
                    >
                        Persediaan
                    </a>

                    <a
                        href="{{ route('laporan.piutang') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-white hover:bg-white/10 hover:text-white transition {{ request()->routeIs('laporan.piutang') ? 'bg-white font-semibold text-[#0F8B8D] shadow-sm' : '' }}"
                    >
                        Piutang
                    </a>

                    <a
                    href="{{ route('laporan.utang') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-white hover:bg-white/10 hover:text-white transition"
                    >
                        Utang
                    </a>

                    <a
                        href="#"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-white hover:bg-white/10 hover:text-white transition"
                    >
                        Kas & Bank
                    </a>


                    <div class="my-2 border-t border-white/10"></div>


                    <a
                        href="{{ route('laporan.laba-rugi') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-white hover:bg-white/10 hover:text-white transition {{ request()->routeIs('laporan.laba-rugi') ? 'bg-white font-semibold text-[#0F8B8D] shadow-sm' : '' }}"
                    >
                        Laba Rugi
                    </a>

                    <a
                        href="{{ route('laporan.neraca') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-white hover:bg-white/10 hover:text-white transition {{ request()->routeIs('laporan.neraca') ? 'bg-white font-semibold text-[#0F8B8D] shadow-sm' : '' }}"
                    >
                        Posisi Keuangan (Neraca)
                    </a>

                    <a
                        href="{{ route('laporan.arus-kas') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-white hover:bg-white/10 hover:text-white transition {{ request()->routeIs('laporan.arus-kas') ? 'bg-white font-semibold text-[#0F8B8D] shadow-sm' : '' }}"
                    >
                        Arus Kas
                    </a>

                    <a
                        href="{{ route('laporan.perubahan-ekuitas') }}"
                        class="block w-full rounded-lg px-3 py-2 text-[13px] text-white hover:bg-white/10 hover:text-white transition {{ request()->routeIs('laporan.perubahan-ekuitas') ? 'bg-white font-semibold text-[#0F8B8D] shadow-sm' : '' }}"
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
                border-t border-white/10
                bg-[#0B7375]
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
                        bg-white
                        text-sm font-semibold
                        text-[#0F8B8D]
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
                            text-white
                        "
                    >
                        {{ auth()->user()->name }}
                    </div>

                    <div class="text-xs text-white/90">
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
                            text-white/90
                            transition
                            hover:bg-white/10
                            hover:text-white
                        "
                    >

                        <x-icon name="log-out" class="h-[17px] w-[17px]" />

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
                border-t-2 border-t-[#32AAA6]/70
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

                    <x-icon name="calendar-days" class="h-4 w-4 text-slate-400" />


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

                    <x-icon name="bell" class="h-[18px] w-[18px]" />


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

