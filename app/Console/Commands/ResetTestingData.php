<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Throwable;

class ResetTestingData extends Command
{
    protected $signature = 'ambaja:reset-testing-data {--execute : Jalankan reset setelah dry-run} {--allow-production : Izinkan APP_ENV=production}';
    protected $description = 'Reset transaksi testing AMBAJA sambil mempertahankan master data.';

    private array $tablesToReset = [
        'jurnal_details', 'stok_awal_imports', 'jurnals',
        'conversion_items', 'conversions',
        'penyesuaian_stok_items', 'penyesuaian_stoks',
        'stock_opname_items', 'stock_opnames',
        'mutasi_gudang_items', 'mutasi_gudangs',
        'mutasi_stoks', 'stok_gudangs',
        'retur_pembelian_allocations', 'invoice_supplier_uang_muka_allocations',
        'pembayaran_po_suppliers', 'retur_pembelian_details', 'retur_pembelians',
        'invoice_supplier_items', 'pembayaran_suppliers', 'invoice_suppliers',
        'penerimaan_barang_items', 'penerimaan_barangs',
        'purchase_order_items', 'purchase_orders',
        'pembayaran_penjualan_details', 'penerimaan_piutang_details',
        'penerimaan_piutangs', 'tindak_lanjut_piutangs', 'kartu_piutangs',
        'retur_penjualan_items', 'retur_penjualan_details', 'retur_penjualans',
        'pembayaran_penjualans', 'invoice_penjualan_items', 'invoice_penjualans',
        'penjualan_items', 'penjualans',
        'rekonsiliasi_banks', 'transaksi_belum_teridentifikasis',
        'uang_keluar_details', 'uang_keluars', 'uang_masuks',
        'transfer_kas_banks', 'mutasi_kas_banks',
        'penutupan_periodes',
    ];

    public function handle(): int
    {
        if (!in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            $this->error('Command ini hanya mendukung MySQL/MariaDB.');
            return self::FAILURE;
        }
        if (app()->environment('production') && !$this->option('allow-production')) {
            $this->error('APP_ENV=production. Reset diblokir; gunakan hanya pada database testing/dev.');
            return self::FAILURE;
        }

        $tables = array_values(array_filter(array_unique($this->tablesToReset), fn ($table) => Schema::hasTable($table)));
        $counts = [];
        foreach ($tables as $table) $counts[$table] = DB::table($table)->count();

        $this->info($this->option('execute') ? 'MODE: EKSEKUSI DIMINTA' : 'MODE: DRY RUN — TIDAK ADA DATA DIHAPUS');
        $this->table(['Tabel transaksi', 'Baris saat ini'], collect($counts)->map(fn ($count, $table) => [$table, number_format($count)])->values()->all());
        $this->line('Master data dipertahankan: barang, gudang, kategori, satuan, supplier, customer, COA, rekening kas/bank, pengaturan akun, profil perusahaan, user, dan konfigurasi penomoran.');
        $this->line('Saldo awal master akun/rekening tidak diubah.');

        if (!$this->option('execute')) {
            $this->warn('Dry-run selesai. Periksa tabel dan jumlah baris di atas. Untuk reset, jalankan dengan --execute.');
            return self::SUCCESS;
        }

        try {
            $foreignKeys = $this->foreignKeys();
            $this->checkExternalReferences($tables, $foreignKeys);
            $ordered = $this->childFirst($tables, $foreignKeys);
        } catch (Throwable $e) {
            $this->error('Reset dibatalkan saat pemeriksaan dependensi: ' . $e->getMessage());
            $this->line('Tidak ada data yang dihapus.');
            return self::FAILURE;
        }

        $this->warn('Tindakan ini menghapus transaksi testing pada tabel di atas, termasuk mutasi/saldo stok, dokumen pembelian/penjualan, pembayaran, konversi, jurnal, dan penutupan periode.');
        $this->warn('Pastikan backup SQL tersedia dan database yang aktif benar.');
        if ($this->ask('Ketik persis RESET DATA TESTING AMBAJA untuk melanjutkan') !== 'RESET DATA TESTING AMBAJA') {
            $this->error('Konfirmasi tidak cocok. Reset dibatalkan.');
            return self::FAILURE;
        }

        try {
            DB::transaction(function () use ($ordered) {
                foreach ($ordered as $table) DB::table($table)->delete();
            }, 3);
        } catch (Throwable $e) {
            report($e);
            $this->error('Reset gagal; transaksi database dibatalkan: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info('Reset transaksi testing selesai. Master data dipertahankan.');
        $this->comment('Berikutnya import stok_awal_dummy_ambaja_GDG-01.csv melalui menu Stok Barang.');
        $this->warn('Command ini tidak membuat jurnal saldo awal persediaan. Jurnal pembukaan perlu dibuat/divalidasi terpisah jika laporan neraca perlu mencerminkan stok.');
        return self::SUCCESS;
    }

    private function foreignKeys(): array
    {
        return DB::select('SELECT TABLE_NAME AS child_table, COLUMN_NAME AS child_column, REFERENCED_TABLE_NAME AS parent_table, REFERENCED_COLUMN_NAME AS parent_column FROM information_schema.KEY_COLUMN_USAGE WHERE TABLE_SCHEMA = DATABASE() AND REFERENCED_TABLE_NAME IS NOT NULL');
    }

    private function checkExternalReferences(array $tables, array $foreignKeys): void
    {
        $selected = array_fill_keys($tables, true);
        foreach ($foreignKeys as $fk) {
            if (!isset($selected[$fk->parent_table]) || isset($selected[$fk->child_table]) || !Schema::hasTable($fk->child_table)) continue;
            $child = $this->q($fk->child_table);
            $childCol = $this->q($fk->child_column);
            $parent = $this->q($fk->parent_table);
            $parentCol = $this->q($fk->parent_column);
            $row = DB::selectOne("SELECT COUNT(*) AS aggregate FROM {$child} c INNER JOIN {$parent} p ON c.{$childCol} = p.{$parentCol}");
            if ((int) ($row->aggregate ?? 0) > 0) {
                throw new RuntimeException("Tabel {$fk->child_table} masih mereferensikan {$fk->parent_table}, tetapi tidak termasuk daftar reset. Periksa tabel tersebut sebelum melanjutkan.");
            }
        }
    }

    /** Urutkan child sebelum parent; FK tetap aktif dan tidak memakai TRUNCATE. */
    private function childFirst(array $tables, array $foreignKeys): array
    {
        $selected = array_fill_keys($tables, true);
        $incoming = array_fill_keys($tables, 0);
        $edges = [];
        foreach ($foreignKeys as $fk) {
            $child = $fk->child_table; $parent = $fk->parent_table;
            if ($child === $parent || !isset($selected[$child], $selected[$parent])) continue;
            if (!isset($edges[$child][$parent])) { $edges[$child][$parent] = true; $incoming[$parent]++; }
        }
        $queue = [];
        foreach ($incoming as $table => $degree) if ($degree === 0) $queue[] = $table;
        $ordered = [];
        while ($queue) {
            $table = array_shift($queue); $ordered[] = $table;
            foreach (array_keys($edges[$table] ?? []) as $parent) {
                $incoming[$parent]--;
                if ($incoming[$parent] === 0) $queue[] = $parent;
            }
        }
        if (count($ordered) !== count($tables)) throw new RuntimeException('Siklus foreign key ditemukan di antara tabel transaksi. Reset otomatis dihentikan.');
        return $ordered;
    }

    private function q(string $name): string { return '`' . str_replace('`', '``', $name) . '`'; }
}
