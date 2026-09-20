<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('profil_perusahaans', function (Blueprint $table) {
            $table->id();
            $table->string('nama_perusahaan')->default('CV ANDIKA MUSTIKA (AMBAJA)');
            $table->string('nama_singkat')->default('AMBAJA');
            $table->text('alamat')->nullable();
            $table->string('telepon')->nullable();
            $table->string('email')->nullable();
            $table->string('website')->nullable();
            $table->string('kota')->default('Surabaya');
            $table->string('rekening_bca')->nullable();
            $table->string('rekening_mandiri')->nullable();
            $table->text('catatan_dokumen')->nullable();
            $table->timestamps();
        });

        DB::table('profil_perusahaans')->insert([
            'id' => 1,
            'nama_perusahaan' => 'CV ANDIKA MUSTIKA (AMBAJA)',
            'nama_singkat' => 'AMBAJA',
            'alamat' => 'Jl. Mustika No. 88, Surabaya',
            'telepon' => '(031) 888-9999 / 085711223344',
            'email' => 'easeproject128@gmail.com',
            'website' => 'www.ambaja.co.id',
            'kota' => 'Surabaya',
            'rekening_bca' => '123-456-7890 a.n CV Andika Mustika',
            'rekening_mandiri' => '987-654-3210 a.n CV Andika Mustika',
            'catatan_dokumen' => 'Terima kasih atas kepercayaan Anda bertransaksi dengan CV Andika Mustika.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('profil_perusahaans');
    }
};
