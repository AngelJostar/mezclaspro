<x-admin-layout>
    <section class="rounded-lg bg-white p-5 shadow-lg">
        <div class="flex flex-col gap-3 border-b border-gray-100 pb-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Hospitales de la instituci&oacute;n</h1>
                <p class="mt-1 text-sm text-blue-600">{{ $institucion->nombre }}</p>
            </div>

            <a href="{{ route('admin.instituciones.index') }}"
                class="text-sm font-medium text-blue-600 hover:text-blue-800">
                &larr; Volver a instituciones
            </a>
        </div>

        <div class="mt-4 flex flex-col gap-4 rounded-lg border border-gray-200 p-4 md:flex-row md:items-center md:justify-between">
            <div class="flex min-w-0 items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-800">
                    <span class="text-sm font-bold" aria-hidden="true">I</span>
                </span>
                <div class="min-w-0">
                    <p class="truncate text-sm font-medium text-gray-900">
                        Instituci&oacute;n: {{ $institucion->nombre }}
                    </p>
                    <p class="mt-1 flex items-center gap-2 text-xs text-gray-600">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        Activa
                    </p>
                </div>
            </div>

            <a href="{{ route('admin.instituciones.hospitals.create', $institucion) }}"
                class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-azul-prodifem px-4 py-2 text-sm font-semibold text-white hover:bg-blue-900 focus:outline-none focus:ring-4 focus:ring-blue-200">
                <span class="text-base leading-none" aria-hidden="true">+</span>
                Crear hospital
            </a>
        </div>

        <div class="mt-4 overflow-hidden rounded-lg border border-gray-200">
            <div class="border-b border-gray-200 px-4 py-3">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900">Hospitales relacionados</h2>
                        <p class="text-xs text-gray-500">
                            {{ $totalHospitals }} {{ $totalHospitals === 1 ? 'hospital' : 'hospitales' }}
                        </p>
                    </div>
                </div>

                <form method="GET" action="{{ route('admin.instituciones.hospitals', $institucion) }}"
                    class="mt-3 grid grid-cols-1 gap-2 md:grid-cols-12">
                    <label class="relative md:col-span-4">
                        <span class="sr-only">Buscar hospital</span>
                        <input type="search" name="search" value="{{ $search }}"
                            placeholder="Buscar por nombre, clave o municipio..."
                            class="h-10 w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </label>

                    <label class="md:col-span-2">
                        <span class="sr-only">Tipo de unidad</span>
                        <select name="unit_type"
                            class="h-10 w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="all">Tipo de unidad: Todos</option>
                            @foreach ($unitTypes as $type)
                                <option value="{{ $type }}" @selected($unitType === $type)>{{ $type }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="md:col-span-2">
                        <span class="sr-only">Estatus</span>
                        <select name="status"
                            class="h-10 w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="all" @selected($status === 'all')>Estatus: Todos</option>
                            <option value="active" @selected($status === 'active')>Activos</option>
                            <option value="inactive" @selected($status === 'inactive')>Inactivos</option>
                        </select>
                    </label>

                    <label class="md:col-span-2">
                        <span class="sr-only">L&iacute;nea de servicio</span>
                        <select name="service"
                            class="h-10 w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="all" @selected($service === 'all')>Servicio: Todos</option>
                            <option value="oncology" @selected($service === 'oncology')>Oncol&oacute;gicos</option>
                            <option value="antibiotics" @selected($service === 'antibiotics')>Antibi&oacute;ticos</option>
                            <option value="nutrition" @selected($service === 'nutrition')>Nutricionales</option>
                        </select>
                    </label>

                    <div class="flex gap-2 md:col-span-2">
                        <button type="submit"
                            class="inline-flex h-10 flex-1 items-center justify-center rounded-lg bg-azul-prodifem px-3 text-sm font-semibold text-white hover:bg-blue-900">
                            Filtrar
                        </button>
                        <a href="{{ route('admin.instituciones.hospitals', $institucion) }}"
                            class="inline-flex h-10 items-center justify-center rounded-lg border border-gray-300 px-3 text-xs font-medium text-gray-700 hover:bg-gray-50">
                            Limpiar
                        </a>
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1050px] text-left text-xs text-gray-600">
                    <thead class="bg-gray-50 text-[11px] uppercase text-gray-700">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Hospital</th>
                            <th class="px-4 py-3 font-semibold">Clave</th>
                            <th class="px-4 py-3 font-semibold">Tipo de unidad</th>
                            <th class="px-4 py-3 font-semibold">Municipio</th>
                            <th class="px-4 py-3 font-semibold">L&iacute;neas de servicio</th>
                            <th class="px-4 py-3 font-semibold">Estatus</th>
                            <th class="px-4 py-3 text-center font-semibold">Editar</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @forelse ($hospitals as $hospital)
                            @php
                                $hasOncology = $hospital->service_oncology || $hospital->onco_medicine_list_id;
                                $hasNutrition = $hospital->service_nutrition || $hospital->nutri_medicine_list_id;
                                $hasServices = $hasOncology || $hospital->service_antibiotics || $hasNutrition;
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $hospital->name }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ $hospital->internal_key ?: '-' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ $hospital->unit_type ?: '-' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ $hospital->municipality ?: '-' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1">
                                        @if ($hasOncology)
                                            <span class="rounded border border-emerald-300 bg-emerald-50 px-2 py-1 text-[10px] font-medium text-emerald-700">Oncol&oacute;gicos</span>
                                        @endif
                                        @if ($hospital->service_antibiotics)
                                            <span class="rounded border border-red-300 bg-red-50 px-2 py-1 text-[10px] font-medium text-red-700">Antibi&oacute;ticos</span>
                                        @endif
                                        @if ($hasNutrition)
                                            <span class="rounded border border-blue-300 bg-blue-50 px-2 py-1 text-[10px] font-medium text-blue-700">Nutricionales</span>
                                        @endif
                                        @unless ($hasServices)
                                            <span class="text-gray-400">Sin definir</span>
                                        @endunless
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-2 font-medium {{ $hospital->is_active ? 'text-emerald-700' : 'text-gray-500' }}">
                                        <span class="h-2 w-2 rounded-full {{ $hospital->is_active ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                                        {{ $hospital->is_active ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <x-table-action-link href="{{ route('admin.hospitals.edit', $hospital) }}"
                                        icon="fa-solid fa-pen">
                                        Editar
                                    </x-table-action-link>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-10 text-center text-sm text-gray-500">
                                    No hay hospitales relacionados con estos filtros.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($hospitals->hasPages())
                <div class="border-t border-gray-200 px-4 py-3">
                    {{ $hospitals->links() }}
                </div>
            @endif
        </div>
    </section>
</x-admin-layout>
