<?php

namespace App\Http\Controllers;

use App\Models\RekeningKasBank;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class RekeningKasBankController extends Controller
{
    /**
     * Daftar Kas & Rekening Bank.
     */
    public function index(Request $request)
    {
        $query = RekeningKasBank::query();

        /*
        |--------------------------------------------------------------------------
        | SEARCH
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where(
                    'kode_rekening',
                    'like',
                    '%' . $search . '%'
                )
                ->orWhere(
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
                )
                ->orWhere(
                    'atas_nama',
                    'like',
                    '%' . $search . '%'
                );
            });
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
                'is_active',
                $request->status === 'aktif'
            );
        }


        $rekeningKasBanks = $query
            ->orderByRaw("
                CASE
                    WHEN jenis = 'KAS' THEN 1
                    WHEN jenis = 'BANK' THEN 2
                    ELSE 3
                END
            ")
            ->orderBy('nama_rekening')
            ->paginate(20)
            ->withQueryString();


        return view(
            'rekening-kas-bank.index',
            compact('rekeningKasBanks')
        );
    }


    /**
     * Form tambah.
     */
    public function create()
    {
        return view(
            'rekening-kas-bank.create'
        );
    }


    /**
     * Simpan.
     */
    public function store(Request $request)
    {
        $validated = $this->validateData(
            $request
        );

        /*
         * Jika jenis KAS, informasi bank
         * harus dikosongkan.
         */
        if ($validated['jenis'] === 'KAS') {
            $validated['nama_bank'] = null;
            $validated['nomor_rekening'] = null;
            $validated['atas_nama'] = null;
        }

        RekeningKasBank::create(
            $validated
        );

        return redirect()
            ->route('rekening-kas-bank.index')
            ->with(
                'success',
                'Kas/Rekening Bank berhasil ditambahkan.'
            );
    }


    /**
     * Form edit.
     */
    public function edit(
        RekeningKasBank $rekeningKasBank
    ) {
        return view(
            'rekening-kas-bank.edit',
            compact('rekeningKasBank')
        );
    }


    /**
     * Update.
     */
    public function update(
        Request $request,
        RekeningKasBank $rekeningKasBank
    ) {
        $validated = $this->validateData(
            $request,
            $rekeningKasBank
        );

        if ($validated['jenis'] === 'KAS') {
            $validated['nama_bank'] = null;
            $validated['nomor_rekening'] = null;
            $validated['atas_nama'] = null;
        }

        $rekeningKasBank->update(
            $validated
        );

        return redirect()
            ->route('rekening-kas-bank.index')
            ->with(
                'success',
                'Kas/Rekening Bank berhasil diperbarui.'
            );
    }


    /**
     * Hapus.
     */
    public function destroy(
        RekeningKasBank $rekeningKasBank
    ) {
        /*
         * Untuk tahap master data saat ini
         * record masih boleh dihapus.
         *
         * Setelah modul transaksi Kas & Bank
         * terhubung, penghapusan akan kita
         * blok jika rekening sudah digunakan.
         */

        $rekeningKasBank->delete();

        return redirect()
            ->route('rekening-kas-bank.index')
            ->with(
                'success',
                'Kas/Rekening Bank berhasil dihapus.'
            );
    }


    /**
     * Validasi create/update.
     */
    private function validateData(
        Request $request,
        ?RekeningKasBank $rekeningKasBank = null
    ): array {
        return $request->validate([
            'kode_rekening' => [
                'required',
                'string',
                'max:30',

                Rule::unique(
                    'rekening_kas_banks',
                    'kode_rekening'
                )->ignore(
                    $rekeningKasBank?->id
                ),
            ],

            'nama_rekening' => [
                'required',
                'string',
                'max:150',
            ],

            'jenis' => [
                'required',
                Rule::in([
                    'KAS',
                    'BANK',
                ]),
            ],

            'nama_bank' => [
                'nullable',
                'required_if:jenis,BANK',
                'string',
                'max:100',
            ],

            'nomor_rekening' => [
                'nullable',
                'required_if:jenis,BANK',
                'string',
                'max:100',
            ],

            'atas_nama' => [
                'nullable',
                'required_if:jenis,BANK',
                'string',
                'max:150',
            ],

            'saldo_awal' => [
                'required',
                'numeric',
                'min:0',
            ],

            'is_active' => [
                'required',
                'boolean',
            ],

            'catatan' => [
                'nullable',
                'string',
            ],
        ]);
    }
}