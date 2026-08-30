<?php

namespace App\Http\Controllers;

use App\Models\InvoiceSupplier;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class LaporanUtangController extends Controller
{
    /**
     * Halaman utama Laporan Utang.
     */
    public function index(Request $request)
    {
        $query = $this->buildQuery($request);

        $invoices = $query
            ->orderBy('tanggal_jatuh_tempo')
            ->orderBy('tanggal_invoice')
            ->orderBy('id')
            ->paginate(20)
            ->withQueryString();

        $summary = $this->getSummary($request);

        $suppliers = Supplier::query()
            ->orderBy('nama_supplier')
            ->get();

        return view('laporan.utang-index', compact(
            'invoices',
            'summary',
            'suppliers'
        ));
    }


    /**
     * Query utama laporan.
     */
    private function buildQuery(Request $request)
    {
        $query = InvoiceSupplier::query()
            ->with([
                'supplier',
                'purchaseOrder',
            ]);


        /*
        |--------------------------------------------------------------------------
        | SEARCH
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {

            $search = trim(
                $request->search
            );

            $query->where(function ($q) use ($search) {

                $q->where(
                    'nomor_invoice_internal',
                    'like',
                    '%' . $search . '%'
                )
                ->orWhere(
                    'nomor_invoice_supplier',
                    'like',
                    '%' . $search . '%'
                )
                ->orWhereHas(
                    'supplier',
                    function ($supplierQuery) use ($search) {

                        $supplierQuery
                            ->where(
                                'kode_supplier',
                                'like',
                                '%' . $search . '%'
                            )
                            ->orWhere(
                                'nama_supplier',
                                'like',
                                '%' . $search . '%'
                            );
                    }
                )
                ->orWhereHas(
                    'purchaseOrder',
                    function ($poQuery) use ($search) {

                        $poQuery->where(
                            'nomor_po',
                            'like',
                            '%' . $search . '%'
                        );
                    }
                );
            });
        }


        /*
        |--------------------------------------------------------------------------
        | SUPPLIER
        |--------------------------------------------------------------------------
        */

        if ($request->filled('supplier_id')) {

            $query->where(
                'supplier_id',
                $request->supplier_id
            );
        }


        /*
        |--------------------------------------------------------------------------
        | STATUS PEMBAYARAN
        |--------------------------------------------------------------------------
        */

        if ($request->filled('status')) {

            if ($request->status === 'BELUM_LUNAS') {

                $query->where(
                    'status',
                    'BELUM_LUNAS'
                );

            } elseif ($request->status === 'SEBAGIAN') {

                $query->where(
                    'status',
                    'SEBAGIAN'
                );

            } elseif ($request->status === 'LUNAS') {

                $query->where(
                    'status',
                    'LUNAS'
                );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | STATUS JATUH TEMPO
        |--------------------------------------------------------------------------
        */

        if ($request->filled('jatuh_tempo')) {

            $today = now()->toDateString();

            if ($request->jatuh_tempo === 'JATUH_TEMPO') {

                $query
                    ->where('sisa_utang', '>', 0)
                    ->whereDate(
                        'tanggal_jatuh_tempo',
                        '<',
                        $today
                    );

            } elseif ($request->jatuh_tempo === 'HARI_INI') {

                $query
                    ->where('sisa_utang', '>', 0)
                    ->whereDate(
                        'tanggal_jatuh_tempo',
                        '=',
                        $today
                    );

            } elseif ($request->jatuh_tempo === 'BELUM_JATUH_TEMPO') {

                $query
                    ->where('sisa_utang', '>', 0)
                    ->whereDate(
                        'tanggal_jatuh_tempo',
                        '>',
                        $today
                    );
            }
        }


        /*
        |--------------------------------------------------------------------------
        | PERIODE TANGGAL INVOICE
        |--------------------------------------------------------------------------
        */

        if ($request->filled('tanggal_mulai')) {

            $query->whereDate(
                'tanggal_invoice',
                '>=',
                $request->tanggal_mulai
            );
        }


        if ($request->filled('tanggal_selesai')) {

            $query->whereDate(
                'tanggal_invoice',
                '<=',
                $request->tanggal_selesai
            );
        }


        /*
        |--------------------------------------------------------------------------
        | PERIODE JATUH TEMPO
        |--------------------------------------------------------------------------
        */

        if ($request->filled('jatuh_tempo_mulai')) {

            $query->whereDate(
                'tanggal_jatuh_tempo',
                '>=',
                $request->jatuh_tempo_mulai
            );
        }


        if ($request->filled('jatuh_tempo_selesai')) {

            $query->whereDate(
                'tanggal_jatuh_tempo',
                '<=',
                $request->jatuh_tempo_selesai
            );
        }


        return $query;
    }


    /**
     * Summary laporan.
     */
    private function getSummary(Request $request): array
    {
        $query = $this->buildQuery($request);

        $totalInvoice =
            (float) (clone $query)
                ->sum('total');

        $totalDibayar =
            (float) (clone $query)
                ->sum('total_dibayar');

        $totalUtang =
            (float) (clone $query)
                ->sum('sisa_utang');

        $utangJatuhTempo =
            (float) (clone $query)
                ->where(
                    'sisa_utang',
                    '>',
                    0
                )
                ->whereDate(
                    'tanggal_jatuh_tempo',
                    '<',
                    now()->toDateString()
                )
                ->sum('sisa_utang');

        $jumlahInvoice =
            (clone $query)->count();

        $jumlahBelumLunas =
            (clone $query)
                ->where(
                    'sisa_utang',
                    '>',
                    0
                )
                ->count();


        return [
            'jumlah_invoice' =>
                $jumlahInvoice,

            'jumlah_belum_lunas' =>
                $jumlahBelumLunas,

            'total_invoice' =>
                $totalInvoice,

            'total_dibayar' =>
                $totalDibayar,

            'total_utang' =>
                $totalUtang,

            'utang_jatuh_tempo' =>
                $utangJatuhTempo,
        ];
    }


    /**
     * Tentukan status jatuh tempo invoice.
     */
    private function getStatusJatuhTempo(
        InvoiceSupplier $invoice
    ): array {

        $sisaUtang =
            (float) $invoice->sisa_utang;


        if ($sisaUtang <= 0) {

            return [
                'status' => 'LUNAS',
                'hari' => 0,
                'label' => 'Lunas',
            ];
        }


        if (!$invoice->tanggal_jatuh_tempo) {

            return [
                'status' => 'TANPA_JATUH_TEMPO',
                'hari' => null,
                'label' => '-',
            ];
        }


        $today =
            Carbon::today();

        $dueDate =
            Carbon::parse(
                $invoice->tanggal_jatuh_tempo
            )->startOfDay();


        if ($dueDate->lt($today)) {

            $days =
                $dueDate->diffInDays(
                    $today
                );

            return [
                'status' =>
                    'JATUH_TEMPO',

                'hari' =>
                    $days,

                'label' =>
                    $days
                    . ' hari terlambat',
            ];
        }


        if ($dueDate->equalTo($today)) {

            return [
                'status' =>
                    'HARI_INI',

                'hari' =>
                    0,

                'label' =>
                    'Jatuh tempo hari ini',
            ];
        }


        $days =
            $today->diffInDays(
                $dueDate
            );


        return [
            'status' =>
                'BELUM_JATUH_TEMPO',

            'hari' =>
                $days,

            'label' =>
                $days
                . ' hari lagi',
        ];
    }


    /**
     * Tambahkan informasi umur utang
     * untuk kebutuhan web / PDF / Excel.
     */
    private function prepareInvoices(
        $invoices
    ) {
        foreach ($invoices as $invoice) {

            $info =
                $this->getStatusJatuhTempo(
                    $invoice
                );

            $invoice->status_jatuh_tempo =
                $info['status'];

            $invoice->umur_utang =
                $info['hari'];

            $invoice->label_jatuh_tempo =
                $info['label'];
        }

        return $invoices;
    }


    /**
     * Export PDF.
     */
    public function pdf(Request $request)
    {
        $invoices =
            $this->buildQuery($request)
                ->orderBy(
                    'tanggal_jatuh_tempo'
                )
                ->orderBy(
                    'tanggal_invoice'
                )
                ->orderBy('id')
                ->get();


        $invoices =
            $this->prepareInvoices(
                $invoices
            );


        $summary =
            $this->getSummary(
                $request
            );


        $pdf = Pdf::loadView(
            'laporan.utang-pdf',
            compact(
                'invoices',
                'summary'
            )
        )
            ->setPaper(
                'a4',
                'landscape'
            );


        return $pdf->download(
            'laporan-utang-'
            . now()->format('Ymd-His')
            . '.pdf'
        );
    }


    /**
     * Export Excel.
     */
    public function excel(Request $request)
    {
        $invoices =
            $this->buildQuery($request)
                ->orderBy(
                    'tanggal_jatuh_tempo'
                )
                ->orderBy(
                    'tanggal_invoice'
                )
                ->orderBy('id')
                ->get();


        $invoices =
            $this->prepareInvoices(
                $invoices
            );


        $summary =
            $this->getSummary(
                $request
            );


        $spreadsheet =
            new Spreadsheet();


        $sheet =
            $spreadsheet
                ->getActiveSheet();


        $sheet->setTitle(
            'Laporan Utang'
        );


        /*
        |--------------------------------------------------------------------------
        | JUDUL
        |--------------------------------------------------------------------------
        */

        $sheet->setCellValue(
            'A1',
            'LAPORAN UTANG SUPPLIER'
        );

        $sheet->mergeCells(
            'A1:K1'
        );


        $sheet->setCellValue(
            'A2',
            'Dicetak: '
            . now()->format(
                'd/m/Y H:i'
            )
        );

        $sheet->mergeCells(
            'A2:K2'
        );


        /*
        |--------------------------------------------------------------------------
        | SUMMARY
        |--------------------------------------------------------------------------
        */

        $sheet->setCellValue(
            'A4',
            'Jumlah Invoice'
        );

        $sheet->setCellValue(
            'B4',
            $summary['jumlah_invoice']
        );


        $sheet->setCellValue(
            'D4',
            'Total Invoice'
        );

        $sheet->setCellValue(
            'E4',
            $summary['total_invoice']
        );


        $sheet->setCellValue(
            'G4',
            'Sudah Dibayar'
        );

        $sheet->setCellValue(
            'H4',
            $summary['total_dibayar']
        );


        $sheet->setCellValue(
            'J4',
            'Sisa Utang'
        );

        $sheet->setCellValue(
            'K4',
            $summary['total_utang']
        );


        $sheet->setCellValue(
            'A5',
            'Invoice Belum Lunas'
        );

        $sheet->setCellValue(
            'B5',
            $summary['jumlah_belum_lunas']
        );


        $sheet->setCellValue(
            'D5',
            'Utang Jatuh Tempo'
        );

        $sheet->setCellValue(
            'E5',
            $summary['utang_jatuh_tempo']
        );


        /*
        |--------------------------------------------------------------------------
        | HEADER
        |--------------------------------------------------------------------------
        */

        $headers = [
            'No',
            'No Invoice',
            'Invoice Supplier',
            'Supplier',
            'No PO',
            'Tanggal Invoice',
            'Jatuh Tempo',
            'Total Invoice',
            'Sudah Dibayar',
            'Sisa Utang',
            'Status / Umur Utang',
        ];


        $column = 'A';

        foreach ($headers as $header) {

            $sheet->setCellValue(
                $column . '7',
                $header
            );

            $column++;
        }


        /*
        |--------------------------------------------------------------------------
        | DATA
        |--------------------------------------------------------------------------
        */

        $row = 8;
        $no = 1;


        foreach ($invoices as $invoice) {

            $sheet->setCellValue(
                'A' . $row,
                $no
            );

            $sheet->setCellValue(
                'B' . $row,
                $invoice
                    ->nomor_invoice_internal
            );

            $sheet->setCellValue(
                'C' . $row,
                $invoice
                    ->nomor_invoice_supplier
                    ?: '-'
            );

            $sheet->setCellValue(
                'D' . $row,
                $invoice->supplier
                    ?->nama_supplier
                    ?: '-'
            );

            $sheet->setCellValue(
                'E' . $row,
                $invoice->purchaseOrder
                    ?->nomor_po
                    ?: '-'
            );

            $sheet->setCellValue(
                'F' . $row,
                $invoice->tanggal_invoice
                    ?->format('d/m/Y')
                    ?: '-'
            );

            $sheet->setCellValue(
                'G' . $row,
                $invoice->tanggal_jatuh_tempo
                    ?->format('d/m/Y')
                    ?: '-'
            );

            $sheet->setCellValue(
                'H' . $row,
                (float)
                $invoice->total
            );

            $sheet->setCellValue(
                'I' . $row,
                (float)
                $invoice->total_dibayar
            );

            $sheet->setCellValue(
                'J' . $row,
                (float)
                $invoice->sisa_utang
            );

            $sheet->setCellValue(
                'K' . $row,
                $invoice
                    ->label_jatuh_tempo
            );


            $row++;
            $no++;
        }


        /*
        |--------------------------------------------------------------------------
        | TOTAL
        |--------------------------------------------------------------------------
        */

        $sheet->setCellValue(
            'A' . $row,
            'TOTAL'
        );

        $sheet->mergeCells(
            'A' . $row
            . ':G' . $row
        );


        $sheet->setCellValue(
            'H' . $row,
            $summary['total_invoice']
        );

        $sheet->setCellValue(
            'I' . $row,
            $summary['total_dibayar']
        );

        $sheet->setCellValue(
            'J' . $row,
            $summary['total_utang']
        );


        /*
        |--------------------------------------------------------------------------
        | FORMAT
        |--------------------------------------------------------------------------
        */

        $sheet
            ->getStyle('A1:K1')
            ->getFont()
            ->setBold(true)
            ->setSize(14);


        $sheet
            ->getStyle('A7:K7')
            ->getFont()
            ->setBold(true);


        $sheet
            ->getStyle(
                'A' . $row
                . ':K' . $row
            )
            ->getFont()
            ->setBold(true);


        $sheet
            ->getStyle(
                'H8:J' . $row
            )
            ->getNumberFormat()
            ->setFormatCode(
                '#,##0.00'
            );


        foreach (
            range('A', 'K')
            as $column
        ) {

            $sheet
                ->getColumnDimension(
                    $column
                )
                ->setAutoSize(true);
        }


        /*
        |--------------------------------------------------------------------------
        | DOWNLOAD
        |--------------------------------------------------------------------------
        */

        $writer =
            new Xlsx(
                $spreadsheet
            );


        $fileName =
            'laporan-utang-'
            . now()->format(
                'Ymd-His'
            )
            . '.xlsx';


        $tempFile =
            tempnam(
                sys_get_temp_dir(),
                'utang_'
            );


        $writer->save(
            $tempFile
        );


        return response()
            ->download(
                $tempFile,
                $fileName
            )
            ->deleteFileAfterSend(
                true
            );
    }
}