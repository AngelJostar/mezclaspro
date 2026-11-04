<?php

namespace App\Services\Integrations\PurchaseOrders;

use App\Models\Oncologicos\LaboratoryPurchaseOrder;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class PurchaseOrderSyncService
{
    public function sync(LaboratoryPurchaseOrder $order): array
    {
        $order->forceFill([
            'oc_sync_attempts' => $order->oc_sync_attempts + 1,
            'oc_last_sync_attempt_at' => now(),
        ])->save();

        $url = trim((string) config('services.purchase_orders.api_url'));
        $token = trim((string) config('services.purchase_orders.token'));
        if ($url === '' || $token === '') {
            return $this->pending($order, 'La integracion con Ordenes de Compra no esta configurada.');
        }

        try {
            $response = Http::acceptJson()
                ->withToken($token)
                ->connectTimeout((int) config('services.purchase_orders.connect_timeout', 3))
                ->timeout((int) config('services.purchase_orders.timeout', 10))
                ->post($url, $this->payload($order));
        } catch (ConnectionException $exception) {
            report($exception);
            return $this->pending($order, 'No fue posible comunicarse con Ordenes de Compra.');
        }

        if ($response->successful()) {
            $remote = $response->json('purchase_order', []);
            $order->forceFill([
                'oc_sync_status' => 'synced',
                'oc_sync_error' => null,
                'oc_synced_at' => now(),
                'oc_remote_id' => $remote['id'] ?? null,
                'oc_remote_folio' => $remote['folio'] ?? null,
            ])->save();

            return ['status' => 'synced', 'message' => $response->json('message'), 'folio' => $remote['folio'] ?? null];
        }

        $message = (string) ($response->json('message') ?: 'Orden rechazada por el sistema de Ordenes de Compra.');
        if ($response->status() === 422) {
            $order->forceFill(['oc_sync_status' => 'rejected', 'oc_sync_error' => $message])->save();
            return ['status' => 'rejected', 'message' => $message];
        }

        return $this->pending($order, $message);
    }

    private function payload(LaboratoryPurchaseOrder $order): array
    {
        return [
            'external_id' => (string) $order->id,
            'external_folio' => $order->folio,
            'company_rfc' => $order->invoice_rfc,
            'provider_rfc' => $order->supplier_rfc,
            'warehouse' => $order->warehouse?->name,
            'created_on' => $order->requested_at?->toDateString(),
            'delivery_date' => ($order->proposed_delivery_at ?: $order->requested_at)?->toDateString(),
            'due_date' => $order->due_date?->toDateString(),
            'is_credit' => (bool) $order->is_credit,
            'credit_days' => $order->is_credit ? $order->credit_days : null,
            'reference' => $order->quotation_number ?: $order->folio,
            'payment_concept' => $order->order_type,
            'observations' => $order->notes,
            'items' => collect($order->items)->map(fn (array $item) => [
                'article' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
            ])->values()->all(),
        ];
    }

    private function pending(LaboratoryPurchaseOrder $order, string $message): array
    {
        $order->forceFill(['oc_sync_status' => 'pending', 'oc_sync_error' => $message])->save();
        return ['status' => 'pending', 'message' => $message];
    }
}
