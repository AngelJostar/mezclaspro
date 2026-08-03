<x-admin-layout>
    <div class="mt-2">
        <h1 class="text-2xl font-medium text-gray-800">
            Presentaciones de: {{ $catalog->denominacion }}
        </h1>
        <div class="mt-2">
            <a href="{{ route('admin.oncologicos.medicines.catalog.index') }}"
               class="text-sm text-blue-600 hover:underline">&larr; Volver a medicamentos</a>
        </div>
    </div>


    <div class="flex justify-end mb-4">
        <a class="text-white bg-azul-prodifem hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 font-medium rounded-full text-sm px-5 py-2.5"
            href="{{ route('admin.oncologicos.medicines.catalog.presentations.create', ['catalog' => $catalog->id]) }}">
            <i class="fa-solid fa-plus pr-1"></i> Agregar presentación
        </a>
    </div>

    @if (session('success') || session('swal.text'))
        <div class="mb-4 p-3 rounded bg-green-100 text-green-800">
            {{ session('swal.text') ?? session('success') }}
        </div>
    @endif

    <div class="relative overflow-x-auto">
        <table class="w-full text-sm text-left text-gray-500">
            <thead class="text-xs text-gray-700 uppercase bg-gray-50">
                <tr>
                    <th class="px-6 py-3">ID</th>
                    <th class="px-6 py-3">Marca</th>
                    <th class="px-6 py-3">Presentación</th>
                    <th class="px-6 py-3">Estado</th>
                    <th class="px-6 py-3">Acciones</th>
                </tr>
            </thead>

            <tbody>
                @forelse ($presentations as $p)
                    @php
                        $vigente = $p->batches->first(); // por el with()->latest()
                    @endphp
                    <tr class="border-b {{ $p->is_available ? 'bg-white' : 'bg-gray-50 opacity-75' }}">
                        <td class="px-6 py-4">{{ $p->id }}</td>
                        <td class="px-6 py-4">{{ $p->marca ?? '—' }}</td>

                        <td class="px-6 py-4">
                            {{ $p->presentacion }}
                            @if ($p->contenido_valor && $p->contenido_unidad)
                                — {{ rtrim(rtrim(number_format($p->contenido_valor, 2, '.', ''), '0'), '.') }}
                                {{ $p->contenido_unidad }}
                            @endif
                        </td>

                        <td class="px-6 py-4">
                            @if ($p->is_available)
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded bg-green-100 text-green-700">
                                    Activa
                                </span>
                            @else
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded bg-red-100 text-red-700">
                                    Deshabilitada
                                </span>
                            @endif
                        </td>

                        <td class="px-6 py-4">
                            <x-row-actions>
                                <a href="{{ route('admin.oncologicos.medicines.catalog.presentations.edit', [$catalog->id, $p->id]) }}"
                                    class="">
                                    <i class="fas fa-edit mr-1"></i> Editar
                                </a>

                                @if ($p->is_available)
                                    <form
                                        action="{{ route('admin.oncologicos.medicines.catalog.presentations.destroy', [$catalog->id, $p->id]) }}"
                                        method="POST" class="inline-block form-deshabilitar">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="action-danger">
                                            <i class="fas fa-trash-alt mr-1"></i> Deshabilitar
                                        </button>
                                    </form>
                                @else
                                    <form
                                        action="{{ route('admin.oncologicos.medicines.catalog.presentations.restore', [$catalog->id, $p->id]) }}"
                                        method="POST" class="inline-block form-habilitar">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit">
                                            <i class="fas fa-check mr-1"></i> Habilitar
                                        </button>
                                    </form>
                                @endif
                            </x-row-actions>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                            No hay presentaciones registradas.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @push('js')
        <script>
            document.addEventListener('submit', function(e) {
                const form = e.target;
                const isDisable = form.classList.contains('form-deshabilitar');
                const isEnable = form.classList.contains('form-habilitar');

                if (!isDisable && !isEnable) {
                    return;
                }

                e.preventDefault();

                Swal.fire({
                    title: isDisable ? 'Deshabilitar presentacion' : 'Habilitar presentacion',
                    text: isDisable ?
                        'La presentacion dejara de estar disponible para nuevas operaciones.' :
                        'La presentacion volvera a estar disponible.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: isDisable ? 'Si, deshabilitar' : 'Si, habilitar',
                    cancelButtonText: 'Cancelar',
                    customClass: {
                        confirmButton: 'swal-button-confirm',
                        cancelButton: 'swal-button-cancel'
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            }, true);
        </script>
    @endpush
</x-admin-layout>
