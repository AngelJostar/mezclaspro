<x-admin-layout>
    <div class="mt-2 mb-4">
        <h1 class="text-2xl font-medium text-gray-800">Reportes de instituciones</h1>
        <p class="text-sm text-gray-500 mt-1">
            Desde aqui puedes descargar los reportes generales por institucion.
        </p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-5">
        <form method="GET" action="{{ route('admin.instituciones.reportes') }}"
            class="flex flex-col gap-3 md:flex-row md:items-center md:justify-end mb-5">
            <div class="relative w-full md:max-w-md">
                <input type="text" name="search" value="{{ $search }}"
                    placeholder="Buscar institucion..."
                    class="w-full rounded-xl border-slate-200 pr-12 focus:border-blue-400 focus:ring-blue-400">
                <span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-slate-400">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
            </div>

            <button type="submit"
                class="inline-flex items-center justify-center rounded-xl border border-slate-200 px-4 py-2.5 text-slate-600 hover:bg-slate-50">
                <i class="fa-solid fa-filter mr-2"></i>
                Filtrar
            </button>
        </form>

        <div class="overflow-x-auto">
            <table class="min-w-full text-sm text-left text-slate-600">
                <thead class="text-xs uppercase text-slate-500 bg-slate-50">
                    <tr>
                        <th class="px-5 py-4">ID</th>
                        <th class="px-5 py-4">Nombre de la institucion</th>
                        <th class="px-5 py-4">RFC</th>
                        <th class="px-5 py-4">Telefono</th>
                        <th class="px-5 py-4 text-center">Hospitales</th>
                        <th class="px-5 py-4">Estatus</th>
                        <th class="px-5 py-4">Reporte de institucion</th>
                        <th class="px-5 py-4">Reporte por hospital</th>
                        <th class="px-5 py-4">Reporte por hospital con detalle</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($instituciones as $institucion)
                        <tr class="bg-white">
                            <td class="px-5 py-5 font-medium text-slate-700">
                                {{ $institucion->id }}
                            </td>
                            <td class="px-5 py-5 min-w-[280px]">
                                <div class="font-medium text-slate-800">{{ $institucion->nombre }}</div>
                                @if ($institucion->razon_social)
                                    <div class="text-xs text-slate-400 mt-1">{{ $institucion->razon_social }}</div>
                                @endif
                            </td>
                            <td class="px-5 py-5">{{ $institucion->rfc ?: '—' }}</td>
                            <td class="px-5 py-5">{{ $institucion->telefono ?: '—' }}</td>
                            <td class="px-5 py-5 text-center">
                                <span
                                    class="inline-flex min-w-8 items-center justify-center rounded-md bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-600">
                                    {{ $institucion->hospitals_count }}
                                </span>
                            </td>
                            <td class="px-5 py-5">
                                <span
                                    class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-xs font-medium text-emerald-600">
                                    Activo
                                </span>
                            </td>
                            <td class="px-5 py-5">
                                <a href="{{ route('admin.instituciones.exportarGeneral', $institucion) }}"
                                    class="inline-flex items-center gap-2 text-blue-600 hover:text-blue-700 font-medium">
                                    <i class="fa-solid fa-download"></i>
                                    Descargar
                                </a>
                            </td>
                            <td class="px-5 py-5">
                                <a href="{{ route('admin.instituciones.exportarHospital', $institucion) }}"
                                    class="inline-flex items-center gap-2 text-blue-600 hover:text-blue-700 font-medium">
                                    <i class="fa-solid fa-download"></i>
                                    Descargar
                                </a>
                            </td>
                            <td class="px-5 py-5">
                                <a href="{{ route('admin.instituciones.exportarHospitalDetalle', $institucion) }}"
                                    class="inline-flex items-center gap-2 text-blue-600 hover:text-blue-700 font-medium">
                                    <i class="fa-solid fa-download"></i>
                                    Descargar
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-5 py-10 text-center text-slate-400">
                                No se encontraron instituciones para mostrar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-5 flex flex-col gap-3 text-sm text-slate-500 md:flex-row md:items-center md:justify-between">
            <p>
                Mostrando {{ $instituciones->firstItem() ?? 0 }} a {{ $instituciones->lastItem() ?? 0 }} de
                {{ $instituciones->total() }} instituciones
            </p>

            <div>
                {{ $instituciones->links() }}
            </div>
        </div>
    </div>
</x-admin-layout>

