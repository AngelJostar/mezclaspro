<x-admin-layout>
    <div class="mt-2 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="text-2xl font-medium text-gray-800">Superadministrador</h1>
        </div>
    </div>

    @if (session('status'))
        <div class="mt-5 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800"
            role="status">
            {{ session('status') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mt-5 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert">
            <ul class="list-disc space-y-1 ps-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="mt-6 overflow-hidden rounded-md border border-gray-200 bg-white shadow-sm"
        aria-labelledby="authorizations-title" x-data="{ authorizationsOpen: false }">
        <button type="button"
            class="group flex min-h-16 w-full cursor-pointer items-center justify-between gap-3 px-4 py-3 text-left transition hover:bg-blue-50/50 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-blue-300"
            aria-controls="authorizations-table" :aria-expanded="authorizationsOpen.toString()"
            :aria-label="authorizationsOpen ? 'Ocultar autorizaciones' : 'Mostrar autorizaciones'"
            :title="authorizationsOpen ? 'Ocultar autorizaciones' : 'Mostrar autorizaciones'"
            @click="authorizationsOpen = ! authorizationsOpen">
            <span class="flex items-center gap-2">
                <span id="authorizations-title" role="heading" aria-level="2" class="text-lg font-semibold text-gray-900">Autorizaciones</span>
                <span class="inline-flex min-w-6 items-center justify-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-800">
                    {{ $administrators->count() }}
                </span>
            </span>

            <span class="inline-flex h-9 w-9 flex-none items-center justify-center rounded-md border border-blue-200 bg-white text-blue-700 transition group-hover:border-blue-300 group-hover:bg-blue-50">
                <svg class="h-4 w-4 transition-transform duration-200" :class="{ 'rotate-180': authorizationsOpen }"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m6 9 6 6 6-6"></path>
                </svg>
            </span>
        </button>

        <div id="authorizations-table" class="overflow-x-auto border-t border-gray-200"
            x-cloak x-show="authorizationsOpen" x-transition.opacity.duration.150ms>
            <table class="w-full min-w-[980px] table-fixed text-left text-sm text-gray-600">
                <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                    <tr>
                        <th class="w-[24%] px-4 py-3">Personal</th>
                        <th class="w-[13%] px-4 py-3">Usuario</th>
                        <th class="w-[15%] px-4 py-3">Contraseña</th>
                        <th class="w-[28%] px-4 py-3">Hospital</th>
                        <th class="w-[9%] px-4 py-3 text-center">Estado</th>
                        <th class="w-[11%] px-4 py-3 text-right">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse ($administrators as $administrator)
                        @php
                            $administratorPassword = $administrator->credential_password
                                ?: $administrator->training_credential_password;
                        @endphp
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900">
                                {{ trim($administrator->name . ' ' . $administrator->lastname) }}
                            </td>
                            <td class="px-4 py-3">{{ $administrator->username }}</td>
                            <td class="px-4 py-3">
                                @if ($administratorPassword)
                                    <span class="font-mono text-xs font-semibold text-gray-800">
                                        {{ $administratorPassword }}
                                    </span>
                                @else
                                    <span class="text-xs text-gray-400">Sin contraseña</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">{{ $administrator->hospital?->name ?? 'Sin hospital' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $administrator->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-700' }}">
                                    <span class="h-2 w-2 rounded-full {{ $administrator->is_active ? 'bg-emerald-500' : 'bg-gray-500' }}"></span>
                                    {{ $administrator->is_active ? 'Activo' : 'Bloqueado' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <form method="POST"
                                    action="{{ route('admin.superadministrator.administrators.dismiss', $administrator) }}"
                                    onsubmit="return confirm('¿Destituir este administrador? Se eliminará su rol y se bloquearán todos sus accesos.');">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                        class="inline-flex items-center gap-2 rounded-md bg-red-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-200">
                                        <i class="fa-solid fa-user-slash" aria-hidden="true"></i>
                                        Destituir
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                No hay administradores nombrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="mt-4 overflow-hidden rounded-md border border-gray-200 bg-white shadow-sm"
        aria-labelledby="waste-report-title"
        x-data="{ wasteOpen: {{ $wasteDateRange['open'] ? 'true' : 'false' }}, wasteFilter: '{{ $initialWasteView }}' }">
        <button type="button"
            class="group flex min-h-16 w-full cursor-pointer items-center justify-between gap-3 px-4 py-3 text-left transition hover:bg-amber-50/60 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-amber-300"
            aria-controls="waste-report-content" :aria-expanded="wasteOpen.toString()"
            :aria-label="wasteOpen ? 'Ocultar reporte de mermas' : 'Mostrar reporte de mermas'"
            :title="wasteOpen ? 'Ocultar reporte de mermas' : 'Mostrar reporte de mermas'"
            @click="wasteOpen = ! wasteOpen">
            <span class="flex items-center gap-2">
                <span id="waste-report-title" role="heading" aria-level="2" class="text-lg font-semibold text-gray-900">Reporte de mermas</span>
                <span class="inline-flex min-w-6 items-center justify-center rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">
                    {{ $wasteSummary['all'] }}
                </span>
            </span>

            <span class="inline-flex h-9 w-9 flex-none items-center justify-center rounded-md border border-amber-300 bg-white text-amber-700 transition group-hover:border-amber-400 group-hover:bg-amber-50">
                <svg class="h-4 w-4 transition-transform duration-200" :class="{ 'rotate-180': wasteOpen }"
                    viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="m6 9 6 6 6-6"></path>
                </svg>
            </span>
        </button>

        <div id="waste-report-content" class="border-t border-gray-200"
            x-cloak x-show="wasteOpen" x-transition.opacity.duration.150ms>
            <div class="flex flex-wrap items-center gap-2 border-b border-gray-200 bg-gray-50 px-4 py-3"
                role="tablist" aria-label="Tipo de merma">
                <button type="button" role="tab" @click="wasteFilter = 'all'"
                    :aria-selected="(wasteFilter === 'all').toString()"
                    class="inline-flex h-9 items-center gap-2 rounded-md border px-3 text-xs font-semibold transition focus:outline-none focus:ring-2 focus:ring-blue-300"
                    :class="wasteFilter === 'all' ? 'border-blue-800 text-white' : 'border-gray-200 bg-white text-gray-700 hover:bg-gray-100'"
                    :style="wasteFilter === 'all' ? 'background-color: #263f7c; border-color: #263f7c; color: #ffffff;' : ''">
                    Todas
                    <span class="inline-flex min-w-5 items-center justify-center rounded px-1.5 py-0.5 text-[10px]"
                        :class="wasteFilter === 'all' ? 'text-blue-900' : 'bg-gray-100 text-gray-700'"
                        :style="wasteFilter === 'all' ? 'background-color: #ffffff; color: #263f7c;' : ''">
                        {{ $wasteSummary['all'] }}
                    </span>
                </button>
                <button type="button" role="tab" @click="wasteFilter = 'remanente'"
                    :aria-selected="(wasteFilter === 'remanente').toString()"
                    class="inline-flex h-9 items-center gap-2 rounded-md border px-3 text-xs font-semibold transition focus:outline-none focus:ring-2 focus:ring-amber-300"
                    :class="wasteFilter === 'remanente' ? 'border-amber-500 bg-amber-400 text-slate-950' : 'border-gray-200 bg-white text-gray-700 hover:bg-amber-50'">
                    Merma de remanente
                    <span class="inline-flex min-w-5 items-center justify-center rounded px-1.5 py-0.5 text-[10px]"
                        :class="wasteFilter === 'remanente' ? 'bg-white/40 text-slate-950' : 'bg-amber-100 text-amber-800'">
                        {{ $wasteSummary['remanente'] }}
                    </span>
                </button>
                <button type="button" role="tab" @click="wasteFilter = 'frasco'"
                    :aria-selected="(wasteFilter === 'frasco').toString()"
                    class="inline-flex h-9 items-center gap-2 rounded-md border px-3 text-xs font-semibold transition focus:outline-none focus:ring-2 focus:ring-red-300"
                    :class="wasteFilter === 'frasco' ? 'border-red-700 bg-red-700 text-white' : 'border-gray-200 bg-white text-gray-700 hover:bg-red-50'">
                    Merma de frasco
                    <span class="inline-flex min-w-5 items-center justify-center rounded px-1.5 py-0.5 text-[10px]"
                        :class="wasteFilter === 'frasco' ? 'bg-white/20 text-white' : 'bg-red-100 text-red-800'">
                        {{ $wasteSummary['frasco'] }}
                    </span>
                </button>
                <button type="button" role="tab" @click="wasteFilter = 'requests'"
                    :aria-selected="(wasteFilter === 'requests').toString()"
                    class="inline-flex h-9 items-center gap-2 rounded-md border px-3 text-xs font-semibold transition focus:outline-none focus:ring-2 focus:ring-emerald-300"
                    :class="wasteFilter === 'requests' ? 'text-white' : 'border-gray-200 bg-white text-gray-700 hover:bg-emerald-50'"
                    :style="wasteFilter === 'requests' ? 'background-color: #047857; border-color: #047857; color: #ffffff;' : ''">
                    Solicitudes de Merma
                    <span class="inline-flex min-w-5 items-center justify-center rounded px-1.5 py-0.5 text-[10px]"
                        :class="wasteFilter === 'requests' ? 'text-emerald-900' : 'bg-amber-100 text-amber-800'"
                        :style="wasteFilter === 'requests' ? 'background-color: #fef3c7; color: #92400e;' : ''">
                        {{ $pendingWasteAuthorizationCount }}
                    </span>
                </button>
            </div>

            <div x-cloak x-show="wasteFilter !== 'requests'">
                <form method="GET" action="{{ route('admin.superadministrator.index') }}"
                class="flex flex-wrap items-end gap-3 border-b border-gray-200 bg-white px-4 py-3"
                aria-label="Filtrar reporte de mermas por periodo">
                <input type="hidden" name="waste_open" value="1">

                <fieldset>
                    <legend class="mb-1 text-xs font-semibold text-gray-700">Desde</legend>
                    <div class="flex gap-2">
                        <label for="waste_from_month" class="sr-only">Mes desde</label>
                        <select id="waste_from_month" name="waste_from_month" required
                            class="h-10 w-40 rounded-md border-gray-300 text-sm text-gray-800 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Mes</option>
                            @foreach ($wasteMonthOptions as $monthValue => $monthLabel)
                                @php
                                    $normalizedMonthValue = str_pad((string) $monthValue, 2, '0', STR_PAD_LEFT);
                                @endphp
                                <option value="{{ $normalizedMonthValue }}" @selected($wasteDateRange['from_month'] === $normalizedMonthValue)>
                                    {{ $monthLabel }}
                                </option>
                            @endforeach
                        </select>

                        <label for="waste_from_year" class="sr-only">Año desde</label>
                        <select id="waste_from_year" name="waste_from_year" required
                            class="h-10 w-28 rounded-md border-gray-300 text-sm text-gray-800 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Año</option>
                            @foreach ($wasteYearOptions as $year)
                                <option value="{{ $year }}" @selected($wasteDateRange['from_year'] === $year)>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                </fieldset>

                <fieldset>
                    <legend class="mb-1 text-xs font-semibold text-gray-700">Hasta</legend>
                    <div class="flex gap-2">
                        <label for="waste_to_month" class="sr-only">Mes hasta</label>
                        <select id="waste_to_month" name="waste_to_month" required
                            class="h-10 w-40 rounded-md border-gray-300 text-sm text-gray-800 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Mes</option>
                            @foreach ($wasteMonthOptions as $monthValue => $monthLabel)
                                @php
                                    $normalizedMonthValue = str_pad((string) $monthValue, 2, '0', STR_PAD_LEFT);
                                @endphp
                                <option value="{{ $normalizedMonthValue }}" @selected($wasteDateRange['to_month'] === $normalizedMonthValue)>
                                    {{ $monthLabel }}
                                </option>
                            @endforeach
                        </select>

                        <label for="waste_to_year" class="sr-only">Año hasta</label>
                        <select id="waste_to_year" name="waste_to_year" required
                            class="h-10 w-28 rounded-md border-gray-300 text-sm text-gray-800 shadow-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="">Año</option>
                            @foreach ($wasteYearOptions as $year)
                                <option value="{{ $year }}" @selected($wasteDateRange['to_year'] === $year)>{{ $year }}</option>
                            @endforeach
                        </select>
                    </div>
                </fieldset>

                <button type="submit"
                    class="inline-flex h-10 items-center justify-center gap-2 rounded-md border px-4 text-sm font-semibold transition hover:opacity-90 focus:outline-none focus:ring-2 focus:ring-blue-300"
                    style="background-color: #263f7c; border-color: #263f7c; color: #ffffff;">
                    <i class="fa-solid fa-filter" aria-hidden="true"></i>
                    Aplicar filtro
                </button>

                @if ($wasteDateRange['active'])
                    <a href="{{ route('admin.superadministrator.index', ['waste_open' => 1]) }}"
                        class="inline-flex h-10 items-center justify-center gap-2 rounded-md border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gray-300">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                        Borrar filtro
                    </a>
                @else
                    <button type="button" disabled
                        class="inline-flex h-10 cursor-not-allowed items-center justify-center gap-2 rounded-md border border-gray-200 bg-gray-100 px-4 text-sm font-semibold text-gray-400">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                        Borrar filtro
                    </button>
                @endif
                </form>

                <div class="overflow-x-auto" data-disable-sticky-x
                    style="min-height: clamp(500px, 62vh, 760px);">
                    <table id="waste-report-table" class="w-full table-fixed text-left text-sm text-gray-600"
                        style="min-width: 2525px;">
                    <colgroup>
                        <col style="width: 175px;">
                        <col style="width: 190px;">
                        <col style="width: 140px;">
                        <col style="width: 235px;">
                        <col style="width: 215px;">
                        <col style="width: 170px;">
                        <col style="width: 150px;">
                        <col style="width: 255px;">
                        <col style="width: 240px;">
                        <col style="width: 230px;">
                        <col style="width: 165px;">
                        <col style="width: 200px;">
                        <col style="width: 160px;">
                    </colgroup>
                    <thead class="bg-gray-50 text-[11px] uppercase text-gray-700">
                        <tr>
                            <x-filterable-table-header column="0" trigger-class="js-waste-column-filter"
                                sort-class="js-waste-column-sort" sort-type="date" scope="col">Fecha y hora</x-filterable-table-header>
                            <x-filterable-table-header column="1" trigger-class="js-waste-column-filter"
                                sort-class="js-waste-column-sort" scope="col">Tipo de merma</x-filterable-table-header>
                            <x-filterable-table-header column="2" trigger-class="js-waste-column-filter"
                                sort-class="js-waste-column-sort" scope="col">Área</x-filterable-table-header>
                            <x-filterable-table-header column="3" trigger-class="js-waste-column-filter"
                                sort-class="js-waste-column-sort" scope="col">Producto</x-filterable-table-header>
                            <x-filterable-table-header column="4" trigger-class="js-waste-column-filter"
                                sort-class="js-waste-column-sort" scope="col">Presentación</x-filterable-table-header>
                            <x-filterable-table-header column="5" trigger-class="js-waste-column-filter"
                                sort-class="js-waste-column-sort" scope="col">Marca</x-filterable-table-header>
                            <x-filterable-table-header column="6" trigger-class="js-waste-column-filter"
                                sort-class="js-waste-column-sort" scope="col">Lote</x-filterable-table-header>
                            <x-filterable-table-header column="7" trigger-class="js-waste-column-filter"
                                sort-class="js-waste-column-sort" sort-type="number" align="right" scope="col">
                                <span class="block">Precio costo por</span>
                                <span class="block whitespace-nowrap">
                                    mililitro <span class="text-[10px] font-medium normal-case text-gray-500">(Precio de compra)</span>
                                </span>
                            </x-filterable-table-header>
                            <x-filterable-table-header column="8" trigger-class="js-waste-column-filter"
                                sort-class="js-waste-column-sort" sort-type="number" align="right" scope="col">
                                <span class="block">Precio costo por</span>
                                <span class="block whitespace-nowrap">
                                    frasco <span class="text-[10px] font-medium normal-case text-gray-500">(Precio de compra)</span>
                                </span>
                            </x-filterable-table-header>
                            <x-filterable-table-header column="9" trigger-class="js-waste-column-filter"
                                sort-class="js-waste-column-sort" scope="col">Central / almacén</x-filterable-table-header>
                            <x-filterable-table-header column="10" trigger-class="js-waste-column-filter"
                                sort-class="js-waste-column-sort" sort-type="number" align="right" scope="col">Cantidad</x-filterable-table-header>
                            <x-filterable-table-header column="11" trigger-class="js-waste-column-filter"
                                sort-class="js-waste-column-sort" scope="col">Motivo</x-filterable-table-header>
                            <x-filterable-table-header column="12" trigger-class="js-waste-column-filter"
                                sort-class="js-waste-column-sort" scope="col">Responsable</x-filterable-table-header>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @foreach ($wasteRecords as $record)
                            <tr x-cloak class="js-waste-filter-row"
                                x-show="wasteFilter === 'all' || wasteFilter === '{{ $record['type'] }}'">
                                <td class="px-3 py-3 align-top whitespace-nowrap"
                                    data-sort-value="{{ $record['occurred_at']?->format('Y-m-d H:i:s') }}">
                                    <span class="block font-medium text-gray-900">{{ $record['occurred_at']?->format('d/m/Y') ?? '-' }}</span>
                                    <span class="block text-xs text-gray-500">{{ $record['occurred_at']?->format('H:i') ?? '-' }}</span>
                                </td>
                                <td class="px-3 py-3 align-top" data-sort-value="{{ $record['type_label'] }}">
                                    <span class="inline-flex rounded px-2 py-1 text-xs font-semibold {{ $record['type'] === 'remanente' ? 'bg-amber-100 text-amber-900' : 'bg-red-100 text-red-800' }}">
                                        {{ $record['type_label'] }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 align-top font-medium text-gray-800" data-sort-value="{{ $record['area'] }}">{{ $record['area'] }}</td>
                                <td class="px-3 py-3 align-top font-semibold text-gray-900" data-sort-value="{{ $record['product'] }}">{{ $record['product'] }}</td>
                                <td class="px-3 py-3 align-top" data-sort-value="{{ $record['presentation'] }}">{{ $record['presentation'] }}</td>
                                <td class="px-3 py-3 align-top font-medium text-gray-800" data-sort-value="{{ $record['brand'] }}">{{ $record['brand'] }}</td>
                                <td class="px-3 py-3 align-top font-mono text-xs text-gray-800" data-sort-value="{{ $record['lot'] }}">{{ $record['lot'] }}</td>
                                <td class="px-3 py-3 text-right align-top whitespace-nowrap"
                                    data-sort-value="{{ $record['purchase_cost_per_ml'] }}">
                                    @if ($record['purchase_cost_per_ml'] !== null)
                                        <span class="font-semibold text-gray-900">
                                            ${{ number_format($record['purchase_cost_per_ml'], 4) }}
                                        </span>
                                    @elseif ($record['purchase_cost_per_bottle'] === null)
                                        <span class="text-xs text-gray-400">Sin costo registrado</span>
                                    @else
                                        <span class="text-xs text-gray-400">Sin volumen configurado</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 text-right align-top whitespace-nowrap"
                                    data-sort-value="{{ $record['purchase_cost_per_bottle'] }}">
                                    @if ($record['purchase_cost_per_bottle'] !== null)
                                        <span class="font-semibold text-gray-900">
                                            ${{ number_format($record['purchase_cost_per_bottle'], 4) }}
                                        </span>
                                    @else
                                        <span class="text-xs text-gray-400">Sin costo registrado</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 align-top"
                                    data-sort-value="{{ $record['laboratory'] }} {{ $record['warehouse'] }}">
                                    <span class="block font-medium text-gray-900">{{ $record['laboratory'] }}</span>
                                    <span class="block text-xs text-gray-500">{{ $record['warehouse'] }}</span>
                                </td>
                                <td class="px-3 py-3 text-right align-top whitespace-nowrap"
                                    data-sort-value="{{ $record['quantity_ml'] > 0 ? $record['quantity_ml'] : $record['quantity_containers'] }}">
                                    @if ($record['quantity_containers'] > 0)
                                        <span class="block font-semibold text-gray-900">
                                            {{ number_format($record['quantity_containers'], 2) }}
                                            {{ abs($record['quantity_containers'] - 1) < 0.0001 ? 'frasco' : 'frascos' }}
                                        </span>
                                    @endif
                                    @if ($record['quantity_ml'] > 0)
                                        <span class="block text-xs font-medium text-gray-600">
                                            {{ number_format($record['quantity_ml'], 2) }} mL
                                        </span>
                                    @endif
                                    @if ($record['quantity_containers'] <= 0 && $record['quantity_ml'] <= 0)
                                        <span class="text-gray-400">-</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 align-top whitespace-normal" data-sort-value="{{ $record['reason'] }}">{{ $record['reason'] }}</td>
                                <td class="px-3 py-3 align-top" data-sort-value="{{ $record['user'] }}">{{ $record['user'] }}</td>
                            </tr>
                        @endforeach

                        @if ($wasteSummary['all'] === 0)
                            <tr x-show="wasteFilter === 'all'">
                                <td colspan="13" class="px-4 py-8 text-center text-gray-500">No hay mermas registradas.</td>
                            </tr>
                        @endif
                        @if ($wasteSummary['remanente'] === 0)
                            <tr x-cloak x-show="wasteFilter === 'remanente'">
                                <td colspan="13" class="px-4 py-8 text-center text-gray-500">No hay mermas de remanente registradas.</td>
                            </tr>
                        @endif
                        @if ($wasteSummary['frasco'] === 0)
                            <tr x-cloak x-show="wasteFilter === 'frasco'">
                                <td colspan="13" class="px-4 py-8 text-center text-gray-500">No hay mermas de frasco registradas.</td>
                            </tr>
                        @endif
                    </tbody>
                    </table>
                </div>
            </div>

            <div x-cloak x-show="wasteFilter === 'requests'" class="overflow-x-auto" data-disable-sticky-x
                style="min-height: clamp(500px, 62vh, 760px);">
                <table class="w-full table-fixed text-left text-sm text-gray-600" style="min-width: 2050px;">
                    <colgroup>
                        <col style="width: 155px;">
                        <col style="width: 125px;">
                        <col style="width: 125px;">
                        <col style="width: 220px;">
                        <col style="width: 190px;">
                        <col style="width: 160px;">
                        <col style="width: 140px;">
                        <col style="width: 215px;">
                        <col style="width: 145px;">
                        <col style="width: 230px;">
                        <col style="width: 185px;">
                        <col style="width: 260px;">
                    </colgroup>
                    <thead class="bg-gray-50 text-[11px] uppercase text-gray-700">
                        <tr>
                            <th class="px-3 py-3">Fecha y hora</th>
                            <th class="px-3 py-3">Estado</th>
                            <th class="px-3 py-3">Área</th>
                            <th class="px-3 py-3">Producto</th>
                            <th class="px-3 py-3">Presentación</th>
                            <th class="px-3 py-3">Marca</th>
                            <th class="px-3 py-3">Lote</th>
                            <th class="px-3 py-3">Central / almacén</th>
                            <th class="px-3 py-3 text-right">Cantidad</th>
                            <th class="px-3 py-3">Motivo</th>
                            <th class="px-3 py-3">Solicitante / revisión</th>
                            <th class="px-3 py-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @forelse ($wasteAuthorizationRequests as $wasteRequest)
                            @php
                                $statusLabel = match ($wasteRequest->status) {
                                    \App\Models\WasteAuthorizationRequest::STATUS_APPROVED => 'Autorizada',
                                    \App\Models\WasteAuthorizationRequest::STATUS_REJECTED => 'Rechazada',
                                    default => 'Pendiente',
                                };
                                $statusClasses = match ($wasteRequest->status) {
                                    \App\Models\WasteAuthorizationRequest::STATUS_APPROVED => 'bg-emerald-100 text-emerald-800',
                                    \App\Models\WasteAuthorizationRequest::STATUS_REJECTED => 'bg-red-100 text-red-800',
                                    default => 'bg-amber-100 text-amber-900',
                                };
                                $areaLabel = match ($wasteRequest->domain) {
                                    'antibiotico' => 'Antibiótico',
                                    'nutricional' => 'Nutricional',
                                    default => 'Oncológico',
                                };
                                $requesterName = trim(($wasteRequest->requester?->name ?? '').' '.($wasteRequest->requester?->lastname ?? ''));
                                $reviewerName = trim(($wasteRequest->reviewer?->name ?? '').' '.($wasteRequest->reviewer?->lastname ?? ''));
                            @endphp
                            <tr>
                                <td class="px-3 py-3 align-top whitespace-nowrap">
                                    <span class="block font-medium text-gray-900">{{ $wasteRequest->created_at?->format('d/m/Y') ?? '-' }}</span>
                                    <span class="block text-xs text-gray-500">{{ $wasteRequest->created_at?->format('H:i') ?? '-' }}</span>
                                </td>
                                <td class="px-3 py-3 align-top">
                                    <span class="inline-flex rounded px-2 py-1 text-xs font-semibold {{ $statusClasses }}">
                                        {{ $statusLabel }}
                                    </span>
                                </td>
                                <td class="px-3 py-3 align-top font-medium text-gray-800">{{ $areaLabel }}</td>
                                <td class="px-3 py-3 align-top font-semibold text-gray-900">{{ $wasteRequest->snapshot_product }}</td>
                                <td class="px-3 py-3 align-top">{{ $wasteRequest->snapshot_presentation ?: 'Sin presentación' }}</td>
                                <td class="px-3 py-3 align-top font-medium text-gray-800">{{ $wasteRequest->snapshot_brand ?: 'Sin marca' }}</td>
                                <td class="px-3 py-3 align-top font-mono text-xs text-gray-800">{{ $wasteRequest->snapshot_lot ?: 'Sin lote' }}</td>
                                <td class="px-3 py-3 align-top">
                                    <span class="block font-medium text-gray-900">{{ $wasteRequest->snapshot_laboratory ?: 'Sin central' }}</span>
                                    <span class="block text-xs text-gray-500">{{ $wasteRequest->snapshot_warehouse ?: 'Sin almacén' }}</span>
                                </td>
                                <td class="px-3 py-3 text-right align-top whitespace-nowrap">
                                    <span class="block font-semibold text-gray-900">
                                        {{ number_format((int) $wasteRequest->quantity_containers) }}
                                        {{ (int) $wasteRequest->quantity_containers === 1 ? 'frasco' : 'frascos' }}
                                    </span>
                                    <span class="block text-xs font-medium text-gray-600">
                                        {{ number_format((float) $wasteRequest->quantity_ml, 2) }} mL
                                    </span>
                                </td>
                                <td class="px-3 py-3 align-top whitespace-normal">{{ $wasteRequest->reason }}</td>
                                <td class="px-3 py-3 align-top">
                                    <span class="block font-medium text-gray-900">
                                        {{ $requesterName !== '' ? $requesterName : ($wasteRequest->requester?->username ?: 'Usuario no disponible') }}
                                    </span>
                                    @if ($wasteRequest->reviewer)
                                        <span class="mt-1 block text-xs text-gray-500">
                                            Revisó: {{ $reviewerName !== '' ? $reviewerName : $wasteRequest->reviewer->username }}
                                        </span>
                                        <span class="block text-xs text-gray-500">
                                            {{ $wasteRequest->reviewed_at?->format('d/m/Y H:i') }}
                                        </span>
                                    @endif
                                    @if ($wasteRequest->review_notes)
                                        <span class="mt-1 block text-xs text-gray-600">{{ $wasteRequest->review_notes }}</span>
                                    @endif
                                </td>
                                <td class="px-3 py-3 align-top">
                                    @if ($wasteRequest->status === \App\Models\WasteAuthorizationRequest::STATUS_PENDING)
                                        <form method="POST"
                                            action="{{ route('admin.superadministrator.waste-requests.approve', $wasteRequest) }}"
                                            class="space-y-2">
                                            @csrf
                                            @method('PATCH')
                                            <label for="waste-review-notes-{{ $wasteRequest->id }}" class="sr-only">Observaciones de revisión</label>
                                            <textarea id="waste-review-notes-{{ $wasteRequest->id }}" name="review_notes" rows="2" maxlength="500"
                                                class="w-full rounded-md border-gray-300 text-xs shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                                placeholder="Observaciones"></textarea>
                                            <div class="flex flex-wrap gap-2">
                                                <button type="submit"
                                                    formaction="{{ route('admin.superadministrator.waste-requests.approve', $wasteRequest) }}"
                                                    class="inline-flex h-8 items-center gap-1.5 rounded-md bg-emerald-600 px-3 text-xs font-semibold text-white hover:bg-emerald-700"
                                                    onclick="return confirm('¿Autorizar esta merma de frasco?');">
                                                    <i class="fa-solid fa-check" aria-hidden="true"></i>
                                                    Autorizar
                                                </button>
                                                <button type="submit"
                                                    formaction="{{ route('admin.superadministrator.waste-requests.reject', $wasteRequest) }}"
                                                    class="inline-flex h-8 items-center gap-1.5 rounded-md bg-red-600 px-3 text-xs font-semibold text-white hover:bg-red-700"
                                                    onclick="if (!this.form.elements['review_notes'].value.trim()) { alert('Indica el motivo del rechazo.'); return false; } return confirm('¿Rechazar esta solicitud de merma?');">
                                                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                                                    Rechazar
                                                </button>
                                            </div>
                                        </form>
                                    @else
                                        <span class="text-xs text-gray-400">Solicitud atendida</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="12" class="px-4 py-10 text-center text-gray-500">
                                    No hay solicitudes de merma registradas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </section>

    @push('js')
        <script>
            @include('admin.catalogo-listas.partials.column-filter-script')

            document.addEventListener('DOMContentLoaded', function() {
                window.createExcelColumnFilters({
                    tableId: 'waste-report-table',
                    rowSelector: '.js-waste-filter-row',
                    triggerSelector: '.js-waste-column-filter',
                    instanceId: 'waste-report',
                    onChange() {
                        document.querySelectorAll('.js-waste-filter-row').forEach((row) => {
                            row.classList.toggle('hidden', row.dataset.columnFilterMatch === '0');
                        });
                    },
                });

                const table = document.getElementById('waste-report-table');
                const tbody = table?.tBodies[0];
                const sortButtons = Array.from(table?.querySelectorAll('.js-waste-column-sort') || []);
                let activeSortColumn = null;
                let activeSortDirection = 'asc';

                sortButtons.forEach((button) => {
                    const icon = button.querySelector('[data-sort-icon]');

                    if (icon) {
                        icon.className = 'text-sm font-black leading-none';
                        icon.textContent = '\u2195';
                    }
                });

                const sortableValue = (row, column) => String(
                    row.cells[column]?.dataset.sortValue ?? row.cells[column]?.textContent ?? ''
                ).replace(/\s+/g, ' ').trim();

                const compareValues = (leftValue, rightValue, type) => {
                    const leftIsEmpty = leftValue === '';
                    const rightIsEmpty = rightValue === '';

                    if (leftIsEmpty || rightIsEmpty) {
                        if (leftIsEmpty && rightIsEmpty) return 0;
                        return leftIsEmpty ? 1 : -1;
                    }

                    if (type === 'number') {
                        return Number(leftValue) - Number(rightValue);
                    }

                    return leftValue.localeCompare(rightValue, 'es', {
                        numeric: true,
                        sensitivity: 'base',
                    });
                };

                sortButtons.forEach((button) => {
                    button.addEventListener('click', () => {
                        const column = Number(button.dataset.sortColumn);
                        const type = button.dataset.sortType || 'text';
                        const direction = activeSortColumn === column && activeSortDirection === 'asc'
                            ? 'desc'
                            : 'asc';
                        const rows = Array.from(tbody?.querySelectorAll('.js-waste-filter-row') || []);

                        rows.sort((leftRow, rightRow) => {
                            const leftValue = sortableValue(leftRow, column);
                            const rightValue = sortableValue(rightRow, column);
                            const result = compareValues(leftValue, rightValue, type);

                            return direction === 'asc' ? result : -result;
                        });

                        rows.forEach((row) => tbody?.appendChild(row));
                        activeSortColumn = column;
                        activeSortDirection = direction;

                        sortButtons.forEach((sortButton) => {
                            const isActive = sortButton === button;
                            const icon = sortButton.querySelector('[data-sort-icon]');
                            const label = sortButton.dataset.sortLabel || 'columna';

                            sortButton.classList.toggle('border-blue-400', isActive);
                            sortButton.classList.toggle('bg-blue-100', isActive);
                            sortButton.classList.toggle('text-blue-700', isActive);
                            sortButton.setAttribute('aria-pressed', isActive ? 'true' : 'false');
                            sortButton.setAttribute('aria-label', isActive
                                ? `Ordenar ${label} ${direction === 'asc' ? 'descendente' : 'ascendente'}`
                                : `Ordenar ${label}`);

                            if (icon) {
                                icon.className = 'text-sm font-black leading-none';
                                icon.textContent = isActive
                                    ? (direction === 'asc' ? '\u2191' : '\u2193')
                                    : '\u2195';
                            }
                        });
                    });
                });
            });
        </script>
    @endpush

</x-admin-layout>
