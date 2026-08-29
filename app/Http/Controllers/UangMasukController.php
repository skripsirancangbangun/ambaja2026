<?php

namespace App\Http\Controllers;

use App\Models\RekeningKasBank;
use App\Models\UangMasuk;
use App\Services\KasBankService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class UangMasukController extends Controller
{
    public function index(Request $request)
    {
        $query = UangMasuk::query()
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
                    'sumber',
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

        $uangMasuks = $query
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
            'uang-masuk.index',
            compact(
                'uangMasuks',
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
            'uang-masuk.create',
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

            'sumber' => [
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

        $uangMasuk = DB::transaction(function () use ($validated) {
            return UangMasuk::create([
                'nomor_transaksi' => $this->generateNomorTransaksi(),
                'tanggal' => $validated['tanggal'],
                'rekening_kas_bank_id' => $validated['rekening_kas_bank_id'],
                'jumlah' => $validated['jumlah'],
                'sumber' => $validated['sumber'],
                'referensi' => $validated['referensi'] ?? null,
                'keterangan' => $validated['keterangan'] ?? null,
                'status' => 'DRAFT',
                'created_by' => auth()->id(),
            ]);
        });

        return redirect()
            ->route(
                'uang-masuk.show',
                $uangMasuk
            )
            ->with(
                'success',
                'Uang Masuk berhasil dibuat sebagai draft.'
            );
    }

    public function show(
        UangMasuk $uangMasuk
    ) {
        $uangMasuk->load([
            'rekeningKasBank',
            'creator',
            'mutasiKasBank',
        ]);

        return view(
            'uang-masuk.show',
            compact('uangMasuk')
        );
    }

    public function edit(
        UangMasuk $uangMasuk
    ) {
        if ($uangMasuk->status !== 'DRAFT') {
            return redirect()
                ->route(
                    'uang-masuk.show',
                    $uangMasuk
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
                $uangMasuk->rekening_kas_bank_id
            )
            ->orderBy('jenis')
            ->orderBy('nama_rekening')
            ->get();

        return view(
            'uang-masuk.edit',
            compact(
                'uangMasuk',
                'rekeningKasBanks'
            )
        );
    }

    public function update(
        Request $request,
        UangMasuk $uangMasuk
    ) {
        if ($uangMasuk->status !== 'DRAFT') {
            return redirect()
                ->route(
                    'uang-masuk.show',
                    $uangMasuk
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

            'sumber' => [
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

        $uangMasuk->update([
            'tanggal' => $validated['tanggal'],
            'rekening_kas_bank_id' => $validated['rekening_kas_bank_id'],
            'jumlah' => $validated['jumlah'],
            'sumber' => $validated['sumber'],
            'referensi' => $validated['referensi'] ?? null,
            'keterangan' => $validated['keterangan'] ?? null,
        ]);

        return redirect()
            ->route(
                'uang-masuk.show',
                $uangMasuk
            )
            ->with(
                'success',
                'Draft Uang Masuk berhasil diperbarui.'
            );
    }

    public function destroy(
        UangMasuk $uangMasuk
    ) {
        if ($uangMasuk->status !== 'DRAFT') {
            return redirect()
                ->route(
                    'uang-masuk.show',
                    $uangMasuk
                )
                ->with(
                    'error',
                    'Transaksi yang sudah tercatat tidak dapat dihapus.'
                );
        }

        $uangMasuk->delete();

        return redirect()
            ->route('uang-masuk.index')
            ->with(
                'success',
                'Draft Uang Masuk berhasil dihapus.'
            );
    }

    public function catat(
        UangMasuk $uangMasuk,
        KasBankService $kasBankService
    ) {
        try {
            DB::transaction(function () use (
                $uangMasuk,
                $kasBankService
            ) {
                $uangMasuk = UangMasuk::query()
                    ->lockForUpdate()
                    ->findOrFail($uangMasuk->id);

                if ($uangMasuk->status !== 'DRAFT') {
                    throw new RuntimeException(
                        'Transaksi Uang Masuk ini sudah tercatat.'
                    );
                }

                $kasBankService->uangMasuk(
                    rekeningKasBankId: (int) $uangMasuk->rekening_kas_bank_id,
                    jumlah: (float) $uangMasuk->jumlah,
                    referenceType: 'UANG_MASUK',
                    referenceId: (int) $uangMasuk->id,
                    nomorReferensi: $uangMasuk->nomor_transaksi,
                    keterangan: $this->buildKeteranganMutasi(
                        $uangMasuk
                    ),
                    tanggalTransaksi: $uangMasuk->tanggal->format('Y-m-d'),
                    tipe: 'UANG_MASUK'
                );

                $uangMasuk->update([
                    'status' => 'TERCATAT',
                ]);
            });

            return redirect()
                ->route(
                    'uang-masuk.show',
                    $uangMasuk
                )
                ->with(
                    'success',
                    'Uang Masuk berhasil dicatat dan saldo Kas/Bank telah diperbarui.'
                );

        } catch (RuntimeException $e) {
            return redirect()
                ->route(
                    'uang-masuk.show',
                    $uangMasuk
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

        $prefix = 'UM/' . $tahun . '/' . $bulan . '/';

        $terakhir = UangMasuk::query()
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
        UangMasuk $uangMasuk
    ): string {
        $parts = [
            'Uang Masuk ' . $uangMasuk->nomor_transaksi,
            'Sumber: ' . $uangMasuk->sumber,
        ];

        if ($uangMasuk->referensi) {
            $parts[] =
                'Referensi: '
                . $uangMasuk->referensi;
        }

        if ($uangMasuk->keterangan) {
            $parts[] =
                $uangMasuk->keterangan;
        }

        return implode(
            ' | ',
            $parts
        );
    }
}