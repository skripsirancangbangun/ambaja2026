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
    Schema::create('penerimaan_barang_items', function (Blueprint $table) {
        $table->id();

        $table->foreignId('penerimaan_barang_id')
            ->constrained('penerimaan_barangs')
            ->cascadeOnDelete();

        $table->foreignId('purchase_order_item_id')
            ->constrained('purchase_order_items')
            ->restrictOnDelete();

        $table->foreignId('barang_id')
            ->constrained('barangs')
            ->restrictOnDelete();

        $table->decimal('qty_diterima', 15, 2);

        $table->decimal('harga_satuan', 18, 2);

        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('penerimaan_barang_items');
}
};
