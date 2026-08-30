<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penjualans', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_penjualan')->unique();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('gudang_id')->nullable()->constrained('gudangs')->nullOnDelete();
            $table->date('tanggal_penjualan');
            $table->date('tanggal_estimasi_kirim')->nullable();
            $table->text('catatan')->nullable();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('diskon', 15, 2)->default(0);
            $table->decimal('pajak_persen', 5, 2)->default(0);
            $table->decimal('pajak', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->enum('status', ['Draft', 'Dikonfirmasi', 'Diproses', 'Selesai', 'Dibatalkan'])->default('Draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penjualans');
    }
};
