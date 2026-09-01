<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

final class AdminMenuAccess
{
    public const GENERAL_ROLE = 'Usuario general';

    /**
     * @return array<string, string>
     */
    public static function roleOptions(): array
    {
        return [
            'Super Admin' => 'Superadministrador',
            'Admin' => 'Administrador',
            self::GENERAL_ROLE => 'Usuario general',
        ];
    }

    /**
     * @return array<int, array{permission: string, label: string, icon: string, children?: array<int, array{permission: string, label: string}>}>
     */
    public static function tree(): array
    {
        return [
            [
                'permission' => 'menu.solicitudes',
                'label' => 'Solicitudes',
                'icon' => 'fa-file-medical',
            ],
            [
                'permission' => 'menu.catalogo',
                'label' => 'Catalogo y listas de precios',
                'icon' => 'fa-book-medical',
                'children' => [
                    ['permission' => 'menu.catalogo.nutricionales', 'label' => 'Medicamentos nutricionales'],
                    ['permission' => 'menu.catalogo.inputs', 'label' => 'Inputs nutricionales'],
                    ['permission' => 'menu.catalogo.listas-nutricionales', 'label' => 'Listas nutricionales'],
                    ['permission' => 'menu.catalogo.oncologicos', 'label' => 'Medicamentos oncologicos'],
                    ['permission' => 'menu.catalogo.diluyentes', 'label' => 'Diluyentes'],
                    ['permission' => 'menu.catalogo.listas-oncologicas', 'label' => 'Listas oncologicas'],
                ],
            ],
            [
                'permission' => 'menu.compras',
                'label' => 'Compras',
                'icon' => 'fa-cart-shopping',
                'children' => [
                    ['permission' => 'menu.compras.mine', 'label' => 'Mis ordenes'],
                    ['permission' => 'menu.compras.new', 'label' => 'Nueva OC'],
                    ['permission' => 'menu.compras.paid', 'label' => 'Pagadas'],
                    ['permission' => 'menu.compras.pending', 'label' => 'Pendientes de pago'],
                    ['permission' => 'menu.compras.rejected', 'label' => 'Rechazadas'],
                    ['permission' => 'menu.proveedores', 'label' => 'Proveedores'],
                ],
            ],
            [
                'permission' => 'menu.distribucion',
                'label' => 'Distribución',
                'icon' => 'fa-route',
            ],
            [
                'permission' => 'menu.warehouses',
                'label' => 'Centrales y Almacenes',
                'icon' => 'fa-warehouse',
            ],
            [
                'permission' => 'menu.instituciones',
                'label' => 'Instituciones',
                'icon' => 'fa-building-columns',
                'children' => [
                    ['permission' => 'menu.instituciones.list', 'label' => 'Lista de instituciones'],
                    ['permission' => 'menu.instituciones.hospitals', 'label' => 'Hospitales'],
                ],
            ],
            [
                'permission' => 'menu.administracion',
                'label' => 'Administracion',
                'icon' => 'fa-chart-column',
                'children' => [
                    ['permission' => 'menu.administracion.reports', 'label' => 'Reportes'],
                ],
            ],
            [
                'permission' => 'menu.facturacion',
                'label' => 'Facturacion',
                'icon' => 'fa-file-invoice-dollar',
                'children' => [
                    ['permission' => 'menu.facturacion.pending', 'label' => 'Pendiente'],
                    ['permission' => 'menu.facturacion.receivable', 'label' => 'Por Cobrar'],
                    ['permission' => 'menu.facturacion.history', 'label' => 'Historial'],
                    ['permission' => 'menu.facturacion.movements', 'label' => 'Bitacora'],
                ],
            ],
            [
                'permission' => 'menu.capacitaciones',
                'label' => 'Personal y Capacitaciones',
                'icon' => 'fa-graduation-cap',
                'children' => [
                    ['permission' => 'menu.capacitaciones.personal', 'label' => 'Personal'],
                    ['permission' => 'menu.capacitaciones.programas', 'label' => 'Programas'],
                    ['permission' => 'menu.capacitaciones.alumnos', 'label' => 'Alumnos'],
                ],
            ],
            [
                'permission' => 'menu.maintenance-qualifications',
                'label' => 'Mantenimiento y Calificaciones',
                'icon' => 'fa-screwdriver-wrench',
            ],
            [
                'permission' => 'menu.users',
                'label' => 'Roles y Permisos',
                'icon' => 'fa-user-shield',
                'children' => [
                    ['permission' => 'menu.users.roles', 'label' => 'Roles'],
                    ['permission' => 'menu.users.permissions', 'label' => 'Permisos'],
                ],
            ],
        ];
    }

