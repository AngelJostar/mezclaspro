<x-admin-layout>
    <div class="mt-2 mb-4">
        <h1 class="text-2xl font-medium text-gray-800">Agregar nueva Institucion</h1>
    </div>

    <form action="{{ route('admin.instituciones.store') }}" method="POST" class="bg-white rounded-lg p-6 shadow-lg">
        @csrf

        <x-validation-errors class="mb-4" />

        <div class="mb-4">
            <x-label class="mb-2">
                Nombre
            </x-label>
            <x-input value="{{ old('nombre') }}" name="nombre" class="w-full"
                placeholder="Escriba el nombre de la institucion" />
        </div>

        <div class="mb-4">
            <x-label class="mb-2">
                Razon social
            </x-label>
            <x-input value="{{ old('razon_social') }}" name="razon_social" class="w-full"
                placeholder="Escriba la razon social de la institucion" />
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <x-label class="mb-2">
                    RFC
                </x-label>
                <x-input value="{{ old('rfc') }}" name="rfc" class="w-full"
                    placeholder="Escriba el RFC de la institucion" />
            </div>

            <div>
                <x-label class="mb-2">
                    Telefono
                </x-label>
                <x-input value="{{ old('telefono') }}" name="telefono" class="w-full"
                    placeholder="Escriba el telefono de la institucion" />
            </div>
        </div>

        <div class="flex justify-end">
            <x-button>
                Crear institucion
            </x-button>
        </div>
    </form>
</x-admin-layout>
