<?php

namespace App\Mail;

use App\Models\RequestQuotation;
use App\Support\QuotationMessage;
use Illuminate\Mail\Mailable;

class RequestQuotationMail extends Mailable
{
    public string $folio;
    public string $contentText;

    public function __construct(RequestQuotation $quotation, string $note = '')
    {
        $this->folio = $quotation->folio;
        $this->contentText = ($note !== '' ? trim($note)."\n\n" : '').QuotationMessage::summary($quotation);
    }

    public function build(): static
    {
        return $this->subject('Cotizacion '.$this->folio.' | PROMESA')
            ->view('mail.request-quotation')
            ->text('mail.request-quotation-text');
    }
}
