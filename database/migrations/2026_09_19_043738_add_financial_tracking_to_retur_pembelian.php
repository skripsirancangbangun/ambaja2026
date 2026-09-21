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
        | INVOICE SUPPLIER
        |--------------------------------------------------------------------------
        |
        | total_retur:
        | Total nilai retur yang sudah mengurangi kewajiban invoice.
        |
        | kredit_supplier:
        | Jika invoice sudah dibayar lebih besar daripada kewajiban setelah retur,
        | selisih menjadi saldo kredit / hak perusahaan kepada supplier.
        |
        */

        if (
            Schema::hasTable('invoice_suppliers')
            && !Schema::hasColumn(
                'invoice_suppliers',
                'total_retur'
            )
        ) {
            Schema::table(
                'invoice_suppliers',
                function (Blueprint $table) {
                    $table
                        ->decimal(
                            'total_retur',
                            15,
                            2
                        )
                        ->default(0)
                        ->after(
                            'total_dibayar'
                        );
                }
            );
        }

        if (
            Schema::hasTable('invoice_suppliers')
            && !Schema::hasColumn(
                'invoice_suppliers',
                'kredit_supplier'
            )
        ) {
            Schema::table(
                'invoice_suppliers',
                function (Blueprint $table) {
                    $table
                        ->decimal(
                            'kredit_supplier',
                            15,
                            2
                        )
                        ->default(0)
                        ->after(
                            'sisa_utang'
                        );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | ALOKASI RETUR PEMBELIAN
        |--------------------------------------------------------------------------
        |
        | Contoh:
        |
        | Penerimaan barang:
        | 10 pcs
        |
        | Invoice A:
        | 6 pcs
        |
        | Invoice B:
        | 4 pcs
        |
        | Jika retur 8 pcs:
        |
        | - 6 pcs dialokasikan ke Invoice A
        | - 2 pcs dialokasikan ke Invoice B
        |
        | Dengan tabel ini sistem tahu invoice mana yang sudah dikoreksi.
        |
        */

        if (
            !Schema::hasTable(
                'retur_pembelian_allocations'
            )
        ) {
            Schema::create(
                'retur_pembelian_allocations',
                function (Blueprint $table) {
                    $table->id();

                    $table
                        ->unsignedBigInteger(
                            'retur_pembelian_id'
                        );

                    $table
                        ->unsignedBigInteger(
                            'retur_pembelian_detail_id'
                        );

                    $table
                        ->unsignedBigInteger(
                            'invoice_supplier_id'
                        );

                    $table
                        ->unsignedBigInteger(
                            'invoice_supplier_item_id'
                        );

                    /*
                    |--------------------------------------------------------------------------
                    | QTY
                    |--------------------------------------------------------------------------
                    */

                    $table
                        ->decimal(
                            'qty',
                            15,
                            2
                        )
                        ->default(0);

                    /*
                    |--------------------------------------------------------------------------
                    | NILAI BARANG
                    |--------------------------------------------------------------------------
                    |
                    | Nilai item invoice yang diretur,
                    | sudah memperhitungkan diskon per-item.
                    |
                    */

                    $table
                        ->decimal(
                            'nilai_barang',
                            15,
                            2
                        )
                        ->default(0);

                    /*
                    |--------------------------------------------------------------------------
                    | DISKON HEADER
                    |--------------------------------------------------------------------------
                    |
                    | Bagian diskon header invoice yang harus direversal.
                    |
                    */

                    $table
                        ->decimal(
                            'diskon_header',
                            15,
                            2
                        )
                        ->default(0);

                    /*
                    |--------------------------------------------------------------------------
                    | PPN
                    |--------------------------------------------------------------------------
                    */

                    $table
                        ->decimal(
                            'pajak',
                            15,
                            2
                        )
                        ->default(0);

                    /*
                    |--------------------------------------------------------------------------
                    | TOTAL KOREKSI
                    |--------------------------------------------------------------------------
                    |
                    | nilai_barang
                    | - diskon_header
                    | + pajak
                    |
                    */

                    $table
                        ->decimal(
                            'total_koreksi',
                            15,
                            2
                        )
                        ->default(0);

                    /*
                    |--------------------------------------------------------------------------
                    | POTONG UTANG
                    |--------------------------------------------------------------------------
                    |
                    | Bagian retur yang mengurangi Accounts Payable.
                    |
                    */

                    $table
                        ->decimal(
                            'potong_utang',
                            15,
                            2
                        )
                        ->default(0);

                    /*
                    |--------------------------------------------------------------------------
                    | KREDIT SUPPLIER
                    |--------------------------------------------------------------------------
                    |
                    | Jika invoice sudah dibayar dan supplier
                    | memiliki kewajiban mengembalikan uang /
                    | memberikan credit balance.
                    |
                    */

                    $table
                        ->decimal(
                            'kredit_supplier',
                            15,
                            2
                        )
                        ->default(0);

                    $table->timestamps();

                    /*
                    |--------------------------------------------------------------------------
                    | INDEX
                    |--------------------------------------------------------------------------
                    */

                    $table->index(
                        'retur_pembelian_id',
                        'rpa_retur_idx'
                    );

                    $table->index(
                        'retur_pembelian_detail_id',
                        'rpa_detail_idx'
                    );

                    $table->index(
                        'invoice_supplier_id',
                        'rpa_invoice_idx'
                    );

                    $table->index(
                        'invoice_supplier_item_id',
                        'rpa_invoice_item_idx'
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | FOREIGN KEYS
                    |--------------------------------------------------------------------------
                    */

                    $table
                        ->foreign(
                            'retur_pembelian_id',
                            'rpa_retur_fk'
                        )
                        ->references('id')
                        ->on('retur_pembelians')
                        ->cascadeOnDelete();

                    $table
                        ->foreign(
                            'retur_pembelian_detail_id',
                            'rpa_detail_fk'
                        )
                        ->references('id')
                        ->on(
                            'retur_pembelian_details'
                        )
                        ->cascadeOnDelete();

                    $table
                        ->foreign(
                            'invoice_supplier_id',
                            'rpa_invoice_fk'
                        )
                        ->references('id')
                        ->on('invoice_suppliers')
                        ->restrictOnDelete();

                    $table
                        ->foreign(
                            'invoice_supplier_item_id',
                            'rpa_invoice_item_fk'
                        )
                        ->references('id')
                        ->on(
                            'invoice_supplier_items'
                        )
                        ->restrictOnDelete();
                }
            );
        }
    }

    public function down(): void
    {
        if (
            Schema::hasTable(
                'retur_pembelian_allocations'
            )
        ) {
            Schema::dropIfExists(
                'retur_pembelian_allocations'
            );
        }

        if (
            Schema::hasTable(
                'invoice_suppliers'
            )
        ) {
            Schema::table(
                'invoice_suppliers',
                function (Blueprint $table) {
                    if (
                        Schema::hasColumn(
                            'invoice_suppliers',
                            'kredit_supplier'
                        )
                    ) {
                        $table->dropColumn(
                            'kredit_supplier'
                        );
                    }

                    if (
                        Schema::hasColumn(
                            'invoice_suppliers',
                            'total_retur'
                        )
                    ) {
                        $table->dropColumn(
                            'total_retur'
                        );
                    }
                }
            );
        }
    }
};