<?php

namespace Tests\Fixtures;

use App\Models\AiAgent;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;

class AgentCenter
{
    public static function seed(int $count = 0): User
    {
        if (DB::getDriverName() !== 'sqlite' || DB::connection()->getDatabaseName() !== ':memory:') {
            throw new \RuntimeException('Agent fixtures require an in-memory database.');
        }
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('username')->nullable();
            $table->timestamps();
        });
        (require database_path('migrations/2024_04_11_013606_create_permission_tables.php'))->up();
        (require database_path('migrations/2026_09_08_000003_create_ai_agents_table.php'))->up();
        $user = User::forceCreate(['name' => 'Administrador de prueba', 'username' => 'agentes-test']);
        $user->assignRole(Role::create(['name' => 'Super Admin', 'guard_name' => 'web']));
        auth()->login($user);
        foreach (range(1, max(1, $count)) as $index) {
            if ($count === 0) break;
            AiAgent::create([
                'name' => 'Agente de prueba '.str_pad($index, 2, '0', STR_PAD_LEFT),
                'description' => 'Descripcion de prueba '.$index,
                'instructions' => 'Instrucciones de prueba '.$index,
            ]);
        }

        return $user;
    }
}
