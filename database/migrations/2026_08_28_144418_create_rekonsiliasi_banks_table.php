<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rekonsiliasi_banks', function (Blueprint $table) {
            $table->id();

            $table->foreignId('transaksi_bank_id')
                ->constrained('transaksi_belum_teridentifikasis')
                ->restrictOnDelete();

            $table->foreignId('mutasi_kas_bank_id')
                ->constrained('mutasi_kas_banks')
                ->restrictOnDelete();

            $table->dateTime('tanggal_rekonsiliasi');

            $table->text('catatan')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique(
                'transaksi_bank_id',
                'rekonsiliasi_bank_transaksi_unique'
            );

            $table->unique(
                'mutasi_kas_bank_id',
                'rekonsiliasi_bank_mutasi_unique'
            );

            $table->index('tanggal_rekonsiliasi');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rekonsiliasi_banks');
    }
};