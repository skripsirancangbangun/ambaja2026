<?php

namespace App\Http\Controllers;

use App\Models\Akun;
use App\Models\Jurnal;
use App\Models\JurnalDetail;
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
        $jurnalUmum->details()->delete();
        $jurnalUmum->delete();

        return redirect()
            ->route('jurnal-umum.index')
            ->with('success', 'Jurnal Umum berhasil dihapus.');
    }
}
