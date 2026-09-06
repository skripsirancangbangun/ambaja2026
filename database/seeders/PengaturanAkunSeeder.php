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
            // PART 1: PENJUALAN & PIUTANG
            [
                'kunci' => 'AKUN_PIUTANG_DAGANG',
                'nama' => 'Akun Piutang Dagang',
                'ket' => 'Digunakan otomatis saat transaksi Penjualan Kredit.',
                'search' => ['Piutang Dagang', 'Piutang'],
            ],
            [
                'kunci' => 'AKUN_PENDAPATAN_PENJUALAN',
                'nama' => 'Akun Pendapatan Penjualan',
                'ket' => 'Digunakan otomatis saat transaksi Penjualan Barang.',
                'search' => ['Pendapatan Penjualan Barang', 'Pendapatan Penjualan', 'Penjualan'],
            ],
            [
                'kunci' => 'AKUN_DISKON_PENJUALAN',
                'nama' => 'Akun Diskon Penjualan',
                'ket' => 'Digunakan otomatis saat memberikan potongan harga penjualan.',
                'search' => ['Diskon Penjualan', 'Potongan Penjualan'],
            ],
            [
                'kunci' => 'AKUN_PERSEDIAAN',
                'nama' => 'Akun Persediaan Barang Dagang',
                'ket' => 'Digunakan otomatis saat persediaan barang bertambah atau berkurang.',
                'search' => ['Persediaan Barang Dagang', 'Persediaan'],
            ],
            [
                'kunci' => 'AKUN_HPP',
                'nama' => 'Akun Harga Pokok Penjualan (HPP)',
                'ket' => 'Digunakan otomatis untuk pengakuan Beban HPP saat penjualan.',
                'search' => ['Harga Pokok Penjualan (HPP)', 'Harga Pokok Penjualan', 'HPP'],
            ],

            // PART 2: RETUR PENJUALAN
            [
                'kunci' => 'AKUN_RETUR_PENJUALAN',
                'nama' => 'Akun Retur Penjualan',
                'ket' => 'Digunakan otomatis saat penerbitan Retur Penjualan.',
                'search' => ['Retur Penjualan', 'Diskon Penjualan', 'Pendapatan Penjualan Barang'],
            ],
            [
                'kunci' => 'AKUN_PPN_PENJUALAN',
                'nama' => 'Akun PPN Penjualan (Pajak)',
                'ket' => 'Digunakan otomatis untuk pencatatan Pajak PPN Keluar / Masuk.',
                'search' => ['Utang Pajak PPN', 'PPN', 'Pajak'],
            ],

            // PART 3: PEMBELIAN & UTANG
            [
                'kunci' => 'AKUN_UTANG_DAGANG',
                'nama' => 'Akun Utang Dagang (Supplier)',
                'ket' => 'Digunakan otomatis saat transaksi Pembelian Kredit dari Supplier.',
                'search' => ['Utang Dagang (Supplier)', 'Utang Dagang', 'Utang Supplier'],
            ],
            [
                'kunci' => 'AKUN_PEMBELIAN_BARANG',
                'nama' => 'Akun Pembelian / Transit Persediaan',
                'ket' => 'Digunakan otomatis saat pencatatan transaksi pembelian barang.',
                'search' => ['Persediaan Barang Dagang', 'Pembelian Barang', 'Pembelian'],
            ],
            [
                'kunci' => 'AKUN_DISKON_PEMBELIAN',
                'nama' => 'Akun Diskon Pembelian',
                'ket' => 'Digunakan otomatis saat menerima potongan harga dari supplier.',
                'search' => ['Diskon Pembelian', 'Potongan Pembelian', 'Harga Pokok Penjualan (HPP)'],
            ],

            // PART 4: RETUR PEMBELIAN & KAS/BANK
            [
                'kunci' => 'AKUN_RETUR_PEMBELIAN',
                'nama' => 'Akun Retur Pembelian',
                'ket' => 'Digunakan otomatis saat transaksi Retur Pembelian ke Supplier.',
                'search' => ['Retur Pembelian', 'Persediaan Barang Dagang'],
            ],
            [
                'kunci' => 'AKUN_KAS_UTAMA',
                'nama' => 'Akun Kas Utama / Toko',
                'ket' => 'Digunakan otomatis untuk transaksi Pembayaran & Penerimaan Tunai.',
                'search' => ['Kas Toko', 'Kas Utama', 'Kas'],
            ],
        ];

        foreach ($settings as $s) {
            $matchedAkun = null;

            foreach ($s['search'] as $term) {
                $matchedAkun = Akun::where('nama_akun', 'like', "%{$term}%")->first();
                if ($matchedAkun) {
                    break;
                }
            }

            PengaturanAkun::updateOrCreate(
                ['kunci_pengaturan' => $s['kunci']],
                [
                    'nama_pengaturan' => $s['nama'],
                    'akun_id' => $matchedAkun?->id,
                    'keterangan' => $s['ket'],
                ]
            );
        }
    }
}
