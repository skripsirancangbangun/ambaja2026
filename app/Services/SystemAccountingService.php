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
use App\Models\PengaturanAkun;
use App\Models\PengaturanPenomoran;
use App\Models\PenutupanPeriode;
use App\Models\RekeningKasBank;
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
        $countPenjualan = InvoicePenjualan::count();

        $countPembelian = InvoiceSupplier::count();

        $countKas = MutasiKasBank::count();

        $countJurnalManual = Jurnal::query()
            ->where('is_system_generated', false)
            ->count();

        return (
            $countPenjualan === 0
            && $countPembelian === 0
            && $countKas === 0
            && $countJurnalManual === 0
        );
    }

    public function isPeriodLocked($tanggal): bool
    {
        if (!$tanggal) {
            return false;
        }

        $tanggal = Carbon::parse($tanggal);

        $penutupan = PenutupanPeriode::query()
            ->where('bulan', $tanggal->month)
            ->where('tahun', $tanggal->year)
            ->first();

        return (
            $penutupan
            && $penutupan->status === 'LOCKED'
        );
    }

    public function getSystemAccountId(
        string $kunciPengaturan
    ): ?int {
        $setting = PengaturanAkun::query()
            ->where(
                'kunci_pengaturan',
                $kunciPengaturan
            )
            ->first();

        return $setting?->akun_id;
    }

    public function generateDocumentNumber(
        string $jenisDokumen
    ): string {
        $setting = PengaturanPenomoran::query()
            ->where(
                'jenis_dokumen',
                $jenisDokumen
            )
            ->first();

        $prefix = $setting?->prefix ?? 'JU/';

        $digits = $setting?->digits ?? 5;

        /*
         * Nomor jurnal kita ambil berdasarkan ID jurnal.
         * Cukup aman untuk project ini karena ID selalu meningkat.
         */
        if ($jenisDokumen === 'JURNAL_UMUM') {
            $nextId = (Jurnal::max('id') ?? 0) + 1;

            return $prefix
                . str_pad(
                    $nextId,
                    $digits,
                    '0',
                    STR_PAD_LEFT
                );
        }

        if ($jenisDokumen === 'INVOICE_PENJUALAN') {
            $nextId =
                (InvoicePenjualan::max('id') ?? 0)
                + 1;

            return $prefix
                . str_pad(
                    $nextId,
                    $digits,
                    '0',
                    STR_PAD_LEFT
                );
        }

        if ($jenisDokumen === 'RETUR_PENJUALAN') {
            $nextId =
                (ReturPenjualan::max('id') ?? 0)
                + 1;

            return $prefix
                . str_pad(
                    $nextId,
                    $digits,
                    '0',
                    STR_PAD_LEFT
                );
        }

        if ($jenisDokumen === 'KWITANSI') {
            $nextId =
                (PenerimaanPiutang::max('id') ?? 0)
                + 1;

            return $prefix
                . str_pad(
                    $nextId,
                    $digits,
                    '0',
                    STR_PAD_LEFT
                );
        }

        return $prefix
            . str_pad(
                1,
                $digits,
                '0',
                STR_PAD_LEFT
            );
    }

    /*
    |--------------------------------------------------------------------------
    | PENJUALAN
    |--------------------------------------------------------------------------
    */

    /**
     * Jurnal otomatis Invoice Penjualan.
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

        /*
         * Hapus jurnal sebelumnya jika method
         * dipanggil ulang.
         */
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

        /*
         * Jika tunai, prioritaskan akun rekening
         * Kas/Bank yang benar-benar dipilih.
         */
        if ($invoice->tipe_pembayaran === 'Tunai') {
            $akunDebitId =
                $invoice->rekeningKasBank?->akun_id
                ?? $this->getSystemAccountId(
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

        $akunHppId =
            $this->getSystemAccountId(
                'AKUN_HPP'
            );

        $akunPersediaanId =
            $this->getSystemAccountId(
                'AKUN_PERSEDIAAN'
            );

        if (
            !$akunDebitId
            || !$akunPendapatanId
        ) {
            return;
        }

        $invoice->loadMissing([
            'customer',
            'items.barang',
            'rekeningKasBank',
        ]);

        /*
         * PERHATIAN:
         * Perhitungan HPP ini masih mengikuti
         * struktur modul Penjualan temanmu.
         *
         * Nanti perlu disatukan lagi dengan
         * InventoryService/StokGudang.
         */
        $totalHpp = 0;

        foreach ($invoice->items as $item) {
            $hargaBeli =
                $item->barang?->harga_beli_rata_rata
                ?? $item->barang?->harga_beli
                ?? 0;

            $totalHpp +=
                (float) $item->qty
                * (float) $hargaBeli;
        }

        $totalDebit =
            (float) $invoice->total
            + (
                (float) $invoice->diskon > 0
                && $akunDiskonId
                    ? (float) $invoice->diskon
                    : 0
            )
            + (
                $totalHpp > 0
                && $akunHppId
                && $akunPersediaanId
                    ? $totalHpp
                    : 0
            );

        $totalKredit =
            (float) $invoice->subtotal
            + (
                $totalHpp > 0
                && $akunHppId
                && $akunPersediaanId
                    ? $totalHpp
                    : 0
            );

        $jurnal = Jurnal::create([
            'nomor_jurnal' =>
                $this->generateDocumentNumber(
                    'JURNAL_UMUM'
                ),

            'tanggal' =>
                $invoice->tanggal_invoice,

            'keterangan' =>
                'Jurnal Penjualan '
                . $invoice->nomor_invoice
                . ' - '
                . (
                    $invoice->customer?->nama_customer
                    ?? 'Customer'
                ),

            'is_system_generated' => true,

            'referensi_type' =>
                'INVOICE_PENJUALAN',

            'referensi_id' =>
                $invoice->id,

            'total_debit' =>
                $totalDebit,

            'total_kredit' =>
                $totalKredit,

            'is_posted' => true,

            'created_by' =>
                auth()->id(),
        ]);

        /*
         * Debit Kas / Piutang
         */
        JurnalDetail::create([
            'jurnal_id' =>
                $jurnal->id,

            'akun_id' =>
                $akunDebitId,

            'debit' =>
                $invoice->total,

            'kredit' =>
                0,

            'keterangan' =>
                $invoice->tipe_pembayaran === 'Tunai'
                    ? 'Penerimaan penjualan tunai '
                        . $invoice->nomor_invoice
                    : 'Piutang penjualan '
                        . $invoice->nomor_invoice,
        ]);

        /*
         * Debit diskon jika ada.
         */
        if (
            (float) $invoice->diskon > 0
            && $akunDiskonId
        ) {
            JurnalDetail::create([
                'jurnal_id' =>
                    $jurnal->id,

                'akun_id' =>
                    $akunDiskonId,

                'debit' =>
                    $invoice->diskon,

                'kredit' =>
                    0,

                'keterangan' =>
                    'Diskon penjualan '
                    . $invoice->nomor_invoice,
            ]);
        }

        /*
         * Kredit Pendapatan.
         */
        JurnalDetail::create([
            'jurnal_id' =>
                $jurnal->id,

            'akun_id' =>
                $akunPendapatanId,

            'debit' =>
                0,

            'kredit' =>
                $invoice->subtotal,

            'keterangan' =>
                'Pendapatan penjualan '
                . $invoice->nomor_invoice,
        ]);

        /*
         * HPP - Persediaan.
         */
        if (
            $totalHpp > 0
            && $akunHppId
            && $akunPersediaanId
        ) {
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

        /*
         * Buku pembantu Piutang.
         */
        if (
            $invoice->tipe_pembayaran === 'Kredit'
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
                    (float) $invoice->total,

                kredit:
                    0,

                keterangan:
                    'Penjualan kredit '
                    . $invoice->nomor_invoice
            );
        }
    }

    /**
     * Jurnal otomatis penerimaan piutang.
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
            ?? $this->getSystemAccountId(
                'AKUN_KAS_UTAMA'
            );

        $akunPiutangId =
            $this->getSystemAccountId(
                'AKUN_PIUTANG_DAGANG'
            );

        if (
            !$akunKasId
            || !$akunPiutangId
        ) {
            return;
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

        $total =
            (float) $penerimaan->total_dibayar;

        $jurnal = Jurnal::create([
            'nomor_jurnal' =>
                $this->generateDocumentNumber(
                    'JURNAL_UMUM'
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

    /**
     * Jurnal Retur Penjualan.
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
            'items.barang',
        ]);

        $akunPiutangId =
            $this->getSystemAccountId(
                'AKUN_PIUTANG_DAGANG'
            );

        $akunPendapatanId =
            $this->getSystemAccountId(
                'AKUN_PENDAPATAN_PENJUALAN'
            );

        $akunPajakPpnId =
            Akun::query()
                ->where(
                    'nama_akun',
                    'like',
                    '%PPN%'
                )
                ->first()
                ?->id;

        $akunHppId =
            $this->getSystemAccountId(
                'AKUN_HPP'
            );

        $akunPersediaanId =
            $this->getSystemAccountId(
                'AKUN_PERSEDIAAN'
            );

        if (
            !$akunPiutangId
            || !$akunPendapatanId
        ) {
            return;
        }

        $totalHppRetur = 0;

        foreach ($retur->items as $item) {
            $hargaBeli =
                $item->barang?->harga_beli_rata_rata
                ?? $item->barang?->harga_beli
                ?? 0;

            $totalHppRetur +=
                (float) $item->qty
                * (float) $hargaBeli;
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

        $totalDebit =
            (float) $retur->subtotal
            + (float) $retur->pajak
            + (
                $totalHppRetur > 0
                    ? $totalHppRetur
                    : 0
            );

        $totalKredit =
            (float) $retur->total
            + (
                $totalHppRetur > 0
                    ? $totalHppRetur
                    : 0
            );

        $jurnal = Jurnal::create([
            'nomor_jurnal' =>
                $this->generateDocumentNumber(
                    'JURNAL_UMUM'
                ),

            'tanggal' =>
                $retur->tanggal_retur,

            'keterangan' =>
                'Retur Penjualan '
                . $retur->nomor_retur
                . ' - '
                . (
                    $retur->customer?->nama_customer
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
                $akunPendapatanId,

            'debit' =>
                $retur->subtotal,

            'kredit' =>
                0,

            'keterangan' =>
                'Retur penjualan '
                . $retur->nomor_retur,
        ]);

        if (
            (float) $retur->pajak > 0
            && $akunPajakPpnId
        ) {
            JurnalDetail::create([
                'jurnal_id' =>
                    $jurnal->id,

                'akun_id' =>
                    $akunPajakPpnId,

                'debit' =>
                    $retur->pajak,

                'kredit' =>
                    0,

                'keterangan' =>
                    'Koreksi PPN retur '
                    . $retur->nomor_retur,
            ]);
        }

        JurnalDetail::create([
            'jurnal_id' =>
                $jurnal->id,

            'akun_id' =>
                $akunPiutangId,

            'debit' =>
                0,

            'kredit' =>
                $retur->total,

            'keterangan' =>
                'Pengurangan piutang retur '
                . $retur->nomor_retur,
        ]);

        if (
            $totalHppRetur > 0
            && $akunPersediaanId
            && $akunHppId
        ) {
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
                (float) $retur->total,

            keterangan:
                'Retur Penjualan '
                . $retur->nomor_retur
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PEMBATALAN INVOICE
    |--------------------------------------------------------------------------
    */

    public function cancelInvoicePenjualan(
        InvoicePenjualan $invoice,
        string $alasan
    ): void {
        $invoice->loadMissing([
            'customer',
            'rekeningKasBank.akun',
        ]);

        $akunDebitId =
            $invoice->tipe_pembayaran === 'Tunai'
                ? (
                    $invoice
                        ->rekeningKasBank
                        ?->akun_id
                    ?? $this->getSystemAccountId(
                        'AKUN_KAS_UTAMA'
                    )
                )
                : $this->getSystemAccountId(
                    'AKUN_PIUTANG_DAGANG'
                );

        $akunPendapatanId =
            $this->getSystemAccountId(
                'AKUN_PENDAPATAN_PENJUALAN'
            );

        if (
            !$akunDebitId
            || !$akunPendapatanId
        ) {
            throw new RuntimeException(
                'Pengaturan akun pembatalan invoice belum lengkap.'
            );
        }

        $total =
            (float) $invoice->total;

        $jurnal = Jurnal::create([
            'nomor_jurnal' =>
                $this->generateDocumentNumber(
                    'JURNAL_UMUM'
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
                $akunPendapatanId,

            'debit' =>
                $invoice->subtotal,

            'kredit' =>
                0,

            'keterangan' =>
                'Pembalik pendapatan '
                . $invoice->nomor_invoice,
        ]);

        JurnalDetail::create([
            'jurnal_id' =>
                $jurnal->id,

            'akun_id' =>
                $akunDebitId,

            'debit' =>
                0,

            'kredit' =>
                $invoice->total,

            'keterangan' =>
                'Pembalik Kas/Piutang '
                . $invoice->nomor_invoice,
        ]);

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

        $invoice->update([
            'status_pembayaran' =>
                'DIBATALKAN',

            'catatan' =>
                (
                    $invoice->catatan
                        ? $invoice->catatan
                            . "\n"
                        : ''
                )
                . '[PEMBATALAN RESMI]: '
                . $alasan,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | UANG KELUAR
    |--------------------------------------------------------------------------
    */

    /**
     * Jurnal otomatis Uang Keluar.
     *
     * Contoh:
     *
     * Dr Beban Gaji               1.000.000
     * Dr Beban Sewa              30.000.000
     *      Cr Bank BCA           31.000.000
     */
    public function autoJournalUangKeluar(
        UangKeluar $uangKeluar
    ): void {
        $uangKeluar->loadMissing([
            'details.akun',
            'rekeningKasBank.akun',
        ]);

        if (
            $this->isPeriodLocked(
                $uangKeluar->tanggal
            )
        ) {
            throw new RuntimeException(
                'Periode accounting untuk tanggal transaksi ini sudah ditutup.'
            );
        }

        if (
            !$uangKeluar->rekeningKasBank
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
                (float) $detail->jumlah <= 0
            ) {
                throw new RuntimeException(
                    'Nominal rincian Uang Keluar harus lebih besar dari 0.'
                );
            }
        }

        /*
         * Cegah jurnal ganda.
         */
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

        $jurnal = Jurnal::create([
            'nomor_jurnal' =>
                $this->generateDocumentNumber(
                    'JURNAL_UMUM'
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

        /*
         * DEBIT semua akun biaya.
         */
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

        /*
         * KREDIT Kas/Bank sebesar total.
         */
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
        $lastKartu =
            KartuPiutang::query()
                ->where(
                    'customer_id',
                    $customerId
                )
                ->orderByDesc('tanggal')
                ->orderByDesc('id')
                ->first();

        $saldoSebelumnya =
            $lastKartu
                ? (float)
                    $lastKartu->saldo_akhir
                : 0;

        $saldoAkhirBaru =
            $saldoSebelumnya
            + ($debit - $kredit);

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