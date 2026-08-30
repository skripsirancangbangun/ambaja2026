<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_penjualans', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_invoice')->unique();
            $table->foreignId('penjualan_id')->nullable()->constrained('penjualans')->nullOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('gudang_id')->constrained('gudangs')->cascadeOnDelete();
            $table->date('tanggal_invoice');
            $table->date('tanggal_jatuh_tempo')->nullable();
            $table->enum('tipe_pembayaran', ['Tunai', 'Kredit'])->default('Kredit');
            $table->foreignId('rekening_kas_bank_id')->nullable()->constrained('rekening_kas_banks')->nullOnDelete();
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('diskon', 15, 2)->default(0);
            $table->decimal('pajak_persen', 5, 2)->default(0);
            $table->decimal('pajak', 15, 2)->default(0);
            $table->decimal('total', 15, 2)->default(0);
            $table->decimal('sudah_dibayar', 15, 2)->default(0);
            $table->decimal('sisa_piutang', 15, 2)->default(0);
            $table->enum('status_pembayaran', ['Belum Lunas', 'Sebagian', 'Lunas'])->default('Belum Lunas');
            $table->text('catatan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_penjualans');
    }
};
