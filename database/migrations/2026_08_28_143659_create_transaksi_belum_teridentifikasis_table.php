<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaksi_belum_teridentifikasis', function (Blueprint $table) {
            $table->id();

            $table->date('tanggal');

            $table->foreignId('rekening_kas_bank_id')
                ->constrained('rekening_kas_banks')
                ->restrictOnDelete();

            $table->enum('jenis', [
                'MASUK',
                'KELUAR',
            ]);

            $table->decimal('jumlah', 18, 2);

            $table->string(
                'nama_pengirim_penerima',
                200
            )->nullable();

            $table->string(
                'nomor_referensi_bank',
                200
            )->nullable();

            $table->text(
                'deskripsi_bank'
            )->nullable();

            $table->enum('status', [
                'BELUM_TERIDENTIFIKASI',
                'TERIDENTIFIKASI',
                'DIABAIKAN',
            ])->default('BELUM_TERIDENTIFIKASI');

            /*
            |--------------------------------------------------------------------------
            | REFERENSI HASIL IDENTIFIKASI
            |--------------------------------------------------------------------------
            |
            | Diisi setelah transaksi rekening koran berhasil dicocokkan
            | dengan transaksi internal melalui Rekonsiliasi Bank.
            |
            */

            $table->string(
                'matched_reference_type',
                100
            )->nullable();

            $table->unsignedBigInteger(
                'matched_reference_id'
            )->nullable();

            $table->dateTime(
                'identified_at'
            )->nullable();

            $table->foreignId('identified_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            /*
            |--------------------------------------------------------------------------
            | INDEX
            |--------------------------------------------------------------------------
            |
            | Nama index dibuat manual dan pendek supaya tidak melewati
            | batas panjang identifier MySQL / MariaDB.
            |
            */

            $table->index(
                'tanggal',
                'tbti_tanggal_idx'
            );

            $table->index(
                'jenis',
                'tbti_jenis_idx'
            );

            $table->index(
                'status',
                'tbti_status_idx'
            );

            $table->index(
                [
                    'rekening_kas_bank_id',
                    'tanggal',
                ],
                'tbti_rekening_tanggal_idx'
            );

            $table->index(
                [
                    'matched_reference_type',
                    'matched_reference_id',
                ],
                'tbti_matched_ref_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(
            'transaksi_belum_teridentifikasis'
        );
    }
};