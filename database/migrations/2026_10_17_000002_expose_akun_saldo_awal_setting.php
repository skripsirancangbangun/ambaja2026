<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('pengaturan_akuns')) {
            return;
        }

        // Tambahkan hanya jika belum ada; jangan menimpa akun_id yang mungkin sudah dipilih.
        $exists = DB::table('pengaturan_akuns')
            ->where('kunci_pengaturan', 'AKUN_SALDO_AWAL')
            ->exists();

        if (!$exists) {
            DB::table('pengaturan_akuns')->insert([
                'kunci_pengaturan' => 'AKUN_SALDO_AWAL',
                'nama_pengaturan' => 'Akun Lawan Saldo Awal Persediaan',
                'akun_id' => null,
                'keterangan' => 'Akun Ekuitas pembukaan untuk jurnal stok awal. Pilih akun sesuai kebijakan akuntansi perusahaan.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        // Hapus hanya jika belum dikonfigurasi, agar rollback tidak menghilangkan pilihan akun pengguna.
        if (Schema::hasTable('pengaturan_akuns')) {
            DB::table('pengaturan_akuns')
                ->where('kunci_pengaturan', 'AKUN_SALDO_AWAL')
                ->whereNull('akun_id')
                ->delete();
        }
    }
};
