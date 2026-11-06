<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\KategoriBarangController;
use App\Http\Controllers\SatuanController;
use App\Http\Controllers\GudangController;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\BarangImportController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PermintaanPembelianController;
use App\Http\Controllers\PenerimaanBarangController;
use App\Http\Controllers\StokBarangController;
use App\Http\Controllers\KartuStokController;
use App\Http\Controllers\MutasiGudangController;
use App\Http\Controllers\StockOpnameController;
use App\Http\Controllers\PenyesuaianStokController;
use App\Http\Controllers\ConversionController;
use App\Http\Controllers\InvoiceSupplierController;
use App\Http\Controllers\UtangController;
use App\Http\Controllers\PembayaranSupplierController;
use App\Http\Controllers\PembayaranPOSupplierController;
use App\Http\Controllers\ReturPembelianController;
use App\Http\Controllers\LaporanPembelianController;
use App\Http\Controllers\LaporanPersediaanController;
use App\Http\Controllers\LaporanUtangController;
use App\Http\Controllers\RekeningKasBankController;
use App\Http\Controllers\UangMasukController;
use App\Http\Controllers\UangKeluarController;
use App\Http\Controllers\TransferKasBankController;
use App\Http\Controllers\MutasiKasBankController;
use App\Http\Controllers\TransaksiBelumTeridentifikasiController;
use App\Http\Controllers\RekonsiliasiBankController;
use App\Http\Controllers\ImportRekeningKoranController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\PenjualanController;
use App\Http\Controllers\InvoicePenjualanController;
use App\Http\Controllers\PembayaranPenjualanController;
use App\Http\Controllers\ReturPenjualanController;
use App\Http\Controllers\PiutangController;
use App\Http\Controllers\CoaController;
use App\Http\Controllers\JurnalUmumController;
use App\Http\Controllers\BukuBesarController;
use App\Http\Controllers\LaporanKeuanganController;
use App\Http\Controllers\LaporanPenjualanController;
use App\Http\Controllers\LaporanPiutangController;
use App\Http\Controllers\PengaturanAkunController;
use App\Http\Controllers\PenutupanPeriodeController;
use App\Http\Controllers\CustomerImportController;
use App\Http\Controllers\PenerimaanPiutangController;
use App\Http\Controllers\KartuPiutangController;
use App\Http\Controllers\KartuUtangController;
use App\Http\Controllers\PengaturanPenomoranController;
use App\Http\Controllers\ProfilPerusahaanController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\UserController;


/*
|--------------------------------------------------------------------------
| PUBLIC
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return view('welcome');
});


/*
|--------------------------------------------------------------------------
| DASHBOARD
|--------------------------------------------------------------------------
*/

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'user.active', 'module.access'])
    ->name('dashboard');


