<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Gudang;
use App\Models\InvoicePenjualan;
use App\Models\RekeningKasBank;
use App\Models\ReturPenjualan;
use App\Models\ReturPenjualanItem;
use App\Services\InventoryService;
use App\Services\KasBankService;
use App\Services\SystemAccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReturPenjualanController extends Controller
{
    protected SystemAccountingService $accountingService;

    public function __construct(SystemAccountingService $accountingService)
    {
        $this->accountingService = $accountingService;
    }

    public function index()
    {
        $returs = ReturPenjualan::with(['invoicePenjualan', 'customer', 'gudang', 'creator', 'rekeningKasBank'])
            ->latest('tanggal_retur')
            ->latest('id')
            ->paginate(15);

        return view('retur-penjualan.index', compact('returs'));
    }

    public function create(Request $request)
    {
        $selectedInvoiceId = $request->input('invoice_id');
        $invoices = InvoicePenjualan::with(['customer', 'gudang', 'items.barang'])
            ->where('status_pembayaran', '!=', 'DIBATALKAN')
            ->orderByDesc('tanggal_invoice')
            ->get();

        $selectedInvoice = null;
        if ($selectedInvoiceId) {
            $selectedInvoice = InvoicePenjualan::with(['customer', 'gudang', 'items.barang'])->find($selectedInvoiceId);
        }

        $rekeningKasBanks = RekeningKasBank::where('is_active', true)->orderBy('nama_rekening')->get();
        $nomorRetur = $this->accountingService->generateDocumentNumber('RETUR_PENJUALAN');

        $defaultPotonganPersen = 20.00;

        return view('retur-penjualan.create', compact('invoices', 'selectedInvoice', 'rekeningKasBanks', 'nomorRetur', 'defaultPotonganPersen'));
    }

    public function store(Request $request, InventoryService $inventoryService, KasBankService $kasBankService)
    {
        $validated = $request->validate([
            'nomor_retur' => 'required|string|unique:retur_penjualans,nomor_retur',
            'invoice_penjualan_id' => 'required|exists:invoice_penjualans,id',
            'tanggal_retur' => 'required|date',
            'alasan_retur' => 'required|string|min:5',
            'potongan_persen' => 'nullable|numeric|min:0|max:100',
            'rekening_kas_bank_id' => 'nullable|exists:rekening_kas_banks,id',
            'items' => 'required|array|min:1',
            'items.*.barang_id' => 'required|exists:barangs,id',
            'items.*.qty' => 'required|numeric|min:0.01',
        ]);

        if ($this->accountingService->isPeriodLocked($validated['tanggal_retur'])) {
            return back()->withInput()->with('error', 'Retur penjualan gagal disimpan karena periode tanggal tersebut sudah DITUTUP (Cut-off).');
        }

        $invoice = InvoicePenjualan::with('items')->findOrFail($validated['invoice_penjualan_id']);

        if ($invoice->status_pembayaran === 'DIBATALKAN') {
            return back()->withInput()->with('error', 'Invoice yang sudah DIBATALKAN tidak dapat diretur.');
        }

        DB::beginTransaction();
        try {
            $bruto = 0;
            $itemsToProcess = [];

            foreach ($validated['items'] as $item) {
                if ((float) $item['qty'] <= 0) continue;

                // 1. Validasi barang ada pada invoice
                $invItem = $invoice->items->where('barang_id', $item['barang_id'])->first();
                if (!$invItem) {
                    throw new \RuntimeException("Barang ID {$item['barang_id']} tidak ditemukan pada Invoice #{$invoice->nomor_invoice}.");
                }

                // 2. Validasi sisa qty yang bisa diretur (mencegah over-return)
                $alreadyReturned = ReturPenjualanItem::whereHas('returPenjualan', function ($q) use ($invoice) {
                    $q->where('invoice_penjualan_id', $invoice->id);
                })->where('barang_id', $item['barang_id'])->sum('qty');

                $sisaBisaRetur = (float) ($invItem->qty - $alreadyReturned);
                if ((float) $item['qty'] > $sisaBisaRetur) {
                    throw new \RuntimeException("Qty retur ({$item['qty']}) untuk barang {$invItem->barang?->nama_barang} melebihi sisa yang bisa diretur ({$sisaBisaRetur}).");
                }

                // Gunakan HARGA JUAL dan HPP SNAPSHOT dari invoice asli
                $hargaSatuan = (float) $invItem->harga_satuan;
                $hppSatuan = (float) ($invItem->hpp_satuan > 0 ? $invItem->hpp_satuan : $invItem->harga_satuan);
                $itemSubtotal = $item['qty'] * $hargaSatuan;
                $itemTotalHpp = $item['qty'] * $hppSatuan;

                $bruto += $itemSubtotal;

                $itemsToProcess[] = [
                    'barang_id' => $item['barang_id'],
                    'qty' => $item['qty'],
                    'harga_satuan' => $hargaSatuan,
                    'hpp_satuan' => $hppSatuan,
                    'subtotal' => $itemSubtotal,
                    'total_hpp' => $itemTotalHpp,
                ];
            }

            if (empty($itemsToProcess)) {
                throw new \RuntimeException('Minimal harus ada 1 barang yang diretur dengan Qty lebih dari 0.');
            }

            // Potongan retur dari harga jual (default 20%, bisa diubah)
            $potonganPersen = isset($validated['potongan_persen']) ? (float) $validated['potongan_persen'] : 20.00;
            $nominalPotongan = ($bruto * $potonganPersen) / 100;
            $subtotal = max(0, $bruto - $nominalPotongan);

            // Hitung PPN Retur secara proporsional jika invoice asal ber-PPN
            $pajakPersen = $invoice->pajak > 0 && $invoice->subtotal > 0
                ? ($invoice->pajak / $invoice->subtotal) * 100
                : 0;

            $pajakRetur = ($subtotal * $pajakPersen) / 100;
            $totalRetur = $subtotal + $pajakRetur;

            $isTunai = ($invoice->tipe_pembayaran === 'Tunai');
            $tipeRetur = $isTunai ? 'Tunai' : 'Kredit';

            // Alokasi Retur Kredit vs Tunai / Excess Refund
            $potongPiutang = 0;
            $pengembalianKas = 0;

            if ($isTunai) {
                $pengembalianKas = $totalRetur;
            } else {
                $potongPiutang = min((float) $invoice->sisa_piutang, $totalRetur);
                $pengembalianKas = max(0, $totalRetur - $potongPiutang);
            }

            $rekeningKasBankId = $validated['rekening_kas_bank_id']
                ?? $invoice->rekening_kas_bank_id
                ?? RekeningKasBank::where('is_active', true)->first()?->id;

            $retur = ReturPenjualan::create([
                'nomor_retur' => $validated['nomor_retur'],
                'invoice_penjualan_id' => $invoice->id,
                'customer_id' => $invoice->customer_id,
                'gudang_id' => $invoice->gudang_id,
                'tanggal_retur' => $validated['tanggal_retur'],
                'tipe_retur' => $tipeRetur,
                'subtotal' => $subtotal,
                'potongan_persen' => $potonganPersen,
                'nominal_potongan' => $nominalPotongan,
                'pajak' => $pajakRetur,
                'total' => $totalRetur,
                'total_retur' => $totalRetur,
                'potong_piutang' => $potongPiutang,
                'pengembalian_kas' => $pengembalianKas,
                'rekening_kas_bank_id' => ($pengembalianKas > 0) ? $rekeningKasBankId : null,
                'alasan' => $validated['alasan_retur'],
                'alasan_retur' => $validated['alasan_retur'],
                'created_by' => auth()->id(),
                'created_by_user_id' => auth()->id(),
            ]);

            foreach ($itemsToProcess as $item) {
                ReturPenjualanItem::create([
                    'retur_penjualan_id' => $retur->id,
                    'barang_id' => $item['barang_id'],
                    'qty' => $item['qty'],
                    'harga_satuan' => $item['harga_satuan'],
                    'hpp_satuan' => $item['hpp_satuan'],
                    'total_hpp' => $item['total_hpp'],
                    'subtotal' => $item['subtotal'],
                ]);

                // RESTOCK BARANG MENGGUNAKAN HPP (COST) BARANG!
                $inventoryService->masuk(
                    $invoice->gudang_id,
                    $item['barang_id'],
                    $item['qty'],
                    $item['hpp_satuan'],
                    'RETUR_PENJUALAN',
                    $retur->id,
                    $retur->nomor_retur,
                    'Pengembalian Barang Retur HPP #' . $retur->nomor_retur,
                    $validated['tanggal_retur']
                );
            }

            // Update sisa piutang / pembayaran pada Invoice Penjualan asal
            if (!$isTunai) {
                $sisaPiutangBaru = max(0, (float) $invoice->sisa_piutang - $potongPiutang);
                $sudahDibayarBaru = max(0, (float) $invoice->sudah_dibayar - $pengembalianKas);
                $statusPembayaranBaru = $sisaPiutangBaru <= 0.01 ? 'Lunas' : 'Sebagian';

                $invoice->update([
                    'sisa_piutang' => $sisaPiutangBaru,
                    'sudah_dibayar' => $sudahDibayarBaru,
                    'status_pembayaran' => $statusPembayaranBaru,
                ]);
            }

            // Jika ada pengembalian kas/bank (retur tunai atau kelebihan retur), catat mutasi Uang Keluar
            if ($pengembalianKas > 0 && $rekeningKasBankId) {
                $customer = Customer::find($invoice->customer_id);
                $kasBankService->uangKeluar(
                    $rekeningKasBankId,
                    $pengembalianKas,
                    'RETUR_PENJUALAN',
                    $retur->id,
                    $retur->nomor_retur,
                    'Pengembalian Dana Retur Penjualan #' . $retur->nomor_retur . ' - ' . ($customer->nama_customer ?? ''),
                    $validated['tanggal_retur']
                );
            }

            // Auto-Journaling Retur (with PPN, Tunai vs Kredit split, & Kartu Piutang update)
            $this->accountingService->autoJournalReturPenjualan($retur);

            DB::commit();

            return redirect()->route('retur-penjualan.index')
                ->with('success', 'Nota Retur Penjualan berhasil diterbitkan (Potongan Retur: ' . number_format($potonganPersen, 0) . '% = Rp ' . number_format($nominalPotongan, 0, ',', '.') . '), barang di-restock ke gudang, serta Kas/Piutang & Jurnal telah diperbarui.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Gagal menyimpan retur penjualan: ' . $e->getMessage());
        }
    }

    public function show(ReturPenjualan $returPenjualan)
    {
        $returPenjualan->load(['invoicePenjualan', 'customer', 'gudang', 'creator', 'rekeningKasBank', 'items.barang.satuan']);
        return view('retur-penjualan.show', compact('returPenjualan'));
    }

    public function pdf(Request $request, ReturPenjualan $returPenjualan)
    {
        $returPenjualan->load(['invoicePenjualan', 'customer', 'gudang', 'creator', 'rekeningKasBank', 'items.barang.satuan']);
        $safeNumber = str_replace(['/', '\\'], '-', $returPenjualan->nomor_retur);

        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('retur-penjualan.pdf', compact('returPenjualan'))->setPaper('a5', 'landscape');
            $filename = 'Nota-Retur-' . $safeNumber . '.pdf';

            if ($request->has('download') || $request->input('download') == '1') {
                return $pdf->download($filename);
            }

            return $pdf->stream($filename);
        }

        return view('retur-penjualan.pdf', compact('returPenjualan'));
    }
}
