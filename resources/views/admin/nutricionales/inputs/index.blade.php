<x-admin-layout>
    <div class="mt-2">
        <h1 class="text-2xl font-medium text-gray-800">Inputs nutricionales</h1>
    </div>

    <div class="flex justify-end mb-4">
        <a class="text-white bg-azul-prodifem hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 font-medium rounded-full text-sm px-5 py-2.5 text-center me-2 mb-2"
            href="{{ route('admin.nutricionales.inputs.create') }}">
            <i class="fa-solid fa-plus pr-1"></i> Agregar input
        </a>
    </div>

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-800 rounded p-3 mb-4">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="relative overflow-x-auto bg-white rounded-lg shadow">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th class="px-4 py-3">Orden</th>
                    <th class="px-4 py-3">Descripcion</th>
                    <th class="px-4 py-3">Categoria</th>
                    <th class="px-4 py-3">Unidad</th>
                    <th class="px-4 py-3">Tipo</th>
                    <th class="px-4 py-3">Mult</th>
                    <th class="px-4 py-3">Div</th>
                    <th class="px-4 py-3 text-center">Estado</th>
                    <th class="px-4 py-3 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($inputs as $input)
                    <tr class="bg-white border-b">
                        <td class="px-4 py-3">{{ $input->orden_enum }}</td>
                        <td class="px-4 py-3 font-medium text-gray-900">{{ $input->description }}</td>
                        <td class="px-4 py-3">{{ $input->category->name ?? '-' }}</td>
                        <td class="px-4 py-3">{{ $input->unidad }}</td>
                        <td class="px-4 py-3">{{ $input->tipo_input }}</td>
                        <td class="px-4 py-3">{{ $input->mult }}</td>
                        <td class="px-4 py-3">{{ $input->div }}</td>
                        <td class="px-4 py-3 text-center">
                            @if ($input->is_active)
                                <span class="inline-flex items-center px-2 py-0.5 rounded bg-green-100 text-green-800">Activo</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded bg-red-100 text-red-800">Inactivo</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-center">
                            <x-row-actions>
                                <a href="{{ route('admin.nutricionales.inputs.edit', $input) }}">
                                    Editar
                                </a>
                                <form action="{{ route('admin.nutricionales.inputs.destroy', $input) }}"
                                    method="POST"
                                    onsubmit="return confirm('Seguro que deseas eliminar este input?');">
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
                        <td colspan="9" class="px-4 py-6 text-center text-gray-500">
                            No hay inputs registrados.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $inputs->links() }}
    </div>
</x-admin-layout>
