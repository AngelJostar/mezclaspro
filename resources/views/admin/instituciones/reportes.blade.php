<x-admin-layout>
    @php
        $reportColumns = [
            'institution_general' => 'Reporte de institución',
            'hospital_summary' => 'Reporte por hospital',
            'hospital_detail' => 'Reporte por hospital con detalle',
            'daily_patient' => 'Reporte diario por paciente',
            'monthly_supplies' => 'Reporte mensual de insumos por hospital',
        ];
    @endphp

    <div class="mt-2 mb-4">
        <h1 class="text-2xl font-medium text-gray-800">Reportes de instituciones</h1>
        <p class="text-sm text-gray-500 mt-1">
            Desde aqui puedes descargar los reportes generales por institucion.
        </p>
    </div>

    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-3">
        <form method="GET" action="{{ route('admin.instituciones.reportes') }}"
            class="flex flex-col gap-2 md:flex-row md:items-center md:justify-end mb-3">
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
                                <span class="block leading-tight">{{ $reportLabel }}</span>
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
                        </tr>
                    @empty
                        <tr>
                            <td colspan="11" class="border border-slate-200 px-2 py-6 text-center text-slate-400">
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

    <div id="report-template-modal"
        data-update-url="{{ route('admin.instituciones.reportes.formatos.update', ['reportTemplate' => '__REPORT__']) }}"
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
</x-admin-layout>
