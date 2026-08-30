<?php

namespace App\Http\Controllers;

use App\Models\Gudang;
use App\Models\PenerimaanBarang;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

                $poItem = PurchaseOrderItem::where(
                    'id',
                    $inputItem['purchase_order_item_id']
                )
                    ->where(
                        'purchase_order_id',
                        $purchaseOrder->id
                    )
                    ->lockForUpdate()
                    ->first();

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

                $itemsToReceive[] = [
                    'po_item' =>
                        $poItem,

                    'qty_diterima' =>
                        $qtyDiterima,
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

                $hargaSatuan =
                    (float) $poItem->harga_satuan;


                /*
                 * Simpan detail penerimaan.
                 */

                $penerimaanItem =
                    $penerimaan
                        ->items()
                        ->create([
                            'purchase_order_item_id' =>
                                $poItem->id,

                            'barang_id' =>
                                $poItem->barang_id,

                            'qty_diterima' =>
                                $qtyDiterima,

                            'harga_satuan' =>
                                $hargaSatuan,
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
                        $hargaSatuan,

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
                        $validated['tanggal_penerimaan']
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