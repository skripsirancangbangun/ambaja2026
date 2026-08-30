<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoice_suppliers', function (Blueprint $table) {
            $table->id();

            $table->string('nomor_invoice_internal')->unique();

            $table->string('nomor_invoice_supplier');

            $table->foreignId('supplier_id')
                ->constrained('suppliers')
                ->restrictOnDelete();

            $table->foreignId('purchase_order_id')
                ->constrained('purchase_orders')
                ->restrictOnDelete();

            $table->date('tanggal_invoice');

            $table->date('tanggal_jatuh_tempo');

            $table->decimal('subtotal', 18, 2)->default(0);

            $table->decimal('diskon', 18, 2)->default(0);

            $table->decimal('pajak', 18, 2)->default(0);

            $table->decimal('total', 18, 2)->default(0);

            $table->decimal('total_dibayar', 18, 2)->default(0);

            $table->decimal('sisa_utang', 18, 2)->default(0);

            $table->enum('status', [
                'BELUM_LUNAS',
                'SEBAGIAN',
                'LUNAS',
            ])->default('BELUM_LUNAS');

            $table->text('catatan')->nullable();

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();

            /*
             * Nomor invoice supplier sebaiknya unik
             * per supplier.
             */
            $table->unique([
                'supplier_id',
                'nomor_invoice_supplier',
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_suppliers');
    }
};