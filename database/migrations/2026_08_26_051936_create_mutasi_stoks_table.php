<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('mutasi_stoks', function (Blueprint $table) {
        $table->id();

        $table->dateTime('tanggal');

        $table->foreignId('barang_id')
            ->constrained('barangs')
            ->restrictOnDelete();

        $table->foreignId('gudang_id')
            ->constrained('gudangs')
            ->restrictOnDelete();

        $table->enum('tipe', [
            'MASUK',
            'KELUAR',
            'PENYESUAIAN',
            'MUTASI_MASUK',
            'MUTASI_KELUAR',
            'RETUR_MASUK',
            'RETUR_KELUAR',
        ]);

        $table->decimal('qty_masuk', 15, 2)->default(0);
        $table->decimal('qty_keluar', 15, 2)->default(0);

        $table->decimal('harga_satuan', 18, 2)->default(0);
        $table->decimal('nilai', 18, 2)->default(0);

        $table->decimal('saldo_qty', 15, 2)->default(0);
        $table->decimal('saldo_nilai', 18, 2)->default(0);

        $table->string('reference_type')->nullable();
        $table->unsignedBigInteger('reference_id')->nullable();

        $table->text('keterangan')->nullable();

        $table->timestamps();

        $table->index([
            'reference_type',
            'reference_id',
        ]);
    });
}

public function down(): void
{
    Schema::dropIfExists('mutasi_stoks');
}
};
