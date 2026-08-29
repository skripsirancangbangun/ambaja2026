<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [

            [
                'kode_supplier' => 'SUP-001',
                'nama_supplier' => 'PT Bumi Hijau Indonesia',
                'npwp' => '1000000004371547',
                'contact_person' => null,
                'telepon' => null,
                'email' => null,
                'alamat' => 'Jl. Gatot Subroto, Kawasan Industri Candi Blok E2 Nomor 69, 71, 82, 86, Ngaliyan',
                'kota' => 'Kota Semarang',
                'is_active' => true,
            ],

            [
                'kode_supplier' => 'SUP-002',
                'nama_supplier' => 'Pranata Jaya Mulia',
                'npwp' => '0024052375503000',
                'contact_person' => null,
                'telepon' => null,
                'email' => null,
                'alamat' => 'Jl. R.E. Martadinata No. 1, Tawangmas, Semarang Barat',
                'kota' => 'Kota Semarang',
                'is_active' => true,
            ],

            [
                'kode_supplier' => 'SUP-003',
                'nama_supplier' => 'PT Abadi Jaya Makmur Mandiri',
                'npwp' => '0750187163503000',
                'contact_person' => null,
                'telepon' => null,
                'email' => null,
                'alamat' => 'Jl. Candi Blok 8, Kawasan Industri Candi No. 9, Ngaliyan',
                'kota' => 'Kota Semarang',
                'is_active' => true,
            ],

            [
                'kode_supplier' => 'SUP-004',
                'nama_supplier' => 'PT Maiko Baru',
                'npwp' => '0011322120511000',
                'contact_person' => null,
                'telepon' => null,
                'email' => null,
                'alamat' => 'Jl. Letjen M.T. Haryono No. 563, Jagalan, Semarang Tengah',
                'kota' => 'Kota Semarang',
                'is_active' => true,
            ],

            [
                'kode_supplier' => 'SUP-005',
                'nama_supplier' => 'Rajawali Baja Nusantara',
                'npwp' => '0639890771513000',
                'contact_person' => null,
                'telepon' => null,
                'email' => null,
                'alamat' => 'Jl. Pantai Ngebum, Mororejo, Kaliwungu',
                'kota' => 'Kabupaten Kendal',
                'is_active' => true,
            ],

            [
                'kode_supplier' => 'SUP-006',
                'nama_supplier' => 'Samudra Baja Dunia',
                'npwp' => '0758199442503000',
                'contact_person' => null,
                'telepon' => null,
                'email' => null,
                'alamat' => 'Jl. Gatot Subroto Blok C-6 No. 7, Ngaliyan',
                'kota' => 'Kota Semarang',
                'is_active' => true,
            ],

            [
                'kode_supplier' => 'SUP-007',
                'nama_supplier' => 'Candi Anugrah Steel',
                'npwp' => '0636164493503000',
                'contact_person' => null,
                'telepon' => null,
                'email' => null,
                'alamat' => 'Kawasan Industri Candi, Jl. Gatot Subroto 8D No. 03, Bambankerep, Ngaliyan',
                'kota' => 'Kota Semarang',
                'is_active' => true,
            ],

            [
                'kode_supplier' => 'SUP-008',
                'nama_supplier' => 'Global Plastindo Industri',
                'npwp' => '0824551378402000',
                'contact_person' => null,
                'telepon' => null,
                'email' => null,
                'alamat' => 'Jl. Prabu Siliwangi No. 82, Alam Jaya, Jatiuwung',
                'kota' => 'Kota Tangerang',
                'is_active' => true,
            ],

            [
                'kode_supplier' => 'SUP-009',
                'nama_supplier' => 'PT Batraja Makmur Wiretama',
                'npwp' => '0019608124518000',
                'contact_person' => null,
                'telepon' => null,
                'email' => null,
                'alamat' => 'Kawasan Industri Terboyo Blok F No. 17-19, Trimulyo, Genuk',
                'kota' => 'Kota Semarang',
                'is_active' => true,
            ],

            [
                'kode_supplier' => 'SUP-010',
                'nama_supplier' => 'Immanuel Dunamis',
                'npwp' => '0018305094511000',
                'contact_person' => null,
                'telepon' => null,
                'email' => null,
                'alamat' => 'Jl. Gatot Subroto N No. 1, Purwoyoso, Ngaliyan',
                'kota' => 'Kota Semarang',
                'is_active' => true,
            ],

            [
                'kode_supplier' => 'SUP-011',
                'nama_supplier' => 'CV Mulia Agung Condro',
                'npwp' => '0029551264503000',
                'contact_person' => null,
                'telepon' => null,
                'email' => null,
                'alamat' => 'Jl. R.E. Martadinata I, Tawangmas, Semarang Barat',
                'kota' => 'Kota Semarang',
                'is_active' => true,
            ],

            [
                'kode_supplier' => 'SUP-012',
                'nama_supplier' => 'PT Eupfin Elektronik Indonesia',
                'npwp' => '0821385986414000',
                'contact_person' => null,
                'telepon' => null,
                'email' => null,
                'alamat' => 'Ruko Jababeka Shop House 9, Pasirgombong, Cikarang Utara',
                'kota' => 'Kabupaten Bekasi',
                'is_active' => true,
            ],

            [
                'kode_supplier' => 'SUP-013',
                'nama_supplier' => 'Anugrah Tritunggal Perkasa Sejati',
                'npwp' => '0804210177503000',
                'contact_person' => null,
                'telepon' => null,
                'email' => null,
                'alamat' => 'Jl. Gatot Subroto, Kawasan Industri Candi Blok 5 No. 1, Ngaliyan',
                'kota' => 'Kota Semarang',
                'is_active' => true,
            ],

            [
                'kode_supplier' => 'SUP-014',
                'nama_supplier' => 'Samwang Brothers Indonesia',
                'npwp' => '0946443843503000',
                'contact_person' => null,
                'telepon' => null,
                'email' => null,
                'alamat' => 'Jl. Gatot Subroto, Kawasan Industri Candi 8A No. 9, Bambankerep, Ngaliyan',
                'kota' => 'Kota Semarang',
                'is_active' => true,
            ],

            [
                'kode_supplier' => 'SUP-015',
                'nama_supplier' => 'Usaha Karya Kencana',
                'npwp' => '0824463541518000',
                'contact_person' => null,
                'telepon' => null,
                'email' => null,
                'alamat' => 'Jl. Raya Stasiun Gawok No. 99, Dusun Gesingan, Luwang, Gatak',
                'kota' => 'Kabupaten Sukoharjo',
                'is_active' => true,
            ],

            [
                'kode_supplier' => 'SUP-016',
                'nama_supplier' => 'Sutindo Raya Mulia',
                'npwp' => '0017395013631000',
                'contact_person' => null,
                'telepon' => null,
                'email' => null,
                'alamat' => null,
                'kota' => null,
                'is_active' => true,
            ],

            [
                'kode_supplier' => 'SUP-017',
                'nama_supplier' => 'PT Kapuas Logam Indonesia',
                'npwp' => '0276310364503000',
                'contact_person' => null,
                'telepon' => null,
                'email' => null,
                'alamat' => null,
                'kota' => null,
                'is_active' => true,
            ],

            [
                'kode_supplier' => 'SUP-018',
                'nama_supplier' => 'Sekawan Niaga Jaya',
                'npwp' => '029550654511000',
                'contact_person' => null,
                'telepon' => null,
                'email' => null,
                'alamat' => 'Jl. Raya Terboyo No. 03, Terboyo Wetan, Genuk',
                'kota' => 'Kota Semarang',
                'is_active' => true,
            ],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::updateOrCreate(
                [
                    'kode_supplier' => $supplier['kode_supplier'],
                ],
                $supplier
            );
        }
    }
}