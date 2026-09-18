<?php

namespace App\Services;

class NutritionTheoreticalWeightService
{
    public function calculate(iterable $items): array
    {
        $weight = 0.0;
        $hasComponents = false;
        $missingDensity = [];

        foreach ($items as $item) {
            $description = trim((string) ($item?->input?->description ?? ''));
            $normalizedDescription = mb_strtolower($description);

            if (
                str_contains($normalizedDescription, 'servicio de mezclado')
                || str_contains($normalizedDescription, 'preparación para npt')
                || str_contains($normalizedDescription, 'preparacion para npt')
            ) {
                continue;
            }

            $volume = $item?->valor_sobrellenado !== null
                ? (float) $item->valor_sobrellenado
                : (float) ($item?->valor_ml ?? 0);

            if ($volume <= 0) {
                continue;
            }

            $hasComponents = true;
            $catalog = $item?->presentation?->catalog
                ?? $item?->input?->nutritionMedicineCatalog;
            $density = $catalog?->densidad;

            if (! is_numeric($density) || (float) $density <= 0) {
                $missingDensity[] = $catalog?->denominacion_generica ?: ($description ?: 'Componente sin nombre');
                continue;
            }

            $weight += $volume * (float) $density;
        }

        $missingDensity = array_values(array_unique($missingDensity));

        return [
            'value' => $hasComponents && $missingDensity === [] ? round($weight, 2) : null,
            'missing' => $missingDensity,
        ];
    }
}
