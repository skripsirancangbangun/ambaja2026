<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel transaksi konversi barang.
     *
     * Satu record = satu transaksi konversi.
     *
     * Contoh:
     * Hollow 6M 10 batang
     * ->
     * Hollow 2M 30 pcs
     */
    public function up(): void
    {
        Schema::create('conversions', function (Blueprint $table) {
            $table->id();

            /**
             * Nomor transaksi.
             *
             * Contoh:
             * KONV-00001
             */
            $table->string('nomor_konversi')->unique();

            /**
             * Tanggal transaksi.
             */
            $table->dateTime('tanggal');

            /**
             * Gudang tempat proses konversi.
             */
            $table->foreignId('gudang_id')
                ->constrained('gudangs')
                ->restrictOnDelete();

            /**
             * Total nilai persediaan
             * yang dialihkan melalui konversi.
             */
            $table->decimal(
                'total_nilai',
                18,
                2
            )->default(0);

            /**
             * Status transaksi.
             *
             * DRAFT  = belum memengaruhi stok
             * POSTED = sudah memengaruhi stok
             * CANCELLED = dibatalkan
             */
            $table->enum('status', [
                'DRAFT',
                'POSTED',
                'CANCELLED',
            ])->default('DRAFT');

            /**
             * Keterangan proses.
             */
            $table->text('keterangan')->nullable();

            /**
             * User yang membuat transaksi.
             */
            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /**
             * User yang melakukan posting.
             */
            $table->foreignId('posted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            /**
             * Waktu posting.
             */
            $table->timestamp('posted_at')->nullable();

            /**
             * Waktu pembatalan.
             */
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            $table->index([
                'tanggal',
                'status',
            ]);

            $table->index([
                'gudang_id',
                'tanggal',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversions');
    }
};