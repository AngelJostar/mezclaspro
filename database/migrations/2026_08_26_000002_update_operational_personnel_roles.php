<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function () {
            $this->renameOrMergeRole('Usuario general', 'Quimico o Tecnico');

            Role::query()->firstOrCreate([
                'name' => 'Mensajero',
                'guard_name' => 'web',
            ]);
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        DB::transaction(function () {
            Role::query()
                ->where('name', 'Mensajero')
                ->where('guard_name', 'web')
                ->first()
                ?->delete();

            $this->renameOrMergeRole('Quimico o Tecnico', 'Usuario general');
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function renameOrMergeRole(string $sourceName, string $targetName): void
    {
        $sourceRole = Role::query()
            ->where('name', $sourceName)
            ->where('guard_name', 'web')
            ->first();
        $targetRole = Role::query()
            ->where('name', $targetName)
            ->where('guard_name', 'web')
            ->first();

        if (! $sourceRole) {
            Role::query()->firstOrCreate([
                'name' => $targetName,
                'guard_name' => 'web',
            ]);

            return;
        }

        if (! $targetRole) {
            $sourceRole->update(['name' => $targetName]);

            return;
        }

        $tableNames = config('permission.table_names');
        $columnNames = config('permission.column_names');
        $modelHasRolesTable = $tableNames['model_has_roles'] ?? 'model_has_roles';
        $roleHasPermissionsTable = $tableNames['role_has_permissions'] ?? 'role_has_permissions';
        $rolePivotKey = $columnNames['role_pivot_key'] ?? 'role_id';

        DB::table($roleHasPermissionsTable)
            ->where($rolePivotKey, $sourceRole->id)
            ->get()
            ->each(function ($assignment) use ($roleHasPermissionsTable, $rolePivotKey, $targetRole) {
                $values = (array) $assignment;
                $values[$rolePivotKey] = $targetRole->id;

                DB::table($roleHasPermissionsTable)->insertOrIgnore($values);
            });

        DB::table($modelHasRolesTable)
            ->where($rolePivotKey, $sourceRole->id)
            ->get()
            ->each(function ($assignment) use ($modelHasRolesTable, $rolePivotKey, $targetRole) {
                $values = (array) $assignment;
                $values[$rolePivotKey] = $targetRole->id;

                DB::table($modelHasRolesTable)->insertOrIgnore($values);
            });

        DB::table($modelHasRolesTable)
            ->where($rolePivotKey, $sourceRole->id)
            ->delete();
        DB::table($roleHasPermissionsTable)
            ->where($rolePivotKey, $sourceRole->id)
            ->delete();

        $sourceRole->delete();
    }
};
