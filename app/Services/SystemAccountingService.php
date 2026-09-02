<?php

namespace App\Services;

use App\Models\Akun;
use App\Models\Barang;
use App\Models\InvoicePenjualan;
use App\Models\InvoiceSupplier;
use App\Models\MutasiKasBank;
use App\Models\Jurnal;
use App\Models\JurnalDetail;
use App\Models\KartuPiutang;
use App\Models\PenerimaanPiutang;
use App\Models\PengaturanAkun;
use App\Models\PengaturanPenomoran;
use App\Models\PenutupanPeriode;
use App\Models\ReturPenjualan;
use Carbon\Carbon;

class SystemAccountingService
{
    public function canEditOpeningBalance(): bool
    {
        $countPenjualan = InvoicePenjualan::count();
        $countPembelian = InvoiceSupplier::count();
        $countKas = MutasiKasBank::count();
        $countJurnalManual = Jurnal::where('is_system_generated', false)->count();

        return ($countPenjualan === 0 && $countPembelian === 0 && $countKas === 0 && $countJurnalManual === 0);
    }

    public function isPeriodLocked($tanggal): bool
    {
        if (!$tanggal) return false;

        $dt = Carbon::parse($tanggal);
        $penutupan = PenutupanPeriode::where('bulan', $dt->month)
            ->where('tahun', $dt->year)
            ->first();

        return $penutupan && $penutupan->status === 'LOCKED';
    }

    public function getSystemAccountId(string $kunciPengaturan): ?int
    {
        $setting = PengaturanAkun::where('kunci_pengaturan', $kunciPengaturan)->first();
        return $setting?->akun_id;
    }

    public function generateDocumentNumber(string $jenisDokumen): string
    {
        $setting = PengaturanPenomoran::where('jenis_dokumen', $jenisDokumen)->first();
        $prefix = $setting?->prefix ?? 'DOC-';
        $digits = $setting?->digits ?? 5;

        $lastId = 1;
        if ($jenisDokumen === 'INVOICE_PENJUALAN') {
            $lastId = (InvoicePenjualan::max('id') ?? 0) + 1;
        } elseif ($jenisDokumen === 'JURNAL_UMUM') {
            $lastId = (Jurnal::max('id') ?? 0) + 1;
        } elseif ($jenisDokumen === 'RETUR_PENJUALAN') {
            $lastId = (ReturPenjualan::max('id') ?? 0) + 1;
        } elseif ($jenisDokumen === 'KWITANSI') {
            $lastId = (PenerimaanPiutang::max('id') ?? 0) + 1;
        }

        return $prefix . str_pad($lastId, $digits, '0', STR_PAD_LEFT);
    }

