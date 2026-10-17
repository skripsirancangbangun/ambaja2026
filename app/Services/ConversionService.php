<?php

namespace App\Services;

use App\Models\Barang;
use App\Models\Conversion;
use App\Models\ConversionItem;
use App\Models\MutasiStok;
use App\Models\StokGudang;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ConversionService
{
    public function __construct(
        protected InventoryService $inventoryService
    ) {
    }

    /**
     * Membuat transaksi konversi.
     *
     * Tahap ini langsung POSTED:
     *
     * INPUT
     *   ↓
     * stok berkurang
     *
     * OUTPUT
     *   ↓
     * stok bertambah
     *
     * HPP OUTPUT dihitung otomatis
     * berdasarkan nilai INPUT.
     */
    public function createAndPost(
        int $gudangId,
        string $tanggal,
        int $barangInputId,
        float $qtyInput,
        array $outputs,
        ?float $wasteQty = null,
        ?int $createdBy = null,
        ?string $keterangan = null
    ): Conversion {

        return DB::transaction(function () use (
            $gudangId,
            $tanggal,
            $barangInputId,
            $qtyInput,
            $outputs,
            $wasteQty,
            $createdBy,
            $keterangan
        ) {

            /*
            |--------------------------------------------------------------------------
            | VALIDASI INPUT
            |--------------------------------------------------------------------------
            */

            if ($qtyInput <= 0) {
                throw new RuntimeException(
                    'Qty barang asal harus lebih dari 0.'
                );
            }

            if (empty($outputs)) {
                throw new RuntimeException(
                    'Barang hasil konversi wajib diisi.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | AMBIL STOK INPUT
            |--------------------------------------------------------------------------
            */

            $stokInput = StokGudang::where(
                'barang_id',
                $barangInputId
            )
                ->where(
                    'gudang_id',
                    $gudangId
                )
                ->lockForUpdate()
                ->first();

            if (!$stokInput) {
                throw new RuntimeException(
                    'Stok barang asal belum tersedia '
                    . 'di gudang yang dipilih.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | CEK QTY
            |--------------------------------------------------------------------------
            */

            $qtyTersedia =
                (float) $stokInput->qty;

            if ($qtyInput > $qtyTersedia) {
                throw new RuntimeException(
                    'Stok barang asal tidak mencukupi. '
                    . 'Stok tersedia: '
                    . number_format(
                        $qtyTersedia,
                        2,
                        ',',
                        '.'
                    )
                );
            }

            /*
            |--------------------------------------------------------------------------
            | AMBIL HPP INPUT
            |--------------------------------------------------------------------------
            */

            $hppInput =
                (float) $stokInput->harga_rata_rata;

            /*
            |--------------------------------------------------------------------------
            | NILAI INPUT
            |--------------------------------------------------------------------------
            |
            | Contoh:
            |
            | 10 batang × Rp60.000
            | = Rp600.000
            |
            */

            $nilaiInput = round(
                $qtyInput * $hppInput,
                2
            );

            if ($nilaiInput < 0) {
                throw new RuntimeException(
                    'Nilai persediaan input tidak valid.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | VALIDASI OUTPUT
            |--------------------------------------------------------------------------
            */

            $totalOutputQty = 0;

            foreach ($outputs as $output) {

                if (
                    empty($output['barang_id'])
                ) {
                    throw new RuntimeException(
                        'Barang hasil konversi wajib dipilih.'
                    );
                }

                $qtyOutput =
                    (float) (
                        $output['qty']
                        ?? 0
                    );

                if ($qtyOutput <= 0) {
                    throw new RuntimeException(
                        'Qty barang hasil harus lebih dari 0.'
                    );
                }

                /*
                 * Barang hasil tidak boleh
                 * sama dengan barang input
                 * pada versi awal.
                 */
                if (
                    (int) $output['barang_id']
                    === $barangInputId
                ) {
                    throw new RuntimeException(
                        'Barang hasil tidak boleh sama '
                        . 'dengan barang asal.'
                    );
                }

                $totalOutputQty += $qtyOutput;
            }

            /*
            |--------------------------------------------------------------------------
            | WASTE
            |--------------------------------------------------------------------------
            */

            $wasteQty =
                $wasteQty !== null
                    ? (float) $wasteQty
                    : 0;

            if ($wasteQty < 0) {
                throw new RuntimeException(
                    'Qty waste tidak boleh negatif.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | NOMOR KONVERSI
            |--------------------------------------------------------------------------
            */

            $nomorKonversi =
                $this->generateNomorKonversi();

            /*
            |--------------------------------------------------------------------------
            | HEADER
            |--------------------------------------------------------------------------
            */

            $conversion = Conversion::create([
                'nomor_konversi' =>
                    $nomorKonversi,

                'tanggal' =>
                    Carbon::parse($tanggal),

                'gudang_id' =>
                    $gudangId,

                'total_nilai' =>
                    $nilaiInput,

                'status' =>
                    Conversion::STATUS_POSTED,

                'keterangan' =>
                    $keterangan,

                'created_by' =>
                    $createdBy,

                'posted_by' =>
                    $createdBy,

                'posted_at' =>
                    now(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | INPUT ITEM
            |--------------------------------------------------------------------------
            */

            $barangInput =
                Barang::findOrFail(
                    $barangInputId
                );

            $conversion->items()->create([
                'barang_id' =>
                    $barangInputId,

                'tipe' =>
                    ConversionItem::TIPE_INPUT,

                'qty' =>
                    $qtyInput,

                'satuan' =>
                    $barangInput->satuan?->nama_satuan,

                'hpp' =>
                    $hppInput,

                'nilai' =>
                    $nilaiInput,

                'keterangan' =>
                    'Barang asal konversi',
            ]);

            /*
            |--------------------------------------------------------------------------
            | STOK KELUAR INPUT
            |--------------------------------------------------------------------------
            */

            $this->inventoryService->stokKeluar(
                barangId:
                    $barangInputId,

                gudangId:
                    $gudangId,

                qty:
                    $qtyInput,

                referenceType:
                    'KONVERSI_BARANG',

                referenceId:
                    $conversion->id,

                keterangan:
                    'Konversi '
                    . $nomorKonversi,

                tipeMutasi:
                    MutasiStok::TIPE_KONVERSI_KELUAR,

                tanggalTransaksi:
                    $tanggal
            );

            /*
            |--------------------------------------------------------------------------
            | ALOKASI NILAI OUTPUT
            |--------------------------------------------------------------------------
            |
            | Untuk versi awal:
            |
            | Total nilai output
            | harus sama dengan
            | nilai input.
            |
            | Jika ada beberapa output,
            | nilai dibagi berdasarkan
            | panjang/berat jika tersedia.
            |
            */

            $weights = [];

            $totalWeight = 0;

            foreach ($outputs as $index => $output) {

                $weight =
                    $this->hitungBobotAlokasi(
                        $output
                    );

                $weights[$index] =
                    $weight;

                $totalWeight += $weight;
            }

            /*
            | Jika user tidak memberikan
            | panjang/berat, gunakan qty.
            */
            if ($totalWeight <= 0) {

                $weights = [];

                $totalWeight = 0;

                foreach (
                    $outputs
                    as $index => $output
                ) {

                    $weight =
                        (float) $output['qty'];

                    $weights[$index] =
                        $weight;

                    $totalWeight +=
                        $weight;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | OUTPUT
            |--------------------------------------------------------------------------
            */

            $nilaiOutputTotal = 0;

            foreach (
                $outputs
                as $index => $output
            ) {

                $barangOutput =
                    Barang::with('satuan')
                        ->findOrFail(
                            $output['barang_id']
                        );

                $qtyOutput =
                    (float) $output['qty'];

                /*
                 * Alokasi nilai.
                 */
                $proporsi =
                    $weights[$index]
                    / $totalWeight;

                $nilaiOutput =
                    round(
                        $nilaiInput
                        * $proporsi,
                        2
                    );

                /*
                 * HPP output.
                 */
                $hppOutput =
                    $qtyOutput > 0
                        ? round(
                            $nilaiOutput
                            / $qtyOutput,
                            2
                        )
                        : 0;

                /*
                 * Simpan detail.
                 */
                $conversion->items()->create([
                    'barang_id' =>
                        $barangOutput->id,

                    'tipe' =>
                        ConversionItem::TIPE_OUTPUT,

                    'qty' =>
                        $qtyOutput,

                    'satuan' =>
                        $barangOutput
                            ->satuan
                            ?->nama_satuan,

                    'panjang' =>
                        $output['panjang']
                        ?? null,

                    'berat' =>
                        $output['berat']
                        ?? null,

                    'hpp' =>
                        $hppOutput,

                    'nilai' =>
                        $nilaiOutput,

                    'keterangan' =>
                        'Hasil konversi',
                ]);

                /*
                 * Stok masuk.
                 */
                $this->inventoryService->stokMasuk(
                    barangId:
                        $barangOutput->id,

                    gudangId:
                        $gudangId,

                    qty:
                        $qtyOutput,

                    hargaSatuan:
                        $hppOutput,

                    referenceType:
                        'KONVERSI_BARANG',

                    referenceId:
                        $conversion->id,

                    keterangan:
                        'Konversi '
                        . $nomorKonversi,

                    tipeMutasi:
                        MutasiStok::TIPE_KONVERSI_MASUK,

                    tanggalTransaksi:
                        $tanggal
                );

                $nilaiOutputTotal +=
                    $nilaiOutput;
            }

            /*
            |--------------------------------------------------------------------------
            | WASTE
            |--------------------------------------------------------------------------
            */

            if ($wasteQty > 0) {

                /*
                 * Untuk versi awal,
                 * waste dihitung menggunakan
                 * proporsi qty.
                 */
                $hppWaste =
                    $qtyInput > 0
                        ? $nilaiInput
                            / $qtyInput
                        : 0;

                $nilaiWaste =
                    round(
                        $wasteQty
                        * $hppWaste,
                        2
                    );

                $conversion->items()->create([
                    'barang_id' =>
                        $barangInputId,

                    'tipe' =>
                        ConversionItem::TIPE_WASTE,

                    'qty' =>
                        $wasteQty,

                    'satuan' =>
                        $barangInput
                            ->satuan
                            ?->nama_satuan,

                    'hpp' =>
                        $hppWaste,

                    'nilai' =>
                        $nilaiWaste,

                    'keterangan' =>
                        'Waste/sisa proses konversi',
                ]);

                /*
                 * Catat waste pada kartu stok.
                 */
                MutasiStok::create([
                    'tanggal' =>
                        Carbon::parse($tanggal),

                    'barang_id' =>
                        $barangInputId,

                    'gudang_id' =>
                        $gudangId,

                    'tipe' =>
                        MutasiStok::TIPE_WASTE,

                    'qty_masuk' =>
                        0,

                    'qty_keluar' =>
                        $wasteQty,

                    'harga_satuan' =>
                        $hppWaste,

                    'nilai' =>
                        $nilaiWaste,

                    'saldo_qty' =>
                        0,

                    'saldo_nilai' =>
                        0,

                    'reference_type' =>
                        'KONVERSI_BARANG',

                    'reference_id' =>
                        $conversion->id,

                    'keterangan' =>
                        'Waste konversi '
                        . $nomorKonversi,
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | VALIDASI NILAI
            |--------------------------------------------------------------------------
            */

            $selisih =
                round(
                    $nilaiInput
                    - $nilaiOutputTotal,
                    2
                );

            /*
             * Toleransi pembulatan 1 sen.
             */
            if (
                abs($selisih) > 0.02
                && $wasteQty <= 0
            ) {
                throw new RuntimeException(
                    'Nilai konversi tidak balance. '
                    . 'Selisih: Rp'
                    . number_format(
                        $selisih,
                        2,
                        ',',
                        '.'
                    )
                );
            }

            return $conversion
                ->load([
                    'items.barang',
                    'gudang',
                ]);
        });
    }

    /**
     * Menghitung bobot alokasi.
     *
     * Prioritas:
     *
     * 1. berat
     * 2. panjang
     * 3. qty
     */
    private function hitungBobotAlokasi(
        array $output
    ): float {

        if (
            isset($output['berat'])
            && (float) $output['berat'] > 0
        ) {
            return (float) $output['berat'];
        }

        if (
            isset($output['panjang'])
            && (float) $output['panjang'] > 0
        ) {
            return
                (float) $output['panjang']
                * (float) $output['qty'];
        }

        return
            (float) (
                $output['qty']
                ?? 0
            );
    }

    /**
     * Generate nomor konversi.
     */
    private function generateNomorKonversi(): string
    {
        $prefix =
            'KONV-'
            . now()->format('Ym')
            . '-';

        $last =
            Conversion::where(
                'nomor_konversi',
                'like',
                $prefix . '%'
            )
                ->orderByDesc('id')
                ->lockForUpdate()
                ->first();

        $nextNumber =
            $last
                ? (
                    (int) substr(
                        $last->nomor_konversi,
                        -5
                    )
                    + 1
                )
                : 1;

        return $prefix
            . str_pad(
                $nextNumber,
                5,
                '0',
                STR_PAD_LEFT
            );
    }
}