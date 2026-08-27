@php
    $openRouteTypeModal = request()->boolean('create') && ! $errors->any() && $editingRoute === null;
@endphp

<div id="route-type-modal" class="fixed inset-0 z-[90] hidden items-center justify-center p-4 sm:p-6"
    role="dialog" aria-modal="true" aria-labelledby="route-type-title" aria-hidden="true"
    data-open-on-load="{{ $openRouteTypeModal ? 'true' : 'false' }}">
    <button type="button" class="absolute inset-0 cursor-default bg-slate-950/55 backdrop-blur-[1px]"
        data-route-type-close aria-label="Cerrar selector de tipo de ruta"></button>

    <section class="relative w-full max-w-2xl overflow-hidden rounded-lg bg-white shadow-2xl">
        <header class="flex items-start justify-between gap-4 border-b border-slate-200 px-6 py-5">
            <div>
                <h2 id="route-type-title" class="text-xl font-semibold text-slate-900">Selecciona el tipo de ruta que deseas crear</h2>
                <p class="mt-1 text-sm text-slate-500">Elige el medio con el que se realizar&aacute;n las entregas.</p>
            </div>
            <button type="button" data-route-type-close title="Cerrar" aria-label="Cerrar"
                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-md text-slate-500 transition hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-2 focus:ring-blue-400">
                <span class="text-xl leading-none" aria-hidden="true">&times;</span>
            </button>
        </header>

        <div class="px-6 py-6">
            <fieldset>
                <legend class="sr-only">Tipo de ruta</legend>
                <div class="grid gap-4 sm:grid-cols-2">
                    <label data-route-type-card="vehicular"
                        class="flex min-h-56 cursor-pointer flex-col items-center justify-center rounded-md border-2 border-blue-600 bg-blue-50/40 px-6 py-6 text-center transition hover:bg-blue-50">
                        <input id="route-type-vehicle" class="sr-only" type="radio" name="route_creation_type" value="vehicular" checked>
                        <span class="flex h-20 w-20 items-center justify-center rounded-full border border-blue-200 bg-white text-3xl text-blue-600">
                            <span aria-hidden="true">&#128666;</span>
                        </span>
                        <span class="mt-4 text-base font-semibold text-slate-900">Ruta vehicular</span>
                        <span class="mt-2 text-sm leading-5 text-slate-500">Entrega realizada mediante veh&iacute;culo terrestre.</span>
                        <span data-route-type-indicator
                            class="mt-5 flex h-5 w-5 items-center justify-center rounded-full border-2 border-blue-600 bg-blue-600">
                            <span class="h-1.5 w-1.5 rounded-full bg-white"></span>
                        </span>
                    </label>

                    <label data-route-type-card="dron"
                        class="flex min-h-56 cursor-pointer flex-col items-center justify-center rounded-md border-2 border-slate-200 bg-white px-6 py-6 text-center transition hover:border-blue-300 hover:bg-blue-50/30">
                        <input id="route-type-drone" class="sr-only" type="radio" name="route_creation_type" value="dron">
                        <span class="flex h-20 w-20 items-center justify-center rounded-full border border-slate-200 bg-white text-3xl text-blue-600">
                            <span aria-hidden="true">&#128641;</span>
                        </span>
                        <span class="mt-4 text-base font-semibold text-slate-900">Ruta de dron</span>
                        <span class="mt-2 text-sm leading-5 text-slate-500">Entrega realizada mediante dron.</span>
                        <span data-route-type-indicator
                            class="mt-5 flex h-5 w-5 items-center justify-center rounded-full border-2 border-slate-300 bg-white">
                            <span class="h-1.5 w-1.5 rounded-full bg-transparent"></span>
                        </span>
                    </label>
                </div>
            </fieldset>

        </div>

        <footer class="flex items-center justify-between gap-3 border-t border-slate-200 bg-slate-50 px-6 py-4">
            <button type="button" data-route-type-close
                class="inline-flex h-10 items-center justify-center rounded-md border border-slate-300 bg-white px-5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                Cancelar
            </button>
            <button id="route-type-next" type="button"
                class="inline-flex h-10 items-center justify-center gap-2 rounded-md bg-blue-700 px-5 text-sm font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-400 disabled:cursor-not-allowed disabled:bg-blue-200">
                <span>Siguiente</span>
                <span class="text-base leading-none" aria-hidden="true">&rarr;</span>
            </button>
        </footer>
    </section>
</div>
