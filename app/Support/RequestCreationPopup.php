<?php

namespace App\Support;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RequestCreationPopup
{
    public static function completed(Request $request): bool
    {
        $type = $request->routeIs('admin.nutricionales.solicitudes.create')
            ? 'nutricionales'
            : ($request->query('tipo_solicitud') === 'antibioticos' ? 'antibioticos' : 'oncologicos');

        return $request->boolean('request_popup') && session('request_popup_created') === $type;
    }

    public static function saved(string $type): RedirectResponse
    {
        $route = $type === 'nutricionales'
            ? 'admin.nutricionales.solicitudes.create'
            : 'admin.oncologicos.solicitudes.create';

        return redirect()->route($route, ['request_popup' => 1, 'tipo_solicitud' => $type])
            ->with('request_popup_created', $type);
    }

    public static function result(?string $error = null): View
    {
        return view('admin.solicitudes._creation-result', ['creationError' => $error]);
    }
}
