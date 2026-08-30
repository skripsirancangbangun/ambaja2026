<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mutasi_gudang_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('mutasi_gudang_id')
                ->constrained('mutasi_gudangs')
                ->cascadeOnDelete();

            $table->foreignId('barang_id')
                ->constrained('barangs')
                ->restrictOnDelete();

            $table->decimal('qty', 15, 2);

            /*
             * Harga moving average gudang asal saat mutasi dilakukan.
             * Disimpan agar nilai mutasi historis tidak berubah.
             */
            $table->decimal('harga_rata_rata', 18, 2);

            $table->decimal('nilai', 18, 2);

            $table->timestamps();

            /*
             * Satu barang cukup satu kali dalam satu dokumen mutasi.
             */
            $table->unique([
                'mutasi_gudang_id',
                'barang_id',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mutasi_gudang_items');
    }
};