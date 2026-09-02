<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('jurnals', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_jurnal')->unique();
            $table->date('tanggal');
            $table->text('keterangan');
            $table->string('referensi_type')->nullable(); // Optional: e.g. INVOICE_PENJUALAN, PEMBAYARAN, etc.
            $table->unsignedBigInteger('referensi_id')->nullable();
            $table->decimal('total_debit', 15, 2)->default(0);
            $table->decimal('total_kredit', 15, 2)->default(0);
            $table->boolean('is_posted')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('jurnals');
    }
};
