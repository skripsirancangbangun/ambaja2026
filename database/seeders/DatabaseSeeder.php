<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@ambaja.com'],
            [
                'name' => 'Admin Ambaja',
                'password' => bcrypt('password'),
                'role' => 'Admin',
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'owner@ambaja.com'],
            [
                'name' => 'Owner Ambaja',
                'password' => bcrypt('password'),
                'role' => 'Owner',
                'is_active' => true,
            ]
        );

        $this->call([
            CoaSeeder::class,
            PengaturanAkunSeeder::class,
            PengaturanPenomoranSeeder::class,
            SupplierSeeder::class,
            CustomerSeeder::class,
            MasterBarangSeeder::class,
            HargaBarangSeeder::class,
            RekeningKasBankSeeder::class,
      ]);
    }
}
