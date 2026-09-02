<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use App\Services\SystemAccountingService;
use Illuminate\Http\Request;

class CoaController extends Controller
{
    protected SystemAccountingService $accountingService;

    public function __construct(SystemAccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    public function index(Request $request)
    {
        $query = Akun::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nama_akun', 'like', "%{$search}%")
                  ->orWhere('kode_akun', 'like', "%{$search}%");
            });
        }

        if ($request->filled('kategori_akun')) {
            $query->where('kategori_akun', $request->kategori_akun);
        }

        $akuns = $query->orderBy('kode_akun')->paginate(20)->withQueryString();
        $canEditOpeningBalance = $this->accountingService->canEditOpeningBalance();

        return view('coa.index', compact('akuns', 'canEditOpeningBalance'));
    }

    public function create()
    {
        $canEditOpeningBalance = $this->accountingService->canEditOpeningBalance();
        return view('coa.create', compact('canEditOpeningBalance'));
    }

    public function store(Request $request)
    {
        $canEditOpeningBalance = $this->accountingService->canEditOpeningBalance();

        $validated = $request->validate([
            'kode_akun' => 'required|string|max:50|unique:akuns,kode_akun',
            'nama_akun' => 'required|string|max:255',
            'kategori_akun' => 'required|string',
            'sub_kategori' => 'nullable|string|max:255',
            'saldo_normal' => 'required|in:DEBIT,KREDIT',
            'saldo_awal' => 'nullable|numeric',
            'is_active' => 'required|boolean',
        ]);

        if (!$canEditOpeningBalance) {
            $validated['saldo_awal'] = 0; // Kunci saldo awal baru jika sistem sudah berjalan
        } else {
            $validated['saldo_awal'] = $validated['saldo_awal'] ?? 0;
        }

        Akun::create($validated);

        return redirect()
            ->route('coa.index')
            ->with('success', 'Akun berhasil ditambahkan ke Chart of Accounts.');
    }

    public function edit(Akun $coa)
    {
        $canEditOpeningBalance = $this->accountingService->canEditOpeningBalance();
        return view('coa.edit', ['akun' => $coa, 'canEditOpeningBalance' => $canEditOpeningBalance]);
    }

    public function update(Request $request, Akun $coa)
    {
        $canEditOpeningBalance = $this->accountingService->canEditOpeningBalance();

        $validated = $request->validate([
            'kode_akun' => 'required|string|max:50|unique:akuns,kode_akun,' . $coa->id,
            'nama_akun' => 'required|string|max:255',
            'kategori_akun' => 'required|string',
            'sub_kategori' => 'nullable|string|max:255',
            'saldo_normal' => 'required|in:DEBIT,KREDIT',
            'saldo_awal' => 'nullable|numeric',
            'is_active' => 'required|boolean',
        ]);

        if (!$canEditOpeningBalance) {
            // Abaikan perubahan saldo awal jika sistem sudah berjalan (Saldo awal terkunci)
            unset($validated['saldo_awal']);
        } else {
            $validated['saldo_awal'] = $validated['saldo_awal'] ?? 0;
        }

        $coa->update($validated);

        return redirect()
            ->route('coa.index')
            ->with('success', 'Data Akun berhasil diperbarui.');
    }

    public function destroy(Akun $coa)
    {
        if ($coa->jurnalDetails()->exists()) {
            return redirect()
                ->route('coa.index')
                ->with('error', 'Akun tidak dapat dihapus karena sudah memiliki riwayat mutasi jurnal.');
        }

        $coa->delete();

        return redirect()
            ->route('coa.index')
            ->with('success', 'Akun berhasil dihapus dari Chart of Accounts.');
    }
}
