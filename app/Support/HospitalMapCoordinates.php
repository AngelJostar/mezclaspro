<?php

namespace App\Support;

use App\Models\Hospital;
use Illuminate\Support\Str;

final class HospitalMapCoordinates
{
    /**
     * Approximate locality centers used only while a hospital has no stored coordinates.
     * More specific matches must appear before broader matches.
     *
     * @var array<int, array{terms: array<int, string>, latitude: float, longitude: float}>
     */
    private const LOCATION_HINTS = [
        ['terms' => ['HOSPITAL SAN DIEGO', 'CUERNAVACA'], 'latitude' => 18.9242, 'longitude' => -99.2216],
        ['terms' => ['TEJUPILCO'], 'latitude' => 18.9047, 'longitude' => -100.1530],
        ['terms' => ['VALLE DE BRAVO'], 'latitude' => 19.1925, 'longitude' => -100.1308],
        ['terms' => ['IXTAPAN DE LA SAL'], 'latitude' => 18.8430, 'longitude' => -99.6768],
        ['terms' => ['TENANCINGO'], 'latitude' => 18.9610, 'longitude' => -99.5900],
        ['terms' => ['SAN JOSE DEL RINCON'], 'latitude' => 19.6620, 'longitude' => -100.1510],
        ['terms' => ['SAN FELIPE DEL PROGRESO'], 'latitude' => 19.7147, 'longitude' => -99.9512],
        ['terms' => ['ATLACOMULCO'], 'latitude' => 19.7985, 'longitude' => -99.8752],
        ['terms' => ['IXTLAHUACA'], 'latitude' => 19.5689, 'longitude' => -99.7660],
        ['terms' => ['JILOTEPEC'], 'latitude' => 19.9523, 'longitude' => -99.5334],
        ['terms' => ['AXAPUSCO'], 'latitude' => 19.7243, 'longitude' => -98.7584],
        ['terms' => ['HUEYPOXTLA'], 'latitude' => 19.9092, 'longitude' => -99.0778],
        ['terms' => ['XONACATLAN'], 'latitude' => 19.4032, 'longitude' => -99.5280],
        ['terms' => ['MONICA PRETELINI', '50010', 'NICOLAS SAN JUAN', 'SAN LORENZO TEPALTITLAN'], 'latitude' => 19.2925, 'longitude' => -99.6569],
        ['terms' => ['ATIZAPAN', 'LOPEZ MATEOS'], 'latitude' => 19.5582, 'longitude' => -99.2617],
        ['terms' => ['NAUCALPAN'], 'latitude' => 19.4785, 'longitude' => -99.2396],
        ['terms' => ['JESUS DEL MONTE', 'ANGELES LOMAS'], 'latitude' => 19.4006, 'longitude' => -99.2817],
        ['terms' => ['CUAUTITLAN'], 'latitude' => 19.6726, 'longitude' => -99.1776],
        ['terms' => ['ECATEPEC'], 'latitude' => 19.6018, 'longitude' => -99.0507],
        ['terms' => ['TEXCOCO'], 'latitude' => 19.5119, 'longitude' => -98.8837],
        ['terms' => ['CHIMALHUACAN'], 'latitude' => 19.4213, 'longitude' => -98.9507],
        ['terms' => ['NEZAHUALCOYOTL'], 'latitude' => 19.4006, 'longitude' => -99.0148],
        ['terms' => ['VALLE DE CHALCO'], 'latitude' => 19.2917, 'longitude' => -98.9380],
        ['terms' => ['CHALCO'], 'latitude' => 19.2613, 'longitude' => -98.8976],
        ['terms' => ['IXTAPALUCA', 'SAN BUENAVENTURA'], 'latitude' => 19.3151, 'longitude' => -98.8824],
        ['terms' => ['LOS REYES', 'MAGDALENA ATLICPAC'], 'latitude' => 19.3513, 'longitude' => -98.9876],
        ['terms' => ['AMECAMECA'], 'latitude' => 19.1238, 'longitude' => -98.7665],
        ['terms' => ['SAN NICOLAS TOLENTINO', '50230'], 'latitude' => 19.3370, 'longitude' => -99.5900],
        ['terms' => ['ACOXPA'], 'latitude' => 19.2998, 'longitude' => -99.1250],
        ['terms' => ['RIOBAMBA', 'LINDAVISTA'], 'latitude' => 19.4871, 'longitude' => -99.1327],
        ['terms' => ['TLACOTALPAN', 'ROMA SUR', 'CLINICA LONDRES'], 'latitude' => 19.4062, 'longitude' => -99.1646],
        ['terms' => ['MOCEL', 'CHAPULTEPEC'], 'latitude' => 19.4117, 'longitude' => -99.1848],
        ['terms' => ['PEDREGAL', 'SANTA TERESA'], 'latitude' => 19.3046, 'longitude' => -99.2080],
        ['terms' => ['POLANCO', 'SANTA MONICA'], 'latitude' => 19.4347, 'longitude' => -99.1973],
        ['terms' => ['UNIVERSIDAD 1080', 'XOCO'], 'latitude' => 19.3606, 'longitude' => -99.1668],
        ['terms' => ['SAN FRANCISCO 5', 'DEL VALLE', 'PRODIFEM', 'CBTA'], 'latitude' => 19.3820, 'longitude' => -99.1718],
    ];

