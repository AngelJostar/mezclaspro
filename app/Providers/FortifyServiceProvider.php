<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Actions\Fortify\UpdateUserProfileInformation;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Fortify;

class FortifyServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Fortify::authenticateUsing(function (Request $request) {
            $user = User::query()
                ->where('username', $request->input('username'))
                ->first();

            if (! $user || ! $user->is_active || ! Hash::check((string) $request->input('password'), $user->password)) {
                return null;
            }

            return $user;
        });

        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserProfileInformationUsing(UpdateUserProfileInformation::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);

        Fortify::authenticateUsing(function (Request $request): ?User {
            $login = trim((string) $request->input(Fortify::username()));
            $password = (string) $request->input('password');

            $request->session()->forget('access_context');

            $softwareUser = User::query()
                ->whereRaw('LOWER(username) = ?', [Str::lower($login)])
                ->first();

            if ($softwareUser && Hash::check($password, $softwareUser->password)) {
                if (! $softwareUser->is_active) {
                    return null;
                }

                if ($softwareUser->isBlockedByOrganization()) {
                    throw ValidationException::withMessages([
                        Fortify::username() => User::INSTITUTION_BLOCKED_MESSAGE,
                    ]);
                }

                return $softwareUser;
            }

            $trainingUser = User::query()
                ->whereNotNull('training_username')
                ->whereRaw('LOWER(training_username) = ?', [Str::lower($login)])
                ->first();

            if ($trainingUser
                && filled($trainingUser->training_password)
                && Hash::check($password, $trainingUser->training_password)) {
                if (! $trainingUser->is_active) {
                    return null;
                }

                if ($trainingUser->isBlockedByOrganization()) {
                    throw ValidationException::withMessages([
                        Fortify::username() => User::INSTITUTION_BLOCKED_MESSAGE,
                    ]);
                }

                $request->session()->put('access_context', 'training');

                return $trainingUser;
            }

            return null;
        });

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });
    }
}