    /**
     * Auto-Journaling PERPETUAL Invoice Penjualan
     */
    public function autoJournalInvoicePenjualan(InvoicePenjualan $invoice): void
    {
        Jurnal::where('ref_type', 'INVOICE_PENJUALAN')
            ->where('ref_id', $invoice->id)
            ->delete();

        $akunDebitId = $invoice->tipe_pembayaran === 'Tunai'
            ? $this->getSystemAccountId('AKUN_KAS_UTAMA')
            : $this->getSystemAccountId('AKUN_PIUTANG_DAGANG');

        $akunPendapatanId = $this->getSystemAccountId('AKUN_PENDAPATAN_PENJUALAN');
        $akunDiskonId = $this->getSystemAccountId('AKUN_DISKON_PENJUALAN');
        $akunHppId = $this->getSystemAccountId('AKUN_HPP');
        $akunPersediaanId = $this->getSystemAccountId('AKUN_PERSEDIAAN');

        if (!$akunDebitId || !$akunPendapatanId) return;

        $noJurnal = $this->generateDocumentNumber('JURNAL_UMUM');

        $jurnal = Jurnal::create([
            'nomor_jurnal' => $noJurnal,
            'tanggal' => $invoice->tanggal_invoice,
            'keterangan' => 'Jurnal Perpetual Penjualan #' . $invoice->nomor_invoice . ' (' . ($invoice->customer?->nama_customer ?? 'Customer') . ')',
            'is_posted' => true,
            'is_system_generated' => true,
            'ref_type' => 'INVOICE_PENJUALAN',
            'ref_id' => $invoice->id,
            'created_by_user_id' => auth()->id() ?? 1,
        ]);

        // 1. Debit Kas/Piutang
        JurnalDetail::create([
            'jurnal_id' => $jurnal->id,
            'akun_id' => $akunDebitId,
            'debit' => $invoice->total,
            'kredit' => 0,
            'catatan' => 'Penerimaan/Tagihan ' . $invoice->nomor_invoice,
        ]);

        // 2. Debit Diskon
        if ($invoice->diskon > 0 && $akunDiskonId) {
            JurnalDetail::create([
                'jurnal_id' => $jurnal->id,
                'akun_id' => $akunDiskonId,
                'debit' => $invoice->diskon,
                'kredit' => 0,
                'catatan' => 'Diskon ' . $invoice->nomor_invoice,
            ]);
        }

        // 3. Kredit Pendapatan Penjualan
        JurnalDetail::create([
            'jurnal_id' => $jurnal->id,
            'akun_id' => $akunPendapatanId,
            'debit' => 0,
            'kredit' => $invoice->subtotal,
            'catatan' => 'Pendapatan ' . $invoice->nomor_invoice,
        ]);

        // 4. Hitung HPP Perpetual dari Items
        $totalHpp = 0;
        foreach ($invoice->items as $item) {
            $hargaBeliAvg = $item->barang?->harga_beli_rata_rata ?? $item->barang?->harga_beli ?? 0;
            $totalHpp += ($item->qty * $hargaBeliAvg);
        }

        // 5. Debit HPP & Kredit Persediaan
        if ($totalHpp > 0 && $akunHppId && $akunPersediaanId) {
            JurnalDetail::create([
                'jurnal_id' => $jurnal->id,
                'akun_id' => $akunHppId,
                'debit' => $totalHpp,
                'kredit' => 0,
                'catatan' => 'Pengakuan HPP Penjualan ' . $invoice->nomor_invoice,
            ]);

            JurnalDetail::create([
                'jurnal_id' => $jurnal->id,
                'akun_id' => $akunPersediaanId,
                'debit' => 0,
                'kredit' => $totalHpp,
                'catatan' => 'Pengurangan Persediaan Barang ' . $invoice->nomor_invoice,
            ]);
        }

        // 6. Catat Mutasi ke Kartu Piutang Customer (Jika Kredit)
        if ($invoice->tipe_pembayaran === 'Kredit') {
            $this->recordKartuPiutang(
                $invoice->customer_id,
                $invoice->tanggal_invoice,
                $invoice->nomor_invoice,
                'PENJUALAN',
                $invoice->total,
                0,
                'Penjualan Kredit Faktur #' . $invoice->nomor_invoice
            );
        }
    }

    /**
     * Auto-Journaling Penerimaan Piutang Customer
     */
    public function autoJournalPenerimaanPiutang(PenerimaanPiutang $penerimaan): void
    {
        $akunKasId = RekeningKasBank::find($penerimaan->rekening_kas_bank_id)?->akun_id
            ?? $this->getSystemAccountId('AKUN_KAS_UTAMA');
        $akunPiutangId = $this->getSystemAccountId('AKUN_PIUTANG_DAGANG');

        if (!$akunKasId || !$akunPiutangId) return;

        $noJurnal = $this->generateDocumentNumber('JURNAL_UMUM');

        $jurnal = Jurnal::create([
            'nomor_jurnal' => $noJurnal,
            'tanggal' => $penerimaan->tanggal,
            'keterangan' => 'Jurnal Pelunasan Piutang #' . $penerimaan->nomor_penerimaan . ' (' . ($penerimaan->customer?->nama_customer ?? 'Customer') . ')',
            'is_posted' => true,
            'is_system_generated' => true,
            'ref_type' => 'PENERIMAAN_PIUTANG',
            'ref_id' => $penerimaan->id,
            'created_by_user_id' => auth()->id() ?? 1,
        ]);

        JurnalDetail::create([
            'jurnal_id' => $jurnal->id,
            'akun_id' => $akunKasId,
            'debit' => $penerimaan->total_dibayar,
            'kredit' => 0,
            'catatan' => 'Penerimaan Kas/Bank Pembayaran Piutang ' . $penerimaan->nomor_penerimaan,
        ]);

        JurnalDetail::create([
            'jurnal_id' => $jurnal->id,
            'akun_id' => $akunPiutangId,
            'debit' => 0,
            'kredit' => $penerimaan->total_dibayar,
            'catatan' => 'Pelunasan Piutang Dagang ' . $penerimaan->customer?->nama_customer,
        ]);

        // Record ke Kartu Piutang Customer
        $this->recordKartuPiutang(
            $penerimaan->customer_id,
            $penerimaan->tanggal,
            $penerimaan->nomor_penerimaan,
            'PEMBAYARAN',
            0,
            $penerimaan->total_dibayar,
            'Pelunasan/Pembayaran Piutang ' . $penerimaan->nomor_penerimaan
        );
    }

