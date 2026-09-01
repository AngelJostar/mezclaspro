<x-admin-layout>
    @php
        $reportColumns = collect($reportTemplates)
            ->mapWithKeys(fn (array $template, string $key) => [$key => $template['name']])
            ->all();
    @endphp

    <div class="mt-2 mb-4">
        <h1 class="text-2xl font-medium text-gray-800">Reportes de instituciones</h1>
        <p class="text-sm text-gray-500 mt-1">
            Desde aqui puedes descargar los reportes generales por institucion.
        </p>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-3">
        <div class="mb-3 flex flex-col gap-3 xl:flex-row xl:items-start xl:justify-between">
            <button type="button" id="custom-report-create"
                class="inline-flex h-10 shrink-0 self-start items-center justify-center gap-2 rounded-md bg-blue-900 px-4 text-sm font-semibold text-white hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-400 focus:ring-offset-2">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
                Crear nuevo reporte
            </button>

            <form method="GET" action="{{ route('admin.instituciones.reportes') }}"
                class="flex flex-1 flex-col gap-2 md:flex-row md:items-center md:justify-end">
            <details class="group relative w-full md:w-auto" @if ($errors->has('daily_from') || $errors->has('daily_to')) open @endif>
                <summary
                    class="flex cursor-pointer list-none items-center justify-center gap-2 rounded-md border border-blue-200 bg-blue-50 px-3 py-2 text-sm font-medium text-blue-800 hover:bg-blue-100 [&::-webkit-details-marker]:hidden">
                    <i class="fa-regular fa-calendar-days"></i>
                    <span>Rango reporte diario</span>
                    <span class="rounded bg-white px-1.5 py-0.5 text-[11px] text-slate-600">
                        {{ \Carbon\Carbon::parse($dailyReportFrom)->format('d/m/Y') }} -
                        {{ \Carbon\Carbon::parse($dailyReportTo)->format('d/m/Y') }}
                    </span>
                    <i class="fa-solid fa-chevron-down text-[10px] transition-transform group-open:rotate-180"></i>
                </summary>

                <div
                    class="absolute right-0 z-30 mt-2 w-full min-w-[310px] rounded-md border border-slate-200 bg-white p-3 shadow-lg md:w-[360px]">
                    <p class="mb-2 text-xs text-slate-500">
                        El reporte incluye por defecto todas las entregas del mes corriente.
                    </p>
                    <div class="grid grid-cols-2 gap-2">
                        <label class="text-xs font-medium text-slate-700">
                            Desde
                            <input type="date" name="daily_from" value="{{ $dailyReportFrom }}"
                                class="mt-1 w-full rounded-md border-slate-300 px-2 py-2 text-sm focus:border-blue-400 focus:ring-blue-400">
                        </label>
                        <label class="text-xs font-medium text-slate-700">
                            Hasta
                            <input type="date" name="daily_to" value="{{ $dailyReportTo }}"
                                class="mt-1 w-full rounded-md border-slate-300 px-2 py-2 text-sm focus:border-blue-400 focus:ring-blue-400">
                        </label>
                    </div>
                    @if ($errors->has('daily_from') || $errors->has('daily_to'))
                        <p class="mt-2 text-xs font-medium text-red-600">
                            {{ $errors->first('daily_from') ?: $errors->first('daily_to') }}
                        </p>
                    @endif
                    <button type="submit"
                        class="mt-3 inline-flex w-full items-center justify-center gap-2 rounded-md bg-blue-900 px-3 py-2 text-sm font-medium text-white hover:bg-blue-800">
                        <i class="fa-solid fa-check"></i>
                        Aplicar rango
                    </button>
                </div>
            </details>

            <div class="relative w-full md:max-w-md">
                <input type="text" name="search" value="{{ $search }}"
                    placeholder="Buscar institucion..."
                    class="w-full rounded-md border-slate-300 py-2 pr-10 text-sm focus:border-blue-400 focus:ring-blue-400">
                <span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-slate-400">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </span>
            </div>

            <button type="submit"
                class="inline-flex items-center justify-center rounded-md border border-slate-300 px-3 py-2 text-sm text-slate-600 hover:bg-slate-50">
                <i class="fa-solid fa-filter mr-2"></i>
                Filtrar
            </button>
            </form>
        </div>

        <section id="custom-report-library"
            class="mb-3 border-y border-slate-200 bg-slate-50 px-3 py-2 {{ count($customReportTemplates) ? '' : 'hidden' }}"
            aria-labelledby="custom-report-library-title">
            <div class="flex flex-col gap-2 lg:flex-row lg:items-center">
                <div class="shrink-0">
                    <p id="custom-report-library-title" class="text-[11px] font-semibold uppercase text-slate-500">
                        Plantillas personalizadas
                    </p>
                    <p class="text-xs text-slate-600"><span id="custom-report-count">{{ count($customReportTemplates) }}</span> guardadas</p>
                </div>
                <div id="custom-report-template-list" class="flex min-w-0 flex-1 flex-wrap gap-2"></div>
            </div>
        </section>

        <div id="report-format-actions"
            class="mb-3 hidden items-center justify-between gap-3 border-y border-slate-200 bg-slate-50 px-3 py-2">
            <div class="min-w-0">
                <span class="text-[11px] font-semibold uppercase text-slate-500">Formato seleccionado</span>
                <p id="report-format-selected-name" class="truncate text-sm font-medium text-slate-800"></p>
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <button type="button" id="report-template-preview"
                    class="inline-flex items-center gap-2 rounded-md border border-blue-200 bg-white px-3 py-2 text-sm font-medium text-blue-800 hover:bg-blue-50">
                    <i class="fa-regular fa-eye"></i>
                    Ver formato
                </button>
                <button type="button" id="report-template-edit"
                    class="inline-flex items-center gap-2 rounded-md bg-blue-900 px-3 py-2 text-sm font-medium text-white hover:bg-blue-800">
                    <i class="fa-solid fa-pen"></i>
                    Editar formato
                </button>
                <button type="button" id="report-format-actions-close"
                    class="inline-flex size-9 items-center justify-center rounded-md text-slate-500 hover:bg-slate-200"
                    title="Cerrar opciones de formato" aria-label="Cerrar opciones de formato">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full border-collapse text-xs text-left text-slate-800">
                <thead class="bg-slate-100 uppercase text-slate-700">
                    <tr>
                        <th class="border border-slate-300 px-2 py-1 font-semibold">ID</th>
                        <th class="border border-slate-300 px-2 py-1 font-semibold">Nombre de la institucion</th>
                        <th class="border border-slate-300 px-2 py-1 font-semibold">RFC</th>
                        <th class="border border-slate-300 px-2 py-1 font-semibold">Telefono</th>
                        <th class="border border-slate-300 px-2 py-1 text-center font-semibold">Hospitales</th>
                        <th class="border border-slate-300 px-2 py-1 font-semibold">Estatus</th>
                        @foreach ($reportColumns as $reportKey => $reportLabel)
                            <th class="min-w-[150px] border border-slate-300 px-2 py-1 font-semibold">
                                <button type="button" data-report-template-select="{{ $reportKey }}"
                                    class="mb-1 inline-flex items-center gap-1 rounded border border-slate-300 bg-white px-2 py-1 text-[10px] font-semibold normal-case text-blue-800 hover:border-blue-300 hover:bg-blue-50"
                                    title="Abrir opciones del formato {{ $reportLabel }}">
                                    <i class="fa-regular fa-file-lines"></i>
                                    Formato
                                </button>
                                <div data-report-template-name-editor="{{ $reportKey }}"
                                    class="flex items-center gap-1 normal-case">
                                    <span data-report-template-name-text class="min-w-0 flex-1 uppercase leading-tight">
                                        {{ $reportLabel }}
                                    </span>
                                    <button type="button" data-report-template-rename="{{ $reportKey }}"
                                        class="inline-flex size-6 shrink-0 items-center justify-center rounded border border-slate-300 bg-white text-blue-700 hover:border-blue-400 hover:bg-blue-50"
                                        title="Editar nombre del reporte"
                                        aria-label="Editar nombre de {{ $reportLabel }}">
                                        <i class="fa-solid fa-pen text-[10px]"></i>
                                    </button>
                                </div>
                            </th>
                        @endforeach
                        @foreach ($publishedCustomReportTemplates as $customTemplate)
                            <th class="min-w-[170px] border border-slate-300 px-2 py-1 font-semibold">
                                <button type="button" data-custom-template-edit="{{ $customTemplate['id'] }}"
                                    class="mb-1 inline-flex items-center gap-1 rounded border border-emerald-200 bg-white px-2 py-1 text-[10px] font-semibold normal-case text-emerald-700 hover:bg-emerald-50"
                                    title="Editar el formato {{ $customTemplate['name'] }}">
                                    <i class="fa-regular fa-file-lines" aria-hidden="true"></i>
                                    Formato
                                </button>
                                <div class="uppercase leading-tight">
                                    {{ $customTemplate['name'] }}
                                </div>
                            </th>
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($instituciones as $institucion)
                        <tr class="bg-white hover:bg-blue-50/40">
                            <td class="border border-slate-200 px-2 py-1 font-medium text-slate-700">
                                {{ $institucion->id }}
                            </td>
                            <td class="border border-slate-200 px-2 py-1 min-w-[220px]">
                                <div class="font-medium leading-tight text-slate-800">{{ $institucion->nombre }}</div>
                                @if ($institucion->razon_social)
                                    <div class="mt-0.5 leading-tight text-slate-500">{{ $institucion->razon_social }}</div>
                                @endif
                            </td>
                            <td class="border border-slate-200 px-2 py-1 whitespace-nowrap">{{ $institucion->rfc ?: '-' }}</td>
                            <td class="border border-slate-200 px-2 py-1 whitespace-nowrap">{{ $institucion->telefono ?: '-' }}</td>
                            <td class="border border-slate-200 px-2 py-1 text-center">
                                <span
                                    class="inline-flex min-w-6 items-center justify-center rounded-sm bg-blue-50 px-1.5 py-0.5 font-semibold text-blue-600">
                                    {{ $institucion->hospitals_count }}
                                </span>
                            </td>
                            <td class="border border-slate-200 px-2 py-1">
                                <span
                                    class="inline-flex items-center rounded-sm bg-emerald-50 px-2 py-0.5 font-medium text-emerald-700">
                                    Activo
                                </span>
                            </td>
                            <td class="border border-slate-200 px-2 py-1 whitespace-nowrap">
                                <a href="{{ route('admin.instituciones.exportarGeneral', $institucion) }}"
                                    class="inline-flex items-center gap-1 text-blue-700 hover:text-blue-800 font-medium">
                                    <i class="fa-solid fa-download text-[11px]"></i>
                                    Descargar
                                </a>
                            </td>
                            <td class="border border-slate-200 px-2 py-1 whitespace-nowrap">
                                <a href="{{ route('admin.instituciones.exportarHospital', $institucion) }}"
                                    class="inline-flex items-center gap-1 text-blue-700 hover:text-blue-800 font-medium">
                                    <i class="fa-solid fa-download text-[11px]"></i>
                                    Descargar
                                </a>
                            </td>
                            <td class="border border-slate-200 px-2 py-1 whitespace-nowrap">
                                @include('admin.instituciones.partials.report-hospital-selector', [
                                    'downloadUrl' => route('admin.instituciones.exportarHospitalDetalle', $institucion),
                                ])
                            </td>
                            <td class="border border-slate-200 px-2 py-1 whitespace-nowrap">
                                @include('admin.instituciones.partials.report-hospital-selector', [
                                    'downloadUrl' => route('admin.instituciones.exportarReporteDiarioPaciente', [
                                        'institucion' => $institucion,
                                        'from' => $dailyReportFrom,
                                        'to' => $dailyReportTo,
                                    ]),
                                ])
                            </td>
                            <td class="border border-slate-200 px-2 py-1 whitespace-nowrap">
                                @include('admin.instituciones.partials.report-hospital-selector', [
                                    'downloadUrl' => route('admin.instituciones.exportarReporteMensualInsumos', $institucion),
                                ])
                            </td>
                            @foreach ($publishedCustomReportTemplates as $customTemplate)
                                <td class="border border-slate-200 px-2 py-1 whitespace-nowrap">
                                    <a href="{{ route('admin.instituciones.reportes.plantillas.download', [
                                        'customTemplate' => $customTemplate['id'],
                                        'institucion' => $institucion,
                                        'from' => $dailyReportFrom,
                                        'to' => $dailyReportTo,
                                    ]) }}"
                                        class="inline-flex items-center gap-1 font-medium text-blue-700 hover:text-blue-800">
                                        <i class="fa-solid fa-download text-[11px]" aria-hidden="true"></i>
                                        Descargar
                                    </a>
                                </td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 6 + count($reportColumns) + count($publishedCustomReportTemplates) }}"
                                class="border border-slate-200 px-2 py-6 text-center text-slate-400">
                                No se encontraron instituciones para mostrar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-3 flex flex-col gap-3 text-xs text-slate-500 md:flex-row md:items-center md:justify-between">
            <p>
                Mostrando {{ $instituciones->firstItem() ?? 0 }} a {{ $instituciones->lastItem() ?? 0 }} de
                {{ $instituciones->total() }} instituciones
            </p>

            <div>
                {{ $instituciones->links() }}
            </div>
        </div>
    </div>

    <script id="institution-report-hospitals-data" type="application/json">{!! json_encode(
        $instituciones->getCollection()->mapWithKeys(fn ($institucion) => [
            (string) $institucion->id => $institucion->hospitals->map(fn ($hospital) => [
                'id' => $hospital->id,
                'name' => $hospital->name,
            ])->values(),
        ]),
        JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
    ) !!}</script>

    <div id="hospital-report-selector-popover"
        class="fixed z-[100] hidden overflow-hidden rounded-md border border-slate-300 bg-white text-sm text-slate-700 shadow-xl"
        role="dialog" aria-label="Seleccionar hospitales">
        <div class="border-b border-slate-200 p-2">
            <input type="search" data-hospital-search placeholder="Buscar hospital..."
                class="h-9 w-full rounded-sm border-slate-300 px-2 text-sm focus:border-blue-500 focus:ring-blue-500">
        </div>
        <div class="max-h-64 overflow-y-auto p-2">
            <label class="flex cursor-pointer items-center gap-2 border-b border-slate-100 px-1 pb-2 font-semibold">
                <input type="checkbox" data-hospital-all
                    class="rounded border-slate-300 text-blue-700 focus:ring-blue-500">
                <span>Seleccionar todos</span>
            </label>
            <div data-hospital-options class="mt-1"></div>
            <p data-hospital-empty class="hidden px-1 py-4 text-center text-xs text-slate-400">
                Sin coincidencias
            </p>
        </div>
        <div class="flex items-center justify-between gap-2 border-t border-slate-200 bg-slate-50 p-2">
            <span data-hospital-count class="text-xs text-slate-500"></span>
            <div class="flex gap-2">
                <button type="button" data-hospital-cancel
                    class="rounded border border-slate-300 bg-white px-3 py-1.5 text-xs text-slate-700 hover:bg-slate-100">
                    Cancelar
                </button>
                <button type="button" data-hospital-apply
                    class="rounded bg-blue-900 px-3 py-1.5 text-xs font-semibold text-white hover:bg-blue-800">
                    Aplicar
                </button>
            </div>
        </div>
    </div>

    <script id="institution-report-templates-data" type="application/json">{!! json_encode($reportTemplates, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    <script id="custom-report-templates-data" type="application/json">{!! json_encode($customReportTemplates, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    <script id="custom-report-parameters-data" type="application/json">{!! json_encode($reportCatalogParameters, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>
    <script id="custom-report-sources-data" type="application/json">{!! json_encode($reportDataSources, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) !!}</script>

    <div id="report-template-modal"
        data-update-url="{{ route('admin.instituciones.reportes.formatos.update', ['reportTemplate' => '__REPORT__']) }}"
        data-rename-url="{{ route('admin.instituciones.reportes.formatos.rename', ['reportTemplate' => '__REPORT__']) }}"
        class="fixed inset-0 z-[70] hidden overflow-y-auto bg-slate-950/55 px-4 py-6"
        role="dialog" aria-modal="true" aria-labelledby="report-template-modal-title">
        <div class="mx-auto flex min-h-full max-w-6xl items-center justify-center">
            <section class="w-full overflow-hidden rounded-lg bg-white shadow-2xl">
                <header class="flex items-center justify-between border-b border-slate-200 px-5 py-4">
                    <div class="min-w-0">
                        <p id="report-template-modal-kicker"
                            class="text-[11px] font-semibold uppercase text-blue-700"></p>
                        <h2 id="report-template-modal-title" class="truncate text-xl font-semibold text-slate-900"></h2>
                    </div>
                    <button type="button" data-report-template-close
                        class="inline-flex size-10 shrink-0 items-center justify-center rounded-md text-xl text-slate-500 hover:bg-slate-100 hover:text-slate-800"
                        title="Cerrar formato" aria-label="Cerrar formato">
                        <span aria-hidden="true" class="text-2xl leading-none">&times;</span>
                    </button>
                </header>

                <div class="flex items-center gap-2 border-b border-slate-200 bg-slate-50 px-5 py-2">
                    <button type="button" data-report-template-mode="preview"
                        class="report-template-mode rounded-md px-3 py-2 text-sm font-medium">
                        Vista previa
                    </button>
                    <button type="button" data-report-template-mode="edit"
                        class="report-template-mode rounded-md px-3 py-2 text-sm font-medium">
                        Editar formato
                    </button>
                </div>

                <div id="report-template-modal-body" class="max-h-[72vh] overflow-y-auto p-5"></div>

                <footer id="report-template-modal-footer"
                    class="hidden items-center justify-between border-t border-slate-200 bg-slate-50 px-5 py-3">
                    <p id="report-template-save-error" class="text-sm text-red-600"></p>
                    <div class="ml-auto flex items-center gap-2">
                        <button type="button" data-report-template-mode="preview"
                            class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-100">
                            Cancelar
                        </button>
                        <button type="button" id="report-template-save"
                            class="inline-flex items-center gap-2 rounded-md bg-blue-900 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800 disabled:cursor-wait disabled:opacity-60">
                            <i class="fa-solid fa-floppy-disk"></i>
                            Guardar formato
                        </button>
                    </div>
                </footer>
            </section>
        </div>
    </div>

    <div id="custom-report-builder-modal"
        data-store-url="{{ route('admin.instituciones.reportes.plantillas.store') }}"
        data-update-url="{{ route('admin.instituciones.reportes.plantillas.update', ['customTemplate' => '__ID__']) }}"
        data-publish-url="{{ route('admin.instituciones.reportes.plantillas.publish', ['customTemplate' => '__ID__']) }}"
        data-delete-url="{{ route('admin.instituciones.reportes.plantillas.destroy', ['customTemplate' => '__ID__']) }}"
        class="fixed inset-0 z-[80] hidden bg-slate-950/60 p-3 sm:p-5"
        role="dialog" aria-modal="true" aria-labelledby="custom-report-builder-title">
        <section class="mx-auto flex h-full w-full max-w-[96rem] flex-col overflow-hidden rounded-lg bg-white shadow-2xl">
            <header class="flex shrink-0 items-center justify-between border-b border-slate-200 px-4 py-3 sm:px-5">
                <div class="min-w-0">
                    <p class="text-[11px] font-semibold uppercase text-blue-700">Diseñador de plantillas</p>
                    <h2 id="custom-report-builder-title" class="truncate text-xl font-semibold text-slate-900">
                        Crear nuevo reporte
                    </h2>
                </div>
                <button type="button" data-custom-report-close
                    class="inline-flex size-10 shrink-0 items-center justify-center rounded-md text-xl text-slate-500 hover:bg-slate-100 hover:text-slate-800"
                    title="Cerrar diseñador" aria-label="Cerrar diseñador">
                    <span aria-hidden="true" class="text-2xl leading-none">&times;</span>
                </button>
            </header>

            <div class="flex shrink-0 flex-wrap items-center gap-2 border-b border-slate-200 bg-slate-50 px-3 py-2">
                <button type="button" data-grid-action="add-row"
                    class="inline-flex h-9 items-center gap-2 rounded-md border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                    Agregar renglón
                </button>
                <button type="button" data-grid-action="add-column"
                    class="inline-flex h-9 items-center gap-2 rounded-md border border-slate-300 bg-white px-3 text-xs font-semibold text-slate-700 hover:bg-slate-100">
                    Agregar columna
                </button>
                <button type="button" data-grid-action="remove-row"
                    class="inline-flex h-9 items-center justify-center rounded-md border border-red-200 bg-white px-3 text-xs font-semibold text-red-600 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-40"
                    title="Eliminar renglón seleccionado" aria-label="Eliminar renglón seleccionado">
                    Quitar renglón
                </button>
                <button type="button" data-grid-action="remove-column"
                    class="inline-flex h-9 items-center justify-center rounded-md border border-red-200 bg-white px-3 text-xs font-semibold text-red-600 hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-40"
                    title="Eliminar columna seleccionada" aria-label="Eliminar columna seleccionada">
                    Quitar columna
                </button>

                <span class="mx-1 h-7 w-px bg-slate-300" aria-hidden="true"></span>

                <label for="custom-cell-font" class="sr-only">Tipo de letra</label>
                <select id="custom-cell-font" class="h-9 w-36 rounded-md border-slate-300 py-1 text-xs focus:border-blue-500 focus:ring-blue-500">
                    <option>Arial</option>
                    <option>Calibri</option>
                    <option>Figtree</option>
                    <option>Georgia</option>
                    <option>Tahoma</option>
                    <option>Times New Roman</option>
                    <option>Verdana</option>
                </select>
                <label for="custom-cell-font-size" class="sr-only">Tamaño de letra</label>
                <input id="custom-cell-font-size" type="number" min="8" max="36" step="1"
                    class="h-9 w-16 rounded-md border-slate-300 px-2 py-1 text-center text-xs focus:border-blue-500 focus:ring-blue-500">

                <div class="flex overflow-hidden rounded-md border border-slate-300 bg-white">
                    <button type="button" data-style-toggle="bold"
                        class="inline-flex size-9 items-center justify-center border-r border-slate-300 text-slate-700 hover:bg-slate-100"
                        title="Negrita" aria-label="Negrita">
                        <strong aria-hidden="true">B</strong>
                    </button>
                    <button type="button" data-style-toggle="italic"
                        class="inline-flex size-9 items-center justify-center border-r border-slate-300 text-slate-700 hover:bg-slate-100"
                        title="Cursiva" aria-label="Cursiva">
                        <em aria-hidden="true">I</em>
                    </button>
                    <button type="button" data-style-toggle="underline"
                        class="inline-flex size-9 items-center justify-center text-slate-700 hover:bg-slate-100"
                        title="Subrayado" aria-label="Subrayado">
                        <span aria-hidden="true" class="underline">U</span>
                    </button>
                </div>

                <div class="flex overflow-hidden rounded-md border border-slate-300 bg-white text-[10px] font-semibold">
                    <button type="button" data-cell-align="left"
                        class="inline-flex h-9 items-center justify-center border-r border-slate-300 px-2 text-slate-700 hover:bg-slate-100"
                        title="Alinear a la izquierda" aria-label="Alinear a la izquierda">
                        Izq.
                    </button>
                    <button type="button" data-cell-align="center"
                        class="inline-flex h-9 items-center justify-center border-r border-slate-300 px-2 text-slate-700 hover:bg-slate-100"
                        title="Centrar" aria-label="Centrar">
                        Cen.
                    </button>
                    <button type="button" data-cell-align="right"
                        class="inline-flex h-9 items-center justify-center px-2 text-slate-700 hover:bg-slate-100"
                        title="Alinear a la derecha" aria-label="Alinear a la derecha">
                        Der.
                    </button>
                </div>

                <label class="flex h-9 items-center gap-2 rounded-md border border-slate-300 bg-white px-2 text-xs font-medium text-slate-700">
                    Texto
                    <input id="custom-cell-color" type="color" value="#1F2937"
                        class="h-6 w-7 cursor-pointer rounded border-0 bg-transparent p-0" title="Color del texto">
                </label>
                <label class="flex h-9 items-center gap-2 rounded-md border border-slate-300 bg-white px-2 text-xs font-medium text-slate-700">
                    Fondo
                    <input id="custom-cell-background" type="color" value="#FFFFFF"
                        class="h-6 w-7 cursor-pointer rounded border-0 bg-transparent p-0" title="Color de fondo">
                </label>
            </div>

            <div class="flex min-h-0 flex-1 flex-col lg:flex-row">
                <main class="min-h-[360px] min-w-0 flex-1 overflow-auto bg-slate-100 p-4" aria-label="Cuadrícula de la plantilla">
                    <div id="custom-report-grid" class="inline-block min-w-full overflow-hidden border border-slate-300 bg-white shadow-sm"></div>
                </main>

                <aside class="w-full shrink-0 overflow-y-auto border-t border-slate-200 bg-white p-4 lg:w-80 lg:border-l lg:border-t-0">
                    <div class="space-y-4">
                        <label class="block text-xs font-semibold text-slate-700">
                            Nombre del reporte
                            <input id="custom-report-name" type="text" maxlength="120" placeholder="Ej. Control mensual de entregas"
                                class="mt-1 h-10 w-full rounded-md border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                        </label>
                        <label class="block text-xs font-semibold text-slate-700">
                            Origen de datos
                            <select id="custom-report-source"
                                class="mt-1 h-10 w-full rounded-md border-slate-300 py-1 text-sm focus:border-blue-500 focus:ring-blue-500"></select>
                        </label>
                        <label class="block text-xs font-semibold text-slate-700">
                            Descripción
                            <textarea id="custom-report-description" rows="2" maxlength="500"
                                class="mt-1 w-full resize-y rounded-md border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                        </label>

                        <div class="border-t border-slate-200 pt-4">
                            <div class="mb-3 flex items-center justify-between">
                                <h3 class="text-sm font-semibold text-slate-900">Celda <span id="custom-selected-cell">A1</span></h3>
                            </div>
                            <div class="grid grid-cols-3 overflow-hidden rounded-md border border-slate-300" role="group" aria-label="Tipo de celda">
                                <button type="button" data-cell-type="text" class="h-9 border-r border-slate-300 px-2 text-xs font-semibold">Texto</button>
                                <button type="button" data-cell-type="free" class="h-9 border-r border-slate-300 px-2 text-xs font-semibold">Campo libre</button>
                                <button type="button" data-cell-type="parameter" class="h-9 px-2 text-xs font-semibold">Parámetro</button>
                            </div>
                        </div>

                        <label id="custom-cell-value-wrap" class="block text-xs font-semibold text-slate-700">
                            Contenido
                            <textarea id="custom-cell-value" rows="3" maxlength="500"
                                class="mt-1 w-full resize-y rounded-md border-slate-300 text-sm focus:border-blue-500 focus:ring-blue-500"></textarea>
                        </label>

                        <label id="custom-cell-parameter-wrap" class="hidden text-xs font-semibold text-slate-700">
                            Parámetro de catálogo
                            <select id="custom-cell-parameter"
                                class="mt-1 w-full rounded-md border-slate-300 py-2 text-sm focus:border-blue-500 focus:ring-blue-500"></select>
                        </label>

                        <div id="custom-cell-repeat-wrap" class="hidden">
                            <p class="mb-1 text-xs font-semibold text-slate-700">Continuación de datos</p>
                            <div class="grid grid-cols-3 overflow-hidden rounded-md border border-slate-300" role="group" aria-label="Dirección de continuación">
                                <button type="button" data-cell-repeat="none"
                                    class="h-9 border-r border-slate-300 px-2 text-xs font-semibold">Sin repetir</button>
                                <button type="button" data-cell-repeat="vertical"
                                    class="inline-flex h-9 items-center justify-center gap-1 border-r border-slate-300 px-2 text-xs font-semibold">
                                    <i class="fa-solid fa-arrow-down" aria-hidden="true"></i>
                                    Vertical
                                </button>
                                <button type="button" data-cell-repeat="horizontal"
                                    class="inline-flex h-9 items-center justify-center gap-1 px-2 text-xs font-semibold">
                                    <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                                    Horizontal
                                </button>
                            </div>
                        </div>
                    </div>
                </aside>
            </div>

            <footer class="flex shrink-0 flex-col gap-3 border-t border-slate-200 bg-slate-50 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p id="custom-report-grid-size" class="text-xs font-medium text-slate-600"></p>
                    <p id="custom-report-save-error" class="text-xs font-medium text-red-600"></p>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" data-custom-report-close
                        class="h-10 rounded-md border border-slate-300 bg-white px-4 text-sm font-semibold text-slate-700 hover:bg-slate-100">
                        Cancelar
                    </button>
                    <button type="button" id="custom-report-save"
                        class="inline-flex h-10 items-center gap-2 rounded-md bg-blue-900 px-4 text-sm font-semibold text-white hover:bg-blue-800 disabled:cursor-wait disabled:opacity-60">
                        Guardar plantilla
                    </button>
                    <button type="button" id="custom-report-publish"
                        class="inline-flex h-10 items-center gap-2 rounded-md bg-emerald-700 px-4 text-sm font-semibold text-white hover:bg-emerald-600 disabled:cursor-wait disabled:opacity-60">
                        Agregar a reportes
                    </button>
                </div>
            </footer>
        </section>
    </div>
</x-admin-layout>
