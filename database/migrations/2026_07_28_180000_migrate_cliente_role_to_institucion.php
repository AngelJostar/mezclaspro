<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $rolesTable = $tableNames['roles'] ?? 'roles';
        $modelHasRolesTable = $tableNames['model_has_roles'] ?? 'model_has_roles';
        $roleHasPermissionsTable = $tableNames['role_has_permissions'] ?? 'role_has_permissions';
        $rolePivotKey = $columnNames['role_pivot_key'] ?? 'role_id';
        $modelMorphKey = $columnNames['model_morph_key'] ?? 'model_id';

        DB::transaction(function () use (
            $rolesTable,
            $modelHasRolesTable,
            $roleHasPermissionsTable,
            $rolePivotKey,
            $modelMorphKey
        ) {
            $clienteRole = Role::query()
                ->where('name', 'Cliente')
                ->where('guard_name', 'web')
                ->first();

            $institucionRole = Role::query()
                ->where('name', 'Institucion')
                ->where('guard_name', 'web')
                ->first();

            if (!$institucionRole) {
                $institucionRole = Role::query()->create([
                    'name' => 'Institucion',
                    'guard_name' => 'web',
                ]);
            }

            if ($clienteRole) {
                $permissionIds = DB::table($roleHasPermissionsTable)
                    ->where($rolePivotKey, $clienteRole->id)
                    ->pluck('permission_id')
                    ->all();

                foreach ($permissionIds as $permissionId) {
                    $exists = DB::table($roleHasPermissionsTable)
                        ->where($rolePivotKey, $institucionRole->id)
                        ->where('permission_id', $permissionId)
                        ->exists();

                    if (!$exists) {
                        DB::table($roleHasPermissionsTable)->insert([
                            'permission_id' => $permissionId,
                            $rolePivotKey => $institucionRole->id,
                        ]);
                    }
                }

                $modelAssignments = DB::table($modelHasRolesTable)
                    ->where($rolePivotKey, $clienteRole->id)
                    ->get();

                foreach ($modelAssignments as $assignment) {
                    $exists = DB::table($modelHasRolesTable)
                        ->where($rolePivotKey, $institucionRole->id)
                        ->where('model_type', $assignment->model_type)
                        ->where($modelMorphKey, $assignment->{$modelMorphKey})
                        ->exists();

                    if (!$exists) {
                        DB::table($modelHasRolesTable)->insert([
                            $rolePivotKey => $institucionRole->id,
                            'model_type' => $assignment->model_type,
                            $modelMorphKey => $assignment->{$modelMorphKey},
                        ]);
                    }
                }

                DB::table($modelHasRolesTable)
                    ->where($rolePivotKey, $clienteRole->id)
                    ->delete();
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $modelHasRolesTable = $tableNames['model_has_roles'] ?? 'model_has_roles';
        $roleHasPermissionsTable = $tableNames['role_has_permissions'] ?? 'role_has_permissions';
        $rolePivotKey = $columnNames['role_pivot_key'] ?? 'role_id';
        $modelMorphKey = $columnNames['model_morph_key'] ?? 'model_id';

        DB::transaction(function () use (
            $modelHasRolesTable,
            $roleHasPermissionsTable,
            $rolePivotKey,
            $modelMorphKey
        ) {
            $clienteRole = Role::query()
                ->where('name', 'Cliente')
                ->where('guard_name', 'web')
                ->first();

            if (!$clienteRole) {
                $clienteRole = Role::query()->create([
                    'name' => 'Cliente',
                    'guard_name' => 'web',
                ]);
            }

            $institucionRole = Role::query()
                ->where('name', 'Institucion')
                ->where('guard_name', 'web')
                ->first();

            if (!$institucionRole) {
                return;
            }

            $permissionIds = DB::table($roleHasPermissionsTable)
                ->where($rolePivotKey, $institucionRole->id)
                ->pluck('permission_id')
                ->all();

            foreach ($permissionIds as $permissionId) {
                $exists = DB::table($roleHasPermissionsTable)
                    ->where($rolePivotKey, $clienteRole->id)
                    ->where('permission_id', $permissionId)
                    ->exists();

                if (!$exists) {
                    DB::table($roleHasPermissionsTable)->insert([
                        'permission_id' => $permissionId,
                        $rolePivotKey => $clienteRole->id,
                    ]);
                }
            }

            $modelAssignments = DB::table($modelHasRolesTable)
                ->where($rolePivotKey, $institucionRole->id)
                ->get();

            foreach ($modelAssignments as $assignment) {
                $exists = DB::table($modelHasRolesTable)
                    ->where($rolePivotKey, $clienteRole->id)
                    ->where('model_type', $assignment->model_type)
                    ->where($modelMorphKey, $assignment->{$modelMorphKey})
                    ->exists();

                if (!$exists) {
                    DB::table($modelHasRolesTable)->insert([
                        $rolePivotKey => $clienteRole->id,
                        'model_type' => $assignment->model_type,
                        $modelMorphKey => $assignment->{$modelMorphKey},
                    ]);
                }
            }

            DB::table($modelHasRolesTable)
                ->where($rolePivotKey, $institucionRole->id)
                ->delete();

            DB::table($roleHasPermissionsTable)
                ->where($rolePivotKey, $institucionRole->id)
                ->delete();

            $institucionRole->delete();
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
