<?php

namespace Database\Seeders;

use App\Models\PengaturanPenomoran;
use Illuminate\Database\Seeder;

class PengaturanPenomoranSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            ['jenis_dokumen' => 'INVOICE_PENJUALAN', 'nama_dokumen' => 'Faktur Penjualan (Invoice)', 'prefix' => 'INV', 'digits' => 4],
            ['jenis_dokumen' => 'JURNAL_UMUM', 'nama_dokumen' => 'Jurnal Umum Manual (JU)', 'prefix' => 'JU', 'digits' => 4],
            ['jenis_dokumen' => 'JURNAL_PENJUALAN', 'nama_dokumen' => 'Jurnal Penjualan (JPJ)', 'prefix' => 'JPJ', 'digits' => 4],
            ['jenis_dokumen' => 'JURNAL_RETUR_PENJUALAN', 'nama_dokumen' => 'Jurnal Retur Penjualan (RJL)', 'prefix' => 'RJL', 'digits' => 4],
            ['jenis_dokumen' => 'JURNAL_PENERIMAAN_PIUTANG', 'nama_dokumen' => 'Jurnal Penerimaan Piutang (JPI)', 'prefix' => 'JPI', 'digits' => 4],
            ['jenis_dokumen' => 'RETUR_PENJUALAN', 'nama_dokumen' => 'Nota Retur Penjualan', 'prefix' => 'RTJ', 'digits' => 4],
            ['jenis_dokumen' => 'KWITANSI', 'nama_dokumen' => 'Kwitansi / Penerimaan Piutang', 'prefix' => 'PAY', 'digits' => 4],
        ];

        foreach ($settings as $s) {
            PengaturanPenomoran::updateOrCreate(['jenis_dokumen' => $s['jenis_dokumen']], $s);
        }
    }
}
