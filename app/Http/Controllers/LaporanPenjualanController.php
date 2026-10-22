<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Gudang;
use App\Models\InvoicePenjualan;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanPenjualanController extends Controller
{
    public function index(Request $request)
    {
        $query = InvoicePenjualan::with(['customer', 'gudang']);
        $this->applyFilters($query, $request);

        $jumlahInvoice = (clone $query)->count();
        $totalPenjualan = (float) (clone $query)->sum('total');
        $totalDibayar = (float) (clone $query)->sum('sudah_dibayar');
        $totalSisaPiutang = (float) (clone $query)->sum('sisa_piutang');

        $invoices = $query->orderByDesc('tanggal_invoice')->orderByDesc('id')->paginate(20)->withQueryString();
        $customers = Customer::where('is_active', true)->orderBy('nama_customer')->get();
        $gudangs = Gudang::where('is_active', true)->orderBy('nama_gudang')->get();

        return view('laporan.penjualan-index', compact(
            'invoices',
            'customers',
            'gudangs',
            'jumlahInvoice',
            'totalPenjualan',
            'totalDibayar',
            'totalSisaPiutang'
        ));
    }

    public function pdf(Request $request)
    {
        $query = InvoicePenjualan::with(['customer', 'gudang']);
        $this->applyFilters($query, $request);

        $invoices = $query->orderBy('tanggal_invoice')->orderBy('id')->get();
        $jumlahInvoice = $invoices->count();
        $totalPenjualan = (float) $invoices->sum('total');
        $totalDibayar = (float) $invoices->sum('sudah_dibayar');
        $totalSisaPiutang = (float) $invoices->sum('sisa_piutang');

        $pdf = Pdf::loadView('laporan.penjualan-pdf', compact(
            'invoices',
            'jumlahInvoice',
            'totalPenjualan',
            'totalDibayar',
            'totalSisaPiutang'
        ))->setPaper('a4', 'landscape');

        return $pdf->stream('laporan-penjualan-' . now()->format('Y-m-d-His') . '.pdf');
    }

    public function excel(Request $request): StreamedResponse
    {
        $query = InvoicePenjualan::with(['customer', 'gudang']);
        $this->applyFilters($query, $request);

        $invoices = $query->orderBy('tanggal_invoice')->orderBy('id')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Penjualan');

        $sheet->mergeCells('A1:L1');
        $sheet->setCellValue('A1', 'LAPORAN PENJUALAN');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $periode = 'Semua Periode';
        if ($request->filled('tanggal_mulai') && $request->filled('tanggal_selesai')) {
            $periode = $request->tanggal_mulai . ' s/d ' . $request->tanggal_selesai;
        } elseif ($request->filled('tanggal_mulai')) {
            $periode = 'Mulai ' . $request->tanggal_mulai;
        } elseif ($request->filled('tanggal_selesai')) {
            $periode = 'Sampai ' . $request->tanggal_selesai;
        }

        $sheet->mergeCells('A2:L2');
        $sheet->setCellValue('A2', 'Periode: ' . $periode);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $headers = ['Tanggal', 'No. Invoice', 'Customer', 'Gudang', 'Tipe Bayar', 'Subtotal', 'Diskon', 'Pajak', 'Total', 'Dibayar', 'Sisa Piutang', 'Status'];
        $column = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($column . '4', $header);
            $column++;
        }
        $sheet->getStyle('A4:L4')->getFont()->setBold(true);
        $sheet->getStyle('A4:L4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $row = 5;
        foreach ($invoices as $inv) {
            $sheet->setCellValue('A' . $row, $inv->tanggal_invoice ? $inv->tanggal_invoice->format('d/m/Y') : '-');
            $sheet->setCellValue('B' . $row, $inv->nomor_invoice);
            $sheet->setCellValue('C' . $row, $inv->customer?->nama_customer ?? '-');
            $sheet->setCellValue('D' . $row, $inv->gudang?->nama_gudang ?? '-');
            $sheet->setCellValue('E' . $row, $inv->tipe_pembayaran);
            $sheet->setCellValue('F' . $row, (float) $inv->subtotal);
            $sheet->setCellValue('G' . $row, (float) $inv->diskon);
            $sheet->setCellValue('H' . $row, (float) $inv->pajak);
            $sheet->setCellValue('I' . $row, (float) $inv->total);
            $sheet->setCellValue('J' . $row, (float) $inv->sudah_dibayar);
            $sheet->setCellValue('K' . $row, (float) $inv->sisa_piutang);
            $sheet->setCellValue('L' . $row, $inv->status_pembayaran);
            $row++;
        }

        $sheet->setCellValue('E' . $row, 'TOTAL');
        $sheet->setCellValue('F' . $row, (float) $invoices->sum('subtotal'));
        $sheet->setCellValue('G' . $row, (float) $invoices->sum('diskon'));
        $sheet->setCellValue('H' . $row, (float) $invoices->sum('pajak'));
        $sheet->setCellValue('I' . $row, (float) $invoices->sum('total'));
        $sheet->setCellValue('J' . $row, (float) $invoices->sum('sudah_dibayar'));
        $sheet->setCellValue('K' . $row, (float) $invoices->sum('sisa_piutang'));
        $sheet->getStyle('E' . $row . ':K' . $row)->getFont()->setBold(true);

        if ($row >= 5) {
            $sheet->getStyle('F5:K' . $row)->getNumberFormat()->setFormatCode('"Rp" #,##0');
        }

        foreach (range('A', 'L') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $sheet->freezePane('A5');

        $filename = 'laporan-penjualan-' . now()->format('Y-m-d-His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    private function applyFilters($query, Request $request): void
    {
        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('tanggal_invoice', '>=', $request->tanggal_mulai);
        }

        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('tanggal_invoice', '<=', $request->tanggal_selesai);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('status')) {
            $query->where('status_pembayaran', $request->status);
        }

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('nomor_invoice', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('nama_customer', 'like', "%{$search}%")
                        ->orWhere('kode_customer', 'like', "%{$search}%");
                  });
            });
        }
    }
}
