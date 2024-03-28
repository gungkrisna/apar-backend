<?php

namespace App\Mail;

use App\Models\InvoiceItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class InvoiceItemExpiryNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $invoiceItem;

    /**
     * Create a new message instance.
     */
    public function __construct(InvoiceItem $invoiceItem)
    {
        $this->invoiceItem = $invoiceItem;
    }


    public function build()
    {
        $subject = 'Alat Proteksi Kebakaran Anda Mendekati Masa Kedaluwarsa - ' . env('APP_NAME');

        return $this->markdown('emails.invoice_item_expiry_notification')
            ->subject($subject)
            ->with(['invoiceItem' => $this->invoiceItem]);
    }
}
