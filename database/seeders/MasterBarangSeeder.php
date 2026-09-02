<?php

namespace Database\Seeders;

use App\Models\Barang;
use App\Models\KategoriBarang;
use App\Models\Satuan;
use Illuminate\Database\Seeder;

class MasterBarangSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | SATUAN
        |--------------------------------------------------------------------------
        */

        $batang = Satuan::firstOrCreate(
            ['kode_satuan' => 'BTG'],
            [
                'nama_satuan' => 'Batang',
                'keterangan' => 'Satuan batang',
                'is_active' => true,
            ]
        );

        $lembar = Satuan::firstOrCreate(
            ['kode_satuan' => 'LBR'],
            [
                'nama_satuan' => 'Lembar',
                'keterangan' => 'Satuan lembar',
                'is_active' => true,
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | KATEGORI BARANG
        |--------------------------------------------------------------------------
        */

        $kategori = [];

        $kategori['baja_ringan'] = KategoriBarang::firstOrCreate(
            ['kode_kategori' => 'KAT-BR'],
            [
                'nama_kategori' => 'Baja Ringan',
                'keterangan' => 'Produk baja ringan',
                'is_active' => true,
            ]
        );

        $kategori['spandek'] = KategoriBarang::firstOrCreate(
            ['kode_kategori' => 'KAT-SP'],
            [
                'nama_kategori' => 'Spandek',
                'keterangan' => 'Produk atap spandek',
                'is_active' => true,
            ]
        );

        $kategori['bondek'] = KategoriBarang::firstOrCreate(
            ['kode_kategori' => 'KAT-BD'],
            [
                'nama_kategori' => 'Bondek',
                'keterangan' => 'Produk bondek',
                'is_active' => true,
            ]
        );

        $kategori['hollow'] = KategoriBarang::firstOrCreate(
            ['kode_kategori' => 'KAT-HL'],
            [
                'nama_kategori' => 'Hollow',
                'keterangan' => 'Produk besi hollow',
                'is_active' => true,
            ]
        );

        $kategori['besi_beton'] = KategoriBarang::firstOrCreate(
            ['kode_kategori' => 'KAT-BB'],
            [
                'nama_kategori' => 'Besi Beton',
                'keterangan' => 'Produk besi beton',
                'is_active' => true,
            ]
        );

        $kategori['besi_siku'] = KategoriBarang::firstOrCreate(
            ['kode_kategori' => 'KAT-BS'],
            [
                'nama_kategori' => 'Besi Siku',
                'keterangan' => 'Produk besi siku',
                'is_active' => true,
            ]
        );

        $kategori['pipa_hitam'] = KategoriBarang::firstOrCreate(
            ['kode_kategori' => 'KAT-PH'],
            [
                'nama_kategori' => 'Pipa Hitam',
                'keterangan' => 'Produk pipa hitam',
                'is_active' => true,
            ]
        );

        $kategori['plat_hitam'] = KategoriBarang::firstOrCreate(
            ['kode_kategori' => 'KAT-PL'],
            [
                'nama_kategori' => 'Plat Hitam',
                'keterangan' => 'Produk plat hitam',
                'is_active' => true,
            ]
        );


        /*
        |--------------------------------------------------------------------------
        | DATA BARANG REAL
        | Sumber: Laporan Mutasi Stok Juni 2026
        |--------------------------------------------------------------------------
        */

        $barangs = [

            /*
            |--------------------------------------------------------------------------
            | BAJA RINGAN
            |--------------------------------------------------------------------------
            */

            [
                'kode_barang' => 'A0-00003',
                'nama_barang' => 'Baja Ringan C75',
                'kategori' => 'baja_ringan',
                'satuan_id' => $batang->id,
                'spesifikasi' => '35 × 0,75 AZ100 Premium',
                'merek' => null,
            ],

            [
                'kode_barang' => 'A0-00006',
                'nama_barang' => 'Baja Ringan C75',
                'kategori' => 'baja_ringan',
                'satuan_id' => $batang->id,
                'spesifikasi' => '35 × 0,70',
                'merek' => null,
            ],

            [
                'kode_barang' => 'A0-00009',
                'nama_barang' => 'Baja Ringan C75',
                'kategori' => 'baja_ringan',
                'satuan_id' => $batang->id,
                'spesifikasi' => '35 × 0,65',
                'merek' => null,
            ],

            [
                'kode_barang' => 'A0-00010',
                'nama_barang' => 'Baja Ringan C75',
                'kategori' => 'baja_ringan',
                'satuan_id' => $batang->id,
                'spesifikasi' => '35 × 0,60',
                'merek' => null,
            ],

            [
                'kode_barang' => 'A0-00013',
                'nama_barang' => 'Baja Ringan C75',
                'kategori' => 'baja_ringan',
                'satuan_id' => $batang->id,
                'spesifikasi' => '35 × 0,45',
                'merek' => null,
            ],


            /*
            |--------------------------------------------------------------------------
            | SPANDEK
            |--------------------------------------------------------------------------
            */

            [
                'kode_barang' => 'A1-00002',
                'nama_barang' => 'Spandek',
                'kategori' => 'spandek',
                'satuan_id' => $lembar->id,
                'spesifikasi' => '0,25 × 4 m',
                'merek' => null,
            ],

            [
                'kode_barang' => 'A1-00003',
                'nama_barang' => 'Spandek',
                'kategori' => 'spandek',
                'satuan_id' => $lembar->id,
                'spesifikasi' => '0,25 × 5 m',
                'merek' => null,
            ],

            [
                'kode_barang' => 'A1-00004',
                'nama_barang' => 'Spandek',
                'kategori' => 'spandek',
                'satuan_id' => $lembar->id,
                'spesifikasi' => '0,25 × 6 m',
                'merek' => null,
            ],


            /*
            |--------------------------------------------------------------------------
            | BONDEK
            |--------------------------------------------------------------------------
            */

            [
                'kode_barang' => 'A2-00003',
                'nama_barang' => 'Bondek Premium',
                'kategori' => 'bondek',
                'satuan_id' => $lembar->id,
                'spesifikasi' => '0,75 × 5 m',
                'merek' => null,
            ],

            [
                'kode_barang' => 'A2-00004',
                'nama_barang' => 'Bondek Premium',
                'kategori' => 'bondek',
                'satuan_id' => $lembar->id,
                'spesifikasi' => '0,75 × 6 m',
                'merek' => null,
            ],

            [
                'kode_barang' => 'A2-00103',
                'nama_barang' => 'Bondek F',
                'kategori' => 'bondek',
                'satuan_id' => $lembar->id,
                'spesifikasi' => '0,75 × 5 m',
                'merek' => null,
            ],

            [
                'kode_barang' => 'A2-00104',
                'nama_barang' => 'Bondek F',
                'kategori' => 'bondek',
                'satuan_id' => $lembar->id,
                'spesifikasi' => '0,75 × 6 m',
                'merek' => null,
            ],


            /*
            |--------------------------------------------------------------------------
            | HOLLOW
            |--------------------------------------------------------------------------
            */

            [
                'kode_barang' => 'A3-01001',
                'nama_barang' => 'Hollow',
                'kategori' => 'hollow',
                'satuan_id' => $batang->id,
                'spesifikasi' => '2 × 4 × 0,30',
                'merek' => 'TUBE',
            ],

            [
                'kode_barang' => 'A3-01002',
                'nama_barang' => 'Hollow',
                'kategori' => 'hollow',
                'satuan_id' => $batang->id,
                'spesifikasi' => '4 × 4 × 0,30',
                'merek' => 'TUBE',
            ],

            [
                'kode_barang' => 'A3-01011',
                'nama_barang' => 'Hollow',
                'kategori' => 'hollow',
                'satuan_id' => $batang->id,
                'spesifikasi' => '2 × 4 × 0,35',
                'merek' => 'BOS',
            ],

            [
                'kode_barang' => 'A3-01012',
                'nama_barang' => 'Hollow',
                'kategori' => 'hollow',
                'satuan_id' => $batang->id,
                'spesifikasi' => '4 × 4 × 0,35',
                'merek' => 'BOS',
            ],


            /*
            |--------------------------------------------------------------------------
            | BESI BETON
            |--------------------------------------------------------------------------
            */

            [
                'kode_barang' => 'D0-00001',
                'nama_barang' => 'Besi Beton Polos',
                'kategori' => 'besi_beton',
                'satuan_id' => $batang->id,
                'spesifikasi' => 'Ø6 mm',
                'merek' => null,
            ],

            [
                'kode_barang' => 'D0-00003',
                'nama_barang' => 'Besi Beton Polos',
                'kategori' => 'besi_beton',
                'satuan_id' => $batang->id,
                'spesifikasi' => 'Ø10 mm',
                'merek' => null,
            ],

            [
                'kode_barang' => 'D0-00005',
                'nama_barang' => 'Besi Beton Polos',
                'kategori' => 'besi_beton',
                'satuan_id' => $batang->id,
                'spesifikasi' => 'Ø16 mm',
                'merek' => null,
            ],

            [
                'kode_barang' => 'D0-00008',
                'nama_barang' => 'Besi Beton Polos',
                'kategori' => 'besi_beton',
                'satuan_id' => $batang->id,
                'spesifikasi' => 'Ø25 mm',
                'merek' => null,
            ],


            /*
            |--------------------------------------------------------------------------
            | BESI SIKU
            |--------------------------------------------------------------------------
            */

            [
                'kode_barang' => 'D3-00001',
                'nama_barang' => 'Besi Siku',
                'kategori' => 'besi_siku',
                'satuan_id' => $batang->id,
                'spesifikasi' => '30 × 30 × 3 mm C - 3,6 kg',
                'merek' => null,
            ],

            [
                'kode_barang' => 'D3-00003',
                'nama_barang' => 'Besi Siku',
                'kategori' => 'besi_siku',
                'satuan_id' => $batang->id,
                'spesifikasi' => '40 × 40 × 4 mm C - 8 kg',
                'merek' => null,
            ],

            [
                'kode_barang' => 'D3-00007',
                'nama_barang' => 'Besi Siku',
                'kategori' => 'besi_siku',
                'satuan_id' => $batang->id,
                'spesifikasi' => '50 × 50 × 5 mm C - 13 kg',
                'merek' => null,
            ],


            /*
            |--------------------------------------------------------------------------
            | PIPA HITAM
            |--------------------------------------------------------------------------
            */

            [
                'kode_barang' => 'D4-00001',
                'nama_barang' => 'Pipa Hitam',
                'kategori' => 'pipa_hitam',
                'satuan_id' => $batang->id,
                'spesifikasi' => '1/2" × 1,6 mm',
                'merek' => null,
            ],

            [
                'kode_barang' => 'D4-00003',
                'nama_barang' => 'Pipa Hitam',
                'kategori' => 'pipa_hitam',
                'satuan_id' => $batang->id,
                'spesifikasi' => '1" × 1,6 mm',
                'merek' => null,
            ],

            [
                'kode_barang' => 'D4-00005',
                'nama_barang' => 'Pipa Hitam',
                'kategori' => 'pipa_hitam',
                'satuan_id' => $batang->id,
                'spesifikasi' => '1 1/2" × 1,6 mm',
                'merek' => null,
            ],

            [
                'kode_barang' => 'D4-00006',
                'nama_barang' => 'Pipa Hitam',
                'kategori' => 'pipa_hitam',
                'satuan_id' => $batang->id,
                'spesifikasi' => '2" × 1,6 mm',
                'merek' => null,
            ],


            /*
            |--------------------------------------------------------------------------
            | PLAT HITAM
            |--------------------------------------------------------------------------
            */

            [
                'kode_barang' => 'D5-00001',
                'nama_barang' => 'Plat Hitam',
                'kategori' => 'plat_hitam',
                'satuan_id' => $lembar->id,
                'spesifikasi' => '0,9 mm (aktual 0,7 mm)',
                'merek' => null,
            ],

            [
                'kode_barang' => 'D5-00002',
                'nama_barang' => 'Plat Hitam',
                'kategori' => 'plat_hitam',
                'satuan_id' => $lembar->id,
                'spesifikasi' => '1,2 mm (aktual 1,0 mm)',
                'merek' => null,
            ],

            [
                'kode_barang' => 'D5-00003',
                'nama_barang' => 'Plat Hitam',
                'kategori' => 'plat_hitam',
                'satuan_id' => $lembar->id,
                'spesifikasi' => '1,4 mm (aktual 1,2 mm)',
                'merek' => null,
            ],
        ];


        /*
        |--------------------------------------------------------------------------
        | SIMPAN / UPDATE BARANG
        |--------------------------------------------------------------------------
        */

        foreach ($barangs as $data) {

            Barang::updateOrCreate(
                [
                    'kode_barang' => $data['kode_barang'],
                ],
                [
                    'nama_barang' => $data['nama_barang'],
                    'kategori_barang_id' => $kategori[$data['kategori']]->id,
                    'satuan_id' => $data['satuan_id'],
                    'spesifikasi' => $data['spesifikasi'],
                    'merek' => $data['merek'],

                    /*
                     * Belum tersedia pada laporan persediaan.
                     * Jangan diisi dengan angka asumsi.
                     */
                    'minimum_stok' => 0,
                    'harga_jual' => 0,

                    'is_active' => true,
                ]
            );
        }
    }
}