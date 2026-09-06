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

        $pendapatanAkuns = Akun::whereIn('kategori_akun', ['Penjualan', 'Pendapatan', 'Pendapatan Lain-lain'])->orderBy('kode_akun')->get();
        $bebanAkuns = Akun::whereIn('kategori_akun', ['HPP', 'Beban', 'Beban Lain-lain', 'Beban Pajak'])->orderBy('kode_akun')->get();

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

        $totPendapatan = Akun::whereIn('kategori_akun', ['Penjualan', 'Pendapatan', 'Pendapatan Lain-lain'])->get()->sum(fn($p) => $calcSaldoKumulatif($p));
        $totBeban = Akun::whereIn('kategori_akun', ['HPP', 'Beban', 'Beban Lain-lain', 'Beban Pajak'])->get()->sum(fn($b) => $calcSaldoKumulatif($b));
        $labaRugiKumulatif = $totPendapatan - $totBeban;

        $ekuitasData = []; $totalEkuitas = 0;
        foreach ($ekuitasAkuns as $e) { $val = $calcSaldoKumulatif($e); $ekuitasData[] = ['akun' => $e, 'nominal' => $val]; $totalEkuitas += $val; }
        $totalEkuitas += $labaRugiKumulatif;

        return compact('tanggal', 'asetData', 'kewajibanData', 'ekuitasData', 'labaRugiKumulatif', 'totalAset', 'totalKewajiban', 'totalEkuitas');
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

        // 1. OPERASIONAL
        $penjualanTunai = (float) InvoicePenjualan::whereBetween('tanggal_invoice', [$tanggalMulai, $tanggalSelesai])
            ->where('tipe_pembayaran', 'Tunai')
            ->where('status_pembayaran', '!=', 'DIBATALKAN')
            ->sum('total');

        $pelunasanPiutang = (float) PenerimaanPiutang::whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai])
            ->sum('total_dibayar');

        $penerimaanPelanggan = $penjualanTunai + $pelunasanPiutang;

        $penerimaanLainnya = (float) UangMasuk::whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai])
            ->sum('nominal');

        $pembayaranSupplier = (float) PembayaranSupplier::whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai])
            ->sum('total_dibayar');

        $pembayaranBeban = (float) UangKeluar::whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai])
            ->sum('total');

        $totalPenerimaanOperasional = $penerimaanPelanggan + $penerimaanLainnya;
        $totalPengeluaranOperasional = $pembayaranSupplier + $pembayaranBeban;
        $arusKasOperasional = $totalPenerimaanOperasional - $totalPengeluaranOperasional;

        // 2. INVESTASI
        $pembelianAsetTetap = (float) MutasiKasBank::whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai])
            ->where('kredit', '>', 0)
            ->where('reference_type', 'INVESTASI')
            ->sum('kredit');

        $arusKasInvestasi = -$pembelianAsetTetap;

        // 3. PENDANAAN
        $setoranModal = (float) MutasiKasBank::whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai])
            ->where('debit', '>', 0)
            ->where('reference_type', 'MODAL_SETOR')
            ->sum('debit');

        $penarikanPrive = (float) MutasiKasBank::whereBetween('tanggal', [$tanggalMulai, $tanggalSelesai])
            ->where('kredit', '>', 0)
            ->where('reference_type', 'PRIVE')
            ->sum('kredit');

        $arusKasPendanaan = $setoranModal - $penarikanPrive;

        // SUMMARY KAS & BANK
        $kenaikanBersihKas = $arusKasOperasional + $arusKasInvestasi + $arusKasPendanaan;

        $kasAkuns = Akun::where('kategori_akun', 'Aset')
            ->where('sub_kategori', 'Aset Lancar')
            ->where(fn($q) => $q->where('nama_akun', 'like', '%Kas%')->orWhere('nama_akun', 'like', '%Bank%'))
            ->get();

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

        $pendapatanAkuns = Akun::whereIn('kategori_akun', ['Penjualan', 'Pendapatan', 'Pendapatan Lain-lain'])->get();
        $bebanAkuns = Akun::whereIn('kategori_akun', ['HPP', 'Beban', 'Beban Lain-lain', 'Beban Pajak'])->get();

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
