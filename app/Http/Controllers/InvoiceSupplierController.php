<?php

namespace App\Http\Controllers;

use App\Models\InvoiceSupplier;
use App\Models\PenerimaanBarangItem;
use App\Models\PurchaseOrder;
use App\Services\SystemAccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceSupplierController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $invoiceSuppliers =
            InvoiceSupplier::with([
                'supplier',
                'purchaseOrder',
                'creator',
            ])
                ->latest(
                    'tanggal_invoice'
                )
                ->latest('id')
                ->paginate(15);

        return view(
            'invoice-supplier.index',
            compact(
                'invoiceSuppliers'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE
    |--------------------------------------------------------------------------
    */

    public function create(
        Request $request,
        SystemAccountingService $accountingService
    ) {
        /*
        |--------------------------------------------------------------------------
        | PO Yang Sudah Memiliki Penerimaan
        |--------------------------------------------------------------------------
        */

        $purchaseOrders =
            PurchaseOrder::with([
                'supplier',
                'items.barang.satuan',
                'penerimaanBarangs.items.barang.satuan',
            ])
                ->whereHas(
                    'penerimaanBarangs'
                )
                ->whereIn(
                    'status',
                    [
                        'Sebagian Diterima',
                        'Selesai',
                    ]
                )
                ->latest(
                    'tanggal_po'
                )
                ->get();

        /*
        |--------------------------------------------------------------------------
        | Selected PO
        |--------------------------------------------------------------------------
        */

        $selectedPurchaseOrder =
            null;

        if (
            $request->filled(
                'purchase_order_id'
            )
        ) {
            $selectedPurchaseOrder =
                PurchaseOrder::with([
                    'supplier',
                    'items.barang.satuan',
                    'penerimaanBarangs.items.barang.satuan',
                ])
                    ->whereHas(
                        'penerimaanBarangs'
                    )
                    ->whereIn(
                        'status',
                        [
                            'Sebagian Diterima',
                            'Selesai',
                        ]
                    )
                    ->find(
                        $request
                            ->purchase_order_id
                    );
        }

        /*
        |--------------------------------------------------------------------------
        | Preview Nomor
        |--------------------------------------------------------------------------
        */

        $previewNomorInvoice =
            $accountingService
                ->generateDocumentNumber(
                    'INVOICE_SUPPLIER'
                );

        return view(
            'invoice-supplier.create',
            compact(
                'purchaseOrders',
                'selectedPurchaseOrder',
                'previewNomorInvoice'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    */

    public function store(
        Request $request,
        SystemAccountingService $accountingService
    ) {
        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        $validated =
            $request->validate([
                'purchase_order_id' => [
                    'required',
                    'exists:purchase_orders,id',
                ],

                'nomor_invoice_supplier' => [
                    'required',
                    'string',
                    'max:255',
                ],

                'tanggal_invoice' => [
                    'required',
                    'date',
                ],

                'tanggal_jatuh_tempo' => [
                    'required',
                    'date',
                    'after_or_equal:tanggal_invoice',
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

                'items.*.penerimaan_barang_item_id' => [
                    'required',
                    'distinct',
                    'exists:penerimaan_barang_items,id',
                ],

                'items.*.qty' => [
                    'required',
                    'numeric',
                    'min:0.01',
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
            ]);

        /*
        |--------------------------------------------------------------------------
        | Cut-Off
        |--------------------------------------------------------------------------
        */

        if (
            $accountingService
                ->isPeriodLocked(
                    $validated[
                        'tanggal_invoice'
                    ]
                )
        ) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Invoice Supplier tidak dapat dibuat karena periode accounting pada tanggal tersebut sudah DITUTUP.'
                );
        }

        try {
            $invoiceSupplier =
                DB::transaction(
                    function () use (
                        $validated,
                        $accountingService
                    ) {
                        /*
                        |--------------------------------------------------------------------------
                        | Lock PO
                        |--------------------------------------------------------------------------
                        */

                        $purchaseOrder =
                            PurchaseOrder::with([
                                'supplier',
                                'penerimaanBarangs.items',
                            ])
                                ->where(
                                    'id',
                                    $validated[
                                        'purchase_order_id'
                                    ]
                                )
                                ->lockForUpdate()
                                ->firstOrFail();

                        /*
                        |--------------------------------------------------------------------------
                        | Validasi PO
                        |--------------------------------------------------------------------------
                        */

                        if (
                            !in_array(
                                $purchaseOrder
                                    ->status,
                                [
                                    'Sebagian Diterima',
                                    'Selesai',
                                ],
                                true
                            )
                        ) {
                            throw ValidationException::withMessages([
                                'purchase_order_id' =>
                                    'Purchase Order belum memiliki penerimaan barang yang dapat ditagihkan.',
                            ]);
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Nomor Invoice Supplier Tidak Boleh Double
                        |--------------------------------------------------------------------------
                        |
                        | Nomor supplier boleh sama antar supplier berbeda,
                        | tetapi tidak boleh sama pada supplier yang sama.
                        |
                        */

                        $invoiceExists =
                            InvoiceSupplier::query()
                                ->where(
                                    'supplier_id',
                                    $purchaseOrder
                                        ->supplier_id
                                )
                                ->where(
                                    'nomor_invoice_supplier',
                                    $validated[
                                        'nomor_invoice_supplier'
                                    ]
                                )
                                ->exists();

                        if ($invoiceExists) {
                            throw ValidationException::withMessages([
                                'nomor_invoice_supplier' =>
                                    'Nomor invoice supplier ini sudah pernah dicatat untuk supplier yang sama.',
                            ]);
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Item Penerimaan Valid Milik PO
                        |--------------------------------------------------------------------------
                        */

                        $penerimaanItemIds =
                            $purchaseOrder
                                ->penerimaanBarangs
                                ->flatMap(
                                    fn ($penerimaan) =>
                                        $penerimaan
                                            ->items
                                )
                                ->pluck('id')
                                ->map(
                                    fn ($id) =>
                                        (int) $id
                                )
                                ->all();

                        $itemsToInvoice =
                            [];

                        $subtotalInvoice =
                            0;

                        /*
                        |--------------------------------------------------------------------------
                        | Proses Item
                        |--------------------------------------------------------------------------
                        */

                        foreach (
                            $validated['items']
                            as $index =>
                                $inputItem
                        ) {
                            $penerimaanItemId =
                                (int) $inputItem[
                                    'penerimaan_barang_item_id'
                                ];

                            if (
                                !in_array(
                                    $penerimaanItemId,
                                    $penerimaanItemIds,
                                    true
                                )
                            ) {
                                throw ValidationException::withMessages([
                                    "items.$index.penerimaan_barang_item_id" =>
                                        'Item penerimaan tidak berasal dari Purchase Order yang dipilih.',
                                ]);
                            }

                            /*
                            |--------------------------------------------------------------------------
                            | Lock Penerimaan Item
                            |--------------------------------------------------------------------------
                            */

                            $penerimaanItem =
                                PenerimaanBarangItem::with([
                                    'barang',
                                    'purchaseOrderItem',
                                ])
                                    ->where(
                                        'id',
                                        $penerimaanItemId
                                    )
                                    ->lockForUpdate()
                                    ->firstOrFail();

                            /*
                            |--------------------------------------------------------------------------
                            | Qty Sudah Invoice
                            |--------------------------------------------------------------------------
                            */

                            $qtySudahInvoice =
                                (float) $penerimaanItem
                                    ->invoiceSupplierItems()
                                    ->sum('qty');

                            $qtyDiterima =
                                (float) $penerimaanItem
                                    ->qty_diterima;

                            $sisaQtyInvoice =
                                $qtyDiterima
                                - $qtySudahInvoice;

                            $qtyInvoice =
                                (float) $inputItem[
                                    'qty'
                                ];

                            if (
                                $sisaQtyInvoice
                                <= 0
                            ) {
                                throw ValidationException::withMessages([
                                    "items.$index.qty" =>
                                        'Item penerimaan ini sudah seluruhnya ditagihkan.',
                                ]);
                            }

                            if (
                                $qtyInvoice
                                > $sisaQtyInvoice
                            ) {
                                throw ValidationException::withMessages([
                                    "items.$index.qty" =>
                                        'Qty invoice melebihi qty yang belum ditagihkan. Sisa: '
                                        . number_format(
                                            $sisaQtyInvoice,
                                            2,
                                            ',',
                                            '.'
                                        ),
                                ]);
                            }

                            /*
                            |--------------------------------------------------------------------------
                            | Harga
                            |--------------------------------------------------------------------------
                            */

                            $hargaSatuan =
                                round(
                                    (float) $inputItem[
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
                            | Gross
                            |--------------------------------------------------------------------------
                            */

                            $grossItem =
                                round(
                                    $qtyInvoice
                                    * $hargaSatuan,
                                    2
                                );

                            /*
                            |--------------------------------------------------------------------------
                            | Diskon Tidak Boleh Lebih Besar
                            |--------------------------------------------------------------------------
                            */

                            if (
                                $diskonItem
                                > $grossItem
                            ) {
                                throw ValidationException::withMessages([
                                    "items.$index.diskon" =>
                                        'Diskon item tidak boleh lebih besar dari nilai barang.',
                                ]);
                            }

                            /*
                            |--------------------------------------------------------------------------
                            | Subtotal Item
                            |--------------------------------------------------------------------------
                            */

                            $subtotal =
                                round(
                                    $grossItem
                                    - $diskonItem,
                                    2
                                );

                            $subtotalInvoice +=
                                $subtotal;

                            $itemsToInvoice[] = [
                                'penerimaan_item' =>
                                    $penerimaanItem,

                                'qty' =>
                                    $qtyInvoice,

                                'harga_satuan' =>
                                    $hargaSatuan,

                                'diskon' =>
                                    $diskonItem,

                                'subtotal' =>
                                    $subtotal,
                            ];
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Subtotal
                        |--------------------------------------------------------------------------
                        */

                        $subtotalInvoice =
                            round(
                                $subtotalInvoice,
                                2
                            );

                        if (
                            $subtotalInvoice
                            <= 0
                        ) {
                            throw ValidationException::withMessages([
                                'items' =>
                                    'Subtotal Invoice Supplier harus lebih besar dari 0.',
                            ]);
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Diskon Header
                        |--------------------------------------------------------------------------
                        */

                        $diskon =
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
                            $diskon
                            > $subtotalInvoice
                        ) {
                            throw ValidationException::withMessages([
                                'diskon' =>
                                    'Diskon invoice tidak boleh lebih besar dari subtotal invoice.',
                            ]);
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | DPP
                        |--------------------------------------------------------------------------
                        */

                        $dasarPajak =
                            round(
                                $subtotalInvoice
                                - $diskon,
                                2
                            );

                        /*
                        |--------------------------------------------------------------------------
                        | Pajak
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

                        $pajak =
                            round(
                                $dasarPajak
                                * $pajakPersen
                                / 100,
                                2
                            );

                        /*
                        |--------------------------------------------------------------------------
                        | Total
                        |--------------------------------------------------------------------------
                        */

                        $total =
                            round(
                                $dasarPajak
                                + $pajak,
                                2
                            );

                        if ($total <= 0) {
                            throw ValidationException::withMessages([
                                'items' =>
                                    'Total Invoice Supplier harus lebih besar dari 0.',
                            ]);
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Nomor Internal
                        |--------------------------------------------------------------------------
                        */

                        $nomorInternal =
                            $accountingService
                                ->generateDocumentNumber(
                                    'INVOICE_SUPPLIER',
                                    $validated[
                                        'tanggal_invoice'
                                    ]
                                );

                        /*
                        |--------------------------------------------------------------------------
                        | Double Safety Nomor Internal
                        |--------------------------------------------------------------------------
                        */

                        if (
                            InvoiceSupplier::query()
                                ->where(
                                    'nomor_invoice_internal',
                                    $nomorInternal
                                )
                                ->exists()
                        ) {
                            throw ValidationException::withMessages([
                                'nomor_invoice_supplier' =>
                                    'Nomor internal invoice sudah digunakan. Silakan simpan kembali.',
                            ]);
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Create Header
                        |--------------------------------------------------------------------------
                        */

                        $invoiceSupplier =
                            InvoiceSupplier::create([
                                'nomor_invoice_internal' =>
                                    $nomorInternal,

                                'nomor_invoice_supplier' =>
                                    $validated[
                                        'nomor_invoice_supplier'
                                    ],

                                'supplier_id' =>
                                    $purchaseOrder
                                        ->supplier_id,

                                'purchase_order_id' =>
                                    $purchaseOrder
                                        ->id,

                                'tanggal_invoice' =>
                                    $validated[
                                        'tanggal_invoice'
                                    ],

                                'tanggal_jatuh_tempo' =>
                                    $validated[
                                        'tanggal_jatuh_tempo'
                                    ],

                                'subtotal' =>
                                    $subtotalInvoice,

                                'diskon' =>
                                    $diskon,

                                'pajak_persen' =>
                                    $pajakPersen,

                                'pajak' =>
                                    $pajak,

                                'total' =>
                                    $total,

                                'total_dibayar' =>
                                    0,

                                'sisa_utang' =>
                                    $total,

                                'status' =>
                                    'BELUM_LUNAS',

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
                            $itemsToInvoice
                            as $item
                        ) {
                            $penerimaanItem =
                                $item[
                                    'penerimaan_item'
                                ];

                            $invoiceSupplier
                                ->items()
                                ->create([
                                    'barang_id' =>
                                        $penerimaanItem
                                            ->barang_id,

                                    'penerimaan_barang_item_id' =>
                                        $penerimaanItem
                                            ->id,

                                    'qty' =>
                                        $item['qty'],

                                    'harga_satuan' =>
                                        $item[
                                            'harga_satuan'
                                        ],

                                    'diskon' =>
                                        $item['diskon'],

                                    'subtotal' =>
                                        $item[
                                            'subtotal'
                                        ],
                                ]);
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Auto Journal
                        |--------------------------------------------------------------------------
                        |
                        | Dr Pembelian / Persediaan
                        | Dr PPN Masukan
                        |     Cr Diskon Pembelian
                        |     Cr Utang Dagang
                        |
                        */

                        $accountingService
                            ->autoJournalInvoiceSupplier(
                                $invoiceSupplier
                            );

                        return $invoiceSupplier;
                    }
                );

        } catch (
            ValidationException $e
        ) {
            throw $e;

        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Gagal mencatat Invoice Supplier: '
                    . $e->getMessage()
                );
        }

        return redirect()
            ->route(
                'invoice-supplier.show',
                $invoiceSupplier
            )
            ->with(
                'success',
                'Invoice Supplier berhasil dicatat. Utang Supplier dan jurnal accounting otomatis telah terbentuk.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW
    |--------------------------------------------------------------------------
    */

    public function show(
        InvoiceSupplier $invoiceSupplier
    ) {
        $invoiceSupplier->load([
            'supplier',
            'purchaseOrder',
            'creator',
            'items.barang.satuan',
            'items.penerimaanBarangItem.penerimaanBarang',
            'pembayaranSuppliers.rekeningKasBank',
        ]);

        return view(
            'invoice-supplier.show',
            compact(
                'invoiceSupplier'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT
    |--------------------------------------------------------------------------
    */

    public function edit(
        InvoiceSupplier $invoiceSupplier
    ) {
        if (
            (float) $invoiceSupplier
                ->total_dibayar
            > 0
        ) {
            return redirect()
                ->route(
                    'invoice-supplier.show',
                    $invoiceSupplier
                )
                ->with(
                    'error',
                    'Invoice yang sudah memiliki pembayaran tidak dapat diedit.'
                );
        }

        return redirect()
            ->route(
                'invoice-supplier.show',
                $invoiceSupplier
            )
            ->with(
                'error',
                'Invoice Supplier yang sudah diposting ke accounting tidak dapat diedit langsung.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        InvoiceSupplier $invoiceSupplier
    ) {
        return redirect()
            ->route(
                'invoice-supplier.show',
                $invoiceSupplier
            )
            ->with(
                'error',
                'Invoice Supplier yang sudah diposting tidak dapat diedit langsung.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | DESTROY
    |--------------------------------------------------------------------------
    |
    | Jangan hard delete karena invoice sudah:
    |
    | - membentuk Utang
    | - membentuk jurnal accounting
    |
    */

    public function destroy(
        InvoiceSupplier $invoiceSupplier
    ) {
        return redirect()
            ->route(
                'invoice-supplier.show',
                $invoiceSupplier
            )
            ->with(
                'error',
                'Invoice Supplier tidak dapat dihapus langsung karena sudah memengaruhi Utang dan jurnal accounting.'
            );
    }
}