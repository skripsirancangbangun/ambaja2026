<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembayaran_penjualan_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pembayaran_penjualan_id')->constrained('pembayaran_penjualans')->cascadeOnDelete();
            $table->foreignId('invoice_penjualan_id')->constrained('invoice_penjualans')->cascadeOnDelete();
            $table->decimal('nominal_bayar', 15, 2)->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran_penjualan_details');
    }
};
