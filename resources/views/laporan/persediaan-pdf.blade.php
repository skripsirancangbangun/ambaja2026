<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Laporan Posisi Persediaan</title>
    <style>
        @page { margin: 18px 20px; }
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 9px;
            color: #1f2937;
        }
        h1 {
            text-align: center;
            font-size: 15px;
            margin: 0 0 3px;
            color: #15235f;
        }
        .company, .subtitle { text-align: center; margin-bottom: 3px; }
        .company { font-size: 9px; font-weight: bold; }
        .subtitle { color: #64748b; font-size: 7px; }
        .filters { margin: 8px 0; padding: 5px; border: 1px solid #cbd5e1; }
        .summary { width: 100%; border-collapse: collapse; margin: 7px 0 9px; }
        .summary td { width: 25%; border: 1px solid #cbd5e1; padding: 5px; }
        .label { color: #64748b; font-size: 6px; text-transform: uppercase; }
        .value { font-size: 9px; font-weight: bold; margin-top: 3px; color: #15235f; }
        .data-lines {
            width: 100%;
            margin: 0;
            padding: 0;
            white-space: pre;
            font-family: DejaVu Sans Mono, monospace;
            font-size: 8.8px;
            line-height: 1.1;
        }
        .page-break { page-break-before: always; height: 0; }
        .empty { margin-top: 20px; text-align: center; font-weight: bold; }
        .footer { margin-top: 8px; text-align: right; color: #64748b; font-size: 6px; }
    </style>
</head>
<body>
    <h1>LAPORAN POSISI PERSEDIAAN</h1>
    <div class="company">CV Andika Mustika</div>
    <div class="subtitle">Dicetak pada {{ $printedAt->format('d/m/Y H:i') }}</div>

    <div class="filters">
        <strong>Filter:</strong>
        Pencarian: {{ $filterSearch !== '' ? $filterSearch : 'Semua barang' }} |
        Gudang: {{ $filterGudang ? ('ID ' . $filterGudang) : 'Semua gudang' }} |
        Status: {{ $filterStatus !== '' ? ucfirst($filterStatus) : 'Semua status' }}
    </div>

    <table class="summary">
        <tr>
            <td><div class="label">Jenis stok tersedia</div><div class="value">{{ number_format($totalJenisStok, 0, ',', '.') }}</div></td>
            <td><div class="label">Total kuantitas</div><div class="value">{{ number_format($totalQty, 2, ',', '.') }}</div></td>
            <td><div class="label">Nilai persediaan</div><div class="value">Rp {{ number_format($totalNilai, 0, ',', '.') }}</div></td>
            <td><div class="label">Stok habis</div><div class="value">{{ number_format($stokHabis, 0, ',', '.') }}</div></td>
        </tr>
    </table>

    @php
        $columnWidths = [12, 32, 28, 17, 17, 9, 10, 15, 17, 10];
        $lineWidth = array_sum($columnWidths) + (count($columnWidths) - 1) * 3;

        $fitText = static function ($value, int $width): string {
            $text = trim((string) ($value ?? '-'));
            $text = preg_replace('/[\r\n\t]+/u', ' ', $text) ?? $text;
            $text = preg_replace('/\s{2,}/u', ' ', $text) ?? $text;
            $text = $text !== '' ? $text : '-';

            if (mb_strwidth($text, 'UTF-8') > $width) {
                $text = mb_strimwidth($text, 0, $width - 1, '…', 'UTF-8');
            }

            return $text . str_repeat(' ', max(0, $width - mb_strwidth($text, 'UTF-8')));
        };

        $fitRight = static function ($value, int $width): string {
            $text = trim((string) $value);

            if (mb_strwidth($text, 'UTF-8') > $width) {
                $text = mb_strimwidth($text, 0, $width, '', 'UTF-8');
            }

            return str_repeat(' ', max(0, $width - mb_strwidth($text, 'UTF-8'))) . $text;
        };

        $makeLine = static function (array $columns) use ($fitText, $columnWidths): string {
            $formatted = [];
            foreach ($columns as $index => $value) {
                $formatted[] = $fitText($value, $columnWidths[$index]);
            }
            return implode(' | ', $formatted);
        };

        $headerLine = $makeLine([
            'KODE', 'NAMA BARANG', 'SPESIFIKASI', 'KATEGORI', 'GUDANG',
            'SATUAN', 'QTY', 'HARGA RATA2', 'NILAI STOK', 'STATUS',
        ]);

        $formatRow = static function ($stok) use ($fitText, $fitRight, $columnWidths): string {
            $qty = (float) $stok->qty;
            $minimumStok = (float) ($stok->barang?->minimum_stok ?? 0);

            if ($qty <= 0) {
                $status = 'Habis';
            } elseif ($minimumStok > 0 && $qty <= $minimumStok) {
                $status = 'Menipis';
            } else {
                $status = 'Tersedia';
            }

            $namaBarang = $stok->barang?->nama_barang ?? '-';
            if ($stok->barang?->merek) {
                $namaBarang .= ' / ' . $stok->barang->merek;
            }

            return implode(' | ', [
                $fitText($stok->barang?->kode_barang ?? '-', $columnWidths[0]),
                $fitText($namaBarang, $columnWidths[1]),
                $fitText($stok->barang?->spesifikasi ?: '-', $columnWidths[2]),
                $fitText($stok->barang?->kategori?->nama_kategori ?? '-', $columnWidths[3]),
                $fitText($stok->gudang?->nama_gudang ?? '-', $columnWidths[4]),
                $fitText($stok->barang?->satuan?->nama_satuan ?? '-', $columnWidths[5]),
                $fitRight(number_format($qty, 2, ',', '.'), $columnWidths[6]),
                $fitRight('Rp ' . number_format((float) $stok->harga_rata_rata, 0, ',', '.'), $columnWidths[7]),
                $fitRight('Rp ' . number_format((float) $stok->nilai_persediaan, 0, ',', '.'), $columnWidths[8]),
                $fitText($status, $columnWidths[9]),
            ]);
        };
    @endphp

    @if ($totalRows === 0)
        <div class="empty">Tidak ada data persediaan untuk filter ini.</div>
    @else
        @php $pageIndex = 0; @endphp
        @foreach ($stokBarangs->chunk(65) as $pageRows)
            @if ($pageIndex > 0)
                <div class="page-break"></div>
            @endif

            <pre class="data-lines">{{ $headerLine . "\n" . str_repeat('-', $lineWidth) . "\n" }}@foreach ($pageRows as $stok){{ $formatRow($stok) . "\n" }}@endforeach</pre>
            @php $pageIndex++; @endphp
        @endforeach
    @endif

    <div class="footer">Laporan ini menggunakan saldo persediaan yang tersimpan di sistem AMBAJA.</div>
</body>
</html>
