<?php

namespace App\Http\Middleware;

use App\Services\Agents\AgentAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCanEditValidationRules
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            $request->user() && app(AgentAccess::class)->canEditValidationRules($request->user()),
            403,
            'No tienes autorización para editar las reglas del motor.'
        );

        return $next($request);
    }
}
