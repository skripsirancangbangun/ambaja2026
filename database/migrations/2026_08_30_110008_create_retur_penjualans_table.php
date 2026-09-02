<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retur_penjualans', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_retur')->unique();
            $table->foreignId('invoice_penjualan_id')->constrained('invoice_penjualans')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('gudang_id')->constrained('gudangs')->cascadeOnDelete();
            $table->date('tanggal_retur');
            $table->decimal('total_retur', 15, 2)->default(0);
            $table->text('alasan')->nullable();
            $table->enum('status', ['Draft', 'Diproses', 'Selesai', 'Dibatalkan'])->default('Draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('retur_penjualans');
    }
};
