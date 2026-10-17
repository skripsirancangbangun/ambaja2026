<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
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