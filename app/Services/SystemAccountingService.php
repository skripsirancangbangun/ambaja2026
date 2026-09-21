<?php

namespace App\Services;

use App\Models\Akun;
use App\Models\InvoicePenjualan;
use App\Models\InvoiceSupplier;
use App\Models\Jurnal;
use App\Models\JurnalDetail;
use App\Models\KartuPiutang;
use App\Models\MutasiKasBank;
use App\Models\PenerimaanPiutang;
use App\Models\PembayaranSupplier;
use App\Models\PengaturanAkun;
use App\Models\PenutupanPeriode;
use App\Models\ReturPembelian;
use App\Models\ReturPenjualan;
use App\Models\UangKeluar;
use Carbon\Carbon;
use RuntimeException;

class SystemAccountingService
{
    /*
    |--------------------------------------------------------------------------
    | GENERAL
    |--------------------------------------------------------------------------
    */

    public function canEditOpeningBalance(): bool
    {
        $countPenjualan =
            InvoicePenjualan::count();

        $countPembelian =
            InvoiceSupplier::count();

        $countKas =
            MutasiKasBank::count();

        $countJurnalManual =
            Jurnal::query()
                ->where(
                    'is_system_generated',
                    false
                )
                ->count();

        return (
            $countPenjualan === 0
            &&
            $countPembelian === 0
            &&
            $countKas === 0
            &&
            $countJurnalManual === 0
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PERIOD LOCK
    |--------------------------------------------------------------------------
    */

    public function isPeriodLocked(
        $tanggal
    ): bool {
        if (!$tanggal) {
            return false;
        }

        $tanggal =
            Carbon::parse(
                $tanggal
            );

        $penutupan =
            PenutupanPeriode::query()
                ->where(
                    'bulan',
                    $tanggal->month
                )
                ->where(
                    'tahun',
                    $tanggal->year
                )
                ->first();

        return (
            $penutupan
            &&
            $penutupan->status === 'LOCKED'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | GET SYSTEM ACCOUNT
    |--------------------------------------------------------------------------
    */

    public function getSystemAccountId(
        string $kunciPengaturan
    ): ?int {
        return PengaturanAkun::query()
            ->where(
                'kunci_pengaturan',
                $kunciPengaturan
            )
            ->value('akun_id');
    }

    /*
    |--------------------------------------------------------------------------
    | GET UANG MUKA PEMBELIAN ACCOUNT
    |--------------------------------------------------------------------------
    */

    private function getUangMukaPembelianAccountId(): ?int
    {
        /*
        |--------------------------------------------------------------------------
        | Prioritas Pengaturan Akun
        |--------------------------------------------------------------------------
        */

        $akunId =
            $this->getSystemAccountId(
                'AKUN_UANG_MUKA_PEMBELIAN'
            );

        if ($akunId) {
            return (int) $akunId;
        }

        /*
        |--------------------------------------------------------------------------
        | Fallback COA 1140
        |--------------------------------------------------------------------------
        */

        $akunId =
            Akun::query()
                ->where(
                    'kode_akun',
                    '1140'
                )
                ->value('id');

        if ($akunId) {
            return (int) $akunId;
        }

        /*
        |--------------------------------------------------------------------------
        | Fallback Nama
        |--------------------------------------------------------------------------
        */

        $akunId =
            Akun::query()
                ->where(
                    'nama_akun',
                    'like',
                    '%Uang Muka Pembelian%'
                )
                ->value('id');

        return $akunId
            ? (int) $akunId
            : null;
    }

    /*
    |--------------------------------------------------------------------------
    | GENERATE DOCUMENT NUMBER
    |--------------------------------------------------------------------------
    */

    public function generateDocumentNumber(
        string $jenisDokumen,
        $tanggal = null
    ): string {
        $date =
            $tanggal
                ? Carbon::parse($tanggal)
                : Carbon::now();

        $year =
            $date->format('Y');

        $month =
            $date->format('m');

        $key =
            strtoupper(
                trim(
                    $jenisDokumen
                )
            );

        $prefixMap = [

            /*
            |--------------------------------------------------------------------------
            | SALES ORDER
            |--------------------------------------------------------------------------
            */

            'SO' => [
                'prefix' => 'SO',
                'model' => \App\Models\Penjualan::class,
                'date_col' => 'tanggal_penjualan',
            ],

            'SALES_ORDER' => [
                'prefix' => 'SO',
                'model' => \App\Models\Penjualan::class,
                'date_col' => 'tanggal_penjualan',
            ],

            'PESANAN_PENJUALAN' => [
                'prefix' => 'SO',
                'model' => \App\Models\Penjualan::class,
                'date_col' => 'tanggal_penjualan',
            ],

            /*
            |--------------------------------------------------------------------------
            | INVOICE PENJUALAN
            |--------------------------------------------------------------------------
            */

            'INV' => [
                'prefix' => 'INV',
                'model' => InvoicePenjualan::class,
                'date_col' => 'tanggal_invoice',
            ],

            'INVOICE_PENJUALAN' => [
                'prefix' => 'INV',
                'model' => InvoicePenjualan::class,
                'date_col' => 'tanggal_invoice',
            ],

            /*
            |--------------------------------------------------------------------------
            | PENERIMAAN PIUTANG
            |--------------------------------------------------------------------------
            */

            'PAY' => [
                'prefix' => 'PAY',
                'model' => PenerimaanPiutang::class,
                'date_col' => 'tanggal',
            ],

            'PENERIMAAN_PIUTANG' => [
                'prefix' => 'PAY',
                'model' => PenerimaanPiutang::class,
                'date_col' => 'tanggal',
            ],

            'PEMBAYARAN_PENJUALAN' => [
                'prefix' => 'PAY',
                'model' => PenerimaanPiutang::class,
                'date_col' => 'tanggal',
            ],

            'KWITANSI' => [
                'prefix' => 'PAY',
                'model' => PenerimaanPiutang::class,
                'date_col' => 'tanggal',
            ],

            /*
            |--------------------------------------------------------------------------
            | RETUR PENJUALAN
            |--------------------------------------------------------------------------
            */

            'RTJ' => [
                'prefix' => 'RTJ',
                'model' => ReturPenjualan::class,
                'date_col' => 'tanggal_retur',
            ],

            'RETUR_PENJUALAN' => [
                'prefix' => 'RTJ',
                'model' => ReturPenjualan::class,
                'date_col' => 'tanggal_retur',
            ],

            /*
            |--------------------------------------------------------------------------
            | PURCHASE ORDER
            |--------------------------------------------------------------------------
            */

            'PO' => [
                'prefix' => 'PO',
                'model' => \App\Models\PurchaseOrder::class,
                'date_col' => 'tanggal_po',
            ],

            'PURCHASE_ORDER' => [
                'prefix' => 'PO',
                'model' => \App\Models\PurchaseOrder::class,
                'date_col' => 'tanggal_po',
            ],

            'PESANAN_PEMBELIAN' => [
                'prefix' => 'PO',
                'model' => \App\Models\PurchaseOrder::class,
                'date_col' => 'tanggal_po',
            ],

            /*
            |--------------------------------------------------------------------------
            | PENERIMAAN BARANG
            |--------------------------------------------------------------------------
            */

            'GRN' => [
                'prefix' => 'GRN',
                'model' => \App\Models\PenerimaanBarang::class,
                'date_col' => 'tanggal_penerimaan',
            ],

            'PENERIMAAN_BARANG' => [
                'prefix' => 'GRN',
                'model' => \App\Models\PenerimaanBarang::class,
                'date_col' => 'tanggal_penerimaan',
            ],

            /*
            |--------------------------------------------------------------------------
            | INVOICE SUPPLIER
            |--------------------------------------------------------------------------
            */

            'INV-SUP' => [
                'prefix' => 'INV-SUP',
                'model' => InvoiceSupplier::class,
                'date_col' => 'tanggal_invoice',
            ],

            'INVOICE_SUPPLIER' => [
                'prefix' => 'INV-SUP',
                'model' => InvoiceSupplier::class,
                'date_col' => 'tanggal_invoice',
            ],

            'INVOICE_PEMBELIAN' => [
                'prefix' => 'INV-SUP',
                'model' => InvoiceSupplier::class,
                'date_col' => 'tanggal_invoice',
            ],

            /*
            |--------------------------------------------------------------------------
            | PEMBAYARAN SUPPLIER
            |--------------------------------------------------------------------------
            */

            'PAY-SUP' => [
                'prefix' => 'PAY-SUP',
                'model' => PembayaranSupplier::class,
                'date_col' => 'tanggal_pembayaran',
            ],

            'PEMBAYARAN_SUPPLIER' => [
                'prefix' => 'PAY-SUP',
                'model' => PembayaranSupplier::class,
                'date_col' => 'tanggal_pembayaran',
            ],

            'PEMBAYARAN_UTANG' => [
                'prefix' => 'PAY-SUP',
                'model' => PembayaranSupplier::class,
                'date_col' => 'tanggal_pembayaran',
            ],

            /*
            |--------------------------------------------------------------------------
            | KAS MASUK
            |--------------------------------------------------------------------------
            */

            'KM' => [
                'prefix' => 'KM',
                'model' => MutasiKasBank::class,
                'date_col' => 'tanggal',
                'where' => [
                    'tipe_mutasi' => 'MASUK',
                ],
            ],

            'UANG_MASUK' => [
                'prefix' => 'KM',
                'model' => MutasiKasBank::class,
                'date_col' => 'tanggal',
                'where' => [
                    'tipe_mutasi' => 'MASUK',
                ],
            ],

            'KAS_MASUK' => [
                'prefix' => 'KM',
                'model' => MutasiKasBank::class,
                'date_col' => 'tanggal',
                'where' => [
                    'tipe_mutasi' => 'MASUK',
                ],
            ],

            /*
            |--------------------------------------------------------------------------
            | KAS KELUAR
            |--------------------------------------------------------------------------
            */

            'KK' => [
                'prefix' => 'KK',
                'model' => UangKeluar::class,
                'date_col' => 'tanggal',
            ],

            'UANG_KELUAR' => [
                'prefix' => 'KK',
                'model' => UangKeluar::class,
                'date_col' => 'tanggal',
            ],

            'KAS_KELUAR' => [
                'prefix' => 'KK',
                'model' => UangKeluar::class,
                'date_col' => 'tanggal',
            ],

            /*
            |--------------------------------------------------------------------------
            | JURNAL
            |--------------------------------------------------------------------------
            */

            'JU' => [
                'prefix' => 'JU',
                'model' => Jurnal::class,
                'date_col' => 'tanggal',
            ],

            'JURNAL_UMUM' => [
                'prefix' => 'JU',
                'model' => Jurnal::class,
                'date_col' => 'tanggal',
            ],
        ];

        if (!isset($prefixMap[$key])) {
            return sprintf(
                '%s/%s/%s/0001',
                $key,
                $year,
                $month
            );
        }

        $config =
            $prefixMap[$key];

        $query =
            $config['model']::query()
                ->whereYear(
                    $config['date_col'],
                    $year
                )
                ->whereMonth(
                    $config['date_col'],
                    $month
                );

        if (
            isset(
                $config['where']
            )
        ) {
            foreach (
                $config['where']
                as $column => $value
            ) {
                $query->where(
                    $column,
                    $value
                );
            }
        }

        $nextSeq =
            $query->count()
            + 1;

        return sprintf(
            '%s/%s/%s/%04d',
            $config['prefix'],
            $year,
            $month,
            $nextSeq
        );
    }

    /*
    |--------------------------------------------------------------------------
    | INVOICE PENJUALAN
    |--------------------------------------------------------------------------
    */

    public function autoJournalInvoicePenjualan(
        InvoicePenjualan $invoice
    ): void {
        if (
            $this->isPeriodLocked(
                $invoice->tanggal_invoice
            )
        ) {
            throw new RuntimeException(
                'Periode accounting untuk tanggal invoice sudah ditutup.'
            );
        }

        if (
            $invoice->status_invoice
            === 'DIBATALKAN'
        ) {
            throw new RuntimeException(
                'Invoice yang sudah dibatalkan tidak dapat diposting kembali.'
            );
        }

        $invoice->loadMissing([
            'customer',
            'items',
            'rekeningKasBank.akun',
        ]);

        if (
            $invoice->tipe_pembayaran
            === 'Tunai'
        ) {
            $akunDebitId =
                $invoice
                    ->rekeningKasBank
                    ?->akun_id
                ??
                $this->getSystemAccountId(
                    'AKUN_KAS_UTAMA'
                );
        } else {
            $akunDebitId =
                $this->getSystemAccountId(
                    'AKUN_PIUTANG_DAGANG'
                );
        }

        $akunPendapatanId =
            $this->getSystemAccountId(
                'AKUN_PENDAPATAN_PENJUALAN'
            );

        $akunDiskonId =
            $this->getSystemAccountId(
                'AKUN_DISKON_PENJUALAN'
            );

        $akunPpnId =
            $this->getSystemAccountId(
                'AKUN_PPN_PENJUALAN'
            );

        $akunHppId =
            $this->getSystemAccountId(
                'AKUN_HPP'
            );

        $akunPersediaanId =
            $this->getSystemAccountId(
                'AKUN_PERSEDIAAN'
            );

        if (!$akunDebitId) {
            throw new RuntimeException(
                $invoice->tipe_pembayaran === 'Tunai'
                    ? 'Akun Kas/Bank untuk invoice tunai belum dikonfigurasi.'
                    : 'Akun Piutang Dagang belum dikonfigurasi.'
            );
        }

        if (!$akunPendapatanId) {
            throw new RuntimeException(
                'Akun Pendapatan Penjualan belum dikonfigurasi.'
            );
        }

        $subtotal =
            (float) $invoice->subtotal;

        $diskon =
            (float) $invoice->diskon;

        $pajak =
            (float) $invoice->pajak;

        $total =
            (float) $invoice->total;

        if (
            $diskon > 0
            &&
            !$akunDiskonId
        ) {
            throw new RuntimeException(
                'Akun Diskon Penjualan belum dikonfigurasi.'
            );
        }

        if (
            $pajak > 0
            &&
            !$akunPpnId
        ) {
            throw new RuntimeException(
                'Akun PPN Penjualan belum dikonfigurasi.'
            );
        }

        $expectedTotal =
            $subtotal
            -
            $diskon
            +
            $pajak;

        if (
            abs(
                $expectedTotal
                -
                $total
            ) > 1
        ) {
            throw new RuntimeException(
                'Nilai invoice tidak balance. Rumus: Subtotal - Diskon + Pajak = Total.'
            );
        }

        $totalHpp =
            0;

        foreach (
            $invoice->items
            as $item
        ) {
            $hpp =
                (float) (
                    $item->total_hpp
                    ?? 0
                );

            if (
                $hpp <= 0
                &&
                (float) (
                    $item->hpp_satuan
                    ?? 0
                ) > 0
            ) {
                $hpp =
                    (float) $item->qty
                    *
                    (float) $item->hpp_satuan;
            }

            $totalHpp +=
                $hpp;
        }

        if (
            $totalHpp > 0
            &&
            (
                !$akunHppId
                ||
                !$akunPersediaanId
            )
        ) {
            throw new RuntimeException(
                'Akun HPP atau Persediaan belum dikonfigurasi.'
            );
        }

        Jurnal::query()
            ->where(
                'referensi_type',
                'INVOICE_PENJUALAN'
            )
            ->where(
                'referensi_id',
                $invoice->id
            )
            ->delete();

        $totalDebit =
            $total
            +
            $diskon
            +
            $totalHpp;

        $totalKredit =
            $subtotal
            +
            $pajak
            +
            $totalHpp;

        if (
            abs(
                $totalDebit
                -
                $totalKredit
            ) > 1
        ) {
            throw new RuntimeException(
                'Jurnal invoice penjualan tidak balance.'
            );
        }

        $jurnal =
            Jurnal::create([
                'nomor_jurnal' =>
                    $this->generateDocumentNumber(
                        'JURNAL_UMUM',
                        $invoice->tanggal_invoice
                    ),

                'tanggal' =>
                    $invoice->tanggal_invoice,

                'keterangan' =>
                    'Jurnal Penjualan '
                    . $invoice->nomor_invoice
                    . ' - '
                    . (
                        $invoice
                            ->customer
                            ?->nama_customer
                        ?? 'Customer'
                    ),

                'is_system_generated' =>
                    true,

                'referensi_type' =>
                    'INVOICE_PENJUALAN',

                'referensi_id' =>
                    $invoice->id,

                'total_debit' =>
                    $totalDebit,

                'total_kredit' =>
                    $totalKredit,

                'is_posted' =>
                    true,

                'created_by' =>
                    auth()->id(),
            ]);

        JurnalDetail::create([
            'jurnal_id' =>
                $jurnal->id,

            'akun_id' =>
                $akunDebitId,

            'debit' =>
                $total,

            'kredit' =>
                0,

            'keterangan' =>
                $invoice->tipe_pembayaran === 'Tunai'
                    ? 'Penerimaan penjualan tunai '
                        . $invoice->nomor_invoice
                    : 'Piutang penjualan '
                        . $invoice->nomor_invoice,
        ]);

        if ($diskon > 0) {
            JurnalDetail::create([
                'jurnal_id' =>
                    $jurnal->id,

                'akun_id' =>
                    $akunDiskonId,

                'debit' =>
                    $diskon,

                'kredit' =>
                    0,

                'keterangan' =>
                    'Diskon penjualan '
                    . $invoice->nomor_invoice,
            ]);
        }

        JurnalDetail::create([
            'jurnal_id' =>
                $jurnal->id,

            'akun_id' =>
                $akunPendapatanId,

            'debit' =>
                0,

            'kredit' =>
                $subtotal,

            'keterangan' =>
                'Pendapatan penjualan '
                . $invoice->nomor_invoice,
        ]);

        if ($pajak > 0) {
            JurnalDetail::create([
                'jurnal_id' =>
                    $jurnal->id,

                'akun_id' =>
                    $akunPpnId,

                'debit' =>
                    0,

                'kredit' =>
                    $pajak,

                'keterangan' =>
                    'PPN Keluaran '
                    . $invoice->nomor_invoice,
            ]);
        }

        if ($totalHpp > 0) {
            JurnalDetail::create([
                'jurnal_id' =>
                    $jurnal->id,

                'akun_id' =>
                    $akunHppId,

                'debit' =>
                    $totalHpp,

                'kredit' =>
                    0,

                'keterangan' =>
                    'HPP penjualan '
                    . $invoice->nomor_invoice,
            ]);

            JurnalDetail::create([
                'jurnal_id' =>
                    $jurnal->id,

                'akun_id' =>
                    $akunPersediaanId,

                'debit' =>
                    0,

                'kredit' =>
                    $totalHpp,

                'keterangan' =>
                    'Pengurangan persediaan '
                    . $invoice->nomor_invoice,
            ]);
        }

        if (
            $invoice->tipe_pembayaran
            === 'Kredit'
        ) {
            $this->recordKartuPiutang(
                customerId:
                    (int) $invoice->customer_id,

                tanggal:
                    $invoice->tanggal_invoice,

                nomorRef:
                    $invoice->nomor_invoice,

                jenis:
                    'PENJUALAN',

                debit:
                    $total,

                kredit:
                    0,

                keterangan:
                    'Penjualan kredit '
                    . $invoice->nomor_invoice
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PENERIMAAN PIUTANG
    |--------------------------------------------------------------------------
    */

    public function autoJournalPenerimaanPiutang(
        PenerimaanPiutang $penerimaan
    ): void {
        if (
            $this->isPeriodLocked(
                $penerimaan->tanggal
            )
        ) {
            throw new RuntimeException(
                'Periode accounting untuk tanggal penerimaan sudah ditutup.'
            );
        }

        $penerimaan->loadMissing([
            'customer',
            'rekeningKasBank.akun',
        ]);

        $akunKasId =
            $penerimaan
                ->rekeningKasBank
                ?->akun_id
            ??
            $this->getSystemAccountId(
                'AKUN_KAS_UTAMA'
            );

        $akunPiutangId =
            $this->getSystemAccountId(
                'AKUN_PIUTANG_DAGANG'
            );

        if (!$akunKasId) {
            throw new RuntimeException(
                'Akun Kas/Bank penerimaan piutang belum dikonfigurasi.'
            );
        }

        if (!$akunPiutangId) {
            throw new RuntimeException(
                'Akun Piutang Dagang belum dikonfigurasi.'
            );
        }

        $total =
            (float) $penerimaan
                ->total_dibayar;

        if ($total <= 0) {
            throw new RuntimeException(
                'Total penerimaan piutang harus lebih besar dari 0.'
            );
        }

        Jurnal::query()
            ->where(
                'referensi_type',
                'PENERIMAAN_PIUTANG'
            )
            ->where(
                'referensi_id',
                $penerimaan->id
            )
            ->delete();

        $jurnal =
            Jurnal::create([
                'nomor_jurnal' =>
                    $this->generateDocumentNumber(
                        'JURNAL_UMUM',
                        $penerimaan->tanggal
                    ),

                'tanggal' =>
                    $penerimaan->tanggal,

                'keterangan' =>
                    'Penerimaan Piutang '
                    . $penerimaan->nomor_penerimaan
                    . ' - '
                    . (
                        $penerimaan
                            ->customer
                            ?->nama_customer
                        ?? 'Customer'
                    ),

                'is_system_generated' =>
                    true,

                'referensi_type' =>
                    'PENERIMAAN_PIUTANG',

                'referensi_id' =>
                    $penerimaan->id,

                'total_debit' =>
                    $total,

                'total_kredit' =>
                    $total,

                'is_posted' =>
                    true,

                'created_by' =>
                    auth()->id(),
            ]);

        JurnalDetail::create([
            'jurnal_id' =>
                $jurnal->id,

            'akun_id' =>
                $akunKasId,

            'debit' =>
                $total,

            'kredit' =>
                0,

            'keterangan' =>
                'Penerimaan pembayaran piutang '
                . $penerimaan->nomor_penerimaan,
        ]);

        JurnalDetail::create([
            'jurnal_id' =>
                $jurnal->id,

            'akun_id' =>
                $akunPiutangId,

            'debit' =>
                0,

            'kredit' =>
                $total,

            'keterangan' =>
                'Pelunasan piutang '
                . (
                    $penerimaan
                        ->customer
                        ?->nama_customer
                    ?? ''
                ),
        ]);

        $this->recordKartuPiutang(
            customerId:
                (int) $penerimaan->customer_id,

            tanggal:
                $penerimaan->tanggal,

            nomorRef:
                $penerimaan->nomor_penerimaan,

            jenis:
                'PEMBAYARAN',

            debit:
                0,

            kredit:
                $total,

            keterangan:
                'Pembayaran piutang '
                . $penerimaan->nomor_penerimaan
        );
    }

    /*
    |--------------------------------------------------------------------------
    | RETUR PENJUALAN
    |--------------------------------------------------------------------------
    */

    public function autoJournalReturPenjualan(
        ReturPenjualan $retur
    ): void {
        if (
            $this->isPeriodLocked(
                $retur->tanggal_retur
            )
        ) {
            throw new RuntimeException(
                'Periode accounting untuk tanggal retur sudah ditutup.'
            );
        }

        $retur->loadMissing([
            'customer',
            'items',
            'rekeningKasBank.akun',
            'invoicePenjualan',
        ]);

        $akunPiutangId =
            $this->getSystemAccountId(
                'AKUN_PIUTANG_DAGANG'
            );

        $akunReturId =
            $this->getSystemAccountId(
                'AKUN_RETUR_PENJUALAN'
            );

        if (!$akunReturId) {
            $akunReturId =
                $this->getSystemAccountId(
                    'AKUN_PENDAPATAN_PENJUALAN'
                );
        }

        $akunPpnId =
            $this->getSystemAccountId(
                'AKUN_PPN_PENJUALAN'
            );

        $akunHppId =
            $this->getSystemAccountId(
                'AKUN_HPP'
            );

        $akunPersediaanId =
            $this->getSystemAccountId(
                'AKUN_PERSEDIAAN'
            );

        if (!$akunReturId) {
            throw new RuntimeException(
                'Akun Retur/Pendapatan Penjualan belum dikonfigurasi.'
            );
        }

        $subtotal =
            (float) $retur->subtotal;

        $pajak =
            (float) $retur->pajak;

        $total =
            (float) $retur->total;

        if (
            $pajak > 0
            &&
            !$akunPpnId
        ) {
            throw new RuntimeException(
                'Akun PPN Penjualan belum dikonfigurasi.'
            );
        }

        $totalHppRetur =
            0;

        foreach (
            $retur->items
            as $item
        ) {
            $hpp =
                (float) (
                    $item->total_hpp
                    ?? 0
                );

            if (
                $hpp <= 0
                &&
                (float) (
                    $item->hpp_satuan
                    ?? 0
                ) > 0
            ) {
                $hpp =
                    (float) $item->qty
                    *
                    (float) $item->hpp_satuan;
            }

            $totalHppRetur +=
                $hpp;
        }

        if (
            $totalHppRetur > 0
            &&
            (
                !$akunPersediaanId
                ||
                !$akunHppId
            )
        ) {
            throw new RuntimeException(
                'Akun Persediaan atau HPP belum dikonfigurasi.'
            );
        }

        Jurnal::query()
            ->where(
                'referensi_type',
                'RETUR_PENJUALAN'
            )
            ->where(
                'referensi_id',
                $retur->id
            )
            ->delete();

        $isTunai =
            $retur->tipe_retur
            === 'Tunai';

        $potongPiutang =
            (float) (
                $retur->potong_piutang
                ?? 0
            );

        $pengembalianKas =
            (float) (
                $retur->pengembalian_kas
                ?? 0
            );

        $akunKasId =
            $retur
                ->rekeningKasBank
                ?->akun_id
            ??
            $this->getSystemAccountId(
                'AKUN_KAS_UTAMA'
            );

        $totalDebit =
            $subtotal
            +
            $pajak
            +
            $totalHppRetur;

        $totalKredit =
            $total
            +
            $totalHppRetur;

        if (
            abs(
                $totalDebit
                -
                $totalKredit
            ) > 1
        ) {
            throw new RuntimeException(
                'Jurnal retur penjualan tidak balance.'
            );
        }

        $jurnal =
            Jurnal::create([
                'nomor_jurnal' =>
                    $this->generateDocumentNumber(
                        'JURNAL_UMUM',
                        $retur->tanggal_retur
                    ),

                'tanggal' =>
                    $retur->tanggal_retur,

                'keterangan' =>
                    'Retur Penjualan '
                    . $retur->nomor_retur
                    . ' - '
                    . (
                        $retur
                            ->customer
                            ?->nama_customer
                        ?? 'Customer'
                    ),

                'is_system_generated' =>
                    true,

                'referensi_type' =>
                    'RETUR_PENJUALAN',

                'referensi_id' =>
                    $retur->id,

                'total_debit' =>
                    $totalDebit,

                'total_kredit' =>
                    $totalKredit,

                'is_posted' =>
                    true,

                'created_by' =>
                    auth()->id(),
            ]);

        JurnalDetail::create([
            'jurnal_id' =>
                $jurnal->id,

            'akun_id' =>
                $akunReturId,

            'debit' =>
                $subtotal,

            'kredit' =>
                0,

            'keterangan' =>
                'Retur penjualan '
                . $retur->nomor_retur,
        ]);

        if ($pajak > 0) {
            JurnalDetail::create([
                'jurnal_id' =>
                    $jurnal->id,

                'akun_id' =>
                    $akunPpnId,

                'debit' =>
                    $pajak,

                'kredit' =>
                    0,

                'keterangan' =>
                    'Koreksi PPN retur '
                    . $retur->nomor_retur,
            ]);
        }

        if (
            $isTunai
            ||
            $pengembalianKas > 0
        ) {
            if ($potongPiutang > 0) {
                if (!$akunPiutangId) {
                    throw new RuntimeException(
                        'Akun Piutang Dagang belum dikonfigurasi.'
                    );
                }

                JurnalDetail::create([
                    'jurnal_id' =>
                        $jurnal->id,

                    'akun_id' =>
                        $akunPiutangId,

                    'debit' =>
                        0,

                    'kredit' =>
                        $potongPiutang,

                    'keterangan' =>
                        'Pengurangan piutang retur '
                        . $retur->nomor_retur,
                ]);
            }

            $nilaiRefund =
                $isTunai
                    ? $total
                    : $pengembalianKas;

            if ($nilaiRefund > 0) {
                if (!$akunKasId) {
                    throw new RuntimeException(
                        'Akun Kas/Bank retur belum dikonfigurasi.'
                    );
                }

                JurnalDetail::create([
                    'jurnal_id' =>
                        $jurnal->id,

                    'akun_id' =>
                        $akunKasId,

                    'debit' =>
                        0,

                    'kredit' =>
                        $nilaiRefund,

                    'keterangan' =>
                        'Pengembalian Kas/Bank retur '
                        . $retur->nomor_retur,
                ]);
            }
        } else {
            if (!$akunPiutangId) {
                throw new RuntimeException(
                    'Akun Piutang Dagang belum dikonfigurasi.'
                );
            }

            JurnalDetail::create([
                'jurnal_id' =>
                    $jurnal->id,

                'akun_id' =>
                    $akunPiutangId,

                'debit' =>
                    0,

                'kredit' =>
                    $total,

                'keterangan' =>
                    'Pengurangan piutang retur '
                    . $retur->nomor_retur,
            ]);
        }

        if ($totalHppRetur > 0) {
            JurnalDetail::create([
                'jurnal_id' =>
                    $jurnal->id,

                'akun_id' =>
                    $akunPersediaanId,

                'debit' =>
                    $totalHppRetur,

                'kredit' =>
                    0,

                'keterangan' =>
                    'Barang retur kembali ke persediaan '
                    . $retur->nomor_retur,
            ]);

            JurnalDetail::create([
                'jurnal_id' =>
                    $jurnal->id,

                'akun_id' =>
                    $akunHppId,

                'debit' =>
                    0,

                'kredit' =>
                    $totalHppRetur,

                'keterangan' =>
                    'Koreksi HPP retur '
                    . $retur->nomor_retur,
            ]);
        }

        if (
            $potongPiutang > 0
            ||
            !$isTunai
        ) {
            $kreditKartu =
                $isTunai
                    ? 0
                    : (
                        $potongPiutang > 0
                            ? $potongPiutang
                            : $total
                    );

            if ($kreditKartu > 0) {
                $this->recordKartuPiutang(
                    customerId:
                        (int) $retur->customer_id,

                    tanggal:
                        $retur->tanggal_retur,

                    nomorRef:
                        $retur->nomor_retur,

                    jenis:
                        'RETUR',

                    debit:
                        0,

                    kredit:
                        $kreditKartu,

                    keterangan:
                        'Retur Penjualan '
                        . $retur->nomor_retur
                );
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | INVOICE SUPPLIER
    |--------------------------------------------------------------------------
    |
    | Dr Persediaan / Pembelian
    | Dr PPN Masukan
    |      Cr Diskon Pembelian
    |      Cr Utang Dagang
    |
    */

    public function autoJournalInvoiceSupplier(
        InvoiceSupplier $invoice
    ): void {
        if (
            $this->isPeriodLocked(
                $invoice->tanggal_invoice
            )
        ) {
            throw new RuntimeException(
                'Periode accounting untuk tanggal Invoice Supplier sudah ditutup.'
            );
        }

        $invoice->loadMissing([
            'supplier',
            'items',
            'purchaseOrder',
        ]);

        $akunPembelianId =
            $this->getSystemAccountId(
                'AKUN_PEMBELIAN_BARANG'
            );

        $akunUtangId =
            $this->getSystemAccountId(
                'AKUN_UTANG_DAGANG'
            );

        $akunDiskonId =
            $this->getSystemAccountId(
                'AKUN_DISKON_PEMBELIAN'
            );

        $akunPpnMasukanId =
            $this->getSystemAccountId(
                'AKUN_PPN_PEMBELIAN'
            );

        if (!$akunPembelianId) {
            throw new RuntimeException(
                'Akun Pembelian/Persediaan belum dikonfigurasi.'
            );
        }

        if (!$akunUtangId) {
            throw new RuntimeException(
                'Akun Utang Dagang Supplier belum dikonfigurasi.'
            );
        }

        $subtotal =
            round(
                (float) $invoice->subtotal,
                2
            );

        $diskon =
            round(
                (float) $invoice->diskon,
                2
            );

        $pajak =
            round(
                (float) $invoice->pajak,
                2
            );

        $total =
            round(
                (float) $invoice->total,
                2
            );

        if ($subtotal < 0) {
            throw new RuntimeException(
                'Subtotal Invoice Supplier tidak valid.'
            );
        }

        if ($diskon < 0) {
            throw new RuntimeException(
                'Diskon Invoice Supplier tidak valid.'
            );
        }

        if ($diskon > $subtotal) {
            throw new RuntimeException(
                'Diskon Invoice Supplier tidak boleh lebih besar dari subtotal.'
            );
        }

        if ($pajak < 0) {
            throw new RuntimeException(
                'Pajak Invoice Supplier tidak valid.'
            );
        }

        if ($total <= 0) {
            throw new RuntimeException(
                'Total Invoice Supplier harus lebih besar dari 0.'
            );
        }

        if (
            $diskon > 0
            &&
            !$akunDiskonId
        ) {
            throw new RuntimeException(
                'Akun Diskon Pembelian belum dikonfigurasi.'
            );
        }

        if (
            $pajak > 0
            &&
            !$akunPpnMasukanId
        ) {
            throw new RuntimeException(
                'Akun PPN Masukan Pembelian belum dikonfigurasi.'
            );
        }

        $expectedTotal =
            round(
                $subtotal
                -
                $diskon
                +
                $pajak,
                2
            );

        if (
            abs(
                $expectedTotal
                -
                $total
            ) > 0.01
        ) {
            throw new RuntimeException(
                'Invoice Supplier tidak balance. Rumus: Subtotal - Diskon + Pajak = Total.'
            );
        }

        Jurnal::query()
            ->where(
                'referensi_type',
                'INVOICE_SUPPLIER'
            )
            ->where(
                'referensi_id',
                $invoice->id
            )
            ->delete();

        $totalDebit =
            round(
                $subtotal
                +
                $pajak,
                2
            );

        $totalKredit =
            round(
                $total
                +
                $diskon,
                2
            );

        if (
            abs(
                $totalDebit
                -
                $totalKredit
            ) > 0.01
        ) {
            throw new RuntimeException(
                'Jurnal Invoice Supplier tidak balance.'
            );
        }

        $jurnal =
            Jurnal::create([
                'nomor_jurnal' =>
                    $this->generateDocumentNumber(
                        'JURNAL_UMUM',
                        $invoice->tanggal_invoice
                    ),

                'tanggal' =>
                    $invoice->tanggal_invoice,

                'keterangan' =>
                    'Invoice Supplier '
                    . $invoice->nomor_invoice_internal
                    . ' - '
                    . (
                        $invoice
                            ->supplier
                            ?->nama_supplier
                        ?? 'Supplier'
                    ),

                'is_system_generated' =>
                    true,

                'referensi_type' =>
                    'INVOICE_SUPPLIER',

                'referensi_id' =>
                    $invoice->id,

                'total_debit' =>
                    $totalDebit,

                'total_kredit' =>
                    $totalKredit,

                'is_posted' =>
                    true,

                'created_by' =>
                    auth()->id(),
            ]);

        if ($subtotal > 0) {
            JurnalDetail::create([
                'jurnal_id' =>
                    $jurnal->id,

                'akun_id' =>
                    $akunPembelianId,

                'debit' =>
                    $subtotal,

                'kredit' =>
                    0,

                'keterangan' =>
                    'Pembelian barang '
                    . $invoice->nomor_invoice_internal,
            ]);
        }

        if ($pajak > 0) {
            JurnalDetail::create([
                'jurnal_id' =>
                    $jurnal->id,

                'akun_id' =>
                    $akunPpnMasukanId,

                'debit' =>
                    $pajak,

                'kredit' =>
                    0,

                'keterangan' =>
                    'PPN Masukan '
                    . $invoice->nomor_invoice_internal,
            ]);
        }

        if ($diskon > 0) {
            JurnalDetail::create([
                'jurnal_id' =>
                    $jurnal->id,

                'akun_id' =>
                    $akunDiskonId,

                'debit' =>
                    0,

                'kredit' =>
                    $diskon,

                'keterangan' =>
                    'Diskon pembelian '
                    . $invoice->nomor_invoice_internal,
            ]);
        }

        JurnalDetail::create([
            'jurnal_id' =>
                $jurnal->id,

            'akun_id' =>
                $akunUtangId,

            'debit' =>
                0,

            'kredit' =>
                $total,

            'keterangan' =>
                'Utang Supplier '
                . $invoice->nomor_invoice_internal
                . ' - '
                . (
                    $invoice
                        ->supplier
                        ?->nama_supplier
                    ?? ''
                ),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | PEMBAYARAN SUPPLIER
    |--------------------------------------------------------------------------
    |
    | Dr Utang Dagang
    |      Cr Kas / Bank
    |
    */

    public function autoJournalPembayaranSupplier(
        PembayaranSupplier $pembayaran
    ): void {
        if (
            $this->isPeriodLocked(
                $pembayaran
                    ->tanggal_pembayaran
            )
        ) {
            throw new RuntimeException(
                'Periode accounting untuk tanggal Pembayaran Supplier sudah ditutup.'
            );
        }

        $pembayaran->loadMissing([
            'invoiceSupplier',
            'supplier',
            'rekeningKasBank.akun',
        ]);

        if (
            !$pembayaran
                ->invoiceSupplier
        ) {
            throw new RuntimeException(
                'Invoice Supplier pembayaran tidak ditemukan.'
            );
        }

        if (
            !$pembayaran
                ->supplier
        ) {
            throw new RuntimeException(
                'Supplier pembayaran tidak ditemukan.'
            );
        }

        $akunUtangId =
            $this->getSystemAccountId(
                'AKUN_UTANG_DAGANG'
            );

        if (!$akunUtangId) {
            throw new RuntimeException(
                'Akun Utang Dagang Supplier belum dikonfigurasi.'
            );
        }

        if (
            !$pembayaran
                ->rekeningKasBank
        ) {
            throw new RuntimeException(
                'Kas/Rekening Bank pembayaran supplier tidak ditemukan.'
            );
        }

        $akunKasBankId =
            $pembayaran
                ->rekeningKasBank
                ->akun_id;

        if (!$akunKasBankId) {
            throw new RuntimeException(
                'Kas/Rekening Bank pembayaran supplier belum terhubung dengan akun COA.'
            );
        }

        $jumlah =
            round(
                (float) $pembayaran
                    ->jumlah_bayar,
                2
            );

        if ($jumlah <= 0) {
            throw new RuntimeException(
                'Jumlah Pembayaran Supplier harus lebih besar dari 0.'
            );
        }

        Jurnal::query()
            ->where(
                'referensi_type',
                'PEMBAYARAN_SUPPLIER'
            )
            ->where(
                'referensi_id',
                $pembayaran->id
            )
            ->delete();

        $jurnal =
            Jurnal::create([
                'nomor_jurnal' =>
                    $this->generateDocumentNumber(
                        'JURNAL_UMUM',
                        $pembayaran
                            ->tanggal_pembayaran
                    ),

                'tanggal' =>
                    $pembayaran
                        ->tanggal_pembayaran,

                'keterangan' =>
                    'Pembayaran Supplier '
                    . $pembayaran
                        ->nomor_pembayaran
                    . ' - '
                    . (
                        $pembayaran
                            ->supplier
                            ?->nama_supplier
                        ?? 'Supplier'
                    ),

                'is_system_generated' =>
                    true,

                'referensi_type' =>
                    'PEMBAYARAN_SUPPLIER',

                'referensi_id' =>
                    $pembayaran->id,

                'total_debit' =>
                    $jumlah,

                'total_kredit' =>
                    $jumlah,

                'is_posted' =>
                    true,

                'created_by' =>
                    auth()->id(),
            ]);

        JurnalDetail::create([
            'jurnal_id' =>
                $jurnal->id,

            'akun_id' =>
                $akunUtangId,

            'debit' =>
                $jumlah,

            'kredit' =>
                0,

            'keterangan' =>
                'Pembayaran utang '
                . (
                    $pembayaran
                        ->invoiceSupplier
                        ->nomor_invoice_internal
                    ?? ''
                ),
        ]);

        JurnalDetail::create([
            'jurnal_id' =>
                $jurnal->id,

            'akun_id' =>
                $akunKasBankId,

            'debit' =>
                0,

            'kredit' =>
                $jumlah,

            'keterangan' =>
                'Pembayaran melalui '
                . $pembayaran
                    ->rekeningKasBank
                    ->nama_rekening,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | RETUR PEMBELIAN
    |--------------------------------------------------------------------------
    |
    | Jika Invoice Belum Dibayar:
    |
    | Dr Utang Dagang
    | Dr Diskon Pembelian
    |      Cr Persediaan
    |      Cr PPN Masukan
    |
    |
    | Jika Invoice Sudah Dibayar:
    |
    | Dr Uang Muka Pembelian / Kredit Supplier
    | Dr Diskon Pembelian
    |      Cr Persediaan
    |      Cr PPN Masukan
    |
    |
    | Bisa juga kombinasi:
    | sebagian mengurangi Utang,
    | sebagian menjadi Kredit Supplier.
    |
    */

    public function autoJournalReturPembelian(
        ReturPembelian $retur
    ): void {
        /*
        |--------------------------------------------------------------------------
        | Period Lock
        |--------------------------------------------------------------------------
        */

        if (
            $this->isPeriodLocked(
                $retur->tanggal_retur
            )
        ) {
            throw new RuntimeException(
                'Periode accounting untuk tanggal Retur Pembelian sudah ditutup.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Load
        |--------------------------------------------------------------------------
        */

        $retur->loadMissing([
            'supplier',
            'allocations',
            'allocations.invoiceSupplier',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Hapus Jurnal Lama
        |--------------------------------------------------------------------------
        */

        Jurnal::query()
            ->where(
                'referensi_type',
                'RETUR_PEMBELIAN'
            )
            ->where(
                'referensi_id',
                $retur->id
            )
            ->delete();

        /*
        |--------------------------------------------------------------------------
        | Jika Barang Belum Pernah Di-Invoice
        |--------------------------------------------------------------------------
        |
        | Tidak ada koreksi accounting karena sebelumnya
        | belum pernah terbentuk Utang / Persediaan dari invoice.
        |
        */

        if (
            $retur
                ->allocations
                ->isEmpty()
        ) {
            return;
        }

        /*
        |--------------------------------------------------------------------------
        | Nilai Allocation
        |--------------------------------------------------------------------------
        */

        $nilaiBarang =
            round(
                (float) $retur
                    ->allocations
                    ->sum('nilai_barang'),
                2
            );

        $diskonHeader =
            round(
                (float) $retur
                    ->allocations
                    ->sum('diskon_header'),
                2
            );

        $pajak =
            round(
                (float) $retur
                    ->allocations
                    ->sum('pajak'),
                2
            );

        $totalKoreksi =
            round(
                (float) $retur
                    ->allocations
                    ->sum('total_koreksi'),
                2
            );

        $potongUtang =
            round(
                (float) $retur
                    ->allocations
                    ->sum('potong_utang'),
                2
            );

        $kreditSupplier =
            round(
                (float) $retur
                    ->allocations
                    ->sum('kredit_supplier'),
                2
            );

        /*
        |--------------------------------------------------------------------------
        | Validasi Nominal
        |--------------------------------------------------------------------------
        */

        if (
            $nilaiBarang <= 0
        ) {
            throw new RuntimeException(
                'Nilai barang Retur Pembelian tidak valid.'
            );
        }

        if (
            $diskonHeader < 0
            ||
            $pajak < 0
            ||
            $totalKoreksi < 0
            ||
            $potongUtang < 0
            ||
            $kreditSupplier < 0
        ) {
            throw new RuntimeException(
                'Nilai accounting Retur Pembelian tidak valid.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Validasi Rumus Total Koreksi
        |--------------------------------------------------------------------------
        */

        $expectedTotalKoreksi =
            round(
                $nilaiBarang
                -
                $diskonHeader
                +
                $pajak,
                2
            );

        if (
            abs(
                $expectedTotalKoreksi
                -
                $totalKoreksi
            ) > 0.02
        ) {
            throw new RuntimeException(
                'Total koreksi Retur Pembelian tidak sesuai dengan nilai barang, diskon, dan pajak.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Total Koreksi Harus Terbagi
        |--------------------------------------------------------------------------
        */

        if (
            abs(
                (
                    $potongUtang
                    +
                    $kreditSupplier
                )
                -
                $totalKoreksi
            ) > 0.02
        ) {
            throw new RuntimeException(
                'Nilai pengurangan utang dan kredit supplier pada Retur Pembelian tidak balance.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Accounts
        |--------------------------------------------------------------------------
        */

        $akunPersediaanId =
            $this->getSystemAccountId(
                'AKUN_PEMBELIAN_BARANG'
            )
            ??
            $this->getSystemAccountId(
                'AKUN_PERSEDIAAN'
            );

        $akunUtangId =
            $this->getSystemAccountId(
                'AKUN_UTANG_DAGANG'
            );

        $akunDiskonId =
            $this->getSystemAccountId(
                'AKUN_DISKON_PEMBELIAN'
            );

        $akunPpnMasukanId =
            $this->getSystemAccountId(
                'AKUN_PPN_PEMBELIAN'
            );

        $akunUangMukaId =
            $this
                ->getUangMukaPembelianAccountId();

        /*
        |--------------------------------------------------------------------------
        | Validasi Account
        |--------------------------------------------------------------------------
        */

        if (!$akunPersediaanId) {
            throw new RuntimeException(
                'Akun Persediaan/Pembelian belum dikonfigurasi.'
            );
        }

        if (
            $potongUtang > 0
            &&
            !$akunUtangId
        ) {
            throw new RuntimeException(
                'Akun Utang Dagang Supplier belum dikonfigurasi.'
            );
        }

        if (
            $diskonHeader > 0
            &&
            !$akunDiskonId
        ) {
            throw new RuntimeException(
                'Akun Diskon Pembelian belum dikonfigurasi.'
            );
        }

        if (
            $pajak > 0
            &&
            !$akunPpnMasukanId
        ) {
            throw new RuntimeException(
                'Akun PPN Masukan Pembelian belum dikonfigurasi.'
            );
        }

        if (
            $kreditSupplier > 0
            &&
            !$akunUangMukaId
        ) {
            throw new RuntimeException(
                'Akun Uang Muka Pembelian untuk Kredit Supplier belum ditemukan.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Journal Balance
        |--------------------------------------------------------------------------
        */

        $totalDebit =
            round(
                $potongUtang
                +
                $kreditSupplier
                +
                $diskonHeader,
                2
            );

        $totalKredit =
            round(
                $nilaiBarang
                +
                $pajak,
                2
            );

        if (
            abs(
                $totalDebit
                -
                $totalKredit
            ) > 0.02
        ) {
            throw new RuntimeException(
                'Jurnal Retur Pembelian tidak balance.'
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Journal Header
        |--------------------------------------------------------------------------
        */

        $jurnal =
            Jurnal::create([
                'nomor_jurnal' =>
                    $this->generateDocumentNumber(
                        'JURNAL_UMUM',
                        $retur->tanggal_retur
                    ),

                'tanggal' =>
                    $retur->tanggal_retur,

                'keterangan' =>
                    'Retur Pembelian '
                    . $retur->nomor_retur
                    . ' - '
                    . (
                        $retur
                            ->supplier
                            ?->nama_supplier
                        ?? 'Supplier'
                    ),

                'is_system_generated' =>
                    true,

                'referensi_type' =>
                    'RETUR_PEMBELIAN',

                'referensi_id' =>
                    $retur->id,

                'total_debit' =>
                    $totalDebit,

                'total_kredit' =>
                    $totalKredit,

                'is_posted' =>
                    true,

                'created_by' =>
                    auth()->id(),
            ]);

        /*
        |--------------------------------------------------------------------------
        | Dr Utang Dagang
        |--------------------------------------------------------------------------
        */

        if (
            $potongUtang > 0
        ) {
            JurnalDetail::create([
                'jurnal_id' =>
                    $jurnal->id,

                'akun_id' =>
                    $akunUtangId,

                'debit' =>
                    $potongUtang,

                'kredit' =>
                    0,

                'keterangan' =>
                    'Pengurangan Utang Supplier dari '
                    . $retur->nomor_retur,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Dr Uang Muka Pembelian / Kredit Supplier
        |--------------------------------------------------------------------------
        */

        if (
            $kreditSupplier > 0
        ) {
            JurnalDetail::create([
                'jurnal_id' =>
                    $jurnal->id,

                'akun_id' =>
                    $akunUangMukaId,

                'debit' =>
                    $kreditSupplier,

                'kredit' =>
                    0,

                'keterangan' =>
                    'Kredit Supplier akibat '
                    . $retur->nomor_retur,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Dr Diskon Pembelian
        |--------------------------------------------------------------------------
        |
        | Invoice awal:
        | Cr Diskon Pembelian.
        |
        | Maka saat diretur:
        | Dr Diskon Pembelian.
        |
        */

        if (
            $diskonHeader > 0
        ) {
            JurnalDetail::create([
                'jurnal_id' =>
                    $jurnal->id,

                'akun_id' =>
                    $akunDiskonId,

                'debit' =>
                    $diskonHeader,

                'kredit' =>
                    0,

                'keterangan' =>
                    'Pembalik Diskon Pembelian '
                    . $retur->nomor_retur,
            ]);
        }

        /*
        |--------------------------------------------------------------------------
        | Cr Persediaan
        |--------------------------------------------------------------------------
        */

        JurnalDetail::create([
            'jurnal_id' =>
                $jurnal->id,

            'akun_id' =>
                $akunPersediaanId,

            'debit' =>
                0,

            'kredit' =>
                $nilaiBarang,

            'keterangan' =>
                'Barang dikembalikan ke Supplier '
                . $retur->nomor_retur,
        ]);

        /*
        |--------------------------------------------------------------------------
        | Cr PPN Masukan
        |--------------------------------------------------------------------------
        */

        if (
            $pajak > 0
        ) {
            JurnalDetail::create([
                'jurnal_id' =>
                    $jurnal->id,

                'akun_id' =>
                    $akunPpnMasukanId,

                'debit' =>
                    0,

                'kredit' =>
                    $pajak,

                'keterangan' =>
                    'Koreksi PPN Masukan '
                    . $retur->nomor_retur,
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | UANG KELUAR
    |--------------------------------------------------------------------------
    */

    public function autoJournalUangKeluar(
        UangKeluar $uangKeluar
    ): void {
        if (
            $this->isPeriodLocked(
                $uangKeluar->tanggal
            )
        ) {
            throw new RuntimeException(
                'Periode accounting untuk tanggal transaksi ini sudah ditutup.'
            );
        }

        $uangKeluar->loadMissing([
            'details.akun',
            'rekeningKasBank.akun',
        ]);

        if (
            !$uangKeluar
                ->rekeningKasBank
        ) {
            throw new RuntimeException(
                'Kas/Rekening Bank transaksi tidak ditemukan.'
            );
        }

        $akunKasBankId =
            $uangKeluar
                ->rekeningKasBank
                ->akun_id;

        if (!$akunKasBankId) {
            throw new RuntimeException(
                'Kas/Rekening Bank belum terhubung dengan akun COA.'
            );
        }

        if (
            $uangKeluar
                ->details
                ->isEmpty()
        ) {
            throw new RuntimeException(
                'Uang Keluar belum memiliki rincian biaya.'
            );
        }

        foreach (
            $uangKeluar->details
            as $detail
        ) {
            if (!$detail->akun_id) {
                throw new RuntimeException(
                    'Semua rincian Uang Keluar harus memiliki akun biaya.'
                );
            }

            if (
                (float) $detail->jumlah
                <= 0
            ) {
                throw new RuntimeException(
                    'Nominal rincian Uang Keluar harus lebih besar dari 0.'
                );
            }
        }

        Jurnal::query()
            ->where(
                'referensi_type',
                'UANG_KELUAR'
            )
            ->where(
                'referensi_id',
                $uangKeluar->id
            )
            ->delete();

        $total =
            (float) $uangKeluar
                ->details
                ->sum('jumlah');

        if ($total <= 0) {
            throw new RuntimeException(
                'Total Uang Keluar harus lebih besar dari 0.'
            );
        }

        $jurnal =
            Jurnal::create([
                'nomor_jurnal' =>
                    $this->generateDocumentNumber(
                        'JURNAL_UMUM',
                        $uangKeluar->tanggal
                    ),

                'tanggal' =>
                    $uangKeluar->tanggal,

                'keterangan' =>
                    'Uang Keluar '
                    . $uangKeluar->nomor_transaksi
                    . ' - '
                    . $uangKeluar
                        ->rekeningKasBank
                        ->nama_rekening,

                'is_system_generated' =>
                    true,

                'referensi_type' =>
                    'UANG_KELUAR',

                'referensi_id' =>
                    $uangKeluar->id,

                'total_debit' =>
                    $total,

                'total_kredit' =>
                    $total,

                'is_posted' =>
                    true,

                'created_by' =>
                    auth()->id(),
            ]);

        foreach (
            $uangKeluar->details
            as $detail
        ) {
            JurnalDetail::create([
                'jurnal_id' =>
                    $jurnal->id,

                'akun_id' =>
                    $detail->akun_id,

                'debit' =>
                    $detail->jumlah,

                'kredit' =>
                    0,

                'keterangan' =>
                    $detail->tujuan,
            ]);
        }

        JurnalDetail::create([
            'jurnal_id' =>
                $jurnal->id,

            'akun_id' =>
                $akunKasBankId,

            'debit' =>
                0,

            'kredit' =>
                $total,

            'keterangan' =>
                'Pengeluaran melalui '
                . $uangKeluar
                    ->rekeningKasBank
                    ->nama_rekening,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | PEMBATALAN INVOICE PENJUALAN
    |--------------------------------------------------------------------------
    */

    public function cancelInvoicePenjualan(
        InvoicePenjualan $invoice,
        string $alasan
    ): void {
        if (
            $invoice->status_invoice
            === 'DIBATALKAN'
        ) {
            throw new RuntimeException(
                'Invoice sudah dibatalkan.'
            );
        }

        $invoice->loadMissing([
            'customer',
            'rekeningKasBank.akun',
            'items',
        ]);

        $akunDebitId =
            $invoice->tipe_pembayaran
            === 'Tunai'
                ? (
                    $invoice
                        ->rekeningKasBank
                        ?->akun_id
                    ??
                    $this->getSystemAccountId(
                        'AKUN_KAS_UTAMA'
                    )
                )
                :
                $this->getSystemAccountId(
                    'AKUN_PIUTANG_DAGANG'
                );

        $akunPendapatanId =
            $this->getSystemAccountId(
                'AKUN_PENDAPATAN_PENJUALAN'
            );

        $akunDiskonId =
            $this->getSystemAccountId(
                'AKUN_DISKON_PENJUALAN'
            );

        $akunPpnId =
            $this->getSystemAccountId(
                'AKUN_PPN_PENJUALAN'
            );

        $akunHppId =
            $this->getSystemAccountId(
                'AKUN_HPP'
            );

        $akunPersediaanId =
            $this->getSystemAccountId(
                'AKUN_PERSEDIAAN'
            );

        if (!$akunDebitId) {
            throw new RuntimeException(
                'Akun Kas/Bank atau Piutang belum dikonfigurasi.'
            );
        }

        if (!$akunPendapatanId) {
            throw new RuntimeException(
                'Akun Pendapatan Penjualan belum dikonfigurasi.'
            );
        }

        $subtotal =
            (float) $invoice->subtotal;

        $diskon =
            (float) $invoice->diskon;

        $pajak =
            (float) $invoice->pajak;

        $total =
            (float) $invoice->total;

        if (
            $diskon > 0
            &&
            !$akunDiskonId
        ) {
            throw new RuntimeException(
                'Akun Diskon Penjualan belum dikonfigurasi.'
            );
        }

        if (
            $pajak > 0
            &&
            !$akunPpnId
        ) {
            throw new RuntimeException(
                'Akun PPN Penjualan belum dikonfigurasi.'
            );
        }

        $expectedTotal =
            $subtotal
            -
            $diskon
            +
            $pajak;

        if (
            abs(
                $expectedTotal
                -
                $total
            ) > 1
        ) {
            throw new RuntimeException(
                'Invoice tidak balance sehingga tidak aman dibatalkan.'
            );
        }

        $totalHpp =
            0;

        foreach (
            $invoice->items
            as $item
        ) {
            $hpp =
                (float) (
                    $item->total_hpp
                    ?? 0
                );

            if (
                $hpp <= 0
                &&
                (float) (
                    $item->hpp_satuan
                    ?? 0
                ) > 0
            ) {
                $hpp =
                    (float) $item->qty
                    *
                    (float) $item->hpp_satuan;
            }

            $totalHpp +=
                $hpp;
        }

        if (
            $totalHpp > 0
            &&
            (
                !$akunHppId
                ||
                !$akunPersediaanId
            )
        ) {
            throw new RuntimeException(
                'Akun HPP atau Persediaan belum dikonfigurasi.'
            );
        }

        $jurnalPembatalanSudahAda =
            Jurnal::query()
                ->where(
                    'referensi_type',
                    'PEMBATALAN_INVOICE'
                )
                ->where(
                    'referensi_id',
                    $invoice->id
                )
                ->exists();

        if (
            $jurnalPembatalanSudahAda
        ) {
            throw new RuntimeException(
                'Jurnal pembatalan invoice sudah pernah dibuat.'
            );
        }

        $totalDebit =
            $subtotal
            +
            $pajak
            +
            $totalHpp;

        $totalKredit =
            $total
            +
            $diskon
            +
            $totalHpp;

        if (
            abs(
                $totalDebit
                -
                $totalKredit
            ) > 1
        ) {
            throw new RuntimeException(
                'Jurnal pembatalan invoice tidak balance.'
            );
        }

        $jurnal =
            Jurnal::create([
                'nomor_jurnal' =>
                    $this->generateDocumentNumber(
                        'JURNAL_UMUM',
                        now()
                    ),

                'tanggal' =>
                    now(),

                'keterangan' =>
                    'Pembatalan Invoice '
                    . $invoice->nomor_invoice
                    . ' - '
                    . $alasan,

                'is_system_generated' =>
                    true,

                'referensi_type' =>
                    'PEMBATALAN_INVOICE',

                'referensi_id' =>
                    $invoice->id,

                'total_debit' =>
                    $totalDebit,

                'total_kredit' =>
                    $totalKredit,

                'is_posted' =>
                    true,

                'created_by' =>
                    auth()->id(),
            ]);

        JurnalDetail::create([
            'jurnal_id' =>
                $jurnal->id,

            'akun_id' =>
                $akunPendapatanId,

            'debit' =>
                $subtotal,

            'kredit' =>
                0,

            'keterangan' =>
                'Pembalik Pendapatan '
                . $invoice->nomor_invoice,
        ]);

        if ($pajak > 0) {
            JurnalDetail::create([
                'jurnal_id' =>
                    $jurnal->id,

                'akun_id' =>
                    $akunPpnId,

                'debit' =>
                    $pajak,

                'kredit' =>
                    0,

                'keterangan' =>
                    'Pembalik PPN Keluaran '
                    . $invoice->nomor_invoice,
            ]);
        }

        if ($totalHpp > 0) {
            JurnalDetail::create([
                'jurnal_id' =>
                    $jurnal->id,

                'akun_id' =>
                    $akunPersediaanId,

                'debit' =>
                    $totalHpp,

                'kredit' =>
                    0,

                'keterangan' =>
                    'Pengembalian Persediaan '
                    . $invoice->nomor_invoice,
            ]);
        }

        JurnalDetail::create([
            'jurnal_id' =>
                $jurnal->id,

            'akun_id' =>
                $akunDebitId,

            'debit' =>
                0,

            'kredit' =>
                $total,

            'keterangan' =>
                $invoice->tipe_pembayaran
                === 'Tunai'
                    ? 'Pembalik Kas/Bank '
                        . $invoice->nomor_invoice
                    : 'Pembalik Piutang '
                        . $invoice->nomor_invoice,
        ]);

        if ($diskon > 0) {
            JurnalDetail::create([
                'jurnal_id' =>
                    $jurnal->id,

                'akun_id' =>
                    $akunDiskonId,

                'debit' =>
                    0,

                'kredit' =>
                    $diskon,

                'keterangan' =>
                    'Pembalik Diskon Penjualan '
                    . $invoice->nomor_invoice,
            ]);
        }

        if ($totalHpp > 0) {
            JurnalDetail::create([
                'jurnal_id' =>
                    $jurnal->id,

                'akun_id' =>
                    $akunHppId,

                'debit' =>
                    0,

                'kredit' =>
                    $totalHpp,

                'keterangan' =>
                    'Pembalik HPP '
                    . $invoice->nomor_invoice,
            ]);
        }

        if (
            $invoice->tipe_pembayaran
            === 'Kredit'
        ) {
            $this->recordKartuPiutang(
                customerId:
                    (int) $invoice->customer_id,

                tanggal:
                    now(),

                nomorRef:
                    $invoice->nomor_invoice,

                jenis:
                    'PEMBATALAN',

                debit:
                    0,

                kredit:
                    $total,

                keterangan:
                    'Pembatalan invoice '
                    . $invoice->nomor_invoice
            );
        }

        $catatanLama =
            trim(
                (string) (
                    $invoice->catatan
                    ?? ''
                )
            );

        $catatanPembatalan =
            '[PEMBATALAN RESMI]: '
            . $alasan;

        $invoice->update([
            'status_invoice' =>
                'DIBATALKAN',

            'sisa_piutang' =>
                0,

            'catatan' =>
                $catatanLama !== ''
                    ? $catatanLama
                        . "\n"
                        . $catatanPembatalan
                    : $catatanPembatalan,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | KARTU PIUTANG
    |--------------------------------------------------------------------------
    */

    public function recordKartuPiutang(
        int $customerId,
        $tanggal,
        string $nomorRef,
        string $jenis,
        float $debit,
        float $kredit,
        string $keterangan
    ): void {
        if ($customerId <= 0) {
            throw new RuntimeException(
                'Customer Kartu Piutang tidak valid.'
            );
        }

        if (
            $debit < 0
            ||
            $kredit < 0
        ) {
            throw new RuntimeException(
                'Debit/Kredit Kartu Piutang tidak boleh negatif.'
            );
        }

        $lastKartu =
            KartuPiutang::query()
                ->where(
                    'customer_id',
                    $customerId
                )
                ->orderByDesc(
                    'tanggal'
                )
                ->orderByDesc(
                    'id'
                )
                ->first();

        $saldoSebelumnya =
            $lastKartu
                ? (float) $lastKartu
                    ->saldo_akhir
                : 0;

        $saldoAkhirBaru =
            $saldoSebelumnya
            +
            (
                $debit
                -
                $kredit
            );

        KartuPiutang::create([
            'customer_id' =>
                $customerId,

            'tanggal' =>
                $tanggal,

            'nomor_referensi' =>
                $nomorRef,

            'jenis_transaksi' =>
                $jenis,

            'debit' =>
                $debit,

            'kredit' =>
                $kredit,

            'saldo_akhir' =>
                $saldoAkhirBaru,

            'keterangan' =>
                $keterangan,
        ]);
    }
}