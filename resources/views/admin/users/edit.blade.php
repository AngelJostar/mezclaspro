<x-admin-layout>
    <div class="mt-2 mb-4">
        <h1 class="text-2xl font-medium text-gray-800">Editar Usario</h1>
    </div>
    <form action="{{ route('admin.users.update', $user) }}" method="POST" class="bg-white rounded-lg p-6 shadow-lg">
        @csrf
        @method('PUT')
        <x-validation-errors class="mb-4" />

        <div class="mb-4">
            <x-label class="mb-2">
                Nombre
            </x-label>
            <x-input value="{{ old('name', $user->name) }}" name="name" class="w-full"
                placeholder="Escriba el nombre del usuario" />
        </div>
        <div class="mb-4">
            <x-label class="mb-2">
                Apellidos
            </x-label>
            <x-input value="{{ old('lastname', $user->lastname) }}" name="lastname" class="w-full"
                placeholder="Escriba los apellido del usuario" />
        </div>
        <div id="username-section" class="mb-4 scroll-mt-6 rounded-md border border-slate-200 bg-slate-50 p-4">
            <x-label class="mb-2">
                Nombre de usuario
            </x-label>
            <x-input value="{{ old('username', $user->username) }}" name="username" class="w-full"
                placeholder="Escriba el nombre del usuario" />
        </div>
        <div id="password-section" class="mb-4 scroll-mt-6 rounded-md border border-slate-200 bg-slate-50 p-4">
            <x-label class="mb-2">
                Nueva contrase&ntilde;a
            </x-label>
            <x-input type="password" value="" name="password" class="w-full"
                placeholder="Escriba una nueva contrase&ntilde;a" autocomplete="new-password" />
            <p class="mt-2 text-xs text-slate-500">
                La contrase&ntilde;a actual esta protegida y no puede mostrarse. Deje este campo vacio para conservarla.
            </p>
        </div>
        <div class="mb-4">
            <x-label class="mb-2">
                Confirmar nueva contrase&ntilde;a
            </x-label>
            <x-input type="password" value="" name="password_confirmation" class="w-full"
                placeholder="Repita la nueva contrase&ntilde;a" autocomplete="new-password" />
        </div>
        <div class="mb-4">
            <x-label class="mb-2">
                Hospital
            </x-label>
            <x-select class="w-full" name="hospital_id">
                @foreach ($hospitals as $hospital)
                    <option @selected(old('hospital_id', $user->hospital_id) == $hospital->id) value="{{ $hospital->id }}">{{ $hospital->name }}</option>
                @endforeach
            </x-select>
        </div>
        <div class="mb-4">
            <x-label class="mb-2" for="warehouse_id">
                Almac&eacute;n asignado
            </x-label>
            <x-select id="warehouse_id" class="w-full" name="warehouse_id">
                <option value="">Sin almac&eacute;n asignado</option>
                @foreach ($warehouses as $warehouse)
                    <option @selected((string) old('warehouse_id', $user->warehouse_id) === (string) $warehouse->id) value="{{ $warehouse->id }}">
                        {{ $warehouse->name }} · {{ $warehouse->laboratory?->nombre ?? 'Sin laboratorio' }}{{ $warehouse->is_active ? '' : ' (Inactivo)' }}
                    </option>
                @endforeach
            </x-select>
        </div>
        <div class="mb-4">
            <ul>
                @foreach ($roles as $role)
                    <li>
                        <label>
                            <input type="checkbox" name="roles[]" value="{{ $role->id }}"
                                class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500"
                                @disabled(! $canManageRoles)
                                @if (in_array($role->id, old('roles', $user->roles()->pluck('id')->toArray()))) checked @endif>
                            {{ $role->name }}
                        </label>
                    </li>
                @endforeach
            </ul>
        </div>
        <div class="flex justify-end">
            <x-button>
                Editar usuario
            </x-button>
        </div>
    </form>
</x-admin-layout>
