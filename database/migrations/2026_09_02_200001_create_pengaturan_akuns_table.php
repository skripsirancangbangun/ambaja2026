<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pengaturan_akuns', function (Blueprint $table) {
            $table->id();
            $table->string('kunci_pengaturan')->unique(); // e.g. AKUN_PIUTANG_DAGANG
            $table->string('nama_pengaturan');
            $table->foreignId('akun_id')->nullable()->constrained('akuns')->nullOnDelete();
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pengaturan_akuns');
    }
};
