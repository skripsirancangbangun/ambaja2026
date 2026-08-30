<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mutasi_gudangs', function (Blueprint $table) {
            $table->id();

            $table->string('nomor_mutasi')->unique();

            $table->date('tanggal_mutasi');

            $table->foreignId('gudang_asal_id')
                ->constrained('gudangs')
                ->restrictOnDelete();

            $table->foreignId('gudang_tujuan_id')
                ->constrained('gudangs')
                ->restrictOnDelete();

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
        Schema::dropIfExists('mutasi_gudangs');
    }
};