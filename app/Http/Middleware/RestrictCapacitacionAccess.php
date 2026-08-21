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

        $usesTrainingCredentials = $request->session()->get('access_context') === 'training';

        if (($usesTrainingCredentials || $user?->hasRole('Capacitacion'))
            && ! $request->routeIs('admin.capacitaciones.*')) {
            return redirect()->route('admin.capacitaciones.index');
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
