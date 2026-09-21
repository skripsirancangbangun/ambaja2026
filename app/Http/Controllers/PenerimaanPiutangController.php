<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\InvoicePenjualan;
use App\Models\PenerimaanPiutang;
use App\Models\PenerimaanPiutangDetail;
use App\Models\PembayaranPenjualan;
use App\Models\PembayaranPenjualanDetail;
use App\Models\RekeningKasBank;
use App\Services\KasBankService;
use App\Services\SystemAccountingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PenerimaanPiutangController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $query =
            PenerimaanPiutang::with([
                'customer',
                'rekeningKasBank',
                'creator',
                'details.invoicePenjualan',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {
            $search =
                trim(
                    $request->search
                );

            $query->where(
                function ($q) use ($search) {
                    $q->where(
                        'nomor_penerimaan',
                        'like',
                        "%{$search}%"
                    )
                        ->orWhereHas(
                            'customer',
                            function ($cq) use ($search) {
                                $cq->where(
                                    'nama_customer',
                                    'like',
                                    "%{$search}%"
                                );
                            }
                        );
                }
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Filter Customer
        |--------------------------------------------------------------------------
        */

        if ($request->filled('customer_id')) {
            $query->where(
                'customer_id',
                $request->customer_id
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Filter Tanggal
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
        | Data
        |--------------------------------------------------------------------------
        */

        $penerimaans =
            $query
                ->latest('tanggal')
                ->latest('id')
                ->paginate(15)
                ->withQueryString();

        /*
        |--------------------------------------------------------------------------
        | Customer Filter
        |--------------------------------------------------------------------------
        */

        $customers =
            Customer::query()
                ->orderBy(
                    'nama_customer'
                )
                ->get();

        return view(
            'piutang.penerimaan-index',
            compact(
                'penerimaans',
                'customers'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE
    |--------------------------------------------------------------------------
    */

    public function create(
        Request $request,
        SystemAccountingService $systemAccountingService
    ) {
        /*
        |--------------------------------------------------------------------------
        | Customer
        |--------------------------------------------------------------------------
        |
        | Hanya customer aktif yang masih memiliki invoice:
        |
        | status_invoice     = AKTIF
        | status_pembayaran != Lunas
        | sisa_piutang       > 0
        |
        */

        $customers =
            Customer::query()
                ->where(
                    'is_active',
                    true
                )
                ->whereHas(
                    'invoicePenjualans',
                    function ($q) {
                        $q->where(
                            'status_invoice',
                            'AKTIF'
                        )
                            ->where(
                                'status_pembayaran',
                                '!=',
                                'Lunas'
                            )
                            ->where(
                                'sisa_piutang',
                                '>',
                                0
                            );
                    }
                )
                ->orderBy(
                    'nama_customer'
                )
                ->get();

        /*
        |--------------------------------------------------------------------------
        | Kas / Rekening Bank
        |--------------------------------------------------------------------------
        */

        $rekeningKasBanks =
            RekeningKasBank::query()
                ->where(
                    'is_active',
                    true
                )
                ->orderBy(
                    'nama_rekening'
                )
                ->get();

        /*
        |--------------------------------------------------------------------------
        | Selected Customer
        |--------------------------------------------------------------------------
        */

        $selectedCustomerId =
            $request->input(
                'customer_id'
            );

        /*
        |--------------------------------------------------------------------------
        | Invoice Belum Lunas
        |--------------------------------------------------------------------------
        */

        $unpaidInvoices =
            collect();

        if ($selectedCustomerId) {
            $unpaidInvoices =
                InvoicePenjualan::query()
                    ->where(
                        'customer_id',
                        $selectedCustomerId
                    )
                    ->where(
                        'status_invoice',
                        'AKTIF'
                    )
                    ->where(
                        'status_pembayaran',
                        '!=',
                        'Lunas'
                    )
                    ->where(
                        'sisa_piutang',
                        '>',
                        0
                    )
                    ->orderByRaw(
                        'CASE
                            WHEN tanggal_jatuh_tempo IS NULL
                            THEN 1
                            ELSE 0
                        END'
                    )
                    ->orderBy(
                        'tanggal_jatuh_tempo'
                    )
                    ->orderBy(
                        'tanggal_invoice'
                    )
                    ->get();
        }

        /*
        |--------------------------------------------------------------------------
        | Nomor Penerimaan
        |--------------------------------------------------------------------------
        */

        $nomorPenerimaan =
            $systemAccountingService
                ->generateDocumentNumber(
                    'PENERIMAAN_PIUTANG'
                );

        return view(
            'piutang.penerimaan-create',
            compact(
                'customers',
                'rekeningKasBanks',
                'selectedCustomerId',
                'unpaidInvoices',
                'nomorPenerimaan'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    */

    public function store(
        Request $request,
        KasBankService $kasBankService,
        SystemAccountingService $systemAccountingService
    ) {
        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        $validated =
            $request->validate([
                'nomor_penerimaan' =>
                    'required|string|max:100|unique:penerimaan_piutangs,nomor_penerimaan',

                'customer_id' =>
                    'required|exists:customers,id',

                'rekening_kas_bank_id' =>
                    'required|exists:rekening_kas_banks,id',

                'tanggal' =>
                    'required|date',

                'catatan' =>
                    'nullable|string|max:1000',

                'alokasi' =>
                    'required|array|min:1',

                /*
                | Satu invoice hanya boleh satu kali
                | dalam satu penerimaan.
                */

                'alokasi.*.invoice_id' =>
                    'required|distinct|exists:invoice_penjualans,id',

                'alokasi.*.bayar' =>
                    'required|numeric|min:0',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Cut-Off
        |--------------------------------------------------------------------------
        */

        if (
            $systemAccountingService
                ->isPeriodLocked(
                    $validated['tanggal']
                )
        ) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Penerimaan piutang gagal disimpan karena periode tanggal tersebut sudah DITUTUP (Cut-Off).'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Customer Aktif
        |--------------------------------------------------------------------------
        */

        $customer =
            Customer::query()
                ->where(
                    'id',
                    $validated[
                        'customer_id'
                    ]
                )
                ->where(
                    'is_active',
                    true
                )
                ->first();

        if (!$customer) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Customer tidak aktif atau tidak ditemukan.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Rekening Aktif
        |--------------------------------------------------------------------------
        */

        $rekening =
            RekeningKasBank::query()
                ->where(
                    'id',
                    $validated[
                        'rekening_kas_bank_id'
                    ]
                )
                ->where(
                    'is_active',
                    true
                )
                ->first();

        if (!$rekening) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Kas/Rekening Bank tidak aktif atau tidak ditemukan.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Ambil Alokasi Dengan Nominal > 0
        |--------------------------------------------------------------------------
        */

        $alokasiAktif =
            array_values(
                array_filter(
                    $validated['alokasi'],
                    function ($item) {
                        return (
                            (float) (
                                $item['bayar']
                                ?? 0
                            )
                            > 0
                        );
                    }
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Minimal Satu Pembayaran
        |--------------------------------------------------------------------------
        */

        if (
            empty(
                $alokasiAktif
            )
        ) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Minimal harus ada satu invoice dengan nominal pembayaran lebih dari 0.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Transaction
        |--------------------------------------------------------------------------
        */

        DB::beginTransaction();

        try {
            /*
            |--------------------------------------------------------------------------
            | Hitung Total Pembayaran
            |--------------------------------------------------------------------------
            */

            $totalDibayar = 0;

            foreach (
                $alokasiAktif
                as $alokasi
            ) {
                $nominal =
                    (float) $alokasi[
                        'bayar'
                    ];

                if ($nominal <= 0) {
                    continue;
                }

                $totalDibayar +=
                    $nominal;
            }

            if ($totalDibayar <= 0) {
                throw new \RuntimeException(
                    'Total pembayaran harus lebih besar dari 0.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Nomor Penerimaan
            |--------------------------------------------------------------------------
            */

            $nomorPenerimaan =
                $validated[
                    'nomor_penerimaan'
                ];

            /*
            | Double safety jika dua user membuka form
            | dengan nomor yang sama.
            */

            if (
                PenerimaanPiutang::query()
                    ->where(
                        'nomor_penerimaan',
                        $nomorPenerimaan
                    )
                    ->exists()
            ) {
                $nomorPenerimaan =
                    $systemAccountingService
                        ->generateDocumentNumber(
                            'PENERIMAAN_PIUTANG',
                            $validated['tanggal']
                        );

                /*
                | Safety tambahan jika masih bentrok.
                */

                if (
                    PenerimaanPiutang::query()
                        ->where(
                            'nomor_penerimaan',
                            $nomorPenerimaan
                        )
                        ->exists()
                ) {
                    $nomorPenerimaan =
                        'PAY-'
                        . now()->format(
                            'YmdHis'
                        )
                        . '-'
                        . random_int(
                            100,
                            999
                        );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Buat Header Penerimaan Piutang
            |--------------------------------------------------------------------------
            */

            $penerimaan =
                PenerimaanPiutang::create([
                    'nomor_penerimaan' =>
                        $nomorPenerimaan,

                    'customer_id' =>
                        $validated[
                            'customer_id'
                        ],

                    'rekening_kas_bank_id' =>
                        $validated[
                            'rekening_kas_bank_id'
                        ],

                    'tanggal' =>
                        $validated[
                            'tanggal'
                        ],

                    'total_dibayar' =>
                        $totalDibayar,

                    'catatan' =>
                        $validated[
                            'catatan'
                        ]
                        ?? null,

                    'created_by_user_id' =>
                        auth()->id(),
                ]);

            /*
            |--------------------------------------------------------------------------
            | Proses Alokasi Invoice
            |--------------------------------------------------------------------------
            */

            foreach (
                $alokasiAktif
                as $alokasi
            ) {
                /*
                |--------------------------------------------------------------------------
                | Lock Invoice
                |--------------------------------------------------------------------------
                */

                $invoice =
                    InvoicePenjualan::query()
                        ->whereKey(
                            $alokasi[
                                'invoice_id'
                            ]
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                $nominalBayar =
                    (float) $alokasi[
                        'bayar'
                    ];

                /*
                |--------------------------------------------------------------------------
                | 1. Invoice Harus Milik Customer
                |--------------------------------------------------------------------------
                */

                if (
                    (int) $invoice
                        ->customer_id
                    !==
                    (int) $validated[
                        'customer_id'
                    ]
                ) {
                    throw new \RuntimeException(
                        'Invoice '
                        . $invoice->nomor_invoice
                        . ' bukan milik customer '
                        . $customer->nama_customer
                        . '.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | 2. Invoice Harus Aktif
                |--------------------------------------------------------------------------
                */

                if (
                    $invoice->status_invoice
                    !== 'AKTIF'
                ) {
                    throw new \RuntimeException(
                        'Invoice '
                        . $invoice->nomor_invoice
                        . ' sudah dibatalkan atau tidak aktif sehingga tidak dapat menerima pembayaran.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | 3. Invoice Tidak Boleh Sudah Lunas
                |--------------------------------------------------------------------------
                */

                if (
                    $invoice
                        ->status_pembayaran
                    === 'Lunas'
                    ||
                    (float) $invoice
                        ->sisa_piutang
                    <= 0
                ) {
                    throw new \RuntimeException(
                        'Invoice '
                        . $invoice->nomor_invoice
                        . ' sudah lunas.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | 4. Tanggal Pembayaran Tidak Boleh Sebelum Invoice
                |--------------------------------------------------------------------------
                */

                if (
                    Carbon::parse(
                        $validated['tanggal']
                    )->lt(
                        Carbon::parse(
                            $invoice
                                ->tanggal_invoice
                        )
                    )
                ) {
                    throw new \RuntimeException(
                        'Tanggal penerimaan untuk invoice '
                        . $invoice->nomor_invoice
                        . ' tidak boleh sebelum tanggal invoice.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | 5. Nominal Harus Positif
                |--------------------------------------------------------------------------
                */

                if ($nominalBayar <= 0) {
                    throw new \RuntimeException(
                        'Nominal pembayaran invoice '
                        . $invoice->nomor_invoice
                        . ' harus lebih besar dari 0.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | 6. Cegah Overpayment
                |--------------------------------------------------------------------------
                */

                $sisaPiutangLama =
                    (float) $invoice
                        ->sisa_piutang;

                if (
                    $nominalBayar
                    >
                    (
                        $sisaPiutangLama
                        + 0.01
                    )
                ) {
                    throw new \RuntimeException(
                        'Pembayaran invoice '
                        . $invoice->nomor_invoice
                        . ' sebesar Rp '
                        . number_format(
                            $nominalBayar,
                            0,
                            ',',
                            '.'
                        )
                        . ' melebihi sisa piutang Rp '
                        . number_format(
                            $sisaPiutangLama,
                            0,
                            ',',
                            '.'
                        )
                        . '.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Simpan Detail Penerimaan
                |--------------------------------------------------------------------------
                */

                PenerimaanPiutangDetail::create([
                    'penerimaan_piutang_id' =>
                        $penerimaan->id,

                    'invoice_penjualan_id' =>
                        $invoice->id,

                    'nominal_dibayar' =>
                        $nominalBayar,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Hitung Pembayaran Baru
                |--------------------------------------------------------------------------
                */

                $sudahDibayarBaru =
                    (float) $invoice
                        ->sudah_dibayar
                    + $nominalBayar;

                /*
                |--------------------------------------------------------------------------
                | Hitung Sisa Piutang
                |--------------------------------------------------------------------------
                */

                $sisaPiutangBaru =
                    max(
                        0,
                        (float) $invoice->total
                        - $sudahDibayarBaru
                    );

                /*
                |--------------------------------------------------------------------------
                | Status Pembayaran
                |--------------------------------------------------------------------------
                */

                if (
                    $sisaPiutangBaru
                    <= 0.01
                ) {
                    $statusPembayaran =
                        'Lunas';

                    $sisaPiutangBaru =
                        0;

                    /*
                    | Supaya angka persis.
                    */

                    $sudahDibayarBaru =
                        (float) $invoice
                            ->total;

                } elseif (
                    $sudahDibayarBaru
                    > 0
                ) {
                    $statusPembayaran =
                        'Sebagian';

                } else {
                    $statusPembayaran =
                        'Belum Lunas';
                }

                /*
                |--------------------------------------------------------------------------
                | Update Invoice
                |--------------------------------------------------------------------------
                */

                $invoice->update([
                    'sudah_dibayar' =>
                        $sudahDibayarBaru,

                    'sisa_piutang' =>
                        $sisaPiutangBaru,

                    'status_pembayaran' =>
                        $statusPembayaran,
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Kas / Bank Masuk
            |--------------------------------------------------------------------------
            */

            $kasBankService->uangMasuk(
                $validated[
                    'rekening_kas_bank_id'
                ],

                $totalDibayar,

                'PENERIMAAN_PIUTANG',

                $penerimaan->id,

                $penerimaan
                    ->nomor_penerimaan,

                'Penerimaan Piutang '
                    . $penerimaan
                        ->nomor_penerimaan
                    . ' - '
                    . $customer
                        ->nama_customer,

                $validated[
                    'tanggal'
                ]
            );

            /*
            |--------------------------------------------------------------------------
            | Jurnal Otomatis
            |--------------------------------------------------------------------------
            |
            | Dr Kas / Bank
            |     Cr Piutang Dagang
            |
            | SystemAccountingService juga membuat
            | Kartu Piutang.
            |
            */

            $systemAccountingService
                ->autoJournalPenerimaanPiutang(
                    $penerimaan
                );

            /*
            |--------------------------------------------------------------------------
            | Sinkron Ke Pembayaran Penjualan
            |--------------------------------------------------------------------------
            |
            | Jika penerimaan dibuat dari menu Piutang,
            | transaksi tetap muncul di menu Pembayaran Penjualan.
            |
            | Hanya sinkron data tampilan.
            | Kas/Bank dan jurnal TIDAK dibuat ulang.
            |
            */

            $penerimaan->load(
                'details'
            );

            $pembayaranSync =
                PembayaranPenjualan::firstOrCreate(
                    [
                        'nomor_pembayaran' =>
                            $penerimaan
                                ->nomor_penerimaan,
                    ],
                    [
                        'customer_id' =>
                            $penerimaan
                                ->customer_id,

                        'rekening_kas_bank_id' =>
                            $penerimaan
                                ->rekening_kas_bank_id,

                        'tanggal_pembayaran' =>
                            $penerimaan
                                ->tanggal,

                        'metode_pembayaran' =>
                            'Transfer',

                        'total_bayar' =>
                            $penerimaan
                                ->total_dibayar,

                        'catatan' =>
                            $penerimaan
                                ->catatan,

                        'created_by' =>
                            $penerimaan
                                ->created_by_user_id,
                    ]
                );

            /*
            |--------------------------------------------------------------------------
            | Sinkron Detail Pembayaran
            |--------------------------------------------------------------------------
            */

            foreach (
                $penerimaan->details
                as $detail
            ) {
                PembayaranPenjualanDetail::firstOrCreate(
                    [
                        'pembayaran_penjualan_id' =>
                            $pembayaranSync->id,

                        'invoice_penjualan_id' =>
                            $detail
                                ->invoice_penjualan_id,
                    ],
                    [
                        'nominal_bayar' =>
                            $detail
                                ->nominal_dibayar,
                    ]
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Commit
            |--------------------------------------------------------------------------
            */

            DB::commit();

            return redirect()
                ->route(
                    'piutang.penerimaan.index'
                )
                ->with(
                    'success',
                    'Penerimaan piutang '
                    . $penerimaan->nomor_penerimaan
                    . ' berhasil disimpan. Invoice, Kas/Bank, jurnal, Kartu Piutang, dan Pembayaran Penjualan telah diperbarui.'
                );

        } catch (\Throwable $e) {
            /*
            |--------------------------------------------------------------------------
            | Rollback
            |--------------------------------------------------------------------------
            */

            DB::rollBack();

            report($e);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Gagal menyimpan penerimaan piutang: '
                    . $e->getMessage()
                );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | KWITANSI PDF
    |--------------------------------------------------------------------------
    */

    public function kwitansiPdf(
        PenerimaanPiutang $penerimaanPiutang
    ) {
        /*
        |--------------------------------------------------------------------------
        | Load Relation
        |--------------------------------------------------------------------------
        */

        $penerimaanPiutang->load([
            'customer',
            'rekeningKasBank',
            'creator',
            'details.invoicePenjualan',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Safe Filename
        |--------------------------------------------------------------------------
        */

        $safeNumber =
            str_replace(
                [
                    '/',
                    '\\',
                ],
                '-',
                $penerimaanPiutang
                    ->nomor_penerimaan
            );

        /*
        |--------------------------------------------------------------------------
        | PDF
        |--------------------------------------------------------------------------
        */

        if (
            class_exists(
                Pdf::class
            )
        ) {
            $pdf =
                Pdf::loadView(
                    'piutang.kwitansi-pdf',
                    compact(
                        'penerimaanPiutang'
                    )
                );

            return $pdf->stream(
                'Kwitansi-'
                . $safeNumber
                . '.pdf'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Fallback View
        |--------------------------------------------------------------------------
        */

        return view(
            'piutang.kwitansi-pdf',
            compact(
                'penerimaanPiutang'
            )
        );
    }
}