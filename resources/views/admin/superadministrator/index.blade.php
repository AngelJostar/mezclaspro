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

    <section class="mt-7" aria-labelledby="current-administrators-title">
        <div class="mb-3 flex items-center gap-2">
            <h2 id="current-administrators-title" class="text-lg font-semibold text-gray-900">
                Administradores actuales
            </h2>
            <span class="inline-flex min-w-6 items-center justify-center rounded-full bg-blue-100 px-2 py-0.5 text-xs font-semibold text-blue-800">
                {{ $administrators->count() }}
            </span>
        </div>

        <div class="overflow-x-auto rounded-md border border-gray-200">
            <table class="w-full min-w-[760px] table-fixed text-left text-sm text-gray-600">
                <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                    <tr>
                        <th class="w-[26%] px-4 py-3">Personal</th>
                        <th class="w-[16%] px-4 py-3">Usuario</th>
                        <th class="w-[32%] px-4 py-3">Hospital</th>
                        <th class="w-[12%] px-4 py-3 text-center">Estado</th>
                        <th class="w-[14%] px-4 py-3 text-right">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse ($administrators as $administrator)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900">
                                {{ trim($administrator->name . ' ' . $administrator->lastname) }}
                            </td>
                            <td class="px-4 py-3">{{ $administrator->username }}</td>
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
                            <td colspan="5" class="px-4 py-8 text-center text-gray-500">
                                No hay administradores nombrados.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <section class="mt-9 border-t border-gray-200 pt-7" aria-labelledby="eligible-personnel-title">
        <div class="mb-3 flex items-center gap-2">
            <h2 id="eligible-personnel-title" class="text-lg font-semibold text-gray-900">
                Personal elegible
            </h2>
            <span class="inline-flex min-w-6 items-center justify-center rounded-full bg-cyan-100 px-2 py-0.5 text-xs font-semibold text-cyan-800">
                {{ $eligiblePersonnel->count() }}
            </span>
        </div>

        <div class="overflow-x-auto rounded-md border border-gray-200">
            <table class="w-full min-w-[900px] table-fixed text-left text-sm text-gray-600">
                <thead class="bg-gray-50 text-xs uppercase text-gray-700">
                    <tr>
                        <th class="w-[22%] px-4 py-3">Personal</th>
                        <th class="w-[14%] px-4 py-3">Usuario</th>
                        <th class="w-[24%] px-4 py-3">Hospital</th>
                        <th class="w-[14%] px-4 py-3">Rol actual</th>
                        <th class="w-[10%] px-4 py-3 text-center">Estado</th>
                        <th class="w-[16%] px-4 py-3 text-right">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse ($eligiblePersonnel as $personnel)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900">
                                {{ trim($personnel->name . ' ' . $personnel->lastname) }}
                            </td>
                            <td class="px-4 py-3">{{ $personnel->username }}</td>
                            <td class="px-4 py-3">{{ $personnel->hospital?->name ?? 'Sin hospital' }}</td>
                            <td class="px-4 py-3">
                                {{ $personnel->roles->pluck('name')->join(', ') ?: 'Sin rol' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-semibold {{ $personnel->is_active ? 'bg-emerald-100 text-emerald-800' : 'bg-gray-200 text-gray-700' }}">
                                    <span class="h-2 w-2 rounded-full {{ $personnel->is_active ? 'bg-emerald-500' : 'bg-gray-500' }}"></span>
                                    {{ $personnel->is_active ? 'Activo' : 'Bloqueado' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <form method="POST"
                                    action="{{ route('admin.superadministrator.personnel.appoint', $personnel) }}"
                                    onsubmit="return confirm('¿Nombrar administrador a este personal? Se activará su cuenta y recibirá el rol de administrador.');">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit"
                                        class="inline-flex items-center gap-2 rounded-md bg-blue-700 px-3 py-2 text-xs font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-200">
                                        <i class="fa-solid fa-user-shield" aria-hidden="true"></i>
                                        Nombrar administrador
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-8 text-center text-gray-500">
                                No hay personal elegible para nombrar.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</x-admin-layout>
