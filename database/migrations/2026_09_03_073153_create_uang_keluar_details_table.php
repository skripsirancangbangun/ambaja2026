<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uang_keluar_details', function (Blueprint $table) {
            $table->id();

            $table->foreignId('uang_keluar_id')
                ->constrained('uang_keluars')
                ->cascadeOnDelete();

            $table->string('tujuan', 150);
            $table->decimal('jumlah', 18, 2);

            $table->timestamps();

            $table->index('uang_keluar_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uang_keluar_details');
    }
};