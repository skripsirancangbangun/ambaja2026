<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\InvoicePenjualan;
use App\Models\TindakLanjutPiutang;
use App\Models\RekeningKasBank;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;

class PiutangController extends Controller
{
    public function index(Request $request)
    {
        $query = InvoicePenjualan::with(['customer', 'penjualan']);

        // Filter status piutang (Default: Tampilkan yang belum lunas / sebagian)
        $statusPembayaran = $request->input('status_pembayaran', 'BELUM_LUNAS_ALL');

        if ($statusPembayaran === 'BELUM_LUNAS_ALL') {
            $query->whereIn('status_pembayaran', ['Belum Lunas', 'Sebagian']);
        } elseif ($statusPembayaran === 'Lunas') {
            $query->where('status_pembayaran', 'Lunas');
        } elseif ($statusPembayaran === 'Belum Lunas') {
            $query->where('status_pembayaran', 'Belum Lunas');
        } elseif ($statusPembayaran === 'Sebagian') {
            $query->where('status_pembayaran', 'Sebagian');
        }
        // jika 'SEMUA', maka tidak me-filter status_pembayaran

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
            'totalInvoiceBelumLunas',
            'statusPembayaran'
        ));
    }

    public function show(InvoicePenjualan $invoice)
    {
        $invoice->load([
            'customer',
            'gudang',
            'items.barang',
            'penerimaanDetails.penerimaanPiutang.rekeningKasBank',
            'penerimaanDetails.penerimaanPiutang.creator',
            'tindakLanjuts.creator',
            'returPenjualans'
        ]);

        $rekeningKasBanks = RekeningKasBank::where('is_active', true)->orderBy('nama_rekening')->get();

        return view('piutang.show', compact('invoice', 'rekeningKasBanks'));
    }

    public function storeTindakLanjut(Request $request, InvoicePenjualan $invoice)
    {
        $validated = $request->validate([
            'tanggal_tindak_lanjut' => 'required|date',
            'jenis_tindak_lanjut' => 'required|in:CATATAN_PENAGIHAN,TELEPON_CUSTOMER,JANJI_BAYAR,SURAT_TAGIHAN,SURAT_PERINGATAN_1,SURAT_PERINGATAN_2,SURAT_PERINGATAN_3',
            'tanggal_janji_bayar' => 'nullable|date',
            'catatan' => 'required|string',
        ]);

        TindakLanjutPiutang::create([
            'invoice_penjualan_id' => $invoice->id,
            'customer_id' => $invoice->customer_id,
            'tanggal_tindak_lanjut' => $validated['tanggal_tindak_lanjut'],
            'jenis_tindak_lanjut' => $validated['jenis_tindak_lanjut'],
            'tanggal_janji_bayar' => $validated['tanggal_janji_bayar'],
            'catatan' => $validated['catatan'],
            'created_by_user_id' => auth()->id() ?? 1,
        ]);

        return redirect()->route('piutang.show', $invoice)
            ->with('success', 'Riwayat tindak lanjut penagihan berhasil dicatat.');
    }

    public function cetakSuratTagihan(InvoicePenjualan $invoice)
    {
        $invoice->load(['customer', 'items.barang']);
        $pdf = Pdf::loadView('piutang.surat-tagihan-pdf', compact('invoice'))->setPaper('a4');
        return $pdf->stream('Surat-Tagihan-' . $invoice->nomor_invoice . '.pdf');
    }

    public function cetakSuratPeringatan(InvoicePenjualan $invoice, $level)
    {
        $invoice->load(['customer', 'items.barang']);
        $levelText = 'SP-' . $level;
        $pdf = Pdf::loadView('piutang.surat-peringatan-pdf', compact('invoice', 'level', 'levelText'))->setPaper('a4');
        return $pdf->stream('Surat-Peringatan-' . $levelText . '-' . $invoice->nomor_invoice . '.pdf');
    }
}
