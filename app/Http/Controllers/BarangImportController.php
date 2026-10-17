<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\KategoriBarang;
use App\Models\Satuan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Throwable;

class BarangImportController extends Controller
{
    private const SESSION_FILE = 'barang_import_file';


    public function create()
    {
        return view('barang.import');
    }


    public function preview(Request $request)
    {
        $validated = $request->validate([
            'file_barang' => 'required|file|extensions:xlsx,xls,csv,txt|max:20480',
        ]);


        $oldPath = session(self::SESSION_FILE);

        if ($oldPath) {
            Storage::disk('local')->delete($oldPath);
        }


        $path = $validated['file_barang']->store('imports/barang');


        try {

            $parsed = $this->parseFile(
                Storage::disk('local')->path($path)
            );

        } catch (Throwable $e) {

            report($e);
            Storage::disk('local')->delete($path);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'File tidak dapat dibaca. Periksa format file dan header template, lalu coba upload kembali.'
                );
        }


        if ($parsed['rows'] === []) {

            Storage::disk('local')->delete($path);

            return back()
                ->with(
                    'error',
                    'Tidak ditemukan data barang pada file Excel.'
                );
        }


        session([
            self::SESSION_FILE => $path,
        ]);


        return view(
            'barang.import-preview',
            [
                'summary' => $parsed['summary'],
                'previewRows' => array_slice(
                    $parsed['rows'],
                    0,
                    100
                ),
                'errors' => $parsed['errors'],
                'warnings' => $parsed['warnings'],
            ]
        );
    }


    public function import(Request $request)
    {
        $path = session(self::SESSION_FILE);


        if (
            !$path
            ||
            !Storage::disk('local')->exists($path)
        ) {

            return redirect()
                ->route('barang.import.create')
                ->with(
                    'error',
                    'File import sudah tidak tersedia. '
                    . 'Silakan upload ulang.'
                );
        }


        try {

            $parsed = $this->parseFile(
                Storage::disk('local')->path($path)
            );

        } catch (Throwable $e) {

            report($e);

            return redirect()
                ->route('barang.import.create')
                ->with(
                    'error',
                    'File tidak dapat dibaca. Periksa format file dan header template, lalu upload kembali.'
                );
        }


        if ($parsed['errors'] !== []) {

            return back()
                ->with(
                    'error',
                    'Import dibatalkan karena masih ada '
                    . 'data yang tidak valid.'
                );
        }


        $rows = $parsed['rows'];
        // Harga barang yang sudah ada hanya boleh diperbarui jika pengguna
        // secara eksplisit mencentang opsi pada halaman preview.
        $updateExistingPrices = $request->boolean('update_existing_prices');

        try {

            $result = DB::transaction(
                function () use ($rows, $updateExistingPrices) {

                    // Hanya master aktif yang boleh dipakai oleh import.
                    $categories = KategoriBarang::query()
                        ->where('is_active', true)
                        ->get()
                        ->groupBy(fn ($item) => $this->normalize($item->nama_kategori));

                    $allCategoryNames = KategoriBarang::query()
                        ->get(['id', 'nama_kategori', 'is_active'])
                        ->groupBy(fn ($item) => $this->normalize($item->nama_kategori));

                    // Normalisasi alias satuan agar BTG/Batang, LBR/Lembar, dll. konsisten.
                    $units = Satuan::query()
                        ->where('is_active', true)
                        ->get()
                        ->groupBy(fn ($item) => $this->normalize(
                            $this->normalizeUnitName($item->nama_satuan)
                        ));

                    $allUnitNames = Satuan::query()
                        ->get(['id', 'nama_satuan', 'is_active'])
                        ->groupBy(fn ($item) => $this->normalize(
                            $this->normalizeUnitName($item->nama_satuan)
                        ));


                    $created = 0;
                    $updated = 0;
                    $createdCategories = 0;
                    $createdUnits = 0;


                    foreach ($rows as $row) {

                        /*
                        |--------------------------------------------------------------------------
                        | KATEGORI
                        |--------------------------------------------------------------------------
                        */

                        $categoryKey =
                            $this->normalize(
                                $row['jenis']
                            );


                        if (!$categories->has($categoryKey)) {
                            if ($allCategoryNames->has($categoryKey)) {
                                throw new \RuntimeException(
                                    'Import dibatalkan: kategori "' . $row['jenis']
                                    . '" sudah ada tetapi nonaktif. Aktifkan kategori tersebut atau gunakan nama kategori aktif.'
                                );
                            }

                            $category = KategoriBarang::create([
                                    'kode_kategori' =>
                                        $this->uniqueCategoryCode(
                                            $row['jenis']
                                        ),

                                    'nama_kategori' =>
                                        $row['jenis'],

                                    'keterangan' =>
                                        'Dibuat otomatis dari '
                                        . 'import master barang.',

                                    'is_active' =>
                                        true,
                                ]);


                            $categories->put(
                                $categoryKey,
                                collect([$category])
                            );


                            $createdCategories++;
                        }


                        /*
                        |--------------------------------------------------------------------------
                        | SATUAN
                        |--------------------------------------------------------------------------
                        */

                        $unitName =
                            $this->normalizeUnitName(
                                $row['satuan']
                            );


                        $unitKey =
                            $this->normalize(
                                $unitName
                            );


                        if (!$units->has($unitKey)) {
                            if ($allUnitNames->has($unitKey)) {
                                throw new \RuntimeException(
                                    'Import dibatalkan: satuan "' . $unitName
                                    . '" sudah ada tetapi nonaktif. Aktifkan satuan tersebut atau gunakan nama satuan aktif.'
                                );
                            }

                            $unit = Satuan::create([
                                    'kode_satuan' =>
                                        $this->uniqueUnitCode(
                                            $unitName
                                        ),

                                    'nama_satuan' =>
                                        $unitName,

                                    'keterangan' =>
                                        'Dibuat otomatis dari '
                                        . 'import master barang.',

                                    'is_active' =>
                                        true,
                                ]);


                            $units->put(
                                $unitKey,
                                collect([$unit])
                            );


                            $createdUnits++;
                        }


                        $category = $categories->get($categoryKey)->first();
                        $unit = $units->get($unitKey)->first();


                        /*
                        |--------------------------------------------------------------------------
                        | BARANG
                        |--------------------------------------------------------------------------
                        */

                        $kodeNormal = $this->normalizeCode($row['kode_barang']);

                        $barang = Barang::query()
                            ->whereRaw('UPPER(TRIM(kode_barang)) = ?', [$kodeNormal])
                            ->lockForUpdate()
                            ->first();


                        $data = [
                            'kode_barang' => $row['kode_barang'],
                            'nama_barang' => $row['nama_barang'],
                            'kategori_barang_id' => $category->id,
                            'satuan_id' => $unit->id,
                            'harga_beli_default' => $row['harga_pokok'],
                            'harga_jual' => $row['harga_jual'],
                        ];

                        // Kolom opsional hanya diubah jika tersedia di file.
                        if ($row['minimum_stok'] !== null) {
                            $data['minimum_stok'] = $row['minimum_stok'];
                        }

                        if ($row['spesifikasi'] !== null) {
                            $data['spesifikasi'] = $row['spesifikasi'];
                        }

                        if ($row['merek'] !== null) {
                            $data['merek'] = $row['merek'];
                        }


                        if ($barang) {
                            if (!$updateExistingPrices) {
                                // Import master tidak boleh menimpa harga existing secara diam-diam.
                                unset($data['harga_beli_default'], $data['harga_jual']);
                            }

                            $barang->update($data);
                            $updated++;

                        } else {

                            $data['spesifikasi'] ??= null;
                            $data['merek'] ??= null;
                            $data['margin_persen'] = 0;
                            $data['minimum_stok'] ??= 0;
                            $data['is_active'] = true;

                            $barang = Barang::create($data);


                            $created++;
                        }
                    }


                    return compact(
                        'created',
                        'updated',
                        'createdCategories',
                        'createdUnits'
                    );
                }
            );

        } catch (Throwable $e) {

            report($e);

            return back()
                ->with(
                    'error',
                    'Import gagal dan seluruh perubahan dibatalkan. Periksa status kategori/satuan dan validasi file, lalu coba kembali.'
                );
        }


        Storage::disk('local')
            ->delete($path);


        session()->forget(
            self::SESSION_FILE
        );


        return redirect()
            ->route('barang.index')
            ->with(
                'success',
                "Import berhasil. "
                . "{$result['created']} barang baru dibuat, "
                . "{$result['updated']} barang diperbarui, "
                . "{$result['createdCategories']} kategori baru, "
                . "dan {$result['createdUnits']} satuan baru dibuat. "
                . ($updateExistingPrices
                    ? 'Harga barang lama diperbarui sesuai file. '
                    : 'Harga beli default dan harga jual barang lama dipertahankan. ')
                . "Kolom STOK tidak mengubah saldo stok gudang."
            );
    }


    public function cancel()
    {
        $path =
            session(
                self::SESSION_FILE
            );


        if ($path) {

            Storage::disk('local')
                ->delete($path);
        }


        session()->forget(
            self::SESSION_FILE
        );


        return redirect()
            ->route('barang.index')
            ->with(
                'success',
                'Preview import dibatalkan. '
                . 'Tidak ada data yang diubah.'
            );
    }


    private function parseFile(string $path): array
    {
        $spreadsheet = IOFactory::load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, false, false);

        $headerIndex = null;
        $columnMap = [];

        // Alias header untuk format Excel AMBAJA lama dan CSV template baru.
        $aliases = [
            'NO.' => 'NO.',
            'NO' => 'NO.',
            'KODE ITEM' => 'KODE BARANG',
            'KODE BARANG' => 'KODE BARANG',
            'NAMA ITEM' => 'NAMA BARANG',
            'NAMA BARANG' => 'NAMA BARANG',
            'JENIS' => 'JENIS',
            'KATEGORI' => 'JENIS',
            'SATUAN' => 'SATUAN',
            'HARGA POKOK' => 'HARGA BELI DEFAULT',
            'HARGA BELI' => 'HARGA BELI DEFAULT',
            'HARGA BELI DEFAULT' => 'HARGA BELI DEFAULT',
            'HARGA JUAL' => 'HARGA JUAL',
            'STOK' => 'STOK',
            'QTY' => 'STOK',
            'SPESIFIKASI' => 'SPESIFIKASI',
            'MEREK' => 'MEREK',
            'MINIMUM STOK' => 'MINIMUM STOK',
            'MINIMUM_STOK' => 'MINIMUM STOK',
        ];

        foreach (array_slice($rows, 0, 30, true) as $index => $row) {
            $normalized = array_map(
                fn ($value) => str_replace('_', ' ', $this->normalize($value)),
                $row
            );

            $candidateMap = [];
            foreach ($normalized as $columnIndex => $header) {
                if ($header === '') {
                    continue;
                }
                $canonical = $aliases[$header] ?? $header;
                $candidateMap[$canonical] = $columnIndex;
            }

            $required = [
                'KODE BARANG',
                'NAMA BARANG',
                'JENIS',
                'SATUAN',
                'HARGA BELI DEFAULT',
                'HARGA JUAL',
            ];

            if (count(array_intersect($required, array_keys($candidateMap))) === count($required)) {
                $headerIndex = $index;
                $columnMap = $candidateMap;
                break;
            }
        }

        if ($headerIndex === null) {
            throw new \RuntimeException(
                'Header tidak ditemukan. Gunakan kolom KODE BARANG, NAMA BARANG, JENIS, SATUAN, HARGA BELI DEFAULT, dan HARGA JUAL. Format Excel lama juga didukung.'
            );
        }

        $parsedRows = [];
        $errors = [];
        $warnings = [];
        $codes = [];

        for ($i = $headerIndex + 1; $i < count($rows); $i++) {
            $row = $rows[$i];
            $line = $i + 1;

            $get = function (string $key) use ($columnMap, $row) {
                return array_key_exists($key, $columnMap)
                    ? ($row[$columnMap[$key]] ?? null)
                    : null;
            };

            $kode = trim((string) ($get('KODE BARANG') ?? ''));
            $nama = trim((string) ($get('NAMA BARANG') ?? ''));

            if ($kode === '' && $nama === '') {
                continue;
            }

            $jenis = trim((string) ($get('JENIS') ?? ''));
            $satuan = trim((string) ($get('SATUAN') ?? ''));
            $hargaPokokRaw = $get('HARGA BELI DEFAULT');
            $hargaJualRaw = $get('HARGA JUAL');
            $minimumRaw = $get('MINIMUM STOK');
            $spesifikasiRaw = $get('SPESIFIKASI');
            $merekRaw = $get('MEREK');
            $stokRaw = $get('STOK');

            $rowErrors = [];

            if ($kode === '') $rowErrors[] = 'Kode barang kosong.';
            if ($nama === '') $rowErrors[] = 'Nama barang kosong.';
            if ($jenis === '') $rowErrors[] = 'Jenis/kategori kosong.';
            if ($satuan === '') $rowErrors[] = 'Satuan kosong.';

            if (!is_numeric($hargaPokokRaw) || (float) $hargaPokokRaw < 0) {
                $rowErrors[] = 'Harga beli/pokok tidak valid.';
            }
            if (!is_numeric($hargaJualRaw) || (float) $hargaJualRaw < 0) {
                $rowErrors[] = 'Harga jual tidak valid.';
            }
            if ($minimumRaw !== null && trim((string) $minimumRaw) !== ''
                && (!is_numeric($minimumRaw) || (float) $minimumRaw < 0)) {
                $rowErrors[] = 'Minimum stok harus berupa angka nol atau lebih.';
            }
            if ($stokRaw !== null && trim((string) $stokRaw) !== ''
                && (!is_numeric($stokRaw) || (float) $stokRaw < 0)) {
                $rowErrors[] = 'Kolom stok harus berupa angka nol atau lebih.';
            }

            $codeKey = $this->normalizeCode($kode);
            if ($kode !== '' && isset($codes[$codeKey])) {
                $rowErrors[] = 'Kode barang duplikat dengan baris ' . $codes[$codeKey] . '.';
            } elseif ($kode !== '') {
                $codes[$codeKey] = $line;
            }

            if ($rowErrors !== []) {
                $errors[] = [
                    'line' => $line,
                    'kode' => $kode,
                    'nama' => $nama,
                    'messages' => $rowErrors,
                ];
            }

            $parsedRows[] = [
                'line' => $line,
                'kode_barang' => $kode,
                'nama_barang' => $nama,
                'jenis' => $jenis,
                'satuan' => $this->normalizeUnitName($satuan),
                'harga_pokok' => is_numeric($hargaPokokRaw) ? (float) $hargaPokokRaw : 0,
                'harga_jual' => is_numeric($hargaJualRaw) ? (float) $hargaJualRaw : 0,
                'minimum_stok' => ($minimumRaw !== null && trim((string) $minimumRaw) !== '')
                    ? (float) $minimumRaw : null,
                'spesifikasi' => ($spesifikasiRaw !== null && trim((string) $spesifikasiRaw) !== '')
                    ? trim((string) $spesifikasiRaw) : null,
                'merek' => ($merekRaw !== null && trim((string) $merekRaw) !== '')
                    ? trim((string) $merekRaw) : null,
                'stok' => is_numeric($stokRaw) ? (float) $stokRaw : 0,
            ];
        }

        if (array_key_exists('STOK', $columnMap)) {
            $warnings[] = 'Kolom STOK/QTY hanya ditampilkan dan tidak mengubah saldo stok gudang. Gunakan menu Import Stok Awal untuk saldo persediaan.';
        }

        // Validasi referensi master saat preview: jangan gunakan master nonaktif.
        $categoryGroups = KategoriBarang::query()
            ->get(['id', 'nama_kategori', 'is_active'])
            ->groupBy(fn ($item) => $this->normalize($item->nama_kategori));

        $unitGroups = Satuan::query()
            ->get(['id', 'nama_satuan', 'is_active'])
            ->groupBy(fn ($item) => $this->normalize(
                $this->normalizeUnitName($item->nama_satuan)
            ));

        foreach ($parsedRows as $row) {
            $categoryKey = $this->normalize($row['jenis']);
            $categoryMatches = $categoryGroups->get($categoryKey, collect());
            $activeCategories = $categoryMatches->where('is_active', true);

            if ($categoryMatches->isNotEmpty() && $activeCategories->isEmpty()) {
                $this->appendReferenceError(
                    $errors,
                    $row,
                    'Kategori "' . $row['jenis'] . '" sudah ada tetapi nonaktif. Aktifkan master tersebut sebelum import.'
                );
            } elseif ($activeCategories->count() > 1) {
                $this->appendReferenceError(
                    $errors,
                    $row,
                    'Ada lebih dari satu kategori aktif dengan nama yang sama setelah normalisasi. Rapikan master kategori sebelum import.'
                );
            }

            $unitKey = $this->normalize(
                $this->normalizeUnitName($row['satuan'])
            );
            $unitMatches = $unitGroups->get($unitKey, collect());
            $activeUnits = $unitMatches->where('is_active', true);

            if ($unitMatches->isNotEmpty() && $activeUnits->isEmpty()) {
                $this->appendReferenceError(
                    $errors,
                    $row,
                    'Satuan "' . $row['satuan'] . '" sudah ada tetapi nonaktif. Aktifkan master tersebut sebelum import.'
                );
            } elseif ($activeUnits->count() > 1) {
                $this->appendReferenceError(
                    $errors,
                    $row,
                    'Ada lebih dari satu satuan aktif dengan nama/alias yang sama setelah normalisasi. Rapikan master satuan sebelum import.'
                );
            }
        }

        // Cocokkan kode barang dengan aturan normalisasi yang sama saat import.
        $normalizedCodes = array_values(array_unique(array_filter(array_map(
            fn ($row) => $this->normalizeCode($row['kode_barang']),
            $parsedRows
        ))));

        $existingGroups = collect();

        if ($normalizedCodes !== []) {
            $existingGroups = Barang::query()
                ->whereIn(DB::raw('UPPER(TRIM(kode_barang))'), $normalizedCodes)
                ->get([
                    'id',
                    'kode_barang',
                    'nama_barang',
                    'harga_beli_default',
                    'harga_jual',
                ])
                ->groupBy(fn ($barang) => $this->normalizeCode($barang->kode_barang));
        }

        // Jangan memilih salah satu secara diam-diam jika master database
        // sudah memiliki beberapa barang dengan kode yang sama setelah normalisasi.
        foreach ($parsedRows as $row) {
            $codeKey = $this->normalizeCode($row['kode_barang']);
            $matches = $existingGroups->get($codeKey, collect());

            if ($matches->count() > 1) {
                $ids = $matches->pluck('id')->implode(', ');
                $codes = $matches->pluck('kode_barang')->implode(', ');

                $this->appendReferenceError(
                    $errors,
                    $row,
                    'Kode barang cocok dengan lebih dari satu record master setelah normalisasi '
                    . '(ID: ' . $ids . '; kode: ' . $codes . '). Rapikan data master sebelum import.'
                );
            }
        }

        // Hanya record yang cocok tepat satu yang boleh dipakai untuk preview/update.
        $existingItems = $existingGroups
            ->filter(fn ($matches) => $matches->count() === 1)
            ->map(fn ($matches) => $matches->first());

        $newCount = 0;
        $updateCount = 0;
        $priceChangeCount = 0;

        foreach ($parsedRows as &$row) {
            $codeKey = $this->normalizeCode($row['kode_barang']);
            $matches = $existingGroups->get($codeKey, collect());
            $existing = $existingItems->get($codeKey);

            // Jika ada kecocokan ambigu, tandai sebagai existing agar ringkasan
            // tidak menyesatkan pengguna dengan menghitungnya sebagai barang baru.
            $row['is_existing'] = $matches->isNotEmpty();
            $row['harga_beli_lama'] = $existing
                ? (float) $existing->harga_beli_default
                : null;
            $row['harga_jual_lama'] = $existing
                ? (float) $existing->harga_jual
                : null;
            $row['nama_barang_lama'] = $existing
                ? (string) $existing->nama_barang
                : null;

            $row['harga_beli_berubah'] = $existing
                && abs((float) $existing->harga_beli_default - (float) $row['harga_pokok']) > 0.0001;
            $row['harga_jual_berubah'] = $existing
                && abs((float) $existing->harga_jual - (float) $row['harga_jual']) > 0.0001;
            $row['harga_berubah'] = $row['harga_beli_berubah'] || $row['harga_jual_berubah'];

            if ($matches->isNotEmpty()) {
                $updateCount++;
                if ($row['harga_berubah']) {
                    $priceChangeCount++;
                }
            } else {
                $newCount++;
            }
        }
        unset($row);

        return [
            'rows' => $parsedRows,
            'errors' => $errors,
            'warnings' => $warnings,
            'summary' => [
                'total' => count($parsedRows),
                'new' => $newCount,
                'update' => $updateCount,
                'price_change' => $priceChangeCount,
                'error' => count($errors),
                'category_count' => collect($parsedRows)->pluck('jenis')
                    ->map(fn ($value) => $this->normalize($value))->filter()->unique()->count(),
                'unit_count' => collect($parsedRows)->pluck('satuan')
                    ->map(fn ($value) => $this->normalize($value))->filter()->unique()->count(),
            ],
        ];
    }


    /** Tambahkan kesalahan referensi master ke baris preview yang sesuai. */
    private function appendReferenceError(array &$errors, array $row, string $message): void
    {
        foreach ($errors as &$error) {
            if (($error['line'] ?? null) === $row['line']) {
                $error['messages'][] = $message;
                unset($error);
                return;
            }
        }
        unset($error);

        $errors[] = [
            'line' => $row['line'],
            'kode' => $row['kode_barang'],
            'nama' => $row['nama_barang'],
            'messages' => [$message],
        ];
    }

    /** Normalisasi kode barang untuk pencocokan, tanpa mengubah kode lama di database. */
    private function normalizeCode(mixed $value): string
    {
        return strtoupper(trim((string) $value));
    }


    private function normalize(
        mixed $value
    ): string {

        return strtoupper(
            trim(
                (string) $value
            )
        );
    }


    private function normalizeUnitName(
        string $value
    ): string {

        $value =
            trim($value);


        return match (
            strtoupper($value)
        ) {

            'BTG', 'BATANG' =>
                'Batang',

            'LBR', 'LEMBAR' =>
                'Lembar',

            'PCS' =>
                'Pcs',

            'KG' =>
                'Kg',

            'MTR' =>
                'Mtr',

            default =>
                $value,
        };
    }


    private function uniqueCategoryCode(
        string $name
    ): string {

        $base =
            'CAT-'
            .
            strtoupper(
                substr(
                    md5(
                        $this->normalize(
                            $name
                        )
                    ),
                    0,
                    8
                )
            );


        $code =
            $base;


        $counter =
            1;


        while (
            KategoriBarang::where(
                'kode_kategori',
                $code
            )->exists()
        ) {

            $code =
                $base
                . '-'
                . $counter++;
        }


        return $code;
    }


    private function uniqueUnitCode(
        string $name
    ): string {

        $base =
            'SAT-'
            .
            strtoupper(
                substr(
                    md5(
                        $this->normalize(
                            $name
                        )
                    ),
                    0,
                    8
                )
            );


        $code =
            $base;


        $counter =
            1;


        while (
            Satuan::where(
                'kode_satuan',
                $code
            )->exists()
        ) {

            $code =
                $base
                . '-'
                . $counter++;
        }


        return $code;
    }
}