<x-admin-layout>
    <div class="mt-2">
        <h1 class="text-2xl font-medium text-gray-800">Medicamentos</h1>
    </div>

    <div class="flex justify-end mb-4">
        <a class="text-white bg-azul-prodifem hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 font-medium rounded-full text-sm px-5 py-2.5 text-center me-2 mb-2"
            href="{{ route('admin.oncologicos.infusores.index') }}">
            Infusores
        </a>

        <a class="text-white bg-azul-prodifem hover:bg-blue-800 focus:outline-none focus:ring-4 focus:ring-blue-300 font-medium rounded-full text-sm px-5 py-2.5 text-center me-2 mb-2"
            href="{{ route('admin.oncologicos.medicines.catalog.create') }}">
            <i class="fa-solid fa-plus pr-1"></i> Agregar nuevo Medicamento
        </a>
    </div>

    <div class="relative overflow-x-auto">
        <livewire:oncologicos.medicines-catalog-table />
    </div>

    @push('js')
        @if (session('success'))
            <script>
                Swal.fire({
                    icon: 'success',
                    title: '¡Éxito!',
                    text: '{{ session('success') }}',
                    customClass: {
                        confirmButton: 'swal-button-confirm',
                        cancelButton: 'swal-button-cancel'
                    }
                });
            </script>
        @endif

        <script>
            // ✅ Importante: Livewire re-renderiza, así que usamos "document" y delegación
            document.addEventListener('submit', function(e) {
                const form = e.target;
                if (!form.classList.contains('form-eliminar')) return;

                e.preventDefault();

                Swal.fire({
                    title: '¿Estás seguro?',
                    text: "Este medicamento será deshabilitado.",
                    icon: 'warning',
                    showCancelButton: true,
                    customClass: {
                        confirmButton: 'swal-button-confirm',
                        cancelButton: 'swal-button-cancel'
                    },
                    confirmButtonText: 'Sí, deshabilitar',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            }, true);
        </script>
    @endpush
</x-admin-layout>
