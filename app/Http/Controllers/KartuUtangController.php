<?php

namespace App\Http\Controllers;

use App\Models\InvoiceSupplier;
use App\Models\InvoiceSupplierUangMukaAllocation;
use App\Models\PembayaranSupplier;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class KartuUtangController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date'],
        ]);

        $supplierId = $validated['supplier_id'] ?? null;
        $tanggalMulai = $validated['tanggal_mulai'] ?? now()->startOfYear()->toDateString();
        $tanggalSelesai = $validated['tanggal_selesai'] ?? now()->toDateString();

        if ($tanggalMulai > $tanggalSelesai) {
            throw ValidationException::withMessages([
                'tanggal_selesai' => 'Tanggal akhir harus sama dengan atau setelah tanggal awal.',
            ]);
        }

        // Supplier yang nonaktif tetap ditampilkan agar histori lama dapat diperiksa.
        $suppliers = Supplier::query()
            ->orderBy('nama_supplier')
            ->get(['id', 'kode_supplier', 'nama_supplier', 'telepon', 'is_active']);

        $selectedSupplier = $supplierId
            ? $suppliers->firstWhere('id', (int) $supplierId)
            : null;

        $transactions = collect();
        $saldoAwal = 0.0;
        $totalInvoicePeriode = 0.0;
        $totalPengurangPeriode = 0.0;
        $saldoAkhirPeriode = 0.0;
        $sisaUtangSaatIni = 0.0;
        $kreditSupplierSaatIni = 0.0;

        if ($supplierId) {
            $transactions = $this->buildTransactions(
                (int) $supplierId,
                $tanggalSelesai
            );

            $priorTransactions = $transactions
                ->filter(fn (array $row) => $row['tanggal'] < $tanggalMulai)
                ->values();

            $periodTransactions = $transactions
                ->filter(fn (array $row) =>
                    $row['tanggal'] >= $tanggalMulai
                    && $row['tanggal'] <= $tanggalSelesai
                )
                ->sortBy(fn (array $row) => sprintf(
                    '%s|%02d|%012d',
                    $row['tanggal'],
                    $row['urutan'],
                    $row['id']
                ))
                ->values();

            $saldoAwal = round(
                (float) $priorTransactions->sum('kredit')
                - (float) $priorTransactions->sum('debit'),
                2
            );

            $totalInvoicePeriode = round(
                (float) $periodTransactions->sum('kredit'),
                2
            );

            $totalPengurangPeriode = round(
                (float) $periodTransactions->sum('debit'),
                2
            );

            $runningBalance = $saldoAwal;

            $rows = $periodTransactions->map(function (array $row) use (&$runningBalance) {
                $runningBalance = round(
                    $runningBalance + $row['kredit'] - $row['debit'],
                    2
                );

                $row['saldo'] = $runningBalance;

                return (object) $row;
            });

            $saldoAkhirPeriode = round($runningBalance, 2);

            $sisaUtangSaatIni = round(
                (float) InvoiceSupplier::query()
                    ->where('supplier_id', $supplierId)
                    ->sum('sisa_utang'),
                2
            );

            $kreditSupplierSaatIni = round(
                (float) InvoiceSupplier::query()
                    ->where('supplier_id', $supplierId)
                    ->sum('kredit_supplier'),
                2
            );
        } else {
            $rows = collect();
        }

        return view('utang.kartu-utang', compact(
            'suppliers',
            'supplierId',
            'selectedSupplier',
            'tanggalMulai',
            'tanggalSelesai',
            'saldoAwal',
            'totalInvoicePeriode',
            'totalPengurangPeriode',
            'saldoAkhirPeriode',
            'sisaUtangSaatIni',
            'kreditSupplierSaatIni',
            'rows'
        ));
    }

    /**
     * Susun mutasi buku pembantu utang dari transaksi sumber yang sudah ada.
     * Tidak membuat tabel ledger kedua agar saldo tidak bergantung pada sinkronisasi manual.
     *
     * Kredit = invoice supplier yang menambah kewajiban.
     * Debit = pembayaran, alokasi uang muka, dan bagian retur yang benar-benar
     * mengurangi utang. Kredit supplier dari retur lebih bayar tidak mengurangi
     * saldo utang; nilainya ditampilkan terpisah sebagai saldo kredit supplier.
     */
    private function buildTransactions(int $supplierId, string $tanggalSelesai): Collection
    {
        $rows = collect();

        $invoices = InvoiceSupplier::query()
            ->where('supplier_id', $supplierId)
            ->whereDate('tanggal_invoice', '<=', $tanggalSelesai)
            ->get([
                'id',
                'tanggal_invoice',
                'nomor_invoice_internal',
                'nomor_invoice_supplier',
                'total',
            ]);

        foreach ($invoices as $invoice) {
            $rows->push([
                'id' => (int) $invoice->id,
                'tanggal' => $invoice->tanggal_invoice->toDateString(),
                'nomor_referensi' => $invoice->nomor_invoice_internal,
                'jenis_transaksi' => 'INVOICE SUPPLIER',
                'keterangan' => 'Invoice supplier: ' . $invoice->nomor_invoice_supplier,
                'debit' => 0.0,
                'kredit' => round((float) $invoice->total, 2),
                'urutan' => 10,
            ]);
        }

        $allocations = InvoiceSupplierUangMukaAllocation::query()
            ->with([
                'invoiceSupplier:id,supplier_id,nomor_invoice_internal',
                'pembayaranPOSupplier:id,nomor_pembayaran,status',
            ])
            ->whereDate('tanggal_alokasi', '<=', $tanggalSelesai)
            ->whereHas('invoiceSupplier', fn ($query) =>
                $query->where('supplier_id', $supplierId)
            )
            ->whereHas('pembayaranPOSupplier', fn ($query) =>
                $query->where('status', 'TERCATAT')
            )
            ->orderBy('tanggal_alokasi')
            ->orderBy('id')
            ->get();

        foreach ($allocations as $allocation) {
            $amount = round((float) $allocation->jumlah_alokasi, 2);

            if ($amount <= 0) {
                continue;
            }

            $invoiceNumber = $allocation->invoiceSupplier?->nomor_invoice_internal ?? '-';
            $paymentNumber = $allocation->pembayaranPOSupplier?->nomor_pembayaran ?? '-';

            $rows->push([
                'id' => (int) $allocation->id,
                'tanggal' => $allocation->tanggal_alokasi->toDateString(),
                'nomor_referensi' => $paymentNumber,
                'jenis_transaksi' => 'ALOKASI UANG MUKA',
                'keterangan' => 'Uang muka PO dialokasikan ke invoice ' . $invoiceNumber,
                'debit' => $amount,
                'kredit' => 0.0,
                'urutan' => 20,
            ]);
        }

        $payments = PembayaranSupplier::query()
            ->with('invoiceSupplier:id,supplier_id,nomor_invoice_internal')
            ->where('supplier_id', $supplierId)
            ->whereDate('tanggal_pembayaran', '<=', $tanggalSelesai)
            ->orderBy('tanggal_pembayaran')
            ->orderBy('id')
            ->get([
                'id',
                'supplier_id',
                'invoice_supplier_id',
                'tanggal_pembayaran',
                'nomor_pembayaran',
                'jumlah_bayar',
                'metode_pembayaran',
                'referensi',
            ]);

        foreach ($payments as $payment) {
            $amount = round((float) $payment->jumlah_bayar, 2);
            $invoiceNumber = $payment->invoiceSupplier?->nomor_invoice_internal ?? '-';
            $method = $payment->metode_pembayaran ?: 'Pembayaran';

            $rows->push([
                'id' => (int) $payment->id,
                'tanggal' => $payment->tanggal_pembayaran->toDateString(),
                'nomor_referensi' => $payment->nomor_pembayaran,
                'jenis_transaksi' => 'PEMBAYARAN SUPPLIER',
                'keterangan' => $method . ' untuk invoice ' . $invoiceNumber
                    . ($payment->referensi ? ' | Ref: ' . $payment->referensi : ''),
                'debit' => $amount,
                'kredit' => 0.0,
                'urutan' => 30,
            ]);
        }

        $returRows = DB::table('retur_pembelian_allocations as allocation')
            ->join('retur_pembelians as retur', 'retur.id', '=', 'allocation.retur_pembelian_id')
            ->join('invoice_suppliers as invoice', 'invoice.id', '=', 'allocation.invoice_supplier_id')
            ->where('retur.supplier_id', $supplierId)
            ->where('retur.status', 'SELESAI')
            ->whereDate('retur.tanggal_retur', '<=', $tanggalSelesai)
            ->select([
                'retur.id as retur_id',
                'retur.tanggal_retur',
                'retur.nomor_retur',
                'invoice.id as invoice_id',
                'invoice.nomor_invoice_internal',
            ])
            ->selectRaw('SUM(allocation.potong_utang) as potong_utang')
            ->groupBy(
                'retur.id',
                'retur.tanggal_retur',
                'retur.nomor_retur',
                'invoice.id',
                'invoice.nomor_invoice_internal'
            )
            ->orderBy('retur.tanggal_retur')
            ->orderBy('retur.id')
            ->get();

        foreach ($returRows as $retur) {
            $amount = round((float) $retur->potong_utang, 2);

            if ($amount <= 0) {
                continue;
            }

            $rows->push([
                'id' => (int) $retur->retur_id,
                'tanggal' => (string) $retur->tanggal_retur,
                'nomor_referensi' => $retur->nomor_retur,
                'jenis_transaksi' => 'RETUR PEMBELIAN',
                'keterangan' => 'Pengurang utang untuk invoice ' . $retur->nomor_invoice_internal,
                'debit' => $amount,
                'kredit' => 0.0,
                'urutan' => 40,
            ]);
        }

        return $rows;
    }
}
