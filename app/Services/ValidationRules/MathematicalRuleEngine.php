<?php

namespace App\Services\ValidationRules;

use InvalidArgumentException;

class MathematicalRuleEngine
{
    /**
     * Evaluates only predefined templates. No expression or executable code is accepted.
     */
    public function evaluate(array $configuration, array $facts): array
    {
        $source = data_get($facts, $configuration['source_variable'] ?? '');
        if (!is_numeric($source)) {
            return $this->incomplete('No se encontró un valor numérico para el dato de origen.');
        }

        $formula = $configuration['formula_template'] ?? null;
        $value = match ($formula) {
            'direct_value' => (float) $source,
            'amount_per_weight', 'amount_per_weight_per_day' => $this->divide((float) $source, data_get($facts, 'patient.weight_kg'), 'peso'),
            'percentage_of_volume' => $this->divide((float) $source * 100, data_get($facts, 'mixture.total_volume_ml'), 'volumen total'),
            'amount_per_1000_ml' => $this->divide((float) $source * 1000, data_get($facts, 'mixture.total_volume_ml'), 'volumen total'),
            'volume_capacity_per_1000_ml' => (float) $source,
            'dose_times_weight_percentage_of_volume' => $this->dosePercentage((float) $source, $facts),
            default => throw new InvalidArgumentException('Plantilla matemática no permitida.'),
        };

        if (!is_float($value)) {
            return $value;
        }

        $operator = $configuration['operator'] ?? null;
        $comparisonValue = (float) ($configuration['threshold_value'] ?? 0);
        if ($formula === 'volume_capacity_per_1000_ml') {
            $volume = data_get($facts, 'mixture.total_volume_ml');
            if (!is_numeric($volume) || (float) $volume <= 0) {
                return $this->incomplete('Falta un volumen total válido para calcular la capacidad máxima.');
            }
            $comparisonValue = (float) $volume * $comparisonValue / 1000;
        }
        $passed = match ($operator) {
            '<' => $value < $comparisonValue,
            '<=' => $value <= $comparisonValue,
            '>' => $value > $comparisonValue,
            '>=' => $value >= $comparisonValue,
            '==' => abs($value - $comparisonValue) < 0.000001,
            'between' => $value >= (float) $configuration['threshold_min'] && $value <= (float) $configuration['threshold_max'],
            default => throw new InvalidArgumentException('Operador matemático no permitido.'),
        };

        return [
            'passed' => $passed,
            'incomplete' => false,
            'value' => round($value, 6),
            'unit' => $configuration['unit'] ?? null,
            'comparison_value' => round($comparisonValue, 6),
            'message' => $passed ? null : ($configuration['failure_message'] ?? 'El resultado no cumple la regla.'),
        ];
    }

    private function dosePercentage(float $dose, array $facts): float|array
    {
        $weight = data_get($facts, 'patient.weight_kg');
        if (!is_numeric($weight) || (float) $weight <= 0) {
            return $this->incomplete('Falta un peso válido para realizar el cálculo.');
        }

        return $this->divide($dose * (float) $weight * 100, data_get($facts, 'mixture.total_volume_ml'), 'volumen total');
    }

    private function divide(float $numerator, mixed $denominator, string $label): float|array
    {
        if (!is_numeric($denominator) || (float) $denominator <= 0) {
            return $this->incomplete("Falta un {$label} válido para realizar el cálculo.");
        }

        return $numerator / (float) $denominator;
    }

    private function incomplete(string $message): array
    {
        return ['passed' => false, 'incomplete' => true, 'value' => null, 'unit' => null, 'message' => $message];
    }
}
