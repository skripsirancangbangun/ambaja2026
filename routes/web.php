<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\KategoriBarangController;
use App\Http\Controllers\SatuanController;
use App\Http\Controllers\GudangController;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PenerimaanBarangController;
use App\Http\Controllers\StokBarangController;
use App\Http\Controllers\KartuStokController;
use App\Http\Controllers\MutasiGudangController;
use App\Http\Controllers\StockOpnameController;
use App\Http\Controllers\PenyesuaianStokController;
use App\Http\Controllers\InvoiceSupplierController;
use App\Http\Controllers\UtangController;
use App\Http\Controllers\PembayaranSupplierController;
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

Route::get('/dashboard', function () {
    return view('dashboard');
})
    ->middleware(['auth', 'verified'])
    ->name('dashboard');


/*
|--------------------------------------------------------------------------
| AUTHENTICATED ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    /*
    |--------------------------------------------------------------------------
    | PROFILE
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/profile',
        [ProfileController::class, 'edit']
    )->name('profile.edit');

    Route::patch(
        '/profile',
        [ProfileController::class, 'update']
    )->name('profile.update');

    Route::delete(
        '/profile',
        [ProfileController::class, 'destroy']
    )->name('profile.destroy');


    /*
    |--------------------------------------------------------------------------
    | MASTER DATA
    |--------------------------------------------------------------------------
    */

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

    Route::resource(
        'supplier',
        SupplierController::class
    )->except('show');


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
    | PURCHASE ORDER
    |--------------------------------------------------------------------------
    */

    Route::get(
        'purchase-order/{purchaseOrder}/pdf',
        [PurchaseOrderController::class, 'pdf']
    )->name('purchase-order.pdf');

    Route::patch(
        'purchase-order/{purchaseOrder}/kirim',
        [PurchaseOrderController::class, 'kirim']
    )->name('purchase-order.kirim');

    Route::resource(
        'purchase-order',
        PurchaseOrderController::class
    );


    /*
    |--------------------------------------------------------------------------
    | PEMBATALAN SISA PURCHASE ORDER
    |--------------------------------------------------------------------------
    */

    Route::patch(
        'purchase-order-item/{purchaseOrderItem}/batalkan-sisa',
        [PenerimaanBarangController::class, 'batalkanSisa']
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
        [ReturPembelianController::class, 'proses']
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
        [UtangController::class, 'index']
    )->name('utang.index');


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
        [StokBarangController::class, 'downloadTemplate']
    )->name('stok-barang.template-stok-awal');

    Route::post(
        '/stok-barang/import-stok-awal',
        [StokBarangController::class, 'importStokAwal']
    )->name('stok-barang.import-stok-awal');

    Route::get(
        '/stok-barang',
        [StokBarangController::class, 'index']
    )->name('stok-barang.index');


    /*
    |--------------------------------------------------------------------------
    | KARTU STOK
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/kartu-stok',
        [KartuStokController::class, 'index']
    )->name('kartu-stok.index');


    /*
    |--------------------------------------------------------------------------
    | MUTASI GUDANG
    |--------------------------------------------------------------------------
    */

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
        [UangMasukController::class, 'catat']
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
        [UangKeluarController::class, 'catat']
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
        [TransferKasBankController::class, 'catat']
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
        [MutasiKasBankController::class, 'index']
    )->name('mutasi-kas-bank.index');


    /*
    |--------------------------------------------------------------------------
    | TRANSAKSI BELUM TERIDENTIFIKASI
    |--------------------------------------------------------------------------
    */

    Route::patch(
        '/transaksi-belum-teridentifikasi/{transaksiBelumTeridentifikasi}/abaikan',
        [TransaksiBelumTeridentifikasiController::class, 'abaikan']
    )->name('transaksi-belum-teridentifikasi.abaikan');

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
    [ImportRekeningKoranController::class, 'create']
)->name('rekening-koran-import.create');

Route::post(
    '/rekening-koran-import/preview',
    [ImportRekeningKoranController::class, 'preview']
)->name('rekening-koran-import.preview.process');

Route::get(
    '/rekening-koran-import/preview',
    [ImportRekeningKoranController::class, 'showPreview']
)->name('rekening-koran-import.preview');

Route::post(
    '/rekening-koran-import/store',
    [ImportRekeningKoranController::class, 'store']
)->name('rekening-koran-import.store');

Route::post(
    '/rekening-koran-import/cancel',
    [ImportRekeningKoranController::class, 'cancel']
)->name('rekening-koran-import.cancel');


    /*
    |--------------------------------------------------------------------------
    | REKONSILIASI BANK
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/rekonsiliasi-bank',
        [RekonsiliasiBankController::class, 'index']
    )->name('rekonsiliasi-bank.index');

    Route::get(
        '/rekonsiliasi-bank/{transaksiBank}',
        [RekonsiliasiBankController::class, 'show']
    )->name('rekonsiliasi-bank.show');

    Route::post(
        '/rekonsiliasi-bank/{transaksiBank}/cocokkan',
        [RekonsiliasiBankController::class, 'cocokkan']
    )->name('rekonsiliasi-bank.cocokkan');

    Route::patch(
        '/rekonsiliasi-bank/{transaksiBank}/batalkan',
        [RekonsiliasiBankController::class, 'batalkan']
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
        [LaporanPembelianController::class, 'index']
    )->name('laporan.pembelian');

    Route::get(
        '/laporan/pembelian/pdf',
        [LaporanPembelianController::class, 'pdf']
    )->name('laporan.pembelian.pdf');

    Route::get(
        '/laporan/pembelian/excel',
        [LaporanPembelianController::class, 'excel']
    )->name('laporan.pembelian.excel');


    /*
    |--------------------------------------------------------------------------
    | LAPORAN PERSEDIAAN
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/laporan/persediaan',
        [LaporanPersediaanController::class, 'index']
    )->name('laporan.persediaan');

    Route::get(
        '/laporan/persediaan/pdf',
        [LaporanPersediaanController::class, 'pdf']
    )->name('laporan.persediaan.pdf');

    Route::get(
        '/laporan/persediaan/excel',
        [LaporanPersediaanController::class, 'excel']
    )->name('laporan.persediaan.excel');


    /*
    |--------------------------------------------------------------------------
    | LAPORAN MUTASI PERSEDIAAN
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/laporan/persediaan/mutasi/pdf',
        [LaporanPersediaanController::class, 'mutasiPdf']
    )->name('laporan.persediaan.mutasi.pdf');

    Route::get(
        '/laporan/persediaan/mutasi/excel',
        [LaporanPersediaanController::class, 'mutasiExcel']
    )->name('laporan.persediaan.mutasi.excel');


    /*
    |--------------------------------------------------------------------------
    | LAPORAN UTANG
    |--------------------------------------------------------------------------
    */

    Route::get(
        '/laporan/utang',
        [LaporanUtangController::class, 'index']
    )->name('laporan.utang');

    Route::get(
        '/laporan/utang/pdf',
        [LaporanUtangController::class, 'pdf']
    )->name('laporan.utang.pdf');

    Route::get(
        '/laporan/utang/excel',
        [LaporanUtangController::class, 'excel']
    )->name('laporan.utang.excel');

});


/*
|--------------------------------------------------------------------------
| AUTH ROUTES
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';