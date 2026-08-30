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
    Schema::create('satuans', function (Blueprint $table) {
        $table->id();
        $table->string('kode_satuan')->unique();
        $table->string('nama_satuan');
        $table->text('keterangan')->nullable();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
}

public function down(): void
{
    Schema::dropIfExists('satuans');
}
};
