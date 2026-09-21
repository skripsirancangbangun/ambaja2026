<?php

namespace App\Http\Controllers;

use App\Models\InvoiceSupplier;
use App\Models\InvoiceSupplierItem;
use App\Models\PenerimaanBarang;
use App\Models\PenerimaanBarangItem;
use App\Models\ReturPembelian;
use App\Models\ReturPembelianAllocation;
use App\Models\ReturPembelianDetail;
use App\Models\StokGudang;
use App\Services\InventoryService;
use App\Services\SystemAccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReturPembelianController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $query =
            ReturPembelian::with([
                'penerimaanBarang',
                'purchaseOrder',
                'supplier',
                'gudang',
                'creator',
                'details',
                'allocations.invoiceSupplier',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {
            $search =
                trim($request->search);

            $query->where(
                function ($q) use ($search) {
                    $q
                        ->where(
                            'nomor_retur',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'alasan_retur',
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
                        )
                        ->orWhereHas(
                            'purchaseOrder',
                            function ($poQuery) use ($search) {
                                $poQuery->where(
                                    'nomor_po',
                                    'like',
                                    "%{$search}%"
                                );
                            }
                        )
                        ->orWhereHas(
                            'penerimaanBarang',
                            function ($penerimaanQuery) use ($search) {
                                $penerimaanQuery->where(
                                    'nomor_penerimaan',
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
        | Filter Status
        |--------------------------------------------------------------------------
        */

        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->status
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Filter Tanggal
        |--------------------------------------------------------------------------
        */

        if (
            $request->filled(
                'tanggal_mulai'
            )
        ) {
            $query->whereDate(
                'tanggal_retur',
                '>=',
                $request->tanggal_mulai
            );
        }

        if (
            $request->filled(
                'tanggal_selesai'
            )
        ) {
            $query->whereDate(
                'tanggal_retur',
                '<=',
                $request->tanggal_selesai
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Data
        |--------------------------------------------------------------------------
        */

        $returs =
            $query
                ->latest(
                    'tanggal_retur'
                )
                ->latest('id')
                ->paginate(15)
                ->withQueryString();

        return view(
            'retur-pembelian.index',
            compact('returs')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE
    |--------------------------------------------------------------------------
    */

    public function create(
        Request $request
    ) {
        /*
        |--------------------------------------------------------------------------
        | Penerimaan Barang
        |--------------------------------------------------------------------------
        */

        $penerimaans =
            PenerimaanBarang::with([
                'purchaseOrder.supplier',
                'gudang',
            ])
                ->latest(
                    'tanggal_penerimaan'
                )
                ->latest('id')
                ->get();

        $selectedPenerimaan =
            null;

        $items =
            collect();

        /*
        |--------------------------------------------------------------------------
        | Penerimaan Terpilih
        |--------------------------------------------------------------------------
        */

        if (
            $request->filled(
                'penerimaan_barang_id'
            )
        ) {
            $selectedPenerimaan =
                PenerimaanBarang::with([
                    'purchaseOrder.supplier',
                    'gudang',
                ])
                    ->find(
                        $request
                            ->penerimaan_barang_id
                    );

            if ($selectedPenerimaan) {
                $items =
                    PenerimaanBarangItem::with([
                        'barang.satuan',
                    ])
                        ->where(
                            'penerimaan_barang_id',
                            $selectedPenerimaan->id
                        )
                        ->get()
                        ->map(
                            function ($item) {
                                /*
                                |--------------------------------------------------------------------------
                                | Qty Retur Selesai
                                |--------------------------------------------------------------------------
                                */

                                $qtySudahDiretur =
                                    ReturPembelianDetail::query()
                                        ->where(
                                            'penerimaan_barang_item_id',
                                            $item->id
                                        )
                                        ->whereHas(
                                            'returPembelian',
                                            function ($query) {
                                                $query->where(
                                                    'status',
                                                    'SELESAI'
                                                );
                                            }
                                        )
                                        ->sum('qty');

                                $qtyDiterima =
                                    round(
                                        (float) $item
                                            ->qty_diterima,
                                        2
                                    );

                                $item->qty_sudah_diretur =
                                    round(
                                        (float) $qtySudahDiretur,
                                        2
                                    );

                                $item->qty_bisa_diretur =
                                    max(
                                        0,
                                        round(
                                            $qtyDiterima
                                            - (float) $qtySudahDiretur,
                                            2
                                        )
                                    );

                                return $item;
                            }
                        )
                        ->filter(
                            fn ($item) =>
                                $item
                                    ->qty_bisa_diretur
                                > 0
                        )
                        ->values();
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Preview Nomor
        |--------------------------------------------------------------------------
        */

        $previewNomorRetur =
            $this
                ->generateNomorRetur();

        return view(
            'retur-pembelian.create',
            compact(
                'penerimaans',
                'selectedPenerimaan',
                'items',
                'previewNomorRetur'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | STORE DRAFT
    |--------------------------------------------------------------------------
    */

    public function store(
        Request $request
    ) {
        $validated =
            $request->validate([
                'penerimaan_barang_id' => [
                    'required',
                    'exists:penerimaan_barangs,id',
                ],

                'tanggal_retur' => [
                    'required',
                    'date',
                ],

                'alasan_retur' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'catatan' => [
                    'nullable',
                    'string',
                    'max:2000',
                ],

                'items' => [
                    'required',
                    'array',
                    'min:1',
                ],

                'items.*.penerimaan_barang_item_id' => [
                    'required',
                    'exists:penerimaan_barang_items,id',
                ],

                'items.*.qty' => [
                    'nullable',
                    'numeric',
                    'min:0',
                ],

                'items.*.keterangan' => [
                    'nullable',
                    'string',
                    'max:500',
                ],
            ]);

        /*
        |--------------------------------------------------------------------------
        | Transaction
        |--------------------------------------------------------------------------
        */

        $retur =
            DB::transaction(
                function () use ($validated) {
                    /*
                    |--------------------------------------------------------------------------
                    | Lock Penerimaan
                    |--------------------------------------------------------------------------
                    */

                    $penerimaan =
                        PenerimaanBarang::with([
                            'purchaseOrder',
                        ])
                            ->where(
                                'id',
                                $validated[
                                    'penerimaan_barang_id'
                                ]
                            )
                            ->lockForUpdate()
                            ->firstOrFail();

                    /*
                    |--------------------------------------------------------------------------
                    | PO
                    |--------------------------------------------------------------------------
                    */

                    if (
                        !$penerimaan
                            ->purchaseOrder
                    ) {
                        throw ValidationException::withMessages([
                            'penerimaan_barang_id' =>
                                'Purchase Order dari penerimaan barang tidak ditemukan.',
                        ]);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Tanggal
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $penerimaan
                            ->tanggal_penerimaan
                        &&
                        $validated[
                            'tanggal_retur'
                        ]
                        <
                        $penerimaan
                            ->tanggal_penerimaan
                            ->format('Y-m-d')
                    ) {
                        throw ValidationException::withMessages([
                            'tanggal_retur' =>
                                'Tanggal retur tidak boleh sebelum tanggal penerimaan barang.',
                        ]);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Duplicate Item
                    |--------------------------------------------------------------------------
                    */

                    $itemIds =
                        collect(
                            $validated['items']
                        )
                            ->pluck(
                                'penerimaan_barang_item_id'
                            )
                            ->filter();

                    if (
                        $itemIds->count()
                        !==
                        $itemIds
                            ->unique()
                            ->count()
                    ) {
                        throw ValidationException::withMessages([
                            'items' =>
                                'Barang penerimaan yang sama tidak boleh dimasukkan lebih dari satu kali.',
                        ]);
                    }

                    $detailData =
                        [];

                    /*
                    |--------------------------------------------------------------------------
                    | Detail
                    |--------------------------------------------------------------------------
                    */

                    foreach (
                        $validated['items']
                        as $index => $inputItem
                    ) {
                        $qtyRetur =
                            round(
                                (float) (
                                    $inputItem[
                                        'qty'
                                    ]
                                    ?? 0
                                ),
                                2
                            );

                        if (
                            $qtyRetur <= 0
                        ) {
                            continue;
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Lock Item Penerimaan
                        |--------------------------------------------------------------------------
                        */

                        $penerimaanItem =
                            PenerimaanBarangItem::query()
                                ->where(
                                    'id',
                                    $inputItem[
                                        'penerimaan_barang_item_id'
                                    ]
                                )
                                ->where(
                                    'penerimaan_barang_id',
                                    $penerimaan->id
                                )
                                ->lockForUpdate()
                                ->first();

                        if (
                            !$penerimaanItem
                        ) {
                            throw ValidationException::withMessages([
                                "items.$index.penerimaan_barang_item_id" =>
                                    'Barang tidak berasal dari penerimaan yang dipilih.',
                            ]);
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Retur Sebelumnya
                        |--------------------------------------------------------------------------
                        */

                        $qtySudahDiretur =
                            ReturPembelianDetail::query()
                                ->where(
                                    'penerimaan_barang_item_id',
                                    $penerimaanItem->id
                                )
                                ->whereHas(
                                    'returPembelian',
                                    function ($query) {
                                        $query->where(
                                            'status',
                                            'SELESAI'
                                        );
                                    }
                                )
                                ->sum('qty');

                        $qtyDiterima =
                            round(
                                (float) $penerimaanItem
                                    ->qty_diterima,
                                2
                            );

                        $qtyBisaDiretur =
                            round(
                                $qtyDiterima
                                - (float) $qtySudahDiretur,
                                2
                            );

                        if (
                            $qtyBisaDiretur <= 0
                        ) {
                            throw ValidationException::withMessages([
                                "items.$index.qty" =>
                                    'Barang ini sudah seluruhnya diretur.',
                            ]);
                        }

                        if (
                            $qtyRetur
                            > $qtyBisaDiretur
                        ) {
                            throw ValidationException::withMessages([
                                "items.$index.qty" =>
                                    'Qty retur melebihi qty yang dapat diretur. Maksimal '
                                    . number_format(
                                        $qtyBisaDiretur,
                                        2,
                                        ',',
                                        '.'
                                    )
                                    . '.',
                            ]);
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Nilai Dokumen
                        |--------------------------------------------------------------------------
                        */

                        $hargaSatuan =
                            round(
                                (float) $penerimaanItem
                                    ->harga_satuan,
                                2
                            );

                        $subtotal =
                            round(
                                $qtyRetur
                                * $hargaSatuan,
                                2
                            );

                        $detailData[] = [
                            'penerimaan_barang_item_id' =>
                                $penerimaanItem->id,

                            'barang_id' =>
                                $penerimaanItem->barang_id,

                            'qty' =>
                                $qtyRetur,

                            'harga_satuan' =>
                                $hargaSatuan,

                            'subtotal' =>
                                $subtotal,

                            'keterangan' =>
                                $inputItem[
                                    'keterangan'
                                ]
                                ?? null,
                        ];
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Harus Ada Item
                    |--------------------------------------------------------------------------
                    */

                    if (
                        empty($detailData)
                    ) {
                        throw ValidationException::withMessages([
                            'items' =>
                                'Masukkan qty retur minimal untuk satu barang.',
                        ]);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Header
                    |--------------------------------------------------------------------------
                    */

                    $retur =
                        ReturPembelian::create([
                            'nomor_retur' =>
                                $this
                                    ->generateNomorRetur(),

                            'penerimaan_barang_id' =>
                                $penerimaan->id,

                            'purchase_order_id' =>
                                $penerimaan
                                    ->purchase_order_id,

                            'supplier_id' =>
                                $penerimaan
                                    ->purchaseOrder
                                    ->supplier_id,

                            'gudang_id' =>
                                $penerimaan
                                    ->gudang_id,

                            'tanggal_retur' =>
                                $validated[
                                    'tanggal_retur'
                                ],

                            'alasan_retur' =>
                                $validated[
                                    'alasan_retur'
                                ],

                            'status' =>
                                'DRAFT',

                            'catatan' =>
                                $validated[
                                    'catatan'
                                ]
                                ?? null,

                            'created_by' =>
                                auth()->id(),
                        ]);

                    /*
                    |--------------------------------------------------------------------------
                    | Detail
                    |--------------------------------------------------------------------------
                    */

                    foreach (
                        $detailData
                        as $detail
                    ) {
                        $retur
                            ->details()
                            ->create(
                                $detail
                            );
                    }

                    return $retur;
                }
            );

        return redirect()
            ->route(
                'retur-pembelian.show',
                $retur
            )
            ->with(
                'success',
                'Retur Pembelian berhasil dibuat sebagai DRAFT.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW
    |--------------------------------------------------------------------------
    */

    public function show(
        ReturPembelian $returPembelian
    ) {
        $returPembelian->load([
            'penerimaanBarang',
            'purchaseOrder',
            'supplier',
            'gudang',
            'creator',
            'details.barang.satuan',
            'details.penerimaanBarangItem',
            'allocations.invoiceSupplier',
            'allocations.invoiceSupplierItem',
            'jurnal.details.akun',
        ]);

        return view(
            'retur-pembelian.show',
            compact(
                'returPembelian'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PROSES RETUR
    |--------------------------------------------------------------------------
    */

    public function proses(
        ReturPembelian $returPembelian,
        InventoryService $inventoryService,
        SystemAccountingService $accountingService
    ) {
        /*
        |--------------------------------------------------------------------------
        | Cut-Off Accounting
        |--------------------------------------------------------------------------
        */

        if (
            $accountingService
                ->isPeriodLocked(
                    $returPembelian
                        ->tanggal_retur
                )
        ) {
            return redirect()
                ->route(
                    'retur-pembelian.show',
                    $returPembelian
                )
                ->with(
                    'error',
                    'Retur tidak dapat diproses karena periode accounting pada tanggal tersebut sudah ditutup.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Transaction
        |--------------------------------------------------------------------------
        */

        DB::transaction(
            function () use (
                $returPembelian,
                $inventoryService,
                $accountingService
            ) {
                /*
                |--------------------------------------------------------------------------
                | Lock Header
                |--------------------------------------------------------------------------
                */

                $retur =
                    ReturPembelian::query()
                        ->where(
                            'id',
                            $returPembelian->id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                /*
                |--------------------------------------------------------------------------
                | Status
                |--------------------------------------------------------------------------
                */

                if (
                    $retur->status
                    !== 'DRAFT'
                ) {
                    throw ValidationException::withMessages([
                        'retur' =>
                            'Hanya Retur Pembelian berstatus DRAFT yang dapat diproses.',
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Load Detail
                |--------------------------------------------------------------------------
                */

                $retur->load([
                    'details',
                    'supplier',
                ]);

                if (
                    $retur
                        ->details
                        ->isEmpty()
                ) {
                    throw ValidationException::withMessages([
                        'retur' =>
                            'Retur Pembelian tidak memiliki detail barang.',
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | Validasi Semua Detail
                |--------------------------------------------------------------------------
                */

                foreach (
                    $retur->details
                    as $detail
                ) {
                    /*
                    |--------------------------------------------------------------------------
                    | Lock Penerimaan Item
                    |--------------------------------------------------------------------------
                    */

                    $penerimaanItem =
                        PenerimaanBarangItem::query()
                            ->where(
                                'id',
                                $detail
                                    ->penerimaan_barang_item_id
                            )
                            ->lockForUpdate()
                            ->first();

                    if (
                        !$penerimaanItem
                    ) {
                        throw ValidationException::withMessages([
                            'retur' =>
                                'Detail penerimaan barang tidak ditemukan.',
                        ]);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Pastikan Penerimaan Sesuai
                    |--------------------------------------------------------------------------
                    */

                    if (
                        (int) $penerimaanItem
                            ->penerimaan_barang_id
                        !==
                        (int) $retur
                            ->penerimaan_barang_id
                    ) {
                        throw ValidationException::withMessages([
                            'retur' =>
                                'Terdapat barang retur yang tidak berasal dari penerimaan yang dipilih.',
                        ]);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Qty Retur Yang Sudah Selesai
                    |--------------------------------------------------------------------------
                    */

                    $qtySudahDiretur =
                        ReturPembelianDetail::query()
                            ->where(
                                'penerimaan_barang_item_id',
                                $penerimaanItem->id
                            )
                            ->where(
                                'retur_pembelian_details.id',
                                '!=',
                                $detail->id
                            )
                            ->whereHas(
                                'returPembelian',
                                function ($query) {
                                    $query->where(
                                        'status',
                                        'SELESAI'
                                    );
                                }
                            )
                            ->sum('qty');

                    $qtyDiterima =
                        round(
                            (float) $penerimaanItem
                                ->qty_diterima,
                            2
                        );

                    $qtyMasihBisaDiretur =
                        round(
                            $qtyDiterima
                            - (float) $qtySudahDiretur,
                            2
                        );

                    if (
                        (float) $detail->qty
                        >
                        $qtyMasihBisaDiretur
                    ) {
                        throw ValidationException::withMessages([
                            'retur' =>
                                'Qty retur melebihi sisa qty yang masih dapat diretur. Periksa retur lain yang sudah diproses.',
                        ]);
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Lock Stok Gudang
                    |--------------------------------------------------------------------------
                    */

                    $stok =
                        StokGudang::query()
                            ->where(
                                'barang_id',
                                $detail->barang_id
                            )
                            ->where(
                                'gudang_id',
                                $retur->gudang_id
                            )
                            ->lockForUpdate()
                            ->first();

                    if (!$stok) {
                        throw ValidationException::withMessages([
                            'retur' =>
                                'Stok salah satu barang tidak ditemukan pada gudang penerimaan.',
                        ]);
                    }

                    if (
                        (float) $detail->qty
                        >
                        (float) $stok->qty
                    ) {
                        throw ValidationException::withMessages([
                            'retur' =>
                                'Stok barang tidak mencukupi untuk diproses retur.',
                        ]);
                    }
                }

                /*
                |--------------------------------------------------------------------------
                | Status Diproses
                |--------------------------------------------------------------------------
                */

                $retur->update([
                    'status' =>
                        'DIPROSES',
                ]);

                /*
                |--------------------------------------------------------------------------
                | Buat Alokasi Ke Invoice Supplier
                |--------------------------------------------------------------------------
                |
                | Retur barang dapat terjadi:
                |
                | 1. Sebelum barang ditagihkan supplier.
                |    -> tidak ada koreksi utang.
                |
                | 2. Sesudah barang masuk Invoice Supplier.
                |    -> retur dialokasikan ke invoice terkait.
                |
                */

                foreach (
                    $retur->details
                    as $detail
                ) {
                    $this
                        ->allocateReturToInvoices(
                            $retur,
                            $detail
                        );
                }

                /*
                |--------------------------------------------------------------------------
                | Update Invoice Supplier Yang Terdampak
                |--------------------------------------------------------------------------
                */

                $this
                    ->updateAffectedInvoices(
                        $retur
                    );

                /*
                |--------------------------------------------------------------------------
                | Stok Keluar
                |--------------------------------------------------------------------------
                */

                foreach (
                    $retur->details
                    as $detail
                ) {
                    $inventoryService
                        ->stokKeluar(
                            barangId:
                                (int) $detail
                                    ->barang_id,

                            gudangId:
                                (int) $retur
                                    ->gudang_id,

                            qty:
                                (float) $detail
                                    ->qty,

                            referenceType:
                                'RETUR_PEMBELIAN',

                            referenceId:
                                (int) $detail
                                    ->id,

                            keterangan:
                                'Retur Pembelian '
                                . $retur
                                    ->nomor_retur,

                            tipeMutasi:
                                'RETUR_KELUAR',

                            tanggalTransaksi:
                                $retur
                                    ->tanggal_retur
                        );
                }

                /*
                |--------------------------------------------------------------------------
                | Jurnal
                |--------------------------------------------------------------------------
                |
                | Jurnal hanya terbentuk untuk bagian retur yang
                | sebelumnya sudah masuk Invoice Supplier.
                |
                */

                $accountingService
                    ->autoJournalReturPembelian(
                        $retur
                    );

                /*
                |--------------------------------------------------------------------------
                | Selesai
                |--------------------------------------------------------------------------
                */

                $retur->update([
                    'status' =>
                        'SELESAI',
                ]);
            }
        );

        return redirect()
            ->route(
                'retur-pembelian.show',
                $returPembelian
            )
            ->with(
                'success',
                'Retur Pembelian berhasil diproses. Stok, utang supplier, kredit supplier, dan jurnal accounting telah diperbarui.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | ALLOCATE RETUR TO INVOICE
    |--------------------------------------------------------------------------
    */

    private function allocateReturToInvoices(
        ReturPembelian $retur,
        ReturPembelianDetail $detail
    ): void {
        $qtyReturTersisa =
            round(
                (float) $detail->qty,
                2
            );

        if (
            $qtyReturTersisa <= 0
        ) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Invoice Items Dari Penerimaan Yang Sama
        |--------------------------------------------------------------------------
        |
        | Diurutkan dari invoice paling lama.
        |
        */

        $invoiceItems =
            InvoiceSupplierItem::query()
                ->where(
                    'penerimaan_barang_item_id',
                    $detail
                        ->penerimaan_barang_item_id
                )
                ->orderBy(
                    'invoice_supplier_id'
                )
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

        foreach (
            $invoiceItems
            as $invoiceItem
        ) {
            if (
                $qtyReturTersisa <= 0
            ) {
                break;
            }

            /*
            |--------------------------------------------------------------------------
            | Qty Sudah Dialokasikan Ke Retur Sebelumnya
            |--------------------------------------------------------------------------
            */

            $qtySudahDialokasikan =
                round(
                    (float)
                    ReturPembelianAllocation::query()
                        ->where(
                            'invoice_supplier_item_id',
                            $invoiceItem->id
                        )
                        ->sum('qty'),
                    2
                );

            $qtyInvoice =
                round(
                    (float) $invoiceItem
                        ->qty,
                    2
                );

            $qtyMasihBisaDialokasikan =
                max(
                    0,
                    round(
                        $qtyInvoice
                        - $qtySudahDialokasikan,
                        2
                    )
                );

            if (
                $qtyMasihBisaDialokasikan <= 0
            ) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Qty Alokasi
            |--------------------------------------------------------------------------
            */

            $qtyAlokasi =
                min(
                    $qtyReturTersisa,
                    $qtyMasihBisaDialokasikan
                );

            $qtyAlokasi =
                round(
                    $qtyAlokasi,
                    2
                );

            /*
            |--------------------------------------------------------------------------
            | Nilai Bersih Item Invoice
            |--------------------------------------------------------------------------
            |
            | subtotal InvoiceSupplierItem sudah mengandung
            | pengurangan diskon per-item.
            |
            */

            $subtotalInvoiceItem =
                round(
                    (float) $invoiceItem
                        ->subtotal,
                    2
                );

            $nilaiPerQty =
                $qtyInvoice > 0
                    ? $subtotalInvoiceItem
                        / $qtyInvoice
                    : 0;

            $nilaiBarang =
                round(
                    $nilaiPerQty
                    * $qtyAlokasi,
                    2
                );

            /*
            |--------------------------------------------------------------------------
            | Simpan Alokasi Awal
            |--------------------------------------------------------------------------
            |
            | Diskon header, PPN, potong utang dan kredit supplier
            | dihitung setelah seluruh item dialokasikan.
            |
            */

            ReturPembelianAllocation::create([
                'retur_pembelian_id' =>
                    $retur->id,

                'retur_pembelian_detail_id' =>
                    $detail->id,

                'invoice_supplier_id' =>
                    $invoiceItem
                        ->invoice_supplier_id,

                'invoice_supplier_item_id' =>
                    $invoiceItem->id,

                'qty' =>
                    $qtyAlokasi,

                'nilai_barang' =>
                    $nilaiBarang,

                'diskon_header' =>
                    0,

                'pajak' =>
                    0,

                'total_koreksi' =>
                    0,

                'potong_utang' =>
                    0,

                'kredit_supplier' =>
                    0,
            ]);

            $qtyReturTersisa =
                round(
                    $qtyReturTersisa
                    - $qtyAlokasi,
                    2
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Jika Masih Ada Qty
        |--------------------------------------------------------------------------
        |
        | Ini BUKAN error.
        |
        | Artinya sebagian / seluruh barang belum pernah
        | masuk Invoice Supplier.
        |
        | Barang tetap dikembalikan secara fisik tetapi
        | tidak mengurangi utang.
        |
        */
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE INVOICE TERDAMPAK
    |--------------------------------------------------------------------------
    */

    private function updateAffectedInvoices(
        ReturPembelian $retur
    ): void {
        /*
        |--------------------------------------------------------------------------
        | Invoice IDs
        |--------------------------------------------------------------------------
        */

        $invoiceIds =
            ReturPembelianAllocation::query()
                ->where(
                    'retur_pembelian_id',
                    $retur->id
                )
                ->pluck(
                    'invoice_supplier_id'
                )
                ->unique()
                ->values();

        /*
        |--------------------------------------------------------------------------
        | Tidak Ada Invoice
        |--------------------------------------------------------------------------
        */

        if (
            $invoiceIds
                ->isEmpty()
        ) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Process Setiap Invoice
        |--------------------------------------------------------------------------
        */

        foreach (
            $invoiceIds
            as $invoiceId
        ) {
            /*
            |--------------------------------------------------------------------------
            | Lock Invoice
            |--------------------------------------------------------------------------
            */

            $invoice =
                InvoiceSupplier::query()
                    ->where(
                        'id',
                        $invoiceId
                    )
                    ->lockForUpdate()
                    ->firstOrFail();

            /*
            |--------------------------------------------------------------------------
            | Allocation Retur Saat Ini
            |--------------------------------------------------------------------------
            */

            $currentAllocations =
                ReturPembelianAllocation::query()
                    ->where(
                        'retur_pembelian_id',
                        $retur->id
                    )
                    ->where(
                        'invoice_supplier_id',
                        $invoice->id
                    )
                    ->orderBy('id')
                    ->lockForUpdate()
                    ->get();

            if (
                $currentAllocations
                    ->isEmpty()
            ) {
                continue;
            }

            /*
            |--------------------------------------------------------------------------
            | Allocation Retur Sebelumnya
            |--------------------------------------------------------------------------
            */

            $previousQuery =
                ReturPembelianAllocation::query()
                    ->where(
                        'invoice_supplier_id',
                        $invoice->id
                    )
                    ->where(
                        'retur_pembelian_id',
                        '!=',
                        $retur->id
                    );

            $previousNilaiBarang =
                round(
                    (float)
                    (clone $previousQuery)
                        ->sum(
                            'nilai_barang'
                        ),
                    2
                );

            $previousDiskonHeader =
                round(
                    (float)
                    (clone $previousQuery)
                        ->sum(
                            'diskon_header'
                        ),
                    2
                );

            $previousPajak =
                round(
                    (float)
                    (clone $previousQuery)
                        ->sum('pajak'),
                    2
                );

            /*
            |--------------------------------------------------------------------------
            | Nilai Barang Retur Sekarang
            |--------------------------------------------------------------------------
            */

            $currentNilaiBarang =
                round(
                    (float)
                    $currentAllocations
                        ->sum(
                            'nilai_barang'
                        ),
                    2
                );

            /*
            |--------------------------------------------------------------------------
            | Jangan Melebihi Subtotal Invoice
            |--------------------------------------------------------------------------
            */

            $sisaNilaiBarangInvoice =
                max(
                    0,
                    round(
                        (float) $invoice
                            ->subtotal
                        - $previousNilaiBarang,
                        2
                    )
                );

            if (
                $currentNilaiBarang
                >
                $sisaNilaiBarangInvoice
                + 0.01
            ) {
                throw ValidationException::withMessages([
                    'retur' =>
                        'Nilai retur melebihi nilai barang pada Invoice Supplier '
                        . $invoice
                            ->nomor_invoice_internal
                        . '.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Diskon Header Yang Masih Bisa Direversal
            |--------------------------------------------------------------------------
            */

            $sisaDiskonHeader =
                max(
                    0,
                    round(
                        (float) $invoice->diskon
                        - $previousDiskonHeader,
                        2
                    )
                );

            /*
            |--------------------------------------------------------------------------
            | Hitung Diskon Header Retur
            |--------------------------------------------------------------------------
            */

            if (
                $sisaNilaiBarangInvoice > 0
                &&
                abs(
                    $currentNilaiBarang
                    - $sisaNilaiBarangInvoice
                ) <= 0.01
            ) {
                /*
                | Seluruh sisa barang invoice diretur.
                | Gunakan seluruh sisa diskon supaya tidak ada
                | rounding yang tertinggal.
                */

                $currentDiskonHeader =
                    $sisaDiskonHeader;

            } elseif (
                (float) $invoice
                    ->subtotal
                > 0
                &&
                (float) $invoice
                    ->diskon
                > 0
            ) {
                $currentDiskonHeader =
                    round(
                        (
                            $currentNilaiBarang
                            /
                            (float) $invoice
                                ->subtotal
                        )
                        *
                        (float) $invoice
                            ->diskon,
                        2
                    );

                $currentDiskonHeader =
                    min(
                        $currentDiskonHeader,
                        $sisaDiskonHeader
                    );

            } else {
                $currentDiskonHeader =
                    0;
            }

            /*
            |--------------------------------------------------------------------------
            | Dasar Pajak Retur
            |--------------------------------------------------------------------------
            */

            $currentDasarPajak =
                max(
                    0,
                    round(
                        $currentNilaiBarang
                        - $currentDiskonHeader,
                        2
                    )
                );

            $invoiceDasarPajak =
                max(
                    0,
                    round(
                        (float) $invoice
                            ->subtotal
                        -
                        (float) $invoice
                            ->diskon,
                        2
                    )
                );

            /*
            |--------------------------------------------------------------------------
            | Pajak Tersisa
            |--------------------------------------------------------------------------
            */

            $sisaPajak =
                max(
                    0,
                    round(
                        (float) $invoice->pajak
                        - $previousPajak,
                        2
                    )
                );

            $previousDasarPajak =
                max(
                    0,
                    round(
                        $previousNilaiBarang
                        - $previousDiskonHeader,
                        2
                    )
                );

            $sisaDasarPajak =
                max(
                    0,
                    round(
                        $invoiceDasarPajak
                        - $previousDasarPajak,
                        2
                    )
                );

            /*
            |--------------------------------------------------------------------------
            | Pajak Retur
            |--------------------------------------------------------------------------
            */

            if (
                $sisaDasarPajak > 0
                &&
                abs(
                    $currentDasarPajak
                    - $sisaDasarPajak
                ) <= 0.01
            ) {
                $currentPajak =
                    $sisaPajak;

            } elseif (
                $invoiceDasarPajak > 0
                &&
                (float) $invoice
                    ->pajak
                > 0
            ) {
                $currentPajak =
                    round(
                        (
                            $currentDasarPajak
                            /
                            $invoiceDasarPajak
                        )
                        *
                        (float) $invoice
                            ->pajak,
                        2
                    );

                $currentPajak =
                    min(
                        $currentPajak,
                        $sisaPajak
                    );

            } else {
                $currentPajak =
                    0;
            }

            /*
            |--------------------------------------------------------------------------
            | Total Koreksi Retur
            |--------------------------------------------------------------------------
            */

            $currentTotalKoreksi =
                round(
                    $currentNilaiBarang
                    - $currentDiskonHeader
                    + $currentPajak,
                    2
                );

            /*
            |--------------------------------------------------------------------------
            | Sisa Kewajiban Sebelum Retur
            |--------------------------------------------------------------------------
            */

            $totalReturSebelum =
                round(
                    (float) $invoice
                        ->total_retur,
                    2
                );

            $totalDibayar =
                round(
                    (float) $invoice
                        ->total_dibayar,
                    2
                );

            $kewajibanSebelum =
                max(
                    0,
                    round(
                        (float) $invoice
                            ->total
                        -
                        $totalReturSebelum,
                        2
                    )
                );

            $sisaUtangSebelum =
                max(
                    0,
                    round(
                        $kewajibanSebelum
                        - $totalDibayar,
                        2
                    )
                );

            $kreditSupplierSebelum =
                max(
                    0,
                    round(
                        $totalDibayar
                        - $kewajibanSebelum,
                        2
                    )
                );

            /*
            |--------------------------------------------------------------------------
            | Setelah Retur
            |--------------------------------------------------------------------------
            */

            $totalReturBaru =
                round(
                    $totalReturSebelum
                    + $currentTotalKoreksi,
                    2
                );

            /*
            |--------------------------------------------------------------------------
            | Safety
            |--------------------------------------------------------------------------
            */

            if (
                $totalReturBaru
                >
                (float) $invoice->total
                + 0.01
            ) {
                throw ValidationException::withMessages([
                    'retur' =>
                        'Total koreksi retur melebihi total Invoice Supplier '
                        . $invoice
                            ->nomor_invoice_internal
                        . '.',
                ]);
            }

            if (
                abs(
                    $totalReturBaru
                    - (float) $invoice
                        ->total
                ) <= 0.01
            ) {
                $totalReturBaru =
                    round(
                        (float) $invoice
                            ->total,
                        2
                    );
            }

            $kewajibanBaru =
                max(
                    0,
                    round(
                        (float) $invoice
                            ->total
                        -
                        $totalReturBaru,
                        2
                    )
                );

            $sisaUtangBaru =
                max(
                    0,
                    round(
                        $kewajibanBaru
                        - $totalDibayar,
                        2
                    )
                );

            $kreditSupplierBaru =
                max(
                    0,
                    round(
                        $totalDibayar
                        - $kewajibanBaru,
                        2
                    )
                );

            /*
            |--------------------------------------------------------------------------
            | Nilai Retur Yang Mengurangi Utang
            |--------------------------------------------------------------------------
            */

            $potongUtang =
                max(
                    0,
                    round(
                        $sisaUtangSebelum
                        - $sisaUtangBaru,
                        2
                    )
                );

            /*
            |--------------------------------------------------------------------------
            | Nilai Retur Menjadi Kredit Supplier
            |--------------------------------------------------------------------------
            */

            $tambahanKreditSupplier =
                max(
                    0,
                    round(
                        $kreditSupplierBaru
                        -
                        $kreditSupplierSebelum,
                        2
                    )
                );

            /*
            |--------------------------------------------------------------------------
            | Safety Balance
            |--------------------------------------------------------------------------
            */

            if (
                abs(
                    (
                        $potongUtang
                        +
                        $tambahanKreditSupplier
                    )
                    -
                    $currentTotalKoreksi
                ) > 0.02
            ) {
                throw ValidationException::withMessages([
                    'retur' =>
                        'Pembagian koreksi utang/kredit supplier tidak balance pada '
                        . $invoice
                            ->nomor_invoice_internal
                        . '.',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Sebarkan Diskon + Pajak Ke Allocation
            |--------------------------------------------------------------------------
            */

            $weights =
                $currentAllocations
                    ->map(
                        fn ($allocation) =>
                            (float) $allocation
                                ->nilai_barang
                    )
                    ->all();

            $diskonDistribution =
                $this->distributeAmount(
                    $currentDiskonHeader,
                    $weights
                );

            $taxWeights =
                [];

            foreach (
                $currentAllocations
                as $index => $allocation
            ) {
                $taxWeights[] =
                    max(
                        0,
                        round(
                            (float) $allocation
                                ->nilai_barang
                            -
                            (
                                $diskonDistribution[
                                    $index
                                ]
                                ?? 0
                            ),
                            2
                        )
                    );
            }

            $pajakDistribution =
                $this->distributeAmount(
                    $currentPajak,
                    $taxWeights
                );

            /*
            |--------------------------------------------------------------------------
            | Potong Utang Diberikan Terlebih Dahulu
            |--------------------------------------------------------------------------
            */

            $sisaPotongUtang =
                $potongUtang;

            $sisaKreditSupplier =
                $tambahanKreditSupplier;

            foreach (
                $currentAllocations
                as $index => $allocation
            ) {
                $diskonAllocation =
                    round(
                        (float) (
                            $diskonDistribution[
                                $index
                            ]
                            ?? 0
                        ),
                        2
                    );

                $pajakAllocation =
                    round(
                        (float) (
                            $pajakDistribution[
                                $index
                            ]
                            ?? 0
                        ),
                        2
                    );

                $totalAllocation =
                    round(
                        (float) $allocation
                            ->nilai_barang
                        -
                        $diskonAllocation
                        +
                        $pajakAllocation,
                        2
                    );

                $allocationPotongUtang =
                    min(
                        $totalAllocation,
                        $sisaPotongUtang
                    );

                $allocationPotongUtang =
                    round(
                        $allocationPotongUtang,
                        2
                    );

                $sisaPotongUtang =
                    round(
                        $sisaPotongUtang
                        -
                        $allocationPotongUtang,
                        2
                    );

                $allocationKreditSupplier =
                    round(
                        $totalAllocation
                        -
                        $allocationPotongUtang,
                        2
                    );

                if (
                    $allocationKreditSupplier
                    >
                    $sisaKreditSupplier
                    &&
                    abs(
                        $allocationKreditSupplier
                        -
                        $sisaKreditSupplier
                    ) <= 0.02
                ) {
                    $allocationKreditSupplier =
                        $sisaKreditSupplier;
                }

                $sisaKreditSupplier =
                    max(
                        0,
                        round(
                            $sisaKreditSupplier
                            -
                            $allocationKreditSupplier,
                            2
                        )
                    );

                $allocation->update([
                    'diskon_header' =>
                        $diskonAllocation,

                    'pajak' =>
                        $pajakAllocation,

                    'total_koreksi' =>
                        $totalAllocation,

                    'potong_utang' =>
                        $allocationPotongUtang,

                    'kredit_supplier' =>
                        $allocationKreditSupplier,
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Status Invoice
            |--------------------------------------------------------------------------
            */

            if (
                $sisaUtangBaru <= 0
            ) {
                $status =
                    'LUNAS';

            } elseif (
                $totalDibayar > 0
                ||
                $totalReturBaru > 0
            ) {
                $status =
                    'SEBAGIAN';

            } else {
                $status =
                    'BELUM_LUNAS';
            }

            /*
            |--------------------------------------------------------------------------
            | Update Invoice
            |--------------------------------------------------------------------------
            */

            $invoice->update([
                'total_retur' =>
                    $totalReturBaru,

                'sisa_utang' =>
                    $sisaUtangBaru,

                'kredit_supplier' =>
                    $kreditSupplierBaru,

                'status' =>
                    $status,
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DISTRIBUTE AMOUNT
    |--------------------------------------------------------------------------
    |
    | Membagikan nominal secara proporsional.
    |
    | Selisih rounding diletakkan pada baris terakhir sehingga
    | total selalu sama dengan nominal sumber.
    |
    */

    private function distributeAmount(
        float $amount,
        array $weights
    ): array {
        $amount =
            round(
                $amount,
                2
            );

        $count =
            count($weights);

        if (
            $count === 0
        ) {
            return [];
        }

        if (
            $amount == 0
        ) {
            return array_fill(
                0,
                $count,
                0
            );
        }

        $totalWeight =
            array_sum(
                $weights
            );

        if (
            $totalWeight <= 0
        ) {
            $result =
                array_fill(
                    0,
                    $count,
                    0
                );

            $result[
                $count - 1
            ] =
                $amount;

            return $result;
        }

        $result =
            [];

        $distributed =
            0;

        foreach (
            $weights
            as $index => $weight
        ) {
            if (
                $index
                ===
                $count - 1
            ) {
                $value =
                    round(
                        $amount
                        - $distributed,
                        2
                    );

            } else {
                $value =
                    round(
                        $amount
                        *
                        (
                            (float) $weight
                            /
                            $totalWeight
                        ),
                        2
                    );

                $distributed =
                    round(
                        $distributed
                        + $value,
                        2
                    );
            }

            $result[] =
                $value;
        }

        return $result;
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT
    |--------------------------------------------------------------------------
    */

    public function edit(
        ReturPembelian $returPembelian
    ) {
        if (
            $returPembelian
                ->status
            !== 'DRAFT'
        ) {
            return redirect()
                ->route(
                    'retur-pembelian.show',
                    $returPembelian
                )
                ->with(
                    'error',
                    'Hanya retur berstatus DRAFT yang dapat diedit.'
                );
        }

        return redirect()
            ->route(
                'retur-pembelian.show',
                $returPembelian
            )
            ->with(
                'error',
                'Edit detail retur belum tersedia. Hapus Draft dan buat kembali jika diperlukan.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        ReturPembelian $returPembelian
    ) {
        return redirect()
            ->route(
                'retur-pembelian.show',
                $returPembelian
            )
            ->with(
                'error',
                'Retur Pembelian belum dapat diedit.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | DELETE DRAFT
    |--------------------------------------------------------------------------
    */

    public function destroy(
        ReturPembelian $returPembelian
    ) {
        if (
            $returPembelian
                ->status
            !== 'DRAFT'
        ) {
            return redirect()
                ->route(
                    'retur-pembelian.show',
                    $returPembelian
                )
                ->with(
                    'error',
                    'Hanya Retur Pembelian berstatus DRAFT yang dapat dihapus.'
                );
        }

        DB::transaction(
            function () use (
                $returPembelian
            ) {
                /*
                |--------------------------------------------------------------------------
                | Safety
                |--------------------------------------------------------------------------
                */

                ReturPembelianAllocation::query()
                    ->where(
                        'retur_pembelian_id',
                        $returPembelian->id
                    )
                    ->delete();

                $returPembelian
                    ->details()
                    ->delete();

                $returPembelian
                    ->delete();
            }
        );

        return redirect()
            ->route(
                'retur-pembelian.index'
            )
            ->with(
                'success',
                'Draft Retur Pembelian berhasil dihapus.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | NOMOR RETUR
    |--------------------------------------------------------------------------
    */

    private function generateNomorRetur(): string
    {
        $year =
            now()
                ->format('Y');

        $month =
            now()
                ->format('m');

        $prefix =
            "RET-PB/{$year}/{$month}/";

        $last =
            ReturPembelian::query()
                ->where(
                    'nomor_retur',
                    'like',
                    $prefix . '%'
                )
                ->orderByDesc('id')
                ->first();

        if ($last) {
            $lastNumber =
                (int) substr(
                    $last
                        ->nomor_retur,
                    -4
                );

            $nextNumber =
                $lastNumber
                + 1;

        } else {
            $nextNumber =
                1;
        }

        return
            $prefix
            .
            str_pad(
                $nextNumber,
                4,
                '0',
                STR_PAD_LEFT
            );
    }
}