<?php

namespace App\Http\Controllers;

use App\Models\InvoiceSupplier;
use App\Models\PembayaranSupplier;
use App\Models\RekeningKasBank;
use App\Models\RekonsiliasiBank;
use App\Models\TransaksiBelumTeridentifikasi;
use App\Services\KasBankService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class PembayaranSupplierController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | DAFTAR PEMBAYARAN SUPPLIER
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $query = PembayaranSupplier::with([
            'invoiceSupplier',
            'supplier',
            'rekeningKasBank',
            'creator',
        ]);


        /*
        |--------------------------------------------------------------------------
        | SEARCH
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {

            $search =
                $request->search;


            $query->where(
                function ($q) use ($search) {

                    $q->where(
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

                                $supplierQuery
                                    ->where(
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
        | FILTER METODE PEMBAYARAN
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
        | FILTER TANGGAL MULAI
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


        /*
        |--------------------------------------------------------------------------
        | FILTER TANGGAL SELESAI
        |--------------------------------------------------------------------------
        */

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
        | DATA
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
            compact(
                'pembayarans'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | FORM PEMBAYARAN
    |--------------------------------------------------------------------------
    */

    public function create(
        Request $request
    ) {

        /*
        |--------------------------------------------------------------------------
        | INVOICE YANG MASIH PUNYA UTANG
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
                ->get();


        /*
        |--------------------------------------------------------------------------
        | INVOICE TERPILIH
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
        | REKENING KAS / BANK AKTIF
        |--------------------------------------------------------------------------
        */

        $rekeningKasBanks =
            RekeningKasBank::query()
                ->where(
                    'is_active',
                    true
                )
                ->orderBy(
                    'jenis'
                )
                ->orderBy(
                    'nama_rekening'
                )
                ->get();


        /*
        |--------------------------------------------------------------------------
        | PREVIEW NOMOR PEMBAYARAN
        |--------------------------------------------------------------------------
        */

        $previewNomorPembayaran =
            $this
                ->generateNomorPembayaran();


        return view(
            'pembayaran-supplier.create',
            compact(
                'invoices',
                'selectedInvoice',
                'rekeningKasBanks',
                'previewNomorPembayaran'
            )
        );
    }


    /*
    |--------------------------------------------------------------------------
    | SIMPAN + POSTING PEMBAYARAN
    |--------------------------------------------------------------------------
    */

    public function store(
        Request $request,
        KasBankService $kasBankService
    ) {

        /*
        |--------------------------------------------------------------------------
        | VALIDASI
        |--------------------------------------------------------------------------
        */

        $validated =
            $request->validate([

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
                ],
            ]);


        try {

            DB::transaction(
                function () use (
                    $validated,
                    $kasBankService
                ) {

                    /*
                    |--------------------------------------------------------------------------
                    | LOCK INVOICE
                    |--------------------------------------------------------------------------
                    */

                    $invoice =
                        InvoiceSupplier::query()
                            ->where(
                                'id',
                                $validated[
                                    'invoice_supplier_id'
                                ]
                            )
                            ->lockForUpdate()
                            ->firstOrFail();


                    /*
                    |--------------------------------------------------------------------------
                    | VALIDASI INVOICE
                    |--------------------------------------------------------------------------
                    */

                    if (
                        (float)
                        $invoice->sisa_utang
                        <= 0
                    ) {

                        throw ValidationException::withMessages([
                            'invoice_supplier_id' =>
                                'Invoice ini sudah lunas.',
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
                                'Invoice ini tidak dapat menerima pembayaran.',
                        ]);
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | LOCK REKENING
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
                    | VALIDASI REKENING
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


                    $sisaUtangSebelum =
                        round(
                            (float)
                            $invoice->sisa_utang,
                            2
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | CEGAH OVERPAYMENT
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $jumlahBayar
                        >
                        $sisaUtangSebelum
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
                    | BUAT PEMBAYARAN SUPPLIER
                    |--------------------------------------------------------------------------
                    */

                    $pembayaran =
                        PembayaranSupplier::create([

                            'nomor_pembayaran' =>
                                $this
                                    ->generateNomorPembayaran(),

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
                    | POSTING KE MUTASI KAS / BANK
                    |--------------------------------------------------------------------------
                    |
                    | Pembayaran supplier merupakan uang keluar.
                    |
                    | KasBankService:
                    |
                    | - Validasi saldo.
                    | - Mengurangi saldo rekening.
                    | - Membuat MutasiKasBank.
                    | - Menyimpan reference pembayaran.
                    |
                    */

                    $kasBankService
                        ->uangKeluar(

                            rekeningKasBankId:
                                (int)
                                $rekening->id,

                            jumlah:
                                $jumlahBayar,

                            referenceType:
                                'PEMBAYARAN_SUPPLIER',

                            referenceId:
                                (int)
                                $pembayaran->id,

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
                    | UPDATE TOTAL DIBAYAR
                    |--------------------------------------------------------------------------
                    */

                    $totalDibayar =
                        round(
                            (float)
                            $invoice->total_dibayar
                            +
                            $jumlahBayar,
                            2
                        );


                    /*
                    |--------------------------------------------------------------------------
                    | HITUNG SISA UTANG
                    |--------------------------------------------------------------------------
                    */

                    $sisaUtang =
                        round(
                            (float)
                            $invoice->total
                            -
                            $totalDibayar,
                            2
                        );


                    if (
                        $sisaUtang <= 0
                    ) {

                        $sisaUtang = 0;
                    }


                    /*
                    |--------------------------------------------------------------------------
                    | STATUS INVOICE
                    |--------------------------------------------------------------------------
                    */

                    if (
                        $sisaUtang == 0
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


                    /*
                    |--------------------------------------------------------------------------
                    | UPDATE INVOICE
                    |--------------------------------------------------------------------------
                    */

                    $invoice->update([

                        'total_dibayar' =>
                            $totalDibayar,

                        'sisa_utang' =>
                            $sisaUtang,

                        'status' =>
                            $status,
                    ]);
                }
            );

        } catch (
            RuntimeException $e
        ) {

            return back()
                ->withInput()
                ->withErrors([
                    'jumlah_bayar' =>
                        $e->getMessage(),
                ]);
        }


        /*
        |--------------------------------------------------------------------------
        | SELESAI
        |--------------------------------------------------------------------------
        */

        return redirect()
            ->route(
                'pembayaran-supplier.index'
            )
            ->with(
                'success',
                'Pembayaran supplier berhasil dicatat dan saldo Kas/Bank berhasil diperbarui.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | DETAIL PEMBAYARAN
    |--------------------------------------------------------------------------
    */

    public function show(
        PembayaranSupplier $pembayaranSupplier
    ) {

        /*
        |--------------------------------------------------------------------------
        | LOAD DATA UTAMA
        |--------------------------------------------------------------------------
        */

        $pembayaranSupplier->load([
            'invoiceSupplier.purchaseOrder',
            'supplier',
            'rekeningKasBank',
            'mutasiKasBank.rekeningKasBank',
            'creator',
        ]);


        /*
        |--------------------------------------------------------------------------
        | MUTASI KAS / BANK
        |--------------------------------------------------------------------------
        |
        | Akan null jika pembayaran lama belum pernah diposting ke Kas/Bank.
        |
        */

        $mutasiKasBank =
            $pembayaranSupplier
                ->mutasiKasBank;


        /*
        |--------------------------------------------------------------------------
        | REKONSILIASI BANK
        |--------------------------------------------------------------------------
        */

        $rekonsiliasi =
            null;


        /*
        |--------------------------------------------------------------------------
        | TRANSAKSI REKENING KORAN
        |--------------------------------------------------------------------------
        */

        $transaksiBank =
            null;


        /*
        |--------------------------------------------------------------------------
        | JIKA MUTASI KAS/BANK ADA
        |--------------------------------------------------------------------------
        */

        if ($mutasiKasBank) {

            /*
            |--------------------------------------------------------------------------
            | CARI REKONSILIASI
            |--------------------------------------------------------------------------
            */

            $rekonsiliasi =
                RekonsiliasiBank::with([
                    'creator',
                ])
                    ->where(
                        'mutasi_kas_bank_id',
                        $mutasiKasBank->id
                    )
                    ->first();


            /*
            |--------------------------------------------------------------------------
            | CARI TRANSAKSI REKENING KORAN
            |--------------------------------------------------------------------------
            */

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


        /*
        |--------------------------------------------------------------------------
        | STATUS POSTING KAS / BANK
        |--------------------------------------------------------------------------
        */

        $sudahPostingKasBank =
            $mutasiKasBank
            !== null;


        /*
        |--------------------------------------------------------------------------
        | STATUS REKONSILIASI
        |--------------------------------------------------------------------------
        */

        $sudahDirekonsiliasi =
            $rekonsiliasi
            !== null;


        /*
        |--------------------------------------------------------------------------
        | RETURN VIEW
        |--------------------------------------------------------------------------
        */

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
    | DELETE
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
                'Pembayaran tidak dapat dihapus langsung. Gunakan mekanisme pembatalan/reversal.'
            );
    }


    /*
    |--------------------------------------------------------------------------
    | KETERANGAN MUTASI KAS / BANK
    |--------------------------------------------------------------------------
    */

    private function buildKeteranganMutasi(
        PembayaranSupplier $pembayaran,
        InvoiceSupplier $invoice
    ): string {

        $parts = [];


        /*
        |--------------------------------------------------------------------------
        | NOMOR PEMBAYARAN
        |--------------------------------------------------------------------------
        */

        $parts[] =
            'Pembayaran Supplier '
            . $pembayaran
                ->nomor_pembayaran;


        /*
        |--------------------------------------------------------------------------
        | NOMOR INVOICE
        |--------------------------------------------------------------------------
        */

        if (
            $invoice
                ->nomor_invoice_internal
        ) {

            $parts[] =
                'Invoice '
                . $invoice
                    ->nomor_invoice_internal;
        }


        /*
        |--------------------------------------------------------------------------
        | REFERENSI
        |--------------------------------------------------------------------------
        */

        if (
            $pembayaran
                ->referensi
        ) {

            $parts[] =
                'Referensi '
                . $pembayaran
                    ->referensi;
        }


        /*
        |--------------------------------------------------------------------------
        | CATATAN
        |--------------------------------------------------------------------------
        */

        if (
            $pembayaran
                ->catatan
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


    /*
    |--------------------------------------------------------------------------
    | GENERATE NOMOR PEMBAYARAN
    |--------------------------------------------------------------------------
    |
    | Contoh:
    |
    | PAY-SUP/2026/08/0001
    |
    */

    private function generateNomorPembayaran(): string
    {
        $year =
            now()->format(
                'Y'
            );


        $month =
            now()->format(
                'm'
            );


        $prefix =
            "PAY-SUP/{$year}/{$month}/";


        /*
        |--------------------------------------------------------------------------
        | NOMOR TERAKHIR
        |--------------------------------------------------------------------------
        */

        $last =
            PembayaranSupplier::query()
                ->where(
                    'nomor_pembayaran',
                    'like',
                    $prefix . '%'
                )
                ->orderByDesc(
                    'id'
                )
                ->first();


        /*
        |--------------------------------------------------------------------------
        | NOMOR BERIKUTNYA
        |--------------------------------------------------------------------------
        */

        if ($last) {

            $lastNumber =
                (int)
                substr(
                    $last
                        ->nomor_pembayaran,
                    -4
                );


            $nextNumber =
                $lastNumber + 1;

        } else {

            $nextNumber =
                1;
        }


        /*
        |--------------------------------------------------------------------------
        | HASIL
        |--------------------------------------------------------------------------
        */

        return
            $prefix
            .
            str_pad(
                $nextNumber,
                4,
                '0',
                STR_PAD_LEFT
            );
    }
}