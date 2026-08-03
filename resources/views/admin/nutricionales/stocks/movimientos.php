<x-admin-layout>
    <div class="mt-2 mb-4 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-medium text-gray-800">
                Movimientos de inventario
            </h1>

            <div class="text-sm text-gray-600 mt-1 space-y-1">
                <div>
                    Laboratorio:
                    <span class="font-semibold text-gray-800">
                        {{ $stock->laboratory->nombre ?? '—' }}
                    </span>
                </div>

                <div>
                    Medicamento genérico:
                    <span class="font-semibold text-gray-800">
                        {{ $stock->presentation->catalog->denominacion_generica ?? '—' }}
                    </span>
                </div>

                <div>
                    Presentación comercial:
                    <span class="font-semibold text-gray-800">
                        {{ $stock->presentation->denominacion_comercial ?? '—' }}
                    </span>
                </div>

                <div>
                    Presentación:
                    <span class="font-semibold text-gray-800">
                        {{ $stock->presentation->presentacion ?? '—' }}
                        @if (!is_null($stock->presentation->presentacion_ml))
                            · {{ $stock->presentation->presentacion_ml }} ml
                        @endif
                    </span>
                </div>

                <div>
                    Lote:
                    <span class="font-semibold text-gray-800">
                        {{ $stock->lote ?? '—' }}
                    </span>
                    <span class="mx-2 text-gray-400">·</span>
                    Caducidad:
                    <span class="font-semibold text-gray-800">
                        {{ $stock->caducidad ? \Carbon\Carbon::parse($stock->caducidad)->format('Y-m-d') : '—' }}
                    </span>
                </div>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <a href="{{ route('admin.nutricionales.stocks.index', ['laboratory_id' => $stock->laboratory_id]) }}"
                class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2 px-4 rounded">
                Volver al inventario
            </a>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg p-4 mb-4">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
            <div>
                <span class="text-gray-500">Stock inicial:</span>
                <span class="font-semibold text-gray-800">
                    {{ number_format($stock->stock_ml_inicial, 2) }} ml
                </span>
            </div>

            <div>
                <span class="text-gray-500">Stock actual:</span>
                <span class="font-semibold text-green-700">
                    {{ number_format($stock->stock_ml_actual, 2) }} ml
                </span>
            </div>

            <div>
                <span class="text-gray-500">Estado:</span>
                @if($stock->is_active)
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800">
                        Activo
                    </span>
                @else
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-red-100 text-red-800">
                        Inactivo
                    </span>
                @endif
            </div>
        </div>
    </div>

    <div class="bg-white shadow rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Fecha</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Usuario</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Tipo</th>
                    <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Cantidad (ml)</th>
                    <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Stock antes</th>
                    <th class="px-4 py-3 text-right text-sm font-semibold text-gray-700">Stock después</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Referencia</th>
                    <th class="px-4 py-3 text-left text-sm font-semibold text-gray-700">Notas</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200">
                @forelse($movements as $movement)
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-800">
                            <div>{{ $movement->created_at?->format('Y-m-d') }}</div>
                            <div class="text-xs text-gray-500">{{ $movement->created_at?->format('H:i:s') }}</div>
                        </td>

                        <td class="px-4 py-3 text-sm text-gray-800">
                            {{ $movement->user->name ?? 'Sistema' }}
                        </td>

                        <td class="px-4 py-3 text-sm">
                            @php
                                $tipo = strtolower($movement->tipo ?? '');
                                $badgeClasses = match ($tipo) {
                                    'entrada' => 'bg-green-100 text-green-800',
                                    'salida' => 'bg-blue-100 text-blue-800',
                                    'merma' => 'bg-red-100 text-red-800',
                                    'ajuste' => 'bg-yellow-100 text-yellow-800',
                                    default => 'bg-gray-100 text-gray-800',
                                };
                            @endphp

                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $badgeClasses }}">
                                {{ ucfirst($movement->tipo ?? '—') }}
                            </span>
                        </td>

                        <td class="px-4 py-3 text-sm text-right text-gray-800">
                            {{ number_format((float) $movement->cantidad_ml, 2) }}
                        </td>

                        <td class="px-4 py-3 text-sm text-right text-gray-800">
                            {{ number_format((float) $movement->stock_antes, 2) }}
                        </td>

                        <td class="px-4 py-3 text-sm text-right font-semibold text-gray-800">
                            {{ number_format((float) $movement->stock_despues, 2) }}
                        </td>

                        <td class="px-4 py-3 text-sm text-gray-700">
                            <div>{{ $movement->reference_type ?? '—' }}</div>
                            @if(!is_null($movement->reference_id))
                                <div class="text-xs text-gray-500">#{{ $movement->reference_id }}</div>
                            @endif
                        </td>

                        <td class="px-4 py-3 text-sm text-gray-700">
                            {{ $movement->notes ?? '—' }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-6 text-center text-gray-500">
                            No hay movimientos registrados para este lote.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $movements->links() }}
    </div>
</x-admin-layout>
