<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_supplier_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('invoice_supplier_id')
                ->constrained('invoice_suppliers')
                ->cascadeOnDelete();

            $table->foreignId('barang_id')
                ->constrained('barangs')
                ->restrictOnDelete();

            $table->foreignId('penerimaan_barang_item_id')
                ->nullable()
                ->constrained('penerimaan_barang_items')
                ->restrictOnDelete();

            $table->decimal('qty', 15, 2);

            $table->decimal('harga_satuan', 18, 2);

            $table->decimal('diskon', 18, 2)
                ->default(0);

            $table->decimal('subtotal', 18, 2);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_supplier_items');
    }
};