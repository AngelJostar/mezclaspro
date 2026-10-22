<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureQuotationServiceEnabled
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_if($request->user() && ! $request->user()->canUseQuotationRequests(), 403,
            'El servicio Solicitud de cotización está inhabilitado para tu hospital.');

        return $next($request);
    }
}
