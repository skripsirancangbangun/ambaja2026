<?php

namespace App\Services;

use App\Models\MutasiStok;
use App\Models\StokGudang;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class InventoryService
{
    /**
     * Stok masuk.
     */
    public function stokMasuk(
        int $barangId,
        int $gudangId,
        float $qty,
        float $hargaSatuan,
        string $referenceType,
        int $referenceId,
        ?string $keterangan = null,
        string $tipeMutasi = 'MASUK',
        $tanggalTransaksi = null
    ): StokGudang {

        if ($qty <= 0) {
            throw new RuntimeException(
                'Qty stok masuk harus lebih dari 0.'
            );
        }

        if ($hargaSatuan < 0) {
            throw new RuntimeException(
                'Harga satuan tidak boleh negatif.'
            );
        }

        $allowedTypes = [
            'MASUK',
            'MUTASI_MASUK',
            'RETUR_MASUK',
            'RETUR_PENJUALAN',
            'PEMBATALAN_INVOICE',
            'PENYESUAIAN',
        ];

        if (!in_array(
            $tipeMutasi,
            $allowedTypes,
            true
        )) {
            throw new RuntimeException(
                'Tipe mutasi stok masuk tidak valid.'
            );
        }

        $tanggalMutasi = $this->resolveTanggalTransaksi($tanggalTransaksi);

        return DB::transaction(
            function () use (
                $barangId,
                $gudangId,
                $qty,
                $hargaSatuan,
                $referenceType,
                $referenceId,
                $keterangan,
                $tipeMutasi,
                $tanggalMutasi
            ) {
                $stok = StokGudang::where('barang_id', $barangId)
                    ->where('gudang_id', $gudangId)
                    ->lockForUpdate()
                    ->first();

                $this->validateUrutanTanggal(
                    $barangId,
                    $gudangId,
                    $tanggalMutasi
                );

                if (!$stok) {
                    StokGudang::create([
                        'barang_id' => $barangId,
                        'gudang_id' => $gudangId,
                        'qty' => 0,
                        'harga_rata_rata' => 0,
                        'nilai_persediaan' => 0,
                    ]);

                    $stok = StokGudang::where('barang_id', $barangId)
                        ->where('gudang_id', $gudangId)
                        ->lockForUpdate()
                        ->firstOrFail();
                }

                $qtyLama = (float) $stok->qty;
                $nilaiLama = (float) $stok->nilai_persediaan;
                $nilaiMasuk = $qty * $hargaSatuan;

                $qtyBaru = $qtyLama + $qty;
                $nilaiBaru = $nilaiLama + $nilaiMasuk;

                $hargaRataRataBaru = $qtyBaru > 0 ? $nilaiBaru / $qtyBaru : 0;

                $stok->update([
                    'qty' => $qtyBaru,
                    'harga_rata_rata' => $hargaRataRataBaru,
                    'nilai_persediaan' => $nilaiBaru,
                ]);

                MutasiStok::create([
                    'tanggal' => $tanggalMutasi,
                    'barang_id' => $barangId,
                    'gudang_id' => $gudangId,
                    'tipe' => $tipeMutasi,
                    'qty_masuk' => $qty,
                    'qty_keluar' => 0,
                    'harga_satuan' => $hargaSatuan,
                    'nilai' => $nilaiMasuk,
                    'saldo_qty' => $qtyBaru,
                    'saldo_nilai' => $nilaiBaru,
                    'reference_type' => $referenceType,
                    'reference_id' => $referenceId,
                    'keterangan' => $keterangan,
                ]);

                return $stok->fresh();
            }
        );
    }

    /**
     * Stok keluar.
     */
    public function stokKeluar(
        int $barangId,
        int $gudangId,
        float $qty,
        string $referenceType,
        int $referenceId,
        ?string $keterangan = null,
        string $tipeMutasi = 'KELUAR',
        $tanggalTransaksi = null
    ): StokGudang {

        if ($qty <= 0) {
            throw new RuntimeException(
                'Qty stok keluar harus lebih dari 0.'
            );
        }

        $allowedTypes = [
            'KELUAR',
            'MUTASI_KELUAR',
            'RETUR_KELUAR',
            'PENJUALAN',
            'INVOICE_PENJUALAN',
            'PENYESUAIAN',
        ];

        if (!in_array(
            $tipeMutasi,
            $allowedTypes,
            true
        )) {
            throw new RuntimeException(
                'Tipe mutasi stok keluar tidak valid.'
            );
        }

        $tanggalMutasi = $this->resolveTanggalTransaksi($tanggalTransaksi);

        return DB::transaction(
            function () use (
                $barangId,
                $gudangId,
                $qty,
                $referenceType,
                $referenceId,
                $keterangan,
                $tipeMutasi,
                $tanggalMutasi
            ) {
                $stok = StokGudang::where('barang_id', $barangId)
                    ->where('gudang_id', $gudangId)
                    ->lockForUpdate()
                    ->first();

                if (!$stok) {
                    StokGudang::create([
                        'barang_id' => $barangId,
                        'gudang_id' => $gudangId,
                        'qty' => 0,
                        'harga_rata_rata' => 0,
                        'nilai_persediaan' => 0,
                    ]);

                    $stok = StokGudang::where('barang_id', $barangId)
                        ->where('gudang_id', $gudangId)
                        ->lockForUpdate()
                        ->firstOrFail();
                }

                $this->validateUrutanTanggal(
                    $barangId,
                    $gudangId,
                    $tanggalMutasi
                );

                $qtyLama = (float) $stok->qty;

                if ($qty > $qtyLama) {
                    $barang = \App\Models\Barang::find($barangId);
                    $namaBarang = $barang ? "{$barang->kode_barang} - {$barang->nama_barang}" : "ID {$barangId}";
                    throw new RuntimeException(
                        "Stok barang '{$namaBarang}' di gudang yang dipilih tidak mencukupi. (Stok tersedia: " . number_format($qtyLama, 0, ',', '.') . ", Dibutuhkan: " . number_format($qty, 0, ',', '.') . ")"
                    );
                }

                $hargaRataRata = (float) $stok->harga_rata_rata;
                $nilaiKeluar = $qty * $hargaRataRata;

                $qtyBaru = $qtyLama - $qty;
                $nilaiLama = (float) $stok->nilai_persediaan;
                $nilaiBaru = $nilaiLama - $nilaiKeluar;

                if ($nilaiBaru < 0 && abs($nilaiBaru) < 0.01) {
                    $nilaiBaru = 0;
                }

                if ($nilaiBaru < 0) {
                    $nilaiBaru = 0;
                }

                if (abs($qtyBaru) < 0.0001) {
                    $qtyBaru = 0;
                    $nilaiBaru = 0;
                }

                $stok->update([
                    'qty' => $qtyBaru,
                    'harga_rata_rata' => $qtyBaru > 0 ? $hargaRataRata : 0,
                    'nilai_persediaan' => $nilaiBaru,
                ]);

                MutasiStok::create([
                    'tanggal' => $tanggalMutasi,
                    'barang_id' => $barangId,
                    'gudang_id' => $gudangId,
                    'tipe' => $tipeMutasi,
                    'qty_masuk' => 0,
                    'qty_keluar' => $qty,
                    'harga_satuan' => $hargaRataRata,
                    'nilai' => $nilaiKeluar,
                    'saldo_qty' => $qtyBaru,
                    'saldo_nilai' => $nilaiBaru,
                    'reference_type' => $referenceType,
                    'reference_id' => $referenceId,
                    'keterangan' => $keterangan,
                ]);

                return $stok->fresh();
            }
        );
    }

    /**
     * Alias method untuk stok masuk dengan urutan (gudangId, barangId, ...)
     */
    public function masuk(
        int $gudangId,
        int $barangId,
        float $qty,
        float $hargaSatuan,
        string $referenceType,
        int $referenceId,
        ?string $referenceNumber = null,
        ?string $keterangan = null,
        $tanggalTransaksi = null
    ): StokGudang {
        return $this->stokMasuk(
            $barangId,
            $gudangId,
            $qty,
            $hargaSatuan,
            $referenceType,
            $referenceId,
            $keterangan,
            'RETUR_MASUK',
            $tanggalTransaksi
        );
    }

    /**
     * Alias method untuk stok keluar dengan urutan (gudangId, barangId, ...)
     */
    public function keluar(
        int $gudangId,
        int $barangId,
        float $qty,
        string $referenceType,
        int $referenceId,
        ?string $referenceNumber = null,
        ?string $keterangan = null,
        $tanggalTransaksi = null
    ): StokGudang {
        return $this->stokKeluar(
            $barangId,
            $gudangId,
            $qty,
            $referenceType,
            $referenceId,
            $keterangan,
            'KELUAR',
            $tanggalTransaksi
        );
    }

    private function validateUrutanTanggal(
        int $barangId,
        int $gudangId,
        Carbon $tanggalMutasi
    ): void {
        $mutasiTerakhir = MutasiStok::where('barang_id', $barangId)
            ->where('gudang_id', $gudangId)
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->lockForUpdate()
            ->first();

        if (!$mutasiTerakhir) {
            return;
        }

        $tanggalTerakhir = Carbon::parse($mutasiTerakhir->tanggal);

        if ($tanggalMutasi->copy()->startOfDay()->lt($tanggalTerakhir->copy()->startOfDay())) {
            throw new RuntimeException(
                'Tanggal transaksi stok tidak boleh sebelum tanggal mutasi stok terakhir untuk barang dan gudang ini. '
                . 'Tanggal mutasi terakhir: '
                . $tanggalTerakhir->format('d/m/Y')
                . '.'
            );
        }
    }

    private function resolveTanggalTransaksi($tanggalTransaksi = null): Carbon
    {
        if (empty($tanggalTransaksi)) {
            return now();
        }

        try {
            $tanggal = Carbon::parse($tanggalTransaksi);

            if (is_string($tanggalTransaksi) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggalTransaksi)) {
                $sekarang = now();
                $tanggal->setTime($sekarang->hour, $sekarang->minute, $sekarang->second);
            }

            return $tanggal;
        } catch (\Throwable $e) {
            throw new RuntimeException('Tanggal transaksi stok tidak valid.');
        }
    }
}