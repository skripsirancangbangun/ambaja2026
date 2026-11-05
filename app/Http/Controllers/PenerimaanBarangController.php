<?php

namespace App\Http\Controllers;

use App\Models\Gudang;
use App\Models\PenerimaanBarang;
use App\Models\PenerimaanBarangItem;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PermintaanPembelian;
use App\Notifications\PermintaanPembelianNotification;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class PenerimaanBarangController extends Controller
{
    public function index()
    {
        $penerimaanBarangs = PenerimaanBarang::with([
            'purchaseOrder.supplier',
            'gudang',
            'creator',
        ])
            ->latest('tanggal_penerimaan')
            ->latest('id')
            ->paginate(10);

        return view(
            'penerimaan-barang.index',
            compact('penerimaanBarangs')
        );
    }

    public function create(Request $request)
    {
        $purchaseOrders = PurchaseOrder::with([
            'supplier',
            'items.barang.satuan',
        ])
            ->whereIn('status', [
                'Dipesan',
                'Sebagian Diterima',
            ])
            ->orderBy('tanggal_po')
            ->get();

        $gudangs = Gudang::where('is_active', true)
            ->orderBy('nama_gudang')
            ->get();

        $selectedPurchaseOrder = null;

        if ($request->filled('purchase_order_id')) {
            $selectedPurchaseOrder = PurchaseOrder::with([
                'supplier',
                'items.barang.satuan',
            ])
                ->whereIn('status', [
                    'Dipesan',
                    'Sebagian Diterima',
                ])
                ->find($request->purchase_order_id);
        }

        $previewNomorPenerimaan =
            $this->generateNomorPenerimaan();

        return view(
            'penerimaan-barang.create',
            compact(
                'purchaseOrders',
                'gudangs',
                'selectedPurchaseOrder',
                'previewNomorPenerimaan'
            )
        );
    }

    public function store(
        Request $request,
        InventoryService $inventoryService
    ) {
        $validated = $request->validate([
            'purchase_order_id' =>
                'required|exists:purchase_orders,id',

            'gudang_id' =>
                'required|exists:gudangs,id',

            'tanggal_penerimaan' =>
                'required|date',

            'nomor_surat_jalan' =>
                'nullable|string|max:255',

            'catatan' =>
                'nullable|string',

            'items' =>
                'required|array|min:1',

            'items.*.purchase_order_item_id' =>
                'required|exists:purchase_order_items,id',

            'items.*.qty_diterima' =>
                'nullable|numeric|min:0',
        ]);

        DB::transaction(function () use (
            $validated,
            $inventoryService
        ) {
            /*
            |--------------------------------------------------------------------------
            | LOCK PURCHASE ORDER
            |--------------------------------------------------------------------------
            */

            $purchaseOrder = PurchaseOrder::where(
                'id',
                $validated['purchase_order_id']
            )
                ->lockForUpdate()
                ->firstOrFail();

            if (
                !in_array(
                    $purchaseOrder->status,
                    [
                        'Dipesan',
                        'Sebagian Diterima',
                    ],
                    true
                )
            ) {
                throw ValidationException::withMessages([
                    'purchase_order_id' =>
                        'Purchase Order ini sudah tidak dapat menerima barang.',
                ]);
            }

            // Periksa ulang status gudang di dalam transaksi agar penerimaan
            // tidak masuk ke gudang yang sudah dinonaktifkan.
            $gudang = Gudang::query()
                ->whereKey($validated['gudang_id'])
                ->lockForUpdate()
                ->first();

            if (!$gudang || !$gudang->is_active) {
                throw ValidationException::withMessages([
                    'gudang_id' =>
                        'Gudang tujuan tidak ditemukan atau sudah nonaktif. Pilih gudang aktif.',
                ]);
            }

            // Lock seluruh item PO dengan urutan konsisten agar alokasi diskon
            // header dihitung dari satu snapshot PO yang sama.
            $poItems = PurchaseOrderItem::query()
                ->where('purchase_order_id', $purchaseOrder->id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $nilaiPO = $this->hitungNilaiPersediaanPO(
                $purchaseOrder,
                $poItems->values()
            );

            /*
            |--------------------------------------------------------------------------
            | VALIDASI DUPLIKAT ITEM
            |--------------------------------------------------------------------------
            */

            $itemIds =
                collect($validated['items'])
                    ->pluck('purchase_order_item_id')
                    ->filter();

            if (
                $itemIds->count()
                !==
                $itemIds->unique()->count()
            ) {
                throw ValidationException::withMessages([
                    'items' =>
                        'Barang yang sama tidak boleh dimasukkan lebih dari satu kali.',
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | SIAPKAN ITEM YANG AKAN DITERIMA
            |--------------------------------------------------------------------------
            */

            $itemsToReceive = [];

            foreach (
                $validated['items']
                as $index => $inputItem
            ) {
                $qtyDiterima = round(
                    (float) (
                        $inputItem['qty_diterima']
                        ?? 0
                    ),
                    2
                );

                if ($qtyDiterima <= 0) {
                    continue;
                }

                $poItem = $poItems->get(
                    (int) $inputItem['purchase_order_item_id']
                );

                if (!$poItem) {
                    throw ValidationException::withMessages([
                        "items.$index.purchase_order_item_id" =>
                            'Barang tidak berasal dari Purchase Order yang dipilih.',
                    ]);
                }

                $sisa = round(
                    (float) $poItem->qty
                    - (float) $poItem->qty_diterima
                    - (float) $poItem->qty_dibatalkan,
                    2
                );

                if ($sisa <= 0) {
                    throw ValidationException::withMessages([
                        "items.$index.qty_diterima" =>
                            'Barang ini sudah selesai diproses.',
                    ]);
                }

                if ($qtyDiterima > $sisa) {
                    throw ValidationException::withMessages([
                        "items.$index.qty_diterima" =>
                            'Qty diterima melebihi sisa Purchase Order. Maksimal '
                            . number_format(
                                $sisa,
                                2,
                                ',',
                                '.'
                            ),
                    ]);
                }

                $nilaiPersediaanDiterima = $this->hitungNilaiPenerimaanItem(
                    $poItem,
                    $qtyDiterima,
                    $sisa,
                    $nilaiPO[$poItem->id]
                );

                $itemsToReceive[] = [
                    'po_item' => $poItem,
                    'qty_diterima' => $qtyDiterima,
                    'nilai_persediaan' => $nilaiPersediaanDiterima,
                ];
            }


            /*
            |--------------------------------------------------------------------------
            | PASTIKAN ADA BARANG DITERIMA
            |--------------------------------------------------------------------------
            */

            if (count($itemsToReceive) === 0) {
                throw ValidationException::withMessages([
                    'items' =>
                        'Masukkan qty diterima minimal untuk satu barang.',
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | BUAT HEADER PENERIMAAN
            |--------------------------------------------------------------------------
            */

            $penerimaan =
                PenerimaanBarang::create([
                    'nomor_penerimaan' =>
                        $this->generateNomorPenerimaan(),

                    'purchase_order_id' =>
                        $purchaseOrder->id,

                    'gudang_id' =>
                        $validated['gudang_id'],

                    'tanggal_penerimaan' =>
                        $validated['tanggal_penerimaan'],

                    'nomor_surat_jalan' =>
                        $validated['nomor_surat_jalan']
                        ?? null,

                    'catatan' =>
                        $validated['catatan']
                        ?? null,

                    'created_by' =>
                        auth()->id(),
                ]);


            /*
            |--------------------------------------------------------------------------
            | SIMPAN DETAIL + UPDATE INVENTORY
            |--------------------------------------------------------------------------
            */

            foreach ($itemsToReceive as $item) {

                $poItem =
                    $item['po_item'];

                $qtyDiterima =
                    $item['qty_diterima'];

                // Harga PO bruto tetap disimpan sebagai harga dokumen.
                // Nilai persediaan dicatat terpisah setelah diskon item dan
                // alokasi diskon header diperhitungkan.
                $hargaSatuan = (float) $poItem->harga_satuan;
                $nilaiPersediaan = (float) $item['nilai_persediaan'];
                $hargaSatuanPenilaian = $nilaiPersediaan / $qtyDiterima;

                $penerimaanItem =
                    $penerimaan
                        ->items()
                        ->create([
                            'purchase_order_item_id' => $poItem->id,
                            'barang_id' => $poItem->barang_id,
                            'qty_diterima' => $qtyDiterima,
                            'harga_satuan' => $hargaSatuan,
                            'nilai_persediaan' => $nilaiPersediaan,
                        ]);


                /*
                 * Update stok gudang.
                 *
                 * PENTING:
                 * tanggalTransaksi menggunakan
                 * tanggal penerimaan barang.
                 *
                 * Jadi MutasiStok mengikuti
                 * tanggal dokumen penerimaan,
                 * bukan tanggal input sistem.
                 */

                $inventoryService->stokMasuk(
                    barangId:
                        (int) $poItem->barang_id,

                    gudangId:
                        (int) $validated['gudang_id'],

                    qty:
                        (float) $qtyDiterima,

                    hargaSatuan:
                        $hargaSatuanPenilaian,

                    referenceType:
                        'PENERIMAAN_BARANG',

                    referenceId:
                        (int) $penerimaanItem->id,

                    keterangan:
                        'Penerimaan Barang '
                        . $penerimaan->nomor_penerimaan,

                    tipeMutasi:
                        'MASUK',

                    tanggalTransaksi:
                        $validated['tanggal_penerimaan'],

                    nilaiMasukOverride:
                        $nilaiPersediaan
                );


                /*
                 * Update qty diterima PO.
                 */

                $poItem->increment(
                    'qty_diterima',
                    $qtyDiterima
                );
            }


            /*
            |--------------------------------------------------------------------------
            | UPDATE STATUS PO
            |--------------------------------------------------------------------------
            */

            $this->updateStatusPurchaseOrder(
                $purchaseOrder
            );
        });


        return redirect()
            ->route('penerimaan-barang.index')
            ->with(
                'success',
                'Penerimaan barang berhasil disimpan dan stok gudang telah diperbarui.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | BATALKAN SISA ITEM PO
    |--------------------------------------------------------------------------
    */

    public function batalkanSisa(
        PurchaseOrderItem $purchaseOrderItem
    ) {
        DB::transaction(function () use (
            $purchaseOrderItem
        ) {
            $item = PurchaseOrderItem::where(
                'id',
                $purchaseOrderItem->id
            )
                ->lockForUpdate()
                ->firstOrFail();

            $purchaseOrder = PurchaseOrder::where(
                'id',
                $item->purchase_order_id
            )
                ->lockForUpdate()
                ->firstOrFail();

            if (
                !in_array(
                    $purchaseOrder->status,
                    [
                        'Dipesan',
                        'Sebagian Diterima',
                    ],
                    true
                )
            ) {
                throw ValidationException::withMessages([
                    'item' =>
                        'Purchase Order ini sudah tidak dapat diubah.',
                ]);
            }

            $sisa = round(
                (float) $item->qty
                - (float) $item->qty_diterima
                - (float) $item->qty_dibatalkan,
                2
            );

            if ($sisa <= 0) {
                throw ValidationException::withMessages([
                    'item' =>
                        'Tidak ada sisa barang yang dapat dibatalkan.',
                ]);
            }

            $item->update([
                'qty_dibatalkan' =>
                    round(
                        (float) $item->qty_dibatalkan
                        + $sisa,
                        2
                    ),
            ]);

            $this->updateStatusPurchaseOrder(
                $purchaseOrder
            );
        });

        return back()->with(
            'success',
            'Sisa barang Purchase Order berhasil dibatalkan.'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | SHOW
    |--------------------------------------------------------------------------
    */

    public function show(
        PenerimaanBarang $penerimaanBarang
    ) {
        $penerimaanBarang->load([
            'purchaseOrder.supplier',
            'gudang',
            'creator',
            'items.barang.satuan',
            'items.purchaseOrderItem',
        ]);

        return view(
            'penerimaan-barang.show',
            compact('penerimaanBarang')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | EDIT
    |--------------------------------------------------------------------------
    */

    public function edit(
        PenerimaanBarang $penerimaanBarang
    ) {
        return redirect()
            ->route(
                'penerimaan-barang.show',
                $penerimaanBarang
            )
            ->with(
                'error',
                'Penerimaan barang yang sudah disimpan tidak dapat diedit.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        PenerimaanBarang $penerimaanBarang
    ) {
        return redirect()
            ->route(
                'penerimaan-barang.show',
                $penerimaanBarang
            )
            ->with(
                'error',
                'Penerimaan barang yang sudah disimpan tidak dapat diedit.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE
    |--------------------------------------------------------------------------
    */

    public function destroy(
        PenerimaanBarang $penerimaanBarang
    ) {
        return redirect()
            ->route(
                'penerimaan-barang.show',
                $penerimaanBarang
            )
            ->with(
                'error',
                'Penerimaan barang tidak dapat dihapus langsung karena sudah memengaruhi stok.'
            );
    }


    /**
     * Hitung biaya perolehan per item PO setelah diskon item dan alokasi
     * diskon header. PPN tidak dimasukkan ke HPP karena diasumsikan PPN Masukan
     * dapat dikreditkan. Jika pajak tidak dapat dikreditkan, kebijakan biaya
     * perolehan harus ditentukan terpisah.
     */
    private function hitungNilaiPersediaanPO(
        PurchaseOrder $purchaseOrder,
        Collection $items
    ): array {
        $lines = [];
        $subtotalHitung = 0.0;

        foreach ($items as $item) {
            $qty = round((float) $item->qty, 2);
            $harga = round((float) $item->harga_satuan, 2);
            $diskonItem = round((float) $item->diskon, 2);
            $bruto = round($qty * $harga, 2);

            if ($qty <= 0 || $harga < 0 || $diskonItem < 0 || $diskonItem > $bruto + 0.01) {
                throw ValidationException::withMessages([
                    'purchase_order_id' =>
                        'Data kuantitas, harga, atau diskon pada PO tidak valid. Periksa detail PO sebelum menerima barang.',
                ]);
            }

            $subtotalItem = round($bruto - $diskonItem, 2);
            $subtotalHitung = round($subtotalHitung + $subtotalItem, 2);

            $lines[$item->id] = [
                'qty' => $qty,
                'qty_dibatalkan' => round((float) $item->qty_dibatalkan, 2),
                'subtotal' => $subtotalItem,
                'diskon_header' => 0.0,
                'nilai_bersih' => $subtotalItem,
            ];
        }

        $subtotalTersimpan = round((float) $purchaseOrder->subtotal, 2);
        if (abs($subtotalHitung - $subtotalTersimpan) > 0.01) {
            throw ValidationException::withMessages([
                'purchase_order_id' =>
                    'Subtotal detail PO tidak sama dengan subtotal header. Periksa PO sebelum penerimaan diproses.',
            ]);
        }

        $diskonHeader = round((float) $purchaseOrder->diskon, 2);
        if ($diskonHeader < 0 || $diskonHeader > $subtotalHitung + 0.01) {
            throw ValidationException::withMessages([
                'purchase_order_id' =>
                    'Diskon header PO tidak valid atau melebihi subtotal.',
            ]);
        }

        // Alokasi proporsional dengan residu sen pada baris terakhir yang bernilai.
        $positiveIds = [];
        foreach ($lines as $id => $line) {
            if ($line['subtotal'] > 0) {
                $positiveIds[] = $id;
            }
        }

        $lastPositiveId = empty($positiveIds) ? null : end($positiveIds);
        $allocatedSoFar = 0.0;

        foreach ($lines as $id => &$line) {
            if ($line['subtotal'] <= 0 || $subtotalHitung <= 0 || $diskonHeader <= 0) {
                $allocation = 0.0;
            } elseif ($id === $lastPositiveId) {
                $allocation = round($diskonHeader - $allocatedSoFar, 2);
            } else {
                $allocation = round(
                    $diskonHeader * $line['subtotal'] / $subtotalHitung,
                    2
                );
                $allocatedSoFar = round($allocatedSoFar + $allocation, 2);
            }

            if ($allocation < 0 || $allocation > $line['subtotal'] + 0.01) {
                unset($line);
                throw ValidationException::withMessages([
                    'purchase_order_id' =>
                        'Alokasi diskon PO menghasilkan nilai item yang tidak valid.',
                ]);
            }

            $line['diskon_header'] = $allocation;
            $line['nilai_bersih'] = round($line['subtotal'] - $allocation, 2);
        }
        unset($line);

        foreach ($lines as $id => &$line) {
            $qtyEfektif = round(max(0, $line['qty'] - $line['qty_dibatalkan']), 2);
            $line['qty_efektif'] = $qtyEfektif;
            $line['biaya_per_unit'] = $line['qty'] > 0
                ? $line['nilai_bersih'] / $line['qty']
                : 0.0;
            $line['target_nilai_diterima'] = $line['qty'] > 0
                ? round($line['nilai_bersih'] * $qtyEfektif / $line['qty'], 2)
                : 0.0;
        }
        unset($line);

        return $lines;
    }

    /**
     * Tentukan nilai penerimaan. Penerimaan parsial menggunakan biaya bersih
     * per unit; penerimaan terakhir menyerap residu pembulatan agar total nilai
     * yang diterima sama dengan nilai bersih PO untuk kuantitas yang tidak dibatalkan.
     */
    private function hitungNilaiPenerimaanItem(
        PurchaseOrderItem $poItem,
        float $qtyDiterima,
        float $sisa,
        array $nilaiPOItem
    ): float {
        if ($nilaiPOItem['nilai_bersih'] <= 0) {
            throw ValidationException::withMessages([
                'items' =>
                    'Nilai bersih item PO setelah diskon adalah nol. Sistem tidak dapat menambahkan stok tanpa biaya perolehan yang valid.',
            ]);
        }

        $previousItems = PenerimaanBarangItem::query()
            ->where('purchase_order_item_id', $poItem->id)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        if ($previousItems->contains(fn ($row) => $row->nilai_persediaan === null)) {
            throw ValidationException::withMessages([
                'items' =>
                    'Item PO ini memiliki penerimaan lama yang belum menyimpan nilai persediaan bersih. Audit/revaluasi penerimaan lama sebelum melanjutkan penerimaan item ini.',
            ]);
        }

        $qtySebelumnya = round(
            (float) $previousItems->sum(fn ($row) => (float) $row->qty_diterima),
            2
        );
        if (abs($qtySebelumnya - (float) $poItem->qty_diterima) > 0.01) {
            throw ValidationException::withMessages([
                'items' =>
                    'Total detail penerimaan tidak sama dengan qty_diterima PO. Periksa integritas penerimaan sebelum melanjutkan.',
            ]);
        }

        $nilaiSebelumnya = round(
            (float) $previousItems->sum(fn ($row) => (float) $row->nilai_persediaan),
            2
        );

        $penerimaanTerakhir = $qtyDiterima >= ($sisa - 0.001);
        if ($penerimaanTerakhir) {
            $nilai = round(
                $nilaiPOItem['target_nilai_diterima'] - $nilaiSebelumnya,
                2
            );
        } else {
            $nilai = round(
                $nilaiPOItem['biaya_per_unit'] * $qtyDiterima,
                2
            );
        }

        if ($nilai < -0.01) {
            throw ValidationException::withMessages([
                'items' =>
                    'Nilai penerimaan yang dihitung negatif. Data penerimaan sebelumnya perlu direkonsiliasi.',
            ]);
        }

        $nilai = max(0, $nilai);
        if ($nilai <= 0) {
            throw ValidationException::withMessages([
                'items' =>
                    'Nilai penerimaan setelah alokasi diskon adalah nol. Periksa harga dan diskon PO.',
            ]);
        }

        return round($nilai, 2);
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE STATUS PURCHASE ORDER
    |--------------------------------------------------------------------------
    */

    private function updateStatusPurchaseOrder(
        PurchaseOrder $purchaseOrder
    ): void {
        $purchaseOrder->load('items');

        $semuaSelesai =
            $purchaseOrder
                ->items
                ->every(function ($item) {

                    return
                        (
                            (float) $item->qty_diterima
                            +
                            (float) $item->qty_dibatalkan
                        )
                        >=
                        (float) $item->qty;
                });

        if ($semuaSelesai) {

            $purchaseOrder->update([
                'status' =>
                    'Selesai',
            ]);

            $permintaanPembelian = PermintaanPembelian::query()
                ->where('purchase_order_id', $purchaseOrder->id)
                ->where('status', PermintaanPembelian::STATUS_PO_DIBUAT)
                ->first();

            if ($permintaanPembelian) {
                $permintaanPembelian->update([
                    'status' => PermintaanPembelian::STATUS_SELESAI,
                ]);

                $permintaanPembelian->creator?->notify(new PermintaanPembelianNotification(
                    $permintaanPembelian->id,
                    $permintaanPembelian->nomor_permintaan,
                    'selesai',
                    "PO {$purchaseOrder->nomor_po} untuk permintaan {$permintaanPembelian->nomor_permintaan} sudah ditutup: seluruh jumlah telah diterima atau sisa jumlah dibatalkan.",
                ));
            }

            return;
        }


        $adaPenerimaan =
            $purchaseOrder
                ->items
                ->contains(
                    fn ($item) =>
                        (float) $item->qty_diterima > 0
                );


        $adaPembatalan =
            $purchaseOrder
                ->items
                ->contains(
                    fn ($item) =>
                        (float) $item->qty_dibatalkan > 0
                );


        if (
            $adaPenerimaan
            ||
            $adaPembatalan
        ) {

            $purchaseOrder->update([
                'status' =>
                    'Sebagian Diterima',
            ]);

            return;
        }


        $purchaseOrder->update([
            'status' =>
                'Dipesan',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | GENERATE NOMOR PENERIMAAN
    |--------------------------------------------------------------------------
    */

    private function generateNomorPenerimaan(): string
    {
        $year =
            now()->format('Y');

        $month =
            now()->format('m');

        $prefix =
            "PB/{$year}/{$month}/";

        $last =
            PenerimaanBarang::where(
                'nomor_penerimaan',
                'like',
                $prefix . '%'
            )
                ->orderByDesc('id')
                ->first();

        $nextNumber =
            $last
                ? (
                    (int) substr(
                        $last->nomor_penerimaan,
                        -4
                    )
                ) + 1
                : 1;

        return
            $prefix
            . str_pad(
                $nextNumber,
                4,
                '0',
                STR_PAD_LEFT
            );
    }
}