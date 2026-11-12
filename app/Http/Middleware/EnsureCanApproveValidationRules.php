<?php

namespace App\Http\Middleware;

use App\Services\Agents\AgentAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureCanApproveValidationRules
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_unless(
            $request->user() && app(AgentAccess::class)->canApproveValidationRules($request->user()),
            403,
            'No tienes autorización para aprobar y activar reglas.'
        );

        return $next($request);
    }
}
