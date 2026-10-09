@php
    $config = old('configuration', $rule?->configuration ?? []);
    $value = fn ($key, $default = null) => old($key, data_get($config, $key, $default));
    $selected = fn ($key) => (array) old($key, data_get($config, $key, []));
    $currentEngine = old('engine', $rule?->engine ?? $engine);
    $ruleMode = old('rule_mode', ($config['mode'] ?? null) === 'allowed_catalog' ? 'allowed_catalog' : 'single');
    $catalogRows = array_values(old('allowed_compositions', $config['allowed_compositions'] ?? [[
        'code' => 'C01', 'label' => '', 'groups' => [], 'component_terms' => [], 'forbidden_component_terms' => [],
    ]]));
    $catalogRows = array_map(fn ($row) => array_merge([
        'code' => '', 'label' => '', 'groups' => [], 'component_terms' => [], 'forbidden_component_terms' => [],
        '_open' => $errors->any(),
    ], $row), $catalogRows);
@endphp

@if ($errors->any())
    <div class="mb-5 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-800"><p class="font-semibold">Revisa los datos:</p><ul class="mt-2 list-disc ps-5">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
@endif

<div class="space-y-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm"
    @if ($currentEngine === 'composition')
        data-rule-mode="{{ $ruleMode }}"
        data-compositions="{{ json_encode($catalogRows, JSON_UNESCAPED_UNICODE) }}"
        data-group-labels="{{ json_encode($groups, JSON_UNESCAPED_UNICODE) }}"
        x-data="{
            ruleMode: 'single',
            compositions: [],
            groupLabels: {},
            addComposition() {
                const number = String(this.compositions.length + 1).padStart(2, '0');
                this.compositions.push({ code: 'C' + number, label: '', groups: [], component_terms: [], forbidden_component_terms: [], _open: true });
            },
            removeComposition(index) { if (this.compositions.length > 1) this.compositions.splice(index, 1); }
        }"
        x-init="ruleMode = $el.dataset.ruleMode; compositions = JSON.parse($el.dataset.compositions); groupLabels = JSON.parse($el.dataset.groupLabels)"
    @endif>
    <div class="grid gap-5 md:grid-cols-2">
        <label class="block"><span class="text-sm font-semibold text-slate-700">Código</span><input name="code" value="{{ old('code', $rule?->code) }}" required placeholder="NP.ADULTO.COMP.001" class="mt-1 w-full rounded-lg border-slate-300 uppercase"><span class="mt-1 block text-xs text-slate-500">Identificador estable; usa mayúsculas, números, puntos o guiones.</span></label>
        <label class="block"><span class="text-sm font-semibold text-slate-700">Nombre</span><input name="name" value="{{ old('name', $rule?->name) }}" required class="mt-1 w-full rounded-lg border-slate-300"></label>
        <label class="block"><span class="text-sm font-semibold text-slate-700">Motor</span><select name="engine" class="mt-1 w-full rounded-lg border-slate-300" {{ $rule ? 'disabled' : '' }}><option value="composition" @selected($currentEngine === 'composition')>Composición</option><option value="mathematical" @selected($currentEngine === 'mathematical')>Matemático</option></select>@if($rule)<input type="hidden" name="engine" value="{{ $currentEngine }}">@endif</label>
        <label class="block"><span class="text-sm font-semibold text-slate-700">Población</span><select name="population" class="mt-1 w-full rounded-lg border-slate-300"><option value="adult" @selected(old('population', $rule?->population) === 'adult')>Adulto</option><option value="pediatric" @selected(old('population', $rule?->population) === 'pediatric')>Pediátrico</option><option value="both" @selected(old('population', $rule?->population ?? 'both') === 'both')>Ambos</option></select></label>
        <label class="block"><span class="text-sm font-semibold text-slate-700">Consecuencia</span><select name="severity" class="mt-1 w-full rounded-lg border-slate-300"><option value="blocking" @selected(old('severity', $rule?->severity ?? 'blocking') === 'blocking')>Bloqueo</option><option value="authorization" @selected(old('severity', $rule?->severity) === 'authorization')>Requiere autorización</option><option value="advisory" @selected(old('severity', $rule?->severity) === 'advisory')>Advertencia</option><option value="information" @selected(old('severity', $rule?->severity) === 'information')>Información</option></select></label>
        <label class="block"><span class="text-sm font-semibold text-slate-700">Estado editorial</span><select name="status" class="mt-1 w-full rounded-lg border-slate-300"><option value="draft" @selected(old('status', $rule?->status ?? 'draft') === 'draft')>Borrador</option><option value="review" @selected(old('status', $rule?->status) === 'review')>Pendiente de revisión química</option><option value="inactive" @selected(old('status', $rule?->status) === 'inactive')>Inactiva</option></select></label>
    </div>
    <label class="block"><span class="text-sm font-semibold text-slate-700">Descripción y fundamento</span><textarea name="description" rows="3" class="mt-1 w-full rounded-lg border-slate-300">{{ old('description', $rule?->description) }}</textarea></label>

    @if ($currentEngine === 'composition')
        <section class="space-y-5 border-t border-slate-200 pt-6">
            <div><h2 class="text-lg font-semibold text-indigo-900">Condiciones de composición</h2><p class="text-sm text-slate-600">Define qué debe contener la mezcla y qué elementos no puede contener.</p></div>
            <label class="block max-w-md"><span class="text-sm font-semibold text-slate-700">Tipo de regla</span><select x-model="ruleMode" class="mt-1 w-full rounded-lg border-slate-300"><option value="single">Regla individual</option><option value="allowed_catalog">Catálogo de composiciones permitidas</option></select></label>
            <input type="hidden" name="rule_mode" value="{{ $ruleMode }}" :value="ruleMode">

            <fieldset x-show="ruleMode === 'single'" x-bind:disabled="ruleMode !== 'single'" x-cloak class="space-y-5">
                <label class="block max-w-md"><span class="text-sm font-semibold text-slate-700">Tipo de coincidencia</span><select name="match_mode" class="mt-1 w-full rounded-lg border-slate-300"><option value="exact" @selected($value('match_mode', 'exact') === 'exact')>Composición exclusiva (solo lo indicado)</option><option value="contains" @selected($value('match_mode') === 'contains')>Debe contenerlo, permite otros componentes</option></select></label>
                <div class="grid gap-6 md:grid-cols-2">
                    <fieldset><legend class="text-sm font-semibold text-slate-700">Grupos obligatorios</legend><div class="mt-2 grid gap-2">@foreach($groups as $key => $label)<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="required_groups[]" value="{{ $key }}" @checked(in_array($key, $selected('required_groups'), true)) class="rounded border-slate-300">{{ $label }}</label>@endforeach</div></fieldset>
                    <fieldset><legend class="text-sm font-semibold text-slate-700">Grupos prohibidos</legend><div class="mt-2 grid gap-2">@foreach($groups as $key => $label)<label class="flex items-center gap-2 text-sm"><input type="checkbox" name="forbidden_groups[]" value="{{ $key }}" @checked(in_array($key, $selected('forbidden_groups'), true)) class="rounded border-slate-300">{{ $label }}</label>@endforeach</div></fieldset>
                </div>
                <div class="grid gap-5 md:grid-cols-2">
                    <label><span class="text-sm font-semibold text-slate-700">Componentes específicos obligatorios</span><select name="required_components[]" multiple size="7" class="mt-1 w-full rounded-lg border-slate-300">@foreach($components as $component)<option value="{{ $component->id }}" @selected(in_array($component->id, array_map('intval', $selected('required_components')), true))>{{ $component->description }} ({{ $component->unidad }})</option>@endforeach</select></label>
                    <label><span class="text-sm font-semibold text-slate-700">Componentes específicos prohibidos</span><select name="forbidden_components[]" multiple size="7" class="mt-1 w-full rounded-lg border-slate-300">@foreach($components as $component)<option value="{{ $component->id }}" @selected(in_array($component->id, array_map('intval', $selected('forbidden_components')), true))>{{ $component->description }} ({{ $component->unidad }})</option>@endforeach</select></label>
                </div>
                <div class="grid items-end gap-5 md:grid-cols-2"><label><span class="text-sm font-semibold text-slate-700">Mínimo de componentes activos</span><input type="number" min="1" max="50" name="minimum_active_components" value="{{ $value('minimum_active_components') }}" class="mt-1 w-full rounded-lg border-slate-300"></label><label class="flex items-center gap-2 pb-2 text-sm"><input type="hidden" name="count_calculated_water" value="0"><input type="checkbox" name="count_calculated_water" value="1" @checked($value('count_calculated_water', false)) class="rounded border-slate-300">Contar el agua calculada automáticamente como parte de la composición</label></div>
            </fieldset>

            <fieldset x-show="ruleMode === 'allowed_catalog'" x-bind:disabled="ruleMode !== 'allowed_catalog'" x-cloak class="space-y-5">
                <div class="rounded-lg border border-indigo-200 bg-indigo-50 p-4 text-sm text-indigo-900">La mezcla cumplirá cuando coincida con cualquiera de las alternativas del catálogo.</div>
                <label class="flex items-center gap-2 text-sm"><input type="hidden" name="allow_any_single_component" value="0"><input type="checkbox" name="allow_any_single_component" value="1" @checked(old('allow_any_single_component', $config['allow_any_single_component'] ?? false)) class="rounded border-slate-300">Permitir cualquier solicitud que contenga un solo componente</label>
                <label class="block"><span class="text-sm font-semibold text-slate-700">Notas para revisión química</span><textarea name="review_notes" rows="2" class="mt-1 w-full rounded-lg border-slate-300">{{ old('review_notes', implode("\n", $config['review_notes'] ?? [])) }}</textarea></label>
                <div class="space-y-4">
                    <template x-for="(composition, index) in compositions" :key="index">
                        <article x-data="{ expanded: composition._open === true }" class="overflow-hidden rounded-xl border border-slate-200 bg-white">
                            <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2"><span class="rounded bg-slate-100 px-2 py-0.5 text-xs font-semibold text-slate-600" x-text="composition.code || ('C' + String(index + 1).padStart(2, '0'))"></span><h3 class="truncate font-semibold text-slate-900" x-text="composition.label || ('Alternativa ' + (index + 1))"></h3></div>
                                    <p class="mt-1 truncate text-xs text-slate-500" x-text="composition.groups.length ? composition.groups.map(group => groupLabels[group] || group).join(' + ') : 'Sin grupos seleccionados'"></p>
                                </div>
                                <div class="flex items-center gap-3"><button type="button" x-on:click="expanded = !expanded" class="rounded-md border border-indigo-200 px-3 py-1.5 text-sm font-semibold text-indigo-700 hover:bg-indigo-50" x-text="expanded ? 'Cerrar' : 'Editar'"></button><button type="button" x-on:click="removeComposition(index)" x-bind:disabled="compositions.length === 1" class="text-sm font-semibold text-red-700 disabled:cursor-not-allowed disabled:opacity-40">Eliminar</button></div>
                            </div>
                            <div x-show="expanded" x-cloak class="border-t border-slate-200 bg-slate-50 p-4">
                            <div class="grid gap-4 md:grid-cols-4">
                                <label><span class="text-xs font-semibold text-slate-700">Código</span><input x-model="composition.code" :name="'allowed_compositions[' + index + '][code]'" x-bind:required="expanded" class="mt-1 w-full rounded-lg border-slate-300 uppercase"></label>
                                <label class="md:col-span-3"><span class="text-xs font-semibold text-slate-700">Nombre descriptivo</span><input x-model="composition.label" :name="'allowed_compositions[' + index + '][label]'" x-bind:required="expanded" class="mt-1 w-full rounded-lg border-slate-300"></label>
                            </div>
                            <fieldset class="mt-4"><legend class="text-xs font-semibold text-slate-700">Grupos que forman esta composición</legend><div class="mt-2 grid gap-2 sm:grid-cols-2 md:grid-cols-3">@foreach($groups as $key => $label)<label class="flex items-center gap-2 text-sm"><input type="checkbox" value="{{ $key }}" x-model="composition.groups" :name="'allowed_compositions[' + index + '][groups][]'" class="rounded border-slate-300">{{ $label }}</label>@endforeach</div></fieldset>
                            <div class="mt-4 grid gap-5 md:grid-cols-2">
                                <fieldset><legend class="text-xs font-semibold text-slate-700">Componentes obligatorios especiales</legend><div class="mt-2 space-y-2">@foreach($componentTerms as $key => $label)<label class="flex items-center gap-2 text-sm"><input type="checkbox" value="{{ $key }}" x-model="composition.component_terms" :name="'allowed_compositions[' + index + '][component_terms][]'" class="rounded border-slate-300">{{ $label }}</label>@endforeach</div></fieldset>
                                <fieldset><legend class="text-xs font-semibold text-slate-700">Componentes prohibidos especiales</legend><div class="mt-2 space-y-2">@foreach($componentTerms as $key => $label)<label class="flex items-center gap-2 text-sm"><input type="checkbox" value="{{ $key }}" x-model="composition.forbidden_component_terms" :name="'allowed_compositions[' + index + '][forbidden_component_terms][]'" class="rounded border-slate-300">{{ $label }}</label>@endforeach</div></fieldset>
                            </div>
                            </div>
                        </article>
                    </template>
                </div>
                <button type="button" x-on:click="addComposition()" class="rounded-lg border border-indigo-300 bg-white px-4 py-2 text-sm font-semibold text-indigo-800 hover:bg-indigo-50">+ Agregar alternativa</button>
            </fieldset>
        </section>
    @else
        <section class="space-y-5 border-t border-slate-200 pt-6">
            <div><h2 class="text-lg font-semibold text-teal-900">Cálculo controlado</h2><p class="text-sm text-slate-600">Selecciona una plantilla segura; el sistema no ejecuta fórmulas escritas libremente.</p></div>
            <div class="grid gap-5 md:grid-cols-2">
                <label><span class="text-sm font-semibold text-slate-700">Plantilla</span><select name="formula_template" class="mt-1 w-full rounded-lg border-slate-300">@foreach($formulas as $key => $label)<option value="{{ $key }}" @selected($value('formula_template', 'direct_value') === $key)>{{ $label }}</option>@endforeach</select></label>
                <label><span class="text-sm font-semibold text-slate-700">Dato de origen</span><select name="source_variable" class="mt-1 w-full rounded-lg border-slate-300">@foreach($variables as $key => $label)<option value="{{ $key }}" @selected($value('source_variable') === $key)>{{ $label }}</option>@endforeach</select></label>
                <label><span class="text-sm font-semibold text-slate-700">Grupo objetivo (si aplica)</span><select name="target_group" class="mt-1 w-full rounded-lg border-slate-300"><option value="">No aplica</option>@foreach($groups as $key => $label)<option value="{{ $key }}" @selected($value('target_group') === $key)>{{ $label }}</option>@endforeach</select></label>
                <label><span class="text-sm font-semibold text-slate-700">Componente objetivo (si aplica)</span><select name="target_component_id" class="mt-1 w-full rounded-lg border-slate-300"><option value="">No aplica</option>@foreach($components as $component)<option value="{{ $component->id }}" @selected((int)$value('target_component_id') === $component->id)>{{ $component->description }}</option>@endforeach</select></label>
                <label><span class="text-sm font-semibold text-slate-700">Comparación</span><select name="operator" class="mt-1 w-full rounded-lg border-slate-300">@foreach(['<' => 'Menor que', '<=' => 'Menor o igual', '>' => 'Mayor que', '>=' => 'Mayor o igual', '==' => 'Igual a', 'between' => 'Dentro del intervalo'] as $key => $label)<option value="{{ $key }}" @selected($value('operator', '<=') === $key)>{{ $label }}</option>@endforeach</select></label>
                <label><span class="text-sm font-semibold text-slate-700">Unidad del resultado</span><input name="unit" value="{{ $value('unit') }}" required placeholder="g/kg/día" class="mt-1 w-full rounded-lg border-slate-300"></label>
                <label><span class="text-sm font-semibold text-slate-700">Valor de comparación</span><input type="number" step="any" name="threshold_value" value="{{ $value('threshold_value') }}" class="mt-1 w-full rounded-lg border-slate-300"><span class="text-xs text-slate-500">Úsalo salvo que la comparación sea un intervalo.</span></label>
                <div class="grid grid-cols-2 gap-3"><label><span class="text-sm font-semibold text-slate-700">Mínimo</span><input type="number" step="any" name="threshold_min" value="{{ $value('threshold_min') }}" class="mt-1 w-full rounded-lg border-slate-300"></label><label><span class="text-sm font-semibold text-slate-700">Máximo</span><input type="number" step="any" name="threshold_max" value="{{ $value('threshold_max') }}" class="mt-1 w-full rounded-lg border-slate-300"></label></div>
            </div>
            <div class="space-y-4"><h3 class="text-sm font-semibold text-slate-700">Aplicar únicamente cuando</h3>
                <div class="grid gap-4 md:grid-cols-4"><label class="text-xs">Edad mínima (días)<input type="number" min="0" name="age_min_days" value="{{ $value('age_min_days') }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></label><label class="text-xs">Edad máxima (días)<input type="number" min="0" name="age_max_days" value="{{ $value('age_max_days') }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></label><label class="text-xs">Peso mínimo (kg)<input type="number" min="0" step="any" name="weight_min_kg" value="{{ $value('weight_min_kg') }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></label><label class="text-xs">Peso máximo (kg)<input type="number" min="0" step="any" name="weight_max_kg" value="{{ $value('weight_max_kg') }}" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></label></div>
                <div class="grid gap-4 md:grid-cols-2"><label class="text-xs">Debe contener el grupo<select name="applicability_required_group" class="mt-1 w-full rounded-lg border-slate-300 text-sm"><option value="">No aplica</option>@foreach($groups as $key => $label)<option value="{{ $key }}" @selected(old('applicability_required_group', data_get($config, 'applicability.required_group')) === $key)>{{ $label }}</option>@endforeach</select></label><label class="text-xs">Grupo clínico del paciente<input name="applicability_patient_group" value="{{ old('applicability_patient_group', data_get($config, 'applicability.patient_group')) }}" placeholder="Ej. neonato o lactante" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></label><label class="text-xs">Condición clínica<input name="applicability_clinical_stage" value="{{ old('applicability_clinical_stage', data_get($config, 'applicability.clinical_stage')) }}" placeholder="Ej. hospitalizado" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></label><label class="text-xs">Grupo de edad descrito por el manual<input name="applicability_age_group" value="{{ old('applicability_age_group', data_get($config, 'applicability.age_group')) }}" placeholder="Ej. 11 a 20 años" class="mt-1 w-full rounded-lg border-slate-300 text-sm"></label></div>
            </div>
            <label class="block"><span class="text-sm font-semibold text-slate-700">Mensaje cuando no cumpla</span><textarea name="failure_message" required rows="3" class="mt-1 w-full rounded-lg border-slate-300" placeholder="Explica qué valor falló, cuál era el límite y qué debe revisar el químico.">{{ $value('failure_message') }}</textarea></label>
        </section>
    @endif

    <div class="flex flex-wrap justify-end gap-3 border-t border-slate-200 pt-5"><a href="{{ route('admin.superadministrator.validation-rules.index') }}" class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700">Cancelar</a><button class="rounded-lg bg-slate-900 px-5 py-2 text-sm font-semibold text-white hover:bg-slate-800">Guardar configuración</button></div>
</div>
