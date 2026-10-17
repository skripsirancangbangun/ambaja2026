<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Detail barang dalam transaksi konversi.
     *
     * TIPE:
     *
     * INPUT
     *  = barang yang dikonsumsi/dikeluarkan
     *
     * OUTPUT
     *  = barang hasil konversi
     *
     * WASTE
     *  = sisa/waste proses
     */
    public function up(): void
    {
        Schema::create('conversion_items', function (Blueprint $table) {
            $table->id();

            /**
             * Header konversi.
             */
            $table->foreignId('conversion_id')
                ->constrained('conversions')
                ->cascadeOnDelete();

            /**
             * Barang.
             */
            $table->foreignId('barang_id')
                ->constrained('barangs')
                ->restrictOnDelete();

            /**
             * Jenis item.
             */
            $table->enum('tipe', [
                'INPUT',
                'OUTPUT',
                'WASTE',
            ]);

            /**
             * Qty barang.
             */
            $table->decimal(
                'qty',
                15,
                2
            )->default(0);

            /**
             * Satuan mengikuti barang.
             *
             * Disimpan juga sebagai snapshot
             * supaya histori tidak berubah
             * ketika master satuan berubah.
             */
            $table->string('satuan')->nullable();

            /**
             * Ukuran/panjang barang.
             *
             * Contoh:
             * 6.00 meter
             * 2.00 meter
             */
            $table->decimal(
                'panjang',
                15,
                4
            )->nullable();

            /**
             * Berat jika dibutuhkan.
             *
             * Contoh:
             * 25.50 kg
             */
            $table->decimal(
                'berat',
                15,
                4
            )->nullable();

            /**
             * HPP pada saat konversi.
             *
             * INPUT:
             * HPP barang asal.
             *
             * OUTPUT:
             * HPP hasil yang dihitung sistem.
             *
             * WASTE:
             * nilai waste.
             */
            $table->decimal(
                'hpp',
                18,
                2
            )->default(0);

            /**
             * Total nilai item.
             */
            $table->decimal(
                'nilai',
                18,
                2
            )->default(0);

            /**
             * Keterangan item.
             */
            $table->text('keterangan')->nullable();

            $table->timestamps();

            $table->index([
                'conversion_id',
                'tipe',
            ]);

            $table->index([
                'barang_id',
                'tipe',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversion_items');
    }
};