@php
    $user = auth()->user();
    $isPrivilegedRole = in_array($user?->role, ['Owner', 'Admin'], true);

    $navLinkClass = 'block w-full rounded-lg px-3 py-2 text-[13px] text-white transition hover:bg-white/10 hover:text-white';
    $navActiveClass = 'bg-white font-semibold text-[#0F8B8D] shadow-sm';
    $navSummaryClass = 'flex w-full cursor-pointer items-center justify-between rounded-lg px-3 py-2.5 text-sm font-medium text-white transition hover:bg-white/10 hover:text-white';
    $navChildrenClass = 'ml-[21px] mt-1 space-y-1 border-l border-white/10 pl-3';
@endphp

<nav class="flex-1 overflow-y-auto px-3 py-4 [scrollbar-color:rgba(255,255,255,0.24)_transparent]">
    {{-- DASHBOARD --}}
    @if($user?->canAccessModule('dashboard'))
        <a
            href="{{ route('dashboard') }}"
            class="mb-2 flex w-full items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs('dashboard') ? 'border-l-[3px] border-[#15235F] bg-white text-[#0F8B8D] shadow-sm' : 'text-white hover:bg-white/10 hover:text-white' }}"
        >
            <x-icon name="home" class="h-[18px] w-[18px] flex-shrink-0" />
            <span>Dashboard</span>
        </a>
    @endif

    {{-- MASTER DATA: hanya Owner dan Admin --}}
    @if($isPrivilegedRole)
        <details class="group mb-1" {{ request()->routeIs(['profil-perusahaan.*', 'pengaturan-penomoran.*', 'barang.*', 'kategori-barang.*', 'satuan.*', 'gudang.*', 'customer.*', 'supplier.*', 'rekening-kas-bank.*', 'coa.*', 'users.*']) ? 'open' : '' }}>
            <summary class="{{ $navSummaryClass }}">
                <span class="flex items-center gap-3">
                    <x-icon name="database" class="h-[18px] w-[18px] flex-shrink-0" />
                    <span>Master Data</span>
                </span>
                <x-icon name="chevron-right" class="h-4 w-4 text-white/80 transition-transform duration-200 group-open:rotate-90" />
            </summary>

            <div class="{{ $navChildrenClass }}">
                <a href="{{ route('profil-perusahaan.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('profil-perusahaan.*') ? $navActiveClass : '' }}">Profil Perusahaan</a>
                <a href="{{ route('pengaturan-penomoran.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('pengaturan-penomoran.*') ? $navActiveClass : '' }}">Pengaturan Penomoran</a>
                <a href="{{ route('barang.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('barang.*') ? $navActiveClass : '' }}">Barang</a>
                <a href="{{ route('kategori-barang.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('kategori-barang.*') ? $navActiveClass : '' }}">Kategori Barang</a>
                <a href="{{ route('satuan.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('satuan.*') ? $navActiveClass : '' }}">Satuan</a>
                <a href="{{ route('gudang.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('gudang.*') ? $navActiveClass : '' }}">Gudang</a>
                <a href="{{ route('customer.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('customer.*') ? $navActiveClass : '' }}">Customer</a>
                <a href="{{ route('supplier.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('supplier.*') ? $navActiveClass : '' }}">Supplier</a>
                <a href="{{ route('rekening-kas-bank.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('rekening-kas-bank.*') ? $navActiveClass : '' }}">Kas & Rekening Bank</a>
                <a href="{{ route('coa.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('coa.*') ? $navActiveClass : '' }}">Chart of Accounts</a>
                <div class="my-1 border-t border-white/10"></div>
                <a href="{{ route('users.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('users.*') ? $navActiveClass : '' }}">User Management</a>
            </div>
        </details>
    @endif

    {{-- PENJUALAN --}}
    @if($user?->canAccessModule('sales'))
        <details class="group mb-1" {{ request()->routeIs(['penjualan.*', 'invoice-penjualan.*', 'pembayaran-penjualan.*', 'retur-penjualan.*']) ? 'open' : '' }}>
            <summary class="{{ $navSummaryClass }}">
                <span class="flex items-center gap-3">
                    <x-icon name="shopping-cart" class="h-[18px] w-[18px] flex-shrink-0" />
                    <span>Penjualan</span>
                </span>
                <x-icon name="chevron-right" class="h-4 w-4 text-white/80 transition-transform duration-200 group-open:rotate-90" />
            </summary>

            <div class="{{ $navChildrenClass }}">
                <a href="{{ route('penjualan.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('penjualan.*') ? $navActiveClass : '' }}">Sales Order</a>
                <a href="{{ route('invoice-penjualan.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('invoice-penjualan.*') ? $navActiveClass : '' }}">Invoice Penjualan</a>
                <a href="{{ route('pembayaran-penjualan.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('pembayaran-penjualan.*') ? $navActiveClass : '' }}">Penerimaan Pembayaran</a>
                <a href="{{ route('retur-penjualan.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('retur-penjualan.*') ? $navActiveClass : '' }}">Retur Penjualan</a>
                @unless($isPrivilegedRole)
                    <div class="my-1 border-t border-white/10"></div>
                    <a href="{{ route('customer.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('customer.*') ? $navActiveClass : '' }}">Data Customer</a>
                @endunless
            </div>
        </details>
    @endif

    {{-- PEMBELIAN --}}
    @if($user?->canAccessModule('purchases'))
        <details class="group mb-1" {{ request()->routeIs(['purchase-order.*', 'penerimaan-barang.*', 'invoice-supplier.*', 'pembayaran-supplier.*', 'pembayaran-po-supplier.*', 'retur-pembelian.*', 'purchase-order-item.*', 'permintaan-pembelian.*']) ? 'open' : '' }}>
            <summary class="{{ $navSummaryClass }}">
                <span class="flex items-center gap-3">
                    <x-icon name="package-open" class="h-[18px] w-[18px] flex-shrink-0" />
                    <span>Pembelian</span>
                </span>
                <x-icon name="chevron-right" class="h-4 w-4 text-white/80 transition-transform duration-200 group-open:rotate-90" />
            </summary>

            <div class="{{ $navChildrenClass }}">
                <a href="{{ route('purchase-order.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('purchase-order.*') ? $navActiveClass : '' }}">Purchase Order</a>
                @if(in_array($user?->role, ['Pembelian', 'Admin', 'Owner'], true))
                    @php($unreadPurchaseRequestCount = $user->unreadNotifications()->where('type', \App\Notifications\PermintaanPembelianNotification::class)->count())
                    <a href="{{ route('permintaan-pembelian.index') }}" class="{{ $navLinkClass }} flex items-center justify-between {{ request()->routeIs('permintaan-pembelian.*') ? $navActiveClass : '' }}">
                        <span>Permintaan dari Gudang</span>
                        @if($unreadPurchaseRequestCount > 0)<span class="rounded-full bg-rose-500 px-2 py-0.5 text-[10px] font-bold text-white">{{ $unreadPurchaseRequestCount }}</span>@endif
                    </a>
                @endif
                <a href="{{ route('penerimaan-barang.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('penerimaan-barang.*') ? $navActiveClass : '' }}">Penerimaan Barang</a>
                <a href="{{ route('invoice-supplier.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('invoice-supplier.*') ? $navActiveClass : '' }}">Invoice Supplier</a>
                <a href="{{ route('pembayaran-supplier.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('pembayaran-supplier.*', 'pembayaran-po-supplier.*') ? $navActiveClass : '' }}">Pembayaran Supplier</a>
                <a href="{{ route('retur-pembelian.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('retur-pembelian.*') ? $navActiveClass : '' }}">Retur Pembelian</a>
                @unless($isPrivilegedRole)
                    <div class="my-1 border-t border-white/10"></div>
                    <a href="{{ route('supplier.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('supplier.*') ? $navActiveClass : '' }}">Data Supplier</a>
                @endunless
            </div>
        </details>
    @endif

    {{-- PERSEDIAAN / GUDANG --}}
    @if($user?->canAccessModule('inventory'))
        <details class="group mb-1" {{ request()->routeIs(['barang.*', 'kategori-barang.*', 'satuan.*', 'gudang.*', 'stok-barang.*', 'kartu-stok.*', 'mutasi-gudang.*', 'stock-opname.*', 'penyesuaian-stok.*', 'conversions.*', 'permintaan-pembelian.*']) ? 'open' : '' }}>
            <summary class="{{ $navSummaryClass }}">
                <span class="flex items-center gap-3">
                    <x-icon name="package" class="h-[18px] w-[18px] flex-shrink-0" />
                    <span>Persediaan</span>
                </span>
                <x-icon name="chevron-right" class="h-4 w-4 text-white/80 transition-transform duration-200 group-open:rotate-90" />
            </summary>

            <div class="{{ $navChildrenClass }}">
                @unless($isPrivilegedRole)
                    <a href="{{ route('barang.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('barang.*') ? $navActiveClass : '' }}">Data Barang</a>
                    <a href="{{ route('kategori-barang.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('kategori-barang.*') ? $navActiveClass : '' }}">Kategori Barang</a>
                    <a href="{{ route('satuan.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('satuan.*') ? $navActiveClass : '' }}">Satuan</a>
                    <a href="{{ route('gudang.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('gudang.*') ? $navActiveClass : '' }}">Data Gudang</a>
                    <div class="my-1 border-t border-white/10"></div>
                @endunless
                <a href="{{ route('stok-barang.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('stok-barang.*') ? $navActiveClass : '' }}">Stok Barang</a>
                @if($user?->role === 'Gudang')
                    @php($unreadPurchaseRequestCount = $user->unreadNotifications()->where('type', \App\Notifications\PermintaanPembelianNotification::class)->count())
                    <a href="{{ route('permintaan-pembelian.index') }}" class="{{ $navLinkClass }} flex items-center justify-between {{ request()->routeIs('permintaan-pembelian.*') ? $navActiveClass : '' }}">
                        <span>Permintaan Pembelian</span>
                        @if($unreadPurchaseRequestCount > 0)<span class="rounded-full bg-rose-500 px-2 py-0.5 text-[10px] font-bold text-white">{{ $unreadPurchaseRequestCount }}</span>@endif
                    </a>
                @endif
                <a href="{{ route('kartu-stok.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('kartu-stok.*') ? $navActiveClass : '' }}">Kartu Stok</a>
                <a href="{{ route('mutasi-gudang.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('mutasi-gudang.*') ? $navActiveClass : '' }}">Mutasi Gudang</a>
                <a href="{{ route('stock-opname.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('stock-opname.*') ? $navActiveClass : '' }}">Stock Opname</a>
                <a href="{{ route('penyesuaian-stok.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('penyesuaian-stok.*') ? $navActiveClass : '' }}">Penyesuaian Stok</a>
                <a href="{{ route('conversions.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('conversions.*') ? $navActiveClass : '' }}">Konversi Barang</a>
            </div>
        </details>
    @endif

    {{-- PIUTANG DAN UTANG --}}
    @if($user?->canAccessModule('receivables') || $user?->canAccessModule('payables'))
        <details class="group mb-1" {{ request()->routeIs(['piutang.*', 'utang.*']) ? 'open' : '' }}>
            <summary class="{{ $navSummaryClass }}">
                <span class="flex items-center gap-3">
                    <x-icon name="clipboard-list" class="h-[18px] w-[18px] flex-shrink-0" />
                    <span>Piutang & Utang</span>
                </span>
                <x-icon name="chevron-right" class="h-4 w-4 text-white/80 transition-transform duration-200 group-open:rotate-90" />
            </summary>

            <div class="{{ $navChildrenClass }}">
                @if($user->canAccessModule('receivables'))
                    <a href="{{ route('piutang.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('piutang.index', 'piutang.show', 'piutang.surat-*') ? $navActiveClass : '' }}">Daftar Piutang</a>
                    <a href="{{ route('piutang.kartu') }}" class="{{ $navLinkClass }} {{ request()->routeIs('piutang.kartu') ? $navActiveClass : '' }}">Kartu Piutang</a>
                    <a href="{{ route('piutang.penerimaan.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('piutang.penerimaan.*') ? $navActiveClass : '' }}">Penerimaan Piutang</a>
                @endif
                @if($user->canAccessModule('payables'))
                    @if($user->canAccessModule('receivables'))
                        <div class="my-1 border-t border-white/10"></div>
                    @endif
                    <a href="{{ route('utang.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('utang.index') ? $navActiveClass : '' }}">Daftar Utang</a>
                    <a href="{{ route('utang.kartu') }}" class="{{ $navLinkClass }} {{ request()->routeIs('utang.kartu') ? $navActiveClass : '' }}">Kartu Utang</a>
                @endif
            </div>
        </details>
    @endif

    {{-- KAS DAN BANK --}}
    @if($user?->canAccessModule('cashbank'))
        <details class="group mb-1" {{ request()->routeIs(['rekening-kas-bank.*', 'uang-masuk.*', 'uang-keluar.*', 'transfer-kas-bank.*', 'mutasi-kas-bank.*', 'rekonsiliasi-bank.*', 'rekening-koran-import.*', 'transaksi-belum-teridentifikasi.*']) ? 'open' : '' }}>
            <summary class="{{ $navSummaryClass }}">
                <span class="flex items-center gap-3">
                    <x-icon name="wallet" class="h-[18px] w-[18px] flex-shrink-0" />
                    <span>Kas & Bank</span>
                </span>
                <x-icon name="chevron-right" class="h-4 w-4 text-white/80 transition-transform duration-200 group-open:rotate-90" />
            </summary>

            <div class="{{ $navChildrenClass }}">
                @unless($isPrivilegedRole)
                    <a href="{{ route('rekening-kas-bank.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('rekening-kas-bank.*') ? $navActiveClass : '' }}">Rekening Kas & Bank</a>
                @endunless
                <a href="{{ route('uang-masuk.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('uang-masuk.*') ? $navActiveClass : '' }}">Uang Masuk</a>
                <a href="{{ route('uang-keluar.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('uang-keluar.*') ? $navActiveClass : '' }}">Uang Keluar</a>
                <a href="{{ route('transfer-kas-bank.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('transfer-kas-bank.*') ? $navActiveClass : '' }}">Transfer Antar Rekening</a>
                <a href="{{ route('mutasi-kas-bank.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('mutasi-kas-bank.*') ? $navActiveClass : '' }}">Mutasi Kas / Bank</a>
                <a href="{{ route('rekonsiliasi-bank.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('rekonsiliasi-bank.*') ? $navActiveClass : '' }}">Rekonsiliasi Bank</a>
                <a href="{{ route('rekening-koran-import.create') }}" class="{{ $navLinkClass }} {{ request()->routeIs('rekening-koran-import.*') ? $navActiveClass : '' }}">Import Rekening Koran</a>
                <a href="{{ route('transaksi-belum-teridentifikasi.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('transaksi-belum-teridentifikasi.*') ? $navActiveClass : '' }}">Transaksi Belum Teridentifikasi</a>
            </div>
        </details>
    @endif

    {{-- ACCOUNTING --}}
    @if($user?->canAccessModule('accounting'))
        <details class="group mb-1" {{ request()->routeIs(['coa.*', 'jurnal-umum.*', 'buku-besar.*', 'pengaturan-akun.*', 'penutupan-periode.*', 'laporan.neraca-saldo']) ? 'open' : '' }}>
            <summary class="{{ $navSummaryClass }}">
                <span class="flex items-center gap-3">
                    <x-icon name="book-text" class="h-[18px] w-[18px] flex-shrink-0" />
                    <span>Accounting</span>
                </span>
                <x-icon name="chevron-right" class="h-4 w-4 text-white/80 transition-transform duration-200 group-open:rotate-90" />
            </summary>

            <div class="{{ $navChildrenClass }}">
                @unless($isPrivilegedRole)
                    <a href="{{ route('coa.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('coa.*') ? $navActiveClass : '' }}">Chart of Accounts</a>
                @endunless
                <a href="{{ route('jurnal-umum.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('jurnal-umum.*') ? $navActiveClass : '' }}">Jurnal Umum</a>
                <a href="{{ route('buku-besar.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('buku-besar.*') ? $navActiveClass : '' }}">Buku Besar</a>
                <a href="{{ route('laporan.neraca-saldo') }}" class="{{ $navLinkClass }} {{ request()->routeIs('laporan.neraca-saldo') ? $navActiveClass : '' }}">Neraca Saldo</a>
                @if($user->canAccessModule('accounting.settings'))
                    <div class="my-1 border-t border-white/10"></div>
                    <a href="{{ route('pengaturan-akun.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('pengaturan-akun.*') ? $navActiveClass : '' }}">Pengaturan Akun Sistem</a>
                @endif
                @if($user->canAccessModule('accounting.period'))
                    <a href="{{ route('penutupan-periode.index') }}" class="{{ $navLinkClass }} {{ request()->routeIs('penutupan-periode.*') ? $navActiveClass : '' }}">Cut-Off (Penutupan Periode)</a>
                @endif
            </div>
        </details>
    @endif

    {{-- LAPORAN --}}
    @if($user?->canAccessModule('reports'))
        <details class="group mb-1" {{ request()->routeIs('laporan.*') ? 'open' : '' }}>
            <summary class="{{ $navSummaryClass }}">
                <span class="flex items-center gap-3">
                    <x-icon name="chart-no-axes-column" class="h-[18px] w-[18px] flex-shrink-0" />
                    <span>Laporan</span>
                </span>
                <x-icon name="chevron-right" class="h-4 w-4 text-white/80 transition-transform duration-200 group-open:rotate-90" />
            </summary>

            <div class="{{ $navChildrenClass }}">
                @if($user->canAccessModule('reports.sales'))
                    <a href="{{ route('laporan.penjualan') }}" class="{{ $navLinkClass }} {{ request()->routeIs('laporan.penjualan*') ? $navActiveClass : '' }}">Penjualan</a>
                @endif
                @if($user->canAccessModule('reports.purchases'))
                    <a href="{{ route('laporan.pembelian') }}" class="{{ $navLinkClass }} {{ request()->routeIs('laporan.pembelian*') ? $navActiveClass : '' }}">Pembelian</a>
                @endif
                @if($user->canAccessModule('reports.inventory'))
                    <a href="{{ route('laporan.persediaan') }}" class="{{ $navLinkClass }} {{ request()->routeIs('laporan.persediaan*') ? $navActiveClass : '' }}">Persediaan</a>
                @endif
                @if($user->canAccessModule('reports.receivables'))
                    <a href="{{ route('laporan.piutang') }}" class="{{ $navLinkClass }} {{ request()->routeIs('laporan.piutang*') ? $navActiveClass : '' }}">Piutang</a>
                @endif
                @if($user->canAccessModule('reports.payables'))
                    <a href="{{ route('laporan.utang') }}" class="{{ $navLinkClass }} {{ request()->routeIs('laporan.utang*') ? $navActiveClass : '' }}">Utang</a>
                @endif
                @if($user->canAccessModule('reports.financial'))
                    <div class="my-1 border-t border-white/10"></div>
                    <a href="{{ route('laporan.laba-rugi') }}" class="{{ $navLinkClass }} {{ request()->routeIs('laporan.laba-rugi*') ? $navActiveClass : '' }}">Laba Rugi</a>
                    <a href="{{ route('laporan.neraca') }}" class="{{ $navLinkClass }} {{ request()->routeIs('laporan.neraca*') ? $navActiveClass : '' }}">Posisi Keuangan (Neraca)</a>
                    <a href="{{ route('laporan.arus-kas') }}" class="{{ $navLinkClass }} {{ request()->routeIs('laporan.arus-kas*') ? $navActiveClass : '' }}">Arus Kas</a>
                    <a href="{{ route('laporan.perubahan-ekuitas') }}" class="{{ $navLinkClass }} {{ request()->routeIs('laporan.perubahan-ekuitas*') ? $navActiveClass : '' }}">Perubahan Ekuitas</a>
                @endif
            </div>
        </details>
    @endif
</nav>
