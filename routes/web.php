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
use App\Http\Controllers\PengaturanPenomoranController;
use App\Http\Controllers\ProfilPerusahaanController;

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

    Route::get('profil-perusahaan', [ProfilPerusahaanController::class, 'index'])->name('profil-perusahaan.index');
    Route::put('profil-perusahaan', [ProfilPerusahaanController::class, 'update'])->name('profil-perusahaan.update');

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
        'customer',
        CustomerController::class
    )->except('show');

    Route::resource(
        'supplier',
        SupplierController::class
    )->except('show');


    /*
    |--------------------------------------------------------------------------
    | PENJUALAN (SALES)
    |--------------------------------------------------------------------------
    */

    Route::get(
        'penjualan/{penjualan}/pdf',
        [PenjualanController::class, 'pdf']
    )->name('penjualan.pdf');

    Route::post(
        'penjualan/{penjualan}/konfirmasi',
        [PenjualanController::class, 'konfirmasi']
    )->name('penjualan.konfirmasi');

    Route::post(
        'penjualan/{penjualan}/batalkan',
        [PenjualanController::class, 'batalkan']
    )->name('penjualan.batalkan');

    Route::resource(
        'penjualan',
        PenjualanController::class
    );

    Route::get(
        'invoice-penjualan/{invoicePenjualan}/pdf',
        [InvoicePenjualanController::class, 'pdf']
    )->name('invoice-penjualan.pdf');

    Route::resource(
        'invoice-penjualan',
        InvoicePenjualanController::class
    );

    Route::resource(
        'pembayaran-penjualan',
        PembayaranPenjualanController::class
    )->except(['edit', 'update', 'destroy']);

    Route::resource(
        'retur-penjualan',
        ReturPenjualanController::class
    )->except(['edit', 'update', 'destroy']);

    Route::get(
        'piutang',
        [PiutangController::class, 'index']
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

    Route::get('/laporan/utang', [LaporanPembelianController::class, 'index'])
    ->name('laporan.utang');

    /*
    |--------------------------------------------------------------------------
    | AKUNTANSI (ACCOUNTING)
    |--------------------------------------------------------------------------
    */

    Route::get('coa/import/template', [CoaController::class, 'downloadTemplate'])->name('coa.import.template');
    Route::post('coa/import', [CoaController::class, 'import'])->name('coa.import');
    Route::resource('coa', CoaController::class)->except('show');
    Route::resource('jurnal-umum', JurnalUmumController::class)->except(['edit', 'update']);
    Route::get('buku-besar', [BukuBesarController::class, 'index'])->name('buku-besar.index');

    // 1. Penjualan
    Route::get('laporan/penjualan', [LaporanPenjualanController::class, 'index'])->name('laporan.penjualan');
    Route::get('laporan/penjualan/pdf', [LaporanPenjualanController::class, 'pdf'])->name('laporan.penjualan.pdf');
    Route::get('laporan/penjualan/excel', [LaporanPenjualanController::class, 'excel'])->name('laporan.penjualan.excel');

    // 2. Piutang
    Route::get('laporan/piutang', [LaporanPiutangController::class, 'index'])->name('laporan.piutang');
    Route::get('laporan/piutang/pdf', [LaporanPiutangController::class, 'pdf'])->name('laporan.piutang.pdf');
    Route::get('laporan/piutang/excel', [LaporanPiutangController::class, 'excel'])->name('laporan.piutang.excel');

    // 3. Laba Rugi
    Route::get('laporan/laba-rugi', [LaporanKeuanganController::class, 'labaRugi'])->name('laporan.laba-rugi');
    Route::get('laporan/laba-rugi/pdf', [LaporanKeuanganController::class, 'labaRugiPdf'])->name('laporan.laba-rugi.pdf');
    Route::get('laporan/laba-rugi/excel', [LaporanKeuanganController::class, 'labaRugiExcel'])->name('laporan.laba-rugi.excel');

    // 4. Neraca
    Route::get('laporan/neraca', [LaporanKeuanganController::class, 'neraca'])->name('laporan.neraca');
    Route::get('laporan/neraca/pdf', [LaporanKeuanganController::class, 'neracaPdf'])->name('laporan.neraca.pdf');
    Route::get('laporan/neraca/excel', [LaporanKeuanganController::class, 'neracaExcel'])->name('laporan.neraca.excel');

    // 5. Arus Kas
    Route::get('laporan/arus-kas', [LaporanKeuanganController::class, 'arusKas'])->name('laporan.arus-kas');
    Route::get('laporan/arus-kas/pdf', [LaporanKeuanganController::class, 'arusKasPdf'])->name('laporan.arus-kas.pdf');
    Route::get('laporan/arus-kas/excel', [LaporanKeuanganController::class, 'arusKasExcel'])->name('laporan.arus-kas.excel');

    // 6. Perubahan Ekuitas
    Route::get('laporan/perubahan-ekuitas', [LaporanKeuanganController::class, 'perubahanEkuitas'])->name('laporan.perubahan-ekuitas');
    Route::get('laporan/perubahan-ekuitas/pdf', [LaporanKeuanganController::class, 'perubahanEkuitasPdf'])->name('laporan.perubahan-ekuitas.pdf');
    Route::get('laporan/perubahan-ekuitas/excel', [LaporanKeuanganController::class, 'perubahanEkuitasExcel'])->name('laporan.perubahan-ekuitas.excel');

    // Neraca Saldo
    Route::get('laporan/neraca-saldo', [LaporanKeuanganController::class, 'neracaSaldo'])->name('laporan.neraca-saldo');

    // Pengaturan Akun Sistem
    Route::get('pengaturan-akun', [PengaturanAkunController::class, 'index'])->name('pengaturan-akun.index');
    Route::put('pengaturan-akun', [PengaturanAkunController::class, 'update'])->name('pengaturan-akun.update');

    // Import Customer & Template
    Route::get('customer/import/template', [CustomerImportController::class, 'downloadTemplate'])->name('customer.import.template');
    Route::post('customer/import', [CustomerImportController::class, 'import'])->name('customer.import');

    // Penerimaan Piutang & Kwitansi
    Route::get('piutang/penerimaan', [PenerimaanPiutangController::class, 'index'])->name('piutang.penerimaan.index');
    Route::get('piutang/penerimaan/create', [PenerimaanPiutangController::class, 'create'])->name('piutang.penerimaan.create');
    Route::post('piutang/penerimaan', [PenerimaanPiutangController::class, 'store'])->name('piutang.penerimaan.store');
    Route::get('piutang/penerimaan/{penerimaan}/pdf', [PenerimaanPiutangController::class, 'kwitansiPdf'])->name('piutang.kwitansi.pdf');

    // Kartu Piutang Customer & Detail Penagihan
    Route::get('piutang/kartu', [KartuPiutangController::class, 'index'])->name('piutang.kartu');
    Route::get('piutang/{invoice}', [PiutangController::class, 'show'])->name('piutang.show');
    Route::post('piutang/{invoice}/tindak-lanjut', [PiutangController::class, 'storeTindakLanjut'])->name('piutang.tindak-lanjut.store');
    Route::get('piutang/{invoice}/surat-tagihan/pdf', [PiutangController::class, 'cetakSuratTagihan'])->name('piutang.surat-tagihan.pdf');
    Route::get('piutang/{invoice}/surat-peringatan/{level}/pdf', [PiutangController::class, 'cetakSuratPeringatan'])->name('piutang.surat-peringatan.pdf');

    // Retur Penjualan
    Route::get('retur-penjualan/{returPenjualan}/pdf', [ReturPenjualanController::class, 'pdf'])->name('retur-penjualan.pdf');
    Route::resource('retur-penjualan', ReturPenjualanController::class)->only(['index', 'create', 'store', 'show']);

    // Pengaturan Format Penomoran Jurnal & Dokumen
    Route::get('pengaturan-penomoran', [PengaturanPenomoranController::class, 'index'])->name('pengaturan-penomoran.index');
    Route::put('pengaturan-penomoran', [PengaturanPenomoranController::class, 'update'])->name('pengaturan-penomoran.update');

    // Pembatalan Resmi Invoice (Kepala Toko / Admin Only)
    Route::post('invoice-penjualan/{invoice}/batal', [InvoicePenjualanController::class, 'batal'])->name('invoice-penjualan.batal');

    // Penutupan Periode (Cut-off Bulanan)
    Route::get('penutupan-periode', [PenutupanPeriodeController::class, 'index'])->name('penutupan-periode.index');
    Route::post('penutupan-periode/close', [PenutupanPeriodeController::class, 'close'])->name('penutupan-periode.close');
    Route::put('penutupan-periode/{penutupanPeriode}/reopen', [PenutupanPeriodeController::class, 'reopen'])->name('penutupan-periode.reopen');

});

/*
|--------------------------------------------------------------------------
| AUTH ROUTES
|--------------------------------------------------------------------------
*/

require __DIR__.'/auth.php';