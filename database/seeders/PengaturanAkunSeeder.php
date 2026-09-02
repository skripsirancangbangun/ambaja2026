<?php

namespace Database\Seeders;

use App\Models\Akun;
use App\Models\PengaturanAkun;
use Illuminate\Database\Seeder;

class PengaturanAkunSeeder extends Seeder
{
    public function run(): void
    {
        $mappings = [
            [
                'kunci_pengaturan' => 'AKUN_PIUTANG_DAGANG',
                'nama_pengaturan' => 'Akun Piutang Dagang',
                'kode_akun_default' => '1120',
                'keterangan' => 'Digunakan otomatis saat transaksi Penjualan Kredit.',
            ],
            [
                'kunci_pengaturan' => 'AKUN_PENDAPATAN_PENJUALAN',
                'nama_pengaturan' => 'Akun Pendapatan Penjualan',
                'kode_akun_default' => '4101',
                'keterangan' => 'Digunakan otomatis saat transaksi Penjualan Barang.',
            ],
            [
                'kunci_pengaturan' => 'AKUN_DISKON_PENJUALAN',
                'nama_pengaturan' => 'Akun Diskon Penjualan',
                'kode_akun_default' => '4102',
                'keterangan' => 'Digunakan otomatis saat memberikan potongan harga penjualan.',
            ],
            [
                'kunci_pengaturan' => 'AKUN_KAS_UTAMA',
                'nama_pengaturan' => 'Akun Kas Utama / Toko',
                'kode_akun_default' => '1101',
                'keterangan' => 'Digunakan otomatis untuk transaksi Penjualan & Pembayaran Tunai.',
            ],
            [
                'kunci_pengaturan' => 'AKUN_UTANG_DAGANG',
                'nama_pengaturan' => 'Akun Utang Dagang (Supplier)',
                'kode_akun_default' => '2101',
                'keterangan' => 'Digunakan otomatis saat transaksi Pembelian Kredit dari Supplier.',
            ],
            [
                'kunci_pengaturan' => 'AKUN_PERSEDIAAN',
                'nama_pengaturan' => 'Akun Persediaan Barang Dagang',
                'kode_akun_default' => '1130',
                'keterangan' => 'Digunakan otomatis saat persediaan barang bertambah atau berkurang.',
            ],
            [
                'kunci_pengaturan' => 'AKUN_HPP',
                'nama_pengaturan' => 'Akun Harga Pokok Penjualan (HPP)',
                'kode_akun_default' => '5101',
                'keterangan' => 'Digunakan otomatis untuk pengakuan Beban HPP saat penjualan.',
            ],
        ];

        foreach ($mappings as $m) {
            $akun = Akun::where('kode_akun', $m['kode_akun_default'])->first();
            PengaturanAkun::updateOrCreate(
                ['kunci_pengaturan' => $m['kunci_pengaturan']],
                [
                    'nama_pengaturan' => $m['nama_pengaturan'],
                    'akun_id' => $akun?->id,
                    'keterangan' => $m['keterangan'],
                ]
            );
        }
    }
}
