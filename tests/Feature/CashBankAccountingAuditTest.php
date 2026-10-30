<?php

namespace Tests\Feature;

use App\Http\Controllers\ImportRekeningKoranController;
use App\Http\Controllers\TransferKasBankController;
use App\Http\Controllers\UangKeluarController;
use App\Http\Controllers\UangMasukController;
use App\Models\Akun;
use App\Models\Jurnal;
use App\Models\JurnalDetail;
use App\Models\RekeningKasBank;
use App\Models\User;
use App\Models\TransferKasBank;
use App\Models\UangKeluar;
use App\Models\UangMasuk;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class CashBankAccountingAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_journal_cannot_post_directly_to_cash_bank_account(): void
    {
        $owner = User::factory()->create(['role' => 'Owner']);
        $cashAccount = $this->createAccount('1101', 'Kas Toko', 'Aset', 'Aset Lancar', 'DEBIT');
        $capitalAccount = $this->createAccount('3101', 'Modal Owner', 'Ekuitas', 'Ekuitas', 'KREDIT');
        $this->createCashBankMapping($cashAccount);

        $response = $this->actingAs($owner)
            ->from(route('jurnal-umum.create'))
            ->post(route('jurnal-umum.store'), [
                'nomor_jurnal' => 'JU-TEST-001',
                'tanggal' => '2026-10-01',
                'keterangan' => 'Setoran modal manual',
                'items' => [
                    ['akun_id' => $cashAccount->id, 'debit' => 100000, 'kredit' => 0],
                    ['akun_id' => $capitalAccount->id, 'debit' => 0, 'kredit' => 100000],
                ],
            ]);

        $response->assertRedirect(route('jurnal-umum.create'))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('jurnals', ['nomor_jurnal' => 'JU-TEST-001']);
    }

    public function test_posted_manual_journal_cannot_be_deleted(): void
    {
        $owner = User::factory()->create(['role' => 'Owner']);
        $expenseAccount = $this->createAccount('6102', 'Beban Listrik', 'Beban', 'Beban Operasional', 'DEBIT');
        $payableAccount = $this->createAccount('2102', 'Utang Gaji', 'Kewajiban', 'Kewajiban Lancar', 'KREDIT');

        $journal = Jurnal::create([
            'nomor_jurnal' => 'JU-POSTED-001',
            'tanggal' => '2026-10-01',
            'keterangan' => 'Jurnal yang sudah diposting',
            'is_system_generated' => false,
            'referensi_type' => null,
            'referensi_id' => null,
            'total_debit' => 100000,
            'total_kredit' => 100000,
            'is_posted' => true,
            'created_by' => $owner->id,
        ]);

        JurnalDetail::create([
            'jurnal_id' => $journal->id,
            'akun_id' => $expenseAccount->id,
            'debit' => 100000,
            'kredit' => 0,
        ]);
        JurnalDetail::create([
            'jurnal_id' => $journal->id,
            'akun_id' => $payableAccount->id,
            'debit' => 0,
            'kredit' => 100000,
        ]);

        $this->actingAs($owner)
            ->delete(route('jurnal-umum.destroy', $journal))
            ->assertRedirect(route('jurnal-umum.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('jurnals', ['id' => $journal->id]);
        $this->assertDatabaseCount('jurnal_details', 2);
    }

    public function test_system_generated_journal_cannot_be_deleted_from_general_journal_menu(): void
    {
        $owner = User::factory()->create(['role' => 'Owner']);
        $expenseAccount = $this->createAccount('6103', 'Beban Transportasi', 'Beban', 'Beban Operasional', 'DEBIT');
        $payableAccount = $this->createAccount('2103', 'Utang Pajak PPN', 'Kewajiban', 'Kewajiban Lancar', 'KREDIT');

        $journal = Jurnal::create([
            'nomor_jurnal' => 'JU-SYSTEM-001',
            'tanggal' => '2026-10-01',
            'keterangan' => 'Jurnal otomatis',
            'is_system_generated' => true,
            'referensi_type' => 'UANG_KELUAR',
            'referensi_id' => 1,
            'total_debit' => 100000,
            'total_kredit' => 100000,
            'is_posted' => true,
            'created_by' => $owner->id,
        ]);

        JurnalDetail::create([
            'jurnal_id' => $journal->id,
            'akun_id' => $expenseAccount->id,
            'debit' => 100000,
            'kredit' => 0,
        ]);
        JurnalDetail::create([
            'jurnal_id' => $journal->id,
            'akun_id' => $payableAccount->id,
            'debit' => 0,
            'kredit' => 100000,
        ]);

        $this->actingAs($owner)
            ->delete(route('jurnal-umum.destroy', $journal))
            ->assertRedirect(route('jurnal-umum.index'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('jurnals', ['id' => $journal->id]);
    }

    public function test_cash_flow_report_reconciles_posted_journals_without_double_counting_transfers(): void
    {
        $owner = User::factory()->create(['role' => 'Owner']);

        $cash = $this->createAccount('1101', 'Kas Toko', 'Aset', 'Aset Lancar', 'DEBIT', 1000);
        $bank = $this->createAccount('1102', 'Bank BCA', 'Aset', 'Aset Lancar', 'DEBIT');
        $revenue = $this->createAccount('4101', 'Pendapatan Penjualan Barang', 'Pendapatan', 'Penjualan', 'KREDIT');
        $expense = $this->createAccount('6102', 'Beban Listrik', 'Beban', 'Beban Operasional', 'DEBIT');
        $capital = $this->createAccount('3101', 'Modal Owner', 'Ekuitas', 'Ekuitas', 'KREDIT');
        $fixedAsset = $this->createAccount('1201', 'Peralatan & Mesin', 'Aset', 'Aset Tetap', 'DEBIT');
        $loan = $this->createAccount('2201', 'Utang Bank Jangka Panjang', 'Kewajiban', 'Kewajiban Jangka Panjang', 'KREDIT');
        $supplierPayable = $this->createAccount('2101', 'Utang Dagang (Supplier)', 'Kewajiban', 'Kewajiban Lancar', 'KREDIT');

        $this->createCashBankMapping($cash, 'KAS-TEST');
        $this->createCashBankMapping($bank, 'BANK-TEST', 'BANK');

        $this->createJournal('JU-SALE', $owner, [
            [$cash, 500, 0], [$revenue, 0, 500],
        ]);
        $this->createJournal('JU-EXPENSE', $owner, [
            [$expense, 100, 0], [$cash, 0, 100],
        ]);
        $this->createJournal('JU-CAPITAL', $owner, [
            [$cash, 200, 0], [$capital, 0, 200],
        ]);
        $this->createJournal('JU-ASSET', $owner, [
            [$fixedAsset, 300, 0], [$cash, 0, 300],
        ]);
        $this->createJournal('JU-LOAN', $owner, [
            [$cash, 400, 0], [$loan, 0, 400],
        ]);
        $this->createJournal('JU-SUPPLIER', $owner, [
            [$supplierPayable, 150, 0], [$cash, 0, 150],
        ]);
        // Transfer antar rekening: debit Bank dan kredit Kas tidak mengubah total kas.
        $this->createJournal('JU-TRANSFER', $owner, [
            [$bank, 100, 0], [$cash, 0, 100],
        ]);

        // Draft journal tidak boleh memengaruhi laporan sampai diposting.
        $draft = Jurnal::create([
            'nomor_jurnal' => 'JU-DRAFT',
            'tanggal' => '2026-10-11',
            'keterangan' => 'Draft belum diposting',
            'is_system_generated' => false,
            'referensi_type' => null,
            'referensi_id' => null,
            'total_debit' => 900,
            'total_kredit' => 900,
            'is_posted' => false,
            'created_by' => $owner->id,
        ]);
        JurnalDetail::create([
            'jurnal_id' => $draft->id,
            'akun_id' => $cash->id,
            'debit' => 900,
            'kredit' => 0,
        ]);
        JurnalDetail::create([
            'jurnal_id' => $draft->id,
            'akun_id' => $revenue->id,
            'debit' => 0,
            'kredit' => 900,
        ]);

        $response = $this->actingAs($owner)->get(route('laporan.arus-kas', [
            'tanggal_mulai' => '2026-10-01',
            'tanggal_selesai' => '2026-10-31',
        ]));

        $response->assertOk()
            ->assertViewHas('penerimaanPelanggan', 500.0)
            ->assertViewHas('pembayaranBeban', 100.0)
            ->assertViewHas('pembayaranSupplier', 150.0)
            ->assertViewHas('setoranModal', 200.0)
            ->assertViewHas('penerimaanPinjaman', 400.0)
            ->assertViewHas('pembelianAsetTetap', 300.0)
            ->assertViewHas('kenaikanBersihKas', 550.0)
            ->assertViewHas('saldoKasAwal', 1000.0)
            ->assertViewHas('saldoKasAkhir', 1550.0);
    }

    public function test_bank_statement_amount_parser_handles_indonesian_and_international_formats(): void
    {
        $controller = new ImportRekeningKoranController();
        $method = new \ReflectionMethod(ImportRekeningKoranController::class, 'parseNominal');
        $method->setAccessible(true);

        $cases = [
            ['5.000', 5000.0],
            ['22.735.000,00', 22735000.0],
            ['22,735,000.00', 22735000.0],
            ['5000,50', 5000.5],
            ['5,000,000', 5000000.0],
            ['Rp 1.234,56', 1234.56],
            [5000.5, 5000.5],
            ['invalid', 0.0],
        ];

        foreach ($cases as [$input, $expected]) {
            $this->assertSame(
                $expected,
                $method->invoke($controller, $input),
                'Unexpected parsed amount for input: ' . (string) $input
            );
        }
    }

    public function test_draft_updates_and_deletes_recheck_persisted_status_under_lock(): void
    {
        $owner = User::factory()->create(['role' => 'Owner']);
        $cashAccount = $this->createAccount('1105', 'Kas Race Test', 'Aset', 'Aset Lancar', 'DEBIT');
        $bankAccount = $this->createAccount('1106', 'Bank Race Test', 'Aset', 'Aset Lancar', 'DEBIT');
        $counterAccount = $this->createAccount('4105', 'Pendapatan Lain Test', 'Pendapatan', 'Pendapatan Lain', 'KREDIT');
        $expenseAccount = $this->createAccount('6105', 'Beban Test', 'Beban', 'Beban Operasional', 'DEBIT');
        $this->createCashBankMapping($cashAccount, 'KAS-RACE');
        $this->createCashBankMapping($bankAccount, 'BANK-RACE', 'BANK');
        $cashBank = RekeningKasBank::where('akun_id', $cashAccount->id)->firstOrFail();
        $bank = RekeningKasBank::where('akun_id', $bankAccount->id)->firstOrFail();

        $uangMasuk = UangMasuk::create([
            'nomor_transaksi' => 'UM-RACE-001',
            'tanggal' => '2026-10-01',
            'rekening_kas_bank_id' => $cashBank->id,
            'jumlah' => 100,
            'sumber' => 'Tes race condition',
            'status' => 'DRAFT',
            'created_by' => $owner->id,
        ]);
        $uangMasuk->akun_lawan_id = $counterAccount->id;
        $uangMasuk->save();
        $staleUangMasuk = clone $uangMasuk;
        UangMasuk::whereKey($uangMasuk->id)->update(['status' => 'TERCATAT']);

        $requestMasuk = Request::create('/uang-masuk/' . $uangMasuk->id, 'PUT', [
            'tanggal' => '2026-10-02',
            'rekening_kas_bank_id' => $cashBank->id,
            'akun_lawan_id' => $counterAccount->id,
            'jumlah' => 999,
            'sumber' => 'Perubahan tidak boleh tersimpan',
        ]);
        $responseMasuk = (new UangMasukController())->update($requestMasuk, $staleUangMasuk);
        $this->assertSame(302, $responseMasuk->getStatusCode());
        $this->assertSame(302, (new UangMasukController())->destroy($staleUangMasuk)->getStatusCode());
        $this->assertDatabaseHas('uang_masuks', [
            'id' => $uangMasuk->id,
            'status' => 'TERCATAT',
            'jumlah' => 100,
        ]);

        $uangKeluar = UangKeluar::create([
            'nomor_transaksi' => 'UK-RACE-001',
            'tanggal' => '2026-10-01',
            'rekening_kas_bank_id' => $cashBank->id,
            'jumlah' => 100,
            'tujuan' => 'Tes race condition',
            'status' => 'DRAFT',
            'created_by' => $owner->id,
        ]);
        $staleUangKeluar = clone $uangKeluar;
        UangKeluar::whereKey($uangKeluar->id)->update(['status' => 'TERCATAT']);

        $requestKeluar = Request::create('/uang-keluar/' . $uangKeluar->id, 'PUT', [
            'tanggal' => '2026-10-02',
            'rekening_kas_bank_id' => $cashBank->id,
            'details' => [[
                'akun_id' => $expenseAccount->id,
                'tujuan' => 'Perubahan tidak boleh tersimpan',
                'jumlah' => 999,
            ]],
        ]);
        $responseKeluar = (new UangKeluarController())->update($requestKeluar, $staleUangKeluar);
        $this->assertSame(302, $responseKeluar->getStatusCode());
        $this->assertSame(302, (new UangKeluarController())->destroy($staleUangKeluar)->getStatusCode());
        $this->assertDatabaseHas('uang_keluars', [
            'id' => $uangKeluar->id,
            'status' => 'TERCATAT',
            'jumlah' => 100,
        ]);

        $transfer = TransferKasBank::create([
            'nomor_transaksi' => 'TRF-RACE-001',
            'tanggal' => '2026-10-01',
            'rekening_asal_id' => $cashBank->id,
            'rekening_tujuan_id' => $bank->id,
            'jumlah' => 100,
            'status' => 'DRAFT',
            'created_by' => $owner->id,
        ]);
        $staleTransfer = clone $transfer;
        TransferKasBank::whereKey($transfer->id)->update(['status' => 'TERCATAT']);

        $requestTransfer = Request::create('/transfer-kas-bank/' . $transfer->id, 'PUT', [
            'tanggal' => '2026-10-02',
            'rekening_asal_id' => $cashBank->id,
            'rekening_tujuan_id' => $bank->id,
            'jumlah' => 999,
        ]);
        $responseTransfer = (new TransferKasBankController())->update($requestTransfer, $staleTransfer);
        $this->assertSame(302, $responseTransfer->getStatusCode());
        $this->assertSame(302, (new TransferKasBankController())->destroy($staleTransfer)->getStatusCode());
        $this->assertDatabaseHas('transfer_kas_banks', [
            'id' => $transfer->id,
            'status' => 'TERCATAT',
            'jumlah' => 100,
        ]);
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

    private function createCashBankMapping(Akun $account, ?string $code = null, string $type = 'KAS'): void
    {
        static $sequence = 1;

        RekeningKasBank::create([
            'akun_id' => $account->id,
            'kode_rekening' => $code ?? 'KAS-TEST-' . $sequence++,
            'nama_rekening' => $account->nama_akun,
            'jenis' => $type,
            'saldo_awal' => 0,
            'is_active' => true,
        ]);
    }

    private function createJournal(string $number, User $user, array $lines): Jurnal
    {
        $totalDebit = array_sum(array_column($lines, 1));
        $totalCredit = array_sum(array_column($lines, 2));

        $journal = Jurnal::create([
            'nomor_jurnal' => $number,
            'tanggal' => '2026-10-10',
            'keterangan' => $number,
            'is_system_generated' => true,
            'referensi_type' => null,
            'referensi_id' => null,
            'total_debit' => $totalDebit,
            'total_kredit' => $totalCredit,
            'is_posted' => true,
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
