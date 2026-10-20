<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Kwitansi Pembayaran - {{ $penerimaan->nomor_penerimaan }}</title>
    <style>
        @page { margin: 20px 25px; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; }
        .header { border-bottom: 2px solid #15235F; padding-bottom: 8px; margin-bottom: 12px; }
        .title { font-size: 15px; font-weight: bold; color: #15235F; text-align: right; }
        .company { font-size: 13px; font-weight: bold; color: #15235F; }
        .info-table { width: 100%; margin-bottom: 12px; }
        .info-table td { padding: 3px 0; vertical-align: top; }
        table.detail { width: 100%; border-collapse: collapse; margin-top: 8px; }
        table.detail th { background: #15235F; color: #ffffff; padding: 6px 5px; font-size: 9px; }
        table.detail td { padding: 6px 5px; border-bottom: 1px solid #cbd5e1; font-size: 9px; }
        .right { text-align: right; }
        .center { text-align: center; }
        .total-box { margin-top: 10px; padding: 8px 10px; border: 1px solid #15235F; background: #f8fafc; font-size: 11px; }
        .footer-sign { margin-top: 25px; width: 100%; text-align: center; }
        .badge-lunas { background: #dcfce7; color: #15803d; border: 1px solid #86efac; padding: 2px 5px; border-radius: 4px; font-weight: bold; font-size: 8px; display: inline-block; }
        .badge-sebagian { background: #fef3c7; color: #b45309; border: 1px solid #fcd34d; padding: 2px 5px; border-radius: 4px; font-weight: bold; font-size: 8px; display: inline-block; }
    </style>
</head>
<body>

    @php
        $profil = \App\Models\ProfilPerusahaan::getProfil();
    @endphp

    <div class="header">
        <table width="100%">
            <tr>
                <td width="55%">
                    <div class="company">{{ strtoupper($profil->nama_perusahaan) }}</div>
                    <div style="font-size: 9px; color: #64748b;">{{ $profil->alamat }} | Telp: {{ $profil->telepon }}</div>
                </td>
                <td width="45%" class="right">
                    <div class="title">KWITANSI BUKTI PEMBAYARAN</div>
                    <div style="font-size: 10px; color: #15235F;"><strong>No: {{ $penerimaan->nomor_penerimaan }}</strong></div>
                </td>
            </tr>
        </table>
    </div>

    <table class="info-table">
        <tr>
            <td width="18%"><strong>Telah Diterima Dari:</strong></td>
            <td width="82%"><strong>{{ $penerimaan->customer?->nama_customer }}</strong> ({{ $penerimaan->customer?->kode_customer }})</td>
        </tr>
        <tr>
            <td><strong>Tanggal Pembayaran:</strong></td>
            <td>{{ $penerimaan->tanggal ? $penerimaan->tanggal->format('d F Y') : '-' }}</td>
        </tr>
        <tr>
            <td><strong>Metode / Rekening:</strong></td>
            <td>{{ $penerimaan->rekeningKasBank?->nama_rekening }} ({{ $penerimaan->rekeningKasBank?->nomor_rekening }})</td>
        </tr>
        <tr>
            <td><strong>Catatan:</strong></td>
            <td>{{ $penerimaan->catatan ?: '-' }}</td>
        </tr>
    </table>

    <table class="detail">
        <thead>
            <tr>
                <th width="22%">No. Invoice</th>
                <th width="14%">Tanggal Faktur</th>
                <th width="18%" class="right">Total Faktur (Rp)</th>
                <th width="18%" class="right">Pembayaran Ini (Rp)</th>
                <th width="18%" class="right">Sisa Piutang (Rp)</th>
                <th width="10%" class="center">Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($penerimaan->details as $d)
                @php
                    $inv = $d->invoicePenjualan;
                    $sisaPiutang = $inv ? $inv->sisa_piutang : 0;
                    $totalInvoice = $inv ? $inv->total : 0;
                    $isLunas = $inv && ($inv->status_pembayaran === 'Lunas' || $sisaPiutang <= 0.01);
                @endphp
                <tr>
                    <td><strong>{{ $inv?->nomor_invoice }}</strong></td>
                    <td>{{ $inv?->tanggal_invoice ? $inv->tanggal_invoice->format('d/m/Y') : '-' }}</td>
                    <td class="right">Rp {{ number_format($totalInvoice, 0, ',', '.') }}</td>
                    <td class="right" style="font-weight: bold; color: #059669;">Rp {{ number_format($d->nominal_dibayar, 0, ',', '.') }}</td>
                    <td class="right" style="font-weight: bold; color: {{ $isLunas ? '#059669' : '#dc2626' }};">
                        Rp {{ number_format($sisaPiutang, 0, ',', '.') }}
                    </td>
                    <td class="center">
                        @if($isLunas)
                            <span class="badge-lunas">LUNAS</span>
                        @else
                            <span class="badge-sebagian">SEBAGIAN</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table width="100%" class="total-box">
        <tr>
            <td width="55%" style="text-align: left;">
                @php
                    $totalSisaStruktur = $penerimaan->details->sum(function($dt) {
                        return $dt->invoicePenjualan ? $dt->invoicePenjualan->sisa_piutang : 0;
                    });
                @endphp
                @if($totalSisaStruktur <= 0.01)
                    <span style="color: #059669; font-weight: bold;"><x-icon name="check" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" /> Seluruh Faktur Terkait Telah LUNAS</span>
                @else
                    <span style="color: #d97706; font-weight: bold;"><x-icon name="alert-triangle" class="inline-block h-[1em] w-[1em] align-[-0.125em] mr-1" /> Total Sisa Piutang Faktur Terkait: Rp {{ number_format($totalSisaStruktur, 0, ',', '.') }}</span>
                @endif
            </td>
            <td width="45%" class="right">
                TOTAL DITERIMA KAS/BANK: <span style="color: #059669; font-size: 13px; font-weight: bold;">Rp {{ number_format($penerimaan->total_dibayar, 0, ',', '.') }}</span>
            </td>
        </tr>
    </table>

    <table class="footer-sign">
        <tr>
            <td width="50%">
                Diterima Oleh,<br><br><br>
                <strong>( {{ $penerimaan->creator?->name ?? 'Kasir / Admin' }} )</strong>
            </td>
            <td width="50%">
                Disetujui Customer,<br><br><br>
                <strong>( {{ $penerimaan->customer?->nama_customer }} )</strong>
            </td>
        </tr>
    </table>

</body>
</html>
