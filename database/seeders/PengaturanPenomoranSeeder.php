<?php

namespace Database\Seeders;

use App\Models\PengaturanPenomoran;
use Illuminate\Database\Seeder;

class PengaturanPenomoranSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['jenis_dokumen' => 'INVOICE_PENJUALAN', 'nama_dokumen' => 'Faktur Penjualan', 'prefix' => 'INV-', 'digits' => 5],
            ['jenis_dokumen' => 'JURNAL_UMUM', 'nama_dokumen' => 'Jurnal Umum / Otomatis', 'prefix' => 'JU-', 'digits' => 5],
            ['jenis_dokumen' => 'RETUR_PENJUALAN', 'nama_dokumen' => 'Nota Retur Penjualan', 'prefix' => 'RTR-', 'digits' => 5],
            ['jenis_dokumen' => 'KWITANSI', 'nama_dokumen' => 'Kwitansi / Penerimaan Piutang', 'prefix' => 'KWT-', 'digits' => 5],
        ];

        foreach ($settings as $s) {
            PengaturanPenomoran::updateOrCreate(['jenis_dokumen' => $s['jenis_dokumen']], $s);
        }
    }
}
