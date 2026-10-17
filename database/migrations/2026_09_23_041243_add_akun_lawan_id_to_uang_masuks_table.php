<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table(
            'uang_masuks',
            function (Blueprint $table) {
                /*
                 * Nullable untuk menjaga kompatibilitas dengan data lama.
                 *
                 * Controller baru mewajibkan akun_lawan_id pada transaksi baru.
                 * Data lama yang sudah TERCATAT perlu direview sebelum dipakai
                 * sebagai laporan accounting.
                 */
                $table
                    ->foreignId('akun_lawan_id')
                    ->nullable()
                    ->after('rekening_kas_bank_id')
                    ->constrained('akuns')
                    ->restrictOnDelete();

                $table->index(
                    'akun_lawan_id'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::table(
            'uang_masuks',
            function (Blueprint $table) {
                $table->dropIndex([
                    'akun_lawan_id',
                ]);

                $table->dropForeign([
                    'akun_lawan_id',
                ]);

                $table->dropColumn(
                    'akun_lawan_id'
                );
            }
        );
    }
};
