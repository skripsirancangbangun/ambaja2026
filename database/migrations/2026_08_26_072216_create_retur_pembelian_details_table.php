<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retur_pembelian_details', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | HEADER RETUR
            |--------------------------------------------------------------------------
            */

            $table->foreignId('retur_pembelian_id')
                ->constrained('retur_pembelians')
                ->cascadeOnDelete();


            /*
            |--------------------------------------------------------------------------
            | ITEM PENERIMAAN
            |--------------------------------------------------------------------------
            |
            | Supaya retur benar-benar berasal dari barang
            | yang sudah pernah diterima.
            |
            */

            $table->foreignId('penerimaan_barang_item_id')
                ->constrained('penerimaan_barang_items')
                ->restrictOnDelete();


            /*
            |--------------------------------------------------------------------------
            | BARANG
            |--------------------------------------------------------------------------
            */

            $table->foreignId('barang_id')
                ->constrained('barangs')
                ->restrictOnDelete();


            /*
            |--------------------------------------------------------------------------
            | QTY RETUR
            |--------------------------------------------------------------------------
            */

            $table->decimal(
                'qty',
                15,
                2
            );


            /*
            |--------------------------------------------------------------------------
            | HARGA SATUAN
            |--------------------------------------------------------------------------
            |
            | Harga historis dari penerimaan / PO.
            |
            */

            $table->decimal(
                'harga_satuan',
                18,
                2
            );


            /*
            |--------------------------------------------------------------------------
            | SUBTOTAL
            |--------------------------------------------------------------------------
            |
            | qty × harga_satuan
            |
            */

            $table->decimal(
                'subtotal',
                18,
                2
            );


            /*
            |--------------------------------------------------------------------------
            | KETERANGAN
            |--------------------------------------------------------------------------
            */

            $table->text(
                'keterangan'
            )->nullable();


            $table->timestamps();


            /*
            |--------------------------------------------------------------------------
            | UNIQUE
            |--------------------------------------------------------------------------
            |
            | Satu item penerimaan hanya boleh muncul sekali
            | dalam satu dokumen retur.
            |
            | Nama index dibuat pendek secara manual karena
            | nama otomatis Laravel terlalu panjang untuk MySQL.
            |
            */

            $table->unique(
                [
                    'retur_pembelian_id',
                    'penerimaan_barang_item_id',
                ],
                'retur_detail_unique'
            );
        });
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'retur_pembelian_details'
        );
    }
};