<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\KartuPiutang;
use Illuminate\Http\Request;

class KartuPiutangController extends Controller
{
    public function index(Request $request)
    {
        $customerId = $request->input('customer_id');
        $tanggalMulai = $request->input('tanggal_mulai', date('Y-01-01'));
        $tanggalSelesai = $request->input('tanggal_selesai', date('Y-m-d'));

        $customers = Customer::where('is_active', true)->orderBy('nama_customer')->get();

        $rows = collect();
        $selectedCustomer = null;
        $saldoAwal = 0;

        if ($customerId) {
            $selectedCustomer = Customer::find($customerId);

            // Saldo sebelum tanggalMulai
            $priorTransactions = KartuPiutang::where('customer_id', $customerId)
                ->whereDate('tanggal', '<', $tanggalMulai)
                ->get();

            $saldoAwal = $priorTransactions->sum('debit') - $priorTransactions->sum('kredit');

            $rows = KartuPiutang::where('customer_id', $customerId)
                ->whereDate('tanggal', '>=', $tanggalMulai)
                ->whereDate('tanggal', '<=', $tanggalSelesai)
                ->orderBy('tanggal')
                ->orderBy('id')
                ->get();
        }

        return view('piutang.kartu-piutang', compact(
            'customers',
            'customerId',
            'selectedCustomer',
            'tanggalMulai',
            'tanggalSelesai',
            'saldoAwal',
            'rows'
        ));
    }
}
