<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekonsiliasi_bank_audits', function (Blueprint $table) {
            $table->id();

            // ID disimpan tanpa foreign key agar audit tetap ada setelah
            // record transaksi/rekonsiliasi terkait berubah atau dihapus.
            $table->unsignedBigInteger('rekonsiliasi_id');
            $table->unsignedBigInteger('transaksi_bank_id');
            $table->unsignedBigInteger('mutasi_kas_bank_id');

            $table->string('aksi', 30);
            $table->json('data_sebelum');
            $table->text('alasan')->nullable();

            $table->foreignId('dibatalkan_oleh')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->dateTime('dibatalkan_pada');
            $table->timestamps();

            $table->index('transaksi_bank_id', 'rba_transaksi_bank_idx');
            $table->index('mutasi_kas_bank_id', 'rba_mutasi_kas_bank_idx');
            $table->index('dibatalkan_pada', 'rba_dibatalkan_pada_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekonsiliasi_bank_audits');
    }
};
