<?php

namespace Database\Seeders;

use App\Models\Akun;
use Illuminate\Database\Seeder;

class CoaSeeder extends Seeder
{
    public function run(): void
    {
        $akuns = [

            /*
            |--------------------------------------------------------------------------
            | ASET LANCAR
            |--------------------------------------------------------------------------
            */

            [
                'kode_akun' => '1101',
                'nama_akun' => 'Kas Toko',
                'kategori_akun' => 'Aset',
                'sub_kategori' => 'Aset Lancar',
                'saldo_normal' => 'DEBIT',
                'saldo_awal' => 25500000,
            ],

            [
                'kode_akun' => '1102',
                'nama_akun' => 'Bank BCA',
                'kategori_akun' => 'Aset',
                'sub_kategori' => 'Aset Lancar',
                'saldo_normal' => 'DEBIT',
                'saldo_awal' => 180250000,
            ],

            [
                'kode_akun' => '1103',
                'nama_akun' => 'Bank Mandiri',
                'kategori_akun' => 'Aset',
                'sub_kategori' => 'Aset Lancar',
                'saldo_normal' => 'DEBIT',
                'saldo_awal' => 72500000,
            ],

            [
                'kode_akun' => '1104',
                'nama_akun' => 'Bank BRI',
                'kategori_akun' => 'Aset',
                'sub_kategori' => 'Aset Lancar',
                'saldo_normal' => 'DEBIT',
                'saldo_awal' => 41200000,
            ],

            [
                'kode_akun' => '1120',
                'nama_akun' => 'Piutang Dagang',
                'kategori_akun' => 'Aset',
                'sub_kategori' => 'Aset Lancar',
                'saldo_normal' => 'DEBIT',
                'saldo_awal' => 42750000,
            ],

            [
                'kode_akun' => '1130',
                'nama_akun' => 'Persediaan Barang Dagang',
                'kategori_akun' => 'Aset',
                'sub_kategori' => 'Aset Lancar',
                'saldo_normal' => 'DEBIT',
                'saldo_awal' => 472300000,
            ],

            [
                'kode_akun' => '1140',
                'nama_akun' => 'Uang Muka Pembelian',
                'kategori_akun' => 'Aset',
                'sub_kategori' => 'Aset Lancar',
                'saldo_normal' => 'DEBIT',
                'saldo_awal' => 0,
            ],

            /*
            | PPN Masukan = pajak pembelian yang dapat dikreditkan.
            */

            [
                'kode_akun' => '1150',
                'nama_akun' => 'PPN Masukan',
                'kategori_akun' => 'Aset',
                'sub_kategori' => 'Aset Lancar',
                'saldo_normal' => 'DEBIT',
                'saldo_awal' => 0,
            ],


            /*
            |--------------------------------------------------------------------------
            | ASET TETAP
            |--------------------------------------------------------------------------
            */

            [
                'kode_akun' => '1201',
                'nama_akun' => 'Peralatan & Mesin',
                'kategori_akun' => 'Aset',
                'sub_kategori' => 'Aset Tetap',
                'saldo_normal' => 'DEBIT',
                'saldo_awal' => 85000000,
            ],

            [
                'kode_akun' => '1202',
                'nama_akun' => 'Akumulasi Penyusutan Peralatan',
                'kategori_akun' => 'Aset',
                'sub_kategori' => 'Aset Tetap',
                'saldo_normal' => 'KREDIT',
                'saldo_awal' => 15000000,
            ],

            [
                'kode_akun' => '1203',
                'nama_akun' => 'Kendaraan Operasional',
                'kategori_akun' => 'Aset',
                'sub_kategori' => 'Aset Tetap',
                'saldo_normal' => 'DEBIT',
                'saldo_awal' => 150000000,
            ],

            [
                'kode_akun' => '1204',
                'nama_akun' => 'Akumulasi Penyusutan Kendaraan',
                'kategori_akun' => 'Aset',
                'sub_kategori' => 'Aset Tetap',
                'saldo_normal' => 'KREDIT',
                'saldo_awal' => 30000000,
            ],


            /*
            |--------------------------------------------------------------------------
            | KEWAJIBAN LANCAR
            |--------------------------------------------------------------------------
            */

            [
                'kode_akun' => '2101',
                'nama_akun' => 'Utang Dagang (Supplier)',
                'kategori_akun' => 'Kewajiban',
                'sub_kategori' => 'Kewajiban Lancar',
                'saldo_normal' => 'KREDIT',
                'saldo_awal' => 68400000,
            ],

            [
                'kode_akun' => '2102',
                'nama_akun' => 'Utang Gaji',
                'kategori_akun' => 'Kewajiban',
                'sub_kategori' => 'Kewajiban Lancar',
                'saldo_normal' => 'KREDIT',
                'saldo_awal' => 0,
            ],

            [
                'kode_akun' => '2103',
                'nama_akun' => 'Utang Pajak PPN',
                'kategori_akun' => 'Kewajiban',
                'sub_kategori' => 'Kewajiban Lancar',
                'saldo_normal' => 'KREDIT',
                'saldo_awal' => 0,
            ],


            /*
            |--------------------------------------------------------------------------
            | KEWAJIBAN JANGKA PANJANG
            |--------------------------------------------------------------------------
            */

            [
                'kode_akun' => '2201',
                'nama_akun' => 'Utang Bank Jangka Panjang',
                'kategori_akun' => 'Kewajiban',
                'sub_kategori' => 'Kewajiban Jangka Panjang',
                'saldo_normal' => 'KREDIT',
                'saldo_awal' => 0,
            ],


            /*
            |--------------------------------------------------------------------------
            | EKUITAS
            |--------------------------------------------------------------------------
            */

            [
                'kode_akun' => '3101',
                'nama_akun' => 'Modal Owner',
                'kategori_akun' => 'Ekuitas',
                'sub_kategori' => 'Ekuitas',
                'saldo_normal' => 'KREDIT',
                'saldo_awal' => 900000000,
            ],

            [
                'kode_akun' => '3102',
                'nama_akun' => 'Laba Ditahan',
                'kategori_akun' => 'Ekuitas',
                'sub_kategori' => 'Ekuitas',
                'saldo_normal' => 'KREDIT',
                'saldo_awal' => 31100000,
            ],

            [
                'kode_akun' => '3103',
                'nama_akun' => 'Prive Owner',
                'kategori_akun' => 'Ekuitas',
                'sub_kategori' => 'Ekuitas',
                'saldo_normal' => 'DEBIT',
                'saldo_awal' => 0,
            ],


            /*
            |--------------------------------------------------------------------------
            | PENJUALAN
            |--------------------------------------------------------------------------
            */

            [
                'kode_akun' => '4101',
                'nama_akun' => 'Pendapatan Penjualan Barang',
                'kategori_akun' => 'Pendapatan',
                'sub_kategori' => 'Penjualan',
                'saldo_normal' => 'KREDIT',
                'saldo_awal' => 0,
            ],

            [
                'kode_akun' => '4102',
                'nama_akun' => 'Diskon Penjualan',
                'kategori_akun' => 'Pendapatan',
                'sub_kategori' => 'Penjualan',
                'saldo_normal' => 'DEBIT',
                'saldo_awal' => 0,
            ],

            [
                'kode_akun' => '4103',
                'nama_akun' => 'Retur Penjualan',
                'kategori_akun' => 'Pendapatan',
                'sub_kategori' => 'Penjualan',
                'saldo_normal' => 'DEBIT',
                'saldo_awal' => 0,
            ],


            /*
            |--------------------------------------------------------------------------
            | PENDAPATAN LAIN-LAIN
            |--------------------------------------------------------------------------
            */

            [
                'kode_akun' => '4201',
                'nama_akun' => 'Pendapatan Lain-lain',
                'kategori_akun' => 'Pendapatan',
                'sub_kategori' => 'Pendapatan Lain-Lain',
                'saldo_normal' => 'KREDIT',
                'saldo_awal' => 0,
            ],


            /*
            |--------------------------------------------------------------------------
            | HPP / PEMBELIAN
            |--------------------------------------------------------------------------
            */

            [
                'kode_akun' => '5101',
                'nama_akun' => 'Harga Pokok Penjualan (HPP)',
                'kategori_akun' => 'Beban',
                'sub_kategori' => 'Harga Pokok Penjualan',
                'saldo_normal' => 'DEBIT',
                'saldo_awal' => 0,
            ],

            [
                'kode_akun' => '5102',
                'nama_akun' => 'Diskon Pembelian',
                'kategori_akun' => 'Beban',
                'sub_kategori' => 'Pembelian',
                'saldo_normal' => 'KREDIT',
                'saldo_awal' => 0,
            ],

            [
                'kode_akun' => '5103',
                'nama_akun' => 'Retur Pembelian',
                'kategori_akun' => 'Beban',
                'sub_kategori' => 'Pembelian',
                'saldo_normal' => 'KREDIT',
                'saldo_awal' => 0,
            ],


            /*
            |--------------------------------------------------------------------------
            | BEBAN OPERASIONAL
            |--------------------------------------------------------------------------
            */

            [
                'kode_akun' => '6101',
                'nama_akun' => 'Beban Gaji Karyawan',
                'kategori_akun' => 'Beban',
                'sub_kategori' => 'Beban Operasional',
                'saldo_normal' => 'DEBIT',
                'saldo_awal' => 0,
            ],

            [
                'kode_akun' => '6102',
                'nama_akun' => 'Beban Listrik, Air & Internet',
                'kategori_akun' => 'Beban',
                'sub_kategori' => 'Beban Operasional',
                'saldo_normal' => 'DEBIT',
                'saldo_awal' => 0,
            ],

            [
                'kode_akun' => '6103',
                'nama_akun' => 'Beban Transportasi & Angkut',
                'kategori_akun' => 'Beban',
                'sub_kategori' => 'Beban Operasional',
                'saldo_normal' => 'DEBIT',
                'saldo_awal' => 0,
            ],

            [
                'kode_akun' => '6104',
                'nama_akun' => 'Beban Sewa Gudang',
                'kategori_akun' => 'Beban',
                'sub_kategori' => 'Beban Operasional',
                'saldo_normal' => 'DEBIT',
                'saldo_awal' => 0,
            ],

            [
                'kode_akun' => '6105',
                'nama_akun' => 'Beban Pemeliharaan & Perbaikan',
                'kategori_akun' => 'Beban',
                'sub_kategori' => 'Beban Operasional',
                'saldo_normal' => 'DEBIT',
                'saldo_awal' => 0,
            ],

            [
                'kode_akun' => '6106',
                'nama_akun' => 'Beban Penyusutan Aset',
                'kategori_akun' => 'Beban',
                'sub_kategori' => 'Beban Operasional',
                'saldo_normal' => 'DEBIT',
                'saldo_awal' => 0,
            ],


            /*
            |--------------------------------------------------------------------------
            | BEBAN LAIN-LAIN
            |--------------------------------------------------------------------------
            */

            [
                'kode_akun' => '6201',
                'nama_akun' => 'Beban Administrasi Bank',
                'kategori_akun' => 'Beban',
                'sub_kategori' => 'Beban Lain-Lain',
                'saldo_normal' => 'DEBIT',
                'saldo_awal' => 0,
            ],


            /*
            |--------------------------------------------------------------------------
            | BEBAN PAJAK
            |--------------------------------------------------------------------------
            */

            [
                'kode_akun' => '6301',
                'nama_akun' => 'Beban Pajak Penghasilan (PPh)',
                'kategori_akun' => 'Beban',
                'sub_kategori' => 'Pajak',
                'saldo_normal' => 'DEBIT',
                'saldo_awal' => 0,
            ],
        ];

        foreach ($akuns as $akun) {
            Akun::updateOrCreate(
                [
                    'kode_akun' =>
                        $akun['kode_akun'],
                ],
                $akun
            );
        }
    }
}