@php
    $patientGroups = [
        [
            'title' => 'Datos del paciente', 'icon' => 'user-round', 'class' => 'patient-data',
            'fields' => [
                ['name' => 'paciente_nombre', 'label' => 'Paciente Nombre(s)', 'value' => $solicitud->nombre_paciente],
                ['name' => 'registro', 'label' => 'Registro*', 'value' => $solicitud->registro_paciente],
                ['name' => 'sexo', 'label' => 'Sexo', 'value' => $solicitud->sexo, 'type' => 'select'],
                ['name' => 'fecha_nacimiento', 'label' => 'Fecha de Nacimiento', 'type' => 'date',
                    'value' => $solicitud->fecha_nacimiento ? \Carbon\Carbon::parse($solicitud->fecha_nacimiento)->format('Y-m-d') : ''],
                ['name' => 'peso', 'label' => 'Peso*', 'value' => $solicitud->peso],
            ],
        ],
        [
            'title' => 'Atención hospitalaria', 'icon' => 'bed', 'class' => 'hospital-care',
            'fields' => [
                ['name' => 'servicio', 'label' => 'Servicio*', 'value' => $solicitud->servicio],
                ['name' => 'piso', 'label' => 'Piso*', 'value' => $solicitud->piso],
                ['name' => 'cama', 'label' => 'Cama*', 'value' => $solicitud->cama],
                ['name' => 'fecha_entrega', 'label' => 'Fecha de entrega*', 'type' => 'datetime-local',
                    'value' => ($mezcla->fecha_entrega ?? $solicitud->fecha_entrega)
                        ? \Carbon\Carbon::parse($mezcla->fecha_entrega ?? $solicitud->fecha_entrega)->format('Y-m-d\TH:i') : ''],
            ],
        ],
        [
            'title' => 'Información clínica', 'icon' => 'circle-plus', 'class' => 'clinical-data',
            'fields' => [
                ['name' => 'diagnostico', 'label' => 'Diagnóstico', 'value' => $solicitud->diagnostico],
                ['name' => 'medico_nombre', 'label' => 'Nombre del Médico', 'value' => $solicitud->nombre_medico],
                ['name' => 'medico_cedula', 'label' => 'Cédula del Médico', 'value' => $solicitud->cedula_medico],
                ['name' => 'observaciones', 'label' => 'Observaciones', 'value' => $solicitud->observaciones, 'wide' => true],
            ],
        ],
    ];
@endphp

<div class="dispensing-patient" data-dispensing-patient>
    @foreach ($patientGroups as $group)
        <section class="dispensing-patient-section" aria-labelledby="{{ $group['class'] }}-heading">
            <h2 id="{{ $group['class'] }}-heading">
                <i data-patient-icon="{{ $group['icon'] }}" aria-hidden="true"></i>
                {{ $group['title'] }}
            </h2>
            <div class="dispensing-patient-grid {{ $group['class'] }}">
                @foreach ($group['fields'] as $field)
                    <div @class(['dispensing-patient-field', 'is-wide' => $field['wide'] ?? false])>
                        <label for="{{ $field['name'] }}">{{ $field['label'] }}</label>
                        @if (($field['type'] ?? 'text') === 'select')
                            <select name="sexo" id="sexo">
                                <option value="">Seleccione</option>
                                <option value="M" @selected(old('sexo', $field['value']) === 'M')>Masculino</option>
                                <option value="F" @selected(old('sexo', $field['value']) === 'F')>Femenino</option>
                            </select>
                        @else
                            <input type="{{ $field['type'] ?? 'text' }}" name="{{ $field['name'] }}" id="{{ $field['name'] }}"
                                value="{{ old($field['name'], $field['value']) }}">
                        @endif
                    </div>
                @endforeach
            </div>
        </section>
    @endforeach
</div>
