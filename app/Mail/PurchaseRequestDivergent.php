<?php

namespace App\Mail;

use App\Models\PurchaseRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PurchaseRequestDivergent extends Mailable
{
    use Queueable, SerializesModels;

    public PurchaseRequest $purchaseRequest;

    public function __construct(PurchaseRequest $purchaseRequest)
    {
        $this->purchaseRequest = $purchaseRequest;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "Divergência na Conferência: {$this->purchaseRequest->product_name} — {$this->purchaseRequest->requester_name}"
        );
    }

    public function content(): Content
    {
        return new Content(view: 'emails.purchase-request-divergent');
    }

    public function attachments(): array
    {
        return [];
    }
}
