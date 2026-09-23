<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictCapacitacionAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user?->hasAnyRole(['Cliente', 'Institucion'])) {
            abort_if($request->routeIs('admin.capacitaciones.*', 'admin.users.*'), 403);

            return $next($request);
        }

        $usesTrainingCredentials = $request->session()->get('access_context') === 'training';

        if (($usesTrainingCredentials || ($user?->hasRole('Capacitacion') && ! $user->isSalesperson()))
            && ! $request->routeIs('admin.capacitaciones.*')) {
            return redirect()->route('admin.capacitaciones.index');
        }

        if ($user?->hasSalesOnlyAccess()
            && ! $request->routeIs('admin.solicitudes.cotizacion.*', 'admin.capacitaciones.index', 'admin.capacitaciones.programas')) {
            return redirect()->route($usesTrainingCredentials ? 'admin.capacitaciones.index' : 'admin.solicitudes.cotizacion.index');
        }

        if ($user?->hasRole('Administracion y facturacion')
            && ! $request->routeIs([
                'admin.instituciones.reportes',
                'admin.instituciones.exportar*',
                'admin.instituciones.billing.*',
            ])) {
            return redirect()->route('admin.instituciones.billing.index');
        }

        return $next($request);
    }
}
