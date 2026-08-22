<x-admin-layout>
    @php
        $stageLabels = [
            'pending' => 'Pendiente',
            'receivable' => 'Por Cobrar',
            'history' => 'Historial',
        ];
        $stageClasses = [
            'pending' => 'border-amber-200 bg-amber-50 text-amber-800',
            'receivable' => 'border-blue-200 bg-blue-50 text-blue-800',
            'history' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
        ];
        $fieldLabels = [
            'folio_factura_uuid' => 'folio UUID',
            'folio_interno' => 'folio interno',
            'fecha_facturacion' => 'fecha de facturación',
            'numero_carta_factura' => 'número de carta factura',
            'fecha_carta_factura' => 'fecha de carta factura',
            'fecha_compensacion' => 'fecha de compensación',
        ];
    @endphp

    <div class="mb-4 mt-2">
        <h1 class="text-2xl font-medium text-gray-800">Facturación / Bitácora</h1>
        <p class="mt-1 text-sm text-gray-500">Consulta los movimientos de las remisiones y el usuario que los realizó.</p>
    </div>

    <section class="border border-slate-200 bg-white p-5 shadow-sm">
        <form method="GET" action="{{ route('admin.instituciones.billing.movements') }}"
            class="mb-5 grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-6">
            <div class="xl:col-span-2">
                <label for="movement-search" class="mb-1 block text-sm font-medium text-slate-700">Buscar</label>
                <input id="movement-search" type="search" name="search" value="{{ $search }}"
                    placeholder="Remisión, usuario o identificador..."
                    class="w-full rounded-lg border-slate-200">
            </div>

            <div>
                <label for="movement-from-stage" class="mb-1 block text-sm font-medium text-slate-700">Desde</label>
                <select id="movement-from-stage" name="from_stage" class="w-full rounded-lg border-slate-200">
                    <option value="">Todos</option>
                    @foreach ($stageLabels as $value => $label)
                        <option value="{{ $value }}" @selected($fromStage === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="movement-to-stage" class="mb-1 block text-sm font-medium text-slate-700">Hacia</label>
                <select id="movement-to-stage" name="to_stage" class="w-full rounded-lg border-slate-200">
                    <option value="">Todos</option>
                    @foreach ($stageLabels as $value => $label)
                        <option value="{{ $value }}" @selected($toStage === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="movement-date-from" class="mb-1 block text-sm font-medium text-slate-700">Fecha inicial</label>
                <input id="movement-date-from" type="date" name="date_from" value="{{ $dateFrom }}"
                    class="w-full rounded-lg border-slate-200">
            </div>

            <div>
                <label for="movement-date-to" class="mb-1 block text-sm font-medium text-slate-700">Fecha final</label>
                <input id="movement-date-to" type="date" name="date_to" value="{{ $dateTo }}"
                    class="w-full rounded-lg border-slate-200">
            </div>

            <div class="flex gap-3 md:col-span-2 xl:col-span-6">
                <button type="submit"
                    class="inline-flex min-w-[140px] items-center justify-center rounded-lg bg-azul-prodifem px-4 py-2 text-sm font-semibold text-white hover:bg-blue-800">
                    Filtrar
                </button>
                <a href="{{ route('admin.instituciones.billing.movements') }}"
                    class="inline-flex min-w-[140px] items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Limpiar
                </a>
            </div>
        </form>

        <div class="overflow-x-auto border border-slate-300">
            <table class="w-full min-w-[1050px] border-collapse text-left text-xs text-slate-800">
                <thead class="bg-slate-100 uppercase text-slate-700">
                    <tr>
                        <th class="border border-slate-300 px-3 py-2 font-semibold">Fecha y hora</th>
                        <th class="border border-slate-300 px-3 py-2 font-semibold">Remisión</th>
                        <th class="border border-slate-300 px-3 py-2 font-semibold">Tipo</th>
                        <th class="border border-slate-300 px-3 py-2 font-semibold">Movimiento</th>
                        <th class="border border-slate-300 px-3 py-2 font-semibold">Usuario</th>
                        <th class="border border-slate-300 px-3 py-2 font-semibold">Detalle</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($movements as $movement)
                        @php
                            $clearedFields = collect($movement->details['cleared_fields'] ?? [])
                                ->map(fn ($field) => $fieldLabels[$field] ?? $field)
                                ->values();
                        @endphp
                        <tr class="bg-white hover:bg-blue-50/40">
                            <td class="whitespace-nowrap border border-slate-200 px-3 py-2">
                                <div>{{ $movement->created_at?->format('d/m/Y') }}</div>
                                <div class="mt-0.5 text-slate-500">{{ $movement->created_at?->format('H:i:s') }}</div>
                            </td>
                            <td class="whitespace-nowrap border border-slate-200 px-3 py-2 font-semibold">
                                {{ $movement->remision ?: '—' }}
                            </td>
                            <td class="border border-slate-200 px-3 py-2">
                                {{ $movement->origen_tipo === 'oncologica_mezcla' ? 'Oncológica' : 'Nutricional' }}
                                <span class="text-slate-400">#{{ $movement->origen_id }}</span>
                            </td>
                            <td class="border border-slate-200 px-3 py-2">
                                <div class="flex items-center gap-2 whitespace-nowrap">
                                    <span class="rounded-full border px-2 py-1 font-semibold {{ $stageClasses[$movement->from_stage] ?? 'border-slate-200 bg-slate-50 text-slate-700' }}">
                                        {{ $stageLabels[$movement->from_stage] ?? $movement->from_stage }}
                                    </span>
                                    <span aria-hidden="true" class="text-slate-400">&rarr;</span>
                                    <span class="rounded-full border px-2 py-1 font-semibold {{ $stageClasses[$movement->to_stage] ?? 'border-slate-200 bg-slate-50 text-slate-700' }}">
                                        {{ $stageLabels[$movement->to_stage] ?? $movement->to_stage }}
                                    </span>
                                </div>
                            </td>
                            <td class="border border-slate-200 px-3 py-2">
                                {{ $movement->user_name ?: ($movement->user?->username ?? 'Sistema') }}
                            </td>
                            <td class="border border-slate-200 px-3 py-2 text-slate-600">
                                @if ($clearedFields->isNotEmpty())
                                    Se limpiaron: {{ $clearedFields->join(', ') }}.
                                @elseif (($movement->details['source'] ?? '') === 'billing_update')
                                    Cambio generado durante la actualización de facturación.
                                @else
                                    Movimiento de facturación.
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="border border-slate-200 px-3 py-8 text-center text-slate-400">
                                No hay movimientos registrados para los filtros seleccionados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $movements->links() }}
        </div>
    </section>
</x-admin-layout>
