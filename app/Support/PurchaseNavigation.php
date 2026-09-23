<?php

namespace App\Support;

use App\Models\User;

final class PurchaseNavigation
{
    public static function sections(?User $user, ?int $laboratoryId = null): array
    {
        $sections = [];
        foreach ([
            'mine' => 'Órdenes de compra',
            'new' => 'Nueva OC',
            'all' => 'Todas',
            'paid' => 'Pagadas',
            'pending' => 'Pendientes de pago',
            'rejected' => 'Rechazadas',
            'minimum-stock' => 'Stock Mínimo',
        ] as $key => $label) {
            $permission = $key === 'minimum-stock' ? 'menu.compras' : 'menu.compras.'.$key;
            if (! AdminMenuAccess::allows($user, $permission)) {
                continue;
            }
            $query = array_filter(['laboratory_id' => $laboratoryId]);
            $sections[$key] = [
                'label' => $label,
                'url' => match ($key) {
                    'new' => route('admin.purchases.create', $query),
                    'minimum-stock' => route('admin.purchases.minimum-stock', $query),
                    default => route('admin.warehouses.purchase-orders.index', ['section' => $key] + $query),
                },
            ];
        }

        return $sections;
    }
}
