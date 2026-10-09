<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Nutricionales\Input;
use App\Models\ValidationRule;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ValidationRuleController extends Controller
{
    private const GROUPS = [
        'amino_acids' => 'Aminoácidos',
        'dextrose' => 'Dextrosa',
        'lipids' => 'Lípidos',
        'electrolytes' => 'Electrolitos',
        'trace_elements' => 'Elementos traza',
        'vitamins' => 'Vitaminas',
        'water' => 'Agua',
        'saline' => 'Solución salina',
        'medications' => 'Medicamentos',
        'additives' => 'Aditivos',
    ];

    private const COMPONENT_TERMS = [
        'aa_adult_10' => 'Aminoácidos adultos 10%',
        'aa_adult_8_5' => 'Aminoácidos adultos 8.5%',
        'aa_adult_8' => 'Aminoácidos adultos 8%',
        'aa_pediatric_10' => 'Aminoácidos pediátricos 10%',
        'insulin' => 'Insulina',
    ];

    private const VARIABLES = [
        'patient.age_days' => 'Edad del paciente (días)',
        'patient.weight_kg' => 'Peso del paciente (kg)',
        'mixture.total_volume_ml' => 'Volumen total (mL)',
        'mixture.infusion_hours' => 'Tiempo de infusión (h)',
        'group.total_amount' => 'Cantidad total de un grupo',
        'component.amount' => 'Cantidad de un componente',
        'component.concentration' => 'Concentración de un componente',
        'groups.amino_acids.dose_g_kg_day' => 'Aminoácidos prescritos (g/kg/día)',
        'groups.dextrose.dose_g_kg_day' => 'Dextrosa prescrita (g/kg/día)',
        'groups.lipids.dose_g_kg_day' => 'Lípidos prescritos (g/kg/día)',
        'groups.sodium.total_meq' => 'Sodio total (mEq)',
        'groups.potassium.total_meq' => 'Potasio total (mEq)',
        'groups.magnesium.total_meq' => 'Magnesio total (mEq)',
        'groups.calcium_phosphate.total_meq' => 'Calcio y fosfato totales (mEq)',
        'groups.calcium.total_meq' => 'Calcio total (mEq)',
        'groups.phosphate.total_meq' => 'Fosfato total (mEq)',
        'groups.chloride.total_meq' => 'Cloro total (mEq)',
        'groups.acetate.total_meq' => 'Acetato total (mEq)',
        'groups.water.volume_ml' => 'Volumen de agua (mL)',
        'groups.sodium.dose_meq_kg_day' => 'Sodio prescrito (mEq/kg/día)',
        'groups.potassium.dose_meq_kg_day' => 'Potasio prescrito (mEq/kg/día)',
        'groups.magnesium.dose_meq_kg_day' => 'Magnesio prescrito (mEq/kg/día)',
        'groups.calcium.dose_meq_kg_day' => 'Calcio prescrito (mEq/kg/día)',
        'groups.phosphate.dose_meq_kg_day' => 'Fosfato prescrito (mEq/kg/día)',
        'groups.chloride.dose_meq_kg_day' => 'Cloro prescrito (mEq/kg/día)',
        'groups.acetate.dose_meq_kg_day' => 'Acetato prescrito (mEq/kg/día)',
    ];

    private const FORMULAS = [
        'direct_value' => 'Comparar un valor directamente',
        'amount_per_weight' => 'Cantidad ÷ peso',
        'amount_per_weight_per_day' => 'Cantidad ÷ peso ÷ día',
        'percentage_of_volume' => '(Cantidad × 100) ÷ volumen total',
        'amount_per_1000_ml' => '(Cantidad × 1000) ÷ volumen total',
        'dose_times_weight_percentage_of_volume' => '(Dosis × peso × 100) ÷ volumen total',
    ];

    public function index(Request $request)
    {
        $canManage = $request->user()->hasRole('Super Admin');
        $canApprove = $canManage || $request->user()->hasRole('Auxiliar de responsable sanitario');
        $engine = in_array($request->string('engine')->toString(), ValidationRule::ENGINES, true)
            ? $request->string('engine')->toString()
            : null;
        $rules = ValidationRule::query()
            ->with(['updater:id,name,lastname', 'approver:id,name,lastname'])
            ->when($engine, fn ($query) => $query->where('engine', $engine))
            ->orderBy('engine')->orderBy('code')->paginate(25)->withQueryString();

        $indexRouteName = $canManage
            ? 'admin.superadministrator.validation-rules.index'
            : 'admin.validation-rules.index';
        $approveRouteName = $canManage
            ? 'admin.superadministrator.validation-rules.approve'
            : 'admin.validation-rules.approve';

        return view('admin.validation-rules.index', compact(
            'rules', 'engine', 'canManage', 'canApprove', 'indexRouteName', 'approveRouteName'
        ));
    }

    public function create(Request $request)
    {
        $engine = in_array($request->string('engine')->toString(), ValidationRule::ENGINES, true)
            ? $request->string('engine')->toString()
            : 'composition';

        return view('admin.validation-rules.create', $this->formData(compact('engine')));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $data['created_by'] = $request->user()->id;
        $data['updated_by'] = $request->user()->id;
        ValidationRule::create($data);

        return redirect()->route('admin.superadministrator.validation-rules.index')
            ->with('status', 'La regla se guardó como configuración no activa.');
    }

    public function edit(ValidationRule $validationRule)
    {
        if ($validationRule->is_enforced) {
            return redirect()->route('admin.superadministrator.validation-rules.index')
                ->withErrors(['rule' => 'Desactiva la regla antes de modificarla.']);
        }

        return view('admin.validation-rules.edit', $this->formData(compact('validationRule')));
    }

    public function update(Request $request, ValidationRule $validationRule)
    {
        if ($validationRule->is_enforced) {
            throw ValidationException::withMessages([
                'rule' => 'Desactiva la regla antes de modificarla.',
            ]);
        }

        $data = $this->validated($request, $validationRule);
        $data['updated_by'] = $request->user()->id;
        $data['version'] = $validationRule->version + 1;
        $validationRule->update($data);

        return redirect()->route('admin.superadministrator.validation-rules.index')
            ->with('status', 'La regla se actualizó. Continúa sin aplicarse a solicitudes.');
    }

    public function destroy(ValidationRule $validationRule)
    {
        if ($validationRule->is_enforced || $validationRule->approved_at) {
            throw ValidationException::withMessages([
                'rule' => 'Esta regla ya fue activada y no puede eliminarse. Cámbiala a inactiva para conservar la evidencia clínica.',
            ]);
        }
        $validationRule->delete();

        return back()->with('status', 'La regla fue eliminada correctamente.');
    }

    public function approve(Request $request, ValidationRule $validationRule)
    {
        if ($validationRule->status !== 'review' || $validationRule->is_enforced) {
            throw ValidationException::withMessages([
                'rule' => 'Solo pueden aprobarse reglas pendientes de revisión química.',
            ]);
        }

        $validationRule->update([
            'status' => 'active',
            'is_enforced' => true,
            'approved_by' => $request->user()->id,
            'approved_at' => now(),
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('status', 'La regla fue aprobada y activada correctamente.');
    }

    public function deactivate(Request $request, ValidationRule $validationRule)
    {
        if (! $validationRule->is_enforced || $validationRule->status !== 'active') {
            throw ValidationException::withMessages([
                'rule' => 'La regla seleccionada no está activa.',
            ]);
        }

        $validationRule->update([
            'status' => 'inactive',
            'is_enforced' => false,
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('status', 'La regla fue desactivada. Se conservó su historial de aprobación.');
    }

    private function validated(Request $request, ?ValidationRule $rule = null): array
    {
        $base = $request->validate([
            'code' => ['required', 'string', 'max:80', 'regex:/^[A-Z0-9._-]+$/', Rule::unique('validation_rules')->ignore($rule)],
            'name' => ['required', 'string', 'max:255'],
            'engine' => ['required', Rule::in(ValidationRule::ENGINES)],
            'population' => ['required', Rule::in(ValidationRule::POPULATIONS)],
            'severity' => ['required', Rule::in(ValidationRule::SEVERITIES)],
            'status' => ['required', Rule::in(ValidationRule::EDITABLE_STATUSES)],
            'description' => ['nullable', 'string', 'max:3000'],
        ], ['code.regex' => 'El código solo puede contener mayúsculas, números, punto, guion y guion bajo.']);

        $base['is_enforced'] = false;
        $base['configuration'] = $base['engine'] === 'composition'
            ? $this->compositionConfiguration($request, $rule)
            : $this->mathematicalConfiguration($request, $rule);

        return $base;
    }

    private function compositionConfiguration(Request $request, ?ValidationRule $rule = null): array
    {
        $mode = $request->validate([
            'rule_mode' => ['required', Rule::in(['single', 'allowed_catalog'])],
        ])['rule_mode'];

        if ($mode === 'allowed_catalog') {
            return $this->compositionCatalogConfiguration($request);
        }
        $data = $request->validate([
            'match_mode' => ['required', Rule::in(['exact', 'contains'])],
            'required_groups' => ['nullable', 'array'],
            'required_groups.*' => [Rule::in(array_keys(self::GROUPS))],
            'forbidden_groups' => ['nullable', 'array'],
            'forbidden_groups.*' => [Rule::in(array_keys(self::GROUPS))],
            'required_components' => ['nullable', 'array'],
            'required_components.*' => ['integer', 'exists:inputs,id'],
            'forbidden_components' => ['nullable', 'array'],
            'forbidden_components.*' => ['integer', 'exists:inputs,id'],
            'minimum_active_components' => ['nullable', 'integer', 'min:1', 'max:50'],
            'count_calculated_water' => ['nullable', 'boolean'],
        ]);
        $required = array_values(array_unique($data['required_groups'] ?? []));
        $forbidden = array_values(array_unique($data['forbidden_groups'] ?? []));
        if (array_intersect($required, $forbidden)) {
            throw ValidationException::withMessages(['required_groups' => 'Un grupo no puede ser obligatorio y prohibido al mismo tiempo.']);
        }
        $requiredComponents = array_values(array_unique($data['required_components'] ?? []));
        $forbiddenComponents = array_values(array_unique($data['forbidden_components'] ?? []));
        if (array_intersect($requiredComponents, $forbiddenComponents)) {
            throw ValidationException::withMessages(['required_components' => 'Un componente no puede ser obligatorio y prohibido al mismo tiempo.']);
        }

        return [
            'mode' => 'single',
            'match_mode' => $data['match_mode'],
            'required_groups' => $required,
            'forbidden_groups' => $forbidden,
            'required_components' => $requiredComponents,
            'forbidden_components' => $forbiddenComponents,
            'minimum_active_components' => $data['minimum_active_components'] ?? null,
            'count_calculated_water' => $request->boolean('count_calculated_water'),
        ];
    }

    private function compositionCatalogConfiguration(Request $request): array
    {
        $data = $request->validate([
            'allow_any_single_component' => ['nullable', 'boolean'],
            'review_notes' => ['nullable', 'string', 'max:5000'],
            'allowed_compositions' => ['required', 'array', 'min:1', 'max:100'],
            'allowed_compositions.*.code' => ['required', 'string', 'max:40', 'regex:/^[A-Za-z0-9._-]+$/', 'distinct'],
            'allowed_compositions.*.label' => ['required', 'string', 'max:255'],
            'allowed_compositions.*.groups' => ['required', 'array', 'min:1'],
            'allowed_compositions.*.groups.*' => [Rule::in(array_keys(self::GROUPS))],
            'allowed_compositions.*.component_terms' => ['nullable', 'array'],
            'allowed_compositions.*.component_terms.*' => [Rule::in(array_keys(self::COMPONENT_TERMS))],
            'allowed_compositions.*.forbidden_component_terms' => ['nullable', 'array'],
            'allowed_compositions.*.forbidden_component_terms.*' => [Rule::in(array_keys(self::COMPONENT_TERMS))],
        ], [
            'allowed_compositions.required' => 'Agrega al menos una composición permitida.',
            'allowed_compositions.*.groups.required' => 'Cada alternativa debe tener al menos un grupo.',
            'allowed_compositions.*.code.distinct' => 'Los códigos de las alternativas no pueden repetirse.',
        ]);

        $compositions = collect($data['allowed_compositions'])->map(function (array $composition, int $index) {
            $requiredTerms = array_values(array_unique($composition['component_terms'] ?? []));
            $forbiddenTerms = array_values(array_unique($composition['forbidden_component_terms'] ?? []));
            if (array_intersect($requiredTerms, $forbiddenTerms)) {
                throw ValidationException::withMessages([
                    "allowed_compositions.{$index}.component_terms" => 'Un componente no puede ser obligatorio y prohibido en la misma alternativa.',
                ]);
            }

            return [
                'code' => strtoupper($composition['code']),
                'label' => $composition['label'],
                'groups' => array_values(array_unique($composition['groups'])),
                'component_terms' => $requiredTerms,
                'forbidden_component_terms' => $forbiddenTerms,
            ];
        })->values()->all();

        return [
            'mode' => 'allowed_catalog',
            'allow_any_single_component' => $request->boolean('allow_any_single_component'),
            'manual_declared_count' => count($compositions),
            'allowed_compositions' => $compositions,
            'review_notes' => collect(preg_split('/\R/', $data['review_notes'] ?? ''))
                ->map(fn ($note) => trim($note))->filter()->values()->all(),
        ];
    }

    private function mathematicalConfiguration(Request $request, ?ValidationRule $rule = null): array
    {
        $data = $request->validate([
            'formula_template' => ['required', Rule::in(array_keys(self::FORMULAS))],
            'source_variable' => ['required', Rule::in(array_keys(self::VARIABLES))],
            'operator' => ['required', Rule::in(['<', '<=', '>', '>=', '==', 'between'])],
            'threshold_value' => ['nullable', 'numeric'],
            'threshold_min' => ['nullable', 'numeric'],
            'threshold_max' => ['nullable', 'numeric'],
            'unit' => ['required', 'string', 'max:40'],
            'target_group' => ['nullable', Rule::in(array_keys(self::GROUPS))],
            'target_component_id' => ['nullable', 'integer', 'exists:inputs,id'],
            'age_min_days' => ['nullable', 'integer', 'min:0'],
            'age_max_days' => ['nullable', 'integer', 'gte:age_min_days'],
            'weight_min_kg' => ['nullable', 'numeric', 'min:0'],
            'weight_max_kg' => ['nullable', 'numeric', 'gte:weight_min_kg'],
            'applicability_required_group' => ['nullable', Rule::in(array_keys(self::GROUPS))],
            'applicability_patient_group' => ['nullable', 'string', 'max:150'],
            'applicability_clinical_stage' => ['nullable', 'string', 'max:150'],
            'applicability_age_group' => ['nullable', 'string', 'max:150'],
            'failure_message' => ['required', 'string', 'max:1000'],
        ]);
        if ($data['operator'] === 'between' && (!isset($data['threshold_min'], $data['threshold_max']) || $data['threshold_min'] > $data['threshold_max'])) {
            throw ValidationException::withMessages(['threshold_min' => 'Captura un intervalo válido, de menor a mayor.']);
        }
        if ($data['operator'] !== 'between' && !isset($data['threshold_value'])) {
            throw ValidationException::withMessages(['threshold_value' => 'Captura el valor contra el que se comparará el resultado.']);
        }

        $data['applicability'] = array_filter([
            'required_group' => $data['applicability_required_group'] ?? null,
            'patient_group' => $data['applicability_patient_group'] ?? null,
            'clinical_stage' => $data['applicability_clinical_stage'] ?? null,
            'age_group' => $data['applicability_age_group'] ?? null,
        ], fn ($value) => filled($value));
        unset($data['applicability_required_group'], $data['applicability_patient_group'], $data['applicability_clinical_stage'], $data['applicability_age_group']);

        return $data;
    }

    private function formData(array $data): array
    {
        return $data + [
            'groups' => self::GROUPS,
            'componentTerms' => self::COMPONENT_TERMS,
            'variables' => self::VARIABLES,
            'formulas' => self::FORMULAS,
            'components' => Input::query()->where('is_active', true)->orderBy('description')->get(['id', 'description', 'unidad']),
        ];
    }
}
