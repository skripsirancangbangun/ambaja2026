<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | 1. Penjualan Items
        |--------------------------------------------------------------------------
        | Tambah qty_terinvoice
        */
        if (
            Schema::hasTable('penjualan_items') &&
            !Schema::hasColumn('penjualan_items', 'qty_terinvoice')
        ) {
            Schema::table('penjualan_items', function (Blueprint $table) {
                $table->decimal('qty_terinvoice', 15, 2)
                    ->default(0)
                    ->after('qty');
            });
        }

        /*
        |--------------------------------------------------------------------------
        | 2. Invoice Penjualan Items
        |--------------------------------------------------------------------------
        | Tambah hpp_satuan dan total_hpp
        */
        if (Schema::hasTable('invoice_penjualan_items')) {

            if (!Schema::hasColumn('invoice_penjualan_items', 'hpp_satuan')) {
                Schema::table('invoice_penjualan_items', function (Blueprint $table) {
                    $table->decimal('hpp_satuan', 15, 2)
                        ->default(0)
                        ->after('harga_satuan');
                });
            }

            if (!Schema::hasColumn('invoice_penjualan_items', 'total_hpp')) {
                Schema::table('invoice_penjualan_items', function (Blueprint $table) {
                    $table->decimal('total_hpp', 15, 2)
                        ->default(0)
                        ->after('hpp_satuan');
                });
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 3. Retur Penjualans
        |--------------------------------------------------------------------------
        | Tambah:
        | - tipe_retur
        | - potong_piutang
        | - pengembalian_kas
        | - rekening_kas_bank_id
        */
        if (Schema::hasTable('retur_penjualans')) {

            if (!Schema::hasColumn('retur_penjualans', 'tipe_retur')) {
                Schema::table('retur_penjualans', function (Blueprint $table) {
                    $table->enum('tipe_retur', ['Kredit', 'Tunai'])
                        ->default('Kredit')
                        ->after('tanggal_retur');
                });
            }

            if (!Schema::hasColumn('retur_penjualans', 'potong_piutang')) {
                Schema::table('retur_penjualans', function (Blueprint $table) {
                    $table->decimal('potong_piutang', 15, 2)
                        ->default(0)
                        ->after('total_retur');
                });
            }

            if (!Schema::hasColumn('retur_penjualans', 'pengembalian_kas')) {
                Schema::table('retur_penjualans', function (Blueprint $table) {
                    $table->decimal('pengembalian_kas', 15, 2)
                        ->default(0)
                        ->after('potong_piutang');
                });
            }

            if (!Schema::hasColumn('retur_penjualans', 'rekening_kas_bank_id')) {
                Schema::table('retur_penjualans', function (Blueprint $table) {
                    $table->foreignId('rekening_kas_bank_id')
                        ->nullable()
                        ->after('pengembalian_kas')
                        ->constrained('rekening_kas_banks')
                        ->nullOnDelete();
                });
            }
        }

        /*
        |--------------------------------------------------------------------------
        | 4. Retur Penjualan Item / Detail
        |--------------------------------------------------------------------------
        | Support dua kemungkinan nama tabel:
        | - retur_penjualan_items
        | - retur_penjualan_details
        */

        if (Schema::hasTable('retur_penjualan_items')) {

            if (!Schema::hasColumn('retur_penjualan_items', 'hpp_satuan')) {
                Schema::table('retur_penjualan_items', function (Blueprint $table) {
                    $table->decimal('hpp_satuan', 15, 2)
                        ->default(0)
                        ->after('harga_satuan');
                });
            }

            if (!Schema::hasColumn('retur_penjualan_items', 'total_hpp')) {
                Schema::table('retur_penjualan_items', function (Blueprint $table) {
                    $table->decimal('total_hpp', 15, 2)
                        ->default(0)
                        ->after('hpp_satuan');
                });
            }

        } elseif (Schema::hasTable('retur_penjualan_details')) {

            if (!Schema::hasColumn('retur_penjualan_details', 'hpp_satuan')) {
                Schema::table('retur_penjualan_details', function (Blueprint $table) {
                    $table->decimal('hpp_satuan', 15, 2)
                        ->default(0)
                        ->after('harga_satuan');
                });
            }

            if (!Schema::hasColumn('retur_penjualan_details', 'total_hpp')) {
                Schema::table('retur_penjualan_details', function (Blueprint $table) {
                    $table->decimal('total_hpp', 15, 2)
                        ->default(0)
                        ->after('hpp_satuan');
                });
            }
        }
    }

    public function down(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Retur Penjualan Items
        |--------------------------------------------------------------------------
        */
        if (Schema::hasTable('retur_penjualan_items')) {
            Schema::table('retur_penjualan_items', function (Blueprint $table) {

                if (Schema::hasColumn('retur_penjualan_items', 'total_hpp')) {
                    $table->dropColumn('total_hpp');
                }

                if (Schema::hasColumn('retur_penjualan_items', 'hpp_satuan')) {
                    $table->dropColumn('hpp_satuan');
                }
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Retur Penjualan Details
        |--------------------------------------------------------------------------
        */
        if (Schema::hasTable('retur_penjualan_details')) {
            Schema::table('retur_penjualan_details', function (Blueprint $table) {

                if (Schema::hasColumn('retur_penjualan_details', 'total_hpp')) {
                    $table->dropColumn('total_hpp');
                }

                if (Schema::hasColumn('retur_penjualan_details', 'hpp_satuan')) {
                    $table->dropColumn('hpp_satuan');
                }
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Retur Penjualans
        |--------------------------------------------------------------------------
        */
        if (Schema::hasTable('retur_penjualans')) {

            if (Schema::hasColumn('retur_penjualans', 'rekening_kas_bank_id')) {
                Schema::table('retur_penjualans', function (Blueprint $table) {
                    $table->dropForeign(['rekening_kas_bank_id']);
                    $table->dropColumn('rekening_kas_bank_id');
                });
            }

            Schema::table('retur_penjualans', function (Blueprint $table) {

                if (Schema::hasColumn('retur_penjualans', 'pengembalian_kas')) {
                    $table->dropColumn('pengembalian_kas');
                }

                if (Schema::hasColumn('retur_penjualans', 'potong_piutang')) {
                    $table->dropColumn('potong_piutang');
                }

                if (Schema::hasColumn('retur_penjualans', 'tipe_retur')) {
                    $table->dropColumn('tipe_retur');
                }
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Invoice Penjualan Items
        |--------------------------------------------------------------------------
        */
        if (Schema::hasTable('invoice_penjualan_items')) {
            Schema::table('invoice_penjualan_items', function (Blueprint $table) {

                if (Schema::hasColumn('invoice_penjualan_items', 'total_hpp')) {
                    $table->dropColumn('total_hpp');
                }

                if (Schema::hasColumn('invoice_penjualan_items', 'hpp_satuan')) {
                    $table->dropColumn('hpp_satuan');
                }
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Penjualan Items
        |--------------------------------------------------------------------------
        */
        if (
            Schema::hasTable('penjualan_items') &&
            Schema::hasColumn('penjualan_items', 'qty_terinvoice')
        ) {
            Schema::table('penjualan_items', function (Blueprint $table) {
                $table->dropColumn('qty_terinvoice');
            });
        }
    }
};