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
                    value="{{ old('name', $role->name) }}" />
            </div>
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

            <div class="flex">
                <x-button>
                    Actualizar rol
                </x-button>
            </div>
        </form>
    </div>
</x-admin-layout>
