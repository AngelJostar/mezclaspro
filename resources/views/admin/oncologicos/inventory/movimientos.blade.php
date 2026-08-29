<x-admin-layout>
    @php
        $presentation = $batch->presentation;
        $catalog = $presentation?->catalog;
        $movementLabels = [
            'entrada' => 'Entrada',
            'salida' => 'Salida',
            'ajuste' => 'Ajuste',
            'reserva' => 'Reserva',
            'liberacion' => 'Liberacion',
            'merma' => 'Merma',
        ];
    @endphp

    <div class="space-y-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Movimientos de inventario oncologico</h1>
                <p class="mt-1 text-sm text-gray-500">
                    {{ $catalog?->denominacion ?? 'Medicamento' }} · {{ $presentation?->marca ?? 'Sin marca' }}
                </p>
            </div>
            <a href="{{ route('admin.oncologicos.inventory.index', array_filter([
                'laboratory_id' => $batch->laboratory_id,
                'warehouse_id' => $batch->warehouse_id,
            ])) }}" class="text-sm font-semibold text-blue-700 hover:underline">
                Volver al inventario
            </a>
        </div>

        <section class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg border border-gray-200 bg-white p-4">
                <p class="text-xs font-semibold uppercase text-gray-500">Presentacion</p>
                <p class="mt-2 font-semibold text-gray-900">{{ $presentation?->presentacion ?? 'Sin dato' }}</p>
                <p class="text-sm text-gray-500">{{ number_format((float) ($presentation?->volumen_diluyente ?? 0), 2) }} mL por frasco</p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-4">
                <p class="text-xs font-semibold uppercase text-gray-500">Lote</p>
                <p class="mt-2 font-semibold text-gray-900">{{ $batch->lote }}</p>
                <p class="text-sm text-gray-500">Cad. {{ optional($batch->caducidad)->format('d/m/Y') ?? 'Sin dato' }}</p>
            </div>
            <div class="rounded-lg border border-blue-200 bg-blue-50 p-4">
                <p class="text-xs font-semibold uppercase text-blue-700">Frascos actuales</p>
                <p class="mt-2 text-2xl font-semibold text-blue-800">{{ number_format((float) $batch->stock_actual, 2) }}</p>
                <p class="text-sm text-blue-700">frascos cerrados</p>
            </div>
            <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4">
                <p class="text-xs font-semibold uppercase text-emerald-700">Stock cerrado</p>
                <p class="mt-2 text-2xl font-semibold text-emerald-800">{{ number_format((float) $batch->stock_ml_actual, 2) }}</p>
                <p class="text-sm text-emerald-700">mL en frascos sin abrir</p>
            </div>
        </section>

        <section class="overflow-hidden rounded-lg border border-gray-200 bg-white">
            <div class="border-b border-gray-200 px-5 py-4">
                <h2 class="font-semibold text-gray-900">Historial de movimientos</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-600">
                        <tr>
                            <th class="px-4 py-3 text-left">Fecha</th>
                            <th class="px-4 py-3 text-left">Usuario</th>
                            <th class="px-4 py-3 text-left">Tipo</th>
                            <th class="px-4 py-3 text-right">Cantidad</th>
                            <th class="px-4 py-3 text-right">Stock antes</th>
                            <th class="px-4 py-3 text-right">Stock despues</th>
                            <th class="px-4 py-3 text-left">Referencia</th>
                            <th class="px-4 py-3 text-left">Notas</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($movements as $movement)
                            <tr>
                                <td class="whitespace-nowrap px-4 py-3">{{ optional($movement->created_at)->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3">{{ $movement->user?->username ?? $movement->user?->name ?? 'Sistema' }}</td>
                                <td class="px-4 py-3 font-semibold">{{ $movementLabels[$movement->movement_type] ?? ucfirst($movement->movement_type) }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">{{ number_format((float) $movement->quantity_ml, 2) }} mL</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">{{ number_format((float) $movement->stock_ml_before, 2) }} mL</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right font-semibold">{{ number_format((float) $movement->stock_ml_after, 2) }} mL</td>
                                <td class="px-4 py-3">{{ $movement->reference_type ?: 'Sin referencia' }}{{ $movement->reference_id ? ' #' . $movement->reference_id : '' }}</td>
                                <td class="min-w-64 px-4 py-3 text-gray-600">{{ $movement->notes ?: '-' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="px-4 py-8 text-center text-gray-500">No hay movimientos registrados para este lote.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-gray-200 px-4 py-3">{{ $movements->links() }}</div>
        </section>

        <section class="overflow-hidden rounded-lg border border-gray-200 bg-white">
            <div class="border-b border-gray-200 px-5 py-4">
                <h2 class="font-semibold text-gray-900">Remanentes abiertos del lote</h2>
                <p class="mt-1 text-sm text-gray-500">Se consumen primero mientras conserven estabilidad. Al vencer, dejan de estar disponibles.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50 text-xs uppercase text-gray-600">
                        <tr>
                            <th class="px-4 py-3 text-left">Apertura</th>
                            <th class="px-4 py-3 text-left">Utilizable hasta</th>
                            <th class="px-4 py-3 text-right">Inicial</th>
                            <th class="px-4 py-3 text-right">Disponible</th>
                            <th class="px-4 py-3 text-left">Estado</th>
                            <th class="px-4 py-3 text-left">Origen</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($remainders as $remainder)
                            @php
                                $expired = $remainder->usable_until && $remainder->usable_until->isPast();
                                $available = $remainder->is_active && !$expired && (float) $remainder->current_ml > 0;
                            @endphp
                            <tr>
                                <td class="whitespace-nowrap px-4 py-3">{{ optional($remainder->opened_at)->format('d/m/Y H:i') }}</td>
                                <td class="whitespace-nowrap px-4 py-3">{{ optional($remainder->usable_until)->format('d/m/Y H:i') ?? 'Sin limite configurado' }}</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right">{{ number_format((float) $remainder->initial_ml, 2) }} mL</td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-lg font-semibold {{ $available ? 'text-emerald-700' : 'text-gray-500' }}">{{ number_format((float) $remainder->current_ml, 2) }} mL</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex rounded-full px-2 py-1 text-xs font-semibold {{ $available ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-100 text-gray-700' }}">
                                        {{ $available ? 'Disponible' : ($expired ? 'Vencido' : 'Agotado/descartado') }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">{{ $remainder->opened_for_type ?: 'Sin referencia' }}{{ $remainder->opened_for_id ? ' #' . $remainder->opened_for_id : '' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-4 py-8 text-center text-gray-500">Este lote aun no tiene remanentes abiertos.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-admin-layout>
