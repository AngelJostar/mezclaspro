<x-admin-layout>
    <div class="rounded-lg bg-white p-5 shadow-sm md:p-6">
        <header class="flex flex-col gap-4 border-b border-gray-200 pb-5 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <nav class="mb-2 text-xs font-medium text-gray-500" aria-label="Ruta de navegación">
                    <a href="{{ route('admin.distribution.index') }}" class="hover:text-blue-700">Distribución</a>
                    <span class="mx-2 text-gray-300">/</span>
                    <span class="text-blue-700">Nueva ruta</span>
                </nav>
                <h1 class="text-2xl font-bold text-gray-950">Crear nueva ruta</h1>
                <p class="mt-1 text-sm text-gray-500">Configura el recorrido, los hospitales y los mensajeros asignados.</p>
            </div>

            <a href="{{ route('admin.distribution.index') }}"
                class="inline-flex h-10 items-center justify-center gap-2 rounded-md border border-gray-300 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                Volver a rutas
            </a>
        </header>

        <form method="POST" action="{{ route('admin.distribution.store') }}" class="mt-6">
            @csrf
            @include('admin.distribution.partials.form')

            <div class="mt-7 flex flex-wrap items-center justify-end gap-3 border-t border-gray-200 pt-5">
                <a href="{{ route('admin.distribution.index') }}"
                    class="inline-flex h-10 items-center justify-center rounded-md border border-gray-300 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancelar</a>
                <button type="submit"
                    class="inline-flex h-10 items-center justify-center gap-2 rounded-md bg-blue-700 px-5 text-sm font-semibold text-white hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <i class="fa-solid fa-check" aria-hidden="true"></i>
                    Crear ruta
                </button>
            </div>
        </form>
    </div>
</x-admin-layout>
