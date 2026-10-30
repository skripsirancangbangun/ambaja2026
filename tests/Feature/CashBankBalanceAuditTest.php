<?php

namespace Tests\Feature;

use App\Models\Akun;
use App\Models\Jurnal;
use App\Models\JurnalDetail;
use App\Models\MutasiKasBank;
use App\Models\RekeningKasBank;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashBankBalanceAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_cash_bank_screen_compares_subledger_to_posted_general_ledger_and_ignores_draft_journals(): void
    {
        $owner = User::factory()->create(['role' => 'Owner']);

        $cash = $this->createAccount('1101', 'Kas Audit', 'Aset', 'Aset Lancar', 'DEBIT', 1000);
        $revenue = $this->createAccount('4101', 'Pendapatan Audit', 'Pendapatan', 'Pendapatan', 'KREDIT');
        $expense = $this->createAccount('6101', 'Beban Audit', 'Beban', 'Beban Operasional', 'DEBIT');

        $rekening = RekeningKasBank::create([
            'akun_id' => $cash->id,
            'kode_rekening' => 'KAS-BAL-AUDIT',
            'nama_rekening' => 'Kas Audit',
            'jenis' => 'KAS',
            'saldo_awal' => 1000,
            'is_active' => true,
        ]);

        $this->createJournal('JU-AUDIT-IN', $owner, [
            [$cash, 250, 0],
            [$revenue, 0, 250],
        ], true);
        $this->createJournal('JU-AUDIT-OUT', $owner, [
            [$expense, 50, 0],
            [$cash, 0, 50],
        ], true);

        // Draft tidak boleh menambah saldo buku besar sampai diposting.
        $this->createJournal('JU-AUDIT-DRAFT', $owner, [
            [$cash, 900, 0],
            [$revenue, 0, 900],
        ], false);

        // Subledger memiliki debit tambahan Rp50 yang tidak ada di buku besar.
        MutasiKasBank::create([
            'tanggal' => '2026-10-01 09:00:00',
            'rekening_kas_bank_id' => $rekening->id,
            'tipe' => 'UANG_MASUK',
            'debit' => 300,
            'kredit' => 0,
            'saldo' => 1300,
            'reference_type' => 'TEST',
            'reference_id' => 1,
            'keterangan' => 'Mutasi dengan selisih audit',
        ]);
        MutasiKasBank::create([
            'tanggal' => '2026-10-02 09:00:00',
            'rekening_kas_bank_id' => $rekening->id,
            'tipe' => 'UANG_KELUAR',
            'debit' => 0,
            'kredit' => 50,
            'saldo' => 1250,
            'reference_type' => 'TEST',
            'reference_id' => 2,
            'keterangan' => 'Mutasi keluar',
        ]);

        $response = $this->actingAs($owner)->get(route('mutasi-kas-bank.index'));
        $response->assertOk();

        $rekeningDiView = $response->viewData('saldoRekenings')->firstWhere('id', $rekening->id);
        $this->assertNotNull($rekeningDiView);
        $this->assertEquals(1250.00, (float) $rekeningDiView->saldo_saat_ini);
        $this->assertEquals(1200.00, (float) $rekeningDiView->saldo_coa_saat_ini);
        $this->assertEquals(50.00, (float) $rekeningDiView->selisih_saldo_coa);
        $this->assertFalse($rekeningDiView->saldo_sinkron_dengan_coa);

        // Atribut saldo COA juga harus mengabaikan jurnal draft.
        $this->assertEquals(1200.00, $cash->fresh()->saldo_saat_ini);
    }

    private function createAccount(
        string $code,
        string $name,
        string $category,
        string $subCategory,
        string $normalBalance,
        float $openingBalance = 0
    ): Akun {
        return Akun::create([
            'kode_akun' => $code,
            'nama_akun' => $name,
            'kategori_akun' => $category,
            'sub_kategori' => $subCategory,
            'saldo_normal' => $normalBalance,
            'saldo_awal' => $openingBalance,
            'is_active' => true,
        ]);
    }

    private function createJournal(string $number, User $user, array $lines, bool $posted): Jurnal
    {
        $totalDebit = array_sum(array_column($lines, 1));
        $totalCredit = array_sum(array_column($lines, 2));

        $journal = Jurnal::create([
            'nomor_jurnal' => $number,
            'tanggal' => '2026-10-10',
            'keterangan' => $number,
            'is_system_generated' => false,
            'referensi_type' => null,
            'referensi_id' => null,
            'total_debit' => $totalDebit,
            'total_kredit' => $totalCredit,
            'is_posted' => $posted,
            'created_by' => $user->id,
        ]);

        foreach ($lines as [$account, $debit, $credit]) {
            JurnalDetail::create([
                'jurnal_id' => $journal->id,
                'akun_id' => $account->id,
                'debit' => $debit,
                'kredit' => $credit,
            ]);
        }

        return $journal;
    }
}
