
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Perubahan ENUM ini khusus MySQL.
        // SQLite menggunakan tipe kolom yang kompatibel untuk pengujian.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement("
            ALTER TABLE mutasi_kas_banks
            MODIFY COLUMN tipe ENUM(
                'SALDO_AWAL',
                'UANG_MASUK',
                'UANG_KELUAR',
                'TRANSFER_MASUK',
                'TRANSFER_KELUAR',
                'PENERIMAAN_PELANGGAN',
                'PEMBAYARAN_SUPPLIER',
                'PEMBAYARAN_PO_SUPPLIER',
                'PENYESUAIAN'
            ) NOT NULL
        ");
    }

    public function down(): void
    {
        // Tidak perlu mengubah skema SQLite.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Jangan menghapus nilai ENUM jika sudah digunakan transaksi.
        $hasPayment = DB::table('mutasi_kas_banks')
            ->where('tipe', 'PEMBAYARAN_PO_SUPPLIER')
            ->exists();

            if ($hasPayment) {
                throw new \RuntimeException(
                    'Rollback ditolak karena tipe PEMBAYARAN_PO_SUPPLIER sudah digunakan.'
                );
            }

        DB::statement("
            ALTER TABLE mutasi_kas_banks
            MODIFY COLUMN tipe ENUM(
                'SALDO_AWAL',
                'UANG_MASUK',
                'UANG_KELUAR',
                'TRANSFER_MASUK',
                'TRANSFER_KELUAR',
                'PENERIMAAN_PELANGGAN',
                'PEMBAYARAN_SUPPLIER',
                'PENYESUAIAN'
            ) NOT NULL
        ");
    }
};
