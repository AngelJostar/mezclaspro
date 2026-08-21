<x-admin-layout>
    <div class="mx-auto max-w-7xl rounded-lg bg-white p-5 shadow-sm md:p-6">
        <header class="mb-5 flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <nav class="mb-2 text-xs font-medium text-gray-500">
                    <a href="{{ route('admin.suppliers.index') }}" class="hover:text-blue-700">Proveedores</a>
                    <span class="mx-2 text-gray-300">/</span>
                    <span class="text-blue-700">Alta</span>
                </nav>
                <h1 class="text-2xl font-bold text-gray-950">Nuevo proveedor</h1>
                <p class="mt-1 text-sm text-gray-500">Registra la informaci&oacute;n comercial, de contacto y bancaria del proveedor.</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.suppliers.index') }}"
                    class="inline-flex h-9 items-center justify-center rounded-md border border-gray-300 px-5 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancelar</a>
                <button type="submit" form="supplier-create-form"
                    class="inline-flex h-9 items-center justify-center gap-2 rounded-md bg-blue-700 px-5 text-sm font-semibold text-white hover:bg-blue-800">
                    <i class="fa-solid fa-check" aria-hidden="true"></i>
                    Guardar proveedor
                </button>
            </div>
        </header>

        <form id="supplier-create-form" method="POST" action="{{ route('admin.suppliers.store') }}" enctype="multipart/form-data">
            @csrf
            @include('admin.suppliers.partials.form')

            <div class="mt-4 flex flex-col gap-3 border-t border-gray-200 pt-4 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-gray-500">Los campos marcados con <span class="text-red-600">*</span> son obligatorios.</p>
                <div class="flex justify-end gap-2">
                    <a href="{{ route('admin.suppliers.index') }}"
                        class="inline-flex h-9 items-center justify-center rounded-md border border-gray-300 px-5 text-sm font-semibold text-gray-700 hover:bg-gray-50">Cancelar</a>
                    <button type="submit"
                        class="inline-flex h-9 items-center justify-center gap-2 rounded-md bg-blue-700 px-5 text-sm font-semibold text-white hover:bg-blue-800">
                        <i class="fa-solid fa-check" aria-hidden="true"></i>
                        Guardar proveedor
                    </button>
                </div>
            </div>
        </form>
    </div>
</x-admin-layout>
