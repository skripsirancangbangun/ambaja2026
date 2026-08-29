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
    Schema::create('barangs', function (Blueprint $table) {
        $table->id();

        $table->string('kode_barang')->unique();
        $table->string('nama_barang');

        $table->foreignId('kategori_barang_id')
            ->constrained('kategori_barangs')
            ->restrictOnDelete();

        $table->foreignId('satuan_id')
            ->constrained('satuans')
            ->restrictOnDelete();

        $table->string('spesifikasi')->nullable();
        $table->string('merek')->nullable();

        $table->decimal('minimum_stok', 15, 2)->default(0);
        $table->decimal('harga_jual', 18, 2)->default(0);

        $table->boolean('is_active')->default(true);

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
{
    Schema::dropIfExists('barangs');
}
};
