<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ConversionItem extends Model
{
    protected $fillable = [
        'conversion_id',
        'barang_id',
        'tipe',
        'qty',
        'satuan',
        'panjang',
        'berat',
        'hpp',
        'nilai',
        'keterangan',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'panjang' => 'decimal:4',
        'berat' => 'decimal:4',
        'hpp' => 'decimal:2',
        'nilai' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | TIPE
    |--------------------------------------------------------------------------
    */

    public const TIPE_INPUT = 'INPUT';

    public const TIPE_OUTPUT = 'OUTPUT';

    public const TIPE_WASTE = 'WASTE';

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIP
    |--------------------------------------------------------------------------
    */

    public function conversion(): BelongsTo
    {
        return $this->belongsTo(
            Conversion::class
        );
    }

    public function barang(): BelongsTo
    {
        return $this->belongsTo(
            Barang::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER
    |--------------------------------------------------------------------------
    */

    public function isInput(): bool
    {
        return $this->tipe === self::TIPE_INPUT;
    }

    public function isOutput(): bool
    {
        return $this->tipe === self::TIPE_OUTPUT;
    }

    public function isWaste(): bool
    {
        return $this->tipe === self::TIPE_WASTE;
    }
}