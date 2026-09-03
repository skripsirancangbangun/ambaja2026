<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('uang_keluar_details', function (Blueprint $table) {
            $table->foreignId('akun_id')
                ->nullable()
                ->after('uang_keluar_id')
                ->constrained('akuns')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('uang_keluar_details', function (Blueprint $table) {
            $table->dropConstrainedForeignId('akun_id');
        });
    }
};