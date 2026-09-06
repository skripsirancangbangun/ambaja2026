<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tambah qty_terinvoice di penjualan_items
        if (Schema::hasTable('penjualan_items') && !Schema::hasColumn('penjualan_items', 'qty_terinvoice')) {
            Schema::table('penjualan_items', function (Blueprint $table) {
                $table->decimal('qty_terinvoice', 15, 2)->default(0)->after('qty');
            });
        }

        // 2. Tambah hpp_satuan & total_hpp di invoice_penjualan_items
        if (Schema::hasTable('invoice_penjualan_items')) {
            Schema::table('invoice_penjualan_items', function (Blueprint $table) {
                if (!Schema::hasColumn('invoice_penjualan_items', 'hpp_satuan')) {
                    $table->decimal('hpp_satuan', 15, 2)->default(0)->after('harga_satuan');
                }
                if (!Schema::hasColumn('invoice_penjualan_items', 'total_hpp')) {
                    $table->decimal('total_hpp', 15, 2)->default(0)->after('hpp_satuan');
                }
            });
        }

        // 3. Tambah tipe_retur, potong_piutang, pengembalian_kas, rekening_kas_bank_id di retur_penjualans
        if (Schema::hasTable('retur_penjualans')) {
            Schema::table('retur_penjualans', function (Blueprint $table) {
                if (!Schema::hasColumn('retur_penjualans', 'tipe_retur')) {
                    $table->enum('tipe_retur', ['Kredit', 'Tunai'])->default('Kredit')->after('tanggal_retur');
                }
                if (!Schema::hasColumn('retur_penjualans', 'potong_piutang')) {
                    $table->decimal('potong_piutang', 15, 2)->default(0)->after('total');
                }
                if (!Schema::hasColumn('retur_penjualans', 'pengembalian_kas')) {
                    $table->decimal('pengembalian_kas', 15, 2)->default(0)->after('potong_piutang');
                }
                if (!Schema::hasColumn('retur_penjualans', 'rekening_kas_bank_id')) {
                    $table->foreignId('rekening_kas_bank_id')->nullable()->after('pengembalian_kas')->constrained('rekening_kas_banks')->nullOnDelete();
                }
            });
        }

        // 4. Tambah hpp_satuan & total_hpp di retur_penjualan_items
        if (Schema::hasTable('retur_penjualan_items')) {
            Schema::table('retur_penjualan_items', function (Blueprint $table) {
                if (!Schema::hasColumn('retur_penjualan_items', 'hpp_satuan')) {
                    $table->decimal('hpp_satuan', 15, 2)->default(0)->after('harga_satuan');
                }
                if (!Schema::hasColumn('retur_penjualan_items', 'total_hpp')) {
                    $table->decimal('total_hpp', 15, 2)->default(0)->after('hpp_satuan');
                }
            });
        }
    }

    public function down(): void
    {
        // Drop added columns if needed
    }
};
