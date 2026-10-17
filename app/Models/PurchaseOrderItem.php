<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseOrderItem extends Model
{
    protected $fillable = [
        'purchase_order_id',
        'barang_id',
        'qty',
        'harga_satuan',
        'diskon',
        'subtotal',
        'qty_diterima',
        'qty_dibatalkan',
        'keterangan',
    ];

    protected $casts = [
        'qty' => 'decimal:2',
        'harga_satuan' => 'decimal:2',
        'diskon' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'qty_diterima' => 'decimal:2',
        'qty_dibatalkan' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | RELATIONSHIPS
    |--------------------------------------------------------------------------
    */

    public function purchaseOrder()
    {
        return $this->belongsTo(
            PurchaseOrder::class
        );
    }

    public function barang()
    {
        return $this->belongsTo(
            Barang::class
        );
    }

    public function penerimaanItems()
    {
        return $this->hasMany(
            PenerimaanBarangItem::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | QTY PESAN EFEKTIF
    |--------------------------------------------------------------------------
    |
    | Qty efektif =
    | Qty PO - Qty yang dibatalkan.
    |
    */

    public function getQtyEfektifAttribute(): float
    {
        return round(
            max(
                0,
                (float) $this->qty
                - (float) $this->qty_dibatalkan
            ),
            2
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SISA PENERIMAAN
    |--------------------------------------------------------------------------
    */

    public function getSisaPenerimaanAttribute(): float
    {
        return round(
            max(
                0,
                (float) $this->qty
                - (float) $this->qty_diterima
                - (float) $this->qty_dibatalkan
            ),
            2
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PERSENTASE PENERIMAAN
    |--------------------------------------------------------------------------
    */

    public function getPersentasePenerimaanAttribute(): float
    {
        $qtyEfektif =
            (float) $this->qty_efektif;

        if ($qtyEfektif <= 0) {
            return 0;
        }

        return round(
            min(
                100,
                (
                    (float) $this->qty_diterima
                    / $qtyEfektif
                ) * 100
            ),
            2
        );
    }

    /*
    |--------------------------------------------------------------------------
    | STATUS PENERIMAAN ITEM
    |--------------------------------------------------------------------------
    */

    public function getStatusPenerimaanAttribute(): string
    {
        $qty =
            (float) $this->qty;

        $diterima =
            (float) $this->qty_diterima;

        $dibatalkan =
            (float) $this->qty_dibatalkan;

        if (
            $qty > 0
            &&
            $dibatalkan >= $qty
        ) {
            return 'Dibatalkan';
        }

        if ($diterima <= 0) {
            return 'Belum Diterima';
        }

        if (
            (float) $this->sisa_penerimaan
            > 0
        ) {
            return 'Sebagian';
        }

        return 'Lengkap';
    }

    /*
    |--------------------------------------------------------------------------
    | NILAI BRUTO ITEM
    |--------------------------------------------------------------------------
    */

    public function getNilaiBrutoAttribute(): float
    {
        return round(
            (float) $this->qty
            * (float) $this->harga_satuan,
            2
        );
    }
}