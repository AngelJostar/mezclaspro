<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $blockedByOrganization = $user?->isBlockedByOrganization() ?? false;

        if (! $user || ($user->is_active && ! $blockedByOrganization)) {
            return $next($request);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $blockedByOrganization
                    ? User::INSTITUTION_BLOCKED_MESSAGE
                    : 'Este acceso se encuentra bloqueado.',
            ], 403);
        }

        return redirect()->route('login')->withErrors([
            'username' => $blockedByOrganization
                ? User::INSTITUTION_BLOCKED_MESSAGE
                : 'Este acceso se encuentra bloqueado. Contacta al administrador.',
        ]);
    }
}
