<x-admin-layout>
    <div class="mt-2 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div class="min-w-0">
            <h1 class="text-2xl font-medium text-gray-800">Lista de Solicitudes</h1>
            @include('admin.solicitudes._type-selector', ['selectedType' => 'todas'])
        </div>

        <div class="relative shrink-0 pb-1" x-data="{ open: false }">
            <button type="button" @click="open = !open"
                class="inline-flex items-center gap-2 rounded-full bg-azul-prodifem px-5 py-2.5 text-sm font-medium text-white hover:bg-blue-800">
                <i class="fa-solid fa-plus"></i>
                Agregar
                <i class="fa-solid fa-chevron-down text-xs"></i>
            </button>
            <div x-cloak x-show="open" @click.outside="open = false"
                class="absolute right-0 z-30 mt-2 w-52 overflow-hidden rounded-md border border-gray-200 bg-white py-1 shadow-lg">
                @if ($canViewNutrition)
                    <a href="{{ route('admin.nutricionales.solicitudes.create') }}"
                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Nutricional</a>
                @endif
                @if ($canViewOncology)
                    <a href="{{ route('admin.oncologicos.solicitudes.create', ['tipo_solicitud' => 'oncologicos']) }}"
                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Oncologica</a>
                    <a href="{{ route('admin.oncologicos.solicitudes.create', ['tipo_solicitud' => 'antibioticos']) }}"
                        class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">Antibiotico</a>
                @endif
            </div>
        </div>
    </div>

    <form method="GET" action="{{ route('admin.solicitudes.index') }}" class="mt-5 flex max-w-2xl gap-2">
        <label class="sr-only" for="unified-request-search">Buscar solicitudes</label>
        <input id="unified-request-search" name="buscar" value="{{ $search }}" type="search"
            placeholder="Buscar..."
            class="min-w-0 flex-1 rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
        <button type="submit"
            class="rounded-md bg-blue-600 px-5 py-2 text-sm font-semibold text-white hover:bg-blue-700">
            Buscar
        </button>
    </form>

    <div class="mt-4 overflow-x-auto rounded-md border border-gray-200">
        <table class="min-w-[1180px] w-full text-left text-xs text-gray-600">
            <thead class="bg-gray-50 text-[11px] uppercase text-gray-700">
                <tr>
                    <th class="px-4 py-3">Tipo</th>
                    <th class="px-4 py-3">ID</th>
                    <th class="px-4 py-3">Hospital</th>
                    <th class="px-4 py-3">Paciente</th>
                    <th class="px-4 py-3">Fecha y hora de solicitud</th>
                    <th class="px-4 py-3">Fecha y hora programada de entrega</th>
                    <th class="px-4 py-3">Estado operativo</th>
                    <th class="px-4 py-3 text-center">Ver</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200 bg-white">
                @forelse ($requests as $requestRow)
                    @php
                        $normalizedStatus = Illuminate\Support\Str::lower(
                            Illuminate\Support\Str::ascii($requestRow['status'])
                        );
                        $statusClass = match (true) {
                            str_contains($normalizedStatus, 'cancel') => 'bg-red-100 text-red-700',
                            str_contains($normalizedStatus, 'prepar') => 'bg-blue-100 text-blue-700',
                            str_contains($normalizedStatus, 'aprob') => 'bg-green-100 text-green-700',
                            str_contains($normalizedStatus, 'inspeccion') => 'bg-purple-100 text-purple-700',
                            str_contains($normalizedStatus, 'entreg') => 'bg-gray-200 text-gray-700',
                            default => 'bg-yellow-100 text-yellow-700',
                        };
                        $typeClass = match ($requestRow['type']) {
                            'nutricionales' => 'bg-emerald-50 text-emerald-700 ring-emerald-200',
                            'antibioticos' => 'bg-rose-50 text-rose-700 ring-rose-200',
                            default => 'bg-indigo-50 text-indigo-700 ring-indigo-200',
                        };
                    @endphp
                    <tr class="hover:bg-gray-50">
                        <td class="whitespace-nowrap px-4 py-3">
                            <span class="rounded-full px-2.5 py-1 font-semibold ring-1 ring-inset {{ $typeClass }}">
                                {{ $requestRow['type_label'] }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-gray-900">{{ $requestRow['id'] }}</td>
                        <td class="whitespace-nowrap px-4 py-3">{{ $requestRow['hospital'] }}</td>
                        <td class="whitespace-nowrap px-4 py-3">{{ $requestRow['patient'] }}</td>
                        <td class="whitespace-nowrap px-4 py-3">
                            {{ $requestRow['requested_at']?->format('d/m/Y H:i') ?? '-' }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            {{ $requestRow['delivery_at']?->format('d/m/Y H:i') ?? '-' }}
                        </td>
                        <td class="whitespace-nowrap px-4 py-3">
                            <span class="rounded-full px-2.5 py-1 font-medium {{ $statusClass }}">
                                {{ $requestRow['status'] }}
                            </span>
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 text-center">
                            <a href="{{ $requestRow['url'] }}"
                                class="inline-flex rounded-full bg-azul-prodifem px-4 py-2 font-semibold text-white hover:bg-blue-800">
                                Ver
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-10 text-center text-sm text-gray-500">
                            No se encontraron solicitudes.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-admin-layout>
