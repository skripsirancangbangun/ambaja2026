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
            if (!Schema::hasColumn('retur_penjualans', 'subtotal')) {
                $table->decimal('subtotal', 15, 2)->default(0)->after('tipe_retur');
            }
            if (!Schema::hasColumn('retur_penjualans', 'pajak')) {
                $table->decimal('pajak', 15, 2)->default(0)->after('subtotal');
            }
            if (!Schema::hasColumn('retur_penjualans', 'total')) {
                $table->decimal('total', 15, 2)->default(0)->after('pajak');
            }
            if (!Schema::hasColumn('retur_penjualans', 'alasan_retur')) {
                $table->text('alasan_retur')->nullable()->after('rekening_kas_bank_id');
            }
            if (!Schema::hasColumn('retur_penjualans', 'created_by_user_id')) {
                $table->foreignId('created_by_user_id')->nullable()->after('alasan_retur')->constrained('users')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('retur_penjualans', function (Blueprint $table) {
            $table->dropColumn(['subtotal', 'pajak', 'total', 'alasan_retur', 'created_by_user_id']);
        });
    }
};
