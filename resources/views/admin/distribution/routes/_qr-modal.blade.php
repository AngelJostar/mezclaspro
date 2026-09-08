<div id="route-qr-modal" class="fixed inset-0 z-[90] hidden items-center justify-center p-4 sm:p-6"
    role="dialog" aria-modal="true" aria-labelledby="route-qr-title" aria-hidden="true">
    <button type="button" class="absolute inset-0 cursor-default bg-slate-950/55 backdrop-blur-[1px]"
        data-route-qr-close aria-label="Cerrar c&oacute;digo QR"></button>

    <section class="relative flex max-h-[92vh] w-full max-w-lg flex-col overflow-hidden rounded-lg bg-white shadow-2xl">
        <header class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-200 px-5 py-4 sm:px-6">
            <div class="min-w-0">
                <p class="text-xs font-semibold uppercase text-blue-700">Ruta de distribuci&oacute;n</p>
                <h2 id="route-qr-title" class="mt-1 truncate text-xl font-semibold text-slate-900">C&oacute;digo QR de la ruta</h2>
                <p id="route-qr-code" class="mt-1 text-sm text-slate-500"></p>
            </div>
            <button type="button" data-route-qr-close
                class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-md border border-slate-200 bg-white text-lg font-semibold text-slate-500 transition hover:border-red-200 hover:bg-red-50 hover:text-red-700 focus:outline-none focus:ring-2 focus:ring-red-200"
                aria-label="Cerrar">
                <span aria-hidden="true">&times;</span>
            </button>
        </header>

        <div class="min-h-0 overflow-y-auto px-5 py-5 sm:px-6">
            <div class="text-center">
                <h3 id="route-qr-name" class="text-lg font-semibold text-slate-900"></h3>
                <p class="mt-1 text-sm text-slate-500">Escanea este c&oacute;digo para identificar e iniciar la ruta asignada.</p>
            </div>

            <div class="mx-auto mt-5 flex aspect-square w-full max-w-[19rem] items-center justify-center rounded-md border border-slate-200 bg-white p-4 shadow-sm">
                <div id="route-qr-loading" class="flex flex-col items-center gap-3 text-sm text-slate-500" role="status">
                    <span class="route-map-spinner" aria-hidden="true"></span>
                    Generando c&oacute;digo QR...
                </div>
                <img id="route-qr-image" class="hidden h-full w-full object-contain" src="" alt="">
                <div id="route-qr-error" class="hidden px-5 text-center text-sm leading-6 text-red-700" role="alert">
                    No fue posible cargar el c&oacute;digo QR. Cierra la ventana e int&eacute;ntalo nuevamente.
                </div>
            </div>

            <dl class="mt-5 grid grid-cols-2 divide-x divide-slate-200 rounded-md border border-slate-200 bg-slate-50">
                <div class="px-4 py-3 text-center">
                    <dt class="text-xs font-medium uppercase text-slate-500">Horario</dt>
                    <dd id="route-qr-schedule" class="mt-1 text-sm font-semibold text-slate-900"></dd>
                </div>
                <div class="px-4 py-3 text-center">
                    <dt class="text-xs font-medium uppercase text-slate-500">Paradas</dt>
                    <dd id="route-qr-stops" class="mt-1 text-sm font-semibold text-slate-900"></dd>
                </div>
            </dl>
        </div>

        <footer class="flex shrink-0 flex-col-reverse gap-2 border-t border-slate-200 bg-slate-50 px-5 py-4 sm:flex-row sm:justify-end sm:px-6">
            <button type="button" data-route-qr-close
                class="inline-flex h-10 items-center justify-center rounded-md border border-slate-300 bg-white px-5 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-300">
                Cerrar
            </button>
            <a id="route-qr-download" href="#" download
                class="inline-flex h-10 items-center justify-center gap-2 rounded-md bg-blue-700 px-5 text-sm font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-400">
                <i class="fa-solid fa-download" aria-hidden="true"></i>
                Descargar QR
            </a>
        </footer>
    </section>
</div>
