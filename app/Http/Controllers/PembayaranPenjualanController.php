<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\InvoicePenjualan;
use App\Models\PenerimaanPiutang;
use App\Models\PenerimaanPiutangDetail;
use App\Models\PembayaranPenjualan;
use App\Models\PembayaranPenjualanDetail;
use App\Models\RekeningKasBank;
use App\Services\KasBankService;
use App\Services\SystemAccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PembayaranPenjualanController extends Controller
{
    public function index(Request $request)
    {
        // Auto-sync unsynced PenerimaanPiutang
        $unsynced = PenerimaanPiutang::with('details')->get();
        foreach ($unsynced as $p) {
            $pb = PembayaranPenjualan::firstOrCreate(
                ['nomor_pembayaran' => $p->nomor_penerimaan],
                [
                    'customer_id' => $p->customer_id,
                    'rekening_kas_bank_id' => $p->rekening_kas_bank_id,
                    'tanggal_pembayaran' => $p->tanggal,
                    'metode_pembayaran' => 'Transfer',
                    'total_bayar' => $p->total_dibayar,
                    'catatan' => $p->catatan,
                    'created_by' => $p->created_by_user_id,
                ]
            );

            foreach ($p->details as $dt) {
                PembayaranPenjualanDetail::firstOrCreate([
                    'pembayaran_penjualan_id' => $pb->id,
                    'invoice_penjualan_id' => $dt->invoice_penjualan_id,
                ], [
                    'jumlah_dibayar' => $dt->nominal_dibayar,
                ]);
            }
        }

        $query = PembayaranPenjualan::with(['customer', 'rekeningKasBank', 'creator', 'details.invoicePenjualan']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nomor_pembayaran', 'like', "%{$search}%")
                  ->orWhere('referensi_transaksi', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('nama_customer', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('tanggal_pembayaran', '>=', $request->tanggal_mulai);
        }

        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('tanggal_pembayaran', '<=', $request->tanggal_selesai);
        }

        $pembayarans = $query->latest('tanggal_pembayaran')->latest('id')->paginate(15)->withQueryString();

        return view('pembayaran-penjualan.index', compact('pembayarans'));
    }

    public function create(Request $request)
    {
        $customers = Customer::whereHas('invoicePenjualans', function ($q) {
            $q->whereNotIn('status_pembayaran', ['Lunas', 'DIBATALKAN'])->where('sisa_piutang', '>', 0);
        })->orWhere('is_active', true)->orderBy('nama_customer')->get();

        $rekeningKasBanks = RekeningKasBank::where('is_active', true)->orderBy('nama_rekening')->get();

        $selectedCustomerId = $request->customer_id;
        $unpaidInvoices = collect();

        if ($selectedCustomerId) {
            $unpaidInvoices = InvoicePenjualan::where('customer_id', $selectedCustomerId)
                ->whereNotIn('status_pembayaran', ['Lunas', 'DIBATALKAN'])
                ->where('sisa_piutang', '>', 0)
                ->orderBy('tanggal_jatuh_tempo')
                ->get();
        }

        $year = date('Y');
        $lastPayment = PembayaranPenjualan::whereYear('tanggal_pembayaran', $year)->latest('id')->first();
        $nextNum = $lastPayment ? ($lastPayment->id + 1) : 1;
        $nomorPembayaran = 'PAY-' . $year . '-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

        return view('pembayaran-penjualan.create', compact(
            'customers',
            'rekeningKasBanks',
            'selectedCustomerId',
            'unpaidInvoices',
            'nomorPembayaran'
        ));
    }

    public function store(
        Request $request,
        KasBankService $kasBankService,
        SystemAccountingService $systemAccountingService
    ) {
        $validated = $request->validate([
            'nomor_pembayaran' => 'required|string|unique:pembayaran_penjualans,nomor_pembayaran',
            'customer_id' => 'required|exists:customers,id',
            'rekening_kas_bank_id' => 'required|exists:rekening_kas_banks,id',
            'tanggal_pembayaran' => 'required|date',
            'metode_pembayaran' => 'required|in:Transfer,Tunai,Giro,Cek',
            'referensi_transaksi' => 'nullable|string',
            'catatan' => 'nullable|string',
            'invoices' => 'required|array|min:1',
            'invoices.*.id' => 'required|exists:invoice_penjualans,id',
            'invoices.*.nominal_bayar' => 'required|numeric|min:0',
        ]);

        if ($systemAccountingService->isPeriodLocked($validated['tanggal_pembayaran'])) {
            return back()->withInput()->with('error', 'Pembayaran gagal disimpan karena periode tanggal tersebut sudah DITUTUP (Cut-off).');
        }

        $allocatedInvoices = array_filter($validated['invoices'], function ($item) {
            return (float) ($item['nominal_bayar'] ?? 0) > 0;
        });

        if (empty($allocatedInvoices)) {
            return back()->withInput()->with('error', 'Minimal harus ada 1 invoice dengan nominal pembayaran lebih dari 0.');
        }

        DB::beginTransaction();
        try {
            $totalBayar = 0;
            foreach ($allocatedInvoices as $item) {
                $totalBayar += (float) $item['nominal_bayar'];
            }

            $pembayaran = PembayaranPenjualan::create([
                'nomor_pembayaran' => $validated['nomor_pembayaran'],
                'customer_id' => $validated['customer_id'],
                'rekening_kas_bank_id' => $validated['rekening_kas_bank_id'],
                'tanggal_pembayaran' => $validated['tanggal_pembayaran'],
                'metode_pembayaran' => $validated['metode_pembayaran'],
                'total_bayar' => $totalBayar,
                'referensi_transaksi' => $validated['referensi_transaksi'] ?? null,
                'catatan' => $validated['catatan'] ?? null,
                'created_by' => auth()->id(),
            ]);

            // Sync ke model PenerimaanPiutang untuk kwitansi/jurnal terpadu
            $penerimaan = PenerimaanPiutang::create([
                'nomor_penerimaan' => 'KWT-' . str_replace('PAY-', '', $validated['nomor_pembayaran']),
                'customer_id' => $validated['customer_id'],
                'rekening_kas_bank_id' => $validated['rekening_kas_bank_id'],
                'tanggal' => $validated['tanggal_pembayaran'],
                'total_dibayar' => $totalBayar,
                'catatan' => $validated['catatan'] ?? null,
                'created_by_user_id' => auth()->id(),
            ]);

            foreach ($allocatedInvoices as $item) {
                $invoice = InvoicePenjualan::lockForUpdate()->findOrFail($item['id']);
                $nominal = (float) $item['nominal_bayar'];

                // Security Check 1: Invoice milik customer yang dipilih
                if ($invoice->customer_id != $validated['customer_id']) {
                    throw new \RuntimeException("Invoice {$invoice->nomor_invoice} bukan milik customer yang dipilih.");
                }

                // Security Check 2: Status bukan DIBATALKAN atau Lunas
                if (in_array($invoice->status_pembayaran, ['Lunas', 'DIBATALKAN']) || $invoice->sisa_piutang <= 0) {
                    throw new \RuntimeException("Invoice {$invoice->nomor_invoice} sudah lunas atau dibatalkan.");
                }

                // Security Check 3: Prevent Overpayment
                if ($nominal > (float) $invoice->sisa_piutang) {
                    throw new \RuntimeException("Nominal bayar untuk invoice {$invoice->nomor_invoice} (Rp " . number_format($nominal, 0, ',', '.') . ") melebihi sisa piutang (Rp " . number_format($invoice->sisa_piutang, 0, ',', '.') . ").");
                }

                PembayaranPenjualanDetail::create([
                    'pembayaran_penjualan_id' => $pembayaran->id,
                    'invoice_penjualan_id' => $invoice->id,
                    'nominal_bayar' => $nominal,
                ]);

                PenerimaanPiutangDetail::create([
                    'penerimaan_piutang_id' => $penerimaan->id,
                    'invoice_penjualan_id' => $invoice->id,
                    'nominal_dibayar' => $nominal,
                ]);

                // 1. Update Invoice status
                $sudahDibayarBaru = (float) $invoice->sudah_dibayar + $nominal;
                $sisaPiutangBaru = (float) $invoice->total - $sudahDibayarBaru;
                $statusPembayaran = $sisaPiutangBaru <= 0.01 ? 'Lunas' : 'Sebagian';

                $invoice->update([
                    'sudah_dibayar' => $sudahDibayarBaru,
                    'sisa_piutang' => max(0, $sisaPiutangBaru),
                    'status_pembayaran' => $statusPembayaran,
                ]);
            }

            // 2. Tambah saldo Kas/Bank
            $customer = Customer::find($validated['customer_id']);
            $kasBankService->uangMasuk(
                $validated['rekening_kas_bank_id'],
                $totalBayar,
                'PEMBAYARAN_PENJUALAN',
                $pembayaran->id,
                $pembayaran->nomor_pembayaran,
                'Penerimaan Pembayaran Piutang - ' . ($customer->nama_customer ?? ''),
                $validated['tanggal_pembayaran']
            );

            // 3. Jurnal Otomatis & 4. Kartu Piutang Customer
            $systemAccountingService->autoJournalPenerimaanPiutang($penerimaan);

            DB::commit();

            return redirect()
                ->route('pembayaran-penjualan.show', $pembayaran)
                ->with('success', 'Penerimaan pembayaran piutang berhasil disimpan. Kas/Bank, Kartu Piutang & Jurnal Otomatis telah diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal memproses pembayaran: ' . $e->getMessage());
        }
    }

    public function show(PembayaranPenjualan $pembayaranPenjualan)
    {
        $pembayaranPenjualan->load(['customer', 'rekeningKasBank', 'creator', 'details.invoicePenjualan']);
        return view('pembayaran-penjualan.show', compact('pembayaranPenjualan'));
    }
}
