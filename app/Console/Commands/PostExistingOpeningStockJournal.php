<?php

namespace App\Console\Commands;

use App\Models\Jurnal;
use App\Models\StokAwalImport;
use App\Models\StokGudang;
use App\Services\StokAwalAccountingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Throwable;

class PostExistingOpeningStockJournal extends Command
{
    protected $signature = 'ambaja:post-existing-opening-stock-journal {--execute : Buat jurnal setelah dry-run diperiksa}';
    protected $description = 'Buat jurnal pembukaan satu kali untuk saldo stok testing yang sudah ada, tanpa mengubah stok.';

    public function handle(StokAwalAccountingService $accountingService): int
    {
        $stocks = StokGudang::query()->orderBy('id')->get([
            'id', 'barang_id', 'gudang_id', 'qty', 'nilai_persediaan',
        ]);

        if ($stocks->isEmpty()) {
            $this->warn('Tidak ada saldo stok. Tidak ada tindakan.');
            return self::SUCCESS;
        }

        $totalQty = round((float) $stocks->sum(fn ($row) => (float) $row->qty), 2);
        $totalValue = round((float) $stocks->sum(fn ($row) => (float) $row->nilai_persediaan), 2);
        $snapshot = $stocks->map(fn ($row) => [
            (int) $row->id, (int) $row->barang_id, (int) $row->gudang_id,
            (string) $row->qty, (string) $row->nilai_persediaan,
        ])->all();
        $hash = hash('sha256', json_encode($snapshot, JSON_UNESCAPED_SLASHES));

        $this->info('Pemeriksaan saldo stok saat ini (tidak mengubah stok):');
        $this->table(['Metrik', 'Nilai'], [
            ['Baris saldo stok', number_format($stocks->count())],
            ['Total kuantitas', number_format($totalQty, 2, ',', '.')],
            ['Total nilai persediaan', number_format($totalValue, 2, ',', '.')],
        ]);

        if (!$this->option('execute')) {
            $this->warn('DRY RUN selesai. Belum ada jurnal yang dibuat. Jika angka ini memang saldo pembukaan testing dan akun lawan sudah dikonfigurasi, jalankan kembali dengan --execute.');
            return self::SUCCESS;
        }

        if ($totalValue <= 0) {
            $this->error('Total nilai persediaan harus lebih dari nol.');
            return self::FAILURE;
        }

        if (StokAwalImport::where('file_hash', $hash)->exists()) {
            $this->error('Jurnal untuk snapshot saldo ini sudah pernah diproses. Tidak ada perubahan.');
            return self::FAILURE;
        }

        if (Jurnal::whereIn('referensi_type', ['STOK_AWAL', 'STOK_AWAL_IMPORT'])->exists()) {
            $this->error('Sudah ada jurnal yang mereferensikan stok awal. Periksa buku besar dahulu; command dibatalkan untuk mencegah jurnal ganda.');
            return self::FAILURE;
        }

        if ($this->ask('Pastikan ini saldo pembukaan testing, bukan saldo periode berjalan. Ketik POST JURNAL STOK AWAL untuk melanjutkan') !== 'POST JURNAL STOK AWAL') {
            $this->warn('Dibatalkan. Tidak ada jurnal yang dibuat.');
            return self::FAILURE;
        }

        try {
            DB::transaction(function () use ($accountingService, $hash, $totalQty, $totalValue) {
                $batch = StokAwalImport::create([
                    'nomor_import' => 'SA-LEGACY-' . now()->format('YmdHis') . '-' . strtoupper(substr($hash, 0, 6)),
                    'tanggal' => now(),
                    'file_hash' => $hash,
                    'total_qty' => $totalQty,
                    'total_nilai' => $totalValue,
                    'jurnal_id' => null,
                    'created_by' => auth()->id(),
                ]);
                $jurnal = $accountingService->createOpeningInventoryJournal($batch, $totalValue);
                $batch->update(['jurnal_id' => $jurnal->id]);
            }, 3);
        } catch (Throwable $e) {
            report($e);
            $this->error('Jurnal gagal dibuat dan transaksi dibatalkan: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info('Jurnal pembukaan berhasil dibuat. Saldo stok tidak diubah.');
        $this->line('Periksa Buku Besar akun Persediaan dan akun lawan saldo awal, lalu pastikan Neraca seimbang.');
        return self::SUCCESS;
    }
}
