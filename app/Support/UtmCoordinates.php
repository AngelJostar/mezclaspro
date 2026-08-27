<?php

namespace App\Support;

use InvalidArgumentException;

final class UtmCoordinates
{
    /**
     * Convert WGS84 UTM coordinates to geographic latitude and longitude.
     *
     * @return array{latitude: float, longitude: float}
     */
    public static function toLatitudeLongitude(
        int $zone,
        string $hemisphere,
        float $easting,
        float $northing
    ): array {
        $hemisphere = strtoupper($hemisphere);

        if ($zone < 1 || $zone > 60) {
            throw new InvalidArgumentException('UTM zone must be between 1 and 60.');
        }

        if (! in_array($hemisphere, ['N', 'S'], true)) {
            throw new InvalidArgumentException('UTM hemisphere must be N or S.');
        }

        if ($easting < 100000 || $easting > 900000) {
            throw new InvalidArgumentException('UTM easting is outside the supported range.');
        }

        if ($northing < 0 || $northing > 10000000) {
            throw new InvalidArgumentException('UTM northing is outside the supported range.');
        }

        $semiMajorAxis = 6378137.0;
        $eccentricitySquared = 0.00669438;
        $scaleFactor = 0.9996;
        $eccentricityPrimeSquared = $eccentricitySquared / (1 - $eccentricitySquared);
        $e1 = (1 - sqrt(1 - $eccentricitySquared)) / (1 + sqrt(1 - $eccentricitySquared));

        $x = $easting - 500000.0;
        $y = $northing;

        if ($hemisphere === 'S') {
            $y -= 10000000.0;
        }

        $meridionalArc = $y / $scaleFactor;
        $mu = $meridionalArc / ($semiMajorAxis * (
            1
            - ($eccentricitySquared / 4)
            - (3 * $eccentricitySquared ** 2 / 64)
            - (5 * $eccentricitySquared ** 3 / 256)
        ));

        $footprintLatitude = $mu
            + ((3 * $e1 / 2) - (27 * $e1 ** 3 / 32)) * sin(2 * $mu)
            + ((21 * $e1 ** 2 / 16) - (55 * $e1 ** 4 / 32)) * sin(4 * $mu)
            + (151 * $e1 ** 3 / 96) * sin(6 * $mu)
            + (1097 * $e1 ** 4 / 512) * sin(8 * $mu);

        $sinFootprint = sin($footprintLatitude);
        $cosFootprint = cos($footprintLatitude);
        $tanFootprint = tan($footprintLatitude);
        $radiusPrimeVertical = $semiMajorAxis / sqrt(
            1 - $eccentricitySquared * $sinFootprint ** 2
        );
        $radiusMeridian = $semiMajorAxis * (1 - $eccentricitySquared) / (
            (1 - $eccentricitySquared * $sinFootprint ** 2) ** 1.5
        );
        $tangentSquared = $tanFootprint ** 2;
        $eccentricityCosine = $eccentricityPrimeSquared * $cosFootprint ** 2;
        $distance = $x / ($radiusPrimeVertical * $scaleFactor);

        $latitude = $footprintLatitude - (
            $radiusPrimeVertical * $tanFootprint / $radiusMeridian
        ) * (
            $distance ** 2 / 2
            - (5 + 3 * $tangentSquared + 10 * $eccentricityCosine
                - 4 * $eccentricityCosine ** 2 - 9 * $eccentricityPrimeSquared)
                * $distance ** 4 / 24
            + (61 + 90 * $tangentSquared + 298 * $eccentricityCosine
                + 45 * $tangentSquared ** 2 - 252 * $eccentricityPrimeSquared
                - 3 * $eccentricityCosine ** 2)
                * $distance ** 6 / 720
        );

        $longitude = (
            $distance
            - (1 + 2 * $tangentSquared + $eccentricityCosine) * $distance ** 3 / 6
            + (5 - 2 * $eccentricityCosine + 28 * $tangentSquared
                - 3 * $eccentricityCosine ** 2 + 8 * $eccentricityPrimeSquared
                + 24 * $tangentSquared ** 2)
                * $distance ** 5 / 120
        ) / $cosFootprint;

        $centralMeridian = (($zone - 1) * 6) - 180 + 3;

        return [
            'latitude' => round(rad2deg($latitude), 7),
            'longitude' => round($centralMeridian + rad2deg($longitude), 7),
        ];
    }
}
