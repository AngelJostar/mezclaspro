<?php

namespace App\Http\Middleware;

use App\Services\Agents\AgentAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCanViewValidationRules
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            $request->user() && app(AgentAccess::class)->canViewValidationRules($request->user()),
            403,
            'No tienes autorización para consultar el motor de validaciones.'
        );

        return $next($request);
    }
}
