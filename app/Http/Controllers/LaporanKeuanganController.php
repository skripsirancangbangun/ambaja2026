<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use App\Models\MutasiKasBank;
use App\Models\JurnalDetail;
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
        $sheet->setCellValue('A1', 'LAPORAN LABA RUGI');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:C2');
        $sheet->setCellValue('A2', 'Periode: ' . $data['tanggalMulai'] . ' s/d ' . $data['tanggalSelesai']);
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A4', 'PENDAPATAN (REVENUE)');
        $sheet->getStyle('A4')->getFont()->setBold(true);
        $row = 5;
        foreach ($data['pendapatanData'] as $p) {
            $sheet->setCellValue('A' . $row, $p['akun']->kode_akun);
            $sheet->setCellValue('B' . $row, $p['akun']->nama_akun);
            $sheet->setCellValue('C' . $row, (float) $p['nominal']);
            $row++;
        }
        $sheet->setCellValue('B' . $row, 'TOTAL PENDAPATAN');
        $sheet->setCellValue('C' . $row, (float) $data['totalPendapatan']);
        $sheet->getStyle('B' . $row . ':C' . $row)->getFont()->setBold(true);

        $row += 2;
        $sheet->setCellValue('A' . $row, 'BEBAN (EXPENSES)');
        $sheet->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;
        foreach ($data['bebanData'] as $b) {
            $sheet->setCellValue('A' . $row, $b['akun']->kode_akun);
            $sheet->setCellValue('B' . $row, $b['akun']->nama_akun);
            $sheet->setCellValue('C' . $row, (float) $b['nominal']);
            $row++;
        }
        $sheet->setCellValue('B' . $row, 'TOTAL BEBAN');
        $sheet->setCellValue('C' . $row, (float) $data['totalBeban']);
        $sheet->getStyle('B' . $row . ':C' . $row)->getFont()->setBold(true);

        $row += 2;
        $labelResult = $data['labaRugiBersih'] >= 0 ? 'LABA BERSIH (NET PROFIT)' : 'RUGI BERSIH (NET LOSS)';
        $sheet->setCellValue('B' . $row, $labelResult);
        $sheet->setCellValue('C' . $row, (float) $data['labaRugiBersih']);
        $sheet->getStyle('B' . $row . ':C' . $row)->getFont()->setBold(true)->setSize(12);

        $sheet->getStyle('C5:C' . $row)->getNumberFormat()->setFormatCode('"Rp" #,##0');
        foreach (range('A', 'C') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = 'laporan-laba-rugi-' . now()->format('Y-m-d-His') . '.xlsx';
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

        $pendapatanAkuns = Akun::where('kategori_akun', 'Pendapatan')->orderBy('kode_akun')->get();
        $bebanAkuns = Akun::where('kategori_akun', 'Beban')->orderBy('kode_akun')->get();

        $pendapatanData = [];
        $totalPendapatan = 0;
        foreach ($pendapatanAkuns as $p) {
            $debit = JurnalDetail::where('akun_id', $p->id)->whereHas('jurnal', fn($q) => $q->whereDate('tanggal', '>=', $tanggalMulai)->whereDate('tanggal', '<=', $tanggalSelesai))->sum('debit');
            $kredit = JurnalDetail::where('akun_id', $p->id)->whereHas('jurnal', fn($q) => $q->whereDate('tanggal', '>=', $tanggalMulai)->whereDate('tanggal', '<=', $tanggalSelesai))->sum('kredit');
            $nominal = ($p->saldo_normal === 'KREDIT') ? ($kredit - $debit) : ($debit - $kredit);
            $pendapatanData[] = ['akun' => $p, 'nominal' => $nominal];
            $totalPendapatan += $nominal;
        }

        $bebanData = [];
        $totalBeban = 0;
        foreach ($bebanAkuns as $b) {
            $debit = JurnalDetail::where('akun_id', $b->id)->whereHas('jurnal', fn($q) => $q->whereDate('tanggal', '>=', $tanggalMulai)->whereDate('tanggal', '<=', $tanggalSelesai))->sum('debit');
            $kredit = JurnalDetail::where('akun_id', $b->id)->whereHas('jurnal', fn($q) => $q->whereDate('tanggal', '>=', $tanggalMulai)->whereDate('tanggal', '<=', $tanggalSelesai))->sum('kredit');
            $nominal = ($b->saldo_normal === 'DEBIT') ? ($debit - $kredit) : ($kredit - $debit);
            $bebanData[] = ['akun' => $b, 'nominal' => $nominal];
            $totalBeban += $nominal;
        }

        $labaRugiBersih = $totalPendapatan - $totalBeban;

        return compact('tanggalMulai', 'tanggalSelesai', 'pendapatanData', 'bebanData', 'totalPendapatan', 'totalBeban', 'labaRugiBersih');
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

        $asetAkuns = Akun::where('kategori_akun', 'Aset')->orderBy('kode_akun')->get();
        $kewajibanAkuns = Akun::where('kategori_akun', 'Kewajiban')->orderBy('kode_akun')->get();
        $ekuitasAkuns = Akun::where('kategori_akun', 'Ekuitas')->orderBy('kode_akun')->get();

        $calcSaldoKumulatif = function (Akun $akun) use ($tanggal) {
            $debit = JurnalDetail::where('akun_id', $akun->id)->whereHas('jurnal', fn($q) => $q->whereDate('tanggal', '<=', $tanggal))->sum('debit');
            $kredit = JurnalDetail::where('akun_id', $akun->id)->whereHas('jurnal', fn($q) => $q->whereDate('tanggal', '<=', $tanggal))->sum('kredit');
            return ($akun->saldo_normal === 'DEBIT') ? ((float) $akun->saldo_awal + ($debit - $kredit)) : ((float) $akun->saldo_awal + ($kredit - $debit));
        };

        $asetData = []; $totalAset = 0;
        foreach ($asetAkuns as $a) { $val = $calcSaldoKumulatif($a); $asetData[] = ['akun' => $a, 'nominal' => $val]; $totalAset += $val; }

        $kewajibanData = []; $totalKewajiban = 0;
        foreach ($kewajibanAkuns as $k) { $val = $calcSaldoKumulatif($k); $kewajibanData[] = ['akun' => $k, 'nominal' => $val]; $totalKewajiban += $val; }

        $totPendapatan = Akun::where('kategori_akun', 'Pendapatan')->get()->sum(fn($p) => $calcSaldoKumulatif($p));
        $totBeban = Akun::where('kategori_akun', 'Beban')->get()->sum(fn($b) => $calcSaldoKumulatif($b));
        $labaRugiKumulatif = $totPendapatan - $totBeban;

        $ekuitasData = []; $totalEkuitas = 0;
        foreach ($ekuitasAkuns as $e) { $val = $calcSaldoKumulatif($e); $ekuitasData[] = ['akun' => $e, 'nominal' => $val]; $totalEkuitas += $val; }
        $totalEkuitas += $labaRugiKumulatif;

        return compact('tanggal', 'asetData', 'kewajibanData', 'ekuitasData', 'labaRugiKumulatif', 'totalAset', 'totalKewajiban', 'totalEkuitas');
    }

    /*
    |--------------------------------------------------------------------------
    | ARUS KAS (METODE TIDAK LANGSUNG / INDIRECT METHOD)
    |--------------------------------------------------------------------------
    */

    public function arusKas(Request $request)
    {
        $data = $this->getArusKasIndirectData($request);
        return view('laporan.arus-kas-index', $data);
    }

    public function arusKasPdf(Request $request)
    {
        $data = $this->getArusKasIndirectData($request);
        $pdf = Pdf::loadView('laporan.arus-kas-pdf', $data)->setPaper('a4', 'portrait');
        return $pdf->stream('laporan-arus-kas-indirect-' . now()->format('Y-m-d-His') . '.pdf');
    }

    public function arusKasExcel(Request $request): StreamedResponse
    {
        $data = $this->getArusKasIndirectData($request);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Arus Kas');

        $sheet->mergeCells('A1:B1');
        $sheet->setCellValue('A1', 'LAPORAN ARUS KAS (METODE TIDAK LANGSUNG)');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $rows = [
            ['ARUS KAS DARI AKTIVITAS OPERASIONAL', ''],
            ['  Laba / (Rugi) Bersih Periode Berjalan', (float) $data['labaBersih']],
            ['  Penyesuaian untuk merekonsiliasi Laba Bersih:', ''],
            ['    (Kenaikan) / Penurunan Piutang Dagang', (float) $data['perubahanPiutang']],
            ['    (Kenaikan) / Penurunan Persediaan Barang', (float) $data['perubahanPersediaan']],
            ['    Kenaikan / (Penurunan) Utang Dagang', (float) $data['perubahanUtang']],
            ['Arus Kas Bersih dari Aktivitas Operasional', (float) $data['arusKasOperasional']],
            ['', ''],
            ['ARUS KAS DARI AKTIVITAS INVESTASI', ''],
            ['  Pembelian Aset Tetap / Peralatan', (float) $data['arusKasInvestasi']],
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

        $filename = 'laporan-arus-kas-indirect-' . now()->format('Y-m-d-His') . '.xlsx';
        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
            $spreadsheet->disconnectWorksheets();
        }, $filename, ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']);
    }

    private function getArusKasIndirectData(Request $request): array
    {
        $tanggalMulai = $request->input('tanggal_mulai', date('Y-01-01'));
        $tanggalSelesai = $request->input('tanggal_selesai', date('Y-m-d'));

        // 1. Laba Bersih Periode Ini
        $labaData = $this->getLabaRugiData($request);
        $labaBersih = $labaData['labaRugiBersih'];

        // 2. Perubahan Modal Kerja (Working Capital Adjustments)
        // Piutang (Kenaikan Debit Piutang = - Kas, Kenaikan Kredit Piutang = + Kas)
        $piutangAkun = Akun::where('nama_akun', 'like', '%Piutang%')->first();
        $perubahanPiutang = 0;
        if ($piutangAkun) {
            $d = JurnalDetail::where('akun_id', $piutangAkun->id)->whereHas('jurnal', fn($q) => $q->whereDate('tanggal', '>=', $tanggalMulai)->whereDate('tanggal', '<=', $tanggalSelesai))->sum('debit');
            $k = JurnalDetail::where('akun_id', $piutangAkun->id)->whereHas('jurnal', fn($q) => $q->whereDate('tanggal', '>=', $tanggalMulai)->whereDate('tanggal', '<=', $tanggalSelesai))->sum('kredit');
            $perubahanPiutang = $k - $d; // Inverted for Cash Flow
        }

        // Persediaan
        $persediaanAkun = Akun::where('nama_akun', 'like', '%Persediaan%')->first();
        $perubahanPersediaan = 0;
        if ($persediaanAkun) {
            $d = JurnalDetail::where('akun_id', $persediaanAkun->id)->whereHas('jurnal', fn($q) => $q->whereDate('tanggal', '>=', $tanggalMulai)->whereDate('tanggal', '<=', $tanggalSelesai))->sum('debit');
            $k = JurnalDetail::where('akun_id', $persediaanAkun->id)->whereHas('jurnal', fn($q) => $q->whereDate('tanggal', '>=', $tanggalMulai)->whereDate('tanggal', '<=', $tanggalSelesai))->sum('kredit');
            $perubahanPersediaan = $k - $d;
        }

        // Utang Dagang (Kenaikan Kredit Utang = + Kas, Kenaikan Debit Utang = - Kas)
        $utangAkun = Akun::where('nama_akun', 'like', '%Utang%')->first();
        $perubahanUtang = 0;
        if ($utangAkun) {
            $d = JurnalDetail::where('akun_id', $utangAkun->id)->whereHas('jurnal', fn($q) => $q->whereDate('tanggal', '>=', $tanggalMulai)->whereDate('tanggal', '<=', $tanggalSelesai))->sum('debit');
            $k = JurnalDetail::where('akun_id', $utangAkun->id)->whereHas('jurnal', fn($q) => $q->whereDate('tanggal', '>=', $tanggalMulai)->whereDate('tanggal', '<=', $tanggalSelesai))->sum('kredit');
            $perubahanUtang = $k - $d;
        }

        $arusKasOperasional = $labaBersih + $perubahanPiutang + $perubahanPersediaan + $perubahanUtang;

        // Investasi & Pendanaan
        $pembelianAset = MutasiKasBank::whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai])->where('jenis_transaksi', 'KELUAR')->where('ref_type', 'INVESTASI')->sum('nominal');
        $arusKasInvestasi = -$pembelianAset;

        $setoranModal = MutasiKasBank::whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai])->where('jenis_transaksi', 'MASUK')->where('ref_type', 'MODAL_SETOR')->sum('nominal');
        $penarikanPrive = MutasiKasBank::whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai])->where('jenis_transaksi', 'KELUAR')->where('ref_type', 'PRIVE')->sum('nominal');
        $arusKasPendanaan = $setoranModal - $penarikanPrive;

        $kenaikanBersihKas = $arusKasOperasional + $arusKasInvestasi + $arusKasPendanaan;
        $kasAkuns = Akun::where('kategori_akun', 'Aset')->where('sub_kategori', 'Aset Lancar')->where(fn($q) => $q->where('nama_akun', 'like', '%Kas%')->orWhere('nama_akun', 'like', '%Bank%'))->get();
        $saldoKasAwal = $kasAkuns->sum(fn($k) => $k->saldo_awal);
        $saldoKasAkhir = $saldoKasAwal + $kenaikanBersihKas;

        return compact(
            'tanggalMulai',
            'tanggalSelesai',
            'labaBersih',
            'perubahanPiutang',
            'perubahanPersediaan',
            'perubahanUtang',
            'arusKasOperasional',
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

        $sheet->setCellValue('A4', 'Modal Awal CV Ambaja');
        $sheet->setCellValue('B4', (float) $data['modalAwal']);
        $sheet->setCellValue('A5', '(+) Laba / (Rugi) Bersih Periode');
        $sheet->setCellValue('B5', (float) $data['labaBersihPeriode']);
        $sheet->setCellValue('A6', '(-) Prive Owner Periode');
        $sheet->setCellValue('B6', -(float) $data['privePeriode']);
        $sheet->setCellValue('A7', 'Perubahan Net Ekuitas');
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

        $modalAkuns = Akun::where('kategori_akun', 'Ekuitas')->get();
        $modalAwal = $modalAkuns->where('nama_akun', '!=', 'Prive Owner')->sum('saldo_awal');

        $pendapatanAkuns = Akun::where('kategori_akun', 'Pendapatan')->get();
        $bebanAkuns = Akun::where('kategori_akun', 'Beban')->get();

        $totPendapatan = 0;
        foreach ($pendapatanAkuns as $p) {
            $d = JurnalDetail::where('akun_id', $p->id)->whereHas('jurnal', fn($q) => $q->whereDate('tanggal', '>=', $tanggalMulai)->whereDate('tanggal', '<=', $tanggalSelesai))->sum('debit');
            $k = JurnalDetail::where('akun_id', $p->id)->whereHas('jurnal', fn($q) => $q->whereDate('tanggal', '>=', $tanggalMulai)->whereDate('tanggal', '<=', $tanggalSelesai))->sum('kredit');
            $totPendapatan += ($p->saldo_normal === 'KREDIT') ? ($k - $d) : ($d - $k);
        }

        $totBeban = 0;
        foreach ($bebanAkuns as $b) {
            $d = JurnalDetail::where('akun_id', $b->id)->whereHas('jurnal', fn($q) => $q->whereDate('tanggal', '>=', $tanggalMulai)->whereDate('tanggal', '<=', $tanggalSelesai))->sum('debit');
            $k = JurnalDetail::where('akun_id', $b->id)->whereHas('jurnal', fn($q) => $q->whereDate('tanggal', '>=', $tanggalMulai)->whereDate('tanggal', '<=', $tanggalSelesai))->sum('kredit');
            $totBeban += ($b->saldo_normal === 'DEBIT') ? ($d - $k) : ($k - $d);
        }

        $labaBersihPeriode = $totPendapatan - $totBeban;
        $priveAkun = Akun::where('nama_akun', 'like', '%Prive%')->first();
        $privePeriode = 0;
        if ($priveAkun) {
            $privePeriode = JurnalDetail::where('akun_id', $priveAkun->id)->whereHas('jurnal', fn($q) => $q->whereDate('tanggal', '>=', $tanggalMulai)->whereDate('tanggal', '<=', $tanggalSelesai))->sum('debit');
        }

        $perubahanNet = $labaBersihPeriode - $privePeriode;
        $modalAkhir = $modalAwal + $perubahanNet;

        return compact('tanggalMulai', 'tanggalSelesai', 'modalAwal', 'labaBersihPeriode', 'privePeriode', 'perubahanNet', 'modalAkhir');
    }
}
