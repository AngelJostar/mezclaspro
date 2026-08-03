<x-admin-layout>
    <div class="flex flex-col">
        <div class="mt-2 mb-4">
            <h1 class="text-2xl font-medium text-gray-800">
                Visualizar Mezcla #{{ $mezcla->id }} - Solicitud #{{ $mezcla->solicitud->id }}
            </h1>
        </div>

        <div class="bg-white rounded-lg p-6 shadow-lg space-y-4">

            <div class="flex items-center gap-3 mb-4">
                <div class="mt-4">
                    <a class="text-white bg-azul-prodifem hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 font-medium rounded-full text-sm px-5 py-2.5"
                        href="{{ route('admin.oncologicos.mezclas.ordenPreparacion', $mezcla) }}" target="_blank">
                        {{ auth()->user()?->hasAnyRole(['Cliente', 'Institucion']) ? 'Trazabilidad' : 'Orden de preparación' }}
                    </a>
                </div>
                <div class="mt-4">
                    <a class="text-white bg-azul-prodifem hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 font-medium rounded-full text-sm px-5 py-2.5"
                        href="{{ route('admin.oncologicos.mezclas.inspeccion', $mezcla) }}" target="_blank">
                        Inspección
                    </a>
                </div>
                @unless (auth()->user()?->hasAnyRole(['Cliente', 'Institucion']))
                    <div class="mt-4">
                        <a class="text-white bg-azul-prodifem hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 font-medium rounded-full text-sm px-5 py-2.5"
                            href="{{ route('admin.oncologicos.mezclas.etiqueta', $mezcla) }}" target="_blank">
                            Etiqueta
                        </a>
                    </div>
                @endunless
            </div>

            {{-- Datos del paciente --}}
            <div class="grid grid-cols-3 gap-4">
                <div>
                    <label class="text-sm font-semibold">Paciente Nombre(s)</label>
                    <p class="border border-gray-300 rounded-md px-2 py-1 bg-gray-100 text-gray-700">
                        {{ $mezcla->solicitud->nombre_paciente }}
                    </p>
                </div>
                <div>
                    <label class="text-sm font-semibold">Servicio</label>
                    <p class="border border-gray-300 rounded-md px-2 py-1 bg-gray-100 text-gray-700">
                        {{ $mezcla->solicitud->servicio }}
                    </p>
                </div>
                <div>
                    <label class="text-sm font-semibold">Registro</label>
                    <p class="border border-gray-300 rounded-md px-2 py-1 bg-gray-100 text-gray-700">
                        {{ $mezcla->solicitud->registro_paciente }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-4 gap-4">
                <div>
                    <label class="text-sm font-semibold">Sexo</label>
                    <p class="border border-gray-300 rounded-md px-2 py-1 bg-gray-100 text-gray-700">
                        {{ $mezcla->solicitud->sexo }}
                    </p>
                </div>
                <div>
                    <label class="text-sm font-semibold">Fecha de nacimiento</label>
                    <p class="border border-gray-300 rounded-md px-2 py-1 bg-gray-100 text-gray-700">
                        {{ $mezcla->solicitud->fecha_nacimiento }}
                    </p>
                </div>
                <div>
                    <label class="text-sm font-semibold">Peso</label>
                    <p class="border border-gray-300 rounded-md px-2 py-1 bg-gray-100 text-gray-700">
                        {{ $mezcla->solicitud->peso }} kg
                    </p>
                </div>
                <div>
                    <label class="text-sm font-semibold">Cama</label>
                    <p class="border border-gray-300 rounded-md px-2 py-1 bg-gray-100 text-gray-700">
                        {{ $mezcla->solicitud->cama }}
                    </p>
                </div>
            </div>

            <div class="grid grid-cols-4 gap-4">
                <div>
                    <label class="text-sm font-semibold">Piso</label>
                    <p class="border border-gray-300 rounded-md px-2 py-1 bg-gray-100 text-gray-700">
                        {{ $mezcla->solicitud->piso }}
                    </p>
                </div>
                <div>
                    <label class="text-sm font-semibold">Fecha de entrega</label>
                    <p class="border border-gray-300 rounded-md px-2 py-1 bg-gray-100 text-gray-700">
                        {{ \Carbon\Carbon::parse($mezcla->solicitud->fecha_entrega)->format('Y-m-d\TH:i') }}
                    </p>
                </div>
                <div>
                    <label class="text-sm font-semibold">Médico</label>
                    <p class="border border-gray-300 rounded-md px-2 py-1 bg-gray-100 text-gray-700">
                        {{ $mezcla->solicitud->nombre_medico }}
                    </p>
                </div>
                <div>
                    <label class="text-sm font-semibold">Cédula</label>
                    <p class="border border-gray-300 rounded-md px-2 py-1 bg-gray-100 text-gray-700">
                        {{ $mezcla->solicitud->cedula_medico }}
                    </p>
                </div>
            </div>

            <div>
                <label class="text-sm font-semibold">Diagnóstico</label>
                <p class="border border-gray-300 rounded-md px-2 py-1 bg-gray-100 text-gray-700">
                    {{ $mezcla->solicitud->diagnostico }}
                </p>
            </div>

            <div>
                <label class="text-sm font-semibold">Observaciones</label>
                <p class="border border-gray-300 rounded-md px-2 py-1 bg-gray-100 text-gray-700">
                    {{ $mezcla->solicitud->observaciones }}
                </p>
            </div>

            {{-- Mezcla visualizada --}}
            <div class="mt-6 border border-gray-300 p-4 rounded-lg">
                <div class="flex items-center justify-between mb-2">
                    <h2 class="text-lg font-semibold">Mezcla #1</h2>

                    {{-- Badge XOR (informativo) --}}
                    <div>
                        @if ($mezcla->set_infusion && !$mezcla->infusor_id)
                            <span class="text-xs px-2 py-1 rounded-full bg-green-100 text-green-700 border border-green-200">
                                Set de infusión activo
                            </span>
                        @elseif(!$mezcla->set_infusion && $mezcla->infusor_id)
                            <span class="text-xs px-2 py-1 rounded-full bg-indigo-100 text-indigo-700 border border-indigo-200">
                                Infusor seleccionado
                            </span>
                        @else
                            <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-700 border border-gray-200">
                                Sin set / infusor
                            </span>
                        @endif
                    </div>
                </div>

                <table class="table-auto w-full text-sm border border-gray-200 mb-4">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border px-4 py-2">Medicamento</th>
                            <th class="border px-4 py-2">Dosis</th>
                            <th class="border px-4 py-2">Diluyente</th>
                            <th class="border px-4 py-2">Vía de administración</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($mezcla->medicamentos as $med)
                            @php
                                // ✅ CAMBIO CLAVE: la llave ahora es mezcla_medicamentos.id
                                $info = $infoAdicional[$med->id] ?? null;

                                // Diluyente: aceptar 'name' o 'denominacion_generica'
                                $diluente = null;
                                if ($info && isset($info['diluyentes'])) {
                                    $dItem = $info['diluyentes']->firstWhere('id', $med->diluyente_id);
                                    $diluente = $dItem->name ?? ($dItem->denominacion_generica ?? null);
                                }

                                // Vía
                                $viaNombre = null;
                                if ($info && isset($info['vias'])) {
                                    $vItem = $info['vias']->firstWhere('id', $med->via_administracion_id);
                                    $viaNombre = $vItem->name ?? null;
                                }
                            @endphp
                            <tr>
                                <td class="border px-4 py-2">{{ $info['denominacion'] ?? '—' }}</td>
                                <td class="border px-4 py-2">{{ $med->dosis }}</td>
                                <td class="border px-4 py-2">{{ $diluente ?? '—' }}</td>
                                <td class="border px-4 py-2">{{ $viaNombre ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>

                {{-- Extras: Set / Infusor / Volumen / Tiempo --}}
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-semibold">Volumen total de dilución (ml)</label>
                        <p class="border border-gray-300 rounded-md px-2 py-1 bg-gray-100 text-gray-700">
                            {{ $mezcla->volumen_dilucion }}
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-semibold">Tiempo de infusión (min)</label>
                        <p class="border border-gray-300 rounded-md px-2 py-1 bg-gray-100 text-gray-700">
                            {{ $mezcla->tiempo_infusion }}
                        </p>
                    </div>

                    <div>
                        <label class="text-sm font-semibold">Set de infusión</label>
                        <p class="border border-gray-300 rounded-md px-2 py-1 bg-gray-100 text-gray-700">
                            {{ $mezcla->set_infusion ? 'Sí' : 'No' }}
                        </p>
                    </div>
                    <div>
                        <label class="text-sm font-semibold">Infusor</label>
                        <p class="border border-gray-300 rounded-md px-2 py-1 bg-gray-100 text-gray-700">
                            @php
                                $inf = optional($mezcla->infusor);
                                $infNombre = $inf->nombre_generico ?: $inf->nombre_comercial;
                            @endphp
                            {{ $infNombre ?? '—' }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="mt-6">
                <a href="{{ route('admin.oncologicos.solicitudes.index') }}" class="text-blue-600 hover:underline">
                    &laquo; Volver
                </a>
            </div>
        </div>
    </div>
</x-admin-layout>

