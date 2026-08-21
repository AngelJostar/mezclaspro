<?php

use App\Support\AdminMenuAccess;
use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Role::query()->firstOrCreate([
            'name' => AdminMenuAccess::GENERAL_ROLE,
            'guard_name' => 'web',
        ]);

        foreach (AdminMenuAccess::permissionNames() as $permission) {
            Permission::query()->firstOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
            ]);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::query()
            ->where('guard_name', 'web')
            ->whereIn('name', AdminMenuAccess::permissionNames())
            ->delete();

        Role::query()
            ->where('name', AdminMenuAccess::GENERAL_ROLE)
            ->where('guard_name', 'web')
            ->first()
            ?->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
