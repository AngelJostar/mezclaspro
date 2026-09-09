<?php

namespace App\Notifications;

use App\Models\ExternalMixtureRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class MixtureIntegrationStatusChanged extends Notification
{
    use Queueable;

    public function __construct(private readonly ExternalMixtureRequest $request)
    {
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $status = $this->request->status;
        $hasError = filled($this->request->last_error)
            || in_array($status, ['materialization_failed', 'rejected', 'cancelled'], true);
        $folio = data_get($this->request->payload, 'external_id') ?: 'Solicitud externa #'.$this->request->id;

        return [
            'title' => $hasError ? 'Integración requiere atención' : 'Actualización de mezcla',
            'message' => $this->message(),
            'severity' => $hasError ? 'error' : ($status === 'delivered' ? 'success' : 'info'),
            'source' => 'dr_sam',
            'folio' => $folio,
            'external_mixture_request_id' => $this->request->id,
            'status' => $status,
            'url' => $this->request->catalog_type === 'oncology'
                ? route('admin.oncologicos.solicitudes.index')
                : route('admin.nutricionales.solicitudes.index'),
        ];
    }

    private function message(): string
    {
        $folio = data_get($this->request->payload, 'external_id') ?: 'Solicitud externa #'.$this->request->id;
        $detail = $this->request->last_error ?: data_get($this->request->status_details, 'reason');
        if (filled($detail)) {
            return $folio.': '.mb_substr((string) $detail, 0, 500);
        }

        $status = match ($this->request->status) {
            'received' => 'recibida desde Dr. Sam',
            'materialized', 'pending' => 'creada y pendiente de operación',
            'authorized' => 'autorizada para preparación',
            'dispensed' => 'dispensada',
            'preparing' => 'en preparación',
            'ready' => 'lista para entrega',
            'delivered' => 'entregada y conciliada',
            'rejected' => 'rechazada',
            'cancelled' => 'cancelada',
            default => 'actualizada',
        };

        return $folio.' fue '.$status.'.';
    }
}
