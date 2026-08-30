<?php

namespace App\Http\Controllers;

use App\Models\InvoiceSupplier;
use App\Models\PenerimaanBarangItem;
use App\Models\PurchaseOrder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InvoiceSupplierController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | DAFTAR INVOICE SUPPLIER
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $invoiceSuppliers = InvoiceSupplier::with([
            'supplier',
            'purchaseOrder',
            'creator',
        ])
            ->latest('tanggal_invoice')
            ->latest('id')
            ->paginate(15);

        return view(
            'invoice-supplier.index',
            compact('invoiceSuppliers')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | FORM BUAT INVOICE SUPPLIER
    |--------------------------------------------------------------------------
    */

    public function create(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | PO YANG SUDAH MEMILIKI PENERIMAAN BARANG
        |--------------------------------------------------------------------------
        */

        $purchaseOrders = PurchaseOrder::with([
            'supplier',
            'items.barang.satuan',
            'penerimaanBarangs.items.barang.satuan',
        ])
            ->whereHas('penerimaanBarangs')
            ->whereIn('status', [
                'Sebagian Diterima',
                'Selesai',
            ])
            ->latest('tanggal_po')
            ->get();


        $selectedPurchaseOrder = null;


        if ($request->filled('purchase_order_id')) {

            $selectedPurchaseOrder = PurchaseOrder::with([
                'supplier',
                'items.barang.satuan',
                'penerimaanBarangs.items.barang.satuan',
            ])
                ->whereHas('penerimaanBarangs')
                ->whereIn('status', [
                    'Sebagian Diterima',
                    'Selesai',
                ])
                ->find($request->purchase_order_id);
        }


        $previewNomorInvoice =
            $this->generateNomorInvoiceInternal();


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
    | SIMPAN INVOICE SUPPLIER
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $validated = $request->validate([

            'purchase_order_id' =>
                'required|exists:purchase_orders,id',

            'nomor_invoice_supplier' =>
                'required|string|max:255',

            'tanggal_invoice' =>
                'required|date',

            'tanggal_jatuh_tempo' =>
                'required|date|after_or_equal:tanggal_invoice',

            'diskon' =>
                'nullable|numeric|min:0',

            'pajak_persen' =>
                'nullable|numeric|min:0|max:100',

            'catatan' =>
                'nullable|string',

            'items' =>
                'required|array|min:1',

            'items.*.penerimaan_barang_item_id' =>
                'required|exists:penerimaan_barang_items,id',

            'items.*.qty' =>
                'required|numeric|min:0.01',

            'items.*.harga_satuan' =>
                'required|numeric|min:0',

            'items.*.diskon' =>
                'nullable|numeric|min:0',
        ]);


        DB::transaction(function () use ($validated) {

            /*
            |--------------------------------------------------------------------------
            | LOCK PURCHASE ORDER
            |--------------------------------------------------------------------------
            */

            $purchaseOrder = PurchaseOrder::with([
                'supplier',
                'penerimaanBarangs.items',
            ])
                ->where(
                    'id',
                    $validated['purchase_order_id']
                )
                ->lockForUpdate()
                ->firstOrFail();


            /*
            |--------------------------------------------------------------------------
            | VALIDASI PO
            |--------------------------------------------------------------------------
            */

            if (
                !in_array(
                    $purchaseOrder->status,
                    [
                        'Sebagian Diterima',
                        'Selesai',
                    ]
                )
            ) {

                throw ValidationException::withMessages([
                    'purchase_order_id' =>
                        'Purchase Order belum memiliki penerimaan barang yang dapat ditagihkan.',
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | CEK DUPLIKAT NOMOR INVOICE SUPPLIER
            |--------------------------------------------------------------------------
            */

            $invoiceExists = InvoiceSupplier::where(
                'supplier_id',
                $purchaseOrder->supplier_id
            )
                ->where(
                    'nomor_invoice_supplier',
                    $validated['nomor_invoice_supplier']
                )
                ->exists();


            if ($invoiceExists) {

                throw ValidationException::withMessages([
                    'nomor_invoice_supplier' =>
                        'Nomor invoice supplier ini sudah pernah dicatat.',
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | KUMPULKAN PENERIMAAN ITEM YANG VALID
            |--------------------------------------------------------------------------
            */

            $penerimaanItemIds =
                $purchaseOrder
                    ->penerimaanBarangs
                    ->flatMap(
                        fn($penerimaan) =>
                            $penerimaan->items
                    )
                    ->pluck('id')
                    ->map(
                        fn($id) => (int) $id
                    )
                    ->all();


            $itemsToInvoice = [];

            $subtotalInvoice = 0;


            foreach (
                $validated['items']
                as $index => $inputItem
            ) {

                $penerimaanItemId =
                    (int) $inputItem[
                        'penerimaan_barang_item_id'
                    ];


                /*
                |--------------------------------------------------------------------------
                | PASTIKAN ITEM BERASAL DARI PENERIMAAN PO TERPILIH
                |--------------------------------------------------------------------------
                */

                if (
                    !in_array(
                        $penerimaanItemId,
                        $penerimaanItemIds
                    )
                ) {

                    throw ValidationException::withMessages([
                        "items.$index.penerimaan_barang_item_id" =>
                            'Item penerimaan tidak berasal dari Purchase Order yang dipilih.',
                    ]);
                }


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
                | CEK QTY YANG SUDAH PERNAH DI-INVOICE
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
                    (float) $inputItem['qty'];


                if ($sisaQtyInvoice <= 0) {

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
                | HARGA DAN DISKON ITEM
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
                            $inputItem['diskon']
                            ?? 0
                        ),
                        2
                    );


                /*
                |--------------------------------------------------------------------------
                | HITUNG SUBTOTAL ITEM
                |--------------------------------------------------------------------------
                */

                $subtotal =
                    round(
                        ($qtyInvoice * $hargaSatuan)
                        - $diskonItem,
                        2
                    );


                if ($subtotal < 0) {
                    $subtotal = 0;
                }


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
            | BULATKAN SUBTOTAL INVOICE
            |--------------------------------------------------------------------------
            */

            $subtotalInvoice =
                round(
                    $subtotalInvoice,
                    2
                );


            /*
            |--------------------------------------------------------------------------
            | DISKON INVOICE
            |--------------------------------------------------------------------------
            */

            $diskon =
                round(
                    (float) (
                        $validated['diskon']
                        ?? 0
                    ),
                    2
                );


            /*
            |--------------------------------------------------------------------------
            | DASAR PAJAK
            |--------------------------------------------------------------------------
            |
            | Subtotal item sudah termasuk pengurangan diskon per item.
            |
            | Dasar pajak:
            |
            | subtotal invoice - diskon invoice
            |
            */

            $dasarPajak =
                round(
                    $subtotalInvoice
                    - $diskon,
                    2
                );


            if ($dasarPajak < 0) {
                $dasarPajak = 0;
            }


            /*
            |--------------------------------------------------------------------------
            | PERSENTASE PAJAK
            |--------------------------------------------------------------------------
            */

            $pajakPersen =
                round(
                    (float) (
                        $validated['pajak_persen']
                        ?? 0
                    ),
                    2
                );


            /*
            |--------------------------------------------------------------------------
            | NOMINAL PAJAK
            |--------------------------------------------------------------------------
            */

            $pajak =
                round(
                    $dasarPajak
                    * $pajakPersen
                    / 100,
                    2
                );


            /*
            |--------------------------------------------------------------------------
            | TOTAL INVOICE
            |--------------------------------------------------------------------------
            */

            $total =
                round(
                    $dasarPajak
                    + $pajak,
                    2
                );


            /*
            |--------------------------------------------------------------------------
            | BUAT HEADER INVOICE
            |--------------------------------------------------------------------------
            */

            $invoiceSupplier =
                InvoiceSupplier::create([

                    'nomor_invoice_internal' =>
                        $this->generateNomorInvoiceInternal(),

                    'nomor_invoice_supplier' =>
                        $validated[
                            'nomor_invoice_supplier'
                        ],

                    'supplier_id' =>
                        $purchaseOrder->supplier_id,

                    'purchase_order_id' =>
                        $purchaseOrder->id,

                    'tanggal_invoice' =>
                        $validated['tanggal_invoice'],

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
                        $validated['catatan']
                        ?? null,

                    'created_by' =>
                        auth()->id(),
                ]);


            /*
            |--------------------------------------------------------------------------
            | SIMPAN DETAIL INVOICE
            |--------------------------------------------------------------------------
            */

            foreach (
                $itemsToInvoice
                as $item
            ) {

                $penerimaanItem =
                    $item['penerimaan_item'];


                $invoiceSupplier
                    ->items()
                    ->create([

                        'barang_id' =>
                            $penerimaanItem
                                ->barang_id,

                        'penerimaan_barang_item_id' =>
                            $penerimaanItem->id,

                        'qty' =>
                            $item['qty'],

                        'harga_satuan' =>
                            $item['harga_satuan'],

                        'diskon' =>
                            $item['diskon'],

                        'subtotal' =>
                            $item['subtotal'],
                    ]);
            }
        });


        return redirect()
            ->route(
                'invoice-supplier.index'
            )
            ->with(
                'success',
                'Invoice Supplier berhasil dicatat dan utang supplier terbentuk.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | DETAIL INVOICE SUPPLIER
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
        ]);


        return view(
            'invoice-supplier.show',
            compact('invoiceSupplier')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | EDIT INVOICE
    |--------------------------------------------------------------------------
    |
    | Untuk sekarang hanya invoice yang belum memiliki
    | pembayaran yang boleh diedit.
    |
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
                'Fitur edit Invoice Supplier akan disiapkan setelah modul pembayaran selesai.'
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
                'Invoice Supplier belum dapat diedit.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | HAPUS INVOICE
    |--------------------------------------------------------------------------
    */

    public function destroy(
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
                    'Invoice yang sudah memiliki pembayaran tidak dapat dihapus.'
                );
        }


        DB::transaction(function () use (
            $invoiceSupplier
        ) {

            $invoiceSupplier
                ->items()
                ->delete();

            $invoiceSupplier
                ->delete();
        });


        return redirect()
            ->route(
                'invoice-supplier.index'
            )
            ->with(
                'success',
                'Invoice Supplier berhasil dihapus.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | GENERATE NOMOR INVOICE INTERNAL
    |--------------------------------------------------------------------------
    |
    | Contoh:
    | INV-SUP/2026/08/0001
    |
    */

    private function generateNomorInvoiceInternal(): string
    {
        $year =
            now()->format('Y');

        $month =
            now()->format('m');


        $prefix =
            "INV-SUP/{$year}/{$month}/";


        $last =
            InvoiceSupplier::where(
                'nomor_invoice_internal',
                'like',
                $prefix . '%'
            )
                ->orderByDesc('id')
                ->first();


        if ($last) {

            $lastNumber =
                (int) substr(
                    $last->nomor_invoice_internal,
                    -4
                );

            $nextNumber =
                $lastNumber + 1;

        } else {

            $nextNumber = 1;
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