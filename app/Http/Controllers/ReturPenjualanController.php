<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Gudang;
use App\Models\InvoicePenjualan;
use App\Models\ReturPenjualan;
use App\Models\ReturPenjualanItem;
use App\Services\InventoryService;
use App\Services\SystemAccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReturPenjualanController extends Controller
{
    protected SystemAccountingService $accountingService;

    public function __construct(SystemAccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    public function index()
    {
        $returs = ReturPenjualan::with(['invoicePenjualan', 'customer', 'gudang', 'creator'])
            ->latest('tanggal_retur')
            ->latest('id')
            ->paginate(15);

        return view('retur-penjualan.index', compact('returs'));
    }

    public function create(Request $request)
    {
        $selectedInvoiceId = $request->input('invoice_id');
        $invoices = InvoicePenjualan::with(['customer', 'gudang', 'items.barang'])
            ->orderByDesc('tanggal_invoice')
            ->get();

        $selectedInvoice = null;
        if ($selectedInvoiceId) {
            $selectedInvoice = InvoicePenjualan::with(['customer', 'gudang', 'items.barang'])->find($selectedInvoiceId);
        }

        $nomorRetur = $this->accountingService->generateDocumentNumber('RETUR_PENJUALAN');

        return view('retur-penjualan.create', compact('invoices', 'selectedInvoice', 'nomorRetur'));
    }

    public function store(Request $request, InventoryService $inventoryService)
    {
        $validated = $request->validate([
            'nomor_retur' => 'required|string|unique:retur_penjualans,nomor_retur',
            'invoice_penjualan_id' => 'required|exists:invoice_penjualans,id',
            'tanggal_retur' => 'required|date',
            'alasan_retur' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.barang_id' => 'required|exists:barangs,id',
            'items.*.qty' => 'required|numeric|min:0.01',
            'items.*.harga_satuan' => 'required|numeric|min:0',
        ]);

        if ($this->accountingService->isPeriodLocked($validated['tanggal_retur'])) {
            return back()->withInput()->with('error', 'Retur penjualan gagal disimpan karena periode tanggal tersebut sudah DITUTUP (Cut-off).');
        }

        $invoice = InvoicePenjualan::findOrFail($validated['invoice_penjualan_id']);

        DB::beginTransaction();
        try {
            $subtotal = 0;
            foreach ($validated['items'] as $item) {
                $subtotal += ($item['qty'] * $item['harga_satuan']);
            }

            // Hitung PPN Retur secara proporsional jika invoice asal ber-PPN
            $pajakPersen = $invoice->pajak > 0 && $invoice->subtotal > 0
                ? ($invoice->pajak / $invoice->subtotal) * 100
                : 0;

            $pajakRetur = ($subtotal * $pajakPersen) / 100;
            $totalRetur = $subtotal + $pajakRetur;

            $retur = ReturPenjualan::create([
                'nomor_retur' => $validated['nomor_retur'],
                'invoice_penjualan_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'gudang_id' => $invoice->gudang_id,
                'tanggal_retur' => $validated['tanggal_retur'],
                'subtotal' => $subtotal,
                'pajak' => $pajakRetur,
                'total' => $totalRetur,
                'alasan_retur' => $validated['alasan_retur'],
                'created_by_user_id' => auth()->id() ?? 1,
            ]);

            foreach ($validated['items'] as $item) {
                if ($item['qty'] > 0) {
                    ReturPenjualanItem::create([
                        'retur_penjualan_id' => $retur->id,
                        'barang_id' => $item['barang_id'],
                        'qty' => $item['qty'],
                        'harga_satuan' => $item['harga_satuan'],
                        'subtotal' => ($item['qty'] * $item['harga_satuan']),
                    ]);

                    // RESTOCK BARANG: Masukkan kembali barang ke stok fisik gudang (Kartu Stok + Moving Average)
                    $inventoryService->masuk(
                        $invoice->gudang_id,
                        $item['barang_id'],
                        $item['qty'],
                        $item['harga_satuan'],
                        'RETUR_PENJUALAN',
                        $retur->id,
                        $retur->nomor_retur,
                        'Pengembalian Barang Retur #' . $retur->nomor_retur,
                        $validated['tanggal_retur']
                    );
                }
            }

            // Update sisa piutang pada Invoice Penjualan asal
            if ($invoice->tipe_pembayaran === 'Kredit' && $invoice->sisa_piutang > 0) {
                $sisaPiutangBaru = max(0, $invoice->sisa_piutang - $totalRetur);
                $statusPembayaranBaru = $sisaPiutangBaru <= 0 ? 'Lunas' : 'Sebagian';

                $invoice->update([
                    'sisa_piutang' => $sisaPiutangBaru,
                    'status_pembayaran' => $statusPembayaranBaru,
                ]);
            }

            // Auto-Journaling Retur (with PPN & Kartu Piutang update)
            $this->accountingService->autoJournalReturPenjualan($retur);

            DB::commit();

            return redirect()->route('retur-penjualan.index')
                ->with('success', 'Nota Retur Penjualan berhasil diterbitkan, stok bertambah, dan piutang/jurnal telah diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal menyimpan retur penjualan: ' . $e->getMessage());
        }
    }
}
