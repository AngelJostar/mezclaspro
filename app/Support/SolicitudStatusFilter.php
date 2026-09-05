<?php

namespace App\Support;

final class SolicitudStatusFilter
{
    public const ALL = 'todas';

    public const PENDING = 'pendientes';

    public const PREPARATION = 'preparacion';

    public const IN_ROUTE = 'ruta';

    public const DELIVERED = 'entregadas';

    public const HISTORY = 'historial';

    public const PREPARATION_STATES = [
        'aprobada',
        'dispensada',
        'enproceso',
        'preparada',
        'revisada',
    ];

    public const DELIVERED_STATES = [
        'entregada',
        'finalizada',
    ];

    public const HISTORY_STATES = [
        'entregada',
        'finalizada',
        'cancelada',
        'no_aprobada',
        'no-aprobada',
    ];

    public static function options(): array
    {
        return [
            self::ALL => 'Todas',
            self::PENDING => 'Pendientes',
            self::PREPARATION => 'En preparación',
            self::IN_ROUTE => 'En ruta',
            self::DELIVERED => 'Entregadas',
            self::HISTORY => 'Historial',
        ];
    }

    public static function normalize(?string $filter): string
    {
        return array_key_exists((string) $filter, self::options())
            ? (string) $filter
            : self::ALL;
    }

    public static function matches(string $filter, ?string $status, bool $isInRoute = false): bool
    {
        $filter = self::normalize($filter);
        $status = self::normalizeStatus($status);

        return match ($filter) {
            self::PENDING => $status === 'pendiente',
            self::PREPARATION => ! $isInRoute && in_array($status, self::PREPARATION_STATES, true),
            self::IN_ROUTE => $isInRoute && in_array($status, self::PREPARATION_STATES, true),
            self::DELIVERED => in_array($status, self::DELIVERED_STATES, true),
            self::HISTORY => in_array($status, self::HISTORY_STATES, true),
            default => true,
        };
    }

    private static function normalizeStatus(?string $status): string
    {
        $status = mb_strtolower(trim((string) $status));

        return $status === '' ? 'pendiente' : str_replace('-', '_', $status);
    }
}
