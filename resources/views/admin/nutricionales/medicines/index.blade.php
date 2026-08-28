<x-admin-layout>
    @php($canEditGenericMedication = auth()->user()?->hasRole('Super Admin'))
    <div class="mt-2">
        <h1 class="text-2xl font-medium text-gray-800">Catálogo de Medicamentos</h1>
    </div>

    <div class="flex justify-end mb-4">
        <a class="text-white bg-azul-prodifem hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 font-medium rounded-full text-sm px-5 py-2.5 text-center me-2 mb-2"
            href="{{ route('admin.nutricionales.medicines.create') }}">
            <i class="fa-solid fa-plus pr-1"></i> Agregar
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

    <div class="relative overflow-x-auto">
        <table id="medicinesTable" class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th scope="col" class="px-6 py-3">ID</th>
                    <th scope="col" class="px-6 py-3">Medicamento / Presentaciones</th>
                    <th scope="col" class="px-6 py-3">Input</th>
                    <th scope="col" class="px-6 py-3">Categoría</th>
                    <th scope="col" class="px-6 py-3">Osmolaridad</th>
                    <th scope="col" class="px-6 py-3 text-center">Activo</th>
                    @if ($canEditGenericMedication)
                        <th scope="col" class="px-6 py-3 text-center">Editar</th>
                    @endif
                </tr>
            </thead>

            <tbody>
                @foreach ($medicines as $medicine)
                    <tr class="bg-white border-b">
                        <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap align-top">
                            {{ $medicine->id }}
                        </th>

                        <td class="px-6 py-4 align-top">
                            <div class="font-semibold text-gray-800">
                                {{ $medicine->denominacion_generica }}
                            </div>

                            @if ($medicine->presentations->isNotEmpty())
                                <div class="flex flex-wrap gap-2 mt-3">
                                    @foreach ($medicine->presentations as $presentation)
                                        <div class="border rounded-lg px-3 py-2 bg-gray-50 min-w-[220px]">
                                            <div class="font-medium text-gray-800">
                                                {{ $presentation->denominacion_comercial }}
                                            </div>

                                            <div class="text-xs text-gray-600">
                                                {{ $presentation->presentacion ?? '—' }}
                                            </div>

                                            <div class="text-xs text-gray-500">
                                                {{ $presentation->presentacion_ml ?? '—' }} ml
                                            </div>

                                            <div class="mt-1">
                                                @if ($presentation->is_available)
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-green-100 text-green-800 text-xs">
                                                        Disponible
                                                    </span>
                                                @else
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded bg-red-100 text-red-800 text-xs">
                                                        No disponible
                                                    </span>
                                                @endif
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                <div class="text-sm text-gray-400 mt-2">
                                    Sin presentaciones registradas
                                </div>
                            @endif
                        </td>

                        <td class="px-6 py-4 align-top">
                            {{ $medicine->input->description ?? '—' }}
                        </td>

                        <td class="px-6 py-4 align-top">
                            {{ $medicine->category->name ?? '—' }}
                        </td>

                        <td class="px-6 py-4 align-top">
                            {{ $medicine->osmolaridad ?? '—' }}
                        </td>

                        <td class="px-6 py-4 text-center align-top">
                            @if ($medicine->is_active)
                                <span class="inline-flex items-center px-2 py-0.5 rounded bg-green-100 text-green-800">
                                    Sí
                                </span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded bg-red-100 text-red-800">
                                    No
                                </span>
                            @endif
                        </td>

                        @if ($canEditGenericMedication)
                            <td class="px-6 py-4 text-center align-top">
                                <x-catalog-edit-button href="{{ route('admin.nutricionales.medicines.edit', $medicine) }}" />
                            </td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    @push('js')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                new DataTable('#medicinesTable', {
                    order: [[1, 'asc']],
                    columnDefs: [
                        @if ($canEditGenericMedication)
                            { orderable: false, searchable: false, targets: -1 }
                        @endif
                    ],
                    language: {
                        lengthMenu: "Mostrar _MENU_ registros por página",
                        zeroRecords: "Nada encontrado - lo siento",
                        info: "Mostrando página _PAGE_ de _PAGES_",
                        infoEmpty: "No hay registros disponibles",
                        infoFiltered: "(filtrado de _MAX_ registros totales)",
                        search: "Buscar:",
                    }
                });
            });
        </script>
    @endpush
</x-admin-layout>
