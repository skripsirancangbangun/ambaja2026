<?php

namespace Database\Seeders;

use App\Models\Akun;
use App\Models\PengaturanAkun;
use Illuminate\Database\Seeder;

class PengaturanAkunSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [

            /*
            |--------------------------------------------------------------------------
            | PENJUALAN & PIUTANG
            |--------------------------------------------------------------------------
            */

            [
                'kunci' =>
                    'AKUN_PIUTANG_DAGANG',

                'nama' =>
                    'Akun Piutang Dagang',

                'ket' =>
                    'Digunakan otomatis saat transaksi Penjualan Kredit.',

                'search' => [
                    'Piutang Usaha',
                    'Piutang Dagang',
                    'Piutang',
                ],
            ],

            [
                'kunci' =>
                    'AKUN_PENDAPATAN_PENJUALAN',

                'nama' =>
                    'Akun Pendapatan Penjualan',

                'ket' =>
                    'Digunakan otomatis saat transaksi Penjualan Barang.',

                'search' => [
                    'Penjualan Besi dan Baja',
                    'Pendapatan Penjualan Barang',
                    'Pendapatan Penjualan',
                    'Penjualan',
                ],
            ],

            [
                'kunci' =>
                    'AKUN_DISKON_PENJUALAN',

                'nama' =>
                    'Akun Diskon Penjualan',

                'ket' =>
                    'Digunakan otomatis saat memberikan potongan harga penjualan.',

                'search' => [
                    'Potongan Penjualan',
                    'Diskon Penjualan',
                ],
            ],

            [
                'kunci' =>
                    'AKUN_PERSEDIAAN',

                'nama' =>
                    'Akun Persediaan Barang Dagang',

                'ket' =>
                    'Digunakan otomatis saat persediaan barang bertambah atau berkurang.',

                'search' => [
                    'Persediaan Besi dan Baja',
                    'Persediaan Barang Dagang',
                    'Persediaan',
                ],
            ],

            [
                'kunci' =>
                    'AKUN_HPP',

                'nama' =>
                    'Akun Harga Pokok Penjualan (HPP)',

                'ket' =>
                    'Digunakan otomatis untuk pengakuan Beban HPP saat penjualan.',

                'search' => [
                    'Harga Pokok Penjualan Besi dan Baja',
                    'Harga Pokok Penjualan (HPP)',
                    'Harga Pokok Penjualan',
                    'HPP',
                ],
            ],


            /*
            |--------------------------------------------------------------------------
            | RETUR / PPN PENJUALAN
            |--------------------------------------------------------------------------
            */

            [
                'kunci' =>
                    'AKUN_RETUR_PENJUALAN',

                'nama' =>
                    'Akun Retur Penjualan',

                'ket' =>
                    'Digunakan otomatis saat penerbitan Retur Penjualan.',

                'search' => [
                    'Retur Penjualan',
                    'Potongan Penjualan',
                    'Diskon Penjualan',
                    'Penjualan Besi dan Baja',
                    'Pendapatan Penjualan Barang',
                ],
            ],

            [
                'kunci' =>
                    'AKUN_PPN_PENJUALAN',

                'nama' =>
                    'Akun PPN Penjualan (PPN Keluaran)',

                'ket' =>
                    'Digunakan otomatis untuk pencatatan PPN Keluaran dari penjualan.',

                'search' => [
                    'Utang PPN',
                    'Utang Pajak PPN',
                    'PPN Keluaran',
                    'PPN',
                    'Pajak',
                ],
            ],


            /*
            |--------------------------------------------------------------------------
            | PEMBELIAN & UTANG
            |--------------------------------------------------------------------------
            */

            [
                'kunci' =>
                    'AKUN_UTANG_DAGANG',

                'nama' =>
                    'Akun Utang Dagang (Supplier)',

                'ket' =>
                    'Digunakan otomatis saat Invoice Supplier diterbitkan.',

                'search' => [
                    'Utang Usaha',
                    'Utang Dagang (Supplier)',
                    'Utang Dagang',
                    'Utang Supplier',
                ],
            ],

            [
                'kunci' =>
                    'AKUN_PEMBELIAN_BARANG',

                'nama' =>
                    'Akun Pembelian / Persediaan Barang',

                'ket' =>
                    'Digunakan otomatis untuk pencatatan nilai barang pada Invoice Supplier.',

                'search' => [
                    'Persediaan Besi dan Baja',
                    'Persediaan Barang Dagang',
                    'Pembelian Barang',
                    'Pembelian',
                ],
            ],

            /*
            | PPN Masukan harus dipisahkan dari PPN Keluaran.
            */

            [
                'kunci' =>
                    'AKUN_PPN_PEMBELIAN',

                'nama' =>
                    'Akun PPN Masukan Pembelian',

                'ket' =>
                    'Digunakan otomatis untuk pencatatan PPN Masukan dari Invoice Supplier.',

                'search' => [
                    'PPN Masukan',
                    'Pajak Masukan',
                ],
            ],

            [
                'kunci' =>
                    'AKUN_DISKON_PEMBELIAN',

                'nama' =>
                    'Akun Diskon Pembelian',

                'ket' =>
                    'Digunakan otomatis saat menerima diskon invoice dari supplier.',

                'search' => [
                    'Potongan Pembelian',
                    'Diskon Pembelian',
                ],
            ],


            /*
            |--------------------------------------------------------------------------
            | UANG MUKA PEMBELIAN
            |--------------------------------------------------------------------------
            */

            [
                'kunci' =>
                    'AKUN_UANG_MUKA_PEMBELIAN',

                'nama' =>
                    'Akun Uang Muka Pembelian',

                'ket' =>
                    'Digunakan otomatis untuk DP Supplier dan kredit supplier yang belum dialokasikan.',

                'search' => [
                    'Uang Muka Pembelian',
                    'Uang Muka Supplier',
                    'DP Supplier',
                ],
            ],


            /*
            |--------------------------------------------------------------------------
            | RETUR PEMBELIAN
            |--------------------------------------------------------------------------
            */

            [
                'kunci' =>
                    'AKUN_RETUR_PEMBELIAN',

                'nama' =>
                    'Akun Retur Pembelian',

                'ket' =>
                    'Digunakan otomatis saat transaksi Retur Pembelian ke Supplier.',

                'search' => [
                    'Retur Pembelian',
                    'Persediaan Besi dan Baja',
                    'Persediaan Barang Dagang',
                ],
            ],


            /*
            |--------------------------------------------------------------------------
            | KAS / BANK
            |--------------------------------------------------------------------------
            */

            [
                'kunci' =>
                    'AKUN_KAS_UTAMA',

                'nama' =>
                    'Akun Kas Utama / Toko',

                'ket' =>
                    'Digunakan otomatis untuk transaksi Pembayaran & Penerimaan Tunai.',

                'search' => [
                    'Kas Kecil',
                    'Kas Toko',
                    'Kas Utama',
                    'Kas di Bank',
                    'Kas',
                ],
            ],
        ];

        foreach ($settings as $setting) {

            $matchedAkun = null;

            foreach (
                $setting['search']
                as $term
            ) {
                $matchedAkun =
                    Akun::query()
                        ->where(
                            'nama_akun',
                            'like',
                            "%{$term}%"
                        )
                        ->first();

                if ($matchedAkun) {
                    break;
                }
            }

            PengaturanAkun::updateOrCreate(
                [
                    'kunci_pengaturan' =>
                        $setting['kunci'],
                ],
                [
                    'nama_pengaturan' =>
                        $setting['nama'],

                    'akun_id' =>
                        $matchedAkun?->id,

                    'keterangan' =>
                        $setting['ket'],
                ]
            );
        }
    }
}