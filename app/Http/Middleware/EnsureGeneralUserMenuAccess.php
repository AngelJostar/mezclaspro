<?php

namespace App\Http\Middleware;

use App\Support\AdminMenuAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGeneralUserMenuAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user?->hasRole(AdminMenuAccess::GENERAL_ROLE)) {
            return $next($request);
        }

        $permission = AdminMenuAccess::requiredPermission($request);

        if ($permission === null || ($permission !== '__deny__' && $user->can($permission))) {
            return $next($request);
        }

        abort(403, 'No tienes permiso para acceder a esta seccion.');
    }
}
