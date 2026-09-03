<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rekening_kas_banks', function (Blueprint $table) {
            $table->foreignId('akun_id')
                ->nullable()
                ->after('id')
                ->constrained('akuns')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('rekening_kas_banks', function (Blueprint $table) {
            $table->dropForeign(['akun_id']);
            $table->dropColumn('akun_id');
        });
    }
};