    /**
     * Auto-Journaling NOTA RETUR PENJUALAN (with PPN Reduction)
     */
    public function autoJournalReturPenjualan(ReturPenjualan $retur): void
    {
        $akunPiutangId = $this->getSystemAccountId('AKUN_PIUTANG_DAGANG');
        $akunPendapatanId = $this->getSystemAccountId('AKUN_PENDAPATAN_PENJUALAN');
        $akunPajakPpnId = Akun::where('nama_akun', 'like', '%PPN%')->first()?->id;
        $akunHppId = $this->getSystemAccountId('AKUN_HPP');
        $akunPersediaanId = $this->getSystemAccountId('AKUN_PERSEDIAAN');

        if (!$akunPiutangId || !$akunPendapatanId) return;

        $noJurnal = $this->generateDocumentNumber('JURNAL_UMUM');

        $jurnal = Jurnal::create([
            'nomor_jurnal' => $noJurnal,
            'tanggal' => $retur->tanggal_retur,
            'keterangan' => 'Jurnal Retur Penjualan #' . $retur->nomor_retur . ' (' . ($retur->customer?->nama_customer ?? '') . ')',
            'is_posted' => true,
            'is_system_generated' => true,
            'ref_type' => 'RETUR_PENJUALAN',
            'ref_id' => $retur->id,
            'created_by_user_id' => auth()->id() ?? 1,
        ]);

        // 1. Debit Pendapatan/Retur Penjualan (Subtotal Retur)
        JurnalDetail::create([
            'jurnal_id' => $jurnal->id,
            'akun_id' => $akunPendapatanId,
            'debit' => $retur->subtotal,
            'kredit' => 0,
            'catatan' => 'Retur Penjualan ' . $retur->nomor_retur,
        ]);

        // 2. Debit Pajak PPN (PPN ikut berkurang!)
        if ($retur->pajak > 0 && $akunPajakPpnId) {
            JurnalDetail::create([
                'jurnal_id' => $jurnal->id,
                'akun_id' => $akunPajakPpnId,
                'debit' => $retur->pajak,
                'kredit' => 0,
                'catatan' => 'Pengurangan PPN Retur ' . $retur->nomor_retur,
            ]);
        }

        // 3. Kredit Piutang Dagang (Total Retur)
        JurnalDetail::create([
            'jurnal_id' => $jurnal->id,
            'akun_id' => $akunPiutangId,
            'debit' => 0,
            'kredit' => $retur->total,
            'catatan' => 'Pengurangan Piutang Retur ' . $retur->nomor_retur,
        ]);

        // 4. Hitung Restock HPP
        $totalHppRetur = 0;
        foreach ($retur->items as $item) {
            $hargaBeliAvg = $item->barang?->harga_beli_rata_rata ?? $item->barang?->harga_beli ?? 0;
            $totalHppRetur += ($item->qty * $hargaBeliAvg);
        }

        // 5. Debit Persediaan (Barang kembali ke stok) & Kredit HPP
        if ($totalHppRetur > 0 && $akunPersediaanId && $akunHppId) {
            JurnalDetail::create([
                'jurnal_id' => $jurnal->id,
                'akun_id' => $akunPersediaanId,
                'debit' => $totalHppRetur,
                'kredit' => 0,
                'catatan' => 'Pengembalian Barang Retur ke Persediaan ' . $retur->nomor_retur,
            ]);

            JurnalDetail::create([
                'jurnal_id' => $jurnal->id,
                'akun_id' => $akunHppId,
                'debit' => 0,
                'kredit' => $totalHppRetur,
                'catatan' => 'Koreksi HPP Retur ' . $retur->nomor_retur,
            ]);
        }

        // Record ke Kartu Piutang Customer
        $this->recordKartuPiutang(
            $retur->customer_id,
            $retur->tanggal_retur,
            $retur->nomor_retur,
            'RETUR',
            0,
            $retur->total,
            'Retur Penjualan #' . $retur->nomor_retur
        );
    }

