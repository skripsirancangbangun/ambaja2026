<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;

class RepairDummyOpeningBalancesCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->createSchema();
        $this->seedBeforeState();
    }

    public function test_dry_run_does_not_change_anything(): void
    {
        $exit = Artisan::call('ambaja:repair-dummy-opening-balances');

        $output = Artisan::output();
        $normalizedOutput = preg_replace('/\s+/', ' ', $output) ?? $output;
        $this->assertSame(0, $exit, $output);
        $this->assertStringContainsString('MODE: DRY RUN', $output);
        $this->assertStringContainsString('DRY RUN selesai', $output);
        $this->assertMoneyEquals(50, DB::table('akuns')->where('kode_akun', '1101')->value('saldo_awal'));
        $this->assertDatabaseMissing('akuns', ['kode_akun' => '3104']);
        $this->assertDatabaseCount('jurnals', 1);
        $this->assertDatabaseCount('jurnal_details', 2);
        $this->assertDatabaseCount('mutasi_stoks', 6584);
        $this->assertDatabaseCount('mutasi_gudangs', 1);
        $this->assertDatabaseCount('mutasi_gudang_items', 3);
        $this->assertStringContainsString('6.578 baris import awal + 6 baris mutasi gudang', $normalizedOutput);
        $this->assertStringContainsString('Rp3.578.242.664', $normalizedOutput);
        $this->assertSame(17, (int) DB::table('pengaturan_akuns')->where('kunci_pengaturan', 'AKUN_SALDO_AWAL')->value('akun_id'));
    }

    public function test_apply_balances_opening_accounts_and_keeps_original_stock_journal(): void
    {
        $exit = Artisan::call('ambaja:repair-dummy-opening-balances', [
            '--apply' => true,
            '--confirm' => 'KOREKSI SALDO AWAL DUMMY AMBAJA',
        ]);

        $this->assertSame(0, $exit, Artisan::output());
        $this->assertMoneyEquals(25500000, DB::table('akuns')->where('kode_akun', '1101')->value('saldo_awal'));
        $this->assertMoneyEquals(180250000, DB::table('akuns')->where('kode_akun', '1102')->value('saldo_awal'));
        $this->assertMoneyEquals(0, DB::table('akuns')->where('kode_akun', '1103')->value('saldo_awal'));
        $this->assertSame(0, (int) DB::table('akuns')->where('kode_akun', '1103')->value('is_active'));
        $this->assertMoneyEquals(205750000, DB::table('akuns')->where('kode_akun', '3104')->value('saldo_awal'));
        $this->assertSame(1, (int) DB::table('pengaturan_akuns')->where('kunci_pengaturan', 'AKUN_SALDO_AWAL')->where('akun_id', 18)->count());

        // Original stock-opening journal remains untouched; a separate reclassification journal is added.
        $this->assertDatabaseHas('jurnals', [
            'id' => 27,
            'referensi_type' => 'STOK_AWAL_IMPORT',
            'total_debit' => 3578242664,
            'total_kredit' => 3578242664,
            'is_posted' => 1,
        ]);
        $this->assertDatabaseHas('jurnals', [
            'referensi_type' => 'OPENING_BALANCE_RECLASSIFICATION',
            'referensi_id' => 2,
            'total_debit' => 3578242664,
            'total_kredit' => 3578242664,
            'is_posted' => 1,
        ]);
        $this->assertDatabaseCount('jurnals', 2);
        $this->assertDatabaseCount('jurnal_details', 4);
        $this->assertDatabaseCount('mutasi_stoks', 6584);
        $this->assertDatabaseCount('mutasi_gudangs', 1);
        $this->assertDatabaseCount('mutasi_gudang_items', 3);
        $this->assertSame('3783992664.00', $this->trialBalanceDebit());
        $this->assertSame('3783992664.00', $this->trialBalanceCredit());
    }

    public function test_apply_aborts_if_a_transaction_exists(): void
    {
        DB::table('invoice_penjualans')->insert(['id' => 1]);

        $exit = Artisan::call('ambaja:repair-dummy-opening-balances', [
            '--apply' => true,
            '--confirm' => 'KOREKSI SALDO AWAL DUMMY AMBAJA',
        ]);

        $this->assertSame(1, $exit);
        $this->assertMoneyEquals(50, DB::table('akuns')->where('kode_akun', '1101')->value('saldo_awal'));
        $this->assertDatabaseMissing('akuns', ['kode_akun' => '3104']);
        $this->assertDatabaseCount('jurnals', 1);
    }

    public function test_apply_aborts_if_an_unrecognized_stock_movement_exists(): void
    {
        DB::table('mutasi_stoks')->insert([
            'tanggal' => '2026-10-17 15:00:00',
            'barang_id' => 9999,
            'gudang_id' => 1,
            'tipe' => 'PENYESUAIAN',
            'qty_masuk' => 1,
            'qty_keluar' => 0,
            'harga_satuan' => 1000,
            'nilai' => 1000,
            'saldo_qty' => 1,
            'saldo_nilai' => 1000,
            'reference_type' => 'UNKNOWN_SOURCE',
            'reference_id' => 999,
        ]);

        $exit = Artisan::call('ambaja:repair-dummy-opening-balances', [
            '--apply' => true,
            '--confirm' => 'KOREKSI SALDO AWAL DUMMY AMBAJA',
        ]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('Riwayat mutasi stok tidak cocok', Artisan::output());
        $this->assertMoneyEquals(50, DB::table('akuns')->where('kode_akun', '1101')->value('saldo_awal'));
        $this->assertDatabaseMissing('akuns', ['kode_akun' => '3104']);
        $this->assertDatabaseCount('jurnals', 1);
    }

    public function test_apply_aborts_if_a_warehouse_transfer_pair_is_missing(): void
    {
        DB::table('mutasi_stoks')
            ->where('reference_type', 'MUTASI_GUDANG')
            ->where('reference_id', 2)
            ->where('tipe', 'MUTASI_MASUK')
            ->delete();

        $exit = Artisan::call('ambaja:repair-dummy-opening-balances', [
            '--apply' => true,
            '--confirm' => 'KOREKSI SALDO AWAL DUMMY AMBAJA',
        ]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('Riwayat mutasi stok tidak cocok', Artisan::output());
        $this->assertMoneyEquals(50, DB::table('akuns')->where('kode_akun', '1101')->value('saldo_awal'));
        $this->assertDatabaseMissing('akuns', ['kode_akun' => '3104']);
        $this->assertDatabaseCount('jurnals', 1);
    }

    public function test_apply_requires_exact_confirmation(): void
    {
        $exit = Artisan::call('ambaja:repair-dummy-opening-balances', [
            '--apply' => true,
            '--confirm' => 'YA LANJUT',
        ]);

        $this->assertSame(1, $exit);
        $this->assertDatabaseMissing('akuns', ['kode_akun' => '3104']);
        $this->assertDatabaseCount('jurnals', 1);
    }

    private function createSchema(): void
    {
        Schema::create('akuns', function (Blueprint $table) {
            $table->id();
            $table->string('kode_akun')->unique();
            $table->string('nama_akun');
            $table->string('kategori_akun');
            $table->string('sub_kategori')->nullable();
            $table->string('saldo_normal');
            $table->decimal('saldo_awal', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('rekening_kas_banks', function (Blueprint $table) {
            $table->id();
            $table->string('kode_rekening');
            $table->string('nama_rekening');
            $table->string('jenis');
            $table->decimal('saldo_awal', 18, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedBigInteger('akun_id')->nullable();
            $table->timestamps();
        });
        Schema::create('pengaturan_akuns', function (Blueprint $table) {
            $table->id();
            $table->string('kunci_pengaturan')->unique();
            $table->string('nama_pengaturan');
            $table->unsignedBigInteger('akun_id')->nullable();
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
        Schema::create('jurnals', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_jurnal')->unique();
            $table->date('tanggal');
            $table->text('keterangan');
            $table->string('referensi_type')->nullable();
            $table->unsignedBigInteger('referensi_id')->nullable();
            $table->decimal('total_debit', 15, 2)->default(0);
            $table->decimal('total_kredit', 15, 2)->default(0);
            $table->boolean('is_system_generated')->default(false);
            $table->boolean('is_posted')->default(true);
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
        Schema::create('jurnal_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('jurnal_id');
            $table->unsignedBigInteger('akun_id');
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('kredit', 15, 2)->default(0);
            $table->string('keterangan')->nullable();
            $table->timestamps();
        });
        Schema::create('stok_awal_imports', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_import');
            $table->dateTime('tanggal');
            $table->string('file_hash');
            $table->decimal('total_qty', 18, 2);
            $table->decimal('total_nilai', 18, 2);
            $table->unsignedBigInteger('jurnal_id')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
        Schema::create('stok_gudangs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('barang_id');
            $table->unsignedBigInteger('gudang_id');
            $table->decimal('qty', 15, 2)->default(0);
            $table->decimal('harga_rata_rata', 18, 2)->default(0);
            $table->decimal('nilai_persediaan', 18, 2)->default(0);
            $table->timestamps();
        });
        foreach ([
            'penjualans', 'invoice_penjualans', 'pembayaran_penjualans', 'penerimaan_piutangs',
            'invoice_suppliers', 'pembayaran_suppliers', 'purchase_orders', 'penerimaan_barangs',
            'uang_masuks', 'uang_keluars', 'transfer_kas_banks',
            'transaksi_belum_teridentifikasis', 'rekonsiliasi_banks',
            'penyesuaian_stoks', 'penyesuaian_stok_items',
            'stock_opnames', 'stock_opname_items', 'conversions', 'conversion_items',
            'retur_pembelians', 'retur_pembelian_details', 'retur_pembelian_allocations',
            'retur_penjualans', 'retur_penjualan_details', 'retur_penjualan_items',
            'pembayaran_po_suppliers', 'penerimaan_barang_items', 'purchase_order_items',
            'invoice_supplier_items', 'invoice_penjualan_items', 'penjualan_items',
            'pembayaran_penjualan_details', 'penerimaan_piutang_details',
        ] as $tableName) {
            Schema::create($tableName, function (Blueprint $table) {
                $table->id();
            });
        }
        Schema::create('mutasi_stoks', function (Blueprint $table) {
            $table->id();
            $table->dateTime('tanggal');
            $table->unsignedBigInteger('barang_id');
            $table->unsignedBigInteger('gudang_id');
            $table->string('tipe');
            $table->decimal('qty_masuk', 15, 2)->default(0);
            $table->decimal('qty_keluar', 15, 2)->default(0);
            $table->decimal('harga_satuan', 18, 2)->default(0);
            $table->decimal('nilai', 18, 2)->default(0);
            $table->decimal('saldo_qty', 15, 2)->default(0);
            $table->decimal('saldo_nilai', 18, 2)->default(0);
            $table->string('reference_type')->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
        });
        Schema::create('mutasi_gudangs', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_mutasi');
            $table->dateTime('tanggal_mutasi');
            $table->unsignedBigInteger('gudang_asal_id');
            $table->unsignedBigInteger('gudang_tujuan_id');
            $table->text('catatan')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
        });
        Schema::create('mutasi_gudang_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mutasi_gudang_id');
            $table->unsignedBigInteger('barang_id');
            $table->decimal('qty', 15, 2);
            $table->decimal('harga_rata_rata', 18, 2);
            $table->decimal('nilai', 18, 2);
        });
        Schema::create('mutasi_kas_banks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rekening_kas_bank_id');
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('kredit', 15, 2)->default(0);
        });
    }

    private function seedBeforeState(): void
    {
        $accounts = [
            [1, '1101', 'Kas Kecil', 'Aset', 'DEBIT', 50],
            [2, '1102', 'Bank BCA', 'Aset', 'DEBIT', 150],
            [3, '1103', 'Bank Mandiri', 'Aset', 'DEBIT', 72500000],
            [4, '1104', 'Bank BRI', 'Aset', 'DEBIT', 41200000],
            [5, '1120', 'Piutang Dagang', 'Aset', 'DEBIT', 42750000],
            [6, '1130', 'Persediaan Barang Dagang', 'Aset', 'DEBIT', 0],
            [7, '1201', 'Peralatan & Mesin', 'Aset', 'DEBIT', 85000000],
            [8, '1202', 'Akumulasi Penyusutan Peralatan', 'Aset', 'KREDIT', 15000000],
            [9, '1203', 'Kendaraan Operasional', 'Aset', 'DEBIT', 150000000],
            [10, '1204', 'Akumulasi Penyusutan Kendaraan', 'Aset', 'KREDIT', 30000000],
            [11, '2101', 'Utang Usaha', 'Kewajiban', 'KREDIT', 200],
            [16, '3101', 'Modal Owner', 'Ekuitas', 'KREDIT', 0],
            [17, '3102', 'Laba Ditahan', 'Ekuitas', 'KREDIT', 0],
        ];
        foreach ($accounts as [$id, $code, $name, $category, $normal, $opening]) {
            DB::table('akuns')->insert([
                'id' => $id,
                'kode_akun' => $code,
                'nama_akun' => $name,
                'kategori_akun' => $category,
                'sub_kategori' => $category === 'Ekuitas' ? 'Ekuitas' : 'Aset Lancar',
                'saldo_normal' => $normal,
                'saldo_awal' => $opening,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        DB::table('rekening_kas_banks')->insert([
            ['id' => 1, 'kode_rekening' => 'KAS-001', 'nama_rekening' => 'Kas Kecil', 'jenis' => 'KAS', 'saldo_awal' => 25500000, 'is_active' => true, 'akun_id' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'kode_rekening' => 'BANK-001', 'nama_rekening' => 'BCA', 'jenis' => 'BANK', 'saldo_awal' => 180250000, 'is_active' => true, 'akun_id' => 2, 'created_at' => now(), 'updated_at' => now()],
        ]);
        DB::table('pengaturan_akuns')->insert([
            'id' => 1, 'kunci_pengaturan' => 'AKUN_SALDO_AWAL', 'nama_pengaturan' => 'Akun Lawan Saldo Awal Persediaan',
            'akun_id' => 17, 'keterangan' => 'Test', 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('stok_awal_imports')->insert([
            'id' => 2, 'nomor_import' => 'SA-TEST-2', 'tanggal' => '2026-10-17 00:00:00',
            'file_hash' => str_repeat('a', 64), 'total_qty' => 13156, 'total_nilai' => 3578242664,
            'jurnal_id' => 27, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('stok_gudangs')->insert([
            [
                'id' => 1, 'barang_id' => 1, 'gudang_id' => 1, 'qty' => 13154,
                'harga_rata_rata' => 272000.00, 'nilai_persediaan' => 3577888000.00,
                'created_at' => now(), 'updated_at' => now(),
            ],
            [
                'id' => 2, 'barang_id' => 2, 'gudang_id' => 1, 'qty' => 2,
                'harga_rata_rata' => 177332.00, 'nilai_persediaan' => 354664.00,
                'created_at' => now(), 'updated_at' => now(),
            ],
        ]);
        DB::table('jurnals')->insert([
            'id' => 27, 'nomor_jurnal' => 'JU/2026/10/0001', 'tanggal' => '2026-10-17',
            'keterangan' => 'Saldo awal persediaan - SA-TEST-2', 'referensi_type' => 'STOK_AWAL_IMPORT',
            'referensi_id' => 2, 'total_debit' => 3578242664, 'total_kredit' => 3578242664,
            'is_system_generated' => true, 'is_posted' => true, 'created_by' => null,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('jurnal_details')->insert([
            ['id' => 1, 'jurnal_id' => 27, 'akun_id' => 6, 'debit' => 3578242664, 'kredit' => 0, 'keterangan' => 'Persediaan', 'created_at' => now(), 'updated_at' => now()],
            ['id' => 2, 'jurnal_id' => 27, 'akun_id' => 17, 'debit' => 0, 'kredit' => 3578242664, 'keterangan' => 'Laba Ditahan', 'created_at' => now(), 'updated_at' => now()],
        ]);
        $this->seedInventoryMovements();
    }

    private function seedInventoryMovements(): void
    {
        $openingRows = [];
        for ($i = 0; $i < 6578; $i++) {
            // 6,577 rows x Rp544,000 plus one Rp354,664 row = Rp3,578,242,664.
            $value = $i === 6577 ? 354664 : 544000;
            $openingRows[] = [
                'tanggal' => '2026-10-17 12:25:28',
                'barang_id' => 10000 + $i,
                'gudang_id' => 1,
                'tipe' => 'PENYESUAIAN',
                'qty_masuk' => 2,
                'qty_keluar' => 0,
                'harga_satuan' => $value / 2,
                'nilai' => $value,
                'saldo_qty' => 2,
                'saldo_nilai' => $value,
                'reference_type' => 'STOK_AWAL_IMPORT',
                'reference_id' => 2,
            ];
            if (count($openingRows) === 100) {
                DB::table('mutasi_stoks')->insert($openingRows);
                $openingRows = [];
            }
        }
        if ($openingRows !== []) DB::table('mutasi_stoks')->insert($openingRows);

        $date = '2026-10-17 14:29:32';
        DB::table('mutasi_gudangs')->insert([
            'id' => 2,
            'nomor_mutasi' => 'MG/2026/10/0001',
            'tanggal_mutasi' => $date,
            'gudang_asal_id' => 1,
            'gudang_tujuan_id' => 3,
            'catatan' => null,
            'created_by' => 1,
        ]);

        $items = [
            ['id' => 2, 'barang_id' => 4067, 'qty' => 1, 'harga_rata_rata' => 11500, 'nilai' => 11500],
            ['id' => 3, 'barang_id' => 4068, 'qty' => 1, 'harga_rata_rata' => 11300, 'nilai' => 11300],
            ['id' => 4, 'barang_id' => 4069, 'qty' => 1, 'harga_rata_rata' => 11300, 'nilai' => 11300],
        ];
        $stockRows = [];
        foreach ($items as $item) {
            DB::table('mutasi_gudang_items')->insert([
                'id' => $item['id'],
                'mutasi_gudang_id' => 2,
                'barang_id' => $item['barang_id'],
                'qty' => $item['qty'],
                'harga_rata_rata' => $item['harga_rata_rata'],
                'nilai' => $item['nilai'],
            ]);

            $stockRows[] = [
                'tanggal' => $date, 'barang_id' => $item['barang_id'], 'gudang_id' => 1,
                'tipe' => 'MUTASI_KELUAR', 'qty_masuk' => 0, 'qty_keluar' => $item['qty'],
                'harga_satuan' => $item['harga_rata_rata'], 'nilai' => $item['nilai'],
                'saldo_qty' => 1, 'saldo_nilai' => $item['nilai'],
                'reference_type' => 'MUTASI_GUDANG', 'reference_id' => $item['id'],
            ];
            $stockRows[] = [
                'tanggal' => $date, 'barang_id' => $item['barang_id'], 'gudang_id' => 3,
                'tipe' => 'MUTASI_MASUK', 'qty_masuk' => $item['qty'], 'qty_keluar' => 0,
                'harga_satuan' => $item['harga_rata_rata'], 'nilai' => $item['nilai'],
                'saldo_qty' => 1, 'saldo_nilai' => $item['nilai'],
                'reference_type' => 'MUTASI_GUDANG', 'reference_id' => $item['id'],
            ];
        }
        DB::table('mutasi_stoks')->insert($stockRows);
    }

    private function trialBalanceDebit(): string
    {
        return number_format((float) DB::table('akuns')->where('kode_akun', '1101')->value('saldo_awal')
            + (float) DB::table('akuns')->where('kode_akun', '1102')->value('saldo_awal')
            + (float) DB::table('akuns')->where('kode_akun', '1130')->value('saldo_awal')
            + (float) DB::table('jurnal_details')->where('akun_id', 6)->sum('debit'), 2, '.', '');
    }

    private function trialBalanceCredit(): string
    {
        return number_format((float) DB::table('akuns')->where('kode_akun', '3104')->value('saldo_awal')
            + (float) DB::table('jurnal_details')->where('akun_id', DB::table('akuns')->where('kode_akun', '3104')->value('id'))->sum('kredit'), 2, '.', '');
    }

    /** Compare monetary values by cents, not by driver-specific string formatting. */
    private function assertMoneyEquals(float $expected, mixed $actual): void
    {
        $this->assertSame(
            (int) round($expected * 100),
            (int) round((float) $actual * 100),
            'Expected monetary value '.number_format($expected, 2, '.', '').'; got '.number_format((float) $actual, 2, '.', '').'.'
        );
    }
}
