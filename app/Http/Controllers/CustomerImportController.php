<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Services\SystemAccountingService;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerImportController extends Controller
{
    /**
     * Download Excel Template for Customer & Saldo Awal Piutang Import
     */
    public function downloadTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Import Customer');

        $headers = ['Kode Customer', 'Nama Customer', 'Telepon', 'Email', 'Alamat', 'Saldo Awal Piutang (Rp)'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '1', $h);
            $col++;
        }

        $sheet->getStyle('A1:F1')->getFont()->setBold(true);
        $sheet->getStyle('A1:F1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Sample Row Data
        $sheet->setCellValue('A2', 'CUST-001');
        $sheet->setCellValue('B2', 'PT Andika Jaya');
        $sheet->setCellValue('C2', '08123456789');
        $sheet->setCellValue('D2', 'andikajaya@example.com');
        $sheet->setCellValue('E2', 'Jl. Merdeka No. 45, Surabaya');
        $sheet->setCellValue('F2', 1500000);

        foreach (range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, 'template-import-customer-ambaja.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Import Customer Data & Saldo Awal Piutang
     */
    public function import(Request $request, SystemAccountingService $accountingService)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:2048',
        ]);

        $file = $request->file('file');
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
        $rows = $spreadsheet->getActiveSheet()->toArray();

        $imported = 0;
        $canEditOpeningBalance = $accountingService->canEditOpeningBalance();

        foreach ($rows as $index => $row) {
            if ($index === 0) continue; // Skip Header

            $kode = trim($row[0] ?? '');
            $nama = trim($row[1] ?? '');
            $telepon = trim($row[2] ?? '');
            $email = trim($row[3] ?? '');
            $alamat = trim($row[4] ?? '');
            $saldoAwal = (float) ($row[5] ?? 0);

            if (empty($nama)) continue;

            if (empty($kode)) {
                $count = Customer::count() + 1;
                $kode = 'CUST-' . str_pad($count, 3, '0', STR_PAD_LEFT);
            }

            $customer = Customer::updateOrCreate(
                ['kode_customer' => $kode],
                [
                    'nama_customer' => $nama,
                    'telepon' => $telepon,
                    'email' => $email,
                    'alamat' => $alamat,
                    'is_active' => true,
                ]
            );

            // Record Saldo Awal Piutang if system is in migration mode
            if ($canEditOpeningBalance && $saldoAwal > 0) {
                $accountingService->recordKartuPiutang(
                    $customer->id,
                    now()->startOfYear(),
                    'SALDO-AWAL-' . $customer->kode_customer,
                    'SALDO_AWAL',
                    $saldoAwal,
                    0,
                    'Saldo Awal Piutang Customer (Migrasi Sistem)'
                );
            }

            $imported++;
        }

        return redirect()->back()->with('success', "Berhasil mengimpor {$imported} data customer!");
    }
}
