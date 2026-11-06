<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Barang extends Model
{
    protected $fillable = [
        'kode_barang',
        'nama_barang',
        'kategori_barang_id',
        'satuan_id',
        'spesifikasi',
        'merek',
        'harga_beli_default',
        'margin_persen',
        'minimum_stok',
        'harga_jual',
        'is_active',
    ];

    protected $casts = [
        'harga_beli_default' => 'decimal:2',
        'margin_persen' => 'decimal:2',
        'minimum_stok' => 'decimal:2',
        'harga_jual' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function kategori()
    {
        return $this->belongsTo(
            KategoriBarang::class,
            'kategori_barang_id'
        );
    }

    public function satuan()
    {
        return $this->belongsTo(Satuan::class);
    }

    public function suppliers()
    {
        return $this->belongsToMany(Supplier::class, 'barang_supplier')
            ->withTimestamps();
    }

    public function purchaseOrderItems()
    {
        return $this->hasMany(PurchaseOrderItem::class);
    }

    public function mutasiGudangItems()
    {
        return $this->hasMany(
            MutasiGudangItem::class
        );
    }

    public function stockOpnameItems()
    {
        return $this->hasMany(
            StockOpnameItem::class
        );
    }

    public function penyesuaianStokItems()
    {
        return $this->hasMany(
            PenyesuaianStokItem::class
        );
    }

    public function invoiceSupplierItems()
    {
        return $this->hasMany(
            InvoiceSupplierItem::class
        );
    }

    public function returPembelianDetails()
    {
        return $this->hasMany(
            ReturPembelianDetail::class
        );
    }

    public function stokGudangs()
    {
        return $this->hasMany(
            StokGudang::class
        );
    }

    public function getStokQty($gudangId)
    {
        return (float) (
            $this->stokGudangs
                ->where(
                    'gudang_id',
                    $gudangId
                )
                ->first()?->qty
            ?? 0
        );
    }

    /**
     * Menghitung SARAN harga jual berdasarkan target markup.
     *
     * Method ini TIDAK mengubah:
     * - harga_jual
     * - margin_persen
     *
     * Jadi perubahan HPP/stok tidak otomatis mengubah setting laba.
     */
    public function hitungHargaJualSaran(
        ?float $hargaDasar = null
    ): float {
        $hargaDasar ??=
            (float) $this->harga_beli_default;

        $markup =
            (float) $this->margin_persen;

        return round(
            $hargaDasar
            + (
                $hargaDasar
                * $markup
                / 100
            ),
            2
        );
    }

    /**
     * Bisa dipanggil:
     *
     * $barang->harga_jual_saran
     */
    public function getHargaJualSaranAttribute(): float
    {
        return $this->hitungHargaJualSaran();
    }
}