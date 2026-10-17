<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('penerimaan_barang_items', function (Blueprint $table) {
            // Nullable untuk mempertahankan penerimaan historis; data lama harus diaudit sebelum dilanjutkan.
            $table->decimal('nilai_persediaan', 18, 2)->nullable()->after('harga_satuan');
        });
    }

    public function down(): void
    {
        Schema::table('penerimaan_barang_items', function (Blueprint $table) {
            $table->dropColumn('nilai_persediaan');
        });
    }
};
