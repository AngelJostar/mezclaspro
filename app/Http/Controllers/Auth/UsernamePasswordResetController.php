<?php

namespace App\Http\Controllers\Auth;

use App\Models\User;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Contracts\Support\Responsable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\CompletePasswordReset;
use Laravel\Fortify\Contracts\PasswordResetResponse;
use Laravel\Fortify\Contracts\ResetsUserPasswords;
use Laravel\Fortify\Http\Controllers\NewPasswordController;

class UsernamePasswordResetController extends NewPasswordController
{
    public function __construct(StatefulGuard $guard)
    {
        parent::__construct($guard);
        $this->middleware('throttle:6,1')->only('store');
        $this->middleware(function ($request, $next) {
            return $next($request)
                ->header('Cache-Control', 'no-store, private')
                ->header('Referrer-Policy', 'no-referrer');
        });
    }

    public function store(Request $request): Responsable
    {
        $input = $request->validate([
            'username' => ['required', 'string', 'max:255'],
            'token' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string'],
            'password_confirmation' => ['required', 'string'],
        ]);
        $input['username'] = Str::lower(trim($input['username']));

        $status = $this->broker()->reset($input, function (User $user) use ($input) {
            if (! $user->is_active || $user->isBlockedByOrganization()) {
                throw ValidationException::withMessages(['username' => trans(Password::INVALID_TOKEN)]);
            }

            app(ResetsUserPasswords::class)->reset($user, $input);
            app(CompletePasswordReset::class)($this->guard, $user);
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['username' => trans(Password::INVALID_TOKEN)]);
        }

        return app(PasswordResetResponse::class, ['status' => $status]);
    }
}
