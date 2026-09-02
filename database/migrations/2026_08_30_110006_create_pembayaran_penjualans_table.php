<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembayaran_penjualans', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_pembayaran')->unique();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('rekening_kas_bank_id')->constrained('rekening_kas_banks')->cascadeOnDelete();
            $table->date('tanggal_pembayaran');
            $table->enum('metode_pembayaran', ['Transfer', 'Tunai', 'Giro', 'Cek'])->default('Transfer');
            $table->decimal('total_bayar', 15, 2)->default(0);
            $table->string('referensi_transaksi')->nullable();
            $table->text('catatan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pembayaran_penjualans');
    }
};
