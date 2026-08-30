<?php

namespace App\Http\Controllers;

use App\Models\Gudang;
use App\Models\MutasiGudang;
use App\Models\StokGudang;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MutasiGudangController extends Controller
{
    /**
     * Daftar mutasi gudang.
     */
    public function index()
    {
        $mutasiGudangs = MutasiGudang::with([
            'gudangAsal',
            'gudangTujuan',
            'creator',
            'items',
        ])
            ->latest('tanggal_mutasi')
            ->latest('id')
            ->paginate(15);

        return view(
            'mutasi-gudang.index',
            compact('mutasiGudangs')
        );
    }


    /**
     * Form mutasi gudang.
     */
    public function create(Request $request)
    {
        $gudangs = Gudang::where(
            'is_active',
            true
        )
            ->orderBy('nama_gudang')
            ->get();

        $selectedGudangId =
            $request->filled('gudang_asal_id')
                ? (int) $request->gudang_asal_id
                : null;

        /*
        |--------------------------------------------------------------------------
        | STOK GUDANG ASAL
        |--------------------------------------------------------------------------
        */

        $stokBarangs = collect();

        if ($selectedGudangId) {
            $stokBarangs = StokGudang::with([
                'barang.satuan',
            ])
                ->where(
                    'gudang_id',
                    $selectedGudangId
                )
                ->where(
                    'qty',
                    '>',
                    0
                )
                ->orderBy('barang_id')
                ->get();
        }

        $previewNomorMutasi =
            $this->generateNomorMutasi();

        return view(
            'mutasi-gudang.create',
            compact(
                'gudangs',
                'stokBarangs',
                'selectedGudangId',
                'previewNomorMutasi'
            )
        );
    }


    /**
     * Simpan mutasi gudang.
     */
    public function store(
        Request $request,
        InventoryService $inventoryService
    ) {
        $validated = $request->validate([
            'tanggal_mutasi' =>
                'required|date',

            'gudang_asal_id' =>
                'required|exists:gudangs,id',

            'gudang_tujuan_id' =>
                'required|exists:gudangs,id|different:gudang_asal_id',

            'catatan' =>
                'nullable|string',

            'items' =>
                'required|array|min:1',

            'items.*.barang_id' =>
                'required|exists:barangs,id',

            'items.*.qty' =>
                'nullable|numeric|min:0',

        ], [
            'gudang_tujuan_id.different' =>
                'Gudang tujuan harus berbeda dengan gudang asal.',
        ]);


        DB::transaction(function () use (
            $validated,
            $inventoryService
        ) {
            /*
            |--------------------------------------------------------------------------
            | VALIDASI & LOCK STOK
            |--------------------------------------------------------------------------
            */

            $itemsToTransfer = [];

            $barangIds = collect(
                $validated['items']
            )
                ->pluck('barang_id')
                ->filter();

            /*
             * Cegah satu barang dikirim dua kali
             * dalam dokumen yang sama.
             */
            if (
                $barangIds->count()
                !==
                $barangIds->unique()->count()
            ) {
                throw ValidationException::withMessages([
                    'items' =>
                        'Barang yang sama tidak boleh dimasukkan lebih dari satu kali.',
                ]);
            }


            foreach (
                $validated['items']
                as $index => $inputItem
            ) {
                $qty =
                    round(
                        (float) (
                            $inputItem['qty']
                            ?? 0
                        ),
                        2
                    );

                /*
                 * Qty 0 berarti barang tidak ikut dimutasi.
                 */
                if ($qty <= 0) {
                    continue;
                }


                /*
                |--------------------------------------------------------------------------
                | AMBIL & LOCK STOK GUDANG ASAL
                |--------------------------------------------------------------------------
                */

                $stok = StokGudang::where(
                    'barang_id',
                    $inputItem['barang_id']
                )
                    ->where(
                        'gudang_id',
                        $validated['gudang_asal_id']
                    )
                    ->lockForUpdate()
                    ->first();

                if (!$stok) {
                    throw ValidationException::withMessages([
                        "items.$index.qty" =>
                            'Barang tidak memiliki stok di gudang asal.',
                    ]);
                }

                $stokTersedia =
                    (float) $stok->qty;

                if ($stokTersedia <= 0) {
                    throw ValidationException::withMessages([
                        "items.$index.qty" =>
                            'Stok barang di gudang asal sudah habis.',
                    ]);
                }

                if ($qty > $stokTersedia) {
                    throw ValidationException::withMessages([
                        "items.$index.qty" =>
                            'Qty mutasi melebihi stok tersedia. Stok tersedia: '
                            . number_format(
                                $stokTersedia,
                                2,
                                ',',
                                '.'
                            ),
                    ]);
                }


                /*
                |--------------------------------------------------------------------------
                | HARGA MOVING AVERAGE GUDANG ASAL
                |--------------------------------------------------------------------------
                |
                | Harga ini diambil SEBELUM stok dikeluarkan.
                | Nilai yang sama akan dipindahkan ke gudang tujuan.
                |
                */

                $hargaRataRata =
                    (float) $stok->harga_rata_rata;

                $itemsToTransfer[] = [
                    'barang_id' =>
                        (int) $inputItem['barang_id'],

                    'qty' =>
                        $qty,

                    'harga_rata_rata' =>
                        $hargaRataRata,

                    'nilai' =>
                        round(
                            $qty * $hargaRataRata,
                            2
                        ),
                ];
            }


            /*
            |--------------------------------------------------------------------------
            | MINIMAL SATU BARANG
            |--------------------------------------------------------------------------
            */

            if (count($itemsToTransfer) === 0) {
                throw ValidationException::withMessages([
                    'items' =>
                        'Masukkan qty mutasi minimal untuk satu barang.',
                ]);
            }


            /*
            |--------------------------------------------------------------------------
            | BUAT HEADER MUTASI
            |--------------------------------------------------------------------------
            */

            $mutasiGudang =
                MutasiGudang::create([
                    'nomor_mutasi' =>
                        $this->generateNomorMutasi(),

                    'tanggal_mutasi' =>
                        $validated['tanggal_mutasi'],

                    'gudang_asal_id' =>
                        $validated['gudang_asal_id'],

                    'gudang_tujuan_id' =>
                        $validated['gudang_tujuan_id'],

                    'catatan' =>
                        $validated['catatan']
                        ?? null,

                    'created_by' =>
                        auth()->id(),
                ]);


            /*
            |--------------------------------------------------------------------------
            | PROSES SETIAP BARANG
            |--------------------------------------------------------------------------
            */

            foreach ($itemsToTransfer as $item) {
                /*
                |--------------------------------------------------------------------------
                | SIMPAN DETAIL MUTASI
                |--------------------------------------------------------------------------
                */

                $mutasiItem =
                    $mutasiGudang
                        ->items()
                        ->create([
                            'barang_id' =>
                                $item['barang_id'],

                            'qty' =>
                                $item['qty'],

                            'harga_rata_rata' =>
                                $item['harga_rata_rata'],

                            'nilai' =>
                                $item['nilai'],
                        ]);


                /*
                |--------------------------------------------------------------------------
                | STOK KELUAR DARI GUDANG ASAL
                |--------------------------------------------------------------------------
                |
                | MutasiStok menggunakan tanggal dokumen
                | Mutasi Gudang.
                |
                */

                $inventoryService->stokKeluar(
                    barangId:
                        $item['barang_id'],

                    gudangId:
                        (int) $validated[
                            'gudang_asal_id'
                        ],

                    qty:
                        $item['qty'],

                    referenceType:
                        'MUTASI_GUDANG',

                    referenceId:
                        (int) $mutasiItem->id,

                    keterangan:
                        'Mutasi keluar '
                        . $mutasiGudang->nomor_mutasi,

                    tipeMutasi:
                        'MUTASI_KELUAR',

                    tanggalTransaksi:
                        $validated['tanggal_mutasi']
                );


                /*
                |--------------------------------------------------------------------------
                | STOK MASUK KE GUDANG TUJUAN
                |--------------------------------------------------------------------------
                |
                | Harga masuk menggunakan moving average
                | gudang asal sebelum stok dikeluarkan.
                |
                | Tanggal mutasi masuk HARUS sama dengan
                | tanggal mutasi keluar.
                |
                */

                $inventoryService->stokMasuk(
                    barangId:
                        $item['barang_id'],

                    gudangId:
                        (int) $validated[
                            'gudang_tujuan_id'
                        ],

                    qty:
                        $item['qty'],

                    hargaSatuan:
                        $item['harga_rata_rata'],

                    referenceType:
                        'MUTASI_GUDANG',

                    referenceId:
                        (int) $mutasiItem->id,

                    keterangan:
                        'Mutasi masuk '
                        . $mutasiGudang->nomor_mutasi,

                    tipeMutasi:
                        'MUTASI_MASUK',

                    tanggalTransaksi:
                        $validated['tanggal_mutasi']
                );
            }
        });


        return redirect()
            ->route(
                'mutasi-gudang.index'
            )
            ->with(
                'success',
                'Mutasi gudang berhasil disimpan.'
            );
    }


    /**
     * Detail mutasi gudang.
     */
    public function show(
        MutasiGudang $mutasiGudang
    ) {
        $mutasiGudang->load([
            'gudangAsal',
            'gudangTujuan',
            'creator',
            'items.barang.satuan',
        ]);

        return view(
            'mutasi-gudang.show',
            compact('mutasiGudang')
        );
    }


    /**
     * Transaksi stok yang sudah disimpan
     * tidak diedit langsung.
     */
    public function edit(
        MutasiGudang $mutasiGudang
    ) {
        return redirect()
            ->route(
                'mutasi-gudang.show',
                $mutasiGudang
            )
            ->with(
                'error',
                'Mutasi gudang yang sudah disimpan tidak dapat diedit.'
            );
    }


    public function update(
        Request $request,
        MutasiGudang $mutasiGudang
    ) {
        return redirect()
            ->route(
                'mutasi-gudang.show',
                $mutasiGudang
            )
            ->with(
                'error',
                'Mutasi gudang yang sudah disimpan tidak dapat diedit.'
            );
    }


    /**
     * Tidak dihapus langsung karena sudah
     * menghasilkan mutasi stok.
     */
    public function destroy(
        MutasiGudang $mutasiGudang
    ) {
        return redirect()
            ->route(
                'mutasi-gudang.show',
                $mutasiGudang
            )
            ->with(
                'error',
                'Mutasi gudang tidak dapat dihapus langsung.'
            );
    }


    /**
     * Generate nomor mutasi otomatis.
     *
     * Contoh:
     * MG/2026/08/0001
     */
    private function generateNomorMutasi(): string
    {
        $year =
            now()->format('Y');

        $month =
            now()->format('m');

        $prefix =
            "MG/{$year}/{$month}/";

        $last =
            MutasiGudang::where(
                'nomor_mutasi',
                'like',
                $prefix . '%'
            )
                ->orderByDesc('id')
                ->first();

        if ($last) {
            $lastNumber =
                (int) substr(
                    $last->nomor_mutasi,
                    -4
                );

            $nextNumber =
                $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        return
            $prefix
            . str_pad(
                $nextNumber,
                4,
                '0',
                STR_PAD_LEFT
            );
    }
}