<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\PurchaseOrder;
use App\Models\PermintaanPembelian;
use App\Notifications\PermintaanPembelianNotification;
use App\Models\Supplier;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class PurchaseOrderController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $query =
            PurchaseOrder::query()
                ->with([
                    'supplier',
                    'creator',
                    'items',
                ]);

        /*
        |--------------------------------------------------------------------------
        | SEARCH
        |--------------------------------------------------------------------------
        */

        if (
            $request->filled('search')
        ) {
            $search =
                trim(
                    $request->search
                );

            $query->where(
                function ($q) use ($search) {
                    $q->where(
                        'nomor_po',
                        'like',
                        "%{$search}%"
                    )
                        ->orWhereHas(
                            'supplier',
                            function ($supplierQuery) use ($search) {
                                $supplierQuery->where(
                                    'nama_supplier',
                                    'like',
                                    "%{$search}%"
                                );
                            }
                        );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | STATUS
        |--------------------------------------------------------------------------
        */

        if (
            $request->filled('status')
        ) {
            $query->where(
                'status',
                $request->status
            );
        }

        /*
        |--------------------------------------------------------------------------
        | TANGGAL MULAI
        |--------------------------------------------------------------------------
        */

        if (
            $request->filled(
                'tanggal_mulai'
            )
        ) {
            $query->whereDate(
                'tanggal_po',
                '>=',
                $request->tanggal_mulai
            );
        }

        /*
        |--------------------------------------------------------------------------
        | TANGGAL SELESAI
        |--------------------------------------------------------------------------
        */

        if (
            $request->filled(
                'tanggal_selesai'
            )
        ) {
            $query->whereDate(
                'tanggal_po',
                '<=',
                $request->tanggal_selesai
            );
        }

        $purchaseOrders =
            $query
                ->latest(
                    'tanggal_po'
                )
                ->latest(
                    'id'
                )
                ->paginate(15)
                ->withQueryString();

        return view(
            'purchase-order.index',
            compact(
                'purchaseOrders'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE
    |--------------------------------------------------------------------------
    */

    public function create(Request $request)
    {
        $permintaanPembelian = null;
        $initialItems = [];

        if ($request->filled('permintaan_pembelian_id')) {
            $permintaanPembelian = PermintaanPembelian::query()
                ->with(['details.barang.satuan'])
                ->where('status', PermintaanPembelian::STATUS_DISETUJUI)
                ->whereNull('purchase_order_id')
                ->findOrFail($request->integer('permintaan_pembelian_id'));

            $initialItems = $permintaanPembelian->details
                ->filter(fn ($detail): bool => (float) $detail->qty_disetujui > 0)
                ->map(fn ($detail): array => [
                    'barang_id' => $detail->barang_id,
                    'qty' => (float) $detail->qty_disetujui,
                    'harga_satuan' => (float) ($detail->barang?->harga_beli_default ?? 0),
                    'diskon' => 0,
                    'keterangan' => 'Dari ' . $permintaanPembelian->nomor_permintaan,
                ])
                ->values()
                ->all();

            if ($initialItems === []) {
                abort(422, 'Permintaan yang disetujui tidak memiliki jumlah barang untuk dibuatkan PO.');
            }
        }

        $suppliers =
            Supplier::query()
                ->where('is_active', true)
                ->orderBy(
                    'nama_supplier'
                )
                ->get();

        $itemsForDisplay = old('items', $initialItems);
        $selectedBarangIds = collect($itemsForDisplay)
            ->pluck('barang_id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        $selectedBarangData = $this->formatBarangPickerData(
            Barang::query()
                ->with(['satuan', 'stokGudangs.gudang'])
                ->whereIn('id', $selectedBarangIds)
                ->get()
        );

        /*
         * Preview saja.
         *
         * Nomor final tetap dibuat lagi
         * saat store di transaction.
         */
        $previewNomorPO =
            $this->generateNomorPO();

        return view(
            'purchase-order.create',
            compact(
                'suppliers',
                'selectedBarangData',
                'previewNomorPO',
                'permintaanPembelian',
                'initialItems'
            )
        );
    }

    /**
     * Pencarian barang khusus untuk supplier yang sedang dipilih di form PO.
     * Stok ditampilkan sebagai total dan rincian per gudang, tanpa hardcode gudang.
     */
    public function barangsBySupplier(Request $request, Supplier $supplier)
    {
        abort_unless($supplier->is_active, 404);

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        $search = trim($validated['search'] ?? '');

        $query = $supplier->barangs()
            ->with([
                'satuan',
                'stokGudangs.gudang:id,kode_gudang,nama_gudang',
            ])
            ->where('barangs.is_active', true);

        if ($search !== '') {
            $query->where(function ($barangQuery) use ($search): void {
                $like = '%' . $search . '%';
                $barangQuery
                    ->where('barangs.kode_barang', 'like', $like)
                    ->orWhere('barangs.nama_barang', 'like', $like)
                    ->orWhere('barangs.spesifikasi', 'like', $like)
                    ->orWhere('barangs.merek', 'like', $like);
            });
        }

        $barangs = $query
            ->orderBy('barangs.nama_barang')
            ->paginate(25);

        $data = $barangs->getCollection()
            ->map(function (Barang $barang): array {
                $stokGudangs = $barang->stokGudangs;

                return [
                    'id' => $barang->id,
                    'kode' => $barang->kode_barang,
                    'nama' => $barang->nama_barang,
                    'spesifikasi' => $barang->spesifikasi,
                    'merek' => $barang->merek,
                    'satuan' => $barang->satuan?->nama_satuan,
                    'harga_beli_default' => (float) $barang->harga_beli_default,
                    'stok_total' => (float) $stokGudangs->sum('qty'),
                    'stok_by_warehouse' => $stokGudangs
                        ->map(fn ($stok): array => [
                            'kode' => $stok->gudang?->kode_gudang ?? '-',
                            'nama' => $stok->gudang?->nama_gudang ?? 'Gudang tidak ditemukan',
                            'qty' => (float) $stok->qty,
                        ])
                        ->values()
                        ->all(),
                ];
            })
            ->values();

        return response()->json([
            'data' => $data,
            'meta' => [
                'current_page' => $barangs->currentPage(),
                'last_page' => $barangs->lastPage(),
                'per_page' => $barangs->perPage(),
                'total' => $barangs->total(),
            ],
        ]);
    }

    private function formatBarangPickerData($barangs): array
    {
        return $barangs->map(function (Barang $barang): array {
            $stokGudangs = $barang->stokGudangs;

            return [
                'id' => $barang->id,
                'kode' => $barang->kode_barang,
                'nama' => $barang->nama_barang,
                'spesifikasi' => $barang->spesifikasi,
                'merek' => $barang->merek,
                'satuan' => $barang->satuan?->nama_satuan,
                'harga_beli_default' => (float) $barang->harga_beli_default,
                'stok_total' => (float) $stokGudangs->sum('qty'),
                'stok_by_warehouse' => $stokGudangs
                    ->map(fn ($stok): array => [
                        'kode' => $stok->gudang?->kode_gudang ?? '-',
                        'nama' => $stok->gudang?->nama_gudang ?? 'Gudang tidak ditemukan',
                        'qty' => (float) $stok->qty,
                    ])
                    ->values()
                    ->all(),
            ];
        })->values()->all();
    }

    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    */

    public function store(
        Request $request
    ) {
        $validated =
            $request->validate(
                $this->validationRules()
            );

        $purchaseOrder =
            DB::transaction(
                function () use ($validated) {
                    $permintaanPembelian = null;

                    if (!empty($validated['permintaan_pembelian_id'])) {
                        $permintaanPembelian = PermintaanPembelian::query()
                            ->whereKey($validated['permintaan_pembelian_id'])
                            ->lockForUpdate()
                            ->firstOrFail();

                        if (
                            $permintaanPembelian->status !== PermintaanPembelian::STATUS_DISETUJUI
                            || $permintaanPembelian->purchase_order_id !== null
                        ) {
                            throw ValidationException::withMessages([
                                'permintaan_pembelian_id' => 'Permintaan sudah diproses atau sudah memiliki PO.',
                            ]);
                        }

                        $approvedDetails = $permintaanPembelian->details()
                            ->lockForUpdate()
                            ->get()
                            ->filter(fn ($detail): bool => (float) $detail->qty_disetujui > 0)
                            ->keyBy('barang_id');

                        $submittedItems = collect($validated['items'])->keyBy(fn ($item): int => (int) $item['barang_id']);

                        if ($approvedDetails->count() !== $submittedItems->count()) {
                            throw ValidationException::withMessages([
                                'items' => 'Detail PO harus sama dengan barang dan jumlah yang telah disetujui.',
                            ]);
                        }

                        foreach ($approvedDetails as $barangId => $detail) {
                            $submitted = $submittedItems->get((int) $barangId);

                            if (
                                !$submitted
                                || round((float) $submitted['qty'], 2) !== round((float) $detail->qty_disetujui, 2)
                            ) {
                                throw ValidationException::withMessages([
                                    'items' => 'Kuantitas PO harus sama dengan kuantitas yang disetujui Pembelian.',
                                ]);
                            }
                        }
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | HITUNG DETAIL + HEADER
                    |--------------------------------------------------------------------------
                    */

                    [
                        $detailData,
                        $totals,
                    ] =
                        $this
                            ->buildPurchaseOrderData(
                                $validated
                            );

                    /*
                    |--------------------------------------------------------------------------
                    | HEADER
                    |--------------------------------------------------------------------------
                    */

                    $purchaseOrder =
                        PurchaseOrder::create([
                            'nomor_po' =>
                                $this->generateNomorPO(
                                    $validated[
                                        'tanggal_po'
                                    ],
                                    true
                                ),

                            'supplier_id' =>
                                $validated[
                                    'supplier_id'
                                ],

                            'tanggal_po' =>
                                $validated[
                                    'tanggal_po'
                                ],

                            'tanggal_estimasi_datang' =>
                                $validated[
                                    'tanggal_estimasi_datang'
                                ] ?? null,

                            'subtotal' =>
                                $totals[
                                    'subtotal'
                                ],

                            'diskon' =>
                                $totals[
                                    'diskon'
                                ],

                            'pajak_persen' =>
                                $totals[
                                    'pajak_persen'
                                ],

                            'pajak' =>
                                $totals[
                                    'pajak'
                                ],

                            'total' =>
                                $totals[
                                    'total'
                                ],

                            'status' =>
                                'Draft',

                            'catatan' =>
                                $validated[
                                    'catatan'
                                ] ?? null,

                            'created_by' =>
                                auth()->id(),
                        ]);

                    /*
                    |--------------------------------------------------------------------------
                    | DETAIL
                    |--------------------------------------------------------------------------
                    */

                    foreach (
                        $detailData
                        as $detail
                    ) {
                        $purchaseOrder
                            ->items()
                            ->create(
                                $detail
                            );
                    }

                    if ($permintaanPembelian) {
                        $permintaanPembelian->update([
                            'purchase_order_id' => $purchaseOrder->id,
                            'status' => PermintaanPembelian::STATUS_PO_DIBUAT,
                        ]);

                        $permintaanPembelian->creator?->notify(new PermintaanPembelianNotification(
                            $permintaanPembelian->id,
                            $permintaanPembelian->nomor_permintaan,
                            'po_dibuat',
                            "PO {$purchaseOrder->nomor_po} sudah dibuat dari permintaan {$permintaanPembelian->nomor_permintaan}.",
                        ));
                    }

                    return $purchaseOrder;
                },
                3
            );

        return redirect()
            ->route(
                'purchase-order.show',
                $purchaseOrder
            )
            ->with(
                'success',
                'Purchase Order berhasil dibuat sebagai Draft.'
            );
    }

       /*
    |--------------------------------------------------------------------------
    | SHOW
    |--------------------------------------------------------------------------
    */

    public function show(
        PurchaseOrder $purchaseOrder
    ) {
        $purchaseOrder->load([
            'supplier',
            'creator',

            'items.barang.satuan',
            'items.barang.kategori',

            /*
             * Load penerimaan barang untuk
             * menampilkan progress penerimaan.
             */
            'penerimaanBarangs',

            /*
             * Load invoice supplier.
             *
             * Nantinya digunakan juga untuk integrasi
             * uang muka PO ke Invoice Supplier.
             */
            'invoiceSuppliers',

            /*
             * Load pembayaran yang dilakukan
             * langsung pada level Purchase Order.
             *
             * Pembayaran PO Supplier berbeda dengan
             * Pembayaran Supplier biasa:
             *
             * PO
             * -> Pembayaran PO Supplier
             * -> Uang Muka Pembelian
             * -> Invoice Supplier
             *
             * Pembayaran PO tidak mengubah stok.
             */
            'pembayaranPOSuppliers' => function ($query) {
                $query
                    ->with([
                        'rekeningKasBank',
                        'creator',
                    ])
                    ->orderByDesc(
                        'tanggal_pembayaran'
                    )
                    ->orderByDesc(
                        'id'
                    );
            },
        ]);

        return view(
            'purchase-order.show',
            compact(
                'purchaseOrder'
            )
        );
    }
    /*
    |--------------------------------------------------------------------------
    | EDIT
    |--------------------------------------------------------------------------
    */

    public function edit(
        PurchaseOrder $purchaseOrder
    ) {
        if (
            $purchaseOrder->status
            !== 'Draft'
        ) {
            return redirect()
                ->route(
                    'purchase-order.show',
                    $purchaseOrder
                )
                ->with(
                    'error',
                    'Hanya Purchase Order berstatus Draft yang dapat diedit.'
                );
        }

        if (PermintaanPembelian::query()->where('purchase_order_id', $purchaseOrder->id)->exists()) {
            return redirect()
                ->route('purchase-order.show', $purchaseOrder)
                ->with('error', 'PO ini berasal dari permintaan Gudang yang telah disetujui. Jika perlu mengubah detail, hapus PO Draft lalu buat ulang dari permintaan yang sama.');
        }

        $purchaseOrder->load([
            'supplier',
            'items.barang',
        ]);

        $suppliers =
            Supplier::query()
                ->where('is_active', true)
                ->orderBy(
                    'nama_supplier'
                )
                ->get();

        $poInitialItems = old('items');

        if (!$poInitialItems) {
            $poInitialItems = $purchaseOrder->items
                ->map(fn ($item): array => [
                    'barang_id' => $item->barang_id,
                    'qty' => (float) $item->qty,
                    'harga_satuan' => (float) $item->harga_satuan,
                    'diskon' => (float) $item->diskon,
                    'keterangan' => $item->keterangan,
                ])
                ->values()
                ->all();
        }

        $selectedBarangIds = collect($poInitialItems)
            ->pluck('barang_id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        $selectedBarangData = $this->formatBarangPickerData(
            Barang::query()
                ->with(['satuan', 'stokGudangs.gudang'])
                ->whereIn('id', $selectedBarangIds)
                ->get()
        );

        return view(
            'purchase-order.edit',
            compact(
                'purchaseOrder',
                'suppliers',
                'selectedBarangData',
                'poInitialItems'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        PurchaseOrder $purchaseOrder
    ) {
        if (
            $purchaseOrder->status
            !== 'Draft'
        ) {
            return redirect()
                ->route(
                    'purchase-order.show',
                    $purchaseOrder
                )
                ->with(
                    'error',
                    'Hanya Purchase Order berstatus Draft yang dapat diedit.'
                );
        }

        $validated =
            $request->validate(
                $this->validationRules()
            );

        DB::transaction(
            function () use (
                $validated,
                $purchaseOrder
            ) {
                /*
                |--------------------------------------------------------------------------
                | LOCK PO
                |--------------------------------------------------------------------------
                */

                $purchaseOrder =
                    PurchaseOrder::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $purchaseOrder->id
                        );

                /*
                 * Cek ulang setelah lock.
                 */
                if (
                    $purchaseOrder->status
                    !== 'Draft'
                ) {
                    throw new RuntimeException(
                        'Purchase Order sudah tidak berstatus Draft.'
                    );
                }

                if (PermintaanPembelian::query()->where('purchase_order_id', $purchaseOrder->id)->exists()) {
                    throw new RuntimeException('PO yang berasal dari permintaan Gudang tidak dapat diedit langsung. Hapus PO Draft dan buat ulang dari permintaan yang disetujui.');
                }

                /*
                |--------------------------------------------------------------------------
                | HITUNG ULANG
                |--------------------------------------------------------------------------
                */

                [
                    $detailData,
                    $totals,
                ] =
                    $this
                        ->buildPurchaseOrderData(
                            $validated,
                            $purchaseOrder
                        );

                /*
                |--------------------------------------------------------------------------
                | UPDATE HEADER
                |--------------------------------------------------------------------------
                */

                $purchaseOrder->update([
                    'supplier_id' =>
                        $validated[
                            'supplier_id'
                        ],

                    'tanggal_po' =>
                        $validated[
                            'tanggal_po'
                        ],

                    'tanggal_estimasi_datang' =>
                        $validated[
                            'tanggal_estimasi_datang'
                        ] ?? null,

                    'subtotal' =>
                        $totals[
                            'subtotal'
                        ],

                    'diskon' =>
                        $totals[
                            'diskon'
                        ],

                    'pajak_persen' =>
                        $totals[
                            'pajak_persen'
                        ],

                    'pajak' =>
                        $totals[
                            'pajak'
                        ],

                    'total' =>
                        $totals[
                            'total'
                        ],

                    'catatan' =>
                        $validated[
                            'catatan'
                        ] ?? null,
                ]);

                /*
                |--------------------------------------------------------------------------
                | REPLACE DETAIL
                |--------------------------------------------------------------------------
                |
                | Aman karena PO masih Draft.
                | Belum boleh ada penerimaan barang.
                |
                */

                $purchaseOrder
                    ->items()
                    ->delete();

                foreach (
                    $detailData
                    as $detail
                ) {
                    $purchaseOrder
                        ->items()
                        ->create(
                            $detail
                        );
                }
            },
            3
        );

        return redirect()
            ->route(
                'purchase-order.show',
                $purchaseOrder
            )
            ->with(
                'success',
                'Purchase Order berhasil diperbarui.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | KIRIM PO
    |--------------------------------------------------------------------------
    */

    public function kirim(
        PurchaseOrder $purchaseOrder
    ) {
        try {
            DB::transaction(
                function () use (
                    $purchaseOrder
                ) {
                    $purchaseOrder =
                        PurchaseOrder::query()
                            ->with('items')
                            ->lockForUpdate()
                            ->findOrFail(
                                $purchaseOrder->id
                            );

                    if (
                        $purchaseOrder->status
                        !== 'Draft'
                    ) {
                        throw new RuntimeException(
                            'Purchase Order ini sudah tidak berstatus Draft.'
                        );
                    }

                    if (
                        $purchaseOrder
                            ->items
                            ->isEmpty()
                    ) {
                        throw new RuntimeException(
                            'Purchase Order tidak memiliki barang.'
                        );
                    }

                    if (!$purchaseOrder->supplier()->where('is_active', true)->exists()) {
                        throw new RuntimeException(
                            'Supplier sudah nonaktif. Aktifkan supplier atau ubah supplier PO sebelum mengirim.'
                        );
                    }

                    if (
                        $purchaseOrder->items()
                            ->whereHas('barang', fn ($query) => $query->where('is_active', false))
                            ->exists()
                    ) {
                        throw new RuntimeException(
                            'PO memiliki barang nonaktif. Aktifkan barang atau perbarui item PO sebelum mengirim.'
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | VALIDASI ITEM
                    |--------------------------------------------------------------------------
                    */

                    foreach (
                        $purchaseOrder->items
                        as $item
                    ) {
                        if (
                            (float) $item->qty
                            <= 0
                        ) {
                            throw new RuntimeException(
                                'Terdapat item Purchase Order dengan Qty tidak valid.'
                            );
                        }

                        if (
                            (float)
                            $item->subtotal
                            < 0
                        ) {
                            throw new RuntimeException(
                                'Terdapat item Purchase Order dengan nilai tidak valid.'
                            );
                        }
                    }

                    if (
                        (float)
                        $purchaseOrder->total
                        < 0
                    ) {
                        throw new RuntimeException(
                            'Total Purchase Order tidak valid.'
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | KIRIM
                    |--------------------------------------------------------------------------
                    */

                    $purchaseOrder->update([
                        'status' =>
                            'Dipesan',
                    ]);
                },
                3
            );

            return redirect()
                ->route(
                    'purchase-order.show',
                    $purchaseOrder
                )
                ->with(
                    'success',
                    'Purchase Order berhasil dikirim. PO sekarang siap untuk proses penerimaan barang.'
                );

        } catch (\Throwable $e) {
            return redirect()
                ->route(
                    'purchase-order.show',
                    $purchaseOrder
                )
                ->with(
                    'error',
                    $e->getMessage()
                );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PDF
    |--------------------------------------------------------------------------
    */

    public function pdf(
        PurchaseOrder $purchaseOrder
    ) {
        $purchaseOrder->load([
            'supplier',
            'creator',
            'items.barang.satuan',
        ]);

        $pdf =
            Pdf::loadView(
                'purchase-order.pdf',
                compact(
                    'purchaseOrder'
                )
            );

        $pdf->setPaper(
            'a4',
            'portrait'
        );

        $safeNumber =
            str_replace(
                [
                    '/',
                    '\\',
                ],
                '-',
                $purchaseOrder->nomor_po
            );

        return $pdf->stream(
            'Purchase-Order-'
            . $safeNumber
            . '.pdf'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE
    |--------------------------------------------------------------------------
    */

    public function destroy(
        PurchaseOrder $purchaseOrder
    ) {
        try {
            DB::transaction(
                function () use (
                    $purchaseOrder
                ) {
                    $purchaseOrder =
                        PurchaseOrder::query()
                            ->lockForUpdate()
                            ->findOrFail(
                                $purchaseOrder->id
                            );

                    /*
                    |--------------------------------------------------------------------------
                    | HANYA DRAFT
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $purchaseOrder->status
                        !== 'Draft'
                    ) {
                        throw new RuntimeException(
                            'Hanya Purchase Order berstatus Draft yang dapat dihapus.'
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | CEK TRANSAKSI TURUNAN
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $purchaseOrder
                            ->penerimaanBarangs()
                            ->exists()
                    ) {
                        throw new RuntimeException(
                            'Purchase Order tidak dapat dihapus karena sudah memiliki Penerimaan Barang.'
                        );
                    }

                    if (
                        $purchaseOrder
                            ->invoiceSuppliers()
                            ->exists()
                    ) {
                        throw new RuntimeException(
                            'Purchase Order tidak dapat dihapus karena sudah memiliki Invoice Supplier.'
                        );
                    }

                    if (
                        $purchaseOrder
                            ->returPembelians()
                            ->exists()
                    ) {
                        throw new RuntimeException(
                            'Purchase Order tidak dapat dihapus karena sudah memiliki Retur Pembelian.'
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | KEMBALIKAN PERMINTAAN JIKA PO DRAFT DIHAPUS
                    |--------------------------------------------------------------------------
                    */

                    $linkedRequest = PermintaanPembelian::query()
                        ->where('purchase_order_id', $purchaseOrder->id)
                        ->where('status', PermintaanPembelian::STATUS_PO_DIBUAT)
                        ->first();

                    if ($linkedRequest) {
                        $linkedRequest->update([
                            'purchase_order_id' => null,
                            'status' => PermintaanPembelian::STATUS_DISETUJUI,
                        ]);

                        $linkedRequest->creator?->notify(new PermintaanPembelianNotification(
                            $linkedRequest->id,
                            $linkedRequest->nomor_permintaan,
                            'po_draft_dihapus',
                            "Draft PO {$purchaseOrder->nomor_po} dihapus. Permintaan {$linkedRequest->nomor_permintaan} kembali disetujui dan dapat dibuatkan PO ulang.",
                        ));
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | DELETE ITEM + HEADER
                    |--------------------------------------------------------------------------
                    */

                    $purchaseOrder
                        ->items()
                        ->delete();

                    $purchaseOrder
                        ->delete();
                },
                3
            );

            return redirect()
                ->route(
                    'purchase-order.index'
                )
                ->with(
                    'success',
                    'Purchase Order berhasil dihapus.'
                );

        } catch (\Throwable $e) {
            return redirect()
                ->route(
                    'purchase-order.index'
                )
                ->with(
                    'error',
                    $e->getMessage()
                );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | VALIDATION RULES
    |--------------------------------------------------------------------------
    */

    private function validationRules():
        array
    {
        return [
            'permintaan_pembelian_id' => [
                'nullable',
                'integer',
                'exists:permintaan_pembelians,id',
            ],

            'supplier_id' => [
                'required',
                'exists:suppliers,id,is_active,1',
            ],

            'tanggal_po' => [
                'required',
                'date',
            ],

            'tanggal_estimasi_datang' => [
                'nullable',
                'date',
                'after_or_equal:tanggal_po',
            ],

            'diskon' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'pajak_persen' => [
                'nullable',
                'numeric',
                'min:0',
                'max:100',
            ],

            'catatan' => [
                'nullable',
                'string',
            ],

            'items' => [
                'required',
                'array',
                'min:1',
            ],

            'items.*.barang_id' => [
                'required',
                'exists:barangs,id,is_active,1',
            ],

            'items.*.qty' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'items.*.harga_satuan' => [
                'required',
                'numeric',
                'min:0',
            ],

            'items.*.diskon' => [
                'nullable',
                'numeric',
                'min:0',
            ],

            'items.*.keterangan' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | BUILD DETAIL + TOTAL
    |--------------------------------------------------------------------------
    */

    private function buildPurchaseOrderData(
        array $validated,
        ?PurchaseOrder $existingPurchaseOrder = null
    ): array {
        /*
        |--------------------------------------------------------------------------
        | CEGAH BARANG DUPLIKAT
        |--------------------------------------------------------------------------
        */

        $barangIds =
            collect(
                $validated['items']
            )
                ->pluck(
                    'barang_id'
                )
                ->filter()
                ->map(
                    fn ($id) =>
                        (int) $id
                );

        if (
            $barangIds->count()
            !==
            $barangIds
                ->unique()
                ->count()
        ) {
            throw ValidationException::withMessages([
                'items' =>
                    'Barang yang sama tidak boleh dimasukkan lebih dari satu kali.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDASI KEANGGOTAAN BARANG PADA SUPPLIER
        |--------------------------------------------------------------------------
        */

        $uniqueBarangIds = $barangIds->unique()->values();

        // Draft PO lama tetap dapat diedit untuk barang yang memang sudah
        // tercatat di PO tersebut, walaupun relasi master supplier-barang
        // baru diperkenalkan setelah PO dibuat.
        $existingBarangIds = collect();
        if (
            $existingPurchaseOrder
            && (int) $existingPurchaseOrder->supplier_id === (int) $validated['supplier_id']
        ) {
            $existingBarangIds = $existingPurchaseOrder->items()
                ->pluck('barang_id')
                ->map(fn ($id): int => (int) $id);
        }

        $barangIdsThatMustBeMapped = $uniqueBarangIds
            ->diff($existingBarangIds)
            ->values();

        $mappedBarangIds = DB::table('barang_supplier')
            ->where('supplier_id', (int) $validated['supplier_id'])
            ->whereIn('barang_id', $barangIdsThatMustBeMapped->all())
            ->pluck('barang_id')
            ->map(fn ($id): int => (int) $id);

        if ($mappedBarangIds->count() !== $barangIdsThatMustBeMapped->count()) {
            throw ValidationException::withMessages([
                'items' => 'Ada barang yang belum terdaftar pada daftar barang supplier terpilih. Periksa relasi barang pada menu Supplier.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | LOAD BARANG SEKALI
        |--------------------------------------------------------------------------
        */

        $barangs =
            Barang::query()
                ->whereIn(
                    'id',
                    $barangIds
                        ->unique()
                        ->values()
                        ->all()
                )
                ->where('is_active', true)
                ->get()
                ->keyBy(
                    'id'
                );

        /*
        |--------------------------------------------------------------------------
        | DETAIL
        |--------------------------------------------------------------------------
        */

        $detailData =
            [];

        $subtotalPO =
            0.0;

        foreach (
            $validated['items']
            as $index => $inputItem
        ) {
            $barangId =
                (int)
                $inputItem[
                    'barang_id'
                ];

            $barang =
                $barangs->get(
                    $barangId
                );

            if (!$barang) {
                throw ValidationException::withMessages([
                    "items.$index.barang_id" =>
                        'Barang tidak ditemukan.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | NORMALISASI
            |--------------------------------------------------------------------------
            */

            $qty =
                round(
                    (float)
                    $inputItem[
                        'qty'
                    ],
                    2
                );

            $hargaSatuan =
                round(
                    (float)
                    $inputItem[
                        'harga_satuan'
                    ],
                    2
                );

            $diskonItem =
                round(
                    (float) (
                        $inputItem[
                            'diskon'
                        ]
                        ?? 0
                    ),
                    2
                );

            /*
            |--------------------------------------------------------------------------
            | VALIDASI
            |--------------------------------------------------------------------------
            */

            if ($qty <= 0) {
                throw ValidationException::withMessages([
                    "items.$index.qty" =>
                        'Qty barang harus lebih besar dari 0.',
                ]);
            }

            if ($hargaSatuan < 0) {
                throw ValidationException::withMessages([
                    "items.$index.harga_satuan" =>
                        'Harga satuan tidak boleh negatif.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | NILAI BRUTO
            |--------------------------------------------------------------------------
            */

            $nilaiBruto =
                round(
                    $qty
                    * $hargaSatuan,
                    2
                );

            /*
            |--------------------------------------------------------------------------
            | DISKON ITEM
            |--------------------------------------------------------------------------
            */

            if (
                $diskonItem
                >
                $nilaiBruto
            ) {
                throw ValidationException::withMessages([
                    "items.$index.diskon" =>
                        'Diskon item tidak boleh melebihi nilai barang.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | SUBTOTAL ITEM
            |--------------------------------------------------------------------------
            */

            $subtotalItem =
                round(
                    $nilaiBruto
                    - $diskonItem,
                    2
                );

            $subtotalPO =
                round(
                    $subtotalPO
                    + $subtotalItem,
                    2
                );

            /*
            |--------------------------------------------------------------------------
            | DETAIL DATA
            |--------------------------------------------------------------------------
            */

            $detailData[] = [
                'barang_id' =>
                    $barang->id,

                'qty' =>
                    $qty,

                'harga_satuan' =>
                    $hargaSatuan,

                'diskon' =>
                    $diskonItem,

                'subtotal' =>
                    $subtotalItem,

                /*
                 * Saat PO baru dibuat,
                 * belum ada penerimaan.
                 */
                'qty_diterima' =>
                    0,

                /*
                 * Belum ada pembatalan sisa.
                 */
                'qty_dibatalkan' =>
                    0,

                'keterangan' =>
                    $inputItem[
                        'keterangan'
                    ] ?? null,
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | DISKON HEADER
        |--------------------------------------------------------------------------
        */

        $diskonPO =
            round(
                (float) (
                    $validated[
                        'diskon'
                    ]
                    ?? 0
                ),
                2
            );

        if (
            $diskonPO
            >
            $subtotalPO
        ) {
            throw ValidationException::withMessages([
                'diskon' =>
                    'Diskon Purchase Order tidak boleh melebihi subtotal barang.',
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | DASAR PAJAK
        |--------------------------------------------------------------------------
        */

        $dasarPajak =
            round(
                $subtotalPO
                - $diskonPO,
                2
            );

        /*
        |--------------------------------------------------------------------------
        | PAJAK %
        |--------------------------------------------------------------------------
        */

        $pajakPersen =
            round(
                (float) (
                    $validated[
                        'pajak_persen'
                    ]
                    ?? 0
                ),
                2
            );

        /*
        |--------------------------------------------------------------------------
        | PAJAK NOMINAL
        |--------------------------------------------------------------------------
        */

        $pajakPO =
            round(
                $dasarPajak
                * $pajakPersen
                / 100,
                2
            );

        /*
        |--------------------------------------------------------------------------
        | TOTAL
        |--------------------------------------------------------------------------
        */

        $totalPO =
            round(
                $dasarPajak
                + $pajakPO,
                2
            );

        return [
            $detailData,

            [
                'subtotal' =>
                    $subtotalPO,

                'diskon' =>
                    $diskonPO,

                'pajak_persen' =>
                    $pajakPersen,

                'pajak' =>
                    $pajakPO,

                'total' =>
                    $totalPO,
            ],
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | GENERATE NOMOR PO
    |--------------------------------------------------------------------------
    |
    | Nomor mengikuti tanggal PO:
    |
    | PO-202609-0001
    |
    */

    private function generateNomorPO(
        ?string $tanggalPO = null,
        bool $lock = false
    ): string {
        $tanggal =
            $tanggalPO
                ? Carbon::parse(
                    $tanggalPO
                )
                : now();

        $yearMonth =
            $tanggal->format(
                'Ym'
            );

        $prefix =
            "PO-{$yearMonth}-";

        $query =
            PurchaseOrder::query()
                ->where(
                    'nomor_po',
                    'like',
                    $prefix . '%'
                )
                ->orderByDesc(
                    'nomor_po'
                );

        /*
         * Saat store dijalankan dalam DB transaction,
         * record terakhir dikunci.
         */
        if ($lock) {
            $query->lockForUpdate();
        }

        $last =
            $query->first();

        $nextNumber =
            1;

        if ($last) {
            $lastNumber =
                (int) substr(
                    $last->nomor_po,
                    -4
                );

            $nextNumber =
                $lastNumber + 1;
        }

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