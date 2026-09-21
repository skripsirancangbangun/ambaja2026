<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | INDEX
    |--------------------------------------------------------------------------
    */

    public function index(Request $request)
    {
        $query = Customer::query();

        /*
        |--------------------------------------------------------------------------
        | Search
        |--------------------------------------------------------------------------
        */

        if ($request->filled('search')) {
            $search = trim($request->search);

            $query->where(function ($q) use ($search) {
                $q->where(
                    'nama_customer',
                    'like',
                    "%{$search}%"
                )
                    ->orWhere(
                        'kode_customer',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'telepon',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'contact_person',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhere(
                        'kota',
                        'like',
                        "%{$search}%"
                    );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Urut Berdasarkan Nomor Customer
        |--------------------------------------------------------------------------
        |
        | CUST-0001
        | CUST-0002
        | CUST-0003
        | ...
        |
        */

        $customers = $query
            ->orderByRaw(
                "CAST(
                    SUBSTRING_INDEX(kode_customer, '-', -1)
                    AS UNSIGNED
                ) ASC"
            )
            ->orderBy('id')
            ->paginate(10)
            ->withQueryString();

        return view(
            'customer.index',
            compact('customers')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | CREATE
    |--------------------------------------------------------------------------
    */

    public function create()
    {
        /*
        |--------------------------------------------------------------------------
        | Generate Kode Customer Berikutnya
        |--------------------------------------------------------------------------
        */

        $defaultKode =
            $this->generateKodeCustomer();

        return view(
            'customer.create',
            compact('defaultKode')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | STORE
    |--------------------------------------------------------------------------
    */

    public function store(Request $request)
    {
        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        $validated = $request->validate([
            /*
            | kode_customer dari form tidak dijadikan
            | sumber nomor utama.
            |
            | Sistem akan generate ulang saat simpan.
            */

            'kode_customer' =>
                'nullable|string|max:50',

            'nama_customer' =>
                'required|string|max:255',

            'npwp' =>
                'nullable|string|max:30',

            'contact_person' =>
                'nullable|string|max:255',

            'telepon' =>
                'nullable|string|max:50',

            'email' =>
                'nullable|email|max:255',

            'alamat' =>
                'nullable|string',

            'kota' =>
                'nullable|string|max:255',

            'limit_piutang' =>
                'nullable|numeric|min:0',

            'is_active' =>
                'required|boolean',
        ]);

        DB::beginTransaction();

        try {
            /*
            |--------------------------------------------------------------------------
            | Generate Kode Saat Benar-benar Disimpan
            |--------------------------------------------------------------------------
            |
            | Jangan hanya percaya nomor dari halaman create.
            |
            | Misalnya:
            | User A buka form → CUST-4137
            | User B buka form → CUST-4137
            |
            | Saat simpan, sistem generate ulang.
            |
            */

            $validated['kode_customer'] =
                $this->generateKodeCustomer(
                    true
                );

            /*
            |--------------------------------------------------------------------------
            | Default Limit Piutang
            |--------------------------------------------------------------------------
            */

            $validated['limit_piutang'] =
                $validated['limit_piutang']
                ?? 0;

            /*
            |--------------------------------------------------------------------------
            | Create
            |--------------------------------------------------------------------------
            */

            $customer =
                Customer::create(
                    $validated
                );

            DB::commit();

            return redirect()
                ->route(
                    'customer.index'
                )
                ->with(
                    'success',
                    'Customer '
                    . $customer->kode_customer
                    . ' berhasil ditambahkan.'
                );

        } catch (\Throwable $e) {
            DB::rollBack();

            report($e);

            return back()
                ->withInput()
                ->with(
                    'error',
                    'Gagal menambahkan customer: '
                    . $e->getMessage()
                );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | EDIT
    |--------------------------------------------------------------------------
    */

    public function edit(Customer $customer)
    {
        return view(
            'customer.edit',
            compact('customer')
        );
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE
    |--------------------------------------------------------------------------
    */

    public function update(
        Request $request,
        Customer $customer
    ) {
        $validated = $request->validate([
            'kode_customer' => [
                'required',
                'string',
                'max:50',

                Rule::unique(
                    'customers',
                    'kode_customer'
                )->ignore(
                    $customer->id
                ),
            ],

            'nama_customer' =>
                'required|string|max:255',

            'npwp' =>
                'nullable|string|max:30',

            'contact_person' =>
                'nullable|string|max:255',

            'telepon' =>
                'nullable|string|max:50',

            'email' =>
                'nullable|email|max:255',

            'alamat' =>
                'nullable|string',

            'kota' =>
                'nullable|string|max:255',

            'limit_piutang' =>
                'nullable|numeric|min:0',

            'is_active' =>
                'required|boolean',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Default Limit Piutang
        |--------------------------------------------------------------------------
        */

        $validated['limit_piutang'] =
            $validated['limit_piutang']
            ?? 0;

        /*
        |--------------------------------------------------------------------------
        | Update
        |--------------------------------------------------------------------------
        */

        $customer->update(
            $validated
        );

        return redirect()
            ->route(
                'customer.index'
            )
            ->with(
                'success',
                'Data Customer berhasil diperbarui.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | DESTROY
    |--------------------------------------------------------------------------
    */

    public function destroy(Customer $customer)
    {
        /*
        |--------------------------------------------------------------------------
        | Cek Invoice / Piutang Aktif
        |--------------------------------------------------------------------------
        |
        | Customer tidak boleh dihapus jika masih mempunyai:
        |
        | - invoice AKTIF
        | - sisa piutang > 0
        |
        | Invoice yang sudah DIBATALKAN tidak dianggap
        | sebagai piutang aktif.
        |
        */

        $masihPunyaPiutang =
            $customer
                ->invoicePenjualans()
                ->where(
                    'status_invoice',
                    'AKTIF'
                )
                ->where(
                    'sisa_piutang',
                    '>',
                    0
                )
                ->exists();

        if ($masihPunyaPiutang) {
            return redirect()
                ->route(
                    'customer.index'
                )
                ->with(
                    'error',
                    'Customer tidak dapat dihapus karena masih memiliki invoice/piutang aktif.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Safety Transaksi
        |--------------------------------------------------------------------------
        |
        | Kalau customer pernah dipakai dalam invoice,
        | lebih aman dinonaktifkan daripada dihapus.
        |
        */

        if (
            $customer
                ->invoicePenjualans()
                ->exists()
        ) {
            $customer->update([
                'is_active' =>
                    false,
            ]);

            return redirect()
                ->route(
                    'customer.index'
                )
                ->with(
                    'success',
                    'Customer tidak dihapus karena sudah memiliki riwayat transaksi. Status customer telah diubah menjadi Nonaktif.'
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Delete
        |--------------------------------------------------------------------------
        */

        $customer->delete();

        return redirect()
            ->route(
                'customer.index'
            )
            ->with(
                'success',
                'Customer berhasil dihapus.'
            );
    }

    /*
    |--------------------------------------------------------------------------
    | GENERATE KODE CUSTOMER
    |--------------------------------------------------------------------------
    */

    private function generateKodeCustomer(
        bool $lock = false
    ): string {
        /*
        |--------------------------------------------------------------------------
        | Ambil Customer Dengan Nomor Terbesar
        |--------------------------------------------------------------------------
        |
        | Jangan menggunakan ID database.
        |
        | Contoh:
        |
        | ID 9000 tetapi kode CUST-4136
        |
        | Maka berikutnya tetap:
        |
        | CUST-4137
        |
        */

        $query = Customer::query()
            ->where(
                'kode_customer',
                'like',
                'CUST-%'
            )
            ->orderByRaw(
                "CAST(
                    SUBSTRING_INDEX(kode_customer, '-', -1)
                    AS UNSIGNED
                ) DESC"
            );

        /*
        |--------------------------------------------------------------------------
        | Lock Saat Store
        |--------------------------------------------------------------------------
        */

        if ($lock) {
            $query->lockForUpdate();
        }

        $lastCustomer =
            $query->first();

        /*
        |--------------------------------------------------------------------------
        | Ambil Angka Terakhir
        |--------------------------------------------------------------------------
        */

        $lastNumber = 0;

        if (
            $lastCustomer
            && $lastCustomer->kode_customer
        ) {
            /*
            | CUST-4136 → 4136
            */

            $parts =
                explode(
                    '-',
                    $lastCustomer
                        ->kode_customer
                );

            $lastNumber =
                (int) end(
                    $parts
                );
        }

        /*
        |--------------------------------------------------------------------------
        | Nomor Berikutnya
        |--------------------------------------------------------------------------
        */

        $nextNumber =
            $lastNumber + 1;

        /*
        |--------------------------------------------------------------------------
        | Format
        |--------------------------------------------------------------------------
        |
        | 1    → CUST-0001
        | 10   → CUST-0010
        | 999  → CUST-0999
        | 4137 → CUST-4137
        |
        | Jika lebih dari 9999:
        | 10000 → CUST-10000
        |
        */

        return
            'CUST-'
            . str_pad(
                $nextNumber,
                4,
                '0',
                STR_PAD_LEFT
            );
    }
}