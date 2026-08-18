<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Role::query()->firstOrCreate([
            'name' => 'Administracion y facturacion',
            'guard_name' => 'web',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Role::query()
            ->where('name', 'Administracion y facturacion')
            ->where('guard_name', 'web')
            ->first()
            ?->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
