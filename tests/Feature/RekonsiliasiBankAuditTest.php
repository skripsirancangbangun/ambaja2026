<?php

namespace Tests\Feature;

use App\Models\MutasiKasBank;
use App\Models\RekeningKasBank;
use App\Models\RekonsiliasiBank;
use App\Models\RekonsiliasiBankAudit;
use App\Models\TransaksiBelumTeridentifikasi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RekonsiliasiBankAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_cancelling_reconciliation_keeps_audit_snapshot_and_reason(): void
    {
        $user = User::factory()->create(['role' => 'Owner']);
        $this->actingAs($user);

        $rekening = RekeningKasBank::create([
            'kode_rekening' => 'BANK-AUDIT-01',
            'nama_rekening' => 'Rekening Audit',
            'jenis' => 'BANK',
            'nama_bank' => 'Bank Uji',
            'nomor_rekening' => '1234567890',
            'saldo_awal' => 0,
            'is_active' => true,
        ]);

        $bank = TransaksiBelumTeridentifikasi::create([
            'tanggal' => '2026-10-20',
            'rekening_kas_bank_id' => $rekening->id,
            'jenis' => 'MASUK',
            'jumlah' => 125000,
            'nama_pengirim_penerima' => 'Pelanggan Uji',
            'nomor_referensi_bank' => 'REF-AUDIT-01',
            'deskripsi_bank' => 'Setoran pelanggan',
            'status' => 'TERIDENTIFIKASI',
            'matched_reference_type' => 'UANG_MASUK',
            'matched_reference_id' => 77,
            'identified_at' => now(),
            'identified_by' => $user->id,
            'created_by' => $user->id,
        ]);

        $mutasi = MutasiKasBank::create([
            'tanggal' => '2026-10-20 10:00:00',
            'rekening_kas_bank_id' => $rekening->id,
            'tipe' => 'UANG_MASUK',
            'debit' => 125000,
            'kredit' => 0,
            'saldo' => 125000,
            'reference_type' => 'UANG_MASUK',
            'reference_id' => 77,
            'nomor_referensi' => 'REF-AUDIT-01',
            'keterangan' => 'Setoran pelanggan',
        ]);

        $rekonsiliasi = RekonsiliasiBank::create([
            'transaksi_bank_id' => $bank->id,
            'mutasi_kas_bank_id' => $mutasi->id,
            'tanggal_rekonsiliasi' => now(),
            'catatan' => 'Pencocokan awal',
            'created_by' => $user->id,
        ]);

        // Hit the real HTTP route so Laravel returns a TestResponse and
        // route middleware, model binding, session, and redirects are tested.
        $response = $this->patch(
            route('rekonsiliasi-bank.batalkan', $bank),
            [
                'alasan' => 'Salah memilih mutasi rekening koran',
            ]
        );

        $response->assertRedirect(route('rekonsiliasi-bank.show', $bank));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('rekonsiliasi_banks', [
            'id' => $rekonsiliasi->id,
        ]);

        $this->assertDatabaseHas('transaksi_belum_teridentifikasis', [
            'id' => $bank->id,
            'status' => 'BELUM_TERIDENTIFIKASI',
            'matched_reference_type' => null,
            'matched_reference_id' => null,
        ]);

        $audit = RekonsiliasiBankAudit::query()->firstOrFail();
        $this->assertSame('DIBATALKAN', $audit->aksi);
        $this->assertSame('Salah memilih mutasi rekening koran', $audit->alasan);
        $this->assertSame($user->id, $audit->dibatalkan_oleh);
        $this->assertSame($rekonsiliasi->id, $audit->rekonsiliasi_id);
        $this->assertSame('TERIDENTIFIKASI', $audit->data_sebelum['transaksi_bank']['status']);
        $this->assertSame($mutasi->id, $audit->data_sebelum['rekonsiliasi']['mutasi_kas_bank_id']);
    }
}
