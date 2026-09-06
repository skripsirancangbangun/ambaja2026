<?php

namespace App\Mail;

use App\Models\InvoicePenjualan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Queue\SerializesModels;

class InvoicePenjualanMail extends Mailable
{
    use Queueable, SerializesModels;

    public InvoicePenjualan $invoice;

    public function __construct(InvoicePenjualan $invoice)
    {
        $this->invoice = $invoice;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                config('mail.from.address', 'ezicraft88@gmail.com'),
                config('mail.from.name', 'CV Andika Mustika')
            ),
            subject: 'Bukti Invoice Penjualan #' . $this->invoice->nomor_invoice . ' - CV Andika Mustika',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.invoice-penjualan',
            with: [
                'invoice' => $this->invoice,
            ],
        );
    }

    public function attachments(): array
    {
        $attachments = [];

        if (class_exists(Pdf::class)) {
            $this->invoice->loadMissing(['customer', 'gudang', 'creator', 'items.barang.satuan', 'rekeningKasBank']);
            $pdf = Pdf::loadView('invoice-penjualan.pdf', ['invoicePenjualan' => $this->invoice]);
            $safeNumber = str_replace(['/', '\\'], '-', $this->invoice->nomor_invoice);

            $attachments[] = Attachment::fromData(
                fn () => $pdf->output(),
                "Invoice-{$safeNumber}.pdf"
            )->withMime('application/pdf');
        }

        return $attachments;
    }
}
