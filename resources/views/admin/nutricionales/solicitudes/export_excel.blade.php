<table>
    <thead>
        <tr>
            <th>ID</th>
            <th>Remisión</th>
            <th>Lote</th>
            <th>Hospital</th>
            <th>Paciente</th>
            <th>Servicio</th>
            <th>Registro</th>
            <th>Diagnostico</th>
            <th>Edad</th>
            <th>Sexo</th>
            <th>Peso</th>
            <th>Sobrellenado</th>
            <th>Volumen total</th>
            <th>NPT</th>
            <th>Fecha de la solicitud</th>
            <th>Nombre del Médico</th>
            <th>Cedula profesional</th>
            <th>Observaciones</th>
            <th>Estatus</th>
            <th>Aminoácidos pediátricos 10% </th>
            <th>Cloruro de Sodio 0.9%</th>
            <th>Dextrosa 50%</th>
            <th>MCT/LCT 10%</th>
            <th>MCT/LCT 20%</th>
            <th>SMOF 20%</th>
            <th>Cloruro de Sodio (3 mEq/mL Na)</th>
            <th>Acetato de Sodio (4 mEq/mL)</th>
            <th>Fosfato de Sodio (4 mEq/mL)</th>
            <th>Sulfato de Magnesio (0.81 mEq/mL) </th>
            <th>Cloruro de Potasio (4 mEq/mL)</th>
            <th>Acetato de Potasio (2 mEq/mL)</th>
            <th>Fosfato de Potasio (2 mEq/mL)</th>
            <th>Gluconato de Calcio (0.465 mEq/mL)</th>
            <th>Ácidos Grasos Omega 3 10%</th>
            <th>Albúmina 25% (0.25 g/mL)</th>
            <th>Albúmina 20% (0.20 g/mL)</th>
            <th>Glutamina 20%</th>
            <th>Cromo (4 mcg/mL)</th>
            <th>Heparina (1000 UI/mL)</th>
            <th>L-Carnitina (200 mg/mL) Insulina (100 UI/mL) </th>
            <th>Manganeso (100 mcg/mL)</th>
            <th>MVI pediátrico</th>
            <th>Oligoelementos Nulanza</th>
            <th>Oligoelementos Tracefusin</th>
            <th>Ácido Folínico (12.5 mg/mL)</th>
            <th>Selenio (40 mcg/mL)</th>
            <th>Vitamina C (100 mg/mL)</th>
            <th>Vitamina K (10 mg/mL)</th>
            <th>Zinc (1 mg/mL)</th>
            <th>L-Cisteina (50mg/mL)</th>
        </tr>
    </thead>
    <tbody>
        @foreach ($solicitudes as $solicitud)
            <tr>
                <td>{{ $solicitud->id }}</td>

                {{-- Remisión --}}
                <td class="px-6 py-4" style="text-align: center">
                    {{ $solicitud->remision ?? '' }}
                </td>

                {{-- Lote --}}
                <td class="px-6 py-4">
                    {{ $solicitud->lote ?? '' }}
                </td>
                <td>{{ $solicitud->user->hospital->name ?? '' }}</td>
                <td>{{ $solicitud->solicitud_patient->nombre_paciente ?? '' }}
                    {{ $solicitud->solicitud_patient->apellidos_paciente ?? '' }}</td>
                <td>{{ $solicitud->solicitud_patient->servicio ?? '' }}</td>
                <td>{{ $solicitud->solicitud_patient->registro ?? '' }}</td>
                <td>{{ $solicitud->solicitud_patient->diagnostico ?? '' }}</td>
                <td>{{ $solicitud->solicitud_patient->edad ?? '' }}</td>
                <td>{{ $solicitud->solicitud_patient->sexo ?? '' }}</td>
                <td>{{ $solicitud->solicitud_patient->peso ?? '' }} kg</td>
                <td>{{ $solicitud->solicitud_detail->sobrellenado_ml ?? '' }}</td>
                <td>{{ $solicitud->solicitud_detail->volumen_total ?? '' }}</td>
                <td>{{ $solicitud->solicitud_detail->npt ?? '' }}</td>
                <td>{{ $solicitud->created_at ? $solicitud->created_at->translatedFormat('d \d\e F \d\e Y') : '' }}
                </td>
                <td>{{ $solicitud->solicitud_detail->nombre_medico ?? '' }}</td>
                <td>{{ $solicitud->solicitud_detail->cedula ?? '' }}</td>
                <td>{{ $solicitud->solicitud_detail->observaciones ?? '' }}</td>
                <td class="px-6 py-4">
                    @if ($solicitud->estado == 'aprobada')
                        <div>
                            Aprobada
                        </div>
                    @elseif ($solicitud->estado == 'preparada')
                        <div>
                            Preparada
                        </div>
                    @elseif ($solicitud->estado == 'revisada')
                        <div>
                            Revisada
                        </div>
                    @elseif ($solicitud->estado == 'cancelada')
                        <div>
                            Cancelada
                        </div>
                    @elseif ($solicitud->estado == 'pendiente')
                        <div>
                            Pendiente
                        </div>
                    @endif
                </td>
                <td>
                    @foreach ($solicitud->input as $input)
                        @if ($input->input_id == 5)
                            {{ $input->valor_ml }}
                        @endif
                    @endforeach
                </td>
                <td>
                    @foreach ($solicitud->input as $input)
                        @if ($input->input_id == 36)
                            {{ $input->valor_ml }}
                        @endif
                    @endforeach
                </td>
                <td>
                    @foreach ($solicitud->input as $input)
                        @if ($input->input_id == 8)
                            {{ $input->valor_ml }}
                        @endif
                    @endforeach
                </td>
                <td>
                    @foreach ($solicitud->input as $input)
                        @if ($input->input_id == 46)
                            {{ $input->valor_ml }}
                        @endif
                    @endforeach
                </td>
                <td>
                    @foreach ($solicitud->input as $input)
                        @if ($input->input_id == 9)
                            {{ $input->valor_ml }}
                        @endif
                    @endforeach
                </td>
                <td>
                    @foreach ($solicitud->input as $input)
                        @if ($input->input_id == 10)
                            {{ $input->valor_ml }}
                        @endif
                    @endforeach
                </td>
                <td>
                    @foreach ($solicitud->input as $input)
                        @if ($input->input_id == 11)
                            {{ $input->valor_ml }}
                        @endif
                    @endforeach
                </td>
                <td>
                    @foreach ($solicitud->input as $input)
                        @if ($input->input_id == 12)
                            {{ $input->valor_ml }}
                        @endif
                    @endforeach
                </td>
                <td>
                    @foreach ($solicitud->input as $input)
                        @if ($input->input_id == 13)
                            {{ $input->valor_ml }}
                        @endif
                    @endforeach
                </td>
                <td>
                    @foreach ($solicitud->input as $input)
                        @if ($input->input_id == 14)
                            {{ $input->valor_ml }}
                        @endif
                    @endforeach
                </td>
                <td>
                    @foreach ($solicitud->input as $input)
                        @if ($input->input_id == 15)
                            {{ $input->valor_ml }}
                        @endif
                    @endforeach
                </td>
                <td>
                    @foreach ($solicitud->input as $input)
                        @if ($input->input_id == 16)
                            {{ $input->valor_ml }}
                        @endif
                    @endforeach
                </td>
                <td>
                    @foreach ($solicitud->input as $input)
                        @if ($input->input_id == 17)
                            {{ $input->valor_ml }}
                        @endif
                    @endforeach
                </td>
                <td>
                    @foreach ($solicitud->input as $input)
                        @if ($input->input_id == 18)
                            {{ $input->valor_ml }}
                        @endif
                    @endforeach
                </td>
                <td>
                    @foreach ($solicitud->input as $input)
                        @if ($input->input_id == 19)
                            {{ $input->valor_ml }}
                        @endif
                    @endforeach
                </td>
                <td>
                    @foreach ($solicitud->input as $input)
                        @if ($input->input_id == 20)
                            {{ $input->valor_ml }}
                        @endif
                    @endforeach
                </td>
                <td>
                    @foreach ($solicitud->input as $input)
                        @if ($input->input_id == 21)
                            {{ $input->valor_ml }}
                        @endif
                    @endforeach
                </td>
                <td>
                    @foreach ($solicitud->input as $input)
                        @if ($input->input_id == 22)
                            {{ $input->valor_ml }}
                        @endif
                    @endforeach
                </td>
                <td>
                    @foreach ($solicitud->input as $input)
                        @if ($input->input_id == 23)
                            {{ $input->valor_ml }}
                        @endif
                    @endforeach
                </td>
                <td>
                    @foreach ($solicitud->input as $input)
                        @if ($input->input_id == 24)
                            {{ $input->valor_ml }}
                        @endif
                    @endforeach
                </td>
                <td>
                    @foreach ($solicitud->input as $input)
                        @if ($input->input_id == 25)
                            {{ $input->valor_ml }}
                        @endif
                    @endforeach
                </td>
                <td>
                    @foreach ($solicitud->input as $input)
                        @if ($input->input_id == 27)
                            {{ $input->valor_ml }}
                        @endif
                    @endforeach
                </td>
                <td>
                    @foreach ($solicitud->input as $input)
                        @if ($input->input_id == 44)
                            {{ $input->valor_ml }}
                        @endif
                    @endforeach
                </td>

                <td>
                    @foreach ($solicitud->input as $input)
                        @if ($input->input_id == 29)
                            {{ $input->valor_ml }}
                        @endif
                    @endforeach
                </td>

                <td>
                    @foreach ($solicitud->input as $input)
                        @if ($input->input_id == 47)
                            {{ $input->valor_ml }}
                        @endif
                    @endforeach
                </td>

                <td>
                    @foreach ($solicitud->input as $input)
                        @if ($input->input_id == 30)
                            {{ $input->valor_ml }}
                        @endif
                    @endforeach
                </td>

                <td>
                    @foreach ($solicitud->input as $input)
                        @if ($input->input_id == 31)
                            {{ $input->valor_ml }}
                        @endif
                    @endforeach
                </td>

                <td>
                    @foreach ($solicitud->input as $input)
                        @if ($input->input_id == 32)
                            {{ $input->valor_ml }}
                        @endif
                    @endforeach
                </td>

                <td>
                    @foreach ($solicitud->input as $input)
                        @if ($input->input_id == 33)
                            {{ $input->valor_ml }}
                        @endif
                    @endforeach
                </td>

                <td>
                    @foreach ($solicitud->input as $input)
                        @if ($input->input_id == 34)
                            {{ $input->valor_ml }}
                        @endif
                    @endforeach
                </td>

                <td>
                    @foreach ($solicitud->input as $input)
                        @if ($input->input_id == 35)
                            {{ $input->valor_ml }}
                        @endif
                    @endforeach
                </td>

            </tr>
        @endforeach
    </tbody>
</table>
