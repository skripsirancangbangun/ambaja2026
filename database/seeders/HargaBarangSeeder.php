<?php

namespace Database\Seeders;

use App\Models\Barang;
use Illuminate\Database\Seeder;

class HargaBarangSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            ['kode_barang' => 'A0-00003', 'harga_beli_default' => 75000, 'harga_jual' => 86500, 'minimum_stok' => 1],
            ['kode_barang' => 'A0-00006', 'harga_beli_default' => 55405.40, 'harga_jual' => 63800, 'minimum_stok' => 0],
            ['kode_barang' => 'A0-00009', 'harga_beli_default' => 59909.91, 'harga_jual' => 69000, 'minimum_stok' => 14],
            ['kode_barang' => 'A0-00010', 'harga_beli_default' => 59909.91, 'harga_jual' => 69000, 'minimum_stok' => 59],
            ['kode_barang' => 'A0-00013', 'harga_beli_default' => 43468.47, 'harga_jual' => 50000, 'minimum_stok' => 99],

            ['kode_barang' => 'A1-00002', 'harga_beli_default' => 63000, 'harga_jual' => 72500, 'minimum_stok' => 0],
            ['kode_barang' => 'A1-00003', 'harga_beli_default' => 78750, 'harga_jual' => 90500, 'minimum_stok' => 0],
            ['kode_barang' => 'A1-00004', 'harga_beli_default' => 94500, 'harga_jual' => 108750, 'minimum_stok' => 0],

            ['kode_barang' => 'A2-00003', 'harga_beli_default' => 432007.72, 'harga_jual' => 496800, 'minimum_stok' => 1],
            ['kode_barang' => 'A2-00004', 'harga_beli_default' => 518400, 'harga_jual' => 596000, 'minimum_stok' => 0],
            ['kode_barang' => 'A2-00103', 'harga_beli_default' => 403153.15, 'harga_jual' => 463600, 'minimum_stok' => 0],
            ['kode_barang' => 'A2-00104', 'harga_beli_default' => 560810.81, 'harga_jual' => 645000, 'minimum_stok' => 0],

            ['kode_barang' => 'A3-01001', 'harga_beli_default' => 6531.53, 'harga_jual' => 7500, 'minimum_stok' => 14],
            ['kode_barang' => 'A3-01002', 'harga_beli_default' => 8750, 'harga_jual' => 10000, 'minimum_stok' => 2],
            ['kode_barang' => 'A3-01011', 'harga_beli_default' => 13558.56, 'harga_jual' => 15600, 'minimum_stok' => 338],
            ['kode_barang' => 'A3-01012', 'harga_beli_default' => 17840.29, 'harga_jual' => 20500, 'minimum_stok' => 148],

            ['kode_barang' => 'D0-00001', 'harga_beli_default' => 24000, 'harga_jual' => 27600, 'minimum_stok' => 0],
            ['kode_barang' => 'D0-00003', 'harga_beli_default' => 65000, 'harga_jual' => 74800, 'minimum_stok' => 0],
            ['kode_barang' => 'D0-00005', 'harga_beli_default' => 180500, 'harga_jual' => 207500, 'minimum_stok' => 0],
            ['kode_barang' => 'D0-00008', 'harga_beli_default' => 441300, 'harga_jual' => 507500, 'minimum_stok' => 0],

            ['kode_barang' => 'D3-00001', 'harga_beli_default' => 43693.69, 'harga_jual' => 50250, 'minimum_stok' => 2],
            ['kode_barang' => 'D3-00003', 'harga_beli_default' => 91891.89, 'harga_jual' => 105700, 'minimum_stok' => 8],
            ['kode_barang' => 'D3-00007', 'harga_beli_default' => 154594.60, 'harga_jual' => 177800, 'minimum_stok' => 1],

            ['kode_barang' => 'D4-00001', 'harga_beli_default' => 38000, 'harga_jual' => 43700, 'minimum_stok' => 0],
            ['kode_barang' => 'D4-00003', 'harga_beli_default' => 64200, 'harga_jual' => 73800, 'minimum_stok' => 1],
            ['kode_barang' => 'D4-00005', 'harga_beli_default' => 113963.96, 'harga_jual' => 131000, 'minimum_stok' => 0],
            ['kode_barang' => 'D4-00006', 'harga_beli_default' => 145000, 'harga_jual' => 166800, 'minimum_stok' => 0],

            ['kode_barang' => 'D5-00001', 'harga_beli_default' => 245000, 'harga_jual' => 281800, 'minimum_stok' => 0],
            ['kode_barang' => 'D5-00002', 'harga_beli_default' => 324500, 'harga_jual' => 373000, 'minimum_stok' => 0],
            ['kode_barang' => 'D5-00003', 'harga_beli_default' => 385000, 'harga_jual' => 442800, 'minimum_stok' => 0],
        ];

        foreach ($data as $item) {
            $barang = Barang::where('kode_barang', $item['kode_barang'])->first();
            if ($barang) {
                $barang->update([
                    'harga_beli_default' => $item['harga_beli_default'],
                    'harga_jual' => $item['harga_jual'],
                    'minimum_stok' => $item['minimum_stok'],
                ]);
            }
        }

        // Fallback: pastikan semua produk di database memiliki harga_jual > 0
        foreach (Barang::all() as $b) {
            if (empty($b->harga_jual) || (float)$b->harga_jual <= 0) {
                $hBeli = (float)$b->harga_beli_default > 0 ? (float)$b->harga_beli_default : 50000;
                $b->update([
                    'harga_jual' => round(($hBeli * 1.15) / 100) * 100
                ]);
            }
        }

        $this->command?->info('Harga beli, harga jual, dan minimum stok berhasil diperbarui untuk semua produk.');
    }
}