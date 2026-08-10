<x-admin-layout>
    <div class="mt-2">
        <h1 class="text-2xl font-medium text-gray-800">Lista de Usuarios</h1>
    </div>

    <div class="flex justify-end mb-4">
        <a class="text-white bg-azul-prodifem hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 font-medium rounded-full text-sm px-5 py-2.5 text-center me-2 mb-2 dark:bg-blue-600 dark:hover:bg-azul-prodifem dark:focus:ring-blue-800"
            href="{{ route('admin.users.create') }}"><i class="fa-solid fa-plus pr-1"></i> Agregar</a>
    </div>


    <div class="relative overflow-x-auto">
        <table class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                <tr>
                    <th scope="col" class="px-6 py-3">
                        Id
                    </th>

                    <th scope="col" class="px-6 py-3">
                        Nombre
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Nombre de usuario
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Hospital
                    </th>
                    <th scope="col" class="px-6 py-3">
                        Roles
                    </th>
                    <th scope="col" class="px-6 py-3">

                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    @foreach ($user->roles as $role)
                        @hasanyrole('Admin')
                            @if (in_array($role->name, ['Cliente', 'Institucion'], true))
                                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                    <th scope="row"
                                        class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                        {{ $user->id }}
                                    </th>
                                    <td class="px-6 py-4">
                                        {{ $user->name }} {{ $user->lastname }}
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ $user->username }}
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ $user->hospital?->name ?? 'Sin hospital asignado' }}
                                    </td>
                                    <td>
                                        @foreach ($user->roles as $role)
                                            {{ $role->name === 'Cliente' ? 'Institucion' : $role->name }}
                                    @endforeach
                                </td>
                                <td class="px-6 py-4">
                                    <x-row-actions>
                                        <a class=""
                                            href="{{ route('admin.users.edit', $user) }}"> <i
                                                    class="fa-solid fa-pen pr-1"></i> Editar</a>
                                    </x-row-actions>
                                </td>
                                </tr>
                            @endif
                        @endhasanyrole
                        @hasanyrole('Super Admin')
                            <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                                <th scope="row"
                                    class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white">
                                    {{ $user->id }}
                                </th>
                                <td class="px-6 py-4">
                                    {{ $user->name }} {{ $user->lastname }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ $user->username }}
                                </td>
                                <td class="px-6 py-4">
                                    {{ $user->hospital?->name ?? 'Sin hospital asignado' }}
                                </td>
                                <td>
                                    @foreach ($user->roles as $role)
                                        {{ $role->name === 'Cliente' ? 'Institucion' : $role->name }}
                                    @endforeach
                                </td>
                                <td class="px-6 py-4">
                                    <x-row-actions>
                                        <a class=""
                                            href="{{ route('admin.users.edit', $user) }}"> <i
                                                class="fa-solid fa-pen pr-1"></i> Editar</a>
                                    </x-row-actions>
                                </td>
                            </tr>
                        @endhasanyrole
                    @endforeach
                @endforeach
            </tbody>
        </table>
        <div class="mt-4">
            {{ $users->links() }}
        </div>

    </div>




</x-admin-layout>
