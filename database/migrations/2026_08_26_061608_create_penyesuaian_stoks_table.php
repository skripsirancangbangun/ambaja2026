<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penyesuaian_stoks', function (Blueprint $table) {
            $table->id();

            $table->string('nomor_penyesuaian')->unique();

            $table->date('tanggal_penyesuaian');

            $table->foreignId('stock_opname_id')
                ->constrained('stock_opnames')
                ->restrictOnDelete();

            $table->foreignId('gudang_id')
                ->constrained('gudangs')
                ->restrictOnDelete();

            $table->text('catatan')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            /*
             * Satu Stock Opname hanya boleh
             * menghasilkan satu Penyesuaian Stok.
             */
            $table->unique('stock_opname_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penyesuaian_stoks');
    }
};