<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use App\Models\Jurnal;
use App\Models\JurnalDetail;
use App\Models\RekeningKasBank;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JurnalUmumController extends Controller
{
    public function index(Request $request)
    {
        $query = Jurnal::with(['creator', 'details.akun']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nomor_jurnal', 'like', "%{$search}%")
                  ->orWhere('keterangan', 'like', "%{$search}%");
            });
        }

        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('tanggal', '>=', $request->tanggal_mulai);
        }

        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('tanggal', '<=', $request->tanggal_selesai);
        }

        $jurnals = $query->latest('tanggal')->latest('id')->paginate(15)->withQueryString();

        return view('jurnal-umum.index', compact('jurnals'));
    }

    public function create()
    {
        $akuns = Akun::where('is_active', true)->orderBy('kode_akun')->get();
        $nomorJurnal = app(\App\Services\SystemAccountingService::class)->generateDocumentNumber('JURNAL_UMUM');

        return view('jurnal-umum.create', compact('akuns', 'nomorJurnal'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nomor_jurnal' => 'required|string|unique:jurnals,nomor_jurnal',
            'tanggal' => 'required|date',
            'keterangan' => 'required|string',
            'items' => 'required|array|min:2',
            'items.*.akun_id' => 'required|exists:akuns,id',
            'items.*.debit' => 'nullable|numeric|min:0',
            'items.*.kredit' => 'nullable|numeric|min:0',
            'items.*.keterangan' => 'nullable|string',
        ]);

        $cashBankAccountIds = $this->getCashBankAccountIds();
        $usesCashBankAccount = collect($validated['items'])->contains(
            fn ($item) => in_array((int) $item['akun_id'], $cashBankAccountIds, true)
        );

        if ($usesCashBankAccount) {
            return back()
                ->withInput()
                ->with('error', 'Jurnal manual tidak boleh menggunakan akun Kas/Bank. Catat transaksi melalui modul Kas/Bank agar mutasi rekening dan jurnal otomatis tetap sinkron.');
        }

        $totalDebit = 0;
        $totalKredit = 0;

        foreach ($validated['items'] as $item) {
            $totalDebit += (float) ($item['debit'] ?? 0);
            $totalKredit += (float) ($item['kredit'] ?? 0);
        }

        if (abs($totalDebit - $totalKredit) > 0.01) {
            return back()->withInput()->with('error', 'Total Debit (Rp ' . number_format($totalDebit, 0, ',', '.') . ') dan Total Kredit (Rp ' . number_format($totalKredit, 0, ',', '.') . ') harus SEIMBANG / BALANCE!');
        }

        if ($totalDebit <= 0) {
            return back()->withInput()->with('error', 'Total nilai jurnal harus lebih dari 0.');
        }

        DB::beginTransaction();
        try {
            $jurnal = Jurnal::create([
                'nomor_jurnal' => $validated['nomor_jurnal'],
                'tanggal' => $validated['tanggal'],
                'keterangan' => $validated['keterangan'],
                'total_debit' => $totalDebit,
                'total_kredit' => $totalKredit,
                'is_posted' => true,
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                $debit = (float) ($item['debit'] ?? 0);
                $kredit = (float) ($item['kredit'] ?? 0);

                if ($debit > 0 || $kredit > 0) {
                    JurnalDetail::create([
                        'jurnal_id' => $jurnal->id,
                        'akun_id' => $item['akun_id'],
                        'debit' => $debit,
                        'kredit' => $kredit,
                        'keterangan' => $item['keterangan'] ?? null,
                    ]);
                }
            }

            DB::commit();

            return redirect()
                ->route('jurnal-umum.show', $jurnal)
                ->with('success', 'Jurnal Umum berhasil dicatat dan diposting.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal menyimpan jurnal umum: ' . $e->getMessage());
        }
    }

    public function show(Jurnal $jurnalUmum)
    {
        $jurnalUmum->load(['creator', 'details.akun']);
        return view('jurnal-umum.show', ['jurnal' => $jurnalUmum]);
    }

    public function destroy(Jurnal $jurnalUmum)
    {
        try {
            DB::transaction(function () use ($jurnalUmum) {
                $jurnal = Jurnal::query()
                    ->lockForUpdate()
                    ->findOrFail($jurnalUmum->id);

                // referensi_type juga dipakai sebagai pengaman untuk jurnal otomatis lama
                // yang mungkin belum memiliki flag is_system_generated = true.
                if ($jurnal->is_system_generated || filled($jurnal->referensi_type)) {
                    throw new \RuntimeException(
                        'Jurnal otomatis tidak dapat dihapus dari menu Jurnal Umum. Batalkan atau koreksi transaksi sumber melalui modul terkait.'
                    );
                }

                if ($jurnal->is_posted) {
                    throw new \RuntimeException(
                        'Jurnal yang sudah diposting tidak boleh dihapus. Catat jurnal pembalik atau koreksi sesuai prosedur akuntansi.'
                    );
                }

                $cashBankAccountIds = $this->getCashBankAccountIds();
                if ($jurnal->details()->whereIn('akun_id', $cashBankAccountIds)->exists()) {
                    throw new \RuntimeException(
                        'Jurnal yang menyentuh akun Kas/Bank tidak dapat dihapus dari menu ini.'
                    );
                }

                $jurnal->details()->delete();
                $jurnal->delete();
            });
        } catch (\RuntimeException $exception) {
            return redirect()
                ->route('jurnal-umum.index')
                ->with('error', $exception->getMessage());
        }

        return redirect()
            ->route('jurnal-umum.index')
            ->with('success', 'Jurnal draft berhasil dihapus.');
    }

    /**
     * Resolve COA accounts that represent cash/bank ledgers.
     * Use explicit account mappings first, plus legacy asset accounts named Kas/Bank.
     */
    private function getCashBankAccountIds(): array
    {
        $mappedIds = RekeningKasBank::query()
            ->whereNotNull('akun_id')
            ->pluck('akun_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $legacyIds = Akun::query()
            ->whereRaw('LOWER(kategori_akun) = ?', ['aset'])
            ->where(function ($query) {
                $query
                    ->where('nama_akun', 'like', '%kas%')
                    ->orWhere('nama_akun', 'like', '%bank%');
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return array_values(array_unique(array_merge($mappedIds, $legacyIds)));
    }
}
