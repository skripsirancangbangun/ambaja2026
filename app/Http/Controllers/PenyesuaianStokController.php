<?php

namespace App\Http\Controllers;

use App\Models\PenyesuaianStok;
use App\Models\StockOpname;
use App\Models\StokGudang;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PenyesuaianStokController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | DAFTAR PENYESUAIAN STOK
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $penyesuaianStoks = PenyesuaianStok::with([
            'stockOpname',
            'gudang',
            'creator',
            'items',
        ])
            ->latest('tanggal_penyesuaian')
            ->latest('id')
            ->paginate(15);

        return view(
            'penyesuaian-stok.index',
            compact('penyesuaianStoks')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | FORM BUAT PENYESUAIAN
    |--------------------------------------------------------------------------
    */

    public function create(Request $request)
    {
        $stockOpnames = StockOpname::with([
            'gudang',
            'items.barang.satuan',
        ])
            ->where('status', 'SELESAI')
            ->whereDoesntHave('penyesuaianStok')
            ->whereHas('items', function ($query) {
                $query->where('selisih', '!=', 0);
            })
            ->latest('tanggal_opname')
            ->get();

        $selectedStockOpname = null;

        if ($request->filled('stock_opname_id')) {
            $selectedStockOpname = StockOpname::with([
                'gudang',
                'items.barang.satuan',
            ])
                ->where('status', 'SELESAI')
                ->whereDoesntHave('penyesuaianStok')
                ->whereHas('items', function ($query) {
                    $query->where('selisih', '!=', 0);
                })
                ->find($request->stock_opname_id);
        }

        $previewNomorPenyesuaian =
            $this->generateNomorPenyesuaian();

        return view(
            'penyesuaian-stok.create',
            compact(
                'stockOpnames',
                'selectedStockOpname',
                'previewNomorPenyesuaian'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | SIMPAN PENYESUAIAN STOK
    |--------------------------------------------------------------------------
    */

    public function store(
        Request $request,
        InventoryService $inventoryService
    ) {
        $validated = $request->validate([
            'stock_opname_id' =>
                'required|exists:stock_opnames,id',

            'tanggal_penyesuaian' =>
                'required|date',

            'catatan' =>
                'nullable|string',
        ]);


        DB::transaction(function () use (
            $validated,
            $inventoryService
        ) {
            /*
            |--------------------------------------------------------------------------
            | LOCK STOCK OPNAME
            |--------------------------------------------------------------------------
            */

            $stockOpname = StockOpname::where(
                'id',
                $validated['stock_opname_id']
            )
                ->lockForUpdate()
                ->firstOrFail();


            /*
            |--------------------------------------------------------------------------
            | VALIDASI STATUS
            |--------------------------------------------------------------------------
            */

            if ($stockOpname->status !== 'SELESAI') {
                throw ValidationException::withMessages([
                    'stock_opname_id' =>
                        'Stock Opname ini tidak dapat diproses untuk penyesuaian.',
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | VALIDASI TANGGAL PENYESUAIAN
            |--------------------------------------------------------------------------
            |
            | Penyesuaian tidak boleh memiliki tanggal
            | sebelum Stock Opname yang menjadi dasarnya.
            |
            */

            if (
                $stockOpname->tanggal_opname
                &&
                $validated['tanggal_penyesuaian']
                <
                $stockOpname
                    ->tanggal_opname
                    ->format('Y-m-d')
            ) {
                throw ValidationException::withMessages([
                    'tanggal_penyesuaian' =>
                        'Tanggal penyesuaian tidak boleh sebelum tanggal Stock Opname.',
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | CEK SUDAH PERNAH DISESUAIKAN
            |--------------------------------------------------------------------------
            */

            $alreadyAdjusted =
                PenyesuaianStok::where(
                    'stock_opname_id',
                    $stockOpname->id
                )
                    ->lockForUpdate()
                    ->exists();

            if ($alreadyAdjusted) {
                throw ValidationException::withMessages([
                    'stock_opname_id' =>
                        'Stock Opname ini sudah memiliki Penyesuaian Stok.',
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | LOAD ITEM STOCK OPNAME
            |--------------------------------------------------------------------------
            */

            $stockOpname->load([
                'items.barang',
                'gudang',
            ]);


            /*
            |--------------------------------------------------------------------------
            | AMBIL ITEM YANG MEMILIKI SELISIH
            |--------------------------------------------------------------------------
            */

            $itemsSelisih =
                $stockOpname
                    ->items
                    ->filter(function ($item) {
                        return
                            abs(
                                (float) $item->selisih
                            ) > 0.0001;
                    });


            if ($itemsSelisih->isEmpty()) {
                throw ValidationException::withMessages([
                    'stock_opname_id' =>
                        'Stock Opname tidak memiliki selisih stok.',
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | VALIDASI SNAPSHOT STOK BELUM BERUBAH
            |--------------------------------------------------------------------------
            |
            | Stok sekarang harus masih sama dengan stok sistem
            | pada saat Stock Opname dilakukan.
            |
            */

            $stokTerkini = [];


            foreach ($itemsSelisih as $opnameItem) {
                $stok = StokGudang::where(
                    'barang_id',
                    $opnameItem->barang_id
                )
                    ->where(
                        'gudang_id',
                        $stockOpname->gudang_id
                    )
                    ->lockForUpdate()
                    ->first();


                /*
                 * Jika stok tidak ada tetapi snapshot juga 0,
                 * kita izinkan khusus untuk penyesuaian tambah.
                 *
                 * InventoryService::stokMasuk() nantinya
                 * akan membuat StokGudang.
                 */

                if (!$stok) {
                    $qtySnapshot =
                        round(
                            (float) $opnameItem->stok_sistem,
                            2
                        );

                    if (
                        $qtySnapshot == 0
                        &&
                        (float) $opnameItem->selisih > 0
                    ) {
                        $stokTerkini[
                            $opnameItem->barang_id
                        ] = null;

                        continue;
                    }

                    throw ValidationException::withMessages([
                        'stock_opname_id' =>
                            'Stok salah satu barang tidak ditemukan pada gudang terkait.',
                    ]);
                }


                $qtyTerkini =
                    round(
                        (float) $stok->qty,
                        2
                    );

                $qtySnapshot =
                    round(
                        (float) $opnameItem->stok_sistem,
                        2
                    );


                if (
                    abs(
                        $qtyTerkini
                        - $qtySnapshot
                    ) > 0.0001
                ) {
                    $namaBarang =
                        $opnameItem->barang
                            ? (
                                $opnameItem
                                    ->barang
                                    ->kode_barang
                                . ' - '
                                . $opnameItem
                                    ->barang
                                    ->nama_barang
                            )
                            : 'Barang ID '
                                . $opnameItem->barang_id;


                    throw ValidationException::withMessages([
                        'stock_opname_id' =>
                            'Stok '
                            . $namaBarang
                            . ' telah berubah setelah Stock Opname. '
                            . 'Stok saat opname: '
                            . number_format(
                                $qtySnapshot,
                                2,
                                ',',
                                '.'
                            )
                            . ', stok sekarang: '
                            . number_format(
                                $qtyTerkini,
                                2,
                                ',',
                                '.'
                            )
                            . '. Buat Stock Opname baru sebelum melakukan penyesuaian.',
                    ]);
                }


                $stokTerkini[
                    $opnameItem->barang_id
                ] = $stok;
            }


            /*
            |--------------------------------------------------------------------------
            | BUAT HEADER PENYESUAIAN
            |--------------------------------------------------------------------------
            */

            $penyesuaian =
                PenyesuaianStok::create([
                    'nomor_penyesuaian' =>
                        $this->generateNomorPenyesuaian(),

                    'tanggal_penyesuaian' =>
                        $validated['tanggal_penyesuaian'],

                    'stock_opname_id' =>
                        $stockOpname->id,

                    'gudang_id' =>
                        $stockOpname->gudang_id,

                    'catatan' =>
                        $validated['catatan']
                        ?? null,

                    'created_by' =>
                        auth()->id(),
                ]);


            /*
            |--------------------------------------------------------------------------
            | PROSES SETIAP SELISIH
            |--------------------------------------------------------------------------
            */

            foreach (
                $itemsSelisih
                as $opnameItem
            ) {
                $selisih =
                    round(
                        (float) $opnameItem->selisih,
                        2
                    );

                $stok =
                    $stokTerkini[
                        $opnameItem->barang_id
                    ] ?? null;

                $qtySebelum =
                    $stok
                        ? (float) $stok->qty
                        : 0;


                /*
                |--------------------------------------------------------------------------
                | SELISIH POSITIF
                |--------------------------------------------------------------------------
                |
                | Fisik > Sistem
                | Stok harus ditambah.
                |
                */

                if ($selisih > 0) {
                    $jenis =
                        'TAMBAH';

                    $qtyPenyesuaian =
                        abs($selisih);


                    /*
                     * Prioritas harga:
                     *
                     * 1. Moving average stok saat ini.
                     * 2. Harga snapshot Stock Opname.
                     *
                     * Jika keduanya 0, nilai persediaan
                     * tambahan juga akan bernilai 0.
                     */

                    $hargaRataRata =
                        $stok
                            ? (float)
                                $stok->harga_rata_rata
                            : 0;

                    if ($hargaRataRata <= 0) {
                        $hargaRataRata =
                            (float)
                            $opnameItem->harga_rata_rata;
                    }

                    /*
                     * Penambahan stok harus memiliki dasar nilai persediaan.
                     * Jangan menambah qty dengan HPP nol karena hal itu akan
                     * membuat stok bertambah tanpa menambah nilai persediaan.
                     */
                    if ($hargaRataRata <= 0) {
                        throw ValidationException::withMessages([
                            'stock_opname_id' =>
                                'Penyesuaian stok tambah tidak dapat diproses karena HPP barang bernilai nol. Tetapkan nilai persediaan yang valid atau perbaiki data Stock Opname terlebih dahulu.',
                        ]);
                    }

                    $qtySesudah = round(
                        $qtySebelum + $qtyPenyesuaian,
                        2
                    );

                    // Samakan pembulatan nilai dengan InventoryService::stokMasuk().
                    $nilaiPenyesuaian = round(
                        $qtyPenyesuaian * $hargaRataRata,
                        2
                    );


                    /*
                    |--------------------------------------------------------------------------
                    | SIMPAN DETAIL
                    |--------------------------------------------------------------------------
                    */

                    $penyesuaianItem =
                        $penyesuaian
                            ->items()
                            ->create([
                                'stock_opname_item_id' =>
                                    $opnameItem->id,

                                'barang_id' =>
                                    $opnameItem->barang_id,

                                'qty_sebelum' =>
                                    $qtySebelum,

                                'qty_penyesuaian' =>
                                    $qtyPenyesuaian,

                                'qty_sesudah' =>
                                    $qtySesudah,

                                'jenis' =>
                                    $jenis,

                                'harga_rata_rata' =>
                                    $hargaRataRata,

                                'nilai_penyesuaian' =>
                                    $nilaiPenyesuaian,

                                'keterangan' =>
                                    $opnameItem->keterangan,
                            ]);


                    /*
                    |--------------------------------------------------------------------------
                    | UPDATE INVENTORY - TAMBAH
                    |--------------------------------------------------------------------------
                    */

                    $inventoryService->stokMasuk(
                        barangId:
                            (int) $opnameItem->barang_id,

                        gudangId:
                            (int) $stockOpname->gudang_id,

                        qty:
                            $qtyPenyesuaian,

                        hargaSatuan:
                            $hargaRataRata,

                        referenceType:
                            'PENYESUAIAN_STOK',

                        referenceId:
                            (int) $penyesuaianItem->id,

                        keterangan:
                            'Penyesuaian stok tambah '
                            . $penyesuaian
                                ->nomor_penyesuaian
                            . ' dari '
                            . $stockOpname
                                ->nomor_opname,

                        tipeMutasi:
                            'PENYESUAIAN',

                        /*
                         * Ledger mengikuti tanggal
                         * dokumen penyesuaian.
                         */
                        tanggalTransaksi:
                            $validated[
                                'tanggal_penyesuaian'
                            ]
                    );
                }


                /*
                |--------------------------------------------------------------------------
                | SELISIH NEGATIF
                |--------------------------------------------------------------------------
                |
                | Fisik < Sistem
                | Stok harus dikurangi.
                |
                */

                if ($selisih < 0) {
                    $jenis =
                        'KURANG';

                    $qtyPenyesuaian =
                        abs($selisih);


                    if (!$stok) {
                        throw ValidationException::withMessages([
                            'stock_opname_id' =>
                                'Stok barang tidak ditemukan untuk penyesuaian kurang.',
                        ]);
                    }


                    if (
                        $qtyPenyesuaian
                        >
                        $qtySebelum
                    ) {
                        throw ValidationException::withMessages([
                            'stock_opname_id' =>
                                'Qty penyesuaian melebihi stok tersedia untuk salah satu barang.',
                        ]);
                    }


                    $hargaRataRata =
                        (float)
                        $stok->harga_rata_rata;


                    $qtySesudah = round(
                        $qtySebelum - $qtyPenyesuaian,
                        2
                    );

                    /*
                     * Harus sama dengan InventoryService::stokKeluar():
                     * jika stok habis seluruhnya, keluarkan seluruh nilai saldo
                     * persediaan, bukan qty x average cost yang telah dibulatkan.
                     */
                    $nilaiPenyesuaian = $qtySesudah == 0.0
                        ? round((float) $stok->nilai_persediaan, 2)
                        : round($qtyPenyesuaian * $hargaRataRata, 2);


                    /*
                    |--------------------------------------------------------------------------
                    | SIMPAN DETAIL
                    |--------------------------------------------------------------------------
                    */

                    $penyesuaianItem =
                        $penyesuaian
                            ->items()
                            ->create([
                                'stock_opname_item_id' =>
                                    $opnameItem->id,

                                'barang_id' =>
                                    $opnameItem->barang_id,

                                'qty_sebelum' =>
                                    $qtySebelum,

                                'qty_penyesuaian' =>
                                    $qtyPenyesuaian,

                                'qty_sesudah' =>
                                    $qtySesudah,

                                'jenis' =>
                                    $jenis,

                                'harga_rata_rata' =>
                                    $hargaRataRata,

                                'nilai_penyesuaian' =>
                                    $nilaiPenyesuaian,

                                'keterangan' =>
                                    $opnameItem->keterangan,
                            ]);


                    /*
                    |--------------------------------------------------------------------------
                    | UPDATE INVENTORY - KURANG
                    |--------------------------------------------------------------------------
                    */

                    $inventoryService->stokKeluar(
                        barangId:
                            (int) $opnameItem->barang_id,

                        gudangId:
                            (int) $stockOpname->gudang_id,

                        qty:
                            $qtyPenyesuaian,

                        referenceType:
                            'PENYESUAIAN_STOK',

                        referenceId:
                            (int) $penyesuaianItem->id,

                        keterangan:
                            'Penyesuaian stok kurang '
                            . $penyesuaian
                                ->nomor_penyesuaian
                            . ' dari '
                            . $stockOpname
                                ->nomor_opname,

                        tipeMutasi:
                            'PENYESUAIAN',

                        /*
                         * Ledger mengikuti tanggal
                         * dokumen penyesuaian.
                         */
                        tanggalTransaksi:
                            $validated[
                                'tanggal_penyesuaian'
                            ]
                    );
                }
            }


            /*
            |--------------------------------------------------------------------------
            | UPDATE STATUS STOCK OPNAME
            |--------------------------------------------------------------------------
            */

            $stockOpname->update([
                'status' =>
                    'DISESUAIKAN',
            ]);
        });


        return redirect()
            ->route(
                'penyesuaian-stok.index'
            )
            ->with(
                'success',
                'Penyesuaian stok berhasil diproses.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | DETAIL PENYESUAIAN
    |--------------------------------------------------------------------------
    */

    public function show(
        PenyesuaianStok $penyesuaianStok
    ) {
        $penyesuaianStok->load([
            'stockOpname',
            'gudang',
            'creator',
            'items.barang.satuan',
            'items.stockOpnameItem',
        ]);

        return view(
            'penyesuaian-stok.show',
            compact('penyesuaianStok')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | EDIT TIDAK DIIZINKAN
    |--------------------------------------------------------------------------
    */

    public function edit(
        PenyesuaianStok $penyesuaianStok
    ) {
        return redirect()
            ->route(
                'penyesuaian-stok.show',
                $penyesuaianStok
            )
            ->with(
                'error',
                'Penyesuaian stok yang sudah diproses tidak dapat diedit.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | UPDATE TIDAK DIIZINKAN
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        PenyesuaianStok $penyesuaianStok
    ) {
        return redirect()
            ->route(
                'penyesuaian-stok.show',
                $penyesuaianStok
            )
            ->with(
                'error',
                'Penyesuaian stok yang sudah diproses tidak dapat diedit.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | DELETE TIDAK DIIZINKAN
    |--------------------------------------------------------------------------
    */

    public function destroy(
        PenyesuaianStok $penyesuaianStok
    ) {
        return redirect()
            ->route(
                'penyesuaian-stok.show',
                $penyesuaianStok
            )
            ->with(
                'error',
                'Penyesuaian stok tidak dapat dihapus langsung.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | GENERATE NOMOR PENYESUAIAN
    |--------------------------------------------------------------------------
    */

    private function generateNomorPenyesuaian(): string
    {
        $year =
            now()->format('Y');

        $month =
            now()->format('m');

        $prefix =
            "PS/{$year}/{$month}/";

        $last =
            PenyesuaianStok::where(
                'nomor_penyesuaian',
                'like',
                $prefix . '%'
            )
                ->orderByDesc('id')
                ->first();


        if ($last) {
            $lastNumber =
                (int) substr(
                    $last->nomor_penyesuaian,
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