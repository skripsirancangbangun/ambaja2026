<?php

namespace App\Http\Controllers;

use App\Models\Barang;
use App\Models\Gudang;
use App\Models\InvoicePenjualan;
use App\Models\MutasiStok;
use App\Models\PenerimaanBarang;
use App\Models\PenerimaanBarangItem;
use App\Models\ReturPembelian;
use App\Models\ReturPenjualan;
use Illuminate\Http\Request;

class KartuStokController extends Controller
{
    public function index(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | QUERY MUTASI STOK
        |--------------------------------------------------------------------------
        */

        $query = MutasiStok::with([
            'barang.satuan',
            'barang.kategori',
            'gudang',
        ]);


        /*
        |--------------------------------------------------------------------------
        | FILTER BARANG
        |--------------------------------------------------------------------------
        */

        if ($request->filled('barang_id')) {

            $query->where(
                'barang_id',
                $request->barang_id
            );

        }


        /*
        |--------------------------------------------------------------------------
        | FILTER GUDANG
        |--------------------------------------------------------------------------
        */

        if ($request->filled('gudang_id')) {

            $query->where(
                'gudang_id',
                $request->gudang_id
            );

        }


        /*
        |--------------------------------------------------------------------------
        | FILTER TIPE
        |--------------------------------------------------------------------------
        */

        if ($request->filled('tipe')) {

            $query->where(
                'tipe',
                $request->tipe
            );

        }


        /*
        |--------------------------------------------------------------------------
        | FILTER TANGGAL
        |--------------------------------------------------------------------------
        */

        if ($request->filled('tanggal_mulai')) {

            $query->whereDate(
                'tanggal',
                '>=',
                $request->tanggal_mulai
            );

        }

        if ($request->filled('tanggal_selesai')) {

            $query->whereDate(
                'tanggal',
                '<=',
                $request->tanggal_selesai
            );

        }


        /*
        |--------------------------------------------------------------------------
        | SEARCH
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {

            $search = trim(
                (string) $request->search
            );

            $query->where(function ($q) use ($search) {

                $q->where(
                    'keterangan',
                    'like',
                    "%{$search}%"
                )

                ->orWhere(
                    'reference_type',
                    'like',
                    "%{$search}%"
                )

                ->orWhere(
                    'reference_id',
                    'like',
                    "%{$search}%"
                )

                ->orWhereHas(
                    'barang',
                    function ($barangQuery) use ($search) {

                        $barangQuery
                            ->where(
                                'kode_barang',
                                'like',
                                "%{$search}%"
                            )

                            ->orWhere(
                                'nama_barang',
                                'like',
                                "%{$search}%"
                            )

                            ->orWhere(
                                'spesifikasi',
                                'like',
                                "%{$search}%"
                            );

                    }
                );

            });

        }


        /*
        |--------------------------------------------------------------------------
        | AMBIL MUTASI
        |--------------------------------------------------------------------------
        */

        $mutasiStoks = $query
            ->orderByDesc('tanggal')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();


        /*
        |--------------------------------------------------------------------------
        | MASTER BARANG
        |--------------------------------------------------------------------------
        */

        $barangs = Barang::with('satuan')
            ->where(
                'is_active',
                true
            )
            ->orderBy('nama_barang')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | MASTER GUDANG
        |--------------------------------------------------------------------------
        */

        $gudangs = Gudang::where(
            'is_active',
            true
        )
            ->orderBy('nama_gudang')
            ->get();


        /*
        |--------------------------------------------------------------------------
        | REFERENSI TRANSAKSI
        |--------------------------------------------------------------------------
        |
        | Dibuat dalam bentuk:
        |
        | reference_type : reference_id
        |
        | Contoh:
        |
        | PENERIMAAN_BARANG:19
        | RETUR_PEMBELIAN:5
        | INVOICE_PENJUALAN:10
        | RETUR_PENJUALAN:3
        |
        */

        $referensiMap = [];


        /*
        |--------------------------------------------------------------------------
        | PENERIMAAN BARANG
        |--------------------------------------------------------------------------
        |
        | Penting:
        | reference_id pada mutasi PENERIMAAN_BARANG
        | menunjuk ke PenerimaanBarangItem.
        |
        | Jadi:
        |
        | MutasiStok.reference_id
        |       ↓
        | PenerimaanBarangItem.id
        |       ↓
        | PenerimaanBarang.id
        |       ↓
        | PurchaseOrder
        |       ↓
        | Supplier
        |
        */

        $penerimaanItemIds = $mutasiStoks
            ->getCollection()
            ->filter(function ($mutasi) {

                return strtoupper(
                    trim(
                        (string) $mutasi->reference_type
                    )
                ) === 'PENERIMAAN_BARANG'
                &&
                !empty(
                    $mutasi->reference_id
                );

            })
            ->pluck('reference_id')
            ->map(
                fn ($id) => (int) $id
            )
            ->unique()
            ->values();


        if ($penerimaanItemIds->isNotEmpty()) {

            $penerimaanItems =
                PenerimaanBarangItem::with([
                    'penerimaanBarang.purchaseOrder.supplier',
                ])
                    ->whereIn(
                        'id',
                        $penerimaanItemIds
                    )
                    ->get();


            foreach (
                $penerimaanItems
                as $penerimaanItem
            ) {

                $penerimaan =
                    $penerimaanItem
                        ->penerimaanBarang;

                if (!$penerimaan) {
                    continue;
                }


                $supplier =
                    $penerimaan
                        ->purchaseOrder
                        ?->supplier;


                $supplierName =
                    $supplier
                        ?->nama_supplier
                        ?? 'Supplier';


                $nomorPenerimaan =
                    $penerimaan
                        ->nomor_penerimaan
                        ?? (
                            'PB #'
                            . $penerimaan->id
                        );


                $referensiMap[
                    'PENERIMAAN_BARANG:'
                    . $penerimaanItem->id
                ] = [

                    'label' =>
                        'SUPPLIER',

                    'nama' =>
                        $supplierName,

                    'nomor' =>
                        $nomorPenerimaan,

                ];

            }

        }


        /*
        |--------------------------------------------------------------------------
        | FALLBACK PENERIMAAN BARANG
        |--------------------------------------------------------------------------
        |
        | Untuk berjaga-jaga jika ada data lama yang menyimpan
        | reference_id langsung ke PenerimaanBarang.
        |
        */

        $penerimaanIds = $mutasiStoks
            ->getCollection()
            ->filter(function ($mutasi) {

                return strtoupper(
                    trim(
                        (string) $mutasi->reference_type
                    )
                ) === 'PENERIMAAN_BARANG'
                &&
                !empty(
                    $mutasi->reference_id
                );

            })
            ->pluck('reference_id')
            ->map(
                fn ($id) => (int) $id
            )
            ->unique()
            ->values();


        if ($penerimaanIds->isNotEmpty()) {

            $penerimaans =
                PenerimaanBarang::with([
                    'purchaseOrder.supplier',
                ])
                    ->whereIn(
                        'id',
                        $penerimaanIds
                    )
                    ->get();


            foreach (
                $penerimaans
                as $penerimaan
            ) {

                $supplier =
                    $penerimaan
                        ->purchaseOrder
                        ?->supplier;


                $supplierName =
                    $supplier
                        ?->nama_supplier
                        ?? 'Supplier';


                $nomorPenerimaan =
                    $penerimaan
                        ->nomor_penerimaan
                        ?? (
                            'PB #'
                            . $penerimaan->id
                        );


                $key =
                    'PENERIMAAN_BARANG:'
                    . $penerimaan->id;


                /*
                | Hanya isi kalau belum ada.
                | Kalau sudah ada dari item, jangan ditimpa.
                */

                if (!isset($referensiMap[$key])) {

                    $referensiMap[$key] = [

                        'label' =>
                            'SUPPLIER',

                        'nama' =>
                            $supplierName,

                        'nomor' =>
                            $nomorPenerimaan,

                    ];

                }

            }

        }


        /*
        |--------------------------------------------------------------------------
        | RETUR PEMBELIAN
        |--------------------------------------------------------------------------
        */

        $returPembelianIds = $mutasiStoks
            ->getCollection()
            ->filter(function ($mutasi) {

                return strtoupper(
                    trim(
                        (string) $mutasi->reference_type
                    )
                ) === 'RETUR_PEMBELIAN'
                &&
                !empty(
                    $mutasi->reference_id
                );

            })
            ->pluck('reference_id')
            ->map(
                fn ($id) => (int) $id
            )
            ->unique()
            ->values();


        if ($returPembelianIds->isNotEmpty()) {

            $returPembelians =
                ReturPembelian::with([
                    'supplier',
                ])
                    ->whereIn(
                        'id',
                        $returPembelianIds
                    )
                    ->get();


            foreach (
                $returPembelians
                as $retur
            ) {

                $supplierName =
                    $retur
                        ->supplier
                        ?->nama_supplier
                        ?? 'Supplier';


                $nomorRetur =
                    $retur
                        ->nomor_retur
                        ?? (
                            'RET-PB #'
                            . $retur->id
                        );


                $referensiMap[
                    'RETUR_PEMBELIAN:'
                    . $retur->id
                ] = [

                    'label' =>
                        'SUPPLIER',

                    'nama' =>
                        $supplierName,

                    'nomor' =>
                        $nomorRetur,

                ];

            }

        }


        /*
        |--------------------------------------------------------------------------
        | INVOICE PENJUALAN
        |--------------------------------------------------------------------------
        |
        | Untuk data penjualan yang reference_id-nya langsung
        | menunjuk ke InvoicePenjualan.
        |
        */

        $invoicePenjualanIds = $mutasiStoks
            ->getCollection()
            ->filter(function ($mutasi) {

                $type = strtoupper(
                    trim(
                        (string) $mutasi->reference_type
                    )
                );

                return in_array(
                    $type,
                    [
                        'INVOICE_PENJUALAN',
                        'PENJUALAN',
                    ],
                    true
                )
                &&
                !empty(
                    $mutasi->reference_id
                );

            })
            ->pluck('reference_id')
            ->map(
                fn ($id) => (int) $id
            )
            ->unique()
            ->values();


        if ($invoicePenjualanIds->isNotEmpty()) {

            $invoicePenjualans =
                InvoicePenjualan::with([
                    'customer',
                ])
                    ->whereIn(
                        'id',
                        $invoicePenjualanIds
                    )
                    ->get();


            foreach (
                $invoicePenjualans
                as $invoice
            ) {

                $customerName =
                    $invoice
                        ->customer
                        ?->nama_customer
                        ?? 'Customer';


                $nomorInvoice =
                    $invoice
                        ->nomor_invoice
                        ?? (
                            'INV #'
                            . $invoice->id
                        );


                foreach (
                    [
                        'INVOICE_PENJUALAN',
                        'PENJUALAN',
                    ]
                    as $type
                ) {

                    $referensiMap[
                        $type
                        . ':'
                        . $invoice->id
                    ] = [

                        'label' =>
                            'CUSTOMER',

                        'nama' =>
                            $customerName,

                        'nomor' =>
                            $nomorInvoice,

                    ];

                }

            }

        }


        /*
        |--------------------------------------------------------------------------
        | RETUR PENJUALAN
        |--------------------------------------------------------------------------
        */

        $returPenjualanIds = $mutasiStoks
            ->getCollection()
            ->filter(function ($mutasi) {

                return strtoupper(
                    trim(
                        (string) $mutasi->reference_type
                    )
                ) === 'RETUR_PENJUALAN'
                &&
                !empty(
                    $mutasi->reference_id
                );

            })
            ->pluck('reference_id')
            ->map(
                fn ($id) => (int) $id
            )
            ->unique()
            ->values();


        if ($returPenjualanIds->isNotEmpty()) {

            $returPenjualans =
                ReturPenjualan::with([
                    'customer',
                ])
                    ->whereIn(
                        'id',
                        $returPenjualanIds
                    )
                    ->get();


            foreach (
                $returPenjualans
                as $retur
            ) {

                $customerName =
                    $retur
                        ->customer
                        ?->nama_customer
                        ?? 'Customer';


                $nomorRetur =
                    $retur
                        ->nomor_retur
                        ?? (
                            'RET-PJ #'
                            . $retur->id
                        );


                $referensiMap[
                    'RETUR_PENJUALAN:'
                    . $retur->id
                ] = [

                    'label' =>
                        'CUSTOMER',

                    'nama' =>
                        $customerName,

                    'nomor' =>
                        $nomorRetur,

                ];

            }

        }


        /*
        |--------------------------------------------------------------------------
        | RETURN VIEW
        |--------------------------------------------------------------------------
        */

        return view(
            'kartu-stok.index',
            compact(
                'mutasiStoks',
                'barangs',
                'gudangs',
                'referensiMap'
            )
        );
    }
}