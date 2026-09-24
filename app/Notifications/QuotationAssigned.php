<?php

namespace App\Notifications;

use App\Models\RequestQuotation;
use Illuminate\Notifications\Notification;

class QuotationAssigned extends Notification
{
    public function __construct(private RequestQuotation $quotation) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Cotizacion recibida',
            'message' => 'Se te asigno la cotizacion '.$this->quotation->folio.'.',
            'quotation_id' => $this->quotation->id,
            'url' => route('admin.solicitudes.cotizacion.index', [
                'estado' => 'recibidas', 'buscar' => $this->quotation->folio,
            ]),
        ];
    }
}
