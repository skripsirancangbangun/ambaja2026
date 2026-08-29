<?php

namespace App\Http\Controllers;

use App\Models\PenerimaanBarang;
use App\Models\PenerimaanBarangItem;
use App\Models\ReturPembelian;
use App\Models\ReturPembelianDetail;
use App\Models\StokGudang;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ReturPembelianController extends Controller
{
    public function index(Request $request)
    {
        $query = ReturPembelian::with([
            'penerimaanBarang',
            'purchaseOrder',
            'supplier',
            'gudang',
            'creator',
            'details',
        ]);

        if ($request->filled('search')) {
            $search = $request->search;

            $query->where(function ($q) use ($search) {
                $q->where(
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
                'tanggal_retur',
                '>=',
                $request->tanggal_mulai
            );
        }

        if ($request->filled('tanggal_selesai')) {
            $query->whereDate(
                'tanggal_retur',
                '<=',
                $request->tanggal_selesai
            );
        }

        $returs = $query
            ->latest('tanggal_retur')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view(
            'retur-pembelian.index',
            compact('returs')
        );
    }


    public function create(Request $request)
    {
        $penerimaans = PenerimaanBarang::with([
            'purchaseOrder.supplier',
            'gudang',
        ])
            ->latest('tanggal_penerimaan')
            ->latest('id')
            ->get();

        $selectedPenerimaan = null;
        $items = collect();

        if ($request->filled('penerimaan_barang_id')) {
            $selectedPenerimaan =
                PenerimaanBarang::with([
                    'purchaseOrder.supplier',
                    'gudang',
                ])
                    ->find(
                        $request->penerimaan_barang_id
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
                        ->map(function ($item) {
                            $qtySudahDiretur =
                                ReturPembelianDetail::where(
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
                                (float) $item->qty_diterima;

                            $item->qty_sudah_diretur =
                                (float) $qtySudahDiretur;

                            $item->qty_bisa_diretur =
                                max(
                                    0,
                                    $qtyDiterima
                                    - (float) $qtySudahDiretur
                                );

                            return $item;
                        })
                        ->filter(
                            fn ($item) =>
                                $item->qty_bisa_diretur > 0
                        )
                        ->values();
            }
        }

        $previewNomorRetur =
            $this->generateNomorRetur();

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


    public function store(Request $request)
    {
        $validated = $request->validate([
            'penerimaan_barang_id' =>
                'required|exists:penerimaan_barangs,id',

            'tanggal_retur' =>
                'required|date',

            'alasan_retur' =>
                'required|string|max:255',

            'catatan' =>
                'nullable|string',

            'items' =>
                'required|array|min:1',

            'items.*.penerimaan_barang_item_id' =>
                'required|exists:penerimaan_barang_items,id',

            'items.*.qty' =>
                'nullable|numeric|min:0',

            'items.*.keterangan' =>
                'nullable|string|max:500',
        ]);

        $retur =
            DB::transaction(function () use ($validated) {
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

                if (!$penerimaan->purchaseOrder) {
                    throw ValidationException::withMessages([
                        'penerimaan_barang_id' =>
                            'Purchase Order dari penerimaan barang tidak ditemukan.',
                    ]);
                }

                /*
                |--------------------------------------------------------------------------
                | VALIDASI TANGGAL RETUR
                |--------------------------------------------------------------------------
                */

                if (
                    $penerimaan->tanggal_penerimaan
                    &&
                    $validated['tanggal_retur']
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
                | CEGAH ITEM DUPLIKAT
                |--------------------------------------------------------------------------
                */

                $itemIds =
                    collect($validated['items'])
                        ->pluck(
                            'penerimaan_barang_item_id'
                        )
                        ->filter();

                if (
                    $itemIds->count()
                    !==
                    $itemIds->unique()->count()
                ) {
                    throw ValidationException::withMessages([
                        'items' =>
                            'Barang penerimaan yang sama tidak boleh dimasukkan lebih dari satu kali.',
                    ]);
                }

                $detailData = [];

                foreach (
                    $validated['items']
                    as $index => $inputItem
                ) {
                    $qtyRetur =
                        round(
                            (float) (
                                $inputItem['qty']
                                ?? 0
                            ),
                            2
                        );

                    if ($qtyRetur <= 0) {
                        continue;
                    }

                    $penerimaanItem =
                        PenerimaanBarangItem::where(
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

                    if (!$penerimaanItem) {
                        throw ValidationException::withMessages([
                            "items.$index.penerimaan_barang_item_id" =>
                                'Barang tidak berasal dari penerimaan yang dipilih.',
                        ]);
                    }

                    $qtySudahDiretur =
                        ReturPembelianDetail::where(
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
                            (float)
                            $penerimaanItem->qty_diterima,
                            2
                        );

                    $qtyBisaDiretur =
                        round(
                            $qtyDiterima
                            - (float) $qtySudahDiretur,
                            2
                        );

                    if ($qtyBisaDiretur <= 0) {
                        throw ValidationException::withMessages([
                            "items.$index.qty" =>
                                'Barang ini sudah seluruhnya diretur.',
                        ]);
                    }

                    if ($qtyRetur > $qtyBisaDiretur) {
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

                    $hargaSatuan =
                        (float)
                        $penerimaanItem->harga_satuan;

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
                            $inputItem['keterangan']
                            ?? null,
                    ];
                }

                if (empty($detailData)) {
                    throw ValidationException::withMessages([
                        'items' =>
                            'Masukkan qty retur minimal untuk satu barang.',
                    ]);
                }

                $retur =
                    ReturPembelian::create([
                        'nomor_retur' =>
                            $this->generateNomorRetur(),

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
                            $penerimaan->gudang_id,

                        'tanggal_retur' =>
                            $validated['tanggal_retur'],

                        'alasan_retur' =>
                            $validated['alasan_retur'],

                        'status' =>
                            'DRAFT',

                        'catatan' =>
                            $validated['catatan']
                            ?? null,

                        'created_by' =>
                            auth()->id(),
                    ]);

                foreach ($detailData as $detail) {
                    $retur
                        ->details()
                        ->create($detail);
                }

                return $retur;
            });

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
        ]);

        return view(
            'retur-pembelian.show',
            compact('returPembelian')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | PROSES RETUR
    |--------------------------------------------------------------------------
    */

    public function proses(
        ReturPembelian $returPembelian,
        InventoryService $inventoryService
    ) {
        DB::transaction(function () use (
            $returPembelian,
            $inventoryService
        ) {
            /*
            |--------------------------------------------------------------------------
            | LOCK HEADER RETUR
            |--------------------------------------------------------------------------
            */

            $retur =
                ReturPembelian::where(
                    'id',
                    $returPembelian->id
                )
                    ->lockForUpdate()
                    ->firstOrFail();

            if ($retur->status !== 'DRAFT') {
                throw ValidationException::withMessages([
                    'retur' =>
                        'Hanya Retur Pembelian berstatus DRAFT yang dapat diproses.',
                ]);
            }

            $retur->load([
                'details',
            ]);

            if ($retur->details->isEmpty()) {
                throw ValidationException::withMessages([
                    'retur' =>
                        'Retur Pembelian tidak memiliki detail barang.',
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | VALIDASI HAK RETUR + STOK
            |--------------------------------------------------------------------------
            |
            | Validasi dilakukan kembali saat proses.
            | Ini penting karena bisa saja terdapat beberapa
            | retur DRAFT untuk item penerimaan yang sama.
            |
            */

            foreach ($retur->details as $detail) {
                /*
                 * Lock item penerimaan.
                 */

                $penerimaanItem =
                    PenerimaanBarangItem::where(
                        'id',
                        $detail->penerimaan_barang_item_id
                    )
                        ->lockForUpdate()
                        ->first();

                if (!$penerimaanItem) {
                    throw ValidationException::withMessages([
                        'retur' =>
                            'Detail penerimaan barang tidak ditemukan.',
                    ]);
                }

                /*
                 * Hitung retur yang SUDAH selesai.
                 *
                 * Retur yang sedang diproses masih DRAFT,
                 * sehingga belum termasuk perhitungan ini.
                 */

                $qtySudahDiretur =
                    ReturPembelianDetail::where(
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
                        (float)
                        $penerimaanItem->qty_diterima,
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
                 * Validasi stok fisik/sistem gudang.
                 */

                $stok =
                    StokGudang::where(
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
            | STATUS DIPROSES
            |--------------------------------------------------------------------------
            */

            $retur->update([
                'status' =>
                    'DIPROSES',
            ]);


            /*
            |--------------------------------------------------------------------------
            | KELUARKAN STOK
            |--------------------------------------------------------------------------
            */

            foreach ($retur->details as $detail) {
                /*
                 * Nilai persediaan keluar menggunakan
                 * moving average dari InventoryService.
                 *
                 * Harga historis pada detail retur tetap
                 * digunakan sebagai informasi dokumen retur.
                 */

                $inventoryService->stokKeluar(
                    barangId:
                        (int) $detail->barang_id,

                    gudangId:
                        (int) $retur->gudang_id,

                    qty:
                        (float) $detail->qty,

                    referenceType:
                        'RETUR_PEMBELIAN',

                    referenceId:
                        (int) $detail->id,

                    keterangan:
                        'Retur Pembelian '
                        . $retur->nomor_retur,

                    tipeMutasi:
                        'RETUR_KELUAR',

                    /*
                     * MutasiStok menggunakan tanggal retur,
                     * bukan tanggal saat tombol Proses ditekan.
                     */
                    tanggalTransaksi:
                        $retur->tanggal_retur
                );
            }


            /*
            |--------------------------------------------------------------------------
            | STATUS SELESAI
            |--------------------------------------------------------------------------
            */

            $retur->update([
                'status' =>
                    'SELESAI',
            ]);
        });

        return redirect()
            ->route(
                'retur-pembelian.show',
                $returPembelian
            )
            ->with(
                'success',
                'Retur Pembelian berhasil diproses dan stok gudang telah dikurangi.'
            );
    }


    public function edit(
        ReturPembelian $returPembelian
    ) {
        if ($returPembelian->status !== 'DRAFT') {
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


    public function destroy(
        ReturPembelian $returPembelian
    ) {
        if ($returPembelian->status !== 'DRAFT') {
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

        DB::transaction(function () use (
            $returPembelian
        ) {
            $returPembelian
                ->details()
                ->delete();

            $returPembelian
                ->delete();
        });

        return redirect()
            ->route(
                'retur-pembelian.index'
            )
            ->with(
                'success',
                'Draft Retur Pembelian berhasil dihapus.'
            );
    }


    private function generateNomorRetur(): string
    {
        $year =
            now()->format('Y');

        $month =
            now()->format('m');

        $prefix =
            "RET-PB/{$year}/{$month}/";

        $last =
            ReturPembelian::where(
                'nomor_retur',
                'like',
                $prefix . '%'
            )
                ->orderByDesc('id')
                ->first();

        if ($last) {
            $lastNumber =
                (int) substr(
                    $last->nomor_retur,
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