    /**
     * @return array{latitude: float, longitude: float, estimated: bool}
     */
    public static function resolve(Hospital $hospital): array
    {
        $knownLocation = self::resolveKnownLocation($hospital);

        if ($knownLocation !== null) {
            return $knownLocation;
        }

        $id = max(1, (int) $hospital->getKey());

        return [
            'latitude' => round(19.4326 + (((($id * 17) % 19) - 9) * 0.012), 6),
            'longitude' => round(-99.1332 + (((($id * 29) % 19) - 9) * 0.014), 6),
            'estimated' => true,
        ];
    }

    /**
     * Unlike the route preview, an institution map must not invent an unknown location.
     *
     * @return array{latitude: float, longitude: float, estimated: bool}|null
     */
    public static function resolveKnownLocation(Hospital $hospital): ?array
    {
        $storedCoordinates = self::storedCoordinates($hospital);

        if ($storedCoordinates !== null) {
            return $storedCoordinates + ['estimated' => false];
        }

        $locationText = Str::upper(Str::ascii(implode(' ', array_filter([
            $hospital->name,
            $hospital->getAttribute('adress'),
            $hospital->getAttribute('municipality'),
            $hospital->getAttribute('state'),
            $hospital->getAttribute('postal_code'),
        ]))));

        foreach (self::LOCATION_HINTS as $hint) {
            if (collect($hint['terms'])->contains(fn (string $term) => str_contains($locationText, $term))) {
                [$latitudeOffset, $longitudeOffset] = self::stableOffset((int) $hospital->getKey());

                return [
                    'latitude' => round($hint['latitude'] + $latitudeOffset, 6),
                    'longitude' => round($hint['longitude'] + $longitudeOffset, 6),
                    'estimated' => true,
                ];
            }
        }

        return null;
    }

    public static function hasStoredCoordinates(Hospital $hospital): bool
    {
        return self::storedCoordinates($hospital) !== null;
    }

    /**
     * @return array{latitude: float, longitude: float}|null
     */
    private static function storedCoordinates(Hospital $hospital): ?array
    {
        $latitude = $hospital->getAttribute('latitude');
        $longitude = $hospital->getAttribute('longitude');

        if (! is_numeric($latitude) || ! is_numeric($longitude)) {
            return null;
        }

        $latitude = (float) $latitude;
        $longitude = (float) $longitude;

        if ($latitude < -90 || $latitude > 90 || $longitude < -180 || $longitude > 180) {
            return null;
        }

        return compact('latitude', 'longitude');
    }

    /**
     * @return array{float, float}
     */
    private static function stableOffset(int $id): array
    {
        return [
            ((($id * 37) % 11) - 5) * 0.0012,
            ((($id * 53) % 11) - 5) * 0.0014,
        ];
    }
}
