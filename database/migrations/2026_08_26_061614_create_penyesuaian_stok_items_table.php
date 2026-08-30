<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penyesuaian_stok_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('penyesuaian_stok_id')
                ->constrained('penyesuaian_stoks')
                ->cascadeOnDelete();

            $table->foreignId('stock_opname_item_id')
                ->constrained('stock_opname_items')
                ->restrictOnDelete();

            $table->foreignId('barang_id')
                ->constrained('barangs')
                ->restrictOnDelete();

            /*
            |--------------------------------------------------------------------------
            | QTY SEBELUM
            |--------------------------------------------------------------------------
            | Saldo sistem sebelum penyesuaian.
            */
            $table->decimal('qty_sebelum', 15, 2);

            /*
            |--------------------------------------------------------------------------
            | QTY PENYESUAIAN
            |--------------------------------------------------------------------------
            | Selalu disimpan sebagai angka positif.
            | Arah penyesuaian ditentukan oleh kolom jenis.
            */
            $table->decimal('qty_penyesuaian', 15, 2);

            /*
            |--------------------------------------------------------------------------
            | QTY SESUDAH
            |--------------------------------------------------------------------------
            */
            $table->decimal('qty_sesudah', 15, 2);

            /*
            |--------------------------------------------------------------------------
            | JENIS
            |--------------------------------------------------------------------------
            */
            $table->enum('jenis', [
                'TAMBAH',
                'KURANG',
            ]);

            /*
            |--------------------------------------------------------------------------
            | HARGA RATA-RATA
            |--------------------------------------------------------------------------
            | Snapshot moving average saat penyesuaian dilakukan.
            */
            $table->decimal('harga_rata_rata', 18, 2)
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | NILAI PENYESUAIAN
            |--------------------------------------------------------------------------
            */
            $table->decimal('nilai_penyesuaian', 18, 2)
                ->default(0);

            $table->text('keterangan')->nullable();

            $table->timestamps();

            /*
             * Satu item hasil opname hanya boleh
             * disesuaikan satu kali.
             */
            $table->unique('stock_opname_item_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penyesuaian_stok_items');
    }
};