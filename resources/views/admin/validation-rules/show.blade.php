@php
    $config = $rule->configuration ?? [];
    $labels = [
        'composition' => 'Composición', 'mathematical' => 'Matemático',
        'adult' => 'Adulto', 'pediatric' => 'Pediátrico', 'both' => 'Ambos',
        'blocking' => 'Bloqueo', 'authorization' => 'Requiere autorización',
        'advisory' => 'Advertencia', 'information' => 'Información',
        'draft' => 'Borrador', 'review' => 'En revisión', 'active' => 'Activa', 'inactive' => 'Inactiva',
        'exact' => 'Composición exclusiva', 'contains' => 'Debe contener lo indicado',
    ];
    $operatorLabels = ['<' => 'menor que', '<=' => 'menor o igual que', '>' => 'mayor que', '>=' => 'mayor o igual que', '==' => 'igual a', 'between' => 'dentro del intervalo'];
    $person = fn ($user) => trim(($user?->name ?? '').' '.($user?->lastname ?? '')) ?: 'No disponible';
@endphp

<x-admin-layout>
    <div class="mx-auto max-w-5xl space-y-6 py-4">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <p class="text-sm font-medium text-slate-500">Consulta de regla · Solo lectura</p>
                <h1 class="mt-1 text-2xl font-semibold text-slate-900">{{ $rule->name }}</h1>
                <p class="mt-1 text-sm text-slate-600">{{ $rule->code }} · Versión {{ $rule->version }}</p>
            </div>
            <a href="{{ route('admin.validation-rules.index') }}" class="rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 hover:bg-slate-50">Volver al listado</a>
        </div>

        <section class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <div><p class="text-xs font-semibold uppercase text-slate-500">Motor</p><p class="mt-1 font-medium text-slate-900">{{ $labels[$rule->engine] ?? $rule->engine }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Población</p><p class="mt-1 font-medium text-slate-900">{{ $labels[$rule->population] ?? $rule->population }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Consecuencia</p><p class="mt-1 font-medium text-slate-900">{{ $labels[$rule->severity] ?? $rule->severity }}</p></div>
                <div><p class="text-xs font-semibold uppercase text-slate-500">Estado</p><p class="mt-1"><span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $rule->is_enforced ? 'bg-emerald-100 text-emerald-800' : 'bg-slate-100 text-slate-700' }}">{{ $labels[$rule->status] ?? $rule->status }}</span></p></div>
            </div>
            <div class="mt-6 border-t border-slate-200 pt-5">
                <p class="text-xs font-semibold uppercase text-slate-500">Descripción y fundamento</p>
                <p class="mt-2 whitespace-pre-wrap text-sm text-slate-700">{{ $rule->description ?: 'Sin descripción registrada.' }}</p>
            </div>
        </section>

        @if ($rule->engine === 'mathematical')
            @php
                $operator = $config['operator'] ?? '';
                $criterion = $operator === 'between'
                    ? (($config['threshold_min'] ?? '—').' a '.($config['threshold_max'] ?? '—'))
                    : ($config['threshold_value'] ?? '—');
                $sourceLabel = $variables[$config['source_variable'] ?? ''] ?? ($config['source_variable'] ?? 'Dato evaluado');
                $sourceLabelWithoutUnit = preg_replace('/\s*\([^)]*\)\s*$/u', '', $sourceLabel);
                $operatorSymbols = ['<' => '<', '<=' => '≤', '>' => '>', '>=' => '≥', '==' => '=', 'between' => 'entre'];
                $formulaTemplate = $config['formula_template'] ?? 'direct_value';
                $formulaExpression = $formulaTemplate === 'direct_value'
                    ? $sourceLabelWithoutUnit
                    : $sourceLabelWithoutUnit.' · '.($formulas[$formulaTemplate] ?? $formulaTemplate);
                $readableExpression = $operator === 'between'
                    ? $formulaExpression.' entre '.($config['threshold_min'] ?? '—').' y '.($config['threshold_max'] ?? '—').' '.($config['unit'] ?? '')
                    : $formulaExpression.' '.($operatorSymbols[$operator] ?? $operator).' '.($config['threshold_value'] ?? '—').' '.($config['unit'] ?? '');
                $applicability = array_filter([
                    'Grupo obligatorio' => isset($config['applicability']['required_group']) ? ($groups[$config['applicability']['required_group']] ?? $config['applicability']['required_group']) : null,
                    'Grupo clínico del paciente' => $config['applicability']['patient_group'] ?? null,
                    'Condición clínica' => $config['applicability']['clinical_stage'] ?? null,
                    'Grupo de edad' => $config['applicability']['age_group'] ?? null,
                    'Edad mínima' => isset($config['age_min_days']) ? $config['age_min_days'].' días' : null,
                    'Edad máxima' => isset($config['age_max_days']) ? $config['age_max_days'].' días' : null,
                    'Peso mínimo' => isset($config['weight_min_kg']) ? $config['weight_min_kg'].' kg' : null,
                    'Peso máximo' => isset($config['weight_max_kg']) ? $config['weight_max_kg'].' kg' : null,
                ], fn ($value) => filled($value));
            @endphp
            <section class="rounded-xl border border-teal-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-teal-900">Formulación matemática</h2>
                <div class="mt-5 rounded-xl border border-teal-200 bg-teal-50 px-5 py-4">
                    <p class="text-xs font-semibold uppercase tracking-wide text-teal-700">Expresión legible de la regla</p>
                    <p class="mt-2 text-xl font-bold text-teal-950">{{ $readableExpression }}</p>
                </div>
                <div class="mt-5 grid gap-5 md:grid-cols-2">
                    <div><p class="text-xs font-semibold uppercase text-slate-500">Dato evaluado</p><p class="mt-1 text-slate-900">{{ $variables[$config['source_variable'] ?? ''] ?? ($config['source_variable'] ?? 'No configurado') }}</p></div>
                    <div><p class="text-xs font-semibold uppercase text-slate-500">Fórmula aplicada</p><p class="mt-1 text-slate-900">{{ $formulas[$config['formula_template'] ?? ''] ?? ($config['formula_template'] ?? 'No configurada') }}</p></div>
                    <div><p class="text-xs font-semibold uppercase text-slate-500">Criterio para cumplir</p><p class="mt-1 text-lg font-semibold text-teal-800">{{ $operatorLabels[$operator] ?? $operator }} {{ $criterion }} {{ $config['unit'] ?? '' }}</p></div>
                    <div><p class="text-xs font-semibold uppercase text-slate-500">Objetivo</p><p class="mt-1 text-slate-900">{{ isset($config['target_group']) ? ($groups[$config['target_group']] ?? $config['target_group']) : 'No aplica' }}@if(!empty($config['target_component_id'])) · {{ $componentLabels[$config['target_component_id']] ?? 'Componente #'.$config['target_component_id'] }}@endif</p></div>
                </div>
                <div class="mt-6 rounded-lg border border-amber-200 bg-amber-50 p-4">
                    <p class="text-xs font-semibold uppercase text-amber-800">Mensaje cuando no cumple</p>
                    <p class="mt-1 text-sm text-amber-950">{{ $config['failure_message'] ?? 'Sin mensaje configurado.' }}</p>
                </div>
                <div class="mt-6">
                    <h3 class="font-semibold text-slate-900">Aplicabilidad</h3>
                    @if ($applicability)
                        <dl class="mt-3 grid gap-3 sm:grid-cols-2">@foreach($applicability as $label => $value)<div class="rounded-lg bg-slate-50 p-3"><dt class="text-xs font-semibold text-slate-500">{{ $label }}</dt><dd class="mt-1 text-sm text-slate-900">{{ $value }}</dd></div>@endforeach</dl>
                    @else
                        <p class="mt-2 text-sm text-slate-600">Se aplica a todas las solicitudes de la población indicada.</p>
                    @endif
                </div>
            </section>
        @else
            <section class="rounded-xl border border-indigo-200 bg-white p-6 shadow-sm">
                <h2 class="text-lg font-semibold text-indigo-900">Formulación de composición</h2>
                @if (($config['mode'] ?? 'single') === 'allowed_catalog')
                    <div class="mt-4 flex flex-wrap gap-3 text-sm">
                        <span class="rounded-lg bg-indigo-50 px-3 py-2 text-indigo-900">Catálogo de {{ count($config['allowed_compositions'] ?? []) }} composiciones permitidas</span>
                        @if($config['allow_any_single_component'] ?? false)<span class="rounded-lg bg-emerald-50 px-3 py-2 text-emerald-900">Permite solicitudes de un solo componente</span>@endif
                    </div>
                    <div class="mt-5 space-y-3">
                        @foreach($config['allowed_compositions'] ?? [] as $index => $composition)
                            <details class="overflow-hidden rounded-lg border border-slate-200" @if($index === 0) open @endif>
                                <summary class="cursor-pointer bg-slate-50 px-4 py-3 font-semibold text-slate-900"><span class="mr-2 text-xs text-slate-500">{{ $composition['code'] ?? 'C'.str_pad($index + 1, 2, '0', STR_PAD_LEFT) }}</span>{{ $composition['label'] ?? 'Composición '.($index + 1) }}</summary>
                                <div class="grid gap-4 p-4 md:grid-cols-3">
                                    <div><p class="text-xs font-semibold text-slate-500">Grupos incluidos</p><p class="mt-1 text-sm">{{ collect($composition['groups'] ?? [])->map(fn($group) => $groups[$group] ?? $group)->join(' + ') ?: 'Ninguno' }}</p></div>
                                    <div><p class="text-xs font-semibold text-slate-500">Componentes especiales obligatorios</p><p class="mt-1 text-sm">{{ collect($composition['component_terms'] ?? [])->map(fn($term) => $componentTerms[$term] ?? $term)->join(', ') ?: 'Ninguno' }}</p></div>
                                    <div><p class="text-xs font-semibold text-slate-500">Componentes especiales prohibidos</p><p class="mt-1 text-sm">{{ collect($composition['forbidden_component_terms'] ?? [])->map(fn($term) => $componentTerms[$term] ?? $term)->join(', ') ?: 'Ninguno' }}</p></div>
                                </div>
                            </details>
                        @endforeach
                    </div>
                    @if(!empty($config['review_notes']))<div class="mt-5 rounded-lg bg-amber-50 p-4 text-sm text-amber-950"><p class="font-semibold">Notas para revisión química</p><ul class="mt-2 list-disc pl-5">@foreach($config['review_notes'] as $note)<li>{{ $note }}</li>@endforeach</ul></div>@endif
                @else
                    <div class="mt-5 grid gap-5 md:grid-cols-2">
                        <div><p class="text-xs font-semibold text-slate-500">Tipo de coincidencia</p><p class="mt-1 text-sm">{{ $labels[$config['match_mode'] ?? ''] ?? ($config['match_mode'] ?? 'No configurado') }}</p></div>
                        <div><p class="text-xs font-semibold text-slate-500">Mínimo de componentes activos</p><p class="mt-1 text-sm">{{ $config['minimum_active_components'] ?? 'Sin mínimo' }}</p></div>
                        @foreach(['required_groups' => 'Grupos obligatorios', 'forbidden_groups' => 'Grupos prohibidos'] as $key => $label)
                            <div><p class="text-xs font-semibold text-slate-500">{{ $label }}</p><p class="mt-1 text-sm">{{ collect($config[$key] ?? [])->map(fn($group) => $groups[$group] ?? $group)->join(', ') ?: 'Ninguno' }}</p></div>
                        @endforeach
                        @foreach(['required_components' => 'Componentes obligatorios', 'forbidden_components' => 'Componentes prohibidos'] as $key => $label)
                            <div><p class="text-xs font-semibold text-slate-500">{{ $label }}</p><p class="mt-1 text-sm">{{ collect($config[$key] ?? [])->map(fn($id) => $componentLabels[$id] ?? 'Componente #'.$id)->join(', ') ?: 'Ninguno' }}</p></div>
                        @endforeach
                        <div><p class="text-xs font-semibold text-slate-500">Agua calculada</p><p class="mt-1 text-sm">{{ ($config['count_calculated_water'] ?? false) ? 'Se cuenta como parte de la composición' : 'No se cuenta como parte de la composición' }}</p></div>
                    </div>
                @endif
            </section>
        @endif

        <section class="rounded-xl border border-slate-200 bg-slate-50 p-5 text-sm text-slate-600">
            <h2 class="font-semibold text-slate-900">Trazabilidad</h2>
            <div class="mt-3 grid gap-3 sm:grid-cols-3">
                <p><strong>Creada por:</strong> {{ $person($rule->creator) }}</p>
                <p><strong>Última modificación:</strong> {{ $person($rule->updater) }}</p>
                <p><strong>Aprobación:</strong> @if($rule->approved_at){{ $person($rule->approver) }} · {{ $rule->approved_at->format('d/m/Y H:i') }}@else No aprobada todavía @endif</p>
            </div>
        </section>
    </div>
</x-admin-layout>
