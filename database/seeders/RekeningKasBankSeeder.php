<?php

namespace Database\Seeders;

use App\Models\RekeningKasBank;
use Illuminate\Database\Seeder;

class RekeningKasBankSeeder extends Seeder
{
    public function run(): void
    {
        $rekenings = [
            [
                'kode_rekening' => 'KAS-TUNAI',
                'nama_rekening' => 'Kas Tunai Toko / Kasir',
                'jenis' => 'KAS',
                'nama_bank' => null,
                'nomor_rekening' => 'TUNAI',
                'atas_nama' => 'CV Andika Mustika',
                'saldo_awal' => 0,
                'is_active' => true,
            ],
            [
                'kode_rekening' => 'BANK-BCA',
                'nama_rekening' => 'Bank BCA (123-456-7890)',
                'jenis' => 'BANK',
                'nama_bank' => 'Bank BCA',
                'nomor_rekening' => '123-456-7890',
                'atas_nama' => 'CV Andika Mustika',
                'saldo_awal' => 0,
                'is_active' => true,
            ],
            [
                'kode_rekening' => 'BANK-MANDIRI',
                'nama_rekening' => 'Bank Mandiri (987-654-3210)',
                'jenis' => 'BANK',
                'nama_bank' => 'Bank Mandiri',
                'nomor_rekening' => '987-654-3210',
                'atas_nama' => 'CV Andika Mustika',
                'saldo_awal' => 0,
                'is_active' => true,
            ],
        ];

        foreach ($rekenings as $r) {
            RekeningKasBank::firstOrCreate(
                ['kode_rekening' => $r['kode_rekening']],
                $r
            );
        }
    }
}
