<?php

namespace App\Console\Commands;

use App\Models\Jurnal;
use App\Models\JurnalDetail;
use App\Services\SystemAccountingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class RepairDummyOpeningBalances extends Command
{
    protected $signature = 'ambaja:repair-dummy-opening-balances
        {--apply : Terapkan koreksi setelah dry-run diperiksa}
        {--confirm= : Frasa konfirmasi wajib saat --apply}';

    protected $description = 'Koreksi saldo awal dummy Kas/Bank secara transaksional tanpa menghapus stok atau jurnal pembukaan.';

    private const CONFIRMATION = 'KOREKSI SALDO AWAL DUMMY AMBAJA';
    private const INVENTORY_VALUE = 3578242664.00;
    private const CASH_OPENING = 25500000.00;
    private const BANK_OPENING = 180250000.00;
    private const EQUITY_OPENING = 205750000.00;
    private const OPERATIONS_START_DATE = '2026-11-01';

    /** Nilai yang diharapkan pada database sebelum koreksi. */
    private const BEFORE_BALANCES = [
        '1101' => 50.00,
        '1102' => 150.00,
        '1103' => 72500000.00,
        '1104' => 41200000.00,
        '1120' => 42750000.00,
        '1201' => 85000000.00,
        '1202' => 15000000.00,
        '1203' => 150000000.00,
        '1204' => 30000000.00,
        '2101' => 200.00,
    ];

    private const CLEAR_CODES = ['1103', '1104', '1120', '1201', '1202', '1203', '1204', '2101'];
    private const TRANSACTION_TABLES = [
        'penjualans',
        'invoice_penjualans',
        'pembayaran_penjualans',
        'penerimaan_piutangs',
        'invoice_suppliers',
        'pembayaran_suppliers',
        'purchase_orders',
        'penerimaan_barangs',
        'uang_masuks',
        'uang_keluars',
        'transfer_kas_banks',
        'mutasi_kas_banks',
        'transaksi_belum_teridentifikasis',
        'rekonsiliasi_banks',
        'penyesuaian_stoks',
        'penyesuaian_stok_items',
        'stock_opnames',
        'stock_opname_items',
        'conversions',
        'conversion_items',
        'retur_pembelians',
        'retur_pembelian_details',
        'retur_pembelian_allocations',
        'retur_penjualans',
        'retur_penjualan_details',
        'retur_penjualan_items',
        'pembayaran_po_suppliers',
        'penerimaan_barang_items',
        'purchase_order_items',
        'invoice_supplier_items',
        'invoice_penjualan_items',
        'penjualan_items',
        'pembayaran_penjualan_details',
        'penerimaan_piutang_details',
    ];

    public function handle(SystemAccountingService $accounting): int
    {
        if (app()->environment('production')) {
            $this->error('Koreksi ini khusus database development/testing. APP_ENV=production diblokir.');
            return self::FAILURE;
        }

        $driver = DB::connection()->getDriverName();
        if (!in_array($driver, ['mysql', 'mariadb'], true) && !app()->environment('testing')) {
            $this->error('Gunakan MySQL/MariaDB. SQLite hanya diizinkan untuk automated test.');
            return self::FAILURE;
        }

        try {
            $state = $this->inspectState();
        } catch (Throwable $e) {
            $this->error('Preflight gagal: '.$e->getMessage());
            $this->line('Tidak ada data yang diubah. Periksa database dan kirim pesan error jika perlu.');
            return self::FAILURE;
        }

        if ($state['already_applied']) {
            $this->info('Koreksi saldo awal sudah pernah diterapkan dan hasil verifikasi masih cocok. Tidak ada perubahan baru.');
            $this->printTotals($state['totals']);
            return self::SUCCESS;
        }

        // Write the mode as plain console text so Artisan::output() captures
        // the exact marker consistently in automated tests and CLI environments.
        if ($this->option('apply')) {
            $this->line('MODE: APPLY DIMINTA — preflight berhasil');
        } else {
            $this->line('MODE: DRY RUN');
            $this->line('Tidak ada data yang diubah.');
        }
        $this->line('Database: '.DB::connection()->getDatabaseName());
        $this->table(['Item', 'Kondisi'], [
            ['Rekening operasional', 'Kas Kecil Rp25.500.000 dan BCA Rp180.250.000; mapping COA benar'],
            ['Mandiri / BRI', 'Tidak memiliki rekening, jurnal, atau pengaturan; akan dinonaktifkan'],
            ['Transaksi operasional lain', 'Seluruh tabel transaksi selain mutasi historis yang diverifikasi dalam preflight harus kosong'],
            ['Mutasi stok historis', '6.578 baris import awal + 6 baris mutasi gudang; 13.156 unit / Rp3.578.242.664'],
            ['Mutasi gudang', '1 header ID 2 dan 3 detail; setiap detail memiliki pasangan keluar/masuk yang cocok'],
            ['Jurnal stok awal', 'ID 27 · posted · debit/kredit Rp3.578.242.664'],
            ['Akun lawan pembukaan', 'Akan direklasifikasi dari 3102 ke 3104 dengan jurnal koreksi, bukan mengedit jurnal lama'],
        ]);

        if (!$this->option('apply')) {
            $this->warn('DRY RUN selesai. Belum ada perubahan.');
            $this->line('Jika hasil di atas sesuai, jalankan perintah APPLY pada panduan patch.');
            return self::SUCCESS;
        }

        if ($this->option('confirm') !== self::CONFIRMATION) {
            $this->error('Konfirmasi tidak cocok. Gunakan --confirm="'.self::CONFIRMATION.'". Tidak ada perubahan.');
            return self::FAILURE;
        }

        $snapshotPath = null;
        $reclassNumber = null;

        try {
            DB::transaction(function () use ($accounting, &$snapshotPath, &$reclassNumber): void {
                // Re-check every precondition while holding row locks to avoid stale dry-run assumptions.
                $state = $this->inspectState(lockRows: true);
                if ($state['already_applied']) {
                    return;
                }

                $snapshotPath = $this->writeSnapshot($state['snapshot']);

                $accounts = $state['accounts'];
                $now = now();

                // Restore only the two agreed operational balances.
                DB::table('akuns')->where('kode_akun', '1101')->update([
                    'saldo_awal' => self::CASH_OPENING,
                    'is_active' => true,
                    'updated_at' => $now,
                ]);
                DB::table('akuns')->where('kode_akun', '1102')->update([
                    'saldo_awal' => self::BANK_OPENING,
                    'is_active' => true,
                    'updated_at' => $now,
                ]);

                // Clear only the explicitly confirmed dummy opening balances.
                DB::table('akuns')->whereIn('kode_akun', self::CLEAR_CODES)->update([
                    'saldo_awal' => 0,
                    'updated_at' => $now,
                ]);
                DB::table('akuns')->whereIn('kode_akun', ['1103', '1104'])->update([
                    'is_active' => false,
                    'updated_at' => $now,
                ]);

                $equity = DB::table('akuns')->where('kode_akun', '3104')->first();
                if (!$equity) {
                    $equityId = DB::table('akuns')->insertGetId([
                        'kode_akun' => '3104',
                        'nama_akun' => 'Ekuitas Saldo Awal Sementara',
                        'kategori_akun' => 'Ekuitas',
                        'sub_kategori' => 'Ekuitas',
                        'saldo_normal' => 'KREDIT',
                        'saldo_awal' => self::EQUITY_OPENING,
                        'is_active' => true,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                } else {
                    $equityId = (int) $equity->id;
                    DB::table('akuns')->where('id', $equityId)->update([
                        'nama_akun' => 'Ekuitas Saldo Awal Sementara',
                        'kategori_akun' => 'Ekuitas',
                        'sub_kategori' => 'Ekuitas',
                        'saldo_normal' => 'KREDIT',
                        'saldo_awal' => self::EQUITY_OPENING,
                        'is_active' => true,
                        'updated_at' => $now,
                    ]);
                }

                // Future stock-opening imports must use the dedicated opening-equity account.
                DB::table('pengaturan_akuns')
                    ->where('kunci_pengaturan', 'AKUN_SALDO_AWAL')
                    ->update(['akun_id' => $equityId, 'updated_at' => $now]);

                // Keep posted stock journal #27 immutable; reclassify its old retained-earnings
                // counterpart using a separate, balanced system-generated journal.
                $sourceJournal = $state['source_journal'];
                $reclassNumber = $accounting->generateDocumentNumber('JURNAL_UMUM', $sourceJournal->tanggal);
                $reclass = Jurnal::create([
                    'nomor_jurnal' => $reclassNumber,
                    'tanggal' => $sourceJournal->tanggal,
                    'keterangan' => 'Reklasifikasi akun lawan saldo awal persediaan ke Ekuitas Saldo Awal - batch SA ID 2',
                    'is_system_generated' => true,
                    'referensi_type' => 'OPENING_BALANCE_RECLASSIFICATION',
                    'referensi_id' => 2,
                    'total_debit' => self::INVENTORY_VALUE,
                    'total_kredit' => self::INVENTORY_VALUE,
                    'is_posted' => true,
                    'created_by' => null,
                ]);

                JurnalDetail::create([
                    'jurnal_id' => $reclass->id,
                    'akun_id' => (int) $accounts['3102']->id,
                    'debit' => self::INVENTORY_VALUE,
                    'kredit' => 0,
                    'keterangan' => 'Reklasifikasi lawan jurnal pembukaan persediaan dari Laba Ditahan.',
                ]);
                JurnalDetail::create([
                    'jurnal_id' => $reclass->id,
                    'akun_id' => $equityId,
                    'debit' => 0,
                    'kredit' => self::INVENTORY_VALUE,
                    'keterangan' => 'Pemindahan ke Ekuitas Saldo Awal Sementara.',
                ]);

                $this->assertFinalState();
            }, 3);
        } catch (Throwable $e) {
            report($e);
            $this->error('Koreksi gagal dan transaksi database dibatalkan: '.$e->getMessage());
            if ($snapshotPath) $this->line('Snapshot sebelum koreksi tetap tersedia: '.$snapshotPath);
            return self::FAILURE;
        }

        $this->info('Koreksi saldo awal berhasil diterapkan. Data barang dan jurnal stok awal ID 27 tidak dihapus atau diedit.');
        if ($snapshotPath) $this->line('Snapshot sebelum koreksi: '.$snapshotPath);
        if ($reclassNumber) $this->line('Jurnal reklasifikasi baru: '.$reclassNumber);
        $this->printTotals($this->calculateTrialBalance());

        return self::SUCCESS;
    }

    private function inspectState(bool $lockRows = false): array
    {
        $requiredTables = array_merge([
            'akuns', 'rekening_kas_banks', 'pengaturan_akuns', 'jurnals',
            'jurnal_details', 'stok_awal_imports', 'stok_gudangs',
            'mutasi_stoks', 'mutasi_gudangs', 'mutasi_gudang_items',
        ], self::TRANSACTION_TABLES);

        foreach ($requiredTables as $table) {
            if (!Schema::hasTable($table)) {
                throw new RuntimeException("Tabel {$table} tidak ditemukan. Jangan jalankan koreksi pada struktur database yang belum lengkap.");
            }
        }
        if (!Schema::hasColumn('rekening_kas_banks', 'akun_id') || !Schema::hasColumn('jurnals', 'is_system_generated')) {
            throw new RuntimeException('Kolom relasi rekening/flag jurnal belum lengkap. Jalankan audit struktur terlebih dahulu.');
        }

        if ($this->isAlreadyApplied()) {
            return ['already_applied' => true, 'totals' => $this->calculateTrialBalance()];
        }

        $codes = array_merge(array_keys(self::BEFORE_BALANCES), ['1130', '3102', '3104']);
        $query = DB::table('akuns')->whereIn('kode_akun', $codes);
        if ($lockRows) $query->lockForUpdate();
        $rows = $query->get()->keyBy('kode_akun');

        foreach (array_keys(self::BEFORE_BALANCES) as $code) {
            if (!$rows->has($code)) throw new RuntimeException("COA {$code} tidak ditemukan.");
            if (!$this->moneyEquals($rows[$code]->saldo_awal, self::BEFORE_BALANCES[$code])) {
                throw new RuntimeException("Saldo awal COA {$code} berubah dari nilai audit. Diharapkan ".self::BEFORE_BALANCES[$code].', ditemukan '.$rows[$code]->saldo_awal.'. Koreksi dihentikan.');
            }
        }
        if (!$rows->has('1130') || !$this->moneyEquals($rows['1130']->saldo_awal, 0)) {
            throw new RuntimeException('Saldo awal COA Persediaan 1130 harus Rp0 karena nilai stok sudah dicatat oleh jurnal pembukaan.');
        }
        if (!$rows->has('3102') || $rows['3102']->saldo_normal !== 'KREDIT') {
            throw new RuntimeException('COA 3102 Laba Ditahan tidak sesuai kondisi audit.');
        }

        // PHP casts numeric-string array keys (for example '1101') to integers.
        // Normalize both lists to strings before the strict comparison so the
        // preflight compares account codes by value, not by PHP's key type.
        $nonZero = DB::table('akuns')
            ->whereRaw('ABS(saldo_awal) > 0.009')
            ->pluck('kode_akun')
            ->map(fn ($value) => (string) $value)
            ->sort(SORT_STRING)
            ->values()
            ->all();
        $expectedNonZero = array_map('strval', array_keys(self::BEFORE_BALANCES));
        sort($expectedNonZero, SORT_STRING);
        if ($nonZero !== $expectedNonZero) {
            throw new RuntimeException('Daftar COA dengan saldo awal nonzero berubah. Ada saldo lain yang tidak termasuk hasil audit.');
        }

        $expectedNormal = [
            '1101' => 'DEBIT', '1102' => 'DEBIT', '1103' => 'DEBIT', '1104' => 'DEBIT',
            '1120' => 'DEBIT', '1201' => 'DEBIT', '1202' => 'KREDIT', '1203' => 'DEBIT',
            '1204' => 'KREDIT', '2101' => 'KREDIT',
        ];
        foreach ($expectedNormal as $code => $normal) {
            if ($rows[$code]->saldo_normal !== $normal) throw new RuntimeException("Saldo normal COA {$code} tidak sesuai hasil audit.");
        }

        $bankRows = DB::table('rekening_kas_banks as r')
            ->leftJoin('akuns as a', 'a.id', '=', 'r.akun_id')
            ->select('r.*', 'a.kode_akun')
            ->orderBy('r.id')
            ->get();
        if ($bankRows->count() !== 2) throw new RuntimeException('Jumlah rekening harus tepat 2 sesuai keputusan: Kas Kecil dan BCA.');
        $expectedAccounts = ['KAS-001' => '1101', 'BANK-001' => '1102'];
        $expectedRekeningBalances = ['KAS-001' => self::CASH_OPENING, 'BANK-001' => self::BANK_OPENING];
        foreach ($bankRows as $rekening) {
            if (!isset($expectedAccounts[$rekening->kode_rekening])) throw new RuntimeException('Ditemukan rekening di luar Kas Kecil/BCA: '.$rekening->kode_rekening);
            if ((string) $rekening->kode_akun !== $expectedAccounts[$rekening->kode_rekening]) throw new RuntimeException('Mapping rekening '.$rekening->kode_rekening.' ke COA tidak sesuai.');
            if (!(bool) $rekening->is_active) throw new RuntimeException('Rekening '.$rekening->kode_rekening.' tidak aktif.');
            if (!$this->moneyEquals($rekening->saldo_awal, $expectedRekeningBalances[$rekening->kode_rekening])) throw new RuntimeException('Saldo awal rekening '.$rekening->kode_rekening.' tidak sesuai target audit.');
        }

        foreach (self::TRANSACTION_TABLES as $table) {
            if (DB::table($table)->count() !== 0) throw new RuntimeException("Tabel {$table} tidak kosong. Ada transaksi yang harus diaudit sebelum koreksi.");
        }

        // Historical stock-opening import and warehouse transfers are allowed only
        // when their complete, known audit fingerprint matches exactly.
        $inventoryMovementAudit = $this->validateKnownInventoryMovements($lockRows);

        if (DB::table('jurnals')->count() !== 1 || DB::table('jurnal_details')->count() !== 2) {
            throw new RuntimeException('Jumlah jurnal/detail berubah dari hasil audit (1 jurnal dan 2 detail). Periksa sebelum koreksi.');
        }
        if (DB::table('stok_awal_imports')->count() !== 1) throw new RuntimeException('Jumlah batch import stok awal berubah dari hasil audit.');

        $batch = DB::table('stok_awal_imports')->where('id', 2)->first();
        $sourceJournal = DB::table('jurnals')->where('id', 27)->first();
        if (!$batch || !$sourceJournal) throw new RuntimeException('Batch stok awal ID 2 atau jurnal ID 27 tidak ditemukan.');
        if ((int) $batch->jurnal_id !== 27 || !$this->moneyEquals($batch->total_qty, 13156) || !$this->moneyEquals($batch->total_nilai, self::INVENTORY_VALUE)) {
            throw new RuntimeException('Data batch stok awal ID 2 berubah dari hasil audit.');
        }
        $stockTotals = DB::table('stok_gudangs')->selectRaw('COALESCE(SUM(qty), 0) AS total_qty, COALESCE(SUM(nilai_persediaan), 0) AS total_nilai')->first();
        if (!$stockTotals || !$this->moneyEquals($stockTotals->total_qty, 13156) || !$this->moneyEquals($stockTotals->total_nilai, self::INVENTORY_VALUE)) {
            throw new RuntimeException('Total stok aktual di stok_gudangs tidak sama dengan batch pembukaan. Periksa kartu stok sebelum koreksi.');
        }
        if (!(bool) $sourceJournal->is_posted || !(bool) $sourceJournal->is_system_generated
            || $sourceJournal->referensi_type !== 'STOK_AWAL_IMPORT' || (int) $sourceJournal->referensi_id !== 2
            || !$this->moneyEquals($sourceJournal->total_debit, self::INVENTORY_VALUE)
            || !$this->moneyEquals($sourceJournal->total_kredit, self::INVENTORY_VALUE)) {
            throw new RuntimeException('Header jurnal pembukaan ID 27 berubah dari hasil audit.');
        }
        if (substr((string) $sourceJournal->tanggal, 0, 10) >= self::OPERATIONS_START_DATE
            || substr((string) $batch->tanggal, 0, 10) !== substr((string) $sourceJournal->tanggal, 0, 10)) {
            throw new RuntimeException('Tanggal import stok/jurnal tidak sesuai cut-off 1 November 2026. Audit tanggal pembukaan secara manual.');
        }
        if (Schema::hasTable('penutupan_periodes')) {
            $sourceDate = strtotime((string) $sourceJournal->tanggal);
            $periodLocked = DB::table('penutupan_periodes')
                ->where('bulan', (int) date('n', $sourceDate))
                ->where('tahun', (int) date('Y', $sourceDate))
                ->where('status', 'LOCKED')
                ->exists();
            if ($periodLocked) throw new RuntimeException('Periode tanggal jurnal pembukaan sudah ditutup. Jangan membuat jurnal reklasifikasi pada periode terkunci.');
        }

        $details = DB::table('jurnal_details as d')
            ->join('akuns as a', 'a.id', '=', 'd.akun_id')
            ->where('d.jurnal_id', 27)
            ->get(['a.kode_akun', 'd.debit', 'd.kredit']);
        $detailMap = $details->keyBy('kode_akun');
        if ($details->count() !== 2
            || !$detailMap->has('1130') || !$detailMap->has('3102')
            || !$this->moneyEquals($detailMap['1130']->debit, self::INVENTORY_VALUE)
            || !$this->moneyEquals($detailMap['1130']->kredit, 0)
            || !$this->moneyEquals($detailMap['3102']->debit, 0)
            || !$this->moneyEquals($detailMap['3102']->kredit, self::INVENTORY_VALUE)) {
            throw new RuntimeException('Detail jurnal pembukaan ID 27 berubah. Tidak akan dibuat jurnal koreksi otomatis.');
        }

        $noJournalAccounts = ['1101', '1102', '1103', '1104', '1120', '1201', '1202', '1203', '1204', '2101'];
        $usedAccountDetails = DB::table('jurnal_details as d')
            ->join('akuns as a', 'a.id', '=', 'd.akun_id')
            ->whereIn('a.kode_akun', $noJournalAccounts)
            ->count();
        if ($usedAccountDetails !== 0) throw new RuntimeException('Ada jurnal yang menggunakan akun saldo dummy selain jurnal stok awal. Koreksi dihentikan.');

        foreach (['1103', '1104'] as $code) {
            $accountId = (int) $rows[$code]->id;
            $refs = DB::table('rekening_kas_banks')->where('akun_id', $accountId)->count()
                + DB::table('pengaturan_akuns')->where('akun_id', $accountId)->count();
            if ($refs !== 0) throw new RuntimeException("COA {$code} masih direferensikan rekening/pengaturan.");
        }

        $setting = DB::table('pengaturan_akuns')->where('kunci_pengaturan', 'AKUN_SALDO_AWAL')->first();
        if (!$setting) throw new RuntimeException('Pengaturan AKUN_SALDO_AWAL tidak ditemukan.');
        if ((int) $setting->akun_id !== (int) $rows['3102']->id) throw new RuntimeException('Pengaturan AKUN_SALDO_AWAL tidak lagi menunjuk ke COA 3102. Periksa manual.');
        if (DB::table('pengaturan_akuns')->where('akun_id', $rows['3102']->id)->count() !== 1) {
            throw new RuntimeException('COA 3102 masih dipakai pengaturan lain selain AKUN_SALDO_AWAL. Periksa mapping sebelum reklasifikasi.');
        }

        $equity = $rows->get('3104');
        if ($equity && ((int) $equity->id === (int) $rows['3102']->id
            || $equity->kategori_akun !== 'Ekuitas'
            || $equity->saldo_normal !== 'KREDIT'
            || !$this->moneyEquals($equity->saldo_awal, 0)
            || DB::table('jurnal_details')->where('akun_id', $equity->id)->exists())) {
            throw new RuntimeException('COA 3104 sudah ada tetapi tidak kosong/tidak sesuai. Periksa manual sebelum melanjutkan.');
        }

        $snapshot = [
            'created_at' => now()->toIso8601String(),
            'database' => DB::connection()->getDatabaseName(),
            'environment' => app()->environment(),
            'accounts' => DB::table('akuns')->whereIn('kode_akun', array_merge(array_keys(self::BEFORE_BALANCES), ['3102', '3104']))->orderBy('kode_akun')->get()->toArray(),
            'setting' => $setting,
            'rekening' => $bankRows->toArray(),
            'source_journal' => $sourceJournal,
            'source_journal_details' => DB::table('jurnal_details')->where('jurnal_id', 27)->orderBy('id')->get()->toArray(),
            'stock_import' => $batch,
            'stock_totals' => $stockTotals,
            'inventory_movement_audit' => $inventoryMovementAudit,
        ];

        return [
            'already_applied' => false,
            'accounts' => $rows,
            'source_journal' => $sourceJournal,
            'snapshot' => $snapshot,
            'totals' => null,
        ];
    }

    /**
     * Verify the exact historical stock movements already reviewed in phpMyAdmin.
     * These rows are retained; they are not operational transactions to be cleared.
     *
     * Expected fingerprint:
     * - 6,578 STOK_AWAL_IMPORT/PENYESUAIAN rows for batch 2;
     * - 13,156 units and Rp357,824,264 opening inventory value;
     * - one warehouse transfer header (ID 2), three item details;
     * - six MUTASI_GUDANG stock rows, one outgoing and one incoming per item.
     */
    private function validateKnownInventoryMovements(bool $lockRows = false): array
    {
        if ($lockRows) {
            // Lock the existing stock ledger rows and warehouse documents again
            // inside the APPLY transaction to reduce the chance of a stale preflight.
            DB::table('mutasi_stoks')->orderBy('id')->lockForUpdate()->get(['id']);
            DB::table('mutasi_gudangs')->orderBy('id')->lockForUpdate()->get(['id']);
            DB::table('mutasi_gudang_items')->orderBy('id')->lockForUpdate()->get(['id']);
        }

        $totalStockRows = DB::table('mutasi_stoks')->count();
        $openingTypeRows = DB::table('mutasi_stoks')
            ->where('reference_type', 'STOK_AWAL_IMPORT')
            ->count();
        $opening = DB::table('mutasi_stoks')
            ->where('reference_type', 'STOK_AWAL_IMPORT')
            ->where('reference_id', 2)
            ->where('tipe', 'PENYESUAIAN')
            ->selectRaw('COUNT(*) AS row_count, COALESCE(SUM(qty_masuk), 0) AS total_qty_masuk, COALESCE(SUM(qty_keluar), 0) AS total_qty_keluar, COALESCE(SUM(nilai), 0) AS total_nilai')
            ->first();

        $warehouseRows = DB::table('mutasi_stoks')
            ->where('reference_type', 'MUTASI_GUDANG')
            ->orderBy('reference_id')
            ->orderBy('id')
            ->get();

        if ($totalStockRows !== 6584
            || $openingTypeRows !== 6578
            || (int) $opening->row_count !== 6578
            || $warehouseRows->count() !== 6
            || $totalStockRows !== $openingTypeRows + $warehouseRows->count()
            || !$this->moneyEquals($opening->total_qty_masuk, 13156)
            || !$this->moneyEquals($opening->total_qty_keluar, 0)
            || !$this->moneyEquals($opening->total_nilai, self::INVENTORY_VALUE)) {
            throw new RuntimeException('Riwayat mutasi stok tidak cocok dengan audit: harus ada 6.578 mutasi import awal batch 2 dan 6 mutasi gudang berpasangan. Tidak ada data yang diubah.');
        }

        $headers = DB::table('mutasi_gudangs')->orderBy('id')->get();
        if ($headers->count() !== 1) {
            throw new RuntimeException('Riwayat mutasi gudang harus tepat 1 dokumen sesuai hasil audit.');
        }
        $header = $headers->first();
        if ((int) $header->id !== 2
            || (int) $header->gudang_asal_id !== 1
            || (int) $header->gudang_tujuan_id !== 3
            || (int) $header->gudang_asal_id === (int) $header->gudang_tujuan_id
            || substr((string) $header->tanggal_mutasi, 0, 10) >= self::OPERATIONS_START_DATE) {
            throw new RuntimeException('Header mutasi gudang tidak cocok dengan dokumen historis ID 2 yang sudah diaudit.');
        }

        $items = DB::table('mutasi_gudang_items')
            ->where('mutasi_gudang_id', $header->id)
            ->orderBy('id')
            ->get();
        $allItemsCount = DB::table('mutasi_gudang_items')->count();
        if ($items->count() !== 3 || $allItemsCount !== 3
            || $items->pluck('barang_id')->unique()->count() !== 3) {
            throw new RuntimeException('Detail mutasi gudang harus tepat 3 item unik pada dokumen ID 2.');
        }

        $totalTransferQty = 0.0;
        $totalTransferValue = 0.0;
        foreach ($items as $item) {
            $qty = (float) $item->qty;
            $unitCost = (float) $item->harga_rata_rata;
            $lineValue = (float) $item->nilai;
            if ($qty <= 0 || $unitCost <= 0
                || !$this->moneyEquals($qty * $unitCost, $lineValue)) {
                throw new RuntimeException('Qty, harga rata-rata, dan nilai detail mutasi gudang tidak konsisten pada item ID '.$item->id.'.');
            }

            $pair = $warehouseRows->where('reference_id', $item->id);
            $outgoing = $pair->where('tipe', 'MUTASI_KELUAR')->values();
            $incoming = $pair->where('tipe', 'MUTASI_MASUK')->values();
            if ($pair->count() !== 2 || $outgoing->count() !== 1 || $incoming->count() !== 1) {
                throw new RuntimeException('Mutasi gudang item ID '.$item->id.' tidak memiliki tepat satu pasangan keluar/masuk.');
            }

            $out = $outgoing->first();
            $in = $incoming->first();
            foreach ([$out, $in] as $movement) {
                if ((int) $movement->barang_id !== (int) $item->barang_id
                    || !$this->moneyEquals($movement->harga_satuan, $unitCost)
                    || !$this->moneyEquals($movement->nilai, $lineValue)
                    || substr((string) $movement->tanggal, 0, 10) !== substr((string) $header->tanggal_mutasi, 0, 10)) {
                    throw new RuntimeException('Barang, harga, nilai, atau tanggal mutasi stok tidak cocok dengan detail gudang item ID '.$item->id.'.');
                }
            }

            if ((int) $out->gudang_id !== (int) $header->gudang_asal_id
                || !$this->moneyEquals($out->qty_keluar, $qty)
                || !$this->moneyEquals($out->qty_masuk, 0)
                || (int) $in->gudang_id !== (int) $header->gudang_tujuan_id
                || !$this->moneyEquals($in->qty_masuk, $qty)
                || !$this->moneyEquals($in->qty_keluar, 0)) {
                throw new RuntimeException('Qty atau gudang asal/tujuan pada pasangan mutasi item ID '.$item->id.' tidak cocok.');
            }

            $totalTransferQty += $qty;
            $totalTransferValue += $lineValue;
        }

        if (!$this->moneyEquals($totalTransferQty, 3)
            || !$this->moneyEquals($totalTransferValue, 34100)) {
            throw new RuntimeException('Total mutasi gudang tidak cocok dengan hasil audit: diharapkan 3 unit senilai Rp34.100.');
        }

        return [
            'opening_stock_mutations' => [
                'count' => (int) $opening->row_count,
                'total_qty_masuk' => (float) $opening->total_qty_masuk,
                'total_qty_keluar' => (float) $opening->total_qty_keluar,
                'total_nilai' => (float) $opening->total_nilai,
            ],
            'warehouse_transfer' => [
                'header' => $header,
                'items' => $items->toArray(),
                'stock_mutations' => $warehouseRows->toArray(),
                'total_qty' => round($totalTransferQty, 2),
                'total_nilai' => round($totalTransferValue, 2),
            ],
        ];
    }

    private function isAlreadyApplied(): bool
    {
        if (!Schema::hasTable('akuns') || !Schema::hasTable('jurnals') || !Schema::hasTable('jurnal_details') || !Schema::hasTable('pengaturan_akuns')) {
            return false;
        }

        $accounts = DB::table('akuns')->whereIn('kode_akun', ['1101', '1102', '1103', '1104', '1120', '1201', '1202', '1203', '1204', '2101', '3102', '3104'])->get()->keyBy('kode_akun');
        foreach (['1101' => self::CASH_OPENING, '1102' => self::BANK_OPENING, '1103' => 0, '1104' => 0, '1120' => 0, '1201' => 0, '1202' => 0, '1203' => 0, '1204' => 0, '2101' => 0, '3104' => self::EQUITY_OPENING] as $code => $amount) {
            if (!$accounts->has($code) || !$this->moneyEquals($accounts[$code]->saldo_awal, $amount)) return false;
        }
        if ((bool) $accounts['1103']->is_active || (bool) $accounts['1104']->is_active) return false;
        if (!$accounts->has('3102')) return false;

        $settingCode = DB::table('pengaturan_akuns as p')
            ->join('akuns as a', 'a.id', '=', 'p.akun_id')
            ->where('p.kunci_pengaturan', 'AKUN_SALDO_AWAL')
            ->value('a.kode_akun');
        if ($settingCode !== '3104') return false;

        $reclass = DB::table('jurnals')->where('referensi_type', 'OPENING_BALANCE_RECLASSIFICATION')->where('referensi_id', 2)->first();
        if (!$reclass || !(bool) $reclass->is_posted || !$this->moneyEquals($reclass->total_debit, self::INVENTORY_VALUE) || !$this->moneyEquals($reclass->total_kredit, self::INVENTORY_VALUE)) return false;

        $original = DB::table('jurnals')->where('id', 27)->first();
        if (!$original || $original->referensi_type !== 'STOK_AWAL_IMPORT' || (int) $original->referensi_id !== 2 || !(bool) $original->is_posted) return false;

        $reclassDetails = DB::table('jurnal_details as d')
            ->join('akuns as a', 'a.id', '=', 'd.akun_id')
            ->where('d.jurnal_id', $reclass->id)
            ->get(['a.kode_akun', 'd.debit', 'd.kredit'])
            ->keyBy('kode_akun');
        if ($reclassDetails->count() !== 2
            || !$reclassDetails->has('3102') || !$reclassDetails->has('3104')
            || !$this->moneyEquals($reclassDetails['3102']->debit, self::INVENTORY_VALUE)
            || !$this->moneyEquals($reclassDetails['3102']->kredit, 0)
            || !$this->moneyEquals($reclassDetails['3104']->debit, 0)
            || !$this->moneyEquals($reclassDetails['3104']->kredit, self::INVENTORY_VALUE)) return false;

        $totals = $this->calculateTrialBalance();
        return $this->moneyEquals($totals['debit'], 3783992664) && $this->moneyEquals($totals['credit'], 3783992664);
    }

    private function writeSnapshot(array $snapshot): string
    {
        $directory = storage_path('app/private/accounting-repairs');
        File::ensureDirectoryExists($directory);
        $filename = 'opening-balance-before-'.now()->format('Ymd-His').'-'.Str::lower(Str::random(6)).'.json';
        $path = $directory.DIRECTORY_SEPARATOR.$filename;
        $written = File::put($path, json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        if ($written === false) throw new RuntimeException('Gagal membuat snapshot backup sebelum koreksi.');
        return $path;
    }

    private function assertFinalState(): void
    {
        $expected = [
            '1101' => self::CASH_OPENING,
            '1102' => self::BANK_OPENING,
            '1103' => 0.0,
            '1104' => 0.0,
            '1120' => 0.0,
            '1201' => 0.0,
            '1202' => 0.0,
            '1203' => 0.0,
            '1204' => 0.0,
            '2101' => 0.0,
            '3104' => self::EQUITY_OPENING,
        ];
        $rows = DB::table('akuns')->whereIn('kode_akun', array_keys($expected))->get()->keyBy('kode_akun');
        foreach ($expected as $code => $balance) {
            if (!$rows->has($code) || !$this->moneyEquals($rows[$code]->saldo_awal, $balance)) {
                throw new RuntimeException("Verifikasi saldo akhir COA {$code} gagal.");
            }
        }
        foreach (['1103', '1104'] as $code) {
            if ((bool) $rows[$code]->is_active) throw new RuntimeException("COA {$code} seharusnya nonaktif.");
        }

        $setting = DB::table('pengaturan_akuns as p')
            ->join('akuns as a', 'a.id', '=', 'p.akun_id')
            ->where('p.kunci_pengaturan', 'AKUN_SALDO_AWAL')
            ->value('a.kode_akun');
        if ($setting !== '3104') throw new RuntimeException('Pengaturan AKUN_SALDO_AWAL belum menunjuk COA 3104.');

        $reclass = DB::table('jurnals')->where('referensi_type', 'OPENING_BALANCE_RECLASSIFICATION')->where('referensi_id', 2)->first();
        if (!$reclass || !(bool) $reclass->is_posted
            || !$this->moneyEquals($reclass->total_debit, self::INVENTORY_VALUE)
            || !$this->moneyEquals($reclass->total_kredit, self::INVENTORY_VALUE)) {
            throw new RuntimeException('Jurnal reklasifikasi pembukaan tidak valid.');
        }
        $reclassDetails = DB::table('jurnal_details as d')
            ->join('akuns as a', 'a.id', '=', 'd.akun_id')
            ->where('d.jurnal_id', $reclass->id)
            ->get(['a.kode_akun', 'd.debit', 'd.kredit'])
            ->keyBy('kode_akun');
        if ($reclassDetails->count() !== 2 || !$reclassDetails->has('3102') || !$reclassDetails->has('3104')
            || !$this->moneyEquals($reclassDetails['3102']->debit, self::INVENTORY_VALUE)
            || !$this->moneyEquals($reclassDetails['3104']->kredit, self::INVENTORY_VALUE)) {
            throw new RuntimeException('Detail jurnal reklasifikasi tidak sesuai.');
        }

        $originalDetails = DB::table('jurnal_details as d')
            ->join('akuns as a', 'a.id', '=', 'd.akun_id')
            ->where('d.jurnal_id', 27)
            ->get(['a.kode_akun', 'd.debit', 'd.kredit'])
            ->keyBy('kode_akun');
        if ($originalDetails->count() !== 2 || !$originalDetails->has('1130') || !$originalDetails->has('3102')
            || !$this->moneyEquals($originalDetails['1130']->debit, self::INVENTORY_VALUE)
            || !$this->moneyEquals($originalDetails['3102']->kredit, self::INVENTORY_VALUE)) {
            throw new RuntimeException('Jurnal stok awal ID 27 berubah. Koreksi dibatalkan.');
        }

        $totals = $this->calculateTrialBalance();
        if (!$this->moneyEquals($totals['debit'], 3783992664.00) || !$this->moneyEquals($totals['credit'], 3783992664.00)) {
            throw new RuntimeException('Neraca saldo setelah koreksi tidak seimbang. Debit '.$totals['debit'].'; kredit '.$totals['credit'].'.');
        }

        $bankCheck = DB::table('rekening_kas_banks as r')
            ->join('akuns as a', 'a.id', '=', 'r.akun_id')
            ->leftJoin('mutasi_kas_banks as m', 'm.rekening_kas_bank_id', '=', 'r.id')
            ->whereIn('r.kode_rekening', ['KAS-001', 'BANK-001'])
            ->groupBy('r.id', 'r.kode_rekening', 'r.saldo_awal', 'a.saldo_awal', 'a.saldo_normal')
            ->selectRaw('r.kode_rekening, r.saldo_awal AS saldo_rekening, a.saldo_awal AS saldo_coa, COALESCE(SUM(m.debit),0) AS mutasi_debit, COALESCE(SUM(m.kredit),0) AS mutasi_kredit')
            ->get();
        foreach ($bankCheck as $row) {
            $subledger = (float) $row->saldo_rekening + (float) $row->mutasi_debit - (float) $row->mutasi_kredit;
            if (!$this->moneyEquals($subledger, $row->saldo_coa)) throw new RuntimeException('Saldo subledger dan COA tidak cocok pada '.$row->kode_rekening.'.');
        }
    }

    private function calculateTrialBalance(): array
    {
        $posted = DB::table('jurnal_details as d')
            ->join('jurnals as j', 'j.id', '=', 'd.jurnal_id')
            ->where('j.is_posted', 1)
            ->groupBy('d.akun_id')
            ->selectRaw('d.akun_id, SUM(d.debit) AS debit, SUM(d.kredit) AS kredit');

        $rows = DB::table('akuns as a')
            ->leftJoinSub($posted, 'p', 'p.akun_id', '=', 'a.id')
            ->get([
                'a.saldo_normal', 'a.saldo_awal',
                DB::raw('COALESCE(p.debit, 0) AS posted_debit'),
                DB::raw('COALESCE(p.kredit, 0) AS posted_kredit'),
            ]);

        $debit = 0.0;
        $credit = 0.0;
        foreach ($rows as $row) {
            $opening = (float) $row->saldo_awal;
            $postedDebit = (float) $row->posted_debit;
            $postedCredit = (float) $row->posted_kredit;
            $normalBalance = $row->saldo_normal === 'DEBIT'
                ? $opening + $postedDebit - $postedCredit
                : $opening + $postedCredit - $postedDebit;
            if ($normalBalance >= 0) {
                if ($row->saldo_normal === 'DEBIT') $debit += $normalBalance;
                else $credit += $normalBalance;
            } else {
                if ($row->saldo_normal === 'DEBIT') $credit += abs($normalBalance);
                else $debit += abs($normalBalance);
            }
        }

        return ['debit' => round($debit, 2), 'credit' => round($credit, 2)];
    }

    private function printTotals(?array $totals): void
    {
        if (!$totals) return;
        $this->table(['Neraca saldo', 'Nilai'], [
            ['Total debit', 'Rp'.number_format($totals['debit'], 2, ',', '.')],
            ['Total kredit', 'Rp'.number_format($totals['credit'], 2, ',', '.')],
            ['Selisih', 'Rp'.number_format(abs($totals['debit'] - $totals['credit']), 2, ',', '.')],
        ]);
    }

    private function moneyEquals(mixed $actual, float|int $expected): bool
    {
        return (int) round(((float) $actual) * 100) === (int) round(((float) $expected) * 100);
    }
}
