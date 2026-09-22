<?php

namespace App\Support;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class NutritionAdditionOrder
{
    public static function sort(Collection $items): Collection
    {
        return $items->sortBy(function ($item) {
            $genericName = $item->presentation?->catalog?->denominacion_generica
                ?? $item->input?->nutritionMedicineCatalog?->denominacion_generica
                ?? $item->input?->description
                ?? '';

            return sprintf('%04d|%s|%010d', self::rank($genericName), self::normalize($genericName), (int) ($item->id ?? 0));
        })->values();
    }

    public static function rank(?string $name): int
    {
        $name = self::normalize($name);

        $rules = [
            10 => ['AMINOACIDOS ESENCIALES 5.4', 'AMINOACIDOS PARA NEFROPATAS 5.4'],
            20 => ['AMINOACIDOS CRISTALINOS 8%', 'AMINOACIDOS CRISTALINOS AL 8%'],
            30 => ['AMINOACIDOS CRISTALINOS 8.5%', 'AMINOACIDOS CRISTALINOS AL 8.5%'],
            40 => ['AMINOACIDOS CRISTALINOS 10%', 'AMINOACIDOS CRISTALINOS AL 10%'],
            50 => ['AMINOACIDOS PEDIATRICOS 10%'],
            60 => ['GLUTAMINA'],
            70 => ['DEXTROSA 50%', 'SOLUCION GLUCOSADA AL 50%'],
            75 => ['SOLUCION GLUCOSADA'],
            80 => ['AGUA ESTERIL', 'AGUA INYECTABLE'],
            90 => ['CLORURO DE SODIO 0.9%'],
            100 => ['ACETATO DE SODIO'],
            110 => ['ACETATO DE POTASIO'],
            120 => ['FOSFATO DE SODIO'],
            130 => ['FOSFATO DE POTASIO'],
            140 => ['CLORURO DE SODIO'],
            150 => ['CLORURO DE POTASIO'],
            160 => ['SULFATO DE MAGNESIO'],
            170 => ['GLUCONATO DE CALCIO'],
            180 => ['OLIGOELEMENTOS'],
            190 => ['OLIGOMETALES'],
            200 => ['CROMO'],
            210 => ['MANGANESO'],
            220 => ['SELENIO'],
            230 => ['ZINC'],
            240 => ['COBRE'],
            250 => ['MULTIVITAMINICO ADULTO', 'MULTIVITAMINAS ADULTO'],
            260 => ['MULTIVITAMINICO PEDIATRICO', 'MULTIVITAMINAS PEDIATRICO'],
            270 => ['VITAMINA C'],
            280 => ['VITAMINA K'],
            290 => ['L-CISTEINA'],
            300 => ['ACIDO FOLINICO'],
            310 => ['L-CARNITINA'],
            320 => ['INSULINA'],
            330 => ['HEPARINA'],
            340 => ['ALBUMINA 20%', 'ALBUMINA 0.2G/ML'],
            350 => ['ALBUMINA 25%'],
            360 => ['LIPIDOS MCT/LCT 10%', 'LIPIDOS DE CADENA MEDIA Y LARGA 10%', 'LIPOFUNDIN 10%'],
            370 => ['LIPIDOS MCT/LCT 20%', 'LIPIDOS DE CADENA MEDIA Y LARGA 20%'],
            380 => ['LIPIDOS LCT 10%'],
            390 => ['LIPIDOS LCT 20%'],
            400 => ['LIPIDOS SEPARADOS'],
            410 => ['LIPIDOS SMOF 20%', 'EMULSION LIPIDICA 20%'],
            420 => ['AC. GRASOS OMEGA 3', 'ACIDOS GRASOS OMEGA 3'],
        ];

        foreach ($rules as $rank => $needles) {
            foreach ($needles as $needle) {
                if (Str::contains($name, $needle)) {
                    return $rank;
                }
            }
        }

        return 900;
    }

    private static function normalize(?string $value): string
    {
        return Str::upper(Str::ascii(trim((string) $value)));
    }
}
