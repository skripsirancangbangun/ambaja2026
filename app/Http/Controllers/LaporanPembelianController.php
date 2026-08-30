<?php

namespace App\Http\Controllers;

use App\Models\InvoiceSupplier;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanPembelianController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | HALAMAN LAPORAN
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $query = InvoiceSupplier::with([
            'supplier',
            'purchaseOrder',
        ]);

        $this->applyFilters($query, $request);

        /*
        |--------------------------------------------------------------------------
        | SUMMARY
        |--------------------------------------------------------------------------
        */

        $jumlahInvoice = (clone $query)->count();

        $totalPembelian = (float) (clone $query)
            ->sum('total');

        $totalDibayar = (float) (clone $query)
            ->sum('total_dibayar');

        $totalSisaUtang = (float) (clone $query)
            ->sum('sisa_utang');


        /*
        |--------------------------------------------------------------------------
        | DATA TABEL
        |--------------------------------------------------------------------------
        */

        $invoices = $query
            ->orderByDesc('tanggal_invoice')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();


        /*
        |--------------------------------------------------------------------------
        | SUPPLIER
        |--------------------------------------------------------------------------
        */

        $suppliers = Supplier::where('is_active', true)
            ->orderBy('nama_supplier')
            ->get();


        return view(
            'laporan.pembelian-index',
            compact(
                'invoices',
                'suppliers',
                'jumlahInvoice',
                'totalPembelian',
                'totalDibayar',
                'totalSisaUtang'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CETAK PDF
    |--------------------------------------------------------------------------
    */

    public function pdf(Request $request)
    {
        $query = InvoiceSupplier::with([
            'supplier',
            'purchaseOrder',
        ]);

        $this->applyFilters($query, $request);

        $invoices = $query
            ->orderBy('tanggal_invoice')
            ->orderBy('id')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | SUMMARY
        |--------------------------------------------------------------------------
        */

        $jumlahInvoice = $invoices->count();

        $totalPembelian = (float) $invoices->sum('total');

        $totalDibayar = (float) $invoices->sum('total_dibayar');

        $totalSisaUtang = (float) $invoices->sum('sisa_utang');


        $pdf = Pdf::loadView(
            'laporan.pembelian-pdf',
            compact(
                'invoices',
                'jumlahInvoice',
                'totalPembelian',
                'totalDibayar',
                'totalSisaUtang'
            )
        )
            ->setPaper('a4', 'landscape');


        return $pdf->stream(
            'laporan-pembelian-' . now()->format('Y-m-d-His') . '.pdf'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | EXPORT EXCEL
    |--------------------------------------------------------------------------
    */

    public function excel(Request $request): StreamedResponse
    {
        $query = InvoiceSupplier::with([
            'supplier',
            'purchaseOrder',
        ]);

        $this->applyFilters($query, $request);

        $invoices = $query
            ->orderBy('tanggal_invoice')
            ->orderBy('id')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | BUAT SPREADSHEET
        |--------------------------------------------------------------------------
        */

        $spreadsheet = new Spreadsheet();

        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setTitle('Laporan Pembelian');


        /*
        |--------------------------------------------------------------------------
        | JUDUL
        |--------------------------------------------------------------------------
        */

        $sheet->mergeCells('A1:L1');

        $sheet->setCellValue(
            'A1',
            'LAPORAN PEMBELIAN'
        );

        $sheet->getStyle('A1')->getFont()
            ->setBold(true)
            ->setSize(16);

        $sheet->getStyle('A1')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);


        /*
        |--------------------------------------------------------------------------
        | PERIODE
        |--------------------------------------------------------------------------
        */

        $periode = 'Semua Periode';

        if (
            $request->filled('tanggal_mulai') &&
            $request->filled('tanggal_selesai')
        ) {
            $periode =
                $request->tanggal_mulai
                . ' s/d '
                . $request->tanggal_selesai;
        } elseif ($request->filled('tanggal_mulai')) {
            $periode =
                'Mulai '
                . $request->tanggal_mulai;
        } elseif ($request->filled('tanggal_selesai')) {
            $periode =
                'Sampai '
                . $request->tanggal_selesai;
        }


        $sheet->mergeCells('A2:L2');

        $sheet->setCellValue(
            'A2',
            'Periode: ' . $periode
        );

        $sheet->getStyle('A2')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER);


        /*
        |--------------------------------------------------------------------------
        | HEADER TABEL
        |--------------------------------------------------------------------------
        */

        $headers = [
            'Tanggal',
            'No. Invoice',
            'Invoice Supplier',
            'Supplier',
            'No. PO',
            'Subtotal',
            'Diskon',
            'Pajak',
            'Total',
            'Dibayar',
            'Sisa Utang',
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


        $sheet->getStyle('A4:L4')
            ->getFont()
            ->setBold(true);

        $sheet->getStyle('A4:L4')
            ->getAlignment()
            ->setHorizontal(
                Alignment::HORIZONTAL_CENTER
            );


        /*
        |--------------------------------------------------------------------------
        | DATA
        |--------------------------------------------------------------------------
        */

        $row = 5;

        foreach ($invoices as $invoice) {

            $sheet->setCellValue(
                'A' . $row,
                $invoice->tanggal_invoice
                    ? $invoice->tanggal_invoice->format('d/m/Y')
                    : '-'
            );

            $sheet->setCellValue(
                'B' . $row,
                $invoice->nomor_invoice_internal
            );

            $sheet->setCellValue(
                'C' . $row,
                $invoice->nomor_invoice_supplier ?: '-'
            );

            $sheet->setCellValue(
                'D' . $row,
                $invoice->supplier?->nama_supplier ?? '-'
            );

            $sheet->setCellValue(
                'E' . $row,
                $invoice->purchaseOrder?->nomor_po ?? '-'
            );

            $sheet->setCellValue(
                'F' . $row,
                (float) $invoice->subtotal
            );

            $sheet->setCellValue(
                'G' . $row,
                (float) $invoice->diskon
            );

            $sheet->setCellValue(
                'H' . $row,
                (float) $invoice->pajak
            );

            $sheet->setCellValue(
                'I' . $row,
                (float) $invoice->total
            );

            $sheet->setCellValue(
                'J' . $row,
                (float) $invoice->total_dibayar
            );

            $sheet->setCellValue(
                'K' . $row,
                (float) $invoice->sisa_utang
            );

            $sheet->setCellValue(
                'L' . $row,
                match ($invoice->status) {
                    'BELUM_LUNAS' => 'Belum Lunas',
                    'SEBAGIAN' => 'Sebagian',
                    'LUNAS' => 'Lunas',
                    default => $invoice->status,
                }
            );

            $row++;
        }


        /*
        |--------------------------------------------------------------------------
        | TOTAL
        |--------------------------------------------------------------------------
        */

        $sheet->setCellValue(
            'E' . $row,
            'TOTAL'
        );

        $sheet->setCellValue(
            'F' . $row,
            (float) $invoices->sum('subtotal')
        );

        $sheet->setCellValue(
            'G' . $row,
            (float) $invoices->sum('diskon')
        );

        $sheet->setCellValue(
            'H' . $row,
            (float) $invoices->sum('pajak')
        );

        $sheet->setCellValue(
            'I' . $row,
            (float) $invoices->sum('total')
        );

        $sheet->setCellValue(
            'J' . $row,
            (float) $invoices->sum('total_dibayar')
        );

        $sheet->setCellValue(
            'K' . $row,
            (float) $invoices->sum('sisa_utang')
        );

        $sheet->getStyle(
            'E' . $row . ':K' . $row
        )
            ->getFont()
            ->setBold(true);


        /*
        |--------------------------------------------------------------------------
        | FORMAT RUPIAH
        |--------------------------------------------------------------------------
        */

        if ($row >= 5) {

            $sheet->getStyle(
                'F5:K' . $row
            )
                ->getNumberFormat()
                ->setFormatCode(
                    '"Rp" #,##0'
                );
        }


        /*
        |--------------------------------------------------------------------------
        | WIDTH KOLOM
        |--------------------------------------------------------------------------
        */

        foreach (
            range('A', 'L')
            as $column
        ) {
            $sheet
                ->getColumnDimension($column)
                ->setAutoSize(true);
        }


        /*
        |--------------------------------------------------------------------------
        | FREEZE HEADER
        |--------------------------------------------------------------------------
        */

        $sheet->freezePane('A5');


        /*
        |--------------------------------------------------------------------------
        | DOWNLOAD
        |--------------------------------------------------------------------------
        */

        $filename =
            'laporan-pembelian-'
            . now()->format('Y-m-d-His')
            . '.xlsx';


        return response()->streamDownload(
            function () use ($spreadsheet) {

                $writer =
                    new Xlsx($spreadsheet);

                $writer->save('php://output');

                $spreadsheet->disconnectWorksheets();

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
    | FILTER LAPORAN
    |--------------------------------------------------------------------------
    */

    private function applyFilters(
        $query,
        Request $request
    ): void {

        /*
        |--------------------------------------------------------------------------
        | TANGGAL MULAI
        |--------------------------------------------------------------------------
        */

        if ($request->filled('tanggal_mulai')) {

            $query->whereDate(
                'tanggal_invoice',
                '>=',
                $request->tanggal_mulai
            );
        }


        /*
        |--------------------------------------------------------------------------
        | TANGGAL SELESAI
        |--------------------------------------------------------------------------
        */

        if ($request->filled('tanggal_selesai')) {

            $query->whereDate(
                'tanggal_invoice',
                '<=',
                $request->tanggal_selesai
            );
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
        | STATUS
        |--------------------------------------------------------------------------
        */

        if ($request->filled('status')) {

            $query->where(
                'status',
                $request->status
            );
        }


        /*
        |--------------------------------------------------------------------------
        | SEARCH
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {

            $search =
                trim($request->search);

            $query->where(
                function ($q) use ($search) {

                    $q->where(
                        'nomor_invoice_internal',
                        'like',
                        "%{$search}%"
                    )

                        ->orWhere(
                            'nomor_invoice_supplier',
                            'like',
                            "%{$search}%"
                        )

                        ->orWhereHas(
                            'supplier',
                            function ($supplierQuery) use ($search) {

                                $supplierQuery
                                    ->where(
                                        'nama_supplier',
                                        'like',
                                        "%{$search}%"
                                    )

                                    ->orWhere(
                                        'kode_supplier',
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
                        );
                }
            );
        }
    }
}