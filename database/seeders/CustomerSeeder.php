<?php

namespace Database\Seeders;

use App\Models\Customer;
use Illuminate\Database\Seeder;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $customers = [
            [
                'kode_customer' => 'CUST-0001',
                'nama_customer' => 'PT Konstruksi Mega Perkasa',
                'npwp' => '01.234.567.8-012.000',
                'contact_person' => 'Bpk. Hendra Gunawan',
                'telepon' => '081234567890',
                'email' => 'purchasing@megaperkasa.co.id',
                'alamat' => 'Jl. Rungkut Industri Raya No. 45',
                'kota' => 'Surabaya',
                'limit_piutang' => 150000000,
                'is_active' => true,
            ],
            [
                'kode_customer' => 'CUST-0002',
                'nama_customer' => 'CV Sumber Makmur Baja',
                'npwp' => '02.345.678.9-023.000',
                'contact_person' => 'Ibu Ratna Sari',
                'telepon' => '081398765432',
                'email' => 'admin@sumbermakmur.com',
                'alamat' => 'Jl. Ahmad Yani KM 12',
                'kota' => 'Sidoarjo',
                'limit_piutang' => 80000000,
                'is_active' => true,
            ],
            [
                'kode_customer' => 'CUST-0003',
                'nama_customer' => 'Toko Besi & Bangunan Jaya Abadi',
                'npwp' => null,
                'contact_person' => 'Koh David',
                'telepon' => '085711223344',
                'email' => 'david.jayaabadi@gmail.com',
                'alamat' => 'Pasar Besar No. 88',
                'kota' => 'Malang',
                'limit_piutang' => 50000000,
                'is_active' => true,
            ],
        ];

        foreach ($customers as $c) {
            Customer::updateOrCreate(['kode_customer' => $c['kode_customer']], $c);
        }
    }
}
