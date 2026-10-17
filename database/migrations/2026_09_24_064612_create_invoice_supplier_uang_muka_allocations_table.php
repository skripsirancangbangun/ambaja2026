<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'invoice_supplier_uang_muka_allocations',
            function (Blueprint $table) {

                $table->id();

                /*
                |--------------------------------------------------------------------------
                | INVOICE SUPPLIER
                |--------------------------------------------------------------------------
                */

                $table->unsignedBigInteger(
                    'invoice_supplier_id'
                );

                $table->foreign(
                    'invoice_supplier_id',
                    'fk_isa_invoice'
                )
                    ->references('id')
                    ->on('invoice_suppliers')
                    ->cascadeOnDelete();


                /*
                |--------------------------------------------------------------------------
                | PURCHASE ORDER
                |--------------------------------------------------------------------------
                */

                $table->unsignedBigInteger(
                    'purchase_order_id'
                );

                $table->foreign(
                    'purchase_order_id',
                    'fk_isa_po'
                )
                    ->references('id')
                    ->on('purchase_orders')
                    ->cascadeOnDelete();


                /*
                |--------------------------------------------------------------------------
                | PEMBAYARAN PO SUPPLIER
                |--------------------------------------------------------------------------
                */

                $table->unsignedBigInteger(
                    'pembayaran_po_supplier_id'
                );

                $table->foreign(
                    'pembayaran_po_supplier_id',
                    'fk_isa_pembayaran_po'
                )
                    ->references('id')
                    ->on('pembayaran_po_suppliers')
                    ->cascadeOnDelete();


                /*
                |--------------------------------------------------------------------------
                | NILAI ALOKASI
                |--------------------------------------------------------------------------
                */

                $table->decimal(
                    'jumlah_alokasi',
                    18,
                    2
                );

                $table->date(
                    'tanggal_alokasi'
                );


                /*
                |--------------------------------------------------------------------------
                | CREATED BY
                |--------------------------------------------------------------------------
                */

                $table->unsignedBigInteger(
                    'created_by'
                )->nullable();

                $table->foreign(
                    'created_by',
                    'fk_isa_user'
                )
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();


                /*
                |--------------------------------------------------------------------------
                | TIMESTAMPS
                |--------------------------------------------------------------------------
                */

                $table->timestamps();


                /*
                |--------------------------------------------------------------------------
                | INDEX
                |--------------------------------------------------------------------------
                */

                $table->index(
                    [
                        'purchase_order_id',
                        'invoice_supplier_id',
                    ],
                    'idx_isa_po_invoice'
                );

                $table->index(
                    'pembayaran_po_supplier_id',
                    'idx_isa_pembayaran_po'
                );
            }
        );
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'invoice_supplier_uang_muka_allocations'
        );
    }
};