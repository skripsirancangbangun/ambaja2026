<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Customer;
use App\Models\Gudang;
use App\Models\Penjualan;
use App\Models\PenjualanItem;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PenjualanController extends Controller
{
    public function index(Request $request)
    {
        $query = Penjualan::with(['customer', 'gudang', 'creator', 'items.barang']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nomor_penjualan', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('nama_customer', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('tanggal_penjualan', '>=', $request->tanggal_mulai);
        }

        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('tanggal_penjualan', '<=', $request->tanggal_selesai);
        }

        $penjualans = $query->latest('tanggal_penjualan')->latest('id')->paginate(15)->withQueryString();

        return view('penjualan.index', compact('penjualans'));
    }

    public function create()
    {
        $customers = Customer::where('is_active', true)->orderBy('nama_customer')->get();
        $gudangs = Gudang::where('is_active', true)->orderBy('nama_gudang')->get();
        $barangs = Barang::where('is_active', true)->orderBy('nama_barang')->get();

        $year = date('Y');
        $lastOrder = Penjualan::whereYear('tanggal_penjualan', $year)->latest('id')->first();
        $nextNum = $lastOrder ? ($lastOrder->id + 1) : 1;
        $nomorPenjualan = 'SO-' . $year . '-' . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

        return view('penjualan.create', compact('customers', 'gudangs', 'barangs', 'nomorPenjualan'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nomor_penjualan' => 'required|string|unique:penjualans,nomor_penjualan',
            'customer_id' => 'required|exists:customers,id',
            'gudang_id' => 'nullable|exists:gudangs,id',
            'tanggal_penjualan' => 'required|date',
            'tanggal_estimasi_kirim' => 'nullable|date',
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
                $itemSubtotal = ($item['qty'] * $item['harga_satuan']) - ($item['diskon'] ?? 0);
                $subtotal += ($item['qty'] * $item['harga_satuan']);
                $totalDiskonItem += ($item['diskon'] ?? 0);
            }

            $pajakPersen = $validated['pajak_persen'] ?? 0;
            $dpp = $subtotal - $totalDiskonItem;
            $pajak = ($dpp * $pajakPersen) / 100;
            $total = $dpp + $pajak;

            $penjualan = Penjualan::create([
                'nomor_penjualan' => $validated['nomor_penjualan'],
                'customer_id' => $validated['customer_id'],
                'gudang_id' => $validated['gudang_id'] ?? null,
                'tanggal_penjualan' => $validated['tanggal_penjualan'],
                'tanggal_estimasi_kirim' => $validated['tanggal_estimasi_kirim'] ?? null,
                'catatan' => $validated['catatan'] ?? null,
                'subtotal' => $subtotal,
                'diskon' => $totalDiskonItem,
                'pajak_persen' => $pajakPersen,
                'pajak' => $pajak,
                'total' => $total,
                'status' => 'Draft',
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                $itemSubtotal = ($item['qty'] * $item['harga_satuan']) - ($item['diskon'] ?? 0);
                PenjualanItem::create([
                    'penjualan_id' => $penjualan->id,
                    'barang_id' => $item['barang_id'],
                    'qty' => $item['qty'],
                    'harga_satuan' => $item['harga_satuan'],
                    'diskon' => $item['diskon'] ?? 0,
                    'subtotal' => $itemSubtotal,
                    'catatan' => $item['catatan'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()
                ->route('penjualan.show', $penjualan)
                ->with('success', 'Pesanan Penjualan (Sales Order) berhasil dibuat.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal membuat pesanan penjualan: ' . $e->getMessage());
        }
    }

    public function show(Penjualan $penjualan)
    {
        $penjualan->load(['customer', 'gudang', 'creator', 'items.barang.satuan', 'invoicePenjualans']);
        return view('penjualan.show', compact('penjualan'));
    }

    public function edit(Penjualan $penjualan)
    {
        if (in_array($penjualan->status, ['Selesai', 'Dibatalkan'])) {
            return redirect()
                ->route('penjualan.show', $penjualan)
                ->with('error', 'Pesanan penjualan yang sudah Selesai atau Dibatalkan tidak dapat diedit.');
        }

        $penjualan->load('items');
        $customers = Customer::where('is_active', true)->orderBy('nama_customer')->get();
        $gudangs = Gudang::where('is_active', true)->orderBy('nama_gudang')->get();
        $barangs = Barang::where('is_active', true)->orderBy('nama_barang')->get();

        return view('penjualan.edit', compact('penjualan', 'customers', 'gudangs', 'barangs'));
    }

    public function update(Request $request, Penjualan $penjualan)
    {
        if (in_array($penjualan->status, ['Selesai', 'Dibatalkan'])) {
            return redirect()
                ->route('penjualan.show', $penjualan)
                ->with('error', 'Pesanan penjualan tidak dapat diedit.');
        }

        $validated = $request->validate([
            'nomor_penjualan' => 'required|string|unique:penjualans,nomor_penjualan,' . $penjualan->id,
            'customer_id' => 'required|exists:customers,id',
            'gudang_id' => 'nullable|exists:gudangs,id',
            'tanggal_penjualan' => 'required|date',
            'tanggal_estimasi_kirim' => 'nullable|date',
            'catatan' => 'nullable|string',
            'pajak_persen' => 'nullable|numeric|min:0|max:100',
            'status' => 'required|in:Draft,Dikonfirmasi,Diproses,Selesai,Dibatalkan',
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

            $penjualan->update([
                'nomor_penjualan' => $validated['nomor_penjualan'],
                'customer_id' => $validated['customer_id'],
                'gudang_id' => $validated['gudang_id'] ?? null,
                'tanggal_penjualan' => $validated['tanggal_penjualan'],
                'tanggal_estimasi_kirim' => $validated['tanggal_estimasi_kirim'] ?? null,
                'catatan' => $validated['catatan'] ?? null,
                'subtotal' => $subtotal,
                'diskon' => $totalDiskonItem,
                'pajak_persen' => $pajakPersen,
                'pajak' => $pajak,
                'total' => $total,
                'status' => $validated['status'],
            ]);

            $penjualan->items()->delete();

            foreach ($validated['items'] as $item) {
                $itemSubtotal = ($item['qty'] * $item['harga_satuan']) - ($item['diskon'] ?? 0);
                PenjualanItem::create([
                    'penjualan_id' => $penjualan->id,
                    'barang_id' => $item['barang_id'],
                    'qty' => $item['qty'],
                    'harga_satuan' => $item['harga_satuan'],
                    'diskon' => $item['diskon'] ?? 0,
                    'subtotal' => $itemSubtotal,
                    'catatan' => $item['catatan'] ?? null,
                ]);
            }

            DB::commit();

            return redirect()
                ->route('penjualan.show', $penjualan)
                ->with('success', 'Pesanan Penjualan berhasil diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal memperbarui pesanan penjualan: ' . $e->getMessage());
        }
    }

    public function destroy(Penjualan $penjualan)
    {
        if ($penjualan->invoicePenjualans()->exists()) {
            return back()->with('error', 'Pesanan penjualan tidak dapat dihapus karena sudah memiliki invoice terkait.');
        }

        $penjualan->items()->delete();
        $penjualan->delete();

        return redirect()->route('penjualan.index')->with('success', 'Pesanan Penjualan berhasil dihapus.');
    }

    public function pdf(Penjualan $penjualan)
    {
        $penjualan->load(['customer', 'gudang', 'creator', 'items.barang.satuan']);
        
        if (class_exists(Pdf::class)) {
            $pdf = Pdf::loadView('penjualan.pdf', compact('penjualan'));
            return $pdf->stream('Sales-Order-' . $penjualan->nomor_penjualan . '.pdf');
        }

        return view('penjualan.pdf', compact('penjualan'));
    }
}
