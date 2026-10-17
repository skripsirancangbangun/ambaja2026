<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversion extends Model
{
    protected $fillable = [
        'nomor_konversi',
        'tanggal',
        'gudang_id',
        'total_nilai',
        'status',
        'keterangan',
        'created_by',
        'posted_by',
        'posted_at',
        'cancelled_at',
    ];

    protected $casts = [
        'tanggal' => 'datetime',
        'total_nilai' => 'decimal:2',
        'posted_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /*
    |--------------------------------------------------------------------------
    | STATUS
    |--------------------------------------------------------------------------
    */

    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_POSTED = 'POSTED';
    public const STATUS_CANCELLED = 'CANCELLED';

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIP
    |--------------------------------------------------------------------------
    */

    public function items(): HasMany
    {
        return $this->hasMany(
            ConversionItem::class
        );
    }

    public function gudang(): BelongsTo
    {
        return $this->belongsTo(
            Gudang::class
        );
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    public function poster(): BelongsTo
    {
        return $this->belongsTo(
            User::class,
            'posted_by'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER
    |--------------------------------------------------------------------------
    */

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isPosted(): bool
    {
        return $this->status === self::STATUS_POSTED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }
}