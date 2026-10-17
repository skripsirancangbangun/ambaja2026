<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stok_awal_imports', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_import', 40)->unique();
            $table->dateTime('tanggal');
            $table->char('file_hash', 64)->unique();
            $table->decimal('total_qty', 18, 2)->default(0);
            $table->decimal('total_nilai', 18, 2)->default(0);
            $table->foreignId('jurnal_id')->nullable()->constrained('jurnals')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        // Do not guess the counterpart equity account: the user must select it in Pengaturan Akun.
        if (Schema::hasTable('pengaturan_akuns')) {
            if (!DB::table('pengaturan_akuns')->where('kunci_pengaturan', 'AKUN_SALDO_AWAL')->exists()) {
                DB::table('pengaturan_akuns')->insert([
                    'kunci_pengaturan' => 'AKUN_SALDO_AWAL',
                    'nama_pengaturan' => 'Akun Lawan Saldo Awal Persediaan',
                    'akun_id' => null,
                    'keterangan' => 'Pilih akun ekuitas pembukaan sesuai kebijakan akuntansi perusahaan.',
                    'updated_at' => now(),
                    'created_at' => now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('pengaturan_akuns')) {
            DB::table('pengaturan_akuns')->where('kunci_pengaturan', 'AKUN_SALDO_AWAL')->whereNull('akun_id')->delete();
        }
        Schema::dropIfExists('stok_awal_imports');
    }
};
