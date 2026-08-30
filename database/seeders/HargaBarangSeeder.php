<?php

namespace Database\Seeders;

use App\Models\Barang;
use Illuminate\Database\Seeder;

class HargaBarangSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | HARGA BELI DEFAULT & MINIMUM STOK
        |--------------------------------------------------------------------------
        |
        | harga_beli_default:
        | Digunakan sebagai harga referensi saat membuat Purchase Order.
        |
        | minimum_stok:
        | Digunakan untuk menentukan batas minimum persediaan.
        |
        | Barang yang belum memiliki data harga yang cukup dibiarkan 0.
        |
        */

        $data = [

            [
                'kode_barang' => 'A0-00003',
                'harga_beli_default' => 75000,
                'minimum_stok' => 1,
            ],

            [
                'kode_barang' => 'A0-00006',
                'harga_beli_default' => 55405.40,
                'minimum_stok' => 0,
            ],

            [
                'kode_barang' => 'A0-00009',
                'harga_beli_default' => 59909.91,
                'minimum_stok' => 14,
            ],

            [
                'kode_barang' => 'A0-00010',
                'harga_beli_default' => 59909.91,
                'minimum_stok' => 59,
            ],

            [
                'kode_barang' => 'A0-00013',
                'harga_beli_default' => 43468.47,
                'minimum_stok' => 99,
            ],

            [
                'kode_barang' => 'A1-00002',
                'harga_beli_default' => 63000,
                'minimum_stok' => 0,
            ],

            [
                'kode_barang' => 'A1-00003',
                'harga_beli_default' => 78750,
                'minimum_stok' => 0,
            ],

            [
                'kode_barang' => 'A1-00004',
                'harga_beli_default' => 94500,
                'minimum_stok' => 0,
            ],

            [
                'kode_barang' => 'A2-00003',
                'harga_beli_default' => 432007.72,
                'minimum_stok' => 1,
            ],

            [
                'kode_barang' => 'A2-00004',
                'harga_beli_default' => 0,
                'minimum_stok' => 0,
            ],

            [
                'kode_barang' => 'A2-00103',
                'harga_beli_default' => 403153.15,
                'minimum_stok' => 0,
            ],

            [
                'kode_barang' => 'A2-00104',
                'harga_beli_default' => 560810.81,
                'minimum_stok' => 0,
            ],

            [
                'kode_barang' => 'A3-01001',
                'harga_beli_default' => 6531.53,
                'minimum_stok' => 14,
            ],

            [
                'kode_barang' => 'A3-01002',
                'harga_beli_default' => 8750,
                'minimum_stok' => 2,
            ],

            [
                'kode_barang' => 'A3-01011',
                'harga_beli_default' => 13558.56,
                'minimum_stok' => 338,
            ],

            [
                'kode_barang' => 'A3-01012',
                'harga_beli_default' => 17840.29,
                'minimum_stok' => 148,
            ],

            [
                'kode_barang' => 'D0-00001',
                'harga_beli_default' => 24000,
                'minimum_stok' => 0,
            ],

            [
                'kode_barang' => 'D0-00003',
                'harga_beli_default' => 0,
                'minimum_stok' => 0,
            ],

            [
                'kode_barang' => 'D0-00005',
                'harga_beli_default' => 180500,
                'minimum_stok' => 0,
            ],

            [
                'kode_barang' => 'D0-00008',
                'harga_beli_default' => 441300,
                'minimum_stok' => 0,
            ],

            [
                'kode_barang' => 'D3-00001',
                'harga_beli_default' => 43693.69,
                'minimum_stok' => 2,
            ],

            [
                'kode_barang' => 'D3-00003',
                'harga_beli_default' => 91891.89,
                'minimum_stok' => 8,
            ],

            [
                'kode_barang' => 'D3-00007',
                'harga_beli_default' => 154594.60,
                'minimum_stok' => 1,
            ],

            [
                'kode_barang' => 'D4-00001',
                'harga_beli_default' => 0,
                'minimum_stok' => 0,
            ],

            [
                'kode_barang' => 'D4-00003',
                'harga_beli_default' => 64200,
                'minimum_stok' => 1,
            ],

            [
                'kode_barang' => 'D4-00005',
                'harga_beli_default' => 113963.96,
                'minimum_stok' => 0,
            ],

            [
                'kode_barang' => 'D4-00006',
                'harga_beli_default' => 0,
                'minimum_stok' => 0,
            ],

            [
                'kode_barang' => 'D5-00001',
                'harga_beli_default' => 0,
                'minimum_stok' => 0,
            ],

            [
                'kode_barang' => 'D5-00002',
                'harga_beli_default' => 324500,
                'minimum_stok' => 0,
            ],

            [
                'kode_barang' => 'D5-00003',
                'harga_beli_default' => 0,
                'minimum_stok' => 0,
            ],
        ];


        /*
        |--------------------------------------------------------------------------
        | UPDATE BARANG
        |--------------------------------------------------------------------------
        |
        | Kita menggunakan kode_barang supaya data barang yang sudah ada
        | tidak dibuat ulang. Seeder ini hanya meng-update harga beli
        | dan minimum stok.
        |
        */

        foreach ($data as $item) {

            $barang = Barang::where(
                'kode_barang',
                $item['kode_barang']
            )->first();


            if (!$barang) {

                $this->command?->warn(
                    "Barang {$item['kode_barang']} tidak ditemukan."
                );

                continue;
            }


            $barang->update([

                'harga_beli_default' =>
                    $item['harga_beli_default'],

                'minimum_stok' =>
                    $item['minimum_stok'],

            ]);


            $this->command?->info(
                "Updated {$item['kode_barang']} - {$barang->nama_barang}"
            );
        }


        $this->command?->newLine();

        $this->command?->info(
            'Harga beli default dan minimum stok berhasil diperbarui.'
        );
    }
}