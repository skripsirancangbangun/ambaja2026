<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\PermintaanPembelian;
use App\Models\PermintaanPembelianDetail;
use App\Models\StokGudang;
use App\Models\User;
use App\Notifications\PermintaanPembelianNotification;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PermintaanPembelianController extends Controller
{
    /** Show the request inbox for Pembelian and request history for Gudang. */
    public function index(): View
    {
        $user = auth()->user();
        $query = PermintaanPembelian::query();

        if ($user->role === 'Gudang') {
            $query->where('created_by', $user->id);
        }

        $pendingCount = (clone $query)
            ->where('status', PermintaanPembelian::STATUS_DIAJUKAN)
            ->count();

        $approvedCount = (clone $query)
            ->where('status', PermintaanPembelian::STATUS_DISETUJUI)
            ->count();

        $poCreatedCount = (clone $query)
            ->where('status', PermintaanPembelian::STATUS_PO_DIBUAT)
            ->count();

        $requests = $query
            ->with(['creator', 'reviewer', 'purchaseOrder'])
            ->withCount('details')
            ->latest('id')
            ->paginate(15);

        return view('permintaan-pembelian.index', compact(
            'requests',
            'pendingCount',
            'approvedCount',
            'poCreatedCount',
        ));
    }

    /** Show at most the 10 most critical active items by combined stock ratio. */
    public function create(): View
    {
        $activeRequestBarangIds = PermintaanPembelianDetail::query()
            ->where(function ($query): void {
                $query->whereHas('permintaanPembelian', function ($requestQuery): void {
                    $requestQuery->where('status', PermintaanPembelian::STATUS_DIAJUKAN);
                })->orWhere(function ($approvedQuery): void {
                    $approvedQuery
                        ->where('qty_disetujui', '>', 0)
                        ->whereHas('permintaanPembelian', function ($requestQuery): void {
                            $requestQuery->whereIn('status', [
                                PermintaanPembelian::STATUS_DISETUJUI,
                                PermintaanPembelian::STATUS_PO_DIBUAT,
                            ]);
                        });
                });
            })
            ->select('barang_id')
            ->distinct();

        $stockTotals = StokGudang::query()
            ->select('barang_id')
            ->selectRaw('COALESCE(SUM(qty), 0) AS total_stok')
            ->groupBy('barang_id');

        $totalStockSql = 'COALESCE(stok_per_barang.total_stok, 0)';

        $barangsQuery = Barang::query()
            ->leftJoinSub($stockTotals, 'stok_per_barang', function ($join): void {
                $join->on('stok_per_barang.barang_id', '=', 'barangs.id');
            })
            ->with('satuan:id,nama_satuan')
            ->where('barangs.is_active', true)
            ->where('barangs.minimum_stok', '>', 0)
            ->whereRaw("{$totalStockSql} <= barangs.minimum_stok")
            ->whereNotIn('barangs.id', $activeRequestBarangIds);

        // Prioritaskan saldo nol/negatif, lalu rasio stok terhadap minimum terendah.
        // Dengan demikian daftar tidak membanjiri Gudang dengan ribuan barang sekaligus.
        $barangs = (clone $barangsQuery)
            ->orderByRaw("CASE WHEN {$totalStockSql} <= 0 THEN 0 ELSE 1 END")
            ->orderByRaw("({$totalStockSql} / NULLIF(barangs.minimum_stok, 0)) ASC")
            ->orderByRaw("{$totalStockSql} ASC")
            ->orderBy('barangs.nama_barang')
            ->limit(10)
            ->get([
                'barangs.id',
                'barangs.kode_barang',
                'barangs.nama_barang',
                'barangs.satuan_id',
                'barangs.minimum_stok',
                DB::raw("{$totalStockSql} AS total_stok"),
            ]);

        return view('permintaan-pembelian.create', compact('barangs'));
    }

    /** Gudang submits selected low-stock items. Stock is not changed here. */
    public function store(Request $request): RedirectResponse
    {
        // Ignore unchecked rows before validating the selected request details.
        $selectedItems = collect($request->input('items', []))
            ->filter(fn ($item): bool => is_array($item) && !empty($item['selected']))
            ->map(fn (array $item): array => [
                'barang_id' => $item['barang_id'] ?? null,
                'qty_diminta' => $item['qty_diminta'] ?? null,
            ])
            ->all();

        $request->merge(['items' => $selectedItems]);

        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:10'],
            'items.*.barang_id' => ['required', 'integer', 'distinct', 'exists:barangs,id'],
            'items.*.qty_diminta' => ['required', 'numeric', 'gt:0', 'max:999999999'],
            'catatan' => ['nullable', 'string', 'max:2000'],
        ], [
            'items.required' => 'Pilih minimal satu barang untuk diajukan.',
            'items.min' => 'Pilih minimal satu barang untuk diajukan.',
            'items.max' => 'Maksimal 10 barang dalam satu permintaan. Ajukan barang lainnya pada permintaan berikutnya.',
            'items.*.qty_diminta.gt' => 'Jumlah permintaan harus lebih dari nol.',
        ]);

        $permintaan = DB::transaction(function () use ($validated): PermintaanPembelian {
            $barangIds = collect($validated['items'])
                ->pluck('barang_id')
                ->map(fn ($id): int => (int) $id)
                ->sort()
                ->values();

            // Lock master rows in a stable order to serialize requests for the same items.
            $barangs = Barang::query()
                ->whereIn('id', $barangIds)
                ->where('is_active', true)
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            if ($barangs->count() !== $barangIds->count()) {
                throw ValidationException::withMessages([
                    'items' => 'Ada barang yang sudah tidak aktif atau tidak ditemukan. Muat ulang daftar stok.',
                ]);
            }

            $hasActiveRequest = PermintaanPembelianDetail::query()
                ->whereIn('barang_id', $barangIds)
                ->where(function ($query): void {
                    $query->whereHas('permintaanPembelian', function ($requestQuery): void {
                        $requestQuery->where('status', PermintaanPembelian::STATUS_DIAJUKAN);
                    })->orWhere(function ($approvedQuery): void {
                        $approvedQuery
                            ->where('qty_disetujui', '>', 0)
                            ->whereHas('permintaanPembelian', function ($requestQuery): void {
                                $requestQuery->whereIn('status', [
                                    PermintaanPembelian::STATUS_DISETUJUI,
                                    PermintaanPembelian::STATUS_PO_DIBUAT,
                                ]);
                            });
                    });
                })
                ->exists();

            if ($hasActiveRequest) {
                throw ValidationException::withMessages([
                    'items' => 'Salah satu barang sudah memiliki permintaan pembelian aktif. Periksa daftar permintaan terlebih dahulu.',
                ]);
            }

            $stockTotals = StokGudang::query()
                ->whereIn('barang_id', $barangIds)
                ->select('barang_id')
                ->selectRaw('COALESCE(SUM(qty), 0) AS total_stok')
                ->groupBy('barang_id')
                ->pluck('total_stok', 'barang_id');

            foreach ($barangIds as $barangId) {
                $barang = $barangs->get($barangId);
                $totalStok = (float) ($stockTotals->get($barangId) ?? 0);

                if ((float) $barang->minimum_stok <= 0 || $totalStok > (float) $barang->minimum_stok) {
                    throw ValidationException::withMessages([
                        'items' => "Stok {$barang->nama_barang} sudah tidak berada pada atau di bawah batas minimum. Muat ulang daftar stok.",
                    ]);
                }
            }

            $permintaan = PermintaanPembelian::create([
                'nomor_permintaan' => 'TEMP-' . Str::uuid(),
                'tanggal_permintaan' => today(),
                'status' => PermintaanPembelian::STATUS_DIAJUKAN,
                'catatan' => $validated['catatan'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $permintaan->update([
                'nomor_permintaan' => 'PP-' . now()->format('Ym') . '-' . str_pad((string) $permintaan->id, 6, '0', STR_PAD_LEFT),
            ]);

            foreach ($validated['items'] as $item) {
                $barangId = (int) $item['barang_id'];
                $barang = $barangs->get($barangId);

                $permintaan->details()->create([
                    'barang_id' => $barangId,
                    'stok_saat_pengajuan' => round((float) ($stockTotals->get($barangId) ?? 0), 2),
                    'minimum_stok' => round((float) $barang->minimum_stok, 2),
                    'qty_diminta' => round((float) $item['qty_diminta'], 2),
                ]);
            }

            $purchasingUsers = User::query()
                ->where('role', 'Pembelian')
                ->where('is_active', true)
                ->get();

            Notification::send($purchasingUsers, new PermintaanPembelianNotification(
                $permintaan->id,
                $permintaan->nomor_permintaan,
                'diajukan',
                "Permintaan {$permintaan->nomor_permintaan} dari Gudang menunggu pemeriksaan Pembelian.",
            ));

            return $permintaan;
        }, 3);

        return redirect()
            ->route('permintaan-pembelian.show', $permintaan)
            ->with('success', "Permintaan {$permintaan->nomor_permintaan} berhasil dikirim ke bagian Pembelian.");
    }

    public function show(PermintaanPembelian $permintaanPembelian): View
    {
        $user = auth()->user();

        abort_if(
            $user->role === 'Gudang' && (int) $permintaanPembelian->created_by !== (int) $user->id,
            403,
            'Anda hanya dapat melihat permintaan yang diajukan oleh akun Anda.',
        );

        $permintaanPembelian->load([
            'creator',
            'reviewer',
            'details.barang.satuan',
            'purchaseOrder',
        ]);

        // Opening a request marks only its related database notifications as read.
        $user->unreadNotifications()
            ->where('type', PermintaanPembelianNotification::class)
            ->get()
            ->each(function ($notification) use ($permintaanPembelian): void {
                if ((int) ($notification->data['permintaan_pembelian_id'] ?? 0) === (int) $permintaanPembelian->id) {
                    $notification->markAsRead();
                }
            });

        return view('permintaan-pembelian.show', compact('permintaanPembelian'));
    }

    /** Pembelian reviews quantities and approves the request before PO creation. */
    public function approve(Request $request, PermintaanPembelian $permintaanPembelian): RedirectResponse
    {
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1'],
            'items.*.qty_disetujui' => ['required', 'numeric', 'min:0', 'max:999999999'],
        ]);

        DB::transaction(function () use ($validated, $permintaanPembelian): void {
            $lockedRequest = PermintaanPembelian::query()
                ->whereKey($permintaanPembelian->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRequest->status !== PermintaanPembelian::STATUS_DIAJUKAN) {
                throw ValidationException::withMessages([
                    'status' => 'Permintaan ini sudah diproses dan tidak dapat disetujui kembali.',
                ]);
            }

            $details = $lockedRequest->details()
                ->orderBy('id')
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $inputItems = collect($validated['items'])
                ->mapWithKeys(fn ($item, $id): array => [(int) $id => $item]);
            $inputIds = $inputItems->keys()->map(fn ($id): int => (int) $id)->sort()->values();
            $detailIds = $details->keys()->map(fn ($id): int => (int) $id)->sort()->values();

            if ($inputIds->all() !== $detailIds->all()) {
                throw ValidationException::withMessages([
                    'items' => 'Detail persetujuan tidak sesuai dengan detail permintaan.',
                ]);
            }

            $totalDisetujui = 0.0;

            foreach ($details as $detailId => $detail) {
                $qtyDisetujui = round((float) $inputItems->get($detailId)['qty_disetujui'], 2);

                if ($qtyDisetujui > (float) $detail->qty_diminta) {
                    throw ValidationException::withMessages([
                        "items.{$detailId}.qty_disetujui" => 'Jumlah disetujui tidak boleh melebihi jumlah yang diminta Gudang.',
                    ]);
                }

                $detail->update(['qty_disetujui' => $qtyDisetujui]);
                $totalDisetujui += $qtyDisetujui;
            }

            if ($totalDisetujui <= 0) {
                throw ValidationException::withMessages([
                    'items' => 'Setujui minimal satu barang dengan jumlah lebih dari nol.',
                ]);
            }

            $lockedRequest->update([
                'status' => PermintaanPembelian::STATUS_DISETUJUI,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'alasan_penolakan' => null,
            ]);

            $lockedRequest->creator?->notify(new PermintaanPembelianNotification(
                $lockedRequest->id,
                $lockedRequest->nomor_permintaan,
                'disetujui',
                "Permintaan {$lockedRequest->nomor_permintaan} telah disetujui Pembelian dan siap dibuatkan PO.",
            ));
        }, 3);

        return redirect()
            ->route('permintaan-pembelian.show', $permintaanPembelian)
            ->with('success', 'Permintaan berhasil disetujui. Pembelian dapat melanjutkan pembuatan PO.');
    }

    public function reject(Request $request, PermintaanPembelian $permintaanPembelian): RedirectResponse
    {
        $validated = $request->validate([
            'alasan_penolakan' => ['required', 'string', 'min:5', 'max:2000'],
        ]);

        DB::transaction(function () use ($validated, $permintaanPembelian): void {
            $lockedRequest = PermintaanPembelian::query()
                ->whereKey($permintaanPembelian->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($lockedRequest->status !== PermintaanPembelian::STATUS_DIAJUKAN) {
                throw ValidationException::withMessages([
                    'status' => 'Hanya permintaan yang masih diajukan yang dapat ditolak.',
                ]);
            }

            $lockedRequest->update([
                'status' => PermintaanPembelian::STATUS_DITOLAK,
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
                'alasan_penolakan' => $validated['alasan_penolakan'],
            ]);

            $lockedRequest->creator?->notify(new PermintaanPembelianNotification(
                $lockedRequest->id,
                $lockedRequest->nomor_permintaan,
                'ditolak',
                "Permintaan {$lockedRequest->nomor_permintaan} ditolak Pembelian. Alasan: {$validated['alasan_penolakan']}",
            ));
        }, 3);

        return redirect()
            ->route('permintaan-pembelian.show', $permintaanPembelian)
            ->with('success', 'Permintaan berhasil ditolak dan Gudang sudah diberi notifikasi.');
    }
}
