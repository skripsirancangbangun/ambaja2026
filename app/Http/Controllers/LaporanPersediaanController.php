<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Gudang;
use App\Models\MutasiStok;
use App\Models\StokGudang;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanPersediaanController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | HALAMAN LAPORAN
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $tab = $request->get('tab', 'posisi');

        $gudangs = Gudang::where('is_active', true)
            ->orderBy('nama_gudang')
            ->get();

        /*
        |--------------------------------------------------------------------------
        | POSISI PERSEDIAAN
        |--------------------------------------------------------------------------
        */

        $query = $this->basePosisiQuery();

        $this->applyPosisiFilters(
            $query,
            $request
        );

        $totalJenisStok = (clone $query)
            ->where('qty', '>', 0)
            ->count();

        $totalQty = (float) (clone $query)
            ->sum('qty');

        $totalNilai = (float) (clone $query)
            ->sum('nilai_persediaan');

        $stokHabis = (clone $query)
            ->where('qty', '<=', 0)
            ->count();

        $stokBarangs = $query
            ->orderBy('gudang_id')
            ->orderBy('barang_id')
            ->paginate(
                20,
                ['*'],
                'posisi_page'
            )
            ->withQueryString();


        /*
        |--------------------------------------------------------------------------
        | MUTASI PERSEDIAAN
        |--------------------------------------------------------------------------
        */

        [$tanggalMulai, $tanggalSelesai] =
            $this->resolvePeriode($request);

        $mutasiPersediaan = collect();

        $mutasiSummary =
            $this->emptyMutasiSummary();

        if ($tab === 'mutasi') {
            $mutasiPersediaan =
                $this->getMutasiPersediaan(
                    $request,
                    $tanggalMulai,
                    $tanggalSelesai
                );

            $mutasiSummary =
                $this->makeMutasiSummary(
                    $mutasiPersediaan
                );
        }


        return view(
            'laporan.persediaan-index',
            compact(
                'tab',
                'gudangs',
                'stokBarangs',
                'totalJenisStok',
                'totalQty',
                'totalNilai',
                'stokHabis',
                'tanggalMulai',
                'tanggalSelesai',
                'mutasiPersediaan',
                'mutasiSummary'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | PDF POSISI PERSEDIAAN
    |--------------------------------------------------------------------------
    */

    public function pdf(Request $request)
    {
        // PDF dapat memuat ribuan baris. Jangan batasi waktu eksekusi request.
        if (function_exists('set_time_limit')) {
            @set_time_limit(0);
        }

        $query = $this->basePosisiQuery();
        $this->applyPosisiFilters($query, $request);

        // Ringkasan dihitung langsung oleh database agar seluruh model stok
        // tidak perlu ditampung di memori hanya untuk menghitung total.
        $totalJenisStok = (clone $query)
            ->where('qty', '>', 0)
            ->count();

        $totalQty = (float) (clone $query)->sum('qty');
        $totalNilai = (float) (clone $query)->sum('nilai_persediaan');

        $stokHabis = (clone $query)
            ->where('qty', '<=', 0)
            ->count();

        $totalRows = (clone $query)->count();

        // lazy(250) mengambil data bertahap dan tetap menjalankan eager loading
        // relasi barang, kategori, satuan, dan gudang pada setiap batch.
        $stokBarangs = (clone $query)
            ->orderBy('gudang_id')
            ->orderBy('barang_id')
            ->orderBy('id')
            ->lazy(250);

        $pdf = Pdf::loadView('laporan.persediaan-pdf', [
            'stokBarangs' => $stokBarangs,
            'totalJenisStok' => $totalJenisStok,
            'totalQty' => $totalQty,
            'totalNilai' => $totalNilai,
            'stokHabis' => $stokHabis,
            'totalRows' => $totalRows,
            'filterSearch' => trim((string) $request->query('search', '')),
            'filterGudang' => $request->query('gudang_id'),
            'filterStatus' => $request->query('status', ''),
            'printedAt' => now(),
        ])
            ->setPaper('a4', 'landscape')
            ->setOptions([
                'isHtml5ParserEnabled' => true,
                'isRemoteEnabled' => false,
                'defaultFont' => 'DejaVu Sans Mono',
                'dpi' => 96,
            ]);

        return $pdf->download(
            'laporan-persediaan-' . now()->format('Y-m-d-His') . '.pdf'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | EXCEL POSISI PERSEDIAAN
    |--------------------------------------------------------------------------
    */

    public function excel(
        Request $request
    ): StreamedResponse {

        $query =
            $this->basePosisiQuery();

        $this->applyPosisiFilters(
            $query,
            $request
        );

        $stokBarangs = $query
            ->orderBy('gudang_id')
            ->orderBy('barang_id')
            ->get();


        $spreadsheet =
            new Spreadsheet();

        $sheet =
            $spreadsheet
                ->getActiveSheet();

        $sheet->setTitle(
            'Posisi Persediaan'
        );


        $sheet->mergeCells(
            'A1:J1'
        );

        $sheet->setCellValue(
            'A1',
            'LAPORAN POSISI PERSEDIAAN'
        );

        $sheet->getStyle('A1')
            ->getFont()
            ->setBold(true)
            ->setSize(16);

        $sheet->getStyle('A1')
            ->getAlignment()
            ->setHorizontal(
                Alignment::HORIZONTAL_CENTER
            );


        $sheet->mergeCells(
            'A2:J2'
        );

        $sheet->setCellValue(
            'A2',
            'Posisi per '
            . now()->format('d/m/Y H:i')
        );

        $sheet->getStyle('A2')
            ->getAlignment()
            ->setHorizontal(
                Alignment::HORIZONTAL_CENTER
            );


        $headers = [
            'Kode Barang',
            'Nama Barang',
            'Spesifikasi',
            'Kategori',
            'Gudang',
            'Satuan',
            'Qty',
            'Harga Rata-rata',
            'Nilai Persediaan',
            'Status',
        ];


        $column = 'A';

        foreach ($headers as $header) {
            $sheet->setCellValue(
                $column . '4',
                $header
            );

            $column++;
        }


        $sheet->getStyle('A4:J4')
            ->getFont()
            ->setBold(true);

        $sheet->getStyle('A4:J4')
            ->getAlignment()
            ->setHorizontal(
                Alignment::HORIZONTAL_CENTER
            );


        $row = 5;


        foreach ($stokBarangs as $stok) {
            $sheet->setCellValue(
                'A' . $row,
                $stok->barang?->kode_barang
                ?? '-'
            );

            $sheet->setCellValue(
                'B' . $row,
                $stok->barang?->nama_barang
                ?? '-'
            );

            $sheet->setCellValue(
                'C' . $row,
                $stok->barang?->spesifikasi
                ?: '-'
            );

            $sheet->setCellValue(
                'D' . $row,
                $stok->barang
                    ?->kategori
                    ?->nama_kategori
                ?? '-'
            );

            $sheet->setCellValue(
                'E' . $row,
                $stok->gudang?->nama_gudang
                ?? '-'
            );

            $sheet->setCellValue(
                'F' . $row,
                $stok->barang
                    ?->satuan
                    ?->nama_satuan
                ?? '-'
            );

            $sheet->setCellValue(
                'G' . $row,
                (float) $stok->qty
            );

            $sheet->setCellValue(
                'H' . $row,
                (float)
                $stok->harga_rata_rata
            );

            $sheet->setCellValue(
                'I' . $row,
                (float)
                $stok->nilai_persediaan
            );

            $sheet->setCellValue(
                'J' . $row,
                $this->getStatusStok($stok)
            );

            $row++;
        }


        /*
        |--------------------------------------------------------------------------
        | TOTAL
        |--------------------------------------------------------------------------
        */

        $sheet->setCellValue(
            'F' . $row,
            'TOTAL'
        );

        $sheet->setCellValue(
            'G' . $row,
            (float)
            $stokBarangs->sum('qty')
        );

        $sheet->setCellValue(
            'I' . $row,
            (float)
            $stokBarangs->sum(
                'nilai_persediaan'
            )
        );


        $sheet->getStyle(
            'F' . $row . ':I' . $row
        )
            ->getFont()
            ->setBold(true);


        $sheet->getStyle(
            'G5:G' . $row
        )
            ->getNumberFormat()
            ->setFormatCode(
                '#,##0.00'
            );


        $sheet->getStyle(
            'H5:I' . $row
        )
            ->getNumberFormat()
            ->setFormatCode(
                '"Rp" #,##0'
            );


        foreach (
            range('A', 'J')
            as $column
        ) {
            $sheet
                ->getColumnDimension(
                    $column
                )
                ->setAutoSize(true);
        }


        $sheet->freezePane('A5');


        $filename =
            'posisi-persediaan-'
            . now()->format(
                'Y-m-d-His'
            )
            . '.xlsx';


        return response()->streamDownload(
            function () use (
                $spreadsheet
            ) {
                $writer =
                    new Xlsx(
                        $spreadsheet
                    );

                $writer->save(
                    'php://output'
                );

                $spreadsheet
                    ->disconnectWorksheets();
            },
            $filename,
            [
                'Content-Type' =>
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | PDF MUTASI PERSEDIAAN
    |--------------------------------------------------------------------------
    */

    public function mutasiPdf(
        Request $request
    ) {
        [$tanggalMulai, $tanggalSelesai] =
            $this->resolvePeriode($request);


        $mutasiPersediaan =
            $this->getMutasiPersediaan(
                $request,
                $tanggalMulai,
                $tanggalSelesai
            );


        $mutasiSummary =
            $this->makeMutasiSummary(
                $mutasiPersediaan
            );


        $pdf = Pdf::loadView(
            'laporan.mutasi-persediaan-pdf',
            compact(
                'mutasiPersediaan',
                'mutasiSummary',
                'tanggalMulai',
                'tanggalSelesai'
            )
        )->setPaper(
            'a4',
            'landscape'
        );


        return $pdf->download(
            'mutasi-persediaan-'
            . now()->format('Y-m-d-His')
            . '.pdf'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | EXCEL MUTASI PERSEDIAAN
    |--------------------------------------------------------------------------
    */

    public function mutasiExcel(
        Request $request
    ): StreamedResponse {

        [$tanggalMulai, $tanggalSelesai] =
            $this->resolvePeriode($request);


        $mutasiPersediaan =
            $this->getMutasiPersediaan(
                $request,
                $tanggalMulai,
                $tanggalSelesai
            );


        $spreadsheet =
            new Spreadsheet();

        $sheet =
            $spreadsheet
                ->getActiveSheet();

        $sheet->setTitle(
            'Mutasi Persediaan'
        );


        /*
         * Sekarang 12 kolom karena
         * Satuan ikut ditampilkan.
         */

        $sheet->mergeCells(
            'A1:L1'
        );

        $sheet->setCellValue(
            'A1',
            'LAPORAN MUTASI PERSEDIAAN'
        );

        $sheet->getStyle('A1')
            ->getFont()
            ->setBold(true)
            ->setSize(16);

        $sheet->getStyle('A1')
            ->getAlignment()
            ->setHorizontal(
                Alignment::HORIZONTAL_CENTER
            );


        $sheet->mergeCells(
            'A2:L2'
        );

        $sheet->setCellValue(
            'A2',
            'Periode '
            . date(
                'd/m/Y',
                strtotime($tanggalMulai)
            )
            . ' s/d '
            . date(
                'd/m/Y',
                strtotime($tanggalSelesai)
            )
        );

        $sheet->getStyle('A2')
            ->getAlignment()
            ->setHorizontal(
                Alignment::HORIZONTAL_CENTER
            );


        $headers = [
            'Kode',
            'Barang',
            'Spesifikasi',
            'Satuan',
            'Stok Awal',
            'Nilai Awal',
            'Masuk',
            'Nilai Masuk',
            'Keluar',
            'Nilai Keluar',
            'Stok Akhir',
            'Nilai Akhir',
        ];


        $column = 'A';

        foreach ($headers as $header) {
            $sheet->setCellValue(
                $column . '4',
                $header
            );

            $column++;
        }


        $sheet->getStyle('A4:L4')
            ->getFont()
            ->setBold(true);

        $sheet->getStyle('A4:L4')
            ->getAlignment()
            ->setHorizontal(
                Alignment::HORIZONTAL_CENTER
            );


        $row = 5;


        foreach (
            $mutasiPersediaan as $item
        ) {
            $sheet->setCellValue(
                'A' . $row,
                $item->kode_barang
            );

            $sheet->setCellValue(
                'B' . $row,
                $item->nama_barang
            );

            $sheet->setCellValue(
                'C' . $row,
                $item->spesifikasi
            );

            $sheet->setCellValue(
                'D' . $row,
                $item->satuan
            );

            $sheet->setCellValue(
                'E' . $row,
                (float) $item->stok_awal
            );

            $sheet->setCellValue(
                'F' . $row,
                (float) $item->nilai_awal
            );

            $sheet->setCellValue(
                'G' . $row,
                (float) $item->stok_masuk
            );

            $sheet->setCellValue(
                'H' . $row,
                (float) $item->nilai_masuk
            );

            $sheet->setCellValue(
                'I' . $row,
                (float) $item->stok_keluar
            );

            $sheet->setCellValue(
                'J' . $row,
                (float) $item->nilai_keluar
            );

            $sheet->setCellValue(
                'K' . $row,
                (float) $item->stok_akhir
            );

            $sheet->setCellValue(
                'L' . $row,
                (float) $item->nilai_akhir
            );

            $row++;
        }


        /*
        |--------------------------------------------------------------------------
        | TOTAL
        |--------------------------------------------------------------------------
        */

        $sheet->setCellValue(
            'D' . $row,
            'TOTAL'
        );

        $sheet->setCellValue(
            'E' . $row,
            (float)
            $mutasiPersediaan->sum(
                'stok_awal'
            )
        );

        $sheet->setCellValue(
            'F' . $row,
            (float)
            $mutasiPersediaan->sum(
                'nilai_awal'
            )
        );

        $sheet->setCellValue(
            'G' . $row,
            (float)
            $mutasiPersediaan->sum(
                'stok_masuk'
            )
        );

        $sheet->setCellValue(
            'H' . $row,
            (float)
            $mutasiPersediaan->sum(
                'nilai_masuk'
            )
        );

        $sheet->setCellValue(
            'I' . $row,
            (float)
            $mutasiPersediaan->sum(
                'stok_keluar'
            )
        );

        $sheet->setCellValue(
            'J' . $row,
            (float)
            $mutasiPersediaan->sum(
                'nilai_keluar'
            )
        );

        $sheet->setCellValue(
            'K' . $row,
            (float)
            $mutasiPersediaan->sum(
                'stok_akhir'
            )
        );

        $sheet->setCellValue(
            'L' . $row,
            (float)
            $mutasiPersediaan->sum(
                'nilai_akhir'
            )
        );


        $sheet->getStyle(
            'D' . $row
            . ':L' . $row
        )
            ->getFont()
            ->setBold(true);


        /*
        |--------------------------------------------------------------------------
        | FORMAT QTY
        |--------------------------------------------------------------------------
        */

        foreach (
            ['E', 'G', 'I', 'K']
            as $qtyColumn
        ) {
            $sheet->getStyle(
                $qtyColumn
                . '5:'
                . $qtyColumn
                . $row
            )
                ->getNumberFormat()
                ->setFormatCode(
                    '#,##0.00'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | FORMAT RUPIAH
        |--------------------------------------------------------------------------
        */

        foreach (
            ['F', 'H', 'J', 'L']
            as $nilaiColumn
        ) {
            $sheet->getStyle(
                $nilaiColumn
                . '5:'
                . $nilaiColumn
                . $row
            )
                ->getNumberFormat()
                ->setFormatCode(
                    '"Rp" #,##0'
                );
        }


        foreach (
            range('A', 'L')
            as $column
        ) {
            $sheet
                ->getColumnDimension(
                    $column
                )
                ->setAutoSize(true);
        }


        $sheet->freezePane('A5');


        $filename =
            'mutasi-persediaan-'
            . now()->format(
                'Y-m-d-His'
            )
            . '.xlsx';


        return response()->streamDownload(
            function () use (
                $spreadsheet
            ) {
                $writer =
                    new Xlsx(
                        $spreadsheet
                    );

                $writer->save(
                    'php://output'
                );

                $spreadsheet
                    ->disconnectWorksheets();
            },
            $filename,
            [
                'Content-Type' =>
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]
        );
    }


    /*
    |--------------------------------------------------------------------------
    | QUERY POSISI PERSEDIAAN
    |--------------------------------------------------------------------------
    */

    private function basePosisiQuery()
    {
        return StokGudang::with([
            'barang.satuan',
            'barang.kategori',
            'gudang',
        ]);
    }


    /*
    |--------------------------------------------------------------------------
    | FILTER POSISI PERSEDIAAN
    |--------------------------------------------------------------------------
    */

    private function applyPosisiFilters(
        $query,
        Request $request
    ): void {

        if ($request->filled('search')) {
            $search =
                trim($request->search);

            $query->whereHas(
                'barang',
                function (
                    $barangQuery
                ) use ($search) {
                    $barangQuery->where(
                        function ($q) use (
                            $search
                        ) {
                            $q->where(
                                'kode_barang',
                                'like',
                                "%{$search}%"
                            )
                                ->orWhere(
                                    'nama_barang',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'spesifikasi',
                                    'like',
                                    "%{$search}%"
                                )
                                ->orWhere(
                                    'merek',
                                    'like',
                                    "%{$search}%"
                                );
                        }
                    );
                }
            );
        }


        if (
            $request->filled(
                'gudang_id'
            )
        ) {
            $query->where(
                'gudang_id',
                $request->gudang_id
            );
        }


        if ($request->filled('status')) {
            switch ($request->status) {
                case 'tersedia':
                    $query->where(
                        'qty',
                        '>',
                        0
                    );
                    break;

                case 'habis':
                    $query->where(
                        'qty',
                        '<=',
                        0
                    );
                    break;

                case 'menipis':
                    $query
                        ->where(
                            'qty',
                            '>',
                            0
                        )
                        ->whereHas(
                            'barang',
                            function ($q) {
                                $q->whereRaw(
                                    'stok_gudangs.qty <= barangs.minimum_stok'
                                );
                            }
                        );
                    break;
            }
        }
    }


    /*
    |--------------------------------------------------------------------------
    | DATA MUTASI PERSEDIAAN
    |--------------------------------------------------------------------------
    */

    private function getMutasiPersediaan(
        Request $request,
        string $tanggalMulai,
        string $tanggalSelesai
    ) {
        /*
        |--------------------------------------------------------------------------
        | MODE LAPORAN
        |--------------------------------------------------------------------------
        |
        | Jika gudang dipilih:
        |     hitung per barang pada gudang tersebut.
        |
        | Jika Semua Gudang:
        |     hitung per barang, kemudian saldo seluruh
        |     gudang dijumlahkan menjadi satu baris.
        |
        */

        $perGudang =
            $request->filled('gudang_id');


        /*
        |--------------------------------------------------------------------------
        | BARANG YANG MEMILIKI MUTASI SAMPAI AKHIR PERIODE
        |--------------------------------------------------------------------------
        */

        $barangIds =
            MutasiStok::query()
                ->whereDate(
                    'tanggal',
                    '<=',
                    $tanggalSelesai
                )
                ->when(
                    $perGudang,
                    function ($query) use (
                        $request
                    ) {
                        $query->where(
                            'gudang_id',
                            $request->gudang_id
                        );
                    }
                )
                ->when(
                    $request->filled(
                        'search'
                    ),
                    function (
                        $query
                    ) use ($request) {
                        $search =
                            trim(
                                $request->search
                            );

                        $query->whereHas(
                            'barang',
                            function (
                                $barangQuery
                            ) use ($search) {
                                $barangQuery
                                    ->where(
                                        function (
                                            $q
                                        ) use ($search) {
                                            $q->where(
                                                'kode_barang',
                                                'like',
                                                "%{$search}%"
                                            )
                                                ->orWhere(
                                                    'nama_barang',
                                                    'like',
                                                    "%{$search}%"
                                                )
                                                ->orWhere(
                                                    'spesifikasi',
                                                    'like',
                                                    "%{$search}%"
                                                )
                                                ->orWhere(
                                                    'merek',
                                                    'like',
                                                    "%{$search}%"
                                                );
                                        }
                                    );
                            }
                        );
                    }
                )
                ->distinct()
                ->pluck('barang_id');


        /*
        |--------------------------------------------------------------------------
        | LOAD MASTER BARANG
        |--------------------------------------------------------------------------
        */

        $barangs =
            Barang::with([
                'satuan',
            ])
                ->whereIn(
                    'id',
                    $barangIds
                )
                ->get()
                ->keyBy('id');


        /*
        |--------------------------------------------------------------------------
        | HITUNG PER BARANG
        |--------------------------------------------------------------------------
        */

        $result =
            $barangIds->map(
                function (
                    $barangId
                ) use (
                    $request,
                    $tanggalMulai,
                    $tanggalSelesai,
                    $perGudang,
                    $barangs
                ) {
                    /*
                    |--------------------------------------------------------------------------
                    | GUDANG YANG RELEVAN UNTUK BARANG
                    |--------------------------------------------------------------------------
                    */

                    if ($perGudang) {
                        $gudangIds =
                            collect([
                                (int)
                                $request->gudang_id,
                            ]);
                    } else {
                        $gudangIds =
                            MutasiStok::where(
                                'barang_id',
                                $barangId
                            )
                                ->whereDate(
                                    'tanggal',
                                    '<=',
                                    $tanggalSelesai
                                )
                                ->distinct()
                                ->pluck(
                                    'gudang_id'
                                );
                    }


                    $stokAwal = 0;
                    $nilaiAwal = 0;

                    $stokMasuk = 0;
                    $nilaiMasuk = 0;

                    $stokKeluar = 0;
                    $nilaiKeluar = 0;


                    /*
                    |--------------------------------------------------------------------------
                    | HITUNG SETIAP GUDANG
                    |--------------------------------------------------------------------------
                    */

                    foreach (
                        $gudangIds
                        as $gudangId
                    ) {
                        /*
                         * Saldo terakhir sebelum periode.
                         */

                        $saldoAwal =
                            MutasiStok::where(
                                'barang_id',
                                $barangId
                            )
                                ->where(
                                    'gudang_id',
                                    $gudangId
                                )
                                ->whereDate(
                                    'tanggal',
                                    '<',
                                    $tanggalMulai
                                )
                                ->orderByDesc(
                                    'tanggal'
                                )
                                ->orderByDesc(
                                    'id'
                                )
                                ->first();


                        $stokAwal +=
                            (float) (
                                $saldoAwal
                                    ?->saldo_qty
                                ?? 0
                            );

                        $nilaiAwal +=
                            (float) (
                                $saldoAwal
                                    ?->saldo_nilai
                                ?? 0
                            );


                        /*
                         * Mutasi periode.
                         */

                        $periodeQuery =
                            MutasiStok::where(
                                'barang_id',
                                $barangId
                            )
                                ->where(
                                    'gudang_id',
                                    $gudangId
                                )
                                ->whereDate(
                                    'tanggal',
                                    '>=',
                                    $tanggalMulai
                                )
                                ->whereDate(
                                    'tanggal',
                                    '<=',
                                    $tanggalSelesai
                                );


                        $stokMasuk +=
                            (float)
                            (
                                clone
                                $periodeQuery
                            )->sum(
                                'qty_masuk'
                            );


                        $stokKeluar +=
                            (float)
                            (
                                clone
                                $periodeQuery
                            )->sum(
                                'qty_keluar'
                            );


                        $nilaiMasuk +=
                            (float)
                            (
                                clone
                                $periodeQuery
                            )
                                ->where(
                                    'qty_masuk',
                                    '>',
                                    0
                                )
                                ->sum('nilai');


                        $nilaiKeluar +=
                            (float)
                            (
                                clone
                                $periodeQuery
                            )
                                ->where(
                                    'qty_keluar',
                                    '>',
                                    0
                                )
                                ->sum('nilai');
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | SALDO AKHIR
                    |--------------------------------------------------------------------------
                    */

                    $stokAkhir =
                        $stokAwal
                        + $stokMasuk
                        - $stokKeluar;


                    $nilaiAkhir =
                        $nilaiAwal
                        + $nilaiMasuk
                        - $nilaiKeluar;


                    $barang =
                        $barangs->get(
                            $barangId
                        );


                    return (object) [
                        'barang_id' =>
                            (int) $barangId,

                        'gudang_id' =>
                            $perGudang
                                ? (int)
                                    $request
                                        ->gudang_id
                                : null,

                        'kode_barang' =>
                            $barang
                                ?->kode_barang
                            ?? '-',

                        'nama_barang' =>
                            $barang
                                ?->nama_barang
                            ?? '-',

                        'spesifikasi' =>
                            $barang
                                ?->spesifikasi
                            ?: '-',

                        'satuan' =>
                            $barang
                                ?->satuan
                                ?->nama_satuan
                            ?? '-',

                        'stok_awal' =>
                            $stokAwal,

                        'nilai_awal' =>
                            $nilaiAwal,

                        'stok_masuk' =>
                            $stokMasuk,

                        'nilai_masuk' =>
                            $nilaiMasuk,

                        'stok_keluar' =>
                            $stokKeluar,

                        'nilai_keluar' =>
                            $nilaiKeluar,

                        'stok_akhir' =>
                            $stokAkhir,

                        'nilai_akhir' =>
                            $nilaiAkhir,
                    ];
                }
            );


        /*
        |--------------------------------------------------------------------------
        | HAPUS BARIS KOSONG
        |--------------------------------------------------------------------------
        */

        return $result
            ->filter(
                function ($item) {
                    return
                        abs(
                            $item->stok_awal
                        ) > 0.0001
                        ||
                        abs(
                            $item->stok_masuk
                        ) > 0.0001
                        ||
                        abs(
                            $item->stok_keluar
                        ) > 0.0001
                        ||
                        abs(
                            $item->stok_akhir
                        ) > 0.0001
                        ||
                        abs(
                            $item->nilai_awal
                        ) > 0.0001
                        ||
                        abs(
                            $item->nilai_masuk
                        ) > 0.0001
                        ||
                        abs(
                            $item->nilai_keluar
                        ) > 0.0001
                        ||
                        abs(
                            $item->nilai_akhir
                        ) > 0.0001;
                }
            )
            ->sortBy('kode_barang')
            ->values();
    }


    /*
    |--------------------------------------------------------------------------
    | RESOLVE & VALIDASI PERIODE
    |--------------------------------------------------------------------------
    */

    private function resolvePeriode(
        Request $request
    ): array {

        $tanggalMulai =
            $request->filled(
                'tanggal_mulai'
            )
                ? $request->tanggal_mulai
                : now()
                    ->startOfMonth()
                    ->format('Y-m-d');


        $tanggalSelesai =
            $request->filled(
                'tanggal_selesai'
            )
                ? $request->tanggal_selesai
                : now()->format('Y-m-d');


        /*
         * Validasi format.
         */

        validator(
            [
                'tanggal_mulai' =>
                    $tanggalMulai,

                'tanggal_selesai' =>
                    $tanggalSelesai,
            ],
            [
                'tanggal_mulai' =>
                    'required|date',

                'tanggal_selesai' =>
                    'required|date|after_or_equal:tanggal_mulai',
            ],
            [
                'tanggal_selesai.after_or_equal' =>
                    'Tanggal selesai tidak boleh sebelum tanggal mulai.',
            ]
        )->validate();


        return [
            $tanggalMulai,
            $tanggalSelesai,
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | SUMMARY MUTASI
    |--------------------------------------------------------------------------
    */

    private function emptyMutasiSummary(): array
    {
        return [
            'stok_awal' => 0,
            'nilai_awal' => 0,
            'stok_masuk' => 0,
            'nilai_masuk' => 0,
            'stok_keluar' => 0,
            'nilai_keluar' => 0,
            'stok_akhir' => 0,
            'nilai_akhir' => 0,
        ];
    }


    private function makeMutasiSummary(
        $mutasiPersediaan
    ): array {

        return [
            'stok_awal' =>
                (float)
                $mutasiPersediaan->sum(
                    'stok_awal'
                ),

            'nilai_awal' =>
                (float)
                $mutasiPersediaan->sum(
                    'nilai_awal'
                ),

            'stok_masuk' =>
                (float)
                $mutasiPersediaan->sum(
                    'stok_masuk'
                ),

            'nilai_masuk' =>
                (float)
                $mutasiPersediaan->sum(
                    'nilai_masuk'
                ),

            'stok_keluar' =>
                (float)
                $mutasiPersediaan->sum(
                    'stok_keluar'
                ),

            'nilai_keluar' =>
                (float)
                $mutasiPersediaan->sum(
                    'nilai_keluar'
                ),

            'stok_akhir' =>
                (float)
                $mutasiPersediaan->sum(
                    'stok_akhir'
                ),

            'nilai_akhir' =>
                (float)
                $mutasiPersediaan->sum(
                    'nilai_akhir'
                ),
        ];
    }


    /*
    |--------------------------------------------------------------------------
    | STATUS POSISI STOK
    |--------------------------------------------------------------------------
    */

    private function getStatusStok(
        StokGudang $stok
    ): string {

        $qty =
            (float) $stok->qty;

        $minimumStok =
            (float) (
                $stok->barang
                    ?->minimum_stok
                ?? 0
            );


        if ($qty <= 0) {
            return 'Habis';
        }


        if (
            $minimumStok > 0
            &&
            $qty <= $minimumStok
        ) {
            return 'Menipis';
        }


        return 'Tersedia';
    }
}