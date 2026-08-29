<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('transaksi_belum_teridentifikasis', function (Blueprint $table) {

            $table->string('import_batch', 100)
                ->nullable()
                ->after('deskripsi_bank');

            $table->string('import_hash', 64)
                ->nullable()
                ->after('import_batch');

            $table->string('nama_file_import', 255)
                ->nullable()
                ->after('import_hash');

            $table->unique(
                'import_hash',
                'tbti_import_hash_unique'
            );

            $table->index(
                'import_batch',
                'tbti_import_batch_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::table('transaksi_belum_teridentifikasis', function (Blueprint $table) {

            $table->dropUnique(
                'tbti_import_hash_unique'
            );

            $table->dropIndex(
                'tbti_import_batch_idx'
            );

            $table->dropColumn([
                'import_batch',
                'import_hash',
                'nama_file_import',
            ]);
        });
    }
};