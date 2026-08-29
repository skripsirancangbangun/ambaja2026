<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_rekening_aliases', function (Blueprint $table) {
            $table->id();

            $table->foreignId('supplier_id')
                ->constrained('suppliers')
                ->cascadeOnDelete();

            /*
            |--------------------------------------------------------------------------
            | NAMA YANG MUNCUL DI REKENING KORAN
            |--------------------------------------------------------------------------
            |
            | Contoh:
            | Supplier sistem : SAMUDRA BAJA
            | Nama bank       : BUDI SANTOSO
            |
            */

            $table->string('nama_alias');

            /*
            |--------------------------------------------------------------------------
            | NORMALISASI
            |--------------------------------------------------------------------------
            |
            | Disimpan juga versi normalized agar pencarian lebih mudah.
            |
            | Contoh:
            | "Budi Santoso" -> "budi santoso"
            |
            */

            $table->string('nama_alias_normalized');

            /*
            |--------------------------------------------------------------------------
            | REKENING BANK PERUSAHAAN
            |--------------------------------------------------------------------------
            |
            | Alias bisa spesifik untuk rekening perusahaan tertentu.
            |
            */

            $table->foreignId('rekening_kas_bank_id')
                ->nullable()
                ->constrained('rekening_kas_banks')
                ->nullOnDelete();

            /*
            |--------------------------------------------------------------------------
            | SUMBER PEMBUATAN
            |--------------------------------------------------------------------------
            */

            $table->string('sumber')
                ->default('REKONSILIASI');

            /*
            |--------------------------------------------------------------------------
            | STATUS
            |--------------------------------------------------------------------------
            */

            $table->boolean('is_active')
                ->default(true);

            /*
            |--------------------------------------------------------------------------
            | USER
            |--------------------------------------------------------------------------
            */

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | INDEX
            |--------------------------------------------------------------------------
            */

            $table->index([
                'supplier_id',
                'is_active',
            ]);

            $table->index([
                'nama_alias_normalized',
                'is_active',
            ]);

            /*
            |--------------------------------------------------------------------------
            | CEGAH DUPLIKAT
            |--------------------------------------------------------------------------
            */

            $table->unique([
                'supplier_id',
                'rekening_kas_bank_id',
                'nama_alias_normalized',
            ], 'supplier_alias_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'supplier_rekening_aliases'
        );
    }
};