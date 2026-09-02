<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\InvoicePenjualan;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanPiutangController extends Controller
{
    public function index(Request $request)
    {
        $perTanggal = $request->input('per_tanggal', date('Y-m-d'));
        $customerId = $request->input('customer_id');

        $query = InvoicePenjualan::with('customer')
            ->where('sisa_piutang', '>', 0)
            ->whereDate('tanggal_invoice', '<=', $perTanggal);

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        $invoices = $query->orderBy('tanggal_jatuh_tempo')->get();

        $aging = [
            'lancar' => 0,       // 0 - 30 hari
            'tempo_30' => 0,     // 31 - 60 hari
            'tempo_60' => 0,     // 61 - 90 hari
            'tempo_90plus' => 0, // > 90 hari
        ];

        $today = Carbon::parse($perTanggal);

        foreach ($invoices as $inv) {
            $dueDate = $inv->tanggal_jatuh_tempo ? Carbon::parse($inv->tanggal_jatuh_tempo) : Carbon::parse($inv->tanggal_invoice);
            $daysOverdue = $dueDate->isPast() ? (int) $dueDate->diffInDays($today) : 0;

            if ($daysOverdue <= 30) {
                $aging['lancar'] += $inv->sisa_piutang;
            } elseif ($daysOverdue <= 60) {
                $aging['tempo_30'] += $inv->sisa_piutang;
            } elseif ($daysOverdue <= 90) {
                $aging['tempo_60'] += $inv->sisa_piutang;
            } else {
                $aging['tempo_90plus'] += $inv->sisa_piutang;
            }
        }

        $totalPiutang = $invoices->sum('sisa_piutang');
        $customers = Customer::where('is_active', true)->orderBy('nama_customer')->get();

        return view('laporan.piutang-index', compact(
            'perTanggal',
            'customerId',
            'invoices',
            'aging',
            'totalPiutang',
            'customers'
        ));
    }

    public function pdf(Request $request)
    {
        $perTanggal = $request->input('per_tanggal', date('Y-m-d'));
        $customerId = $request->input('customer_id');

        $query = InvoicePenjualan::with('customer')
            ->where('sisa_piutang', '>', 0)
            ->whereDate('tanggal_invoice', '<=', $perTanggal);

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        $invoices = $query->orderBy('tanggal_jatuh_tempo')->get();

        $aging = [
            'lancar' => 0,
            'tempo_30' => 0,
            'tempo_60' => 0,
            'tempo_90plus' => 0,
        ];

        $today = Carbon::parse($perTanggal);

        foreach ($invoices as $inv) {
            $dueDate = $inv->tanggal_jatuh_tempo ? Carbon::parse($inv->tanggal_jatuh_tempo) : Carbon::parse($inv->tanggal_invoice);
            $daysOverdue = $dueDate->isPast() ? (int) $dueDate->diffInDays($today) : 0;

            if ($daysOverdue <= 30) {
                $aging['lancar'] += $inv->sisa_piutang;
            } elseif ($daysOverdue <= 60) {
                $aging['tempo_30'] += $inv->sisa_piutang;
            } elseif ($daysOverdue <= 90) {
                $aging['tempo_60'] += $inv->sisa_piutang;
            } else {
                $aging['tempo_90plus'] += $inv->sisa_piutang;
            }
        }

        $totalPiutang = $invoices->sum('sisa_piutang');

        $pdf = Pdf::loadView('laporan.piutang-pdf', compact(
            'perTanggal',
            'invoices',
            'aging',
            'totalPiutang'
        ))->setPaper('a4', 'landscape');

        return $pdf->stream('laporan-piutang-aging-' . now()->format('Y-m-d-His') . '.pdf');
    }

    public function excel(Request $request): StreamedResponse
    {
        $perTanggal = $request->input('per_tanggal', date('Y-m-d'));
        $customerId = $request->input('customer_id');

        $query = InvoicePenjualan::with('customer')
            ->where('sisa_piutang', '>', 0)
            ->whereDate('tanggal_invoice', '<=', $perTanggal);

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        $invoices = $query->orderBy('tanggal_jatuh_tempo')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Aging Piutang');

        $sheet->mergeCells('A1:I1');
        $sheet->setCellValue('A1', 'LAPORAN AGING PIUTANG DAGANG');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:I2');
        $sheet->setCellValue('A2', 'Posisi Per Tanggal: ' . $perTanggal);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $headers = ['No. Invoice', 'Customer', 'Tanggal Faktur', 'Jatuh Tempo', 'Lancar (0-30 Hari)', '31 - 60 Hari', '61 - 90 Hari', '> 90 Hari', 'Total Sisa Piutang'];
        $column = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($column . '4', $header);
            $column++;
        }
        $sheet->getStyle('A4:I4')->getFont()->setBold(true);
        $sheet->getStyle('A4:I4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $row = 5;
        $today = Carbon::parse($perTanggal);

        foreach ($invoices as $inv) {
            $dueDate = $inv->tanggal_jatuh_tempo ? Carbon::parse($inv->tanggal_jatuh_tempo) : Carbon::parse($inv->tanggal_invoice);
            $daysOverdue = $dueDate->isPast() ? (int) $dueDate->diffInDays($today) : 0;

            $lancar = $daysOverdue <= 30 ? $inv->sisa_piutang : 0;
            $t30 = ($daysOverdue > 30 && $daysOverdue <= 60) ? $inv->sisa_piutang : 0;
            $t60 = ($daysOverdue > 60 && $daysOverdue <= 90) ? $inv->sisa_piutang : 0;
            $t90 = $daysOverdue > 90 ? $inv->sisa_piutang : 0;

            $sheet->setCellValue('A' . $row, $inv->nomor_invoice);
            $sheet->setCellValue('B' . $row, $inv->customer?->nama_customer ?? '-');
            $sheet->setCellValue('C' . $row, $inv->tanggal_invoice ? $inv->tanggal_invoice->format('d/m/Y') : '-');
            $sheet->setCellValue('D' . $row, $inv->tanggal_jatuh_tempo ? $inv->tanggal_jatuh_tempo->format('d/m/Y') : '-');
            $sheet->setCellValue('E' . $row, (float) $lancar);
            $sheet->setCellValue('F' . $row, (float) $t30);
            $sheet->setCellValue('G' . $row, (float) $t60);
            $sheet->setCellValue('H' . $row, (float) $t90);
            $sheet->setCellValue('I' . $row, (float) $inv->sisa_piutang);
            $row++;
        }

        $sheet->setCellValue('D' . $row, 'TOTAL');
        $sheet->setCellValue('E' . $row, '=SUM(E5:E' . ($row - 1) . ')');
        $sheet->setCellValue('F' . $row, '=SUM(F5:F' . ($row - 1) . ')');
        $sheet->setCellValue('G' . $row, '=SUM(G5:G' . ($row - 1) . ')');
        $sheet->setCellValue('H' . $row, '=SUM(H5:H' . ($row - 1) . ')');
        $sheet->setCellValue('I' . $row, '=SUM(I5:I' . ($row - 1) . ')');
        $sheet->getStyle('D' . $row . ':I' . $row)->getFont()->setBold(true);

        if ($row >= 5) {
            $sheet->getStyle('E5:I' . $row)->getNumberFormat()->setFormatCode('"Rp" #,##0');
        }

        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'laporan-aging-piutang-' . now()->format('Y-m-d-His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
