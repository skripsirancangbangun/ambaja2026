<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Supplier;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SupplierController extends Controller
{
    public function index()
    {
        $suppliers = Supplier::query()
            ->withCount('barangs')
            ->latest()
            ->paginate(10);

        return view('supplier.index', compact('suppliers'));
    }

    public function create()
    {
        return view('supplier.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_supplier' => 'required|string|max:50|unique:suppliers,kode_supplier',
            'nama_supplier' => 'required|string|max:255',
            'npwp' => 'nullable|string|max:30',
            'contact_person' => 'nullable|string|max:255',
            'telepon' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'alamat' => 'nullable|string',
            'kota' => 'nullable|string|max:255',
            'is_active' => 'required|boolean',
        ]);

        Supplier::create($validated);

        return redirect()
            ->route('supplier.index')
            ->with('success', 'Supplier berhasil ditambahkan.');
    }

    public function edit(Supplier $supplier)
    {
        return view('supplier.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'kode_supplier' => 'required|string|max:50|unique:suppliers,kode_supplier,' . $supplier->id,
            'nama_supplier' => 'required|string|max:255',
            'npwp' => 'nullable|string|max:30',
            'contact_person' => 'nullable|string|max:255',
            'telepon' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'alamat' => 'nullable|string',
            'kota' => 'nullable|string|max:255',
            'is_active' => 'required|boolean',
        ]);

        $supplier->update($validated);

        return redirect()
            ->route('supplier.index')
            ->with('success', 'Supplier berhasil diperbarui.');
    }

    /**
     * Menampilkan daftar barang yang dapat dipasangkan ke supplier.
     * Pencarian dan pagination dilakukan di server agar aman untuk master
     * barang yang jumlahnya ribuan.
     */
    public function barang(Request $request, Supplier $supplier)
    {
        abort_unless($supplier->is_active, 404);

        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        $search = trim($validated['search'] ?? '');

        $barangs = Barang::query()
            ->with('satuan')
            ->where(function ($query) use ($supplier): void {
                $query->where('is_active', true)
                    ->orWhereHas('suppliers', fn ($supplierQuery) => $supplierQuery->whereKey($supplier->id));
            })
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($barangQuery) use ($search): void {
                    $like = '%' . $search . '%';
                    $barangQuery
                        ->where('kode_barang', 'like', $like)
                        ->orWhere('nama_barang', 'like', $like)
                        ->orWhere('spesifikasi', 'like', $like)
                        ->orWhere('merek', 'like', $like);
                });
            })
            ->orderBy('nama_barang')
            ->paginate(30)
            ->withQueryString();

        $assignedBarangIds = $supplier->barangs()
            ->pluck('barangs.id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        return view('supplier.barang', compact(
            'supplier',
            'barangs',
            'assignedBarangIds',
        ));
    }

    /** Tambahkan satu barang ke daftar supplier tanpa menghapus relasi lain. */
    public function attachBarang(Supplier $supplier, Barang $barang)
    {
        abort_unless($supplier->is_active, 404);

        if (!$barang->is_active) {
            return back()->with('error', 'Barang nonaktif tidak dapat ditambahkan ke daftar supplier.');
        }

        $supplier->barangs()->syncWithoutDetaching([$barang->id]);

        return back()->with('success', 'Barang berhasil ditambahkan ke supplier.');
    }

    /** Hapus hubungan supplier-barang tanpa menghapus master atau transaksi. */
    public function detachBarang(Supplier $supplier, Barang $barang)
    {
        $usedByDraftPurchaseOrder = DB::table('purchase_order_items')
            ->join('purchase_orders', 'purchase_orders.id', '=', 'purchase_order_items.purchase_order_id')
            ->where('purchase_orders.supplier_id', $supplier->id)
            ->where('purchase_orders.status', 'Draft')
            ->where('purchase_order_items.barang_id', $barang->id)
            ->exists();

        if ($usedByDraftPurchaseOrder) {
            return back()->with(
                'error',
                'Barang masih digunakan pada Purchase Order Draft supplier ini. Selesaikan atau hapus PO Draft tersebut sebelum mengeluarkan barang.'
            );
        }

        $supplier->barangs()->detach($barang->id);

        return back()->with('success', 'Barang berhasil dikeluarkan dari daftar supplier.');
    }

    public function destroy(Supplier $supplier)
    {
        try {
            $supplier->delete();
        } catch (QueryException $e) {
            report($e);

            return redirect()
                ->route('supplier.index')
                ->with(
                    'error',
                    'Supplier tidak dapat dihapus karena masih digunakan oleh transaksi atau data terkait. Nonaktifkan supplier jika tidak ingin digunakan lagi.'
                );
        }

        return redirect()
            ->route('supplier.index')
            ->with('success', 'Supplier berhasil dihapus.');
    }
}
