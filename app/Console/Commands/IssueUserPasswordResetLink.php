<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class IssueUserPasswordResetLink extends Command
{
    protected $signature = 'users:password-reset-link {username}';

    protected $description = 'Genera desde el servidor un enlace temporal para restablecer el acceso al software';

    public function handle(): int
    {
        $username = Str::lower(trim((string) $this->argument('username')));
        $user = User::whereRaw('LOWER(username) = ?', [$username])->first();

        if (! $user || ! $user->is_active || $user->isBlockedByOrganization()) {
            $this->error('No existe un usuario activo y habilitado con ese nombre.');

            return self::FAILURE;
        }

        $brokerName = config('fortify.passwords');
        $token = Password::broker($brokerName)->createToken($user);

        $this->warn('Enlace privado de un solo uso. Entregar unicamente al titular de la cuenta.');
        $this->line('Vigencia: '.config("auth.passwords.$brokerName.expire").' minutos.');
        $this->line(route('password.reset', ['token' => $token, 'username' => $user->username]));

        return self::SUCCESS;
    }
}
