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

    public function getSystemAccountId(string $kunciPengaturan): ?int
    {
        $setting = PengaturanAkun::query()
            ->where('kunci_pengaturan', $kunciPengaturan)
            ->first();

        return $setting?->akun_id;
    }

    public function generateDocumentNumber(string $jenisDokumen, $tanggal = null): string
    {
        $date = $tanggal ? Carbon::parse($tanggal) : Carbon::now();
        $year = $date->format('Y');
        $month = $date->format('m');

        $key = strtoupper(trim($jenisDokumen));

        $prefixMap = [
            'SO' => ['prefix' => 'SO', 'model' => \App\Models\Penjualan::class, 'date_col' => 'tanggal_penjualan', 'num_col' => 'nomor_penjualan'],
            'SALES_ORDER' => ['prefix' => 'SO', 'model' => \App\Models\Penjualan::class, 'date_col' => 'tanggal_penjualan', 'num_col' => 'nomor_penjualan'],
            'PESANAN_PENJUALAN' => ['prefix' => 'SO', 'model' => \App\Models\Penjualan::class, 'date_col' => 'tanggal_penjualan', 'num_col' => 'nomor_penjualan'],

            'INV' => ['prefix' => 'INV', 'model' => \App\Models\InvoicePenjualan::class, 'date_col' => 'tanggal_invoice', 'num_col' => 'nomor_invoice'],
            'INVOICE_PENJUALAN' => ['prefix' => 'INV', 'model' => \App\Models\InvoicePenjualan::class, 'date_col' => 'tanggal_invoice', 'num_col' => 'nomor_invoice'],

            'PAY' => ['prefix' => 'PAY', 'model' => \App\Models\PenerimaanPiutang::class, 'date_col' => 'tanggal', 'num_col' => 'nomor_penerimaan'],
            'PENERIMAAN_PIUTANG' => ['prefix' => 'PAY', 'model' => \App\Models\PenerimaanPiutang::class, 'date_col' => 'tanggal', 'num_col' => 'nomor_penerimaan'],
            'PEMBAYARAN_PENJUALAN' => ['prefix' => 'PAY', 'model' => \App\Models\PenerimaanPiutang::class, 'date_col' => 'tanggal', 'num_col' => 'nomor_penerimaan'],
            'KWITANSI' => ['prefix' => 'PAY', 'model' => \App\Models\PenerimaanPiutang::class, 'date_col' => 'tanggal', 'num_col' => 'nomor_penerimaan'],

            'RTJ' => ['prefix' => 'RTJ', 'model' => \App\Models\ReturPenjualan::class, 'date_col' => 'tanggal_retur', 'num_col' => 'nomor_retur'],
            'RETUR_PENJUALAN' => ['prefix' => 'RTJ', 'model' => \App\Models\ReturPenjualan::class, 'date_col' => 'tanggal_retur', 'num_col' => 'nomor_retur'],

            'PO' => ['prefix' => 'PO', 'model' => \App\Models\PurchaseOrder::class, 'date_col' => 'tanggal_po', 'num_col' => 'nomor_po'],
            'PURCHASE_ORDER' => ['prefix' => 'PO', 'model' => \App\Models\PurchaseOrder::class, 'date_col' => 'tanggal_po', 'num_col' => 'nomor_po'],
            'PESANAN_PEMBELIAN' => ['prefix' => 'PO', 'model' => \App\Models\PurchaseOrder::class, 'date_col' => 'tanggal_po', 'num_col' => 'nomor_po'],

            'GRN' => ['prefix' => 'GRN', 'model' => \App\Models\PenerimaanBarang::class, 'date_col' => 'tanggal_penerimaan', 'num_col' => 'nomor_penerimaan'],
            'PENERIMAAN_BARANG' => ['prefix' => 'GRN', 'model' => \App\Models\PenerimaanBarang::class, 'date_col' => 'tanggal_penerimaan', 'num_col' => 'nomor_penerimaan'],

            'INV-SUP' => ['prefix' => 'INV-SUP', 'model' => \App\Models\InvoiceSupplier::class, 'date_col' => 'tanggal_invoice', 'num_col' => 'nomor_invoice'],
            'INVOICE_SUPPLIER' => ['prefix' => 'INV-SUP', 'model' => \App\Models\InvoiceSupplier::class, 'date_col' => 'tanggal_invoice', 'num_col' => 'nomor_invoice'],
            'INVOICE_PEMBELIAN' => ['prefix' => 'INV-SUP', 'model' => \App\Models\InvoiceSupplier::class, 'date_col' => 'tanggal_invoice', 'num_col' => 'nomor_invoice'],

            'PAY-SUP' => ['prefix' => 'PAY-SUP', 'model' => \App\Models\PembayaranSupplier::class, 'date_col' => 'tanggal', 'num_col' => 'nomor_pembayaran'],
            'PEMBAYARAN_SUPPLIER' => ['prefix' => 'PAY-SUP', 'model' => \App\Models\PembayaranSupplier::class, 'date_col' => 'tanggal', 'num_col' => 'nomor_pembayaran'],
            'PEMBAYARAN_UTANG' => ['prefix' => 'PAY-SUP', 'model' => \App\Models\PembayaranSupplier::class, 'date_col' => 'tanggal', 'num_col' => 'nomor_pembayaran'],

            'KM' => ['prefix' => 'KM', 'model' => \App\Models\MutasiKasBank::class, 'date_col' => 'tanggal', 'num_col' => 'nomor_mutasi', 'where' => ['tipe_mutasi' => 'MASUK']],
            'UANG_MASUK' => ['prefix' => 'KM', 'model' => \App\Models\MutasiKasBank::class, 'date_col' => 'tanggal', 'num_col' => 'nomor_mutasi', 'where' => ['tipe_mutasi' => 'MASUK']],
            'KAS_MASUK' => ['prefix' => 'KM', 'model' => \App\Models\MutasiKasBank::class, 'date_col' => 'tanggal', 'num_col' => 'nomor_mutasi', 'where' => ['tipe_mutasi' => 'MASUK']],

            'KK' => ['prefix' => 'KK', 'model' => \App\Models\UangKeluar::class, 'date_col' => 'tanggal', 'num_col' => 'nomor_uang_keluar'],
            'UANG_KELUAR' => ['prefix' => 'KK', 'model' => \App\Models\UangKeluar::class, 'date_col' => 'tanggal', 'num_col' => 'nomor_uang_keluar'],
            'KAS_KELUAR' => ['prefix' => 'KK', 'model' => \App\Models\UangKeluar::class, 'date_col' => 'tanggal', 'num_col' => 'nomor_uang_keluar'],

            // JOURNALS DEDICATED PREFIXES BY TRANSACTION TYPE
            'JU' => ['prefix' => 'JU', 'model' => \App\Models\Jurnal::class, 'date_col' => 'tanggal', 'num_col' => 'nomor_jurnal'],
            'JURNAL_UMUM' => ['prefix' => 'JU', 'model' => \App\Models\Jurnal::class, 'date_col' => 'tanggal', 'num_col' => 'nomor_jurnal'],

            'JPJ' => ['prefix' => 'JPJ', 'model' => \App\Models\Jurnal::class, 'date_col' => 'tanggal', 'num_col' => 'nomor_jurnal'],
            'JURNAL_PENJUALAN' => ['prefix' => 'JPJ', 'model' => \App\Models\Jurnal::class, 'date_col' => 'tanggal', 'num_col' => 'nomor_jurnal'],

            'RJL' => ['prefix' => 'RJL', 'model' => \App\Models\Jurnal::class, 'date_col' => 'tanggal', 'num_col' => 'nomor_jurnal'],
            'JURNAL_RETUR_PENJUALAN' => ['prefix' => 'RJL', 'model' => \App\Models\Jurnal::class, 'date_col' => 'tanggal', 'num_col' => 'nomor_jurnal'],

            'JPI' => ['prefix' => 'JPI', 'model' => \App\Models\Jurnal::class, 'date_col' => 'tanggal', 'num_col' => 'nomor_jurnal'],
            'JURNAL_PENERIMAAN_PIUTANG' => ['prefix' => 'JPI', 'model' => \App\Models\Jurnal::class, 'date_col' => 'tanggal', 'num_col' => 'nomor_jurnal'],

            'JPB' => ['prefix' => 'JPB', 'model' => \App\Models\Jurnal::class, 'date_col' => 'tanggal', 'num_col' => 'nomor_jurnal'],
            'JURNAL_PEMBELIAN' => ['prefix' => 'JPB', 'model' => \App\Models\Jurnal::class, 'date_col' => 'tanggal', 'num_col' => 'nomor_jurnal'],

            'RPB' => ['prefix' => 'RPB', 'model' => \App\Models\Jurnal::class, 'date_col' => 'tanggal', 'num_col' => 'nomor_jurnal'],
            'JURNAL_RETUR_PEMBELIAN' => ['prefix' => 'RPB', 'model' => \App\Models\Jurnal::class, 'date_col' => 'tanggal', 'num_col' => 'nomor_jurnal'],

            'JPU' => ['prefix' => 'JPU', 'model' => \App\Models\Jurnal::class, 'date_col' => 'tanggal', 'num_col' => 'nomor_jurnal'],
            'JURNAL_PEMBAYARAN_UTANG' => ['prefix' => 'JPU', 'model' => \App\Models\Jurnal::class, 'date_col' => 'tanggal', 'num_col' => 'nomor_jurnal'],

            'JBT' => ['prefix' => 'JBT', 'model' => \App\Models\Jurnal::class, 'date_col' => 'tanggal', 'num_col' => 'nomor_jurnal'],
            'JURNAL_PEMBATALAN' => ['prefix' => 'JBT', 'model' => \App\Models\Jurnal::class, 'date_col' => 'tanggal', 'num_col' => 'nomor_jurnal'],
        ];

        if (isset($prefixMap[$key])) {
            $config = $prefixMap[$key];
            $prefix = $config['prefix'];

            $dbSetting = PengaturanPenomoran::where('jenis_dokumen', $key)->first();
            if ($dbSetting && !empty($dbSetting->prefix)) {
                $prefix = rtrim($dbSetting->prefix, '-/');
            }

            $model = $config['model'];
            $dateCol = $config['date_col'];
            $numCol = $config['num_col'] ?? 'nomor_jurnal';

            $prefixPattern = sprintf('%s/%s/%s/%%', $prefix, $year, $month);

            $query = $model::query()
                ->whereYear($dateCol, $year)
                ->whereMonth($dateCol, $month);

            if (isset($config['where'])) {
                foreach ($config['where'] as $wCol => $wVal) {
                    $query->where($wCol, $wVal);
                }
            }

            $lastItem = $query->where($numCol, 'like', $prefixPattern)->orderByDesc($numCol)->first();

            if ($lastItem) {
                $lastNumStr = substr($lastItem->$numCol, -4);
                $nextSeq = ((int) $lastNumStr) + 1;
            } else {
                $nextSeq = 1;
            }

            return sprintf('%s/%s/%s/%04d', $prefix, $year, $month, $nextSeq);
        }

        return sprintf('%s/%s/%s/0001', $key, $year, $month);
    }

    /*
    |--------------------------------------------------------------------------
    | PENJUALAN
    |--------------------------------------------------------------------------
    */

    /**
     * Jurnal otomatis Invoice Penjualan.
     */
    public function autoJournalInvoicePenjualan(InvoicePenjualan $invoice): void
    {
        if ($this->isPeriodLocked($invoice->tanggal_invoice)) {
            throw new RuntimeException('Periode accounting untuk tanggal invoice sudah ditutup.');
        }

        // Hapus jurnal lama jika dipanggil ulang
        Jurnal::query()
            ->where('referensi_type', 'INVOICE_PENJUALAN')
            ->where('referensi_id', $invoice->id)
            ->delete();

        if ($invoice->tipe_pembayaran === 'Tunai') {
            $akunDebitId = $invoice->rekeningKasBank?->akun_id
                ?? $this->getSystemAccountId('AKUN_KAS_UTAMA');
        } else {
            $akunDebitId = $this->getSystemAccountId('AKUN_PIUTANG_DAGANG');
        }

        $akunPendapatanId = $this->getSystemAccountId('AKUN_PENDAPATAN_PENJUALAN');
        $akunDiskonId = $this->getSystemAccountId('AKUN_DISKON_PENJUALAN');
        $akunPpnId = $this->getSystemAccountId('AKUN_PPN_PENJUALAN')
            ?? Akun::where('nama_akun', 'like', '%PPN%')->first()?->id;
        $akunHppId = $this->getSystemAccountId('AKUN_HPP');
        $akunPersediaanId = $this->getSystemAccountId('AKUN_PERSEDIAAN');

        if (!$akunDebitId || !$akunPendapatanId) {
            return;
        }

        $invoice->loadMissing([
            'customer',
            'items.barang',
            'rekeningKasBank',
        ]);

        // HPP diambil dari snapshot total_hpp pada items invoice
        $totalHpp = 0;
        foreach ($invoice->items as $item) {
            $totalHpp += (float) ($item->total_hpp > 0 ? $item->total_hpp : ($item->qty * $item->hpp_satuan));
        }

        $diskon = (float) $invoice->diskon;
        $pajak = (float) $invoice->pajak;

        $totalDebit = (float) $invoice->total
            + ($diskon > 0 && $akunDiskonId ? $diskon : 0)
            + ($totalHpp > 0 && $akunHppId && $akunPersediaanId ? $totalHpp : 0);

        $totalKredit = (float) $invoice->subtotal
            + ($pajak > 0 && $akunPpnId ? $pajak : 0)
            + ($totalHpp > 0 && $akunHppId && $akunPersediaanId ? $totalHpp : 0);

        $jurnal = Jurnal::create([
            'nomor_jurnal' => $this->generateDocumentNumber('JURNAL_PENJUALAN', $invoice->tanggal_invoice),
            'tanggal' => $invoice->tanggal_invoice,
            'keterangan' => 'Jurnal Penjualan ' . $invoice->nomor_invoice . ' - ' . ($invoice->customer?->nama_customer ?? 'Customer'),
            'is_system_generated' => true,
            'referensi_type' => 'INVOICE_PENJUALAN',
            'referensi_id' => $invoice->id,
            'total_debit' => $totalDebit,
            'total_kredit' => $totalKredit,
            'is_posted' => true,
            'created_by' => auth()->id(),
        ]);

        // Debit Kas / Piutang
        JurnalDetail::create([
            'jurnal_id' => $jurnal->id,
            'akun_id' => $akunDebitId,
            'debit' => $invoice->total,
            'kredit' => 0,
            'keterangan' => $invoice->tipe_pembayaran === 'Tunai'
                ? 'Penerimaan penjualan tunai ' . $invoice->nomor_invoice
                : 'Piutang penjualan ' . $invoice->nomor_invoice,
        ]);

        // Debit diskon jika ada
        if ($diskon > 0 && $akunDiskonId) {
            JurnalDetail::create([
                'jurnal_id' => $jurnal->id,
                'akun_id' => $akunDiskonId,
                'debit' => $diskon,
                'kredit' => 0,
                'keterangan' => 'Diskon penjualan ' . $invoice->nomor_invoice,
            ]);
        }

        // Kredit Pendapatan
        JurnalDetail::create([
            'jurnal_id' => $jurnal->id,
            'akun_id' => $akunPendapatanId,
            'debit' => 0,
            'kredit' => $invoice->subtotal,
            'keterangan' => 'Pendapatan penjualan ' . $invoice->nomor_invoice,
        ]);

        // Kredit PPN Keluaran jika ada pajak
        if ($pajak > 0 && $akunPpnId) {
            JurnalDetail::create([
                'jurnal_id' => $jurnal->id,
                'akun_id' => $akunPpnId,
                'debit' => 0,
                'kredit' => $pajak,
                'keterangan' => 'PPN Keluaran ' . $invoice->nomor_invoice,
            ]);
        }

        // HPP - Persediaan
        if ($totalHpp > 0 && $akunHppId && $akunPersediaanId) {
            JurnalDetail::create([
                'jurnal_id' => $jurnal->id,
                'akun_id' => $akunHppId,
                'debit' => $totalHpp,
                'kredit' => 0,
                'keterangan' => 'HPP penjualan ' . $invoice->nomor_invoice,
            ]);

            JurnalDetail::create([
                'jurnal_id' => $jurnal->id,
                'akun_id' => $akunPersediaanId,
                'debit' => 0,
                'kredit' => $totalHpp,
                'keterangan' => 'Pengurangan persediaan ' . $invoice->nomor_invoice,
            ]);
        }

        // Record Buku Pembantu Piutang untuk Penjualan Kredit
        if ($invoice->tipe_pembayaran === 'Kredit') {
            $this->recordKartuPiutang(
                customerId: (int) $invoice->customer_id,
                tanggal: $invoice->tanggal_invoice,
                nomorRef: $invoice->nomor_invoice,
                jenis: 'PENJUALAN',
                debit: (float) $invoice->total,
                kredit: 0,
                keterangan: 'Penjualan kredit ' . $invoice->nomor_invoice
            );
        }
    }

    /**
     * Jurnal otomatis penerimaan piutang.
     */
    public function autoJournalPenerimaanPiutang(PenerimaanPiutang $penerimaan): void
    {
        if ($this->isPeriodLocked($penerimaan->tanggal)) {
            throw new RuntimeException('Periode accounting untuk tanggal penerimaan sudah ditutup.');
        }

        $penerimaan->loadMissing([
            'customer',
            'rekeningKasBank.akun',
        ]);

        $akunKasId = $penerimaan->rekeningKasBank?->akun_id
            ?? $this->getSystemAccountId('AKUN_KAS_UTAMA');

        $akunPiutangId = $this->getSystemAccountId('AKUN_PIUTANG_DAGANG');

        if (!$akunKasId || !$akunPiutangId) {
            return;
        }

        Jurnal::query()
            ->where('referensi_type', 'PENERIMAAN_PIUTANG')
            ->where('referensi_id', $penerimaan->id)
            ->delete();

        $total = (float) $penerimaan->total_dibayar;

        $jurnal = Jurnal::create([
            'nomor_jurnal' => $this->generateDocumentNumber('JURNAL_PENERIMAAN_PIUTANG', $penerimaan->tanggal),
            'tanggal' => $penerimaan->tanggal,
            'keterangan' => 'Penerimaan Piutang ' . $penerimaan->nomor_penerimaan . ' - ' . ($penerimaan->customer?->nama_customer ?? 'Customer'),
            'is_system_generated' => true,
            'referensi_type' => 'PENERIMAAN_PIUTANG',
            'referensi_id' => $penerimaan->id,
            'total_debit' => $total,
            'total_kredit' => $total,
            'is_posted' => true,
            'created_by' => auth()->id(),
        ]);

        JurnalDetail::create([
            'jurnal_id' => $jurnal->id,
            'akun_id' => $akunKasId,
            'debit' => $total,
            'kredit' => 0,
            'keterangan' => 'Penerimaan pembayaran piutang ' . $penerimaan->nomor_penerimaan,
        ]);

        JurnalDetail::create([
            'jurnal_id' => $jurnal->id,
            'akun_id' => $akunPiutangId,
            'debit' => 0,
            'kredit' => $total,
            'keterangan' => 'Pelunasan piutang ' . ($penerimaan->customer?->nama_customer ?? ''),
        ]);

        $this->recordKartuPiutang(
            customerId: (int) $penerimaan->customer_id,
            tanggal: $penerimaan->tanggal,
            nomorRef: $penerimaan->nomor_penerimaan,
            jenis: 'PEMBAYARAN',
            debit: 0,
            kredit: $total,
            keterangan: 'Pembayaran piutang ' . $penerimaan->nomor_penerimaan
        );
    }

    /**
     * Jurnal Retur Penjualan (Membedakan Tunai vs Kredit).
     */
    public function autoJournalReturPenjualan(ReturPenjualan $retur): void
    {
        if ($this->isPeriodLocked($retur->tanggal_retur)) {
            throw new RuntimeException('Periode accounting untuk tanggal retur sudah ditutup.');
        }

        $retur->loadMissing([
            'customer',
            'items.barang',
            'rekeningKasBank',
            'invoicePenjualan',
        ]);

        $akunPiutangId = $this->getSystemAccountId('AKUN_PIUTANG_DAGANG');
        $akunPendapatanId = $this->getSystemAccountId('AKUN_PENDAPATAN_PENJUALAN');
        $akunReturId = $this->getSystemAccountId('AKUN_RETUR_PENJUALAN')
            ?? Akun::where('kode_akun', '4.1.02')->first()?->id
            ?? $akunPendapatanId;
        $akunPajakPpnId = $this->getSystemAccountId('AKUN_PPN_PENJUALAN')
            ?? Akun::query()->where('nama_akun', 'like', '%PPN%')->first()?->id;
        $akunHppId = $this->getSystemAccountId('AKUN_HPP');
        $akunPersediaanId = $this->getSystemAccountId('AKUN_PERSEDIAAN');

        if (!$akunPendapatanId) {
            return;
        }

        // HPP retur menggunakan snapshot HPP dari item retur
        $totalHppRetur = 0;
        foreach ($retur->items as $item) {
            $totalHppRetur += (float) ($item->total_hpp > 0 ? $item->total_hpp : ($item->qty * $item->hpp_satuan));
        }

        Jurnal::query()
            ->where('referensi_type', 'RETUR_PENJUALAN')
            ->where('referensi_id', $retur->id)
            ->delete();

        $isTunai = ($retur->tipe_retur === 'Tunai');
        $potongPiutang = (float) $retur->potong_piutang;
        $pengembalianKas = (float) $retur->pengembalian_kas;
        $akunKasId = $retur->rekeningKasBank?->akun_id ?? $this->getSystemAccountId('AKUN_KAS_UTAMA');

        $totalDebit = (float) $retur->subtotal + (float) $retur->pajak + ($totalHppRetur > 0 ? $totalHppRetur : 0);
        $totalKredit = (float) $retur->total + ($totalHppRetur > 0 ? $totalHppRetur : 0);

        $jurnal = Jurnal::create([
            'nomor_jurnal' => $this->generateDocumentNumber('JURNAL_RETUR_PENJUALAN', $retur->tanggal_retur),
            'tanggal' => $retur->tanggal_retur,
            'keterangan' => 'Retur Penjualan ' . $retur->nomor_retur . ' - ' . ($retur->customer?->nama_customer ?? 'Customer'),
            'is_system_generated' => true,
            'referensi_type' => 'RETUR_PENJUALAN',
            'referensi_id' => $retur->id,
            'total_debit' => $totalDebit,
            'total_kredit' => $totalKredit,
            'is_posted' => true,
            'created_by' => auth()->id(),
        ]);

        // Debit Retur Penjualan
        JurnalDetail::create([
            'jurnal_id' => $jurnal->id,
            'akun_id' => $akunReturId,
            'debit' => $retur->subtotal,
            'kredit' => 0,
            'keterangan' => 'Retur penjualan ' . $retur->nomor_retur,
        ]);

        // Debit Pajak jika ada
        if ((float) $retur->pajak > 0 && $akunPajakPpnId) {
            JurnalDetail::create([
                'jurnal_id' => $jurnal->id,
                'akun_id' => $akunPajakPpnId,
                'debit' => $retur->pajak,
                'kredit' => 0,
                'keterangan' => 'Koreksi PPN retur ' . $retur->nomor_retur,
            ]);
        }

        // Kredit: Piutang Dagang dan/atau Kas Bank
        if ($isTunai || $pengembalianKas > 0) {
            if ($potongPiutang > 0 && $akunPiutangId) {
                JurnalDetail::create([
                    'jurnal_id' => $jurnal->id,
                    'akun_id' => $akunPiutangId,
                    'debit' => 0,
                    'kredit' => $potongPiutang,
                    'keterangan' => 'Pengurangan piutang retur ' . $retur->nomor_retur,
                ]);
            }

            if (($isTunai ? $retur->total : $pengembalianKas) > 0 && $akunKasId) {
                JurnalDetail::create([
                    'jurnal_id' => $jurnal->id,
                    'akun_id' => $akunKasId,
                    'debit' => 0,
                    'kredit' => $isTunai ? $retur->total : $pengembalianKas,
                    'keterangan' => 'Pengembalian kas/bank retur ' . $retur->nomor_retur,
                ]);
            }
        } else {
            if ($akunPiutangId) {
                JurnalDetail::create([
                    'jurnal_id' => $jurnal->id,
                    'akun_id' => $akunPiutangId,
                    'debit' => 0,
                    'kredit' => $retur->total,
                    'keterangan' => 'Pengurangan piutang retur ' . $retur->nomor_retur,
                ]);
            }
        }

        // HPP & Persediaan
        if ($totalHppRetur > 0 && $akunPersediaanId && $akunHppId) {
            JurnalDetail::create([
                'jurnal_id' => $jurnal->id,
                'akun_id' => $akunPersediaanId,
                'debit' => $totalHppRetur,
                'kredit' => 0,
                'keterangan' => 'Barang retur kembali ke persediaan ' . $retur->nomor_retur,
            ]);

            JurnalDetail::create([
                'jurnal_id' => $jurnal->id,
                'akun_id' => $akunHppId,
                'debit' => 0,
                'kredit' => $totalHppRetur,
                'keterangan' => 'Koreksi HPP retur ' . $retur->nomor_retur,
            ]);
        }

        // Record Kartu Piutang jika ada potongan piutang
        if ($potongPiutang > 0 || !$isTunai) {
            $kreditKartu = $isTunai ? 0 : ($potongPiutang > 0 ? $potongPiutang : $retur->total);
            if ($kreditKartu > 0) {
                $this->recordKartuPiutang(
                    customerId: (int) $retur->customer_id,
                    tanggal: $retur->tanggal_retur,
                    nomorRef: $retur->nomor_retur,
                    jenis: 'RETUR',
                    debit: 0,
                    kredit: $kreditKartu,
                    keterangan: 'Retur Penjualan ' . $retur->nomor_retur
                );
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | PEMBATALAN INVOICE
    |--------------------------------------------------------------------------
    */

    public function cancelInvoicePenjualan(InvoicePenjualan $invoice, string $alasan): void
    {
        $invoice->loadMissing([
            'customer',
            'rekeningKasBank.akun',
        ]);

        $akunDebitId = $invoice->tipe_pembayaran === 'Tunai'
            ? ($invoice->rekeningKasBank?->akun_id ?? $this->getSystemAccountId('AKUN_KAS_UTAMA'))
            : $this->getSystemAccountId('AKUN_PIUTANG_DAGANG');

        $akunPendapatanId = $this->getSystemAccountId('AKUN_PENDAPATAN_PENJUALAN');

        if (!$akunDebitId || !$akunPendapatanId) {
            throw new RuntimeException('Pengaturan akun pembatalan invoice belum lengkap.');
        }

        $total = (float) $invoice->total;

        $jurnal = Jurnal::create([
            'nomor_jurnal' => $this->generateDocumentNumber('JURNAL_PEMBATALAN', now()),
            'tanggal' => now(),
            'keterangan' => 'Pembatalan Invoice ' . $invoice->nomor_invoice . ' - ' . $alasan,
            'is_system_generated' => true,
            'referensi_type' => 'PEMBATALAN_INVOICE',
            'referensi_id' => $invoice->id,
            'total_debit' => $total,
            'total_kredit' => $total,
            'is_posted' => true,
            'created_by' => auth()->id(),
        ]);

        JurnalDetail::create([
            'jurnal_id' => $jurnal->id,
            'akun_id' => $akunPendapatanId,
            'debit' => $invoice->subtotal,
            'kredit' => 0,
            'keterangan' => 'Pembalik pendapatan ' . $invoice->nomor_invoice,
        ]);

        JurnalDetail::create([
            'jurnal_id' => $jurnal->id,
            'akun_id' => $akunDebitId,
            'debit' => 0,
            'kredit' => $invoice->total,
            'keterangan' => 'Pembalik Kas/Piutang ' . $invoice->nomor_invoice,
        ]);

        if ($invoice->tipe_pembayaran === 'Kredit') {
            $this->recordKartuPiutang(
                customerId: (int) $invoice->customer_id,
                tanggal: now(),
                nomorRef: $invoice->nomor_invoice,
                jenis: 'PEMBATALAN',
                debit: 0,
                kredit: $total,
                keterangan: 'Pembatalan invoice ' . $invoice->nomor_invoice
            );
        }

        $invoice->update([
            'status_pembayaran' => 'DIBATALKAN',
            'sisa_piutang' => 0,
            'catatan' => ($invoice->catatan ? $invoice->catatan . "\n" : '') . '[PEMBATALAN RESMI]: ' . $alasan,
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
        $lastKartu = KartuPiutang::query()
            ->where('customer_id', $customerId)
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->first();

        $saldoSebelumnya = $lastKartu ? (float) $lastKartu->saldo_akhir : 0;
        $saldoAkhirBaru = $saldoSebelumnya + ($debit - $kredit);

        KartuPiutang::create([
            'customer_id' => $customerId,
            'tanggal' => $tanggal,
            'nomor_referensi' => $nomorRef,
            'jenis_transaksi' => $jenis,
            'debit' => $debit,
            'kredit' => $kredit,
            'saldo_akhir' => $saldoAkhirBaru,
            'keterangan' => $keterangan,
        ]);
    }
}