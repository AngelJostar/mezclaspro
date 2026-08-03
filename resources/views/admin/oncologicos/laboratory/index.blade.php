<x-admin-layout>
    <div class="mt-2 mb-4 flex items-center justify-between">
        <h1 class="text-2xl font-medium text-gray-800">
            Laboratorios (Sucursales)
        </h1>

        <a href="{{ route('admin.oncologicos.laboratory.create') }}"
            class="bg-green-600 hover:bg-green-700 text-white font-bold py-2 px-4 rounded">
            <i class="fa-solid fa-plus pr-1"></i> Nuevo laboratorio
        </a>
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-lg shadow p-4 mb-4">
        <form method="GET"
              action="{{ route('admin.oncologicos.laboratory.index') }}"
              class="grid grid-cols-1 md:grid-cols-4 gap-3">

            <div class="md:col-span-2">
                <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                <input type="text"
                       name="q"
                       value="{{ request('q') }}"
                       placeholder="Nombre, estado o dirección..."
                       class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Estatus</label>
                <select name="activo" class="w-full rounded border-gray-300 focus:border-blue-500 focus:ring-blue-500">
                    <option value="">Todos</option>
                    <option value="1" {{ request('activo') === '1' ? 'selected' : '' }}>Activos</option>
                    <option value="0" {{ request('activo') === '0' ? 'selected' : '' }}>Inactivos</option>
                </select>
            </div>

            <div class="flex items-end gap-2">
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                    Buscar
                </button>

                <a href="{{ route('admin.oncologicos.laboratory.index') }}"
                   class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold py-2 px-4 rounded">
                    Limpiar
                </a>
            </div>
        </form>
    </div>

    {{-- Tabla --}}
    <div class="relative overflow-x-auto bg-white rounded-lg shadow">
        <table class="w-full text-sm text-left text-gray-600">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th class="px-6 py-3">Nombre</th>
                    <th class="px-6 py-3">Estado</th>
                    <th class="px-6 py-3">Dirección</th>
                    <th class="px-6 py-3 text-center">Estatus</th>
                    <th class="px-6 py-3 text-right">Acciones</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($laboratories as $lab)
                    <tr class="bg-white border-b last:border-b-0">
                        <td class="px-6 py-3 font-medium text-gray-800">
                            {{ $lab->nombre }}
                        </td>

                        <td class="px-6 py-3">
                            {{ $lab->estado ?: '—' }}
                        </td>

                        <td class="px-6 py-3">
                            <div class="max-w-[520px] truncate" title="{{ $lab->direccion }}">
                                {{ $lab->direccion ?: '—' }}
                            </div>
                        </td>

                        <td class="px-6 py-3 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium
                                {{ $lab->activo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                {{ $lab->activo ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>

                        <td class="px-6 py-3 text-right">
                            <x-row-actions>
                                <a href="{{ route('admin.oncologicos.laboratory.edit', $lab) }}"
                                   class="">
                                    <i class="fa-solid fa-pen"></i>Editar
                                </a>

                                <form method="POST"
                                      action="{{ route('admin.oncologicos.laboratory.destroy', $lab) }}"
                                      class="inline-block form-delete">
                                    @csrf
                                    @method('DELETE')

                                    <button type="submit" class="action-danger">
                                        <i class="fa-solid fa-trash"></i>Eliminar
                                    </button>
                                </form>
                            </x-row-actions>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-6 text-center text-gray-500">
                            No hay laboratorios para mostrar con los filtros actuales.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    {{-- Paginación --}}
    <div class="mt-4">
        {{ $laboratories->appends(request()->query())->links() }}
    </div>

    @push('js')
        <script>
            (function () {
                document.querySelectorAll('.form-delete').forEach(form => {
                    form.addEventListener('submit', function (e) {
                        e.preventDefault();

                        Swal.fire({
                            title: '¿Eliminar laboratorio?',
                            text: 'Esta acción no se puede deshacer.',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'Sí, eliminar',
                            cancelButtonText: 'Cancelar',
                            customClass: {
                                confirmButton: 'swal-button-confirm',
                                cancelButton: 'swal-button-cancel'
                            }
                        }).then((result) => {
                            if (result.isConfirmed) {
                                form.submit();
                            }
                        });
                    });
                });
            })();
        </script>
    @endpush
</x-admin-layout>
