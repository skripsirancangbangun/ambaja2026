<?php

namespace App\Http\Controllers;

use App\Models\Gudang;
use App\Models\StockOpname;
use App\Models\StokGudang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockOpnameController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | DAFTAR STOCK OPNAME
    |--------------------------------------------------------------------------
    */

    public function index()
    {
        $stockOpnames = StockOpname::with([
            'gudang',
            'creator',
            'items',
        ])
            ->latest('tanggal_opname')
            ->latest('id')
            ->paginate(15);

        return view(
            'stock-opname.index',
            compact('stockOpnames')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | FORM BUAT STOCK OPNAME
    |--------------------------------------------------------------------------
    */

    public function create(Request $request)
    {
        $gudangs = Gudang::where('is_active', true)
            ->orderBy('nama_gudang')
            ->get();

        $selectedGudangId =
            $request->filled('gudang_id')
                ? (int) $request->gudang_id
                : null;

        $stokBarangs = collect();


        if ($selectedGudangId) {

            /*
            |--------------------------------------------------------------------------
            | CEK DRAFT YANG MASIH AKTIF
            |--------------------------------------------------------------------------
            |
            | Satu gudang sebaiknya tidak punya dua Stock Opname Draft
            | bersamaan.
            |
            */

            $draftExists = StockOpname::where(
                'gudang_id',
                $selectedGudangId
            )
                ->where(
                    'status',
                    'DRAFT'
                )
                ->exists();


            if ($draftExists) {

                return redirect()
                    ->route('stock-opname.index')
                    ->with(
                        'error',
                        'Gudang tersebut masih memiliki Stock Opname berstatus DRAFT.'
                    );
            }


            /*
            |--------------------------------------------------------------------------
            | AMBIL STOK GUDANG
            |--------------------------------------------------------------------------
            */

            $stokBarangs = StokGudang::with([
                'barang.satuan',
                'barang.kategori',
            ])
                ->where(
                    'gudang_id',
                    $selectedGudangId
                )
                ->orderBy('barang_id')
                ->get();
        }


        $previewNomorOpname =
            $this->generateNomorOpname();


        return view(
            'stock-opname.create',
            compact(
                'gudangs',
                'selectedGudangId',
                'stokBarangs',
                'previewNomorOpname'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | SIMPAN STOCK OPNAME DRAFT
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        $validated = $request->validate([

            'tanggal_opname' =>
                'required|date',

            'gudang_id' =>
                'required|exists:gudangs,id',

            'catatan' =>
                'nullable|string',
        ]);


        DB::transaction(function () use ($validated) {

            /*
            |--------------------------------------------------------------------------
            | CEK DRAFT GANDA
            |--------------------------------------------------------------------------
            */

            $draftExists = StockOpname::where(
                'gudang_id',
                $validated['gudang_id']
            )
                ->where(
                    'status',
                    'DRAFT'
                )
                ->lockForUpdate()
                ->exists();


            if ($draftExists) {

                throw ValidationException::withMessages([
                    'gudang_id' =>
                        'Gudang ini masih memiliki Stock Opname berstatus DRAFT.',
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | AMBIL STOK TERKINI
            |--------------------------------------------------------------------------
            */

            $stokBarangs = StokGudang::where(
                'gudang_id',
                $validated['gudang_id']
            )
                ->lockForUpdate()
                ->get();


            if ($stokBarangs->isEmpty()) {

                throw ValidationException::withMessages([
                    'gudang_id' =>
                        'Gudang ini belum memiliki data stok untuk diopname.',
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | BUAT HEADER
            |--------------------------------------------------------------------------
            */

            $stockOpname =
                StockOpname::create([

                    'nomor_opname' =>
                        $this->generateNomorOpname(),

                    'tanggal_opname' =>
                        $validated['tanggal_opname'],

                    'gudang_id' =>
                        $validated['gudang_id'],

                    'status' =>
                        'DRAFT',

                    'catatan' =>
                        $validated['catatan']
                        ?? null,

                    'created_by' =>
                        auth()->id(),
                ]);


            /*
            |--------------------------------------------------------------------------
            | SNAPSHOT STOK SISTEM
            |--------------------------------------------------------------------------
            */

            foreach ($stokBarangs as $stok) {

                $stockOpname
                    ->items()
                    ->create([

                        'barang_id' =>
                            $stok->barang_id,

                        'stok_sistem' =>
                            (float) $stok->qty,

                        /*
                         * Pada awal draft, stok fisik kita samakan
                         * dengan stok sistem sebagai nilai awal.
                         */
                        'stok_fisik' =>
                            (float) $stok->qty,

                        'selisih' =>
                            0,

                        'harga_rata_rata' =>
                            (float) $stok->harga_rata_rata,

                        'nilai_selisih' =>
                            0,

                        'keterangan' =>
                            null,
                    ]);
            }
        });


        return redirect()
            ->route('stock-opname.index')
            ->with(
                'success',
                'Stock Opname berhasil dibuat sebagai DRAFT.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | DETAIL STOCK OPNAME
    |--------------------------------------------------------------------------
    */

    public function show(
        StockOpname $stockOpname
    ) {
        $stockOpname->load([
            'gudang',
            'creator',
            'items.barang.satuan',
            'items.barang.kategori',
        ]);

        return view(
            'stock-opname.show',
            compact('stockOpname')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | FORM INPUT HASIL FISIK
    |--------------------------------------------------------------------------
    */

    public function edit(
        StockOpname $stockOpname
    ) {
        if (
            $stockOpname->status
            !== 'DRAFT'
        ) {

            return redirect()
                ->route(
                    'stock-opname.show',
                    $stockOpname
                )
                ->with(
                    'error',
                    'Hanya Stock Opname berstatus DRAFT yang dapat diisi.'
                );
        }


        $stockOpname->load([
            'gudang',
            'items.barang.satuan',
            'items.barang.kategori',
        ]);


        return view(
            'stock-opname.edit',
            compact('stockOpname')
        );
    }


    /*
    |--------------------------------------------------------------------------
    | SIMPAN HASIL FISIK
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        StockOpname $stockOpname
    ) {
        if (
            $stockOpname->status
            !== 'DRAFT'
        ) {

            return redirect()
                ->route(
                    'stock-opname.show',
                    $stockOpname
                )
                ->with(
                    'error',
                    'Stock Opname ini sudah tidak dapat diubah.'
                );
        }


        $validated = $request->validate([

            'items' =>
                'required|array|min:1',

            'items.*.id' =>
                'required|exists:stock_opname_items,id',

            'items.*.stok_fisik' =>
                'required|numeric|min:0',

            'items.*.keterangan' =>
                'nullable|string|max:500',

            'catatan' =>
                'nullable|string',
        ]);


        DB::transaction(function () use (
            $validated,
            $stockOpname
        ) {

            /*
            |--------------------------------------------------------------------------
            | LOCK HEADER
            |--------------------------------------------------------------------------
            */

            $stockOpname = StockOpname::where(
                'id',
                $stockOpname->id
            )
                ->lockForUpdate()
                ->firstOrFail();


            if (
                $stockOpname->status
                !== 'DRAFT'
            ) {

                throw ValidationException::withMessages([
                    'items' =>
                        'Stock Opname sudah tidak dapat diubah.',
                ]);
            }


            $stockOpname->load('items');


            /*
            |--------------------------------------------------------------------------
            | UPDATE DETAIL
            |--------------------------------------------------------------------------
            */

            foreach (
                $validated['items']
                as $index => $inputItem
            ) {

                $item =
                    $stockOpname
                        ->items
                        ->firstWhere(
                            'id',
                            $inputItem['id']
                        );


                if (!$item) {

                    throw ValidationException::withMessages([
                        "items.$index.id" =>
                            'Item Stock Opname tidak valid.',
                    ]);
                }


                $stokSistem =
                    (float) $item->stok_sistem;

                $stokFisik =
                    (float) $inputItem[
                        'stok_fisik'
                    ];

                $selisih =
                    $stokFisik
                    - $stokSistem;

                $hargaRataRata =
                    (float) $item
                        ->harga_rata_rata;

                $nilaiSelisih =
                    $selisih
                    * $hargaRataRata;


                $item->update([

                    'stok_fisik' =>
                        $stokFisik,

                    'selisih' =>
                        $selisih,

                    'nilai_selisih' =>
                        $nilaiSelisih,

                    'keterangan' =>
                        $inputItem[
                            'keterangan'
                        ] ?? null,
                ]);
            }


            $stockOpname->update([

                'catatan' =>
                    $validated['catatan']
                    ?? $stockOpname->catatan,

                'status' =>
                    'SELESAI',
            ]);
        });


        return redirect()
            ->route(
                'stock-opname.show',
                $stockOpname
            )
            ->with(
                'success',
                'Stock Opname berhasil diselesaikan. Selisih stok sudah dihitung.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | HAPUS DRAFT
    |--------------------------------------------------------------------------
    */

    public function destroy(
        StockOpname $stockOpname
    ) {
        if (
            $stockOpname->status
            !== 'DRAFT'
        ) {

            return redirect()
                ->route(
                    'stock-opname.index'
                )
                ->with(
                    'error',
                    'Hanya Stock Opname berstatus DRAFT yang dapat dihapus.'
                );
        }


        $stockOpname->delete();


        return redirect()
            ->route(
                'stock-opname.index'
            )
            ->with(
                'success',
                'Stock Opname Draft berhasil dihapus.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | GENERATE NOMOR OPNAME
    |--------------------------------------------------------------------------
    |
    | Contoh:
    | SO/2026/08/0001
    |
    */

    private function generateNomorOpname(): string
    {
        $year =
            now()->format('Y');

        $month =
            now()->format('m');

        $prefix =
            "SO/{$year}/{$month}/";


        $last =
            StockOpname::where(
                'nomor_opname',
                'like',
                $prefix . '%'
            )
                ->orderByDesc('id')
                ->first();


        if ($last) {

            $lastNumber =
                (int) substr(
                    $last->nomor_opname,
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