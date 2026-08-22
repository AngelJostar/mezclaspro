<x-admin-layout>
    <div class="rounded-lg bg-white p-5 shadow-sm md:p-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="text-2xl font-medium text-gray-900">Crear nuevo almacén</h1>
                <p class="mt-1 text-sm text-gray-500">Vincula el almacén con la central de mezclas que abastece sus inventarios.</p>
            </div>
            <a href="{{ route('admin.warehouses.index', ['laboratory_id' => $selectedLaboratoryId]) }}"
                class="inline-flex items-center justify-center gap-2 rounded border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                Volver a almacenes
            </a>
        </div>

        <form method="POST" action="{{ route('admin.warehouses.store') }}" class="mt-5 max-w-4xl border-t border-gray-200 pt-4">
            @csrf

            <div class="grid grid-cols-1 gap-x-4 gap-y-3 md:grid-cols-2">
                <div>
                    <label for="laboratory_id" class="mb-1 block text-xs font-medium text-gray-700">Central de mezclas *</label>
                    <select id="laboratory_id" name="laboratory_id" required
                        class="h-9 w-full rounded border-gray-300 py-1 text-sm focus:border-blue-500 focus:ring-blue-500">
                        @foreach ($laboratories as $laboratory)
                            <option value="{{ $laboratory->id }}" @selected((string) old('laboratory_id', $selectedLaboratoryId) === (string) $laboratory->id)>
                                {{ $laboratory->nombre }}
                            </option>
                        @endforeach
                    </select>
                    @error('laboratory_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="name" class="mb-1 block text-xs font-medium text-gray-700">Nombre del almacén *</label>
                    <input id="name" type="text" name="name" value="{{ old('name') }}" required
                        class="h-9 w-full rounded border-gray-300 px-3 py-1 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div>
                    <label for="state" class="mb-1 block text-xs font-medium text-gray-700">Ciudad / Estado</label>
                    <input id="state" type="text" name="state" value="{{ old('state') }}"
                        class="h-9 w-full rounded border-gray-300 px-3 py-1 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('state')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="md:col-span-2">
                    <label for="address" class="mb-1 block text-xs font-medium text-gray-700">Dirección</label>
                    <input id="address" type="text" name="address" value="{{ old('address') }}"
                        class="h-9 w-full rounded border-gray-300 px-3 py-1 text-sm focus:border-blue-500 focus:ring-blue-500">
                    @error('address')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <label class="inline-flex items-center gap-2 text-sm text-gray-700">
                    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))
                        class="rounded border-gray-300 text-blue-700 focus:ring-blue-500">
                    Almacén activo
                </label>
            </div>

            <div class="mt-5 flex justify-end gap-2">
                <a href="{{ route('admin.warehouses.index', ['laboratory_id' => $selectedLaboratoryId]) }}"
                    class="rounded border border-gray-300 bg-white px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancelar</a>
                <button type="submit" class="rounded bg-green-600 px-5 py-2 text-sm font-semibold text-white hover:bg-green-700">
                    Crear almacén
                </button>
            </div>
        </form>
    </div>
</x-admin-layout>
