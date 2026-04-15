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

    public array $purchaseRequests;
    public PurchaseRequest $first;

    public function __construct(array $purchaseRequests)
    {
        $this->purchaseRequests = $purchaseRequests;
        $this->first = $purchaseRequests[0];
    }

    public function envelope(): Envelope
    {
        $count = count($this->purchaseRequests);
        $subject = $count === 1
            ? "Nova requisição: {$this->first->product_name}"
            : "Nova requisição com {$count} itens — {$this->first->requester_name}";

        return new Envelope(subject: $subject);
    }

    public function content(): Content
    {
        return new Content(view: 'emails.purchase-request-created');
    }

    public function attachments(): array
    {
        return [];
    }
}
