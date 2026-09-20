<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use App\Models\MutasiKasBank;
use App\Models\JurnalDetail;
use App\Models\InvoicePenjualan;
use App\Models\PenerimaanPiutang;
use App\Models\UangMasuk;
use App\Models\UangKeluar;
use App\Models\PembayaranSupplier;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LaporanKeuanganController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | LABA RUGI
    |--------------------------------------------------------------------------
    */

    public function labaRugi(Request $request)
    {
        $data = $this->getLabaRugiData($request);
        return view('laporan.laba-rugi-index', $data);
    }

    public function labaRugiPdf(Request $request)
    {
        $data = $this->getLabaRugiData($request);
        $pdf = Pdf::loadView('laporan.laba-rugi-pdf', $data)->setPaper('a4', 'portrait');
        return $pdf->stream('laporan-laba-rugi-' . now()->format('Y-m-d-His') . '.pdf');
    }

    public function labaRugiExcel(Request $request): StreamedResponse
    {
        $data = $this->getLabaRugiData($request);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Laba Rugi');

        $sheet->mergeCells('A1:C1');
        $sheet->setCellValue('A1', 'CV AMBAJA (ANDIKA MUSTIKA)');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:C2');
        $sheet->setCellValue('A2', 'LAPORAN LABA RUGI (STANDAR PSAK)');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A3:C3');
        $sheet->setCellValue('A3', 'Periode: ' . date('d/m/Y', strtotime($data['tanggalMulai'])) . ' s/d ' . date('d/m/Y', strtotime($data['tanggalSelesai'])));
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $row = 5;

        // 1. PENJUALAN
        $sheet->setCellValue('A' . $row, 'PENJUALAN (REVENUE)');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        foreach ($data['penjualanKotorData'] as $p) {
            $sheet->setCellValue('A' . $row, $p['akun']->kode_akun);
            $sheet->setCellValue('B' . $row, $p['akun']->nama_akun);
            $sheet->setCellValue('C' . $row, (float) $p['nominal']);
            $row++;
        }
        foreach ($data['diskonPenjualanData'] as $p) {
            $sheet->setCellValue('A' . $row, $p['akun']->kode_akun);
            $sheet->setCellValue('B' . $row, '(-) ' . $p['akun']->nama_akun);
            $sheet->setCellValue('C' . $row, -(float) $p['nominal']);
            $row++;
        }
        foreach ($data['returPenjualanData'] as $p) {
            $sheet->setCellValue('A' . $row, $p['akun']->kode_akun);
            $sheet->setCellValue('B' . $row, '(-) ' . $p['akun']->nama_akun);
            $sheet->setCellValue('C' . $row, -(float) $p['nominal']);
            $row++;
        }
        $sheet->setCellValue('B' . $row, 'PENJUALAN BERSIH (NET SALES)');
        $sheet->setCellValue('C' . $row, (float) $data['penjualanBersih']);
        $sheet->getStyle('B' . $row . ':C' . $row)->getFont()->setBold(true);
        $row += 2;

        // 2. HARGA POKOK PENJUALAN
        $sheet->setCellValue('A' . $row, 'HARGA POKOK PENJUALAN (HPP / COGS)');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        foreach ($data['hppUtamaData'] as $p) {
            $sheet->setCellValue('A' . $row, $p['akun']->kode_akun);
            $sheet->setCellValue('B' . $row, $p['akun']->nama_akun);
            $sheet->setCellValue('C' . $row, (float) $p['nominal']);
            $row++;
        }
        foreach ($data['biayaAngkutData'] as $p) {
            $sheet->setCellValue('A' . $row, $p['akun']->kode_akun);
            $sheet->setCellValue('B' . $row, '(+) ' . $p['akun']->nama_akun);
            $sheet->setCellValue('C' . $row, (float) $p['nominal']);
            $row++;
        }
        foreach ($data['biayaBongkarData'] as $p) {
            $sheet->setCellValue('A' . $row, $p['akun']->kode_akun);
            $sheet->setCellValue('B' . $row, '(+) ' . $p['akun']->nama_akun);
            $sheet->setCellValue('C' . $row, (float) $p['nominal']);
            $row++;
        }
        foreach ($data['potonganPembelianData'] as $p) {
            $sheet->setCellValue('A' . $row, $p['akun']->kode_akun);
            $sheet->setCellValue('B' . $row, '(-) ' . $p['akun']->nama_akun);
            $sheet->setCellValue('C' . $row, -(float) $p['nominal']);
            $row++;
        }
        foreach ($data['returPembelianData'] as $p) {
            $sheet->setCellValue('A' . $row, $p['akun']->kode_akun);
            $sheet->setCellValue('B' . $row, '(-) ' . $p['akun']->nama_akun);
            $sheet->setCellValue('C' . $row, -(float) $p['nominal']);
            $row++;
        }
        $sheet->setCellValue('B' . $row, 'TOTAL HARGA POKOK PENJUALAN');
        $sheet->setCellValue('C' . $row, (float) $data['totalHpp']);
        $sheet->getStyle('B' . $row . ':C' . $row)->getFont()->setBold(true);
        $row += 2;

        // 3. LABA KOTOR
        $labelLabaKotor = $data['labaKotor'] >= 0 ? 'LABA KOTOR (GROSS PROFIT)' : 'RUGI KOTOR (GROSS LOSS)';
        $sheet->setCellValue('B' . $row, $labelLabaKotor);
        $sheet->setCellValue('C' . $row, (float) $data['labaKotor']);
        $sheet->getStyle('B' . $row . ':C' . $row)->getFont()->setBold(true)->setSize(11);
        $row += 2;

        // 4. BEBAN OPERASIONAL
        $sheet->setCellValue('A' . $row, 'BEBAN OPERASIONAL (OPERATING EXPENSES)');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        foreach ($data['bebanOpsData'] as $b) {
            $sheet->setCellValue('A' . $row, $b['akun']->kode_akun);
            $sheet->setCellValue('B' . $row, $b['akun']->nama_akun);
            $sheet->setCellValue('C' . $row, (float) $b['nominal']);
            $row++;
        }
        $sheet->setCellValue('B' . $row, 'TOTAL BEBAN OPERASIONAL');
        $sheet->setCellValue('C' . $row, (float) $data['totalBebanOps']);
        $sheet->getStyle('B' . $row . ':C' . $row)->getFont()->setBold(true);
        $row += 2;

        // 5. LABA OPERASIONAL
        $labelLabaOps = $data['labaOperasional'] >= 0 ? 'LABA OPERASIONAL (OPERATING PROFIT)' : 'RUGI OPERASIONAL (OPERATING LOSS)';
        $sheet->setCellValue('B' . $row, $labelLabaOps);
        $sheet->setCellValue('C' . $row, (float) $data['labaOperasional']);
        $sheet->getStyle('B' . $row . ':C' . $row)->getFont()->setBold(true)->setSize(11);
        $row += 2;

        // 6. PENDAPATAN & BEBAN LAIN-LAIN
        $sheet->setCellValue('A' . $row, 'PENDAPATAN & BEBAN LAIN-LAIN');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        foreach ($data['pendLainData'] as $p) {
            $sheet->setCellValue('A' . $row, $p['akun']->kode_akun);
            $sheet->setCellValue('B' . $row, '(+) ' . $p['akun']->nama_akun);
            $sheet->setCellValue('C' . $row, (float) $p['nominal']);
            $row++;
        }
        foreach ($data['bebanLainData'] as $b) {
            $sheet->setCellValue('A' . $row, $b['akun']->kode_akun);
            $sheet->setCellValue('B' . $row, '(-) ' . $b['akun']->nama_akun);
            $sheet->setCellValue('C' . $row, -(float) $b['nominal']);
            $row++;
        }

        // 7. LABA SEBELUM PAJAK
        $labelEBT = $data['labaSebelumPajak'] >= 0 ? 'LABA BERSIH SEBELUM PAJAK (PROFIT BEFORE TAX)' : 'RUGI BERSIH SEBELUM PAJAK';
        $sheet->setCellValue('B' . $row, $labelEBT);
        $sheet->setCellValue('C' . $row, (float) $data['labaSebelumPajak']);
        $sheet->getStyle('B' . $row . ':C' . $row)->getFont()->setBold(true);
        $row += 2;

        // 8. PAJAK
        foreach ($data['bebanPajakData'] as $p) {
            $sheet->setCellValue('A' . $row, $p['akun']->kode_akun);
            $sheet->setCellValue('B' . $row, '(-) ' . $p['akun']->nama_akun);
            $sheet->setCellValue('C' . $row, -(float) $p['nominal']);
            $row++;
        }

        // 9. LABA BERSIH SETELAH PAJAK
        $labelEAT = $data['labaSetelahPajak'] >= 0 ? 'LABA BERSIH SETELAH PAJAK (NET PROFIT AFTER TAX)' : 'RUGI BERSIH SETELAH PAJAK (NET LOSS)';
        $sheet->setCellValue('B' . $row, $labelEAT);
        $sheet->setCellValue('C' . $row, (float) $data['labaSetelahPajak']);
        $sheet->getStyle('B' . $row . ':C' . $row)->getFont()->setBold(true)->setSize(12);

        $sheet->getStyle('C5:C' . $row)->getNumberFormat()->setFormatCode('"Rp" #,##0');
        foreach (range('A', 'C') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'laporan-laba-rugi-psak-' . now()->format('Y-m-d-His') . '.xlsx';
        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    private function getLabaRugiData(Request $request): array
    {
        $tanggalMulai = $request->input('tanggal_mulai', date('Y-01-01'));
        $tanggalSelesai = $request->input('tanggal_selesai', date('Y-m-d'));

        $calcNominal = function ($kodeOrAkun) use ($tanggalMulai, $tanggalSelesai) {
            if (is_string($kodeOrAkun)) {
                $akun = Akun::where('is_active', true)->where('kode_akun', $kodeOrAkun)->first();
            } else {
                $akun = $kodeOrAkun;
            }
            if (!$akun) return 0;

            $debit = JurnalDetail::where('akun_id', $akun->id)
                ->whereHas('jurnal', fn($q) => $q->whereDate('tanggal', '>=', $tanggalMulai)->whereDate('tanggal', '<=', $tanggalSelesai))
                ->sum('debit');

            $kredit = JurnalDetail::where('akun_id', $akun->id)
                ->whereHas('jurnal', fn($q) => $q->whereDate('tanggal', '>=', $tanggalMulai)->whereDate('tanggal', '<=', $tanggalSelesai))
                ->sum('kredit');

            return ($akun->saldo_normal === 'KREDIT') ? ($kredit - $debit) : ($debit - $kredit);
        };

        // 1. PENJUALAN
        $penjualanKotorAkuns = Akun::where('is_active', true)->where('kode_akun', 'like', '4.1.01%')->get();
        $penjualanKotorData = []; $totalPenjualanKotor = 0;
        foreach ($penjualanKotorAkuns as $a) {
            $nom = $calcNominal($a);
            $penjualanKotorData[] = ['akun' => $a, 'nominal' => $nom];
            $totalPenjualanKotor += $nom;
        }

        $diskonPenjualanAkuns = Akun::where('is_active', true)->where('kode_akun', 'like', '4.1.03%')->get();
        $diskonPenjualanData = []; $totalDiskonPenjualan = 0;
        foreach ($diskonPenjualanAkuns as $a) {
            $nom = $calcNominal($a);
            $diskonPenjualanData[] = ['akun' => $a, 'nominal' => $nom];
            $totalDiskonPenjualan += $nom;
        }

        $returPenjualanAkuns = Akun::where('is_active', true)->where('kode_akun', 'like', '4.1.02%')->get();
        $returPenjualanData = []; $totalReturPenjualan = 0;
        foreach ($returPenjualanAkuns as $a) {
            $nom = $calcNominal($a);
            $returPenjualanData[] = ['akun' => $a, 'nominal' => $nom];
            $totalReturPenjualan += $nom;
        }

        $penjualanBersih = $totalPenjualanKotor - $totalDiskonPenjualan - $totalReturPenjualan;

        // 2. HARGA POKOK PENJUALAN (HPP)
        $hppUtamaAkuns = Akun::where('is_active', true)->where('kode_akun', 'like', '5.1.01%')->get();
        $hppUtamaData = []; $totalHppUtama = 0;
        foreach ($hppUtamaAkuns as $a) {
            $nom = $calcNominal($a);
            $hppUtamaData[] = ['akun' => $a, 'nominal' => $nom];
            $totalHppUtama += $nom;
        }

        $biayaAngkutAkuns = Akun::where('is_active', true)->where('kode_akun', 'like', '5.1.02%')->get();
        $biayaAngkutData = []; $totalBiayaAngkut = 0;
        foreach ($biayaAngkutAkuns as $a) {
            $nom = $calcNominal($a);
            $biayaAngkutData[] = ['akun' => $a, 'nominal' => $nom];
            $totalBiayaAngkut += $nom;
        }

        $biayaBongkarAkuns = Akun::where('is_active', true)->where('kode_akun', 'like', '5.1.03%')->get();
        $biayaBongkarData = []; $totalBiayaBongkar = 0;
        foreach ($biayaBongkarAkuns as $a) {
            $nom = $calcNominal($a);
            $biayaBongkarData[] = ['akun' => $a, 'nominal' => $nom];
            $totalBiayaBongkar += $nom;
        }

        $returPembelianAkuns = Akun::where('is_active', true)->where('kode_akun', 'like', '5.1.04%')->get();
        $returPembelianData = []; $totalReturPembelian = 0;
        foreach ($returPembelianAkuns as $a) {
            $nom = $calcNominal($a);
            $returPembelianData[] = ['akun' => $a, 'nominal' => $nom];
            $totalReturPembelian += $nom;
        }

        $potonganPembelianAkuns = Akun::where('is_active', true)->where('kode_akun', 'like', '5.1.05%')->get();
        $potonganPembelianData = []; $totalPotonganPembelian = 0;
        foreach ($potonganPembelianAkuns as $a) {
            $nom = $calcNominal($a);
            $potonganPembelianData[] = ['akun' => $a, 'nominal' => $nom];
            $totalPotonganPembelian += $nom;
        }

        $totalHpp = $totalHppUtama + $totalBiayaAngkut + $totalBiayaBongkar - $totalReturPembelian - $totalPotonganPembelian;

        // 3. LABA KOTOR
        $labaKotor = $penjualanBersih - $totalHpp;

        // 4. BEBAN OPERASIONAL
        $bebanOpsAkuns = Akun::where('is_active', true)->where('kode_akun', 'like', '6.%')->orderBy('kode_akun')->get();
        $bebanOpsData = []; $totalBebanOps = 0;
        foreach ($bebanOpsAkuns as $a) {
            $nom = $calcNominal($a);
            $bebanOpsData[] = ['akun' => $a, 'nominal' => $nom];
            $totalBebanOps += $nom;
        }

        // 5. LABA OPERASIONAL
        $labaOperasional = $labaKotor - $totalBebanOps;

        // 6. PENDAPATAN LAIN-LAIN
        $pendLainAkuns = Akun::where('is_active', true)->where('kode_akun', 'like', '7.%')->orderBy('kode_akun')->get();
        $pendLainData = []; $totalPendLain = 0;
        foreach ($pendLainAkuns as $a) {
            $nom = $calcNominal($a);
            $pendLainData[] = ['akun' => $a, 'nominal' => $nom];
            $totalPendLain += $nom;
        }

        // 7. BEBAN LAIN-LAIN
        $bebanLainAkuns = Akun::where('is_active', true)->where('kode_akun', 'like', '8.%')->orderBy('kode_akun')->get();
        $bebanLainData = []; $totalBebanLain = 0;
        foreach ($bebanLainAkuns as $a) {
            $nom = $calcNominal($a);
            $bebanLainData[] = ['akun' => $a, 'nominal' => $nom];
            $totalBebanLain += $nom;
        }

        // 8. LABA SEBELUM PAJAK
        $labaSebelumPajak = $labaOperasional + $totalPendLain - $totalBebanLain;

        // 9. PAJAK
        $bebanPajakAkuns = Akun::where('is_active', true)->where('kode_akun', 'like', '9.%')->orderBy('kode_akun')->get();
        $bebanPajakData = []; $totalBebanPajak = 0;
        foreach ($bebanPajakAkuns as $a) {
            $nom = $calcNominal($a);
            $bebanPajakData[] = ['akun' => $a, 'nominal' => $nom];
            $totalBebanPajak += $nom;
        }

        // 10. LABA SETELAH PAJAK
        $labaSetelahPajak = $labaSebelumPajak - $totalBebanPajak;

        // Fallbacks for compatibility
        $totalPendapatan = $penjualanBersih + $totalPendLain;
        $totalBeban = $totalHpp + $totalBebanOps + $totalBebanLain + $totalBebanPajak;
        $labaRugiBersih = $labaSetelahPajak;
        $pendapatanData = array_merge($penjualanKotorData, $pendLainData);
        $bebanData = array_merge($hppUtamaData, $bebanOpsData, $bebanLainData, $bebanPajakData);

        return compact(
            'tanggalMulai', 'tanggalSelesai',
            'penjualanKotorData', 'totalPenjualanKotor',
            'diskonPenjualanData', 'totalDiskonPenjualan',
            'returPenjualanData', 'totalReturPenjualan',
            'penjualanBersih',
            'hppUtamaData', 'totalHppUtama',
            'biayaAngkutData', 'totalBiayaAngkut',
            'biayaBongkarData', 'totalBiayaBongkar',
            'returPembelianData', 'totalReturPembelian',
            'potonganPembelianData', 'totalPotonganPembelian',
            'totalHpp',
            'labaKotor',
            'bebanOpsData', 'totalBebanOps',
            'labaOperasional',
            'pendLainData', 'totalPendLain',
            'bebanLainData', 'totalBebanLain',
            'labaSebelumPajak',
            'bebanPajakData', 'totalBebanPajak',
            'labaSetelahPajak',
            'totalPendapatan', 'totalBeban', 'labaRugiBersih',
            'pendapatanData', 'bebanData'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | NERACA (POSISI KEUANGAN)
    |--------------------------------------------------------------------------
    */

    public function neraca(Request $request)
    {
        $data = $this->getNeracaData($request);
        return view('laporan.neraca-index', $data);
    }

    public function neracaPdf(Request $request)
    {
        $data = $this->getNeracaData($request);
        $pdf = Pdf::loadView('laporan.neraca-pdf', $data)->setPaper('a4', 'portrait');
        return $pdf->stream('laporan-neraca-' . now()->format('Y-m-d-His') . '.pdf');
    }

    public function neracaExcel(Request $request): StreamedResponse
    {
        $data = $this->getNeracaData($request);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Neraca Posisi Keuangan');

        $sheet->mergeCells('A1:D1');
        $sheet->setCellValue('A1', 'LAPORAN NERACA (POSISI KEUANGAN)');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:D2');
        $sheet->setCellValue('A2', 'Per Tanggal: ' . $data['tanggal']);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A4', 'ASET / AKTIVA');
        $sheet->getStyle('A4')->getFont()->setBold(true);
        $row = 5;
        foreach ($data['asetData'] as $a) {
            $sheet->setCellValue('A' . $row, $a['akun']->kode_akun);
            $sheet->setCellValue('B' . $row, $a['akun']->nama_akun);
            $sheet->setCellValue('C' . $row, (float) $a['nominal']);
            $row++;
        }
        $sheet->setCellValue('B' . $row, 'TOTAL ASET');
        $sheet->setCellValue('C' . $row, (float) $data['totalAset']);
        $sheet->getStyle('B' . $row . ':C' . $row)->getFont()->setBold(true);

        $row += 2;
        $sheet->setCellValue('A' . $row, 'KEWAJIBAN & EKUITAS');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        foreach ($data['kewajibanData'] as $k) {
            $sheet->setCellValue('A' . $row, $k['akun']->kode_akun);
            $sheet->setCellValue('B' . $row, $k['akun']->nama_akun);
            $sheet->setCellValue('C' . $row, (float) $k['nominal']);
            $row++;
        }
        foreach ($data['ekuitasData'] as $e) {
            $sheet->setCellValue('A' . $row, $e['akun']->kode_akun);
            $sheet->setCellValue('B' . $row, $e['akun']->nama_akun);
            $sheet->setCellValue('C' . $row, (float) $e['nominal']);
            $row++;
        }
        $sheet->setCellValue('B' . $row, 'Laba / (Rugi) Kumulatif');
        $sheet->setCellValue('C' . $row, (float) $data['labaRugiKumulatif']);
        $row++;
        $sheet->setCellValue('B' . $row, 'TOTAL KEWAJIBAN & EKUITAS');
        $sheet->setCellValue('C' . $row, (float) ($data['totalKewajiban'] + $data['totalEkuitas']));
        $sheet->getStyle('B' . $row . ':C' . $row)->getFont()->setBold(true);

        $sheet->getStyle('C5:C' . $row)->getNumberFormat()->setFormatCode('"Rp" #,##0');
        foreach (range('A', 'C') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'laporan-neraca-' . now()->format('Y-m-d-His') . '.xlsx';
        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    private function getNeracaData(Request $request): array
    {
        $tanggal = $request->input('tanggal', date('Y-m-d'));

        $asetLancarAkuns = Akun::where('is_active', true)
            ->where(function ($q) {
                $q->where('kode_akun', 'like', '1.1.%')
                  ->orWhere('sub_kategori', 'Aset Lancar');
            })
            ->orderBy('kode_akun')
            ->get();

        $asetTetapAkuns = Akun::where('is_active', true)
            ->where(function ($q) {
                $q->where('kode_akun', 'like', '1.2.%')
                  ->orWhere('sub_kategori', 'Aset Tetap');
            })
            ->orderBy('kode_akun')
            ->get();

        $kewajibanAkuns = Akun::where('is_active', true)
            ->where(function ($q) {
                $q->where(DB::raw('LOWER(kategori_akun)'), 'kewajiban')
                  ->orWhere('kode_akun', 'like', '2.%');
            })
            ->orderBy('kode_akun')
            ->get();

        $ekuitasAkuns = Akun::where('is_active', true)
            ->where(function ($q) {
                $q->where(DB::raw('LOWER(kategori_akun)'), 'ekuitas')
                  ->orWhere('kode_akun', 'like', '3.%');
            })
            ->orderBy('kode_akun')
            ->get();

        $calcSaldoKumulatif = function (Akun $akun) use ($tanggal) {
            $debit = JurnalDetail::where('akun_id', $akun->id)->whereHas('jurnal', fn($q) => $q->whereDate('tanggal', '<=', $tanggal))->sum('debit');
            $kredit = JurnalDetail::where('akun_id', $akun->id)->whereHas('jurnal', fn($q) => $q->whereDate('tanggal', '<=', $tanggal))->sum('kredit');
            if ($akun->saldo_normal === 'DEBIT') {
                return (float) $akun->saldo_awal + ($debit - $kredit);
            } else {
                if (str_starts_with($akun->kode_akun, '1.') || strtoupper($akun->kategori_akun) === 'ASET') {
                    return - ((float) $akun->saldo_awal + ($kredit - $debit));
                }
                return (float) $akun->saldo_awal + ($kredit - $debit);
            }
        };

        $asetLancarData = []; $totalAsetLancar = 0;
        foreach ($asetLancarAkuns as $a) {
            $val = $calcSaldoKumulatif($a);
            $asetLancarData[] = ['akun' => $a, 'nominal' => $val];
            $totalAsetLancar += $val;
        }

        $asetTetapData = []; $totalAsetTetap = 0;
        foreach ($asetTetapAkuns as $a) {
            $val = $calcSaldoKumulatif($a);
            $asetTetapData[] = ['akun' => $a, 'nominal' => $val];
            $totalAsetTetap += $val;
        }

        $totalAset = $totalAsetLancar + $totalAsetTetap;
        $asetData = array_merge($asetLancarData, $asetTetapData);

        $kewajibanData = []; $totalKewajiban = 0;
        foreach ($kewajibanAkuns as $k) { $val = $calcSaldoKumulatif($k); $kewajibanData[] = ['akun' => $k, 'nominal' => $val]; $totalKewajiban += $val; }

        $pendapatanForNeraca = Akun::where('is_active', true)
            ->where(function ($q) {
                $q->whereIn(DB::raw('LOWER(kategori_akun)'), ['penjualan', 'pendapatan', 'pendapatan lain-lain'])
                  ->orWhere('kode_akun', 'like', '4.%')
                  ->orWhere('kode_akun', 'like', '7.%');
            })->get();

        $bebanForNeraca = Akun::where('is_active', true)
            ->where(function ($q) {
                $q->whereIn(DB::raw('LOWER(kategori_akun)'), ['hpp', 'beban', 'beban lain-lain', 'beban pajak'])
                  ->orWhere('kode_akun', 'like', '5.%')
                  ->orWhere('kode_akun', 'like', '6.%')
                  ->orWhere('kode_akun', 'like', '8.%')
                  ->orWhere('kode_akun', 'like', '9.%');
            })->get();

        $totPendapatan = 0;
        foreach ($pendapatanForNeraca as $p) {
            $d = JurnalDetail::where('akun_id', $p->id)->whereHas('jurnal', fn($q) => $q->whereDate('tanggal', '<=', $tanggal))->sum('debit');
            $k = JurnalDetail::where('akun_id', $p->id)->whereHas('jurnal', fn($q) => $q->whereDate('tanggal', '<=', $tanggal))->sum('kredit');
            if ($p->saldo_normal === 'KREDIT') {
                $totPendapatan += ($k - $d);
            } else {
                $totPendapatan -= ($d - $k);
            }
        }

        $totBeban = 0;
        foreach ($bebanForNeraca as $b) {
            $d = JurnalDetail::where('akun_id', $b->id)->whereHas('jurnal', fn($q) => $q->whereDate('tanggal', '<=', $tanggal))->sum('debit');
            $k = JurnalDetail::where('akun_id', $b->id)->whereHas('jurnal', fn($q) => $q->whereDate('tanggal', '<=', $tanggal))->sum('kredit');
            if ($b->saldo_normal === 'DEBIT') {
                $totBeban += ($d - $k);
            } else {
                $totBeban -= ($k - $d);
            }
        }

        $labaRugiKumulatif = $totPendapatan - $totBeban;

        $ekuitasData = []; $totalEkuitas = 0;
        foreach ($ekuitasAkuns as $e) {
            $val = $calcSaldoKumulatif($e);
            if ($e->kode_akun === '3.1.01' || str_contains(strtolower($e->nama_akun), 'modal')) {
                $val += $labaRugiKumulatif;
            }
            $ekuitasData[] = ['akun' => $e, 'nominal' => $val];
            $totalEkuitas += $val;
        }

        return compact('tanggal', 'asetLancarData', 'totalAsetLancar', 'asetTetapData', 'totalAsetTetap', 'asetData', 'kewajibanData', 'ekuitasData', 'labaRugiKumulatif', 'totalAset', 'totalKewajiban', 'totalEkuitas');
    }

    /*
    |--------------------------------------------------------------------------
    | ARUS KAS (METODE LANGSUNG / DIRECT METHOD)
    |--------------------------------------------------------------------------
    */

    public function arusKas(Request $request)
    {
        $data = $this->getArusKasDirectData($request);
        return view('laporan.arus-kas-index', $data);
    }

    public function arusKasPdf(Request $request)
    {
        $data = $this->getArusKasDirectData($request);
        $pdf = Pdf::loadView('laporan.arus-kas-pdf', $data)->setPaper('a4', 'portrait');
        return $pdf->stream('laporan-arus-kas-direct-' . now()->format('Y-m-d-His') . '.pdf');
    }

    public function arusKasExcel(Request $request): StreamedResponse
    {
        $data = $this->getArusKasDirectData($request);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Arus Kas');

        $sheet->mergeCells('A1:B1');
        $sheet->setCellValue('A1', 'LAPORAN ARUS KAS (METODE LANGSUNG)');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $rows = [
            ['ARUS KAS DARI AKTIVITAS OPERASIONAL', ''],
            ['  Penerimaan Kas dari Pelanggan & Penjualan', (float) $data['penerimaanPelanggan']],
            ['  Penerimaan Kas Operasional Lainnya', (float) $data['penerimaanLainnya']],
            ['  Pembayaran Kas kepada Supplier / Pembelian', -(float) $data['pembayaranSupplier']],
            ['  Pembayaran Kas untuk Beban Operasional', -(float) $data['pembayaranBeban']],
            ['Arus Kas Bersih dari Aktivitas Operasional', (float) $data['arusKasOperasional']],
            ['', ''],
            ['ARUS KAS DARI AKTIVITAS INVESTASI', ''],
            ['  Pembelian Aset Tetap / Peralatan', -(float) $data['pembelianAsetTetap']],
            ['Arus Kas Bersih dari Aktivitas Investasi', (float) $data['arusKasInvestasi']],
            ['', ''],
            ['ARUS KAS DARI AKTIVITAS PENDANAAN', ''],
            ['  Setoran Modal Owner', (float) $data['setoranModal']],
            ['  Pengambilan Prive Owner', -(float) $data['penarikanPrive']],
            ['Arus Kas Bersih dari Aktivitas Pendanaan', (float) $data['arusKasPendanaan']],
            ['', ''],
            ['Kenaikan / (Penurunan) Bersih Kas & Bank', (float) $data['kenaikanBersihKas']],
            ['Saldo Kas & Bank Awal Periode', (float) $data['saldoKasAwal']],
            ['Saldo Kas & Bank Akhir Periode', (float) $data['saldoKasAkhir']],
        ];

        $r = 5;
        foreach ($rows as $item) {
            $sheet->setCellValue('A' . $r, $item[0]);
            if ($item[1] !== '') {
                $sheet->setCellValue('B' . $r, $item[1]);
            }
            $r++;
        }

        $sheet->getStyle('B5:B' . $r)->getNumberFormat()->setFormatCode('"Rp" #,##0');
        foreach (range('A', 'B') as $col) { $sheet->getColumnDimension($col)->setAutoSize(true); }

        $filename = 'laporan-arus-kas-direct-' . now()->format('Y-m-d-His') . '.xlsx';
        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    private function getArusKasDirectData(Request $request): array
    {
        $tanggalMulai = $request->input('tanggal_mulai', date('Y-01-01'));
        $tanggalSelesai = $request->input('tanggal_selesai', date('Y-m-d'));

        // Active Cash & Bank COAs
        $kasAkuns = Akun::where('is_active', true)
            ->where(function ($q) {
                $q->whereIn('kode_akun', ['1.1.01', '1.1.02'])
                  ->orWhere('nama_akun', 'like', '%Kas%')
                  ->orWhere('nama_akun', 'like', '%Bank%');
            })
            ->get();
        $kasIds = $kasAkuns->pluck('id')->toArray();

        // 1. OPERASIONAL
        $penjualanTunai = (float) InvoicePenjualan::whereDate('tanggal_invoice', '>=', $tanggalMulai)
            ->whereDate('tanggal_invoice', '<=', $tanggalSelesai)
            ->where('tipe_pembayaran', 'Tunai')
            ->where('status_pembayaran', '!=', 'DIBATALKAN')
            ->sum('total');

        $pelunasanPiutang = (float) PenerimaanPiutang::whereDate('tanggal', '>=', $tanggalMulai)
            ->whereDate('tanggal', '<=', $tanggalSelesai)
            ->sum('total_dibayar');

        $jurnalPenerimaanPlg = (float) JurnalDetail::whereIn('akun_id', $kasIds)
            ->where('debit', '>', 0)
            ->whereHas('jurnal', fn($q) => $q->whereDate('tanggal', '>=', $tanggalMulai)->whereDate('tanggal', '<=', $tanggalSelesai)->whereNull('sumber'))
            ->whereHas('jurnal.details', function($q) use ($kasIds) {
                $q->whereNotIn('akun_id', $kasIds)
                  ->whereHas('akun', fn($ak) => $ak->where('kode_akun', 'like', '4.%')->orWhere('kode_akun', '1.1.03'));
            })
            ->sum('debit');

        $penerimaanPelanggan = $penjualanTunai + $pelunasanPiutang + $jurnalPenerimaanPlg;

        $penerimaanLainnya = (float) UangMasuk::whereDate('tanggal', '>=', $tanggalMulai)
            ->whereDate('tanggal', '<=', $tanggalSelesai)
            ->sum('nominal');

        $pembayaranSupplier = (float) PembayaranSupplier::whereDate('tanggal', '>=', $tanggalMulai)
            ->whereDate('tanggal', '<=', $tanggalSelesai)
            ->sum('total_dibayar');

        $pembayaranBeban = (float) UangKeluar::whereDate('tanggal', '>=', $tanggalMulai)
            ->whereDate('tanggal', '<=', $tanggalSelesai)
            ->sum('total');

        $jurnalPembayaranBeban = (float) JurnalDetail::whereIn('akun_id', $kasIds)
            ->where('kredit', '>', 0)
            ->whereHas('jurnal', fn($q) => $q->whereDate('tanggal', '>=', $tanggalMulai)->whereDate('tanggal', '<=', $tanggalSelesai)->whereNull('sumber'))
            ->whereHas('jurnal.details', function($q) use ($kasIds) {
                $q->whereNotIn('akun_id', $kasIds)
                  ->whereHas('akun', fn($ak) => $ak->where('kode_akun', 'like', '5.%')->orWhere('kode_akun', 'like', '6.%')->orWhere('kode_akun', 'like', '8.%')->orWhere('kode_akun', 'like', '9.%'));
            })
            ->sum('kredit');

        $pembayaranBeban += $jurnalPembayaranBeban;

        $totalPenerimaanOperasional = $penerimaanPelanggan + $penerimaanLainnya;
        $totalPengeluaranOperasional = $pembayaranSupplier + $pembayaranBeban;
        $arusKasOperasional = $totalPenerimaanOperasional - $totalPengeluaranOperasional;

        // 2. INVESTASI
        $pembelianAsetTetap = (float) MutasiKasBank::whereDate('tanggal', '>=', $tanggalMulai)
            ->whereDate('tanggal', '<=', $tanggalSelesai)
            ->where('kredit', '>', 0)
            ->where('reference_type', 'INVESTASI')
            ->sum('kredit');

        $jurnalPembelianAset = (float) JurnalDetail::whereIn('akun_id', $kasIds)
            ->where('kredit', '>', 0)
            ->whereHas('jurnal', fn($q) => $q->whereDate('tanggal', '>=', $tanggalMulai)->whereDate('tanggal', '<=', $tanggalSelesai)->whereNull('sumber'))
            ->whereHas('jurnal.details', function($q) use ($kasIds) {
                $q->whereNotIn('akun_id', $kasIds)
                  ->whereHas('akun', fn($ak) => $ak->where('kode_akun', 'like', '1.2.%'));
            })
            ->sum('kredit');

        $pembelianAsetTetap += $jurnalPembelianAset;
        $arusKasInvestasi = -$pembelianAsetTetap;

        // 3. PENDANAAN
        $setoranModal = (float) MutasiKasBank::whereDate('tanggal', '>=', $tanggalMulai)
            ->whereDate('tanggal', '<=', $tanggalSelesai)
            ->where('debit', '>', 0)
            ->where('reference_type', 'MODAL_SETOR')
            ->sum('debit');

        $penarikanPrive = (float) MutasiKasBank::whereDate('tanggal', '>=', $tanggalMulai)
            ->whereDate('tanggal', '<=', $tanggalSelesai)
            ->where('kredit', '>', 0)
            ->where('reference_type', 'PRIVE')
            ->sum('kredit');

        $jurnalPrive = (float) JurnalDetail::whereIn('akun_id', $kasIds)
            ->where('kredit', '>', 0)
            ->whereHas('jurnal', fn($q) => $q->whereDate('tanggal', '>=', $tanggalMulai)->whereDate('tanggal', '<=', $tanggalSelesai)->whereNull('sumber'))
            ->whereHas('jurnal.details', function($q) use ($kasIds) {
                $q->whereNotIn('akun_id', $kasIds)
                  ->whereHas('akun', fn($ak) => $ak->where('nama_akun', 'like', '%Prive%'));
            })
            ->sum('kredit');

        $penarikanPrive += $jurnalPrive;
        $arusKasPendanaan = $setoranModal - $penarikanPrive;

        // SUMMARY KAS & BANK
        $kenaikanBersihKas = $arusKasOperasional + $arusKasInvestasi + $arusKasPendanaan;

        $saldoKasAwal = $kasAkuns->sum(function ($akun) use ($tanggalMulai) {
            $debit = JurnalDetail::where('akun_id', $akun->id)
                ->whereHas('jurnal', fn($q) => $q->whereDate('tanggal', '<', $tanggalMulai))
                ->sum('debit');

            $kredit = JurnalDetail::where('akun_id', $akun->id)
                ->whereHas('jurnal', fn($q) => $q->whereDate('tanggal', '<', $tanggalMulai))
                ->sum('kredit');

            return (float) $akun->saldo_awal + ($debit - $kredit);
        });

        $saldoKasAkhir = $saldoKasAwal + $kenaikanBersihKas;

        return compact(
            'tanggalMulai',
            'tanggalSelesai',
            'penerimaanPelanggan',
            'penerimaanLainnya',
            'pembayaranSupplier',
            'pembayaranBeban',
            'totalPenerimaanOperasional',
            'totalPengeluaranOperasional',
            'arusKasOperasional',
            'pembelianAsetTetap',
            'arusKasInvestasi',
            'setoranModal',
            'penarikanPrive',
            'arusKasPendanaan',
            'kenaikanBersihKas',
            'saldoKasAwal',
            'saldoKasAkhir'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PERUBAHAN EKUITAS
    |--------------------------------------------------------------------------
    */

    public function perubahanEkuitas(Request $request)
    {
        $data = $this->getPerubahanEkuitasData($request);
        return view('laporan.perubahan-ekuitas-index', $data);
    }

    public function perubahanEkuitasPdf(Request $request)
    {
        $data = $this->getPerubahanEkuitasData($request);
        $pdf = Pdf::loadView('laporan.perubahan-ekuitas-pdf', $data)->setPaper('a4', 'portrait');
        return $pdf->stream('laporan-perubahan-ekuitas-' . now()->format('Y-m-d-His') . '.pdf');
    }

    public function perubahanEkuitasExcel(Request $request): StreamedResponse
    {
        $data = $this->getPerubahanEkuitasData($request);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Perubahan Ekuitas');

        $sheet->mergeCells('A1:B1');
        $sheet->setCellValue('A1', 'LAPORAN PERUBAHAN EKUITAS');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $labelLabaRugi = $data['labaBersihPeriode'] >= 0 ? '(+) Laba Bersih Periode' : '(-) Rugi Bersih Periode';
        $labelPerubahanNet = $data['perubahanNet'] >= 0 ? 'Kenaikan Bersih Ekuitas' : 'Penurunan Bersih Ekuitas';

        $sheet->setCellValue('A4', 'Modal Awal CV Ambaja');
        $sheet->setCellValue('B4', (float) $data['modalAwal']);
        $sheet->setCellValue('A5', $labelLabaRugi);
        $sheet->setCellValue('B5', (float) $data['labaBersihPeriode']);
        $sheet->setCellValue('A6', '(-) Prive Owner Periode');
        $sheet->setCellValue('B6', -(float) $data['privePeriode']);
        $sheet->setCellValue('A7', $labelPerubahanNet);
        $sheet->setCellValue('B7', (float) $data['perubahanNet']);
        $sheet->setCellValue('A8', 'Modal Akhir CV Ambaja');
        $sheet->setCellValue('B8', (float) $data['modalAkhir']);
        $sheet->getStyle('A8:B8')->getFont()->setBold(true);

        $sheet->getStyle('B4:B8')->getNumberFormat()->setFormatCode('"Rp" #,##0');
        foreach (range('A', 'B') as $col) { $sheet->getColumnDimension($col)->setAutoSize(true); }

        $filename = 'laporan-perubahan-ekuitas-' . now()->format('Y-m-d-His') . '.xlsx';
        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    private function getPerubahanEkuitasData(Request $request): array
    {
        $tanggalMulai = $request->input('tanggal_mulai', date('Y-01-01'));
        $tanggalSelesai = $request->input('tanggal_selesai', date('Y-m-d'));

        // Ambil Laba Setelah Pajak dari Laporan Laba Rugi agar 100% konsisten
        $labaRugi = $this->getLabaRugiData($request);
        $labaBersihPeriode = $labaRugi['labaSetelahPajak'];

        $modalAkuns = Akun::where('is_active', true)
            ->where(function ($q) {
                $q->where(DB::raw('LOWER(kategori_akun)'), 'ekuitas')
                  ->orWhere('kode_akun', 'like', '3.%');
            })->get();

        $modalAwal = (float) $modalAkuns
            ->reject(fn($a) => str_contains(strtolower($a->nama_akun), 'prive') || str_contains(strtolower($a->nama_akun), 'laba ditahan'))
            ->sum('saldo_awal');

        $priveAkun = Akun::where('is_active', true)->where('nama_akun', 'like', '%Prive%')->first();
        $privePeriode = 0;
        if ($priveAkun) {
            $privePeriode = (float) JurnalDetail::where('akun_id', $priveAkun->id)
                ->whereHas('jurnal', fn($q) => $q->whereDate('tanggal', '>=', $tanggalMulai)->whereDate('tanggal', '<=', $tanggalSelesai))
                ->sum('debit');
        }

        $perubahanNet = $labaBersihPeriode - $privePeriode;
        $modalAkhir = $modalAwal + $perubahanNet;

        return compact('tanggalMulai', 'tanggalSelesai', 'modalAwal', 'labaBersihPeriode', 'privePeriode', 'perubahanNet', 'modalAkhir');
    }

    /*
    |--------------------------------------------------------------------------
    | NERACA SALDO (TRIAL BALANCE)
    |--------------------------------------------------------------------------
    */

    public function neracaSaldo(Request $request)
    {
        $tanggalMulai = $request->input('tanggal_mulai', date('Y-01-01'));
        $tanggalSelesai = $request->input('tanggal_selesai', date('Y-m-d'));

        $akuns = Akun::where('is_active', true)->orderBy('kode_akun')->get();

        $rows = [];
        $grandTotalDebit = 0;
        $grandTotalKredit = 0;

        foreach ($akuns as $akun) {
            $debitPrior = JurnalDetail::where('akun_id', $akun->id)
                ->whereHas('jurnal', fn($q) => $q->whereDate('tanggal', '<', $tanggalMulai))
                ->sum('debit');

            $kreditPrior = JurnalDetail::where('akun_id', $akun->id)
                ->whereHas('jurnal', fn($q) => $q->whereDate('tanggal', '<', $tanggalMulai))
                ->sum('kredit');

            $debitPeriod = JurnalDetail::where('akun_id', $akun->id)
                ->whereHas('jurnal', fn($q) => $q->whereDate('tanggal', '>=', $tanggalMulai)->whereDate('tanggal', '<=', $tanggalSelesai))
                ->sum('debit');

            $kreditPeriod = JurnalDetail::where('akun_id', $akun->id)
                ->whereHas('jurnal', fn($q) => $q->whereDate('tanggal', '>=', $tanggalMulai)->whereDate('tanggal', '<=', $tanggalSelesai))
                ->sum('kredit');

            $isNominalLabaRugi = preg_match('/^[456789]/', $akun->kode_akun);

            if ($isNominalLabaRugi) {
                $totalDebit = $debitPeriod;
                $totalKredit = $kreditPeriod;
                $saldoAwal = 0;
            } else {
                $totalDebit = $debitPrior + $debitPeriod;
                $totalKredit = $kreditPrior + $kreditPeriod;
                $saldoAwal = (float) $akun->saldo_awal;
            }

            $saldoAkhirNet = 0;
            if ($akun->saldo_normal === 'DEBIT') {
                $saldoAkhirNet = $saldoAwal + ($totalDebit - $totalKredit);
            } else {
                $saldoAkhirNet = $saldoAwal + ($totalKredit - $totalDebit);
            }

            $saldoDebit = 0;
            $saldoKredit = 0;

            if ($akun->saldo_normal === 'DEBIT') {
                if ($saldoAkhirNet >= 0) {
                    $saldoDebit = $saldoAkhirNet;
                } else {
                    $saldoKredit = abs($saldoAkhirNet);
                }
            } else {
                if ($saldoAkhirNet >= 0) {
                    $saldoKredit = $saldoAkhirNet;
                } else {
                    $saldoDebit = abs($saldoAkhirNet);
                }
            }

            $rows[] = [
                'akun' => $akun,
                'saldo_debit' => $saldoDebit,
                'saldo_kredit' => $saldoKredit,
            ];

            $grandTotalDebit += $saldoDebit;
            $grandTotalKredit += $saldoKredit;
        }

        return view('laporan.neraca-saldo', compact(
            'tanggalMulai',
            'tanggalSelesai',
            'rows',
            'grandTotalDebit',
            'grandTotalKredit'
        ));
    }
}
