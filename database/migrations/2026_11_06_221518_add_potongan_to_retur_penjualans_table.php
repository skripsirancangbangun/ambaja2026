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
        Schema::table('retur_penjualans', function (Blueprint $table) {
            $table->decimal('potongan_persen', 5, 2)->default(20.00)->after('subtotal');
            $table->decimal('nominal_potongan', 15, 2)->default(0.00)->after('potongan_persen');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('retur_penjualans', function (Blueprint $table) {
            $table->dropColumn(['potongan_persen', 'nominal_potongan']);
        });
    }
};
