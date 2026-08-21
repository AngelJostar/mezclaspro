<x-admin-layout>
    <div class="mt-2 space-y-4">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h1 class="text-2xl font-medium text-gray-800">
                    Movimientos de inventario
                </h1>
                <p class="mt-1 text-sm text-gray-500">
                    Historial completo de entradas, ajustes, mermas y salidas del lote seleccionado.
                </p>
            </div>

            <a href="{{ route('admin.nutricionales.stocks.index', ['laboratory_id' => $stock->laboratory_id, 'warehouse_id' => $stock->warehouse_id]) }}"
                class="inline-flex items-center justify-center rounded bg-gray-100 px-4 py-2 font-semibold text-gray-700 hover:bg-gray-200">
                Volver al inventario
            </a>
        </div>

        <div class="grid grid-cols-1 gap-4 xl:grid-cols-3">
            <div class="rounded-lg bg-white p-5 shadow">
                <div class="space-y-2 text-sm text-gray-600">
                    <div>
                        Central de mezclas:
                        <span class="font-semibold text-gray-800">
                            {{ $stock->laboratory->nombre ?? '-' }}
                        </span>
                    </div>
                    <div>
                        Medicamento genérico:
                        <span class="font-semibold text-gray-800">
                            {{ $stock->presentation->catalog->denominacion_generica ?? '-' }}
                        </span>
                    </div>
                    <div>
                        Presentación comercial:
                        <span class="font-semibold text-gray-800">
                            {{ $stock->presentation->denominacion_comercial ?? '-' }}
                        </span>
                    </div>
                    <div>
                        Presentación:
                        <span class="font-semibold text-gray-800">
                            {{ $stock->presentation->presentacion ?? '-' }}
                            @if (!is_null($stock->presentation->presentacion_ml))
                                · {{ number_format((float) $stock->presentation->presentacion_ml, 2) }} ml
                            @endif
                        </span>
                    </div>
                </div>
            </div>

            <div class="rounded-lg bg-white p-5 shadow">
                <div class="space-y-2 text-sm text-gray-600">
                    <div>
                        Lote:
                        <span class="font-semibold text-gray-800">
                            {{ $stock->lote ?? '-' }}
                        </span>
                    </div>
                    <div>
                        Caducidad:
                        <span class="font-semibold text-gray-800">
                            {{ $stock->caducidad ? \Carbon\Carbon::parse($stock->caducidad)->format('d/m/Y') : '-' }}
                        </span>
                    </div>
                    <div>
                        Estado:
                        @if ($stock->is_active)
                            <span class="inline-flex items-center rounded-full bg-green-100 px-2.5 py-0.5 text-xs font-semibold text-green-700">
                                Activo
                            </span>
                        @else
                            <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-semibold text-red-700">
                                Inactivo
                            </span>
                        @endif
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3 xl:grid-cols-1">
                <div class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Stock inicial
                    </div>
                    <div class="mt-2 text-2xl font-semibold text-gray-800">
                        {{ number_format((float) $stock->stock_ml_inicial, 2) }}
                    </div>
                    <div class="text-sm text-gray-500">ml</div>
                </div>

                <div class="rounded-lg border border-green-200 bg-white p-4 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Stock actual
                    </div>
                    <div class="mt-2 text-2xl font-semibold text-green-700">
                        {{ number_format((float) $stock->stock_ml_actual, 2) }}
                    </div>
                    <div class="text-sm text-gray-500">ml</div>
                </div>

                <div class="rounded-lg border border-blue-200 bg-white p-4 shadow-sm">
                    <div class="text-xs font-semibold uppercase tracking-wide text-gray-500">
                        Frascos actuales
                    </div>
                    <div class="mt-2 text-2xl font-semibold text-blue-700">
                        {{ number_format((float) $stock->frascos_actuales, 2) }}
                    </div>
                    <div class="text-sm text-gray-500">frascos</div>
                </div>
            </div>
        </div>

        <div class="overflow-hidden rounded-lg bg-white shadow">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Fecha</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Usuario</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Tipo</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600">Cantidad</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600">Stock antes</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600">Stock después</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600">Frascos antes</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-600">Frascos después</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Referencia</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wide text-gray-600">Notas</th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-gray-100 bg-white">
                        @forelse($movements as $movement)
                            @php
                                $tipo = strtolower($movement->tipo ?? '');
                                $badgeClasses = match ($tipo) {
                                    'entrada' => 'bg-green-100 text-green-700',
                                    'salida' => 'bg-blue-100 text-blue-700',
                                    'merma' => 'bg-red-100 text-red-700',
                                    'ajuste' => 'bg-amber-100 text-amber-700',
                                    default => 'bg-gray-100 text-gray-700',
                                };
                            @endphp

                            <tr class="hover:bg-slate-50/80">
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-800">
                                    <div>{{ $movement->created_at?->format('d/m/Y') }}</div>
                                    <div class="text-xs text-gray-500">{{ $movement->created_at?->format('H:i:s') }}</div>
                                </td>

                                <td class="px-4 py-3 text-sm text-gray-800">
                                    {{ $movement->user->name ?? 'Sistema' }}
                                </td>

                                <td class="px-4 py-3 text-sm">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $badgeClasses }}">
                                        {{ ucfirst($movement->tipo ?? '-') }}
                                    </span>
                                </td>

                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-800">
                                    {{ number_format((float) $movement->cantidad_ml, 2) }} ml
                                </td>

                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">
                                    {{ number_format((float) $movement->stock_antes, 2) }} ml
                                </td>

                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-gray-900">
                                    {{ number_format((float) $movement->stock_despues, 2) }} ml
                                </td>

                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-700">
                                    {{ number_format((float) $movement->frascos_antes, 2) }}
                                </td>

                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm font-semibold text-gray-900">
                                    {{ number_format((float) $movement->frascos_despues, 2) }}
                                </td>

                                <td class="px-4 py-3 text-sm text-gray-700">
                                    <div>{{ $movement->reference_type ?? '-' }}</div>
                                    @if(!is_null($movement->reference_id))
                                        <div class="text-xs text-gray-500">#{{ $movement->reference_id }}</div>
                                    @endif
                                </td>

                                <td class="max-w-xs px-4 py-3 text-sm text-gray-700">
                                    <div class="break-words">
                                        {{ $movement->notes ?? '-' }}
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="px-4 py-10 text-center text-sm text-gray-500">
                                    No hay movimientos registrados para este lote.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="pb-2">
            {{ $movements->links() }}
        </div>
    </div>
</x-admin-layout>
