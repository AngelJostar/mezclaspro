<?php

namespace App\Mail;

use App\Models\RequestQuotation;
use App\Services\RequestQuotationPdf;
use Illuminate\Mail\Mailable;

class RequestQuotationMail extends Mailable
{
    public string $folio;
    public string $contentText;

    public function __construct(RequestQuotation $quotation, string $note = '')
    {
        $this->folio = $quotation->folio;
        $this->contentText = ($note !== '' ? trim($note)."\n\n" : '').'Se adjunta la cotizacion '.$this->folio.' en formato PDF.';
        $this->attachData(app(RequestQuotationPdf::class)->render($quotation), RequestQuotationPdf::filename($quotation), ['mime' => 'application/pdf']);
    }

    public function build(): static
    {
        return $this->subject('Cotizacion '.$this->folio.' | PROMESA')
            ->view('mail.request-quotation')
            ->text('mail.request-quotation-text');
    }
}
