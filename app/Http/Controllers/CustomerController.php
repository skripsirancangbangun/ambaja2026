<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_customer', 'like', "%{$search}%")
                  ->orWhere('kode_customer', 'like', "%{$search}%")
                  ->orWhere('telepon', 'like', "%{$search}%");
            });
        }

        $customers = $query->latest()->paginate(10)->withQueryString();

        return view('customer.index', compact('customers'));
    }

    public function create()
    {
        // Auto generate kode customer
        $lastCustomer = Customer::latest('id')->first();
        $nextNumber = $lastCustomer ? ($lastCustomer->id + 1) : 1;
        $defaultKode = 'CUST-' . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        return view('customer.create', compact('defaultKode'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'kode_customer' => 'required|string|max:50|unique:customers,kode_customer',
            'nama_customer' => 'required|string|max:255',
            'npwp' => 'nullable|string|max:30',
            'contact_person' => 'nullable|string|max:255',
            'telepon' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'alamat' => 'nullable|string',
            'kota' => 'nullable|string|max:255',
            'limit_piutang' => 'nullable|numeric|min:0',
            'is_active' => 'required|boolean',
        ]);

        $validated['limit_piutang'] = $validated['limit_piutang'] ?? 0;

        Customer::create($validated);

        return redirect()
            ->route('customer.index')
            ->with('success', 'Customer berhasil ditambahkan.');
    }

    public function edit(Customer $customer)
    {
        return view('customer.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'kode_customer' => 'required|string|max:50|unique:customers,kode_customer,' . $customer->id,
            'nama_customer' => 'required|string|max:255',
            'npwp' => 'nullable|string|max:30',
            'contact_person' => 'nullable|string|max:255',
            'telepon' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'alamat' => 'nullable|string',
            'kota' => 'nullable|string|max:255',
            'limit_piutang' => 'nullable|numeric|min:0',
            'is_active' => 'required|boolean',
        ]);

        $validated['limit_piutang'] = $validated['limit_piutang'] ?? 0;

        $customer->update($validated);

        return redirect()
            ->route('customer.index')
            ->with('success', 'Data Customer berhasil diperbarui.');
    }

    public function destroy(Customer $customer)
    {
        if ($customer->invoicePenjualans()->where('status_pembayaran', '!=', 'Lunas')->exists()) {
            return redirect()
                ->route('customer.index')
                ->with('error', 'Customer tidak dapat dihapus karena masih memiliki invoice/piutang aktif.');
        }

        $customer->delete();

        return redirect()
            ->route('customer.index')
            ->with('success', 'Customer berhasil dihapus.');
    }
}
