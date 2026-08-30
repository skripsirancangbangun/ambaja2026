<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Gudang;
use App\Models\MutasiStok;
use App\Models\StokGudang;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StokBarangController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | DAFTAR STOK
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $query = StokGudang::with([
            'barang.satuan',
            'barang.kategori',
            'gudang',
        ]);

        /*
        |--------------------------------------------------------------------------
        | SEARCH
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {

            $search = $request->search;

            $query->whereHas('barang', function ($q) use ($search) {

                $q->where(function ($subQuery) use ($search) {

                    $subQuery
                        ->where('kode_barang', 'like', "%{$search}%")
                        ->orWhere('nama_barang', 'like', "%{$search}%")
                        ->orWhere('spesifikasi', 'like', "%{$search}%")
                        ->orWhere('merek', 'like', "%{$search}%");
                });
            });
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER GUDANG
        |--------------------------------------------------------------------------
        */

        if ($request->filled('gudang_id')) {

            $query->where(
                'gudang_id',
                $request->gudang_id
            );
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER STATUS STOK
        |--------------------------------------------------------------------------
        */

        if ($request->filled('status')) {

            if ($request->status === 'habis') {

                $query->where('qty', '<=', 0);

            } elseif ($request->status === 'tersedia') {

                $query->where('qty', '>', 0);

            } elseif ($request->status === 'menipis') {

                $query
                    ->where('qty', '>', 0)
                    ->whereHas('barang', function ($q) {

                        $q->whereRaw(
                            'stok_gudangs.qty <= barangs.min_stok'
                        );
                    });
            }
        }

        $stokBarangs = $query
            ->orderBy('gudang_id')
            ->orderBy('barang_id')
            ->paginate(15)
            ->withQueryString();

        $gudangs = Gudang::where(
            'is_active',
            true
        )
            ->orderBy('nama_gudang')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | RINGKASAN
        |--------------------------------------------------------------------------
        */

        $totalJenisStok = StokGudang::where(
            'qty',
            '>',
            0
        )->count();

        $totalQty = StokGudang::sum('qty');

        $totalNilai = StokGudang::sum(
            'nilai_persediaan'
        );

        return view(
            'stok-barang.index',
            compact(
                'stokBarangs',
                'gudangs',
                'totalJenisStok',
                'totalQty',
                'totalNilai'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | DOWNLOAD TEMPLATE STOK AWAL
    |--------------------------------------------------------------------------
    */

    public function downloadTemplate()
    {
        $filename = 'template_import_stok_awal.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' =>
                'attachment; filename="' . $filename . '"',
        ];

        $callback = function () {

            $file = fopen(
                'php://output',
                'w'
            );

            /*
             * BOM supaya CSV nyaman dibuka di Excel.
             */
            fwrite(
                $file,
                "\xEF\xBB\xBF"
            );

            fputcsv(
                $file,
                [
                    'kode_barang',
                    'kode_gudang',
                    'qty',
                    'harga_satuan',
                ]
            );

            /*
             * Contoh data.
             */
            fputcsv(
                $file,
                [
                    'BRG001',
                    'GDG001',
                    '100',
                    '50000',
                ]
            );

            fclose($file);
        };

        return response()->stream(
            $callback,
            200,
            $headers
        );
    }

    /*
    |--------------------------------------------------------------------------
    | IMPORT STOK AWAL
    |--------------------------------------------------------------------------
    */

    public function importStokAwal(
        Request $request,
        InventoryService $inventoryService
    ) {
        $validated = $request->validate([
            'file_stok_awal' =>
                'required|file|mimes:csv,txt|max:5120',
        ]);

        $file = $validated[
            'file_stok_awal'
        ];

        $handle = fopen(
            $file->getRealPath(),
            'r'
        );

        if (!$handle) {

            return back()->with(
                'error',
                'File stok awal tidak dapat dibaca.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | BACA HEADER
        |--------------------------------------------------------------------------
        */

        $header = fgetcsv($handle);

        if (!$header) {

            fclose($handle);

            return back()->with(
                'error',
                'File CSV kosong atau tidak memiliki header.'
            );
        }

        /*
         * Hilangkan BOM dari kolom pertama.
         */
        $header[0] = preg_replace(
            '/^\xEF\xBB\xBF/',
            '',
            $header[0]
        );

        $header = array_map(
            fn ($value) =>
                strtolower(
                    trim($value)
                ),
            $header
        );

        $requiredColumns = [
            'kode_barang',
            'kode_gudang',
            'qty',
            'harga_satuan',
        ];

        foreach ($requiredColumns as $column) {

            if (!in_array(
                $column,
                $header
            )) {

                fclose($handle);

                return back()->with(
                    'error',
                    "Kolom {$column} tidak ditemukan pada file import."
                );
            }
        }

        /*
        |--------------------------------------------------------------------------
        | BACA SELURUH BARIS
        |--------------------------------------------------------------------------
        */

        $rows = [];

        $lineNumber = 1;

        while (
            ($row = fgetcsv($handle))
            !== false
        ) {

            $lineNumber++;

            /*
             * Abaikan baris kosong.
             */
            if (
                count(
                    array_filter(
                        $row,
                        fn ($value) =>
                            trim((string) $value) !== ''
                    )
                ) === 0
            ) {
                continue;
            }

            /*
             * Jumlah kolom harus sesuai header.
             */
            if (
                count($row)
                !==
                count($header)
            ) {

                fclose($handle);

                return back()->with(
                    'error',
                    "Format kolom tidak sesuai pada baris {$lineNumber}."
                );
            }

            $data = array_combine(
                $header,
                $row
            );

            $rows[] = [
                'line' =>
                    $lineNumber,

                'kode_barang' =>
                    trim(
                        (string) $data[
                            'kode_barang'
                        ]
                    ),

                'kode_gudang' =>
                    trim(
                        (string) $data[
                            'kode_gudang'
                        ]
                    ),

                'qty' =>
                    trim(
                        (string) $data['qty']
                    ),

                'harga_satuan' =>
                    trim(
                        (string) $data[
                            'harga_satuan'
                        ]
                    ),
            ];
        }

        fclose($handle);

        if (empty($rows)) {

            return back()->with(
                'error',
                'Tidak ada data stok yang dapat diimport.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | CEGAH DUPLIKAT DALAM FILE
        |--------------------------------------------------------------------------
        */

        $kombinasiFile = [];

        foreach ($rows as $row) {

            $key =
                strtoupper(
                    $row['kode_barang']
                )
                . '|'
                . strtoupper(
                    $row['kode_gudang']
                );

            if (isset(
                $kombinasiFile[$key]
            )) {

                return back()->with(
                    'error',
                    'Barang dan gudang yang sama ditemukan lebih dari satu kali '
                    . "pada file import. Baris {$row['line']}."
                );
            }

            $kombinasiFile[$key] =
                true;
        }

        /*
        |--------------------------------------------------------------------------
        | VALIDASI SEMUA DATA TERLEBIH DAHULU
        |--------------------------------------------------------------------------
        */

        $preparedRows = [];

        foreach ($rows as $row) {

            $line =
                $row['line'];

            if (
                $row['kode_barang']
                === ''
            ) {

                return back()->with(
                    'error',
                    "Kode barang kosong pada baris {$line}."
                );
            }

            if (
                $row['kode_gudang']
                === ''
            ) {

                return back()->with(
                    'error',
                    "Kode gudang kosong pada baris {$line}."
                );
            }

            if (
                !is_numeric(
                    $row['qty']
                )
                ||
                (float) $row['qty']
                <= 0
            ) {

                return back()->with(
                    'error',
                    "Qty harus lebih dari 0 pada baris {$line}."
                );
            }

            if (
                !is_numeric(
                    $row['harga_satuan']
                )
                ||
                (float) $row[
                    'harga_satuan'
                ] < 0
            ) {

                return back()->with(
                    'error',
                    "Harga satuan tidak valid pada baris {$line}."
                );
            }

            $barang = Barang::where(
                'kode_barang',
                $row['kode_barang']
            )->first();

            if (!$barang) {

                return back()->with(
                    'error',
                    "Barang dengan kode {$row['kode_barang']} "
                    . "tidak ditemukan. Baris {$line}."
                );
            }

            $gudang = Gudang::where(
                'kode_gudang',
                $row['kode_gudang']
            )->first();

            if (!$gudang) {

                return back()->with(
                    'error',
                    "Gudang dengan kode {$row['kode_gudang']} "
                    . "tidak ditemukan. Baris {$line}."
                );
            }

            /*
            |--------------------------------------------------------------------------
            | CEK RIWAYAT MUTASI
            |--------------------------------------------------------------------------
            |
            | Kalau barang + gudang sudah punya mutasi,
            | maka stok awal tidak boleh diimport lagi.
            |
            */

            $sudahAdaMutasi =
                MutasiStok::where(
                    'barang_id',
                    $barang->id
                )
                    ->where(
                        'gudang_id',
                        $gudang->id
                    )
                    ->exists();

            if ($sudahAdaMutasi) {

                return back()->with(
                    'error',
                    "{$barang->kode_barang} - {$barang->nama_barang} "
                    . "pada gudang {$gudang->nama_gudang} "
                    . 'sudah memiliki riwayat stok sehingga tidak dapat '
                    . "diimport sebagai stok awal. Baris {$line}."
                );
            }

            $preparedRows[] = [
                'barang' =>
                    $barang,

                'gudang' =>
                    $gudang,

                'qty' =>
                    round(
                        (float) $row['qty'],
                        2
                    ),

                'harga_satuan' =>
                    round(
                        (float) $row[
                            'harga_satuan'
                        ],
                        2
                    ),
            ];
        }

        /*
        |--------------------------------------------------------------------------
        | PROSES IMPORT
        |--------------------------------------------------------------------------
        */

        DB::transaction(function () use (
            $preparedRows,
            $inventoryService
        ) {

            foreach (
                $preparedRows
                as $data
            ) {

                /*
                 * Gunakan PENYESUAIAN supaya kompatibel
                 * dengan tipe mutasi yang sudah tersedia.
                 *
                 * Reference Type membedakan bahwa transaksi
                 * ini berasal dari STOK AWAL.
                 */

                $inventoryService->stokMasuk(

                    barangId:
                        (int) $data[
                            'barang'
                        ]->id,

                    gudangId:
                        (int) $data[
                            'gudang'
                        ]->id,

                    qty:
                        (float) $data[
                            'qty'
                        ],

                    hargaSatuan:
                        (float) $data[
                            'harga_satuan'
                        ],

                    referenceType:
                        'STOK_AWAL',

                    /*
                     * Tidak ada header transaksi khusus
                     * untuk stok awal saat ini.
                     */
                    referenceId:
                        0,

                    keterangan:
                        'Import Stok Awal',

                    tipeMutasi:
                        'PENYESUAIAN'
                );
            }
        });

        return redirect()
            ->route(
                'stok-barang.index'
            )
            ->with(
                'success',
                count($preparedRows)
                . ' data stok awal berhasil diimport.'
            );
    }
}