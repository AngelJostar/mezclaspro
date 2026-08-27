<x-admin-layout>
    @if ($temporaryCredential = session('hospitalTemporaryCredential'))
        <div id="hospital-credential-modal"
            class="fixed inset-0 z-[70] flex items-center justify-center bg-gray-950/60 p-4"
            role="dialog" aria-modal="true" aria-labelledby="hospital-credential-title">
            <div class="w-full max-w-xl rounded-lg bg-white shadow-2xl">
                <div class="flex items-start justify-between border-b border-gray-200 px-6 py-5">
                    <div>
                        <p class="text-xs font-semibold uppercase text-emerald-700">Credencial temporal</p>
                        <h2 id="hospital-credential-title" class="mt-1 text-xl font-semibold text-gray-900">
                            Acceso de {{ $temporaryCredential['hospital'] }}
                        </h2>
                    </div>
                    <button type="button" data-close-hospital-credential
                        class="inline-flex h-9 w-9 items-center justify-center rounded-md bg-red-600 text-white hover:bg-red-700 focus:outline-none focus:ring-4 focus:ring-red-200"
                        title="Cerrar">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                        <span class="sr-only">Cerrar</span>
                    </button>
                </div>

                <div class="space-y-4 px-6 py-5">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="block">
                            <span class="mb-1 block text-xs font-semibold text-gray-700">Usuario</span>
                            <input type="text" readonly value="{{ $temporaryCredential['username'] }}"
                                class="h-11 w-full rounded-md border-gray-300 bg-gray-50 text-sm font-medium text-gray-900">
                        </label>
                        <label class="block">
                            <span class="mb-1 block text-xs font-semibold text-gray-700">Contrase&ntilde;a temporal</span>
                            <input id="hospital-temporary-password" type="text" readonly
                                value="{{ $temporaryCredential['password'] }}"
                                class="h-11 w-full rounded-md border-emerald-300 bg-emerald-50 font-mono text-base font-semibold text-gray-900">
                        </label>
                    </div>

                    <p class="rounded-md border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                        Esta contrase&ntilde;a se mostrar&aacute; una sola vez. Comp&aacute;rtela por un medio seguro.
                    </p>
                </div>

                <div class="flex justify-end gap-2 border-t border-gray-200 px-6 py-4">
                    <button type="button" data-copy-hospital-password
                        class="inline-flex min-h-10 items-center justify-center gap-2 rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 focus:outline-none focus:ring-4 focus:ring-emerald-200">
                        <i class="fa-regular fa-copy" aria-hidden="true"></i>
                        <span data-copy-label>Copiar contrase&ntilde;a</span>
                    </button>
                    <button type="button" data-close-hospital-credential
                        class="inline-flex min-h-10 items-center justify-center rounded-md border border-gray-300 px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-200">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    @endif

    <section class="rounded-lg bg-white p-5 shadow-lg">
        <div class="flex flex-col gap-3 border-b border-gray-100 pb-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Hospitales de la instituci&oacute;n</h1>
                <p class="mt-1 text-sm text-blue-600">{{ $institucion->nombre }}</p>
            </div>

            <a href="{{ route('admin.instituciones.index') }}"
                class="text-sm font-medium text-blue-600 hover:text-blue-800">
                &larr; Volver a instituciones
            </a>
        </div>

        <div class="mt-4 flex flex-col gap-4 rounded-lg border border-gray-200 p-4 md:flex-row md:items-center md:justify-between">
            <div class="flex min-w-0 items-center gap-3">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-blue-800">
                    <span class="text-sm font-bold" aria-hidden="true">I</span>
                </span>
                <div class="min-w-0">
                    <p class="truncate text-sm font-medium text-gray-900">
                        Instituci&oacute;n: {{ $institucion->nombre }}
                    </p>
                    <p class="mt-1 flex items-center gap-2 text-xs text-gray-600">
                        <span class="h-2 w-2 rounded-full bg-emerald-500"></span>
                        Activa
                    </p>
                </div>
            </div>

            <a href="{{ route('admin.instituciones.hospitals.create', $institucion) }}"
                class="inline-flex min-h-10 items-center justify-center gap-2 rounded-lg bg-azul-prodifem px-4 py-2 text-sm font-semibold text-white hover:bg-blue-900 focus:outline-none focus:ring-4 focus:ring-blue-200">
                <span class="text-base leading-none" aria-hidden="true">+</span>
                Crear hospital
            </a>
        </div>

        <div class="mt-4 overflow-hidden rounded-lg border border-gray-200">
            <div class="border-b border-gray-200 px-4 py-3">
                <div class="flex flex-col gap-1 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-gray-900">Hospitales relacionados</h2>
                        <p class="text-xs text-gray-500">
                            {{ $totalHospitals }} {{ $totalHospitals === 1 ? 'hospital' : 'hospitales' }}
                        </p>
                    </div>
                </div>

                <form method="GET" action="{{ route('admin.instituciones.hospitals', $institucion) }}"
                    class="mt-3 grid grid-cols-1 gap-2 md:grid-cols-12">
                    <label class="relative md:col-span-4">
                        <span class="sr-only">Buscar hospital</span>
                        <input type="search" name="search" value="{{ $search }}"
                            placeholder="Buscar por nombre, clave o municipio..."
                            class="h-10 w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                    </label>

                    <label class="md:col-span-2">
                        <span class="sr-only">Tipo de unidad</span>
                        <select name="unit_type"
                            class="h-10 w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="all">Tipo de unidad: Todos</option>
                            @foreach ($unitTypes as $type)
                                <option value="{{ $type }}" @selected($unitType === $type)>{{ $type }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="md:col-span-2">
                        <span class="sr-only">Estatus</span>
                        <select name="status"
                            class="h-10 w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="all" @selected($status === 'all')>Estatus: Todos</option>
                            <option value="active" @selected($status === 'active')>Activos</option>
                            <option value="inactive" @selected($status === 'inactive')>Inactivos</option>
                        </select>
                    </label>

                    <label class="md:col-span-2">
                        <span class="sr-only">L&iacute;nea de servicio</span>
                        <select name="service"
                            class="h-10 w-full rounded-lg border-gray-300 text-sm focus:border-blue-500 focus:ring-blue-500">
                            <option value="all" @selected($service === 'all')>Servicio: Todos</option>
                            <option value="oncology" @selected($service === 'oncology')>Oncol&oacute;gicos</option>
                            <option value="antibiotics" @selected($service === 'antibiotics')>Antibi&oacute;ticos</option>
                            <option value="nutrition" @selected($service === 'nutrition')>Nutricionales</option>
                        </select>
                    </label>

                    <div class="flex gap-2 md:col-span-2">
                        <button type="submit"
                            class="inline-flex h-10 flex-1 items-center justify-center rounded-lg bg-azul-prodifem px-3 text-sm font-semibold text-white hover:bg-blue-900">
                            Filtrar
                        </button>
                        <a href="{{ route('admin.instituciones.hospitals', $institucion) }}"
                            class="inline-flex h-10 items-center justify-center rounded-lg border border-gray-300 px-3 text-xs font-medium text-gray-700 hover:bg-gray-50">
                            Limpiar
                        </a>
                    </div>
                </form>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1320px] text-left text-xs text-gray-600">
                    <thead class="bg-gray-50 text-[11px] uppercase text-gray-700">
                        <tr>
                            <th class="px-4 py-3 font-semibold">Hospital</th>
                            <th class="px-4 py-3 font-semibold">Usuario</th>
                            <th class="px-4 py-3 font-semibold">Contrase&ntilde;a</th>
                            <th class="px-4 py-3 font-semibold">Clave</th>
                            <th class="px-4 py-3 font-semibold">Tipo de unidad</th>
                            <th class="px-4 py-3 font-semibold">Municipio</th>
                            <th class="px-4 py-3 font-semibold">L&iacute;neas de servicio</th>
                            <th class="px-4 py-3 font-semibold">Estatus</th>
                            <th class="px-4 py-3 text-center font-semibold">Editar</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @forelse ($hospitals as $hospital)
                            @php
                                $hasOncology = $hospital->service_oncology || $hospital->onco_medicine_list_id;
                                $hasNutrition = $hospital->service_nutrition || $hospital->nutri_medicine_list_id;
                                $hasServices = $hasOncology || $hospital->service_antibiotics || $hasNutrition;
                            @endphp
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-3 font-medium text-gray-900">{{ $hospital->name }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-col items-start gap-1">
                                        @forelse ($hospital->users as $hospitalUser)
                                            <span class="inline-flex items-center gap-2 whitespace-nowrap font-medium text-gray-900">
                                                <span class="h-1.5 w-1.5 rounded-full {{ $hospitalUser->is_active ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                                                {{ $hospitalUser->username }}
                                            </span>
                                        @empty
                                            <span class="text-gray-400">Sin usuario</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-col items-start gap-1">
                                        @forelse ($hospital->users as $hospitalUser)
                                            <div class="flex items-center gap-2 whitespace-nowrap">
                                                <span class="font-semibold tracking-widest text-gray-500" aria-label="Contrase&ntilde;a protegida">
                                                    &bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;
                                                </span>
                                                <form method="POST"
                                                    action="{{ route('admin.instituciones.hospitals.credentials.reset', [$institucion, $hospital, $hospitalUser]) }}"
                                                    onsubmit="return confirm('Se reemplazara la contrasena actual de este hospital. Desea continuar?')">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit"
                                                        class="inline-flex min-h-8 items-center justify-center gap-1.5 rounded-full bg-azul-prodifem px-3 py-1.5 text-[11px] font-semibold text-white hover:bg-blue-900 focus:outline-none focus:ring-4 focus:ring-blue-200">
                                                        <i class="fa-solid fa-key" aria-hidden="true"></i>
                                                        Generar y mostrar
                                                    </button>
                                                </form>
                                            </div>
                                        @empty
                                            <span class="text-gray-400">Sin acceso</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ $hospital->internal_key ?: '-' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ $hospital->unit_type ?: '-' }}</td>
                                <td class="px-4 py-3 whitespace-nowrap">{{ $hospital->municipality ?: '-' }}</td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-wrap gap-1">
                                        @if ($hasOncology)
                                            <span class="rounded border border-emerald-300 bg-emerald-50 px-2 py-1 text-[10px] font-medium text-emerald-700">Oncol&oacute;gicos</span>
                                        @endif
                                        @if ($hospital->service_antibiotics)
                                            <span class="rounded border border-red-300 bg-red-50 px-2 py-1 text-[10px] font-medium text-red-700">Antibi&oacute;ticos</span>
                                        @endif
                                        @if ($hasNutrition)
                                            <span class="rounded border border-blue-300 bg-blue-50 px-2 py-1 text-[10px] font-medium text-blue-700">Nutricionales</span>
                                        @endif
                                        @unless ($hasServices)
                                            <span class="text-gray-400">Sin definir</span>
                                        @endunless
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="inline-flex items-center gap-2 font-medium {{ $hospital->is_active ? 'text-emerald-700' : 'text-gray-500' }}">
                                        <span class="h-2 w-2 rounded-full {{ $hospital->is_active ? 'bg-emerald-500' : 'bg-gray-400' }}"></span>
                                        {{ $hospital->is_active ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-center whitespace-nowrap">
                                    <x-table-action-link href="{{ route('admin.hospitals.edit', $hospital) }}"
                                        icon="fa-solid fa-pen">
                                        Editar
                                    </x-table-action-link>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-4 py-10 text-center text-sm text-gray-500">
                                    No hay hospitales relacionados con estos filtros.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($hospitals->hasPages())
                <div class="border-t border-gray-200 px-4 py-3">
                    {{ $hospitals->links() }}
                </div>
            @endif
        </div>
    </section>

    @if (session()->has('hospitalTemporaryCredential'))
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const modal = document.getElementById('hospital-credential-modal');
                const passwordInput = document.getElementById('hospital-temporary-password');
                const copyButton = modal?.querySelector('[data-copy-hospital-password]');
                const copyLabel = modal?.querySelector('[data-copy-label]');

                modal?.querySelectorAll('[data-close-hospital-credential]').forEach((button) => {
                    button.addEventListener('click', () => modal.remove());
                });

                copyButton?.addEventListener('click', async () => {
                    try {
                        await navigator.clipboard.writeText(passwordInput.value);
                        copyLabel.textContent = 'Copiada';
                    } catch (error) {
                        passwordInput.select();
                        document.execCommand('copy');
                        copyLabel.textContent = 'Copiada';
                    }
                });
            });
        </script>
    @endif
</x-admin-layout>
