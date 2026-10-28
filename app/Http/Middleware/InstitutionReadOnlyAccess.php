<?php

namespace App\Http\Middleware;

use App\Models\Nutricionales\Solicitud;
use App\Models\Oncologicos\Mezcla;
use App\Models\Oncologicos\SolicitudOnco;
use App\Support\InstitutionPortal;
use Closure;
use Illuminate\Http\Request;

class InstitutionReadOnlyAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();
        if (! $user?->isInstitutionAccount()) return $next($request);

        $route = (string) $request->route()?->getName();
        if ($route === 'logout') return $next($request);
        abort_unless($user->hasRole('Institucion'), 403, 'La cuenta debe conservar el perfil de institución.');
        if ($request->isMethod('GET') && ($request->path() === '/' || in_array($route, ['login', 'admin.dashboard'], true))) {
            return redirect()->route('admin.solicitudes.index');
        }
        abort_unless(in_array($request->method(), ['GET', 'HEAD'], true)
            && in_array($route, InstitutionPortal::READ_ROUTES, true), 403, 'El acceso de institución es solo para consulta.');

        $ids = $user->accessibleHospitalIds();
        $key = 'institution_hospital.'.$user->id;
        $selected = $request->has('institution_hospital') ? $request->query('institution_hospital') : $request->session()->get($key);
        if ($request->has('institution_hospital')) {
            abort_unless($selected === null || $selected === '' || (is_scalar($selected)
                && ctype_digit((string) $selected) && in_array((int) $selected, $ids, true)), 404);
        }
        $selected = in_array((int) $selected, $ids, true) ? (int) $selected : null;
        $request->session()->put($key, $selected);
        $request->attributes->set('institution_hospital', $selected);

        $listTypes = [
            'admin.nutricionales.solicitudes.index' => 'nutricionales',
            'admin.oncologicos.solicitudes.index' => 'oncologicos',
            'admin.antibioticos.solicitudes.index' => 'antibioticos',
            'admin.nutricionales.solicitudes.exportar' => 'nutricionales',
            'admin.oncologicos.solicitudes.exportar' => 'oncologicos',
        ];
        if (isset($listTypes[$route])) {
            return redirect()->route(str_ends_with($route, '.exportar') ? 'admin.solicitudes.exportar' : 'admin.solicitudes.index',
                array_merge($request->only('estado'), ['tipo' => $listTypes[$route]]));
        }

        // Scope IDs before controllers that historically assumed a single hospital.
        $parameters = $request->route()->parameters();
        $target = null;
        if (str_starts_with($route, 'admin.nutricionales.solicitudes.') && isset($parameters['solicitud'])) {
            $target = Solicitud::findOrFail($this->id($parameters['solicitud']));
        } elseif ($route === 'admin.oncologicos.mezclas.index') {
            $target = SolicitudOnco::findOrFail($this->id($parameters['mezcla']));
        } elseif (in_array($route, ['admin.oncologicos.mezclas.show', 'admin.oncologicos.mezclas.ordenPreparacion', 'admin.oncologicos.mezclas.inspeccion'], true)) {
            $target = Mezcla::findOrFail($this->id($parameters['mezcla']))->solicitud;
            abort_unless($target, 404);
        } elseif (in_array($route, ['admin.oncologicos.mezclas.solicitudCompleta', 'admin.oncologicos.mezclas.envio'], true)) {
            $target = SolicitudOnco::findOrFail($this->id($parameters['solicitud']));
        }
        if ($target) abort_unless($user->canConsultHospital($target->hospital_id), 404);

        return $next($request);
    }

    private function id($parameter)
    {
        return $parameter instanceof \Illuminate\Database\Eloquent\Model ? $parameter->getKey() : $parameter;
    }
}
