<?php

namespace App\Http\Controllers;

use App\Models\RekeningKasBank;
use App\Models\UangKeluar;
use App\Services\KasBankService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class UangKeluarController extends Controller
{
    public function index(Request $request)
    {
        $query = UangKeluar::query()
            ->with([
                'rekeningKasBank',
                'creator',
            ]);

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where(
                    'nomor_transaksi',
                    'like',
                    '%' . $search . '%'
                )
                ->orWhere(
                    'tujuan',
                    'like',
                    '%' . $search . '%'
                )
                ->orWhere(
                    'referensi',
                    'like',
                    '%' . $search . '%'
                )
                ->orWhereHas(
                    'rekeningKasBank',
                    function ($rekeningQuery) use ($search) {
                        $rekeningQuery
                            ->where(
                                'kode_rekening',
                                'like',
                                '%' . $search . '%'
                            )
                            ->orWhere(
                                'nama_rekening',
                                'like',
                                '%' . $search . '%'
                            );
                    }
                );
            });
        }

        if ($request->filled('rekening_kas_bank_id')) {
            $query->where(
                'rekening_kas_bank_id',
                $request->rekening_kas_bank_id
            );
        }

        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->status
            );
        }

        if ($request->filled('tanggal_mulai')) {
            $query->whereDate(
                'tanggal',
                '>=',
                $request->tanggal_mulai
            );
        }

        if ($request->filled('tanggal_selesai')) {
            $query->whereDate(
                'tanggal',
                '<=',
                $request->tanggal_selesai
            );
        }

        $uangKeluars = $query
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $rekeningKasBanks = RekeningKasBank::query()
            ->where('is_active', true)
            ->orderBy('jenis')
            ->orderBy('nama_rekening')
            ->get();

        return view(
            'uang-keluar.index',
            compact(
                'uangKeluars',
                'rekeningKasBanks'
            )
        );
    }

    public function create()
    {
        $rekeningKasBanks = RekeningKasBank::query()
            ->where('is_active', true)
            ->orderBy('jenis')
            ->orderBy('nama_rekening')
            ->get();

        return view(
            'uang-keluar.create',
            compact('rekeningKasBanks')
        );
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tanggal' => [
                'required',
                'date',
            ],

            'rekening_kas_bank_id' => [
                'required',
                'exists:rekening_kas_banks,id',
            ],

            'jumlah' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'tujuan' => [
                'required',
                'string',
                'max:150',
            ],

            'referensi' => [
                'nullable',
                'string',
                'max:150',
            ],

            'keterangan' => [
                'nullable',
                'string',
            ],
        ]);

        $uangKeluar = DB::transaction(function () use ($validated) {
            return UangKeluar::create([
                'nomor_transaksi' => $this->generateNomorTransaksi(),
                'tanggal' => $validated['tanggal'],
                'rekening_kas_bank_id' => $validated['rekening_kas_bank_id'],
                'jumlah' => $validated['jumlah'],
                'tujuan' => $validated['tujuan'],
                'referensi' => $validated['referensi'] ?? null,
                'keterangan' => $validated['keterangan'] ?? null,
                'status' => 'DRAFT',
                'created_by' => auth()->id(),
            ]);
        });

        return redirect()
            ->route(
                'uang-keluar.show',
                $uangKeluar
            )
            ->with(
                'success',
                'Uang Keluar berhasil dibuat sebagai draft.'
            );
    }

    public function show(UangKeluar $uangKeluar)
    {
        $uangKeluar->load([
            'rekeningKasBank',
            'creator',
            'mutasiKasBank',
        ]);

        return view(
            'uang-keluar.show',
            compact('uangKeluar')
        );
    }

    public function edit(UangKeluar $uangKeluar)
    {
        if ($uangKeluar->status !== 'DRAFT') {
            return redirect()
                ->route(
                    'uang-keluar.show',
                    $uangKeluar
                )
                ->with(
                    'error',
                    'Transaksi yang sudah tercatat tidak dapat diedit.'
                );
        }

        $rekeningKasBanks = RekeningKasBank::query()
            ->where('is_active', true)
            ->orWhere(
                'id',
                $uangKeluar->rekening_kas_bank_id
            )
            ->orderBy('jenis')
            ->orderBy('nama_rekening')
            ->get();

        return view(
            'uang-keluar.edit',
            compact(
                'uangKeluar',
                'rekeningKasBanks'
            )
        );
    }

    public function update(
        Request $request,
        UangKeluar $uangKeluar
    ) {
        if ($uangKeluar->status !== 'DRAFT') {
            return redirect()
                ->route(
                    'uang-keluar.show',
                    $uangKeluar
                )
                ->with(
                    'error',
                    'Transaksi yang sudah tercatat tidak dapat diubah.'
                );
        }

        $validated = $request->validate([
            'tanggal' => [
                'required',
                'date',
            ],

            'rekening_kas_bank_id' => [
                'required',
                'exists:rekening_kas_banks,id',
            ],

            'jumlah' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'tujuan' => [
                'required',
                'string',
                'max:150',
            ],

            'referensi' => [
                'nullable',
                'string',
                'max:150',
            ],

            'keterangan' => [
                'nullable',
                'string',
            ],
        ]);

        $uangKeluar->update([
            'tanggal' => $validated['tanggal'],
            'rekening_kas_bank_id' => $validated['rekening_kas_bank_id'],
            'jumlah' => $validated['jumlah'],
            'tujuan' => $validated['tujuan'],
            'referensi' => $validated['referensi'] ?? null,
            'keterangan' => $validated['keterangan'] ?? null,
        ]);

        return redirect()
            ->route(
                'uang-keluar.show',
                $uangKeluar
            )
            ->with(
                'success',
                'Draft Uang Keluar berhasil diperbarui.'
            );
    }

    public function destroy(UangKeluar $uangKeluar)
    {
        if ($uangKeluar->status !== 'DRAFT') {
            return redirect()
                ->route(
                    'uang-keluar.show',
                    $uangKeluar
                )
                ->with(
                    'error',
                    'Transaksi yang sudah tercatat tidak dapat dihapus.'
                );
        }

        $uangKeluar->delete();

        return redirect()
            ->route('uang-keluar.index')
            ->with(
                'success',
                'Draft Uang Keluar berhasil dihapus.'
            );
    }

    public function catat(
        UangKeluar $uangKeluar,
        KasBankService $kasBankService
    ) {
        try {
            DB::transaction(function () use (
                $uangKeluar,
                $kasBankService
            ) {
                $uangKeluar = UangKeluar::query()
                    ->lockForUpdate()
                    ->findOrFail($uangKeluar->id);

                if ($uangKeluar->status !== 'DRAFT') {
                    throw new RuntimeException(
                        'Transaksi Uang Keluar ini sudah tercatat.'
                    );
                }

                $kasBankService->uangKeluar(
                    rekeningKasBankId: (int) $uangKeluar->rekening_kas_bank_id,
                    jumlah: (float) $uangKeluar->jumlah,
                    referenceType: 'UANG_KELUAR',
                    referenceId: (int) $uangKeluar->id,
                    nomorReferensi: $uangKeluar->nomor_transaksi,
                    keterangan: $this->buildKeteranganMutasi(
                        $uangKeluar
                    ),
                    tanggalTransaksi: $uangKeluar->tanggal->format('Y-m-d'),
                    tipe: 'UANG_KELUAR'
                );

                $uangKeluar->update([
                    'status' => 'TERCATAT',
                ]);
            });

            return redirect()
                ->route(
                    'uang-keluar.show',
                    $uangKeluar
                )
                ->with(
                    'success',
                    'Uang Keluar berhasil dicatat dan saldo Kas/Bank telah diperbarui.'
                );

        } catch (RuntimeException $e) {
            return redirect()
                ->route(
                    'uang-keluar.show',
                    $uangKeluar
                )
                ->with(
                    'error',
                    $e->getMessage()
                );
        }
    }

    private function generateNomorTransaksi(): string
    {
        $tahun = now()->format('Y');
        $bulan = now()->format('m');

        $prefix = 'UK/' . $tahun . '/' . $bulan . '/';

        $terakhir = UangKeluar::query()
            ->where(
                'nomor_transaksi',
                'like',
                $prefix . '%'
            )
            ->orderByDesc('nomor_transaksi')
            ->lockForUpdate()
            ->first();

        $urutan = 1;

        if ($terakhir) {
            $nomorTerakhir = (int) substr(
                $terakhir->nomor_transaksi,
                -4
            );

            $urutan = $nomorTerakhir + 1;
        }

        return $prefix
            . str_pad(
                $urutan,
                4,
                '0',
                STR_PAD_LEFT
            );
    }

    private function buildKeteranganMutasi(
        UangKeluar $uangKeluar
    ): string {
        $parts = [
            'Uang Keluar ' . $uangKeluar->nomor_transaksi,
            'Tujuan: ' . $uangKeluar->tujuan,
        ];

        if ($uangKeluar->referensi) {
            $parts[] =
                'Referensi: '
                . $uangKeluar->referensi;
        }

        if ($uangKeluar->keterangan) {
            $parts[] = $uangKeluar->keterangan;
        }

        return implode(' | ', $parts);
    }
}