    /**
     * Memproses Pembatalan Resmi Invoice Penjualan (Jurnal Pembalik)
     */
    public function cancelInvoicePenjualan(InvoicePenjualan $invoice, string $alasan): void
    {
        $akunDebitId = $invoice->tipe_pembayaran === 'Tunai'
            ? $this->getSystemAccountId('AKUN_KAS_UTAMA')
            : $this->getSystemAccountId('AKUN_PIUTANG_DAGANG');

        $akunPendapatanId = $this->getSystemAccountId('AKUN_PENDAPATAN_PENJUALAN');
        $akunHppId = $this->getSystemAccountId('AKUN_HPP');
        $akunPersediaanId = $this->getSystemAccountId('AKUN_PERSEDIAAN');

        $noJurnal = $this->generateDocumentNumber('JURNAL_UMUM');

        // Jurnal Pembalik Pembatalan
        $jurnal = Jurnal::create([
            'nomor_jurnal' => $noJurnal,
            'tanggal' => now(),
            'keterangan' => 'JURNAL PEMBALIK PEMBATALAN INVOICE #' . $invoice->nomor_invoice . ' (Alasan: ' . $alasan . ')',
            'is_posted' => true,
            'is_system_generated' => true,
            'ref_type' => 'PEMBATALAN_INVOICE',
            'ref_id' => $invoice->id,
            'created_by_user_id' => auth()->id() ?? 1,
        ]);

        // Reverse Pendapatan (Debit)
        JurnalDetail::create([
            'jurnal_id' => $jurnal->id,
            'akun_id' => $akunPendapatanId,
            'debit' => $invoice->subtotal,
            'kredit' => 0,
            'catatan' => 'Pembalik Pendapatan Pembatalan ' . $invoice->nomor_invoice,
        ]);

        // Reverse Piutang/Kas (Kredit)
        JurnalDetail::create([
            'jurnal_id' => $jurnal->id,
            'akun_id' => $akunDebitId,
            'debit' => 0,
            'kredit' => $invoice->total,
            'catatan' => 'Pembalik Piutang/Kas Pembatalan ' . $invoice->nomor_invoice,
        ]);

        // Record ke Kartu Piutang
        if ($invoice->tipe_pembayaran === 'Kredit') {
            $this->recordKartuPiutang(
                $invoice->customer_id,
                now(),
                $invoice->nomor_invoice,
                'PEMBATALAN',
                0,
                $invoice->total,
                'Pembatalan Resmi Invoice #' . $invoice->nomor_invoice
            );
        }

        $invoice->update([
            'status_pembayaran' => 'DIBATALKAN',
            'catatan' => ($invoice->catatan ? $invoice->catatan . "\n" : '') . '[PEMBATALAN RESMI]: ' . $alasan,
        ]);
    }

    /**
     * Mencatat Mutasi Buku Pembantu Piutang (Kartu Piutang Customer)
     */
    public function recordKartuPiutang(int $customerId, $tanggal, string $nomorRef, string $jenis, float $debit, float $kredit, string $keterangan): void
    {
        $lastKartu = KartuPiutang::where('customer_id', $customerId)
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->first();

        $saldoSebelumnya = $lastKartu ? $lastKartu->saldo_akhir : 0;
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
