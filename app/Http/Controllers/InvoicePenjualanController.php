<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Customer;
use App\Models\Gudang;
use App\Models\InvoicePenjualan;
use App\Models\InvoicePenjualanItem;
use App\Models\Penjualan;
use App\Models\PenjualanItem;
use App\Models\RekeningKasBank;
use App\Models\StokGudang;
use App\Services\InventoryService;
use App\Services\KasBankService;
use App\Services\SystemAccountingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class InvoicePenjualanController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $query = InvoicePenjualan::with([
            'customer',
            'gudang',
            'creator',
            'items.barang',
        ]);

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where(
                    'nomor_invoice',
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
            });
        }

        if ($request->filled('status_pembayaran')) {
            $query->where(
                'status_pembayaran',
                $request->status_pembayaran
            );
        }

        if ($request->filled('status_invoice')) {
            $query->where(
                'status_invoice',
                $request->status_invoice
            );
        }

        if ($request->filled('tipe_pembayaran')) {
            $query->where(
                'tipe_pembayaran',
                $request->tipe_pembayaran
            );
        }

        if ($request->filled('tanggal_mulai')) {
            $query->whereDate(
                'tanggal_invoice',
                '>=',
                $request->tanggal_mulai
            );
        }

        if ($request->filled('tanggal_selesai')) {
            $query->whereDate(
                'tanggal_invoice',
                '<=',
                $request->tanggal_selesai
            );
        }

        $invoices = $query
            ->latest('tanggal_invoice')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view(
            'invoice-penjualan.index',
            compact('invoices')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE
    |--------------------------------------------------------------------------
    */

    public function create(Request $request)
    {
        $customers = Customer::where(
            'is_active',
            true
        )
            ->orderBy('nama_customer')
            ->get();

        $gudangs = Gudang::where(
            'is_active',
            true
        )
            ->orderBy('nama_gudang')
            ->get();

        $rekeningKasBanks = RekeningKasBank::where(
            'is_active',
            true
        )
            ->orderBy('nama_rekening')
            ->get();

        $barangs = Barang::with([
            'stokGudangs',
            'suppliers',
            'satuan',
            'kategori',
        ])
            ->where(
                'is_active',
                true
            )
            ->orderBy('nama_barang')
            ->get();

        $nomorInvoice = app(
            SystemAccountingService::class
        )->generateDocumentNumber('INV');

        /*
        |--------------------------------------------------------------------------
        | Sales Order Yang Belum Selesai Di-Invoice
        |--------------------------------------------------------------------------
        */

        $penjualans = Penjualan::with([
            'customer',
            'items.barang',
        ])
            ->whereIn(
                'status',
                [
                    'Dikonfirmasi',
                    'Diproses',
                ]
            )
            ->whereHas(
                'items',
                function ($q) {
                    $q->whereRaw(
                        'qty > COALESCE(qty_terinvoice, 0)'
                    );
                }
            )
            ->latest('tanggal_penjualan')
            ->get();

        $selectedPenjualan = null;

        if ($request->filled('penjualan_id')) {
            $selectedPenjualan = Penjualan::with([
                'customer',
                'items.barang',
            ])->find(
                $request->penjualan_id
            );
        }

        $initialItems = [
            [
                'barang_id' => '',
                'qty' => 1,
                'harga_satuan' => 0,
                'diskon' => 0,
                'catatan' => '',
            ],
        ];

        if (
            $selectedPenjualan
            && $selectedPenjualan->items->isNotEmpty()
        ) {
            $initialItems = $selectedPenjualan
                ->items
                ->filter(function ($item) {
                    return (
                        (float) $item->qty
                        - (float) ($item->qty_terinvoice ?? 0)
                    ) > 0;
                })
                ->map(function ($item) {
                    $sisa =
                        (float) $item->qty
                        - (float) ($item->qty_terinvoice ?? 0);

                    return [
                        'barang_id' =>
                            $item->barang_id,

                        'qty' =>
                            $sisa,

                        'harga_satuan' =>
                            (float) $item->harga_satuan,

                        'diskon' =>
                            (float) ($item->diskon ?? 0),

                        'catatan' =>
                            $item->catatan ?? '',
                    ];
                })
                ->values()
                ->toArray();
        }

        return view(
            'invoice-penjualan.create',
            compact(
                'customers',
                'gudangs',
                'rekeningKasBanks',
                'barangs',
                'penjualans',
                'selectedPenjualan',
                'nomorInvoice',
                'initialItems'
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
        InventoryService $inventoryService,
        KasBankService $kasBankService,
        SystemAccountingService $systemAccountingService
    ) {
        $validated = $request->validate([
            'nomor_invoice' =>
                'required|string|unique:invoice_penjualans,nomor_invoice',

            'penjualan_id' =>
                'nullable|exists:penjualans,id',

            'customer_id' =>
                'required|exists:customers,id',

            'gudang_id' =>
                'required|exists:gudangs,id',

            'tanggal_invoice' =>
                'required|date',

            'tanggal_jatuh_tempo' =>
                'nullable|date',

            'tipe_pembayaran' =>
                'nullable|in:Tunai,Kredit',

            'rekening_kas_bank_id' =>
                'nullable|exists:rekening_kas_banks,id',

            'catatan' =>
                'nullable|string',

            'pajak_persen' =>
                'nullable|numeric|min:0|max:100',

            'items' =>
                'required|array|min:1',

            'items.*.barang_id' =>
                'required|distinct|exists:barangs,id',

            'items.*.qty' =>
                'required|numeric|min:0.01',

            'items.*.harga_satuan' =>
                'required|numeric|min:0',

            'items.*.diskon' =>
                'nullable|numeric|min:0',

            'items.*.catatan' =>
                'nullable|string',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Cut-Off
        |--------------------------------------------------------------------------
        */

        if (
            $systemAccountingService
                ->isPeriodLocked(
                    $validated['tanggal_invoice']
                )
        ) {
            return back()
                ->withInput()
                ->with(
                    'error',
                    'Transaksi pada tanggal ini tidak dapat dibuat karena periode bulanan sudah DITUTUP (Cut-Off).'
                );
        }

        $tipePembayaran =
            $validated['tipe_pembayaran']
            ?? null;

        $isTunai =
            $tipePembayaran === 'Tunai';

        $isKredit =
            $tipePembayaran === 'Kredit';

        /*
        |--------------------------------------------------------------------------
        | Validasi Rekening Tunai
        |--------------------------------------------------------------------------
        */

        if ($isTunai) {
            if (empty($validated['rekening_kas_bank_id'])) {
                return back()
                    ->withInput()
                    ->with(
                        'error',
                        'Pilih Kas/Rekening Bank untuk pembayaran Tunai.'
                    );
            }

            $rekening = RekeningKasBank::query()
                ->where(
                    'id',
                    $validated['rekening_kas_bank_id']
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
        }

        /*
        |--------------------------------------------------------------------------
        | Jatuh Tempo
        |--------------------------------------------------------------------------
        */

        if ($isKredit) {
            if (
                empty(
                    $validated['tanggal_jatuh_tempo']
                )
            ) {
                $validated['tanggal_jatuh_tempo'] =
                    Carbon::parse(
                        $validated['tanggal_invoice']
                    )
                        ->addDays(30)
                        ->format('Y-m-d');
            } elseif (
                Carbon::parse(
                    $validated['tanggal_jatuh_tempo']
                )->lt(
                    Carbon::parse(
                        $validated['tanggal_invoice']
                    )
                )
            ) {
                return back()
                    ->withInput()
                    ->with(
                        'error',
                        'Tanggal jatuh tempo tidak boleh sebelum tanggal invoice.'
                    );
            }
        } else {
            $validated['tanggal_jatuh_tempo'] =
                null;
        }

        /*
        |--------------------------------------------------------------------------
        | Validasi Sales Order
        |--------------------------------------------------------------------------
        */

        $so = null;

        if (
            !empty(
                $validated['penjualan_id']
            )
        ) {
            $so = Penjualan::with(
                'items'
            )->find(
                $validated['penjualan_id']
            );

            if (
                !$so
                || in_array(
                    $so->status,
                    [
                        'Selesai',
                        'Dibatalkan',
                    ],
                    true
                )
            ) {
                return back()
                    ->withInput()
                    ->with(
                        'error',
                        'Sales Order yang dipilih sudah Selesai atau Dibatalkan.'
                    );
            }

            if (
                (int) $so->customer_id
                !== (int) $validated['customer_id']
            ) {
                return back()
                    ->withInput()
                    ->with(
                        'error',
                        'Customer yang dipilih tidak sesuai dengan Sales Order.'
                    );
            }

            if (
                $so->gudang_id
                && (int) $so->gudang_id
                !== (int) $validated['gudang_id']
            ) {
                return back()
                    ->withInput()
                    ->with(
                        'error',
                        'Gudang yang dipilih tidak sesuai dengan Sales Order.'
                    );
            }
        }

        DB::beginTransaction();

        try {
            /*
            |--------------------------------------------------------------------------
            | Lock Sales Order
            |--------------------------------------------------------------------------
            */

            if ($so) {
                $so = Penjualan::with('items')
                    ->lockForUpdate()
                    ->findOrFail(
                        $validated['penjualan_id']
                    );
            }

            /*
            |--------------------------------------------------------------------------
            | Hitung Invoice
            |--------------------------------------------------------------------------
            */

            $subtotal = 0;
            $totalDiskonItem = 0;

            foreach (
                $validated['items']
                as $item
            ) {
                $qty =
                    (float) $item['qty'];

                $hargaSatuan =
                    (float) $item['harga_satuan'];

                $diskonItem =
                    (float) ($item['diskon'] ?? 0);

                $grossItem =
                    $qty * $hargaSatuan;

                if (
                    $diskonItem
                    > $grossItem
                ) {
                    throw new \RuntimeException(
                        'Diskon item tidak boleh lebih besar dari nilai barang.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Validasi Ketersediaan Stok
                |--------------------------------------------------------------------------
                */

                $stokGudangObj = StokGudang::query()
                    ->where(
                        'barang_id',
                        $item['barang_id']
                    )
                    ->where(
                        'gudang_id',
                        $validated['gudang_id']
                    )
                    ->first();

                $stokTersedia =
                    $stokGudangObj
                        ? (float) $stokGudangObj->qty
                        : 0;

                if (
                    $stokTersedia <= 0
                    || $stokTersedia < $qty
                ) {
                    $barang = Barang::find(
                        $item['barang_id']
                    );

                    $namaBarang = $barang
                        ? $barang->kode_barang
                            . ' - '
                            . $barang->nama_barang
                        : 'ID ' . $item['barang_id'];

                    if ($so) {
                        throw new \RuntimeException(
                            "Gagal menerbitkan invoice dari Sales Order #{$so->nomor_penjualan}: Stok barang '{$namaBarang}' masih 0 / tidak mencukupi di gudang yang dipilih. "
                            . '(Stok tersedia: '
                            . number_format(
                                $stokTersedia,
                                2,
                                ',',
                                '.'
                            )
                            . ', Dibutuhkan: '
                            . number_format(
                                $qty,
                                2,
                                ',',
                                '.'
                            )
                            . '). Silakan isi stok persediaan terlebih dahulu melalui Pembelian / Penerimaan Barang.'
                        );
                    }

                    throw new \RuntimeException(
                        "Gagal menerbitkan invoice: Barang '{$namaBarang}' stoknya 0 / tidak mencukupi di gudang yang dipilih. "
                        . '(Stok tersedia: '
                        . number_format(
                            $stokTersedia,
                            2,
                            ',',
                            '.'
                        )
                        . ', Diminta: '
                        . number_format(
                            $qty,
                            2,
                            ',',
                            '.'
                        )
                        . '). Silakan isi stok persediaan terlebih dahulu melalui Pembelian / Penerimaan Barang.'
                    );
                }

                if ($so) {
                    $soItem = $so
                        ->items
                        ->where(
                            'barang_id',
                            $item['barang_id']
                        )
                        ->first();

                    if (!$soItem) {
                        throw new \RuntimeException(
                            'Barang pada invoice tidak ditemukan dalam Sales Order #'
                            . $so->nomor_penjualan
                        );
                    }

                    $sisaQtySO =
                        (float) $soItem->qty
                        - (float) ($soItem->qty_terinvoice ?? 0);

                    if (
                        $qty
                        > $sisaQtySO
                    ) {
                        throw new \RuntimeException(
                            "Jumlah barang pada invoice ({$qty}) melebihi sisa yang belum ter-invoice pada Sales Order ({$sisaQtySO})."
                        );
                    }
                }

                $subtotal +=
                    $grossItem;

                $totalDiskonItem +=
                    $diskonItem;
            }

            /*
            |--------------------------------------------------------------------------
            | DPP
            |--------------------------------------------------------------------------
            */

            $dpp =
                $subtotal
                - $totalDiskonItem;

            if ($dpp < 0) {
                throw new \RuntimeException(
                    'Nilai DPP invoice tidak boleh negatif.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Pajak
            |--------------------------------------------------------------------------
            */

            $pajakPersen =
                (float) (
                    $validated['pajak_persen']
                    ?? 0
                );

            $pajak =
                ($dpp * $pajakPersen)
                / 100;

            /*
            |--------------------------------------------------------------------------
            | Total
            |--------------------------------------------------------------------------
            */

            $total =
                $dpp
                + $pajak;

            if ($total < 0) {
                throw new \RuntimeException(
                    'Total invoice tidak valid.'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Limit Piutang (jika langsung dipilih Kredit)
            |--------------------------------------------------------------------------
            */

            if ($isKredit) {
                $customer = Customer::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $validated['customer_id']
                    );

                if (
                    (float) $customer->limit_piutang
                    > 0
                ) {
                    $currentPiutang =
                        (float) InvoicePenjualan::query()
                            ->where(
                                'customer_id',
                                $customer->id
                            )
                            ->where(
                                'status_invoice',
                                '!=',
                                'DIBATALKAN'
                            )
                            ->sum(
                                'sisa_piutang'
                            );

                    if (
                        (
                            $currentPiutang
                            + $total
                        )
                        > (float) $customer->limit_piutang
                    ) {
                        throw new \RuntimeException(
                            "Limit piutang customer '{$customer->nama_customer}' melampaui batas! "
                            . "Limit: Rp "
                            . number_format(
                                $customer->limit_piutang,
                                0,
                                ',',
                                '.'
                            )
                            . ", Piutang Berjalan: Rp "
                            . number_format(
                                $currentPiutang,
                                0,
                                ',',
                                '.'
                            )
                            . ", Tagihan Baru: Rp "
                            . number_format(
                                $total,
                                0,
                                ',',
                                '.'
                            )
                        );
                    }
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Status Pembayaran
            |--------------------------------------------------------------------------
            */

            $sudahDibayar =
                $isTunai
                    ? $total
                    : 0;

            $sisaPiutang =
                $isTunai
                    ? 0
                    : $total;

            $statusPembayaran =
                $isTunai
                    ? 'Lunas'
                    : ($isKredit ? 'Belum Lunas' : 'Menunggu Pembayaran');

            /*
            |--------------------------------------------------------------------------
            | Nomor Invoice
            |--------------------------------------------------------------------------
            */

            $nomorInvoice =
                $validated['nomor_invoice'];

            if (
                InvoicePenjualan::where(
                    'nomor_invoice',
                    $nomorInvoice
                )->exists()
            ) {
                $nomorInvoice =
                    $systemAccountingService
                        ->generateDocumentNumber(
                            'INV',
                            $validated['tanggal_invoice']
                        );
            }

            /*
            |--------------------------------------------------------------------------
            | Buat Invoice
            |--------------------------------------------------------------------------
            */

            $invoice = InvoicePenjualan::create([
                'nomor_invoice' =>
                    $nomorInvoice,

                'penjualan_id' =>
                    $validated['penjualan_id']
                    ?? null,

                'customer_id' =>
                    $validated['customer_id'],

                'gudang_id' =>
                    $validated['gudang_id'],

                'tanggal_invoice' =>
                    $validated['tanggal_invoice'],

                'tanggal_jatuh_tempo' =>
                    $isKredit
                        ? $validated['tanggal_jatuh_tempo']
                        : null,

                'tipe_pembayaran' =>
                    $tipePembayaran,

                'rekening_kas_bank_id' =>
                    $isTunai
                        ? ($validated['rekening_kas_bank_id'] ?? null)
                        : null,

                'subtotal' =>
                    $subtotal,

                'diskon' =>
                    $totalDiskonItem,

                'pajak_persen' =>
                    $pajakPersen,

                'pajak' =>
                    $pajak,

                'total' =>
                    $total,

                'sudah_dibayar' =>
                    $sudahDibayar,

                'sisa_piutang' =>
                    $sisaPiutang,

                'status_pembayaran' =>
                    $statusPembayaran,

                'status_invoice' =>
                    'AKTIF',

                'catatan' =>
                    $validated['catatan']
                    ?? null,

                'created_by' =>
                    auth()->id(),
            ]);

            /*
            |--------------------------------------------------------------------------
            | Item Invoice + Stok + HPP
            |--------------------------------------------------------------------------
            */

            foreach (
                $validated['items']
                as $item
            ) {
                $barangId =
                    (int) $item['barang_id'];

                $qty =
                    (float) $item['qty'];

                $hargaJual =
                    (float) $item['harga_satuan'];

                $diskonItem =
                    (float) ($item['diskon'] ?? 0);

                $itemSubtotal =
                    ($qty * $hargaJual)
                    - $diskonItem;

                /*
                |--------------------------------------------------------------------------
                | Snapshot Stok Sebelum Barang Keluar
                |--------------------------------------------------------------------------
                */

                $stokSebelum = StokGudang::query()
                    ->where(
                        'barang_id',
                        $barangId
                    )
                    ->where(
                        'gudang_id',
                        $validated['gudang_id']
                    )
                    ->lockForUpdate()
                    ->first();

                if (!$stokSebelum) {
                    $barang = Barang::find(
                        $barangId
                    );

                    throw new \RuntimeException(
                        'Stok barang '
                        . ($barang->nama_barang ?? $barangId)
                        . ' tidak ditemukan pada gudang yang dipilih.'
                    );
                }

                if (
                    $qty
                    > (float) $stokSebelum->qty
                ) {
                    $barang = Barang::find(
                        $barangId
                    );

                    throw new \RuntimeException(
                        'Stok '
                        . ($barang->nama_barang ?? $barangId)
                        . ' tidak mencukupi. '
                        . 'Stok tersedia: '
                        . number_format(
                            (float) $stokSebelum->qty,
                            2,
                            ',',
                            '.'
                        )
                        . ', jumlah keluar: '
                        . number_format(
                            $qty,
                            2,
                            ',',
                            '.'
                        )
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Snapshot HPP
                |--------------------------------------------------------------------------
                */

                $hppSatuan =
                    (float) (
                        $stokSebelum
                            ->harga_rata_rata
                        ?? 0
                    );

                if ($hppSatuan < 0) {
                    throw new \RuntimeException(
                        'Harga rata-rata persediaan tidak valid.'
                    );
                }

                $totalHpp =
                    $qty
                    * $hppSatuan;

                /*
                |--------------------------------------------------------------------------
                | Stok Keluar
                |--------------------------------------------------------------------------
                */

                $inventoryService->stokKeluar(
                    $barangId,
                    (int) $validated['gudang_id'],
                    $qty,
                    'INVOICE_PENJUALAN',
                    (int) $invoice->id,
                    'Penjualan Invoice '
                        . $invoice->nomor_invoice,
                    'KELUAR',
                    $validated['tanggal_invoice']
                );

                /*
                |--------------------------------------------------------------------------
                | Simpan Item
                |--------------------------------------------------------------------------
                */

                InvoicePenjualanItem::create([
                    'invoice_penjualan_id' =>
                        $invoice->id,

                    'barang_id' =>
                        $barangId,

                    'qty' =>
                        $qty,

                    'harga_satuan' =>
                        $hargaJual,

                    'hpp_satuan' =>
                        $hppSatuan,

                    'total_hpp' =>
                        $totalHpp,

                    'diskon' =>
                        $diskonItem,

                    'subtotal' =>
                        $itemSubtotal,

                    'catatan' =>
                        $item['catatan']
                        ?? null,
                ]);

                /*
                |--------------------------------------------------------------------------
                | Update Qty Terinvoice Sales Order
                |--------------------------------------------------------------------------
                */

                if ($so) {
                    $soItem = $so
                        ->items
                        ->where(
                            'barang_id',
                            $barangId
                        )
                        ->first();

                    if (!$soItem) {
                        throw new \RuntimeException(
                            'Item Sales Order tidak ditemukan ketika memperbarui qty terinvoice.'
                        );
                    }

                    $soItem->increment(
                        'qty_terinvoice',
                        $qty
                    );
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Update Status Sales Order
            |--------------------------------------------------------------------------
            */

            if ($so) {
                $so->load('items');

                $fullyInvoiced =
                    $so->items->every(
                        function ($item) {
                            return (
                                (float) ($item->qty_terinvoice ?? 0)
                                >= (float) $item->qty
                            );
                        }
                    );

                $so->update([
                    'status' =>
                        $fullyInvoiced
                            ? 'Selesai'
                            : 'Diproses',
                ]);
            }

            /*
            |--------------------------------------------------------------------------
            | Penjualan Tunai → Kas / Bank Masuk
            |--------------------------------------------------------------------------
            */

            if (
                $isTunai
                && $total > 0
            ) {
                $customer = Customer::find(
                    $validated['customer_id']
                );

                $kasBankService->uangMasuk(
                    $validated[
                        'rekening_kas_bank_id'
                    ],

                    $total,

                    'INVOICE_PENJUALAN',

                    $invoice->id,

                    $invoice->nomor_invoice,

                    'Penjualan Tunai '
                        . $invoice->nomor_invoice
                        . ' - '
                        . (
                            $customer->nama_customer
                            ?? ''
                        ),

                    $validated[
                        'tanggal_invoice'
                    ]
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Auto Journal (hanya jika cara pembayaran langsung ditentukan)
            |--------------------------------------------------------------------------
            */

            if ($tipePembayaran !== null) {
                $systemAccountingService
                    ->autoJournalInvoicePenjualan(
                        $invoice
                    );
            }

            DB::commit();

            $pesanSukses = $tipePembayaran === null
                ? 'Invoice Penjualan #' . $invoice->nomor_invoice . ' berhasil diterbitkan dan stok gudang telah diperbarui. Silakan tetapkan cara pembayaran (Tunai atau Kredit) pada halaman ini.'
                : 'Invoice Penjualan #' . $invoice->nomor_invoice . ' berhasil diterbitkan, stok diperbarui, dan jurnal otomatis telah dibuat.';

            return redirect()
                ->route(
                    'invoice-penjualan.show',
                    $invoice
                )
                ->with(
                    'success',
                    $pesanSukses
                );

        } catch (\Throwable $e) {
            DB::rollBack();

            report($e);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Gagal menerbitkan invoice penjualan: '
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
        InvoicePenjualan $invoicePenjualan
    ) {
        $invoicePenjualan->load([
            'customer',
            'gudang',
            'rekeningKasBank',
            'creator',
            'penjualan',
            'items.barang.satuan',

            /*
            | Untuk pembayaran dari modul Penerimaan Piutang.
            */

            'penerimaanDetails.penerimaanPiutang.rekeningKasBank',

            /*
            | Untuk pembayaran dari modul Pembayaran Penjualan.
            */

            'pembayaranDetails.pembayaranPenjualan.rekeningKasBank',

            'returPenjualans',
        ]);

        return view(
            'invoice-penjualan.show',
            [
                'invoicePenjualan' => $invoicePenjualan,
                'rekeningKasBanks' => RekeningKasBank::query()
                    ->where('is_active', true)
                    ->orderBy('nama_rekening')
                    ->get(),
            ]
        );
    }

    /*
    |--------------------------------------------------------------------------
    | KONFIRMASI PEMBAYARAN (STEP 2: TUNAI ATAU KREDIT)
    |--------------------------------------------------------------------------
    */

    public function konfirmasiPembayaran(
        Request $request,
        InvoicePenjualan $invoicePenjualan,
        KasBankService $kasBankService,
        SystemAccountingService $systemAccountingService
    ) {
        if ($invoicePenjualan->status_invoice === 'DIBATALKAN') {
            return back()->with(
                'error',
                'Invoice yang dibatalkan tidak dapat dikonfirmasi pembayarannya.'
            );
        }

        if (
            $invoicePenjualan->status_pembayaran !== 'Menunggu Pembayaran'
            && !empty($invoicePenjualan->tipe_pembayaran)
        ) {
            return back()->with(
                'error',
                'Cara pembayaran untuk invoice ini sudah pernah ditetapkan ('
                . $invoicePenjualan->tipe_pembayaran
                . ').'
            );
        }

        $validated = $request->validate([
            'tipe_pembayaran' =>
                'required|in:Tunai,Kredit',

            'rekening_kas_bank_id' =>
                'nullable|required_if:tipe_pembayaran,Tunai|exists:rekening_kas_banks,id',

            'tanggal_jatuh_tempo' =>
                'nullable|required_if:tipe_pembayaran,Kredit|date',
        ]);

        $tipe = $validated['tipe_pembayaran'];
        $total = (float) $invoicePenjualan->total;

        DB::beginTransaction();
        try {
            $invoice = InvoicePenjualan::query()
                ->whereKey($invoicePenjualan->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($tipe === 'Tunai') {
                $rekening = RekeningKasBank::findOrFail(
                    $validated['rekening_kas_bank_id']
                );

                $invoice->update([
                    'tipe_pembayaran' => 'Tunai',
                    'rekening_kas_bank_id' => $rekening->id,
                    'tanggal_jatuh_tempo' => null,
                    'status_pembayaran' => 'Lunas',
                    'sudah_dibayar' => $total,
                    'sisa_piutang' => 0,
                ]);

                if ($total > 0) {
                    $customer = Customer::find(
                        $invoice->customer_id
                    );

                    $kasBankService->uangMasuk(
                        $rekening->id,
                        $total,
                        'INVOICE_PENJUALAN',
                        $invoice->id,
                        $invoice->nomor_invoice,
                        'Penjualan Tunai '
                            . $invoice->nomor_invoice
                            . ' - '
                            . ($customer->nama_customer ?? ''),
                        $invoice->tanggal_invoice
                    );
                }

                $systemAccountingService
                    ->autoJournalInvoicePenjualan($invoice);

                DB::commit();

                return redirect()
                    ->route('invoice-penjualan.show', $invoice)
                    ->with(
                        'success',
                        'Cara pembayaran berhasil ditetapkan sebagai TUNAI (Lunas). Kas/Bank dan Jurnal Penjualan otomatis telah dibukukan.'
                    );
            }

            if ($tipe === 'Kredit') {
                if (empty($validated['tanggal_jatuh_tempo'])) {
                    throw new \RuntimeException(
                        'Tanggal jatuh tempo wajib diisi untuk penjualan kredit.'
                    );
                }

                if ($validated['tanggal_jatuh_tempo'] < $invoice->tanggal_invoice) {
                    throw new \RuntimeException(
                        'Tanggal jatuh tempo tidak boleh mendahului tanggal invoice.'
                    );
                }

                $customer = Customer::query()
                    ->lockForUpdate()
                    ->findOrFail($invoice->customer_id);

                $limitPiutang = (float) ($customer->limit_piutang ?? 0);
                if ($limitPiutang > 0) {
                    $totalPiutangBerjalan = (float) InvoicePenjualan::query()
                        ->where('customer_id', $customer->id)
                        ->where('status_invoice', 'AKTIF')
                        ->where('status_pembayaran', '!=', 'Lunas')
                        ->where('id', '!=', $invoice->id)
                        ->sum('sisa_piutang');

                    if (($totalPiutangBerjalan + $total) > $limitPiutang) {
                        throw new \RuntimeException(
                            'Total piutang customer ('
                            . number_format($totalPiutangBerjalan + $total, 0, ',', '.')
                            . ') melebihi limit kredit ('
                            . number_format($limitPiutang, 0, ',', '.')
                            . ').'
                        );
                    }
                }

                $invoice->update([
                    'tipe_pembayaran' => 'Kredit',
                    'tanggal_jatuh_tempo' => $validated['tanggal_jatuh_tempo'],
                    'rekening_kas_bank_id' => null,
                    'status_pembayaran' => 'Belum Lunas',
                    'sudah_dibayar' => 0,
                    'sisa_piutang' => $total,
                ]);

                $systemAccountingService
                    ->autoJournalInvoicePenjualan($invoice);

                DB::commit();

                return redirect()
                    ->route('invoice-penjualan.show', $invoice)
                    ->with(
                        'success',
                        'Cara pembayaran berhasil ditetapkan sebagai KREDIT (Belum Lunas). Piutang dan Jurnal Penjualan otomatis telah dibukukan.'
                    );
            }

            throw new \RuntimeException('Tipe pembayaran tidak valid.');

        } catch (\Throwable $e) {
            DB::rollBack();
            report($e);

            return back()->with(
                'error',
                'Gagal menetapkan cara pembayaran: ' . $e->getMessage()
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | DESTROY
    |--------------------------------------------------------------------------
    */

    public function destroy(
        InvoicePenjualan $invoicePenjualan
    ) {
        if (
            $invoicePenjualan->status_invoice
            === 'DIBATALKAN'
        ) {
            return back()->with(
                'error',
                'Invoice ini sudah dibatalkan.'
            );
        }

        if (
            $invoicePenjualan
                ->penerimaanDetails()
                ->exists()
        ) {
            return back()->with(
                'error',
                'Invoice tidak dapat dihapus karena sudah memiliki riwayat pembayaran.'
            );
        }

        if (
            $invoicePenjualan
                ->returPenjualans()
                ->exists()
        ) {
            return back()->with(
                'error',
                'Invoice tidak dapat dihapus karena sudah memiliki retur.'
            );
        }

        return back()->with(
            'error',
            'Invoice penjualan tidak boleh dihapus langsung karena sudah memengaruhi stok, Kas/Bank, dan jurnal. Gunakan fitur Pembatalan Resmi.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PEMBATALAN RESMI
    |--------------------------------------------------------------------------
    */

    public function batal(
        Request $request,
        InvoicePenjualan $invoice,
        SystemAccountingService $systemAccountingService,
        InventoryService $inventoryService,
        KasBankService $kasBankService
    ) {
        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        $request->validate([
            'alasan_pembatalan' =>
                'required|string|min:5|max:1000',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Refresh
        |--------------------------------------------------------------------------
        */

        $invoice->refresh();

        /*
        |--------------------------------------------------------------------------
        | Sudah Dibatalkan
        |--------------------------------------------------------------------------
        */

        if (
            $invoice->status_invoice
            === 'DIBATALKAN'
        ) {
            return back()->with(
                'error',
                'Invoice ini sudah dibatalkan sebelumnya.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Cut-Off
        |--------------------------------------------------------------------------
        */

        if (
            $systemAccountingService
                ->isPeriodLocked(
                    $invoice->tanggal_invoice
                )
        ) {
            return back()->with(
                'error',
                'Invoice tidak dapat dibatalkan karena periode bulanan invoice sudah DITUTUP (Cut-Off).'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Invoice Kredit Sudah Dibayar
        |--------------------------------------------------------------------------
        */

        if (
            $invoice->tipe_pembayaran
            === 'Kredit'
            && (float) $invoice->sudah_dibayar > 0
        ) {
            return back()->with(
                'error',
                'Invoice tidak dapat dibatalkan langsung karena sudah memiliki pembayaran piutang. Batalkan/koreksi pembayaran terlebih dahulu.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Double Safety Riwayat Penerimaan
        |--------------------------------------------------------------------------
        */

        if (
            $invoice
                ->penerimaanDetails()
                ->exists()
        ) {
            return back()->with(
                'error',
                'Invoice tidak dapat dibatalkan karena sudah memiliki transaksi penerimaan pembayaran.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Tidak Boleh Ada Retur
        |--------------------------------------------------------------------------
        |
        | PENTING:
        |
        | Tidak memakai:
        |
        | ->where('total', '>', 0)
        |
        | karena tabel retur_penjualans tidak memiliki kolom total.
        |
        */

        if (
            $invoice
                ->returPenjualans()
                ->exists()
        ) {
            return back()->with(
                'error',
                'Invoice tidak dapat dibatalkan karena sudah memiliki transaksi retur penjualan.'
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
            | Lock Invoice
            |--------------------------------------------------------------------------
            */

            $invoice = InvoicePenjualan::query()
                ->whereKey($invoice->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (
                $invoice->status_invoice
                === 'DIBATALKAN'
            ) {
                throw new \RuntimeException(
                    'Invoice sudah dibatalkan oleh proses lain.'
                );
            }

            $invoice->loadMissing([
                'items',
                'customer',
                'rekeningKasBank',
            ]);

            /*
            |--------------------------------------------------------------------------
            | 1. Kembalikan Barang Ke Stok
            |--------------------------------------------------------------------------
            */

            foreach (
                $invoice->items
                as $item
            ) {
                /*
                |--------------------------------------------------------------------------
                | HPP Snapshot
                |--------------------------------------------------------------------------
                */

                $hargaHpp =
                    (float) $item->hpp_satuan;

                if ($hargaHpp < 0) {
                    throw new \RuntimeException(
                        'HPP item invoice tidak valid.'
                    );
                }

                if (
                    $hargaHpp == 0
                    && (float) $item->qty > 0
                ) {
                    throw new \RuntimeException(
                        'HPP pada salah satu item invoice bernilai 0. '
                        . 'Pembatalan dihentikan agar nilai persediaan tidak salah. '
                        . 'Periksa HPP invoice terlebih dahulu.'
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Stok Masuk
                |--------------------------------------------------------------------------
                */

                $inventoryService->stokMasuk(
                    (int) $item->barang_id,
                    (int) $invoice->gudang_id,
                    (float) $item->qty,
                    $hargaHpp,
                    'PEMBATALAN_INVOICE',
                    (int) $invoice->id,
                    'Restock Pembatalan Invoice '
                        . $invoice->nomor_invoice,
                    'MASUK',
                    now()
                );

                /*
                |--------------------------------------------------------------------------
                | Kembalikan Qty Terinvoice Sales Order
                |--------------------------------------------------------------------------
                */

                if (
                    $invoice->penjualan_id
                ) {
                    $soItem = PenjualanItem::query()
                        ->where(
                            'penjualan_id',
                            $invoice->penjualan_id
                        )
                        ->where(
                            'barang_id',
                            $item->barang_id
                        )
                        ->lockForUpdate()
                        ->first();

                    if ($soItem) {
                        $qtyTerinvoice =
                            (float) (
                                $soItem->qty_terinvoice
                                ?? 0
                            );

                        $jumlahKembali =
                            min(
                                $qtyTerinvoice,
                                (float) $item->qty
                            );

                        if (
                            $jumlahKembali
                            > 0
                        ) {
                            $soItem->decrement(
                                'qty_terinvoice',
                                $jumlahKembali
                            );
                        }
                    }
                }
            }

            /*
            |--------------------------------------------------------------------------
            | 2. Kembalikan Status Sales Order
            |--------------------------------------------------------------------------
            */

            if (
                $invoice->penjualan_id
            ) {
                $so = Penjualan::query()
                    ->whereKey(
                        $invoice->penjualan_id
                    )
                    ->lockForUpdate()
                    ->first();

                if ($so) {
                    $so->load('items');

                    $totalQtyTerinvoice =
                        (float) $so
                            ->items
                            ->sum(
                                'qty_terinvoice'
                            );

                    $so->update([
                        'status' =>
                            $totalQtyTerinvoice > 0
                                ? 'Diproses'
                                : 'Dikonfirmasi',
                    ]);
                }
            }

            /*
            |--------------------------------------------------------------------------
            | 3. Invoice Tunai → Refund Kas/Bank
            |--------------------------------------------------------------------------
            */

            if (
                $invoice->tipe_pembayaran
                === 'Tunai'
                && (float) $invoice->total > 0
            ) {
                if (
                    !$invoice->rekening_kas_bank_id
                ) {
                    throw new \RuntimeException(
                        'Rekening Kas/Bank invoice tunai tidak ditemukan.'
                    );
                }

                $customer = Customer::find(
                    $invoice->customer_id
                );

                $kasBankService->uangKeluar(
                    $invoice->rekening_kas_bank_id,

                    (float) $invoice->total,

                    'PEMBATALAN_INVOICE',

                    $invoice->id,

                    $invoice->nomor_invoice,

                    'Pengembalian Tunai Pembatalan Invoice '
                        . $invoice->nomor_invoice
                        . ' - '
                        . (
                            $customer->nama_customer
                            ?? ''
                        ),

                    now()
                );
            }

            /*
            |--------------------------------------------------------------------------
            | 4. Jurnal Reversal + Status
            |--------------------------------------------------------------------------
            */

            $systemAccountingService
                ->cancelInvoicePenjualan(
                    $invoice,
                    $request->alasan_pembatalan
                );

            /*
            |--------------------------------------------------------------------------
            | Commit
            |--------------------------------------------------------------------------
            */

            DB::commit();

            return redirect()
                ->route(
                    'invoice-penjualan.show',
                    $invoice
                )
                ->with(
                    'success',
                    'Invoice #'
                    . $invoice->nomor_invoice
                    . ' berhasil DIBATALKAN secara resmi. Stok, Sales Order, Kas/Bank, Piutang, dan jurnal telah disesuaikan.'
                );

        } catch (\Throwable $e) {
            DB::rollBack();

            report($e);

            return back()->with(
                'error',
                'Gagal membatalkan invoice: '
                    . $e->getMessage()
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PDF
    |--------------------------------------------------------------------------
    */

    public function pdf(
        Request $request,
        InvoicePenjualan $invoicePenjualan
    ) {
        $invoicePenjualan->load([
            'customer',
            'gudang',
            'creator',
            'items.barang.satuan',
            'rekeningKasBank',
        ]);

        if (
            class_exists(Pdf::class)
        ) {
            $safeNumber =
                str_replace(
                    [
                        '/',
                        '\\',
                    ],
                    '-',
                    $invoicePenjualan
                        ->nomor_invoice
                );

            $pdf = Pdf::loadView(
                'invoice-penjualan.pdf',
                compact(
                    'invoicePenjualan'
                )
            )->setPaper('a5', 'landscape');

            $filename = 'Invoice-' . $safeNumber . '.pdf';

            if ($request->has('download') || $request->input('download') == '1') {
                return $pdf->download($filename);
            }

            return $pdf->stream($filename);
        }

        return view(
            'invoice-penjualan.pdf',
            compact(
                'invoicePenjualan'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | EMAIL
    |--------------------------------------------------------------------------
    */

    public function kirimEmail(
        Request $request,
        InvoicePenjualan $invoicePenjualan
    ) {
        $request->validate([
            'email_tujuan' =>
                'required|email',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Tidak Bisa Kirim Invoice Batal
        |--------------------------------------------------------------------------
        */

        if (
            $invoicePenjualan->status_invoice
            === 'DIBATALKAN'
        ) {
            return back()->with(
                'error',
                'Invoice yang sudah dibatalkan tidak dapat dikirim sebagai invoice aktif.'
            );
        }

        $emailTujuan =
            trim(
                $request->email_tujuan
            );

        try {
            /*
            |--------------------------------------------------------------------------
            | SMTP Check
            |--------------------------------------------------------------------------
            */

            if (
                config('mail.default')
                === 'smtp'
                && empty(
                    config(
                        'mail.mailers.smtp.password'
                    )
                )
            ) {
                return back()->with(
                    'error',
                    'Gagal mengirim email asli: Sandi Aplikasi Google (App Password) belum dimasukkan pada file .env (MAIL_PASSWORD).'
                );
            }

            /*
            |--------------------------------------------------------------------------
            | Load
            |--------------------------------------------------------------------------
            */

            $invoicePenjualan->load([
                'customer',
                'gudang',
                'creator',
                'items.barang.satuan',
                'rekeningKasBank',
            ]);

            /*
            |--------------------------------------------------------------------------
            | Kirim
            |--------------------------------------------------------------------------
            */

            Mail::to(
                $emailTujuan
            )->send(
                new \App\Mail\InvoicePenjualanMail(
                    $invoicePenjualan
                )
            );

            /*
            |--------------------------------------------------------------------------
            | Update Log
            |--------------------------------------------------------------------------
            */

            $invoicePenjualan->update([
                'is_email_sent' =>
                    true,

                'email_sent_at' =>
                    now(),

                'email_sent_to' =>
                    $emailTujuan,
            ]);

            return back()->with(
                'success',
                'Bukti Invoice Penjualan #'
                    . $invoicePenjualan->nomor_invoice
                    . ' berhasil dikirimkan via email ke: '
                    . $emailTujuan
            );

        } catch (\Throwable $e) {
            report($e);

            $msg =
                $e->getMessage();

            if (
                str_contains(
                    $msg,
                    'Authentic'
                )
                || str_contains(
                    $msg,
                    'Password'
                )
                || str_contains(
                    $msg,
                    'Username'
                )
            ) {
                $msg =
                    'Gagal Autentikasi SMTP Gmail. Pastikan Sandi Aplikasi 16-digit Google (App Password) sudah diisi di .env pada MAIL_PASSWORD.';
            }

            return back()->with(
                'error',
                'Gagal mengirimkan email invoice: '
                    . $msg
            );
        }
    }
}