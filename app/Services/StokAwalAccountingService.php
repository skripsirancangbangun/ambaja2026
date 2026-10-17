<?php

namespace App\Services;

use App\Models\Akun;
use App\Models\Jurnal;
use App\Models\JurnalDetail;
use App\Models\StokAwalImport;
use RuntimeException;

class StokAwalAccountingService
{
    public function __construct(private SystemAccountingService $accounting) {}

    public function createOpeningInventoryJournal(StokAwalImport $batch, float $totalNilai): Jurnal
    {
        $totalNilai = round($totalNilai, 2);
        if ($totalNilai <= 0) {
            throw new RuntimeException('Nilai stok awal harus lebih dari nol.');
        }

        $persediaanId = $this->accounting->getSystemAccountId('AKUN_PERSEDIAAN');
        $saldoAwalId = $this->accounting->getSystemAccountId('AKUN_SALDO_AWAL');

        if (!$persediaanId) {
            throw new RuntimeException('AKUN_PERSEDIAAN belum dikonfigurasi pada Pengaturan Akun.');
        }
        if (!$saldoAwalId) {
            throw new RuntimeException('AKUN_SALDO_AWAL belum dikonfigurasi. Pilih akun ekuitas pembukaan pada Pengaturan Akun. Import dibatalkan tanpa menyimpan perubahan.');
        }
        if ((int) $persediaanId === (int) $saldoAwalId) {
            throw new RuntimeException('Akun persediaan dan akun lawan saldo awal tidak boleh sama.');
        }

        $persediaan = Akun::find($persediaanId);
        $saldoAwal = Akun::find($saldoAwalId);
        if (!$persediaan || !$persediaan->is_active) {
            throw new RuntimeException('Akun persediaan tidak ditemukan atau tidak aktif.');
        }
        if (!$saldoAwal || !$saldoAwal->is_active) {
            throw new RuntimeException('Akun lawan saldo awal tidak ditemukan atau tidak aktif.');
        }

        if (Jurnal::where('referensi_type', 'STOK_AWAL_IMPORT')->where('referensi_id', $batch->id)->exists()) {
            throw new RuntimeException('Jurnal batch ini sudah ada; import dibatalkan untuk mencegah jurnal ganda.');
        }

        $jurnal = Jurnal::create([
            'nomor_jurnal' => $this->accounting->generateDocumentNumber('JURNAL_UMUM', $batch->tanggal),
            'tanggal' => $batch->tanggal,
            'keterangan' => 'Saldo awal persediaan - '.$batch->nomor_import,
            'is_system_generated' => true,
            'referensi_type' => 'STOK_AWAL_IMPORT',
            'referensi_id' => $batch->id,
            'total_debit' => $totalNilai,
            'total_kredit' => $totalNilai,
            'is_posted' => true,
            'created_by' => auth()->id(),
        ]);

        JurnalDetail::create([
            'jurnal_id' => $jurnal->id, 'akun_id' => $persediaanId,
            'debit' => $totalNilai, 'kredit' => 0,
            'keterangan' => 'Pengakuan saldo awal persediaan '.$batch->nomor_import,
        ]);
        JurnalDetail::create([
            'jurnal_id' => $jurnal->id, 'akun_id' => $saldoAwalId,
            'debit' => 0, 'kredit' => $totalNilai,
            'keterangan' => 'Akun lawan saldo pembukaan '.$batch->nomor_import,
        ]);

        return $jurnal;
    }
}
