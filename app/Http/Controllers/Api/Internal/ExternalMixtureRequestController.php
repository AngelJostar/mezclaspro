<?php

namespace App\Http\Controllers\Api\Internal;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Admin\Nutricionales\SolicitudController as NptSolicitudController;
use App\Http\Controllers\Admin\Oncologicos\SolicitudController as OncologySolicitudController;
use App\Http\Requests\Api\Internal\StoreExternalMixtureRequest;
use App\Models\ExternalMixtureRequest;
use App\Models\Nutricionales\Solicitud;
use App\Models\Oncologicos\SolicitudOnco;
use App\Services\Integrations\DrSam\ExternalMixtureRequestService;
use App\Services\Integrations\DrSam\ExternalMixtureMaterializer;
use App\Services\Integrations\DrSam\ExternalMixtureStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExternalMixtureRequestController extends Controller
{
    public function store(StoreExternalMixtureRequest $request, ExternalMixtureRequestService $service, ExternalMixtureMaterializer $materializer, ExternalMixtureStatusService $statuses): JsonResponse
    {
        [$record, $created] = $service->store($request->validated());
        if (in_array($record->status, ['received', 'materialization_failed'], true)) {
            $materializer->materialize($record);
        }

        $record = $statuses->refresh($record->fresh());

        return response()->json(['data' => $this->resource($record)], $created ? 201 : 200);
    }

    public function show(Request $request, string $remoteRequestId, ExternalMixtureStatusService $statuses): JsonResponse
    {
        abort_unless($request->user()?->tokenCan('requests:read'), 403, 'El token no tiene permiso para consultar solicitudes.');
        $record = ExternalMixtureRequest::query()->where('remote_request_id', $remoteRequestId)->firstOrFail();

        return response()->json(['data' => $this->resource($statuses->refresh($record))]);
    }

    public function remission(Request $request, string $remoteRequestId, ExternalMixtureStatusService $statuses): Response
    {
        abort_unless($request->user()?->tokenCan('requests:read'), 403, 'El token no tiene permiso para consultar remisiones.');
        $record = ExternalMixtureRequest::query()->where('remote_request_id', $remoteRequestId)->firstOrFail();
        $record = $statuses->refresh($record);

        abort_unless($record->materialized_id, 404);
        abort_unless(data_get($record->status_details, 'remission.available') === true, 404);

        return match ($record->materialized_type) {
            'npt' => app(NptSolicitudController::class)->remision(
                Solicitud::query()->findOrFail($record->materialized_id)
            ),
            'oncology' => app(OncologySolicitudController::class)->remision(
                SolicitudOnco::query()->findOrFail($record->materialized_id)
            ),
            default => abort(404),
        };
    }

    private function resource(ExternalMixtureRequest $record): array
    {
        return [
            'request_id' => $record->remote_request_id,
            'local_external_id' => $record->local_external_id,
            'status' => $record->status,
            'integration_stage' => $this->integrationStage($record),
            'status_message' => $this->statusMessage($record),
            'has_error' => in_array($record->status, ['materialization_failed', 'rejected', 'cancelled'], true),
            'status_details' => $record->status_details,
            'status_checked_at' => $record->status_checked_at?->toIso8601String(),
            'remission' => data_get($record->status_details, 'remission'),
            'documents' => $record->documents()->orderBy('id')->get()->map(fn ($document) => [
                'id' => $document->id,
                'type' => $document->type,
                'name' => $document->original_name,
                'mime_type' => $document->mime_type,
                'size' => $document->size,
                'sha256' => $document->sha256,
                'uploaded_at' => $document->uploaded_at?->toIso8601String(),
            ])->all(),
            'catalog_type' => $record->catalog_type,
            'catalog_version' => $record->catalog_version,
            'received_at' => $record->received_at?->toIso8601String(),
            'materialized_type' => $record->materialized_type,
            'materialized_id' => $record->materialized_id,
            'materialized_at' => $record->materialized_at?->toIso8601String(),
            'last_error' => $record->last_error,
        ];
    }

    private function integrationStage(ExternalMixtureRequest $record): string
    {
        return match ($record->status) {
            'received' => 'received',
            'materialization_failed', 'materialized', 'pending' => 'materialization',
            'authorized', 'preparing', 'ready' => 'operation',
            'delivered' => 'delivery',
            'rejected', 'cancelled' => 'closed',
            default => 'synchronization',
        };
    }

    private function statusMessage(ExternalMixtureRequest $record): string
    {
        if (filled($record->last_error)) {
            return mb_substr($record->last_error, 0, 500);
        }

        $reason = data_get($record->status_details, 'reason');
        if (filled($reason)) {
            return mb_substr((string) $reason, 0, 500);
        }

        return match ($record->status) {
            'received' => 'Solicitud recibida desde Dr. Sam; pendiente de materialización.',
            'materialized', 'pending' => 'Solicitud creada en Mezclas y pendiente de operación.',
            'authorized' => 'Solicitud autorizada para preparación.',
            'preparing' => 'La mezcla se encuentra en preparación.',
            'ready' => 'La mezcla está lista para entrega.',
            'delivered' => 'La mezcla fue entregada y conciliada.',
            'rejected' => 'La solicitud fue rechazada en Mezclas.',
            'cancelled' => 'La solicitud fue cancelada.',
            default => 'Estado de integración actualizado.',
        };
    }
}
