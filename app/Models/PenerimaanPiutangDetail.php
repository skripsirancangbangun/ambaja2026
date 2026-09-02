<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PenerimaanPiutangDetail extends Model
{
    use HasFactory;

    protected $fillable = [
        'penerimaan_piutang_id',
        'invoice_penjualan_id',
        'nominal_dibayar',
    ];

    public function penerimaanPiutang()
    {
        return $this->belongsTo(PenerimaanPiutang::class);
    }

    public function invoicePenjualan()
    {
        return $this->belongsTo(InvoicePenjualan::class);
    }
}