/*
|--------------------------------------------------------------------------
| AUTHENTICATED ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'user.active', 'module.access'])->group(function () {

    /*
    |--------------------------------------------------------------------------
    | USER MANAGEMENT (Owner/Admin only)
    |--------------------------------------------------------------------------
    */

    Route::middleware('role:Owner,Admin')->prefix('users')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::get('/create', [UserController::class, 'create'])->name('create');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::get('/{user}/edit', [UserController::class, 'edit'])->name('edit');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
    });


    /*
    |--------------------------------------------------------------------------
    | PROFILE
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/profile',
        [
            ProfileController::class,
            'edit',
        ]
    )->name('profile.edit');

    Route::patch(
        '/profile',
        [
            ProfileController::class,
            'update',
        ]
    )->name('profile.update');

    Route::delete(
        '/profile',
        [
            ProfileController::class,
            'destroy',
        ]
    )->name('profile.destroy');

    /*
    |--------------------------------------------------------------------------
    | MASTER DATA
    |--------------------------------------------------------------------------
    */

    Route::get(
        'profil-perusahaan',
        [
            ProfilPerusahaanController::class,
            'index',
        ]
    )->name('profil-perusahaan.index');

    Route::put(
        'profil-perusahaan',
        [
            ProfilPerusahaanController::class,
            'update',
        ]
    )->name('profil-perusahaan.update');


    Route::resource(
        'kategori-barang',
        KategoriBarangController::class
    )->except('show');


    Route::resource(
        'satuan',
        SatuanController::class
    )->except('show');


    Route::resource(
        'gudang',
        GudangController::class
    )->except('show');


    Route::resource(
        'barang',
        BarangController::class
    )->except('show');


    /*
    |--------------------------------------------------------------------------
    | IMPORT MASTER BARANG EXCEL
    |--------------------------------------------------------------------------
    */

    Route::get(
        'barang/import',
        [
            BarangImportController::class,
            'create',
        ]
    )->name('barang.import.create');


    Route::post(
        'barang/import/preview',
        [
            BarangImportController::class,
            'preview',
        ]
    )->name('barang.import.preview');


    Route::post(
        'barang/import/process',
        [
            BarangImportController::class,
            'import',
        ]
    )->name('barang.import.process');


    Route::post(
        'barang/import/cancel',
        [
            BarangImportController::class,
            'cancel',
        ]
    )->name('barang.import.cancel');


    Route::resource(
        'customer',
        CustomerController::class
    )->except('show');


    Route::get(
        'supplier/{supplier}/barang',
        [SupplierController::class, 'barang']
    )->name('supplier.barang.index');

    Route::post(
        'supplier/{supplier}/barang/{barang}',
        [SupplierController::class, 'attachBarang']
    )->name('supplier.barang.attach');

    Route::delete(
        'supplier/{supplier}/barang/{barang}',
        [SupplierController::class, 'detachBarang']
    )->name('supplier.barang.detach');

    Route::resource(
        'supplier',
        SupplierController::class
    )->except('show');


    /*
    |--------------------------------------------------------------------------
    | PENJUALAN
    |--------------------------------------------------------------------------
    */


    /*
    |--------------------------------------------------------------------------
    | SALES ORDER / PENJUALAN
    |--------------------------------------------------------------------------
    */

    Route::get(
        'penjualan/{penjualan}/pdf',
        [
            PenjualanController::class,
            'pdf',
        ]
    )->name('penjualan.pdf');


    Route::post(
        'penjualan/{penjualan}/konfirmasi',
        [
            PenjualanController::class,
            'konfirmasi',
        ]
    )->name('penjualan.konfirmasi');


    Route::post(
        'penjualan/{penjualan}/batalkan',
        [
            PenjualanController::class,
            'batalkan',
        ]
    )->name('penjualan.batalkan');


    Route::resource(
        'penjualan',
        PenjualanController::class
    );


    /*
    |--------------------------------------------------------------------------
    | INVOICE PENJUALAN
    |--------------------------------------------------------------------------
    */

    Route::get(
        'invoice-penjualan/{invoicePenjualan}/pdf',
        [
            InvoicePenjualanController::class,
            'pdf',
        ]
    )->name('invoice-penjualan.pdf');


    /*
    |--------------------------------------------------------------------------
    | KIRIM EMAIL INVOICE
    |--------------------------------------------------------------------------
    */

    Route::post(
        'invoice-penjualan/{invoicePenjualan}/kirim-email',
        [
            InvoicePenjualanController::class,
            'kirimEmail',
        ]
    )->name('invoice-penjualan.kirim-email');

    Route::post(
        'invoice-penjualan/{invoicePenjualan}/konfirmasi-pembayaran',
        [
            InvoicePenjualanController::class,
            'konfirmasiPembayaran',
        ]
    )->name('invoice-penjualan.konfirmasi-pembayaran');


    Route::resource(
        'invoice-penjualan',
        InvoicePenjualanController::class
    );


    /*
    |--------------------------------------------------------------------------
    | PEMBAYARAN PENJUALAN
    |--------------------------------------------------------------------------
    */

    Route::resource(
        'pembayaran-penjualan',
        PembayaranPenjualanController::class
    )->except([
        'edit',
        'update',
        'destroy',
    ]);


    /*
    |--------------------------------------------------------------------------
    | RETUR PENJUALAN
    |--------------------------------------------------------------------------
    */

    Route::resource(
        'retur-penjualan',
        ReturPenjualanController::class
    )->except([
        'edit',
        'update',
        'destroy',
    ]);


    /*
    |--------------------------------------------------------------------------
    | PIUTANG
    |--------------------------------------------------------------------------
    */

    Route::get(
        'piutang',
        [
            PiutangController::class,
            'index',
        ]
    )->name('piutang.index');


    /*
    |--------------------------------------------------------------------------
    | KAS & REKENING BANK
    |--------------------------------------------------------------------------
    */

    Route::resource(
        'rekening-kas-bank',
        RekeningKasBankController::class
    )->except('show');


    /*
    |--------------------------------------------------------------------------
    | PEMBELIAN
    |--------------------------------------------------------------------------
    */


    /*
    |--------------------------------------------------------------------------
    | PERMINTAAN PEMBELIAN DARI GUDANG
    |--------------------------------------------------------------------------
    */

    Route::prefix('permintaan-pembelian')->name('permintaan-pembelian.')->group(function () {
        Route::middleware('role:Gudang,Pembelian,Admin,Owner')->group(function () {
            Route::get('/', [PermintaanPembelianController::class, 'index'])->name('index');
            Route::get('/{permintaanPembelian}', [PermintaanPembelianController::class, 'show'])->name('show');
        });

        Route::middleware('role:Gudang,Admin,Owner')->group(function () {
            Route::get('/buat/baru', [PermintaanPembelianController::class, 'create'])->name('create');
            Route::post('/', [PermintaanPembelianController::class, 'store'])->name('store');
        });

        Route::middleware('role:Pembelian,Admin,Owner')->group(function () {
            Route::patch('/{permintaanPembelian}/setujui', [PermintaanPembelianController::class, 'approve'])->name('approve');
            Route::patch('/{permintaanPembelian}/tolak', [PermintaanPembelianController::class, 'reject'])->name('reject');
        });
    });


    /*
    |--------------------------------------------------------------------------
    | PURCHASE ORDER
    |--------------------------------------------------------------------------
    */

    Route::get(
        'purchase-order/{purchaseOrder}/pdf',
        [
            PurchaseOrderController::class,
            'pdf',
        ]
    )->name('purchase-order.pdf');


    Route::patch(
        'purchase-order/{purchaseOrder}/kirim',
        [
            PurchaseOrderController::class,
            'kirim',
        ]
    )->name('purchase-order.kirim');


    Route::get(
        'purchase-order/supplier/{supplier}/barangs',
        [PurchaseOrderController::class, 'barangsBySupplier']
    )->name('purchase-order.supplier-barangs');

    Route::resource(
        'purchase-order',
        PurchaseOrderController::class
    );


    /*
    |--------------------------------------------------------------------------
    | PEMBAYARAN PO SUPPLIER
    |--------------------------------------------------------------------------
    */

    Route::get(
        'purchase-order/{purchaseOrder}/pembayaran/create',
        [
            PembayaranPOSupplierController::class,
            'create',
        ]
    )->name(
        'pembayaran-po-supplier.create'
    );


    Route::post(
        'purchase-order/{purchaseOrder}/pembayaran',
        [
            PembayaranPOSupplierController::class,
            'store',
        ]
    )->name(
        'pembayaran-po-supplier.store'
    );


    Route::get(
        'pembayaran-po-supplier/{pembayaranPOSupplier}',
        [
            PembayaranPOSupplierController::class,
            'show',
        ]
    )->name(
        'pembayaran-po-supplier.show'
    );


    Route::post(
        'pembayaran-po-supplier/{pembayaranPOSupplier}/catat',
        [
            PembayaranPOSupplierController::class,
            'catat',
        ]
    )->name(
        'pembayaran-po-supplier.catat'
    );


    /*
    |--------------------------------------------------------------------------
    | PEMBATALAN SISA PURCHASE ORDER
    |--------------------------------------------------------------------------
    */

    Route::patch(
        'purchase-order-item/{purchaseOrderItem}/batalkan-sisa',
        [
            PenerimaanBarangController::class,
            'batalkanSisa',
        ]
    )->name('purchase-order-item.batalkan-sisa');


    /*
    |--------------------------------------------------------------------------
    | PENERIMAAN BARANG
    |--------------------------------------------------------------------------
    */

    Route::resource(
        'penerimaan-barang',
        PenerimaanBarangController::class
    );


    /*
    |--------------------------------------------------------------------------
    | INVOICE SUPPLIER
    |--------------------------------------------------------------------------
    */

    Route::resource(
        'invoice-supplier',
        InvoiceSupplierController::class
    );


    /*
    |--------------------------------------------------------------------------
    | PEMBAYARAN SUPPLIER
    |--------------------------------------------------------------------------
    */

    Route::resource(
        'pembayaran-supplier',
        PembayaranSupplierController::class
    );


    /*
    |--------------------------------------------------------------------------
    | RETUR PEMBELIAN
    |--------------------------------------------------------------------------
    */

    Route::patch(
        'retur-pembelian/{returPembelian}/proses',
        [
            ReturPembelianController::class,
            'proses',
        ]
    )->name('retur-pembelian.proses');


    Route::resource(
        'retur-pembelian',
        ReturPembelianController::class
    );


    /*
    |--------------------------------------------------------------------------
    | UTANG
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/utang',
        [
            UtangController::class,
            'index',
        ]
    )->name('utang.index');


    Route::get(
        '/utang/kartu',
        [
            KartuUtangController::class,
            'index',
        ]
    )->name('utang.kartu');


    /*
    |--------------------------------------------------------------------------
    | PERSEDIAAN
    |--------------------------------------------------------------------------
    */


    /*
    |--------------------------------------------------------------------------
    | STOK BARANG
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/stok-barang/template-stok-awal',
        [
            StokBarangController::class,
            'downloadTemplate',
        ]
    )->name('stok-barang.template-stok-awal');


    Route::post(
        '/stok-barang/import-stok-awal',
        [
            StokBarangController::class,
            'importStokAwal',
        ]
    )->name('stok-barang.import-stok-awal');


    Route::get(
        '/stok-barang',
        [
            StokBarangController::class,
            'index',
        ]
    )->name('stok-barang.index');


    /*
    |--------------------------------------------------------------------------
    | KARTU STOK
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/kartu-stok',
        [
            KartuStokController::class,
            'index',
        ]
    )->name('kartu-stok.index');


/*
|--------------------------------------------------------------------------
| MUTASI GUDANG
|--------------------------------------------------------------------------
*/

