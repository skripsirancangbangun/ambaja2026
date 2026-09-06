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
        Schema::table('invoice_penjualans', function (Blueprint $table) {
            $table->boolean('is_email_sent')->default(false)->after('catatan');
            $table->timestamp('email_sent_at')->nullable()->after('is_email_sent');
            $table->string('email_sent_to')->nullable()->after('email_sent_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice_penjualans', function (Blueprint $table) {
            $table->dropColumn(['is_email_sent', 'email_sent_at', 'email_sent_to']);
        });
    }
};
