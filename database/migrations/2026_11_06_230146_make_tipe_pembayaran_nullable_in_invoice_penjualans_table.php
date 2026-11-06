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
        Schema::table('invoice_penjualans', function (Blueprint $table) {
            $table->string('tipe_pembayaran')->nullable()->default(null)->change();
            $table->string('status_pembayaran')->default('Menunggu Pembayaran')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_penjualans', function (Blueprint $table) {
            $table->string('tipe_pembayaran')->default('Kredit')->change();
            $table->string('status_pembayaran')->default('Belum Lunas')->change();
        });
    }
};
