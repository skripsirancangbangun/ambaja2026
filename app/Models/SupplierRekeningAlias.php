<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SupplierRekeningAlias extends Model
{
    protected $table = 'supplier_rekening_aliases';

    protected $fillable = [
        'supplier_id',
        'nama_alias',
        'nama_alias_normalized',
        'rekening_kas_bank_id',
        'sumber',
        'is_active',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];


    /*
    |--------------------------------------------------------------------------
    | SUPPLIER
    |--------------------------------------------------------------------------
    */

    public function supplier()
    {
        return $this->belongsTo(
            Supplier::class,
            'supplier_id'
        );
    }


    /*
    |--------------------------------------------------------------------------
    | REKENING KAS / BANK
    |--------------------------------------------------------------------------
    */

    public function rekeningKasBank()
    {
        return $this->belongsTo(
            RekeningKasBank::class,
            'rekening_kas_bank_id'
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
}