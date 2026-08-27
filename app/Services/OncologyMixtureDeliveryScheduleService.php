<?php

namespace App\Services;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Validation\ValidationException;

class OncologyMixtureDeliveryScheduleService
{
    public const MAX_SCHEDULED_MIXTURES = 99;

    public function normalize(array $mixtures, ?CarbonInterface $today = null): array
    {
        $timezone = (string) config('app.timezone', 'America/Mexico_City');
        $minimumDate = $today
            ? CarbonImmutable::instance($today)->setTimezone($timezone)->startOfDay()
            : CarbonImmutable::today($timezone);
        $scheduledMixtures = 0;

        foreach ($mixtures as $mixtureIndex => &$mixture) {
            $dates = $mixture['fechas_entrega'] ?? null;

            if (! is_array($dates) || $dates === []) {
                $this->fail($mixtureIndex, 'agrega al menos una fecha de entrega.');
            }

            $normalizedDates = [];

            foreach ($dates as $date) {
                if (! is_string($date) || trim($date) === '') {
                    $this->fail($mixtureIndex, 'completa todas las fechas de entrega.');
                }

                try {
                    $deliveryAt = CarbonImmutable::parse(trim($date), $timezone);
                } catch (\Throwable) {
                    $this->fail($mixtureIndex, 'contiene una fecha de entrega no válida.');
                }

                if ($deliveryAt->lt($minimumDate)) {
                    $this->fail($mixtureIndex, 'no puede tener una fecha de entrega anterior a hoy.');
                }

                $normalized = $deliveryAt->format('Y-m-d H:i:s');

                if (in_array($normalized, $normalizedDates, true)) {
                    $this->fail($mixtureIndex, 'no puede repetir la misma fecha de entrega.');
                }

                $normalizedDates[] = $normalized;
            }

            $scheduledMixtures += count($normalizedDates);
            $mixture['fechas_entrega'] = $normalizedDates;
        }
        unset($mixture);

        if ($scheduledMixtures > self::MAX_SCHEDULED_MIXTURES) {
            throw ValidationException::withMessages([
                'mezclas' => 'La solicitud no puede generar más de '.self::MAX_SCHEDULED_MIXTURES.' mezclas programadas.',
            ]);
        }

        return $mixtures;
    }

    public function firstDeliveryAt(array $mixtures): ?string
    {
        $dates = [];

        foreach ($mixtures as $mixture) {
            array_push($dates, ...($mixture['fechas_entrega'] ?? []));
        }

        sort($dates);

        return $dates[0] ?? null;
    }

    private function fail(int $mixtureIndex, string $message): never
    {
        throw ValidationException::withMessages([
            'mezclas' => 'La Mezcla #'.($mixtureIndex + 1).' '.$message,
        ]);
    }
}
