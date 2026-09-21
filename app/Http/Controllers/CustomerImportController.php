<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\KartuPiutang;
use App\Services\SystemAccountingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerImportController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | DOWNLOAD TEMPLATE
    |--------------------------------------------------------------------------
    */

    public function downloadTemplate(): StreamedResponse
    {
        $spreadsheet = new Spreadsheet();

        $sheet =
            $spreadsheet->getActiveSheet();

        $sheet->setTitle(
            'Template Import Customer'
        );

        /*
        |--------------------------------------------------------------------------
        | Header
        |--------------------------------------------------------------------------
        */

        $headers = [
            'Kode Customer',
            'Nama Customer',
            'Telepon',
            'Email',
            'Alamat',
            'Saldo Awal Piutang (Rp)',
        ];

        $col = 'A';

        foreach ($headers as $header) {
            $sheet->setCellValue(
                $col . '1',
                $header
            );

            $col++;
        }

        /*
        |--------------------------------------------------------------------------
        | Style Header
        |--------------------------------------------------------------------------
        */

        $sheet
            ->getStyle('A1:F1')
            ->getFont()
            ->setBold(true);

        $sheet
            ->getStyle('A1:F1')
            ->getAlignment()
            ->setHorizontal(
                Alignment::HORIZONTAL_CENTER
            );

        /*
        |--------------------------------------------------------------------------
        | Format Telepon Sebagai Text
        |--------------------------------------------------------------------------
        |
        | Supaya nomor seperti:
        |
        | 08123456789
        |
        | tidak kehilangan angka 0 di depan.
        |
        */

        $sheet
            ->getStyle('C:C')
            ->getNumberFormat()
            ->setFormatCode(
                NumberFormat::FORMAT_TEXT
            );

        /*
        |--------------------------------------------------------------------------
        | Contoh Data
        |--------------------------------------------------------------------------
        |
        | Kode customer boleh dikosongkan.
        |
        | Untuk customer BARU, sistem akan otomatis membuat:
        | CUST-0001, CUST-0002, dst.
        |
        | Jika ingin UPDATE customer yang sudah ada,
        | isi kode customer lama.
        |
        */

        $sheet->setCellValue(
            'A2',
            ''
        );

        $sheet->setCellValue(
            'B2',
            'PT Andika Jaya'
        );

        $sheet->setCellValueExplicit(
            'C2',
            '08123456789',
            \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING
        );

        $sheet->setCellValue(
            'D2',
            'andikajaya@example.com'
        );

        $sheet->setCellValue(
            'E2',
            'Jl. Merdeka No. 45, Surabaya'
        );

        $sheet->setCellValue(
            'F2',
            1500000
        );

        /*
        |--------------------------------------------------------------------------
        | Catatan
        |--------------------------------------------------------------------------
        */

        $sheet->setCellValue(
            'H1',
            'CATATAN'
        );

        $sheet->setCellValue(
            'H2',
            'Kode Customer boleh dikosongkan untuk customer baru.'
        );

        $sheet->setCellValue(
            'H3',
            'Customer baru akan otomatis mendapat kode berurutan CUST-0001, CUST-0002, dst.'
        );

        $sheet->setCellValue(
            'H4',
            'Isi Kode Customer hanya jika ingin memperbarui customer yang sudah ada.'
        );

        $sheet->getColumnDimension('H')
            ->setWidth(70);

        /*
        |--------------------------------------------------------------------------
        | Auto Size
        |--------------------------------------------------------------------------
        */

        foreach (
            range('A', 'F')
            as $column
        ) {
            $sheet
                ->getColumnDimension(
                    $column
                )
                ->setAutoSize(true);
        }

        /*
        |--------------------------------------------------------------------------
        | Download
        |--------------------------------------------------------------------------
        */

        return response()
            ->streamDownload(
                function () use ($spreadsheet) {
                    $writer =
                        new Xlsx(
                            $spreadsheet
                        );

                    $writer->save(
                        'php://output'
                    );

                    $spreadsheet
                        ->disconnectWorksheets();
                },
                'template-import-customer-ambaja.xlsx',
                [
                    'Content-Type' =>
                        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                ]
            );
    }

    /*
    |--------------------------------------------------------------------------
    | IMPORT CUSTOMER
    |--------------------------------------------------------------------------
    */

    public function import(
        Request $request,
        SystemAccountingService $accountingService
    ) {
        /*
        |--------------------------------------------------------------------------
        | Validation
        |--------------------------------------------------------------------------
        */

        $request->validate([
            'file' =>
                'required|file|mimes:xlsx,xls,csv|max:5120',
        ]);

        /*
        |--------------------------------------------------------------------------
        | Read File
        |--------------------------------------------------------------------------
        */

        try {
            $file =
                $request->file(
                    'file'
                );

            $spreadsheet =
                IOFactory::load(
                    $file->getRealPath()
                );

            $rows =
                $spreadsheet
                    ->getActiveSheet()
                    ->toArray(
                        null,
                        true,
                        true,
                        false
                    );

        } catch (\Throwable $e) {
            report($e);

            return back()->with(
                'error',
                'File gagal dibaca: '
                    . $e->getMessage()
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Mode Saldo Awal
        |--------------------------------------------------------------------------
        */

        $canEditOpeningBalance =
            $accountingService
                ->canEditOpeningBalance();

        /*
        |--------------------------------------------------------------------------
        | Counter
        |--------------------------------------------------------------------------
        */

        $imported = 0;
        $updated = 0;
        $skipped = 0;
        $saldoAwalCreated = 0;
        $saldoAwalSkipped = 0;

        /*
        |--------------------------------------------------------------------------
        | Transaction
        |--------------------------------------------------------------------------
        */

        DB::beginTransaction();

        try {
            /*
            |--------------------------------------------------------------------------
            | Ambil Nomor Customer Terbesar
            |--------------------------------------------------------------------------
            |
            | Misal database sekarang:
            |
            | CUST-0001
            | ...
            | CUST-4136
            |
            | Maka nomor berikutnya = 4137.
            |
            */

            $lastCustomer =
                Customer::query()
                    ->where(
                        'kode_customer',
                        'like',
                        'CUST-%'
                    )
                    ->orderByRaw(
                        "CAST(
                            SUBSTRING_INDEX(
                                kode_customer,
                                '-',
                                -1
                            )
                            AS UNSIGNED
                        ) DESC"
                    )
                    ->lockForUpdate()
                    ->first();

            $lastNumber = 0;

            if (
                $lastCustomer
                && $lastCustomer
                    ->kode_customer
            ) {
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
            | Loop Excel
            |--------------------------------------------------------------------------
            */

            foreach (
                $rows
                as $index => $row
            ) {
                /*
                |--------------------------------------------------------------------------
                | Skip Header
                |--------------------------------------------------------------------------
                */

                if ($index === 0) {
                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Ambil Data
                |--------------------------------------------------------------------------
                */

                $kodeInput =
                    strtoupper(
                        trim(
                            (string) (
                                $row[0]
                                ?? ''
                            )
                        )
                    );

                $nama =
                    trim(
                        (string) (
                            $row[1]
                            ?? ''
                        )
                    );

                $telepon =
                    trim(
                        (string) (
                            $row[2]
                            ?? ''
                        )
                    );

                $email =
                    strtolower(
                        trim(
                            (string) (
                                $row[3]
                                ?? ''
                            )
                        )
                    );

                $alamat =
                    trim(
                        (string) (
                            $row[4]
                            ?? ''
                        )
                    );

                /*
                |--------------------------------------------------------------------------
                | Saldo Awal
                |--------------------------------------------------------------------------
                */

                $saldoRaw =
                    $row[5]
                    ?? 0;

                /*
                | Jika Excel memakai format seperti:
                |
                | 1.500.000
                | Rp 1.500.000
                |
                */

                if (
                    is_string(
                        $saldoRaw
                    )
                ) {
                    $saldoRaw =
                        preg_replace(
                            '/[^0-9,\.\-]/',
                            '',
                            $saldoRaw
                        );

                    /*
                    | Format Indonesia:
                    | 1.500.000
                    */

                    if (
                        substr_count(
                            $saldoRaw,
                            '.'
                        ) > 1
                    ) {
                        $saldoRaw =
                            str_replace(
                                '.',
                                '',
                                $saldoRaw
                            );
                    }
                }

                $saldoAwal =
                    (float) $saldoRaw;

                /*
                |--------------------------------------------------------------------------
                | Skip Baris Kosong
                |--------------------------------------------------------------------------
                */

                if ($nama === '') {
                    $skipped++;

                    continue;
                }

                /*
                |--------------------------------------------------------------------------
                | Validasi Email
                |--------------------------------------------------------------------------
                */

                if (
                    $email !== ''
                    && !filter_var(
                        $email,
                        FILTER_VALIDATE_EMAIL
                    )
                ) {
                    throw new \RuntimeException(
                        'Email tidak valid pada baris '
                        . ($index + 1)
                        . ': '
                        . $email
                    );
                }

                /*
                |--------------------------------------------------------------------------
                | Cek Customer Existing Berdasarkan Kode
                |--------------------------------------------------------------------------
                */

                $customer = null;

                if (
                    $kodeInput !== ''
                ) {
                    $customer =
                        Customer::query()
                            ->where(
                                'kode_customer',
                                $kodeInput
                            )
                            ->first();
                }

                /*
                |--------------------------------------------------------------------------
                | Fallback Cari Email
                |--------------------------------------------------------------------------
                |
                | Jika kode kosong, tetapi email yang sama sudah ada,
                | customer tersebut di-update.
                |
                */

                if (
                    !$customer
                    && $email !== ''
                ) {
                    $customer =
                        Customer::query()
                            ->where(
                                'email',
                                $email
                            )
                            ->first();
                }

                /*
                |--------------------------------------------------------------------------
                | Fallback Cari Telepon
                |--------------------------------------------------------------------------
                */

                if (
                    !$customer
                    && $telepon !== ''
                ) {
                    $customer =
                        Customer::query()
                            ->where(
                                'telepon',
                                $telepon
                            )
                            ->first();
                }

                /*
                |--------------------------------------------------------------------------
                | Existing Customer → Update
                |--------------------------------------------------------------------------
                */

                if ($customer) {
                    $customer->update([
                        'nama_customer' =>
                            $nama,

                        'telepon' =>
                            $telepon !== ''
                                ? $telepon
                                : null,

                        'email' =>
                            $email !== ''
                                ? $email
                                : null,

                        'alamat' =>
                            $alamat !== ''
                                ? $alamat
                                : null,

                        'is_active' =>
                            true,
                    ]);

                    $updated++;

                } else {
                    /*
                    |--------------------------------------------------------------------------
                    | Customer Baru → Generate Kode Otomatis
                    |--------------------------------------------------------------------------
                    |
                    | PENTING:
                    |
                    | Jika file berisi kode seperti:
                    |
                    | CUST-9999
                    |
                    | tetapi kode tersebut BELUM ADA di database,
                    | sistem TIDAK menggunakan CUST-9999.
                    |
                    | Sistem tetap melanjutkan nomor urut.
                    |
                    */

                    do {
                        $lastNumber++;

                        $kodeBaru =
                            'CUST-'
                            . str_pad(
                                $lastNumber,
                                4,
                                '0',
                                STR_PAD_LEFT
                            );

                        $kodeExists =
                            Customer::query()
                                ->where(
                                    'kode_customer',
                                    $kodeBaru
                                )
                                ->exists();

                    } while (
                        $kodeExists
                    );

                    /*
                    |--------------------------------------------------------------------------
                    | Create Customer
                    |--------------------------------------------------------------------------
                    */

                    $customer =
                        Customer::create([
                            'kode_customer' =>
                                $kodeBaru,

                            'nama_customer' =>
                                $nama,

                            'telepon' =>
                                $telepon !== ''
                                    ? $telepon
                                    : null,

                            'email' =>
                                $email !== ''
                                    ? $email
                                    : null,

                            'alamat' =>
                                $alamat !== ''
                                    ? $alamat
                                    : null,

                            'limit_piutang' =>
                                0,

                            'is_active' =>
                                true,
                        ]);

                    $imported++;
                }

                /*
                |--------------------------------------------------------------------------
                | Saldo Awal Piutang
                |--------------------------------------------------------------------------
                */

                if (
                    $saldoAwal > 0
                ) {
                    /*
                    |--------------------------------------------------------------------------
                    | Saldo Awal Hanya Saat Migration Mode
                    |--------------------------------------------------------------------------
                    */

                    if (
                        !$canEditOpeningBalance
                    ) {
                        $saldoAwalSkipped++;

                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Cegah Saldo Awal Double
                    |--------------------------------------------------------------------------
                    |
                    | Import ulang file yang sama tidak boleh menghasilkan:
                    |
                    | SALDO AWAL 1.500.000
                    | SALDO AWAL 1.500.000
                    |
                    | sehingga piutang menjadi 3.000.000.
                    |
                    */

                    $nomorReferensi =
                        'SALDO-AWAL-'
                        . $customer
                            ->kode_customer;

                    $saldoSudahAda =
                        KartuPiutang::query()
                            ->where(
                                'customer_id',
                                $customer->id
                            )
                            ->where(
                                'nomor_referensi',
                                $nomorReferensi
                            )
                            ->where(
                                'jenis_transaksi',
                                'SALDO_AWAL'
                            )
                            ->exists();

                    if (
                        $saldoSudahAda
                    ) {
                        $saldoAwalSkipped++;

                        continue;
                    }

                    /*
                    |--------------------------------------------------------------------------
                    | Catat Saldo Awal
                    |--------------------------------------------------------------------------
                    */

                    $accountingService
                        ->recordKartuPiutang(
                            customerId:
                                (int) $customer->id,

                            tanggal:
                                now()
                                    ->startOfYear(),

                            nomorRef:
                                $nomorReferensi,

                            jenis:
                                'SALDO_AWAL',

                            debit:
                                $saldoAwal,

                            kredit:
                                0,

                            keterangan:
                                'Saldo Awal Piutang Customer (Migrasi Sistem)'
                        );

                    $saldoAwalCreated++;
                }
            }

            /*
            |--------------------------------------------------------------------------
            | Commit
            |--------------------------------------------------------------------------
            */

            DB::commit();

            /*
            |--------------------------------------------------------------------------
            | Bersihkan Spreadsheet
            |--------------------------------------------------------------------------
            */

            $spreadsheet
                ->disconnectWorksheets();

            /*
            |--------------------------------------------------------------------------
            | Message
            |--------------------------------------------------------------------------
            */

            $message =
                "Import selesai. "
                . "Customer baru: {$imported}, "
                . "Customer diperbarui: {$updated}";

            if ($skipped > 0) {
                $message .=
                    ", Baris dilewati: {$skipped}";
            }

            if (
                $canEditOpeningBalance
            ) {
                $message .=
                    ", Saldo awal dibuat: {$saldoAwalCreated}";

                if (
                    $saldoAwalSkipped > 0
                ) {
                    $message .=
                        ", Saldo awal dilewati: {$saldoAwalSkipped}";
                }
            } elseif (
                $saldoAwalSkipped > 0
            ) {
                $message .=
                    ", Saldo awal tidak diimpor karena sistem sudah memiliki transaksi";
            }

            return redirect()
                ->back()
                ->with(
                    'success',
                    $message . '.'
                );

        } catch (\Throwable $e) {
            /*
            |--------------------------------------------------------------------------
            | Rollback
            |--------------------------------------------------------------------------
            */

            DB::rollBack();

            report($e);

            return back()
                ->with(
                    'error',
                    'Import Customer gagal: '
                    . $e->getMessage()
                );
        }
    }
}