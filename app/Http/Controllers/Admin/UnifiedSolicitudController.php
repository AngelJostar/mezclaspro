<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Nutricionales\Solicitud as NutritionSolicitud;
use App\Models\Oncologicos\SolicitudOnco;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class UnifiedSolicitudController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $canViewNutrition = $user->can('nutricionales_solicitudes_index');
        $canViewOncology = $user->can('oncologicos_solicitudes_index');

        abort_unless($canViewNutrition || $canViewOncology, 403);

        $role = $user->roles->first()?->name;
        $requests = collect();

        if ($canViewNutrition) {
            $nutritionQuery = NutritionSolicitud::query()
                ->with(['user.hospital', 'solicitud_detail', 'solicitud_patient']);

            if (in_array($role, ['Cliente', 'Institucion'], true)) {
                $nutritionQuery->where('user_id', $user->id);
            }

            $requests = $requests->concat(
                $nutritionQuery->get()->map(function (NutritionSolicitud $solicitud) {
                    $patient = trim(implode(' ', array_filter([
                        $solicitud->solicitud_patient?->nombre_paciente,
                        $solicitud->solicitud_patient?->apellidos_paciente,
                    ])));

                    return [
                        'type' => 'nutricionales',
                        'type_label' => 'Nutricional',
                        'id' => $solicitud->id,
                        'hospital' => $solicitud->user?->hospital?->name ?? 'Sin hospital',
                        'patient' => $patient ?: 'Sin paciente',
                        'requested_at' => $solicitud->created_at,
                        'delivery_at' => $this->parseDate($solicitud->solicitud_detail?->fecha_hora_entrega),
                        'status' => $solicitud->estado ?: 'Pendiente',
                        'url' => route('admin.nutricionales.solicitudes.edit', $solicitud),
                    ];
                })
            );
        }

        if ($canViewOncology) {
            $oncologyQuery = SolicitudOnco::query()
                ->with(['hospital', 'user'])
                ->whereIn('tipo_solicitud', ['oncologicos', 'antibioticos']);

            if (in_array($role, ['Cliente', 'Institucion'], true)) {
                $oncologyQuery->where('hospital_id', $user->hospital_id);
            }

            $requests = $requests->concat(
                $oncologyQuery->get()->map(function (SolicitudOnco $solicitud) {
                    $type = $solicitud->tipo_solicitud === 'antibioticos'
                        ? 'antibioticos'
                        : 'oncologicos';

                    return [
                        'type' => $type,
                        'type_label' => $type === 'antibioticos' ? 'Antibiotico' : 'Oncologica',
                        'id' => $solicitud->id,
                        'hospital' => $solicitud->hospital?->name ?? 'Sin hospital',
                        'patient' => $solicitud->nombre_paciente ?: 'Sin paciente',
                        'requested_at' => $solicitud->created_at,
                        'delivery_at' => $solicitud->fecha_entrega,
                        'status' => $solicitud->estado ?: 'Pendiente',
                        'url' => route('admin.oncologicos.solicitudes.edit', $solicitud->id),
                    ];
                })
            );
        }

        $search = trim((string) $request->query('buscar'));

        if ($search !== '') {
            $normalizedSearch = Str::lower(Str::ascii($search));
            $requests = $requests->filter(function (array $row) use ($normalizedSearch) {
                $haystack = Str::lower(Str::ascii(implode(' ', [
                    $row['type_label'],
                    $row['id'],
                    $row['hospital'],
                    $row['patient'],
                    $row['status'],
                ])));

                return str_contains($haystack, $normalizedSearch);
            });
        }

        $requests = $requests
            ->sortByDesc(fn (array $row) => $row['requested_at']?->getTimestamp() ?? 0)
            ->values();

        return view('admin.solicitudes.index', compact(
            'requests',
            'search',
            'canViewNutrition',
            'canViewOncology'
        ));
    }

    private function parseDate(mixed $value): ?Carbon
    {
        return filled($value) ? Carbon::parse($value) : null;
    }
}
