<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Customer;
use App\Models\Gudang;
use App\Models\InvoicePenjualan;
use App\Models\InvoicePenjualanItem;
use App\Models\Penjualan;
use App\Models\PenjualanItem;
use App\Models\RekeningKasBank;
use App\Services\InventoryService;
use App\Services\KasBankService;
use App\Services\SystemAccountingService;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoicePenjualanController extends Controller
{
    public function index(Request $request)
    {
        $query = InvoicePenjualan::with(['customer', 'gudang', 'creator', 'items.barang']);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('nomor_invoice', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('nama_customer', 'like', "%{$search}%");
                  });
            });
        }

        if ($request->filled('status_pembayaran')) {
            $query->where('status_pembayaran', $request->status_pembayaran);
        }

        if ($request->filled('tipe_pembayaran')) {
            $query->where('tipe_pembayaran', $request->tipe_pembayaran);
        }

        if ($request->filled('tanggal_mulai')) {
            $query->whereDate('tanggal_invoice', '>=', $request->tanggal_mulai);
        }

        if ($request->filled('tanggal_selesai')) {
            $query->whereDate('tanggal_invoice', '<=', $request->tanggal_selesai);
        }

        $invoices = $query->latest('tanggal_invoice')->latest('id')->paginate(15)->withQueryString();

        return view('invoice-penjualan.index', compact('invoices'));
    }

    public function create(Request $request)
    {
        $customers = Customer::where('is_active', true)->orderBy('nama_customer')->get();
        $gudangs = Gudang::where('is_active', true)->orderBy('nama_gudang')->get();
        $rekeningKasBanks = RekeningKasBank::where('is_active', true)->orderBy('nama_rekening')->get();
        $barangs = Barang::with(['stokGudangs'])->where('is_active', true)->orderBy('nama_barang')->get();
        $nomorInvoice = app(SystemAccountingService::class)->generateDocumentNumber('INV');

        // Hanya Sales Order yang berstatus Dikonfirmasi / Diproses dan masih memiliki sisa Qty yang belum di-invoice
        $penjualans = Penjualan::with(['customer', 'items.barang'])
            ->whereIn('status', ['Dikonfirmasi', 'Diproses'])
            ->whereHas('items', function ($q) {
                $q->whereRaw('qty > qty_terinvoice');
            })
            ->latest('tanggal_penjualan')
            ->get();

        $selectedPenjualan = null;
        if ($request->filled('penjualan_id')) {
            $selectedPenjualan = Penjualan::with(['customer', 'items.barang'])->find($request->penjualan_id);
        }

        $initialItems = [
            ['barang_id' => '', 'qty' => 1, 'harga_satuan' => 0, 'diskon' => 0]
        ];

        if ($selectedPenjualan && $selectedPenjualan->items->isNotEmpty()) {
            $initialItems = $selectedPenjualan->items
                ->filter(fn($i) => ($i->qty - $i->qty_terinvoice) > 0)
                ->map(function ($i) {
                    $sisa = (float) ($i->qty - $i->qty_terinvoice);
                    return [
                        'barang_id' => $i->barang_id,
                        'qty' => $sisa,
                        'harga_satuan' => (float) $i->harga_satuan,
                        'diskon' => (float) $i->diskon,
                    ];
                })->values()->toArray();
        }

        return view('invoice-penjualan.create', compact(
            'customers',
            'gudangs',
            'rekeningKasBanks',
            'barangs',
            'penjualans',
            'selectedPenjualan',
            'nomorInvoice',
            'initialItems'
        ));
    }

    private function generateNomorInvoice(): string
    {
        $prefix = 'INV-' . now()->format('Ym') . '-';

        $lastInvoice = InvoicePenjualan::where('nomor_invoice', 'like', $prefix . '%')
            ->orderByDesc('nomor_invoice')
            ->first();

        if (!$lastInvoice) {
            return $prefix . '0001';
        }

        $lastNumber = (int) substr($lastInvoice->nomor_invoice, -4);
        return $prefix . str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);
    }

    public function store(
        Request $request,
        InventoryService $inventoryService,
        KasBankService $kasBankService,
        SystemAccountingService $systemAccountingService
    ) {
        $validated = $request->validate([
            'nomor_invoice' => 'required|string|unique:invoice_penjualans,nomor_invoice',
            'penjualan_id' => 'nullable|exists:penjualans,id',
            'customer_id' => 'required|exists:customers,id',
            'gudang_id' => 'required|exists:gudangs,id',
            'tanggal_invoice' => 'required|date',
            'tanggal_jatuh_tempo' => 'nullable|date',
            'tipe_pembayaran' => 'required|in:Tunai,Kredit',
            'rekening_kas_bank_id' => 'nullable|required_if:tipe_pembayaran,Tunai|exists:rekening_kas_banks,id',
            'catatan' => 'nullable|string',
            'pajak_persen' => 'nullable|numeric|min:0|max:100',
            'items' => 'required|array|min:1',
            'items.*.barang_id' => 'required|exists:barangs,id',
            'items.*.qty' => 'required|numeric|min:0.01',
            'items.*.harga_satuan' => 'required|numeric|min:0',
            'items.*.diskon' => 'nullable|numeric|min:0',
        ]);

        if ($systemAccountingService->isPeriodLocked($validated['tanggal_invoice'])) {
            return back()->withInput()->with('error', 'Transaksi pada tanggal ini tidak dapat dibuat karena periode bulanan sudah DITUTUP (Cut-Off).');
        }

        $isTunai = $validated['tipe_pembayaran'] === 'Tunai';

        // Validasi Tanggal Jatuh Tempo untuk Kredit
        if (!$isTunai) {
            if (empty($validated['tanggal_jatuh_tempo'])) {
                $validated['tanggal_jatuh_tempo'] = Carbon::parse($validated['tanggal_invoice'])->addDays(30)->format('Y-m-d');
            } elseif ($validated['tanggal_jatuh_tempo'] < $validated['tanggal_invoice']) {
                return back()->withInput()->with('error', 'Tanggal jatuh tempo tidak boleh sebelum tanggal invoice.');
            }
        }

        // Strict SO Validation if sales order is provided
        $so = null;
        if (!empty($validated['penjualan_id'])) {
            $so = Penjualan::with('items')->find($validated['penjualan_id']);
            if (!$so || in_array($so->status, ['Selesai', 'Dibatalkan'])) {
                return back()->withInput()->with('error', 'Sales Order yang dipilih sudah Selesai atau Dibatalkan.');
            }

            if ($so->customer_id != $validated['customer_id']) {
                return back()->withInput()->with('error', 'Customer yang dipilih tidak sesuai dengan Sales Order.');
            }

            if ($so->gudang_id && $so->gudang_id != $validated['gudang_id']) {
                return back()->withInput()->with('error', 'Gudang yang dipilih tidak sesuai dengan Sales Order.');
            }
        }

        DB::beginTransaction();
        try {
            $subtotal = 0;
            $totalDiskonItem = 0;

            foreach ($validated['items'] as $item) {
                // If SO present, validate items and remaining qty
                if ($so) {
                    $soItem = $so->items->where('barang_id', $item['barang_id'])->first();
                    if (!$soItem) {
                        throw new \RuntimeException('Barang pada invoice tidak ditemukan dalam Sales Order #' . $so->nomor_penjualan);
                    }

                    $sisaQtySO = (float) ($soItem->qty - $soItem->qty_terinvoice);
                    if ($item['qty'] > $sisaQtySO) {
                        throw new \RuntimeException("Jumlah barang pada invoice ({$item['qty']}) melebihi sisa yang belum ter-invoice pada Sales Order ({$sisaQtySO}).");
                    }
                }

                $subtotal += ($item['qty'] * $item['harga_satuan']);
                $totalDiskonItem += ($item['diskon'] ?? 0);
            }

            $pajakPersen = $validated['pajak_persen'] ?? 0;
            $dpp = $subtotal - $totalDiskonItem;
            $pajak = ($dpp * $pajakPersen) / 100;
            $total = $dpp + $pajak;

            // Integrasi Limit Piutang Customer jika Kredit
            if (!$isTunai) {
                $customer = Customer::findOrFail($validated['customer_id']);
                if ($customer->limit_piutang > 0) {
                    $currentPiutang = InvoicePenjualan::where('customer_id', $customer->id)
                        ->where('status_pembayaran', '!=', 'DIBATALKAN')
                        ->sum('sisa_piutang');

                    if (($currentPiutang + $total) > $customer->limit_piutang) {
                        throw new \RuntimeException(
                            "Limit piutang customer '{$customer->nama_customer}' melampaui batas! " .
                            "Limit: Rp " . number_format($customer->limit_piutang, 0, ',', '.') .
                            ", Piutang Berjalan: Rp " . number_format($currentPiutang, 0, ',', '.') .
                            ", Tagihan Baru: Rp " . number_format($total, 0, ',', '.')
                        );
                    }
                }
            }

            $sudahDibayar = $isTunai ? $total : 0;
            $sisaPiutang = $isTunai ? 0 : $total;
            $statusPembayaran = $isTunai ? 'Lunas' : 'Belum Lunas';

            $nomorInvoice = $validated['nomor_invoice'];
            if (InvoicePenjualan::where('nomor_invoice', $nomorInvoice)->exists()) {
                $nomorInvoice = $systemAccountingService->generateDocumentNumber('INV', $validated['tanggal_invoice']);
            }

            $invoice = InvoicePenjualan::create([
                'nomor_invoice' => $nomorInvoice,
                'penjualan_id' => $validated['penjualan_id'] ?? null,
                'customer_id' => $validated['customer_id'],
                'gudang_id' => $validated['gudang_id'],
                'tanggal_invoice' => $validated['tanggal_invoice'],
                'tanggal_jatuh_tempo' => $isTunai ? null : $validated['tanggal_jatuh_tempo'],
                'tipe_pembayaran' => $validated['tipe_pembayaran'],
                'rekening_kas_bank_id' => $isTunai ? $validated['rekening_kas_bank_id'] : null,
                'subtotal' => $subtotal,
                'diskon' => $totalDiskonItem,
                'pajak_persen' => $pajakPersen,
                'pajak' => $pajak,
                'total' => $total,
                'sudah_dibayar' => $sudahDibayar,
                'sisa_piutang' => $sisaPiutang,
                'status_pembayaran' => $statusPembayaran,
                'catatan' => $validated['catatan'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($validated['items'] as $item) {
                $itemSubtotal = ($item['qty'] * $item['harga_satuan']) - ($item['diskon'] ?? 0);

                // Potong stok persediaan dan dapatkan HPP Aktual (moving average)
                $stokGudang = $inventoryService->stokKeluar(
                    $item['barang_id'],
                    $validated['gudang_id'],
                    $item['qty'],
                    'INVOICE_PENJUALAN',
                    $invoice->id,
                    'Penjualan Invoice ' . $invoice->nomor_invoice,
                    'KELUAR',
                    $validated['tanggal_invoice']
                );

                $hppSatuan = (float) ($stokGudang->harga_rata_rata ?? 0);
                $totalHpp = $item['qty'] * $hppSatuan;

                // Simpan snapshot HPP ke invoice_penjualan_items
                InvoicePenjualanItem::create([
                    'invoice_penjualan_id' => $invoice->id,
                    'barang_id' => $item['barang_id'],
                    'qty' => $item['qty'],
                    'harga_satuan' => $item['harga_satuan'],
                    'hpp_satuan' => $hppSatuan,
                    'total_hpp' => $totalHpp,
                    'diskon' => $item['diskon'] ?? 0,
                    'subtotal' => $itemSubtotal,
                    'catatan' => $item['catatan'] ?? null,
                ]);

                // Update qty_terinvoice pada SO jika dari SO
                if ($so) {
                    $soItem = $so->items->where('barang_id', $item['barang_id'])->first();
                    if ($soItem) {
                        $soItem->increment('qty_terinvoice', $item['qty']);
                    }
                }
            }

            // Update status SO jika berasal dari SO
            if ($so) {
                $so->load('items');
                $fullyInvoiced = $so->items->every(fn($i) => $i->qty_terinvoice >= $i->qty);
                $so->update([
                    'status' => $fullyInvoiced ? 'Selesai' : 'Diproses',
                ]);
            }

            // Jika penjualan tunai, catat langsung mutasi Kas/Bank Masuk
            if ($isTunai && $total > 0) {
                $customer = Customer::find($validated['customer_id']);
                $kasBankService->uangMasuk(
                    $validated['rekening_kas_bank_id'],
                    $total,
                    'INVOICE_PENJUALAN',
                    $invoice->id,
                    $invoice->nomor_invoice,
                    'Penjualan Tunai ' . $invoice->nomor_invoice . ' - ' . ($customer->nama_customer ?? ''),
                    $validated['tanggal_invoice']
                );
            }

            // Auto-Journaling Akuntansi Otomatis
            $systemAccountingService->autoJournalInvoicePenjualan($invoice);

            DB::commit();

            return redirect()
                ->route('invoice-penjualan.show', $invoice)
                ->with('success', 'Invoice Penjualan berhasil diterbitkan dan stok telah diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal menerbitkan invoice penjualan: ' . $e->getMessage());
        }
    }

    public function show(InvoicePenjualan $invoicePenjualan)
    {
        $invoicePenjualan->load([
            'customer',
            'gudang',
            'rekeningKasBank',
            'creator',
            'items.barang.satuan',
            'penerimaanDetails.penerimaanPiutang.rekeningKasBank',
            'returPenjualans'
        ]);

        return view('invoice-penjualan.show', compact('invoicePenjualan'));
    }

    public function destroy(InvoicePenjualan $invoicePenjualan)
    {
        if ($invoicePenjualan->penerimaanDetails()->exists()) {
            return back()->with('error', 'Invoice tidak dapat dihapus karena sudah memiliki riwayat pembayaran.');
        }

        if ($invoicePenjualan->returPenjualans()->exists()) {
            return back()->with('error', 'Invoice tidak dapat dihapus karena sudah memiliki retur.');
        }

        return back()->with('error', 'Invoice penjualan yang telah memotong stok dan mutasi tidak disarankan untuk dihapus langsung demi konsistensi akuntansi. Silakan gunakan fitur Retur Penjualan atau Pembatalan Resmi.');
    }

    /**
     * Pembatalan Resmi Invoice Penjualan (Hanya untuk Kepala Toko / Admin)
     */
    public function batal(
        Request $request,
        InvoicePenjualan $invoice,
        SystemAccountingService $systemAccountingService,
        InventoryService $inventoryService,
        KasBankService $kasBankService
    ) {
        $request->validate([
            'alasan_pembatalan' => 'required|string|min:5',
        ]);

        if ($systemAccountingService->isPeriodLocked($invoice->tanggal_invoice)) {
            return back()->with('error', 'Invoice tidak dapat dibatalkan karena periode bulanan sudah DITUTUP (Cut-off).');
        }

        if ($invoice->status_pembayaran === 'DIBATALKAN') {
            return back()->with('error', 'Invoice ini sudah dibatalkan sebelumnya.');
        }

        // Tolak pembatalan jika sudah ada pembayaran atau retur
        if ($invoice->sudah_dibayar > 0 && $invoice->tipe_pembayaran === 'Kredit') {
            return back()->with('error', 'Invoice tidak dapat dibatalkan langsung karena sudah memiliki riwayat pembayaran piutang.');
        }

        if ($invoice->returPenjualans()->where('total', '>', 0)->exists()) {
            return back()->with('error', 'Invoice tidak dapat dibatalkan karena sudah memiliki transaksi retur penjualan.');
        }

        DB::beginTransaction();
        try {
            // 1. Restock Barang ke Persediaan menggunakan HPP snapshot
            foreach ($invoice->items as $item) {
                $inventoryService->masuk(
                    $invoice->gudang_id,
                    $item->barang_id,
                    $item->qty,
                    $item->hpp_satuan > 0 ? $item->hpp_satuan : $item->harga_satuan,
                    'PEMBATALAN_INVOICE',
                    $invoice->id,
                    $invoice->nomor_invoice,
                    'Restock HPP dari Pembatalan Invoice #' . $invoice->nomor_invoice,
                    now()
                );

                // Kembalikan qty_terinvoice pada Sales Order jika ada
                if ($invoice->penjualan_id) {
                    $soItem = PenjualanItem::where('penjualan_id', $invoice->penjualan_id)
                        ->where('barang_id', $item->barang_id)
                        ->first();
                    if ($soItem) {
                        $soItem->decrement('qty_terinvoice', min($soItem->qty_terinvoice, $item->qty));
                    }
                }
            }

            // Kembalikan status SO ke Dikonfirmasi / Diproses jika dari SO
            if ($invoice->penjualan_id) {
                $so = Penjualan::find($invoice->penjualan_id);
                if ($so) {
                    $so->load('items');
                    $anyInvoiced = $so->items->sum('qty_terinvoice') > 0;
                    $so->update([
                        'status' => $anyInvoiced ? 'Diproses' : 'Dikonfirmasi',
                    ]);
                }
            }

            // 2. Jika Tunai, balikkan Mutasi Kas/Bank (Uang Keluar Pengembalian)
            if ($invoice->tipe_pembayaran === 'Tunai' && $invoice->total > 0 && $invoice->rekening_kas_bank_id) {
                $customer = Customer::find($invoice->customer_id);
                $kasBankService->uangKeluar(
                    $invoice->rekening_kas_bank_id,
                    $invoice->total,
                    'PEMBATALAN_INVOICE',
                    $invoice->id,
                    $invoice->nomor_invoice,
                    'Pengembalian Tunai Pembatalan Invoice ' . $invoice->nomor_invoice . ' - ' . ($customer->nama_customer ?? ''),
                    now()
                );
            }

            // 3. Terbitkan Jurnal Pembalik & Update status invoice
            $systemAccountingService->cancelInvoicePenjualan($invoice, $request->alasan_pembatalan);

            DB::commit();

            return redirect()->route('invoice-penjualan.show', $invoice)
                ->with('success', 'Invoice #' . $invoice->nomor_invoice . ' berhasil DIBATALKAN secara resmi. Stok & Kas/Bank telah disesuaikan.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Gagal membatalkan invoice: ' . $e->getMessage());
        }
    }

    public function pdf(InvoicePenjualan $invoicePenjualan)
    {
        $invoicePenjualan->load(['customer', 'gudang', 'creator', 'items.barang.satuan', 'rekeningKasBank']);

        if (class_exists(Pdf::class)) {
            $safeNumber = str_replace(['/', '\\'], '-', $invoicePenjualan->nomor_invoice);
            $pdf = Pdf::loadView('invoice-penjualan.pdf', compact('invoicePenjualan'));
            return $pdf->stream('Invoice-' . $safeNumber . '.pdf');
        }

        return view('invoice-penjualan.pdf', compact('invoicePenjualan'));
    }

    public function kirimEmail(Request $request, InvoicePenjualan $invoicePenjualan)
    {
        $request->validate([
            'email_tujuan' => 'required|email',
        ]);

        $emailTujuan = trim($request->email_tujuan);

        try {
            if (config('mail.default') === 'smtp' && empty(config('mail.mailers.smtp.password'))) {
                return back()->with('error', 'Gagal mengirim email asli: Sandi Aplikasi Google (App Password) belum dimasukkan pada file .env (MAIL_PASSWORD). Silakan masukkan 16-digit App Password Google Anda.');
            }

            $invoicePenjualan->load(['customer', 'gudang', 'creator', 'items.barang.satuan', 'rekeningKasBank']);

            \Illuminate\Support\Facades\Mail::to($emailTujuan)->send(new \App\Mail\InvoicePenjualanMail($invoicePenjualan));

            $invoicePenjualan->update([
                'is_email_sent' => true,
                'email_sent_at' => now(),
                'email_sent_to' => $emailTujuan,
            ]);

            return back()->with('success', 'Bukti Invoice Penjualan #' . $invoicePenjualan->nomor_invoice . ' berhasil dikirimkan via email ke: ' . $emailTujuan);
        } catch (\Exception $e) {
            $msg = $e->getMessage();
            if (str_contains($msg, 'Authentic') || str_contains($msg, 'Password') || str_contains($msg, 'Username')) {
                $msg = 'Gagal Autentikasi SMTP Gmail. Pastikan Sandi Aplikasi 16-digit Google (App Password) sudah diisi di .env pada MAIL_PASSWORD.';
            }
            return back()->with('error', 'Gagal mengirimkan email invoice: ' . $msg);
        }
    }
}
