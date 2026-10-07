<?php

namespace App\Services;

use App\Models\HospitalConciliationSubmission;
use App\Support\ConciliationInboxTable;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ConciliationInboxData
{
    public function __construct(private InstitutionBillingPricingService $pricing) {}

    public function rows(array $requests, Collection $submissions): Collection
    {
        $latest = self::indexSubmissions($submissions);
        $rows = collect();
        foreach (['nutrition', 'onco'] as $type) {
            foreach ($requests[$type] as $record) {
                $isNutrition = $type === 'nutrition';
                $kind = $isNutrition ? 'nutricionales' : $record->solicitud->tipo_solicitud;
                if (!in_array($kind, ['nutricionales', 'oncologicos', 'antibioticos'], true)) continue;
                $hospitalId = (int) ($isNutrition ? $record->hospital_id : $record->solicitud->hospital_id);
                $submission = $latest[$hospitalId.':'.$kind.':'.$record->id] ?? null;
                $billing = $record->billing;
                if ($billing && (int) $billing->hospital_id !== $hospitalId) $billing = null;
                $previous = $billing?->conciliable;
                $price = $billing?->precio_total;
                if ($price === null) {
                    $pricing = $isNutrition ? $this->pricing->priceNutritionRequest($record) : $this->pricing->priceOncoMix($record);
                    $total = $pricing['total_iva_included'] ?? null;
                    $price = is_numeric($total) && $total > 0 ? number_format((float) $total, 2, '.', '') : '';
                }
                $patient = $isNutrition ? trim(($record->solicitud_patient?->nombre_paciente ?? '').' '.($record->solicitud_patient?->apellidos_paciente ?? '')) : $record->solicitud?->nombre_paciente;
                $date = $isNutrition ? $record->created_at : $record->solicitud?->created_at;
                $status = ConciliationInboxTable::status($previous, $submission);
                $bucket = $submission ? ($submission->direction === 'sent' ? 'enviadas' : 'recibidas') : ($status === 'Conciliado' ? 'conciliadas' : 'pendientes');
                $mixture = [
                    'kind' => $kind, 'id' => $record->id, 'hospital_id' => $hospitalId,
                    'cells' => ['patient' => $patient ?: 'Sin paciente', 'date' => $date?->format('Y-m-d H:i') ?? '—'],
                    'remision' => $record->remision ?: '—', 'can_edit_price' => true,
                    'current_price' => $price, 'previous_price' => $billing?->precio_total,
                    'previous_conciliable' => $previous,
                    'current_conciliable' => blank($previous) || in_array(Str::lower(Str::ascii(trim($previous))), ['si', 'yes', 'true', '1', 'conciliado', 'conciliable'], true),
                    'url' => route($isNutrition ? 'admin.nutricionales.solicitudes.show' : 'admin.oncologicos.mezclas.show', $record->id),
                    'conciliation_status' => $status,
                    'reference' => $submission?->folio() ?? 'Solicitud',
                    'conciliable_url' => route('admin.instituciones.conciliaciones.request-conciliable', [$kind, $record->id]),
                    'price_url' => route('admin.instituciones.conciliaciones.request-price', [$kind, $record->id]),
                ];
                $rows->push(compact('submission', 'mixture', 'bucket'));
            }
        }
        return $rows;
    }

    public static function indexSubmissions(Collection $submissions): array
    {
        $latest = [];
        foreach ($submissions->sortByDesc('id') as $submission) {
            foreach ($submission->snapshot ?? [] as $row) {
                $key = $submission->hospital_id.':'.$row['kind'].':'.$row['id'];
                $latest[$key] ??= $submission;
            }
        }
        return $latest;
    }

    public static function latestSubmission(int $hospitalId, string $kind, int $id): ?HospitalConciliationSubmission
    {
        return HospitalConciliationSubmission::where('hospital_id', $hospitalId)->latest('id')->get()
            ->first(fn ($submission) => collect($submission->snapshot)->contains(fn ($row) => $row['kind'] === $kind && (int) $row['id'] === $id));
    }
}
