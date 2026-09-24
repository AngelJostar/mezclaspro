<?php

namespace App\Http\Controllers\Admin;

use App\Exports\SolicitudValidationsExport;
use App\Http\Controllers\Controller;
use App\Models\Nutricionales\Solicitud as NutritionSolicitud;
use App\Models\Oncologicos\Mezcla;
use App\Models\Oncologicos\SolicitudOnco;
use App\Support\SolicitudStatusFilter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class SolicitudValidationController extends Controller
{
    private const REJECTED_STATES = ['cancelada', 'no_aprobada', 'no-aprobada'];

    public function index(Request $request)
    {
        return view('admin.solicitudes.validaciones', $this->screenData($request));
    }

    public function exportarExcel(Request $request)
    {
        $data = $this->screenData($request);

        return Excel::download(new SolicitudValidationsExport($data['validations'], $data['isHospitalView']), 'validaciones.xlsx');
    }

    private function screenData(Request $request): array
    {
        $user = $request->user();
        $canViewNutrition = $user?->can('nutricionales_solicitudes_index') ?? false;
        $canViewOncology = $user?->can('oncologicos_solicitudes_index') ?? false;
        abort_unless($canViewNutrition || $canViewOncology, 403);

        $selectedType = $request->query('tipo', 'todas');
        if (!in_array($selectedType, ['todas', 'nutricionales', 'oncologicos', 'antibioticos'], true)) {
            $selectedType = 'todas';
        }
        abort_if($selectedType === 'nutricionales' && !$canViewNutrition, 403);
        abort_if(in_array($selectedType, ['oncologicos', 'antibioticos'], true) && !$canViewOncology, 403);

        $statusFilter = SolicitudStatusFilter::normalize($request->query('estado'));
        $isHospitalView = $user->hasAnyRole(['Cliente', 'Institucion']);

        // Demo source: existing rejections, not inferred medical or chemical findings.
        $validations = collect();
        if ($canViewNutrition && in_array($selectedType, ['todas', 'nutricionales'], true)) {
            $query = NutritionSolicitud::query()
                ->with(['hospital', 'solicitud_detail', 'solicitud_patient'])
                ->withExists('inspeccionNutricional')
                ->whereIn('estado', self::REJECTED_STATES);
            if ($isHospitalView) {
                $query->where('user_id', $user->id);
            }
            $validations = $validations->concat($query->get()->map(function (NutritionSolicitud $solicitud) {
                $delivery = $solicitud->solicitud_detail?->fecha_hora_entrega;

                return [
                    'type' => 'nutricionales', 'type_label' => 'Nutricional',
                    'id' => $solicitud->id, 'request_id' => $solicitud->id,
                    'hospital' => $solicitud->hospital?->name ?? 'Sin hospital',
                    'patient' => trim(($solicitud->solicitud_patient?->nombre_paciente ?? '').' '.($solicitud->solicitud_patient?->apellidos_paciente ?? '')) ?: 'Sin paciente',
                    'requested_at' => $solicitud->created_at,
                    'delivery_at' => filled($delivery) ? Carbon::parse($delivery) : null,
                    'status' => str_replace('-', '_', mb_strtolower($solicitud->estado)),
                    'lot' => $solicitud->lote,
                    'validation_type' => 'Rechazo de solicitud',
                    'observations' => $solicitud->solicitud_detail?->observaciones,
                    'url' => route('admin.nutricionales.solicitudes.show', $solicitud),
                    'document_url' => route('admin.nutricionales.solicitudes.solicitud', $solicitud),
                    'inspection_url' => $solicitud->inspeccion_nutricional_exists
                        ? route('admin.nutricionales.solicitudes.inspeccion', $solicitud) : null,
                ];
            }));
        }

        if ($canViewOncology && in_array($selectedType, ['todas', 'oncologicos', 'antibioticos'], true)) {
            $query = SolicitudOnco::query()
                ->with(['hospital', 'mezclas' => fn ($query) => $query->withExists('inspeccion')->orderBy('id')])
                ->whereIn('tipo_solicitud', $selectedType === 'todas' ? ['oncologicos', 'antibioticos'] : [$selectedType])
                ->where(fn ($query) => $query->whereIn('estado', self::REJECTED_STATES)
                    ->orWhereHas('mezclas', fn ($mixtures) => $mixtures->whereIn('estado', self::REJECTED_STATES)));
            if ($isHospitalView) {
                // A hospital account without an assigned hospital must not see unassigned requests.
                $query->where('hospital_id', $user->hospital_id ?: 0);
            }
            $validations = $validations->concat($query->get()->flatMap(function (SolicitudOnco $solicitud) {
                $mixtures = $solicitud->mezclas->isNotEmpty() ? $solicitud->mezclas : collect([null]);

                return $mixtures->map(function (?Mezcla $mezcla) use ($solicitud) {
                    $mezcla?->setRelation('solicitud', $solicitud);
                    $status = $mezcla?->operational_status ?? $solicitud->estado;
                    if (!in_array($status, self::REJECTED_STATES, true)) {
                        return null;
                    }

                    return [
                        'type' => $solicitud->tipo_solicitud,
                        'type_label' => $solicitud->tipo_solicitud === 'antibioticos' ? 'Antibiótico' : 'Oncológica',
                        'id' => $mezcla?->id, 'request_id' => $solicitud->id,
                        'hospital' => $solicitud->hospital?->name ?? 'Sin hospital',
                        'patient' => $solicitud->nombre_paciente ?: 'Sin paciente',
                        'requested_at' => $solicitud->created_at,
                        'delivery_at' => $mezcla?->fecha_entrega ?? $solicitud->fecha_entrega,
                        'status' => str_replace('-', '_', $status), 'lot' => $mezcla?->lote,
                        'validation_type' => in_array($solicitud->estado, self::REJECTED_STATES, true) ? 'Rechazo de solicitud' : 'Rechazo de mezcla',
                        'observations' => $solicitud->observaciones,
                        'url' => $mezcla ? route('admin.oncologicos.mezclas.show', $mezcla) : route('admin.oncologicos.mezclas.index', $solicitud),
                        'document_url' => route('admin.oncologicos.mezclas.solicitudCompleta', $solicitud),
                        'inspection_url' => $mezcla?->inspeccion_exists ? route('admin.oncologicos.mezclas.inspeccion', $mezcla) : null,
                    ];
                })->filter();
            }));
        }

        $validations = $validations
            ->filter(fn (array $row) => SolicitudStatusFilter::matches($statusFilter, $row['status']))
            ->sortByDesc(fn (array $row) => $row['requested_at']?->getTimestamp() ?? 0)->values();

        return compact('canViewNutrition', 'canViewOncology', 'selectedType', 'statusFilter', 'validations', 'isHospitalView');
    }
}
