<x-admin-layout>
    <div class="mt-2">
        <div class="flex items-center justify-between mb-4">
            <h1 class="text-2xl font-semibold text-gray-800">Diluyentes</h1>
            <a href="{{ route('admin.oncologicos.diluents.create') }}"
                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                + Nuevo diluyente
            </a>
        </div>

        @if (session('success'))
            <div class="mb-4 p-3 rounded bg-green-100 text-green-800">
                {{ session('success') }}
            </div>
        @endif

        <div class="bg-white rounded shadow overflow-x-auto">
            <livewire:oncologicos.diluents-table />
        </div>

        @push('js')
            <script>
                // ✅ Delegación para que funcione con Livewire al paginar/buscar
                document.addEventListener('submit', function(e) {
                    const form = e.target;
                    if (!form.classList.contains('form-eliminar-diluent')) return;

                    e.preventDefault();

                    Swal.fire({
                        title: '¿Eliminar este diluyente?',
                        text: "Esta acción no se puede deshacer.",
                        icon: 'warning',
                        showCancelButton: true,
                        customClass: {
                            confirmButton: 'swal-button-confirm',
                            cancelButton: 'swal-button-cancel'
                        },
                        confirmButtonText: 'Sí, eliminar',
                        cancelButtonText: 'Cancelar'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                }, true);
            </script>
        @endpush
    </div>
</x-admin-layout>
