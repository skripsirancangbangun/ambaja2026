<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('retur_pembelians', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | NOMOR RETUR
            |--------------------------------------------------------------------------
            */

            $table->string(
                'nomor_retur'
            )->unique();


            /*
            |--------------------------------------------------------------------------
            | PENERIMAAN BARANG
            |--------------------------------------------------------------------------
            */

            $table->foreignId(
                'penerimaan_barang_id'
            )
                ->constrained(
                    'penerimaan_barangs'
                )
                ->restrictOnDelete();


            /*
            |--------------------------------------------------------------------------
            | PURCHASE ORDER
            |--------------------------------------------------------------------------
            */

            $table->foreignId(
                'purchase_order_id'
            )
                ->constrained(
                    'purchase_orders'
                )
                ->restrictOnDelete();


            /*
            |--------------------------------------------------------------------------
            | SUPPLIER
            |--------------------------------------------------------------------------
            */

            $table->foreignId(
                'supplier_id'
            )
                ->constrained(
                    'suppliers'
                )
                ->restrictOnDelete();


            /*
            |--------------------------------------------------------------------------
            | GUDANG
            |--------------------------------------------------------------------------
            */

            $table->foreignId(
                'gudang_id'
            )
                ->constrained(
                    'gudangs'
                )
                ->restrictOnDelete();


            /*
            |--------------------------------------------------------------------------
            | TANGGAL RETUR
            |--------------------------------------------------------------------------
            */

            $table->date(
                'tanggal_retur'
            );


            /*
            |--------------------------------------------------------------------------
            | ALASAN RETUR
            |--------------------------------------------------------------------------
            */

            $table->string(
                'alasan_retur'
            );


            /*
            |--------------------------------------------------------------------------
            | STATUS
            |--------------------------------------------------------------------------
            */

            $table->enum(
                'status',
                [
                    'DRAFT',
                    'DIPROSES',
                    'SELESAI',
                    'DIBATALKAN',
                ]
            )->default('DRAFT');


            /*
            |--------------------------------------------------------------------------
            | CATATAN
            |--------------------------------------------------------------------------
            */

            $table->text(
                'catatan'
            )->nullable();


            /*
            |--------------------------------------------------------------------------
            | USER PEMBUAT
            |--------------------------------------------------------------------------
            */

            $table->foreignId(
                'created_by'
            )
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();


            $table->timestamps();


            /*
            |--------------------------------------------------------------------------
            | INDEX
            |--------------------------------------------------------------------------
            */

            $table->index(
                'tanggal_retur'
            );

            $table->index(
                [
                    'supplier_id',
                    'tanggal_retur',
                ],
                'retur_supplier_tanggal_idx'
            );
        });
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'retur_pembelians'
        );
    }
};