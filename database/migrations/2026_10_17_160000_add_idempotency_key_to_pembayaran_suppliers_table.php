<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('pembayaran_suppliers', 'idempotency_key')) {
            Schema::table('pembayaran_suppliers', function (Blueprint $table) {
                $table->uuid('idempotency_key')->nullable();
                $table->unique(
                    'idempotency_key',
                    'pembayaran_suppliers_idempotency_key_unique'
                );
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('pembayaran_suppliers', 'idempotency_key')) {
            Schema::table('pembayaran_suppliers', function (Blueprint $table) {
                $table->dropUnique('pembayaran_suppliers_idempotency_key_unique');
                $table->dropColumn('idempotency_key');
            });
        }
    }
};
