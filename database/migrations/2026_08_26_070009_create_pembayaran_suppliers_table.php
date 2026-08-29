<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pembayaran_suppliers', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | NOMOR PEMBAYARAN
            |--------------------------------------------------------------------------
            |
            | Contoh:
            | PAY-SUP/2026/08/0001
            |
            */

            $table->string('nomor_pembayaran')->unique();


            /*
            |--------------------------------------------------------------------------
            | INVOICE SUPPLIER
            |--------------------------------------------------------------------------
            */

            $table->foreignId('invoice_supplier_id')
                ->constrained('invoice_suppliers')
                ->restrictOnDelete();


            /*
            |--------------------------------------------------------------------------
            | SUPPLIER
            |--------------------------------------------------------------------------
            */

            $table->foreignId('supplier_id')
                ->constrained('suppliers')
                ->restrictOnDelete();


            /*
            |--------------------------------------------------------------------------
            | INFORMASI PEMBAYARAN
            |--------------------------------------------------------------------------
            */

            $table->date('tanggal_pembayaran');

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

            $table->enum(
                'metode_pembayaran',
                [
                    'TRANSFER',
                    'TUNAI',
                    'GIRO',
                    'LAINNYA',
                ]
            )->default('TRANSFER');


            /*
            |--------------------------------------------------------------------------
            | KAS / REKENING BANK
            |--------------------------------------------------------------------------
            |
            | Untuk sekarang belum dibuat FK karena modul Kas & Bank
            | dikerjakan terpisah.
            |
            | Nanti field ini dapat dihubungkan ke tabel rekening kas/bank.
            |
            */

            $table->unsignedBigInteger(
                'rekening_kas_bank_id'
            )->nullable();


            /*
            |--------------------------------------------------------------------------
            | REFERENSI
            |--------------------------------------------------------------------------
            |
            | Contoh:
            | nomor transfer
            | nomor giro
            | nomor bukti pembayaran
            |
            */

            $table->string(
                'referensi'
            )->nullable();


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
                'tanggal_pembayaran'
            );

            $table->index([
                'supplier_id',
                'tanggal_pembayaran',
            ]);
        });
    }


    public function down(): void
    {
        Schema::dropIfExists(
            'pembayaran_suppliers'
        );
    }
};