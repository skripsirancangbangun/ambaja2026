<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('rekening_kas_banks', function (Blueprint $table) {
            $table->id();

            /*
            |--------------------------------------------------------------------------
            | IDENTITAS REKENING
            |--------------------------------------------------------------------------
            */

            $table->string('kode_rekening', 30)->unique();

            $table->string('nama_rekening', 150);

            /*
             * KAS  = Kas Besar, Kas Kecil, dll.
             * BANK = Rekening bank perusahaan.
             */
            $table->enum('jenis', [
                'KAS',
                'BANK',
            ]);


            /*
            |--------------------------------------------------------------------------
            | INFORMASI BANK
            |--------------------------------------------------------------------------
            |
            | Nullable karena rekening dengan jenis KAS
            | tidak memiliki informasi bank.
            */

            $table->string('nama_bank', 100)->nullable();

            $table->string('nomor_rekening', 100)->nullable();

            $table->string('atas_nama', 150)->nullable();


            /*
            |--------------------------------------------------------------------------
            | SALDO AWAL
            |--------------------------------------------------------------------------
            |
            | Saldo saat rekening pertama kali dimasukkan
            | ke dalam sistem.
            */

            $table->decimal(
                'saldo_awal',
                18,
                2
            )->default(0);


            /*
            |--------------------------------------------------------------------------
            | STATUS
            |--------------------------------------------------------------------------
            */

            $table->boolean(
                'is_active'
            )->default(true);


            /*
            |--------------------------------------------------------------------------
            | CATATAN
            |--------------------------------------------------------------------------
            */

            $table->text(
                'catatan'
            )->nullable();


            $table->timestamps();
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists(
            'rekening_kas_banks'
        );
    }
};