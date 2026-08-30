<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\InvoicePenjualan;
use Illuminate\Http\Request;

class PiutangController extends Controller
{
    public function index(Request $request)
    {
        $query = InvoicePenjualan::with(['customer', 'penjualan'])
            ->where('sisa_piutang', '>', 0);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nomor_invoice', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('nama_customer', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('status_pembayaran')) {
            $query->where('status_pembayaran', $request->status_pembayaran);
        }

        if ($request->jatuh_tempo === 'lewat') {
            $query->whereDate('tanggal_jatuh_tempo', '<', now());
        } elseif ($request->jatuh_tempo === 'mendekati') {
            $query->whereDate('tanggal_jatuh_tempo', '>=', now())
                  ->whereDate('tanggal_jatuh_tempo', '<=', now()->addDays(7));
        }

        // Summary metrics
        $totalPiutang = InvoicePenjualan::where('sisa_piutang', '>', 0)->sum('sisa_piutang');
        $totalPiutangJatuhTempo = InvoicePenjualan::where('sisa_piutang', '>', 0)
            ->whereDate('tanggal_jatuh_tempo', '<', now())
            ->sum('sisa_piutang');
        $totalInvoiceBelumLunas = InvoicePenjualan::where('sisa_piutang', '>', 0)->count();

        $invoices = $query->orderBy('tanggal_jatuh_tempo')->paginate(15)->withQueryString();
        $customers = Customer::where('is_active', true)->orderBy('nama_customer')->get();

        return view('piutang.index', compact(
            'invoices',
            'customers',
            'totalPiutang',
            'totalPiutangJatuhTempo',
            'totalInvoiceBelumLunas'
        ));
    }
}
