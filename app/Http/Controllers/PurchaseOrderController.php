<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PurchaseOrderController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $query = PurchaseOrder::with([
            'supplier',
            'creator',
            'items',
        ]);

        if ($request->filled('search')) {

            $search = $request->search;

            $query->where(function ($q) use ($search) {

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
            });
        }


        if ($request->filled('status')) {

            $query->where(
                'status',
                $request->status
            );
        }


        if ($request->filled('tanggal_mulai')) {

            $query->whereDate(
                'tanggal_po',
                '>=',
                $request->tanggal_mulai
            );
        }


        if ($request->filled('tanggal_selesai')) {

            $query->whereDate(
                'tanggal_po',
                '<=',
                $request->tanggal_selesai
            );
        }


        $purchaseOrders = $query
            ->latest('tanggal_po')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();


        return view(
            'purchase-order.index',
            compact('purchaseOrders')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CREATE
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        $suppliers = Supplier::orderBy(
            'nama_supplier'
        )->get();


        $barangs = Barang::with([
            'kategori',
            'satuan',
        ])
            ->where(
                'is_active',
                true
            )
            ->orderBy('nama_barang')
            ->get();


        $previewNomorPO =
            $this->generateNomorPO();


        return view(
            'purchase-order.create',
            compact(
                'suppliers',
                'barangs',
                'previewNomorPO'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $validated = $request->validate([

            'supplier_id' =>
                'required|exists:suppliers,id',

            'tanggal_po' =>
                'required|date',

            'tanggal_estimasi_datang' =>
                'nullable|date|after_or_equal:tanggal_po',

            'diskon' =>
                'nullable|numeric|min:0',

            /*
            |--------------------------------------------------------------------------
            | PAJAK DALAM PERSEN
            |--------------------------------------------------------------------------
            */

            'pajak_persen' =>
                'nullable|numeric|min:0|max:100',

            'catatan' =>
                'nullable|string',

            'items' =>
                'required|array|min:1',

            'items.*.barang_id' =>
                'required|exists:barangs,id',

            'items.*.qty' =>
                'required|numeric|min:0.01',

            'items.*.harga_satuan' =>
                'required|numeric|min:0',

            'items.*.diskon' =>
                'nullable|numeric|min:0',

            'items.*.keterangan' =>
                'nullable|string|max:500',
        ]);


        $purchaseOrder = DB::transaction(
            function () use ($validated) {

                /*
                |--------------------------------------------------------------------------
                | CEGAH BARANG DUPLIKAT
                |--------------------------------------------------------------------------
                */

                $barangIds =
                    collect($validated['items'])
                        ->pluck('barang_id')
                        ->filter();


                if (
                    $barangIds->count()
                    !==
                    $barangIds->unique()->count()
                ) {

                    throw ValidationException::withMessages([
                        'items' =>
                            'Barang yang sama tidak boleh dimasukkan lebih dari satu kali.',
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | HITUNG DETAIL
                |--------------------------------------------------------------------------
                */

                $detailData = [];

                $subtotalPO = 0;


                foreach (
                    $validated['items']
                    as $index => $inputItem
                ) {

                    $barang =
                        Barang::find(
                            $inputItem['barang_id']
                        );


                    if (!$barang) {

                        throw ValidationException::withMessages([
                            "items.$index.barang_id" =>
                                'Barang tidak ditemukan.',
                        ]);
                    }


                    $qty =
                        round(
                            (float) $inputItem['qty'],
                            2
                        );


                    $hargaSatuan =
                        round(
                            (float) $inputItem['harga_satuan'],
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


                    $subtotalItem =
                        round(
                            ($qty * $hargaSatuan)
                            - $diskonItem,
                            2
                        );


                    if ($subtotalItem < 0) {
                        $subtotalItem = 0;
                    }


                    $subtotalPO +=
                        $subtotalItem;


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

                        'qty_diterima' =>
                            0,

                        /*
                         * Sudah digunakan oleh fitur
                         * Batalkan Sisa PO.
                         */
                        'qty_dibatalkan' =>
                            0,

                        'keterangan' =>
                            $inputItem['keterangan']
                            ?? null,
                    ];
                }


                $subtotalPO =
                    round(
                        $subtotalPO,
                        2
                    );


                /*
                |--------------------------------------------------------------------------
                | TOTAL HEADER
                |--------------------------------------------------------------------------
                */

                $diskonPO =
                    round(
                        (float) (
                            $validated['diskon']
                            ?? 0
                        ),
                        2
                    );


                /*
                 * Dasar pajak dihitung setelah Diskon PO.
                 *
                 * Subtotal Barang - Diskon PO
                 */
                $dasarPajak =
                    round(
                        $subtotalPO
                        - $diskonPO,
                        2
                    );


                if ($dasarPajak < 0) {
                    $dasarPajak = 0;
                }


                /*
                |--------------------------------------------------------------------------
                | PAJAK PERSENTASE
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
                 * Contoh:
                 *
                 * Dasar Pajak = 1.000.000
                 * Pajak       = 11%
                 *
                 * Nominal Pajak = 110.000
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
                | TOTAL PO
                |--------------------------------------------------------------------------
                */

                $totalPO =
                    round(
                        $dasarPajak
                        + $pajakPO,
                        2
                    );


                if ($totalPO < 0) {
                    $totalPO = 0;
                }


                /*
                |--------------------------------------------------------------------------
                | HEADER
                |--------------------------------------------------------------------------
                */

                $purchaseOrder =
                    PurchaseOrder::create([

                        'nomor_po' =>
                            $this->generateNomorPO(),

                        'supplier_id' =>
                            $validated['supplier_id'],

                        'tanggal_po' =>
                            $validated['tanggal_po'],

                        'tanggal_estimasi_datang' =>
                            $validated[
                                'tanggal_estimasi_datang'
                            ] ?? null,

                        'subtotal' =>
                            $subtotalPO,

                        'diskon' =>
                            $diskonPO,

                        /*
                         * pajak_persen = persentase
                         * pajak        = nominal Rupiah
                         */
                        'pajak_persen' =>
                            $pajakPersen,

                        'pajak' =>
                            $pajakPO,

                        'total' =>
                            $totalPO,

                        'status' =>
                            'Draft',

                        'catatan' =>
                            $validated['catatan']
                            ?? null,

                        'created_by' =>
                            auth()->id(),
                    ]);


                /*
                |--------------------------------------------------------------------------
                | DETAIL
                |--------------------------------------------------------------------------
                */

                foreach ($detailData as $detail) {

                    $purchaseOrder
                        ->items()
                        ->create($detail);
                }


                return $purchaseOrder;
            }
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
        ]);


        return view(
            'purchase-order.show',
            compact('purchaseOrder')
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


        $purchaseOrder->load([
            'supplier',
            'items.barang',
        ]);


        $suppliers = Supplier::orderBy(
            'nama_supplier'
        )->get();


        $barangs = Barang::with([
            'kategori',
            'satuan',
        ])
            ->where(
                'is_active',
                true
            )
            ->orderBy('nama_barang')
            ->get();


        return view(
            'purchase-order.edit',
            compact(
                'purchaseOrder',
                'suppliers',
                'barangs'
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


        $validated = $request->validate([

            'supplier_id' =>
                'required|exists:suppliers,id',

            'tanggal_po' =>
                'required|date',

            'tanggal_estimasi_datang' =>
                'nullable|date|after_or_equal:tanggal_po',

            'diskon' =>
                'nullable|numeric|min:0',

            /*
            |--------------------------------------------------------------------------
            | PAJAK DALAM PERSEN
            |--------------------------------------------------------------------------
            */

            'pajak_persen' =>
                'nullable|numeric|min:0|max:100',

            'catatan' =>
                'nullable|string',

            'items' =>
                'required|array|min:1',

            'items.*.barang_id' =>
                'required|exists:barangs,id',

            'items.*.qty' =>
                'required|numeric|min:0.01',

            'items.*.harga_satuan' =>
                'required|numeric|min:0',

            'items.*.diskon' =>
                'nullable|numeric|min:0',

            'items.*.keterangan' =>
                'nullable|string|max:500',
        ]);


        DB::transaction(
            function () use (
                $validated,
                $purchaseOrder
            ) {

                /*
                |--------------------------------------------------------------------------
                | CEGAH BARANG DUPLIKAT
                |--------------------------------------------------------------------------
                */

                $barangIds =
                    collect($validated['items'])
                        ->pluck('barang_id')
                        ->filter();


                if (
                    $barangIds->count()
                    !==
                    $barangIds->unique()->count()
                ) {

                    throw ValidationException::withMessages([
                        'items' =>
                            'Barang yang sama tidak boleh dimasukkan lebih dari satu kali.',
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | HITUNG DETAIL
                |--------------------------------------------------------------------------
                */

                $detailData = [];

                $subtotalPO = 0;


                foreach (
                    $validated['items']
                    as $index => $inputItem
                ) {

                    $barang =
                        Barang::find(
                            $inputItem['barang_id']
                        );


                    if (!$barang) {

                        throw ValidationException::withMessages([
                            "items.$index.barang_id" =>
                                'Barang tidak ditemukan.',
                        ]);
                    }


                    $qty =
                        round(
                            (float) $inputItem['qty'],
                            2
                        );


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


                    $subtotalItem =
                        round(
                            ($qty * $hargaSatuan)
                            - $diskonItem,
                            2
                        );


                    if ($subtotalItem < 0) {
                        $subtotalItem = 0;
                    }


                    $subtotalPO +=
                        $subtotalItem;


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

                        'qty_diterima' =>
                            0,

                        'qty_dibatalkan' =>
                            0,

                        'keterangan' =>
                            $inputItem[
                                'keterangan'
                            ] ?? null,
                    ];
                }


                $subtotalPO =
                    round(
                        $subtotalPO,
                        2
                    );


                /*
                |--------------------------------------------------------------------------
                | TOTAL HEADER
                |--------------------------------------------------------------------------
                */

                $diskonPO =
                    round(
                        (float) (
                            $validated['diskon']
                            ?? 0
                        ),
                        2
                    );


                /*
                 * Dasar pajak:
                 *
                 * Subtotal - Diskon PO
                 */
                $dasarPajak =
                    round(
                        $subtotalPO
                        - $diskonPO,
                        2
                    );


                if ($dasarPajak < 0) {
                    $dasarPajak = 0;
                }


                /*
                |--------------------------------------------------------------------------
                | PAJAK PERSENTASE
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


                if ($totalPO < 0) {
                    $totalPO = 0;
                }


                /*
                |--------------------------------------------------------------------------
                | UPDATE HEADER
                |--------------------------------------------------------------------------
                */

                $purchaseOrder->update([

                    'supplier_id' =>
                        $validated['supplier_id'],

                    'tanggal_po' =>
                        $validated['tanggal_po'],

                    'tanggal_estimasi_datang' =>
                        $validated[
                            'tanggal_estimasi_datang'
                        ] ?? null,

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

                    'catatan' =>
                        $validated['catatan']
                        ?? null,
                ]);


                /*
                |--------------------------------------------------------------------------
                | REPLACE DETAIL
                |--------------------------------------------------------------------------
                */

                $purchaseOrder
                    ->items()
                    ->delete();


                foreach ($detailData as $detail) {

                    $purchaseOrder
                        ->items()
                        ->create($detail);
                }
            }
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
                    'Purchase Order ini sudah tidak berstatus Draft.'
                );
        }


        if (
            !$purchaseOrder
                ->items()
                ->exists()
        ) {

            return redirect()
                ->route(
                    'purchase-order.show',
                    $purchaseOrder
                )
                ->with(
                    'error',
                    'Purchase Order tidak memiliki barang.'
                );
        }


        $purchaseOrder->update([
            'status' =>
                'Dipesan',
        ]);


        return redirect()
            ->route(
                'purchase-order.show',
                $purchaseOrder
            )
            ->with(
                'success',
                'Purchase Order berhasil dikirim.'
            );
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


        $pdf = Pdf::loadView(
            'purchase-order.pdf',
            compact('purchaseOrder')
        );


        $pdf->setPaper(
            'a4',
            'portrait'
        );


        $safeNumber = str_replace(['/', '\\'], '-', $purchaseOrder->nomor_po);

        return $pdf->stream(
            'Purchase-Order-' . $safeNumber . '.pdf'
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
        if (
            $purchaseOrder->status
            !== 'Draft'
        ) {

            return redirect()
                ->route(
                    'purchase-order.index'
                )
                ->with(
                    'error',
                    'Hanya Purchase Order berstatus Draft yang dapat dihapus.'
                );
        }


        DB::transaction(
            function () use (
                $purchaseOrder
            ) {

                $purchaseOrder
                    ->items()
                    ->delete();


                $purchaseOrder
                    ->delete();
            }
        );


        return redirect()
            ->route(
                'purchase-order.index'
            )
            ->with(
                'success',
                'Purchase Order berhasil dihapus.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | GENERATE NOMOR PO
    |--------------------------------------------------------------------------
    */

    private function generateNomorPO(): string
    {
        $yearMonth =
            now()->format('Ym');


        $prefix =
            "PO-{$yearMonth}-";


        $last =
            PurchaseOrder::where(
                'nomor_po',
                'like',
                $prefix . '%'
            )
                ->orderByDesc('id')
                ->first();


        if ($last) {

            $lastNumber =
                (int) substr(
                    $last->nomor_po,
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