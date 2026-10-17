<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class PurchaseOrder extends Model
{
    protected $fillable = [
        'nomor_po',
        'supplier_id',
        'tanggal_po',
        'tanggal_estimasi_datang',
        'catatan',
        'subtotal',
        'diskon',
        'pajak',
        'pajak_persen',
        'total',
        'status',
        'created_by',
    ];

    protected $casts = [
        'tanggal_po' =>
            'date',

        'tanggal_estimasi_datang' =>
            'date',

        'subtotal' =>
            'decimal:2',

        'diskon' =>
            'decimal:2',

        'pajak' =>
            'decimal:2',

        'pajak_persen' =>
            'decimal:2',

        'total' =>
            'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | SUPPLIER
    |--------------------------------------------------------------------------
    */

    public function supplier()
    {
        return $this->belongsTo(
            Supplier::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ITEM
    |--------------------------------------------------------------------------
    */

    public function items()
    {
        return $this->hasMany(
            PurchaseOrderItem::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | USER PEMBUAT
    |--------------------------------------------------------------------------
    */

    public function creator()
    {
        return $this->belongsTo(
            User::class,
            'created_by'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PENERIMAAN BARANG
    |--------------------------------------------------------------------------
    */

    public function penerimaanBarangs()
    {
        return $this->hasMany(
            PenerimaanBarang::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | INVOICE SUPPLIER
    |--------------------------------------------------------------------------
    */

    public function invoiceSuppliers()
    {
        return $this->hasMany(
            InvoiceSupplier::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | RETUR PEMBELIAN
    |--------------------------------------------------------------------------
    */

    public function returPembelians()
    {
        return $this->hasMany(
            ReturPembelian::class
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PEMBAYARAN PO SUPPLIER
    |--------------------------------------------------------------------------
    |
    | Pembayaran yang dilakukan sebelum atau saat
    | proses penerimaan barang.
    |
    | PembayaranSupplier:
    |
    | Invoice Supplier
    | -> Utang
    | -> Bayar
    |
    | PembayaranPOSupplier:
    |
    | PO
    | -> Uang Muka Pembelian
    | -> Invoice Supplier nanti
    |
    */

    public function pembayaranPOSuppliers()
    {
        return $this->hasMany(
            PembayaranPOSupplier::class,
            'purchase_order_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | ALOKASI UANG MUKA
    |--------------------------------------------------------------------------
    */

    public function uangMukaAllocations()
    {
        return $this->hasMany(
            InvoiceSupplierUangMukaAllocation::class,
            'purchase_order_id'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | HELPER ITEM COLLECTION
    |--------------------------------------------------------------------------
    */

    private function getItemsForCalculation(): Collection
    {
        if (
            $this->relationLoaded(
                'items'
            )
        ) {
            return $this->items;
        }

        return $this
            ->items()
            ->get();
    }

    /*
    |--------------------------------------------------------------------------
    | TOTAL QTY PESAN
    |--------------------------------------------------------------------------
    */

    public function getTotalQtyPesanAttribute(): float
    {
        return round(
            (float)
            $this
                ->getItemsForCalculation()
                ->sum(
                    fn ($item) =>
                    (float) $item->qty
                ),
            2
        );
    }

    /*
    |--------------------------------------------------------------------------
    | TOTAL QTY DITERIMA
    |--------------------------------------------------------------------------
    */

    public function getTotalQtyDiterimaAttribute(): float
    {
        return round(
            (float)
            $this
                ->getItemsForCalculation()
                ->sum(
                    fn ($item) =>
                    (float)
                    $item->qty_diterima
                ),
            2
        );
    }

    /*
    |--------------------------------------------------------------------------
    | TOTAL QTY DIBATALKAN
    |--------------------------------------------------------------------------
    */

    public function getTotalQtyDibatalkanAttribute(): float
    {
        return round(
            (float)
            $this
                ->getItemsForCalculation()
                ->sum(
                    fn ($item) =>
                    (float)
                    $item->qty_dibatalkan
                ),
            2
        );
    }

    /*
    |--------------------------------------------------------------------------
    | TOTAL QTY EFEKTIF
    |--------------------------------------------------------------------------
    */

    public function getTotalQtyEfektifAttribute(): float
    {
        return round(
            max(
                0,

                (float)
                $this->total_qty_pesan

                -

                (float)
                $this->total_qty_dibatalkan
            ),
            2
        );
    }

    /*
    |--------------------------------------------------------------------------
    | TOTAL SISA PENERIMAAN
    |--------------------------------------------------------------------------
    */

    public function getTotalQtySisaAttribute(): float
    {
        return round(
            (float)
            $this
                ->getItemsForCalculation()
                ->sum(
                    fn ($item) =>
                    (float)
                    $item->sisa_penerimaan
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
            (float)
            $this->total_qty_efektif;

        if (
            $qtyEfektif <= 0
        ) {
            return 0;
        }

        return round(
            min(
                100,

                (
                    (float)
                    $this->total_qty_diterima

                    /

                    $qtyEfektif
                )

                * 100
            ),
            2
        );
    }

    /*
    |--------------------------------------------------------------------------
    | STATUS PENERIMAAN
    |--------------------------------------------------------------------------
    */

    public function getStatusPenerimaanAttribute(): string
    {
        $qtyPesan =
            (float)
            $this->total_qty_pesan;

        $qtyEfektif =
            (float)
            $this->total_qty_efektif;

        $qtyDiterima =
            (float)
            $this->total_qty_diterima;

        if (
            $qtyPesan <= 0
        ) {
            return 'BELUM DITERIMA';
        }

        if (
            $qtyEfektif <= 0
            &&
            (float)
            $this->total_qty_dibatalkan > 0
        ) {
            return 'DIBATALKAN';
        }

        if (
            $qtyDiterima <= 0
        ) {
            return 'BELUM DITERIMA';
        }

        if (
            (float)
            $this->total_qty_sisa > 0
        ) {
            return 'SEBAGIAN';
        }

        return 'LENGKAP';
    }

    /*
    |--------------------------------------------------------------------------
    | TOTAL PEMBAYARAN PO
    |--------------------------------------------------------------------------
    |
    | Hanya pembayaran TERCATAT yang dihitung.
    |
    */

    public function getTotalDibayarPoAttribute(): float
    {
        if (
            $this->relationLoaded(
                'pembayaranPOSuppliers'
            )
        ) {
            $total =
                $this
                    ->pembayaranPOSuppliers
                    ->where(
                        'status',
                        'TERCATAT'
                    )
                    ->sum(
                        'jumlah_bayar'
                    );
        } else {
            $total =
                $this
                    ->pembayaranPOSuppliers()
                    ->where(
                        'status',
                        'TERCATAT'
                    )
                    ->sum(
                        'jumlah_bayar'
                    );
        }

        return round(
            (float) $total,
            2
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SISA PEMBAYARAN PO
    |--------------------------------------------------------------------------
    */

    public function getSisaPembayaranPoAttribute(): float
    {
        return round(
            max(
                0,

                (float)
                $this->total

                -

                (float)
                $this->total_dibayar_po
            ),
            2
        );
    }

    /*
    |--------------------------------------------------------------------------
    | PERSENTASE PEMBAYARAN
    |--------------------------------------------------------------------------
    */

    public function getPersentasePembayaranAttribute(): float
    {
        $totalPO =
            (float)
            $this->total;

        if (
            $totalPO <= 0
        ) {
            return 0;
        }

        return round(
            min(
                100,

                (
                    (float)
                    $this->total_dibayar_po

                    /

                    $totalPO
                )

                * 100
            ),
            2
        );
    }

    /*
    |--------------------------------------------------------------------------
    | STATUS PEMBAYARAN
    |--------------------------------------------------------------------------
    |
    | Status pembayaran tidak bergantung pada penerimaan.
    |
    */

    public function getStatusPembayaranAttribute(): string
    {
        $totalPO =
            round(
                (float)
                $this->total,
                2
            );

        $dibayar =
            round(
                (float)
                $this->total_dibayar_po,
                2
            );

        if (
            $dibayar <= 0
        ) {
            return 'BELUM DIBAYAR';
        }

        if (
            $dibayar + 0.01
            <
            $totalPO
        ) {
            return 'SEBAGIAN';
        }

        return 'LUNAS';
    }

    /*
    |--------------------------------------------------------------------------
    | TOTAL UANG MUKA SUDAH DIALOKASIKAN
    |--------------------------------------------------------------------------
    */

    public function getTotalUangMukaDialokasikanAttribute(): float
    {
        if (
            $this->relationLoaded(
                'uangMukaAllocations'
            )
        ) {
            $total =
                $this
                    ->uangMukaAllocations
                    ->sum(
                        'jumlah_alokasi'
                    );
        } else {
            $total =
                $this
                    ->uangMukaAllocations()
                    ->sum(
                        'jumlah_alokasi'
                    );
        }

        return round(
            (float) $total,
            2
        );
    }

    /*
    |--------------------------------------------------------------------------
    | SISA UANG MUKA
    |--------------------------------------------------------------------------
    |
    | Contoh:
    |
    | PO dibayar di muka        Rp 6.300.000
    | Invoice pertama           Rp   630.000
    |
    | Setelah invoice dialokasi:
    |
    | Sudah dialokasikan        Rp   630.000
    | Sisa uang muka            Rp 5.670.000
    |
    */

    public function getSisaUangMukaAttribute(): float
    {
        return round(
            max(
                0,

                (float)
                $this->total_dibayar_po

                -

                (float)
                $this->total_uang_muka_dialokasikan
            ),
            2
        );
    }

    /*
    |--------------------------------------------------------------------------
    | STATUS HELPER
    |--------------------------------------------------------------------------
    */

    public function getIsDraftAttribute(): bool
    {
        return
            $this->status
            === 'Draft';
    }

    public function getIsDipesanAttribute(): bool
    {
        return
            $this->status
            === 'Dipesan';
    }

    /*
    |--------------------------------------------------------------------------
    | BISA MENERIMA BARANG
    |--------------------------------------------------------------------------
    */

    public function getBisaDiterimaAttribute(): bool
    {
        return (
            $this->status !== 'Draft'
            &&
            (float)
            $this->total_qty_sisa > 0
        );
    }

    /*
    |--------------------------------------------------------------------------
    | BISA DIBAYAR DI LEVEL PO
    |--------------------------------------------------------------------------
    */

    public function getBisaDibayarPoAttribute(): bool
    {
        return (
            $this->status !== 'Draft'
            &&
            (float)
            $this->sisa_pembayaran_po > 0
        );
    }
}