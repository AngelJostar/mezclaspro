<x-admin-layout>
    <div class="mx-auto max-w-7xl rounded-lg bg-white p-5 shadow-sm md:p-6">
        <header class="mb-6 flex flex-col gap-4 border-b border-gray-200 pb-5 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <nav class="mb-2 text-xs font-medium text-gray-500">
                    <a href="{{ route('admin.suppliers.index') }}" class="hover:text-blue-700">Proveedores</a>
                    <span class="mx-2 text-gray-300">/</span>
                    <a href="{{ route('admin.suppliers.show', $supplier) }}" class="hover:text-blue-700">{{ $supplier->name }}</a>
                    <span class="mx-2 text-gray-300">/</span>
                    <span class="text-blue-700">Editar</span>
                </nav>
                <h1 class="text-2xl font-bold text-gray-950">Editar proveedor</h1>
                <p class="mt-1 text-sm text-gray-500">Actualiza la informacion fiscal, comercial y de contacto.</p>
            </div>
            <a href="{{ route('admin.suppliers.show', $supplier) }}"
                class="inline-flex h-10 items-center justify-center rounded-md border border-gray-300 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                Volver a la ficha
            </a>
        </header>

        <form method="POST" action="{{ route('admin.suppliers.update', $supplier) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('admin.suppliers.partials.form')

            <div class="mt-6 flex justify-end gap-2 border-t border-gray-200 pt-4">
                <a href="{{ route('admin.suppliers.show', $supplier) }}"
                    class="inline-flex h-10 items-center justify-center rounded-md border border-gray-300 px-4 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancelar</a>
                <button type="submit"
                    class="inline-flex h-10 items-center justify-center gap-2 rounded-md bg-blue-800 px-5 text-sm font-semibold text-white hover:bg-blue-900">
                    <i class="fa-solid fa-check" aria-hidden="true"></i>
                    Guardar cambios
                </button>
            </div>
        </form>
    </div>
</x-admin-layout>
