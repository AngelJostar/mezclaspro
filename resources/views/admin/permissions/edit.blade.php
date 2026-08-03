<x-admin-layout>
    <div class="bg-white shadow rounded-lg p-6">
        <form action="{{ route('admin.permissions.update', $permission) }}" method="POST">
            @csrf
            @method('PUT')
            <x-validation-errors class="mb-4" />
            <div class="mb-4">
                <x-label class="mb-1">
                    Nombre del permiso
                </x-label>
                <x-input name="name" class="w-full" aria-placeholder="Ingrese el nombre del permiso"
                    value="{{ old('name', $permission->name) }}" />
            </div>

            <div class="flex">
                <x-button>
                    Actualizar permiso
                </x-button>
            </div>
        </form>
    </div>
</x-admin-layout>
