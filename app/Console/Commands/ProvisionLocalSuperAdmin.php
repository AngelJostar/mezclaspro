<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ProvisionLocalSuperAdmin extends Command
{
    protected $signature = 'local:provision-superadmin
        {username=superadmin.local : Nombre de usuario local}
        {--name=Administrador : Nombre visible}
        {--lastname=Local : Apellido visible}';

    protected $description = 'Crea o reactiva un superadministrador local y genera una contrasena temporal';

    public function handle(): int
    {
        $password = Str::password(20, symbols: true);
        $role = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'web']);
        $user = User::query()->updateOrCreate(
            ['username' => (string) $this->argument('username')],
            [
                'name' => (string) $this->option('name'),
                'lastname' => (string) $this->option('lastname'),
                'password' => Hash::make($password),
                'is_active' => true,
                'hospital_id' => null,
            ]
        );

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->syncRoles([$role]);

        $this->info('Superadministrador local preparado correctamente.');
        $this->line('Usuario: '.$user->username);
        $this->warn('Contrasena temporal: '.$password);
        $this->line('Guardala ahora: no queda almacenada en texto plano y no volvera a mostrarse.');

        return self::SUCCESS;
    }
}
