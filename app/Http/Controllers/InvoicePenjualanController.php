<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Customer;
use App\Models\Gudang;
use App\Models\InvoicePenjualan;
use App\Models\InvoicePenjualanItem;
use App\Models\Penjualan;
use App\Models\RekeningKasBank;
use App\Services\InventoryService;
use App\Services\KasBankService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoicePenjualanController extends Controller
{
    public function index(Request $request)
    {
        $query = InvoicePenjualan::with(['customer', 'gudang', 'creator', 'items.barang']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nomor_invoice', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('nama_customer', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status_pembayaran')) {
            $query->where('status_pembayaran', $request->status_pembayaran);
        }

        if ($request->filled('tipe_pembayaran')) {
            $query->where('tipe_pembayaran', $request->tipe_pembayaran);
        }

        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('tanggal_invoice', '>=', $request->tanggal_mulai);
        }

        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('tanggal_invoice', '<=', $request->tanggal_selesai);
        }

        $invoices = $query->latest('tanggal_invoice')->latest('id')->paginate(15)->withQueryString();

        return view('invoice-penjualan.index', compact('invoices'));
    }

    public function create(Request $request)
    {
        $customers = Customer::where('is_active', true)->orderBy('nama_customer')->get();
        $gudangs = Gudang::where('is_active', true)->orderBy('nama_gudang')->get();
        $rekeningKasBanks = RekeningKasBank::where('is_active', true)->orderBy('nama_rekening')->get();
        $barangs = Barang::where('is_active', true)->orderBy('nama_barang')->get();

        // Optional Sales Orders to convert
        $penjualans = Penjualan::with(['customer', 'items.barang'])
            ->whereIn('status', ['Draft', 'Dikonfirmasi', 'Diproses'])
            ->latest('tanggal_penjualan')
            ->get();

        $selectedPenjualan = null;
        if ($request->filled('penjualan_id')) {
            $selectedPenjualan = Penjualan::with(['customer', 'items.barang'])->find($request->penjualan_id);
        }

        $initialItems = [
            ['barang_id' => '', 'qty' => 1, 'harga_satuan' => 0, 'diskon' => 0]
        ];

        if ($selectedPenjualan && $selectedPenjualan->items->isNotEmpty()) {
            $initialItems = $selectedPenjualan->items->map(function ($i) {
                return [
                    'barang_id' => $i->barang_id,
                    'qty' => (float) $i->qty,
                    'harga_satuan' => (float) $i->harga_satuan,
                    'diskon' => (float) $i->diskon,
                ];
            })->values()->toArray();
        }

        return view('invoice-penjualan.create', compact(
            'customers',
            'gudangs',
            'rekeningKasBanks',
            'barangs',
            'penjualans',
            'selectedPenjualan',
            'nomorInvoice',
            'initialItems'
        ));
    }

    public function store(Request $request, InventoryService $inventoryService, KasBankService $kasBankService)
    {
        $validated = $request->validate([
            'nomor_invoice' => 'required|string|unique:invoice_penjualans,nomor_invoice',
            'penjualan_id' => 'nullable|exists:penjualans,id',
            'customer_id' => 'required|exists:customers,id',
            'gudang_id' => 'required|exists:gudangs,id',
            'tanggal_invoice' => 'required|date',
            'tanggal_jatuh_tempo' => 'nullable|date',
            'tipe_pembayaran' => 'required|in:Tunai,Kredit',
            'rekening_kas_bank_id' => 'nullable|required_if:tipe_pembayaran,Tunai|exists:rekening_kas_banks,id',
            'catatan' => 'nullable|string',
            'pajak_persen' => 'nullable|numeric|min:0|max:100',
            'items' => 'required|array|min:1',
            'items.*.barang_id' => 'required|exists:barangs,id',
            'items.*.qty' => 'required|numeric|min:0.01',
            'items.*.harga_satuan' => 'required|numeric|min:0',
            'items.*.diskon' => 'nullable|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {
            $subtotal = 0;
            $totalDiskonItem = 0;

            foreach ($validated['items'] as $item) {
                $subtotal += ($item['qty'] * $item['harga_satuan']);
                $totalDiskonItem += ($item['diskon'] ?? 0);
            }

            $pajakPersen = $validated['pajak_persen'] ?? 0;
            $dpp = $subtotal - $totalDiskonItem;
            $pajak = ($dpp * $pajakPersen) / 100;
            $total = $dpp + $pajak;

            $isTunai = $validated['tipe_pembayaran'] === 'Tunai';
            $sudahDibayar = $isTunai ? $total : 0;
            $sisaPiutang = $isTunai ? 0 : $total;
            $statusPembayaran = $isTunai ? 'Lunas' : 'Belum Lunas';

            $invoice = InvoicePenjualan::create([
                'nomor_invoice' => $validated['nomor_invoice'],
                'penjualan_id' => $validated['penjualan_id'] ?? null,
                'customer_id' => $validated['customer_id'],
                'gudang_id' => $validated['gudang_id'],
                'tanggal_invoice' => $validated['tanggal_invoice'],
                'tanggal_jatuh_tempo' => $isTunai ? null : ($validated['tanggal_jatuh_tempo'] ?? $validated['tanggal_invoice']),
                'tipe_pembayaran' => $validated['tipe_pembayaran'],
                'rekening_kas_bank_id' => $isTunai ? $validated['rekening_kas_bank_id'] : null,
                'subtotal' => $subtotal,
                'diskon' => $totalDiskonItem,
                'pajak_persen' => $pajakPersen,
                'pajak' => $pajak,
                'total' => $total,
                'sudah_dibayar' => $sudahDibayar,
                'sisa_piutang' => $sisaPiutang,
                'status_pembayaran' => $statusPembayaran,
                'catatan' => $validated['catatan'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                $itemSubtotal = ($item['qty'] * $item['harga_satuan']) - ($item['diskon'] ?? 0);
                InvoicePenjualanItem::create([
                    'invoice_penjualan_id' => $invoice->id,
                    'barang_id' => $item['barang_id'],
                    'qty' => $item['qty'],
                    'harga_satuan' => $item['harga_satuan'],
                    'diskon' => $item['diskon'] ?? 0,
                    'subtotal' => $itemSubtotal,
                    'catatan' => $item['catatan'] ?? null,
                ]);

                // Reduce inventory stock
                $inventoryService->stokKeluar(
                    $item['barang_id'],
                    $validated['gudang_id'],
                    $item['qty'],
                    'INVOICE_PENJUALAN',
                    $invoice->id,
                    'Penjualan Invoice ' . $invoice->nomor_invoice,
                    'PENJUALAN',
                    $validated['tanggal_invoice']
                );
            }

            // If cash sale, record incoming money immediately
            if ($isTunai && $total > 0) {
                $customer = Customer::find($validated['customer_id']);
                $kasBankService->uangMasuk(
                    $validated['rekening_kas_bank_id'],
                    $total,
                    'INVOICE_PENJUALAN',
                    $invoice->id,
                    $invoice->nomor_invoice,
                    'Penjualan Tunai ' . $invoice->nomor_invoice . ' - ' . ($customer->nama_customer ?? ''),
                    $validated['tanggal_invoice']
                );
            }

            // Update sales order status if applicable
            if (!empty($validated['penjualan_id'])) {
                Penjualan::where('id', $validated['penjualan_id'])->update(['status' => 'Selesai']);
            }

            DB::commit();

            return redirect()
                ->route('invoice-penjualan.show', $invoice)
                ->with('success', 'Invoice Penjualan berhasil diterbitkan dan stok telah diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal menerbitkan invoice penjualan: ' . $e->getMessage());
        }
    }

    public function show(InvoicePenjualan $invoicePenjualan)
    {
        $invoicePenjualan->load([
            'customer',
            'gudang',
            'rekeningKasBank',
            'creator',
            'items.barang.satuan',
            'pembayaranDetails.pembayaranPenjualan.rekeningKasBank',
            'returPenjualans'
        ]);

        return view('invoice-penjualan.show', compact('invoicePenjualan'));
    }

    public function destroy(InvoicePenjualan $invoicePenjualan)
    {
        if ($invoicePenjualan->pembayaranDetails()->exists()) {
            return back()->with('error', 'Invoice tidak dapat dihapus karena sudah memiliki riwayat pembayaran.');
        }

        if ($invoicePenjualan->returPenjualans()->exists()) {
            return back()->with('error', 'Invoice tidak dapat dihapus karena sudah memiliki retur.');
        }

        return back()->with('error', 'Invoice penjualan yang telah memotong stok dan mutasi tidak disarankan untuk dihapus langsung demi konsistensi akuntansi. Silakan gunakan fitur Retur Penjualan.');
    }

    public function pdf(InvoicePenjualan $invoicePenjualan)
    {
        $invoicePenjualan->load(['customer', 'gudang', 'creator', 'items.barang.satuan', 'rekeningKasBank']);

        if (class_exists(Pdf::class)) {
            $pdf = Pdf::loadView('invoice-penjualan.pdf', compact('invoicePenjualan'));
            return $pdf->stream('Invoice-' . $invoicePenjualan->nomor_invoice . '.pdf');
        }

        return view('invoice-penjualan.pdf', compact('invoicePenjualan'));
    }
}
