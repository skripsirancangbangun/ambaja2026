<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_opname_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('stock_opname_id')
                ->constrained('stock_opnames')
                ->cascadeOnDelete();

            $table->foreignId('barang_id')
                ->constrained('barangs')
                ->restrictOnDelete();

            /*
            |--------------------------------------------------------------------------
            | STOK SISTEM
            |--------------------------------------------------------------------------
            | Snapshot stok saat opname dibuat.
            */
            $table->decimal('stok_sistem', 15, 2)
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | STOK FISIK
            |--------------------------------------------------------------------------
            | Diisi berdasarkan hasil perhitungan fisik.
            */
            $table->decimal('stok_fisik', 15, 2)
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | SELISIH
            |--------------------------------------------------------------------------
            | stok_fisik - stok_sistem
            */
            $table->decimal('selisih', 15, 2)
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | HARGA RATA-RATA
            |--------------------------------------------------------------------------
            | Snapshot moving average saat opname dibuat.
            */
            $table->decimal('harga_rata_rata', 18, 2)
                ->default(0);

            /*
            |--------------------------------------------------------------------------
            | NILAI SELISIH
            |--------------------------------------------------------------------------
            | selisih × harga_rata_rata
            |
            | Bisa positif atau negatif.
            */
            $table->decimal('nilai_selisih', 18, 2)
                ->default(0);

            $table->text('keterangan')
                ->nullable();

            $table->timestamps();

            /*
            | Satu barang hanya satu kali
            | dalam satu dokumen Stock Opname.
            */
            $table->unique([
                'stock_opname_id',
                'barang_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_opname_items');
    }
};