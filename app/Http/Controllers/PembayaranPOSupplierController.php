<?php

namespace App\Http\Controllers;

use App\Models\PembayaranPOSupplier;
use App\Models\PurchaseOrder;
use App\Models\RekeningKasBank;
use App\Services\KasBankService;
use App\Services\SystemAccountingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class PembayaranPOSupplierController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | CREATE
    |--------------------------------------------------------------------------
    |
    | Membuka form pembayaran berdasarkan Purchase Order.
    |
    | Pembayaran hanya boleh dilakukan setelah PO dikirim / tidak Draft.
    |
    */

    public function create(
        PurchaseOrder $purchaseOrder
    ) {
        $purchaseOrder->load([
            'supplier',
            'items.barang.satuan',
            'pembayaranPOSuppliers',
        ]);

        /*
        |--------------------------------------------------------------------------
        | PO DRAFT BELUM BOLEH DIBAYAR
        |--------------------------------------------------------------------------
        */

        if (
            $purchaseOrder->status === 'Draft'
        ) {
            return redirect()
                ->route(
                    'purchase-order.show',
                    $purchaseOrder
                )
                ->with(
                    'error',
                    'Purchase Order masih Draft. Kirim PO terlebih dahulu sebelum melakukan pembayaran.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | CEK TOTAL PO
        |--------------------------------------------------------------------------
        */

        if (
            (float) $purchaseOrder->total
            <= 0
        ) {
            return redirect()
                ->route(
                    'purchase-order.show',
                    $purchaseOrder
                )
                ->with(
                    'error',
                    'Total Purchase Order tidak valid.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | CEK SISA PEMBAYARAN
        |--------------------------------------------------------------------------
        */

        if (
            (float)
            $purchaseOrder->sisa_pembayaran_po
            <= 0
        ) {
            return redirect()
                ->route(
                    'purchase-order.show',
                    $purchaseOrder
                )
                ->with(
                    'error',
                    'Purchase Order ini sudah lunas.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | KAS / BANK AKTIF
        |--------------------------------------------------------------------------
        */

        $rekeningKasBanks =
            RekeningKasBank::query()
                ->with('akun')
                ->where(
                    'is_active',
                    true
                )
                ->whereNotNull(
                    'akun_id'
                )
                ->orderByRaw("
                    CASE
                        WHEN jenis = 'BANK' THEN 1
                        WHEN jenis = 'KAS' THEN 2
                        ELSE 3
                    END
                ")
                ->orderBy(
                    'nama_rekening'
                )
                ->get();

        if (
            $rekeningKasBanks->isEmpty()
        ) {
            return redirect()
                ->route(
                    'purchase-order.show',
                    $purchaseOrder
                )
                ->with(
                    'error',
                    'Belum ada Kas/Rekening Bank aktif yang terhubung dengan COA.'
                );
        }

        return view(
            'pembayaran-po-supplier.create',
            compact(
                'purchaseOrder',
                'rekeningKasBanks'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    |
    | Menyimpan pembayaran sebagai DRAFT.
    |
    | Pada tahap ini:
    |
    | - Kas/Bank BELUM berkurang
    | - Jurnal BELUM terbentuk
    |
    | Perubahan accounting baru terjadi saat tombol
    | "Catat Pembayaran" ditekan.
    |
    */

    public function store(
        Request $request,
        PurchaseOrder $purchaseOrder
    ) {
        $validated =
            $request->validate([
                'tanggal_pembayaran' => [
                    'required',
                    'date',
                ],

                'rekening_kas_bank_id' => [
                    'required',
                    'integer',
                    'exists:rekening_kas_banks,id',
                ],

                'jumlah_bayar' => [
                    'required',
                    'numeric',
                    'gt:0',
                ],

                'metode_pembayaran' => [
                    'nullable',
                    'string',
                    'max:50',
                ],

                'nomor_referensi' => [
                    'nullable',
                    'string',
                    'max:100',
                ],

                'keterangan' => [
                    'nullable',
                    'string',
                    'max:1000',
                ],
            ]);

        try {
            $pembayaran =
                DB::transaction(
                    function () use (
                        $validated,
                        $purchaseOrder
                    ) {
                        /*
                        |--------------------------------------------------------------------------
                        | LOCK PURCHASE ORDER
                        |--------------------------------------------------------------------------
                        */

                        $purchaseOrder =
                            PurchaseOrder::query()
                                ->with([
                                    'supplier',
                                    'pembayaranPOSuppliers',
                                ])
                                ->lockForUpdate()
                                ->findOrFail(
                                    $purchaseOrder->id
                                );

                        /*
                        |--------------------------------------------------------------------------
                        | VALIDASI STATUS PO
                        |--------------------------------------------------------------------------
                        */

                        if (
                            $purchaseOrder->status
                            === 'Draft'
                        ) {
                            throw ValidationException::withMessages([
                                'purchase_order' =>
                                    'Purchase Order masih Draft. Kirim PO terlebih dahulu.',
                            ]);
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | VALIDASI SUPPLIER
                        |--------------------------------------------------------------------------
                        */

                        if (
                            !$purchaseOrder->supplier_id
                            ||
                            !$purchaseOrder->supplier
                        ) {
                            throw ValidationException::withMessages([
                                'purchase_order' =>
                                    'Supplier pada Purchase Order tidak ditemukan.',
                            ]);
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | VALIDASI TOTAL PO
                        |--------------------------------------------------------------------------
                        */

                        $totalPO =
                            round(
                                (float)
                                $purchaseOrder->total,
                                2
                            );

                        if (
                            $totalPO <= 0
                        ) {
                            throw ValidationException::withMessages([
                                'purchase_order' =>
                                    'Total Purchase Order harus lebih besar dari 0.',
                            ]);
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | VALIDASI TANGGAL
                        |--------------------------------------------------------------------------
                        */

                        $tanggalPembayaran =
                            Carbon::parse(
                                $validated[
                                    'tanggal_pembayaran'
                                ]
                            )->startOfDay();

                        $tanggalPO =
                            Carbon::parse(
                                $purchaseOrder
                                    ->tanggal_po
                            )->startOfDay();

                        if (
                            $tanggalPembayaran->lt(
                                $tanggalPO
                            )
                        ) {
                            throw ValidationException::withMessages([
                                'tanggal_pembayaran' =>
                                    'Tanggal pembayaran tidak boleh sebelum tanggal Purchase Order.',
                            ]);
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | VALIDASI REKENING KAS / BANK
                        |--------------------------------------------------------------------------
                        */

                        $rekening =
                            RekeningKasBank::query()
                                ->with('akun')
                                ->where(
                                    'is_active',
                                    true
                                )
                                ->whereNotNull(
                                    'akun_id'
                                )
                                ->find(
                                    $validated[
                                        'rekening_kas_bank_id'
                                    ]
                                );

                        if (!$rekening) {
                            throw ValidationException::withMessages([
                                'rekening_kas_bank_id' =>
                                    'Kas/Rekening Bank tidak valid, tidak aktif, atau belum terhubung dengan COA.',
                            ]);
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | TOTAL YANG SUDAH TERCATAT
                        |--------------------------------------------------------------------------
                        |
                        | Draft tidak dihitung.
                        |
                        | Yang mengurangi kewajiban pembayaran PO hanya transaksi
                        | yang benar-benar sudah TERCATAT.
                        |
                        */

                        $sudahDibayar =
                            round(
                                (float)
                                PembayaranPOSupplier::query()
                                    ->where(
                                        'purchase_order_id',
                                        $purchaseOrder->id
                                    )
                                    ->where(
                                        'status',
                                        'TERCATAT'
                                    )
                                    ->sum(
                                        'jumlah_bayar'
                                    ),
                                2
                            );

                        /*
                        |--------------------------------------------------------------------------
                        | SISA PEMBAYARAN
                        |--------------------------------------------------------------------------
                        */

                        $sisaPembayaran =
                            round(
                                max(
                                    0,
                                    $totalPO
                                    - $sudahDibayar
                                ),
                                2
                            );

                        if (
                            $sisaPembayaran <= 0
                        ) {
                            throw ValidationException::withMessages([
                                'jumlah_bayar' =>
                                    'Purchase Order ini sudah lunas.',
                            ]);
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | JUMLAH BAYAR
                        |--------------------------------------------------------------------------
                        */

                        $jumlahBayar =
                            round(
                                (float)
                                $validated[
                                    'jumlah_bayar'
                                ],
                                2
                            );

                        if (
                            $jumlahBayar <= 0
                        ) {
                            throw ValidationException::withMessages([
                                'jumlah_bayar' =>
                                    'Jumlah pembayaran harus lebih besar dari 0.',
                            ]);
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | CEGAH OVERPAYMENT
                        |--------------------------------------------------------------------------
                        */

                        if (
                            $jumlahBayar
                            >
                            $sisaPembayaran + 0.01
                        ) {
                            throw ValidationException::withMessages([
                                'jumlah_bayar' =>
                                    'Jumlah pembayaran melebihi sisa pembayaran PO. '
                                    . 'Sisa pembayaran: Rp '
                                    . number_format(
                                        $sisaPembayaran,
                                        0,
                                        ',',
                                        '.'
                                    )
                                    . '.',
                            ]);
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | CREATE DRAFT
                        |--------------------------------------------------------------------------
                        */

                        return PembayaranPOSupplier::create([
                            'nomor_pembayaran' =>
                                $this
                                    ->generateNomorPembayaran(
                                        $validated[
                                            'tanggal_pembayaran'
                                        ]
                                    ),

                            'purchase_order_id' =>
                                $purchaseOrder->id,

                            'supplier_id' =>
                                $purchaseOrder
                                    ->supplier_id,

                            'rekening_kas_bank_id' =>
                                $rekening->id,

                            'tanggal_pembayaran' =>
                                $validated[
                                    'tanggal_pembayaran'
                                ],

                            'jumlah_bayar' =>
                                $jumlahBayar,

                            'metode_pembayaran' =>
                                $validated[
                                    'metode_pembayaran'
                                ] ?? null,

                            'nomor_referensi' =>
                                $validated[
                                    'nomor_referensi'
                                ] ?? null,

                            'status' =>
                                'DRAFT',

                            'keterangan' =>
                                $validated[
                                    'keterangan'
                                ] ?? null,

                            'created_by' =>
                                auth()->id(),
                        ]);
                    },
                    3
                );

            return redirect()
                ->route(
                    'pembayaran-po-supplier.show',
                    $pembayaran
                )
                ->with(
                    'success',
                    'Pembayaran PO berhasil dibuat sebagai Draft. Periksa kembali lalu klik Catat Pembayaran.'
                );

        } catch (ValidationException $e) {
            throw $e;

        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    $e->getMessage()
                );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | SHOW
    |--------------------------------------------------------------------------
    */

    public function show(
        PembayaranPOSupplier $pembayaranPOSupplier
    ) {
        $pembayaranPOSupplier->load([
            'purchaseOrder.items.barang.satuan',
            'purchaseOrder.pembayaranPOSuppliers',
            'supplier',
            'rekeningKasBank.akun',
            'creator',
            'jurnal.details.akun',
            'mutasiKasBank',
        ]);

        return view(
            'pembayaran-po-supplier.show',
            compact(
                'pembayaranPOSupplier'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | CATAT PEMBAYARAN
    |--------------------------------------------------------------------------
    |
    | Saat dicatat:
    |
    | 1. Mutasi Kas/Bank dibuat
    | 2. Saldo Kas/Bank berkurang
    | 3. Jurnal otomatis dibuat
    |
    | Dr Uang Muka Pembelian
    |      Cr Kas / Bank
    |
    | 4. Status pembayaran menjadi TERCATAT
    |
    */

    public function catat(
        PembayaranPOSupplier $pembayaranPOSupplier,
        KasBankService $kasBankService,
        SystemAccountingService $accountingService
    ) {
        try {
            DB::transaction(
                function () use (
                    $pembayaranPOSupplier,
                    $kasBankService,
                    $accountingService
                ) {
                    /*
                    |--------------------------------------------------------------------------
                    | LOCK PEMBAYARAN
                    |--------------------------------------------------------------------------
                    */

                    $pembayaran =
                        PembayaranPOSupplier::query()
                            ->with([
                                'purchaseOrder',
                                'supplier',
                                'rekeningKasBank.akun',
                            ])
                            ->lockForUpdate()
                            ->findOrFail(
                                $pembayaranPOSupplier
                                    ->id
                            );

                    /*
                    |--------------------------------------------------------------------------
                    | STATUS
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $pembayaran->status
                        !== 'DRAFT'
                    ) {
                        throw new RuntimeException(
                            'Hanya Pembayaran PO berstatus Draft yang dapat dicatat.'
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | VALIDASI PURCHASE ORDER
                    |--------------------------------------------------------------------------
                    */

                    if (
                        !$pembayaran
                            ->purchaseOrder
                    ) {
                        throw new RuntimeException(
                            'Purchase Order pembayaran tidak ditemukan.'
                        );
                    }

                    if (
                        $pembayaran
                            ->purchaseOrder
                            ->status
                        === 'Draft'
                    ) {
                        throw new RuntimeException(
                            'Purchase Order masih Draft dan belum boleh dibayar.'
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | VALIDASI SUPPLIER
                    |--------------------------------------------------------------------------
                    */

                    if (
                        !$pembayaran
                            ->supplier
                    ) {
                        throw new RuntimeException(
                            'Supplier pembayaran tidak ditemukan.'
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | VALIDASI REKENING
                    |--------------------------------------------------------------------------
                    */

                    if (
                        !$pembayaran
                            ->rekeningKasBank
                    ) {
                        throw new RuntimeException(
                            'Kas/Rekening Bank pembayaran tidak ditemukan.'
                        );
                    }

                    if (
                        !$pembayaran
                            ->rekeningKasBank
                            ->is_active
                    ) {
                        throw new RuntimeException(
                            'Kas/Rekening Bank pembayaran sudah tidak aktif.'
                        );
                    }

                    if (
                        !$pembayaran
                            ->rekeningKasBank
                            ->akun_id
                    ) {
                        throw new RuntimeException(
                            'Kas/Rekening Bank belum terhubung dengan akun COA.'
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | JUMLAH
                    |--------------------------------------------------------------------------
                    */

                    $jumlah =
                        round(
                            (float)
                            $pembayaran
                                ->jumlah_bayar,
                            2
                        );

                    if (
                        $jumlah <= 0
                    ) {
                        throw new RuntimeException(
                            'Jumlah pembayaran harus lebih besar dari 0.'
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | TOTAL PO
                    |--------------------------------------------------------------------------
                    */

                    $totalPO =
                        round(
                            (float)
                            $pembayaran
                                ->purchaseOrder
                                ->total,
                            2
                        );

                    if (
                        $totalPO <= 0
                    ) {
                        throw new RuntimeException(
                            'Total Purchase Order tidak valid.'
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | LOCK PURCHASE ORDER
                    |--------------------------------------------------------------------------
                    */

                    PurchaseOrder::query()
                        ->where(
                            'id',
                            $pembayaran
                                ->purchase_order_id
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                    /*
                    |--------------------------------------------------------------------------
                    | HITUNG PEMBAYARAN TERCATAT LAIN
                    |--------------------------------------------------------------------------
                    |
                    | Pembayaran yang sedang diproses masih DRAFT,
                    | jadi belum masuk perhitungan ini.
                    |
                    */

                    $sudahDibayar =
                        round(
                            (float)
                            PembayaranPOSupplier::query()
                                ->where(
                                    'purchase_order_id',
                                    $pembayaran
                                        ->purchase_order_id
                                )
                                ->where(
                                    'status',
                                    'TERCATAT'
                                )
                                ->where(
                                    'id',
                                    '!=',
                                    $pembayaran->id
                                )
                                ->sum(
                                    'jumlah_bayar'
                                ),
                            2
                        );

                    /*
                    |--------------------------------------------------------------------------
                    | CEK OVERPAYMENT
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $sudahDibayar
                        + $jumlah
                        >
                        $totalPO + 0.01
                    ) {
                        $sisa =
                            round(
                                max(
                                    0,
                                    $totalPO
                                    - $sudahDibayar
                                ),
                                2
                            );

                        throw new RuntimeException(
                            'Pembayaran tidak dapat dicatat karena akan melebihi nilai PO. '
                            . 'Sisa pembayaran saat ini: Rp '
                            . number_format(
                                $sisa,
                                0,
                                ',',
                                '.'
                            )
                            . '.'
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | PERIOD LOCK
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $accountingService
                            ->isPeriodLocked(
                                $pembayaran
                                    ->tanggal_pembayaran
                            )
                    ) {
                        throw new RuntimeException(
                            'Periode accounting untuk tanggal pembayaran sudah ditutup.'
                        );
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | MUTASI KAS / BANK
                    |--------------------------------------------------------------------------
                    |
                    | Hanya uang keluar.
                    |
                    | Tidak mengubah stok.
                    |
                    */

                    $kasBankService
                        ->uangKeluar(
                            rekeningKasBankId:
                                (int)
                                $pembayaran
                                    ->rekening_kas_bank_id,

                            jumlah:
                                $jumlah,

                            referenceType:
                                'PEMBAYARAN_PO_SUPPLIER',

                            referenceId:
                                (int)
                                $pembayaran->id,

                            nomorReferensi:
                                $pembayaran
                                    ->nomor_pembayaran,

                            keterangan:
                                'Pembayaran PO '
                                . (
                                    $pembayaran
                                        ->purchaseOrder
                                        ?->nomor_po
                                    ?? '-'
                                )
                                . ' - '
                                . (
                                    $pembayaran
                                        ->supplier
                                        ?->nama_supplier
                                    ?? 'Supplier'
                                ),

                            tanggalTransaksi:
                                Carbon::parse(
                                    $pembayaran
                                        ->tanggal_pembayaran
                                )
                                    ->format(
                                        'Y-m-d'
                                    ),

                            tipe:
                                'PEMBAYARAN_PO_SUPPLIER'
                        );

                    /*
                    |--------------------------------------------------------------------------
                    | JURNAL ACCOUNTING
                    |--------------------------------------------------------------------------
                    */

                    $accountingService
                        ->autoJournalPembayaranPOSupplier(
                            $pembayaran
                        );

                    /*
                    |--------------------------------------------------------------------------
                    | UPDATE STATUS
                    |--------------------------------------------------------------------------
                    */

                    $pembayaran->update([
                        'status' =>
                            'TERCATAT',
                    ]);
                },
                3
            );

            return redirect()
                ->route(
                    'pembayaran-po-supplier.show',
                    $pembayaranPOSupplier
                )
                ->with(
                    'success',
                    'Pembayaran PO berhasil dicatat. Saldo Kas/Bank telah berkurang dan jurnal accounting telah dibuat.'
                );

        } catch (\Throwable $e) {
            return redirect()
                ->route(
                    'pembayaran-po-supplier.show',
                    $pembayaranPOSupplier
                )
                ->with(
                    'error',
                    $e->getMessage()
                );
        }
    }


    /*
    |--------------------------------------------------------------------------
    | GENERATE NOMOR PEMBAYARAN
    |--------------------------------------------------------------------------
    |
    | Format:
    |
    | PPO/2026/09/0001
    |
    */

    private function generateNomorPembayaran(
        $tanggal = null
    ): string {
        $date =
            $tanggal
                ? Carbon::parse(
                    $tanggal
                )
                : now();

        $year =
            $date->format(
                'Y'
            );

        $month =
            $date->format(
                'm'
            );

        $prefix =
            "PPO/{$year}/{$month}/";

        /*
        |--------------------------------------------------------------------------
        | NOMOR TERAKHIR
        |--------------------------------------------------------------------------
        */

        $last =
            PembayaranPOSupplier::query()
                ->where(
                    'nomor_pembayaran',
                    'like',
                    $prefix . '%'
                )
                ->orderByDesc(
                    'nomor_pembayaran'
                )
                ->lockForUpdate()
                ->first();

        $next =
            1;

        if ($last) {
            $lastNumber =
                (int)
                substr(
                    $last
                        ->nomor_pembayaran,
                    -4
                );

            $next =
                $lastNumber + 1;
        }

        return
            $prefix
            . str_pad(
                $next,
                4,
                '0',
                STR_PAD_LEFT
            );
    }
}