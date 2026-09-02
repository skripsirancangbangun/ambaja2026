<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. KARTU PIUTANG CUSTOMER
        if (!Schema::hasTable('kartu_piutangs')) {
            Schema::create('kartu_piutangs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
                $table->date('tanggal');
                $table->string('nomor_referensi');
                $table->enum('jenis_transaksi', ['SALDO_AWAL', 'PENJUALAN', 'PEMBAYARAN', 'RETUR', 'PEMBATALAN']);
                $table->decimal('debit', 15, 2)->default(0);
                $table->decimal('kredit', 15, 2)->default(0);
                $table->decimal('saldo_akhir', 15, 2)->default(0);
                $table->text('keterangan')->nullable();
                $table->timestamps();
            });
        }

        // 2. PENERIMAAN PIUTANG
        if (!Schema::hasTable('penerimaan_piutangs')) {
            Schema::create('penerimaan_piutangs', function (Blueprint $table) {
                $table->id();
                $table->string('nomor_penerimaan')->unique();
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
                $table->foreignId('rekening_kas_bank_id')->constrained('rekening_kas_banks')->cascadeOnDelete();
                $table->date('tanggal');
                $table->decimal('total_dibayar', 15, 2);
                $table->text('catatan')->nullable();
                $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // 3. PENERIMAAN PIUTANG DETAIL
        if (!Schema::hasTable('penerimaan_piutang_details')) {
            Schema::create('penerimaan_piutang_details', function (Blueprint $table) {
                $table->id();
                $table->foreignId('penerimaan_piutang_id')->constrained('penerimaan_piutangs')->cascadeOnDelete();
                $table->foreignId('invoice_penjualan_id')->constrained('invoice_penjualans')->cascadeOnDelete();
                $table->decimal('nominal_dibayar', 15, 2);
                $table->timestamps();
            });
        }

        // 4. NOTA RETUR PENJUALAN
        if (!Schema::hasTable('retur_penjualans')) {
            Schema::create('retur_penjualans', function (Blueprint $table) {
                $table->id();
                $table->string('nomor_retur')->unique();
                $table->foreignId('invoice_penjualan_id')->constrained('invoice_penjualans')->cascadeOnDelete();
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
                $table->foreignId('gudang_id')->constrained('gudangs')->cascadeOnDelete();
                $table->date('tanggal_retur');
                $table->decimal('subtotal', 15, 2)->default(0);
                $table->decimal('pajak', 15, 2)->default(0);
                $table->decimal('total', 15, 2)->default(0);
                $table->text('alasan_retur')->nullable();
                $table->foreignId('created_by_user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();
            });
        }

        // 5. NOTA RETUR ITEMS
        if (!Schema::hasTable('retur_penjualan_items')) {
            Schema::create('retur_penjualan_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('retur_penjualan_id')->constrained('retur_penjualans')->cascadeOnDelete();
                $table->foreignId('barang_id')->constrained('barangs')->cascadeOnDelete();
                $table->decimal('qty', 12, 2);
                $table->decimal('harga_satuan', 15, 2);
                $table->decimal('subtotal', 15, 2);
                $table->timestamps();
            });
        }

        // 6. PENGATURAN PENOMORAN
        if (!Schema::hasTable('pengaturan_penomorans')) {
            Schema::create('pengaturan_penomorans', function (Blueprint $table) {
                $table->id();
                $table->string('jenis_dokumen')->unique();
                $table->string('nama_dokumen');
                $table->string('prefix');
                $table->integer('digits')->default(5);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturan_penomorans');
        Schema::dropIfExists('retur_penjualan_items');
        Schema::dropIfExists('retur_penjualans');
        Schema::dropIfExists('penerimaan_piutang_details');
        Schema::dropIfExists('penerimaan_piutangs');
        Schema::dropIfExists('kartu_piutangs');
    }
};
