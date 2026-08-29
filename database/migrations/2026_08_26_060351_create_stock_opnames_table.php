<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();

            $table->string('nomor_opname')->unique();

            $table->date('tanggal_opname');

            $table->foreignId('gudang_id')
                ->constrained('gudangs')
                ->restrictOnDelete();

            $table->enum('status', [
                'DRAFT',
                'SELESAI',
                'DISESUAIKAN',
            ])->default('DRAFT');

            $table->text('catatan')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_opnames');
    }
};