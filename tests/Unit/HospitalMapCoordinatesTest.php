<?php

namespace Tests\Unit;

use App\Models\Hospital;
use App\Support\HospitalMapCoordinates;
use Tests\TestCase;

class HospitalMapCoordinatesTest extends TestCase
{
    public function test_stored_coordinates_take_priority_and_are_not_estimated(): void
    {
        $hospital = new Hospital(['name' => 'Cuernavaca', 'latitude' => 20.5, 'longitude' => -98.5]);
        $expected = ['latitude' => 20.5, 'longitude' => -98.5, 'estimated' => false];
        $this->assertSame($expected, HospitalMapCoordinates::resolveKnownLocation($hospital));
        $this->assertSame($expected, HospitalMapCoordinates::resolve($hospital));
    }

    public function test_known_localities_keep_route_estimates_and_unknown_locations_are_not_fabricated(): void
    {
        $hospital = new Hospital(['name' => 'Hospital local', 'adress' => 'Cuernavaca']);
        $hospital->id = 47;
        $location = HospitalMapCoordinates::resolveKnownLocation($hospital);
        $this->assertTrue($location['estimated']);
        $this->assertSame($location, HospitalMapCoordinates::resolve($hospital));

        $hospital->adress = 'Sin direccion';
        $this->assertNull(HospitalMapCoordinates::resolveKnownLocation($hospital));
        $this->assertTrue(HospitalMapCoordinates::resolve($hospital)['estimated']);
    }

    public function test_missing_or_out_of_range_coordinates_are_not_treated_as_exact(): void
    {
        foreach ([[null, null], [19, null], [null, -99], [91, -99], [19, -181]] as [$lat, $lng]) {
            $hospital = new Hospital(['name' => 'Hospital sin ubicacion', 'latitude' => $lat, 'longitude' => $lng]);
            $this->assertNull(HospitalMapCoordinates::resolveKnownLocation($hospital));
        }
    }
}
