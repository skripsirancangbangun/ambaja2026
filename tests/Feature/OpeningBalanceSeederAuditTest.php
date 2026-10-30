<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\RekeningKasBank;
use App\Models\PengaturanAkun;
use Database\Seeders\CoaSeeder;
use Database\Seeders\PengaturanAkunSeeder;
use Database\Seeders\RekeningKasBankSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class OpeningBalanceSeederAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeded_opening_balances_are_balanced_and_cash_bank_matches_coa(): void
    {
        $this->seed(CoaSeeder::class);
        $this->seed(PengaturanAkunSeeder::class);
        $this->seed(RekeningKasBankSeeder::class);

        $totalDebit = (float) Akun::query()
            ->where('saldo_normal', 'DEBIT')
            ->sum('saldo_awal');
        $totalCredit = (float) Akun::query()
            ->where('saldo_normal', 'KREDIT')
            ->sum('saldo_awal');

        $this->assertEqualsWithDelta(205750000.00, $totalDebit, 0.01);
        $this->assertEqualsWithDelta($totalDebit, $totalCredit, 0.01);
        $this->assertSame(2, RekeningKasBank::query()->count());

        $expectedMapping = [
            'KAS-001' => '1101',
            'BANK-001' => '1102',
        ];

        foreach ($expectedMapping as $kodeRekening => $kodeAkun) {
            $rekening = RekeningKasBank::query()
                ->with('akun')
                ->where('kode_rekening', $kodeRekening)
                ->firstOrFail();

            $this->assertNotNull($rekening->akun);
            $this->assertSame($kodeAkun, $rekening->akun->kode_akun);
            $this->assertEqualsWithDelta(
                (float) $rekening->saldo_awal,
                (float) $rekening->akun->saldo_awal,
                0.01,
            );
        }

        $this->assertSame(0, (int) Akun::query()->where('kode_akun', '1103')->value('is_active'));
        $this->assertSame(0, (int) Akun::query()->where('kode_akun', '1104')->value('is_active'));
        $this->assertEqualsWithDelta(205750000.00, (float) Akun::query()->where('kode_akun', '3104')->value('saldo_awal'), 0.01);
        $this->assertSame('3104', Akun::query()->find(PengaturanAkun::query()->where('kunci_pengaturan', 'AKUN_SALDO_AWAL')->value('akun_id'))->kode_akun);

        $this->assertEqualsWithDelta(
            0.00,
            (float) Akun::query()->where('kode_akun', '1130')->value('saldo_awal'),
            0.01,
            'Persediaan awal harus dibuat melalui alur import stok awal agar tidak dihitung dua kali.',
        );
    }

    public function test_coa_seeder_refuses_to_run_when_existing_opening_balances_are_unbalanced(): void
    {
        $this->seed(CoaSeeder::class);

        $cash = Akun::query()->where('kode_akun', '1101')->firstOrFail();
        $cash->saldo_awal = 100.00;
        $cash->save();

        try {
            $this->seed(CoaSeeder::class);
            $this->fail('Seeder seharusnya menolak saldo awal yang tidak seimbang.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('tidak seimbang', $exception->getMessage());
        }

        $this->assertEqualsWithDelta(
            100.00,
            (float) $cash->fresh()->saldo_awal,
            0.01,
            'Seeder tidak boleh mengubah saldo awal yang sudah ada saat preflight gagal.',
        );
    }
}
