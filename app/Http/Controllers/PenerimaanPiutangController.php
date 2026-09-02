<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\InvoicePenjualan;
use App\Models\PenerimaanPiutang;
use App\Models\PenerimaanPiutangDetail;
use App\Models\RekeningKasBank;
use App\Services\SystemAccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class PenerimaanPiutangController extends Controller
{
    protected SystemAccountingService $accountingService;

    public function __construct(SystemAccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    public function index()
    {
        $penerimaans = PenerimaanPiutang::with(['customer', 'rekeningKasBank', 'creator'])
            ->latest('tanggal')
            ->latest('id')
            ->paginate(15);

        return view('piutang.penerimaan-index', compact('penerimaans'));
    }

    public function create(Request $request)
    {
        $selectedCustomerId = $request->input('customer_id');
        $customers = Customer::where('is_active', true)->orderBy('nama_customer')->get();
        $rekeningKasBanks = RekeningKasBank::where('is_active', true)->orderBy('nama_rekening')->get();

        $invoices = collect();
        if ($selectedCustomerId) {
            $invoices = InvoicePenjualan::where('customer_id', $selectedCustomerId)
                ->where('sisa_piutang', '>', 0)
                ->orderBy('tanggal_jatuh_tempo')
                ->get();
        }

        $nomorPenerimaan = $this->accountingService->generateDocumentNumber('KWITANSI');

        return view('piutang.penerimaan-create', compact(
            'customers',
            'rekeningKasBanks',
            'selectedCustomerId',
            'invoices',
            'nomorPenerimaan'
        ));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nomor_penerimaan' => 'required|string|unique:penerimaan_piutangs,nomor_penerimaan',
            'customer_id' => 'required|exists:customers,id',
            'rekening_kas_bank_id' => 'required|exists:rekening_kas_banks,id',
            'tanggal' => 'required|date',
            'catatan' => 'nullable|string',
            'alokasi' => 'required|array|min:1',
            'alokasi.*.invoice_id' => 'required|exists:invoice_penjualans,id',
            'alokasi.*.bayar' => 'required|numeric|min:0.01',
        ]);

        if ($this->accountingService->isPeriodLocked($validated['tanggal'])) {
            return back()->withInput()->with('error', 'Penerimaan piutang gagal disimpan karena periode tanggal tersebut sudah DITUTUP (Cut-off).');
        }

        DB::beginTransaction();
        try {
            $totalDibayar = 0;
            foreach ($validated['alokasi'] as $item) {
                $totalDibayar += $item['bayar'];
            }

            $penerimaan = PenerimaanPiutang::create([
                'nomor_penerimaan' => $validated['nomor_penerimaan'],
                'customer_id' => $validated['customer_id'],
                'rekening_kas_bank_id' => $validated['rekening_kas_bank_id'],
                'tanggal' => $validated['tanggal'],
                'total_dibayar' => $totalDibayar,
                'catatan' => $validated['catatan'],
                'created_by_user_id' => auth()->id() ?? 1,
            ]);

            foreach ($validated['alokasi'] as $item) {
                if ($item['bayar'] > 0) {
                    PenerimaanPiutangDetail::create([
                        'penerimaan_piutang_id' => $penerimaan->id,
                        'invoice_penjualan_id' => $item['invoice_id'],
                        'nominal_dibayar' => $item['bayar'],
                    ]);

                    // Update sisa piutang di Invoice Penjualan
                    $inv = InvoicePenjualan::find($item['invoice_id']);
                    $sudahDibayarBaru = $inv->sudah_dibayar + $item['bayar'];
                    $sisaPiutangBaru = max(0, $inv->total - $sudahDibayarBaru);
                    $statusPembayaranBaru = $sisaPiutangBaru <= 0 ? 'Lunas' : 'Sebagian';

                    $inv->update([
                        'sudah_dibayar' => $sudahDibayarBaru,
                        'sisa_piutang' => $sisaPiutangBaru,
                        'status_pembayaran' => $statusPembayaranBaru,
                    ]);
                }
            }

            // Auto-Journaling Pelunasan & Kartu Piutang
            $this->accountingService->autoJournalPenerimaanPiutang($penerimaan);

            DB::commit();

            return redirect()->route('piutang.penerimaan.index')
                ->with('success', 'Penerimaan piutang berhasil disimpan, kartu piutang & jurnal telah diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal menyimpan penerimaan piutang: ' . $e->getMessage());
        }
    }

    public function kwitansiPdf(PenerimaanPiutang $penerimaan)
    {
        $penerimaan->load(['customer', 'rekeningKasBank', 'details.invoicePenjualan', 'creator']);

        $pdf = Pdf::loadView('piutang.kwitansi-pdf', compact('penerimaan'))
            ->setPaper('a5', 'landscape');

        return $pdf->stream('kwitansi-' . $penerimaan->nomor_penerimaan . '.pdf');
    }
}
