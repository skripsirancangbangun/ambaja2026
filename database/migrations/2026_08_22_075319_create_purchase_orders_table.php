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
    Schema::create('purchase_orders', function (Blueprint $table) {
        $table->id();

        $table->string('nomor_po')->unique();

        $table->foreignId('supplier_id')
            ->constrained('suppliers')
            ->restrictOnDelete();

        $table->date('tanggal_po');
        $table->date('tanggal_estimasi_datang')->nullable();

        $table->text('catatan')->nullable();

        $table->decimal('subtotal', 18, 2)->default(0);
        $table->decimal('diskon', 18, 2)->default(0);
        $table->decimal('pajak', 18, 2)->default(0);
        $table->decimal('total', 18, 2)->default(0);

        $table->enum('status', [
            'Draft',
            'Dipesan',
            'Sebagian Diterima',
            'Selesai',
            'Dibatalkan',
        ])->default('Draft');

        $table->foreignId('created_by')
            ->nullable()
            ->constrained('users')
            ->nullOnDelete();

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
{
    Schema::dropIfExists('purchase_orders');
}
};
