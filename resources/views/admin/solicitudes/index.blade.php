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

    @include('admin.solicitudes._status-selector', ['activeStatus' => $statusFilter])

    <div class="mt-4 overflow-x-auto" data-sticky-x-position="viewport">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 bg-gray-50 uppercase">
                @include('admin.solicitudes._table-header')
            </thead>
            <tbody>
                @forelse ($requests as $requestRow)
                    @php
                        $solicitud = $requestRow['model'];
                        $isNutrition = $requestRow['type'] === 'nutricionales';
                        $mezcla = $requestRow['mixture'] ?? null;
                        $estado = $requestRow['status'] ?? 'pendiente';
                    @endphp
                    <tr class="border-b">
                        <td class="px-2 py-2 text-center whitespace-nowrap">
                            @include('admin.solicitudes._type-badge', ['type' => $requestRow['type']])
                        </td>
                        <td class="px-2 py-2 text-center">{{ $requestRow['id'] ?? '—' }}</td>
                        <td class="px-2 py-2 text-center">{{ $requestRow['request_id'] }}</td>
                        <td class="px-2 py-2 text-center">{{ $requestRow['hospital'] }}</td>
                        <td class="px-2 py-2 text-center">{{ $requestRow['patient'] }}</td>
                        <td class="w-[11rem] min-w-[11rem] max-w-[11rem] px-2 py-2 text-center whitespace-nowrap">
                            {{ $requestRow['requested_at']?->format('Y-m-d H:i') ?? '—' }}
                        </td>
                        <td class="w-[11rem] min-w-[11rem] max-w-[11rem] px-2 py-2 text-center whitespace-nowrap">
                            {{ $requestRow['delivery_at']?->format('Y-m-d H:i') ?? '—' }}
                        </td>
                        <td class="px-2 py-2 text-center">
                            @include('admin.solicitudes._status-badge', ['status' => $estado])
                        </td>
                        <td class="px-2 py-2 text-center">{{ $requestRow['remission'] ?: '—' }}</td>
                        <td class="px-2 py-2 text-center">{{ $requestRow['lot'] ?: '—' }}</td>

                        <td class="px-2 py-2 text-center whitespace-nowrap">
                            <a href="{{ $isNutrition
                                ? route('admin.nutricionales.solicitudes.show', $solicitud)
                                : ($mezcla
                                    ? route('admin.oncologicos.mezclas.show', $mezcla)
                                    : route('admin.oncologicos.mezclas.index', $solicitud)) }}"
                                class="inline-flex items-center justify-center rounded-full bg-azul-prodifem px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                                Ver
                            </a>
                        </td>

                        <td class="px-2 py-2 text-center whitespace-nowrap">
                            <a href="{{ $isNutrition
                                ? route('admin.nutricionales.solicitudes.solicitud', $solicitud)
                                : route('admin.oncologicos.mezclas.solicitudCompleta', $solicitud) }}"
                                target="_blank" rel="noopener"
                                class="inline-flex items-center justify-center rounded-full bg-azul-prodifem px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                                Ver
                            </a>
                        </td>

                        <td class="px-2 py-2 text-center whitespace-nowrap">
                            @hasanyrole('Admin|Super Admin')
                                @if ($estado === 'pendiente')
                                    <a href="{{ $isNutrition
                                        ? route('admin.nutricionales.solicitudes.edit', $solicitud)
                                        : route('admin.oncologicos.solicitudes.edit', $solicitud->id) }}"
                                        class="inline-flex items-center justify-center rounded-full bg-amber-400 px-3 py-2 text-xs font-semibold text-white transition hover:bg-amber-500 focus:outline-none focus:ring-4 focus:ring-amber-200">
                                        Aprobar
                                    </a>
                                @elseif (in_array($estado, ['aprobada', 'preparada', 'revisada'], true))
                                    <a href="{{ $isNutrition
                                        ? route('admin.nutricionales.solicitudes.edit', $solicitud)
                                        : route('admin.oncologicos.solicitudes.edit', $solicitud->id) }}"
                                        class="inline-flex items-center justify-center rounded-full bg-blue-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-700 focus:outline-none focus:ring-4 focus:ring-blue-200">
                                        Editar
                                    </a>
                                @else
                                    <button type="button" disabled
                                        class="inline-flex cursor-not-allowed items-center justify-center rounded-full bg-gray-300 px-3 py-2 text-xs font-semibold text-gray-500 opacity-80">
                                        Editar
                                    </button>
                                @endif
                            @else
                                <button type="button" disabled
                                    class="inline-flex cursor-not-allowed items-center justify-center rounded-full bg-gray-300 px-3 py-2 text-xs font-semibold text-gray-500 opacity-80">
                                    Editar
                                </button>
                            @endhasanyrole
                        </td>

                        <td class="px-2 py-2 text-center whitespace-nowrap">
                            <div class="flex min-w-max items-center justify-center gap-1.5">
                                @hasanyrole('Admin|Super Admin')
                                    @if ($isNutrition)
                                        @if ($estado === 'aprobada')
                                            <form method="POST"
                                                action="{{ route('admin.nutricionales.solicitudes.preparar', $solicitud) }}"
                                                class="inline-block" data-request-process-form
                                                data-confirm-title="¿Marcar solicitud como preparada?"
                                                data-confirm-text="La solicitud pasará al estado PREPARADA."
                                                data-confirm-button="Sí, preparar">
                                                @csrf
                                                <button type="submit"
                                                    class="inline-flex items-center justify-center rounded-full bg-slate-700 px-3 py-2 text-xs font-semibold text-white transition hover:bg-slate-800 focus:outline-none focus:ring-4 focus:ring-slate-300">
                                                    Preparada
                                                </button>
                                            </form>
                                        @elseif ($estado === 'preparada')
                                            <button type="button"
                                                onclick="window.Livewire.dispatch('abrir-modal-inspeccion-nutricional', { solicitudId: {{ $solicitud->id }} })"
                                                class="inline-flex items-center justify-center rounded-full bg-violet-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-violet-700 focus:outline-none focus:ring-4 focus:ring-violet-200">
                                                Inspeccionar
                                            </button>
                                        @elseif ($estado === 'revisada')
                                            <form method="POST"
                                                action="{{ route('admin.nutricionales.solicitudes.entregar', $solicitud) }}"
                                                class="inline-block" data-request-process-form
                                                data-confirm-title="¿Marcar solicitud como entregada?"
                                                data-confirm-text="La solicitud quedará como ENTREGADA."
                                                data-confirm-icon="success" data-confirm-color="#374151"
                                                data-confirm-button="Sí, entregar">
                                                @csrf
                                                <button type="submit"
                                                    class="inline-flex items-center justify-center rounded-full bg-emerald-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-200">
                                                    Entregar
                                                </button>
                                            </form>
                                        @else
                                            <button type="button" disabled
                                                class="inline-flex cursor-not-allowed items-center justify-center rounded-full bg-gray-300 px-3 py-2 text-xs font-semibold text-gray-500 opacity-80">
                                                Proceso
                                            </button>
                                        @endif
                                    @elseif ($mezcla)
                                            @if ($estado === 'aprobada')
                                                <form method="POST"
                                                    action="{{ route('admin.oncologicos.mezclas.update', $mezcla) }}"
                                                    class="inline-block" data-request-process-form
                                                    data-confirm-title="¿Marcar mezcla como preparada?"
                                                    data-confirm-text="La mezcla pasará al estado PREPARADA."
                                                    data-confirm-button="Sí, preparar">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="accion" value="preparada">
                                                    <button type="submit"
                                                        class="inline-flex items-center justify-center rounded-full bg-slate-700 px-3 py-2 text-xs font-semibold text-white transition hover:bg-slate-800 focus:outline-none focus:ring-4 focus:ring-slate-300">
                                                        Preparada
                                                    </button>
                                                </form>
                                            @elseif ($estado === 'preparada')
                                                <button type="button"
                                                    onclick="window.dispatchEvent(new CustomEvent('abrir-modal-inspeccion', { detail: [{{ $mezcla->id }}] }))"
                                                    class="inline-flex items-center justify-center rounded-full bg-violet-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-violet-700 focus:outline-none focus:ring-4 focus:ring-violet-200">
                                                    Inspeccionar
                                                </button>
                                            @elseif ($estado === 'revisada')
                                                <form method="POST"
                                                    action="{{ route('admin.oncologicos.mezclas.update', $mezcla) }}"
                                                    class="inline-block" data-request-process-form
                                                    data-confirm-title="¿Marcar mezcla como entregada?"
                                                    data-confirm-text="La mezcla quedará como ENTREGADA."
                                                    data-confirm-icon="success" data-confirm-color="#374151"
                                                    data-confirm-button="Sí, entregar">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="hidden" name="accion" value="entregada">
                                                    <button type="submit"
                                                        class="inline-flex items-center justify-center rounded-full bg-emerald-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-200">
                                                        Entregar
                                                    </button>
                                                </form>
                                            @else
                                                <button type="button" disabled
                                                    class="inline-flex cursor-not-allowed items-center justify-center rounded-full bg-gray-300 px-3 py-2 text-xs font-semibold text-gray-500 opacity-80">
                                                    Proceso
                                                </button>
                                            @endif
                                    @else
                                        <button type="button" disabled
                                            class="inline-flex cursor-not-allowed items-center justify-center rounded-full bg-gray-300 px-3 py-2 text-xs font-semibold text-gray-500 opacity-80">
                                            Proceso
                                        </button>
                                    @endif
                                @else
                                    <button type="button" disabled
                                        class="inline-flex cursor-not-allowed items-center justify-center rounded-full bg-gray-300 px-3 py-2 text-xs font-semibold text-gray-500 opacity-80">
                                        Proceso
                                    </button>
                                @endhasanyrole
                            </div>
                        </td>

                        <td class="px-4 py-2 text-center whitespace-nowrap">
                            <a href="{{ $isNutrition
                                ? route('admin.nutricionales.solicitudes.solicitud', $solicitud)
                                : route('admin.oncologicos.mezclas.solicitudCompleta', $solicitud) }}"
                                target="_blank" rel="noopener"
                                class="inline-flex items-center justify-center rounded-full bg-azul-prodifem px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                                Solicitud completa
                            </a>
                        </td>

                        @foreach ([
                            ['label' => 'Inspección', 'nutritionRoute' => 'admin.nutricionales.solicitudes.inspeccion', 'oncologyRoute' => 'admin.oncologicos.mezclas.inspeccion'],
                            ['label' => 'Etiqueta', 'nutritionRoute' => 'admin.nutricionales.solicitudes.etiqueta', 'oncologyRoute' => 'admin.oncologicos.mezclas.etiqueta'],
                            ['label' => 'Orden de preparación', 'nutritionRoute' => 'admin.nutricionales.solicitudes.ordenPreparacion', 'oncologyRoute' => 'admin.oncologicos.mezclas.ordenPreparacion'],
                        ] as $documentAction)
                            <td class="px-4 py-2 text-center whitespace-nowrap">
                                <div class="flex min-w-max items-center justify-center gap-1.5">
                                    @if ($isNutrition)
                                        <a href="{{ route($documentAction['nutritionRoute'], $solicitud) }}"
                                            target="_blank" rel="noopener"
                                            class="inline-flex items-center justify-center rounded-full bg-azul-prodifem px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                                            {{ $documentAction['label'] }}
                                        </a>
                                    @elseif ($mezcla)
                                        <a href="{{ route($documentAction['oncologyRoute'], $mezcla) }}"
                                            target="_blank" rel="noopener"
                                            title="{{ $documentAction['label'] }} de la mezcla #{{ $mezcla->id }}"
                                            class="inline-flex items-center justify-center rounded-full bg-azul-prodifem px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                                            {{ $documentAction['label'] }}
                                        </a>
                                    @else
                                            <button type="button" disabled
                                                class="inline-flex cursor-not-allowed items-center justify-center rounded-full bg-gray-300 px-3 py-2 text-xs font-semibold text-gray-500 opacity-80">
                                                {{ $documentAction['label'] }}
                                            </button>
                                    @endif
                                </div>
                            </td>
                        @endforeach

                        <td class="px-4 py-2 text-center whitespace-nowrap">
                            <a href="{{ $isNutrition
                                ? route('admin.nutricionales.solicitudes.envio', $solicitud)
                                : route('admin.oncologicos.mezclas.envio', $solicitud) }}"
                                target="_blank" rel="noopener"
                                class="inline-flex items-center justify-center rounded-full bg-azul-prodifem px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                                Registros de envío
                            </a>
                        </td>
                        <td class="px-4 py-2 text-center whitespace-nowrap">
                            @if ($isNutrition || $mezcla)
                                <a href="{{ $isNutrition
                                    ? route('admin.nutricionales.solicitudes.remision', $solicitud)
                                    : route('admin.oncologicos.mezclas.remision', [
                                        'solicitud' => $solicitud,
                                        'mezcla' => $mezcla,
                                    ]) }}"
                                    target="_blank" rel="noopener"
                                    class="inline-flex items-center justify-center rounded-full bg-azul-prodifem px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                                    Remisión
                                </a>
                            @else
                                <button type="button" disabled
                                    class="inline-flex cursor-not-allowed items-center justify-center rounded-full bg-gray-300 px-3 py-2 text-xs font-semibold text-gray-500 opacity-80">
                                    Remisión
                                </button>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="20" class="px-4 py-10 text-center text-sm text-gray-500">
                            No se encontraron solicitudes.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($canViewNutrition)
        <livewire:nutricionales.inspeccion-nutricional />
    @endif

    @if ($canViewOncology)
        <livewire:oncologicos.inspeccion-mezcla />
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            let inspectionReloadScheduled = false;

            const reloadAfterInspection = () => {
                if (inspectionReloadScheduled) {
                    return;
                }

                inspectionReloadScheduled = true;
                window.setTimeout(() => window.location.reload(), 150);
            };

            window.addEventListener('nutricional-inspeccionada', reloadAfterInspection);
            window.addEventListener('mezcla-inspeccionada', reloadAfterInspection);
        });
    </script>
</x-admin-layout>
