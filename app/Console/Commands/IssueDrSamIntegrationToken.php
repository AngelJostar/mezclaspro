<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class IssueDrSamIntegrationToken extends Command
{
    protected $signature = 'integration:issue-drsam-token
        {username=drsam.integration : Usuario tecnico propietario del token}
        {--name=dr-sam-integration : Nombre auditable del token}
        {--provision : Crear el usuario tecnico si no existe}
        {--upgrade-existing : Actualizar las capacidades de los tokens existentes sin revelar ni rotar secretos}';

    protected $description = 'Emite un token de servicio para la integracion controlada con Dr. Sam';

    public function handle(): int
    {
        $username = (string) $this->argument('username');
        $user = User::query()->where('username', $username)->first();

        if (! $user && $this->option('provision')) {
            $user = User::query()->create([
                'name' => 'Dr. Sam',
                'lastname' => 'Integracion',
                'username' => $username,
                'password' => Hash::make(Str::random(64)),
                'is_active' => true,
            ]);
        }

        if (! $user) {
            $this->error('No existe el usuario indicado. Usa --provision para crear la identidad tecnica.');

            return self::FAILURE;
        }

        $abilities = [
            'catalogs:read',
            'requests:prevalidate',
            'requests:create',
            'requests:read',
            'requests:documents',
        ];

        if ($this->option('upgrade-existing')) {
            $updated = $user->tokens()->update(['abilities' => json_encode($abilities)]);
            $this->info("Tokens actualizados: {$updated}.");

            return self::SUCCESS;
        }

        $token = $user->createToken((string) $this->option('name'), $abilities);

        $this->warn('Guarda este token ahora; no volvera a mostrarse:');
        $this->line($token->plainTextToken);

        return self::SUCCESS;
    }
}
