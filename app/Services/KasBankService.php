<?php

namespace App\Services;

use App\Models\MutasiKasBank;
use App\Models\RekeningKasBank;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class KasBankService
{
    public function uangMasuk(
        int $rekeningKasBankId,
        float $jumlah,
        string $referenceType,
        int $referenceId,
        ?string $nomorReferensi = null,
        ?string $keterangan = null,
        ?string $tanggalTransaksi = null,
        string $tipe = 'UANG_MASUK'
    ): MutasiKasBank {
        if ($jumlah <= 0) {
            throw new RuntimeException(
                'Jumlah uang masuk harus lebih besar dari 0.'
            );
        }

        return DB::transaction(function () use (
            $rekeningKasBankId,
            $jumlah,
            $referenceType,
            $referenceId,
            $nomorReferensi,
            $keterangan,
            $tanggalTransaksi,
            $tipe
        ) {
            $rekening = RekeningKasBank::query()
                ->lockForUpdate()
                ->findOrFail($rekeningKasBankId);

            if (!$rekening->is_active) {
                throw new RuntimeException(
                    'Kas/Rekening Bank yang dipilih sudah tidak aktif.'
                );
            }

            $tanggal = $this->resolveTanggal(
                $tanggalTransaksi
            );

            $this->validateUrutanTanggal(
                $rekening->id,
                $tanggal
            );

            $this->validateDuplikat(
                $referenceType,
                $referenceId,
                $tipe
            );

            $saldoSebelumnya = $this->getSaldoTerakhir(
                $rekening
            );

            $saldoBaru = $saldoSebelumnya + $jumlah;

            return MutasiKasBank::create([
                'tanggal' => $tanggal,
                'rekening_kas_bank_id' => $rekening->id,
                'tipe' => $tipe,
                'debit' => $jumlah,
                'kredit' => 0,
                'saldo' => $saldoBaru,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'nomor_referensi' => $nomorReferensi,
                'keterangan' => $keterangan,
            ]);
        });
    }

    public function uangKeluar(
        int $rekeningKasBankId,
        float $jumlah,
        string $referenceType,
        int $referenceId,
        ?string $nomorReferensi = null,
        ?string $keterangan = null,
        ?string $tanggalTransaksi = null,
        string $tipe = 'UANG_KELUAR'
    ): MutasiKasBank {
        if ($jumlah <= 0) {
            throw new RuntimeException(
                'Jumlah uang keluar harus lebih besar dari 0.'
            );
        }

        return DB::transaction(function () use (
            $rekeningKasBankId,
            $jumlah,
            $referenceType,
            $referenceId,
            $nomorReferensi,
            $keterangan,
            $tanggalTransaksi,
            $tipe
        ) {
            $rekening = RekeningKasBank::query()
                ->lockForUpdate()
                ->findOrFail($rekeningKasBankId);

            if (!$rekening->is_active) {
                throw new RuntimeException(
                    'Kas/Rekening Bank yang dipilih sudah tidak aktif.'
                );
            }

            $tanggal = $this->resolveTanggal(
                $tanggalTransaksi
            );

            $this->validateUrutanTanggal(
                $rekening->id,
                $tanggal
            );

            $this->validateDuplikat(
                $referenceType,
                $referenceId,
                $tipe
            );

            $saldoSebelumnya = $this->getSaldoTerakhir(
                $rekening
            );

            if ($jumlah > $saldoSebelumnya) {
                throw new RuntimeException(
                    'Saldo Kas/Rekening Bank tidak mencukupi.'
                );
            }

            $saldoBaru = $saldoSebelumnya - $jumlah;

            return MutasiKasBank::create([
                'tanggal' => $tanggal,
                'rekening_kas_bank_id' => $rekening->id,
                'tipe' => $tipe,
                'debit' => 0,
                'kredit' => $jumlah,
                'saldo' => $saldoBaru,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'nomor_referensi' => $nomorReferensi,
                'keterangan' => $keterangan,
            ]);
        });
    }

    public function transfer(
        int $rekeningAsalId,
        int $rekeningTujuanId,
        float $jumlah,
        string $referenceType,
        int $referenceId,
        ?string $nomorReferensi = null,
        ?string $keterangan = null,
        ?string $tanggalTransaksi = null
    ): array {
        if ($rekeningAsalId === $rekeningTujuanId) {
            throw new RuntimeException(
                'Rekening asal dan rekening tujuan tidak boleh sama.'
            );
        }

        if ($jumlah <= 0) {
            throw new RuntimeException(
                'Jumlah transfer harus lebih besar dari 0.'
            );
        }

        return DB::transaction(function () use (
            $rekeningAsalId,
            $rekeningTujuanId,
            $jumlah,
            $referenceType,
            $referenceId,
            $nomorReferensi,
            $keterangan,
            $tanggalTransaksi
        ) {
            /*
             * Lock berdasarkan ID supaya urutan lock konsisten
             * dan mengurangi risiko deadlock.
             */
            $ids = [
                $rekeningAsalId,
                $rekeningTujuanId,
            ];

            sort($ids);

            $rekeningLocked = RekeningKasBank::query()
                ->whereIn('id', $ids)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $rekeningAsal = $rekeningLocked->get(
                $rekeningAsalId
            );

            $rekeningTujuan = $rekeningLocked->get(
                $rekeningTujuanId
            );

            if (!$rekeningAsal || !$rekeningTujuan) {
                throw new RuntimeException(
                    'Kas/Rekening Bank tidak ditemukan.'
                );
            }

            if (
                !$rekeningAsal->is_active
                || !$rekeningTujuan->is_active
            ) {
                throw new RuntimeException(
                    'Rekening asal dan tujuan harus aktif.'
                );
            }

            $tanggal = $this->resolveTanggal(
                $tanggalTransaksi
            );

            $this->validateUrutanTanggal(
                $rekeningAsal->id,
                $tanggal
            );

            $this->validateUrutanTanggal(
                $rekeningTujuan->id,
                $tanggal
            );

            $this->validateDuplikat(
                $referenceType,
                $referenceId,
                'TRANSFER_KELUAR'
            );

            $this->validateDuplikat(
                $referenceType,
                $referenceId,
                'TRANSFER_MASUK'
            );

            $saldoAsal = $this->getSaldoTerakhir(
                $rekeningAsal
            );

            if ($jumlah > $saldoAsal) {
                throw new RuntimeException(
                    'Saldo rekening asal tidak mencukupi untuk transfer.'
                );
            }

            $saldoTujuan = $this->getSaldoTerakhir(
                $rekeningTujuan
            );

            $saldoAsalBaru = $saldoAsal - $jumlah;
            $saldoTujuanBaru = $saldoTujuan + $jumlah;

            $mutasiKeluar = MutasiKasBank::create([
                'tanggal' => $tanggal,
                'rekening_kas_bank_id' => $rekeningAsal->id,
                'tipe' => 'TRANSFER_KELUAR',
                'debit' => 0,
                'kredit' => $jumlah,
                'saldo' => $saldoAsalBaru,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'nomor_referensi' => $nomorReferensi,
                'keterangan' => $keterangan,
            ]);

            $mutasiMasuk = MutasiKasBank::create([
                'tanggal' => $tanggal,
                'rekening_kas_bank_id' => $rekeningTujuan->id,
                'tipe' => 'TRANSFER_MASUK',
                'debit' => $jumlah,
                'kredit' => 0,
                'saldo' => $saldoTujuanBaru,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'nomor_referensi' => $nomorReferensi,
                'keterangan' => $keterangan,
            ]);

            return [
                'keluar' => $mutasiKeluar,
                'masuk' => $mutasiMasuk,
            ];
        });
    }

    public function getSaldo(
        int $rekeningKasBankId
    ): float {
        $rekening = RekeningKasBank::findOrFail(
            $rekeningKasBankId
        );

        return $this->getSaldoTerakhir(
            $rekening
        );
    }

    private function validateDuplikat(
        string $referenceType,
        int $referenceId,
        string $tipe
    ): void {
        $exists = MutasiKasBank::query()
            ->where(
                'reference_type',
                $referenceType
            )
            ->where(
                'reference_id',
                $referenceId
            )
            ->where(
                'tipe',
                $tipe
            )
            ->lockForUpdate()
            ->exists();

        if ($exists) {
            throw new RuntimeException(
                'Transaksi ini sudah tercatat pada Mutasi Kas/Bank.'
            );
        }
    }

    private function getSaldoTerakhir(
        RekeningKasBank $rekening
    ): float {
        $mutasiTerakhir = MutasiKasBank::query()
            ->where(
                'rekening_kas_bank_id',
                $rekening->id
            )
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->first();

        if ($mutasiTerakhir) {
            return (float) $mutasiTerakhir->saldo;
        }

        return (float) $rekening->saldo_awal;
    }

    private function resolveTanggal(
        ?string $tanggalTransaksi
    ): Carbon {
        if (!$tanggalTransaksi) {
            return now();
        }

        $tanggal = Carbon::parse(
            $tanggalTransaksi
        );

        if (
            preg_match(
                '/^\d{4}-\d{2}-\d{2}$/',
                $tanggalTransaksi
            )
        ) {
            $sekarang = now();

            $tanggal->setTime(
                $sekarang->hour,
                $sekarang->minute,
                $sekarang->second
            );
        }

        return $tanggal;
    }

    private function validateUrutanTanggal(
        int $rekeningKasBankId,
        Carbon $tanggal
    ): void {
        $mutasiTerakhir = MutasiKasBank::query()
            ->where(
                'rekening_kas_bank_id',
                $rekeningKasBankId
            )
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();

        if (!$mutasiTerakhir) {
            return;
        }

        $tanggalTerakhir = Carbon::parse(
            $mutasiTerakhir->tanggal
        );

        if (
            $tanggal->copy()->startOfDay()->lt(
                $tanggalTerakhir
                    ->copy()
                    ->startOfDay()
            )
        ) {
            throw new RuntimeException(
                'Tanggal transaksi tidak boleh sebelum tanggal mutasi Kas/Bank terakhir. '
                . 'Tanggal mutasi terakhir: '
                . $tanggalTerakhir->format('d/m/Y')
                . '.'
            );
        }
    }
}