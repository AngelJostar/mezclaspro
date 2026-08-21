<x-admin-layout>
    <section class="rounded-lg bg-white p-5 shadow-lg">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <h1 class="text-2xl font-semibold text-gray-900">Lista de Usuarios</h1>
                <p class="mt-1 text-sm text-gray-500">Administra los accesos internos y las cuentas de hospitales.</p>
            </div>

            @if ($section === 'prodifem')
                <a href="{{ route('admin.users.create') }}"
                    class="inline-flex h-10 items-center justify-center gap-2 self-start rounded-md bg-azul-prodifem px-4 text-sm font-semibold text-white hover:bg-blue-900">
                    <i class="fa-solid fa-plus" aria-hidden="true"></i>
                    Agregar usuario
                </a>
            @endif
        </div>

        <nav class="mt-4 flex max-w-2xl overflow-x-auto rounded-md border border-gray-200 bg-gray-50"
            aria-label="Tipo de usuarios" role="tablist">
            @if ($isSuperAdmin)
                <a href="{{ route('admin.users.index', ['section' => 'prodifem']) }}" role="tab"
                    aria-selected="{{ $section === 'prodifem' ? 'true' : 'false' }}"
                    class="inline-flex min-h-10 flex-1 items-center justify-center gap-2 border-b-2 px-4 text-sm font-semibold transition {{ $section === 'prodifem' ? 'border-blue-600 bg-white text-blue-800' : 'border-transparent text-gray-600 hover:bg-white hover:text-gray-900' }}">
                    <i class="fa-solid fa-building" aria-hidden="true"></i>
                    Prodifem
                </a>
            @endif

            <a href="{{ route('admin.users.index', ['section' => 'instituciones']) }}" role="tab"
                aria-selected="{{ $section === 'instituciones' ? 'true' : 'false' }}"
                class="inline-flex min-h-10 flex-1 items-center justify-center gap-2 border-b-2 px-4 text-sm font-semibold transition {{ $section === 'instituciones' ? 'border-blue-600 bg-white text-blue-800' : 'border-transparent text-gray-600 hover:bg-white hover:text-gray-900' }}">
                <i class="fa-solid fa-hospital" aria-hidden="true"></i>
                Instituciones y Hospitales
            </a>
        </nav>

        @if ($section === 'prodifem')
            <div class="mt-5">
                <div class="mb-3">
                    <h2 class="text-lg font-semibold text-gray-900">Usuarios Prodifem</h2>
                    <p class="text-sm text-gray-500">Empleados y colaboradores internos de la central de mezclas.</p>
                </div>

                <div class="mb-4 rounded-md border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900" role="note">
                    <div class="flex items-start gap-3">
                        <i class="fa-solid fa-circle-info mt-0.5 text-blue-600" aria-hidden="true"></i>
                        <div>
                            <p class="font-semibold">Las credenciales del software y de capacitaci&oacute;n son independientes.</p>
                            <div class="mt-2 flex flex-wrap gap-x-6 gap-y-2 text-xs font-medium">
                                <span class="inline-flex items-center gap-2">
                                    <i class="fa-solid fa-desktop text-blue-800" aria-hidden="true"></i>
                                    Acceso al software
                                </span>
                                <span class="inline-flex items-center gap-2 text-teal-700">
                                    <i class="fa-solid fa-graduation-cap" aria-hidden="true"></i>
                                    Acceso a capacitaci&oacute;n
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="overflow-x-auto rounded-md border border-gray-200">
                    <table class="w-full min-w-[1840px] table-fixed text-left text-sm text-gray-600">
                        <colgroup>
                            <col class="w-16">
                            <col class="w-64">
                            <col class="w-60">
                            <col class="w-64">
                            <col class="w-60">
                            <col class="w-64">
                            <col class="w-72">
                            <col class="w-44">
                            <col class="w-36">
                            <col class="w-32">
                        </colgroup>
                        <thead class="text-xs uppercase text-gray-700">
                            <tr class="bg-gray-50">
                                <th scope="col" rowspan="2" class="px-4 py-3 align-middle">Id</th>
                                <th scope="col" rowspan="2" class="px-4 py-3 align-middle">Nombre</th>
                                <th scope="colgroup" colspan="2"
                                    class="border-x border-blue-200 bg-blue-100 px-4 py-3 text-center font-bold text-blue-900">
                                    <i class="fa-solid fa-desktop mr-2" aria-hidden="true"></i>
                                    Acceso al software
                                </th>
                                <th scope="colgroup" colspan="2"
                                    class="border-r border-teal-200 bg-teal-100 px-4 py-3 text-center font-bold text-teal-800">
                                    <i class="fa-solid fa-graduation-cap mr-2" aria-hidden="true"></i>
                                    Acceso a capacitaci&oacute;n
                                </th>
                                <th scope="col" rowspan="2" class="px-4 py-3 align-middle">Central</th>
                                <th scope="col" rowspan="2" class="px-4 py-3 align-middle">Roles</th>
                                <th scope="col" rowspan="2" class="px-4 py-3 text-center align-middle">Editar</th>
                                <th scope="col" rowspan="2" class="px-4 py-3 text-center align-middle">Bloqueo</th>
                            </tr>
                            <tr class="border-t border-gray-200 bg-gray-50 text-[11px]">
                                <th scope="col" class="border-l border-blue-200 bg-blue-50 px-4 py-2">Usuario software</th>
                                <th scope="col" class="border-r border-blue-200 bg-blue-50 px-4 py-2">Contrase&ntilde;a software</th>
                                <th scope="col" class="bg-teal-50 px-4 py-2">Usuario capacitaci&oacute;n</th>
                                <th scope="col" class="border-r border-teal-200 bg-teal-50 px-4 py-2">Contrase&ntilde;a capacitaci&oacute;n</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @forelse ($users as $user)
                                <tr class="hover:bg-gray-50">
                                    <td class="px-4 py-4 font-medium text-gray-900">{{ $user->id }}</td>
                                    <td class="px-4 py-4 font-medium text-gray-900">{{ trim($user->name . ' ' . $user->lastname) }}</td>
                                    <td class="border-l border-blue-100 px-4 py-4">
                                        <x-inline-user-credential-editor :user="$user" field="username" />
                                    </td>
                                    <td class="border-r border-blue-100 px-4 py-4">
                                        <x-inline-user-credential-editor :user="$user" field="password" />
                                    </td>
                                    <td class="px-4 py-4">
                                        <x-inline-user-credential-editor :user="$user" field="training_username" />
                                    </td>
                                    <td class="border-r border-teal-100 px-4 py-4">
                                        <x-inline-user-credential-editor :user="$user" field="training_password" />
                                    </td>
                                    <td class="px-4 py-4">{{ $user->hospital?->laboratory?->nombre ?? 'Sin central asignada' }}</td>
                                    <td class="px-4 py-4" data-role-cell="{{ $user->id }}">
                                        {{ $user->roles
                                            ->map(fn ($role) => \App\Support\AdminMenuAccess::roleLabel($role->name))
                                            ->join(', ') ?: 'Sin rol asignado' }}
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        @if ($isSuperAdmin)
                                            <button type="button"
                                                class="inline-flex h-8 items-center justify-center gap-1.5 rounded-md bg-azul-prodifem px-3 text-xs font-semibold text-white hover:bg-blue-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
                                                data-role-access-open
                                                data-action="{{ route('admin.users.role-access.update', $user) }}"
                                                data-user-name="{{ trim($user->name . ' ' . $user->lastname) }}"
                                                data-current-role="{{ $user->roles->first()?->name ?? '' }}"
                                                data-menu-permissions='@json($user->permissions->pluck('name')->filter(fn ($permission) => str_starts_with($permission, 'menu.'))->values())'>
                                                <i class="fa-solid fa-pen-to-square" aria-hidden="true"></i>
                                                Editar
                                            </button>
                                        @else
                                            <span class="text-gray-400">-</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 text-center">
                                        <x-access-status-toggle type="user" :target="$user" :is-active="$user->is_active" />
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="10" class="px-5 py-10 text-center text-sm text-gray-500">
                                        No hay colaboradores de Prodifem registrados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($users->hasPages())
                    <div class="mt-4">{{ $users->links() }}</div>
                @endif
            </div>
        @else
            <div class="mt-5">
                <div class="mb-3">
                    <h2 class="text-lg font-semibold text-gray-900">Instituciones y Hospitales</h2>
                    <p class="text-sm text-gray-500">Abre una instituci&oacute;n para consultar sus hospitales y accesos.</p>
                </div>

                <div class="mb-4 rounded-md border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900" role="note">
                    <div class="flex items-center gap-3">
                        <i class="fa-solid fa-circle-info text-blue-600" aria-hidden="true"></i>
                        <p>Los accesos al software y a capacitaci&oacute;n se administran por separado. El bloqueo de una instituci&oacute;n afecta a todos sus hospitales.</p>
                    </div>
                </div>

                <div class="overflow-hidden rounded-md border border-gray-200">
                    @forelse ($institutions as $institution)
                        @php
                            $institutionAccessActive = $institution->is_active;
                        @endphp
                        <details class="group border-b border-gray-200 last:border-b-0">
                            <summary class="flex min-h-16 cursor-pointer list-none items-center justify-between gap-4 px-4 py-3 hover:bg-gray-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-blue-500 [&::-webkit-details-marker]:hidden">
                                <div class="min-w-0">
                                    <p class="truncate text-sm font-semibold text-gray-900">{{ $institution->nombre }}</p>
                                    @if ($institution->razon_social && $institution->razon_social !== $institution->nombre)
                                        <p class="mt-0.5 truncate text-xs text-gray-500">{{ $institution->razon_social }}</p>
                                    @endif
                                </div>

                                <div class="flex shrink-0 items-center gap-3">
                                    <span class="text-xs font-medium text-gray-500">
                                        {{ $institution->hospitals_count }} {{ $institution->hospitals_count === 1 ? 'hospital' : 'hospitales' }}
                                    </span>
                                    <x-access-status-toggle type="institution" :target="$institution"
                                        :is-active="$institutionAccessActive" compact />
                                    <span class="inline-flex h-8 w-8 items-center justify-center rounded-full border border-blue-800 text-lg font-semibold text-blue-800"
                                        aria-hidden="true">
                                        <span class="group-open:hidden">+</span>
                                        <span class="hidden group-open:inline">&minus;</span>
                                    </span>
                                </div>
                            </summary>

                            <div class="border-t border-gray-200 bg-gray-50 p-4">
                                @if ($institution->hospitals->isEmpty())
                                    <p class="py-6 text-center text-sm text-gray-500">Esta instituci&oacute;n no tiene hospitales registrados.</p>
                                @else
                                    <div class="overflow-x-auto rounded border border-gray-200 bg-white">
                                        <table class="w-full min-w-[1580px] table-fixed text-left text-xs text-gray-600">
                                            <colgroup>
                                                <col class="w-56">
                                                <col class="w-32">
                                                <col class="w-56">
                                                <col class="w-64">
                                                <col class="w-56">
                                                <col class="w-64">
                                                <col class="w-40">
                                                <col class="w-36">
                                            </colgroup>
                                            <thead class="text-[11px] uppercase text-gray-700">
                                                <tr class="bg-gray-100">
                                                    <th rowspan="2" class="px-4 py-3 align-middle">Hospital</th>
                                                    <th rowspan="2" class="px-4 py-3 align-middle">Clave</th>
                                                    <th colspan="2" class="border-x border-blue-200 bg-blue-100 px-4 py-3 text-center text-blue-900">
                                                        Acceso al software
                                                    </th>
                                                    <th colspan="2" class="border-r border-teal-200 bg-teal-100 px-4 py-3 text-center text-teal-800">
                                                        Acceso a capacitaci&oacute;n
                                                    </th>
                                                    <th rowspan="2" class="px-4 py-3 align-middle">Roles</th>
                                                    <th rowspan="2" class="px-4 py-3 text-center align-middle">Bloqueo</th>
                                                </tr>
                                                <tr class="border-t border-gray-200 bg-gray-100">
                                                    <th class="border-l border-blue-200 bg-blue-50 px-4 py-2">Usuario software</th>
                                                    <th class="border-r border-blue-200 bg-blue-50 px-4 py-2">Contrase&ntilde;a software</th>
                                                    <th class="bg-teal-50 px-4 py-2">Usuario capacitaci&oacute;n</th>
                                                    <th class="border-r border-teal-200 bg-teal-50 px-4 py-2">Contrase&ntilde;a capacitaci&oacute;n</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-200">
                                                @foreach ($institution->hospitals as $hospital)
                                                    @php
                                                        $hospitalHasAccess = $hospital->users->isNotEmpty();
                                                        $hospitalAccessActive = (bool) $hospital->access_is_active;
                                                    @endphp
                                                    <tr class="align-top hover:bg-gray-50">
                                                        <td class="px-4 py-3 font-semibold text-gray-900">{{ $hospital->name }}</td>
                                                        <td class="px-4 py-3">{{ $hospital->internal_key ?: '-' }}</td>
                                                        <td class="border-l border-blue-100 px-4 py-3">
                                                            @forelse ($hospital->users as $accessUser)
                                                                <x-inline-user-credential-editor :user="$accessUser" field="username" compact
                                                                    class="{{ ! $loop->first ? 'mt-2 border-t border-gray-100 pt-2' : '' }}" />
                                                            @empty
                                                                <div class="flex items-center justify-between gap-2">
                                                                    <span class="text-gray-400">Sin usuario</span>
                                                                    @can('usuarios')
                                                                        <a href="{{ route('admin.users.create', ['hospital_id' => $hospital->id, 'role' => 'Institucion']) }}"
                                                                            class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-md border border-blue-800 text-blue-800 hover:bg-blue-50"
                                                                            title="Crear acceso" aria-label="Crear acceso">
                                                                            <i class="fa-solid fa-plus" aria-hidden="true"></i>
                                                                        </a>
                                                                    @endcan
                                                                </div>
                                                            @endforelse
                                                        </td>
                                                        <td class="border-r border-blue-100 px-4 py-3">
                                                            @forelse ($hospital->users as $accessUser)
                                                                <x-inline-user-credential-editor :user="$accessUser" field="password" compact
                                                                    class="{{ ! $loop->first ? 'mt-2 border-t border-gray-100 pt-2' : '' }}" />
                                                            @empty
                                                                <span class="text-gray-400">Sin contrase&ntilde;a</span>
                                                            @endforelse
                                                        </td>
                                                        <td class="px-4 py-3">
                                                            @forelse ($hospital->users as $accessUser)
                                                                <x-inline-user-credential-editor :user="$accessUser" field="training_username" compact
                                                                    class="{{ ! $loop->first ? 'mt-2 border-t border-gray-100 pt-2' : '' }}" />
                                                            @empty
                                                                <span class="text-gray-400">Sin usuario</span>
                                                            @endforelse
                                                        </td>
                                                        <td class="border-r border-teal-100 px-4 py-3">
                                                            @forelse ($hospital->users as $accessUser)
                                                                <x-inline-user-credential-editor :user="$accessUser" field="training_password" compact
                                                                    class="{{ ! $loop->first ? 'mt-2 border-t border-gray-100 pt-2' : '' }}" />
                                                            @empty
                                                                <span class="text-gray-400">Sin contrase&ntilde;a</span>
                                                            @endforelse
                                                        </td>
                                                        <td class="px-4 py-3">
                                                            {{ $hospital->users
                                                                ->flatMap->roles
                                                                ->unique('id')
                                                                ->map(fn ($role) => $role->name === 'Cliente' ? 'Institucion' : $role->name)
                                                                ->join(', ') ?: 'Sin acceso' }}
                                                        </td>
                                                        <td class="px-4 py-3 text-center">
                                                            <x-access-status-toggle type="hospital" :target="$hospital"
                                                                :is-active="$hospitalAccessActive" :disabled="! $hospitalHasAccess" compact />
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                @endif
                            </div>
                        </details>
                    @empty
                        <p class="px-4 py-10 text-center text-sm text-gray-500">No hay instituciones registradas.</p>
                    @endforelse
                </div>

                @if ($institutions->hasPages())
                    <div class="mt-4">{{ $institutions->links() }}</div>
                @endif
            </div>
        @endif
    </section>

    @if ($isSuperAdmin)
        <div class="fixed inset-0 z-[70] hidden items-center justify-center bg-gray-950/50 p-4"
            data-role-access-modal role="dialog" aria-modal="true" aria-labelledby="role-access-title">
            <div class="flex max-h-[92vh] w-full max-w-4xl flex-col overflow-hidden rounded-lg bg-white shadow-2xl">
                <div class="flex items-start justify-between gap-4 border-b border-gray-200 px-5 py-4">
                    <div>
                        <h2 id="role-access-title" class="text-xl font-semibold text-gray-900">Administrar rol y accesos</h2>
                        <p class="mt-1 text-sm text-gray-500">
                            Usuario: <span class="font-semibold text-gray-700" data-role-access-user></span>
                        </p>
                    </div>
                    <button type="button"
                        class="inline-flex h-9 w-9 shrink-0 items-center justify-center rounded-md text-gray-500 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        data-role-access-close aria-label="Cerrar">
                        <i class="fa-solid fa-xmark text-lg" aria-hidden="true"></i>
                    </button>
                </div>

                <form class="flex min-h-0 flex-1 flex-col" data-role-access-form>
                    @csrf
                    @method('PATCH')

                    <div class="min-h-0 flex-1 overflow-y-auto px-5 py-4">
                        <fieldset>
                            <legend class="text-sm font-semibold text-gray-900">Rol del usuario</legend>
                            <div class="mt-2 grid gap-2 sm:grid-cols-3">
                                @foreach ($roleOptions as $roleName => $roleLabel)
                                    <label class="flex min-h-12 cursor-pointer items-center gap-3 rounded-md border border-gray-200 px-3 py-2 hover:border-blue-300 hover:bg-blue-50">
                                        <input type="radio" name="role" value="{{ $roleName }}"
                                            class="h-4 w-4 border-gray-300 text-blue-700 focus:ring-blue-500"
                                            data-role-option required>
                                        <span class="text-sm font-semibold text-gray-800">{{ $roleLabel }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </fieldset>

                        <fieldset class="mt-5" data-role-menu-fieldset>
                            <div class="flex flex-wrap items-center justify-between gap-3 border-b border-gray-200 pb-3">
                                <div>
                                    <legend class="text-sm font-semibold text-gray-900">Accesos de Usuario general</legend>
                                    <p class="mt-1 text-xs text-gray-500">Selecciona los menús y submenús disponibles para este usuario.</p>
                                </div>
                                <label class="inline-flex cursor-pointer items-center gap-2 text-sm font-semibold text-blue-800">
                                    <input type="checkbox" class="h-4 w-4 rounded border-gray-300 text-blue-700 focus:ring-blue-500"
                                        data-role-select-all>
                                    Seleccionar todo
                                </label>
                            </div>

                            <div class="mt-3 divide-y divide-gray-200 rounded-md border border-gray-200">
                                @foreach ($roleAccessTree as $menuItem)
                                    @if (! empty($menuItem['children']))
                                        <details class="group" data-role-menu-group>
                                            <summary class="flex min-h-12 cursor-pointer list-none items-center gap-3 px-3 py-2 hover:bg-gray-50 [&::-webkit-details-marker]:hidden">
                                                <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded-md border border-gray-200 text-gray-600">
                                                    <i class="fa-solid fa-plus text-xs group-open:hidden" aria-hidden="true"></i>
                                                    <i class="fa-solid fa-minus hidden text-xs group-open:inline" aria-hidden="true"></i>
                                                </span>
                                                <i class="fa-solid {{ $menuItem['icon'] }} w-5 text-center text-blue-700" aria-hidden="true"></i>
                                                <span class="min-w-0 flex-1 text-sm font-semibold text-gray-800">{{ $menuItem['label'] }}</span>
                                                <input type="checkbox" name="menu_permissions[]"
                                                    value="{{ $menuItem['permission'] }}"
                                                    class="h-4 w-4 rounded border-gray-300 text-blue-700 focus:ring-blue-500"
                                                    data-role-menu-parent aria-label="Dar acceso a {{ $menuItem['label'] }}"
                                                    onclick="event.stopPropagation()">
                                            </summary>
                                            <div class="grid gap-1 bg-gray-50 px-4 py-3 sm:grid-cols-2">
                                                @foreach ($menuItem['children'] as $child)
                                                    <label class="flex min-h-9 cursor-pointer items-center gap-3 rounded-md px-3 py-2 text-sm text-gray-700 hover:bg-white">
                                                        <input type="checkbox" name="menu_permissions[]"
                                                            value="{{ $child['permission'] }}"
                                                            class="h-4 w-4 rounded border-gray-300 text-blue-700 focus:ring-blue-500"
                                                            data-role-menu-child>
                                                        <span>{{ $child['label'] }}</span>
                                                    </label>
                                                @endforeach
                                            </div>
                                        </details>
                                    @else
                                        <label class="flex min-h-12 cursor-pointer items-center gap-3 px-3 py-2 hover:bg-gray-50">
                                            <span class="inline-flex h-7 w-7 shrink-0 items-center justify-center text-gray-400">
                                                <i class="fa-solid fa-circle text-[6px]" aria-hidden="true"></i>
                                            </span>
                                            <i class="fa-solid {{ $menuItem['icon'] }} w-5 text-center text-blue-700" aria-hidden="true"></i>
                                            <span class="min-w-0 flex-1 text-sm font-semibold text-gray-800">{{ $menuItem['label'] }}</span>
                                            <input type="checkbox" name="menu_permissions[]"
                                                value="{{ $menuItem['permission'] }}"
                                                class="h-4 w-4 rounded border-gray-300 text-blue-700 focus:ring-blue-500"
                                                data-role-menu-parent>
                                        </label>
                                    @endif
                                @endforeach
                            </div>
                        </fieldset>

                        <p class="mt-3 hidden text-sm font-medium text-red-600" data-role-access-error></p>
                    </div>

                    <div class="flex items-center justify-end gap-3 border-t border-gray-200 bg-gray-50 px-5 py-4">
                        <button type="button"
                            class="inline-flex h-10 items-center justify-center rounded-md border border-gray-300 bg-white px-4 text-sm font-semibold text-gray-700 hover:bg-gray-100"
                            data-role-access-close>
                            Cancelar
                        </button>
                        <button type="submit"
                            class="inline-flex h-10 items-center justify-center gap-2 rounded-md bg-azul-prodifem px-4 text-sm font-semibold text-white hover:bg-blue-900 disabled:cursor-not-allowed disabled:opacity-60"
                            data-role-access-save>
                            <i class="fa-solid fa-check" aria-hidden="true"></i>
                            Guardar cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    @push('js')
        <script>
            (function() {
                if (window.userAccessTableReady) {
                    return;
                }

                window.userAccessTableReady = true;
                const editorSelector = '[data-inline-credential-form]';

                function isPasswordEditor(form) {
                    return form.dataset.isPassword === 'true';
                }

                function closeEditor(form, restoreValue) {
                    const display = form.querySelector('[data-inline-display]');
                    const editor = form.querySelector('[data-inline-editor]');
                    const input = form.querySelector('[data-inline-input]');
                    const error = form.querySelector('[data-inline-error]');

                    if (restoreValue && input) {
                        input.value = form.dataset.originalValue || '';
                    }

                    if (display) {
                        display.hidden = false;
                    }

                    if (editor) {
                        editor.hidden = true;
                    }

                    if (error) {
                        error.hidden = true;
                        error.textContent = '';
                    }

                }

                function openEditor(form) {
                    document.querySelectorAll(editorSelector).forEach(function(otherForm) {
                        if (otherForm !== form) {
                            closeEditor(otherForm, true);
                        }
                    });

                    const display = form.querySelector('[data-inline-display]');
                    const editor = form.querySelector('[data-inline-editor]');
                    const input = form.querySelector('[data-inline-input]');

                    if (display) {
                        display.hidden = true;
                    }

                    if (editor) {
                        editor.hidden = false;
                    }

                    if (input) {
                        input.focus();
                        input.select();
                    }
                }

                function updateDisplayedValue(form, value) {
                    const valueElement = form.querySelector('[data-inline-value]');
                    const emptyLabel = form.dataset.emptyLabel || '';

                    if (!valueElement) {
                        return;
                    }

                    valueElement.textContent = value || emptyLabel;

                    if (isPasswordEditor(form)) {
                        valueElement.className = value
                            ? 'min-w-0 flex-1 truncate font-mono font-semibold text-gray-900'
                            : 'min-w-0 flex-1 text-[11px] font-medium text-amber-700';
                    } else {
                        valueElement.className = 'min-w-0 flex-1 truncate font-medium text-gray-800';
                    }
                }

                function validationMessage(payload) {
                    if (payload && payload.errors) {
                        const firstError = Object.values(payload.errors).flat()[0];

                        if (firstError) {
                            return firstError;
                        }
                    }

                    return payload && payload.message
                        ? payload.message
                        : 'No fue posible guardar el cambio.';
                }

                const roleModal = document.querySelector('[data-role-access-modal]');
                const roleForm = roleModal ? roleModal.querySelector('[data-role-access-form]') : null;
                const generalRole = @json(\App\Support\AdminMenuAccess::GENERAL_ROLE);
                let roleModalTrigger = null;

                function roleMenuCheckboxes() {
                    return roleForm
                        ? Array.from(roleForm.querySelectorAll('[data-role-menu-parent], [data-role-menu-child]'))
                        : [];
                }

                function updateRoleSelectAll() {
                    if (!roleForm) {
                        return;
                    }

                    const selectAll = roleForm.querySelector('[data-role-select-all]');
                    const checkboxes = roleMenuCheckboxes();
                    const checkedCount = checkboxes.filter(function(checkbox) { return checkbox.checked; }).length;

                    if (selectAll) {
                        selectAll.checked = checkboxes.length > 0 && checkedCount === checkboxes.length;
                        selectAll.indeterminate = checkedCount > 0 && checkedCount < checkboxes.length;
                    }
                }

                function syncRoleMenuGroup(group) {
                    const parent = group.querySelector('[data-role-menu-parent]');
                    const children = Array.from(group.querySelectorAll('[data-role-menu-child]'));

                    if (!parent || children.length === 0) {
                        return;
                    }

                    const checkedCount = children.filter(function(child) { return child.checked; }).length;
                    parent.checked = checkedCount > 0;
                    parent.indeterminate = checkedCount > 0 && checkedCount < children.length;
                }

                function updateRoleMenuAvailability() {
                    if (!roleForm) {
                        return;
                    }

                    const fieldset = roleForm.querySelector('[data-role-menu-fieldset]');
                    const selectedRole = roleForm.querySelector('input[name="role"]:checked');
                    const disabled = !selectedRole || selectedRole.value !== generalRole;

                    if (fieldset) {
                        fieldset.disabled = disabled;
                        fieldset.classList.toggle('opacity-50', disabled);
                    }
                }

                function setRoleMenuSelection(selectedPermissions) {
                    const selected = new Set(selectedPermissions || []);

                    roleMenuCheckboxes().forEach(function(checkbox) {
                        checkbox.checked = selected.has(checkbox.value);
                        checkbox.indeterminate = false;
                    });

                    if (roleForm) {
                        roleForm.querySelectorAll('[data-role-menu-group]').forEach(syncRoleMenuGroup);
                    }

                    updateRoleSelectAll();
                }

                function openRoleAccessModal(button) {
                    if (!roleModal || !roleForm) {
                        return;
                    }

                    roleModalTrigger = button;
                    roleForm.action = button.dataset.action || '';

                    const userLabel = roleModal.querySelector('[data-role-access-user]');
                    const error = roleForm.querySelector('[data-role-access-error]');
                    const roleRadios = Array.from(roleForm.querySelectorAll('[data-role-option]'));
                    const currentRole = button.dataset.currentRole || '';
                    const selectedRole = roleRadios.find(function(radio) { return radio.value === currentRole; })
                        || roleRadios.find(function(radio) { return radio.value === 'Admin'; })
                        || roleRadios[0];

                    roleRadios.forEach(function(radio) {
                        radio.checked = radio === selectedRole;
                    });

                    let selectedPermissions = [];

                    try {
                        selectedPermissions = JSON.parse(button.dataset.menuPermissions || '[]');
                    } catch (parseError) {
                        selectedPermissions = [];
                    }

                    setRoleMenuSelection(selectedPermissions);
                    updateRoleMenuAvailability();

                    if (userLabel) {
                        userLabel.textContent = button.dataset.userName || '';
                    }

                    if (error) {
                        error.hidden = true;
                        error.textContent = '';
                    }

                    roleModal.classList.remove('hidden');
                    roleModal.classList.add('flex');
                    document.body.classList.add('overflow-hidden');
                    window.setTimeout(function() {
                        selectedRole?.focus();
                    }, 0);
                }

                function closeRoleAccessModal() {
                    if (!roleModal) {
                        return;
                    }

                    roleModal.classList.add('hidden');
                    roleModal.classList.remove('flex');
                    document.body.classList.remove('overflow-hidden');
                    roleModalTrigger?.focus();
                    roleModalTrigger = null;
                }

                document.addEventListener('click', function(event) {
                    const roleOpenButton = event.target.closest('[data-role-access-open]');

                    if (roleOpenButton) {
                        openRoleAccessModal(roleOpenButton);
                        return;
                    }

                    if (event.target.closest('[data-role-access-close]') || event.target === roleModal) {
                        closeRoleAccessModal();
                        return;
                    }

                    const statusButton = event.target.closest('[data-access-status-form] button[type="submit"]');

                    if (statusButton) {
                        event.stopPropagation();
                        return;
                    }

                    const startButton = event.target.closest('[data-inline-start]');

                    if (startButton) {
                        const form = startButton.closest(editorSelector);

                        if (form) {
                            openEditor(form);
                        }

                        return;
                    }

                    const cancelButton = event.target.closest('[data-inline-cancel]');

                    if (cancelButton) {
                        const form = cancelButton.closest(editorSelector);

                        if (form) {
                            closeEditor(form, true);
                        }
                    }
                });

                document.addEventListener('change', function(event) {
                    if (!roleForm || !event.target.closest('[data-role-access-form]')) {
                        return;
                    }

                    if (event.target.matches('[data-role-option]')) {
                        updateRoleMenuAvailability();
                        return;
                    }

                    if (event.target.matches('[data-role-select-all]')) {
                        roleMenuCheckboxes().forEach(function(checkbox) {
                            checkbox.checked = event.target.checked;
                            checkbox.indeterminate = false;
                        });

                        roleForm.querySelectorAll('[data-role-menu-group]').forEach(syncRoleMenuGroup);
                        updateRoleSelectAll();
                        return;
                    }

                    if (event.target.matches('[data-role-menu-parent]')) {
                        const group = event.target.closest('[data-role-menu-group]');

                        if (group) {
                            group.querySelectorAll('[data-role-menu-child]').forEach(function(child) {
                                child.checked = event.target.checked;
                            });
                            event.target.indeterminate = false;
                        }
                    }

                    if (event.target.matches('[data-role-menu-child]')) {
                        const group = event.target.closest('[data-role-menu-group]');

                        if (group) {
                            syncRoleMenuGroup(group);
                        }
                    }

                    updateRoleSelectAll();
                });

                document.addEventListener('keydown', function(event) {
                    if (event.key === 'Escape' && roleModal && !roleModal.classList.contains('hidden')) {
                        event.preventDefault();
                        closeRoleAccessModal();
                        return;
                    }

                    if (event.key !== 'Escape' || !event.target.matches('[data-inline-input]')) {
                        return;
                    }

                    const form = event.target.closest(editorSelector);

                    if (form) {
                        event.preventDefault();
                        closeEditor(form, true);
                    }
                });

                document.addEventListener('submit', async function(event) {
                    const form = event.target.closest('[data-role-access-form]');

                    if (!form) {
                        return;
                    }

                    event.preventDefault();

                    if (!form.reportValidity()) {
                        return;
                    }

                    const saveButton = form.querySelector('[data-role-access-save]');
                    const error = form.querySelector('[data-role-access-error]');

                    if (error) {
                        error.hidden = true;
                        error.textContent = '';
                    }

                    if (saveButton) {
                        saveButton.disabled = true;
                        saveButton.setAttribute('aria-busy', 'true');
                    }

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: new FormData(form),
                        });
                        const payload = await response.json().catch(function() { return {}; });

                        if (!response.ok) {
                            throw new Error(validationMessage(payload));
                        }

                        closeRoleAccessModal();
                        await Swal.fire({
                            title: 'Accesos actualizados',
                            text: payload.message || 'El rol y los permisos se guardaron correctamente.',
                            icon: 'success',
                            confirmButtonText: 'Aceptar',
                        });
                        window.location.reload();
                    } catch (requestError) {
                        if (error) {
                            error.textContent = requestError.message;
                            error.hidden = false;
                        }
                    } finally {
                        if (saveButton) {
                            saveButton.disabled = false;
                            saveButton.removeAttribute('aria-busy');
                        }
                    }
                });

                document.addEventListener('submit', async function(event) {
                    const form = event.target.closest(editorSelector);

                    if (!form) {
                        return;
                    }

                    event.preventDefault();
                    const submitButton = form.querySelector('[data-inline-submit]');
                    const cancelButton = form.querySelector('[data-inline-cancel]');
                    const input = form.querySelector('[data-inline-input]');
                    const error = form.querySelector('[data-inline-error]');
                    const submitIcon = form.querySelector('[data-inline-submit-icon]');
                    const submitSpinner = form.querySelector('[data-inline-submit-spinner]');

                    if (!input || !form.reportValidity()) {
                        return;
                    }

                    if (error) {
                        error.hidden = true;
                        error.textContent = '';
                    }

                    const submittedValue = input.value;
                    const formData = new FormData(form);
                    input.disabled = true;

                    [submitButton, cancelButton].forEach(function(button) {
                        if (button) {
                            button.disabled = true;
                        }
                    });

                    if (submitButton) {
                        submitButton.setAttribute('aria-busy', 'true');
                    }

                    if (submitIcon) {
                        submitIcon.hidden = true;
                    }

                    if (submitSpinner) {
                        submitSpinner.hidden = false;
                    }

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: formData,
                        });
                        const payload = await response.json().catch(function() { return {}; });

                        if (!response.ok) {
                            throw new Error(validationMessage(payload));
                        }

                        const savedValue = payload.value || payload.username || submittedValue.trim();
                        form.dataset.originalValue = savedValue;
                        input.value = savedValue;
                        updateDisplayedValue(form, savedValue);
                        closeEditor(form, false);
                    } catch (requestError) {
                        if (error) {
                            error.textContent = requestError.message;
                            error.hidden = false;
                        }
                    } finally {
                        input.disabled = false;

                        [submitButton, cancelButton].forEach(function(button) {
                            if (button) {
                                button.disabled = false;
                            }
                        });

                        if (submitButton) {
                            submitButton.removeAttribute('aria-busy');
                        }

                        if (submitIcon) {
                            submitIcon.hidden = false;
                        }

                        if (submitSpinner) {
                            submitSpinner.hidden = true;
                        }
                    }

                    if (!error || !error.hidden) {
                        input.focus();
                    }
                });

                document.addEventListener('submit', async function(event) {
                    const form = event.target.closest('[data-access-status-form]');

                    if (!form) {
                        return;
                    }

                    event.preventDefault();
                    event.stopPropagation();
                    const button = form.querySelector('button[type="submit"]');
                    const statusInput = form.querySelector('input[name="is_active"]');
                    const willActivate = statusInput && statusInput.value === '1';
                    const targetLabel = form.dataset.targetLabel || 'acceso';
                    const confirmation = await Swal.fire({
                        title: '¿Estás seguro?',
                        text: willActivate
                            ? `Se activará el acceso de este ${targetLabel}.`
                            : `Se bloqueará el acceso de este ${targetLabel}.`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Sí',
                        cancelButtonText: 'No',
                        confirmButtonColor: willActivate ? '#059669' : '#dc2626',
                    });

                    if (!confirmation.isConfirmed) {
                        return;
                    }

                    if (button) {
                        button.disabled = true;
                        button.setAttribute('aria-busy', 'true');
                    }

                    try {
                        const response = await fetch(form.action, {
                            method: 'POST',
                            headers: {
                                Accept: 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            body: new FormData(form),
                        });
                        const payload = await response.json().catch(function() { return {}; });

                        if (!response.ok) {
                            throw new Error(validationMessage(payload));
                        }

                        await Swal.fire({
                            title: willActivate ? 'Acceso activado' : 'Acceso bloqueado',
                            text: payload.message || 'El cambio se guardó correctamente.',
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

                        if (button) {
                            button.disabled = false;
                            button.removeAttribute('aria-busy');
                        }
                    }
                });
            })();
        </script>
    @endpush
</x-admin-layout>
