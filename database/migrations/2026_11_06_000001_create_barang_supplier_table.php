<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('barang_supplier', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('supplier_id')
                ->constrained('suppliers')
                ->cascadeOnDelete();
            $table->foreignId('barang_id')
                ->constrained('barangs')
                ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['supplier_id', 'barang_id'], 'barang_supplier_unique');
            $table->index('barang_id', 'barang_supplier_barang_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('barang_supplier');
    }
};
