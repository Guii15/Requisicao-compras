<?php

namespace App\Mail;

use App\Models\PurchaseRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class PurchaseRequestCreated extends Mailable
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
            subject: 'Nova requisição de compra',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.purchase-request-created',
        );
    }

    public function attachments(): array
    {
        return [];
    }
}