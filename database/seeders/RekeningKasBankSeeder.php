<?php

namespace Database\Seeders;

use App\Models\Akun;
use App\Models\MutasiKasBank;
use App\Models\RekeningKasBank;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RekeningKasBankSeeder extends Seeder
{
    public function run(): void
    {
        $rekenings = [
            [
                'kode_rekening' => 'KAS-001',
                'nama_rekening' => 'Kas Kecil',
                'jenis' => 'KAS',
                'nama_bank' => null,
                'nomor_rekening' => 'TUNAI',
                'atas_nama' => 'CV Andika Mustika',
                'saldo_awal' => 25500000,
                'is_active' => true,
                'kode_akun' => '1101',
            ],
            [
                'kode_rekening' => 'BANK-001',
                'nama_rekening' => 'BCA',
                'jenis' => 'BANK',
                'nama_bank' => 'Bank BCA',
                'nomor_rekening' => '123-456-7890',
                'atas_nama' => 'CV Andika Mustika',
                'saldo_awal' => 180250000,
                'is_active' => true,
                'kode_akun' => '1102',
            ],
        ];

        DB::transaction(function () use ($rekenings): void {
            foreach ($rekenings as $data) {
                $akun = Akun::query()
                    ->where('kode_akun', $data['kode_akun'])
                    ->first();

                if (!$akun) {
                    throw new RuntimeException(sprintf(
                        'COA %s belum tersedia. Jalankan CoaSeeder terlebih dahulu.',
                        $data['kode_akun'],
                    ));
                }

                $payload = $data;
                unset($payload['kode_akun']);
                $payload['akun_id'] = $akun->id;

                $rekening = RekeningKasBank::query()
                    ->where('kode_rekening', $payload['kode_rekening'])
                    ->first();

                if (!$rekening) {
                    RekeningKasBank::create($payload);
                    continue;
                }

                $hasMutations = MutasiKasBank::query()
                    ->where('rekening_kas_bank_id', $rekening->id)
                    ->exists();

                if ($hasMutations && (int) $rekening->akun_id !== (int) $akun->id) {
                    throw new RuntimeException(sprintf(
                        'Rekening %s memiliki mutasi dan mapping COA berbeda. Seeder dibatalkan agar histori tidak berubah.',
                        $rekening->kode_rekening,
                    ));
                }

                // Preserve existing balances. This seeder initializes balances only on a clean database.
                unset($payload['saldo_awal']);
                $rekening->fill($payload);
                $rekening->save();
            }

            foreach ($rekenings as $data) {
                $rekening = RekeningKasBank::query()
                    ->where('kode_rekening', $data['kode_rekening'])
                    ->with('akun')
                    ->firstOrFail();

                if (!$rekening->akun) {
                    throw new RuntimeException(sprintf(
                        'Rekening %s belum terhubung ke COA.',
                        $rekening->kode_rekening,
                    ));
                }

                if (abs((float) $rekening->saldo_awal - (float) $rekening->akun->saldo_awal) > 0.01) {
                    throw new RuntimeException(sprintf(
                        'Saldo awal rekening %s tidak sama dengan COA %s. Seeder dibatalkan; jangan koreksi saldo secara otomatis.',
                        $rekening->kode_rekening,
                        $rekening->akun->kode_akun,
                    ));
                }
            }
        });
    }
}
