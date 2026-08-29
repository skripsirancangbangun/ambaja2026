<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {

            $table->decimal(
                'qty_dibatalkan',
                15,
                2
            )
                ->default(0)
                ->after('qty_diterima');
        });
    }

    public function down(): void
    {
        Schema::table('purchase_order_items', function (Blueprint $table) {

            $table->dropColumn(
                'qty_dibatalkan'
            );
        });
    }
};