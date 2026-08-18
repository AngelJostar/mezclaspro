<x-admin-layout>
    @role('Super Admin')
        <section class="mb-5 rounded-lg border border-slate-100 bg-white p-5 shadow-sm">
            <div class="mb-4 flex items-center justify-between gap-3">
                <h1 class="text-2xl font-medium text-slate-800">Usuarios y accesos</h1>
                <a href="{{ route('admin.users.create') }}"
                    class="inline-flex items-center rounded-full bg-azul-prodifem px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800">
                    <i class="fa-solid fa-plus mr-2"></i>Nuevo usuario
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 text-xs uppercase text-slate-700">
                        <tr>
                            <th class="px-4 py-3">ID</th>
                            <th class="px-4 py-3">Nombre</th>
                            <th class="px-4 py-3">Usuario</th>
                            <th class="px-4 py-3">Contrase&ntilde;a</th>
                            <th class="px-4 py-3">Rol</th>
                            <th class="px-4 py-3 text-center">Editar usuario</th>
                            <th class="px-4 py-3 text-center">Cambiar contrase&ntilde;a</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr class="border-b border-slate-200 bg-white">
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $user->id }}</td>
                                <td class="px-4 py-3">{{ trim($user->name . ' ' . $user->lastname) }}</td>
                                <td class="px-4 py-3 font-medium text-slate-900">{{ $user->username }}</td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-1 text-xs font-semibold text-slate-600">
                                        <i class="fa-solid fa-lock mr-1.5"></i>Protegida
                                    </span>
                                </td>
                                <td class="px-4 py-3">{{ $user->roles->pluck('name')->join(', ') ?: 'Sin rol' }}</td>
                                <td class="px-4 py-3 text-center">
                                    <x-table-action-link href="{{ route('admin.users.edit', $user) }}" icon="fa-solid fa-user-pen">
                                        Editar
                                    </x-table-action-link>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <x-table-action-link href="{{ route('admin.users.edit', $user) }}#password-section" icon="fa-solid fa-key">
                                        Cambiar
                                    </x-table-action-link>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-4 py-8 text-center text-slate-500">No hay usuarios registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>
    @endrole

    <section class="rounded-lg border border-slate-100 bg-white p-5 shadow-sm">
        <div class="mb-4 flex items-center justify-between gap-3">
            <h2 class="text-2xl font-medium text-slate-800">Roles</h2>
            <a href="{{ route('admin.roles.create') }}"
                class="inline-flex items-center rounded-full bg-azul-prodifem px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800">
                <i class="fa-solid fa-plus mr-2"></i>Agregar
            </a>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs uppercase text-slate-700">
                    <tr>
                        <th class="px-6 py-3">ID</th>
                        <th class="px-6 py-3">Nombre</th>
                        <th class="px-6 py-3 text-center">Usuarios</th>
                        <th class="px-6 py-3 text-center">Editar</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($roles as $rol)
                        <tr class="border-b border-slate-200 bg-white">
                            <td class="px-6 py-4 font-medium text-slate-900">{{ $rol->id }}</td>
                            <td class="px-6 py-4">{{ $rol->name }}</td>
                            <td class="px-6 py-4 text-center">{{ $rol->users_count }}</td>
                            <td class="px-6 py-4 text-center">
                                <x-table-action-link href="{{ route('admin.roles.edit', $rol) }}" icon="fa-solid fa-pen">
                                    Editar
                                </x-table-action-link>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </section>
</x-admin-layout>