    /**
     * @return array<int, string>
     */
    public static function permissionNames(): array
    {
        return collect(self::tree())
            ->flatMap(function (array $item) {
                return collect([$item['permission']])
                    ->merge(collect($item['children'] ?? [])->pluck('permission'));
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, string>  $permissions
     * @return array<int, string>
     */
    public static function normalizeSelection(array $permissions): array
    {
        $allowed = collect(self::permissionNames());
        $selection = collect($permissions)
            ->map(fn ($permission) => trim((string) $permission))
            ->filter(fn ($permission) => $allowed->contains($permission))
            ->unique();

        foreach (self::tree() as $item) {
            $children = collect($item['children'] ?? [])->pluck('permission');

            if ($children->intersect($selection)->isNotEmpty()) {
                $selection->push($item['permission']);
            }
        }

        return $selection->unique()->values()->all();
    }

    /**
     * Existing permissions are retained as compatibility permissions for route
     * middleware that predates the menu-level access controls.
     *
     * @param  array<int, string>  $menuPermissions
     * @return array<int, string>
     */
    public static function supportingPermissionNames(array $menuPermissions): array
    {
        $map = [
            'menu.solicitudes' => [
                'nutricionales_solicitudes_index',
                'nutricionales_solicitudes_create',
                'nutricionales_solicitudes_store',
                'nutricionales_solicitudes_show',
                'nutricionales_solicitudes_edit',
                'nutricionales_solicitudes_update',
                'oncologicos_solicitudes_index',
                'oncologicos_solicitudes_create',
                'oncologicos_solicitudes_store',
                'oncologicos_solicitudes_show',
                'oncologicos_solicitudes_edit',
                'oncologicos_solicitudes_update',
                'oncologicos_mezclas_index',
                'oncologicos_mezclas_create',
                'oncologicos_mezclas_show',
                'oncologicos_mezclas_store',
                'oncologicos_mezclas_edit',
                'oncologicos_mezclas_update',
            ],
            'menu.catalogo' => [
                'medicamentos_nutricionales',
                'nutricionales_listas',
                'medicamentos_oncologicos',
            ],
            'menu.catalogo.nutricionales' => ['medicamentos_nutricionales'],
            'menu.catalogo.inputs' => ['medicamentos_nutricionales'],
            'menu.catalogo.listas-nutricionales' => ['nutricionales_listas'],
            'menu.catalogo.oncologicos' => ['medicamentos_oncologicos'],
            'menu.catalogo.diluyentes' => [
                'medicamentos_oncologicos',
                'oncologicos_diluents_index',
                'oncologicos_diluents_create',
                'oncologicos_diluents_store',
                'oncologicos_diluents_edit',
                'oncologicos_diluents_update',
                'oncologicos_diluents_destroy',
            ],
            'menu.catalogo.listas-oncologicas' => ['medicamentos_oncologicos'],
            'menu.compras' => ['oncologicos_laboratory_index'],
            'menu.proveedores' => ['oncologicos_laboratory_index'],
            'menu.distribucion' => ['laboratorios'],
            'menu.warehouses' => [
                'laboratorios',
                'oncologicos_laboratory_index',
                'oncologicos_laboratory_create',
                'oncologicos_laboratory_store',
                'oncologicos_laboratory_edit',
                'oncologicos_laboratory_update',
                'oncologicos_laboratory_destroy',
                'medicamentos_oncologicos',
                'medicamentos_nutricionales',
            ],
            'menu.instituciones.hospitals' => ['hospitales'],
            'menu.capacitaciones.personal' => ['usuarios'],
            'menu.users.roles' => ['roles'],
            'menu.users.permissions' => ['permisos'],
        ];

        $selected = collect($menuPermissions);

        return collect($map)
            ->filter(fn ($supporting, $menuPermission) => $selected->contains($menuPermission))
            ->flatten()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public static function managedPermissionNames(): array
    {
        return collect(self::permissionNames())
            ->merge(self::supportingPermissionNames(self::permissionNames()))
            ->unique()
            ->values()
            ->all();
    }

    public static function roleLabel(?string $role): string
    {
        return self::roleOptions()[$role] ?? ($role ?: 'Sin rol asignado');
    }

    public static function allows(?User $user, string $permission, bool $legacyAccess = true): bool
    {
        if (! $user) {
            return false;
        }

        return $user->hasRole(self::GENERAL_ROLE)
            ? $user->can($permission)
            : $legacyAccess;
    }

    public static function requiredPermission(Request $request): ?string
    {
        $routeName = (string) $request->route()?->getName();

        if ($routeName === 'admin.dashboard') {
            return null;
        }

        if (str_starts_with($routeName, 'admin.instituciones.billing.')) {
            return match ($routeName) {
                'admin.instituciones.billing.receivable' => 'menu.facturacion.receivable',
                'admin.instituciones.billing.history',
                'admin.instituciones.billing.move' => 'menu.facturacion.history',
                'admin.instituciones.billing.movements' => 'menu.facturacion.movements',
                default => 'menu.facturacion.pending',
            };
        }

        if (str_starts_with($routeName, 'admin.instituciones.reportes')
            || str_starts_with($routeName, 'admin.instituciones.exportar')) {
            return 'menu.administracion.reports';
        }

        if ($routeName === 'admin.warehouses.purchase-orders.index') {
            return match ((string) $request->query('section', 'mine')) {
                'paid' => 'menu.compras.paid',
                'pending' => 'menu.compras.pending',
                'rejected' => 'menu.compras.rejected',
                default => 'menu.compras.mine',
            };
        }

        if ($routeName === 'admin.purchases.create'
            || in_array($routeName, [
                'admin.oncologicos.laboratory.purchase-orders.create',
                'admin.oncologicos.laboratory.purchase-orders.store',
            ], true)) {
            return 'menu.compras.new';
        }

        if ($routeName === 'admin.oncologicos.laboratory.purchase-orders.download') {
            return 'menu.compras';
        }

        if (str_starts_with($routeName, 'admin.suppliers.')) {
            return 'menu.proveedores';
        }

        if (str_starts_with($routeName, 'admin.distribution.')) {
            return 'menu.distribucion';
        }

        if (str_starts_with($routeName, 'admin.solicitudes.')
            || str_starts_with($routeName, 'admin.nutricionales.solicitudes.')
            || str_starts_with($routeName, 'admin.oncologicos.solicitudes.')
            || str_starts_with($routeName, 'admin.antibioticos.solicitudes.')
            || str_starts_with($routeName, 'admin.oncologicos.mezclas.')) {
            return 'menu.solicitudes';
        }

        if (str_starts_with($routeName, 'admin.catalogo-listas.')) {
            return 'menu.catalogo';
        }

        if (str_starts_with($routeName, 'admin.nutricionales.medicines.')) {
            return 'menu.catalogo.nutricionales';
        }

        if (str_starts_with($routeName, 'admin.nutricionales.inputs.')) {
            return 'menu.catalogo.inputs';
        }

        if (str_starts_with($routeName, 'admin.nutricionales.nutri-medicine-lists.')) {
            return 'menu.catalogo.listas-nutricionales';
        }

        if (str_starts_with($routeName, 'admin.oncologicos.diluents.')
            || str_starts_with($routeName, 'admin.oncologicos.diluent_presentations.')
            || str_starts_with($routeName, 'admin.oncologicos.infusores.')) {
            return 'menu.catalogo.diluyentes';
        }

        if (str_starts_with($routeName, 'admin.oncologicos.medicines.catalog.')) {
            return 'menu.catalogo.oncologicos';
        }

        if (str_starts_with($routeName, 'admin.oncologicos.medicines.')) {
            return 'menu.catalogo.listas-oncologicas';
        }

        if (str_starts_with($routeName, 'admin.warehouses.')
            || str_starts_with($routeName, 'admin.nutricionales.stocks.')
            || str_starts_with($routeName, 'admin.oncologicos.inventory.')
            || (str_starts_with($routeName, 'admin.oncologicos.laboratory.')
                && ! str_contains($routeName, '.purchase-orders.'))) {
            return 'menu.warehouses';
        }

        if (str_starts_with($routeName, 'admin.hospitals.')
            || str_starts_with($routeName, 'admin.instituciones.hospitals')) {
            return 'menu.instituciones.hospitals';
        }

        if (str_starts_with($routeName, 'admin.instituciones.')) {
            return 'menu.instituciones.list';
        }

        if (str_starts_with($routeName, 'admin.capacitaciones.')) {
            return match ($routeName) {
                'admin.capacitaciones.alumnos' => 'menu.capacitaciones.alumnos',
                'admin.capacitaciones.personal',
                'admin.capacitaciones.personal.store' => 'menu.capacitaciones.personal',
                default => 'menu.capacitaciones.programas',
            };
        }

        if (str_starts_with($routeName, 'admin.maintenance-qualifications.')) {
            return 'menu.maintenance-qualifications';
        }

        if (in_array($routeName, [
            'admin.users.index',
            'admin.users.username.update',
            'admin.users.password.update',
            'admin.users.training-username.update',
            'admin.users.training-password.update',
            'admin.users.role-access.update',
            'admin.users.status.update',
        ], true)) {
            return 'menu.capacitaciones.personal';
        }

        if (str_starts_with($routeName, 'admin.users.')) {
            return 'menu.users.users';
        }

        if (str_starts_with($routeName, 'admin.roles.')) {
            return 'menu.users.roles';
        }

        if (str_starts_with($routeName, 'admin.permissions.')) {
            return 'menu.users.permissions';
        }

        return '__deny__';
    }
}
