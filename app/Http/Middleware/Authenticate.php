<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;

class Authenticate extends Middleware
{
    public function handle($request, \Closure $next, ...$guards)
    {
        return parent::handle($request, function ($request) use ($next) {
            // API tokens must not bypass the institution's web-only, read-only portal.
            abort_if($request->user()?->isInstitutionAccount() && $request->is('api/*'), 403);
            return $next($request);
        }, ...$guards);
    }

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        return $request->expectsJson() ? null : route('login');
    }
}
