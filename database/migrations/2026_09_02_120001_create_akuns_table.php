<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('akuns', function (Blueprint $table) {
            $table->id();
            $table->string('kode_akun')->unique();
            $table->string('nama_akun');
            $table->string('kategori_akun');
            $table->string('sub_kategori')->nullable(); // e.g. Aset Lancar, Aset Tetap, Utang Lancar, HPP, Beban Operasional
            $table->enum('saldo_normal', ['DEBIT', 'KREDIT']);
            $table->decimal('saldo_awal', 15, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('akuns');
    }
};
