<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('penutupan_periodes', function (Blueprint $table) {
            $table->id();
            $table->integer('bulan'); // 1 - 12
            $table->integer('tahun'); // e.g. 2026
            $table->enum('status', ['LOCKED', 'OPEN'])->default('LOCKED');
            $table->timestamp('tanggal_penutupan')->nullable();
            $table->foreignId('closed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('tanggal_pembukaan')->nullable();
            $table->foreignId('opened_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('alasan_buka_kembali')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->unique(['bulan', 'tahun']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penutupan_periodes');
    }
};
