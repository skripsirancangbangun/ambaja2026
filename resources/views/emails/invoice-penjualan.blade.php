<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bukti Invoice Penjualan</title>
    <style>
        body {
            font-family: 'Segoe UI', Arial, sans-serif;
            background-color: #f8fafc;
            color: #334155;
            margin: 0;
            padding: 20px;
        }
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        }
        .header {
            background-color: #15235F;
            color: #ffffff;
            padding: 24px 32px;
            text-align: left;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            font-weight: 700;
        }
        .header p {
            margin: 4px 0 0 0;
            font-size: 12px;
            opacity: 0.85;
        }
        .content {
            padding: 32px;
        }
        .greeting {
            font-size: 15px;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 12px;
        }
        .text {
            font-size: 14px;
            line-height: 1.6;
            color: #475569;
            margin-bottom: 24px;
        }
        .summary-box {
            background-color: #f1f5f9;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            padding: 6px 0;
            border-bottom: 1px dashed #cbd5e1;
        }
        .summary-row:last-child {
            border-bottom: none;
            font-weight: 700;
            font-size: 15px;
            color: #15235F;
            padding-top: 12px;
        }
        .label {
            color: #64748b;
        }
        .value {
            color: #1e293b;
            text-align: right;
        }
        .badge-lunas {
            display: inline-block;
            background-color: #dcfce7;
            color: #15803d;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
        }
        .badge-kredit {
            display: inline-block;
            background-color: #fef3c7;
            color: #b45309;
            padding: 4px 12px;
            border-radius: 9999px;
            font-size: 12px;
            font-weight: 600;
        }
        .footer {
            background-color: #f8fafc;
            padding: 20px 32px;
            text-align: center;
            font-size: 12px;
            color: #94a3b8;
            border-top: 1px solid #e2e8f0;
        }
    </style>
</head>
<body>

<div class="container">
    <div class="header">
        <h1>CV ANDIKA MUSTIKA</h1>
        <p>Sistem Informasi Akuntansi & Penjualan Resmi</p>
    </div>

    <div class="content">
        <div class="greeting">
            Kepada Yth. {{ $invoice->customer->nama_customer ?? 'Pelanggan Setia' }},
        </div>

        <div class="text">
            Terima kasih atas kepercayaan Anda bertransaksi dengan <strong>CV Andika Mustika</strong>. Berikut kami sampaikan rincian bukti Invoice Penjualan resmi untuk transaksi Anda. Lampiran dokumen berupa file PDF lengkap telah disertakan pada email ini.
        </div>

        <div class="summary-box">
            <div class="summary-row">
                <span class="label">Nomor Invoice:</span>
                <span class="value"><strong>{{ $invoice->nomor_invoice }}</strong></span>
            </div>
            <div class="summary-row">
                <span class="label">Tanggal Invoice:</span>
                <span class="value">{{ \Carbon\Carbon::parse($invoice->tanggal_invoice)->translatedFormat('d F Y') }}</span>
            </div>
            <div class="summary-row">
                <span class="label">Tipe Pembayaran:</span>
                <span class="value">
                    @if($invoice->tipe_pembayaran === 'Tunai')
                        <span class="badge-lunas">Tunai (Lunas)</span>
                    @else
                        <span class="badge-kredit">Kredit / Tempo</span>
                    @endif
                </span>
            </div>
            @if($invoice->tanggal_jatuh_tempo && $invoice->tipe_pembayaran === 'Kredit')
            <div class="summary-row">
                <span class="label">Jatuh Tempo:</span>
                <span class="value">{{ \Carbon\Carbon::parse($invoice->tanggal_jatuh_tempo)->translatedFormat('d F Y') }}</span>
            </div>
            @endif
            <div class="summary-row">
                <span class="label">Total Pembayaran:</span>
                <span class="value">Rp {{ number_format($invoice->total, 0, ',', '.') }}</span>
            </div>
        </div>

        <div class="text">
            Apabila ada pertanyaan lebih lanjut mengenai tagihan ini, silakan hubungi tim kami di email <strong>ezicraft88@gmail.com</strong>.
        </div>
    </div>

    <div class="footer">
        © {{ date('Y') }} CV Andika Mustika — Email Resmi: ezicraft88@gmail.com<br>
        Email ini dikirimkan secara otomatis oleh Sistem Informasi Akuntansi AMBAJA.
    </div>
</div>

</body>
</html>
