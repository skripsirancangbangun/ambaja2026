<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_penjualan_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_penjualan_id')->constrained('invoice_penjualans')->cascadeOnDelete();
            $table->foreignId('barang_id')->constrained('barangs')->cascadeOnDelete();
            $table->decimal('qty', 15, 2)->default(0);
            $table->decimal('harga_satuan', 15, 2)->default(0);
            $table->decimal('diskon', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->string('catatan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_penjualan_items');
    }
};
