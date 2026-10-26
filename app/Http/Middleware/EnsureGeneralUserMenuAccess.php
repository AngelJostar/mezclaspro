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

        if ($user?->isSalesperson() && $request->routeIs('admin.solicitudes.cotizacion.*')) {
            return $next($request);
        }

        if (! $user?->hasRole(AdminMenuAccess::GENERAL_ROLE)) {
            return $next($request);
        }

        $permission = AdminMenuAccess::requiredPermission($request);

        // An explicit assignment grants access to the employee's own learner area, not to administration.
        if ($request->routeIs('admin.capacitaciones.index', 'admin.capacitaciones.programas', 'admin.capacitaciones.asignaciones.*', 'admin.capacitaciones.continua.*')
            && \App\Models\TrainingAssignment::where('user_id', $user->id)->exists()) {
            return $next($request);
        }

        if ($permission === null || ($permission !== '__deny__' && $user->can($permission))) {
            return $next($request);
        }

        abort(403, 'No tienes permiso para acceder a esta seccion.');
    }
}
