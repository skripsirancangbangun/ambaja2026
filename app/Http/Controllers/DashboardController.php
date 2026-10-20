<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\InvoicePenjualan;
use App\Models\InvoiceSupplier;
use App\Models\ReturPembelianAllocation;
use App\Models\RekeningKasBank;
use App\Models\MutasiKasBank;
use App\Models\StokGudang;
use App\Models\TransaksiBelumTeridentifikasi;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $today = today();
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        $activeSalesInvoices = InvoicePenjualan::query()
            ->where('status_invoice', 'AKTIF');

        $salesThisMonth = (clone $activeSalesInvoices)
            ->whereBetween('tanggal_invoice', [$monthStart, $monthEnd])
            ->sum('total');

        // Pembelian bruto: seluruh invoice supplier pada periode berjalan.
        $purchasesGrossThisMonth = (float) InvoiceSupplier::query()
            ->whereBetween('tanggal_invoice', [$monthStart, $monthEnd])
            ->sum('total');

        // Retur hanya mengurangi pembelian ketika sudah SELESAI diproses.
        // Gunakan tanggal retur, bukan tanggal invoice supplier, untuk cut-off periode.
        // Hanya alokasi yang terhubung ke invoice supplier yang mengurangi nilai pembelian.
        $purchaseReturnsThisMonth = (float) ReturPembelianAllocation::query()
            ->whereHas('returPembelian', function ($query) use ($monthStart, $monthEnd): void {
                $query
                    ->where('status', 'SELESAI')
                    ->whereBetween('tanggal_retur', [$monthStart, $monthEnd]);
            })
            ->sum('total_koreksi');

        // Jangan membatasi hasil minimum nol: retur periode ini bisa terkait
        // dengan invoice dari periode sebelumnya.
        $purchasesThisMonth = round(
            $purchasesGrossThisMonth - $purchaseReturnsThisMonth,
            2
        );

        $receivables = InvoicePenjualan::query()
            ->where('status_invoice', 'AKTIF')
            ->where('sisa_piutang', '>', 0);

        $receivablesTotal = (clone $receivables)->sum('sisa_piutang');
        $receivablesCount = (clone $receivables)->count();
        $overdueReceivables = (clone $receivables)
            ->whereNotNull('tanggal_jatuh_tempo')
            ->whereDate('tanggal_jatuh_tempo', '<', $today)
            ->sum('sisa_piutang');

        $payables = InvoiceSupplier::query()->where('sisa_utang', '>', 0);
        $payablesTotal = (clone $payables)->sum('sisa_utang');
        $payablesCount = (clone $payables)->count();
        $overduePayables = (clone $payables)
            ->whereNotNull('tanggal_jatuh_tempo')
            ->whereDate('tanggal_jatuh_tempo', '<', $today)
            ->sum('sisa_utang');

        // Saldo kas/bank dihitung dari saldo awal rekening + mutasi debit - kredit.
        // Rumus ini mengikuti logika saldo yang dipakai modul Kas & Bank.
        $cashBankAccounts = RekeningKasBank::query()
            ->where('is_active', true)
            ->orderBy('jenis')
            ->orderBy('nama_rekening')
            ->get();

        $cashBankMutations = MutasiKasBank::query()
            ->selectRaw('rekening_kas_bank_id, COALESCE(SUM(debit), 0) AS total_debit, COALESCE(SUM(kredit), 0) AS total_kredit')
            ->groupBy('rekening_kas_bank_id')
            ->get()
            ->keyBy('rekening_kas_bank_id');

        $cashBankAccounts->each(function (RekeningKasBank $account) use ($cashBankMutations): void {
            $mutation = $cashBankMutations->get($account->id);
            $account->setAttribute(
                'saldo_dashboard',
                (float) $account->saldo_awal
                    + (float) ($mutation->total_debit ?? 0)
                    - (float) ($mutation->total_kredit ?? 0)
            );
        });

        $cashBankTotal = $cashBankAccounts->sum('saldo_dashboard');
        $cashBankAccountCount = $cashBankAccounts->count();

        $inventoryValue = StokGudang::query()->sum('nilai_persediaan');
        $activeItemsCount = Barang::query()->where('is_active', true)->count();
        $lowStockCount = Barang::query()
            ->where('is_active', true)
            ->where('minimum_stok', '>', 0)
            ->whereRaw(
                '(SELECT COALESCE(SUM(stok_gudangs.qty), 0) FROM stok_gudangs WHERE stok_gudangs.barang_id = barangs.id) <= barangs.minimum_stok'
            )
            ->count();

        $unidentifiedBankTransactions = TransaksiBelumTeridentifikasi::query()
            ->where('status', 'BELUM_TERIDENTIFIKASI');
        $unidentifiedBankCount = (clone $unidentifiedBankTransactions)->count();
        $unidentifiedBankTotal = (clone $unidentifiedBankTransactions)->sum('jumlah');

        $showSales = auth()->user()->canAccessModule('sales');
        $latestInvoices = $showSales
            ? (clone $activeSalesInvoices)
                ->with('customer:id,nama_customer')
                ->latest('tanggal_invoice')
                ->latest('id')
                ->limit(5)
                ->get(['id', 'nomor_invoice', 'customer_id', 'tanggal_invoice', 'total', 'status_pembayaran'])
            : collect();

        return view('dashboard', compact(
            'salesThisMonth',
            'purchasesThisMonth',
            'receivablesTotal',
            'receivablesCount',
            'overdueReceivables',
            'payablesTotal',
            'payablesCount',
            'overduePayables',
            'cashBankAccounts',
            'cashBankTotal',
            'cashBankAccountCount',
            'inventoryValue',
            'activeItemsCount',
            'lowStockCount',
            'unidentifiedBankCount',
            'unidentifiedBankTotal',
            'latestInvoices',
            'showSales',
        ));
    }
}
