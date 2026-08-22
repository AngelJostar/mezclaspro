<x-admin-layout>
    <div class="mt-2">
        <h1 class="text-2xl font-medium text-gray-800">Lista de Instituciones</h1>
    </div>

    <div class="flex justify-end mb-4">
        <a class="text-white bg-azul-prodifem hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 font-medium rounded-full text-sm px-5 py-2.5 text-center me-2 mb-2 dark:bg-blue-600 dark:hover:bg-azul-prodifem dark:focus:ring-blue-800"
            href="{{ route('admin.instituciones.create') }}">
            <i class="fa-solid fa-plus pr-1"></i> Agregar
        </a>
    </div>

    <div class="relative overflow-x-auto">
        <table id="institutions-table"
            class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <x-filterable-table-header column="0" trigger-class="js-institution-column-filter"
                        scope="col">Id</x-filterable-table-header>
                    <x-filterable-table-header column="1" trigger-class="js-institution-column-filter"
                        scope="col">Nombre</x-filterable-table-header>
                    <x-filterable-table-header column="2" trigger-class="js-institution-column-filter"
                        scope="col">Razon social</x-filterable-table-header>
                    <th scope="col" class="px-4 py-3 text-center">Editar</th>
                    <th scope="col" class="px-4 py-3 text-center">Hospitales</th>
                    <th scope="col" class="px-4 py-3 text-center">Bloqueo</th>
                    <th scope="col" class="px-4 py-3 text-center">Eliminar</th>
                    <th scope="col" class="px-4 py-3 text-center">Reporte</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($instituciones as $institucion)
                    <tr class="js-institution-filter-row bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                        <th scope="row"
                            class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                            {{ $institucion->id }}
                        </th>

                        <td class="px-6 py-4">
                            {{ $institucion->nombre }}
                        </td>

                        <td class="px-6 py-4">
                            {{ $institucion->razon_social }}
                        </td>

                        <td class="px-4 py-4 text-center whitespace-nowrap">
                            <a href="{{ route('admin.instituciones.edit', $institucion) }}"
                                class="inline-flex items-center justify-center rounded-full bg-azul-prodifem px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                                <i class="fa-solid fa-pen pr-1"></i> Editar
                            </a>
                        </td>

                        <td class="px-4 py-4 text-center whitespace-nowrap">
                            <span class="inline-flex items-center gap-2">
                                <a href="{{ route('admin.instituciones.hospitals', $institucion) }}"
                                    class="inline-flex items-center justify-center rounded-full bg-azul-prodifem px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300">
                                    <i class="fa-solid fa-hospital pr-1"></i> Hospitales
                                </a>
                                <span
                                    class="inline-flex h-7 min-w-7 items-center justify-center rounded-full border border-blue-200 bg-blue-50 px-2 text-xs font-bold text-blue-900"
                                    title="{{ $institucion->hospitals_count }} hospitales asignados"
                                    aria-label="{{ $institucion->hospitals_count }} hospitales asignados">
                                    {{ $institucion->hospitals_count }}
                                </span>
                            </span>
                        </td>

                        <td class="px-4 py-4 text-center whitespace-nowrap">
                            <form method="POST"
                                action="{{ route('admin.users.institutions.status.update', $institucion) }}"
                                data-institution-access-form
                                data-institution-name="{{ $institucion->nombre }}">
                                @csrf
                                @method('PATCH')
                                <input type="hidden" name="is_active" value="{{ $institucion->is_active ? 0 : 1 }}">

                                <button type="submit"
                                    class="inline-flex min-w-24 items-center justify-center rounded-full px-3 py-2 text-xs font-semibold text-white transition focus:outline-none focus:ring-4 {{ $institucion->is_active
                                        ? 'bg-green-600 hover:bg-green-700 focus:ring-green-300'
                                        : 'bg-red-600 hover:bg-red-700 focus:ring-red-300' }}">
                                    <i class="fa-solid {{ $institucion->is_active ? 'fa-lock-open' : 'fa-lock' }} pr-1"
                                        aria-hidden="true"></i>
                                    <span>{{ $institucion->is_active ? 'Bloquear' : 'Bloqueado' }}</span>
                                </button>
                            </form>
                        </td>

                        <td class="px-4 py-4 text-center whitespace-nowrap">
                            <form action="{{ route('admin.instituciones.destroy', $institucion) }}" method="POST"
                                onsubmit="return confirm('Seguro que deseas eliminar esta institucion?');">
                                @csrf
                                @method('DELETE')

                                <button type="submit"
                                    class="inline-flex items-center justify-center rounded-full bg-red-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-300">
                                    <i class="fa-solid fa-trash pr-1"></i> Eliminar
                                </button>
                            </form>
                        </td>

                        <td class="px-4 py-4 text-center whitespace-nowrap">
                            <a href="{{ route('admin.instituciones.exportarMezclasOnco', $institucion) }}"
                                target="_blank"
                                rel="noopener"
                                class="inline-flex items-center justify-center rounded-full bg-green-600 px-3 py-2 text-xs font-semibold text-white transition hover:bg-green-800 focus:outline-none focus:ring-4 focus:ring-green-300">
                                <i class="fa-solid fa-file-excel pr-1"></i> Reporte
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                        <td colspan="8" class="px-6 py-6 text-center text-gray-500">
                            No hay instituciones registradas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="mt-4">
            {{ $instituciones->links() }}
        </div>
    </div>

    @push('js')
        <script>
            @include('admin.catalogo-listas.partials.column-filter-script')

            document.addEventListener('DOMContentLoaded', function() {
                window.createExcelColumnFilters({
                    tableId: 'institutions-table',
                    rowSelector: '.js-institution-filter-row',
                    triggerSelector: '.js-institution-column-filter',
                    instanceId: 'institutions',
                    onChange() {
                        document.querySelectorAll('.js-institution-filter-row').forEach((row) => {
                            row.classList.toggle('hidden', row.dataset.columnFilterMatch === '0');
                        });
                    },
                });

                document.addEventListener('submit', async function(event) {
                    const form = event.target.closest('[data-institution-access-form]');

                    if (!form) {
                        return;
                    }

                    event.preventDefault();
                    const statusInput = form.querySelector('input[name="is_active"]');
                    const button = form.querySelector('button[type="submit"]');
                    const willActivate = statusInput?.value === '1';
                    const institutionName = form.dataset.institutionName || 'esta institucion';
                    const confirmation = await Swal.fire({
                        title: '¿Estas seguro?',
                        text: willActivate
                            ? `Se reactivara el acceso de los hospitales de ${institutionName}.`
                            : `Se bloqueara el acceso de los hospitales de ${institutionName}.`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Si',
                        cancelButtonText: 'No',
                        confirmButtonColor: willActivate ? '#16a34a' : '#dc2626',
                    });

                    if (!confirmation.isConfirmed) {
                        return;
                    }

                    button.disabled = true;
                    button.setAttribute('aria-busy', 'true');

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: new FormData(form),
                        });
                        const payload = await response.json().catch(function() {
                            return {};
                        });

                        if (!response.ok) {
                            const validationMessage = Object.values(payload.errors || {})
                                .flat()
                                .find(Boolean);
                            throw new Error(validationMessage || payload.message || 'No se pudo cambiar el acceso.');
                        }

                        await Swal.fire({
                            title: willActivate ? 'Acceso reactivado' : 'Acceso bloqueado',
                            text: payload.message || 'El cambio se guardo correctamente.',
                            icon: 'success',
                            confirmButtonText: 'Aceptar',
                        });
                        window.location.reload();
                    } catch (requestError) {
                        await Swal.fire({
                            title: 'No se pudo guardar',
                            text: requestError.message,
                            icon: 'error',
                            confirmButtonText: 'Aceptar',
                        });
                        button.disabled = false;
                        button.removeAttribute('aria-busy');
                    }
                });
            });
        </script>
    @endpush
</x-admin-layout>
