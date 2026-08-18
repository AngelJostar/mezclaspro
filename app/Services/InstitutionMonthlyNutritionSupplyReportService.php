<?php

namespace App\Services;

use App\Models\Nutricionales\Solicitud as NutricionalSolicitud;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class InstitutionMonthlyNutritionSupplyReportService
{
    public function requestsForHospital(int $hospitalId, CarbonInterface $month): Collection
    {
        return NutricionalSolicitud::query()
            ->with([
                'solicitud_detail',
                'input.input',
            ])
            ->whereIn('estado', ['entregada', 'finalizada'])
            ->whereHas('user', fn ($query) => $query->where('hospital_id', $hospitalId))
            ->whereHas('solicitud_detail', function ($query) use ($month) {
                $query->whereBetween('fecha_hora_entrega', [
                    $month->copy()->startOfMonth()->startOfDay(),
                    $month->copy()->endOfMonth()->endOfDay(),
                ]);
            })
            ->get();
    }

    public function build(Collection $requests): array
    {
        $supplies = collect();

        foreach ($requests as $request) {
            if (!$this->isDelivered($request)) {
                continue;
            }

            foreach ($request->input ?? collect() as $requestInput) {
                $input = $requestInput->input;
                if (!$input) {
                    continue;
                }

                $description = trim((string) $input->description);
                $categoryId = (int) ($input->category_id ?? 0);

                if ($description === '' || $this->isMixingService($description, $categoryId)) {
                    continue;
                }

                // EVA bags are packaging and are not part of the requested supplies summary.
                if ($categoryId === 6 || str_contains($this->normalize($description), 'bolsa eva')) {
                    continue;
                }

                if ($this->isPieceSupply($description, $categoryId)) {
                    $quantity = max((float) ($requestInput->valor ?? 0), 1.0);
                    $unit = 'PZ';
                } else {
                    $quantity = $requestInput->valor_sobrellenado !== null
                        ? (float) $requestInput->valor_sobrellenado
                        : (float) ($requestInput->valor_ml ?? 0);
                    $unit = 'ML';
                }

                if ($quantity <= 0) {
                    continue;
                }

                $key = $this->normalize($description) . '|' . $unit;
                $current = $supplies->get($key, [
                    'description' => $description,
                    'unit' => $unit,
                    'quantity' => 0.0,
                ]);
                $current['quantity'] += $quantity;
                $supplies->put($key, $current);
            }

            $serviceKey = 'servicio de mezclado de npt|PZ';
            $service = $supplies->get($serviceKey, [
                'description' => 'Servicio de mezclado de NPT',
                'unit' => 'PZ',
                'quantity' => 0.0,
            ]);
            $service['quantity'] += 1;
            $supplies->put($serviceKey, $service);
        }

        $rows = $supplies
            ->sortBy(fn (array $supply) => $this->normalize($supply['description']), SORT_NATURAL)
            ->map(fn (array $supply) => [
                $supply['description'],
                $supply['unit'],
                round((float) $supply['quantity'], 2),
            ])
            ->values()
            ->all();

        return $rows ?: [['Sin insumos registrados para este mes', '-', 0]];
    }

    private function isDelivered(NutricionalSolicitud $request): bool
    {
        return in_array($this->normalize((string) $request->estado), ['entregada', 'finalizada'], true);
    }

    private function isMixingService(string $description, int $categoryId): bool
    {
        $normalized = $this->normalize($description);

        return $categoryId === 11
            || str_contains($normalized, 'preparacion para npt')
            || str_contains($normalized, 'servicio de mezclado');
    }

    private function isPieceSupply(string $description, int $categoryId): bool
    {
        return $categoryId === 10 || str_contains($this->normalize($description), 'set de infusion');
    }

    private function normalize(string $value): string
    {
        return Str::lower(trim(Str::ascii($value)));
    }
}
