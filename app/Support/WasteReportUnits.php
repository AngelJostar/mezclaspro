<?php

namespace App\Support;

class WasteReportUnits
{
    public static function sum(iterable $rows): array
    {
        $totals = ['quantities' => [], 'missing' => 0];
        foreach ($rows as $quantities) {
            foreach ($quantities as $unit => $amount) {
                if ($amount === null) {
                    $totals['missing']++;
                } else {
                    $totals['quantities'][$unit] = ($totals['quantities'][$unit] ?? 0) + (float) $amount;
                }
            }
        }

        return $totals;
    }

    public static function formatQuantities(array $quantities): string
    {
        $parts = [];
        foreach (['mg', 'mL', 'frascos', 'mezclas'] as $unit) {
            if (!array_key_exists($unit, $quantities)) continue;
            $amount = $quantities[$unit];
            if ($amount === null) {
                $parts[] = 'Sin dato ('.$unit.')';
            } else {
                $label = (float) $amount === 1.0 ? match ($unit) {
                    'frascos' => 'frasco', 'mezclas' => 'mezcla', default => $unit,
                } : $unit;
                $parts[] = number_format($amount, 2).' '.$label;
            }
        }

        return implode(' · ', $parts) ?: '0.00 unidades';
    }

    public static function formatTotal(array $total): string
    {
        return self::formatQuantities($total['quantities'])
            . ($total['missing'] > 0 ? ' · Sin cuantificar: '.$total['missing'] : '');
    }
}
