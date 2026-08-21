<x-admin-layout>
    @php
        $statusClasses = [
            \App\Models\DistributionRoute::STATUS_PENDING => 'border-blue-200 bg-blue-50 text-blue-700',
            \App\Models\DistributionRoute::STATUS_IN_ROUTE => 'border-emerald-200 bg-emerald-50 text-emerald-700',
            \App\Models\DistributionRoute::STATUS_DELAYED => 'border-amber-300 bg-amber-50 text-amber-700',
            \App\Models\DistributionRoute::STATUS_COMPLETED => 'border-gray-200 bg-gray-100 text-gray-600',
        ];
    @endphp

    <div class="rounded-lg bg-white p-5 shadow-sm md:p-6">
        <header class="flex flex-col gap-4 border-b border-gray-200 pb-5 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <nav class="mb-2 text-xs font-medium text-gray-500" aria-label="Ruta de navegación">
                    <a href="{{ route('admin.distribution.index') }}" class="hover:text-blue-700">Distribución</a>
                    <span class="mx-2 text-gray-300">/</span>
                    <span class="text-blue-700">Seguimiento de ruta</span>
                </nav>
                <div class="flex flex-wrap items-center gap-3">
                    <h1 class="text-2xl font-bold text-gray-950 md:text-3xl">{{ $distributionRoute->name }}</h1>
                    <span class="inline-flex rounded-md border px-3 py-1 text-xs font-semibold {{ $statusClasses[$distributionRoute->status] ?? $statusClasses[\App\Models\DistributionRoute::STATUS_PENDING] }}">
                        {{ $statuses[$distributionRoute->status] ?? $distributionRoute->status }}
                    </span>
                </div>
                <p class="mt-1 text-sm text-gray-500">{{ $distributionRoute->code }} · Horario {{ $distributionRoute->scheduleLabel() }}</p>
            </div>

            <div class="flex flex-wrap gap-2">
                <a href="{{ route('admin.distribution.index') }}"
                    class="inline-flex h-10 items-center justify-center gap-2 rounded-md border border-gray-300 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                    Volver a rutas
                </a>
                <a href="{{ route('admin.distribution.edit', $distributionRoute) }}"
                    class="inline-flex h-10 items-center justify-center gap-2 rounded-md bg-blue-700 px-4 text-sm font-semibold text-white hover:bg-blue-800">
                    <i class="fa-solid fa-pen" aria-hidden="true"></i>
                    Editar ruta
                </a>
            </div>
        </header>

        <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(0,1fr)_320px]">
            <section aria-labelledby="route-stops-title">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 id="route-stops-title" class="text-lg font-bold text-gray-950">Recorrido y hospitales</h2>
                        <p class="mt-1 text-sm text-gray-500">Orden de entrega definido para esta ruta.</p>
                    </div>

                    <form method="POST" action="{{ route('admin.distribution.status.update', $distributionRoute) }}"
                        class="flex items-end gap-2" onsubmit="return confirm('¿Estás seguro de cambiar el estatus de la ruta?')">
                        @csrf
                        @method('PATCH')
                        <label class="block">
                            <span class="mb-1 block text-xs font-medium text-gray-600">Estatus de la ruta</span>
                            <select name="status" class="h-10 min-w-40 rounded-md border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}" @selected($distributionRoute->status === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </label>
                        <button type="submit" class="inline-flex h-10 w-10 items-center justify-center rounded-md bg-blue-700 text-white hover:bg-blue-800"
                            title="Guardar estatus" aria-label="Guardar estatus">
                            <i class="fa-solid fa-check" aria-hidden="true"></i>
                        </button>
                    </form>
                </div>

                <ol class="mt-4 overflow-hidden rounded-md border border-gray-200 bg-white">
                    @foreach ($distributionRoute->hospitals as $hospital)
                        <li class="grid gap-3 border-b border-gray-200 px-4 py-4 last:border-b-0 sm:grid-cols-[48px_minmax(0,1fr)_auto] sm:items-center">
                            <span class="inline-flex h-10 w-10 items-center justify-center rounded-full bg-blue-50 text-sm font-bold text-blue-700">
                                {{ $hospital->pivot->stop_order }}
                            </span>
                            <div class="min-w-0">
                                <p class="font-semibold text-gray-950">{{ $hospital->short_name ?: $hospital->name }}</p>
                                <p class="mt-1 text-sm text-gray-500">{{ $hospital->adress ?: 'Dirección no registrada' }}</p>
                            </div>
                            @if ($hospital->google_maps_url)
                                <a href="{{ $hospital->google_maps_url }}" target="_blank" rel="noopener noreferrer"
                                    class="inline-flex h-9 items-center justify-center gap-2 rounded-md border border-blue-300 px-3 text-xs font-semibold text-blue-700 hover:bg-blue-50">
                                    <i class="fa-solid fa-location-dot" aria-hidden="true"></i>
                                    Abrir mapa
                                </a>
                            @else
                                <span class="text-xs font-medium text-gray-400">Sin ubicación</span>
                            @endif
                        </li>
                    @endforeach
                </ol>
            </section>

            <aside class="space-y-6">
                <section class="rounded-md border border-gray-200 p-4" aria-labelledby="route-qr-title">
                    <h2 id="route-qr-title" class="font-bold text-gray-950">QR de la ruta</h2>
                    <p class="mt-1 text-xs text-gray-500">Escanea para abrir el seguimiento.</p>
                    <img src="{{ route('admin.distribution.qr', $distributionRoute) }}" alt="Código QR de {{ $distributionRoute->name }}"
                        class="mx-auto mt-4 h-44 w-44" width="176" height="176">
                    <a href="{{ route('admin.distribution.qr', [$distributionRoute, 'download' => 1]) }}"
                        class="mt-3 inline-flex h-10 w-full items-center justify-center gap-2 rounded-md border-2 border-blue-600 text-sm font-semibold text-blue-700 hover:bg-blue-50">
                        <i class="fa-solid fa-download" aria-hidden="true"></i>
                        Descargar QR
                    </a>
                </section>

                <section class="rounded-md border border-gray-200 p-4" aria-labelledby="route-messengers-title">
                    <h2 id="route-messengers-title" class="font-bold text-gray-950">Mensajeros asignados</h2>
                    <div class="mt-3 space-y-3">
                        @foreach ($distributionRoute->messengers as $messenger)
                            <div class="flex items-center gap-3">
                                <span class="inline-flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-cyan-100 text-xs font-bold text-cyan-800">
                                    {{ mb_strtoupper(mb_substr($messenger->name, 0, 1).mb_substr($messenger->lastname ?: $messenger->name, 0, 1)) }}
                                </span>
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-gray-900">{{ trim($messenger->name.' '.$messenger->lastname) }}</p>
                                    <p class="truncate text-xs text-gray-500">{{ $messenger->username }}</p>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>

                <section class="rounded-md border border-gray-200 p-4 text-sm" aria-labelledby="route-audit-title">
                    <h2 id="route-audit-title" class="font-bold text-gray-950">Registro</h2>
                    <dl class="mt-3 space-y-2 text-gray-600">
                        <div class="flex justify-between gap-3">
                            <dt>Creada por</dt>
                            <dd class="text-right font-medium text-gray-900">{{ $distributionRoute->creator ? trim($distributionRoute->creator->name.' '.$distributionRoute->creator->lastname) : 'Sistema' }}</dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt>Actualización</dt>
                            <dd class="text-right font-medium text-gray-900">{{ $distributionRoute->updated_at?->format('d/m/Y H:i') }}</dd>
                        </div>
                    </dl>
                </section>
            </aside>
        </div>
    </div>
</x-admin-layout>
