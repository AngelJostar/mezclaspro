<?php

namespace App\Support;

class PediatricNutritionalCompositionCatalogV2
{
    public static function compositions(): array
    {
        return collect(AdultNutritionalCompositionCatalogV2::compositions())
            ->map(function (array $composition): array {
                $composition['code'] = str_replace('AD2-', 'PED2-', $composition['code']);
                $composition['label'] = preg_replace(
                    '/^Aminoácidos/',
                    'Aminoácidos pediátricos 10%',
                    $composition['label']
                );
                $composition['component_terms'] = ['aa_pediatric_10'];

                return $composition;
            })
            ->all();
    }
}
