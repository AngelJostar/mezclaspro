<?php

namespace App\Services\Clinical;

final class NutritionManual
{
    public const MODES = [
        'INF' => ['option' => 'PEDIÁTRICO', 'manual_type' => 'npt_pediatrico', 'label' => 'Nutrición Parenteral Pediátrico'],
        'ADULT' => ['option' => 'ADULTO', 'manual_type' => 'npt_adulto', 'label' => 'Nutrición Parenteral Adulto'],
    ];

    public static function selection(string $mode): ?array
    {
        $manual = self::MODES[$mode] ?? null;
        return $manual ? ['field' => 'npt', 'mode' => $mode, 'manual_type' => $manual['manual_type'], 'label' => $manual['label']] : null;
    }
}
