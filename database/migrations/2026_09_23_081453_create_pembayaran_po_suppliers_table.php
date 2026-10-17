<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(
            'pembayaran_po_suppliers',
            function (Blueprint $table) {
                $table->id();

                /*
                |--------------------------------------------------------------------------
                | NOMOR PEMBAYARAN
                |--------------------------------------------------------------------------
                */

                $table
                    ->string(
                        'nomor_pembayaran',
                        50
                    )
                    ->unique();

                /*
                |--------------------------------------------------------------------------
                | PURCHASE ORDER
                |--------------------------------------------------------------------------
                */

                $table
                    ->foreignId(
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

                $table
                    ->foreignId(
                        'supplier_id'
                    )
                    ->constrained(
                        'suppliers'
                    )
                    ->restrictOnDelete();

                /*
                |--------------------------------------------------------------------------
                | KAS / BANK
                |--------------------------------------------------------------------------
                */

                $table
                    ->foreignId(
                        'rekening_kas_bank_id'
                    )
                    ->constrained(
                        'rekening_kas_banks'
                    )
                    ->restrictOnDelete();

                /*
                |--------------------------------------------------------------------------
                | TANGGAL PEMBAYARAN
                |--------------------------------------------------------------------------
                */

                $table->date(
                    'tanggal_pembayaran'
                );

                /*
                |--------------------------------------------------------------------------
                | JUMLAH BAYAR
                |--------------------------------------------------------------------------
                */

                $table->decimal(
                    'jumlah_bayar',
                    18,
                    2
                );

                /*
                |--------------------------------------------------------------------------
                | METODE PEMBAYARAN
                |--------------------------------------------------------------------------
                */

                $table
                    ->string(
                        'metode_pembayaran',
                        50
                    )
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | NOMOR REFERENSI
                |--------------------------------------------------------------------------
                */

                $table
                    ->string(
                        'nomor_referensi',
                        100
                    )
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | BUKTI PEMBAYARAN
                |--------------------------------------------------------------------------
                */

                $table
                    ->string(
                        'bukti_pembayaran'
                    )
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | STATUS
                |--------------------------------------------------------------------------
                |
                | DRAFT
                | TERCATAT
                | DIBATALKAN
                |
                */

                $table
                    ->string(
                        'status',
                        20
                    )
                    ->default(
                        'DRAFT'
                    );

                /*
                |--------------------------------------------------------------------------
                | KETERANGAN
                |--------------------------------------------------------------------------
                */

                $table
                    ->text(
                        'keterangan'
                    )
                    ->nullable();

                /*
                |--------------------------------------------------------------------------
                | CREATED BY
                |--------------------------------------------------------------------------
                */

                $table
                    ->foreignId(
                        'created_by'
                    )
                    ->nullable()
                    ->constrained(
                        'users'
                    )
                    ->nullOnDelete();

                $table->timestamps();

                /*
                |--------------------------------------------------------------------------
                | INDEX
                |--------------------------------------------------------------------------
                |
                | Nama dibuat pendek secara manual karena MySQL
                | maksimal 64 karakter untuk nama index.
                |
                */

                $table->index(
                    [
                        'purchase_order_id',
                        'status',
                    ],
                    'ppo_po_status_idx'
                );

                $table->index(
                    [
                        'supplier_id',
                        'tanggal_pembayaran',
                    ],
                    'ppo_supplier_tgl_idx'
                );

                $table->index(
                    [
                        'rekening_kas_bank_id',
                        'tanggal_pembayaran',
                    ],
                    'ppo_rekening_tgl_idx'
                );
            }
        );
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'pembayaran_po_suppliers'
        );
    }
};