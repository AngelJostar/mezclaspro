<?php

namespace App\Services;

use App\Models\Hospital;
use App\Models\Institucion;
use App\Models\InstitutionReportTemplate;
use App\Models\Nutricionales\Solicitud as NutricionalSolicitud;
use App\Models\Oncologicos\Mezcla;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class InstitutionCustomReportDataService
{
    public function records(
        InstitutionReportTemplate $template,
        Institucion $institution,
        ?User $user,
        CarbonInterface $periodFrom,
        CarbonInterface $periodTo,
        array $repeatedParameterKeys
    ): Collection {
        $institution->loadMissing('hospitals');
        $base = $this->baseContext($institution, $user, $periodFrom, $periodTo);
        $keys = collect($repeatedParameterKeys);
        $usesRequests = in_array($template->data_source, ['solicitudes', 'facturacion'], true)
            || $keys->contains(fn (string $key) => $this->startsWithAny($key, ['request.', 'mixture.', 'billing.']));

        if ($usesRequests) {
            return $this->requestRecords($institution, $base, $periodFrom, $periodTo);
        }

        $usesHospitals = $template->data_source === 'instituciones'
            || $keys->contains(fn (string $key) => str_starts_with($key, 'hospital.'));

        if ($usesHospitals) {
            $records = $institution->hospitals
                ->values()
                ->map(fn (Hospital $hospital, int $index) => array_merge(
                    $base,
                    $this->hospitalContext($hospital),
                    ['__sort.sequence' => $index]
                ));

            return $records->isNotEmpty() ? $records : collect([$base]);
        }

        return collect([$base]);
    }

    public function baseContext(
        Institucion $institution,
        ?User $user,
        CarbonInterface $periodFrom,
        CarbonInterface $periodTo
    ): array {
        $institution->loadMissing('hospitals');
        $hospitals = $institution->hospitals;

        return [
            'institution.name' => $institution->nombre,
            'institution.legal_name' => $institution->razon_social,
            'institution.rfc' => $institution->rfc,
            'institution.phone' => $institution->telefono,
            'institution.hospitals_count' => (string) $hospitals->count(),
            'hospital.name' => $hospitals->pluck('name')->filter()->join(', '),
            'hospital.service' => $hospitals->flatMap(fn (Hospital $hospital) => $this->hospitalServices($hospital))
                ->unique()
                ->join(', '),
            'hospital.address' => $hospitals->pluck('adress')->filter()->unique()->join(', '),
            'hospital.city' => $hospitals->pluck('municipality')->filter()->unique()->join(', '),
            'hospital.state' => $hospitals->pluck('state')->filter()->unique()->join(', '),
            'system.generated_at' => now()->format('d/m/Y H:i'),
            'system.period_from' => $periodFrom->format('d/m/Y'),
            'system.period_to' => $periodTo->format('d/m/Y'),
            'system.user_name' => $this->userName($user),
            '__sort.sequence' => 0,
        ];
    }

    private function requestRecords(
        Institucion $institution,
        array $base,
        CarbonInterface $periodFrom,
        CarbonInterface $periodTo
    ): Collection {
        $hospitalIds = $institution->hospitals->pluck('id');
        if ($hospitalIds->isEmpty()) {
            return collect();
        }

        $onco = Mezcla::query()
            ->with([
                'billing',
                'solicitud.hospital',
                'medicamentos.medicamentoOnco.catalog',
                'medicamentos.presentacionesUsadas.batch.presentation',
                'medicamentos.presentacionesUsadas.batch.warehouse',
            ])
            ->whereHas('solicitud', fn ($query) => $query
                ->whereIn('hospital_id', $hospitalIds)
                ->whereBetween('created_at', [$periodFrom, $periodTo]))
            ->get()
            ->map(fn (Mezcla $mixture) => $this->oncologyRecord($mixture, $base));

        $nutrition = NutricionalSolicitud::query()
            ->with([
                'billing',
                'user.hospital',
                'solicitud_detail',
                'solicitud_patient',
                'input.input.nutritionMedicineCatalog',
                'input.presentation.catalog',
            ])
            ->whereHas('user', fn ($query) => $query->whereIn('hospital_id', $hospitalIds))
            ->whereBetween('created_at', [$periodFrom, $periodTo])
            ->get()
            ->map(fn (NutricionalSolicitud $request) => $this->nutritionRecord($request, $base));

        return $onco
            ->concat($nutrition)
            ->values()
            ->map(function (array $record, int $index) {
                $record['__sort.sequence'] = $index;

                return $record;
            });
    }

    private function oncologyRecord(Mezcla $mixture, array $base): array
    {
        $request = $mixture->solicitud;
        $hospital = $request?->hospital;
        $lines = $this->oncologyLines($mixture);
        $requestedAt = $request?->created_at;
        $deliveryAt = $mixture->fecha_entrega ?? $request?->fecha_entrega;
        $billingTotal = $mixture->billing?->precio_total ?? $lines->sum('subtotal');
        $presentations = $mixture->medicamentos
            ->flatMap(fn ($medicine) => $medicine->presentacionesUsadas->map(
                fn ($used) => $used->presentacion_snapshot ?: $used->batch?->presentation?->presentacion
            ))
            ->filter()
            ->unique()
            ->join(', ');
        $warehouses = $mixture->medicamentos
            ->flatMap(fn ($medicine) => $medicine->presentacionesUsadas->pluck('batch.warehouse.name'))
            ->filter()
            ->unique()
            ->join(', ');

        return array_merge(
            $base,
            $hospital ? $this->hospitalContext($hospital) : [],
            [
                'request.id' => 'ONC-'.$request?->id,
                'request.type' => $request?->tipo_solicitud === 'antibioticos' ? 'Antibiótica' : 'Oncológica',
                'request.requested_at' => $this->dateTime($requestedAt),
                'request.delivery_at' => $this->dateTime($deliveryAt),
                'request.status' => $this->status($mixture->operational_status),
                'request.patient' => trim((string) $request?->nombre_paciente),
                'request.physician' => trim((string) $request?->nombre_medico),
                'request.diagnosis' => trim((string) $request?->diagnostico),
                'request.record' => trim((string) $request?->registro_paciente),
                'mixture.remission' => trim((string) ($mixture->remision ?: $request?->remision)),
                'mixture.lot' => trim((string) $mixture->lote),
                'mixture.product' => $lines->pluck('description')->filter()->unique()->join(', '),
                'mixture.presentation' => $presentations,
                'mixture.quantity' => $lines->pluck('quantity')->filter(fn ($value) => $value !== null)->join(', '),
                'mixture.unit' => $lines->pluck('unit_label')->filter()->unique()->join(', '),
                'mixture.warehouse' => $warehouses,
                'billing.unit_price' => $this->currencyList($lines->pluck('unit_price')),
                'billing.total' => $this->currency($billingTotal),
                'billing.invoice' => trim((string) ($mixture->billing?->folio_interno ?: $mixture->billing?->folio_factura_uuid)),
                'billing.status' => $this->status($mixture->billing?->estatus_facturacion),
                '__sort.request.id' => (int) ($request?->id ?? 0),
                '__sort.request.requested_at' => $this->timestamp($requestedAt),
                '__sort.request.delivery_at' => $this->timestamp($deliveryAt),
                '__sort.billing.total' => (float) ($billingTotal ?? 0),
            ]
        );
    }

    private function nutritionRecord(NutricionalSolicitud $request, array $base): array
    {
        $hospital = $request->user?->hospital;
        $patient = $request->solicitud_patient;
        $detail = $request->solicitud_detail;
        $lines = $this->nutritionLines($request);
        $requestedAt = $request->created_at;
        $deliveryAt = $detail?->fecha_hora_entrega;
        $billingTotal = $request->billing?->precio_total ?? $lines->sum('subtotal');
        $presentations = $request->input
            ->pluck('presentation.presentacion')
            ->filter()
            ->unique()
            ->join(', ');
        $patientName = trim(implode(' ', array_filter([
            trim((string) $patient?->nombre_paciente),
            trim((string) $patient?->apellidos_paciente),
        ])));

        return array_merge(
            $base,
            $hospital ? $this->hospitalContext($hospital) : [],
            [
                'request.id' => 'NUT-'.$request->id,
                'request.type' => 'Nutricional',
                'request.requested_at' => $this->dateTime($requestedAt),
                'request.delivery_at' => $this->dateTime($deliveryAt),
                'request.status' => $this->status($request->estado),
                'request.patient' => $patientName,
                'request.physician' => trim((string) $detail?->nombre_medico),
                'request.diagnosis' => trim((string) $patient?->diagnostico),
                'request.record' => trim((string) $patient?->registro),
                'mixture.remission' => trim((string) $request->remision),
                'mixture.lot' => trim((string) ($request->lote ?: $request->input->pluck('lote')->filter()->first())),
                'mixture.product' => $lines->pluck('description')->filter()->unique()->join(', '),
                'mixture.presentation' => $presentations,
                'mixture.quantity' => $lines->pluck('quantity')->filter(fn ($value) => $value !== null)->join(', '),
                'mixture.unit' => $lines->pluck('unit_label')->filter()->unique()->join(', '),
                'mixture.warehouse' => '',
                'billing.unit_price' => $this->currencyList($lines->pluck('unit_price')),
                'billing.total' => $this->currency($billingTotal),
                'billing.invoice' => trim((string) ($request->billing?->folio_interno ?: $request->billing?->folio_factura_uuid)),
                'billing.status' => $this->status($request->billing?->estatus_facturacion),
                '__sort.request.id' => (int) $request->id,
                '__sort.request.requested_at' => $this->timestamp($requestedAt),
                '__sort.request.delivery_at' => $this->timestamp($deliveryAt),
                '__sort.billing.total' => (float) ($billingTotal ?? 0),
            ]
        );
    }

    private function oncologyLines(Mezcla $mixture): Collection
    {
        return $mixture->medicamentos->map(function ($medicine) {
            $presentations = $medicine->presentacionesUsadas;
            $chargeBy = in_array($medicine->charge_by, ['mg', 'ml', 'frasco'], true)
                ? $medicine->charge_by
                : 'frasco';
            $quantity = match ($chargeBy) {
                'mg' => (float) ($medicine->dosis ?? 0),
                'ml' => (float) ($medicine->dosis_ml ?? $presentations->sum('volumen_usado_ml')),
                default => (float) $presentations->sum('unidades_usadas'),
            };
            $unitPrice = match ($chargeBy) {
                'mg' => (float) ($medicine->precio_mg_snapshot ?? 0),
                'ml' => (float) ($medicine->precio_ml_snapshot ?? 0),
                default => (float) ($presentations
                    ->pluck('precio_unitario_snapshot')
                    ->filter(fn ($value) => $value !== null && $value !== '')
                    ->first()
                    ?? $presentations->pluck('precio_frasco_snapshot')->filter()->first()
                    ?? 0),
            };
            $subtotal = (float) $presentations->sum('subtotal');

            if ($subtotal <= 0 && $quantity > 0 && $unitPrice > 0) {
                $subtotal = $quantity * $unitPrice;
            }

            return [
                'description' => trim((string) (
                    $medicine->denominacion_snapshot
                    ?: $medicine->nombre_medicamento
                    ?: $medicine->medicamentoOnco?->catalog?->denominacion_generica
                )),
                'quantity' => $quantity,
                'unit_label' => $chargeBy,
                'unit_price' => $unitPrice,
                'subtotal' => round($subtotal, 2),
            ];
        });
    }

    private function nutritionLines(NutricionalSolicitud $request): Collection
    {
        return $request->input->map(function ($item) {
            $quantity = (float) ($item->valor_sobrellenado ?? $item->valor_ml ?? $item->valor ?? 0);
            $unitPrice = (float) ($item->precio_ml ?? 0);

            return [
                'description' => trim((string) (
                    $item->input?->nutritionMedicineCatalog?->denominacion_generica
                    ?: $item->input?->description
                )),
                'quantity' => $quantity,
                'unit_label' => trim((string) ($item->input?->unidad ?: 'mL')),
                'unit_price' => $unitPrice,
                'subtotal' => round($quantity * $unitPrice, 2),
            ];
        });
    }

    private function hospitalContext(Hospital $hospital): array
    {
        return [
            'hospital.name' => $hospital->name,
            'hospital.service' => collect($this->hospitalServices($hospital))->join(', '),
            'hospital.address' => $hospital->adress,
            'hospital.city' => $hospital->municipality,
            'hospital.state' => $hospital->state,
        ];
    }

    private function hospitalServices(Hospital $hospital): array
    {
        return array_values(array_filter([
            $hospital->service_oncology ? 'Oncología' : null,
            $hospital->service_antibiotics ? 'Antibióticos' : null,
            $hospital->service_nutrition ? 'Nutrición' : null,
        ]));
    }

    private function userName(?User $user): string
    {
        if (! $user) {
            return 'Sistema';
        }

        $fullName = trim(implode(' ', array_filter([$user->name, $user->lastname])));

        return $fullName !== '' ? $fullName : (string) ($user->username ?: 'Usuario');
    }

    private function dateTime(mixed $value): string
    {
        if (! $value) {
            return '';
        }

        try {
            return Carbon::parse($value)->format('d/m/Y H:i');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    private function timestamp(mixed $value): int
    {
        if (! $value) {
            return 0;
        }

        try {
            return Carbon::parse($value)->timestamp;
        } catch (\Throwable) {
            return 0;
        }
    }

    private function status(mixed $value): string
    {
        $value = trim(str_replace(['_', '-'], ' ', (string) $value));

        return $value === '' ? '' : mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }

    private function currency(mixed $value): string
    {
        return $value === null || $value === '' ? '' : '$'.number_format((float) $value, 2);
    }

    private function currencyList(Collection $values): string
    {
        return $values
            ->filter(fn ($value) => $value !== null && $value !== '')
            ->map(fn ($value) => $this->currency($value))
            ->unique()
            ->join(', ');
    }

    private function startsWithAny(string $value, array $prefixes): bool
    {
        foreach ($prefixes as $prefix) {
            if (str_starts_with($value, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
