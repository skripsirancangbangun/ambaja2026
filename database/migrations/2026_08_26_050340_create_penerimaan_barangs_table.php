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
    Schema::create('penerimaan_barangs', function (Blueprint $table) {
        $table->id();

        $table->string('nomor_penerimaan')->unique();

        $table->foreignId('purchase_order_id')
            ->constrained('purchase_orders')
            ->restrictOnDelete();

        $table->foreignId('gudang_id')
            ->constrained('gudangs')
            ->restrictOnDelete();

        $table->date('tanggal_penerimaan');

        $table->string('nomor_surat_jalan')
            ->nullable();

        $table->text('catatan')
            ->nullable();

        $table->foreignId('created_by')
            ->nullable()
            ->constrained('users')
            ->nullOnDelete();

        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('penerimaan_barangs');
}
};
