<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tindak_lanjut_piutangs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_penjualan_id')->constrained('invoice_penjualans')->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->date('tanggal_tindak_lanjut');
            $table->enum('jenis_tindak_lanjut', [
                'CATATAN_PENAGIHAN',
                'TELEPON_CUSTOMER',
                'JANJI_BAYAR',
                'SURAT_TAGIHAN',
                'SURAT_PERINGATAN_1',
                'SURAT_PERINGATAN_2',
                'SURAT_PERINGATAN_3'
            ])->default('CATATAN_PENAGIHAN');
            $table->date('tanggal_janji_bayar')->nullable();
            $table->text('catatan');
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tindak_lanjut_piutangs');
    }
};
