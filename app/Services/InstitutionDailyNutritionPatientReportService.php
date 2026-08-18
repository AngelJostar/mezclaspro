<?php

namespace App\Services;

use App\Models\Nutricionales\Solicitud as NutricionalSolicitud;
use Carbon\Carbon;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class InstitutionDailyNutritionPatientReportService
{
    public function __construct(private InstitutionBillingPricingService $pricing) {}

    public function requestsForHospital(
        int $hospitalId,
        ?CarbonInterface $from = null,
        ?CarbonInterface $to = null
    ): Collection
    {
        [$rangeStart, $rangeEnd] = $this->dateRange($from, $to);

        return NutricionalSolicitud::query()
            ->with([
                'billing',
                'solicitud_detail',
                'solicitud_patient',
                'input.input.nutritionMedicineCatalog',
                'input.presentation',
                'user.hospital',
            ])
            ->whereIn('estado', ['entregada', 'finalizada'])
            ->whereHas('user', fn ($query) => $query->where('hospital_id', $hospitalId))
            ->whereHas('solicitud_detail', function ($query) use ($rangeStart, $rangeEnd) {
                $query->whereBetween('fecha_hora_entrega', [$rangeStart, $rangeEnd]);
            })
            ->get();
    }

    public function build(
        Collection $requests,
        ?CarbonInterface $from = null,
        ?CarbonInterface $to = null
    ): array
    {
        [$rangeStart, $rangeEnd] = $this->dateRange($from, $to);

        $details = $requests
            ->filter(fn (NutricionalSolicitud $request) => $this->isDelivered($request))
            ->reject(fn (NutricionalSolicitud $request) => $this->isNotConciliable($request))
            ->map(function (NutricionalSolicitud $request) use ($rangeStart, $rangeEnd) {
                $deliveryDate = $request->solicitud_detail?->fecha_hora_entrega;
                if (!$deliveryDate) {
                    return null;
                }

                try {
                    $deliveredAt = Carbon::parse($deliveryDate);
                } catch (\Throwable) {
                    return null;
                }

                if ($deliveredAt->lt($rangeStart) || $deliveredAt->gt($rangeEnd)) {
                    return null;
                }

                $patient = trim(implode(' ', array_filter([
                    trim((string) ($request->solicitud_patient?->nombre_paciente ?? '')),
                    trim((string) ($request->solicitud_patient?->apellidos_paciente ?? '')),
                ])));
                $lot = trim((string) ($request->lote ?? ''));

                if ($lot === '') {
                    $lot = trim((string) ($request->input?->first(
                        fn ($item) => trim((string) ($item->lote ?? '')) !== ''
                    )?->lote ?? ''));
                }

                $birthDate = $request->solicitud_patient?->fecha_nacimiento;
                try {
                    $formattedBirthDate = $birthDate ? Carbon::parse($birthDate)->format('d-m-Y') : '-';
                } catch (\Throwable) {
                    $formattedBirthDate = (string) $birthDate;
                }

                $pricing = $this->pricing->priceNutritionRequest($request);

                return [
                    'date_key' => $deliveredAt->format('Y-m-d'),
                    'date' => $deliveredAt->format('d-m-Y'),
                    'timestamp' => $deliveredAt->timestamp,
                    'id' => (int) $request->id,
                    'lot' => $lot !== '' ? $lot : '-',
                    'patient' => $patient !== '' ? $patient : '-',
                    'doctor' => trim((string) ($request->solicitud_detail?->nombre_medico ?? '')) ?: '-',
                    'birth_date' => $formattedBirthDate,
                    'cost' => round((float) ($pricing['total_iva_included'] ?? 0), 2),
                ];
            })
            ->filter()
            ->sortBy([
                ['timestamp', 'asc'],
                ['id', 'asc'],
            ])
            ->values();

        if ($details->isEmpty()) {
            return [
                'rows' => [[null, null, 'Sin nutriciones conciliables entregadas', null, null, null]],
                'summary_rows' => [2],
                'detail_count' => 0,
            ];
        }

        $rows = [];
        $summaryRows = [];

        foreach ($details->groupBy('date_key') as $dailyDetails) {
            $count = $dailyDetails->count();
            $dailyTotal = round((float) $dailyDetails->sum('cost'), 2);
            $summaryRows[] = count($rows) + 2;
            $rows[] = [
                $dailyDetails->first()['date'],
                null,
                $count === 1 ? '1 nutricion entregada' : $count . ' nutriciones entregadas',
                null,
                null,
                $dailyTotal,
            ];

            foreach ($dailyDetails as $detail) {
                $rows[] = [
                    $detail['date'],
                    $detail['lot'],
                    $detail['patient'],
                    $detail['doctor'],
                    $detail['birth_date'],
                    $detail['cost'],
                ];
            }
        }

        return [
            'rows' => $rows,
            'summary_rows' => $summaryRows,
            'detail_count' => $details->count(),
        ];
    }

    private function dateRange(?CarbonInterface $from, ?CarbonInterface $to): array
    {
        $rangeStart = $from
            ? Carbon::instance($from)->startOfDay()
            : now()->startOfMonth()->startOfDay();
        $rangeEnd = $to
            ? Carbon::instance($to)->endOfDay()
            : now()->endOfMonth()->endOfDay();

        return [$rangeStart, $rangeEnd];
    }

    private function isDelivered(NutricionalSolicitud $request): bool
    {
        return in_array(mb_strtolower(trim((string) $request->estado)), ['entregada', 'finalizada'], true);
    }

    private function isNotConciliable(NutricionalSolicitud $request): bool
    {
        $value = mb_strtolower(trim((string) ($request->billing?->conciliable ?? '')));

        return in_array($value, ['no', 'no conciliable'], true);
    }
}
