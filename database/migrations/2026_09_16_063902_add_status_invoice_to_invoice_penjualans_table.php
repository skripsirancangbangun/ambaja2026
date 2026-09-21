<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('invoice_penjualans', function (Blueprint $table) {
            if (!Schema::hasColumn('invoice_penjualans', 'status_invoice')) {
                $table->enum('status_invoice', [
                    'AKTIF',
                    'DIBATALKAN',
                ])
                ->default('AKTIF')
                ->after('status_pembayaran');
            }

            if (!Schema::hasColumn('invoice_penjualans', 'dibatalkan_at')) {
                $table->timestamp('dibatalkan_at')
                    ->nullable()
                    ->after('status_invoice');
            }

            if (!Schema::hasColumn('invoice_penjualans', 'dibatalkan_oleh')) {
                $table->foreignId('dibatalkan_oleh')
                    ->nullable()
                    ->after('dibatalkan_at')
                    ->constrained('users')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('invoice_penjualans', 'alasan_pembatalan')) {
                $table->text('alasan_pembatalan')
                    ->nullable()
                    ->after('dibatalkan_oleh');
            }
        });
    }

    public function down(): void
    {
        Schema::table('invoice_penjualans', function (Blueprint $table) {
            if (Schema::hasColumn('invoice_penjualans', 'dibatalkan_oleh')) {
                $table->dropForeign(['dibatalkan_oleh']);
                $table->dropColumn('dibatalkan_oleh');
            }

            $columns = [];

            foreach ([
                'status_invoice',
                'dibatalkan_at',
                'alasan_pembatalan',
            ] as $column) {
                if (Schema::hasColumn('invoice_penjualans', $column)) {
                    $columns[] = $column;
                }
            }

            if (!empty($columns)) {
                $table->dropColumn($columns);
            }
        });
    }
};