Route::get(
    'mutasi-gudang/search-barang',
    [MutasiGudangController::class, 'searchBarang']
)->name('mutasi-gudang.search-barang');

Route::resource(
    'mutasi-gudang',
    MutasiGudangController::class
);



    /*
    |--------------------------------------------------------------------------
    | STOCK OPNAME
    |--------------------------------------------------------------------------
    */

    Route::resource(
        'stock-opname',
        StockOpnameController::class
    );


    /*
    |--------------------------------------------------------------------------
    | PENYESUAIAN STOK
    |--------------------------------------------------------------------------
    */

    Route::resource(
        'penyesuaian-stok',
        PenyesuaianStokController::class
    );


    /*
    |--------------------------------------------------------------------------
    | KONVERSI BARANG
    |--------------------------------------------------------------------------
    */

    Route::resource(
        'conversions',
        ConversionController::class
    )->only([
        'index',
        'create',
        'store',
        'show',
    ]);


    /*
    |--------------------------------------------------------------------------
    | KAS & BANK
    |--------------------------------------------------------------------------
    */


    /*
    |--------------------------------------------------------------------------
    | UANG MASUK
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/uang-masuk/{uangMasuk}/catat',
        [
            UangMasukController::class,
            'catat',
        ]
    )->name('uang-masuk.catat');


    Route::resource(
        'uang-masuk',
        UangMasukController::class
    );


    /*
    |--------------------------------------------------------------------------
    | UANG KELUAR
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/uang-keluar/{uangKeluar}/catat',
        [
            UangKeluarController::class,
            'catat',
        ]
    )->name('uang-keluar.catat');


    Route::resource(
        'uang-keluar',
        UangKeluarController::class
    );


    /*
    |--------------------------------------------------------------------------
    | TRANSFER ANTAR REKENING
    |--------------------------------------------------------------------------
    */

    Route::post(
        '/transfer-kas-bank/{transferKasBank}/catat',
        [
            TransferKasBankController::class,
            'catat',
        ]
    )->name('transfer-kas-bank.catat');


    Route::resource(
        'transfer-kas-bank',
        TransferKasBankController::class
    );


    /*
    |--------------------------------------------------------------------------
    | MUTASI KAS / BANK
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/mutasi-kas-bank',
        [
            MutasiKasBankController::class,
            'index',
        ]
    )->name('mutasi-kas-bank.index');


    /*
    |--------------------------------------------------------------------------
    | TRANSAKSI BELUM TERIDENTIFIKASI
    |--------------------------------------------------------------------------
    */

    Route::patch(
        '/transaksi-belum-teridentifikasi/{transaksiBelumTeridentifikasi}/abaikan',
        [
            TransaksiBelumTeridentifikasiController::class,
            'abaikan',
        ]
    )->name(
        'transaksi-belum-teridentifikasi.abaikan'
    );


    Route::resource(
        'transaksi-belum-teridentifikasi',
        TransaksiBelumTeridentifikasiController::class
    );


    /*
    |--------------------------------------------------------------------------
    | IMPORT REKENING KORAN
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/rekening-koran-import',
        [
            ImportRekeningKoranController::class,
            'create',
        ]
    )->name('rekening-koran-import.create');


    Route::post(
        '/rekening-koran-import/preview',
        [
            ImportRekeningKoranController::class,
            'preview',
        ]
    )->name('rekening-koran-import.preview.process');


    Route::get(
        '/rekening-koran-import/preview',
        [
            ImportRekeningKoranController::class,
            'showPreview',
        ]
    )->name('rekening-koran-import.preview');


    Route::post(
        '/rekening-koran-import/store',
        [
            ImportRekeningKoranController::class,
            'store',
        ]
    )->name('rekening-koran-import.store');


    Route::post(
        '/rekening-koran-import/cancel',
        [
            ImportRekeningKoranController::class,
            'cancel',
        ]
    )->name('rekening-koran-import.cancel');


    /*
    |--------------------------------------------------------------------------
    | REKONSILIASI BANK
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/rekonsiliasi-bank',
        [
            RekonsiliasiBankController::class,
            'index',
        ]
    )->name('rekonsiliasi-bank.index');


    Route::get(
        '/rekonsiliasi-bank/{transaksiBank}',
        [
            RekonsiliasiBankController::class,
            'show',
        ]
    )->name('rekonsiliasi-bank.show');


    Route::post(
        '/rekonsiliasi-bank/{transaksiBank}/cocokkan',
        [
            RekonsiliasiBankController::class,
            'cocokkan',
        ]
    )->name('rekonsiliasi-bank.cocokkan');


    Route::patch(
        '/rekonsiliasi-bank/{transaksiBank}/batalkan',
        [
            RekonsiliasiBankController::class,
            'batalkan',
        ]
    )->name('rekonsiliasi-bank.batalkan');


    /*
    |--------------------------------------------------------------------------
    | LAPORAN
    |--------------------------------------------------------------------------
    */


    /*
    |--------------------------------------------------------------------------
    | LAPORAN PEMBELIAN
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/laporan/pembelian',
        [
            LaporanPembelianController::class,
            'index',
        ]
    )->name('laporan.pembelian');


    Route::get(
        '/laporan/pembelian/pdf',
        [
            LaporanPembelianController::class,
            'pdf',
        ]
    )->name('laporan.pembelian.pdf');


    Route::get(
        '/laporan/pembelian/excel',
        [
            LaporanPembelianController::class,
            'excel',
        ]
    )->name('laporan.pembelian.excel');


    /*
    |--------------------------------------------------------------------------
    | LAPORAN PERSEDIAAN
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/laporan/persediaan',
        [
            LaporanPersediaanController::class,
            'index',
        ]
    )->name('laporan.persediaan');


    Route::get(
        '/laporan/persediaan/pdf',
        [
            LaporanPersediaanController::class,
            'pdf',
        ]
    )->name('laporan.persediaan.pdf');


    Route::get(
        '/laporan/persediaan/excel',
        [
            LaporanPersediaanController::class,
            'excel',
        ]
    )->name('laporan.persediaan.excel');


    /*
    |--------------------------------------------------------------------------
    | LAPORAN MUTASI PERSEDIAAN
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/laporan/persediaan/mutasi/pdf',
        [
            LaporanPersediaanController::class,
            'mutasiPdf',
        ]
    )->name('laporan.persediaan.mutasi.pdf');


    Route::get(
        '/laporan/persediaan/mutasi/excel',
        [
            LaporanPersediaanController::class,
            'mutasiExcel',
        ]
    )->name('laporan.persediaan.mutasi.excel');


    /*
    |--------------------------------------------------------------------------
    | LAPORAN UTANG
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/laporan/utang',
        [
            LaporanUtangController::class,
            'index',
        ]
    )->name('laporan.utang');


    Route::get(
        '/laporan/utang/pdf',
        [
            LaporanUtangController::class,
            'pdf',
        ]
    )->name('laporan.utang.pdf');


    Route::get(
        '/laporan/utang/excel',
        [
            LaporanUtangController::class,
            'excel',
        ]
    )->name('laporan.utang.excel');


    /*
    |--------------------------------------------------------------------------
    | ACCOUNTING
    |--------------------------------------------------------------------------
    */


    /*
    |--------------------------------------------------------------------------
    | CHART OF ACCOUNTS
    |--------------------------------------------------------------------------
    */

    Route::get(
        'coa/import/template',
        [
            CoaController::class,
            'downloadTemplate',
        ]
    )->name('coa.import.template');


    Route::post(
        'coa/import',
        [
            CoaController::class,
            'import',
        ]
    )->name('coa.import');


    Route::resource(
        'coa',
        CoaController::class
    )->except('show');


    /*
    |--------------------------------------------------------------------------
    | JURNAL UMUM
    |--------------------------------------------------------------------------
    */

    Route::resource(
        'jurnal-umum',
        JurnalUmumController::class
    )->except([
        'edit',
        'update',
    ]);


    /*
    |--------------------------------------------------------------------------
    | BUKU BESAR
    |--------------------------------------------------------------------------
    */

    Route::get(
        'buku-besar',
        [
            BukuBesarController::class,
            'index',
        ]
    )->name('buku-besar.index');


    /*
    |--------------------------------------------------------------------------
    | LAPORAN PENJUALAN
    |--------------------------------------------------------------------------
    */

    Route::get(
        'laporan/penjualan',
        [
            LaporanPenjualanController::class,
            'index',
        ]
    )->name('laporan.penjualan');


    Route::get(
        'laporan/penjualan/pdf',
        [
            LaporanPenjualanController::class,
            'pdf',
        ]
    )->name('laporan.penjualan.pdf');


    Route::get(
        'laporan/penjualan/excel',
        [
            LaporanPenjualanController::class,
            'excel',
        ]
    )->name('laporan.penjualan.excel');


    /*
    |--------------------------------------------------------------------------
    | LAPORAN PIUTANG
    |--------------------------------------------------------------------------
    */

    Route::get(
        'laporan/piutang',
        [
            LaporanPiutangController::class,
            'index',
        ]
    )->name('laporan.piutang');


    Route::get(
        'laporan/piutang/pdf',
        [
            LaporanPiutangController::class,
            'pdf',
        ]
    )->name('laporan.piutang.pdf');


    Route::get(
        'laporan/piutang/excel',
        [
            LaporanPiutangController::class,
            'excel',
        ]
    )->name('laporan.piutang.excel');


    /*
    |--------------------------------------------------------------------------
    | LAPORAN LABA RUGI
    |--------------------------------------------------------------------------
    */

    Route::get(
        'laporan/laba-rugi',
        [
            LaporanKeuanganController::class,
            'labaRugi',
        ]
    )->name('laporan.laba-rugi');


    Route::get(
        'laporan/laba-rugi/pdf',
        [
            LaporanKeuanganController::class,
            'labaRugiPdf',
        ]
    )->name('laporan.laba-rugi.pdf');


    Route::get(
        'laporan/laba-rugi/excel',
        [
            LaporanKeuanganController::class,
            'labaRugiExcel',
        ]
    )->name('laporan.laba-rugi.excel');


    /*
    |--------------------------------------------------------------------------
    | LAPORAN NERACA
    |--------------------------------------------------------------------------
    */

    Route::get(
        'laporan/neraca',
        [
            LaporanKeuanganController::class,
            'neraca',
        ]
    )->name('laporan.neraca');


    Route::get(
        'laporan/neraca/pdf',
        [
            LaporanKeuanganController::class,
            'neracaPdf',
        ]
    )->name('laporan.neraca.pdf');


    Route::get(
        'laporan/neraca/excel',
        [
            LaporanKeuanganController::class,
            'neracaExcel',
        ]
    )->name('laporan.neraca.excel');


    /*
    |--------------------------------------------------------------------------
    | LAPORAN ARUS KAS
    |--------------------------------------------------------------------------
    */

    Route::get(
        'laporan/arus-kas',
        [
            LaporanKeuanganController::class,
            'arusKas',
        ]
    )->name('laporan.arus-kas');


    Route::get(
        'laporan/arus-kas/pdf',
        [
            LaporanKeuanganController::class,
            'arusKasPdf',
        ]
    )->name('laporan.arus-kas.pdf');


    Route::get(
        'laporan/arus-kas/excel',
        [
            LaporanKeuanganController::class,
            'arusKasExcel',
        ]
    )->name('laporan.arus-kas.excel');


    /*
    |--------------------------------------------------------------------------
    | LAPORAN PERUBAHAN EKUITAS
    |--------------------------------------------------------------------------
    */

    Route::get(
        'laporan/perubahan-ekuitas',
        [
            LaporanKeuanganController::class,
            'perubahanEkuitas',
        ]
    )->name('laporan.perubahan-ekuitas');


    Route::get(
        'laporan/perubahan-ekuitas/pdf',
        [
            LaporanKeuanganController::class,
            'perubahanEkuitasPdf',
        ]
    )->name('laporan.perubahan-ekuitas.pdf');


    Route::get(
        'laporan/perubahan-ekuitas/excel',
        [
            LaporanKeuanganController::class,
            'perubahanEkuitasExcel',
        ]
    )->name('laporan.perubahan-ekuitas.excel');


    /*
    |--------------------------------------------------------------------------
    | NERACA SALDO
    |--------------------------------------------------------------------------
    */

    Route::get(
        'laporan/neraca-saldo',
        [
            LaporanKeuanganController::class,
            'neracaSaldo',
        ]
    )->name('laporan.neraca-saldo');


    /*
    |--------------------------------------------------------------------------
    | PENGATURAN AKUN SISTEM
    |--------------------------------------------------------------------------
    */

    Route::get(
        'pengaturan-akun',
        [
            PengaturanAkunController::class,
            'index',
        ]
    )->name('pengaturan-akun.index');


    Route::put(
        'pengaturan-akun',
        [
            PengaturanAkunController::class,
            'update',
        ]
    )->name('pengaturan-akun.update');


    /*
    |--------------------------------------------------------------------------
    | IMPORT CUSTOMER
    |--------------------------------------------------------------------------
    */

    Route::get(
        'customer/import/template',
        [
            CustomerImportController::class,
            'downloadTemplate',
        ]
    )->name('customer.import.template');


    Route::post(
        'customer/import',
        [
            CustomerImportController::class,
            'import',
        ]
    )->name('customer.import');


    /*
    |--------------------------------------------------------------------------
    | PENERIMAAN PIUTANG
    |--------------------------------------------------------------------------
    */

    Route::get(
        'piutang/penerimaan',
        [
            PenerimaanPiutangController::class,
            'index',
        ]
    )->name('piutang.penerimaan.index');


    Route::get(
        'piutang/penerimaan/create',
        [
            PenerimaanPiutangController::class,
            'create',
        ]
    )->name('piutang.penerimaan.create');


    Route::post(
        'piutang/penerimaan',
        [
            PenerimaanPiutangController::class,
            'store',
        ]
    )->name('piutang.penerimaan.store');


    Route::get(
        'piutang/penerimaan/{penerimaan}/pdf',
        [
            PenerimaanPiutangController::class,
            'kwitansiPdf',
        ]
    )->name('piutang.kwitansi.pdf');


    /*
    |--------------------------------------------------------------------------
    | KARTU PIUTANG
    |--------------------------------------------------------------------------
    */

    Route::get(
        'piutang/kartu',
        [
            KartuPiutangController::class,
            'index',
        ]
    )->name('piutang.kartu');


    /*
    |--------------------------------------------------------------------------
    | DETAIL PIUTANG & PENAGIHAN
    |--------------------------------------------------------------------------
    */

    Route::get(
        'piutang/{invoice}',
        [
            PiutangController::class,
            'show',
        ]
    )->name('piutang.show');


    Route::post(
        'piutang/{invoice}/tindak-lanjut',
        [
            PiutangController::class,
            'storeTindakLanjut',
        ]
    )->name('piutang.tindak-lanjut.store');


    Route::get(
        'piutang/{invoice}/surat-tagihan/pdf',
        [
            PiutangController::class,
            'cetakSuratTagihan',
        ]
    )->name('piutang.surat-tagihan.pdf');


    Route::get(
        'piutang/{invoice}/surat-peringatan/{level}/pdf',
        [
            PiutangController::class,
            'cetakSuratPeringatan',
        ]
    )->name('piutang.surat-peringatan.pdf');


    /*
    |--------------------------------------------------------------------------
    | RETUR PENJUALAN PDF
    |--------------------------------------------------------------------------
    */

    Route::get(
        'retur-penjualan/{returPenjualan}/pdf',
        [
            ReturPenjualanController::class,
            'pdf',
        ]
    )->name('retur-penjualan.pdf');


    /*
    |--------------------------------------------------------------------------
    | PENGATURAN PENOMORAN
    |--------------------------------------------------------------------------
    */

    Route::get(
        'pengaturan-penomoran',
        [
            PengaturanPenomoranController::class,
            'index',
        ]
    )->name('pengaturan-penomoran.index');


    Route::put(
        'pengaturan-penomoran',
        [
            PengaturanPenomoranController::class,
            'update',
        ]
    )->name('pengaturan-penomoran.update');


    /*
    |--------------------------------------------------------------------------
    | PEMBATALAN RESMI INVOICE PENJUALAN
    |--------------------------------------------------------------------------
    */

    Route::post(
        'invoice-penjualan/{invoice}/batal',
        [
            InvoicePenjualanController::class,
            'batal',
        ]
    )->name('invoice-penjualan.batal');


    /*
    |--------------------------------------------------------------------------
    | PENUTUPAN PERIODE
    |--------------------------------------------------------------------------
    */

    Route::get(
        'penutupan-periode',
        [
            PenutupanPeriodeController::class,
            'index',
        ]
    )->name('penutupan-periode.index');


    Route::post(
        'penutupan-periode/close',
        [
            PenutupanPeriodeController::class,
            'close',
        ]
    )->name('penutupan-periode.close');


    Route::put(
        'penutupan-periode/{penutupanPeriode}/reopen',
        [
            PenutupanPeriodeController::class,
            'reopen',
        ]
    )->name('penutupan-periode.reopen');

});


/*
|--------------------------------------------------------------------------
| AUTH ROUTES
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';