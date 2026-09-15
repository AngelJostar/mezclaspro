<?php

namespace App\Support;

use App\Models\User;

final class AdministrationNavigation
{
    public static function canViewReports(?User $user): bool
    {
        return self::allows($user, 'menu.administracion.reports');
    }

    public static function billingSections(?User $user): array
    {
        $sections = [
            'pending' => ['label' => 'Pendientes', 'route' => 'admin.instituciones.billing.index'],
            'receivable' => ['label' => 'Por cobrar', 'route' => 'admin.instituciones.billing.receivable'],
            'history' => ['label' => 'Historial', 'route' => 'admin.instituciones.billing.history'],
            'movements' => ['label' => 'Bitácora', 'route' => 'admin.instituciones.billing.movements'],
        ];

        return array_filter($sections, fn ($key) => self::allows($user, 'menu.facturacion.'.$key), ARRAY_FILTER_USE_KEY);
    }

    private static function allows(?User $user, string $permission): bool
    {
        return $user && AdminMenuAccess::allows($user, $permission,
            $user->hasAnyRole(['Super Admin', 'Administracion y facturacion']) || $user->can($permission));
    }
}
