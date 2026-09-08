<?php

namespace Tests\Unit;

use App\Support\UtmCoordinates;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class UtmCoordinatesTest extends TestCase
{
    public function test_it_converts_mexico_city_utm_coordinates_to_latitude_and_longitude(): void
    {
        $coordinates = UtmCoordinates::toLatitudeLongitude(
            zone: 14,
            hemisphere: 'N',
            easting: 486017.3309,
            northing: 2148700.2198
        );

        $this->assertEqualsWithDelta(19.4326, $coordinates['latitude'], 0.001);
        $this->assertEqualsWithDelta(-99.1332, $coordinates['longitude'], 0.001);
    }

    public function test_it_rejects_an_invalid_utm_zone(): void
    {
        $this->expectException(InvalidArgumentException::class);

        UtmCoordinates::toLatitudeLongitude(0, 'N', 486017.3309, 2148700.2198);
    }
}
