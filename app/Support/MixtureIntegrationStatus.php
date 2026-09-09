<?php

namespace App\Support;

final class MixtureIntegrationStatus
{
    public const LABELS = [
        'pending' => 'Pendiente',
        'authorized' => 'Aprobada',
        'dispensed' => 'Dispensada',
        'preparing' => 'Preparada',
        'ready' => 'Inspeccionada',
        'in_route' => 'En ruta',
        'delivered' => 'Entregada',
        'rejected' => 'No aprobada',
        'cancelled' => 'Cancelada',
        'materialization_failed' => 'Error de integración',
        'received' => 'Pendiente',
        'materialized' => 'Pendiente',
    ];

    public static function fromCbta(?string $status): string
    {
        return match (strtolower((string) $status)) {
            'aprobada', 'autorizada' => 'authorized',
            'dispensada' => 'dispensed',
            'enproceso', 'preparada' => 'preparing',
            'revisada', 'inspeccionada', 'lista' => 'ready',
            'en_ruta', 'en-ruta' => 'in_route',
            'finalizada', 'entregada' => 'delivered',
            'cancelada' => 'cancelled',
            'no_aprobada', 'no-aprobada', 'rechazada' => 'rejected',
            'materialization_failed' => 'materialization_failed',
            default => 'pending',
        };
    }

    public static function label(?string $status): string
    {
        return self::LABELS[$status ?? ''] ?? 'Pendiente';
    }
}
