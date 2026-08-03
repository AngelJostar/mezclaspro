<x-admin-layout>
    <div class="mt-2">
        <div class="mb-4">
            <h1 class="text-2xl font-semibold text-gray-800">
                Inventario de diluyente: {{ $diluent->denominacion_generica ?? '-' }}
            </h1>
            <div class="mt-2">
                <a href="{{ route('admin.oncologicos.diluents.index') }}"
                   class="text-sm text-blue-600 hover:underline">&larr; Volver a diluyentes</a>
            </div>
        </div>

        @if (session('success'))
            <div class="mb-4 p-3 rounded bg-green-100 text-green-800">{{ session('success') }}</div>
        @endif

        <div class="flex justify-end mb-3">
            <a href="{{ route('admin.oncologicos.diluent_presentations.create', $diluent) }}"
               class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                + Nuevo lote / presentacion
            </a>
        </div>

        <div class="bg-white rounded shadow overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead class="bg-gray-100 text-gray-600">
                    <tr>
                        <th class="px-4 py-3 text-left">Presentacion</th>
                        <th class="px-4 py-3 text-left">Laboratorio</th>
                        <th class="px-4 py-3 text-left">Volumen (mL)</th>
                        <th class="px-4 py-3 text-left">Nombre comercial</th>
                        <th class="px-4 py-3 text-left">Lote</th>
                        <th class="px-4 py-3 text-left">Caducidad</th>
                        <th class="px-4 py-3 text-left">Ingreso</th>
                        <th class="px-4 py-3 text-right">Stock</th>
                        <th class="px-4 py-3 text-center">Activo</th>
                        <th class="px-4 py-3 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    @forelse($presentations as $p)
                        <tr>
                            <td class="px-4 py-3">{{ $p->presentacion ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $p->laboratory->nombre ?? 'General' }}</td>
                            <td class="px-4 py-3">{{ number_format((float) $p->volume_ml, 2) }}</td>
                            <td class="px-4 py-3">{{ $p->denominacion_comercial ?? '-' }}</td>
                            <td class="px-4 py-3">{{ $p->lote ?? '-' }}</td>
                            <td class="px-4 py-3">
                                {{ $p->caducidad ? \Carbon\Carbon::parse($p->caducidad)->format('d/m/Y') : '-' }}
                            </td>
                            <td class="px-4 py-3">
                                {{ $p->fecha_ingreso ? \Carbon\Carbon::parse($p->fecha_ingreso)->format('d/m/Y') : '-' }}
                            </td>
                            <td class="px-4 py-3 text-right font-semibold">
                                {{ number_format((float) ($p->stock_actual ?? 0), 0) }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @if($p->is_active)
                                    <span class="px-2 py-1 text-xs rounded bg-green-100 text-green-800">Si</span>
                                @else
                                    <span class="px-2 py-1 text-xs rounded bg-gray-200 text-gray-700">No</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <x-row-actions>
                                    <a href="{{ route('admin.oncologicos.diluent_presentations.edit', [$diluent, $p]) }}">
                                        Editar
                                    </a>

                                    <form action="{{ route('admin.oncologicos.diluent_presentations.destroy', [$diluent, $p]) }}"
                                          method="POST" class="inline-block"
                                          onsubmit="return confirm('Eliminar esta presentacion?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="action-danger">
                                            Eliminar
                                        </button>
                                    </form>
                                </x-row-actions>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td class="px-4 py-6 text-center text-gray-500" colspan="10">
                                No hay presentaciones registradas.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $presentations->links() }}
        </div>
    </div>
</x-admin-layout>
