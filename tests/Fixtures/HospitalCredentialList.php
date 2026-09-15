<?php

namespace Tests\Fixtures;

use App\Models\Hospital;
use App\Models\User;
use App\View\Components\AdminLayout;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\Component;
use Spatie\Permission\Models\Role;

class HospitalCredentialList extends Component
{
    public static function seed(): User
    {
        if (DB::getDriverName() !== 'sqlite' || DB::connection()->getDatabaseName() !== ':memory:') {
            throw new \RuntimeException('Hospital credential fixtures require an in-memory database.');
        }
        foreach ([
            '2024_01_10_035338_create_hospitals_table.php',
            '2026_02_06_201859_create_clientes_table.php',
            '2026_02_06_214504_create_cliente_hospital_table.php',
            '2024_04_11_013606_create_permission_tables.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->nullable();
            $table->string('password')->nullable();
            $table->text('credential_password')->nullable();
            $table->string('training_username')->nullable();
            $table->string('training_password')->nullable();
            $table->text('training_credential_password')->nullable();
            $table->foreignId('hospital_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        foreach (['Super Admin', 'Admin', 'Cliente', 'Institucion', 'Capacitacion'] as $role) {
            Role::create(['name' => $role, 'guard_name' => 'web']);
        }
        $manager = User::create(['name' => 'Administrador de prueba', 'username' => 'gestor']);
        $manager->assignRole('Super Admin');
        auth()->login($manager);
        app()->bind(AdminLayout::class, fn () => new self());
        view()->share('errors', new ViewErrorBag());

        return $manager;
    }

    public static function hospital(string $name = 'Hospital de prueba'): Hospital
    {
        return Hospital::create(['name' => $name, 'adress' => 'Direccion de prueba', 'is_active' => true]);
    }

    public static function account(Hospital $hospital, string $username, string $password = 'clave-prueba', string $role = 'Institucion'): User
    {
        $user = User::create([
            'name' => 'Acceso de prueba', 'username' => $username, 'hospital_id' => $hospital->id,
            'password' => Hash::make($password), 'credential_password' => $password,
        ]);
        $user->assignRole($role);

        return $user;
    }

    public function render(): string
    {
        return '<main class="admin-content">{{ $slot }}</main>@stack("css")@stack("js")';
    }
}
