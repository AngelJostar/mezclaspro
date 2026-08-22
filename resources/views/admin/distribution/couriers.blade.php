<x-admin-layout>
    <div class="rounded-lg bg-white p-5 shadow-sm md:p-6">
        <header class="flex flex-col gap-4 border-b border-gray-200 pb-5 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <nav class="mb-2 text-xs font-medium text-gray-500" aria-label="Ruta de navegación">
                    <a href="{{ route('admin.distribution.index') }}" class="hover:text-blue-700">Distribución</a>
                    <span class="mx-2 text-gray-300">/</span>
                    <span class="text-blue-700">Mensajeros</span>
                </nav>
                <h1 class="text-2xl font-bold text-gray-950">Catálogo de mensajeros</h1>
                <p class="mt-1 text-sm text-gray-500">Personal activo con el puesto de mensajero disponible para las rutas de distribución.</p>
            </div>
            <a href="{{ route('admin.distribution.index') }}"
                class="inline-flex h-10 items-center justify-center gap-2 rounded-md border border-gray-300 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                Volver a rutas
            </a>
        </header>

        <form method="GET" action="{{ route('admin.distribution.couriers') }}" class="mt-5 flex flex-col gap-2 sm:flex-row">
            <label class="relative block max-w-xl flex-1">
                <span class="sr-only">Buscar mensajero</span>
                <i class="fa-solid fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-400" aria-hidden="true"></i>
                <input type="search" name="search" value="{{ $search }}" placeholder="Buscar por nombre, usuario, teléfono o departamento..."
                    class="h-11 w-full rounded-md border-gray-300 pl-10 text-sm focus:border-blue-500 focus:ring-blue-500">
            </label>
            <button type="submit" class="inline-flex h-11 items-center justify-center gap-2 rounded-md bg-blue-700 px-4 text-sm font-semibold text-white hover:bg-blue-800">
                <i class="fa-solid fa-filter" aria-hidden="true"></i>
                Filtrar
            </button>
            @if ($search !== '')
                <a href="{{ route('admin.distribution.couriers') }}"
                    class="inline-flex h-11 items-center justify-center gap-2 rounded-md border border-gray-300 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    <i class="fa-solid fa-eraser" aria-hidden="true"></i>
                    Limpiar
                </a>
            @endif
        </form>

        <div class="mt-5 flex items-center justify-between gap-3">
            <h2 class="font-bold text-gray-950">Mensajeros disponibles</h2>
            <span class="text-sm font-semibold text-gray-500">{{ number_format($messengers->total()) }} registrados</span>
        </div>

        <div class="mt-3 overflow-x-auto rounded-md border border-gray-200">
            <table class="w-full min-w-[1050px] text-left text-sm text-gray-700">
                <thead class="bg-gray-50 text-xs font-semibold uppercase text-gray-600">
                    <tr>
                        <th class="px-4 py-3">Mensajero</th>
                        <th class="px-4 py-3">Usuario</th>
                        <th class="px-4 py-3">Teléfono</th>
                        <th class="px-4 py-3">Correo</th>
                        <th class="px-4 py-3">Departamento</th>
                        <th class="px-4 py-3">Central</th>
                        <th class="px-4 py-3 text-center">Estatus</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse ($messengers as $messenger)
                        <tr class="hover:bg-gray-50/70">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <span class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-cyan-100 text-xs font-bold text-cyan-800">
                                        {{ mb_strtoupper(mb_substr($messenger->name, 0, 1).mb_substr($messenger->lastname ?: $messenger->name, 0, 1)) }}
                                    </span>
                                    <span class="font-semibold text-gray-950">{{ trim($messenger->name.' '.$messenger->lastname) }}</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 font-medium">{{ $messenger->username }}</td>
                            <td class="px-4 py-3">{{ $messenger->personnelProfile?->phone ?: 'Sin registrar' }}</td>
                            <td class="px-4 py-3">{{ $messenger->personnelProfile?->personal_email ?: 'Sin registrar' }}</td>
                            <td class="px-4 py-3">{{ $messenger->personnelProfile?->department ?: 'Sin asignar' }}</td>
                            <td class="px-4 py-3">{{ $messenger->personnelProfile?->laboratory?->nombre ?: 'Sin asignar' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex rounded-md border border-emerald-200 bg-emerald-50 px-3 py-1 text-xs font-semibold text-emerald-700">Activo</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-14 text-center">
                                <i class="fa-solid fa-users text-2xl text-gray-300" aria-hidden="true"></i>
                                <p class="mt-2 font-semibold text-gray-900">No se encontraron mensajeros.</p>
                                <p class="mt-1 text-sm text-gray-500">Asigna el puesto Mensajero de red fria desde el panel de Personal.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($messengers->hasPages())
            <div class="mt-4">{{ $messengers->links() }}</div>
        @endif
    </div>
</x-admin-layout>
