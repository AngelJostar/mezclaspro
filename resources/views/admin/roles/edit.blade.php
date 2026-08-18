<x-admin-layout>
    <div class="bg-white shadow rounded-lg p-6">
        <form action="{{ route('admin.roles.update', $role) }}" method="POST">
            @csrf
            @method('PUT')
            <x-validation-errors class="mb-4" />
            <div class="mb-4">
                <x-label class="mb-1">
                    Nombre del rol
                </x-label>
                <x-input name="name" class="w-full" aria-placeholder="Ingrese el nombre del Rol"
                    value="{{ old('name', $role->name) }}"
                    @disabled(in_array($role->name, ['Capacitacion', 'Administracion y facturacion'], true)) />
            </div>
            @if (in_array($role->name, ['Capacitacion', 'Administracion y facturacion'], true))
                <div class="mb-4 rounded-md border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-800">
                    @if ($role->name === 'Capacitacion')
                        Acceso exclusivo al menu de capacitaciones.
                    @else
                        Acceso exclusivo a los menus de Administracion y Facturacion.
                    @endif
                    Este rol no admite permisos adicionales.
                </div>
            @else
            <div class="mb-4">
                <ul>
                    @foreach ($permissions as $permission)
                        <li>
                           <label for="">
                            <x-checkbox type="checkbox"
                                name="permissions[]"
                                value="{{$permission->id}}"
                                :checked="in_array($permission->id, old('permissions', $role->permissions()->pluck('id')->toArray()))"/>
                                {{$permission->name}}
                           </label>
                        </li>
                    @endforeach
                </ul>
            </div>
            @endif

            <div class="flex">
                <x-button>
                    Actualizar rol
                </x-button>
            </div>
        </form>
    </div>
</x-admin-layout>
