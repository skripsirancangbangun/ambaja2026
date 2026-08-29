<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mutasi_kas_banks', function (Blueprint $table) {
            $table->id();

            $table->dateTime('tanggal');

            $table->foreignId('rekening_kas_bank_id')
                ->constrained('rekening_kas_banks')
                ->restrictOnDelete();

            $table->enum('tipe', [
                'SALDO_AWAL',
                'UANG_MASUK',
                'UANG_KELUAR',
                'TRANSFER_MASUK',
                'TRANSFER_KELUAR',
                'PENERIMAAN_PELANGGAN',
                'PEMBAYARAN_SUPPLIER',
                'PENYESUAIAN',
            ]);

            $table->decimal('debit', 18, 2)->default(0);

            $table->decimal('kredit', 18, 2)->default(0);

            $table->decimal('saldo', 18, 2)->default(0);

            $table->string('reference_type', 100)->nullable();

            $table->unsignedBigInteger('reference_id')->nullable();

            $table->string('nomor_referensi', 150)->nullable();

            $table->text('keterangan')->nullable();

            $table->timestamps();

            $table->index([
                'rekening_kas_bank_id',
                'tanggal',
            ]);

            $table->index([
                'reference_type',
                'reference_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mutasi_kas_banks');
    }
};