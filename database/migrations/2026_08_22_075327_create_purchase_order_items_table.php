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
    Schema::create('purchase_order_items', function (Blueprint $table) {
        $table->id();

        $table->foreignId('purchase_order_id')
            ->constrained('purchase_orders')
            ->cascadeOnDelete();

        $table->foreignId('barang_id')
            ->constrained('barangs')
            ->restrictOnDelete();

        $table->decimal('qty', 15, 2);
        $table->decimal('harga_satuan', 18, 2);

        $table->decimal('diskon', 18, 2)->default(0);
        $table->decimal('subtotal', 18, 2);

        $table->decimal('qty_diterima', 15, 2)->default(0);

        $table->text('keterangan')->nullable();

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
{
    Schema::dropIfExists('purchase_order_items');
}
};
