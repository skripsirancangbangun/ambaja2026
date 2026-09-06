<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use App\Services\SystemAccountingService;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CoaController extends Controller
{
    protected SystemAccountingService $accountingService;

    public function __construct(SystemAccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    public function index(Request $request)
    {
        $query = Akun::withCount('jurnalDetails');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_akun', 'like', "%{$search}%")
                  ->orWhere('kode_akun', 'like', "%{$search}%");
            });
        }

        if ($request->filled('kategori_akun')) {
            $query->where('kategori_akun', $request->kategori_akun);
        }

        $akuns = $query->orderBy('kode_akun')->paginate(20)->withQueryString();
        $canEditOpeningBalance = $this->accountingService->canEditOpeningBalance();

        return view('coa.index', compact('akuns', 'canEditOpeningBalance'));
    }

    public function create()
    {
        $canEditOpeningBalance = $this->accountingService->canEditOpeningBalance();
        return view('coa.create', compact('canEditOpeningBalance'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_akun' => 'required|string|max:50|unique:akuns,kode_akun',
            'nama_akun' => 'required|string|max:255',
            'kategori_akun' => 'required|string',
            'sub_kategori' => 'nullable|string|max:255',
            'saldo_normal' => 'required|in:DEBIT,KREDIT',
            'is_active' => 'required|boolean',
        ]);

        $validated['saldo_awal'] = 0;

        Akun::create($validated);

        return redirect()
            ->route('coa.index')
            ->with('success', 'Akun berhasil ditambahkan ke Chart of Accounts.');
    }

    public function edit(Akun $coa)
    {
        if ($coa->jurnalDetails()->exists()) {
            return redirect()
                ->route('coa.index')
                ->with('error', 'Akun tidak dapat diubah karena sudah memiliki riwayat mutasi jurnal.');
        }

        $canEditOpeningBalance = $this->accountingService->canEditOpeningBalance();
        return view('coa.edit', ['akun' => $coa, 'canEditOpeningBalance' => $canEditOpeningBalance]);
    }

    public function update(Request $request, Akun $coa)
    {
        if ($coa->jurnalDetails()->exists()) {
            return redirect()
                ->route('coa.index')
                ->with('error', 'Akun tidak dapat diubah karena sudah memiliki riwayat mutasi jurnal.');
        }

        $validated = $request->validate([
            'kode_akun' => 'required|string|max:50|unique:akuns,kode_akun,' . $coa->id,
            'nama_akun' => 'required|string|max:255',
            'kategori_akun' => 'required|string',
            'sub_kategori' => 'nullable|string|max:255',
            'saldo_normal' => 'required|in:DEBIT,KREDIT',
            'is_active' => 'required|boolean',
        ]);

        $coa->update($validated);

        return redirect()
            ->route('coa.index')
            ->with('success', 'Data Akun berhasil diperbarui.');
    }

    public function destroy(Akun $coa)
    {
        if ($coa->jurnalDetails()->exists()) {
            return redirect()
                ->route('coa.index')
                ->with('error', 'Akun tidak dapat dihapus karena sudah memiliki riwayat mutasi jurnal.');
        }

        $coa->delete();

        return redirect()
            ->route('coa.index')
            ->with('success', 'Akun berhasil dihapus dari Chart of Accounts.');
    }

    /**
     * Download Excel Template for COA Import
     */
    public function downloadTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template Import COA');

        $headers = ['Kode Akun', 'Nama Akun', 'Kategori Akun', 'Sub Kategori', 'Saldo Normal (DEBIT/KREDIT)', 'Saldo Awal (Rp)'];
        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue($col . '1', $h);
            $col++;
        }

        $sheet->getStyle('A1:F1')->getFont()->setBold(true);
        $sheet->getStyle('A1:F1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Sample Rows
        $samples = [
            ['1101', 'Kas Kecil', 'Aset', 'Aset Lancar', 'DEBIT', 5000000],
            ['1102', 'Bank BCA', 'Aset', 'Aset Lancar', 'DEBIT', 25000000],
            ['2101', 'Utang Usaha', 'Kewajiban', 'Kewajiban Lancar', 'KREDIT', 0],
            ['3101', 'Modal Pemilik', 'Ekuitas', 'Ekuitas', 'KREDIT', 30000000],
        ];

        $rowNum = 2;
        foreach ($samples as $sample) {
            $sheet->setCellValue('A' . $rowNum, $sample[0]);
            $sheet->setCellValue('B' . $rowNum, $sample[1]);
            $sheet->setCellValue('C' . $rowNum, $sample[2]);
            $sheet->setCellValue('D' . $rowNum, $sample[3]);
            $sheet->setCellValue('E' . $rowNum, $sample[4]);
            $sheet->setCellValue('F' . $rowNum, $sample[5]);
            $rowNum++;
        }

        foreach (range('A', 'F') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, 'template-import-coa-ambaja.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Import COA Data & Saldo Awal (in Migration Mode)
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls,csv|max:2048',
        ]);

        $file = $request->file('file');
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getRealPath());
        $rows = $spreadsheet->getActiveSheet()->toArray();

        $imported = 0;
        $canEditOpeningBalance = $this->accountingService->canEditOpeningBalance();

        foreach ($rows as $index => $row) {
            if ($index === 0) continue; // Skip Header

            $kodeAkun = trim((string)($row[0] ?? ''));
            $namaAkun = trim((string)($row[1] ?? ''));
            $kategoriAkun = trim((string)($row[2] ?? ''));
            $subKategori = trim((string)($row[3] ?? ''));
            $saldoNormal = strtoupper(trim((string)($row[4] ?? 'DEBIT')));
            $saldoAwal = (float) ($row[5] ?? 0);

            if (empty($kodeAkun) || empty($namaAkun)) continue;

            if (!in_array($saldoNormal, ['DEBIT', 'KREDIT'])) {
                $saldoNormal = 'DEBIT';
            }

            $data = [
                'nama_akun' => $namaAkun,
                'kategori_akun' => $kategoriAkun ?: 'Aset',
                'sub_kategori' => $subKategori ?: null,
                'saldo_normal' => $saldoNormal,
                'is_active' => true,
            ];

            if ($canEditOpeningBalance) {
                $data['saldo_awal'] = $saldoAwal;
            }

            Akun::updateOrCreate(
                ['kode_akun' => $kodeAkun],
                $data
            );

            $imported++;
        }

        $message = "Berhasil mengimpor {$imported} akun COA.";
        if (!$canEditOpeningBalance) {
            $message .= " (Catatan: Saldo awal diabaikan karena sistem tidak sedang dalam Mode Migrasi Data).";
        }

        return redirect()->back()->with('success', $message);
    }
}

