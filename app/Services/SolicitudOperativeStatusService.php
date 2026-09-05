<?php

namespace App\Services;

use App\Models\Oncologicos\SolicitudOnco;

class SolicitudOperativeStatusService
{
    private const STAGE_ORDER = [
        'pendiente' => 0,
        'aprobada' => 1,
        'dispensada' => 2,
        'preparada' => 3,
        'revisada' => 4,
        'entregada' => 5,
    ];

    private const TERMINAL_REQUEST_STATES = [
        'cancelada',
        'no_aprobada',
        'no-aprobada',
    ];

    public function sync(int $solicitudId): ?string
    {
        $solicitud = SolicitudOnco::query()->find($solicitudId);

        if (!$solicitud) {
            return null;
        }

        if (in_array($solicitud->estado, self::TERMINAL_REQUEST_STATES, true)) {
            return $solicitud->estado;
        }

        $estado = self::resolve($solicitud->mezclas()->pluck('estado'));

        if ($solicitud->estado !== $estado) {
            $solicitud->updateQuietly(['estado' => $estado]);
        }

        return $estado;
    }

    public static function resolve(iterable $mixStates): string
    {
        $states = collect($mixStates)
            ->map(fn ($state) => self::normalize((string) $state))
            ->values();

        if ($states->isEmpty()) {
            return 'pendiente';
        }

        $activeStates = $states->reject(fn ($state) => $state === 'cancelada')->values();

        if ($activeStates->isEmpty()) {
            return 'cancelada';
        }

        return $activeStates
            ->sortBy(fn ($state) => self::STAGE_ORDER[$state] ?? self::STAGE_ORDER['pendiente'])
            ->first();
    }

    private static function normalize(string $state): string
    {
        return match ($state) {
            'enproceso' => 'preparada',
            'finalizada' => 'entregada',
            default => array_key_exists($state, self::STAGE_ORDER) || $state === 'cancelada'
                ? $state
                : 'pendiente',
        };
    }
}
