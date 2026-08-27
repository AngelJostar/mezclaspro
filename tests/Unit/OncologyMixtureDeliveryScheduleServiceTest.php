<?php

namespace Tests\Unit;

use App\Services\OncologyMixtureDeliveryScheduleService;
use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OncologyMixtureDeliveryScheduleServiceTest extends TestCase
{
    public function test_it_normalizes_multiple_delivery_dates_per_mixture(): void
    {
        $service = new OncologyMixtureDeliveryScheduleService();

        $mixtures = $service->normalize([
            ['fechas_entrega' => ['2026-08-27T09:30', '2026-08-28T11:00']],
            ['fechas_entrega' => ['2026-08-29T12:15']],
        ], CarbonImmutable::parse('2026-08-26 10:00:00', 'America/Mexico_City'));

        $this->assertSame([
            '2026-08-27 09:30:00',
            '2026-08-28 11:00:00',
        ], $mixtures[0]['fechas_entrega']);
        $this->assertSame('2026-08-27 09:30:00', $service->firstDeliveryAt($mixtures));
    }

    public function test_it_rejects_duplicate_delivery_dates_in_a_mixture(): void
    {
        $this->expectException(ValidationException::class);

        (new OncologyMixtureDeliveryScheduleService())->normalize([
            ['fechas_entrega' => ['2026-08-27T09:30', '2026-08-27T09:30']],
        ], CarbonImmutable::parse('2026-08-26', 'America/Mexico_City'));
    }

    public function test_it_rejects_past_delivery_dates(): void
    {
        $this->expectException(ValidationException::class);

        (new OncologyMixtureDeliveryScheduleService())->normalize([
            ['fechas_entrega' => ['2026-08-25T09:30']],
        ], CarbonImmutable::parse('2026-08-26', 'America/Mexico_City'));
    }
}
