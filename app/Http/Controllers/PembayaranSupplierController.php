<?php

namespace App\Http\Controllers;

use App\Models\InvoiceSupplier;
use App\Models\PembayaranSupplier;
use App\Models\RekeningKasBank;
use App\Models\RekonsiliasiBank;
use App\Models\TransaksiBelumTeridentifikasi;
use App\Services\KasBankService;
use App\Services\SystemAccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class PembayaranSupplierController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $query =
            PembayaranSupplier::with([
                'invoiceSupplier',
                'supplier',
                'rekeningKasBank',
                'creator',
            ]);

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {
            $search =
                trim($request->search);

            $query->where(
                function ($q) use ($search) {
                    $q
                        ->where(
                            'nomor_pembayaran',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhere(
                            'referensi',
                            'like',
                            "%{$search}%"
                        )
                        ->orWhereHas(
                            'invoiceSupplier',
                            function ($invoiceQuery) use ($search) {
                                $invoiceQuery
                                    ->where(
                                        'nomor_invoice_internal',
                                        'like',
                                        "%{$search}%"
                                    )
                                    ->orWhere(
                                        'nomor_invoice_supplier',
                                        'like',
                                        "%{$search}%"
                                    );
                            }
                        )
                        ->orWhereHas(
                            'supplier',
                            function ($supplierQuery) use ($search) {
                                $supplierQuery->where(
                                    'nama_supplier',
                                    'like',
                                    "%{$search}%"
                                );
                            }
                        )
                        ->orWhereHas(
                            'rekeningKasBank',
                            function ($rekeningQuery) use ($search) {
                                $rekeningQuery
                                    ->where(
                                        'nama_rekening',
                                        'like',
                                        "%{$search}%"
                                    )
                                    ->orWhere(
                                        'nama_bank',
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
        | Filter Metode
        |--------------------------------------------------------------------------
        */

        if (
            $request->filled(
                'metode_pembayaran'
            )
        ) {
            $query->where(
                'metode_pembayaran',
                $request->metode_pembayaran
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Filter Tanggal
        |--------------------------------------------------------------------------
        */

        if (
            $request->filled(
                'tanggal_mulai'
            )
        ) {
            $query->whereDate(
                'tanggal_pembayaran',
                '>=',
                $request->tanggal_mulai
            );
        }

        if (
            $request->filled(
                'tanggal_selesai'
            )
        ) {
            $query->whereDate(
                'tanggal_pembayaran',
                '<=',
                $request->tanggal_selesai
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Pagination
        |--------------------------------------------------------------------------
        */

        $pembayarans =
            $query
                ->latest(
                    'tanggal_pembayaran'
                )
                ->latest('id')
                ->paginate(15)
                ->withQueryString();

        return view(
            'pembayaran-supplier.index',
            compact('pembayarans')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE
    |--------------------------------------------------------------------------
    */

    public function create(
        Request $request,
        SystemAccountingService $accountingService
    ) {
        /*
        |--------------------------------------------------------------------------
        | Invoice Yang Masih Memiliki Utang
        |--------------------------------------------------------------------------
        */

        $invoices =
            InvoiceSupplier::with([
                'supplier',
                'purchaseOrder',
            ])
                ->where(
                    'sisa_utang',
                    '>',
                    0
                )
                ->whereIn(
                    'status',
                    [
                        'BELUM_LUNAS',
                        'SEBAGIAN',
                    ]
                )
                ->orderBy(
                    'tanggal_jatuh_tempo'
                )
                ->orderBy('id')
                ->get();

        /*
        |--------------------------------------------------------------------------
        | Selected Invoice
        |--------------------------------------------------------------------------
        */

        $selectedInvoice =
            null;

        if (
            $request->filled(
                'invoice_supplier_id'
            )
        ) {
            $selectedInvoice =
                InvoiceSupplier::with([
                    'supplier',
                    'purchaseOrder',
                    'pembayaranSuppliers',
                ])
                    ->where(
                        'sisa_utang',
                        '>',
                        0
                    )
                    ->whereIn(
                        'status',
                        [
                            'BELUM_LUNAS',
                            'SEBAGIAN',
                        ]
                    )
                    ->find(
                        $request
                            ->invoice_supplier_id
                    );
        }

        /*
        |--------------------------------------------------------------------------
        | Rekening Aktif
        |--------------------------------------------------------------------------
        */

        $rekeningKasBanks =
            RekeningKasBank::query()
                ->where(
                    'is_active',
                    true
                )
                ->orderBy('jenis')
                ->orderBy(
                    'nama_rekening'
                )
                ->get();

        /*
        |--------------------------------------------------------------------------
        | Preview Nomor
        |--------------------------------------------------------------------------
        */

        $previewNomorPembayaran =
            $accountingService
                ->generateDocumentNumber(
                    'PEMBAYARAN_SUPPLIER'
                );

        // Token unik untuk mencegah satu form yang sama diproses dua kali.
        $idempotencyKey = (string) Str::uuid();

        return view(
            'pembayaran-supplier.create',
            compact(
                'invoices',
                'selectedInvoice',
                'rekeningKasBanks',
                'previewNomorPembayaran',
                'idempotencyKey'
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
        SystemAccountingService $accountingService
    ) {
        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        $validated =
            $request->validate([
                'idempotency_key' => [
                    'required',
                    'uuid',
                ],

                'invoice_supplier_id' => [
                    'required',
                    'exists:invoice_suppliers,id',
                ],

                'tanggal_pembayaran' => [
                    'required',
                    'date',
                ],

                'jumlah_bayar' => [
                    'required',
                    'numeric',
                    'decimal:0,2',
                    'min:0.01',
                ],

                'metode_pembayaran' => [
                    'required',
                    'in:TRANSFER,TUNAI,GIRO,LAINNYA',
                ],

                'rekening_kas_bank_id' => [
                    'required',
                    'exists:rekening_kas_banks,id',
                ],

                'referensi' => [
                    'nullable',
                    'string',
                    'max:255',
                ],

                'catatan' => [
                    'nullable',
                    'string',
                    'max:2000',
                ],
            ]);

        // Fast path untuk retry setelah request pertama berhasil.
        // Pemeriksaan yang sama tetap diulang di dalam transaksi untuk menangani race condition.
        $existingPayment = PembayaranSupplier::query()
            ->where('idempotency_key', $validated['idempotency_key'])
            ->first();

        if ($existingPayment) {
            if (!$this->matchesIdempotencyPayload($existingPayment, $validated, auth()->id())) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'idempotency_key' => 'Token formulir ini sudah digunakan untuk data pembayaran yang berbeda. Muat ulang formulir sebelum mencoba lagi.',
                    ]);
            }

            return redirect()
                ->route('pembayaran-supplier.show', $existingPayment)
                ->with('success', 'Permintaan pembayaran ini sudah diproses sebelumnya. Tidak ada pembayaran, mutasi, atau jurnal baru yang dibuat.');
        }

        /*
        |--------------------------------------------------------------------------
        | Cut-Off
        |--------------------------------------------------------------------------
        */

        if (
            $accountingService
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
                    'Pembayaran supplier tidak dapat diproses karena periode accounting pada tanggal tersebut sudah DITUTUP.'
                );
        }

        $alreadyProcessed = false;

        try {
            $pembayaran =
                DB::transaction(
                    function () use (
                        &$alreadyProcessed,
                        $validated,
                        $kasBankService,
                        $accountingService
                    ) {
                        /*
                        |--------------------------------------------------------------------------
                        | Lock Invoice
                        |--------------------------------------------------------------------------
                        */

                        $invoice =
                            InvoiceSupplier::query()
                                ->with('supplier')
                                ->where(
                                    'id',
                                    $validated[
                                        'invoice_supplier_id'
                                    ]
                                )
                                ->lockForUpdate()
                                ->firstOrFail();

                        // Cek ulang setelah lock invoice agar request paralel dengan token
                        // yang sama tidak membuat pembayaran kedua.
                        $existingPayment = PembayaranSupplier::query()
                            ->where('idempotency_key', $validated['idempotency_key'])
                            ->lockForUpdate()
                            ->first();

                        if ($existingPayment) {
                            if (!$this->matchesIdempotencyPayload($existingPayment, $validated, auth()->id())) {
                                throw ValidationException::withMessages([
                                    'idempotency_key' => 'Token formulir ini sudah digunakan untuk data pembayaran yang berbeda. Muat ulang formulir sebelum mencoba lagi.',
                                ]);
                            }

                            $alreadyProcessed = true;
                            return $existingPayment;
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Validasi Invoice
                        |--------------------------------------------------------------------------
                        */

                        if (
                            (float) $invoice
                                ->sisa_utang
                            <= 0
                        ) {
                            throw ValidationException::withMessages([
                                'invoice_supplier_id' =>
                                    'Invoice Supplier ini sudah lunas.',
                            ]);
                        }

                        if (
                            !in_array(
                                $invoice->status,
                                [
                                    'BELUM_LUNAS',
                                    'SEBAGIAN',
                                ],
                                true
                            )
                        ) {
                            throw ValidationException::withMessages([
                                'invoice_supplier_id' =>
                                    'Invoice Supplier ini tidak dapat menerima pembayaran.',
                            ]);
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Tanggal Pembayaran Tidak Boleh Sebelum Invoice
                        |--------------------------------------------------------------------------
                        */

                        if (
                            \Carbon\Carbon::parse(
                                $validated[
                                    'tanggal_pembayaran'
                                ]
                            )->lt(
                                \Carbon\Carbon::parse(
                                    $invoice
                                        ->tanggal_invoice
                                )
                            )
                        ) {
                            throw ValidationException::withMessages([
                                'tanggal_pembayaran' =>
                                    'Tanggal pembayaran tidak boleh sebelum tanggal Invoice Supplier.',
                            ]);
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Lock Rekening
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
                                ->lockForUpdate()
                                ->firstOrFail();

                        /*
                        |--------------------------------------------------------------------------
                        | Validasi Rekening
                        |--------------------------------------------------------------------------
                        */

                        if (
                            !$rekening->is_active
                        ) {
                            throw ValidationException::withMessages([
                                'rekening_kas_bank_id' =>
                                    'Kas/Rekening Bank yang dipilih sudah tidak aktif.',
                            ]);
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Rekening Harus Terhubung Ke COA
                        |--------------------------------------------------------------------------
                        |
                        | Jangan fallback ke Kas Utama karena apabila user memilih
                        | BCA, jurnal harus benar-benar masuk ke akun Bank BCA.
                        |
                        */

                        if (
                            !$rekening->akun_id
                        ) {
                            throw ValidationException::withMessages([
                                'rekening_kas_bank_id' =>
                                    'Kas/Rekening Bank yang dipilih belum terhubung dengan akun COA. Atur akun Kas/Bank terlebih dahulu.',
                            ]);
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Jumlah Bayar
                        |--------------------------------------------------------------------------
                        */

                        $jumlahBayar =
                            round(
                                (float) $validated[
                                    'jumlah_bayar'
                                ],
                                2
                            );

                        $sisaUtangSebelum =
                            round(
                                (float) $invoice
                                    ->sisa_utang,
                                2
                            );

                        /*
                        |--------------------------------------------------------------------------
                        | Overpayment
                        |--------------------------------------------------------------------------
                        */

                        if (
                            $jumlahBayar
                            > $sisaUtangSebelum
                        ) {
                            throw ValidationException::withMessages([
                                'jumlah_bayar' =>
                                    'Jumlah pembayaran tidak boleh melebihi sisa utang sebesar Rp '
                                    . number_format(
                                        $sisaUtangSebelum,
                                        0,
                                        ',',
                                        '.'
                                    )
                                    . '.',
                            ]);
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Nomor Pembayaran
                        |--------------------------------------------------------------------------
                        */

                        $nomorPembayaran =
                            $accountingService
                                ->generateDocumentNumber(
                                    'PEMBAYARAN_SUPPLIER',
                                    $validated[
                                        'tanggal_pembayaran'
                                    ]
                                );

                        /*
                        |--------------------------------------------------------------------------
                        | Safety Duplikat Nomor
                        |--------------------------------------------------------------------------
                        */

                        if (
                            PembayaranSupplier::query()
                                ->where(
                                    'nomor_pembayaran',
                                    $nomorPembayaran
                                )
                                ->exists()
                        ) {
                            throw ValidationException::withMessages([
                                'nomor_pembayaran' =>
                                    'Nomor pembayaran supplier sudah digunakan. Silakan simpan kembali.',
                            ]);
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Buat Pembayaran
                        |--------------------------------------------------------------------------
                        */

                        $pembayaran =
                            PembayaranSupplier::create([
                                'idempotency_key' =>
                                    $validated['idempotency_key'],

                                'nomor_pembayaran' =>
                                    $nomorPembayaran,

                                'invoice_supplier_id' =>
                                    $invoice->id,

                                'supplier_id' =>
                                    $invoice->supplier_id,

                                'tanggal_pembayaran' =>
                                    $validated[
                                        'tanggal_pembayaran'
                                    ],

                                'jumlah_bayar' =>
                                    $jumlahBayar,

                                'metode_pembayaran' =>
                                    $validated[
                                        'metode_pembayaran'
                                    ],

                                'rekening_kas_bank_id' =>
                                    $rekening->id,

                                'referensi' =>
                                    $validated[
                                        'referensi'
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
                        | Kas / Bank Keluar
                        |--------------------------------------------------------------------------
                        */

                        $kasBankService
                            ->uangKeluar(
                                rekeningKasBankId:
                                    (int) $rekening->id,

                                jumlah:
                                    $jumlahBayar,

                                referenceType:
                                    'PEMBAYARAN_SUPPLIER',

                                referenceId:
                                    (int) $pembayaran->id,

                                nomorReferensi:
                                    $pembayaran
                                        ->nomor_pembayaran,

                                keterangan:
                                    $this
                                        ->buildKeteranganMutasi(
                                            $pembayaran,
                                            $invoice
                                        ),

                                tanggalTransaksi:
                                    $validated[
                                        'tanggal_pembayaran'
                                    ],

                                tipe:
                                    'PEMBAYARAN_SUPPLIER'
                            );

                        /*
                        |--------------------------------------------------------------------------
                        | Update Invoice Supplier
                        |--------------------------------------------------------------------------
                        */

                        $totalDibayar =
                            round(
                                (float) $invoice
                                    ->total_dibayar
                                + $jumlahBayar,
                                2
                            );

                        /*
                        | Safety:
                        | total dibayar tidak boleh melebihi total.
                        */

                        if (
                            $totalDibayar
                            > (float) $invoice->total
                            + 0.01
                        ) {
                            throw ValidationException::withMessages([
                                'jumlah_bayar' =>
                                    'Total pembayaran melebihi total Invoice Supplier.',
                            ]);
                        }

                        $sisaUtang =
                            round(
                                (float) $invoice->total
                                - $totalDibayar,
                                2
                            );

                        if (
                            abs($sisaUtang)
                            < 0.01
                        ) {
                            $sisaUtang =
                                0;
                        }

                        if ($sisaUtang < 0) {
                            throw ValidationException::withMessages([
                                'jumlah_bayar' =>
                                    'Sisa utang menjadi negatif. Transaksi dihentikan.',
                            ]);
                        }

                        /*
                        |--------------------------------------------------------------------------
                        | Status
                        |--------------------------------------------------------------------------
                        */

                        if (
                            $sisaUtang <= 0
                        ) {
                            $status =
                                'LUNAS';

                        } elseif (
                            $totalDibayar > 0
                        ) {
                            $status =
                                'SEBAGIAN';

                        } else {
                            $status =
                                'BELUM_LUNAS';
                        }

                        $invoice->update([
                            'total_dibayar' =>
                                $totalDibayar,

                            'sisa_utang' =>
                                $sisaUtang,

                            'status' =>
                                $status,
                        ]);

                        /*
                        |--------------------------------------------------------------------------
                        | Journal
                        |--------------------------------------------------------------------------
                        |
                        | Dr Utang Dagang
                        |     Cr Kas / Bank
                        |
                        */

                        $accountingService
                            ->autoJournalPembayaranSupplier(
                                $pembayaran
                            );

                        return $pembayaran;
                    }
                );

        } catch (
            ValidationException $e
        ) {
            throw $e;

        } catch (\Illuminate\Database\QueryException $e) {
            report($e);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Gagal mencatat pembayaran supplier karena terjadi masalah pada penyimpanan data. Periksa data dan coba lagi.'
                );

        } catch (RuntimeException $e) {
            // Pesan KasBankService/SystemAccountingService untuk aturan bisnis.
            return back()
                ->withInput()
                ->with('error', $e->getMessage());

        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Gagal mencatat pembayaran supplier. Periksa kembali data dan coba lagi. Jika masalah berlanjut, hubungi administrator.'
                );
        }

        return redirect()
            ->route(
                'pembayaran-supplier.show',
                $pembayaran
            )
            ->with(
                'success',
                $alreadyProcessed
                    ? 'Permintaan pembayaran ini sudah diproses sebelumnya. Tidak ada pembayaran, mutasi, atau jurnal baru yang dibuat.'
                    : 'Pembayaran supplier berhasil dicatat. Utang, saldo Kas/Bank, dan jurnal accounting telah diperbarui.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW
    |--------------------------------------------------------------------------
    */

    public function show(
        PembayaranSupplier $pembayaranSupplier
    ) {
        $pembayaranSupplier->load([
            'invoiceSupplier.purchaseOrder',
            'supplier',
            'rekeningKasBank',
            'mutasiKasBank.rekeningKasBank',
            'creator',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Mutasi Kas/Bank
        |--------------------------------------------------------------------------
        */

        $mutasiKasBank =
            $pembayaranSupplier
                ->mutasiKasBank;

        /*
        |--------------------------------------------------------------------------
        | Rekonsiliasi
        |--------------------------------------------------------------------------
        */

        $rekonsiliasi =
            null;

        $transaksiBank =
            null;

        if ($mutasiKasBank) {
            $rekonsiliasi =
                RekonsiliasiBank::with([
                    'creator',
                ])
                    ->where(
                        'mutasi_kas_bank_id',
                        $mutasiKasBank->id
                    )
                    ->first();

            if ($rekonsiliasi) {
                $transaksiBank =
                    TransaksiBelumTeridentifikasi::with([
                        'rekeningKasBank',
                        'identifier',
                    ])
                        ->find(
                            $rekonsiliasi
                                ->transaksi_bank_id
                        );
            }
        }

        $sudahPostingKasBank =
            $mutasiKasBank !== null;

        $sudahDirekonsiliasi =
            $rekonsiliasi !== null;

        return view(
            'pembayaran-supplier.show',
            compact(
                'pembayaranSupplier',
                'mutasiKasBank',
                'rekonsiliasi',
                'transaksiBank',
                'sudahPostingKasBank',
                'sudahDirekonsiliasi'
            )
        );
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT
    |--------------------------------------------------------------------------
    */

    public function edit(
        PembayaranSupplier $pembayaranSupplier
    ) {
        return redirect()
            ->route(
                'pembayaran-supplier.show',
                $pembayaranSupplier
            )
            ->with(
                'error',
                'Pembayaran yang sudah diproses tidak dapat diedit langsung.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        PembayaranSupplier $pembayaranSupplier
    ) {
        return redirect()
            ->route(
                'pembayaran-supplier.show',
                $pembayaranSupplier
            )
            ->with(
                'error',
                'Pembayaran yang sudah diproses tidak dapat diedit langsung.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | DESTROY
    |--------------------------------------------------------------------------
    */

    public function destroy(
        PembayaranSupplier $pembayaranSupplier
    ) {
        return redirect()
            ->route(
                'pembayaran-supplier.show',
                $pembayaranSupplier
            )
            ->with(
                'error',
                'Pembayaran Supplier tidak dapat dihapus langsung karena sudah memengaruhi Utang, Kas/Bank, dan jurnal. Gunakan mekanisme reversal.'
            );
    }

    /**
     * Memastikan token idempotensi hanya dapat mengulang payload pembayaran yang sama.
     */
    private function matchesIdempotencyPayload(
        PembayaranSupplier $payment,
        array $validated,
        ?int $userId
    ): bool {
        $normalizeText = static fn ($value): string => trim((string) ($value ?? ''));

        return (int) $payment->invoice_supplier_id === (int) $validated['invoice_supplier_id']
            && Carbon::parse($payment->tanggal_pembayaran)->toDateString() === Carbon::parse($validated['tanggal_pembayaran'])->toDateString()
            && round((float) $payment->jumlah_bayar, 2) === round((float) $validated['jumlah_bayar'], 2)
            && (string) $payment->metode_pembayaran === (string) $validated['metode_pembayaran']
            && (int) $payment->rekening_kas_bank_id === (int) $validated['rekening_kas_bank_id']
            && $normalizeText($payment->referensi) === $normalizeText($validated['referensi'] ?? null)
            && $normalizeText($payment->catatan) === $normalizeText($validated['catatan'] ?? null)
            && (int) ($payment->created_by ?? 0) === (int) ($userId ?? 0);
    }

    /*
    |--------------------------------------------------------------------------
    | BUILD KETERANGAN MUTASI
    |--------------------------------------------------------------------------
    */

    private function buildKeteranganMutasi(
        PembayaranSupplier $pembayaran,
        InvoiceSupplier $invoice
    ): string {
        $parts = [];

        $parts[] =
            'Pembayaran Supplier '
            . $pembayaran
                ->nomor_pembayaran;

        if (
            $invoice
                ->nomor_invoice_internal
        ) {
            $parts[] =
                'Invoice '
                . $invoice
                    ->nomor_invoice_internal;
        }

        if (
            $pembayaran
                ->supplier
                ?->nama_supplier
        ) {
            $parts[] =
                'Supplier '
                . $pembayaran
                    ->supplier
                    ->nama_supplier;
        }

        if (
            $pembayaran->referensi
        ) {
            $parts[] =
                'Referensi '
                . $pembayaran
                    ->referensi;
        }

        if (
            $pembayaran->catatan
        ) {
            $parts[] =
                $pembayaran
                    ->catatan;
        }

        return implode(
            ' | ',
            $parts
        );
    }
}