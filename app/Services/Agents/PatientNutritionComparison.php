<?php

namespace App\Services\Agents;

use App\Models\{AiAgent, AiAgentRun, Hospital, User};
use App\Models\Nutricionales\Solicitud;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PatientNutritionComparison
{
    public static function number($value): ?float
    {
        return is_numeric($value) && is_finite((float) $value) && (float) $value >= 0 ? (float) $value : null;
    }

    public static function display($value, int $precision = 3): string
    {
        return $value === null ? 'Sin dato' : rtrim(rtrim(number_format($value, $precision, '.', ','), '0'), '.');
    }

    public function run(AiAgent $agent, User $user, array $input): AiAgentRun
    {
        app(AgentAccess::class)->authorize($user, $agent, 'can_run');
        abort_unless($agent->integration_key === OperationalAgents::NUTRITION, 404);
        if (!$agent->is_active) throw ValidationException::withMessages(['execution' => 'El superadministrador debe activar el agente.']);
        $data = validator($input, [
            'hospital_id' => 'required|integer|exists:hospitals,id', 'registry' => 'nullable|string|max:100',
            'first_name' => 'required_without:registry|nullable|string|max:150', 'last_name' => 'required_without:registry|nullable|string|max:150',
            'birth_date' => 'required_without:registry|nullable|date_format:Y-m-d|before_or_equal:today',
            'from' => 'required|date_format:Y-m-d', 'to' => 'required|date_format:Y-m-d|after_or_equal:from',
        ])->validate();
        $access = app(AgentAccess::class);
        if ($access->isHospitalScoped($user)) abort_unless(in_array((int) $data['hospital_id'], $user->accessibleHospitalIds()), 403);
        $query = Solicitud::with('solicitud_patient', 'solicitud_detail', 'input.input')
            ->forHospitalIds([(int) $data['hospital_id']])->whereIn('estado', ['entregada', 'finalizada'])
            ->whereHas('solicitud_detail', fn ($q) => $q->whereBetween('fecha_hora_entrega', [$data['from'].' 00:00:00', $data['to'].' 23:59:59']))
            ->whereHas('solicitud_patient', function ($q) use ($data) {
                if (!empty($data['registry'])) $q->where('registro', trim($data['registry']));
                else $q->where('nombre_paciente', trim($data['first_name']))->where('apellidos_paciente', trim($data['last_name']))->whereDate('fecha_nacimiento', $data['birth_date']);
            });
        if ((clone $query)->count() > 1000) throw ValidationException::withMessages(['patient' => 'Hay más de 1,000 nutriciones. Reduce las fechas para generar un informe completo.']);
        $requests = $query->get()->sortBy(fn ($row) => $row->solicitud_detail->fecha_hora_entrega.' '.str_pad($row->id, 12, '0', STR_PAD_LEFT))->values();
        if ($requests->isEmpty()) throw ValidationException::withMessages(['patient' => 'No se encontraron nutriciones entregadas con esos datos y fechas.']);
        $identities = $requests->map(fn ($row) => Str::lower(Str::ascii(trim($row->solicitud_patient->nombre_paciente).' '.trim($row->solicitud_patient->apellidos_paciente).'|'.substr($row->solicitud_patient->fecha_nacimiento ?? '', 0, 10))))->unique();
        if ($identities->count() !== 1) throw ValidationException::withMessages(['patient' => 'El expediente coincide con pacientes distintos. Corrige la identificación antes de compararlos.']);
        $report = $this->build($requests);
        $report['hospital'] = Hospital::findOrFail($data['hospital_id'])->name;
        $report['hospital_id'] = (int) $data['hospital_id'];
        $report['from'] = $data['from']; $report['to'] = $data['to'];
        return $agent->runs()->create(['user_id' => $user->id, 'trigger' => 'manual', 'status' => 'completed', 'started_at' => now(), 'finished_at' => now(),
            'configuration' => ['patient' => $data, 'access_hospital_ids' => [(int) $data['hospital_id']], 'activation' => 'manual'],
            'result' => ['nutrition_report' => $report]]);
    }

    public function build($requests): array
    {
        $rows = []; $columns = []; $previous = []; $previousWeight = null; $notes = [];
        foreach ($requests as $request) {
            $patient = $request->solicitud_patient; $detail = $request->solicitud_detail;
            $weight = self::number($patient->peso);
            if ($weight !== null && $weight <= 0) $weight = null;
            $volume = self::number($detail->volumen_total);
            $row = ['id' => $request->id, 'date' => Carbon::parse($detail->fecha_hora_entrega)->format('d/m/Y H:i'),
                'day' => substr($detail->fecha_hora_entrega, 0, 10), 'remission' => $request->remision, 'lot' => $request->lote,
                'weight' => $weight, 'weight_change' => $weight !== null && $previousWeight !== null ? round($weight - $previousWeight, 6) : null,
                'volume' => $volume, 'mode' => $detail->npt, 'route' => $detail->via_administracion,
                'doctor' => $detail->nombre_medico, 'infusion_hours' => $detail->tiempo_infusion_min, 'components' => []];
            foreach ($request->input as $item) {
                $catalog = $item->input;
                if (!$catalog) { $notes[] = 'Solicitud #'.$request->id.': componente sin catálogo; no se pudo identificar su unidad.'; continue; }
                if (in_array((int) $catalog->category_id, [6, 10, 11])) continue;
                $quantity = self::number($item->valor);
                $weighted = $detail->npt === 'INF' && in_array((int) $catalog->category_id, [1, 2, 3, 4, 8]);
                $unit = $catalog->unidad;
                if ($detail->npt === 'ADULT' && in_array((int) $catalog->category_id, [1, 2, 3])) $unit = 'g/día';
                if ($detail->npt === 'ADULT' && (int) $catalog->category_id === 4) $unit = 'mEq/día';
                $total = $quantity === null || ($weighted && $weight === null) ? null : $quantity * ($weighted ? $weight : 1);
                $base = trim(preg_replace('~/(kg|día|dia|day)~iu', '', $unit));
                $concentration = $total !== null && $volume > 0 && in_array(mb_strtolower($base), ['g', 'mg', 'mcg', 'µg', 'meq', 'mmol', 'ui', 'ml'])
                    && in_array($detail->npt, ['INF', 'ADULT']) && ($weighted || !preg_match('~/kg~i', $unit)) ? round($total / $volume * 100, 6) : null;
                $key = $catalog->id.'|'.$unit;
                $columns[$key] = ['label' => $catalog->description, 'unit' => $unit, 'concentration_unit' => $base.'/100 mL'];
                $prior = $previous[$key] ?? null;
                $row['components'][$key] = ['quantity' => $quantity, 'total' => $total, 'volume_ml' => self::number($item->valor_ml),
                    'concentration' => $concentration,
                    'change' => $quantity !== null && ($prior['quantity'] ?? null) !== null ? round($quantity - $prior['quantity'], 6) : null,
                    'concentration_change' => $concentration !== null && ($prior['concentration'] ?? null) !== null ? round($concentration - $prior['concentration'], 6) : null];
            }
            $previous = $row['components']; $previousWeight = $weight; $rows[] = $row;
        }
        $weights = array_values(array_filter(array_column($rows, 'weight'), fn ($value) => $value !== null));
        $firstWeight = $weights[0] ?? null; $lastWeight = $weights ? end($weights) : null;
        $change = $firstWeight !== null ? round($lastWeight - $firstWeight, 6) : null;
        $patient = $requests->first()->solicitud_patient;
        return ['patient' => trim($patient->nombre_paciente.' '.$patient->apellidos_paciente), 'registry' => $patient->registro,
            'birth_date' => $patient->fecha_nacimiento, 'rows' => $rows, 'columns' => $columns,
            'summary' => ['count' => count($rows), 'days' => count(array_unique(array_column($rows, 'day'))),
                'first_weight' => $firstWeight, 'last_weight' => $lastWeight, 'weight_change' => $change,
                'weight_percent' => $firstWeight > 0 ? round($change / $firstWeight * 100, 2) : null,
                'changes' => collect($rows)->sum(fn ($row) => collect($row['components'])->filter(fn ($component) => $component['change'] !== null && abs($component['change']) > 0.000001)->count())],
            'notes' => array_values(array_unique(array_merge($notes, [
                'Se comparan prescripciones de nutriciones registradas como entregadas o finalizadas; esto no acredita su administración al paciente.',
                'Los cambios se calculan respecto de la nutrición anterior, sin sumar recetas del mismo día. Un componente ausente se muestra como Sin dato, no como cero.',
                'Concentración calculada = cantidad total del componente / volumen prescrito × 100. En pediatría se aplica el peso registrado cuando la unidad se prescribe por kg. No incluye sobrellenado.',
                'El peso procede de cada solicitud. Las diferencias describen el registro y no determinan causas, diagnósticos ni cambios de tratamiento.',
                'Los nombres y unidades de componentes corresponden al catálogo disponible al generar este informe.',
            ])))];
    }

    public static function authorizeReport(User $user, AiAgentRun $run): array
    {
        $agent = AiAgent::findOrFail($run->ai_agent_id);
        app(AgentAccess::class)->authorize($user, $agent);
        $report = $run->result['nutrition_report'] ?? null;
        abort_unless($agent->integration_key === OperationalAgents::NUTRITION && $report, 404);
        if (app(AgentAccess::class)->isHospitalScoped($user)) {
            abort_unless(in_array((int) $report['hospital_id'], $user->accessibleHospitalIds()), 403);
        }
        return $report;
    }
}
