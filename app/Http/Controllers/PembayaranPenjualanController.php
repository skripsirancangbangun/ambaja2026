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
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PembayaranPenjualanController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $query = PembayaranPenjualan::with([
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
                        'nomor_pembayaran',
                        'like',
                        "%{$search}%"
                    )
                        ->orWhere(
                            'referensi_transaksi',
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
        | Filter Tanggal
        |--------------------------------------------------------------------------
        */

        if ($request->filled('tanggal_mulai')) {
            $query->whereDate(
                'tanggal_pembayaran',
                '>=',
                $request->tanggal_mulai
            );
        }

        if ($request->filled('tanggal_selesai')) {
            $query->whereDate(
                'tanggal_pembayaran',
                '<=',
                $request->tanggal_selesai
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Data
        |--------------------------------------------------------------------------
        */

        $pembayarans = $query
            ->latest('tanggal_pembayaran')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view(
            'pembayaran-penjualan.index',
            compact(
                'pembayarans'
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
        | Hanya customer aktif yang masih mempunyai:
        |
        | - invoice AKTIF
        | - belum lunas
        | - masih mempunyai sisa piutang
        |
        */

        $customers = Customer::query()
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
        | Rekening Kas / Bank
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

        $unpaidInvoices =
            collect();

        /*
        |--------------------------------------------------------------------------
        | Invoice Belum Lunas
        |--------------------------------------------------------------------------
        */

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
                            WHEN tanggal_jatuh_tempo IS NULL THEN 1
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
        | Nomor Pembayaran
        |--------------------------------------------------------------------------
        */

        $nomorPembayaran =
            $systemAccountingService
                ->generateDocumentNumber(
                    'PAY'
                );

        return view(
            'pembayaran-penjualan.create',
            compact(
                'customers',
                'rekeningKasBanks',
                'selectedCustomerId',
                'unpaidInvoices',
                'nomorPembayaran'
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
                'nomor_pembayaran' =>
                    'required|string|max:100|unique:pembayaran_penjualans,nomor_pembayaran',

                'customer_id' =>
                    'required|exists:customers,id',

                'rekening_kas_bank_id' =>
                    'required|exists:rekening_kas_banks,id',

                'tanggal_pembayaran' =>
                    'required|date',

                'metode_pembayaran' =>
                    'required|in:Transfer,Tunai,Giro,Cek',

                'referensi_transaksi' =>
                    'nullable|string|max:255',

                'catatan' =>
                    'nullable|string|max:1000',

                'invoices' =>
                    'required|array|min:1',

                /*
                | Invoice yang sama tidak boleh muncul dua kali.
                */

                'invoices.*.id' =>
                    'required|distinct|exists:invoice_penjualans,id',

                'invoices.*.nominal_bayar' =>
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
                    $validated[
                        'tanggal_pembayaran'
                    ]
                )
        ) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Pembayaran gagal disimpan karena periode tanggal tersebut sudah DITUTUP (Cut-Off).'
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
                    'Customer yang dipilih tidak aktif atau tidak ditemukan.'
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
                    'Kas/Rekening Bank yang dipilih tidak aktif atau tidak ditemukan.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Ambil Invoice Yang Benar-Benar Dibayar
        |--------------------------------------------------------------------------
        */

        $allocatedInvoices =
            array_values(
                array_filter(
                    $validated['invoices'],
                    function ($item) {
                        return (
                            (float) (
                                $item[
                                    'nominal_bayar'
                                ]
                                ?? 0
                            )
                            > 0
                        );
                    }
                )
            );

        /*
        |--------------------------------------------------------------------------
        | Minimal 1 Invoice
        |--------------------------------------------------------------------------
        */

        if (
            empty(
                $allocatedInvoices
            )
        ) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Minimal harus ada 1 invoice dengan nominal pembayaran lebih dari 0.'
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
            | Hitung Total
            |--------------------------------------------------------------------------
            */

            $totalBayar = 0;

            foreach (
                $allocatedInvoices
                as $item
            ) {
                $nominal =
                    (float) $item[
                        'nominal_bayar'
                    ];

                if ($nominal <= 0) {
                    continue;
                }

                $totalBayar +=
                    $nominal;
            }

            if ($totalBayar <= 0) {
                throw new \RuntimeException(
                    'Total pembayaran harus lebih besar dari 0.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Nomor Pembayaran
            |--------------------------------------------------------------------------
            |
            | Double safety apabila nomor sudah dipakai karena dua user
            | membuka form secara bersamaan.
            |
            */

            $nomorPembayaran =
                $validated[
                    'nomor_pembayaran'
                ];

            if (
                PembayaranPenjualan::query()
                    ->where(
                        'nomor_pembayaran',
                        $nomorPembayaran
                    )
                    ->exists()
            ) {
                $nomorPembayaran =
                    $systemAccountingService
                        ->generateDocumentNumber(
                            'PAY',
                            $validated[
                                'tanggal_pembayaran'
                            ]
                        );
            }

            /*
            |--------------------------------------------------------------------------
            | Buat Pembayaran Penjualan
            |--------------------------------------------------------------------------
            */

            $pembayaran =
                PembayaranPenjualan::create([
                    'nomor_pembayaran' =>
                        $nomorPembayaran,

                    'customer_id' =>
                        $validated[
                            'customer_id'
                        ],

                    'rekening_kas_bank_id' =>
                        $validated[
                            'rekening_kas_bank_id'
                        ],

                    'tanggal_pembayaran' =>
                        $validated[
                            'tanggal_pembayaran'
                        ],

                    'metode_pembayaran' =>
                        $validated[
                            'metode_pembayaran'
                        ],

                    'total_bayar' =>
                        $totalBayar,

                    'referensi_transaksi' =>
                        $validated[
                            'referensi_transaksi'
                        ]
                        ?? null,

                    'catatan' =>
                        $validated[
                            'catatan'
                        ]
                        ?? null,

                    'created_by' =>
                        auth()->id(),
                ]);

            /*
            |--------------------------------------------------------------------------
            | Sinkron Penerimaan Piutang
            |--------------------------------------------------------------------------
            |
            | PembayaranPenjualan = bukti pembayaran / UI penjualan
            |
            | PenerimaanPiutang = sumber jurnal & kartu piutang.
            |
            */

            $nomorPenerimaan =
                $systemAccountingService
                    ->generateDocumentNumber(
                        'KWITANSI',
                        $validated[
                            'tanggal_pembayaran'
                        ]
                    );

            /*
            | Double safety nomor penerimaan.
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
                    'KWT-'
                    . now()->format(
                        'YmdHis'
                    )
                    . '-'
                    . $pembayaran->id;
            }

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
                            'tanggal_pembayaran'
                        ],

                    'total_dibayar' =>
                        $totalBayar,

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
            | Proses Setiap Invoice
            |--------------------------------------------------------------------------
            */

            foreach (
                $allocatedInvoices
                as $item
            ) {
                $invoice =
                    InvoicePenjualan::query()
                        ->whereKey(
                            $item['id']
                        )
                        ->lockForUpdate()
                        ->firstOrFail();

                $nominal =
                    (float) $item[
                        'nominal_bayar'
                    ];

                /*
                |--------------------------------------------------------------------------
                | Security 1:
                | Invoice Harus Milik Customer
                |--------------------------------------------------------------------------
                */

                if (
                    (int) $invoice->customer_id
                    !==
                    (int) $validated[
                        'customer_id'
                    ]
                ) {
                    throw new \RuntimeException(
                        'Invoice '
                        . $invoice->nomor_invoice
                        . ' bukan milik customer yang dipilih.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Security 2:
                | Invoice Harus AKTIF
                |--------------------------------------------------------------------------
                */

                if (
                    $invoice->status_invoice
                    !== 'AKTIF'
                ) {
                    throw new \RuntimeException(
                        'Invoice '
                        . $invoice->nomor_invoice
                        . ' sudah dibatalkan atau tidak aktif sehingga tidak dapat dibayar.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Security 3:
                | Invoice Tidak Boleh Sudah Lunas
                |--------------------------------------------------------------------------
                */

                if (
                    $invoice->status_pembayaran
                    === 'Lunas'
                    ||
                    (float) $invoice->sisa_piutang
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
                | Security 4:
                | Pembayaran Tidak Boleh Sebelum Invoice
                |--------------------------------------------------------------------------
                */

                if (
                    Carbon::parse(
                        $validated[
                            'tanggal_pembayaran'
                        ]
                    )->lt(
                        Carbon::parse(
                            $invoice
                                ->tanggal_invoice
                        )
                    )
                ) {
                    throw new \RuntimeException(
                        'Tanggal pembayaran invoice '
                        . $invoice->nomor_invoice
                        . ' tidak boleh sebelum tanggal invoice.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Security 5:
                | Prevent Overpayment
                |--------------------------------------------------------------------------
                */

                $sisaPiutangLama =
                    (float) $invoice
                        ->sisa_piutang;

                if (
                    $nominal
                    > (
                        $sisaPiutangLama
                        + 0.01
                    )
                ) {
                    throw new \RuntimeException(
                        'Nominal bayar untuk invoice '
                        . $invoice->nomor_invoice
                        . ' (Rp '
                        . number_format(
                            $nominal,
                            0,
                            ',',
                            '.'
                        )
                        . ') melebihi sisa piutang (Rp '
                        . number_format(
                            $sisaPiutangLama,
                            0,
                            ',',
                            '.'
                        )
                        . ').'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Detail Pembayaran Penjualan
                |--------------------------------------------------------------------------
                */

                PembayaranPenjualanDetail::create([
                    'pembayaran_penjualan_id' =>
                        $pembayaran->id,

                    'invoice_penjualan_id' =>
                        $invoice->id,

                    'nominal_bayar' =>
                        $nominal,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Detail Penerimaan Piutang
                |--------------------------------------------------------------------------
                */

                PenerimaanPiutangDetail::create([
                    'penerimaan_piutang_id' =>
                        $penerimaan->id,

                    'invoice_penjualan_id' =>
                        $invoice->id,

                    'nominal_dibayar' =>
                        $nominal,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Update Invoice
                |--------------------------------------------------------------------------
                */

                $sudahDibayarBaru =
                    (float) $invoice
                        ->sudah_dibayar
                    + $nominal;

                $sisaPiutangBaru =
                    max(
                        0,
                        (float) $invoice->total
                        - $sudahDibayarBaru
                    );

                /*
                |--------------------------------------------------------------------------
                | Tentukan Status Pembayaran
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
                    | Pastikan angka sudah_dibayar persis dengan total.
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
                | Update
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
            | Tambah Kas / Bank
            |--------------------------------------------------------------------------
            */

            $kasBankService->uangMasuk(
                $validated[
                    'rekening_kas_bank_id'
                ],

                $totalBayar,

                'PEMBAYARAN_PENJUALAN',

                $pembayaran->id,

                $pembayaran
                    ->nomor_pembayaran,

                'Penerimaan Pembayaran Piutang - '
                    . $customer
                        ->nama_customer,

                $validated[
                    'tanggal_pembayaran'
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
            | Sekaligus membuat Kartu Piutang.
            |
            */

            $systemAccountingService
                ->autoJournalPenerimaanPiutang(
                    $penerimaan
                );

            /*
            |--------------------------------------------------------------------------
            | Commit
            |--------------------------------------------------------------------------
            */

            DB::commit();

            return redirect()
                ->route(
                    'pembayaran-penjualan.show',
                    $pembayaran
                )
                ->with(
                    'success',
                    'Pembayaran berhasil disimpan. Invoice, Kas/Bank, Kartu Piutang, dan jurnal otomatis telah diperbarui.'
                );

        } catch (\Throwable $e) {
            DB::rollBack();

            report($e);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Gagal memproses pembayaran: '
                    . $e->getMessage()
                );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW
    |--------------------------------------------------------------------------
    */

    public function show(
        PembayaranPenjualan $pembayaranPenjualan
    ) {
        $pembayaranPenjualan->load([
            'customer',
            'rekeningKasBank',
            'creator',
            'details.invoicePenjualan',
        ]);

        return view(
            'pembayaran-penjualan.show',
            compact(
                'pembayaranPenjualan'
            )
        );
    }
}