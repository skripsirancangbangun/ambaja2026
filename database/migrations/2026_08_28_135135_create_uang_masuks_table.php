<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uang_masuks', function (Blueprint $table) {
            $table->id();

            $table->string('nomor_transaksi', 50)->unique();

            $table->date('tanggal');

            $table->foreignId('rekening_kas_bank_id')
                ->constrained('rekening_kas_banks')
                ->restrictOnDelete();

            $table->decimal('jumlah', 18, 2);

            $table->string('sumber', 150);

            $table->string('referensi', 150)->nullable();

            $table->text('keterangan')->nullable();

            $table->enum('status', [
                'DRAFT',
                'TERCATAT',
            ])->default('DRAFT');

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            $table->index('tanggal');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uang_masuks');
    }
};