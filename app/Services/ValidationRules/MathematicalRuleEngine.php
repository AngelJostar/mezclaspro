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

        $value = match ($configuration['formula_template'] ?? null) {
            'direct_value' => (float) $source,
            'amount_per_weight', 'amount_per_weight_per_day' => $this->divide((float) $source, data_get($facts, 'patient.weight_kg'), 'peso'),
            'percentage_of_volume' => $this->divide((float) $source * 100, data_get($facts, 'mixture.total_volume_ml'), 'volumen total'),
            'amount_per_1000_ml' => $this->divide((float) $source * 1000, data_get($facts, 'mixture.total_volume_ml'), 'volumen total'),
            'dose_times_weight_percentage_of_volume' => $this->dosePercentage((float) $source, $facts),
            default => throw new InvalidArgumentException('Plantilla matemática no permitida.'),
        };

        if (!is_float($value)) {
            return $value;
        }

        $operator = $configuration['operator'] ?? null;
        $passed = match ($operator) {
            '<' => $value < (float) $configuration['threshold_value'],
            '<=' => $value <= (float) $configuration['threshold_value'],
            '>' => $value > (float) $configuration['threshold_value'],
            '>=' => $value >= (float) $configuration['threshold_value'],
            '==' => abs($value - (float) $configuration['threshold_value']) < 0.000001,
            'between' => $value >= (float) $configuration['threshold_min'] && $value <= (float) $configuration['threshold_max'],
            default => throw new InvalidArgumentException('Operador matemático no permitido.'),
        };

        return [
            'passed' => $passed,
            'incomplete' => false,
            'value' => round($value, 6),
            'unit' => $configuration['unit'] ?? null,
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
