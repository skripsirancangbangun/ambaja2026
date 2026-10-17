<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Gudang;
use App\Models\MutasiStok;
use App\Models\StokGudang;
use App\Models\StokAwalImport;
use App\Services\InventoryService;
use App\Services\StokAwalAccountingService;
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
                            'stok_gudangs.qty <= barangs.minimum_stok'
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
        InventoryService $inventoryService,
        StokAwalAccountingService $accountingService
    ) {
        $validated = $request->validate([
            'file_stok_awal' => 'required|file|mimes:csv,txt|max:5120',
        ]);
        $file = $validated['file_stok_awal'];
        $fileHash = hash_file('sha256', $file->getRealPath());

        if (!$fileHash) {
            return back()->with('error', 'File CSV tidak dapat diverifikasi.');
        }
        if (StokAwalImport::where('file_hash', $fileHash)->exists()) {
            return back()->with('error', 'File dengan isi yang sama sudah pernah diproses. Tidak ada stok yang diubah.');
        }

        $handle = fopen($file->getRealPath(), 'r');
        if (!$handle) {
            return back()->with('error', 'File stok awal tidak dapat dibaca.');
        }

        try {
            $header = fgetcsv($handle);
            if (!$header) {
                return back()->with('error', 'File CSV kosong atau tidak memiliki header.');
            }
            $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]);
            $header = array_map(fn ($v) => strtolower(trim((string) $v)), $header);
            $required = ['kode_barang', 'kode_gudang', 'qty', 'harga_satuan'];
            if (count($header) !== count(array_unique($header))) {
                return back()->with('error', 'Header CSV memiliki nama kolom duplikat.');
            }
            foreach ($required as $column) {
                if (!in_array($column, $header, true)) {
                    return back()->with('error', "Kolom {$column} tidak ditemukan pada file import.");
                }
            }

            $rows = [];
            $line = 1;
            while (($row = fgetcsv($handle)) !== false) {
                $line++;
                if (count(array_filter($row, fn ($v) => trim((string) $v) !== '')) === 0) continue;
                if (count($row) !== count($header)) {
                    return back()->with('error', "Format kolom tidak sesuai pada baris {$line}.");
                }
                $data = array_combine($header, $row);
                $kode = trim((string) ($data['kode_barang'] ?? ''));
                $kodeGudang = trim((string) ($data['kode_gudang'] ?? ''));
                $qtyRaw = trim((string) ($data['qty'] ?? ''));
                $hargaRaw = trim((string) ($data['harga_satuan'] ?? ''));
                if ($kode === '' || $kodeGudang === '') {
                    return back()->with('error', "Kode barang/gudang kosong pada baris {$line}.");
                }
                if (!is_numeric($qtyRaw) || !is_numeric($hargaRaw)) {
                    return back()->with('error', "Qty dan harga_satuan harus berupa angka pada baris {$line}.");
                }
                $qty = round((float) $qtyRaw, 2);
                $harga = round((float) $hargaRaw, 2);
                if ($qty <= 0 || $harga <= 0) {
                    return back()->with('error', "Qty dan harga_satuan harus lebih dari 0 pada baris {$line}.");
                }
                $key = strtoupper($kode).'|'.strtoupper($kodeGudang);
                if (isset($rows[$key])) {
                    return back()->with('error', "Barang-gudang duplikat pada baris {$line}.");
                }
                $barang = Barang::where('kode_barang', $kode)->first();
                if (!$barang) return back()->with('error', "Barang {$kode} tidak ditemukan pada baris {$line}.");
                $gudang = Gudang::where('kode_gudang', $kodeGudang)->first();
                if (!$gudang) return back()->with('error', "Gudang {$kodeGudang} tidak ditemukan pada baris {$line}.");

                $rows[$key] = [
                    'line' => $line, 'barang_id' => (int) $barang->id,
                    'kode_barang' => $barang->kode_barang, 'nama_barang' => $barang->nama_barang,
                    'gudang_id' => (int) $gudang->id, 'kode_gudang' => $gudang->kode_gudang,
                    'qty' => $qty, 'harga_satuan' => $harga,
                ];
            }
            fclose($handle);
            $handle = null;
            $preparedRows = array_values($rows);
            if (!$preparedRows) return back()->with('error', 'Tidak ada data stok yang dapat diimport.');

            DB::transaction(function () use ($preparedRows, $fileHash, $inventoryService, $accountingService) {
                $batch = StokAwalImport::create([
                    'nomor_import' => 'SA-'.now()->format('YmdHis').'-'.strtoupper(substr($fileHash, 0, 6)),
                    'tanggal' => now(), 'file_hash' => $fileHash,
                    'total_qty' => 0, 'total_nilai' => 0, 'jurnal_id' => null,
                    'created_by' => auth()->id(),
                ]);
                $totalQty = 0.0;
                $totalNilai = 0.0;

                foreach ($preparedRows as $data) {
                    $saldoAda = StokGudang::where('barang_id', $data['barang_id'])
                        ->where('gudang_id', $data['gudang_id'])->lockForUpdate()->exists();
                    $mutasiAda = MutasiStok::where('barang_id', $data['barang_id'])
                        ->where('gudang_id', $data['gudang_id'])->lockForUpdate()->exists();
                    if ($saldoAda || $mutasiAda) {
                        throw new \RuntimeException("{$data['kode_barang']} sudah memiliki saldo atau riwayat mutasi di gudang {$data['kode_gudang']}. Seluruh import dibatalkan.");
                    }
                    $totalQty += $data['qty'];
                    $totalNilai += round($data['qty'] * $data['harga_satuan'], 2);
                    $inventoryService->stokMasuk(
                        barangId: $data['barang_id'], gudangId: $data['gudang_id'],
                        qty: $data['qty'], hargaSatuan: $data['harga_satuan'],
                        referenceType: 'STOK_AWAL_IMPORT', referenceId: (int) $batch->id,
                        keterangan: 'Import stok awal '.$batch->nomor_import,
                        tipeMutasi: 'PENYESUAIAN', tanggalTransaksi: $batch->tanggal
                    );
                }

                $totalQty = round($totalQty, 2);
                $totalNilai = round($totalNilai, 2);
                $jurnal = $accountingService->createOpeningInventoryJournal($batch, $totalNilai);
                $batch->update(['total_qty' => $totalQty, 'total_nilai' => $totalNilai, 'jurnal_id' => $jurnal->id]);
            }, 3);
        } catch (\Throwable $e) {
            report($e);
            $message = $e instanceof \RuntimeException
                ? $e->getMessage()
                : 'Import stok awal gagal. Tidak ada perubahan yang disimpan. Periksa log Laravel untuk detail teknis.';
            return back()->with('error', $message);
        } finally {
            if (is_resource($handle)) fclose($handle);
        }

        return redirect()->route('stok-barang.index')
            ->with('success', count($preparedRows).' data stok awal berhasil diimport beserta jurnal pembukaan persediaan.');
    }

}