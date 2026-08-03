<x-admin-layout>

    <div class="mt-2">
        <h1 class="text-2xl font-medium text-gray-800">Lista de Solicitudes</h1>
    </div>

    {{-- ERRORES --}}
    @if ($errors->any())
        <div class="mt-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
            <ul class="list-disc list-inside">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- MENSAJES SWEETALERT --}}
    @if (session('swal'))
        @push('js')
            <script>
                Swal.fire(@json(session('swal')));
            </script>
        @endpush
    @endif

    <div class="flex flex-wrap justify-end mt-4">
        <div class="mb-4">
            <a class="text-white bg-azul-prodifem hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 font-medium rounded-full text-sm px-5 py-2.5 text-center me-2 mb-2"
                href="{{ route('admin.oncologicos.solicitudes.create') }}">
                <i class="fa-solid fa-plus pr-1"></i> Agregar
            </a>
        </div>

        <div class="mb-4">
            <a href="{{ route('admin.oncologicos.solicitudes.exportar') }}"
                target="_blank"
                class="text-white bg-green-600 hover:bg-green-800 focus:outline-none focus:ring-4 focus:ring-green-300 font-medium rounded-full text-sm px-5 py-2.5 text-center me-2 mb-2">
                <i class="fa-solid fa-file-excel pr-1"></i> Exportar a Excel
            </a>
        </div>
    </div>

    <div class="relative overflow-x-auto">
        <livewire:oncologicos.solicitudes-table />
    </div>

</x-admin-layout>
