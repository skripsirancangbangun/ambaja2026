<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PermintaanPembelian extends Model
{
    public const STATUS_DIAJUKAN = 'DIAJUKAN';
    public const STATUS_DISETUJUI = 'DISETUJUI';
    public const STATUS_DITOLAK = 'DITOLAK';
    public const STATUS_PO_DIBUAT = 'PO_DIBUAT';
    public const STATUS_SELESAI = 'SELESAI';

    protected $fillable = [
        'nomor_permintaan',
        'tanggal_permintaan',
        'status',
        'catatan',
        'alasan_penolakan',
        'created_by',
        'reviewed_by',
        'reviewed_at',
        'purchase_order_id',
    ];

    protected $casts = [
        'tanggal_permintaan' => 'date',
        'reviewed_at' => 'datetime',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function details(): HasMany
    {
        return $this->hasMany(PermintaanPembelianDetail::class);
    }

    public function purchaseOrder(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrder::class);
    }
}
