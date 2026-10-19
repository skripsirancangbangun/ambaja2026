<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $adminPassword = env('AMBAJA_ADMIN_PASSWORD');
        $ownerPassword = env('AMBAJA_OWNER_PASSWORD');

        if (app()->environment('production') && (blank($adminPassword) || blank($ownerPassword))) {
            throw new RuntimeException(
                'Set AMBAJA_ADMIN_PASSWORD and AMBAJA_OWNER_PASSWORD before seeding users in production.'
            );
        }

        // Keep the convenience password only for local/test development.
        $adminPassword = $adminPassword ?: 'password';
        $ownerPassword = $ownerPassword ?: 'password';

        User::updateOrCreate(
            ['email' => 'admin@ambaja.com'],
            [
                'name' => 'Admin Ambaja',
                'password' => Hash::make($adminPassword),
                'role' => 'Admin',
                'is_active' => true,
            ]
        );

        User::updateOrCreate(
            ['email' => 'owner@ambaja.com'],
            [
                'name' => 'Owner Ambaja',
                'password' => Hash::make($ownerPassword),
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
