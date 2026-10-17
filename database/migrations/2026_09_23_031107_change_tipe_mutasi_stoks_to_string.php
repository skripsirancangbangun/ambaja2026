<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Mengubah kolom tipe yang sebelumnya ENUM
     * menjadi VARCHAR.
     *
     * Tujuannya supaya tipe transaksi baru
     * seperti:
     *
     * INVOICE_PENJUALAN
     * RETUR_PENJUALAN
     * PEMBATALAN_INVOICE
     * KONVERSI_MASUK
     * KONVERSI_KELUAR
     * WASTE
     *
     * tidak membutuhkan ALTER ENUM terus-menerus.
     */
    public function up(): void
    {
        Schema::table(
            'mutasi_stoks',
            function (Blueprint $table) {

                $table
                    ->string(
                        'tipe',
                        50
                    )
                    ->change();
            }
        );

        Schema::table(
            'mutasi_stoks',
            function (Blueprint $table) {

                $table
                    ->index(
                        'tipe'
                    );
            }
        );
    }

    public function down(): void
    {
        /*
         * Jangan dikembalikan ke ENUM lama.
         *
         * Setelah sistem memakai tipe mutasi baru,
         * rollback ke ENUM lama berisiko gagal
         * atau memotong data.
         *
         * Rollback hanya menghapus index.
         */
        Schema::table(
            'mutasi_stoks',
            function (Blueprint $table) {

                $table->dropIndex([
                    'tipe'
                ]);
            }
        );
    }
};