<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('permintaan_pembelians', function (Blueprint $table): void {
            $table->id();
            $table->string('nomor_permintaan')->unique();
            $table->date('tanggal_permintaan');
            $table->string('status', 30)->default('DIAJUKAN')->index();
            $table->text('catatan')->nullable();
            $table->text('alasan_penolakan')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('purchase_order_id')->nullable()->unique()->constrained('purchase_orders')->nullOnDelete();
            $table->timestamps();

            $table->index(['created_by', 'status']);
        });

        Schema::create('permintaan_pembelian_details', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('permintaan_pembelian_id')
                ->constrained('permintaan_pembelians')
                ->cascadeOnDelete();
            $table->foreignId('barang_id')->constrained('barangs')->restrictOnDelete();
            $table->decimal('stok_saat_pengajuan', 15, 2)->default(0);
            $table->decimal('minimum_stok', 15, 2)->default(0);
            $table->decimal('qty_diminta', 15, 2);
            $table->decimal('qty_disetujui', 15, 2)->nullable();
            $table->timestamps();

            $table->unique(['permintaan_pembelian_id', 'barang_id'], 'pp_detail_request_barang_unique');
            $table->index('barang_id');
        });

        // Database notifications used for delivery to Pembelian and feedback to Gudang.
        Schema::create('notifications', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['notifiable_type', 'notifiable_id', 'read_at'], 'notifications_recipient_read_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('permintaan_pembelian_details');
        Schema::dropIfExists('permintaan_pembelians');
    }
};
