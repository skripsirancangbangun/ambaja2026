<?php

namespace App\Http\Controllers;

use App\Models\RekeningKasBank;
use App\Models\TransaksiBelumTeridentifikasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TransaksiBelumTeridentifikasiController extends Controller
{
    public function index(Request $request)
    {
        $rekeningBanks = RekeningKasBank::query()
            ->where('jenis', 'BANK')
            ->orderBy('nama_bank')
            ->orderBy('nama_rekening')
            ->get();

        $query = TransaksiBelumTeridentifikasi::query()
            ->with([
                'rekeningKasBank',
                'creator',
                'identifier',
            ]);

        /*
        |--------------------------------------------------------------------------
        | SEARCH
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where(
                    'nama_pengirim_penerima',
                    'like',
                    '%' . $search . '%'
                )
                ->orWhere(
                    'nomor_referensi_bank',
                    'like',
                    '%' . $search . '%'
                )
                ->orWhere(
                    'deskripsi_bank',
                    'like',
                    '%' . $search . '%'
                )
                ->orWhereHas(
                    'rekeningKasBank',
                    function ($rekening) use ($search) {
                        $rekening
                            ->where(
                                'nama_rekening',
                                'like',
                                '%' . $search . '%'
                            )
                            ->orWhere(
                                'nama_bank',
                                'like',
                                '%' . $search . '%'
                            )
                            ->orWhere(
                                'nomor_rekening',
                                'like',
                                '%' . $search . '%'
                            );
                    }
                );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER BANK
        |--------------------------------------------------------------------------
        */

        if ($request->filled('rekening_kas_bank_id')) {
            $query->where(
                'rekening_kas_bank_id',
                $request->rekening_kas_bank_id
            );
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER JENIS
        |--------------------------------------------------------------------------
        */

        if ($request->filled('jenis')) {
            $query->where(
                'jenis',
                $request->jenis
            );
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER STATUS
        |--------------------------------------------------------------------------
        */

        if ($request->filled('status')) {
            $query->where(
                'status',
                $request->status
            );
        } else {
            /*
             * Default halaman ini menampilkan yang
             * memang belum teridentifikasi.
             */
            $query->where(
                'status',
                'BELUM_TERIDENTIFIKASI'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | FILTER TANGGAL
        |--------------------------------------------------------------------------
        */

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

        /*
        |--------------------------------------------------------------------------
        | SUMMARY
        |--------------------------------------------------------------------------
        */

        $summaryQuery = clone $query;

        $totalMasuk = (float) (clone $summaryQuery)
            ->where('jenis', 'MASUK')
            ->sum('jumlah');

        $totalKeluar = (float) (clone $summaryQuery)
            ->where('jenis', 'KELUAR')
            ->sum('jumlah');

        $jumlahTransaksi = (clone $summaryQuery)->count();

        /*
        |--------------------------------------------------------------------------
        | DATA
        |--------------------------------------------------------------------------
        */

        $transaksis = $query
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view(
            'transaksi-belum-teridentifikasi.index',
            compact(
                'transaksis',
                'rekeningBanks',
                'totalMasuk',
                'totalKeluar',
                'jumlahTransaksi'
            )
        );
    }

    public function create()
    {
        $rekeningBanks = RekeningKasBank::query()
            ->where('jenis', 'BANK')
            ->where('is_active', true)
            ->orderBy('nama_bank')
            ->orderBy('nama_rekening')
            ->get();

        return view(
            'transaksi-belum-teridentifikasi.create',
            compact('rekeningBanks')
        );
    }

    public function store(Request $request)
    {
        $validated = $this->validateData($request);

        /*
         * Pastikan rekening benar-benar BANK,
         * bukan Kas Kecil.
         */
        RekeningKasBank::query()
            ->where('id', $validated['rekening_kas_bank_id'])
            ->where('jenis', 'BANK')
            ->where('is_active', true)
            ->firstOrFail();

        $transaksi = TransaksiBelumTeridentifikasi::create([
            'tanggal' => $validated['tanggal'],

            'rekening_kas_bank_id' =>
                $validated['rekening_kas_bank_id'],

            'jenis' => $validated['jenis'],

            'jumlah' => $validated['jumlah'],

            'nama_pengirim_penerima' =>
                $validated['nama_pengirim_penerima'] ?? null,

            'nomor_referensi_bank' =>
                $validated['nomor_referensi_bank'] ?? null,

            'deskripsi_bank' =>
                $validated['deskripsi_bank'] ?? null,

            'status' => 'BELUM_TERIDENTIFIKASI',

            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->route(
                'transaksi-belum-teridentifikasi.show',
                $transaksi
            )
            ->with(
                'success',
                'Transaksi bank berhasil dicatat sebagai belum teridentifikasi.'
            );
    }

    public function show(
        TransaksiBelumTeridentifikasi $transaksiBelumTeridentifikasi
    ) {
        $transaksiBelumTeridentifikasi->load([
            'rekeningKasBank',
            'creator',
            'identifier',
        ]);

        return view(
            'transaksi-belum-teridentifikasi.show',
            compact('transaksiBelumTeridentifikasi')
        );
    }

    public function edit(
        TransaksiBelumTeridentifikasi $transaksiBelumTeridentifikasi
    ) {
        if (
            $transaksiBelumTeridentifikasi->status
            !== 'BELUM_TERIDENTIFIKASI'
        ) {
            return redirect()
                ->route(
                    'transaksi-belum-teridentifikasi.show',
                    $transaksiBelumTeridentifikasi
                )
                ->with(
                    'error',
                    'Transaksi yang sudah diproses tidak dapat diedit.'
                );
        }

        $rekeningBanks = RekeningKasBank::query()
            ->where('jenis', 'BANK')
            ->where(function ($query) use (
                $transaksiBelumTeridentifikasi
            ) {
                $query
                    ->where('is_active', true)
                    ->orWhere(
                        'id',
                        $transaksiBelumTeridentifikasi
                            ->rekening_kas_bank_id
                    );
            })
            ->orderBy('nama_bank')
            ->orderBy('nama_rekening')
            ->get();

        return view(
            'transaksi-belum-teridentifikasi.edit',
            compact(
                'transaksiBelumTeridentifikasi',
                'rekeningBanks'
            )
        );
    }

    public function update(
        Request $request,
        TransaksiBelumTeridentifikasi $transaksiBelumTeridentifikasi
    ) {
        $validated = $this->validateData($request);

        $updated = DB::transaction(function () use (
            $validated,
            $transaksiBelumTeridentifikasi
        ): bool {
            $transaksi = TransaksiBelumTeridentifikasi::query()
                ->whereKey($transaksiBelumTeridentifikasi->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($transaksi->status !== 'BELUM_TERIDENTIFIKASI') {
                return false;
            }

            $rekening = RekeningKasBank::query()
                ->whereKey($validated['rekening_kas_bank_id'])
                ->where('jenis', 'BANK')
                ->where(function ($query) use ($transaksi) {
                    $query->where('is_active', true)
                        ->orWhere('id', $transaksi->rekening_kas_bank_id);
                })
                ->lockForUpdate()
                ->first();

            if (!$rekening) {
                throw ValidationException::withMessages([
                    'rekening_kas_bank_id' =>
                        'Pilih rekening bank yang aktif.',
                ]);
            }

            $transaksi->update([
                'tanggal' => $validated['tanggal'],
                'rekening_kas_bank_id' => $rekening->id,
                'jenis' => $validated['jenis'],
                'jumlah' => $validated['jumlah'],
                'nama_pengirim_penerima' =>
                    $validated['nama_pengirim_penerima'] ?? null,
                'nomor_referensi_bank' =>
                    $validated['nomor_referensi_bank'] ?? null,
                'deskripsi_bank' =>
                    $validated['deskripsi_bank'] ?? null,
            ]);

            // import_hash sengaja dipertahankan sebagai fingerprint sumber asli.
            // Mengubahnya setelah edit akan memungkinkan baris yang sama diimpor ulang.
            return true;
        });

        if (!$updated) {
            return redirect()
                ->route('transaksi-belum-teridentifikasi.show', $transaksiBelumTeridentifikasi)
                ->with('error', 'Transaksi sudah diproses oleh proses lain dan tidak dapat diubah.');
        }

        return redirect()
            ->route('transaksi-belum-teridentifikasi.show', $transaksiBelumTeridentifikasi)
            ->with('success', 'Transaksi bank berhasil diperbarui.');
    }

    public function destroy(
        TransaksiBelumTeridentifikasi $transaksiBelumTeridentifikasi
    ) {
        $deleted = DB::transaction(function () use (
            $transaksiBelumTeridentifikasi
        ): bool {
            $transaksi = TransaksiBelumTeridentifikasi::query()
                ->whereKey($transaksiBelumTeridentifikasi->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($transaksi->status !== 'BELUM_TERIDENTIFIKASI') {
                return false;
            }

            // Baris impor harus tetap menyimpan fingerprint-nya agar import ulang
            // tidak menciptakan transaksi duplikat. Gunakan aksi "Abaikan".
            if (!empty($transaksi->import_hash)) {
                return false;
            }

            $transaksi->delete();

            return true;
        });

        if (!$deleted) {
            return redirect()
                ->route('transaksi-belum-teridentifikasi.show', $transaksiBelumTeridentifikasi)
                ->with(
                    'error',
                    $transaksiBelumTeridentifikasi->import_hash
                        ? 'Transaksi hasil impor tidak dapat dihapus permanen. Gunakan aksi Abaikan agar fingerprint sumber tetap mencegah impor duplikat.'
                        : 'Transaksi sudah diproses oleh proses lain dan tidak dapat dihapus.'
                );
        }

        return redirect()
            ->route('transaksi-belum-teridentifikasi.index')
            ->with('success', 'Transaksi bank berhasil dihapus.');
    }

    public function abaikan(
        TransaksiBelumTeridentifikasi $transaksiBelumTeridentifikasi
    ) {
        $ignored = DB::transaction(function () use (
            $transaksiBelumTeridentifikasi
        ): bool {
            $transaksi = TransaksiBelumTeridentifikasi::query()
                ->whereKey($transaksiBelumTeridentifikasi->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($transaksi->status !== 'BELUM_TERIDENTIFIKASI') {
                return false;
            }

            $transaksi->update([
                'status' => 'DIABAIKAN',
                'identified_at' => now(),
                'identified_by' => auth()->id(),
            ]);

            return true;
        });

        if (!$ignored) {
            return back()->with(
                'error',
                'Transaksi sudah diproses oleh proses lain.'
            );
        }

        return redirect()
            ->route('transaksi-belum-teridentifikasi.index')
            ->with('success', 'Transaksi ditandai sebagai diabaikan.');
    }

    private function validateData(Request $request): array
    {
        return $request->validate([
            'tanggal' => [
                'required',
                'date',
            ],

            'rekening_kas_bank_id' => [
                'required',
                'exists:rekening_kas_banks,id',
            ],

            'jenis' => [
                'required',
                'in:MASUK,KELUAR',
            ],

            'jumlah' => [
                'required',
                'numeric',
                'gt:0',
            ],

            'nama_pengirim_penerima' => [
                'nullable',
                'string',
                'max:200',
            ],

            'nomor_referensi_bank' => [
                'nullable',
                'string',
                'max:200',
            ],

            'deskripsi_bank' => [
                'nullable',
                'string',
            ],
        ]);
    }
}