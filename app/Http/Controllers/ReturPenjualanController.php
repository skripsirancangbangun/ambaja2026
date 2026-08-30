<?php

namespace App\Http\Controllers;

use App\Models\Gudang;
use App\Models\InvoicePenjualan;
use App\Models\ReturPenjualan;
use App\Models\ReturPenjualanDetail;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReturPenjualanController extends Controller
{
    public function index(Request $request)
    {
        $query = ReturPenjualan::with(['customer', 'invoicePenjualan', 'gudang', 'creator', 'details.barang']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nomor_retur', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('nama_customer', 'like', "%{$search}%");
                  })
                  ->orWhereHas('invoicePenjualan', function ($iq) use ($search) {
                      $iq->where('nomor_invoice', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('tanggal_retur', '>=', $request->tanggal_mulai);
        }

        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('tanggal_retur', '<=', $request->tanggal_selesai);
        }

        $returs = $query->latest('tanggal_retur')->latest('id')->paginate(15)->withQueryString();

        return view('retur-penjualan.index', compact('returs'));
    }

    public function create(Request $request)
    {
        $invoices = InvoicePenjualan::with(['customer', 'items.barang.satuan', 'gudang'])
            ->latest('tanggal_invoice')
            ->get();

        $gudangs = Gudang::where('is_active', true)->orderBy('nama_gudang')->get();

        $selectedInvoice = null;
        if ($request->filled('invoice_id')) {
            $selectedInvoice = InvoicePenjualan::with(['customer', 'items.barang.satuan', 'gudang'])->find($request->invoice_id);
        }

        $year = date('Y');
        $lastRetur = ReturPenjualan::whereYear('tanggal_retur', $year)->latest('id')->first();
        $nextNum = $lastRetur ? ($lastRetur->id + 1) : 1;
        $nomorRetur = 'RTR-PJ-' . $year . '-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

        return view('retur-penjualan.create', compact('invoices', 'gudangs', 'selectedInvoice', 'nomorRetur'));
    }

    public function store(Request $request, InventoryService $inventoryService)
    {
        $validated = $request->validate([
            'nomor_retur' => 'required|string|unique:retur_penjualans,nomor_retur',
            'invoice_penjualan_id' => 'required|exists:invoice_penjualans,id',
            'gudang_id' => 'required|exists:gudangs,id',
            'tanggal_retur' => 'required|date',
            'alasan' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.barang_id' => 'required|exists:barangs,id',
            'items.*.qty' => 'required|numeric|min:0',
            'items.*.harga_satuan' => 'required|numeric|min:0',
            'items.*.alasan' => 'nullable|string',
        ]);

        $returItems = array_filter($validated['items'], function ($item) {
            return (float) ($item['qty'] ?? 0) > 0;
        });

        if (empty($returItems)) {
            return back()->withInput()->with('error', 'Minimal harus ada 1 barang dengan jumlah retur lebih dari 0.');
        }

        DB::beginTransaction();
        try {
            $invoice = InvoicePenjualan::lockForUpdate()->findOrFail($validated['invoice_penjualan_id']);

            $totalRetur = 0;
            foreach ($returItems as $item) {
                $totalRetur += ((float) $item['qty'] * (float) $item['harga_satuan']);
            }

            $retur = ReturPenjualan::create([
                'nomor_retur' => $validated['nomor_retur'],
                'invoice_penjualan_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'gudang_id' => $validated['gudang_id'],
                'tanggal_retur' => $validated['tanggal_retur'],
                'total_retur' => $totalRetur,
                'alasan' => $validated['alasan'] ?? null,
                'status' => 'Selesai',
                'created_by' => auth()->id(),
            ]);

            foreach ($returItems as $item) {
                $subtotal = (float) $item['qty'] * (float) $item['harga_satuan'];

                ReturPenjualanDetail::create([
                    'retur_penjualan_id' => $retur->id,
                    'barang_id' => $item['barang_id'],
                    'qty' => $item['qty'],
                    'harga_satuan' => $item['harga_satuan'],
                    'subtotal' => $subtotal,
                    'alasan' => $item['alasan'] ?? null,
                ]);

                // Return stock back to warehouse
                $inventoryService->stokMasuk(
                    $item['barang_id'],
                    $validated['gudang_id'],
                    $item['qty'],
                    $item['harga_satuan'],
                    'RETUR_PENJUALAN',
                    $retur->id,
                    'Retur Penjualan ' . $retur->nomor_retur . ' (Inv: ' . $invoice->nomor_invoice . ')',
                    'RETUR_PENJUALAN',
                    $validated['tanggal_retur']
                );
            }

            // Adjust invoice piutang if invoice is not fully settled
            if ((float) $invoice->sisa_piutang > 0) {
                $sisaBaru = max(0, (float) $invoice->sisa_piutang - $totalRetur);
                $statusPembayaran = $sisaBaru <= 0.01 ? 'Lunas' : 'Sebagian';

                $invoice->update([
                    'sisa_piutang' => $sisaBaru,
                    'status_pembayaran' => $statusPembayaran,
                ]);
            }

            DB::commit();

            return redirect()
                ->route('retur-penjualan.show', $retur)
                ->with('success', 'Retur penjualan berhasil diproses. Stok barang telah dikembalikan ke gudang.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal memproses retur penjualan: ' . $e->getMessage());
        }
    }

    public function show(ReturPenjualan $returPenjualan)
    {
        $returPenjualan->load(['customer', 'invoicePenjualan', 'gudang', 'creator', 'details.barang.satuan']);
        return view('retur-penjualan.show', compact('returPenjualan'));
    }
}
