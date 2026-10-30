<?php

namespace App\Http\Controllers;

use App\Models\RekeningKasBank;
use App\Models\TransferKasBank;
use App\Services\KasBankService;
use App\Services\SystemAccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class TransferKasBankController extends Controller
{
    public function index(Request $request)
    {
        $query = TransferKasBank::query()
            ->with([
                'rekeningAsal',
                'rekeningTujuan',
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
                    'referensi',
                    'like',
                    '%' . $search . '%'
                )
                ->orWhereHas(
                    'rekeningAsal',
                    function ($rekening) use ($search) {
                        $rekening->where(
                            'nama_rekening',
                            'like',
                            '%' . $search . '%'
                        );
                    }
                )
                ->orWhereHas(
                    'rekeningTujuan',
                    function ($rekening) use ($search) {
                        $rekening->where(
                            'nama_rekening',
                            'like',
                            '%' . $search . '%'
                        );
                    }
                );
            });
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

        $transfers = $query
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view(
            'transfer-kas-bank.index',
            compact('transfers')
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
            'transfer-kas-bank.create',
            compact('rekeningKasBanks')
        );
    }

    public function store(Request $request)
    {
        $validated = $this->validateData($request);

        $transfer = DB::transaction(function () use (
            $validated
        ) {
            return TransferKasBank::create([
                'nomor_transaksi' =>
                    $this->generateNomorTransaksi(),

                'tanggal' =>
                    $validated['tanggal'],

                'rekening_asal_id' =>
                    $validated['rekening_asal_id'],

                'rekening_tujuan_id' =>
                    $validated['rekening_tujuan_id'],

                'jumlah' =>
                    $validated['jumlah'],

                'referensi' =>
                    $validated['referensi'] ?? null,

                'keterangan' =>
                    $validated['keterangan'] ?? null,

                'status' => 'DRAFT',

                'created_by' => auth()->id(),
            ]);
        });

        return redirect()
            ->route(
                'transfer-kas-bank.show',
                $transfer
            )
            ->with(
                'success',
                'Transfer berhasil dibuat sebagai draft.'
            );
    }

    public function show(
        TransferKasBank $transferKasBank
    ) {
        $transferKasBank->load([
            'rekeningAsal',
            'rekeningTujuan',
            'creator',
            'mutasiKeluar',
            'mutasiMasuk',
        ]);

        return view(
            'transfer-kas-bank.show',
            compact('transferKasBank')
        );
    }

    public function edit(
        TransferKasBank $transferKasBank
    ) {
        if ($transferKasBank->status !== 'DRAFT') {
            return redirect()
                ->route(
                    'transfer-kas-bank.show',
                    $transferKasBank
                )
                ->with(
                    'error',
                    'Transfer yang sudah tercatat tidak dapat diedit.'
                );
        }

        $rekeningKasBanks = RekeningKasBank::query()
            ->where('is_active', true)
            ->orWhereIn('id', [
                $transferKasBank->rekening_asal_id,
                $transferKasBank->rekening_tujuan_id,
            ])
            ->orderBy('jenis')
            ->orderBy('nama_rekening')
            ->get();

        return view(
            'transfer-kas-bank.edit',
            compact(
                'transferKasBank',
                'rekeningKasBanks'
            )
        );
    }

    public function update(
        Request $request,
        TransferKasBank $transferKasBank
    ) {
        if ($transferKasBank->status !== 'DRAFT') {
            return redirect()
                ->route('transfer-kas-bank.show', $transferKasBank)
                ->with(
                    'error',
                    'Transfer yang sudah tercatat tidak dapat diedit.'
                );
        }

        $validated = $this->validateData($request);

        $updated = DB::transaction(function () use ($validated, $transferKasBank) {
            $locked = TransferKasBank::query()
                ->lockForUpdate()
                ->findOrFail($transferKasBank->id);

            if ($locked->status !== 'DRAFT') {
                return false;
            }

            $locked->update([
                'tanggal' => $validated['tanggal'],
                'rekening_asal_id' => $validated['rekening_asal_id'],
                'rekening_tujuan_id' => $validated['rekening_tujuan_id'],
                'jumlah' => $validated['jumlah'],
                'referensi' => $validated['referensi'] ?? null,
                'keterangan' => $validated['keterangan'] ?? null,
            ]);

            return true;
        });

        if (!$updated) {
            return redirect()
                ->route('transfer-kas-bank.show', $transferKasBank)
                ->with(
                    'error',
                    'Transfer sudah tercatat oleh proses lain dan tidak dapat diubah.'
                );
        }

        return redirect()
            ->route('transfer-kas-bank.show', $transferKasBank)
            ->with('success', 'Draft transfer berhasil diperbarui.');
    }

    public function destroy(
        TransferKasBank $transferKasBank
    ) {
        if ($transferKasBank->status !== 'DRAFT') {
            return redirect()
                ->route('transfer-kas-bank.show', $transferKasBank)
                ->with(
                    'error',
                    'Transfer yang sudah tercatat tidak dapat dihapus.'
                );
        }

        $deleted = DB::transaction(function () use ($transferKasBank) {
            $locked = TransferKasBank::query()
                ->lockForUpdate()
                ->findOrFail($transferKasBank->id);

            if ($locked->status !== 'DRAFT') {
                return false;
            }

            $locked->delete();
            return true;
        });

        if (!$deleted) {
            return redirect()
                ->route('transfer-kas-bank.show', $transferKasBank)
                ->with(
                    'error',
                    'Transfer sudah tercatat oleh proses lain dan tidak dapat dihapus.'
                );
        }

        return redirect()
            ->route('transfer-kas-bank.index')
            ->with('success', 'Draft transfer berhasil dihapus.');
    }



    public function catat(
        TransferKasBank $transferKasBank,
        KasBankService $kasBankService,
        SystemAccountingService $accountingService
    ) {
        try {
            DB::transaction(function () use (
                $transferKasBank,
                $kasBankService,
                $accountingService
            ) {
                $transfer = TransferKasBank::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $transferKasBank->id
                    );

                if ($transfer->status !== 'DRAFT') {
                    throw new RuntimeException(
                        'Transfer ini sudah tercatat.'
                    );
                }

                $kasBankService->transfer(
                    rekeningAsalId:
                        (int) $transfer->rekening_asal_id,

                    rekeningTujuanId:
                        (int) $transfer->rekening_tujuan_id,

                    jumlah:
                        (float) $transfer->jumlah,

                    referenceType:
                        'TRANSFER_KAS_BANK',

                    referenceId:
                        (int) $transfer->id,

                    nomorReferensi:
                        $transfer->nomor_transaksi,

                    keterangan:
                        $this->buildKeteranganMutasi(
                            $transfer
                        ),

                    tanggalTransaksi:
                        $transfer->tanggal->format(
                            'Y-m-d'
                        )
                );

                /*
                |--------------------------------------------------------------------------
                | JURNAL OTOMATIS
                |--------------------------------------------------------------------------
                |
                | Dr Akun Kas/Bank Tujuan
                |    Cr Akun Kas/Bank Asal
                |
                */

                $accountingService
                    ->autoJournalTransferKasBank(
                        $transfer
                    );

                $transfer->update([
                    'status' => 'TERCATAT',
                ]);
            });

            return redirect()
                ->route(
                    'transfer-kas-bank.show',
                    $transferKasBank
                )
                ->with(
                    'success',
                    'Transfer berhasil dicatat. Saldo rekening asal/tujuan dan jurnal accounting telah diperbarui.'
                );

        } catch (\Throwable $e) {
            return redirect()
                ->route(
                    'transfer-kas-bank.show',
                    $transferKasBank
                )
                ->with(
                    'error',
                    $e->getMessage()
                );
        }
    }

    private function validateData(
        Request $request
    ): array {
        return $request->validate([
            'tanggal' => [
                'required',
                'date',
            ],

            'rekening_asal_id' => [
                'required',
                'exists:rekening_kas_banks,id',
                'different:rekening_tujuan_id',
            ],

            'rekening_tujuan_id' => [
                'required',
                'exists:rekening_kas_banks,id',
                'different:rekening_asal_id',
            ],

            'jumlah' => [
                'required',
                'numeric',
                'gt:0',
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
    }

    private function generateNomorTransaksi(): string
    {
        $tahun = now()->format('Y');
        $bulan = now()->format('m');

        $prefix =
            'TRF/'
            . $tahun
            . '/'
            . $bulan
            . '/';

        $terakhir = TransferKasBank::query()
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
        TransferKasBank $transfer
    ): string {
        $transfer->loadMissing([
            'rekeningAsal',
            'rekeningTujuan',
        ]);

        $parts = [
            'Transfer '
            . $transfer->nomor_transaksi,

            ($transfer->rekeningAsal?->nama_rekening ?? '-')
            . ' → '
            . ($transfer->rekeningTujuan?->nama_rekening ?? '-'),
        ];

        if ($transfer->referensi) {
            $parts[] =
                'Referensi: '
                . $transfer->referensi;
        }

        if ($transfer->keterangan) {
            $parts[] =
                $transfer->keterangan;
        }

        return implode(
            ' | ',
            $parts
        );
    }
}